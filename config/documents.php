<?php

return [
    // Disco usado para armazenar os anexos. No projeto atual, o `local` já aponta para storage/app/private.
    'disk' => env('DOCUMENTS_DISK', env('FILESYSTEM_DISK', 'local')),

    // 10MB em KB (padrão do Filament)
    'max_size_kb' => 50 * 1024,

    'accepted_mime_types' => [
        'application/pdf',
        'image/jpg',
        'image/jpeg',
        'image/png',
        'image/webp',
    ],
];
