<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use App\Models\StripeEvent;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class StripeEventController extends Controller
{
    public function index(Request $request): View
    {
        $filters = $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
            'type' => ['nullable', 'string', 'max:100'],
            'status' => ['nullable', Rule::in(['received', 'processed', 'failed'])],
            'sort' => ['nullable', Rule::in(['created_at', 'type', 'status'])],
            'direction' => ['nullable', Rule::in(['asc', 'desc'])],
        ]);
        $events = StripeEvent::query()
            ->when($filters['q'] ?? null, fn ($query, $search) => $query->where(fn ($nested) => $nested
                ->where('stripe_event_id', 'like', '%'.$search.'%')
                ->orWhere('type', 'like', '%'.$search.'%')
                ->orWhere('error_message', 'like', '%'.$search.'%')))
            ->when($filters['type'] ?? null, fn ($query, $type) => $query->where('type', $type))
            ->when($filters['status'] ?? null, fn ($query, $status) => $query->where('status', $status))
            ->orderBy($filters['sort'] ?? 'created_at', $filters['direction'] ?? 'desc')
            ->paginate(15)->withQueryString();

        return view('admin.stripe-events.index', [
            'events' => $events,
            'types' => StripeEvent::query()->distinct()->orderBy('type')->pluck('type'),
            'timezone' => $this->timezone(),
        ]);
    }

    public function show(StripeEvent $event): View
    {
        return view('admin.stripe-events.show', ['event' => $event, 'timezone' => $this->timezone()]);
    }

    private function timezone(): string
    {
        $timezone = (string) Setting::query()->whereKey('timezone')->value('value');

        return in_array($timezone, timezone_identifiers_list(), true) ? $timezone : config('app.timezone', 'UTC');
    }
}
