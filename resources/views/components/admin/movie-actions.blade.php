@props(['movie'])

<div x-data="movieActions" class="flex justify-end">
    <button
        type="button"
        aria-label="More actions for {{ $movie->title }}"
        popovertarget="movie-actions-{{ $movie->id }}"
        @click.prevent="open($refs.menu, $event.currentTarget)"
        class="flex h-8 w-8 items-center justify-center rounded-lg border border-cine-border/10 bg-cine-bg text-cine-secondary transition hover:border-cine-accent/30 hover:text-cine-primary focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-cine-accent/50"
    >
        <x-admin.icon name="more-vertical" class="h-4 w-4" />
    </button>
    <div
        id="movie-actions-{{ $movie->id }}"
        x-ref="menu"
        popover="auto"
        class="!fixed !m-0 z-[100] w-40 rounded-xl border border-cine-border/10 bg-cine-surface p-1.5 text-cine-primary shadow-cine-card backdrop:bg-transparent"
    >
        <a
            href="{{ route('admin.movies.edit', $movie) }}"
            class="block rounded-lg px-3 py-2 text-left text-xs font-medium text-cine-secondary transition hover:bg-cine-elevated hover:text-cine-primary"
        >
            Edit movie
        </a>
        <form
            id="delete-movie-{{ $movie->id }}"
            data-movie-title="{{ $movie->title }}"
            method="POST"
            action="{{ route('admin.movies.destroy', $movie) }}"
        >
            @csrf
            @method('DELETE')
            <button
                type="submit"
                @click.prevent="$dispatch('confirm-delete', { formId: $el.form.id, title: $el.form.dataset.movieTitle })"
                class="block w-full rounded-lg px-3 py-2 text-left text-xs font-medium text-cine-danger transition hover:bg-cine-danger/10"
            >
                Delete movie
            </button>
        </form>
    </div>
</div>
