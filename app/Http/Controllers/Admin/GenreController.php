<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Genre;
use App\Support\AdminTablePage;
use Illuminate\Http\Request;
use Illuminate\View\View;

class GenreController extends Controller
{
    public function index(Request $request): View
    {
        return AdminTablePage::render(
            $request,
            'Genres',
            'Organize movies into browsable genres.',
            'Catalog',
            'tags',
            Genre::query()->withCount('movies'),
            ['name', 'slug'],
            ['name', 'slug', 'created_at'],
            [
                ['label' => 'Name', 'key' => 'name'],
                ['label' => 'Slug', 'key' => 'slug'],
                ['label' => 'Movies', 'key' => 'movies_count'],
                ['label' => 'Created', 'key' => 'created_at'],
            ],
        );
    }
}
