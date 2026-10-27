<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BookingItem;
use App\Models\Movie;
use App\Models\Refund;
use App\Services\AuditLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportController extends Controller
{
    public function index(Request $request): View
    {
        $from = Carbon::parse($request->query('from', now()->subDays(30)->toDateString()))->startOfDay();
        $to = Carbon::parse($request->query('to', now()->toDateString()))->endOfDay();
        $movieId = $request->integer('movie_id') ?: null;

        $items = BookingItem::query()
            ->with(['showtime.movie', 'booking'])
            ->whereHas('booking', fn ($bookings) => $bookings
                ->whereNotNull('confirmed_at')
                ->whereBetween('confirmed_at', [$from, $to])
                ->when($movieId, fn ($bookings) => $bookings->whereHas('showtime', fn ($showtimes) => $showtimes->where('movie_id', $movieId))))
            ->get();
        $refunds = Refund::query()
            ->with(['booking.showtime.movie'])
            ->where('status', 'succeeded')
            ->whereBetween('processed_at', [$from, $to])
            ->when($movieId, fn ($query) => $query->whereHas('booking.showtime', fn ($showtimes) => $showtimes->where('movie_id', $movieId)))
            ->get();

        $salesByDate = $items->groupBy(fn (BookingItem $item) => $item->booking->confirmed_at->toDateString());
        $refundsByDate = $refunds
            ->groupBy(fn (Refund $refund) => $refund->processed_at?->toDateString())
            ->map->sum('amount_cents');
        $dates = $salesByDate->keys()->merge($refundsByDate->keys())->unique()->sort()->values();
        $daily = $dates->map(fn (string $date): array => [
            'date' => $date,
            'tickets' => $salesByDate->get($date, collect())->count(),
            'gross_cents' => $salesByDate->get($date, collect())->sum('price_cents'),
            'refunds_cents' => $refundsByDate->get($date, 0),
        ]);

        $itemsByMovie = $items->groupBy(fn (BookingItem $item): int => $item->showtime->movie_id);
        $refundsByMovie = $refunds->groupBy(fn (Refund $refund): int => $refund->booking->showtime->movie_id);
        $movieIds = $itemsByMovie->keys()->merge($refundsByMovie->keys())->unique();
        $movies = $movieIds
            ->map(function (int $movieId) use ($itemsByMovie, $refundsByMovie): array {
                $movieItems = $itemsByMovie->get($movieId, collect());
                $movieRefunds = $refundsByMovie->get($movieId, collect());

                return [
                    'movie' => $movieItems->first()?->showtime->movie
                        ?? $movieRefunds->first()->booking->showtime->movie
                        ?? Movie::query()->findOrFail($movieId),
                    'tickets_sold' => $movieItems->count(),
                    'gross_cents' => $movieItems->sum('price_cents'),
                    'refunds_cents' => $movieRefunds->sum('amount_cents'),
                ];
            })
            ->sortByDesc('tickets_sold')
            ->values();

        return view('admin.reports.index', [
            'daily' => $daily,
            'movies' => $movies,
            'moviesList' => Movie::query()->orderBy('title')->get(['id', 'title']),
            'movieId' => $movieId,
            'from' => $from->toDateString(),
            'to' => $to->toDateString(),
            'tickets' => $items->count(),
            'grossCents' => $items->sum('price_cents'),
            'refundsCents' => $refunds->sum('amount_cents'),
        ]);
    }

    public function export(Request $request, AuditLogger $auditLogger): StreamedResponse
    {
        $response = $this->index($request);
        $data = $response->getData();
        $auditLogger->logResource(
            $request->user(),
            'report.csv_export_requested',
            'ReportExport',
            0,
            [],
            [
                'from' => $data['from'],
                'to' => $data['to'],
                'movie_id' => $data['movieId'],
                'tickets' => $data['tickets'],
                'gross_cents' => $data['grossCents'],
                'refunds_cents' => $data['refundsCents'],
            ],
            $request->ip(),
        );

        return response()->streamDownload(function () use ($data): void {
            $handle = fopen('php://output', 'w');

            if ($handle === false) {
                throw new \RuntimeException('Unable to open the sales report export stream.');
            }

            fputcsv($handle, ['Date', 'Tickets', 'Gross USD', 'Refunds USD', 'Net USD']);
            foreach ($data['daily'] as $day) {
                fputcsv($handle, [$day['date'], $day['tickets'], number_format($day['gross_cents'] / 100, 2), number_format($day['refunds_cents'] / 100, 2), number_format(($day['gross_cents'] - $day['refunds_cents']) / 100, 2)]);
            }
            fclose($handle);
        }, 'sales-report.csv');
    }
}
