<?php

/**
 * Extra mock data for the staff reference lookup (on top of staff-bookings-mock.php).
 *
 * Temporary: delete this file once tickets (seat type, token, used time) come from the database.
 */
return [
    // Seat rows sold as premium.
    'premium_rows' => ['G', 'H'],
    // When a scanned ticket was admitted, unless listed below.
    'default_used_at' => '2026-10-15 18:45',
    // Scan time per ticket (in seat order), by booking reference.
    'used_at' => [
        'CB-261015-0482' => ['2026-10-15 18:58'],
    ],
];
