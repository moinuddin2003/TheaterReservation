<?php

use App\Enums\MovieStatus;
use App\Enums\Role;
use App\Models\Genre;
use App\Models\Movie;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

test('an administrator can view catalog movies and their genres', function () {
    $admin = User::factory()->create(['role' => Role::Admin]);
    $genre = Genre::factory()->create(['name' => 'Science Fiction', 'slug' => 'science-fiction']);
    $movie = Movie::factory()->create([
        'title' => 'Orbit Seven',
        'status' => MovieStatus::NowShowing,
    ]);
    $movie->genres()->attach($genre);

    $this->actingAs($admin)
        ->get(route('admin.movies.index'))
        ->assertOk()
        ->assertSee('Orbit Seven')
        ->assertSee('Science Fiction')
        ->assertSee('Now Showing');

    $this->get(route('admin.genres.index'))
        ->assertOk()
        ->assertSee('Science Fiction')
        ->assertSee('science-fiction')
        ->assertSee('1');
});

test('an administrator can create, edit, and delete genres', function () {
    $admin = User::factory()->create(['role' => Role::Admin]);

    $this->actingAs($admin)
        ->get(route('admin.genres.index'))
        ->assertOk()
        ->assertSee('Create genre')
        ->assertSee('Add genre');

    $this->post(route('admin.genres.store'), [
        'name' => 'Science Fiction',
        'slug' => '',
    ])->assertRedirect(route('admin.genres.index'))
        ->assertSessionHas('status');

    $genre = Genre::query()->where('name', 'Science Fiction')->firstOrFail();
    expect($genre->slug)->toBe('science-fiction');

    $this->get(route('admin.genres.edit', $genre))
        ->assertRedirect(route('admin.genres.index', ['edit' => $genre->id]));

    $this->get(route('admin.genres.index', ['edit' => $genre->id]))
        ->assertOk()
        ->assertSee('Edit genre')
        ->assertSee('Science Fiction');

    $this->put(route('admin.genres.update', $genre), [
        'name' => 'Sci-Fi',
        'slug' => '',
    ])->assertRedirect(route('admin.genres.index'))
        ->assertSessionHas('status');

    expect($genre->fresh()->slug)->toBe('sci-fi');

    $this->get(route('admin.genres.index', ['delete' => $genre->id]))
        ->assertOk()
        ->assertSee('Delete confirmation')
        ->assertSee('Confirm deletion');

    $this->delete(route('admin.genres.destroy', $genre))
        ->assertRedirect(route('admin.genres.index'))
        ->assertSessionHas('status');

    $this->assertDatabaseMissing('genres', ['id' => $genre->id]);
});

test('a genre assigned to movies cannot be deleted and shows the affected movies', function () {
    $admin = User::factory()->create(['role' => Role::Admin]);
    $genre = Genre::factory()->create(['name' => 'Action']);
    $movie = Movie::factory()->create(['title' => 'The Last Horizon']);
    $movie->genres()->attach($genre);

    $this->actingAs($admin)
        ->get(route('admin.genres.index', ['delete' => $genre->id]))
        ->assertOk()
        ->assertSee('Delete confirmation · In use')
        ->assertSee('The Last Horizon')
        ->assertSee('Keep genre')
        ->assertDontSee('Confirm deletion');

    $this->delete(route('admin.genres.destroy', $genre))
        ->assertRedirect(route('admin.genres.index', ['delete' => $genre->id]))
        ->assertSessionHas('error');

    $this->assertDatabaseHas('genres', ['id' => $genre->id]);
});

test('the movie catalog search filter narrows the results', function () {
    $admin = User::factory()->create(['role' => Role::Admin]);
    Movie::factory()->create(['title' => 'Orbit Seven']);
    Movie::factory()->create(['title' => 'Quiet Harbor']);

    $this->actingAs($admin)
        ->get(route('admin.movies.index', ['search' => 'Orbit']))
        ->assertOk()
        ->assertSee('Orbit Seven')
        ->assertDontSee('Quiet Harbor');
});

