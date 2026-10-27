<x-admin.layout title="{{ $jobClass }} · delivery failed" breadcrumb="Operations / Failed jobs">
    <div class="mb-5 rounded-md bg-[#164b3d] px-3 py-3 text-xs leading-5 text-cine-primary">✓ Booking unchanged · still confirmed<br>This retry or delete action does not change the booking, seats or payment.</div>
    <div class="grid gap-4 xl:grid-cols-[minmax(0,1.35fr)_minmax(280px,.65fr)]">
        <x-admin.card>
            <h2 class="text-lg font-bold text-cine-primary">{{ $jobClass }}</h2>
            <dl class="mt-5 space-y-4 text-xs text-cine-secondary"><div><dt class="text-cine-tertiary">Failed at</dt><dd class="mt-1">{{ \Illuminate\Support\Carbon::parse($job->failed_at)->format('M j, Y · H:i:s') }}</dd></div><div><dt class="text-cine-tertiary">Queue</dt><dd class="mt-1">{{ $job->queue }}</dd></div><div><dt class="text-cine-tertiary">Connection</dt><dd class="mt-1">{{ $job->connection }}</dd></div><div><dt class="text-cine-tertiary">Sanitized exception</dt><dd class="mt-1 whitespace-pre-wrap leading-5">{{ $job->exception }}</dd></div></dl>
        </x-admin.card>
        <div class="space-y-4">
            <x-admin.card title="Try delivery again"><p class="text-sm leading-6 text-cine-secondary">Queue one retry of this existing failed job. The reservation remains confirmed.</p>@if ($canRetry)<form method="POST" action="{{ route('admin.failed-jobs.retry', $job->id) }}" class="mt-4">@csrf<x-admin.button type="submit">Confirm retry</x-admin.button></form>@else<p class="mt-3 text-xs text-cine-warning">Retry is unavailable because the failed_jobs table has no payload column.</p>@endif</x-admin.card>
            <x-admin.card title="Remove this failed job"><p class="text-sm leading-6 text-cine-secondary">This removes the failed queue record, not the booking. No email is sent. This cannot be undone.</p><form method="POST" action="{{ route('admin.failed-jobs.destroy', $job->id) }}" class="mt-4" onsubmit="return confirm('Remove this failed job?')">@csrf @method('DELETE')<x-admin.button type="submit" variant="danger">Confirm delete</x-admin.button></form></x-admin.card>
        </div>
    </div>
</x-admin.layout>
