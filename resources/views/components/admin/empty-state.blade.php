@props([
    'icon' => 'film',
    'title' => 'Coming soon',
    'text' => 'This area is being prepared. Check back soon for updates.',
])

<div class="mx-auto flex max-w-lg flex-col items-center py-10 text-center sm:py-14">
    <span class="mb-5 flex h-14 w-14 items-center justify-center rounded-2xl border border-cine-accent/20 bg-cine-accent/10 text-cine-accent">
        <x-admin.icon :name="$icon" class="h-6 w-6" />
    </span>
    <h3 class="text-lg font-semibold text-cine-primary">{{ $title }}</h3>
    <p class="mt-2 text-sm leading-6 text-cine-secondary">{{ $text }}</p>
</div>
