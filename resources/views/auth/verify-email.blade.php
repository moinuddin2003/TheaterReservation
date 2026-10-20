<x-guest-layout title="Check your inbox" subtitle="One quick step before your next movie night.">
    <div class="auth-mail-icon" aria-hidden="true">✉</div>
    <div class="auth-notice auth-notice--card">
        @if (session('status') === 'verification-link-sent')
            <strong>Verification email sent</strong>
            <span>A new verification link was sent to your email address.</span>
        @else
            <strong>We sent a verification link to</strong>
            <span>{{ auth()->user()->email }}</span>
            <span>Open the link to verify your email. You can browse movies now, but seat holds and checkout stay locked until verification.</span>
        @endif

        <form method="POST" action="{{ route('verification.send') }}">
            @csrf
            <button class="button button--primary" type="submit">Resend verification email</button>
        </form>
        <p class="auth-help">Email sent · You can request another link at any time.</p>
    </div>

    <p class="auth-switch"><a href="{{ route('movies.index') }}">Continue browsing →</a></p>
    <form method="POST" action="{{ route('logout') }}">
        @csrf
        <button class="auth-text-button" type="submit">Sign out</button>
    </form>
</x-guest-layout>
