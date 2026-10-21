<?php

namespace App\Services\Staff;

use DateTimeImmutable;

/**
 * Builds the data behind the staff "Booking reference lookup" screen.
 * It only formats what StaffData::findBooking() returns, so the data
 * source (mock config or database) can change without touching this class.
 */
class BookingLookupService
{
    public const MAX_REFERENCE_LENGTH = 40;

    public const STATE_IDLE = 'idle';

    public const STATE_FOUND = 'found';

    public const STATE_NOT_FOUND = 'not_found';

    public function __construct(
        private readonly int $entryMinutesBefore,
        private readonly int $entryMinutesAfter,
    ) {
    }

    public static function fromConfig(): self
    {
        return new self(
            config('cinebook.scan_window_before_minutes'),
            config('cinebook.scan_window_after_minutes'),
        );
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
     * @param  string  $reference  the normalised reference ('' when nothing was searched)
     * @param  array<string, mixed>|null  $booking  from StaffData::findBooking(), null when none matched
     * @return array{state: string, reference: string, booking: array<string, mixed>|null}
     */
    public function build(string $reference, ?array $booking): array
    {
        if ($reference === '') {
            return ['state' => self::STATE_IDLE, 'reference' => '', 'booking' => null];
        }

        if (! $booking) {
            return ['state' => self::STATE_NOT_FOUND, 'reference' => $reference, 'booking' => null];
        }

        return ['state' => self::STATE_FOUND, 'reference' => $reference, 'booking' => $this->toView($booking)];
    }

    /**
     * @param  array<string, mixed>  $booking
     * @return array<string, mixed>
     */
    private function toView(array $booking): array
    {
        $show = $booking['show'];
        $startsAt = new DateTimeImmutable($show['starts_at']);
        $window = EntryWindow::forShowStart($startsAt, $this->entryMinutesBefore, $this->entryMinutesAfter);
        $tickets = array_map($this->ticketRow(...), $booking['tickets']);

        return [
            'ref' => $booking['ref'],
            'name' => $booking['name'],
            'show_id' => $show['id'],
            'movie' => $show['movie'],
            'screen' => $show['screen'],
            'date' => $startsAt->format('M j, Y'),
            'time' => $startsAt->format('H:i'),
            'window' => $window->label(),
            'seats' => count($tickets),
            'scanned' => count(array_filter($tickets, fn (array $t): bool => $t['used'])),
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
