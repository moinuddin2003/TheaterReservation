<?php

namespace App\Enums;

enum MovieStatus: string
{
    case ComingSoon = 'coming_soon';
    case NowShowing = 'now_showing';
    case Archived = 'archived';
}
