<?php

namespace App\Contracts\Staff;

use DateTimeImmutable;

/**
 * Everything the staff screens read. MockStaffData reads the staff-mock config
 * files, EloquentStaffData reads the database; both return exactly these shapes,
 * so the screens, services and views never know which one is in use.
 */
interface StaffData
{
    /** The current moment (a fixed time in the mock). */
    public function now(): DateTimeImmutable;

    /** Short venue name shown in "Today at ...". */
    public function venue(): string;

    /**
     * Scheduled shows on the given day, earliest first.
     *
     * @return list<array{id: int, movie: string, screen: string, starts_at: string, sold: int, scanned: int}>
     */
    public function shows(DateTimeImmutable $day): array;

    /**
     * Confirmed bookings for shows on the given day, for the roster.
     *
     * @return list<array{ref: string, name: string, show_id: int, seats: list<string>, scanned: int}>
     */
    public function rosterBookings(DateTimeImmutable $day): array;

    /**
     * Recent scan attempts for shows on the given day, newest first, keyed by show id.
     *
     * @return array<int, list<array{time: string, result: string, seat: string|null, ticket: string|null, used_at: string|null}>>
     */
    public function recentAttempts(DateTimeImmutable $day): array;

    /**
     * One confirmed booking by its (upper case) reference, or null.
     * "show" is the booking's show; "tickets" are its tickets.
     *
     * @return array{ref: string, name: string, show: array{id: int, movie: string, screen: string, starts_at: string}, tickets: list<array{seat: string, seat_type: string, code: string, used_at: DateTimeImmutable|null}>}|null
     */
    public function findBooking(string $reference): ?array;
}
