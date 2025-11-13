<?php

namespace App\Enums;

enum BalanceRequestStatus: string
{
    case EN_ATTENTE = 'en_attente';
    case APPROUVEE = 'approuvee';
    case REJETEE = 'rejetee';
}
