<?php

namespace App\Services\Staff;

use DateTimeImmutable;

/**
 * Builds the data behind the staff "Booking reference lookup" screen.
 *
 * It only works on plain arrays, so the data source (mock config now,
 * database later) can change without touching this class.
 *
 * A booking passed in must have this shape (confirmed bookings only):
 *   ref, name, show_id, tickets: list<{seat, seat_type, code, used_at: ?DateTimeImmutable}>
 */
class BookingLookupService
{
    public const MAX_REFERENCE_LENGTH = 40;

    public const STATE_IDLE = 'idle';

    public const STATE_FOUND = 'found';

    public const STATE_NOT_FOUND = 'not_found';

    /**
     * @param  list<array<string, mixed>>  $bookings  confirmed bookings, see the shape above
     * @param  list<array<string, mixed>>  $shows  rows from TodayShowsService::build()
     * @return array{state: string, reference: string, booking: array<string, mixed>|null}
     */
    public function build(string $reference, array $bookings, array $shows): array
    {
        $reference = $this->normalize($reference);

        if ($reference === '') {
            return ['state' => self::STATE_IDLE, 'reference' => '', 'booking' => null];
        }

        $booking = $this->findBooking($bookings, $reference);
        $show = $booking ? $this->findShow($shows, $booking['show_id']) : null;

        if (! $booking || ! $show) {
            return ['state' => self::STATE_NOT_FOUND, 'reference' => $reference, 'booking' => null];
        }

        return [
            'state' => self::STATE_FOUND,
            'reference' => $reference,
            'booking' => $this->toView($booking, $show),
        ];
    }

    /**
     * Staff may type spaces or lower case; references are stored upper case.
     */
    public function normalize(string $reference): string
    {
        $clean = strtoupper(preg_replace('/\s+/', '', $reference) ?? '');

        return mb_substr($clean, 0, self::MAX_REFERENCE_LENGTH);
    }

    /**
     * @param  list<array<string, mixed>>  $bookings
     * @return array<string, mixed>|null
     */
    private function findBooking(array $bookings, string $reference): ?array
    {
        foreach ($bookings as $booking) {
            if (strtoupper($booking['ref']) === $reference) {
                return $booking;
            }
        }

        return null;
    }

    /**
     * @param  list<array<string, mixed>>  $shows
     * @return array<string, mixed>|null
     */
    private function findShow(array $shows, int $showId): ?array
    {
        foreach ($shows as $show) {
            if ($show['id'] === $showId) {
                return $show;
            }
        }

        return null;
    }

    /**
     * @param  array<string, mixed>  $booking
     * @param  array<string, mixed>  $show
     * @return array<string, mixed>
     */
    private function toView(array $booking, array $show): array
    {
        $tickets = array_map($this->ticketRow(...), $booking['tickets']);
        $scanned = count(array_filter($booking['tickets'], fn (array $t): bool => $t['used_at'] !== null));

        return [
            'ref' => $booking['ref'],
            'name' => $booking['name'],
            'show_id' => $show['id'],
            'movie' => $show['movie'],
            'screen' => $show['screen'],
            'date' => $show['starts_at']->format('M j, Y'),
            'time' => $show['time'],
            'window' => $show['window'],
            'seats' => count($tickets),
            'scanned' => $scanned,
            'tickets' => $tickets,
        ];
    }

    /**
     * @param  array{seat: string, seat_type: string, code: string, used_at: DateTimeImmutable|null}  $ticket
     * @return array{seat: string, code: string, admission: string, used: bool}
     */
    private function ticketRow(array $ticket): array
    {
        $usedAt = $ticket['used_at'];

        return [
            'seat' => $ticket['seat'].' · '.$ticket['seat_type'],
            'code' => $ticket['code'],
            'admission' => $usedAt ? 'Used · '.$usedAt->format('M j \a\t H:i') : 'Not scanned',
            'used' => $usedAt !== null,
        ];
    }
}
