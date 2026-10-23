@props(['summary', 'show'])

<div class="roster-toolbar">
    <p class="roster-toolbar__admitted">✓ {{ $summary['admitted'] }} / {{ $summary['seats'] }} admitted</p>
    <p class="roster-toolbar__note">{{ $summary['bookings'] }} confirmed {{ Str::plural('booking', $summary['bookings']) }} · Read-only admission roster</p>
    <x-staff.link to="staff.scan" :params="['show' => $show['id']]" class="button button--quiet">Back to scanner →</x-staff.link>
</div>
