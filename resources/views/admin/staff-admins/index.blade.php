<x-admin.layout title="Staff & admins" breadcrumb="CineBook / People / Staff & admins">
    <x-admin.page-header
        title="Staff & admins"
        subtitle="The team that runs the cinema: admins manage the panel, staff scan tickets at the door."
    >
        <x-slot:actions>
            <x-admin.filter-dropdown :action="route('admin.staff-admins.index')" label="Filters">
                <input type="hidden" name="search" value="{{ $filters['search'] }}">
                <label>
                    <span class="mb-1.5 block text-[10px] font-semibold uppercase tracking-wider text-cine-tertiary">Role</span>
                    <select name="role" class="min-h-10 w-full rounded-lg border border-cine-border/15 bg-cine-bg px-3 text-xs text-cine-secondary focus:border-cine-accent/50 focus:outline-none focus:ring-2 focus:ring-cine-accent/20">
                        <option value="">All roles</option>
                        <option value="admin" @selected($filters['role'] === 'admin')>Admin</option>
                        <option value="staff" @selected($filters['role'] === 'staff')>Staff</option>
                    </select>
                </label>
                <div class="mt-4 flex justify-end gap-2">
                    <a href="{{ route('admin.staff-admins.index') }}" class="inline-flex min-h-9 items-center rounded-lg border border-cine-border/10 px-3 text-xs font-semibold text-cine-secondary transition hover:bg-cine-elevated hover:text-cine-primary">Reset</a>
                    <x-admin.button type="submit" class="!min-h-9 !rounded-lg !px-3 !py-1 !text-xs">Apply filters</x-admin.button>
                </div>
            </x-admin.filter-dropdown>
        </x-slot:actions>
    </x-admin.page-header>

    @php
        $summaryCards = [
            ['label' => 'Team members', 'value' => $stats['team_members'], 'icon' => 'users', 'tone' => 'text-cine-primary'],
            ['label' => 'Admins', 'value' => $stats['admins'], 'icon' => 'settings', 'tone' => 'text-violet-400'],
            ['label' => 'Staff', 'value' => $stats['staff'], 'icon' => 'scan-line', 'tone' => 'text-cine-success'],
            ['label' => 'Scans today', 'value' => $stats['scans_today'], 'icon' => 'ticket', 'tone' => 'text-cine-accent'],
        ];
    @endphp

    <div class="mb-5 grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
        @foreach ($summaryCards as $card)
            <div class="min-h-[96px] rounded-2xl border border-cine-border/10 bg-gradient-to-br from-cine-surface to-cine-bg p-4">
                <div class="flex items-center justify-between gap-3">
                    <div>
                        <p class="text-xs font-medium text-cine-secondary">{{ $card['label'] }}</p>
                        <p class="mt-1 text-2xl font-bold tracking-tight text-cine-primary">{{ number_format($card['value']) }}</p>
                    </div>
                    <span class="flex h-10 w-10 items-center justify-center rounded-full border border-cine-border/10 bg-cine-elevated/70 {{ $card['tone'] }}">
                        <x-admin.icon :name="$card['icon']" class="h-5 w-5" />
                    </span>
                </div>
            </div>
        @endforeach
    </div>

    <x-admin.card class="!p-0">
        <div class="flex flex-col gap-3 border-b border-cine-border/10 px-4 py-4 sm:flex-row sm:items-center sm:justify-between sm:px-5">
            <div>
                <h3 class="text-sm font-bold text-cine-primary">Team members</h3>
                <p class="mt-1 text-[10px] text-cine-tertiary">Admins and staff accounts</p>
            </div>
            <div class="flex items-center gap-3">
                <span class="whitespace-nowrap text-[10px] text-cine-tertiary">{{ number_format($members->total()) }} matching</span>
                <form method="GET" action="{{ route('admin.staff-admins.index') }}" class="relative w-full sm:w-80">
                    <input type="hidden" name="role" value="{{ $filters['role'] }}">
                    <x-admin.icon name="search" class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-cine-tertiary" />
                    <input name="search" type="search" value="{{ $filters['search'] }}" placeholder="Search by name or email..." aria-label="Search staff and admins" class="min-h-9 w-full rounded-lg border border-cine-border/15 bg-cine-bg pl-9 pr-3 text-[10px] text-cine-primary placeholder:text-cine-tertiary focus:border-cine-accent/50 focus:outline-none focus:ring-2 focus:ring-cine-accent/20">
                </form>
            </div>
        </div>

        @if ($members->isEmpty())
            <div class="p-8">
                <x-admin.empty-state icon="users" title="No team members found" text="Try changing your search or role filter." />
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="admin-data-table w-full min-w-[760px] table-fixed divide-y divide-cine-border/10">
                    <colgroup>
                        <col class="w-[30%]">
                        <col class="w-[13%]">
                        <col class="w-[17%]">
                        <col class="w-[18%]">
                        <col class="w-[22%]">
                    </colgroup>
                    <thead class="bg-cine-elevated/40">
                        <tr class="text-left text-[9px] font-bold uppercase tracking-wider text-cine-tertiary">
                            <th scope="col" class="px-3 py-3">Member</th>
                            <th scope="col" class="px-3 py-3">Role</th>
                            <th scope="col" class="px-3 py-3">Scans</th>
                            <th scope="col" class="px-3 py-3">Last scan</th>
                            <th scope="col" class="px-3 py-3">Joined</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-cine-border/10">
                        @foreach ($members as $member)
                            @php
                                $isStaff = $member->isStaff();
                                $initials = collect(preg_split('/\s+/', trim($member->name)) ?: [])
                                    ->take(2)
                                    ->map(fn (string $part): string => mb_strtoupper(mb_substr($part, 0, 1)))
                                    ->implode('');
                            @endphp
                            <tr class="{{ $isStaff ? 'cursor-pointer transition hover:bg-cine-elevated/30' : '' }}">
                                <td class="px-3 py-3">
                                    <div class="flex min-w-0 items-center gap-2.5">
                                        <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full border border-cine-accent/15 bg-cine-accent/10 text-[10px] font-bold text-cine-accent">
                                            {{ $initials }}
                                        </span>
                                        <div class="min-w-0">
                                            @if ($isStaff)
                                                <a href="{{ route('admin.staff-admins.show', $member) }}" class="block truncate text-xs font-semibold text-cine-primary transition hover:text-cine-accent">
                                                    {{ $member->name }}
                                                </a>
                                            @else
                                                <span class="block truncate text-xs font-semibold text-cine-primary">{{ $member->name }}</span>
                                            @endif
                                            <span class="mt-1 block truncate text-[10px] text-cine-tertiary">{{ $member->email }}</span>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-3 py-3">
                                    @if ($isStaff)
                                        <x-admin.badge status="success">Staff</x-admin.badge>
                                    @else
                                        <x-admin.badge status="info">Admin</x-admin.badge>
                                    @endif
                                </td>
                                <td class="px-3 py-3 text-xs font-semibold text-cine-primary">
                                    @if ($isStaff)
                                        {{ number_format($member->ticket_scans_count) }}
                                        <span class="mt-1 block text-[9px] font-normal text-cine-tertiary">total scans</span>
                                    @else
                                        <span class="text-cine-tertiary">—</span>
                                    @endif
                                </td>
                                <td class="px-3 py-3 text-[10px] text-cine-secondary">
                                    @if (! $isStaff)
                                        <span class="text-cine-tertiary">Owner account</span>
                                    @elseif ($member->ticket_scans_max_scanned_at)
                                        {{ \Illuminate\Support\Carbon::parse($member->ticket_scans_max_scanned_at)->timezone(config('app.timezone'))->format('M j · g:i A') }}
                                    @else
                                        <span class="text-cine-tertiary">No scans yet</span>
                                    @endif
                                </td>
                                <td class="px-3 py-3 text-[10px] text-cine-secondary">
                                    {{ $member->created_at->format('M j, Y') }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="flex flex-col gap-3 border-t border-cine-border/10 px-4 py-3 sm:flex-row sm:items-center sm:justify-between">
                <p class="text-[11px] text-cine-tertiary">
                    Showing {{ $members->firstItem() }}–{{ $members->lastItem() }} of {{ number_format($members->total()) }} team members
                </p>
                <div>{{ $members->onEachSide(1)->links('vendor.pagination.admin') }}</div>
            </div>
        @endif
    </x-admin.card>

    <p class="mt-3 text-[10px] leading-5 text-cine-tertiary">Staff scan history is retained for audit purposes. Administrator accounts are not shown as staff details.</p>

    <div class="mt-4 grid grid-cols-1 gap-3 sm:grid-cols-2">
        <x-admin.card class="!p-4">
            <div class="flex items-center gap-2">
                <x-admin.badge status="info">Admin</x-admin.badge>
                <h3 class="text-xs font-semibold text-cine-primary">Manages the cinema panel</h3>
            </div>
            <ul class="mt-3 space-y-2 text-[10px] text-cine-secondary">
                <li>Movies, showtimes, bookings, refunds and settings</li>
                <li>Staff accounts are controlled by the system owner</li>
                <li>Admin accounts cannot be deleted here</li>
            </ul>
        </x-admin.card>
        <x-admin.card class="!p-4">
            <div class="flex items-center gap-2">
                <x-admin.badge status="success">Staff</x-admin.badge>
                <h3 class="text-xs font-semibold text-cine-primary">Works at the door</h3>
            </div>
            <ul class="mt-3 space-y-2 text-[10px] text-cine-secondary">
                <li>Scans tickets at the cinema</li>
                <li>Cannot open the admin panel</li>
                <li>Scan history is retained for the audit trail</li>
            </ul>
        </x-admin.card>
    </div>
</x-admin.layout>
