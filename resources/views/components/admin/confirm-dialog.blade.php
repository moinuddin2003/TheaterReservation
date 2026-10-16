@props([
    'action',
    'method' => 'POST',
    'title' => 'Confirm action',
    'message' => 'Are you sure you want to continue?',
    'reason' => false,
    'reasonName' => 'reason',
    'id' => null,
    'hidden' => [],
])

@php($confirmId = $id ?? 'confirm-'.\Illuminate\Support\Str::uuid())

<div x-data="{ open: false }">
    {{ $slot }}
    <div x-cloak x-show="open" x-on:keydown.escape.window="open = false" class="fixed inset-0 z-[80] grid place-items-center bg-cine-bg/80 p-4 backdrop-blur-sm" role="alertdialog" aria-modal="true" aria-labelledby="{{ $confirmId }}-title">
        <button type="button" class="fixed inset-0 cursor-default" aria-label="Cancel action" x-on:click="open = false"></button>
        <section x-trap.noscroll="open" class="relative z-10 w-full max-w-md rounded-2xl border border-cine-border/10 bg-cine-surface p-6 shadow-cine-card">
            <h2 id="{{ $confirmId }}-title" class="text-lg font-semibold text-cine-primary">{{ $title }}</h2>
            <p class="mt-2 text-sm leading-6 text-cine-secondary">{{ $message }}</p>
            <form method="POST" action="{{ $action }}" class="mt-5">
                @csrf
                @if (strtoupper($method) !== 'POST')
                    @method($method)
                @endif
                @foreach ($hidden as $name => $value)
                    <input type="hidden" name="{{ $name }}" value="{{ $value }}">
                @endforeach
                @if ($reason)
                    <x-admin.textarea :name="$reasonName" label="Reason" required rows="3" />
                @endif
                <div class="mt-6 flex justify-end gap-3">
                    <x-admin.button variant="secondary" x-on:click="open = false">Cancel</x-admin.button>
                    <x-admin.button variant="danger" type="submit">Confirm</x-admin.button>
                </div>
            </form>
        </section>
    </div>
</div>
