<?php

namespace App\Services\Staff;

use App\Contracts\Staff\StaffData;
use DateTimeImmutable;

/**
 * Staff screen data from the staff-mock*.php config files (see StaffData for the shapes).
 * Delete it, with those files, once the mock-based tests are moved to the database.
 */
class MockStaffData implements StaffData
{
    public function now(): DateTimeImmutable
    {
        return new DateTimeImmutable(config('staff-mock.now'));
    }

    public function venue(): string
    {
        return config('staff-mock.venue');
    }

    public function shows(DateTimeImmutable $day): array
    {
        return config('staff-mock.shows');
    }

    public function rosterBookings(DateTimeImmutable $day): array
    {
        return config('staff-bookings-mock.bookings');
    }

    public function recentAttempts(DateTimeImmutable $day): array
    {
        return config('staff-mock.scan_attempts');
    }

    public function findBooking(string $reference): ?array
    {
        $bookings = MockLookupBookings::fromConfig()->toLookupBookings(config('staff-bookings-mock.bookings'));

        foreach ($bookings as $booking) {
            $show = $this->showWithId($booking['show_id']);

            if ($show && strtoupper($booking['ref']) === $reference) {
                return ['ref' => $booking['ref'], 'name' => $booking['name'], 'show' => $show, 'tickets' => $booking['tickets']];
            }
        }

        return null;
    }

    /**
     * @return array{id: int, movie: string, screen: string, starts_at: string}|null
     */
    private function showWithId(int $id): ?array
    {
        foreach (config('staff-mock.shows') as $show) {
            if ($show['id'] === $id) {
                return ['id' => $show['id'], 'movie' => $show['movie'], 'screen' => $show['screen'], 'starts_at' => $show['starts_at']];
            }
        }

        return null;
    }
}
