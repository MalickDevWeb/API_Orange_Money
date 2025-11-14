<?php

namespace App\Enums;

enum UserStatus: string
{
    case ACTIF = 'actif';
    case INACTIF = 'inactif';
    case EN_ATTENTE = 'en_attente';
    case SUSPENDU = 'suspendu';
}
