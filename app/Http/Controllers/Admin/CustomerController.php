<?php

namespace App\Http\Controllers\Admin;

use App\Enums\BookingStatus;
use App\Enums\PaymentStatus;
use App\Enums\RefundStatus;
use App\Enums\Role;
use App\Http\Controllers\Controller;
use App\Models\BookingItem;
use App\Models\Genre;
use App\Models\Refund;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class CustomerController extends Controller
{
    public function index(Request $request): View
    {
        $validated = $request->validate([
            'search' => ['nullable', 'string', 'max:120'],
            'status' => ['nullable', Rule::in(['verified', 'unverified'])],
        ]);
        $search = trim($validated['search'] ?? '');
        $status = $validated['status'] ?? '';

        $customers = User::query()
            ->where('role', Role::Customer)
            ->withCount('bookings')
            ->withSum([
                'payments as total_spent_cents' => fn (Builder $query) => $query
                    ->where('payments.status', PaymentStatus::Succeeded),
            ], 'amount_cents')
            ->when($search !== '', function (Builder $query) use ($search): void {
                $query->where(function (Builder $query) use ($search): void {
                    $query->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                });
            })
            ->when($status === 'verified', fn (Builder $query) => $query->whereNotNull('email_verified_at'))
            ->when($status === 'unverified', fn (Builder $query) => $query->whereNull('email_verified_at'))
            ->orderBy('name')
            ->paginate(10)
            ->withQueryString();

        $customers->getCollection()->each(
            fn (User $customer) => $customer->setAttribute('total_spent_cents', (int) $customer->total_spent_cents),
        );

        return view('admin.customers.index', [
            'customers' => $customers,
            'filters' => [
                'search' => $search,
                'status' => $status,
            ],
        ]);
    }

    public function show(Request $request, User $customer): View
    {
        abort_unless($customer->isCustomer(), 404);

        $validated = $request->validate([
            'status' => ['nullable', Rule::enum(BookingStatus::class)],
            'sort' => ['nullable', Rule::in(['newest', 'oldest'])],
        ]);
        $status = $validated['status'] ?? '';
        $sort = $validated['sort'] ?? 'newest';

        $customer->loadCount('bookings');
        $successfulPayments = $customer->payments()
            ->where('payments.status', PaymentStatus::Succeeded);
        $refundedCents = Refund::query()
            ->where('status', RefundStatus::Succeeded)
            ->whereHas('booking', fn (Builder $query) => $query->where('user_id', $customer->getKey()))
            ->sum('amount_cents');
        $customer->setAttribute(
            'total_spent_cents',
            max(0, (int) $successfulPayments->sum('amount_cents') - (int) $refundedCents),
        );

        $averageTicketPriceCents = BookingItem::query()
            ->whereHas('booking', fn (Builder $query) => $query
                ->where('user_id', $customer->getKey())
                ->whereHas('payments', fn (Builder $payments) => $payments
                    ->where('payments.status', PaymentStatus::Succeeded)))
            ->avg('price_cents');
        $favoriteGenre = Genre::query()
            ->join('genre_movie', 'genres.id', '=', 'genre_movie.genre_id')
            ->join('movies', 'genre_movie.movie_id', '=', 'movies.id')
            ->join('showtimes', 'movies.id', '=', 'showtimes.movie_id')
            ->join('bookings', 'showtimes.id', '=', 'bookings.showtime_id')
            ->join('payments', 'bookings.id', '=', 'payments.booking_id')
            ->where('bookings.user_id', $customer->getKey())
            ->where('payments.status', PaymentStatus::Succeeded)
            ->select('genres.name')
            ->selectRaw('COUNT(DISTINCT bookings.id) as bookings_count')
            ->groupBy('genres.id', 'genres.name')
            ->orderByDesc('bookings_count')
            ->orderBy('genres.name')
            ->value('genres.name');

        return view('admin.customers.show', [
            'customer' => $customer,
            'bookings' => $customer->bookings()
                ->with(['showtime.movie', 'showtime.screen', 'items', 'payments'])
                ->withCount('items')
                ->when($status !== '', fn (Builder $query) => $query->where('status', $status))
                ->orderBy('created_at', $sort === 'oldest' ? 'asc' : 'desc')
                ->paginate(10)
                ->withQueryString(),
            'filters' => [
                'status' => $status,
                'sort' => $sort,
            ],
            'averageTicketPriceCents' => (int) round($averageTicketPriceCents ?? 0),
            'favoriteGenre' => $favoriteGenre ?? '—',
        ]);
    }
}
