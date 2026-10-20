<?php

use App\Models\User;

// The staff screens are only open to staff, so these tests run as one.
beforeEach(fn () => $this->actingAs(User::factory()->staff()->make()));

test('the staff today screen shows the summary, nearest show and every show', function () {
    $response = $this->get(route('staff.today'));

    $response->assertOk()
        ->assertSee('Today at Downtown')
        ->assertSee('Thu, Oct 15, 2026')
        ->assertSee('Scheduled shows')
        ->assertSee('Tickets expected')
        ->assertSee('Guests admitted')
        ->assertSee('✓ ENTRY OPEN · NEAREST SHOW')
        ->assertSee('Local time · 18:55')
        ->assertSee('Entry 18:30–20:00 · 18 / 45 scanned')
        ->assertSee('Scan this show →')
        ->assertSee('Signal Zero · preview')
        ->assertSee('18:30–20:00 · Open', false)
        ->assertSee('13:30–15:00 · Closed', false)
        ->assertSee('21:00–22:30 · Not open', false)
        ->assertSee('Admission desk');
});

test('the staff today screen totals match the mock shows', function () {
    $html = $this->get(route('staff.today'))->getContent();

    preg_match_all('/stat-card__value">\s*(\d+)\s*</', $html, $matches);

    expect($matches[1])->toBe(['6', '264', '146']);
});

test('the nearest show card announces the next show when entry is not open yet', function () {
    config(['staff-mock.now' => '2026-10-15 12:00']);

    $this->get(route('staff.today'))
        ->assertOk()
        ->assertSee('ENTRY NOT OPEN YET · NEXT SHOW');
});

test('the nearest show card is left out once all entry windows have closed', function () {
    config(['staff-mock.now' => '2026-10-15 23:00']);

    $this->get(route('staff.today'))
        ->assertOk()
        ->assertDontSee('NEAREST SHOW')
        ->assertDontSee('Scan this show')
        ->assertSee('Side by Side');
});

test('the staff screens are reachable without logging in for now', function () {
    $this->get('/staff')->assertOk();
});

test('the nearest show card links to the scan screen for that show', function () {
    $this->get(route('staff.today'))->assertSee(route('staff.scan', ['show' => 4]), false);
});
