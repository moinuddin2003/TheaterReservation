<?php

namespace Database\Seeders;

use App\Enums\BookingStatus;
use App\Enums\HoldStatus;
use App\Enums\MovieStatus;
use App\Enums\PaymentStatus;
use App\Enums\RefundStatus;
use App\Enums\Role;
use App\Enums\ScanResult;
use App\Enums\SeatType;
use App\Enums\ShowtimeStatus;
use App\Enums\TicketStatus;
use App\Models\Booking;
use App\Models\Cinema;
use App\Models\Genre;
use App\Models\Movie;
use App\Models\Payment;
use App\Models\Screen;
use App\Models\Seat;
use App\Models\Showtime;
use App\Models\Ticket;
use App\Models\User;
use App\Services\TokenGenerator;
use Carbon\CarbonImmutable;
use Faker\Factory;
use Faker\Generator;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class DemoDataSeeder extends Seeder
{
    private const CHUNK_SIZE = 500;

    public function run(): void
    {
        if (app()->isProduction()) {
            $this->command?->info('Demo data skipped in production.');

            return;
        }
        if (Movie::query()->exists()) {
            $this->command?->info('Demo data already seeded');

            return;
        }

        DB::transaction(function (): void {
            $faker = Factory::create('en_US');
            $faker->seed(2026);
            $now = CarbonImmutable::now('UTC');
            $genres = $this->seedGenres($now);
            $movies = $this->seedMovies($faker, $genres, $now);
            [$cinema, $screens, $seatsByScreen] = $this->seedCinema($now);
            $users = $this->seedUsers($faker, $now);
            $showtimes = $this->seedShowtimes($faker, $movies, $screens, $now);
            $this->seedBookings($faker, $movies, $screens, $seatsByScreen, $showtimes, $users, $now);
            $this->seedStripeEvents($faker, $now);
            $this->seedAuditLogs($faker, $users, $now);
        }, attempts: 3);
    }

    /** @return list<Genre> */
    private function seedGenres(CarbonImmutable $now): array
    {
        $names = ['Action', 'Adventure', 'Animation', 'Comedy', 'Drama', 'Science Fiction'];
        $genres = [];
        foreach ($names as $name) {
            $genres[] = Genre::query()->create(['name' => $name, 'slug' => Str::slug($name)]);
        }

        return $genres;
    }

    /** @param list<Genre> $genres
     * @return list<Movie>
     */
    private function seedMovies(Generator $faker, array $genres, CarbonImmutable $now): array
    {
        $titles = [
            'The Glass Horizon', 'Midnight at Meridian', 'A Map of Ashes', 'Paper Satellites',
            'The Last Orchard', 'Echoes of Tomorrow', 'The Quiet Signal', 'Northbound',
            'City of Small Wonders', 'The Blue Hour', 'A Borrowed Sky', 'Brightwater',
            "The Clockmaker's Garden", 'Wildflower Season', 'Orbit of Us', 'The Hidden Current',
            'After the Rainfall', 'Moonlit Passage', 'The Long Way Home', 'Velvet Comet',
            'A House Between Worlds', 'The Winter Archive', 'Silver Lining Road', 'The Fourth Lantern',
        ];
        $movies = [];
        foreach ($titles as $index => $title) {
            $status = match ($index % 8) {
                0, 1, 2, 3 => MovieStatus::NowShowing,
                4, 5, 6 => MovieStatus::ComingSoon,
                default => MovieStatus::Archived,
            };
            $movie = Movie::query()->create([
                'title' => $title,
                'slug' => Str::slug($title),
                'synopsis' => $faker->paragraphs(3, true),
                'duration_minutes' => $faker->numberBetween(88, 165),
                'rating' => ['G', 'PG', 'PG-13', 'R'][$index % 4],
                'release_date' => $now->subDays($faker->numberBetween(0, 240))->toDateString(),
                'poster_path' => null,
                'trailer_url' => null,
                'status' => $status,
                'created_at' => $now->subMonths(3),
                'updated_at' => $now,
            ]);
            $assignedGenres = [
                $genres[$index % count($genres)]->id,
                $genres[($index + 2) % count($genres)]->id,
            ];
            $movie->genres()->sync(array_unique($assignedGenres));
            $movies[] = $movie;
        }

        return $movies;
    }

    /** @return array{Cinema, list<Screen>, array<int, list<Seat>>} */
    private function seedCinema(CarbonImmutable $now): array
    {
        $cinema = Cinema::query()->create([
            'name' => 'CineBook Grand',
            'address' => '125 Grand Avenue, New York, NY 10013',
            'phone' => '(212) 555-0148',
        ]);
        $layouts = [[7, 16], [8, 12], [10, 10], [6, 14]];
        $screens = [];
        $seatsByScreen = [];
        $seatRows = [];
        foreach ($layouts as $index => [$rows, $perRow]) {
            $screen = Screen::query()->create([
                'cinema_id' => $cinema->id,
                'name' => 'Screen '.str_pad((string) ($index + 1), 2, '0', STR_PAD_LEFT),
                'rows_count' => $rows,
                'seats_per_row' => $perRow,
                'is_active' => true,
            ]);
            $screens[] = $screen;
            $seatsByScreen[$screen->id] = [];
            for ($row = 0; $row < $rows; $row++) {
                for ($seatNumber = 1; $seatNumber <= $perRow; $seatNumber++) {
                    $label = chr(65 + $row).$seatNumber;
                    $type = $seatNumber === 1
                        ? SeatType::Accessible->value
                        : ($row >= max(0, $rows - 2) ? SeatType::Premium->value : SeatType::Standard->value);
                    $seatRows[] = [
                        'screen_id' => $screen->id,
                        'row_label' => chr(65 + $row),
                        'seat_number' => $seatNumber,
                        'label' => $label,
                        'type' => $type,
                        'is_disabled' => false,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ];
                }
            }
        }
        foreach (array_chunk($seatRows, self::CHUNK_SIZE) as $chunk) {
            DB::table('seats')->insert($chunk);
        }
        foreach (Seat::query()->orderBy('id')->get() as $seat) {
            $seatsByScreen[$seat->screen_id][] = $seat;
        }

        return [$cinema, $screens, $seatsByScreen];
    }

    /** @return list<User> */
    private function seedUsers(Generator $faker, CarbonImmutable $now): array
    {
        $password = Hash::make('password');
        $users = [];
        for ($index = 1; $index <= 25; $index++) {
            $users[] = User::query()->create([
                'name' => $index === 1 ? 'CineBook Staff' : $faker->name(),
                'email' => $index === 1
                    ? 'staff@example.com'
                    : 'cinebook.demo.'.str_pad((string) $index, 2, '0', STR_PAD_LEFT).'@example.test',
                'password' => $password,
                'role' => $index <= 3 ? Role::Staff : Role::Customer,
                'email_verified_at' => $now->subDays($faker->numberBetween(1, 365)),
                'created_at' => $now->subDays($faker->numberBetween(1, 365)),
                'updated_at' => $now,
            ]);
        }

        return $users;
    }

    /** @param list<Movie> $movies
     * @param  list<Screen>  $screens
     * @return list<Showtime>
     */
    private function seedShowtimes(Generator $faker, array $movies, array $screens, CarbonImmutable $now): array
    {
        $showtimes = [];
        $dayCounts = array_fill(0, 29, 2);
        for ($index = 0; $index < 12; $index++) {
            $dayCounts[$index * 2] = 3;
        }
        $statusCount = 0;
        foreach ($dayCounts as $dayIndex => $count) {
            $dayOffset = $dayIndex - 14;
            for ($slot = 0; $slot < $count; $slot++) {
                $screenIndex = ($dayIndex + $slot) % count($screens);
                $start = $now->startOfDay()->addDays($dayOffset)->setTime(10 + ($slot * 4), 0);
                $movie = $movies[($dayIndex * 3 + $slot) % count($movies)];
                $cancelled = $statusCount % 19 === 0 && $dayOffset > 0;
                $showtimes[] = Showtime::query()->create([
                    'movie_id' => $movie->id,
                    'screen_id' => $screens[$screenIndex]->id,
                    'starts_at' => $start,
                    'ends_at' => $start->addMinutes($movie->duration_minutes + 20),
                    'base_price_cents' => 1400 + (($statusCount % 4) * 300),
                    'status' => $cancelled ? ShowtimeStatus::Cancelled : ($start->lt($now) ? ShowtimeStatus::Completed : ShowtimeStatus::Scheduled),
                    'cancelled_at' => $cancelled ? $now->subHours(4) : null,
                    'cancelled_by' => $cancelled ? User::query()->where('role', Role::Admin->value)->value('id') : null,
                    'cancel_reason' => $cancelled ? 'Scheduled technical maintenance' : null,
                    'created_at' => $now->subDays(25),
                    'updated_at' => $now,
                ]);
                $statusCount++;
            }
        }

        return $showtimes;
    }

    /**
     * @param  list<Movie>  $movies
     * @param  list<Screen>  $screens
     * @param  array<int, list<Seat>>  $seatsByScreen
     * @param  list<Showtime>  $showtimes
     * @param  list<User>  $users
     */
    private function seedBookings(
        Generator $faker,
        array $movies,
        array $screens,
        array $seatsByScreen,
        array $showtimes,
        array $users,
        CarbonImmutable $now,
    ): void {
        $customers = array_values(array_filter($users, fn (User $user) => $user->role === Role::Customer));
        $staff = array_values(array_filter($users, fn (User $user) => $user->role === Role::Staff));
        $adminId = User::query()->where('role', Role::Admin->value)->value('id');
        $itemsToInsert = [];
        $holdsToInsert = [];
        $paymentsToInsert = [];
        $ticketsToInsert = [];
        $refundsToInsert = [];
        $scansToInsert = [];
        $soldSeatIds = [];
        $nextBookingItemOffset = 0;
        $ticketCandidates = [];
        $bookingIndex = 0;
        $specialShowtime = collect($showtimes)->first(fn (Showtime $showtime) => $showtime->screen_id === $screens[0]->id && $showtime->starts_at->isFuture());
        $otherShowtimes = array_values(array_filter($showtimes, fn (Showtime $showtime) => $showtime->id !== $specialShowtime?->id));

        for ($bookingIndex = 0; $bookingIndex < 150; $bookingIndex++) {
            $isSoldOutGroup = $bookingIndex < 19;
            $showtime = $isSoldOutGroup
                ? $specialShowtime
                : $otherShowtimes[($bookingIndex - 19) % count($otherShowtimes)];
            if (! $showtime) {
                throw new \RuntimeException('A showtime is required to seed bookings.');
            }
            $screenSeats = $seatsByScreen[$showtime->screen_id];
            $seatCount = $isSoldOutGroup
                ? ($bookingIndex < 18 ? 6 : 4)
                : $faker->numberBetween(1, 4);
            $status = $isSoldOutGroup ? BookingStatus::Confirmed : match ($bookingIndex % 17) {
                0 => BookingStatus::Expired,
                1 => BookingStatus::Failed,
                2 => BookingStatus::PendingPayment,
                3 => BookingStatus::Cancelled,
                default => BookingStatus::Confirmed,
            };
            $customer = $bookingIndex < 15
                ? $customers[0]
                : $customers[($bookingIndex * 7) % count($customers)];
            $createdAt = $now->subDays($faker->numberBetween(0, 28))->subMinutes($faker->numberBetween(0, 1200));
            $chosenSeats = [];
            $offset = $soldSeatIds[$showtime->id] ?? 0;
            for ($seatOffset = 0; $seatOffset < $seatCount; $seatOffset++) {
                if (! isset($screenSeats[$offset + $seatOffset])) {
                    throw new \RuntimeException('Demo booking allocation exceeded screen capacity.');
                }
                $chosenSeats[] = $screenSeats[$offset + $seatOffset];
            }
            $soldSeatIds[$showtime->id] = $offset + $seatCount;
            $totalCents = 0;
            foreach ($chosenSeats as $seat) {
                $totalCents += $showtime->base_price_cents + ($seat->type === SeatType::Premium ? 500 : 0);
            }

            $booking = Booking::query()->create([
                'reference' => 'CB-'.strtoupper(Str::random(12)),
                'user_id' => $customer->id,
                'showtime_id' => $showtime->id,
                'status' => $status,
                'seats_count' => $seatCount,
                'total_cents' => $totalCents,
                'currency' => 'USD',
                'hold_expires_at' => $createdAt->addMinutes(10),
                'confirmed_at' => in_array($status, [BookingStatus::Confirmed, BookingStatus::Cancelled], true) ? $createdAt->addMinutes(3) : null,
                'cancelled_at' => $status === BookingStatus::Cancelled ? $createdAt->addHours(1) : null,
                'cancelled_by' => $status === BookingStatus::Cancelled ? $customer->id : null,
                'cancel_reason' => $status === BookingStatus::Cancelled ? 'Customer cancellation' : null,
                'created_at' => $createdAt,
                'updated_at' => $createdAt,
            ]);
            $bookingItems = [];
            foreach ($chosenSeats as $seat) {
                $price = $showtime->base_price_cents + ($seat->type === SeatType::Premium ? 500 : 0);
                $itemStatus = $status === BookingStatus::Cancelled ? 'cancelled' : ($status === BookingStatus::Expired ? 'expired' : 'active');
                $item = [
                    'booking_id' => $booking->id,
                    'showtime_id' => $showtime->id,
                    'seat_id' => $seat->id,
                    'seat_label' => $seat->label,
                    'seat_type' => $seat->type->value,
                    'price_cents' => $price,
                    'status' => $itemStatus,
                    'created_at' => $createdAt,
                    'updated_at' => $createdAt,
                ];
                $bookingItems[] = $item;
                $itemsToInsert[] = $item;
                $holdStatus = match ($status) {
                    BookingStatus::Confirmed => HoldStatus::Converted,
                    BookingStatus::Cancelled, BookingStatus::Failed => HoldStatus::Released,
                    default => HoldStatus::Expired,
                };
                $holdsToInsert[] = [
                    'hold_token' => (string) Str::uuid(),
                    'showtime_id' => $showtime->id,
                    'seat_id' => $seat->id,
                    'user_id' => $customer->id,
                    'booking_id' => $booking->id,
                    'price_cents' => $price,
                    'status' => $holdStatus->value,
                    'expires_at' => $createdAt->addMinutes(10),
                    'released_at' => $holdStatus === HoldStatus::Released ? $createdAt->addMinutes(10) : null,
                    'created_at' => $createdAt,
                    'updated_at' => $createdAt,
                ];
            }

            $paymentStatus = match ($status) {
                BookingStatus::Confirmed, BookingStatus::Cancelled => PaymentStatus::Succeeded,
                BookingStatus::Failed, BookingStatus::Expired => PaymentStatus::Failed,
                default => PaymentStatus::Pending,
            };
            $payment = Payment::query()->create([
                'booking_id' => $booking->id,
                'gateway' => 'stripe',
                'gateway_session_id' => 'cs_test_demo_'.Str::lower(Str::random(20)),
                'gateway_payment_intent_id' => $paymentStatus === PaymentStatus::Succeeded ? 'pi_test_demo_'.Str::lower(Str::random(20)) : null,
                'idempotency_key' => (string) Str::uuid(),
                'amount_cents' => $totalCents,
                'currency' => 'USD',
                'status' => $paymentStatus,
                'failure_reason' => $paymentStatus === PaymentStatus::Failed ? 'Card was declined by the test issuer.' : null,
                'paid_at' => $paymentStatus === PaymentStatus::Succeeded ? $createdAt->addMinutes(3) : null,
                'created_at' => $createdAt,
                'updated_at' => $createdAt,
            ]);

            foreach ($bookingItems as $itemIndex => $item) {
                if (! in_array($status, [BookingStatus::Confirmed, BookingStatus::Cancelled], true)) {
                    continue;
                }
                $ticket = [
                    'booking_item_id' => $nextBookingItemOffset + $itemIndex + 1,
                    'booking_id' => $booking->id,
                    'showtime_id' => $showtime->id,
                    'ticket_number' => 'CBT-'.strtoupper(Str::random(12)),
                    'token' => app(TokenGenerator::class)->generate(),
                    'status' => $status === BookingStatus::Cancelled ? TicketStatus::Voided->value : TicketStatus::Valid->value,
                    'used_at' => null,
                    'used_by' => null,
                    'voided_at' => $status === BookingStatus::Cancelled ? $createdAt->addHours(1) : null,
                    'created_at' => $createdAt,
                    'updated_at' => $createdAt,
                ];
                $ticketsToInsert[] = $ticket;
                if ($showtime->starts_at->toDateString() <= $now->toDateString() && $ticket['status'] === TicketStatus::Valid->value) {
                    $ticketCandidates[] = $ticket;
                }
            }
            $nextBookingItemOffset += count($bookingItems);

            if ($paymentStatus === PaymentStatus::Succeeded && $bookingIndex > 30 && count($refundsToInsert) < 12 && $bookingIndex % 7 === 0) {
                $refundStatus = match (count($refundsToInsert) % 4) {
                    0 => RefundStatus::Pending,
                    1 => RefundStatus::Failed,
                    default => RefundStatus::Succeeded,
                };
                $refundsToInsert[] = [
                    'payment_id' => $payment->id,
                    'booking_id' => $booking->id,
                    'amount_cents' => max(1, intdiv($totalCents, 2)),
                    'currency' => 'USD',
                    'status' => $refundStatus->value,
                    'reason' => $refundStatus === RefundStatus::Pending ? 'customer_request' : 'admin_adjustment',
                    'gateway_refund_id' => $refundStatus === RefundStatus::Succeeded ? 're_test_demo_'.Str::lower(Str::random(16)) : null,
                    'idempotency_key' => (string) Str::uuid(),
                    'failure_message' => $refundStatus === RefundStatus::Failed ? 'Transient test gateway failure.' : null,
                    'initiated_by' => $adminId,
                    'processed_at' => $refundStatus === RefundStatus::Succeeded ? $createdAt->addDay() : null,
                    'created_at' => $createdAt->addHours(1),
                    'updated_at' => $createdAt->addHours(1),
                ];
            }
        }

        $this->insertChunks('booking_items', $itemsToInsert);
        $this->insertChunks('seat_holds', $holdsToInsert);
        $this->insertChunks('tickets', $ticketsToInsert);

        $this->seedScans($faker, $ticketCandidates, $staff, $showtimes, $now);
        $this->insertChunks('refunds', $refundsToInsert);
    }

    /** @param list<array<string, mixed>> $ticketCandidates
     * @param  list<User>  $staff
     * @param  list<Showtime>  $showtimes
     */
    private function seedScans(Generator $faker, array $ticketCandidates, array $staff, array $showtimes, CarbonImmutable $now): void
    {
        $tickets = Ticket::query()->whereIn('status', [TicketStatus::Valid->value, TicketStatus::Voided->value])->get()->keyBy('ticket_number');
        $pastOrToday = $tickets->filter(fn (Ticket $ticket) => $ticket->showtime()->first()?->starts_at?->toDateString() <= $now->toDateString())->values();
        $rows = [];
        for ($index = 0; $index < 80; $index++) {
            $isInvalid = $index % 17 === 0;
            $isDuplicate = $index % 13 === 0 && $pastOrToday->isNotEmpty();
            $ticket = $isInvalid ? null : ($pastOrToday[$index % max(1, $pastOrToday->count())] ?? null);
            $result = $isInvalid ? ScanResult::Invalid : ($isDuplicate ? ScanResult::Duplicate : ScanResult::Valid);
            $scanner = $staff[$index % count($staff)];
            $showtimeId = $ticket?->showtime_id;
            $rows[] = [
                'ticket_id' => $ticket?->id,
                'showtime_id' => $showtimeId,
                'token_hash' => hash('sha256', $ticket?->token ?? 'invalid-demo-'.$index),
                'scanned_by' => $scanner->id,
                'result' => $result->value,
                'message' => match ($result) {
                    ScanResult::Duplicate => 'Ticket has already been scanned.',
                    ScanResult::Invalid => 'Ticket token could not be found.',
                    default => 'Ticket accepted.',
                },
                'ip_address' => '192.0.2.'.($index + 1),
                'scanned_at' => $now->subHours($index + 1),
            ];
        }
        $this->insertChunks('ticket_scans', $rows);
    }

    private function seedStripeEvents(Generator $faker, CarbonImmutable $now): void
    {
        $rows = [];
        for ($index = 1; $index <= 40; $index++) {
            $status = $index === 40 ? 'failed' : ($index % 11 === 0 ? 'received' : 'processed');
            $rows[] = [
                'stripe_event_id' => 'evt_test_demo_'.str_pad((string) $index, 8, '0', STR_PAD_LEFT),
                'type' => ['payment_intent.succeeded', 'checkout.session.completed', 'payment_intent.payment_failed'][$index % 3],
                'status' => $status,
                'error_message' => $status === 'failed' ? 'Transient webhook handler failure; replay is available.' : null,
                'processed_at' => $status === 'processed' ? $now->subMinutes($index * 15) : null,
                'created_at' => $now->subMinutes($index * 15),
                'updated_at' => $now->subMinutes($index * 15),
            ];
        }
        $this->insertChunks('stripe_events', $rows);
    }

    /** @param list<User> $users */
    private function seedAuditLogs(Generator $faker, array $users, CarbonImmutable $now): void
    {
        $adminId = User::query()->where('role', Role::Admin->value)->value('id');
        $rows = [];
        $actions = ['movie.created', 'movie.updated', 'booking.cancelled', 'refund.created', 'ticket.voided', 'settings.updated'];
        $entities = ['Movie', 'Movie', 'Booking', 'Refund', 'Ticket', 'Setting'];
        for ($index = 0; $index < 80; $index++) {
            $actionIndex = $index % count($actions);
            $rows[] = [
                'user_id' => $index % 4 === 0 ? $users[$index % count($users)]->id : $adminId,
                'action' => $actions[$actionIndex],
                'auditable_type' => $entities[$actionIndex],
                'auditable_id' => $index + 1,
                'before' => json_encode(['status' => 'previous'], JSON_THROW_ON_ERROR),
                'after' => json_encode(['status' => 'updated'], JSON_THROW_ON_ERROR),
                'ip_address' => '192.0.2.'.(($index % 200) + 1),
                'created_at' => $now->subHours($index * 2),
            ];
        }
        $this->insertChunks('audit_logs', $rows);
    }

    /** @param list<array<string, mixed>> $rows */
    private function insertChunks(string $table, array $rows): void
    {
        foreach (array_chunk($rows, self::CHUNK_SIZE) as $chunk) {
            DB::table($table)->insert($chunk);
        }
    }
}
