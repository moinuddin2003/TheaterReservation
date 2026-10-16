<?php

namespace App\Http\Controllers\Admin;

use App\Enums\PaymentStatus;
use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class PaymentController extends Controller
{
    public function index(Request $request): View
    {
        $filters = $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
            'status' => ['nullable', Rule::in(array_column(PaymentStatus::cases(), 'value'))],
            'from' => ['nullable', 'date_format:Y-m-d'],
            'to' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:from'],
            'sort' => ['nullable', Rule::in(['created_at', 'status', 'amount_cents'])],
            'direction' => ['nullable', Rule::in(['asc', 'desc'])],
        ]);
        $query = Payment::query()->with('booking.user')
            ->when($filters['q'] ?? null, fn ($payments, $search) => $payments->where(fn ($nested) => $nested
                ->where('gateway_payment_intent_id', 'like', '%'.$search.'%')
                ->orWhereHas('booking', fn ($bookings) => $bookings->where('reference', 'like', '%'.$search.'%'))))
            ->when($filters['status'] ?? null, fn ($payments, $status) => $payments->where('status', $status))
            ->when($filters['from'] ?? null, fn ($payments, $from) => $payments->whereDate('created_at', '>=', $from))
            ->when($filters['to'] ?? null, fn ($payments, $to) => $payments->whereDate('created_at', '<=', $to));
        $totals = (clone $query)->selectRaw('COUNT(*) as payment_count, COALESCE(SUM(amount_cents), 0) as amount_total')->first();
        $payments = $query->orderBy($filters['sort'] ?? 'created_at', $filters['direction'] ?? 'desc')
            ->paginate(15)->withQueryString();

        return view('admin.payments.index', [
            'payments' => $payments,
            'totals' => $totals,
            'timezone' => $this->timezone(),
        ]);
    }

    public function show(Payment $payment): View
    {
        $payment->load(['booking.user', 'booking.showtime.movie', 'refunds.initiator']);

        return view('admin.payments.show', ['payment' => $payment, 'timezone' => $this->timezone()]);
    }

    private function timezone(): string
    {
        $timezone = (string) Setting::query()->whereKey('timezone')->value('value');

        return in_array($timezone, timezone_identifiers_list(), true) ? $timezone : config('app.timezone', 'UTC');
    }
}
