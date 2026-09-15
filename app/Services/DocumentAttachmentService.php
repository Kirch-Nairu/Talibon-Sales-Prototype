<?php

namespace App\Services;

use App\Models\Document;
use App\Models\DocumentLink;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use RuntimeException;
use Throwable;

final class DocumentAttachmentService
{
    private const RELATIONSHIPS = [
        'supporting_document',
        'route_evidence',
        'action_evidence',
        'response_attachment',
        'photo_evidence',
    ];

    private const CLASSIFICATIONS = [
        'public',
        'internal',
        'confidential',
        'restricted',
    ];

    public function __construct(private readonly AuditLogger $audit)
    {
    }

    /** @param array<int, UploadedFile> $files */
    public function assertValidUploads(array $files): void
    {
        $maxFiles = max(1, (int) config('documents.max_files_per_operation', 5));
        if (count($files) > $maxFiles) {
            throw ValidationException::withMessages([
                'evidence' => "No more than {$maxFiles} evidence files may be uploaded in one operation.",
            ]);
        }

        $maxBytes = max(1, (int) config('documents.max_upload_mb', 15)) * 1024 * 1024;

        foreach (array_values($files) as $index => $file) {
            if (! $file instanceof UploadedFile || ! $file->isValid()) {
                throw ValidationException::withMessages([
                    "evidence.$index" => 'The evidence upload is invalid.',
                ]);
            }

            if ((int) $file->getSize() > $maxBytes) {
                throw ValidationException::withMessages([
                    "evidence.$index" => 'The evidence upload exceeds the configured maximum size.',
                ]);
            }

            $this->descriptor($file, $index);
        }
    }

    /**
     * @param array<int, UploadedFile> $files
     * @param array<int, array{model:Model,relationship:string}> $links
     * @return array<int, Document>
     */
    public function attach(
        User $actor,
        array $files,
        array $links,
        string $classification = 'internal',
    ): array {
        if ($files === []) {
            return [];
        }

        $this->assertLinks($links);
        $classification = $this->classification($classification);
        $actor->loadMissing('employee');

        if (! $actor->employee?->department_id) {
            throw ValidationException::withMessages([
                'evidence' => 'An active employee office is required to upload evidence.',
            ]);
        }

        $disk = (string) config('documents.disk', 'documents');
        $storedPaths = [];
        $documents = [];

        try {
            foreach (array_values($files) as $index => $file) {
                if (! $file instanceof UploadedFile || ! $file->isValid()) {
                    throw ValidationException::withMessages([
                        "evidence.$index" => 'The evidence upload is invalid.',
                    ]);
                }

                $descriptor = $this->descriptor($file, $index);
                $path = now()->format('Y/m').'/'.Str::uuid().'.'.$descriptor['extension'];
                $stream = fopen((string) $file->getRealPath(), 'rb');

                if ($stream === false) {
                    throw new RuntimeException('Unable to open evidence upload for protected storage.');
                }

                try {
                    $stored = Storage::disk($disk)->put($path, $stream);
                } finally {
                    fclose($stream);
                }

                if (! $stored) {
                    throw new RuntimeException('Unable to write evidence upload to protected storage.');
                }

                $storedPaths[] = $path;
                $originalName = $this->safeOriginalName($file);
                $relationships = array_values(array_unique(array_column($links, 'relationship')));

                $document = Document::query()->create([
                    'title' => $originalName,
                    'document_type' => $relationships[0],
                    'classification' => $classification,
                    'original_name' => $originalName,
                    'mime_type' => $descriptor['mime'],
                    'size_bytes' => (int) $file->getSize(),
                    'storage_disk' => $disk,
                    'storage_path' => $path,
                    'checksum_sha256' => $descriptor['checksum'],
                    'owner_department_id' => (int) $actor->employee->department_id,
                    'uploaded_by_user_id' => (int) $actor->id,
                    'metadata' => [
                        'source' => 'core_portal_evidence',
                        'original_extension' => $descriptor['extension'],
                        'relationships' => $relationships,
                    ],
                ]);

                foreach ($links as $link) {
                    DocumentLink::query()->create([
                        'document_id' => $document->id,
                        'linkable_type' => $link['model']->getMorphClass(),
                        'linkable_id' => $link['model']->getKey(),
                        'relationship' => $link['relationship'],
                        'created_by_user_id' => $actor->id,
                    ]);
                }

                $this->audit->record(
                    $actor,
                    'document.uploaded',
                    'Uploaded protected Core Portal evidence.',
                    entityType: 'document',
                    entityId: $document->id,
                );

                $documents[] = $document;
            }

            return $documents;
        } catch (Throwable $exception) {
            foreach ($storedPaths as $path) {
                Storage::disk($disk)->delete($path);
            }

            throw $exception;
        }
    }

    /** @param iterable<Document> $documents */
    public function cleanupDocuments(iterable $documents): void
    {
        foreach ($documents as $document) {
            if ($document->storage_path && $document->storage_disk) {
                Storage::disk($document->storage_disk)->delete($document->storage_path);
            }
        }
    }

