<?php

use App\Enums\MovieStatus;

/*
|--------------------------------------------------------------------------
| Seed movie data
|--------------------------------------------------------------------------
|
| Fictional sample movies. Each entry is the single place to edit a movie's
| details. Swap the `poster`, `backdrop` and `trailer` values for real URLs
| whenever you like - no code changes needed. `backdrop` is the wide hero image
| (home banner / movie detail); if omitted, a wide placeholder is generated.
| `genres` refers to genre slugs.
|
*/

return [
    [
        'title' => 'The Last Projectionist',
        'slug' => 'the-last-projectionist',
        'synopsis' => 'An ageing projectionist fights to keep his beloved single-screen cinema alive as the town around it changes forever.',
        'duration_minutes' => 118,
        'rating' => 'PG-13',
        'release_date' => now()->subDays(21)->toDateString(),
        'status' => MovieStatus::NowShowing,
        'genres' => ['drama'],
        'poster' => 'https://placehold.co/600x900/1e293b/ffffff?text=The+Last+Projectionist',
        'backdrop' => 'https://placehold.co/1600x900/0b1622/ffffff?text=The+Last+Projectionist',
    ],
    [
        'title' => 'Neon Horizon',
        'slug' => 'neon-horizon',
        'synopsis' => 'A courier races across a rain-soaked megacity to deliver a package that could topple a corporate empire.',
        'duration_minutes' => 132,
        'rating' => 'PG-13',
        'release_date' => now()->subDays(14)->toDateString(),
        'status' => MovieStatus::NowShowing,
        'genres' => ['sci-fi', 'action'],
        'poster' => 'https://placehold.co/600x900/1e293b/ffffff?text=Neon+Horizon',
        'backdrop' => 'https://placehold.co/1600x900/0b1622/ffffff?text=Neon+Horizon',
    ],
    [
        'title' => 'Whiskers and Wonders',
        'slug' => 'whiskers-and-wonders',
        'synopsis' => 'A curious house cat and a clumsy puppy set off on a big adventure to find their way home.',
        'duration_minutes' => 92,
        'rating' => 'G',
        'release_date' => now()->subDays(10)->toDateString(),
        'status' => MovieStatus::NowShowing,
        'genres' => ['family', 'comedy'],
        'poster' => 'https://placehold.co/600x900/1e293b/ffffff?text=Whiskers+and+Wonders',
        'backdrop' => 'https://placehold.co/1600x900/0b1622/ffffff?text=Whiskers+and+Wonders',
    ],
    [
        'title' => 'Midnight Cargo',
        'slug' => 'midnight-cargo',
        'synopsis' => 'A dockworker discovers that the shipment he just loaded is worth killing for.',
        'duration_minutes' => 108,
        'rating' => 'R',
        'release_date' => now()->subDays(7)->toDateString(),
        'status' => MovieStatus::NowShowing,
        'genres' => ['action'],
        'poster' => 'https://placehold.co/600x900/1e293b/ffffff?text=Midnight+Cargo',
        'backdrop' => 'https://placehold.co/1600x900/0b1622/ffffff?text=Midnight+Cargo',
    ],
    [
        'title' => 'The Quiet Orchard',
        'slug' => 'the-quiet-orchard',
        'synopsis' => 'Three estranged siblings return to their family orchard and must decide what to save and what to let go.',
        'duration_minutes' => 125,
        'rating' => 'PG',
        'release_date' => now()->subDays(3)->toDateString(),
        'status' => MovieStatus::NowShowing,
        'genres' => ['drama'],
        'poster' => 'https://placehold.co/600x900/1e293b/ffffff?text=The+Quiet+Orchard',
        'backdrop' => 'https://placehold.co/1600x900/0b1622/ffffff?text=The+Quiet+Orchard',
    ],
    [
        'title' => 'Galaxy Rovers',
        'slug' => 'galaxy-rovers',
        'synopsis' => 'A scrappy crew of young explorers chart the far edges of the galaxy in a ship held together by hope.',
        'duration_minutes' => 104,
        'rating' => 'PG',
        'release_date' => now()->addDays(12)->toDateString(),
        'status' => MovieStatus::ComingSoon,
        'genres' => ['sci-fi', 'family'],
        'poster' => 'https://placehold.co/600x900/1e293b/ffffff?text=Galaxy+Rovers',
        'backdrop' => 'https://placehold.co/1600x900/0b1622/ffffff?text=Galaxy+Rovers',
    ],
    [
        'title' => 'Laugh Track',
        'slug' => 'laugh-track',
        'synopsis' => 'A washed-up sitcom writer gets one last shot at prime time, if his cast can stop sabotaging him.',
        'duration_minutes' => 99,
        'rating' => 'PG-13',
        'release_date' => now()->addDays(20)->toDateString(),
        'status' => MovieStatus::ComingSoon,
        'genres' => ['comedy'],
        'poster' => 'https://placehold.co/600x900/1e293b/ffffff?text=Laugh+Track',
        'backdrop' => 'https://placehold.co/1600x900/0b1622/ffffff?text=Laugh+Track',
    ],
    [
        'title' => 'Crimson Divide',
        'slug' => 'crimson-divide',
        'synopsis' => 'Two old friends find themselves on opposite sides of a border conflict that will define them both.',
        'duration_minutes' => 141,
        'rating' => 'R',
        'release_date' => now()->addDays(28)->toDateString(),
        'status' => MovieStatus::ComingSoon,
        'genres' => ['drama', 'action'],
        'poster' => 'https://placehold.co/600x900/1e293b/ffffff?text=Crimson+Divide',
        'backdrop' => 'https://placehold.co/1600x900/0b1622/ffffff?text=Crimson+Divide',
    ],
];
