@props(['title' => 'Admission desk'])

<aside class="staff-notice">
    <p><strong>ⓘ {{ $title }}</strong></p>
    <p>{{ $slot }}</p>
</aside>
