<?php

namespace App\Actions\Admin;

use App\Models\Movie;
use App\Services\AuditLogger;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class DeleteMovieAction
{
    public function __construct(private AuditLogger $audit) {}

    public function execute(Movie $movie): void
    {
        DB::transaction(function () use ($movie): void {
            $movie = Movie::query()->lockForUpdate()->findOrFail($movie->id);
            if ($movie->showtimes()->exists()) {
                throw ValidationException::withMessages([
                    'movie' => 'This movie has showtimes and cannot be hard-deleted. Archive it instead.',
                ]);
            }

            $before = $this->audit->snapshot($movie);
            $posterPath = $movie->poster_path;
            $movie->genres()->detach();
            $movie->delete();
            $this->audit->log('movie.deleted', $movie, $before, null);
            if ($posterPath) {
                Storage::disk('public')->delete($posterPath);
            }
        });
    }
}
