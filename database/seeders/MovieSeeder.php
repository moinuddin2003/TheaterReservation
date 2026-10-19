<?php

namespace Database\Seeders;

use App\Models\Genre;
use App\Models\Movie;
use Illuminate\Database\Seeder;

class MovieSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        /** @var array<int, array<string, mixed>> $movies */
        $movies = require database_path('seeders/data/movies.php');

        foreach ($movies as $data) {
            $movie = Movie::firstOrNew(['slug' => $data['slug']]);

            $movie->fill([
                'title' => $data['title'],
                'synopsis' => $data['synopsis'],
                'duration_minutes' => $data['duration_minutes'],
                'rating' => $data['rating'],
                'release_date' => $data['release_date'],
                'poster_path' => $data['poster'] ?? $this->placeholderPoster($data['title']),
                'backdrop_path' => $data['backdrop'] ?? $this->placeholderBackdrop($data['title']),
                'trailer_url' => $data['trailer'] ?? config('cinebook.sample_trailer_url'),
            ]);

            $movie->status = $data['status'];
            $movie->save();

            $movie->genres()->sync(
                Genre::whereIn('slug', $data['genres'])->pluck('id'),
            );
        }
    }

    /**
     * Build a placeholder poster URL for a title.
     */
    private function placeholderPoster(string $title): string
    {
        return 'https://placehold.co/600x900/1e293b/ffffff?text='.urlencode($title);
    }

    /**
     * Build a placeholder backdrop URL for a title.
     */
    private function placeholderBackdrop(string $title): string
    {
        return 'https://placehold.co/1600x900/0b1622/ffffff?text='.urlencode($title);
    }
}
