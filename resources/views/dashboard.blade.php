@extends('layouts.cinema')

@section('title', 'Dashboard — ' . ($cinema?->name ?? config('app.name')))

@section('content')
    <div class="page-shell booking-page">
        <p class="eyebrow">ACCOUNT <span aria-hidden="true">/</span> DASHBOARD</p>
        <h1 class="booking-page__title">Welcome to {{ $cinema?->name ?? config('app.name') }}, {{ auth()->user()->name }}
        </h1>
        <p class="booking-page__intro">Find your next movie night or review your bookings.</p>

        <section class="booking-panel">
            <h2>What would you like to do?</h2>
            <div class="booking-list-card__actions">
                <a class="button button--primary" href="{{ route('movies.index') }}">
                    Browse movies <span aria-hidden="true">→</span>
                </a>
                <a class="button button--quiet" href="{{ route('bookings.index') }}">
                    My bookings <span aria-hidden="true">→</span>
                </a>
            </div>
        </section>
    </div>
@endsection