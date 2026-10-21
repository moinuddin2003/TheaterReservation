<?php

use App\Models\User;

// The staff screens are only open to staff, so these tests run as one.
beforeEach(fn () => $this->actingAs(User::factory()->staff()->create()));

function scanToken(string $token, int $show = 4)
{
    return test()->followingRedirects()->post(route('staff.scan.store'), ['token' => $token, 'show' => $show]);
}

test('an already used ticket shows the already used result', function () {
    scanToken('DEMO-USED-G7')
        ->assertOk()
        ->assertSee('No ambiguity at the entrance.')
        ->assertSee('Already used')
        ->assertSee('G7 · The Last Horizon · Screen 1')
        ->assertSee('This ticket was admitted today at 18:58. A ticket can only be used once. There is no reset or undo.')
        ->assertSee('Current time 19:02 · Entry window 18:30–20:00')
        ->assertDontSee('ADMITTED');
});

test('a ticket for another show shows the wrong showtime result', function () {
    scanToken('DEMO-WRONG-SHOW')
        ->assertSee('Wrong showtime')
        ->assertSee('This ticket is for Night Circuit · 18:00 · Screen 2 IMAX.')
        ->assertSee('You’re scanning The Last Horizon · 19:30 · Screen 1. Choose the matching showtime before scanning again.')
        ->assertSee('Current time 18:55 · Selected show 19:30');
});

test('a ticket scanned too soon shows the too early result', function () {
    scanToken('DEMO-EARLY')
        ->assertSee('Too early')
        ->assertSee('Entry opens today at 18:30.')
        ->assertSee('Come back when the entry window opens, 60 minutes before the show.')
        ->assertSee('Current time 18:10 · Opens in 20 minutes');
});

test('a ticket scanned too late shows the expired result', function () {
    scanToken('DEMO-EXPIRED')
        ->assertSee('Expired')
        ->assertSee('Entry closed today at 20:00.')
        ->assertSee('This ticket is more than 30 minutes past the show start and can no longer be admitted.')
        ->assertSee('Current time 20:05 · Closed 5 minutes ago');
});

test('a cancelled ticket shows the void result', function () {
    scanToken('DEMO-VOID-G7')
        ->assertSee('Void · Do not admit')
        ->assertSee('This ticket was cancelled or refunded.')
        ->assertSee('A refund state never makes a void ticket valid again.')
        ->assertSee('Current time 18:55 · Entry window 18:30–20:00');
});

test('an unrecognised token shows the invalid result', function () {
    scanToken('not-a-ticket')
        ->assertSee('Invalid')
        ->assertSee('We couldn’t recognize this ticket.')
        ->assertSee('No valid ticket matches this token. Try scanning once more, type the token carefully or use the booking reference lookup.')
        ->assertSee('Current time 18:58 · Selected show 19:30');
});

test('every rejection leaves the scanner ready for the next ticket', function (string $token) {
    scanToken($token)
        ->assertSee('Next ticket token · focused and ready')
        ->assertSee('data-scan-input', false)
        ->assertSee('Submit with Enter')
        ->assertSee('Reference lookup available')
        ->assertSee(route('staff.lookup'), false)
        ->assertSee('Recent 10 attempts');
})->with(['DEMO-USED-G7', 'DEMO-WRONG-SHOW', 'DEMO-EARLY', 'DEMO-VOID-G7', 'not-a-ticket']);

test('a rejection does not count as a scanned guest', function () {
    scanToken('DEMO-USED-G7')->assertSee('18 / 45')->assertDontSee('19 / 45');
});

test('a rejection is added to the recent attempts', function () {
    scanToken('DEMO-USED-G7')
        ->assertSee('19:02:00')
        ->assertSee('Already used · 18:58')
        ->assertSee('T-0482-01');
});

test('every rejection is announced as an alert with its own icon', function () {
    scanToken('DEMO-VOID-G7')
        ->assertSee('role="alert"', false)
        ->assertSee('scan-result--danger', false)
        ->assertSee('<svg class="scan-result__icon"', false);

    scanToken('DEMO-EARLY')->assertSee('scan-result--warning', false);
});

test('a rejection is shown once and reloading returns to the ready screen', function () {
    test()->post(route('staff.scan.store'), ['token' => 'DEMO-VOID-G7', 'show' => 4]);

    $this->get(route('staff.scan', ['show' => 4]))->assertSee('This ticket was cancelled or refunded.');

    $this->get(route('staff.scan', ['show' => 4]))
        ->assertDontSee('This ticket was cancelled or refunded.')
        ->assertSee('Ready when they are.');
});

test('the token typed is not echoed back on the result page', function () {
    scanToken('<script>alert(1)</script>')
        ->assertDontSee('<script>alert(1)</script>', false)
        ->assertSee('We couldn’t recognize this ticket.');
});

test('a rejected ticket for a show that has closed still returns to a usable scanner', function () {
    scanToken('DEMO-EXPIRED')
        ->assertSee('Entry closed today at 20:00.')
        ->assertSee('Next ticket token · focused and ready');
});
