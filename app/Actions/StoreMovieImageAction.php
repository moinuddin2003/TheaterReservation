<?php

namespace App\Actions;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class StoreMovieImageAction
{
    /**
     * Store an uploaded movie image on the public disk and return its path.
     */
    public function store(?UploadedFile $file): ?string
    {
        return $file?->store('movies', 'public');
    }

    /**
     * Delete a storage-managed image, leaving static public assets untouched.
     */
    public function delete(?string $path): void
    {
        if ($path === null || $path === '' || str_starts_with($path, '/')) {
            return;
        }

        Storage::disk('public')->delete($path);
    }
}
