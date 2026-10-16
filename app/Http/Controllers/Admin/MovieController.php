<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Movie;
use App\Support\AdminTablePage;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MovieController extends Controller
{
    public function index(Request $request): View
    {
        return AdminTablePage::render(
            $request,
            'Movies',
            'Manage the films available in your cinema.',
            'Catalog',
            'clapperboard',
            Movie::query()->with('genres')->withCount('showtimes'),
            ['title', 'synopsis'],
            ['title', 'status', 'release_date', 'created_at'],
            [
                ['label' => 'Title', 'key' => 'title'],
                ['label' => 'Status', 'key' => 'status', 'type' => 'status'],
                ['label' => 'Release date', 'key' => 'release_date'],
                ['label' => 'Genres', 'key' => 'genres', 'property' => 'name'],
                ['label' => 'Showtimes', 'key' => 'showtimes_count'],
            ],
        );
    }
}
