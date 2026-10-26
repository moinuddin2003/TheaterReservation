<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\View\View;

class PaymentController extends Controller
{
    public function index(): View
    {
        return view('admin.pages.placeholder', [
            'pageTitle' => 'Payments',
            'pageSubtitle' => 'Review payment activity for customer bookings.',
            'breadcrumb' => 'Sales',
            'icon' => 'credit-card',
        ]);
    }
}
