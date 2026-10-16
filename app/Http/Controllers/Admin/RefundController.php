<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Refund;
use App\Support\AdminTablePage;
use Illuminate\Http\Request;
use Illuminate\View\View;

class RefundController extends Controller
{
    public function index(Request $request): View
    {
        return AdminTablePage::render(
            $request,
            'Refunds',
            'Review refund requests and their processing status.',
            'Sales',
            'undo-2',
            Refund::query()->with(['booking', 'initiator']),
            ['booking.reference', 'reason'],
            ['created_at', 'status', 'amount_cents'],
            [
                ['label' => 'Booking', 'key' => 'booking.reference'],
                ['label' => 'Amount', 'key' => 'amount_cents', 'type' => 'money'],
                ['label' => 'Reason', 'key' => 'reason'],
                ['label' => 'Status', 'key' => 'status', 'type' => 'status'],
                ['label' => 'Processed', 'key' => 'processed_at'],
            ],
            [
                ['name' => 'status', 'label' => 'status', 'options' => [
                    'pending' => 'Pending',
                    'processing' => 'Processing',
                    'succeeded' => 'Succeeded',
                    'failed' => 'Failed',
                ]],
            ],
            ['status' => 'status'],
        );
    }
}
