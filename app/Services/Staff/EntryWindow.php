<?php

namespace App\Services\Staff;

use DateTimeImmutable;

/**
 * The period in which tickets for a show are accepted at the entrance.
 * The minutes before/after the start come from config/cinebook.php
 * (scan_window_before_minutes / scan_window_after_minutes).
 */
final class EntryWindow
{
    public const NOT_OPEN = 'not_open';

    public const OPEN = 'open';

    public const CLOSED = 'closed';

    public function __construct(
        public readonly DateTimeImmutable $opensAt,
        public readonly DateTimeImmutable $closesAt,
    ) {
    }

    public static function forShowStart(DateTimeImmutable $startsAt, int $minutesBefore, int $minutesAfter): self
    {
        return new self(
            $startsAt->modify("-{$minutesBefore} minutes"),
            $startsAt->modify("+{$minutesAfter} minutes"),
        );
    }

    /**
     * @return self::NOT_OPEN|self::OPEN|self::CLOSED
     */
    public function statusAt(DateTimeImmutable $now): string
    {
        if ($now < $this->opensAt) {
            return self::NOT_OPEN;
        }

        return $now <= $this->closesAt ? self::OPEN : self::CLOSED;
    }

    public function label(): string
    {
        return $this->opensAt->format('H:i').'–'.$this->closesAt->format('H:i');
    }
}
