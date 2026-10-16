<?php

use App\Enums\MovieStatus;
use App\Enums\Role;
use App\Enums\ShowtimeStatus;
use App\Models\Cinema;
use App\Models\Genre;
use App\Models\Movie;
use App\Models\Screen;
use App\Models\Seat;
use App\Models\Showtime;
use App\Models\User;
use Illuminate\Support\Facades\DB;

function adminCatalogMovieData(array $overrides = []): array
{
    return array_merge([
        'title' => 'Sample Feature',
        'synopsis' => 'A feature film for the catalog.',
        'duration_minutes' => 110,
        'rating' => 'PG-13',
        'release_date' => now()->toDateString(),
        'trailer_url' => null,
        'status' => MovieStatus::ComingSoon->value,
        'genre_ids' => [],
    ], $overrides);
}

function adminCatalogShowtimeData(int $movieId, int $screenId, string $startsAt, array $overrides = []): array
{
    return array_merge([
        'movie_id' => $movieId,
        'screen_id' => $screenId,
        'starts_at' => $startsAt,
        'base_price_cents' => 1800,
    ], $overrides);
}

test('administrators can view the requested admin pages', function () {
    $admin = User::factory()->create(['role' => Role::Admin]);

    $this->actingAs($admin);
    foreach ([
        'admin.dashboard',
        'admin.movies.index',
        'admin.movies.create',
        'admin.genres.index',
        'admin.cinema.index',
        'admin.screens.create',
        'admin.showtimes.index',
        'admin.showtimes.create',
    ] as $route) {
        $this->get(route($route))->assertOk();
    }
});

test('customers and staff are forbidden from requested admin pages', function (Role $role) {
    $this->actingAs(User::factory()->create(['role' => $role]));

    foreach ([
        'admin.dashboard',
        'admin.movies.index',
        'admin.movies.create',
        'admin.genres.index',
        'admin.cinema.index',
        'admin.screens.create',
        'admin.showtimes.index',
        'admin.showtimes.create',
    ] as $route) {
        $this->get(route($route))->assertForbidden();
    }
    $this->post(route('admin.movies.store'), [])->assertForbidden();
    $this->post(route('admin.genres.store'), [])->assertForbidden();
    $this->post(route('admin.cinema.store'), [])->assertForbidden();
})->with([
    'customer' => [Role::Customer],
    'staff' => [Role::Staff],
]);

test('admins can create update and delete movies with validation and audit logs', function () {
    $admin = User::factory()->create(['role' => Role::Admin]);
    $genre = Genre::query()->create(['name' => 'Adventure', 'slug' => 'adventure']);
    $this->actingAs($admin);

    $this->post(route('admin.movies.store'), [])->assertSessionHasErrors(['title', 'synopsis', 'duration_minutes', 'status']);
    $this->post(route('admin.movies.store'), adminCatalogMovieData(['genre_ids' => [$genre->id]]))
        ->assertRedirect();

    $movie = Movie::query()->where('title', 'Sample Feature')->firstOrFail();
    expect($movie->genres()->whereKey($genre->id)->exists())->toBeTrue();

    $this->put(route('admin.movies.update', $movie), adminCatalogMovieData([
        'title' => 'Updated Feature',
        'genre_ids' => [],
    ]))->assertRedirect();
    $this->get(route('admin.movies.show', $movie))->assertOk();
    $this->get(route('admin.movies.edit', $movie))->assertOk();
    $movie->refresh();
    expect($movie->title)->toBe('Updated Feature')
        ->and($movie->genres()->exists())->toBeFalse();

    $this->delete(route('admin.movies.destroy', $movie))->assertRedirect(route('admin.movies.index'));
    expect(Movie::query()->whereKey($movie->id)->exists())->toBeFalse()
        ->and(DB::table('audit_logs')->whereIn('action', ['movie.created', 'movie.updated', 'movie.deleted'])->count())->toBe(3);
});

test('genres can be created updated and deleted while duplicates and in-use deletes are rejected', function () {
    $admin = User::factory()->create(['role' => Role::Admin]);
    $this->actingAs($admin);

    $this->post(route('admin.genres.store'), ['name' => 'Drama'])->assertRedirect();
    $genre = Genre::query()->where('name', 'Drama')->firstOrFail();
    expect($genre->slug)->toBe('drama');

    $this->post(route('admin.genres.store'), ['name' => 'Drama'])->assertSessionHasErrors('name');
    $this->put(route('admin.genres.update', $genre), ['name' => 'Mystery'])->assertRedirect();
    expect($genre->fresh()->slug)->toBe('mystery');

    $movieData = adminCatalogMovieData(['slug' => 'mystery-film']);
    unset($movieData['genre_ids']);
    $movie = Movie::query()->create($movieData);
    $genre->movies()->attach($movie);
    $this->delete(route('admin.genres.destroy', $genre))->assertSessionHasErrors('genre');

    $genre->movies()->detach();
    $this->delete(route('admin.genres.destroy', $genre))->assertRedirect(route('admin.genres.index'));
    expect(Genre::query()->whereKey($genre->id)->exists())->toBeFalse();
});

