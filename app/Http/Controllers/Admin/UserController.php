<?php

namespace App\Http\Controllers\Admin;

use App\Enums\Role;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\AdminTablePage;
use Illuminate\Http\Request;
use Illuminate\View\View;

class UserController extends Controller
{
    public function index(Request $request): View
    {
        return AdminTablePage::render(
            $request,
            'Users',
            'View customer and staff accounts.',
            'People',
            'users',
            User::query()->withCount('bookings'),
            ['name', 'email'],
            ['name', 'email', 'role', 'created_at'],
            [
                ['label' => 'Name', 'key' => 'name'],
                ['label' => 'Email', 'key' => 'email'],
                ['label' => 'Role', 'key' => 'role', 'type' => 'status'],
                ['label' => 'Bookings', 'key' => 'bookings_count'],
                ['label' => 'Joined', 'key' => 'created_at'],
            ],
            [
                ['name' => 'role', 'label' => 'role', 'options' => [
                    Role::Customer->value => 'Customer',
                    Role::Staff->value => 'Staff',
                    Role::Admin->value => 'Admin',
                ]],
            ],
            ['role' => 'role'],
        );
    }
}
