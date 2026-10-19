<x-admin.layout title="Genres" breadcrumb="Catalog">
    <x-admin.page-header
        title="Genres"
        subtitle="Browse movie genres and see how many catalog titles are assigned to each."
    />

    <x-admin.card>
        @if ($genres->isEmpty())
            <x-admin.empty-state
                icon="tags"
                title="No genres yet"
                text="Genres added to the catalog will appear here."
            />
        @else
            <div class="-mx-5 overflow-x-auto sm:-mx-6">
                <table class="min-w-full divide-y divide-cine-border/10">
                    <thead class="bg-cine-bg/50">
                        <tr class="text-left text-xs font-semibold uppercase tracking-wider text-cine-tertiary">
                            <th scope="col" class="px-5 py-3 sm:px-6">Genre</th>
                            <th scope="col" class="px-5 py-3">Slug</th>
                            <th scope="col" class="px-5 py-3">Movies</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-cine-border/10">
                        @foreach ($genres as $genre)
                            <tr class="text-sm">
                                <td class="px-5 py-4 font-semibold text-cine-primary sm:px-6">{{ $genre->name }}</td>
                                <td class="px-5 py-4 text-cine-secondary">{{ $genre->slug }}</td>
                                <td class="px-5 py-4 text-cine-secondary">{{ $genre->movies_count }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </x-admin.card>
</x-admin.layout>
