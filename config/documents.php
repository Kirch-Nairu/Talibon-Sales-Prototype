<?php

return [
    'disk' => 'documents',
    'max_upload_mb' => (int) env('DOCUMENT_MAX_UPLOAD_MB', 15),
    'max_files_per_operation' => (int) env('DOCUMENT_MAX_FILES_PER_OPERATION', 5),
    'allowed_extensions' => [
        'pdf',
        'docx',
        'jpg',
        'jpeg',
        'png',
        'webp',
    ],
];
