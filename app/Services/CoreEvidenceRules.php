<?php

namespace App\Services;

final class CoreEvidenceRules
{
    /** @return array<string, array<int, string>> */
    public static function rules(string $field = 'evidence'): array
    {
        $maxFiles = max(1, (int) config('documents.max_files_per_operation', 5));
        $maxKilobytes = max(1, (int) config('documents.max_upload_mb', 15)) * 1024;

        return [
            $field => ['nullable', 'array', 'max:'.$maxFiles],
            $field.'.*' => ['file', 'max:'.$maxKilobytes],
        ];
    }
}
