<?php

namespace App\Traits;

use App\Models\Document;
use Illuminate\Database\Eloquent\Relations\MorphMany;

trait HasDocuments
{
    public function documents(): MorphMany
    {
        return $this->morphMany(Document::class, 'documentable');
    }

    public function documentsStorageDirectory(): string
    {
        $type = class_basename($this);

        return match ($type) {
            'Avalista' => "documents/avalistas/{$this->getKey()}",
            'Locador' => "documents/locadores/{$this->getKey()}",
            'Locatario' => "documents/locatarios/{$this->getKey()}",
            default => "documents/" . str($type)->plural()->kebab() . "/{$this->getKey()}",
        };
    }
}
