<?php

namespace App\Actions\Admin;

use App\Models\Genre;
use App\Services\AuditLogger;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class SaveGenreAction
{
    public function __construct(private AuditLogger $audit) {}

    /** @param array{name: string} $data */
    public function execute(array $data, ?Genre $genre = null): Genre
    {
        return DB::transaction(function () use ($data, $genre): Genre {
            $genre = $genre ? Genre::query()->lockForUpdate()->findOrFail($genre->id) : new Genre;
            $before = $genre->exists ? $this->audit->snapshot($genre) : null;
            $genre->fill([
                'name' => $data['name'],
                'slug' => $this->uniqueSlug($data['name'], $genre),
            ])->save();
            $this->audit->log($before === null ? 'genre.created' : 'genre.updated', $genre, $before, $this->audit->snapshot($genre));

            return $genre->refresh();
        });
    }

    private function uniqueSlug(string $name, Genre $genre): string
    {
        $base = Str::slug($name);
        $slug = $base;
        $suffix = 2;
        while (Genre::query()->where('slug', $slug)->when($genre->exists, fn ($query) => $query->whereKeyNot($genre->id))->exists()) {
            $slug = $base.'-'.$suffix++;
        }

        return $slug;
    }
}