test('cinema profile can be updated and screens can be generated resized and deleted', function () {
    $admin = User::factory()->create(['role' => Role::Admin]);
    $this->actingAs($admin);

    $this->post(route('admin.cinema.store'), ['name' => 'Original Cinema'])->assertRedirect();
    $cinema = Cinema::query()->where('name', 'Original Cinema')->firstOrFail();
    $this->patch(route('admin.cinema.update', $cinema), [
        'name' => 'Renamed Cinema',
        'address' => 'New address',
        'phone' => '555-1234',
    ])->assertRedirect();
    expect($cinema->fresh()->name)->toBe('Renamed Cinema');

    $this->post(route('admin.screens.store'), [
        'cinema_id' => $cinema->id,
        'name' => 'Screen One',
        'rows_count' => 2,
        'seats_per_row' => 3,
    ])->assertRedirect();
    $screen = Screen::query()->where('name', 'Screen One')->firstOrFail();
    expect($screen->seats()->count())->toBe(6);
    $this->get(route('admin.screens.edit', $screen))->assertOk();
    $this->get(route('admin.seats.index', $screen))->assertOk();

    $this->put(route('admin.screens.update', $screen), [
        'cinema_id' => $cinema->id,
        'name' => 'Screen Updated',
        'rows_count' => 3,
        'seats_per_row' => 3,
        'is_active' => '1',
    ])->assertRedirect();
    expect($screen->fresh()->seats()->count())->toBe(9);

    $seat = $screen->seats()->firstOrFail();
    $this->patch(route('admin.seats.toggle-disabled', $seat))->assertRedirect();
    expect($seat->fresh()->is_disabled)->toBeTrue();

    $this->delete(route('admin.screens.destroy', $screen))->assertRedirect(route('admin.cinema.index'));
    expect(Screen::query()->whereKey($screen->id)->exists())->toBeFalse()
        ->and(Seat::query()->where('screen_id', $screen->id)->exists())->toBeFalse();
    $this->delete(route('admin.cinema.destroy', $cinema))->assertRedirect(route('admin.cinema.index'));
    expect(Cinema::query()->whereKey($cinema->id)->exists())->toBeFalse();
});

test('showtimes can be created updated cancelled and reject screen overlaps', function () {
    $admin = User::factory()->create(['role' => Role::Admin]);
    $cinema = Cinema::query()->create(['name' => 'Cineplex']);
    $screen = Screen::query()->create([
        'cinema_id' => $cinema->id,
        'name' => 'Screen A',
        'rows_count' => 2,
        'seats_per_row' => 4,
    ]);
    $screen->seats()->create([
        'row_label' => 'A',
        'seat_number' => 1,
        'label' => 'A1',
        'type' => 'standard',
        'is_disabled' => false,
    ]);
    $movieData = adminCatalogMovieData(['slug' => 'night-film']);
    unset($movieData['genre_ids']);
    $movie = Movie::query()->create($movieData);
    $this->actingAs($admin);
    $startsAt = now()->addDays(3)->setTime(18, 0)->format('Y-m-d\TH:i');

    $this->post(route('admin.showtimes.store'), adminCatalogShowtimeData($movie->id, $screen->id, 'invalid'))
        ->assertSessionHasErrors('starts_at');
    $this->post(route('admin.showtimes.store'), adminCatalogShowtimeData($movie->id, $screen->id, $startsAt))
        ->assertRedirect();
    $showtime = Showtime::query()->firstOrFail();
    expect($showtime->status)->toBe(ShowtimeStatus::Scheduled)
        ->and((int) $showtime->base_price_cents)->toBe(1800);
    $this->get(route('admin.showtimes.show', $showtime))->assertOk();
    $this->get(route('admin.showtimes.edit', $showtime))->assertOk();

    $this->post(route('admin.showtimes.store'), adminCatalogShowtimeData($movie->id, $screen->id, $startsAt))
        ->assertSessionHasErrors('starts_at');

    $updatedStart = now()->addDays(4)->setTime(19, 0)->format('Y-m-d\TH:i');
    $this->put(route('admin.showtimes.update', $showtime), adminCatalogShowtimeData(
        $movie->id,
        $screen->id,
        $updatedStart,
        ['base_price_cents' => 2200],
    ))->assertRedirect();
    expect((int) $showtime->fresh()->base_price_cents)->toBe(2200);

    $this->post(route('admin.showtimes.cancel', $showtime), ['reason' => 'Maintenance'])->assertRedirect();
    expect($showtime->fresh()->status)->toBe(ShowtimeStatus::Cancelled);
    $this->delete(route('admin.movies.destroy', $movie))->assertSessionHasErrors('movie');
});
