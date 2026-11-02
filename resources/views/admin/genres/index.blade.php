@php($fieldClass = 'min-h-10 w-full rounded-lg border border-cine-border/10 bg-cine-bg px-3 py-2 text-sm text-cine-primary placeholder:text-cine-tertiary focus:border-cine-accent/50 focus:outline-none focus:ring-2 focus:ring-cine-accent/20')
@php($labelClass = 'mb-1.5 block text-[10px] font-semibold uppercase tracking-wider text-cine-tertiary')
@php($genreFormBag = $editingGenre ? 'editGenre' : 'createGenre')
@php($genreNameId = $editingGenre ? 'edit-name' : 'create-name')
@php($genreSlugId = $editingGenre ? 'edit-slug' : 'create-slug')

<x-admin.layout title="Genres" breadcrumb="CineBook / Admin / Catalog">
    <x-admin.page-header
        title="Genres"
        subtitle="Organize your catalog and manage the genres used to discover movies."
    />

    @if (session('status'))
        <div role="status" class="mb-5 rounded-xl border border-cine-success/20 bg-cine-success/10 px-4 py-3 text-sm text-cine-success">
            {{ session('status') }}
        </div>
    @endif
    @if (session('error'))
        <div role="alert" class="mb-5 rounded-xl border border-cine-danger/20 bg-cine-danger/10 px-4 py-3 text-sm text-cine-danger">
            {{ session('error') }}
        </div>
    @endif

    <div class="grid items-start gap-5 xl:grid-cols-[minmax(0,1fr)_minmax(280px,360px)]">
        <div>
            <x-admin.card class="!p-0">
                @if ($genres->isEmpty())
                    <div class="p-5">
                        <x-admin.empty-state
                            icon="tags"
                            title="No genres yet"
                            text="Create your first genre with the form beside this list."
                        />
                    </div>
                @else
                    <div class="overflow-x-auto">
                        <table class="admin-data-table min-w-full divide-y divide-cine-border/10">
                            <thead class="bg-cine-bg/40">
                                <tr class="text-left text-[10px] font-bold uppercase tracking-wider text-cine-tertiary">
                                    <th scope="col" class="px-4 py-3 sm:px-5">Genre</th>
                                    <th scope="col" class="px-4 py-3">Slug</th>
                                    <th scope="col" class="px-4 py-3">Movies</th>
                                    <th scope="col" class="px-4 py-3 text-right">Manage</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-cine-border/10">
                                @foreach ($genres as $genre)
                                    <tr @class(['bg-cine-accent/5' => $editingGenre?->is($genre)])>
                                        <td class="whitespace-nowrap px-4 py-3.5 text-sm font-semibold text-cine-primary sm:px-5">{{ $genre->name }}</td>
                                        <td class="whitespace-nowrap px-4 py-3.5 text-xs text-cine-secondary">{{ $genre->slug }}</td>
                                        <td class="whitespace-nowrap px-4 py-3.5">
                                            <x-admin.badge :status="$genre->movies_count > 0 ? 'info' : 'warning'">
                                                {{ $genre->movies_count }}
                                            </x-admin.badge>
                                        </td>
                                        <td class="whitespace-nowrap px-4 py-3.5 text-right">
                                            <div class="inline-flex items-center gap-3 text-xs">
                                                <a
                                                    href="{{ route('admin.genres.edit', $genre) }}"
                                                    class="font-semibold text-cine-accent transition hover:text-cine-primary"
                                                >
                                                    Edit
                                                </a>
                                                <a
                                                    href="{{ route('admin.genres.index', ['delete' => $genre->id]) }}#delete-confirmation"
                                                    class="font-semibold text-cine-danger transition hover:text-cine-primary"
                                                >
                                                    Delete
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </x-admin.card>
            <p class="mt-2 px-1 text-xs text-cine-tertiary">
                {{ $genres->count() }} {{ Str::plural('genre', $genres->count()) }} · Used to filter movie discovery.
            </p>

            @if ($deletingGenre)
                <x-admin.card id="delete-confirmation" class="mt-5 border-cine-danger/20">
                    @if ($deletingGenre->movies->isNotEmpty())
                        <div class="flex items-start gap-3">
                            <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-cine-warning/10 text-cine-warning">
                                <x-admin.icon name="alert-triangle" class="h-4 w-4" />
                            </span>
                            <div>
                                <p class="text-[10px] font-bold uppercase tracking-wider text-cine-warning">Delete confirmation · In use</p>
                                <h3 class="mt-2 text-sm font-bold text-cine-primary">
                                    {{ $deletingGenre->name }} is used by {{ $deletingGenre->movies->count() }} {{ Str::plural('movie', $deletingGenre->movies->count()) }}.
                                </h3>
                                <p class="mt-2 text-xs leading-5 text-cine-secondary">
                                    Remove this genre from these movies before deleting it:
                                    {{ $deletingGenre->movies->pluck('title')->join(', ') }}.
                                </p>
                                <x-admin.button :href="route('admin.genres.index')" variant="secondary" class="mt-4 !min-h-8 !rounded-lg !px-3 !py-1 !text-xs">
                                    Keep genre
                                </x-admin.button>
                            </div>
                        </div>
                    @else
                        <p class="text-[10px] font-bold uppercase tracking-wider text-cine-danger">Delete confirmation</p>
                        <h3 class="mt-2 text-sm font-bold text-cine-primary">Delete {{ $deletingGenre->name }}?</h3>
                        <p class="mt-2 text-xs leading-5 text-cine-secondary">
                            This genre is not assigned to any movies. Deleting it permanently removes its name and slug.
                        </p>
                        <div class="mt-4 flex flex-wrap gap-2">
                            <x-admin.button :href="route('admin.genres.index')" variant="secondary" class="!min-h-8 !rounded-lg !px-3 !py-1 !text-xs">
                                Keep genre
                            </x-admin.button>
                            <form method="POST" action="{{ route('admin.genres.destroy', $deletingGenre) }}">
                                @csrf
                                @method('DELETE')
                                <x-admin.button type="submit" variant="danger" class="!min-h-8 !rounded-lg !px-3 !py-1 !text-xs">
                                    Confirm deletion
                                </x-admin.button>
                            </form>
                        </div>
                    @endif
                </x-admin.card>
            @endif
        </div>

        <div>
            <x-admin.card
                :title="$editingGenre ? 'Edit genre' : 'Create genre'"
                :subtitle="$editingGenre ? 'Update the genre name and URL slug.' : 'Add a genre for movie discovery and filtering.'"
            >
                <form
                    method="POST"
                    action="{{ $editingGenre ? route('admin.genres.update', $editingGenre) : route('admin.genres.store') }}"
                    class="space-y-3"
                    novalidate
                >
                    @csrf
                    @if ($editingGenre)
                        @method('PUT')
                    @endif
                    <div>
                        <label for="{{ $genreNameId }}" class="{{ $labelClass }}">Name <span class="text-cine-danger">*</span></label>
                        <input
                            id="{{ $genreNameId }}"
                            name="name"
                            value="{{ old('name', $editingGenre?->name) }}"
                            required
                            maxlength="60"
                            class="{{ $fieldClass }}"
                            @if ($errors->getBag($genreFormBag)->has('name')) aria-invalid="true" aria-describedby="{{ $genreNameId }}-error" @endif
                        >
                        <x-admin.field-error field="name" :bag="$genreFormBag" :id="$genreNameId.'-error'" />
                    </div>
                    <div>
                        <label for="{{ $genreSlugId }}" class="{{ $labelClass }}">Slug <span class="text-cine-danger">*</span></label>
                        <input
                            id="{{ $genreSlugId }}"
                            name="slug"
                            value="{{ old('slug', $editingGenre?->slug) }}"
                            @required($editingGenre)
                            maxlength="80"
                            class="{{ $fieldClass }}"
                            placeholder="{{ $editingGenre ? '' : 'generated-from-name' }}"
                            @if ($errors->getBag($genreFormBag)->has('slug')) aria-invalid="true" aria-describedby="{{ $genreSlugId }}-error" @endif
                        >
                        @unless ($editingGenre)
                            <p class="mt-1 text-[11px] text-cine-tertiary">Leave blank to generate it from the name.</p>
                        @endunless
                        <x-admin.field-error field="slug" :bag="$genreFormBag" :id="$genreSlugId.'-error'" />
                    </div>
                    <div class="flex gap-2 pt-1">
                        <x-admin.button type="submit" class="!min-h-9 !rounded-lg !px-3 !py-1 !text-xs">
                            {{ $editingGenre ? 'Save genre' : 'Add genre' }}
                        </x-admin.button>
                        @if ($editingGenre)
                            <x-admin.button :href="route('admin.genres.index')" variant="secondary" class="!min-h-9 !rounded-lg !px-3 !py-1 !text-xs">
                                Cancel
                            </x-admin.button>
                        @endif
                    </div>
                </form>
            </x-admin.card>
        </div>
    </div>
</x-admin.layout>
