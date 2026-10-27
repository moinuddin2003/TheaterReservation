<?php

use App\Contracts\Staff\TicketScanner;
use App\Models\Ticket;
use App\Models\TicketScan;
use App\Models\User;
use App\Services\Staff\EloquentTicketScanner;
use App\Services\Staff\MockTicketScanner;
use Illuminate\Support\Carbon;
use Tests\Support\StaffDbFixture;

function dbScan(string $token, ?int $show, User $staff): array
{
    return app(EloquentTicketScanner::class)->scan($token, $show, $staff->id, '10.0.0.5');
}

beforeEach(function () {
    Carbon::setTestNow('2026-10-15 18:58:00');
    $this->staff = User::factory()->staff()->create();
    $this->show = StaffDbFixture::show('2026-10-15 19:30');
    $this->booking = StaffDbFixture::booking($this->show, 'CB-261015-0482', 'Alex Morgan', ['G7' => 'premium', 'G8' => 'premium']);
    $this->token = StaffDbFixture::token('CB-261015-0482', 'G7');
});

afterEach(fn () => Carbon::setTestNow());

test('a valid ticket for the selected show is admitted and marked used', function () {
    $result = dbScan($this->token, $this->show->id, $this->staff);

    expect($result)->toMatchArray(['status' => 'admitted', 'seat' => 'G7', 'ticket' => 'T-0482-01', 'movie' => 'The Last Horizon', 'seat_label' => 'Premium seat', 'admitted_at' => '18:58']);

    $ticket = Ticket::where('token', $this->token)->first();
    expect($ticket->status->value)->toBe('used')
        ->and($ticket->used_by)->toBe($this->staff->id)
        ->and($ticket->used_at->format('H:i'))->toBe('18:58');
});

test('every attempt is saved with the staff member, result and a hash of the token', function () {
    dbScan($this->token, $this->show->id, $this->staff);

    $scan = TicketScan::first();

    expect($scan->result->value)->toBe('admitted')
        ->and($scan->scanned_by)->toBe($this->staff->id)
        ->and($scan->showtime_id)->toBe($this->show->id)
        ->and($scan->ticket_id)->toBe(Ticket::where('token', $this->token)->value('id'))
        ->and($scan->token_hash)->toBe(hash('sha256', $this->token))
        ->and($scan->token_hash)->not->toContain($this->token)
        ->and($scan->ip_address)->toBe('10.0.0.5')
        ->and($scan->message)->toBeNull();
});

test('scanning the same ticket twice admits once and then says already used', function () {
    dbScan($this->token, $this->show->id, $this->staff);
    Carbon::setTestNow('2026-10-15 19:02:00');

    $second = dbScan($this->token, $this->show->id, $this->staff);

    expect($second)->toMatchArray(['status' => 'already_used', 'used_at' => '18:58', 'seat' => 'G7'])
        ->and(TicketScan::count())->toBe(2)
        ->and(Ticket::where('token', $this->token)->first()->used_at->format('H:i'))->toBe('18:58')
        ->and(TicketScan::latest('id')->first()->message)->toBe('G7 · The Last Horizon · Screen 1');
});

test('a ticket for another show is the wrong showtime and stays valid', function () {
    $other = StaffDbFixture::show('2026-10-15 18:00', 'Night Circuit', 'Screen 2 IMAX');

    $result = dbScan($this->token, $other->id, $this->staff);

    expect($result)->toMatchArray(['status' => 'wrong_showtime', 'show_id' => $other->id])
        ->and($result['ticket_show']['movie'])->toBe('The Last Horizon')
        ->and($result['selected_show']['movie'])->toBe('Night Circuit')
        ->and(Ticket::where('token', $this->token)->first()->status->value)->toBe('valid');
});

test('a ticket is too early before the window opens and expired after it closes', function () {
    Carbon::setTestNow('2026-10-15 18:10:00');
    $early = dbScan($this->token, $this->show->id, $this->staff);

    Carbon::setTestNow('2026-10-15 20:05:00');
    $late = dbScan($this->token, $this->show->id, $this->staff);

    expect($early['status'])->toBe('too_early')
        ->and($early['opens_at'])->toBe('2026-10-15 18:30:00')
        ->and($late['status'])->toBe('expired')
        ->and($late['closes_at'])->toBe('2026-10-15 20:00:00')
        ->and(Ticket::where('token', $this->token)->first()->status->value)->toBe('valid');
});

test('a void ticket is never admitted', function () {
    Ticket::where('token', $this->token)->update(['status' => 'void']);

    expect(dbScan($this->token, $this->show->id, $this->staff)['status'])->toBe('void');
});

test('a legacy voided ticket is never admitted', function () {
    Ticket::where('token', $this->token)->update(['status' => 'voided']);

    expect(dbScan($this->token, $this->show->id, $this->staff)['status'])->toBe('void');
});

test('a ticket of a booking that is no longer confirmed counts as void', function () {
    $this->booking->forceFill(['status' => 'cancelled'])->save();

    expect(dbScan($this->token, $this->show->id, $this->staff)['status'])->toBe('void')
        ->and(Ticket::where('token', $this->token)->first()->status->value)->toBe('valid');
});

test('an unknown token is invalid and is still logged without a ticket', function () {
    $result = dbScan('not-a-ticket', $this->show->id, $this->staff);

    expect($result)->toMatchArray(['status' => 'invalid', 'seat' => null])
        ->and(TicketScan::first()->ticket_id)->toBeNull()
        ->and(TicketScan::first()->result->value)->toBe('invalid');
});

test('an empty token is invalid', function () {
    expect(dbScan('   ', $this->show->id, $this->staff)['status'])->toBe('invalid');
});

test('the token may carry surrounding whitespace', function () {
    expect(dbScan("  {$this->token}\n", $this->show->id, $this->staff)['status'])->toBe('admitted');
});

test('the scanner leaves the screen data alone because it already comes from the database', function () {
    $screen = ['shows' => [], 'selected' => ['id' => 1, 'scanned' => 3], 'attempts' => []];

    expect(app(EloquentTicketScanner::class)->applyTo($screen, ['status' => 'admitted', 'show_id' => 1]))->toBe($screen);
});

test('the staff data source setting picks the scanner', function () {
    config(['staff.data_source' => 'mock']);

    expect(app(TicketScanner::class))->toBeInstanceOf(MockTicketScanner::class);

    config(['staff.data_source' => 'database']);

    expect(app(TicketScanner::class))->toBeInstanceOf(EloquentTicketScanner::class);
});
