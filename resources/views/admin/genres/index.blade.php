<x-admin.layout title="Genres" breadcrumb="Catalog">
    <div x-data="{ createGenreOpen: false }">
        <x-admin.page-header title="Genres" subtitle="Keep movie discovery organized with a clean genre catalog.">
            <x-slot:actions><x-admin.button x-on:click="createGenreOpen = true">Add genre</x-admin.button></x-slot:actions>
        </x-admin.page-header>
        <x-admin.modal name="createGenreOpen" title="Add genre">
            <form method="POST" action="{{ route('admin.genres.store') }}" class="space-y-4" x-data="{ saving: false }" x-on:submit="if (saving) { $event.preventDefault(); return; } saving = true">
                @csrf
                <x-admin.input name="name" label="Genre name" required />
                <div class="flex justify-end"><x-admin.button type="submit" x-bind:disabled="saving">Create genre</x-admin.button></div>
            </form>
        </x-admin.modal>
    <x-admin.toast />

    <x-admin.filters-bar :action="route('admin.genres.index')">
        <label class="text-xs font-semibold text-cine-secondary">Sort
            <select name="sort" class="mt-1.5 min-h-10 rounded-xl border border-cine-border/10 bg-cine-bg px-3 text-sm text-cine-primary">
                @foreach (['name' => 'Name', 'slug' => 'Slug', 'movies_count' => 'Movie count', 'created_at' => 'Created'] as $key => $label)
                    <option value="{{ $key }}" @selected(request('sort', 'name') === $key)>{{ $label }}</option>
                @endforeach
            </select>
        </label>
    </x-admin.filters-bar>

    <div class="mt-5 overflow-hidden rounded-2xl border border-cine-border/10 bg-cine-surface">
        <div class="overflow-x-auto">
            <table class="min-w-full text-left text-sm">
                <thead class="sticky top-0 bg-cine-elevated/80 text-xs uppercase text-cine-tertiary"><tr><th class="px-4 py-3">Genre</th><th class="px-4 py-3">Slug</th><th class="px-4 py-3">Movies</th><th class="px-4 py-3">Created</th><th class="px-4 py-3">Actions</th></tr></thead>
                <tbody class="divide-y divide-cine-border/10">
                    @forelse ($genres as $genre)
                        <tr class="hover:bg-cine-elevated/30">
                            <td class="px-4 py-3 font-semibold text-cine-primary">{{ $genre->name }}</td>
                            <td class="px-4 py-3 text-cine-secondary">{{ $genre->slug }}</td>
                            <td class="px-4 py-3 text-cine-secondary">{{ $genre->movies_count }}</td>
                            <td class="px-4 py-3 text-cine-secondary">{{ $genre->created_at->format('M j, Y') }}</td>
                            <td class="px-4 py-3">
                                <div x-data="{ editGenreOpen: false }" class="inline">
                                    <button type="button" x-on:click="editGenreOpen = true" class="rounded-lg px-3 py-2 text-xs font-semibold text-cine-accent hover:bg-cine-elevated">Edit</button>
                                    <x-admin.modal name="editGenreOpen" :id="'genre-edit-'.$genre->id" title="Edit genre">
                                        <form method="POST" action="{{ route('admin.genres.update', $genre) }}" class="space-y-4" x-data="{ saving: false }" x-on:submit="if (saving) { $event.preventDefault(); return; } saving = true">@csrf @method('PUT')<x-admin.input name="name" label="Name" :value="$genre->name" required /><div class="flex justify-end"><x-admin.button type="submit" x-bind:disabled="saving">Save changes</x-admin.button></div></form>
                                    </x-admin.modal>
                                </div>
                                @if ($genre->movies_count === 0)
                                    <x-admin.confirm-dialog :action="route('admin.genres.destroy', $genre)" :id="'genre-delete-'.$genre->id" method="DELETE" title="Delete genre" message="Delete this genre from the catalog?">
                                        <button type="button" x-on:click="open = true" class="ml-2 rounded-lg px-3 py-2 text-xs font-semibold text-cine-danger hover:bg-cine-danger/10">Delete</button>
                                    </x-admin.confirm-dialog>
                                @else
                                    <span class="ml-2 text-xs text-cine-tertiary" title="Genres assigned to movies cannot be deleted">In use · {{ $genre->movies_count }} movies</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="px-5 py-10"><x-admin.empty-state title="No genres found" text="Create a genre to organize movies." icon="tags" /></td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="border-t border-cine-border/10 px-4 py-3">{{ $genres->withQueryString()->links() }}</div>
    </div>
    </div>
</x-admin.layout>
