<?php

namespace App\Http\Controllers\Admin;

use App\Enums\RefundStatus;
use App\Http\Controllers\Controller;
use App\Models\Refund;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class RefundController extends Controller
{
    public function index(Request $request): View
    {
        $filters = $request->validate([
            'status' => ['nullable', Rule::in([RefundStatus::Pending->value, RefundStatus::Succeeded->value, RefundStatus::Failed->value])],
            'q' => ['nullable', 'string', 'max:100'],
            'from' => ['nullable', 'date_format:Y-m-d'],
            'to' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:from'],
            'sort' => ['nullable', Rule::in(['created_at', 'status', 'amount_cents'])],
            'direction' => ['nullable', Rule::in(['asc', 'desc'])],
        ]);
        $counts = Refund::query()->selectRaw('status, COUNT(*) as aggregate')->groupBy('status')->pluck('aggregate', 'status');
        $status = $filters['status'] ?? RefundStatus::Pending->value;
        $refunds = Refund::query()->with(['booking.user', 'payment'])
            ->where('status', $status)
            ->when($filters['q'] ?? null, fn ($query, $search) => $query->where(fn ($nested) => $nested
                ->where('reason', 'like', '%'.$search.'%')
                ->orWhereHas('booking', fn ($bookings) => $bookings->where('reference', 'like', '%'.$search.'%'))
                ->orWhereHas('booking.user', fn ($users) => $users->where('name', 'like', '%'.$search.'%')->orWhere('email', 'like', '%'.$search.'%'))))
            ->when($filters['from'] ?? null, fn ($query, $from) => $query->whereDate('created_at', '>=', $from))
            ->when($filters['to'] ?? null, fn ($query, $to) => $query->whereDate('created_at', '<=', $to))
            ->orderBy($filters['sort'] ?? 'created_at', $filters['direction'] ?? 'desc')
            ->paginate(15)->withQueryString();

        return view('admin.refunds.index', ['refunds' => $refunds, 'counts' => $counts, 'status' => $status, 'timezone' => $this->timezone()]);
    }

    public function show(Refund $refund): View
    {
        $refund->load(['booking.user', 'booking.showtime.movie', 'payment', 'initiator']);

        return view('admin.refunds.show', ['refund' => $refund, 'timezone' => $this->timezone()]);
    }

    private function timezone(): string
    {
        $timezone = (string) Setting::query()->whereKey('timezone')->value('value');

        return in_array($timezone, timezone_identifiers_list(), true) ? $timezone : config('app.timezone', 'UTC');
    }
}
