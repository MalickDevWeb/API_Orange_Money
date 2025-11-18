<?php

namespace App\Enums;

enum DefaultValues: int
{
    case PER_PAGE = 15;
    case MAX_PER_PAGE = 100;
    case MIN_PER_PAGE = 1;
    case OTP_SIZE = 6;
}
