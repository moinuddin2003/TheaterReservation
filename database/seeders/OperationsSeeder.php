<?php

namespace Database\Seeders;

use App\Models\AuditLog;
use App\Models\Booking;
use App\Models\Movie;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Queue\CallQueuedClosure;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\DB;

class OperationsSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::query()->where('email', 'admin@cinebook.test')->firstOrFail();

        $this->seedAuditLogs($admin);
        $this->seedFailedJobs();
    }

    private function seedAuditLogs(User $admin): void
    {
        AuditLog::query()
            ->where('ip_address', 'like', '198.51.100.%')
            ->delete();

        $movieId = Movie::query()->value('id') ?? 0;
        $bookingId = Booking::query()->where('reference', 'DEMO-PAY-001')->value('id') ?? 0;
        $entries = [
            ['settings.updated', Setting::class, 0, 'hold_minutes', '10', '15'],
            ['settings.updated', Setting::class, 0, 'max_seats_per_booking', '6', '8'],
            ['movie.updated', Movie::class, $movieId, 'status', 'draft', 'published'],
            ['booking.cancelled', Booking::class, $bookingId, 'status', 'confirmed', 'cancelled'],
        ];

        foreach ($entries as $index => [$action, $type, $id, $field, $before, $after]) {
            AuditLog::query()->create([
                'user_id' => $admin->id,
                'action' => $action,
                'auditable_type' => $type,
                'auditable_id' => $id,
                'before' => $type === Setting::class ? ['key' => $field, 'value' => $before] : [$field => $before],
                'after' => $type === Setting::class ? ['key' => $field, 'value' => $after] : [$field => $after],
                'ip_address' => '198.51.100.'.(10 + $index),
                'created_at' => now()->subHours($index + 1),
            ]);
        }
    }

    private function seedFailedJobs(): void
    {
        $uuids = [
            '00000000-0000-4000-8000-000000000001',
            '00000000-0000-4000-8000-000000000002',
        ];

        DB::table('failed_jobs')->whereIn('uuid', $uuids)->delete();

        $jobs = [
            ['SendBookingConfirmation', 'MailTransportTimeout', 'Delivery timed out after 30 seconds.', 20],
            ['ReleaseExpiredSeatHold', 'RedisConnectionException', 'Queue connection refused.', 90],
        ];

        foreach ($jobs as $index => [$jobClass, $exception, $message, $minutesAgo]) {
            $queueJobId = Queue::connection('database')->push(
                CallQueuedClosure::create(static function (): void {})->name($jobClass),
                '',
                'notifications',
            );
            $payload = DB::table('jobs')->where('id', $queueJobId)->value('payload');
            DB::table('jobs')->where('id', $queueJobId)->delete();

            if (! is_string($payload)) {
                throw new \RuntimeException("Unable to create a queue payload for demo job [{$jobClass}].");
            }

            $data = [
                'uuid' => $uuids[$index],
                'connection' => 'database',
                'queue' => 'notifications',
                'payload' => $payload,
                'exception' => "{$exception}\n{$jobClass}: {$message}",
                'failed_at' => now()->subMinutes($minutesAgo),
            ];

            DB::table('failed_jobs')->insert($data);
        }
    }
}