test('the movie library shows ten movies per page and avoids zero-addition copy', function () {
    $admin = User::factory()->create(['role' => Role::Admin]);
    Movie::factory()->count(11)->create();

    $response = $this->actingAs($admin)->get(route('admin.movies.index'));

    $response->assertOk()
        ->assertSee('No new movies this week')
        ->assertSee('Showing 1–10 of 11 movies')
        ->assertDontSee('0 addition this week');
    $response->assertDontSee('Showing 1 to 10 of 11 results');
    $response->assertViewHas('movies', fn ($movies) => $movies->count() === 10 && $movies->total() === 11);

    $secondPage = $this->get(route('admin.movies.index', ['page' => 2]));
    $secondPage->assertOk()
        ->assertViewHas('movies', fn ($movies) => $movies->count() === 1 && $movies->total() === 11);
});

test('seeded public movie posters resolve to their public image URLs', function () {
    $posterPath = '/images/movies/interstellar-poster.jpg';
    $movie = Movie::factory()->create([
        'title' => 'Seeded Poster Test',
        'poster_path' => $posterPath,
    ]);

    expect(file_exists(public_path(ltrim($posterPath, '/'))))->toBeTrue()
        ->and($movie->posterUrl())->toBe(asset(ltrim($posterPath, '/')));

    $admin = User::factory()->create(['role' => Role::Admin]);
    $this->actingAs($admin)
        ->get(route('admin.movies.index'))
        ->assertOk()
        ->assertSee('src="'.asset(ltrim($posterPath, '/')).'"', false);
});

test('an administrator can filter movies by genre and status and switch to grid view', function () {
    $admin = User::factory()->create(['role' => Role::Admin]);
    $scienceFiction = Genre::factory()->create(['name' => 'Science Fiction', 'slug' => 'science-fiction']);
    $drama = Genre::factory()->create(['name' => 'Drama', 'slug' => 'drama']);
    $matchingMovie = Movie::factory()->create([
        'title' => 'Orbit Seven',
        'status' => MovieStatus::ComingSoon,
    ]);
    $matchingMovie->genres()->attach($scienceFiction);

    $otherMovie = Movie::factory()->create([
        'title' => 'Quiet Harbor',
        'status' => MovieStatus::ComingSoon,
    ]);
    $otherMovie->genres()->attach($drama);

    $this->actingAs($admin)
        ->get(route('admin.movies.index', [
            'genre' => $scienceFiction->id,
            'status' => MovieStatus::ComingSoon->value,
            'view' => 'grid',
        ]))
        ->assertOk()
        ->assertSee('Orbit Seven')
        ->assertSee('Science Fiction')
        ->assertDontSee('Quiet Harbor')
        ->assertSee('aria-label="Grid view"', false);
});

