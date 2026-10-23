@props(['message' => null])

{{-- Shown once after a token that is not a ticket was submitted. --}}
@if ($message)
    <p class="scan-error" role="alert">⚠ {{ $message }}</p>
@endif
