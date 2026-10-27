<?php

namespace App\Http\Controllers\Admin;

use App\Actions\CancelBookingAction;
use App\Actions\IssueRefundAction;
use App\Actions\VoidTicketAction;
use App\Enums\BookingStatus;
use App\Enums\PaymentStatus;
use App\Enums\RefundStatus;
use App\Enums\TicketStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\BookingIndexRequest;
use App\Http\Requests\Admin\CancelBookingRequest;
use App\Http\Requests\Admin\RefundBookingRequest;
use App\Http\Requests\Admin\VoidTicketRequest;
use App\Mail\BookingCancelledMail;
use App\Mail\BookingConfirmedMail;
use App\Models\Booking;
use App\Models\Movie;
use App\Models\Payment;
use App\Models\Refund;
use App\Models\Ticket;
use App\Services\AuditLogger;
use App\Services\SettingsService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;

class BookingController extends Controller
{
    public function index(BookingIndexRequest $request): View
    {
        $filters = $request->filters();
        $bookingsQuery = $this->filteredBookings($request, $filters);
        $todayStart = Carbon::now($request->timezone())->startOfDay()->utc();
        $tomorrowStart = $todayStart->copy()->addDay();

        return view('admin.bookings.index', [
            'bookings' => $bookingsQuery
                ->with(['user', 'showtime.movie', 'showtime.screen.cinema', 'items', 'payments'])
                ->withCount('items')
                ->latest('created_at')
                ->paginate(10)
                ->withQueryString(),
            'filters' => $filters,
            'movies' => Movie::query()->orderBy('title')->get(['id', 'title']),
            'stats' => [
                'total_bookings' => Booking::query()->count(),
                'total_revenue' => Payment::query()->where('status', PaymentStatus::Succeeded)->sum('amount_cents'),
                'pending_refunds' => Refund::query()->where('status', RefundStatus::Pending)->count(),
                'failed_payments' => Payment::query()->where('status', PaymentStatus::Failed)
                    ->where('created_at', '>=', $todayStart)->where('created_at', '<', $tomorrowStart)->count(),
            ],
            'timezone' => $request->timezone(),
        ]);
    }

    public function show(Booking $booking, SettingsService $settings): View
    {
        $booking->load([
            'user',
            'showtime.movie',
            'showtime.screen.cinema',
            'items.seat',
            'items.ticket.scans.scannedBy',
            'payments.refunds',
            'refunds.initiatedBy',
        ]);

        $refundablePayment = $booking->payments
            ->filter(fn (Payment $candidate): bool => $candidate->status === PaymentStatus::Succeeded
                && $candidate->gateway === 'stripe'
                && filled($candidate->gateway_payment_intent_id)
                && ! $candidate->refunds->contains(fn (Refund $refund): bool => $refund->status === RefundStatus::Pending)
                && $candidate->refunds->where('status', RefundStatus::Succeeded)->sum('amount_cents') < $candidate->amount_cents)
            ->sortByDesc('created_at')
            ->first();
        $refundableCents = $refundablePayment
            ? max(0, $refundablePayment->amount_cents - (int) $refundablePayment->refunds
                ->where('status', RefundStatus::Succeeded)
                ->sum('amount_cents'))
            : 0;
        $cancelWindowHours = (int) $settings->get('cancel_window_hours');
        $cancellationWindowClosed = now()->greaterThan(
            $booking->showtime->starts_at->copy()->subHours(max(0, $cancelWindowHours)),
        );
        $hasUsedTicket = $booking->items->contains(
            fn ($item): bool => $item->ticket?->status === TicketStatus::Used,
        );

        $showtimeInFuture = $booking->showtime->starts_at->isFuture();

        return view('admin.bookings.show', [
            'booking' => $booking,
            'payment' => $booking->payments->sortByDesc('created_at')->first(),
            'refundablePayment' => $refundablePayment,
            'refundableCents' => $refundableCents,
            'stripeConfigured' => filled(config('services.stripe.secret')),
            'canCancel' => $booking->status === BookingStatus::Confirmed
                && $showtimeInFuture
                && ! $hasUsedTicket,
            'canRefund' => $showtimeInFuture && $refundableCents > 0,
            'canResendEmail' => $booking->status === BookingStatus::Confirmed && $showtimeInFuture,
            'showtimeInFuture' => $showtimeInFuture,
            'cancelWindowHours' => max(0, $cancelWindowHours),
            'cancellationWindowClosed' => $cancellationWindowClosed,
            'timeline' => $this->timeline($booking),
            'timezone' => $this->timezone(),
        ]);
    }

