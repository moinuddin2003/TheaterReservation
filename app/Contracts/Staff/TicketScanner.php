<?php

namespace App\Contracts\Staff;

/**
 * Checks a scanned token. MockTicketScanner uses the staff-mock tickets,
 * EloquentTicketScanner uses the tickets table.
 */
interface TicketScanner
{
    /**
     * Decides what a scan means and, for a real scanner, records it.
     *
     * @param  int|null  $selectedShowId  the show the staff member is scanning for
     * @return array<string, mixed> the result facts; "status" is a ScanResult value
     */
    public function scan(string $token, ?int $selectedShowId, int $staffUserId, ?string $ip): array;

    /**
     * Reflects a result on the scan screen data. The mock changes the screen
     * because it stores nothing; the database scanner leaves it as is, since
     * the screen data already comes from the saved scans.
     *
     * @param  array<string, mixed>  $screen
     * @param  array<string, mixed>  $result
     * @return array<string, mixed>
     */
    public function applyTo(array $screen, array $result): array;
}
