<?php

namespace App\Http\Controllers;

use App\Models\Document;
use App\Services\AuditLogger;
use App\Services\DocumentAccessService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

final class DocumentController extends Controller
{
    public function download(
        Request $request,
        Document $document,
        DocumentAccessService $access,
        AuditLogger $audit,
    ): StreamedResponse {
        $actor = $request->user()->loadMissing('employee');

        if (! $access->canDownload($actor, $document)) {
            $audit->record(
                $actor,
                'document.download',
                'Denied protected Core Portal evidence download.',
                outcome: 'denied',
                entityType: 'document',
                entityId: $document->id,
            );

            throw new AuthorizationException('You are not authorized to download this evidence.');
        }

        $disk = (string) config('documents.disk', 'documents');
        if ($document->storage_disk !== $disk
            || ! $document->storage_path
            || ! Storage::disk($disk)->exists($document->storage_path)) {
            abort(404);
        }

        $audit->record(
            $actor,
            'document.download',
            'Downloaded protected Core Portal evidence.',
            entityType: 'document',
            entityId: $document->id,
        );

        return Storage::disk($disk)->download(
            $document->storage_path,
            $document->original_name ?: $document->title,
            [
                'Content-Type' => $document->mime_type ?: 'application/octet-stream',
                'Cache-Control' => 'private, no-store',
                'X-Content-Type-Options' => 'nosniff',
            ],
        );
    }
}
