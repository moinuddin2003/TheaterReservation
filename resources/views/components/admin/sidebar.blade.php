@php
    $navigationGroups = collect(config('admin_navigation'))
        ->filter(fn (array $item) => in_array(auth()->user()->role->value, $item['roles'], true))
        ->groupBy('group');
    $initials = collect(explode(' ', auth()->user()->name))
        ->filter()
        ->take(2)
        ->map(fn (string $part) => mb_strtoupper(mb_substr($part, 0, 1)))
        ->implode('');
@endphp

<aside
    class="fixed inset-y-0 left-0 z-50 flex w-[264px] -translate-x-full flex-col border-r border-cine-border/10 bg-cine-bg/95 transition-[width,transform] duration-150 ease-out motion-reduce:transition-none lg:sticky lg:top-0 lg:h-screen lg:translate-x-0"
    :class="{
        'lg:w-[76px]': collapsed,
        'lg:w-[264px]': !collapsed,
        'translate-x-0': mobileOpen,
        '-translate-x-full': !mobileOpen,
    }"
    aria-label="Admin sidebar"
>
    <div class="flex h-[76px] shrink-0 items-center gap-3 border-b border-cine-border/10 px-5">
        <a href="{{ route('admin.dashboard') }}" class="flex min-w-0 items-center gap-3 rounded-xl focus-visible:shadow-cine-focus" aria-label="CineBook admin home">
            <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-2xl border border-cine-accent/40 bg-gradient-to-br from-cine-selected-start to-cine-selected-end text-white shadow-cine-glow">
                <x-admin.icon name="film" class="h-5 w-5" />
            </span>
            <span x-show="!collapsed || mobileOpen" x-transition.opacity class="min-w-0">
                <span class="block truncate text-base font-bold tracking-tight text-cine-primary">CineBook</span>
                <span class="block text-[10px] font-semibold uppercase tracking-[0.2em] text-cine-tertiary">Admin studio</span>
            </span>
        </a>
    </div>

    <nav
        x-ref="sidebarNavigation"
        @scroll.passive="saveSidebarScroll($event.target.scrollTop)"
        class="min-h-0 flex-1 space-y-6 overflow-y-auto px-3 py-5"
        aria-label="Admin navigation"
    >
        @foreach ($navigationGroups as $group => $items)
            <div>
                <p x-show="!collapsed || mobileOpen" x-transition.opacity class="mb-2 px-3 text-[10px] font-bold uppercase tracking-[0.2em] text-cine-tertiary">
                    {{ $group }}
                </p>
                <div class="space-y-1">
                    @foreach ($items as $item)
                        <x-admin.sidebar-link
                            :label="$item['label']"
                            :route="$item['route']"
                            :icon="$item['icon']"
                            :badge="$item['badge'] ?? null"
                        />
                    @endforeach
                </div>
            </div>
        @endforeach
    </nav>

    <div class="shrink-0 border-t border-cine-border/10 p-3">
        <div class="flex items-center gap-3 rounded-2xl bg-cine-surface px-3 py-3" :class="{ 'justify-center px-1': collapsed && !mobileOpen }">
            <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full border border-cine-accent/30 bg-cine-elevated text-sm font-bold text-cine-primary">
                {{ $initials }}
            </span>
            <div x-show="!collapsed || mobileOpen" x-transition.opacity class="min-w-0 flex-1">
                <p class="truncate text-sm font-semibold text-cine-primary">{{ auth()->user()->name }}</p>
                <p class="mt-0.5 text-xs text-cine-secondary">Admin</p>
            </div>
            <form x-show="!collapsed || mobileOpen" x-transition.opacity method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="rounded-xl p-2 text-cine-secondary transition-colors duration-150 hover:bg-cine-elevated hover:text-cine-primary" aria-label="Log out">
                    <x-admin.icon name="log-out" class="h-4 w-4" />
                </button>
            </form>
        </div>
        <form x-show="collapsed && !mobileOpen" x-transition.opacity method="POST" action="{{ route('logout') }}" class="mt-2 text-center">
            @csrf
            <button type="submit" class="rounded-xl p-2 text-cine-secondary transition-colors duration-150 hover:bg-cine-elevated hover:text-cine-primary" aria-label="Log out">
                <x-admin.icon name="log-out" class="h-4 w-4" />
            </button>
        </form>
    </div>
</aside>
