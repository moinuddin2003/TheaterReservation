<?php

namespace App\Actions\Admin;

use App\Models\Movie;
use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class SaveMovieAction
{
    public function __construct(private AuditLogger $audit) {}

    /** @param array<string, mixed> $data */
    public function execute(array $data, ?Movie $movie = null, ?User $actor = null): Movie
    {
        return DB::transaction(function () use ($data, $movie): Movie {
            $movie = $movie ? Movie::query()->lockForUpdate()->findOrFail($movie->id) : new Movie;
            $before = $movie->exists ? $this->audit->snapshot($movie) : null;
            $genres = $data['genre_ids'] ?? [];
            $removePoster = (bool) ($data['remove_poster'] ?? false);
            unset($data['genre_ids']);
            unset($data['remove_poster']);

            $title = (string) $data['title'];
            $data['slug'] = $this->uniqueSlug($title, $movie);
            $poster = $data['poster'] ?? null;
            unset($data['poster']);
            $oldPosterPath = $movie->poster_path;
            if ($poster instanceof UploadedFile) {
                $data['poster_path'] = $poster->store('posters', 'public');
            } elseif ($removePoster) {
                $data['poster_path'] = null;
            }

            $movie->fill($data)->save();
            $movie->genres()->sync($genres);
            $this->audit->log($before === null ? 'movie.created' : 'movie.updated', $movie, $before, $this->audit->snapshot($movie));

            if ($oldPosterPath && $movie->poster_path !== $oldPosterPath) {
                Storage::disk('public')->delete($oldPosterPath);
            }

            return $movie->refresh();
        });
    }

    private function uniqueSlug(string $title, Movie $movie): string
    {
        $base = Str::slug($title);
        $slug = $base;
        $suffix = 2;
        while (Movie::query()->where('slug', $slug)->when($movie->exists, fn ($query) => $query->whereKeyNot($movie->id))->exists()) {
            $slug = $base.'-'.$suffix++;
        }

        return $slug;
    }
}
