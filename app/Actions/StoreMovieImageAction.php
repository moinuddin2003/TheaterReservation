<?php

namespace App\Actions;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

class StoreMovieImageAction
{
    /**
     * Store an uploaded movie image on the public disk and return its path.
     */
    public function store(?UploadedFile $file): ?string
    {
        if ($file === null) {
            return null;
        }

        $path = $file->storePublicly('movies', 'public');

        if (! is_string($path)) {
            throw new RuntimeException('The movie image could not be saved.');
        }

        return $path;
    }

    /**
     * Delete a storage-managed image, leaving static public assets untouched.
     */
    public function delete(?string $path): void
    {
        if ($path === null || ! str_starts_with($path, 'movies/') || basename($path) !== substr($path, strlen('movies/'))) {
            return;
        }

        if (! Storage::disk('public')->delete($path)) {
            throw new RuntimeException('The stored movie image could not be deleted.');
        }
    }
}
