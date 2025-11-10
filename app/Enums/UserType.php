<?php

namespace App\Enums;

enum UserType: string
{
    case ADMIN = 'admin';
    case CLIENT = 'client';
    case COMMERCANT = 'commercant';
}
