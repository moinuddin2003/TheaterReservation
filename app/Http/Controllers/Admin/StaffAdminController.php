<?php

namespace App\Http\Controllers\Admin;

use App\Enums\Role;
use App\Enums\ScanResult;
use App\Http\Controllers\Controller;
use App\Models\TicketScan;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class StaffAdminController extends Controller
{
    public function index(Request $request): View
    {
        $validated = $request->validate([
            'search' => ['nullable', 'string', 'max:120'],
            'role' => ['nullable', Rule::in([Role::Admin->value, Role::Staff->value])],
        ]);
        $search = trim($validated['search'] ?? '');
        $role = $validated['role'] ?? '';

        return view('admin.staff-admins.index', [
            'members' => User::query()
                ->whereIn('role', [Role::Admin, Role::Staff])
                ->withCount('ticketScans')
                ->withMax('ticketScans', 'scanned_at')
                ->when($search !== '', fn (Builder $query) => $query->where(
                    fn (Builder $query) => $query->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%"),
                ))
                ->when($role !== '', fn (Builder $query) => $query->where('role', $role))
                ->orderByRaw('CASE WHEN role = ? THEN 0 ELSE 1 END', [Role::Admin->value])
                ->orderBy('name')
                ->paginate(10)
                ->withQueryString(),
            'filters' => [
                'search' => $search,
                'role' => $role,
            ],
            'stats' => [
                'team_members' => User::query()->whereIn('role', [Role::Admin, Role::Staff])->count(),
                'admins' => User::query()->where('role', Role::Admin)->count(),
                'staff' => User::query()->where('role', Role::Staff)->count(),
                'scans_today' => TicketScan::query()
                    ->whereDate('scanned_at', today())
                    ->whereHas('scannedBy', fn (Builder $query) => $query->where('role', Role::Staff))
                    ->count(),
            ],
        ]);
    }

    public function show(Request $request, User $staff): View
    {
        abort_unless($staff->isStaff(), 404);

        $validated = $request->validate([
            'result' => ['nullable', Rule::enum(ScanResult::class)],
            'sort' => ['nullable', Rule::in(['newest', 'oldest'])],
        ]);
        $result = $validated['result'] ?? '';
        $sort = $validated['sort'] ?? 'newest';
        $staff->loadCount('ticketScans');

        return view('admin.staff-admins.show', [
            'staff' => $staff,
            'scans' => $staff->ticketScans()
                ->with(['ticket.booking.user', 'showtime.movie', 'showtime.screen'])
                ->when($result !== '', fn (Builder $query) => $query->where('result', $result))
                ->orderBy('scanned_at', $sort === 'oldest' ? 'asc' : 'desc')
                ->paginate(10)
                ->withQueryString(),
            'filters' => [
                'result' => $result,
                'sort' => $sort,
            ],
            'admittedScans' => $staff->ticketScans()->where('result', ScanResult::Admitted)->count(),
            'lastScan' => $staff->ticketScans()->max('scanned_at'),
        ]);
    }
}
