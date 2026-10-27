@props([
    'title' => 'Admin',
    'breadcrumb' => 'Control room',
])

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <meta name="color-scheme" content="light dark">
        <title>{{ $title }} · {{ config('app.name', 'CineBook') }}</title>
        <link rel="icon" href="data:,">
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=manrope:400,500,600,700&display=swap" rel="stylesheet">
        @vite(['resources/css/admin.css', 'resources/js/app.js'])
    </head>
    <body>
        <div
            x-data="adminShell"
            @keydown.escape.window="mobileOpen = false; userMenuOpen = false"
            class="admin-shell"
            :data-theme="theme"
        >
            <div
                x-cloak
                x-show="mobileOpen"
                x-transition.opacity
                class="fixed inset-0 z-40 bg-black/70 backdrop-blur-sm lg:hidden"
            >
                <button
                    type="button"
                    class="absolute inset-0 h-full w-full cursor-default"
                    aria-label="Close navigation"
                    @click="mobileOpen = false"
                ></button>
            </div>

            <div class="flex min-h-screen">
                <x-admin.sidebar />

                <div class="min-w-0 flex-1">
                    <button type="button" @click="mobileOpen = true" class="fixed left-4 top-4 z-30 flex h-10 w-10 items-center justify-center rounded-xl border border-cine-border/10 bg-cine-surface text-cine-secondary lg:hidden" aria-label="Open navigation">
                        <x-admin.icon name="menu" class="h-5 w-5" />
                    </button>
                    <main class="mx-auto w-full max-w-[1600px] px-4 py-7 sm:px-6 lg:px-9 lg:py-9">
                        {{ $slot }}
                    </main>
                </div>
            </div>

            <div
                x-cloak
                x-data="deleteConfirmationToast"
                @confirm-delete.window="ask($event.detail)"
                x-show="open"
                x-transition:enter="transition ease-out duration-200"
                x-transition:enter-start="translate-y-4 opacity-0"
                x-transition:enter-end="translate-y-0 opacity-100"
                x-transition:leave="transition ease-in duration-150"
                x-transition:leave-start="translate-y-0 opacity-100"
                x-transition:leave-end="translate-y-4 opacity-0"
                role="alertdialog"
                aria-live="assertive"
                aria-label="Confirm movie deletion"
                class="fixed bottom-5 left-1/2 z-[200] w-[min(92vw,440px)] -translate-x-1/2 rounded-2xl border border-cine-danger/30 bg-cine-surface p-4 shadow-cine-card"
            >
                <div class="flex items-start gap-3">
                    <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-cine-danger/10 text-cine-danger">
                        <x-admin.icon name="alert-triangle" class="h-4 w-4" />
                    </span>
                    <div class="min-w-0 flex-1">
                        <p class="text-sm font-semibold text-cine-primary">Delete movie?</p>
                        <p class="mt-1 truncate text-xs text-cine-secondary" x-text="`Delete “${title}”? This cannot be undone.`"></p>
                        <div class="mt-3 flex justify-end gap-2">
                            <button
                                type="button"
                                @click="dismiss()"
                                class="min-h-8 rounded-lg border border-cine-border/10 bg-cine-elevated px-3 text-xs font-semibold text-cine-secondary transition hover:text-cine-primary"
                            >
                                Cancel
                            </button>
                            <button
                                type="button"
                                @click="confirm()"
                                class="min-h-8 rounded-lg border border-cine-danger/30 bg-cine-danger px-3 text-xs font-semibold text-white transition hover:bg-cine-danger/90"
                            >
                                Confirm delete
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </body>
</html>
