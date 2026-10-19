<?php

namespace App\Models;

use App\Enums\MovieStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\RouteKey;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;

#[Fillable([
    'title',
    'slug',
    'synopsis',
    'duration_minutes',
    'rating',
    'release_date',
    'poster_path',
    'backdrop_path',
    'trailer_url',
])]
#[RouteKey('slug')]
class Movie extends Model
{
    use HasFactory;

    /**
     * @var array<string, mixed>
     */
    protected $attributes = [
        'status' => MovieStatus::ComingSoon->value,
    ];

    /**
     * @return BelongsToMany<Genre, $this>
     */
    public function genres(): BelongsToMany
    {
        return $this->belongsToMany(Genre::class);
    }

    /**
     * @return HasMany<Showtime, $this>
     */
    public function showtimes(): HasMany
    {
        return $this->hasMany(Showtime::class);
    }

    /**
     * @param  Builder<Movie>  $query
     * @return Builder<Movie>
     */
    public function scopeNowShowing(Builder $query): Builder
    {
        return $query->where('status', MovieStatus::NowShowing);
    }

    /**
     * @param  Builder<Movie>  $query
     * @return Builder<Movie>
     */
    public function scopeComingSoon(Builder $query): Builder
    {
        return $query->where('status', MovieStatus::ComingSoon);
    }

    /**
     * @param  Builder<Movie>  $query
     * @return Builder<Movie>
     */
    public function scopeArchived(Builder $query): Builder
    {
        return $query->where('status', MovieStatus::Archived);
    }

    /**
     * @param  Builder<Movie>  $query
     * @return Builder<Movie>
     */
    public function scopeSearch(Builder $query, ?string $term): Builder
    {
        return $query->when(
            filled($term),
            fn (Builder $query) => $query->where('title', 'like', '%'.$term.'%'),
        );
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'duration_minutes' => 'integer',
            'release_date' => 'date',
            'status' => MovieStatus::class,
        ];
    }

    public function isNowShowing(): bool
    {
        return $this->status === MovieStatus::NowShowing;
    }

    /**
     * Public URL for the movie's poster, or null when none is set.
     */
    public function posterUrl(): ?string
    {
        return $this->artworkUrl($this->poster_path);
    }

    /**
     * Public URL for the movie's wide backdrop, or null when none is set.
     */
    public function backdropUrl(): ?string
    {
        return $this->artworkUrl($this->backdrop_path);
    }

    /**
     * Resolve a stored artwork path to a public URL.
     *
     * Absolute URLs are returned as-is, leading-slash paths are treated as
     * files in `public/`, and everything else lives on the public disk.
     */
    private function artworkUrl(?string $path): ?string
    {
        if (blank($path)) {
            return null;
        }

        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
            return $path;
        }

        return str_starts_with($path, '/')
            ? asset($path)
            : Storage::disk('public')->url($path);
    }
}
