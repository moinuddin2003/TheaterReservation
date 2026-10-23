<?php

use App\Services\Staff\ScanRejectionView;

function rejectionFacts(string $status, string $at, array $extra = []): array
{
    $horizon = ['movie' => 'The Last Horizon', 'screen' => 'Screen 1', 'starts_at' => '2026-10-15 19:30'];

    return array_merge([
        'status' => $status,
        'seat' => 'G7',
        'used_at' => null,
        'at' => "2026-10-15 {$at}:00",
        'ticket_show' => $horizon,
        'selected_show' => $horizon,
        'opens_at' => '2026-10-15 18:30:00',
        'closes_at' => '2026-10-15 20:00:00',
    ], $extra);
}

function rejectionText(string $status, string $at, array $extra = []): array
{
    return (new ScanRejectionView)->make(rejectionFacts($status, $at, $extra));
}

test('already used names the ticket, when it was used and the entry window', function () {
    expect(rejectionText('already_used', '19:02', ['used_at' => '18:58']))->toBe([
        'status' => 'already_used',
        'tone' => 'danger',
        'label' => 'Already used',
        'headline' => 'G7 · The Last Horizon · Screen 1',
        'detail' => 'This ticket was admitted today at 18:58. A ticket can only be used once. There is no reset or undo.',
        'context' => 'Current time 19:02 · Entry window 18:30–20:00',
    ]);
});

test('wrong showtime compares the ticket\'s show with the selected show', function () {
    $text = rejectionText('wrong_showtime', '18:55', [
        'ticket_show' => ['movie' => 'Night Circuit', 'screen' => 'Screen 2 IMAX', 'starts_at' => '2026-10-15 18:00'],
    ]);

    expect($text['headline'])->toBe('This ticket is for Night Circuit · 18:00 · Screen 2 IMAX.')
        ->and($text['detail'])->toBe('You’re scanning The Last Horizon · 19:30 · Screen 1. Choose the matching showtime before scanning again.')
        ->and($text['context'])->toBe('Current time 18:55 · Selected show 19:30');
});

test('too early says when entry opens and uses the warning tone', function () {
    $text = rejectionText('too_early', '18:10');

    expect($text['tone'])->toBe('warning')
        ->and($text['headline'])->toBe('Entry opens today at 18:30.')
        ->and($text['detail'])->toBe('The Last Horizon · Screen 1 · 19:30. Come back when the entry window opens, 60 minutes before the show.')
        ->and($text['context'])->toBe('Current time 18:10 · Opens in 20 minutes');
});

test('expired says when entry closed and how long ago', function () {
    $text = rejectionText('expired', '20:05');

    expect($text['headline'])->toBe('Entry closed today at 20:00.')
        ->and($text['detail'])->toBe('The Last Horizon · Screen 1 · 19:30. This ticket is more than 30 minutes past the show start and can no longer be admitted.')
        ->and($text['context'])->toBe('Current time 20:05 · Closed 5 minutes ago');
});

test('a void ticket says do not admit', function () {
    $text = rejectionText('void', '18:55');

    expect($text['label'])->toBe('Void · Do not admit')
        ->and($text['headline'])->toBe('This ticket was cancelled or refunded.')
        ->and($text['detail'])->toBe('G7 · The Last Horizon · Screen 1. The booking is no longer valid. A refund state never makes a void ticket valid again.')
        ->and($text['context'])->toBe('Current time 18:55 · Entry window 18:30–20:00');
});

test('an invalid ticket suggests scanning again or using the reference lookup', function () {
    $text = rejectionText('invalid', '18:55', ['seat' => null, 'ticket_show' => null, 'opens_at' => null, 'closes_at' => null]);

    expect($text['headline'])->toBe('We couldn’t recognize this ticket.')
        ->and($text['detail'])->toBe('No valid ticket matches this token. Try scanning once more, type the token carefully or use the booking reference lookup.')
        ->and($text['context'])->toBe('Current time 18:55 · Selected show 19:30');
});

test('an invalid ticket without a selected show shows only the time', function () {
    $text = rejectionText('invalid', '18:55', ['selected_show' => null, 'ticket_show' => null]);

    expect($text['context'])->toBe('Current time 18:55');
});

test('one minute is not pluralised', function () {
    expect(rejectionText('too_early', '18:29')['context'])->toBe('Current time 18:29 · Opens in 1 minute');
});
