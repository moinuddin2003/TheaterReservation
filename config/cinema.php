<?php

return [
    'brand' => 'cinebook',
    'venue' => 'CineBook Downtown',
    'featured_movie' => 'the-last-horizon',
    'movies' => [
        'the-last-horizon' => [
            'slug' => 'the-last-horizon',
            'title' => 'The Last Horizon',
            'status' => 'Now showing',
            'rating' => 'PG-13',
            'runtime_minutes' => 124,
            'genres' => ['Sci-Fi', 'Drama'],
            'synopsis' => 'When a distant signal breaks years of silence, astronaut Mara Vale crosses an uncharted ocean world to find its source. What waits beyond the horizon could bring her home — or change the meaning of home forever.',
            'images' => [
                'cover' => 'images/movies/the-last-horizon-cover.jpg',
                'poster' => 'images/movies/the-last-horizon-poster.jpg',
            ],
            'showtimes' => [
                [
                    'date' => '2026-10-15',
                    'weekday_short' => 'THU',
                    'date_short' => 'Oct 15',
                    'date_label' => 'Thursday, October 15',
                    'seat_date_label' => 'Thursday, October 15, 2026',
                    'screen' => 'Screen 1',
                    'screenings' => [
                        ['time' => '14:30', 'closed' => true, 'available_seats' => 0],
                        ['time' => '19:30', 'sales_close' => '19:15', 'available_seats' => 44, 'featured' => true],
                        ['time' => '22:00', 'sales_close' => '21:45', 'available_seats' => 65],
                    ],
                ],
                [
                    'date' => '2026-10-16',
                    'weekday_short' => 'FRI',
                    'date_short' => 'Oct 16',
                    'date_label' => 'Friday, October 16',
                    'seat_date_label' => 'Friday, October 16, 2026',
                    'screen' => 'Screen 1',
                    'screenings' => [
                        ['time' => '19:30', 'sales_close' => '19:15', 'available_seats' => 44, 'featured' => true],
                        ['time' => '22:00', 'sales_close' => '21:45', 'available_seats' => 65],
                    ],
                ],
                [
                    'date' => '2026-10-17',
                    'weekday_short' => 'SAT',
                    'date_short' => 'Oct 17',
                    'date_label' => 'Saturday, October 17',
                    'seat_date_label' => 'Saturday, October 17, 2026',
                    'screen' => 'Screen 1',
                    'screenings' => [
                        ['time' => '19:30', 'sales_close' => '19:15', 'available_seats' => 44, 'featured' => true],
                        ['time' => '22:00', 'sales_close' => '21:45', 'available_seats' => 65],
                    ],
                ],
                [
                    'date' => '2026-10-18',
                    'weekday_short' => 'SUN',
                    'date_short' => 'Oct 18',
                    'date_label' => 'Sunday, October 18',
                    'seat_date_label' => 'Sunday, October 18, 2026',
                    'screen' => 'Screen 1',
                    'screenings' => [
                        ['time' => '19:30', 'sales_close' => '19:15', 'available_seats' => 44, 'featured' => true],
                        ['time' => '22:00', 'sales_close' => '21:45', 'available_seats' => 65],
                    ],
                ],
            ],
            'seat_map' => [
                'rows' => [
                    'A' => 'standard',
                    'B' => 'standard',
                    'C' => 'standard',
                    'D' => 'standard',
                    'E' => 'standard',
                    'F' => 'standard',
                    'G' => 'premium',
                    'H' => 'premium',
                ],
                'seats_per_row' => 12,
                'prices' => [
                    'standard' => 10,
                    'premium' => 12,
                ],
                'max_selection' => 6,
                'held' => ['D5', 'D6'],
                'sold' => ['C2', 'C3', 'F12'],
                'disabled' => ['A6'],
                'selected' => ['G7', 'G8'],
            ],
        ],
    ],
];