    public function cancel(
        CancelBookingRequest $request,
        Booking $booking,
        CancelBookingAction $action,
        IssueRefundAction $refundAction,
    ): RedirectResponse|JsonResponse {
        $refund = null;

        DB::transaction(function () use ($action, $request, $booking, $refundAction, &$refund): void {
            $action->execute(
                $booking,
                $request->user(),
                $request->validated('cancel_reason'),
                $request->validated('override_reason'),
                $request->ip(),
                $request->validated('note'),
            );

            if ($request->boolean('also_refund')) {
                $refund = $refundAction->prepare(
                    $booking,
                    $request->user(),
                    $this->amountCents($request->validated('amount')),
                    'requested_by_customer',
                    $request->ip(),
                );
            }
        });

        $cancelledBooking = $booking->fresh(['user', 'showtime.movie', 'showtime.screen.cinema']);
        $emailSent = $this->sendBookingEmail($cancelledBooking, new BookingCancelledMail($cancelledBooking));
        $refundMessage = '';
        if ($refund) {
            try {
                $refund = $refundAction->processPrepared($refund, $request->user(), $request->ip());
                $refundMessage = match ($refund->status) {
                    RefundStatus::Succeeded => ' Refund processed successfully.',
                    RefundStatus::Failed => ' The booking was cancelled, but Stripe could not process the refund.',
                    RefundStatus::Pending => ' The refund is pending confirmation from Stripe.',
                };
            } catch (ValidationException $exception) {
                if ($refund->fresh()->status !== RefundStatus::Pending) {
                    throw $exception;
                }

                report($exception);
                $refundMessage = ' The booking was cancelled; the refund is pending confirmation from Stripe.';
            }
        }

        $message = 'Booking cancelled. Seats released, tickets voided and audit log saved.'
            .($emailSent ? ' Customer email sent.' : ' Customer email could not be sent; check the mail configuration.')
            .$refundMessage;

        if ($request->expectsJson()) {
            return response()->json(['message' => $message]);
        }

        return to_route('admin.bookings.show', $booking)->with('status', $message);
    }

    public function refund(
        RefundBookingRequest $request,
        Booking $booking,
        IssueRefundAction $action,
    ): RedirectResponse|JsonResponse {
        $refund = $action->execute(
            $booking,
            $request->user(),
            $request->amountCents(),
            $request->validated('reason'),
            $request->ip(),
        );
        $message = match ($refund->status) {
            RefundStatus::Pending => 'Refund submitted to Stripe and is pending.',
            RefundStatus::Succeeded => 'Refund processed successfully.',
            RefundStatus::Failed => 'Stripe could not process the refund.',
        };

        if ($request->expectsJson()) {
            return response()->json([
                'message' => $message,
                'error' => $refund->status === RefundStatus::Failed,
            ]);
        }

        return to_route('admin.bookings.show', $booking)
            ->with($refund->status === RefundStatus::Failed ? 'error' : 'status', $message);
    }

    public function voidTicket(
        VoidTicketRequest $request,
        Booking $booking,
        Ticket $ticket,
        VoidTicketAction $action,
    ): JsonResponse|RedirectResponse {
        $action->execute($booking, $ticket, $request->user(), $request->validated('reason'), $request->ip());
        $message = 'Ticket voided. No refund was issued.';

        if ($request->expectsJson()) {
            return response()->json(['message' => $message]);
        }

        return to_route('admin.bookings.show', $booking)->with('status', $message);
    }

