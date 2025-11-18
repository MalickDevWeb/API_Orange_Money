<?php

namespace App\Enums;

enum TransactionLimits: string
{
    case MIN_AMOUNT = '0.01';
    case MAX_AMOUNT = '10000000';
    case MAX_NOTE_LENGTH = '1000';
    case MAX_REFERENCE_LENGTH = '255';
}
