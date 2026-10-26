<?php

namespace App\Http\Controllers\Admin;

use App\Enums\RefundStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\RefundIndexRequest;
use App\Models\Refund;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class RefundController extends Controller
{
    public function index(RefundIndexRequest $request): View
    {
        $filters = $request->filters();
        $from = Carbon::parse($filters['from'])->startOfDay();
        $to = Carbon::parse($filters['to'])->addDay()->startOfDay();
        $filteredRefunds = $this->filteredRefunds($filters);

        return view('admin.refunds.index', [
            'refunds' => (clone $filteredRefunds)
                ->with(['booking.user'])
                ->orderByDesc('refunds.created_at')
                ->orderByDesc('refunds.id')
                ->paginate(10)
                ->withQueryString(),
            'filters' => $filters,
            'totals' => $this->totals($from, $to),
        ]);
    }

    public function export(RefundIndexRequest $request): StreamedResponse
    {
        $refunds = $this->filteredRefunds($request->filters())
            ->with(['booking.user'])
            ->orderByDesc('refunds.created_at')
            ->orderByDesc('refunds.id');

        return response()->streamDownload(function () use ($refunds): void {
            $output = fopen('php://output', 'w');

            if ($output === false) {
                throw new \RuntimeException('Unable to open the refund export stream.');
            }

            fputcsv($output, [
                'Refund ID',
                'Booking reference',
                'Customer',
                'Email',
                'Amount',
                'Currency',
                'Status',
                'Reason',
                'Requested at',
                'Processed at',
            ]);

            $refunds->chunk(200, function ($chunk) use ($output): void {
                foreach ($chunk as $refund) {
                    fputcsv($output, [
                        $refund->getKey(),
                        $refund->booking->reference,
                        $refund->booking->user->name,
                        $refund->booking->user->email,
                        number_format($refund->amount_cents / 100, 2, '.', ''),
                        strtoupper($refund->currency),
                        $refund->status->value,
                        $refund->reason,
                        $refund->created_at->toDateTimeString(),
                        $refund->processed_at?->toDateTimeString(),
                    ]);
                }
            });

            fclose($output);
        }, 'refunds.csv', [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    /**
     * @param  array{search: string, from: string, to: string}  $filters
     * @return Builder<Refund>
     */
    private function filteredRefunds(array $filters): Builder
    {
        return Refund::query()
            ->where('refunds.created_at', '>=', Carbon::parse($filters['from'])->startOfDay())
            ->where('refunds.created_at', '<', Carbon::parse($filters['to'])->addDay()->startOfDay())
            ->when($filters['search'] !== '', function (Builder $query) use ($filters): void {
                $search = $filters['search'];

                $query->where(function (Builder $query) use ($search): void {
                    $query->where('refunds.gateway_refund_id', 'like', '%'.$search.'%')
                        ->orWhereHas('booking', function (Builder $bookings) use ($search): void {
                            $bookings->where('reference', 'like', '%'.$search.'%')
                                ->orWhereHas('user', fn (Builder $users) => $users
                                    ->where('name', 'like', '%'.$search.'%')
                                    ->orWhere('email', 'like', '%'.$search.'%'));
                        });
                });
            });
    }

    /**
     * @return array{count: int, amount_cents: int, pending: int, failed: int, succeeded: int}
     */
    private function totals(Carbon $from, Carbon $to): array
    {
        $query = Refund::query()
            ->where('created_at', '>=', $from)
            ->where('created_at', '<', $to);

        return [
            'count' => (clone $query)->count(),
            'amount_cents' => (int) (clone $query)
                ->where('status', RefundStatus::Succeeded)
                ->sum('amount_cents'),
            'pending' => (clone $query)->where('status', RefundStatus::Pending)->count(),
            'failed' => (clone $query)->where('status', RefundStatus::Failed)->count(),
            'succeeded' => (clone $query)->where('status', RefundStatus::Succeeded)->count(),
        ];
    }
}
