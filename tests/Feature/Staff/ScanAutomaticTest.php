<?php

use App\Models\User;

// The staff screens are only open to staff, so these tests run as one.
beforeEach(function () {
    config(['staff.data_source' => 'mock']);
    $this->actingAs(User::factory()->staff()->create());
});

test('the scan screen tells staff a scanned QR code is checked automatically', function () {
    $this->get(route('staff.scan'))
        ->assertOk()
        ->assertSee('checked automatically')
        ->assertSee('Or type the token and press Enter');
});

test('the token box is a focused, required field in the form that posts the scan', function () {
    $this->get(route('staff.scan', ['show' => 4]))
        ->assertSee('name="token"', false)
        ->assertSee('autofocus', false)
        ->assertSee('required', false)
        ->assertSee('data-scan-input', false)
        ->assertSee(route('staff.scan.store'), false);
});

test('after an admission the box is ready for the next scan with the same hint', function () {
    $this->followingRedirects()
        ->post(route('staff.scan.store'), ['token' => 'DEMO-ADMIT-G7', 'show' => 4])
        ->assertSee('ADMITTED')
        ->assertSee('data-scan-input', false)
        ->assertSee('Scan the next QR code and it is checked automatically');
});

test('after a rejection the box is ready for the next scan', function () {
    $this->followingRedirects()
        ->post(route('staff.scan.store'), ['token' => 'not-a-ticket', 'show' => 4])
        ->assertSee('We couldn’t recognize this ticket.')
        ->assertSee('data-scan-input', false);
});

test('a scanned token with stray whitespace from the scanner is still read', function () {
    $this->followingRedirects()
        ->post(route('staff.scan.store'), ['token' => "  DEMO-ADMIT-G7\r\n", 'show' => 4])
        ->assertSee('ADMITTED');
});
