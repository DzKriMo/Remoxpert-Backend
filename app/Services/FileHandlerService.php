<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class FileHandlerService
{
    /**
     * Store a single file in private storage
     */
    public function storeFile(UploadedFile $file, string $path): string
    {
        return $file->store($path, 'private');
    }

    /**
     * Store multiple files in private storage
     */
    public function storeFiles(array $files, string $path): array
    {
        $paths = [];
        foreach ($files as $file) {
            $paths[] = $this->storeFile($file, $path);
        }
        return $paths;
    }

    /**
     * Delete a file from private storage
     */
    public function deleteFile(?string $path): void
    {
        if ($path && Storage::disk('private')->exists($path)) {
            Storage::disk('private')->delete($path);
        }
    }

    /**
     * Get a file from private storage
     */
    public function getFile(string $path)
    {
        if (Storage::disk('private')->exists($path)) {
            return Storage::disk('private')->download($path);
        }
        return null;
    }
}