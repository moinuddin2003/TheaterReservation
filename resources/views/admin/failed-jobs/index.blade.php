<x-admin.layout title="Failed jobs" breadcrumb="Operations">
    <x-admin.page-header title="Failed jobs" subtitle="Background tasks only · Current booking states remain authoritative." />
    <p class="mb-5 text-xs text-cine-danger">{{ $failedJobs->total() }} need review</p>
    <form method="GET" class="mb-5 grid gap-3 md:grid-cols-3">
        <input name="job_class" value="{{ $jobClass }}" placeholder="Job class" class="rounded-lg border border-cine-border/10 bg-cine-surface px-3 py-3 text-xs text-cine-primary placeholder:text-cine-tertiary">
        <select name="range" class="rounded-lg border border-cine-border/10 bg-cine-surface px-3 py-3 text-xs text-cine-primary"><option value="24h" @selected($range === '24h')>Last 24 hours</option><option value="7d" @selected($range === '7d')>Last 7 days</option><option value="all" @selected($range === 'all')>All time</option></select>
        <div class="flex gap-2"><input name="summary" value="{{ $summary }}" placeholder="Search reference or message..." class="min-w-0 flex-1 rounded-lg border border-cine-border/10 bg-cine-surface px-3 py-3 text-xs text-cine-primary placeholder:text-cine-tertiary"><x-admin.button type="submit">Filter</x-admin.button></div>
    </form>
    <x-admin.card class="overflow-hidden !p-0">
        <div class="overflow-x-auto"><table class="w-full min-w-[820px] text-left text-xs"><thead class="border-b border-cine-border/10 text-[10px] uppercase tracking-wider text-cine-tertiary"><tr><th class="px-5 py-4">Failed at</th><th class="px-5 py-4">Job class</th><th class="px-5 py-4">Summary</th><th class="px-5 py-4">Exception</th><th class="px-5 py-4">Action</th></tr></thead><tbody class="divide-y divide-cine-border/10">
            @forelse ($failedJobs as $job)
                @php $decoded = json_decode($job->payload, true); $class = $decoded['data']['commandName'] ?? $decoded['displayName'] ?? 'Unknown job'; @endphp
                <tr class="text-cine-secondary"><td class="px-5 py-4">{{ \Illuminate\Support\Carbon::parse($job->failed_at)->format('M j · H:i') }}</td><td class="px-5 py-4">{{ class_basename($class) }}</td><td class="px-5 py-4">{{ str($job->exception)->before("\n")->limit(72) }}</td><td class="max-w-[260px] truncate px-5 py-4">{{ str($job->exception)->after("\n")->limit(90) }}</td><td class="px-5 py-4"><a href="{{ route('admin.failed-jobs.show', $job->id) }}" class="font-semibold text-cine-accent hover:underline">Details →</a></td></tr>
            @empty
                <tr><td colspan="5" class="px-5 py-14"><x-admin.empty-state icon="circle-check" title="No failed jobs" text="The queue is healthy." /></td></tr>
            @endforelse
        </tbody></table></div>
    </x-admin.card>
    <div class="mt-4">{{ $failedJobs->links() }}</div>
    <div class="mt-4 rounded-md bg-[#3b3020] px-3 py-3 text-xs leading-5 text-cine-primary">ⓘ Email failure never changes a booking<br>Booking state remains authoritative; failed notification jobs can be retried independently.</div>
</x-admin.layout>