    public function resendEmail(Request $request, Booking $booking, AuditLogger $auditLogger): JsonResponse|RedirectResponse
    {
        $booking->loadMissing('showtime');
        if ($booking->status !== BookingStatus::Confirmed || ! $booking->showtime->starts_at->isFuture()) {
            throw ValidationException::withMessages([
                'email' => 'Only confirmed bookings for upcoming showtimes can receive a resent confirmation email.',
            ]);
        }

        if ($this->mailTransportDoesNotDeliver()) {
            throw ValidationException::withMessages([
                'email' => 'Email delivery is not configured. The active mail transport does not deliver messages; configure SMTP or another delivery transport and try again.',
            ]);
        }

        $booking->loadMissing('user', 'showtime.movie', 'showtime.screen.cinema', 'items');
        try {
            Mail::to($booking->user->email)->send(new BookingConfirmedMail($booking));
        } catch (Throwable $exception) {
            report($exception);
            throw ValidationException::withMessages([
                'email' => 'The confirmation email could not be sent. Check the mail configuration and try again.',
            ]);
        }

        $auditLogger->log(
            $request->user(),
            'booking.confirmation_resent',
            $booking,
            [],
            ['recipient' => $booking->user->email],
            $request->ip(),
        );

        $message = 'Confirmation email sent to '.$booking->user->email.'.';

        if ($request->expectsJson()) {
            return response()->json(['message' => $message]);
        }

        return to_route('admin.bookings.show', $booking)->with('status', $message);
    }

    private function sendBookingEmail(Booking $booking, BookingCancelledMail $mail): bool
    {
        if ($this->mailTransportDoesNotDeliver()) {
            return false;
        }

        try {
            Mail::to($booking->user->email)->send($mail);

            return true;
        } catch (Throwable $exception) {
            report($exception);

            return false;
        }
    }

    private function mailTransportDoesNotDeliver(): bool
    {
        $mailer = config('mail.default');
        $transport = config("mail.mailers.{$mailer}.transport");

        return in_array($transport, ['log', 'array'], true);
    }

    private function amountCents(string $amount): int
    {
        [$whole, $fraction] = array_pad(explode('.', $amount, 2), 2, '');

        return ((int) $whole * 100) + (int) str_pad($fraction, 2, '0');
    }

