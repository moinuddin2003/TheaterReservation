<?php

namespace Database\Seeders;

use App\Enums\BookingStatus;
use App\Enums\Role;
use App\Enums\ScanResult;
use App\Enums\TicketStatus;
use App\Models\Booking;
use App\Models\BookingItem;
use App\Models\Seat;
use App\Models\Showtime;
use App\Models\Ticket;
use App\Models\TicketScan;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class TicketDemoSeeder extends Seeder
{
    /**
     * Seed repeatable ticket and scan activity for the admin Tickets & Scans page.
     */
    public function run(): void
    {
        $showtime = Showtime::query()
            ->upcoming()
            ->with('screen')
            ->orderBy('starts_at')
            ->first();
        $staff = User::query()->where('role', Role::Staff)->orderBy('id')->first();

        if (! $showtime || ! $staff) {
            $this->command?->warn('Ticket demo data needs an upcoming showtime and a staff user. Nothing seeded.');

            return;
        }

        DB::transaction(function () use ($showtime, $staff): void {
            $customers = $this->customers();

            for ($number = 1; $number <= 20; $number++) {
                $reference = sprintf('DEMO-TIX-%04d', $number);
                $booking = Booking::query()->where('reference', $reference)->first();

                if (! $booking) {
                    $seat = $this->availableSeat($showtime);

                    if (! $seat) {
                        $this->command?->warn('No more available seats for the ticket demo showtime. Seeded the available tickets.');

                        break;
                    }

                    $isUsed = $number >= 11 && $number <= 16;
                    $isVoid = $number >= 17;
                    $createdAt = now()->subMinutes($number * 7);
                    $booking = Booking::forceCreate([
                        'reference' => $reference,
                        'user_id' => $customers[($number - 1) % count($customers)]->id,
                        'showtime_id' => $showtime->id,
                        'status' => $isVoid ? BookingStatus::Cancelled : BookingStatus::Confirmed,
                        'seats_count' => 1,
                        'total_cents' => $showtime->base_price_cents,
                        'currency' => 'USD',
                        'hold_expires_at' => $createdAt->copy()->addMinutes(10),
                        'confirmed_at' => $isVoid ? null : $createdAt,
                        'cancelled_at' => $isVoid ? $createdAt : null,
                        'cancelled_by' => $isVoid ? $customers[($number - 1) % count($customers)]->id : null,
                        'cancel_reason' => $isVoid ? 'Cancelled by customer' : null,
                        'created_at' => $createdAt,
                        'updated_at' => $createdAt,
                    ]);

                    $item = BookingItem::forceCreate([
                        'booking_id' => $booking->id,
                        'showtime_id' => $showtime->id,
                        'seat_id' => $seat->id,
                        'seat_label' => $seat->label,
                        'seat_type' => $seat->type->value,
                        'price_cents' => $showtime->base_price_cents,
                        'status' => $isVoid ? 'cancelled' : 'active',
                    ]);

                    $ticketStatus = match (true) {
                        $isVoid => TicketStatus::Void,
                        $isUsed => TicketStatus::Used,
                        default => TicketStatus::Valid,
                    };
                    $ticket = Ticket::forceCreate([
                        'booking_item_id' => $item->id,
                        'booking_id' => $booking->id,
                        'showtime_id' => $showtime->id,
                        'ticket_number' => sprintf('DEMO-TICKET-%03d', 100 + $number),
                        'token' => hash('sha1', $reference),
                        'status' => $ticketStatus,
                        'used_at' => $isUsed ? $createdAt->copy()->addMinutes(3) : null,
                        'used_by' => $isUsed ? $staff->id : null,
                        'voided_at' => $isVoid ? $createdAt : null,
                        'created_at' => $createdAt,
                        'updated_at' => $createdAt,
                    ]);
                } else {
                    $ticket = Ticket::query()->where('booking_id', $booking->id)->first();
                }

                if (! $ticket) {
                    continue;
                }

                if ($ticket->status === TicketStatus::Used) {
                    $this->recordScan($ticket, $showtime, $staff, ScanResult::Admitted, 'Ticket admitted.');
                    $this->recordScan($ticket, $showtime, $staff, ScanResult::AlreadyUsed, 'Ticket was already admitted.');
                } elseif ($ticket->status === TicketStatus::Void || $ticket->status === TicketStatus::Voided) {
                    $this->recordScan($ticket, $showtime, $staff, ScanResult::Void, 'Ticket is void.');
                }
            }

            for ($attempt = 1; $attempt <= 5; $attempt++) {
                $tokenHash = hash('sha256', 'ticket-demo-invalid-'.$attempt);

                TicketScan::query()->firstOrCreate(
                    ['token_hash' => $tokenHash, 'result' => ScanResult::Invalid],
                    [
                        'ticket_id' => null,
                        'showtime_id' => $showtime->id,
                        'scanned_by' => $staff->id,
                        'message' => 'Ticket code is invalid.',
                        'ip_address' => '127.0.0.1',
                        'scanned_at' => now()->subMinutes($attempt * 4),
                    ],
                );
            }
        });
    }

    /**
     * @return list<User>
     */
    private function customers(): array
    {
        $names = [
            'Alex Morgan',
            'Maya Chen',
            'Nash Patel',
            'Ella Brooks',
            'Ava Clark',
            'Noah Patel',
            'Liam Park',
            'Olivia Reed',
        ];

        return collect($names)->map(function (string $name): User {
            $email = strtolower(str_replace(' ', '.', $name)).'.tickets@cinebook.test';

            return User::query()->firstOrCreate(
                ['email' => $email],
                [
                    'name' => $name,
                    'password' => env('SEED_PASSWORD', 'password'),
                    'role' => Role::Customer,
                    'email_verified_at' => now(),
                ],
            );
        })->all();
    }

    private function availableSeat(Showtime $showtime): ?Seat
    {
        return Seat::query()
            ->where('screen_id', $showtime->screen_id)
            ->where('is_disabled', false)
            ->whereDoesntHave('bookingItems', fn (Builder $items) => $items
                ->where('showtime_id', $showtime->id)
                ->where('status', 'active'))
            ->whereDoesntHave('seatHolds', fn (Builder $holds) => $holds
                ->where('showtime_id', $showtime->id)
                ->where('status', 'active')
                ->where('expires_at', '>', now()))
            ->orderBy('row_label')
            ->orderBy('seat_number')
            ->first();
    }

    private function recordScan(
        Ticket $ticket,
        Showtime $showtime,
        User $staff,
        ScanResult $result,
        string $message,
    ): void {
        $tokenHash = hash('sha256', $ticket->token.'-'.$result->value);

        TicketScan::query()->firstOrCreate(
            ['token_hash' => $tokenHash, 'result' => $result],
            [
                'ticket_id' => $ticket->id,
                'showtime_id' => $showtime->id,
                'scanned_by' => $staff->id,
                'message' => $message,
                'ip_address' => '127.0.0.1',
                'scanned_at' => $ticket->used_at ?? $ticket->voided_at ?? now(),
            ],
        );
    }
}
