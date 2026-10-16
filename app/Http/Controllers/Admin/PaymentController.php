<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Support\AdminTablePage;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PaymentController extends Controller
{
    public function index(Request $request): View
    {
        return AdminTablePage::render(
            $request,
            'Payments',
            'Review payment activity for customer bookings.',
            'Sales',
            'credit-card',
            Payment::query()->with('booking'),
            ['booking.reference', 'gateway_payment_intent_id'],
            ['created_at', 'status', 'amount_cents'],
            [
                ['label' => 'Booking', 'key' => 'booking.reference'],
                ['label' => 'Gateway', 'key' => 'gateway'],
                ['label' => 'Amount', 'key' => 'amount_cents', 'type' => 'money'],
                ['label' => 'Status', 'key' => 'status', 'type' => 'status'],
                ['label' => 'Paid at', 'key' => 'paid_at'],
            ],
            [
                ['name' => 'status', 'label' => 'status', 'options' => [
                    'pending' => 'Pending',
                    'succeeded' => 'Succeeded',
                    'failed' => 'Failed',
                ]],
            ],
            ['status' => 'status'],
        );
    }
}
