<?php

namespace App\Http\Controllers;

use App\Enums\BookingStatus;
use App\Models\Booking;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
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
            ])->whereHas('showtime', fn($q) => $q->where('starts_at', '>=', now()));
        };

        // 2. Reusable rules for past bookings
        $pastRule = function ($query) {
            $query->whereHas('showtime', fn($q) => $q->where('starts_at', '<', now()));
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
        $hasActiveHolds = $booking->seatHolds->contains(
            fn($hold): bool => $hold->isActive() && $hold->expires_at->isFuture(),
        );

        if (!$booking->isPendingPayment() || !$hasActiveHolds) {
            return redirect()
                ->route('bookings.show', $booking->reference)
                ->with('status', 'This seat hold is no longer active. No payment was started.');
        }

        return view('bookings.checkout', ['booking' => $booking]);
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

        if (!$booking->isConfirmed()) {
            return redirect()->route('bookings.show', $booking->reference);
        }

        return view('bookings.confirmed', ['booking' => $booking]);
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
        ];
    }
}
