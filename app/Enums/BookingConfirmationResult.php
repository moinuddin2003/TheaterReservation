<?php

namespace App\Enums;

enum BookingConfirmationResult: string
{
    case Confirmed = 'confirmed';
    case AlreadyConfirmed = 'already_confirmed';
    case CannotConfirm = 'cannot_confirm';
}
