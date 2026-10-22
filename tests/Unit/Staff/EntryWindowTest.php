<?php

use App\Services\Staff\EntryWindow;

function staffShowWindow(): EntryWindow
{
    return EntryWindow::forShowStart(new DateTimeImmutable('2026-10-15 19:30'), 60, 30);
}

test('entry window runs from the configured minutes before to after the show starts', function () {
    expect(staffShowWindow()->label())->toBe('18:30–20:00');
});

test('entry window status follows the opening and closing time', function (string $time, string $expected) {
    expect(staffShowWindow()->statusAt(new DateTimeImmutable("2026-10-15 {$time}")))->toBe($expected);
})->with([
    'a minute before opening' => ['18:29', EntryWindow::NOT_OPEN],
    'exactly at opening' => ['18:30', EntryWindow::OPEN],
    'exactly at closing' => ['20:00', EntryWindow::OPEN],
    'a minute after closing' => ['20:01', EntryWindow::CLOSED],
]);
