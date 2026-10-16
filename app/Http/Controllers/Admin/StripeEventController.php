<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\StripeEvent;
use App\Support\AdminTablePage;
use Illuminate\Http\Request;
use Illuminate\View\View;

class StripeEventController extends Controller
{
    public function index(Request $request): View
    {
        return AdminTablePage::render(
            $request,
            'Stripe Events',
            'Read-only log of webhook events received from Stripe.',
            'System',
            'webhook',
            StripeEvent::query(),
            ['stripe_event_id', 'type', 'error_message'],
            ['created_at', 'type', 'status'],
            [
                ['label' => 'Event ID', 'key' => 'stripe_event_id'],
                ['label' => 'Type', 'key' => 'type'],
                ['label' => 'Status', 'key' => 'status', 'type' => 'status'],
                ['label' => 'Processed', 'key' => 'processed_at'],
                ['label' => 'Error', 'key' => 'error_message'],
            ],
            [
                ['name' => 'status', 'label' => 'status', 'options' => [
                    'received' => 'Received',
                    'processed' => 'Processed',
                    'failed' => 'Failed',
                ]],
            ],
            ['status' => 'status'],
        );
    }
}
