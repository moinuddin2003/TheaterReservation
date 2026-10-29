@extends('layouts.staff')

@section('title', 'Your account')

@section('active', '')

@section('content')
    <div class="page-shell account-page">
        <p class="eyebrow">ACCOUNT <span aria-hidden="true">/</span> PROFILE</p>
        <h1 class="booking-page__title">Your account</h1>

        <div class="account-layout">
            <aside class="account-summary">
                <p class="eyebrow">{{ $cinema?->name ?? config('app.name') }}</p>
                <h2>{{ $user->name }}</h2>
                <p>{{ $user->hasVerifiedEmail() ? '✓ Email verified' : 'Email not verified' }}</p>
                <p>Staff account</p>
            </aside>

            <div class="account-forms">
                <section class="account-panel">
                    @include('profile.partials.update-profile-information-form')
                </section>

                <section class="account-panel">
                    @include('profile.partials.update-password-form')
                </section>
            </div>
        </div>
    </div>
@endsection
