<?php

namespace App\Actions\Admin;

use App\Models\Genre;
use App\Services\AuditLogger;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class DeleteGenreAction
{
    public function __construct(private AuditLogger $audit) {}

    public function execute(Genre $genre): void
    {
        DB::transaction(function () use ($genre): void {
            $genre = Genre::query()->lockForUpdate()->findOrFail($genre->id);
            if ($genre->movies()->exists()) {
                throw ValidationException::withMessages(['genre' => 'This genre is assigned to movies and cannot be deleted.']);
            }

            $before = $this->audit->snapshot($genre);
            $genre->delete();
            $this->audit->log('genre.deleted', $genre, $before, null);
        });
    }
}
