<?php

namespace App\Enums;

enum Role: string
{
    case Customer = 'customer';
    case Staff = 'staff';
    case Admin = 'admin';
}
