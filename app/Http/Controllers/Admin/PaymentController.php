<?php

namespace App\Http\Controllers\Admin;

use App\Enums\PaymentStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\PaymentIndexRequest;
use App\Models\Payment;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PaymentController extends Controller
{
    public function index(PaymentIndexRequest $request): View
    {
        $filters = $request->filters();
        $paymentsForCounts = $this->filteredPayments($filters, includeStatus: false);
        $filteredPayments = $this->filteredPayments($filters);

        return view('admin.payments.index', [
            'payments' => (clone $filteredPayments)
                ->with(['booking.user', 'booking.showtime.movie', 'booking.showtime.screen'])
                ->orderByDesc('payments.created_at')
                ->orderByDesc('payments.id')
                ->paginate(10)
                ->withQueryString(),
            'filters' => $filters,
            'counts' => [
                'all' => (clone $paymentsForCounts)->count(),
                'succeeded' => (clone $paymentsForCounts)->where('payments.status', PaymentStatus::Succeeded)->count(),
                'pending' => (clone $paymentsForCounts)->where('payments.status', PaymentStatus::Pending)->count(),
                'failed' => (clone $paymentsForCounts)->where('payments.status', PaymentStatus::Failed)->count(),
            ],
        ]);
    }

    public function export(PaymentIndexRequest $request): StreamedResponse
    {
        $payments = $this->filteredPayments($request->filters())
            ->with(['booking.user'])
            ->orderByDesc('payments.created_at')
            ->orderByDesc('payments.id');

        return response()->streamDownload(function () use ($payments): void {
            $output = fopen('php://output', 'w');

            if ($output === false) {
                throw new \RuntimeException('Unable to open the payment export stream.');
            }

            fputcsv($output, [
                'Payment ID',
                'Booking reference',
                'Customer',
                'Email',
                'Amount',
                'Currency',
                'Status',
                'Gateway',
                'Created at',
                'Updated at',
            ]);

            $payments->chunk(200, function ($chunk) use ($output): void {
                foreach ($chunk as $payment) {
                    fputcsv($output, [
                        $payment->getKey(),
                        $payment->booking->reference,
                        $payment->booking->user->name,
                        $payment->booking->user->email,
                        number_format($payment->amount_cents / 100, 2, '.', ''),
                        strtoupper($payment->currency),
                        $payment->status->value,
                        $payment->gateway,
                        $payment->created_at->toDateTimeString(),
                        $payment->updated_at->toDateTimeString(),
                    ]);
                }
            });

            fclose($output);
        }, 'payments.csv', [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    /**
     * @param  array{search: string, from: string|null, to: string|null, status: string|null}  $filters
     * @return Builder<Payment>
     */
    private function filteredPayments(array $filters, bool $includeStatus = true): Builder
    {
        return Payment::query()
            ->select('payments.*')
            ->when($filters['search'] !== '', function (Builder $query) use ($filters): void {
                $search = $filters['search'];

                $query->where(function (Builder $query) use ($search): void {
                    $query->where('payments.id', 'like', '%'.$search.'%')
                        ->orWhere('payments.gateway_payment_intent_id', 'like', '%'.$search.'%')
                        ->orWhere('payments.gateway_session_id', 'like', '%'.$search.'%')
                        ->orWhereHas('booking', function (Builder $bookings) use ($search): void {
                            $bookings->where('reference', 'like', '%'.$search.'%')
                                ->orWhereHas('user', fn (Builder $users) => $users
                                    ->where('name', 'like', '%'.$search.'%')
                                    ->orWhere('email', 'like', '%'.$search.'%'));
                        });
                });
            })
            ->when($filters['from'], fn (Builder $query, string $from) => $query->where(
                'payments.created_at',
                '>=',
                Carbon::parse($from)->startOfDay(),
            ))
            ->when($filters['to'], fn (Builder $query, string $to) => $query->where(
                'payments.created_at',
                '<',
                Carbon::parse($to)->addDay()->startOfDay(),
            ))
            ->when(
                $includeStatus ? $filters['status'] : null,
                fn (Builder $query, string $status) => $query->where('payments.status', $status),
            );
    }
}