    public function export(BookingIndexRequest $request): StreamedResponse
    {
        $filters = $request->filters();
        $bookings = $this->filteredBookings($request, $filters)
            ->with(['user', 'showtime.movie', 'showtime.screen.cinema', 'items', 'payments'])
            ->orderBy('id')
            ->lazyById(500);

        return response()->streamDownload(function () use ($bookings, $request): void {
            $output = fopen('php://output', 'w');
            fputcsv($output, ['Booking code', 'Customer', 'Email', 'Movie', 'Showtime', 'Cinema', 'Screen', 'Seats', 'Total', 'Booking status', 'Payment status', 'Booked at']);

            foreach ($bookings as $booking) {
                $payment = $booking->payments->sortByDesc('created_at')->first();
                fputcsv($output, [
                    $booking->reference,
                    $booking->user->name,
                    $booking->user->email,
                    $booking->showtime->movie->title,
                    $booking->showtime->starts_at->setTimezone($request->timezone())->format('Y-m-d H:i'),
                    $booking->showtime->screen->cinema->name,
                    $booking->showtime->screen->name,
                    $booking->items->pluck('seat_label')->implode(', '),
                    number_format($booking->total_cents / 100, 2, '.', ''),
                    $booking->status->value,
                    $payment?->status?->value ?? 'awaiting',
                    $booking->created_at->setTimezone($request->timezone())->format('Y-m-d H:i'),
                ]);
            }

            fclose($output);
        }, 'bookings.csv', [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    /**
     * @param  array{search: string, movie_id: int|null, from: string|null, to: string|null, status: string|null}  $filters
     * @return Builder<Booking>
     */
    private function filteredBookings(
        BookingIndexRequest $request,
        array $filters,
    ): Builder {
        return Booking::query()
            ->when($filters['search'] !== '', function (Builder $query) use ($filters): void {
                $search = $filters['search'];
                $query->where(function (Builder $query) use ($search): void {
                    $query->where('reference', 'like', '%'.$search.'%')
                        ->orWhereHas('user', fn (Builder $users) => $users
                            ->where('name', 'like', '%'.$search.'%')
                            ->orWhere('email', 'like', '%'.$search.'%'));
                });
            })
            ->when($filters['movie_id'], fn (Builder $query, int $movieId) => $query->whereHas(
                'showtime',
                fn (Builder $showtimes) => $showtimes->where('movie_id', $movieId),
            ))
            ->when($filters['from'], function (Builder $query, string $from) use ($request): void {
                $query->whereHas('showtime', fn (Builder $showtimes) => $showtimes->where(
                    'starts_at',
                    '>=',
                    Carbon::parse($from, $request->timezone())->startOfDay()->utc(),
                ));
            })
            ->when($filters['to'], function (Builder $query, string $to) use ($request): void {
                $query->whereHas('showtime', fn (Builder $showtimes) => $showtimes->where(
                    'starts_at',
                    '<',
                    Carbon::parse($to, $request->timezone())->addDay()->startOfDay()->utc(),
                ));
            })
            ->when($filters['status'] !== null, fn (Builder $query) => $query->where('status', $filters['status']));
    }

    /**
     * @return Collection<int, array{label: string, detail: string|null, at: Carbon, tone: string}>
     */
    private function timeline(Booking $booking): Collection
    {
        $events = collect([
            [
                'label' => 'Booking created',
                'detail' => 'Reservation was created.',
                'at' => $booking->created_at,
                'tone' => 'accent',
            ],
        ]);

        foreach ($booking->payments as $payment) {
            $events->push([
                'label' => match ($payment->status) {
                    PaymentStatus::Succeeded => 'Payment succeeded',
                    PaymentStatus::Failed => 'Payment failed',
                    PaymentStatus::Expired => 'Payment expired',
                    default => 'Payment started',
                },
                'detail' => $payment->failure_reason ?: strtoupper($payment->gateway).' payment · '.number_format($payment->amount_cents / 100, 2).' '.$payment->currency,
                'at' => $payment->paid_at ?? $payment->created_at,
                'tone' => $payment->isSucceeded() ? 'success' : 'accent',
            ]);
        }

        if ($booking->confirmed_at) {
            $events->push([
                'label' => 'Booking confirmed',
                'detail' => $booking->items->count().' seat'.($booking->items->count() === 1 ? '' : 's').' reserved.',
                'at' => $booking->confirmed_at,
                'tone' => 'success',
            ]);
        }

        foreach ($booking->items as $item) {
            if (! $item->ticket) {
                continue;
            }

            $events->push([
                'label' => 'Ticket issued',
                'detail' => $item->ticket->ticket_number.' · Seat '.$item->seat_label,
                'at' => $item->ticket->created_at,
                'tone' => 'success',
            ]);

            foreach ($item->ticket->scans as $scan) {
                $events->push([
                    'label' => 'Ticket scan · '.str($scan->result->value)->replace('_', ' ')->lower(),
                    'detail' => $scan->message ?: 'Scanned by '.($scan->scannedBy?->name ?? 'staff member'),
                    'at' => $scan->scanned_at,
                    'tone' => $scan->result->value === 'admitted' ? 'success' : 'warning',
                ]);
            }
        }

        if ($booking->cancelled_at) {
            $events->push([
                'label' => 'Booking cancelled',
                'detail' => $booking->cancel_reason,
                'at' => $booking->cancelled_at,
                'tone' => 'danger',
            ]);
        }

        foreach ($booking->refunds as $refund) {
            $events->push([
                'label' => 'Refund '.str($refund->status->value)->replace('_', ' ')->lower(),
                'detail' => $refund->reason,
                'at' => $refund->processed_at ?? $refund->created_at,
                'tone' => 'warning',
            ]);
        }

        return $events->sortBy('at')->values();
    }

    private function timezone(): string
    {
        $timezone = Setting::query()->whereKey('timezone')->value('value');

        return is_string($timezone) && $timezone !== ''
            ? $timezone
            : (string) config('app.timezone');
    }
}
