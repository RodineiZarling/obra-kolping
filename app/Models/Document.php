<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Facades\Storage;

class Document extends Model
{
    protected $fillable = [
        'document_type',
        'titulo',
        'original_name',
        'file_path',
        'mime_type',
        'file_size',
        'uploaded_by',
    ];

    protected $casts = [
        'file_size' => 'integer',
    ];

    public function displayTitle(): string
    {
        return $this->titulo ?: $this->original_name;
    }

    public function documentable(): MorphTo
    {
        return $this->morphTo();
    }

    public function uploadedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function storageDisk(): string
    {
        return config('documents.disk', config('filesystems.default', 'local'));
    }

    public function isViewable(): bool
    {
        return in_array($this->mime_type, [
            'application/pdf',
            'image/jpg',
            'image/jpeg',
            'image/png',
            'image/webp',
        ]);
    }

    protected static function booted(): void
    {
        static::deleting(function (self $document) {
            $disk = $document->storageDisk();

            if ($document->file_path && Storage::disk($disk)->exists($document->file_path)) {
                Storage::disk($disk)->delete($document->file_path);
            }
        });
    }
}
