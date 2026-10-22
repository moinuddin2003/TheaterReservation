@php($editing = $movie !== null)
@php($posterUrl = $movie?->posterUrl())
@php($backdropUrl = $movie?->backdropUrl())
@php($selectedGenres = (array) old('genres', $movie?->genres->pluck('id')->all() ?? []))
@php($fieldClass = 'min-h-11 w-full rounded-lg border border-cine-border/10 bg-cine-bg px-3 py-2 text-sm text-cine-primary placeholder:text-cine-tertiary focus:border-cine-accent/50 focus:outline-none focus:ring-2 focus:ring-cine-accent/20')
@php($labelClass = 'mb-2 block text-[10px] font-semibold uppercase tracking-wider text-cine-tertiary')

<x-admin.layout :title="$editing ? 'Edit movie' : 'Add movie'" breadcrumb="CineBook / Admin / Movies">
    <x-admin.page-header
        :title="$editing ? 'Edit movie' : 'Add movie'"
        :subtitle="$editing ? 'Update this title in your movie catalog.' : 'Add a title to your movie catalog.'"
        :back-url="route('admin.movies.index')"
        back-label="Back to movies"
    >
    </x-admin.page-header>

    @if ($errors->any())
        <div role="alert" class="mb-5 rounded-xl border border-cine-danger/20 bg-cine-danger/10 px-4 py-3 text-sm text-cine-danger">
            <p class="font-semibold">Please correct the highlighted fields.</p>
            <ul class="mt-2 list-inside list-disc">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form
        method="POST"
        enctype="multipart/form-data"
        action="{{ $editing ? route('admin.movies.update', $movie) : route('admin.movies.store') }}"
        class="space-y-5"
        novalidate
    >
        @csrf
        @if ($editing)
            @method('PUT')
        @endif

        <div class="grid items-start gap-5 lg:grid-cols-[minmax(0,1fr)_minmax(240px,320px)]">
            <x-admin.card title="Movie information">
                <div class="space-y-3">
                    <div>
                        <label for="title" class="{{ $labelClass }}">Title <span class="text-cine-danger">*</span></label>
                        <input
                            id="title"
                            name="title"
                            value="{{ old('title', $movie?->title) }}"
                            required
                            maxlength="200"
                            autocomplete="off"
                            class="{{ $fieldClass }}"
                            placeholder="The Last Horizon"
                            @error('title') aria-invalid="true" aria-describedby="title-error" @enderror
                        >
                        <x-admin.field-error field="title" id="title-error" />
                    </div>

                    <div>
                        <label for="slug" class="{{ $labelClass }}">Slug</label>
                        <input
                            id="slug"
                            name="slug"
                            value="{{ old('slug', $movie?->slug) }}"
                            maxlength="220"
                            class="{{ $fieldClass }}"
                            placeholder="the-last-horizon"
                            @error('slug') aria-invalid="true" aria-describedby="slug-error" @enderror
                        >
                        <p class="mt-1 text-[11px] text-cine-tertiary">Unique URL: /movies/{{ old('slug', $movie?->slug ?: 'the-last-horizon') }}</p>
                        <x-admin.field-error field="slug" id="slug-error" />
                    </div>

                    <div>
                        <label for="synopsis" class="{{ $labelClass }}">Synopsis <span class="text-cine-danger">*</span></label>
                        <textarea
                            id="synopsis"
                            name="synopsis"
                            rows="4"
                            required
                            class="{{ $fieldClass }} resize-y"
                            placeholder="Write a short description of the movie..."
                            @error('synopsis') aria-invalid="true" aria-describedby="synopsis-error" @enderror
                        >{{ old('synopsis', $movie?->synopsis) }}</textarea>
                        <x-admin.field-error field="synopsis" id="synopsis-error" />
                    </div>

                    <div class="grid gap-3 sm:grid-cols-2">
                        <div>
                            <label for="duration_minutes" class="{{ $labelClass }}">Duration (minutes) <span class="text-cine-danger">*</span></label>
                            <input
                                id="duration_minutes"
                                name="duration_minutes"
                                type="number"
                                min="1"
                                max="600"
                                required
                                value="{{ old('duration_minutes', $movie?->duration_minutes) }}"
                                class="{{ $fieldClass }}"
                                placeholder="124"
                                @error('duration_minutes') aria-invalid="true" aria-describedby="duration-error" @enderror
                            >
                            <x-admin.field-error field="duration_minutes" id="duration-error" />
                        </div>
                        <div>
                            <label for="rating" class="{{ $labelClass }}">Rating</label>
                            <input
                                id="rating"
                                name="rating"
                                value="{{ old('rating', $movie?->rating) }}"
                                maxlength="10"
                                class="{{ $fieldClass }}"
                                placeholder="PG-13"
                                @error('rating') aria-invalid="true" aria-describedby="rating-error" @enderror
                            >
                            <x-admin.field-error field="rating" id="rating-error" />
                        </div>
                    </div>

                    <div class="grid gap-3 sm:grid-cols-2">
                        <div>
                            <label for="release_date" class="{{ $labelClass }}">Release date</label>
                            <input
                                id="release_date"
                                name="release_date"
                                type="date"
                                value="{{ old('release_date', $movie?->release_date?->format('Y-m-d')) }}"
                                class="{{ $fieldClass }}"
                                @error('release_date') aria-invalid="true" aria-describedby="release-date-error" @enderror
                            >
                            <x-admin.field-error field="release_date" id="release-date-error" />
                        </div>
                        <div>
                            <label for="status" class="{{ $labelClass }}">Status <span class="text-cine-danger">*</span></label>
                            <select id="status" name="status" required class="{{ $fieldClass }}" @error('status') aria-invalid="true" aria-describedby="status-error" @enderror>
                                @foreach ($statuses as $status)
                                    <option value="{{ $status->value }}" @selected(old('status', $movie?->status->value ?? \App\Enums\MovieStatus::ComingSoon->value) === $status->value)>
                                        {{ str($status->value)->replace('_', ' ')->title() }}
                                    </option>
                                @endforeach
                            </select>
                            <x-admin.field-error field="status" id="status-error" />
                        </div>
                    </div>

                    <div>
                        <label for="trailer_url" class="{{ $labelClass }}">Trailer URL (HTTPS)</label>
                        <input
                            id="trailer_url"
                            name="trailer_url"
                            type="url"
                            value="{{ old('trailer_url', $movie?->trailer_url) }}"
                            maxlength="255"
                            class="{{ $fieldClass }}"
                            placeholder="https://trailers.example.com/the-last-horizon"
                            @error('trailer_url') aria-invalid="true" aria-describedby="trailer-url-error" @enderror
                        >
                        <x-admin.field-error field="trailer_url" id="trailer-url-error" />
                    </div>

                    <fieldset>
                        <legend class="{{ $labelClass }}">Genres</legend>
                        @if ($genres->isEmpty())
                            <p class="text-xs text-cine-tertiary">No genres are available yet.</p>
                        @else
                            <div class="grid grid-cols-2 gap-x-3 gap-y-2 sm:grid-cols-3">
                                @foreach ($genres as $genre)
                                    <label class="flex min-w-0 items-center gap-2 rounded-lg px-2 py-1.5 text-xs text-cine-secondary transition hover:bg-cine-elevated">
                                        <input
                                            type="checkbox"
                                            name="genres[]"
                                            value="{{ $genre->id }}"
                                            @checked(in_array($genre->id, $selectedGenres))
                                            class="rounded border-cine-border/20 bg-cine-bg text-cine-accent focus:ring-cine-accent"
                                            @if ($errors->has('genres') || $errors->has('genres.*')) aria-invalid="true" aria-describedby="genres-error" @endif
                                        >
                                        <span class="truncate">{{ $genre->name }}</span>
                                    </label>
                                @endforeach
                            </div>
                        @endif
                        <x-admin.field-error field="genres.*" id="genres-error" />
                    </fieldset>
                </div>
            </x-admin.card>

            <div class="space-y-4">
                <x-admin.card title="Poster">
                    <div class="space-y-3">
                        <div class="mx-auto flex aspect-[2/3] max-h-[360px] w-full items-center justify-center overflow-hidden rounded-xl border border-cine-border/10 bg-cine-bg">
                            <img
                                id="poster-preview"
                                src="{{ $posterUrl }}"
                                alt="{{ $movie?->title ? $movie->title.' poster' : 'Selected movie poster preview' }}"
                                @class(['h-full w-full object-cover' => $posterUrl, 'hidden' => ! $posterUrl])
                            >
                            <span id="poster-placeholder" @class(['text-cine-tertiary', 'hidden' => $posterUrl])>
                                <x-admin.icon name="film" class="h-10 w-10" />
                            </span>
                        </div>
                        <label
                            for="poster"
                            class="inline-flex min-h-9 cursor-pointer items-center justify-center rounded-lg border border-cine-border/10 bg-cine-elevated px-3 text-xs font-semibold text-cine-secondary transition hover:border-cine-accent/30 hover:text-cine-primary"
                        >
                            {{ $editing && $posterUrl ? 'Replace poster' : 'Choose poster' }}
                        </label>
                        <input
                            id="poster"
                            name="poster"
                            type="file"
                            accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp"
                            class="sr-only"
                            @error('poster') aria-invalid="true" aria-describedby="poster-error" @enderror
                        >
                        <p class="text-[11px] leading-5 text-cine-tertiary">
                            JPG, PNG, or WebP · Maximum 4 MB.
                            @if ($editing && $posterUrl)
                                Leave empty to keep the current poster.
                            @endif
                        </p>
                        <x-admin.field-error field="poster" id="poster-error" />
                    </div>
                </x-admin.card>

                <x-admin.card title="Backdrop">
                    <div class="space-y-3">
                        <div class="flex aspect-video w-full items-center justify-center overflow-hidden rounded-xl border border-cine-border/10 bg-cine-bg">
                            <img
                                id="backdrop-preview"
                                src="{{ $backdropUrl }}"
                                alt="{{ $movie?->title ? $movie->title.' backdrop' : 'Selected movie backdrop preview' }}"
                                @class(['h-full w-full object-cover' => $backdropUrl, 'hidden' => ! $backdropUrl])
                            >
                            <span id="backdrop-placeholder" @class(['text-cine-tertiary', 'hidden' => $backdropUrl])>
                                <x-admin.icon name="film" class="h-10 w-10" />
                            </span>
                        </div>
                        <label
                            for="backdrop"
                            class="inline-flex min-h-9 cursor-pointer items-center justify-center rounded-lg border border-cine-border/10 bg-cine-elevated px-3 text-xs font-semibold text-cine-secondary transition hover:border-cine-accent/30 hover:text-cine-primary"
                        >
                            {{ $editing && $backdropUrl ? 'Replace backdrop' : 'Choose backdrop' }}
                        </label>
                        <input
                            id="backdrop"
                            name="backdrop"
                            type="file"
                            accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp"
                            class="sr-only"
                            @error('backdrop') aria-invalid="true" aria-describedby="backdrop-error" @enderror
                        >
                        <p class="text-[11px] leading-5 text-cine-tertiary">
                            JPG, PNG, or WebP · Maximum 8 MB.
                            @if ($editing && $backdropUrl)
                                Leave empty to keep the current backdrop.
                            @endif
                        </p>
                        <x-admin.field-error field="backdrop" id="backdrop-error" />
                    </div>
                </x-admin.card>

                @if ($editing && $movie->showtimes_count > 0)
                    <div class="rounded-xl border border-cine-warning/20 bg-cine-warning/10 p-3 text-xs text-cine-secondary">
                        <p class="font-semibold text-cine-warning">Showtimes exist</p>
                        <p class="mt-1">This movie has {{ $movie->showtimes_count }} {{ Str::plural('showtime', $movie->showtimes_count) }}. Change its status instead of deleting it.</p>
                    </div>
                @elseif (! $editing)
                    <p class="px-1 text-[11px] leading-5 text-cine-tertiary">The movie will be added to the catalog using the details above.</p>
                @endif
            </div>
        </div>

        <div class="flex flex-wrap justify-end gap-3">
            <x-admin.button :href="route('admin.movies.index')" variant="secondary">Cancel</x-admin.button>
            <x-admin.button type="submit">{{ $editing ? 'Save changes' : 'Add movie' }}</x-admin.button>
        </div>

        <script>
            ['poster', 'backdrop'].forEach((imageType) => {
                document.getElementById(imageType)?.addEventListener('change', (event) => {
                    const file = event.target.files?.[0];
                    if (!file) return;

                    const preview = document.getElementById(`${imageType}-preview`);
                    const placeholder = document.getElementById(`${imageType}-placeholder`);
                    preview.src = URL.createObjectURL(file);
                    preview.classList.remove('hidden');
                    placeholder.classList.add('hidden');
                });
            });
        </script>
    </form>
</x-admin.layout>
