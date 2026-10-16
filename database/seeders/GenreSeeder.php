<?php

namespace Database\Seeders;

use App\Models\Genre;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class GenreSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        foreach (['Action', 'Comedy', 'Drama', 'Sci-Fi', 'Family'] as $name) {
            Genre::updateOrCreate(
                ['slug' => Str::slug($name)],
                ['name' => $name],
            );
        }
    }
}