    /** @param array<int, array{model:Model,relationship:string}> $links */
    private function assertLinks(array $links): void
    {
        if ($links === []) {
            throw new RuntimeException('Evidence requires at least one authoritative parent link.');
        }

        foreach ($links as $link) {
            if (! isset($link['model'], $link['relationship']) || ! $link['model'] instanceof Model) {
                throw new RuntimeException('Evidence parent link is invalid.');
            }

            if (! in_array($link['relationship'], self::RELATIONSHIPS, true)) {
                throw new RuntimeException('Evidence relationship is not allowed.');
            }
        }
    }

    private function classification(string $classification): string
    {
        if (! in_array($classification, self::CLASSIFICATIONS, true)) {
            throw new RuntimeException('Evidence classification is not supported.');
        }

        return $classification;
    }

    /** @return array{extension:string,mime:string,checksum:string} */
    private function descriptor(UploadedFile $file, int $index): array
    {
        $extension = Str::lower($file->getClientOriginalExtension());
        $allowed = config('documents.allowed_extensions', []);

        if (! is_array($allowed) || ! in_array($extension, $allowed, true)) {
            throw ValidationException::withMessages([
                "evidence.$index" => 'Evidence must be PDF, DOCX, JPEG, PNG, or WebP.',
            ]);
        }

        $realPath = $file->getRealPath();
        if (! is_string($realPath) || $realPath === '') {
            throw ValidationException::withMessages([
                "evidence.$index" => 'The uploaded evidence cannot be inspected.',
            ]);
        }

        $contents = file_get_contents($realPath);
        if (! is_string($contents)) {
            throw ValidationException::withMessages([
                "evidence.$index" => 'The uploaded evidence cannot be inspected.',
            ]);
        }

        $detectedMime = Str::lower((string) ($file->getMimeType() ?: 'application/octet-stream'));
        $canonicalMime = match ($extension) {
            'pdf' => $this->assertPdf($contents, $detectedMime, $index),
            'docx' => $this->assertDocx($contents, $detectedMime, $index),
            'jpg', 'jpeg' => $this->assertJpeg($contents, $detectedMime, $index),
            'png' => $this->assertPng($contents, $detectedMime, $index),
            'webp' => $this->assertWebp($contents, $detectedMime, $index),
            default => throw ValidationException::withMessages([
                "evidence.$index" => 'Evidence file type is not supported.',
            ]),
        };

        $checksum = hash_file('sha256', $realPath);
        if (! is_string($checksum)) {
            throw ValidationException::withMessages([
                "evidence.$index" => 'The uploaded evidence checksum could not be calculated.',
            ]);
        }

        return [
            'extension' => $extension,
            'mime' => $canonicalMime,
            'checksum' => $checksum,
        ];
    }

    private function assertPdf(string $contents, string $mime, int $index): string
    {
        $this->assertMime($mime, ['application/pdf', 'application/x-pdf', 'application/octet-stream'], $index);

        if (! str_starts_with($contents, '%PDF-')) {
            $this->invalidContent($index);
        }

        return 'application/pdf';
    }

    private function assertDocx(string $contents, string $mime, int $index): string
    {
        $this->assertMime($mime, [
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'application/zip',
            'application/x-zip-compressed',
            'application/octet-stream',
        ], $index);

        if (! str_starts_with($contents, "PK\x03\x04")
            || ! str_contains($contents, '[Content_Types].xml')
            || ! str_contains($contents, 'word/document.xml')) {
            $this->invalidContent($index);
        }

        return 'application/vnd.openxmlformats-officedocument.wordprocessingml.document';
    }

    private function assertJpeg(string $contents, string $mime, int $index): string
    {
        $this->assertMime($mime, ['image/jpeg', 'image/pjpeg', 'application/octet-stream'], $index);

        if (substr($contents, 0, 3) !== "\xFF\xD8\xFF") {
            $this->invalidContent($index);
        }

        return 'image/jpeg';
    }

    private function assertPng(string $contents, string $mime, int $index): string
    {
        $this->assertMime($mime, ['image/png', 'application/octet-stream'], $index);

        if (substr($contents, 0, 8) !== "\x89PNG\r\n\x1A\n") {
            $this->invalidContent($index);
        }

        return 'image/png';
    }

    private function assertWebp(string $contents, string $mime, int $index): string
    {
        $this->assertMime($mime, ['image/webp', 'application/octet-stream'], $index);

        if (substr($contents, 0, 4) !== 'RIFF' || substr($contents, 8, 4) !== 'WEBP') {
            $this->invalidContent($index);
        }

        return 'image/webp';
    }

    /** @param array<int, string> $allowed */
    private function assertMime(string $mime, array $allowed, int $index): void
    {
        if (! in_array($mime, $allowed, true)) {
            throw ValidationException::withMessages([
                "evidence.$index" => 'The uploaded evidence content does not match an allowed file type.',
            ]);
        }
    }

    private function invalidContent(int $index): never
    {
        throw ValidationException::withMessages([
            "evidence.$index" => 'The uploaded evidence content does not match its file type.',
        ]);
    }

    private function safeOriginalName(UploadedFile $file): string
    {
        $name = basename(str_replace('\\', '/', $file->getClientOriginalName()));
        $name = trim($name) !== '' ? trim($name) : 'evidence-file';

        return Str::limit($name, 255, '');
    }
}
