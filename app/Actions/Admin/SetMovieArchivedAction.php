<?php

namespace App\Actions\Admin;

use App\Enums\MovieStatus;
use App\Models\Movie;
use App\Services\AuditLogger;
use Illuminate\Support\Facades\DB;

class SetMovieArchivedAction
{
    public function __construct(private AuditLogger $audit) {}

    public function execute(Movie $movie, bool $archived): Movie
    {
        return DB::transaction(function () use ($movie, $archived): Movie {
            $movie = Movie::query()->lockForUpdate()->findOrFail($movie->id);
            $before = $this->audit->snapshot($movie);
            $movie->forceFill(['status' => $archived ? MovieStatus::Archived : MovieStatus::ComingSoon])->save();
            $this->audit->log($archived ? 'movie.archived' : 'movie.restored', $movie, $before, $this->audit->snapshot($movie));

            return $movie->refresh();
        });
    }
}
