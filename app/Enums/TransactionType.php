<?php

namespace App\Enums;

enum TransactionType: string
{
    case DEPOT = 'depot';
    case RETRAIT = 'retrait';
    case TRANSFERT = 'transfert';
    case PAIEMENT = 'paiement';
    case ACHAT_VIRTUEL = 'achat_virtuel';
}
