<?php

namespace App\Http\Controllers;

use App\Models\Document;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DocumentDownloadController extends Controller
{
    public function __invoke(Request $request, Document $document): StreamedResponse
    {
        $this->authorize('download', $document);

        $disk = $document->storageDisk();
        $resolvedPath = $this->resolvePathForDisk($disk, (string) $document->file_path);

        abort_unless($resolvedPath !== null, 404);

        return Storage::disk($disk)->download($resolvedPath, $document->original_name);
    }

    private function resolvePathForDisk(string $disk, string $filePath): ?string
    {
        $normalized = str_replace('\\', '/', trim($filePath));

        if ($normalized === '') {
            return null;
        }

        if (filter_var($normalized, FILTER_VALIDATE_URL)) {
            $urlPath = (string) parse_url($normalized, PHP_URL_PATH);
            $normalized = trim($urlPath, '/');
        }

        $candidates = array_values(array_unique(array_filter([
            ltrim($normalized, '/'),
            ltrim(urldecode($normalized), '/'),
            ltrim(preg_replace('#^storage/#', '', $normalized) ?? '', '/'),
            ltrim(preg_replace('#^public/#', '', $normalized) ?? '', '/'),
        ])));

        foreach ($candidates as $candidate) {
            if (Storage::disk($disk)->exists($candidate)) {
                return $candidate;
            }
        }

        return null;
    }
}
