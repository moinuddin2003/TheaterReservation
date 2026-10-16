<?php

namespace App\Enums;

enum ScanResult: string
{
    case Admitted = 'admitted';
    case AlreadyUsed = 'already_used';
    case WrongShowtime = 'wrong_showtime';
    case TooEarly = 'too_early';
    case Expired = 'expired';
    case Void = 'void';
    case Invalid = 'invalid';
}
