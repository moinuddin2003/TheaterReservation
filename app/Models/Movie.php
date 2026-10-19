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
use Illuminate\Support\Str;

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

    public function posterUrl(): ?string
    {
        if (! filled($this->poster_path)) {
            return null;
        }

        if (Str::startsWith($this->poster_path, ['http://', 'https://'])) {
            return $this->poster_path;
        }

        return Storage::disk('public')->url($this->poster_path);
    }
}
