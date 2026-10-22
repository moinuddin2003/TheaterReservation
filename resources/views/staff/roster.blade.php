@extends('layouts.staff')

@section('title', 'Show roster')
@section('active', 'roster')

@section('content')
    <x-staff.page-head title="Show roster" :date="$now" />

    @if ($roster['selected'])
        <x-staff.roster-filters :shows="$roster['shows']" :selected="$roster['selected']" :query="$roster['query']" />
        <x-staff.roster-toolbar :summary="$roster['summary']" :show="$roster['selected']" />
        <x-staff.roster-table :rows="$roster['rows']" :query="$roster['query']" />
        <x-staff.roster-pager :roster="$roster" />
        <x-staff.notice title="Admission information only">
            Names, references, seats and scan counts. This view does not show email addresses or payment information, and admission can’t be reset.
        </x-staff.notice>

        @push('scripts')
            <x-staff.roster-search-script />
        @endpush
    @else
        <section class="staff-panel scan-empty">
            <h2>No shows today</h2>
            <p>There is no roster to show.</p>
            <div class="staff-actions">
                <x-staff.link to="staff.today" class="button button--quiet">Back to today’s shows</x-staff.link>
            </div>
        </section>
    @endif
@endsection
