<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\View\View;

class StripeEventController extends Controller
{
    public function index(): View
    {
        return view('admin.pages.placeholder', [
            'pageTitle' => 'Stripe Events',
            'pageSubtitle' => 'Read-only log of webhook events received from Stripe.',
            'breadcrumb' => 'System',
            'icon' => 'webhook',
        ]);
    }
}
