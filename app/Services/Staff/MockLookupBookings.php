<?php

namespace App\Services\Staff;

use DateTimeImmutable;

/**
 * Stand-in for the database while the staff screens use mock data.
 * Turns the roster mock bookings into the shape BookingLookupService expects.
 * Delete it, together with config/staff-lookup-mock.php and its use in
 * LookupController, once bookings and tickets come from the models.
 */
class MockLookupBookings
{
    /**
     * @param  list<string>  $premiumRows  seat rows that are premium
     * @param  array<string, list<string>>  $usedAt  scan time per ticket, keyed by booking reference
     */
    public function __construct(
        private readonly array $premiumRows,
        private readonly array $usedAt,
        private readonly string $defaultUsedAt,
    ) {
    }

    public static function fromConfig(): self
    {
        return new self(
            config('staff-lookup-mock.premium_rows'),
            config('staff-lookup-mock.used_at'),
            config('staff-lookup-mock.default_used_at'),
        );
    }

    /**
     * @param  list<array{ref: string, name: string, show_id: int, seats: list<string>, scanned: int}>  $bookings
     * @return list<array<string, mixed>>
     */
    public function toLookupBookings(array $bookings): array
    {
        return array_map(fn (array $booking): array => [
            'ref' => $booking['ref'],
            'name' => $booking['name'],
            'show_id' => $booking['show_id'],
            'tickets' => $this->tickets($booking),
        ], $bookings);
    }

    /**
     * The first `scanned` seats count as already admitted.
     *
     * @param  array{ref: string, seats: list<string>, scanned: int}  $booking
     * @return list<array<string, mixed>>
     */
    private function tickets(array $booking): array
    {
        $tickets = [];

        foreach ($booking['seats'] as $index => $seat) {
            $usedAt = $this->usedAt[$booking['ref']][$index] ?? $this->defaultUsedAt;

            $tickets[] = [
                'seat' => $seat,
                'seat_type' => in_array($seat[0], $this->premiumRows, true) ? 'Premium' : 'Standard',
                'code' => sprintf('T-%s-%02d', substr($booking['ref'], -4), $index + 1),
                'used_at' => $index < $booking['scanned'] ? new DateTimeImmutable($usedAt) : null,
            ];
        }

        return $tickets;
    }
}
