<x-guest-layout title="Verify your email" subtitle="Verification is optional. Choose whether to verify now or continue.">
    <div class="auth-mail-icon" aria-hidden="true">✉</div>
    <div class="auth-notice auth-notice--card">
        @if (session('status') === 'verification-link-sent')
            <strong>Verification email sent</strong>
            <span>A new verification link was sent to your email address.</span>
        @else
            <strong>Your email address is not verified</strong>
            <span>{{ auth()->user()->email }}</span>
            <span>Verification is optional. Send yourself a link to verify this address, or continue without verifying.</span>
        @endif

        <form method="POST" action="{{ route('verification.send') }}">
            @csrf
            <button class="button button--primary" type="submit">
                {{ session('status') === 'verification-link-sent' ? 'Resend verification email' : 'Send verification email' }}
            </button>
        </form>
        <form method="POST" action="{{ route('verification.continue') }}">
            @csrf
            <button class="button button--quiet" type="submit">Continue without verifying</button>
        </form>
        <p class="auth-help">You can verify your email later from your profile.</p>
    </div>

    <form method="POST" action="{{ route('logout') }}">
        @csrf
        <button class="auth-text-button" type="submit">Sign out</button>
    </form>
</x-guest-layout>
