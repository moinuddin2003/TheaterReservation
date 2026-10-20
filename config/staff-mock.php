<?php

/**
 * Mock data for the staff screens.
 *
 * Temporary: delete this file once the staff pages read from the database.
 */
return [
    'venue' => 'Downtown',
    'now' => '2026-10-15 18:55',
    // The time of a mock scan (shown on the admitted result screen).
    'scan_at' => '2026-10-15 18:58:00',
    'user' => ['name' => 'Sam Rivera'],
    'shows' => [
        ['id' => 1, 'movie' => 'The Last Horizon', 'screen' => 'Screen 1', 'starts_at' => '2026-10-15 14:30', 'sold' => 38, 'scanned' => 38],
        ['id' => 2, 'movie' => 'The Paper Kingdom', 'screen' => 'Screen 2 IMAX', 'starts_at' => '2026-10-15 15:00', 'sold' => 40, 'scanned' => 40],
        ['id' => 3, 'movie' => 'Night Circuit', 'screen' => 'Screen 2 IMAX', 'starts_at' => '2026-10-15 18:00', 'sold' => 72, 'scanned' => 50],
        ['id' => 4, 'movie' => 'The Last Horizon', 'screen' => 'Screen 1', 'starts_at' => '2026-10-15 19:30', 'sold' => 45, 'scanned' => 18],
        ['id' => 5, 'movie' => 'Signal Zero · preview', 'screen' => 'Screen 2 IMAX', 'starts_at' => '2026-10-15 20:30', 'sold' => 41, 'scanned' => 0],
        ['id' => 6, 'movie' => 'Side by Side', 'screen' => 'Screen 1', 'starts_at' => '2026-10-15 22:00', 'sold' => 28, 'scanned' => 0],
    ],
    // Tickets that can be scanned, keyed by token. On the scan screen, type DEMO-ADMIT-G7 to see an admission.
    'tickets' => [
        'DEMO-ADMIT-G7' => ['show_id' => 4, 'seat' => 'G7', 'seat_type' => 'Premium', 'code' => 'T-0482-01'],
    ],
    // Scan attempts per show id, shown on the scan screen.
    'scan_attempts' => [
        4 => [
            ['time' => '18:54:58', 'result' => 'admitted', 'seat' => 'F6', 'ticket' => 'T-0478-02'],
            ['time' => '18:54:42', 'result' => 'already_used', 'used_at' => '18:48', 'seat' => 'D5', 'ticket' => 'T-0471-01'],
            ['time' => '18:54:20', 'result' => 'admitted', 'seat' => 'F5', 'ticket' => 'T-0478-01'],
            ['time' => '18:53:56', 'result' => 'admitted', 'seat' => 'E8', 'ticket' => 'T-0476-02'],
            ['time' => '18:53:40', 'result' => 'wrong_showtime', 'seat' => null, 'ticket' => null],
            ['time' => '18:53:10', 'result' => 'admitted', 'seat' => 'E7', 'ticket' => 'T-0476-01'],
            ['time' => '18:52:48', 'result' => 'admitted', 'seat' => 'A4', 'ticket' => 'T-0473-01'],
            ['time' => '18:52:12', 'result' => 'invalid', 'seat' => null, 'ticket' => null],
            ['time' => '18:51:46', 'result' => 'admitted', 'seat' => 'B2', 'ticket' => 'T-0470-02'],
            ['time' => '18:51:28', 'result' => 'admitted', 'seat' => 'B1', 'ticket' => 'T-0470-01'],
        ],
    ],
];
