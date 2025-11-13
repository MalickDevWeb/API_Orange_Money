<?php

namespace App\Enums;

enum TransactionStatus: string
{
    case REUSSIE = 'reussie';
    case ECHOUEE = 'echouee';
    case ANNULEE = 'annulee';
}
