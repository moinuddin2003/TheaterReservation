<?php

namespace App\Http\Controllers\Admin;

use App\Enums\Role;
use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Setting;
use App\Models\User;
use App\Services\Admin\UserActivity;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class UserController extends Controller
{
    public function index(Request $request, UserActivity $activity): View
    {
        $filters = $request->validate([
            'tab' => ['nullable', Rule::in(['customers', 'staff'])],
            'q' => ['nullable', 'string', 'max:100'],
            'state' => ['nullable', Rule::in(['active', 'inactive'])],
            'sort' => ['nullable', Rule::in(['name', 'email', 'created_at'])],
            'direction' => ['nullable', Rule::in(['asc', 'desc'])],
        ]);
        $tab = $filters['tab'] ?? 'customers';
        $role = $tab === 'staff' ? Role::Staff : Role::Customer;
        $inactiveIds = $activity->inactiveUserIds();
        $users = User::query()->where('role', $role->value)->withCount('bookings')
            ->when($filters['q'] ?? null, fn ($query, $search) => $query->where(fn ($nested) => $nested
                ->where('name', 'like', '%'.$search.'%')->orWhere('email', 'like', '%'.$search.'%')))
            ->when(($filters['state'] ?? null) === 'active', fn ($query) => $query->whereNotIn('id', $inactiveIds))
            ->when(($filters['state'] ?? null) === 'inactive', fn ($query) => $query->whereIn('id', $inactiveIds))
            ->orderBy($filters['sort'] ?? 'created_at', $filters['direction'] ?? 'desc')
            ->paginate(15)->withQueryString();

        return view('admin.users.index', compact('users', 'tab', 'inactiveIds') + ['timezone' => $this->timezone()]);
    }

    public function show(User $user, UserActivity $activity): View
    {
        abort_if($user->role === Role::Admin, 404);
        $user->loadCount('bookings');

        return view('admin.users.show', [
            'user' => $user,
            'active' => $activity->isActive($user),
            'bookings' => Booking::query()->with(['showtime.movie', 'payments'])
                ->where('user_id', $user->id)->latest('created_at')->paginate(10)->withQueryString(),
            'timezone' => $this->timezone(),
        ]);
    }

    private function timezone(): string
    {
        $timezone = (string) Setting::query()->whereKey('timezone')->value('value');

        return in_array($timezone, timezone_identifiers_list(), true) ? $timezone : config('app.timezone', 'UTC');
    }
}
