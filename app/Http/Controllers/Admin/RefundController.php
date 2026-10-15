<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\View\View;

class RefundController extends Controller
{
    public function index(): View
    {
        return view('admin.pages.placeholder', [
            'pageTitle' => 'Refunds',
            'pageSubtitle' => 'Review refund requests and their processing status.',
            'breadcrumb' => 'Sales',
            'icon' => 'undo-2',
        ]);
    }
}
