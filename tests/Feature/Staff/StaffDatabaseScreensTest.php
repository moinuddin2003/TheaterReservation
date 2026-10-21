<?php

use App\Contracts\Staff\StaffData;
use App\Models\User;
use App\Services\Staff\EloquentStaffData;
use App\Services\Staff\MockStaffData;
use Illuminate\Support\Carbon;
use Tests\Support\StaffDbFixture;

// Every staff screen again, now reading the database (STAFF_DATA_SOURCE=database).
beforeEach(function () {
    config(['staff.data_source' => 'database']);
    Carbon::setTestNow('2026-10-15 18:55:00');
    $this->actingAs(User::factory()->staff()->create(['name' => 'Jamie Okafor']));

    $this->show = StaffDbFixture::show('2026-10-15 19:30');
    StaffDbFixture::booking($this->show, 'CB-261015-0482', 'Alex Morgan', ['G7' => 'premium', 'G8' => 'premium'], used: ['G7']);
});

afterEach(fn () => Carbon::setTestNow());

test('the data source setting picks mock or database data', function () {
    expect(app(StaffData::class))->toBeInstanceOf(EloquentStaffData::class);

    config(['staff.data_source' => 'mock']);

    expect(app(StaffData::class))->toBeInstanceOf(MockStaffData::class);
});

test('today shows the real show and its counts', function () {
    $this->get(route('staff.today'))
        ->assertOk()
        ->assertSee('The Last Horizon')
        ->assertSee('19:30')
        ->assertSee('Screen 1')
        ->assertSee('Jamie Okafor')
        ->assertDontSee('Night Circuit');
});

test('the roster lists the real booking', function () {
    $this->get(route('staff.roster'))
        ->assertOk()
        ->assertSee('Alex Morgan')
        ->assertSee('CB-261015-0482')
        ->assertSee('G7')
        ->assertSee('G8');
});

test('the lookup finds the real booking and its tickets', function () {
    $this->get(route('staff.lookup', ['reference' => 'cb-261015-0482']))
        ->assertOk()
        ->assertSee('Alex Morgan')
        ->assertSee('The Last Horizon · Oct 15, 2026 · 19:30')
        ->assertSee('G7 · Premium')
        ->assertSee('T-0482-01')
        ->assertSee('Used · Oct 15 at 18:48')
        ->assertSee('Not scanned')
        ->assertSee('2 / 1')
        ->assertSee('18:30–20:00');
});

test('the lookup finds a booking for a show on another day', function () {
    StaffDbFixture::booking(StaffDbFixture::show('2026-10-20 12:00'), 'CB-261020-0001', 'Later Lee', ['B2' => 'standard']);

    $this->get(route('staff.lookup', ['reference' => 'CB-261020-0001']))
        ->assertSee('Later Lee')
        ->assertSee('Oct 20, 2026');
});

test('the lookup says no booking found for an unknown reference', function () {
    $this->get(route('staff.lookup', ['reference' => 'CB-000000-0000']))->assertSee('No booking found');
});

test('scanning a real ticket admits the guest and the screens show it', function () {
    $token = StaffDbFixture::token('CB-261015-0482', 'G8');

    $this->followingRedirects()
        ->post(route('staff.scan.store'), ['token' => $token, 'show' => $this->show->id])
        ->assertOk()
        ->assertSee('ADMITTED')
        ->assertSee('G8')
        ->assertSee('T-0482-02')
        ->assertSee('2 / 2');

    $this->get(route('staff.scan', ['show' => $this->show->id]))
        ->assertSee('Ready when they are.')
        ->assertSee('2 / 2')
        ->assertSee('T-0482-02');

    $this->get(route('staff.lookup', ['reference' => 'CB-261015-0482']))->assertDontSee('Not scanned');
});

test('scanning the same real ticket again shows already used and the attempt list', function () {
    $token = StaffDbFixture::token('CB-261015-0482', 'G7');

    $this->followingRedirects()
        ->post(route('staff.scan.store'), ['token' => $token, 'show' => $this->show->id])
        ->assertSee('Already used')
        ->assertSee('This ticket was admitted today at 18:48.')
        ->assertSee('1 / 2')
        ->assertSee('Already used · 18:48');
});

test('an unknown token shows the invalid result and is listed as an attempt', function () {
    $this->followingRedirects()
        ->post(route('staff.scan.store'), ['token' => 'nope', 'show' => $this->show->id])
        ->assertSee('We couldn’t recognize this ticket.')
        ->assertSee('Recent 10 attempts');

    $this->get(route('staff.scan', ['show' => $this->show->id]))->assertSee('Invalid');
});

test('a day with no shows still opens every staff page', function () {
    Carbon::setTestNow('2026-11-01 12:00:00');

    foreach (['staff.today', 'staff.scan', 'staff.roster', 'staff.lookup'] as $route) {
        $this->get(route($route))->assertOk();
    }
});
