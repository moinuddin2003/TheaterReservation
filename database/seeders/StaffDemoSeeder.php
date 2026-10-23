<?php

namespace Database\Seeders;

use App\Enums\Role;
use App\Models\Booking;
use App\Models\Seat;
use App\Models\Showtime;
use App\Models\Ticket;
use App\Models\TicketScan;
use App\Models\User;
use Database\Seeders\Staff\DemoBookingBuilder;
use Database\Seeders\Staff\DemoCustomers;
use Database\Seeders\Staff\DemoPlan;
use Database\Seeders\Staff\DemoScanAttempts;
use Database\Seeders\Staff\DemoScanClock;
use Database\Seeders\Staff\DemoSeatPicker;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Demo data for the staff screens: customers, confirmed bookings with seats and tickets,
 * admitted tickets and scan history, for the shows on 2026-10-21 (already in the database).
 * Safe to run again: bookings that exist are left alone.
 *
 * Run it with: php artisan db:seed --class=StaffDemoSeeder
 */
class StaffDemoSeeder extends Seeder
{
    public const DEMO_DATE = '2026-10-21';

    public function run(): void
    {
        $staff = User::query()->where('role', Role::Staff->value)->orderBy('id')->first();
        $shows = $this->demoShows();

        if (! $staff || $shows->isEmpty()) {
            $this->command?->warn('Needs the staff user (AdminSeeder) and the showtimes of '.self::DEMO_DATE.'. Nothing seeded.');

            return;
        }

        DB::transaction(fn () => $this->seed($staff, $shows));

        $this->command?->info('Staff demo data is ready. Try these scans:');
        $this->command?->table(['Try', 'Booking', 'Seat', 'Token'], $this->demoTokens());
    }

    /**
     * @param  Collection<string, Showtime>  $shows
     */
    private function seed(User $staff, Collection $shows): void
    {
        $customers = (new DemoCustomers)->seed();
        $builder = new DemoBookingBuilder($staff, new DemoScanClock);

        foreach (DemoPlan::bookings() as $key => $plans) {
            $show = $shows->get($key);

            if (! $show) {
                continue;
            }

            $picker = new DemoSeatPicker($this->seatsOf($show));

            foreach ($plans as $plan) {
                $seats = $picker->take($plan['seats'], $plan['labels']);

                if (! Booking::query()->where('reference', $this->reference($show, $plan['ref']))->exists()) {
                    $builder->create($show, $customers[$plan['customer']], $plan, $seats);
                }
            }
        }

        $this->useShortTokens($shows);
        $this->addRejectedAttempts($staff, $shows);
    }

    /**
     * Gives the demo tickets their short tokens, also when they were seeded earlier with long ones.
     * Only the demo bookings are touched, and the scan history keeps pointing at the same ticket.
     *
     * @param  Collection<string, Showtime>  $shows
     */
    private function useShortTokens(Collection $shows): void
    {
        $references = $shows->flatMap(fn (Showtime $show, string $key): array => array_map(
            fn (array $plan): string => $this->reference($show, $plan['ref']),
            DemoPlan::bookings()[$key] ?? [],
        ))->all();

        Ticket::query()->whereHas('booking', fn ($q) => $q->whereIn('reference', $references))->each(function (Ticket $ticket): void {
            $token = DemoBookingBuilder::token($ticket->ticket_number);

            if ($ticket->token === $token) {
                return;
            }

            TicketScan::query()->where('ticket_id', $ticket->id)->where('token_hash', hash('sha256', $ticket->token))
                ->update(['token_hash' => hash('sha256', $token)]);
            $ticket->forceFill(['token' => $token])->save();
        });
    }

    private function addRejectedAttempts(User $staff, Collection $shows): void
    {
        (new DemoScanAttempts($staff))->add(
            $shows['11:00 Screen 1'],
            $this->ticketOf(478, '11:00 Screen 1', $shows),
            $this->ticketOf(510, '15:00 Screen 1', $shows),
        );
    }

    /**
     * Today's shows keyed like the plan: "11:00 Screen 1".
     *
     * @return Collection<string, Showtime>
     */
    private function demoShows(): Collection
    {
        return Showtime::query()->scheduled()->onDate(self::DEMO_DATE)->with('screen')->get()
            ->keyBy(fn (Showtime $show): string => $show->starts_at->format('H:i').' '.$show->screen->name);
    }

    /**
     * @return Collection<int, Seat>
     */
    private function seatsOf(Showtime $show): Collection
    {
        return Seat::query()->where('screen_id', $show->screen_id)->where('is_disabled', false)
            ->orderBy('row_label')->orderBy('seat_number')->get();
    }

    private function reference(Showtime $show, int $number): string
    {
        return sprintf('CB-%s-%04d', $show->starts_at->format('ymd'), $number);
    }

    /**
     * @param  Collection<string, Showtime>  $shows
     */
    private function ticketOf(int $number, string $showKey, Collection $shows, int $position = 0): ?Ticket
    {
        return Ticket::query()
            ->whereHas('booking', fn ($q) => $q->where('reference', $this->reference($shows[$showKey], $number)))
            ->orderBy('id')->skip($position)->first();
    }

    /**
     * One token for each scan outcome, so every staff scan screen can be tried by hand.
     *
     * @return list<array{string, string, string, string}>
     */
    private function demoTokens(): array
    {
        $shows = $this->demoShows();
        $rows = [
            ['Admit (pick 11:00 Screen 1)', 468, '11:00 Screen 1', 0],
            ['Already used', 478, '11:00 Screen 1', 0],
            ['Wrong showtime (pick 11:00 Screen 1)', 510, '15:00 Screen 1', 0],
            ['Too early (pick 19:00 Screen 1)', 610, '19:00 Screen 1', 0],
            ['Void (cancelled booking)', 480, '11:00 Screen 1', 0],
        ];

        return collect($rows)
            ->map(fn (array $row): ?array => ($ticket = $this->ticketOf($row[1], $row[2], $shows, $row[3]))
                ? [$row[0], $ticket->booking->reference, $ticket->bookingItem->seat_label, $ticket->token]
                : null)
            ->filter()->values()->all();
    }
}
