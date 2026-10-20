<?php

test('the scan screen shows the scanner, summary and recent attempts', function () {
    $this->get(route('staff.scan'))
        ->assertOk()
        ->assertSee('Ready when they are.')
        ->assertSee('✓ Entry open')
        ->assertSee('Scan a ticket')
        ->assertSee('Ticket token · always focused')
        ->assertSee('data-scan-input', false)
        ->assertSee('Submit token · Enter ↵')
        ->assertSee('No camera needed · Single-use admission · No undo or reset')
        ->assertSee('Recent 10 attempts')
        ->assertSee('Already used · 18:48')
        ->assertSee('T-0478-02')
        ->assertSee('18 / 45')
        ->assertSee('Guests admitted / tickets sold')
        ->assertSee('Entry opens 18:30')
        ->assertSee('Entry closes 20:00')
        ->assertSee('Current time 18:55')
        ->assertSee('Damaged QR?')
        ->assertSee('Reference lookup →');
});

test('the show picker lists only shows that can still be scanned', function () {
    $this->get(route('staff.scan'))
        ->assertSee('The Last Horizon · 19:30 · Screen 1')
        ->assertSee('Signal Zero · preview · 20:30 · Screen 2 IMAX')
        ->assertDontSee('Night Circuit');
});

test('another show can be picked from the query string', function () {
    $this->get(route('staff.scan', ['show' => 5]))
        ->assertOk()
        ->assertSee('0 / 41')
        ->assertSee('Entry opens 19:30')
        ->assertSee('No scan attempts yet for this show.');
});

test('the scan screen explains when there is nothing left to scan', function () {
    config(['staff-mock.now' => '2026-10-15 23:00']);

    $this->get(route('staff.scan'))
        ->assertOk()
        ->assertSee('No shows to scan right now')
        ->assertDontSee('Scan a ticket');
});

test('submitting a token returns to the scan screen for the same show', function () {
    $this->post(route('staff.scan.store'), ['token' => 'abc', 'show' => 4])
        ->assertRedirect(route('staff.scan', ['show' => 4]));
});
