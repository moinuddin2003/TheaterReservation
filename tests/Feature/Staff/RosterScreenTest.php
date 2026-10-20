<?php

use App\Models\User;

// The staff screens are only open to staff, so these tests run as one.
beforeEach(fn () => $this->actingAs(User::factory()->staff()->make()));

test('the roster shows the selected show, totals, the first page of bookings and the notice', function () {
    $this->get(route('staff.roster'))
        ->assertOk()
        ->assertSee('Show roster')
        ->assertSee('Thu, Oct 15, 2026')
        ->assertSee('The Last Horizon · Oct 15 · 19:30 · Screen 1')
        ->assertSee('Search name or reference')
        ->assertSee('✓ 19 / 45 admitted')
        ->assertSee('21 confirmed bookings · Read-only admission roster')
        ->assertSee('Back to scanner →')
        ->assertSee('CB-261015-0482')
        ->assertSee('Alex Morgan')
        ->assertSee('G7, G8')
        ->assertSee('1 / 2')
        ->assertSee('CB-261015-0465')
        ->assertDontSee('CB-261015-0462')
        ->assertSee('Showing 1–8 of 21 bookings')
        ->assertSee('Admission information only');
});

test('the roster is reached from the staff navigation as the active page', function () {
    $this->get(route('staff.roster'))
        ->assertSee('staff-nav__link is-active', false)
        ->assertSee(route('staff.roster'), false);
});

test('the second page continues the list', function () {
    $this->get(route('staff.roster', ['page' => 2]))
        ->assertSee('Showing 9–16 of 21 bookings')
        ->assertSee('CB-261015-0462')
        ->assertDontSee('CB-261015-0482');
});

test('searching by name keeps only the matching booking', function () {
    $this->get(route('staff.roster', ['q' => 'maya']))
        ->assertOk()
        ->assertSee('Showing 1–1 of 1 booking')
        ->assertSee('Maya Chen')
        ->assertDontSee('Alex Morgan')
        ->assertSee('21 confirmed bookings');
});

test('searching by reference works and says so when nothing matches', function () {
    $this->get(route('staff.roster', ['q' => '0473']))
        ->assertSee('Ella Brooks')
        ->assertDontSee('Maya Chen');

    $this->get(route('staff.roster', ['q' => 'zzz']))
        ->assertSee('No bookings match “zzz”.')
        ->assertSee('No bookings to show');
});

test('a show without bookings says so', function () {
    $this->get(route('staff.roster', ['show' => 6]))
        ->assertOk()
        ->assertSee('No confirmed bookings for this show yet.')
        ->assertSee('✓ 0 / 0 admitted');
});

test('the show picker lists every show of the day', function () {
    $this->get(route('staff.roster'))
        ->assertSee('Night Circuit · Oct 15 · 18:00 · Screen 2 IMAX')
        ->assertSee('Side by Side · Oct 15 · 22:00 · Screen 1');
});

test('the previous button is disabled on the first page and next on the last', function () {
    $this->get(route('staff.roster'))
        ->assertSee('aria-disabled="true">← Previous', false)
        ->assertSee('rel="next"', false);

    $this->get(route('staff.roster', ['page' => 3]))
        ->assertSee('rel="prev"', false)
        ->assertSee('aria-disabled="true">Next →', false);
});

test('the roster loads the live search script', function () {
    $this->get(route('staff.roster'))
        ->assertSee("getElementById('roster-search')", false);
});
