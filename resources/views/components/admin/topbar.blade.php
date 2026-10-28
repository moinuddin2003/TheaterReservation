@props(['title', 'breadcrumb'])

<header class="sticky top-0 z-30 border-b border-cine-border/10 bg-cine-bg/80 backdrop-blur-xl">
    <div class="flex h-[76px] items-center justify-between gap-4 px-4 sm:px-6 lg:px-8">
        <div class="flex min-w-0 items-center gap-3 sm:gap-4">
            <button
                type="button"
                @click="toggleSidebar()"
                class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full border border-cine-border/10 bg-cine-surface text-cine-secondary transition-colors duration-150 hover:border-cine-accent/40 hover:text-cine-primary"
                aria-label="Toggle sidebar"
            >
                <x-admin.icon name="menu" class="h-5 w-5" />
            </button>
            <div class="min-w-0">
                <p class="truncate text-[10px] font-bold uppercase tracking-[0.2em] text-cine-tertiary">{{ $breadcrumb }}</p>
                <h1 class="truncate text-lg font-bold tracking-tight text-cine-primary sm:text-xl">{{ $title }}</h1>
            </div>
        </div>

        <div class="flex shrink-0 items-center gap-2 sm:gap-3">
            <button
                type="button"
                @click="toggleTheme()"
                class="flex h-10 w-10 items-center justify-center rounded-full border border-cine-border/10 bg-cine-surface text-cine-secondary transition-colors duration-150 hover:border-cine-accent/40 hover:text-cine-primary"
                :aria-label="theme === 'dark' ? 'Switch to light mode' : 'Switch to dark mode'"
                :title="theme === 'dark' ? 'Switch to light mode' : 'Switch to dark mode'"
                :aria-pressed="(theme === 'light').toString()"
            >
                <span x-show="theme === 'dark'">
                    <x-admin.icon name="sun" class="h-[18px] w-[18px]" />
                </span>
                <span x-show="theme === 'light'" x-cloak>
                    <x-admin.icon name="moon" class="h-[18px] w-[18px]" />
                </span>
            </button>

            <div class="relative ml-1">
                <button
                    type="button"
                    @click="userMenuOpen = !userMenuOpen"
                    @click.outside="userMenuOpen = false"
                    class="flex items-center gap-2 rounded-full border border-cine-border/10 bg-cine-surface py-1 pl-1 pr-2 transition-colors duration-150 hover:border-cine-accent/40 sm:gap-3 sm:pr-3"
                    :aria-expanded="userMenuOpen.toString()"
                    aria-haspopup="true"
                    aria-label="Open account menu"
                >
                    <span class="flex h-8 w-8 items-center justify-center rounded-full border border-cine-accent/30 bg-cine-elevated text-xs font-bold text-cine-primary">
                        {{ collect(explode(' ', auth()->user()->name))->filter()->take(2)->map(fn ($part) => mb_strtoupper(mb_substr($part, 0, 1)))->implode('') }}
                    </span>
                    <span class="hidden text-left sm:block">
                        <span class="block max-w-28 truncate text-xs font-semibold text-cine-primary">{{ auth()->user()->name }}</span>
                        <span class="block text-[10px] text-cine-secondary">Admin</span>
                    </span>
                    <x-admin.icon name="chevron-down" class="hidden h-4 w-4 text-cine-tertiary sm:block" />
                </button>

                <div
                    x-cloak
                    x-show="userMenuOpen"
                    x-transition.origin.top.right
                    class="absolute right-0 mt-3 w-52 rounded-2xl border border-cine-border/10 bg-cine-surface p-2 shadow-cine-card"
                    role="menu"
                >
                    <a href="{{ route('profile.edit') }}" class="flex items-center rounded-xl px-3 py-2.5 text-sm text-cine-secondary transition-colors hover:bg-cine-elevated hover:text-cine-primary" role="menuitem">
                        Profile
                    </a>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="flex w-full items-center rounded-xl px-3 py-2.5 text-left text-sm text-cine-secondary transition-colors hover:bg-cine-elevated hover:text-cine-primary" role="menuitem">
                            Log out
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</header>
