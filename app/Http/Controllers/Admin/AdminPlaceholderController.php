<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\View\View;

class AdminPlaceholderController extends Controller
{
    public function staffAdmins(): View
    {
        return $this->placeholder(
            'Staff and admins',
            'Manage staff and administrator accounts.',
            'People',
            'users',
        );
    }

    public function reports(): View
    {
        return $this->placeholder(
            'Reports',
            'Review operational and sales reports.',
            'Insights',
            'chart-no-axes-column-increasing',
        );
    }

    public function failedJobs(): View
    {
        return $this->placeholder(
            'Failed jobs',
            'Review queued tasks that did not complete successfully.',
            'System',
            'alert-triangle',
        );
    }

    private function placeholder(string $title, string $subtitle, string $breadcrumb, string $icon): View
    {
        return view('admin.pages.placeholder', [
            'pageTitle' => $title,
            'pageSubtitle' => $subtitle,
            'breadcrumb' => $breadcrumb,
            'icon' => $icon,
            'emptyText' => 'This section is coming soon.',
        ]);
    }
}
