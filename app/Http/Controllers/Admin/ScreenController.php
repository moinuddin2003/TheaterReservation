<?php

namespace App\Http\Controllers\Admin;

use App\Actions\Admin\DeleteScreenAction;
use App\Actions\Admin\SaveScreenAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreScreenRequest;
use App\Models\Cinema;
use App\Models\Screen;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class ScreenController extends Controller
{
    public function create(): View
    {
        return view('admin.screens.form', [
            'screen' => new Screen,
            'cinemas' => Cinema::query()->orderBy('name')->get(),
        ]);
    }

    public function store(StoreScreenRequest $request, SaveScreenAction $action): RedirectResponse
    {
        $screen = $action->execute($request->validated());

        return to_route('admin.seats.index', $screen)->with('status', 'Screen and seats created.');
    }

    public function edit(Screen $screen): View
    {
        return view('admin.screens.form', [
            'screen' => $screen,
            'cinemas' => Cinema::query()->orderBy('name')->get(),
        ]);
    }

    public function update(StoreScreenRequest $request, Screen $screen, SaveScreenAction $action): RedirectResponse
    {
        $screen = $action->execute($request->validated(), $screen);

        return to_route('admin.seats.index', $screen)->with('status', 'Screen updated.');
    }

    public function destroy(Screen $screen, DeleteScreenAction $action): RedirectResponse
    {
        $action->execute($screen);

        return to_route('admin.cinema.index')->with('status', 'Screen deleted.');
    }
}
