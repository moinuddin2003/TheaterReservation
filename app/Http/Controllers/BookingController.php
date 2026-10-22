<?php

namespace App\Http\Controllers;

use App\Actions\CreateCheckoutAction;
use App\Actions\HoldSeatsAction;
use App\Enums\BookingStatus;
use App\Models\Booking;
use App\Models\Showtime;
use App\Models\User;
use App\Services\BookingCancellationService;
use App\Services\QrCodeService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class BookingController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();
        $filter = $request->query('filter', 'all');
        $filter = in_array($filter, ['upcoming', 'past', 'all'], true) ? $filter : 'all';

        // 1. Reusable rules for upcoming bookings
        $upcomingRule = function ($query) {
            $query->whereIn('status', [
                BookingStatus::PendingPayment->value,
                BookingStatus::Confirmed->value,
            ])->whereHas('showtime', fn ($q) => $q->where('starts_at', '>=', now()));
        };

        // 2. Reusable rules for past bookings
        $pastRule = function ($query) {
            $query->whereHas('showtime', fn ($q) => $q->where('starts_at', '<', now()));
        };

        // 3. Main bookings query
        $bookingsQuery = $user->bookings()->with($this->bookingRelations());

        if ($filter === 'upcoming') {
            $upcomingRule($bookingsQuery);
        } elseif ($filter === 'past') {
            $pastRule($bookingsQuery);
        }

        $bookings = $bookingsQuery->latest()->paginate(8)->withQueryString();

        // 4. Counts for tabs using the same rules
        $upcomingCountQuery = $user->bookings();
        $upcomingRule($upcomingCountQuery);

        $pastCountQuery = $user->bookings();
        $pastRule($pastCountQuery);

        return view('bookings.index', [
            'bookings' => $bookings,
            'filter' => $filter,
            'upcomingCount' => $upcomingCountQuery->count(),
            'pastCount' => $pastCountQuery->count(),
            'allCount' => $user->bookings()->count(),
        ]);
    }

    public function show(Request $request, string $reference): View
    {
        return view('bookings.show', [
            'booking' => $this->findForUser($request, $reference),
        ]);
    }

    public function checkout(Request $request, string $reference): View|RedirectResponse
    {
        $booking = $this->findForUser($request, $reference);
        if (! $booking->isPendingPayment() || ! $this->hasCompleteActiveHold($booking)) {
            return redirect()
                ->route('bookings.show', $booking->reference)
                ->with('status', 'This seat hold is no longer active. No payment was started.');
        }

        return view('bookings.checkout', ['booking' => $booking]);
    }

    public function holdSeats(
        Request $request,
        Showtime $showtime,
        HoldSeatsAction $holdSeatsAction,
    ): RedirectResponse {
        $user = $request->user();
        abort_unless($user instanceof User, 401);

        $maxSeats = (int) config('cinebook.max_seats_per_booking', 6);
        $validated = $request->validate([
            'seat_ids' => ['required', 'array', 'min:1', 'max:'.$maxSeats],
            'seat_ids.*' => ['required', 'integer', 'distinct:strict'],
        ]);

        $booking = $holdSeatsAction->execute(
            $showtime,
            $user,
            array_map('intval', $validated['seat_ids']),
        );

        return redirect()->route('bookings.checkout', $booking->reference);
    }

    public function startCheckout(
        Request $request,
        string $reference,
        CreateCheckoutAction $createCheckoutAction,
    ): RedirectResponse {
        $booking = $this->findForUser($request, $reference);
        if (! $booking->isPendingPayment() || ! $this->hasCompleteActiveHold($booking)) {
            return redirect()
                ->route('bookings.show', $booking->reference)
                ->with('status', 'This seat hold is no longer active. No payment was started.');
        }

        try {
            $session = $createCheckoutAction->execute($booking);
        } catch (ValidationException $exception) {
            return redirect()
                ->route('bookings.checkout', $booking->reference)
                ->withErrors($exception->errors());
        }

        if (! is_string($session->url)) {
            return redirect()->route('bookings.processing', $booking->reference);
        }

        return redirect()->away($session->url);
    }

    public function processing(Request $request, string $reference): RedirectResponse|View
    {
        $booking = $this->findForUser($request, $reference);

        if ($booking->isConfirmed()) {
            return redirect()->route('bookings.confirmed', $booking->reference);
        }

        return view('bookings.processing', ['booking' => $booking]);
    }

    public function confirmed(Request $request, string $reference): RedirectResponse|View
    {
        $booking = $this->findForUser($request, $reference);

        if (! $booking->isConfirmed()) {
            return redirect()->route('bookings.show', $booking->reference);
        }

        return view('bookings.confirmed', ['booking' => $booking]);
    }

    public function cancelConfirmation(Request $request, string $reference): View
    {
        return view('bookings.cancel', [
            'booking' => $this->findForUser($request, $reference),
        ]);
    }

    public function cancel(
        Request $request,
        string $reference,
        BookingCancellationService $cancellationService,
    ): RedirectResponse {
        $booking = $this->findForUser($request, $reference);
        $refund = $cancellationService->cancel($booking, $request->user());

        $message = $refund->isSucceeded()
            ? 'Your booking is cancelled and the refund has been sent to your original payment method.'
            : 'Your booking is cancelled. The refund is still being processed.';

        return redirect()
            ->route('bookings.show', $booking->reference)
            ->with('status', $message);
    }

    public function ticket(
        Request $request,
        string $reference,
        int $ticketId,
        QrCodeService $qrCodeService,
    ): View {
        $booking = $this->findForUser($request, $reference);

        abort_unless($booking->isConfirmed(), 404);

        $ticket = $booking->tickets()
            ->with(['bookingItem', 'showtime.movie', 'showtime.screen'])
            ->findOrFail($ticketId);

        abort_unless(! $ticket->isVoid(), 404);

        return view('bookings.ticket', [
            'booking' => $booking,
            'ticket' => $ticket,
            'qrCode' => $qrCodeService->createDataUri($ticket->token),
        ]);
    }

    private function findForUser(Request $request, string $reference): Booking
    {
        return $request->user()->bookings()
            ->with($this->bookingRelations())
            ->where('reference', $reference)
            ->firstOrFail();
    }

    /**
     * @return array<int, string>
     */
    private function bookingRelations(): array
    {
        return [
            'showtime.movie',
            'showtime.screen',
            'items.ticket',
            'payments.refunds',
            'refunds',
            'seatHolds.seat',
            'tickets',
        ];
    }

    private function hasCompleteActiveHold(Booking $booking): bool
    {
        return $booking->seatHolds
            ->filter(fn ($hold): bool => $hold->isActive() && $hold->expires_at->isFuture())
            ->count() === $booking->seats_count;
    }
}