test('an administrator can create and edit a movie stored in the database', function () {
    $admin = User::factory()->create(['role' => Role::Admin]);
    $genre = Genre::factory()->create();
    $movieData = [
        'title' => 'Orbit Seven',
        'synopsis' => 'A crew travels beyond the edge of known space.',
        'duration_minutes' => 124,
        'rating' => 'PG-13',
        'release_date' => '2026-10-19',
        'poster_path' => 'https://example.com/orbit-seven.jpg',
        'trailer_url' => 'https://example.com/orbit-seven-trailer',
        'status' => MovieStatus::ComingSoon->value,
        'genres' => [$genre->id],
    ];

    $this->actingAs($admin)
        ->get(route('admin.movies.create'))
        ->assertOk()
        ->assertSee('Add movie')
        ->assertSee('Back to movies')
        ->assertSee('type="file"', false);

    $this->post(route('admin.movies.store'), $movieData)
        ->assertRedirect(route('admin.movies.index'))
        ->assertSessionHas('status');

    $movie = Movie::query()->where('title', 'Orbit Seven')->firstOrFail();
    expect($movie->slug)->toBe('orbit-seven')
        ->and($movie->synopsis)->toBe($movieData['synopsis'])
        ->and($movie->genres->modelKeys())->toBe([$genre->id]);

    $this->get(route('admin.movies.edit', $movie))
        ->assertOk()
        ->assertSee('Edit movie')
        ->assertSee('Orbit Seven')
        ->assertSee('Back to movies');

    $this->put(route('admin.movies.update', $movie), [
        ...$movieData,
        'title' => 'Orbit Seven: New Mission',
        'slug' => 'orbit-seven-new-mission',
        'status' => MovieStatus::NowShowing->value,
    ])->assertRedirect(route('admin.movies.index'));

    expect($movie->fresh()->title)->toBe('Orbit Seven: New Mission')
        ->and($movie->fresh()->status)->toBe(MovieStatus::NowShowing)
        ->and($movie->fresh()->genres->modelKeys())->toBe([$genre->id]);
});

test('an administrator can upload a movie poster when creating and editing a movie', function () {
    Storage::fake('public');

    $admin = User::factory()->create(['role' => Role::Admin]);
    $movieData = [
        'title' => 'Poster Upload Test',
        'synopsis' => 'A movie with an uploaded poster.',
        'duration_minutes' => 100,
        'status' => MovieStatus::ComingSoon->value,
        'poster' => UploadedFile::fake()->create('poster.jpg', 100, 'image/jpeg'),
    ];

    $this->actingAs($admin)
        ->post(route('admin.movies.store'), $movieData)
        ->assertRedirect(route('admin.movies.index'));

    $movie = Movie::query()->where('title', 'Poster Upload Test')->firstOrFail();
    expect($movie->poster_path)->toStartWith('movies/');
    Storage::disk('public')->assertExists($movie->poster_path);

    $oldPosterPath = $movie->poster_path;
    $this->put(route('admin.movies.update', $movie), [
        ...$movieData,
        'poster' => UploadedFile::fake()->create('new-poster.png', 100, 'image/png'),
    ])->assertRedirect(route('admin.movies.index'));

    $movie->refresh();
    expect($movie->poster_path)->not->toBe($oldPosterPath);
    Storage::disk('public')->assertMissing($oldPosterPath);
    Storage::disk('public')->assertExists($movie->poster_path);

    $this->actingAs($admin)
        ->get(route('admin.movies.index', ['view' => 'grid']))
        ->assertOk()
        ->assertSee('More actions for Poster Upload Test')
        ->assertSee('Delete movie')
        ->assertSee($movie->posterUrl());
});

test('movie edit and delete actions are available in list and grid views', function () {
    $admin = User::factory()->create(['role' => Role::Admin]);
    $movie = Movie::factory()->create(['title' => 'Action Menu Movie']);

    $this->actingAs($admin)
        ->get(route('admin.movies.index'))
        ->assertOk()
        ->assertSee('More actions for Action Menu Movie')
        ->assertSee('Edit movie')
        ->assertSee('Delete movie')
        ->assertSee('popover="auto"', false)
        ->assertSee('role="alertdialog"', false)
        ->assertSee('Confirm delete')
        ->assertDontSee('onsubmit="return confirm', false)
        ->assertDontSee('> Edit </button>', false);

    $this->get(route('admin.movies.index', ['view' => 'grid']))
        ->assertOk()
        ->assertSee('More actions for Action Menu Movie')
        ->assertSee('Edit movie')
        ->assertSee('Delete movie')
        ->assertSee('popover="auto"', false)
        ->assertDontSee('> Edit </button>', false);

    $this->delete(route('admin.movies.destroy', $movie))
        ->assertRedirect(route('admin.movies.index'))
        ->assertSessionHas('status');

    $this->assertDatabaseMissing('movies', ['id' => $movie->id]);
});
