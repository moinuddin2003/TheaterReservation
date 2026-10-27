<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Queue\CallQueuedClosure;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;

class FailedJobController extends Controller
{
    public function index(Request $request): View
    {
        $jobClass = trim((string) $request->query('job_class', ''));
        $range = (string) $request->query('range', '24h');
        $summary = trim((string) $request->query('summary', ''));
        $since = match ($range) {
            '7d' => now()->subDays(7),
            'all' => null,
            default => now()->subDay(),
        };

        $failedJobs = $this->filteredJobs($jobClass, $since, $summary)->paginate(15)->withQueryString();
        $failedJobs->getCollection()->transform(fn (object $job): object => $this->withPayloadFallback($job));

        return view('admin.failed-jobs.index', [
            'failedJobs' => $failedJobs,
            'jobClass' => $jobClass,
            'range' => $range,
            'summary' => $summary,
        ]);
    }

    public function show(string $failedJob): View
    {
        $job = DB::table('failed_jobs')->where('id', $failedJob)->first();
        abort_unless($job, 404);

        return view('admin.failed-jobs.show', [
            'job' => $this->withPayloadFallback($job),
            'jobClass' => $this->jobClass($this->withPayloadFallback($job)->payload),
            'canRetry' => Schema::hasColumn('failed_jobs', 'payload') && filled($job->payload ?? null),
        ]);
    }

    public function retry(string $failedJob): RedirectResponse
    {
        $job = DB::table('failed_jobs')->where('id', $failedJob)->first();
        abort_unless($job, 404);
        abort_unless(
            Schema::hasColumn('failed_jobs', 'payload') && filled($job->payload ?? null),
            409,
            'This failed job cannot be retried because it has no stored payload.',
        );

        Artisan::call('queue:retry', ['id' => [$job->uuid]]);

        return to_route('admin.failed-jobs.index')->with('status', 'Failed job queued for retry.');
    }

    public function destroy(string $failedJob): RedirectResponse
    {
        $deleted = DB::table('failed_jobs')->where('id', $failedJob)->delete();
        abort_unless($deleted, 404);

        return to_route('admin.failed-jobs.index')->with('status', 'Failed job removed.');
    }

    private function filteredJobs(string $jobClass, ?\Illuminate\Support\Carbon $since, string $summary): \Illuminate\Database\Query\Builder
    {
        return DB::table('failed_jobs')
            ->when($since, fn ($query) => $query->where('failed_at', '>=', $since))
            ->when($summary !== '', fn ($query) => $query->where(function ($query) use ($summary): void {
                $query->where('exception', 'like', "%{$summary}%")
                    ->when(Schema::hasColumn('failed_jobs', 'payload'), fn ($query) => $query->orWhere('payload', 'like', "%{$summary}%"));
            }))
            ->when($jobClass !== '', fn ($query) => $query->where(function ($query) use ($jobClass): void {
                $query->where('exception', 'like', "%{$jobClass}%")
                    ->when(Schema::hasColumn('failed_jobs', 'payload'), fn ($query) => $query->orWhere('payload', 'like', "%{$jobClass}%"));
            }))
            ->latest('failed_at');
    }

    private function withPayloadFallback(object $job): object
    {
        if (! isset($job->payload)) {
            $job->payload = json_encode([
                'displayName' => $this->jobClassFromException($job->exception),
            ], JSON_THROW_ON_ERROR);
        }

        return $job;
    }

    private function jobClassFromException(string $exception): string
    {
        $line = str($exception)->after("\n")->before(':')->trim()->toString();

        return $line !== '' ? $line : 'Unknown job';
    }

    private function jobClass(string $payload): string
    {
        $decoded = json_decode($payload, true);
        $command = $decoded['data']['commandName'] ?? 'Unknown job';

        if ($command === CallQueuedClosure::class && is_string($decoded['displayName'] ?? null)) {
            return str($decoded['displayName'])->before(' - Closure')->toString();
        }

        return is_string($command) ? class_basename($command) : 'Unknown job';
    }
}
