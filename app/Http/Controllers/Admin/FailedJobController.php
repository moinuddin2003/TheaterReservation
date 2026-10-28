<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\AuditLogger;
use Illuminate\Database\Query\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Queue\CallQueuedClosure;
use Illuminate\Support\Carbon;
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

    public function retry(Request $request, string $failedJob, AuditLogger $auditLogger): RedirectResponse
    {
        $job = DB::table('failed_jobs')->where('id', $failedJob)->first();
        abort_unless($job, 404);
        abort_unless(
            Schema::hasColumn('failed_jobs', 'payload') && filled($job->payload ?? null),
            409,
            'This failed job cannot be retried because it has no stored payload.',
        );

        Artisan::call('queue:retry', ['id' => [$job->uuid]]);
        $auditLogger->logResource(
            $request->user(),
            'failed_job.retried',
            'FailedJob',
            (int) $job->id,
            $this->jobAuditData($job),
            ['uuid' => $job->uuid, 'queue' => $job->queue, 'status' => 'queued'],
            $request->ip(),
        );

        return to_route('admin.failed-jobs.index')->with('status', 'Failed job queued for retry.');
    }

    public function destroy(Request $request, string $failedJob, AuditLogger $auditLogger): RedirectResponse
    {
        DB::transaction(function () use ($request, $failedJob, $auditLogger): void {
            $job = DB::table('failed_jobs')->where('id', $failedJob)->lockForUpdate()->first();
            abort_unless($job, 404);

            DB::table('failed_jobs')->where('id', $failedJob)->delete();
            $auditLogger->logResource(
                $request->user(),
                'failed_job.deleted',
                'FailedJob',
                (int) $job->id,
                $this->jobAuditData($job),
                [],
                $request->ip(),
            );
        });

        return to_route('admin.failed-jobs.index')->with('status', 'Failed job removed.');
    }

    private function filteredJobs(string $jobClass, ?Carbon $since, string $summary): Builder
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

    /**
     * @return array<string, mixed>
     */
    private function jobAuditData(object $job): array
    {
        return [
            'uuid' => $job->uuid,
            'queue' => $job->queue,
            'failed_at' => $job->failed_at,
            'job_class' => $this->jobClass($this->withPayloadFallback($job)->payload),
        ];
    }
}
