<?php

test('the lookup page asks for a reference before anything is searched', function () {
    $this->get(route('staff.lookup'))
        ->assertOk()
        ->assertSee('Help a guest find their ticket')
        ->assertSee('Thu, Oct 15, 2026')
        ->assertSee('Booking reference')
        ->assertSee('Look up reference →')
        ->assertSee('Enter a booking reference')
        ->assertDontSee('Admission context');
});

test('a known reference shows the booking, its tickets and the admission context', function () {
    $this->get(route('staff.lookup', ['reference' => 'CB-261015-0482']))
        ->assertOk()
        ->assertSee('Confirmed booking')
        ->assertSee('Alex Morgan')
        ->assertSee('The Last Horizon · Oct 15, 2026 · 19:30 CineBook Downtown · Screen 1')
        ->assertSee('G7 · Premium')
        ->assertSee('T-0482-01')
        ->assertSee('Used · Oct 15 at 18:58')
        ->assertSee('G8 · Premium')
        ->assertSee('T-0482-02')
        ->assertSee('Not scanned')
        ->assertSee('Damaged QR assistance')
        ->assertSee('Return to focused scanner →')
        ->assertSee('Admission context')
        ->assertSee('19:30 · Screen 1')
        ->assertSee('2 / 1')
        ->assertSee('18:30–20:00')
        ->assertSee('Read-only result. No payment, refund or email details are shown.')
        ->assertSee('Single-use rules still apply');
});

test('the lookup is reached from the staff navigation as the active page', function () {
    $this->get(route('staff.lookup'))
        ->assertSee('staff-nav__link is-active', false)
        ->assertSee(route('staff.lookup'), false)
        ->assertSee('Reference lookup');
});

test('the reference can be typed in lower case with spaces', function () {
    $this->get(route('staff.lookup', ['reference' => ' cb-261015-0482 ']))
        ->assertSee('Alex Morgan')
        ->assertSee('value="CB-261015-0482"', false);
});

test('a booking with no scanned seat shows every ticket as not scanned', function () {
    $this->get(route('staff.lookup', ['reference' => 'CB-261015-0468']))
        ->assertSee('Ava Clark')
        ->assertSee('H4 · Premium')
        ->assertDontSee('Used ·');
});

test('standard seats are labelled standard', function () {
    $this->get(route('staff.lookup', ['reference' => 'CB-261015-0465']))
        ->assertSee('C8 · Standard');
});

test('an unknown reference says no booking was found', function () {
    $this->get(route('staff.lookup', ['reference' => 'CB-000000-0000']))
        ->assertOk()
        ->assertSee('No booking found')
        ->assertSee('“CB-000000-0000”')
        ->assertDontSee('Admission context');
});

test('a reference is escaped when it is echoed back', function () {
    $this->get(route('staff.lookup', ['reference' => '<script>alert(1)</script>']))
        ->assertOk()
        ->assertDontSee('<script>alert(1)</script>', false)
        ->assertSee('No booking found');
});

test('the result never shows email or payment details', function () {
    $this->get(route('staff.lookup', ['reference' => 'CB-261015-0482']))
        ->assertDontSee('@')
        ->assertDontSee('$')
        ->assertDontSee('Stripe');
});

test('return to the scanner keeps the booking\'s show selected', function () {
    $this->get(route('staff.lookup', ['reference' => 'CB-261015-0482']))
        ->assertSee(route('staff.scan', ['show' => 4]), false);
});
