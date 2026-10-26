<?php

namespace Database\Seeders;

use App\Enums\BookingStatus;
use App\Enums\PaymentStatus;
use App\Enums\RefundStatus;
use App\Enums\Role;
use App\Enums\TicketStatus;
use App\Models\Booking;
use App\Models\BookingItem;
use App\Models\Payment;
use App\Models\Refund;
use App\Models\Seat;
use App\Models\Showtime;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PaymentSeeder extends Seeder
{
    /**
     * Create repeatable demo payments for the admin payment screen.
     */
    public function run(): void
    {
        $showtime = Showtime::query()
            ->upcoming()
            ->with('screen')
            ->orderBy('starts_at')
            ->firstOrFail();

        $customers = [
            $this->customer('Alex Morgan', 'alex.morgan.demo@cinebook.test'),
            $this->customer('Maya Chen', 'maya.chen.demo@cinebook.test'),
            $this->customer('Nash Patel', 'nash.patel.demo@cinebook.test'),
            $this->customer('Ella Brooks', 'ella.brooks.demo@cinebook.test'),
            $this->customer('Ava Clark', 'ava.clark.demo@cinebook.test'),
        ];

        $examples = [
            ['succeeded', 2400, 0, 15, null],
            ['pending', 1200, 0, 14, null],
            ['succeeded', 2000, 1, 29, null],
            ['failed', 1600, 2, 38, 'The card was declined.'],
            ['succeeded', 2800, 3, 52, null],
            ['pending', 1400, 0, 12, null],
            ['succeeded', 1800, 0, 9, null],
            ['expired', 1200, 1, 90, 'Checkout session expired.'],
        ];

        DB::transaction(function () use ($examples, $customers, $showtime): void {
            foreach ($examples as $index => [$status, $amountCents, $customerIndex, $minutesAgo, $failureReason]) {
                $number = $index + 1;
                $reference = sprintf('DEMO-PAY-%03d', $number);
                $createdAt = now()->subMinutes($minutesAgo);
                $paymentStatus = PaymentStatus::from($status);
                $bookingStatus = match ($paymentStatus) {
                    PaymentStatus::Succeeded => BookingStatus::Confirmed,
                    PaymentStatus::Pending => BookingStatus::PendingPayment,
                    PaymentStatus::Failed => BookingStatus::Failed,
                    PaymentStatus::Expired => BookingStatus::Expired,
                    PaymentStatus::Refunded => BookingStatus::Refunded,
                };
                $booking = Booking::query()->firstOrNew(['reference' => $reference]);
                $booking->forceFill([
                    'user_id' => $customers[$customerIndex]->getKey(),
                    'showtime_id' => $showtime->getKey(),
                    'status' => $bookingStatus,
                    'seats_count' => 1,
                    'total_cents' => $amountCents,
                    'currency' => 'USD',
                    'hold_expires_at' => $createdAt->copy()->addMinutes(10),
                    'confirmed_at' => $paymentStatus === PaymentStatus::Succeeded ? $createdAt : null,
                    'created_at' => $createdAt,
                    'updated_at' => $createdAt,
                ])->save();

                $payment = Payment::query()->firstOrNew([
                    'idempotency_key' => sprintf('demo-payment-%03d', $number),
                ]);
                $payment->forceFill([
                    'booking_id' => $booking->getKey(),
                    'gateway' => 'demo',
                    'gateway_session_id' => null,
                    'gateway_payment_intent_id' => null,
                    'amount_cents' => $amountCents,
                    'currency' => 'USD',
                    'status' => $paymentStatus,
                    'failure_reason' => $failureReason,
                    'paid_at' => $paymentStatus === PaymentStatus::Succeeded ? $createdAt : null,
                    'created_at' => $createdAt,
                    'updated_at' => $createdAt,
                ])->save();

                if ($paymentStatus !== PaymentStatus::Succeeded) {
                    continue;
                }

                $seat = Seat::query()
                    ->where('screen_id', $showtime->screen_id)
                    ->where('is_disabled', false)
                    ->whereDoesntHave('bookingItems', fn (Builder $query) => $query
                        ->where('showtime_id', $showtime->getKey())
                        ->where('status', 'active')
                        ->where('booking_id', '!=', $booking->getKey()))
                    ->whereDoesntHave('seatHolds', fn (Builder $query) => $query
                        ->where('showtime_id', $showtime->getKey())
                        ->where('status', 'active')
                        ->where('expires_at', '>', now()))
                    ->orderBy('row_label')
                    ->orderBy('seat_number')
                    ->first();

                if (! $seat) {
                    throw new \RuntimeException('Payment demo data requires an available seat on its upcoming showtime.');
                }

                $item = BookingItem::query()->firstOrNew([
                    'booking_id' => $booking->getKey(),
                    'seat_id' => $seat->getKey(),
                ]);
                $item->forceFill([
                    'showtime_id' => $showtime->getKey(),
                    'seat_label' => $seat->label,
                    'seat_type' => $seat->type->value,
                    'price_cents' => $amountCents,
                    'status' => 'active',
                ])->save();

                $ticket = Ticket::query()->firstOrNew(['booking_item_id' => $item->getKey()]);
                $ticket->forceFill([
                    'booking_id' => $booking->getKey(),
                    'showtime_id' => $showtime->getKey(),
                    'ticket_number' => sprintf('DEMO-TICKET-%03d', $number),
                    'token' => hash('sha1', $reference),
                    'status' => TicketStatus::Valid,
                ])->save();
            }

            $refundExamples = [
                ['demo-payment-001', 1200, RefundStatus::Pending, 'requested_by_customer', 2, null],
                ['demo-payment-005', 1000, RefundStatus::Pending, 'duplicate', 3, null],
                ['demo-payment-003', 1000, RefundStatus::Succeeded, 'requested_by_customer', 6, 're_demo_refund_003'],
                ['demo-payment-007', 800, RefundStatus::Failed, 'fraudulent', 9, 're_demo_refund_004'],
                ['demo-payment-007', 1000, RefundStatus::Succeeded, 'duplicate', 12, 're_demo_refund_005'],
            ];

            foreach ($refundExamples as $index => [$paymentKey, $amountCents, $status, $reason, $daysAgo, $gatewayRefundId]) {
                $payment = Payment::query()->where('idempotency_key', $paymentKey)->firstOrFail();
                $createdAt = now()->subDays($daysAgo);
                $refund = Refund::query()->firstOrNew([
                    'idempotency_key' => sprintf('demo-refund-%03d', $index + 1),
                ]);
                $refund->forceFill([
                    'payment_id' => $payment->getKey(),
                    'booking_id' => $payment->booking_id,
                    'amount_cents' => $amountCents,
                    'currency' => $payment->currency,
                    'status' => $status,
                    'reason' => $reason,
                    'gateway_refund_id' => $gatewayRefundId,
                    'failure_message' => $status === RefundStatus::Failed
                        ? 'Temporary connection error. Retry the existing refund.'
                        : null,
                    'initiated_by' => User::query()->where('email', 'admin@cinebook.test')->value('id'),
                    'processed_at' => $status === RefundStatus::Pending ? null : $createdAt->copy()->addMinutes(2),
                    'created_at' => $createdAt,
                    'updated_at' => $createdAt,
                ])->save();
            }
        });
    }

    private function customer(string $name, string $email): User
    {
        return User::query()->firstOrCreate(
            ['email' => $email],
            [
                'name' => $name,
                'password' => env('SEED_PASSWORD', 'password'),
                'role' => Role::Customer,
                'email_verified_at' => now(),
            ],
        );
    }
}
