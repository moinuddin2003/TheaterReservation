<?php

use App\Enums\MovieStatus;

/*
|--------------------------------------------------------------------------
| Seed movie data
|--------------------------------------------------------------------------
|
| Real blockbuster movies with high-resolution generated and official posters
| and wide backdrop banners. Each entry is the single source of truth for
| seeding movie details, genres, posters, and backdrops.
|
*/

return [
    [
        'title' => 'Interstellar',
        'slug' => 'interstellar',
        'synopsis' => 'When Earth becomes uninhabitable in the future, a farmer and ex-NASA pilot, Joseph Cooper, is tasked to pilot a spacecraft along with a team of researchers to find a new planet for humans.',
        'duration_minutes' => 169,
        'rating' => 'PG-13',
        'release_date' => now()->subDays(28)->toDateString(),
        'status' => MovieStatus::NowShowing,
        'genres' => ['sci-fi', 'drama'],
        'poster' => '/images/movies/interstellar-poster.jpg',
        'backdrop' => '/images/movies/interstellar-backdrop.jpg',
    ],
    [
        'title' => 'Inception',
        'slug' => 'inception',
        'synopsis' => 'A thief who steals corporate secrets through the use of dream-sharing technology is given the inverse task of planting an idea into the mind of a C.E.O.',
        'duration_minutes' => 148,
        'rating' => 'PG-13',
        'release_date' => now()->subDays(21)->toDateString(),
        'status' => MovieStatus::NowShowing,
        'genres' => ['sci-fi', 'action'],
        'poster' => '/images/movies/inception-poster.jpg',
        'backdrop' => '/images/movies/inception-backdrop.jpg',
    ],
    [
        'title' => 'The Dark Knight',
        'slug' => 'the-dark-knight',
        'synopsis' => 'When the menace known as the Joker wreaks havoc and chaos on the people of Gotham, Batman must accept one of the greatest psychological and physical tests of his ability to fight injustice.',
        'duration_minutes' => 152,
        'rating' => 'PG-13',
        'release_date' => now()->subDays(14)->toDateString(),
        'status' => MovieStatus::NowShowing,
        'genres' => ['action', 'drama'],
        'poster' => '/images/movies/the-dark-knight-poster.jpg',
        'backdrop' => '/images/movies/the-dark-knight-backdrop.jpg',
    ],
    [
        'title' => 'Oppenheimer',
        'slug' => 'oppenheimer',
        'synopsis' => 'The story of American scientist J. Robert Oppenheimer and his role in the development of the atomic bomb during World War II.',
        'duration_minutes' => 180,
        'rating' => 'R',
        'release_date' => now()->subDays(7)->toDateString(),
        'status' => MovieStatus::NowShowing,
        'genres' => ['drama'],
        'poster' => '/images/movies/oppenheimer-poster.jpg',
        'backdrop' => '/images/movies/oppenheimer-backdrop.jpg',
    ],
    [
        'title' => 'Inside Out 2',
        'slug' => 'inside-out-2',
        'synopsis' => 'Joy, Sadness, Anger, Fear and Disgust navigate the turbulence of teenage life when new emotions Anxiety, Envy, Ennui, and Embarrassment show up at headquarters.',
        'duration_minutes' => 96,
        'rating' => 'PG',
        'release_date' => now()->subDays(3)->toDateString(),
        'status' => MovieStatus::NowShowing,
        'genres' => ['family', 'comedy'],
        'poster' => '/images/movies/inside-out-2-poster.jpg',
        'backdrop' => '/images/movies/inside-out-2-backdrop.jpg',
    ],
    [
        'title' => 'Dune: Part Two',
        'slug' => 'dune-part-two',
        'synopsis' => 'Paul Atreides unites with Chani and the Fremen while seeking revenge against the conspirators who destroyed his family, facing a choice between the love of his life and the fate of the universe.',
        'duration_minutes' => 166,
        'rating' => 'PG-13',
        'release_date' => now()->addDays(10)->toDateString(),
        'status' => MovieStatus::ComingSoon,
        'genres' => ['sci-fi', 'action'],
        'poster' => '/images/movies/dune-part-two-poster.jpg',
        'backdrop' => '/images/movies/dune-part-two-backdrop.jpg',
    ],
    [
        'title' => 'Spider-Man: Across the Spider-Verse',
        'slug' => 'spider-man-across-the-spider-verse',
        'synopsis' => 'Miles Morales catapults across the Multiverse, where he encounters a team of Spider-People charged with protecting its very existence, but must redefine what it means to be a hero.',
        'duration_minutes' => 140,
        'rating' => 'PG',
        'release_date' => now()->addDays(18)->toDateString(),
        'status' => MovieStatus::ComingSoon,
        'genres' => ['action', 'family'],
        'poster' => '/images/movies/spider-man-across-the-spider-verse-poster.jpg',
        'backdrop' => '/images/movies/spider-man-across-the-spider-verse-backdrop.jpg',
    ],
    [
        'title' => 'Gladiator II',
        'slug' => 'gladiator-ii',
        'synopsis' => 'Years after witnessing the death of the revered hero Maximus, Lucius must enter the Colosseum after his home is conquered by the tyrannical Emperors who now lead Rome.',
        'duration_minutes' => 148,
        'rating' => 'R',
        'release_date' => now()->addDays(25)->toDateString(),
        'status' => MovieStatus::ComingSoon,
        'genres' => ['action', 'drama'],
        'poster' => '/images/movies/gladiator-ii-poster.jpg',
        'backdrop' => '/images/movies/gladiator-ii-backdrop.jpg',
    ],
];
