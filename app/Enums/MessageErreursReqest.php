<?php

namespace App\Enums;

enum MessageErreursReqest: string
{
    case NUMERO_COMPTE_REQUIRED = 'Le numéro de compte est obligatoire.';
    case NUMERO_COMPTE_STRING = 'Le numéro de compte doit être une chaîne de caractères.';
    case NUMERO_COMPTE_UNIQUE = 'Le numéro de compte existe déjà.';
    case TYPE_COMPTE_REQUIRED = 'Le type de compte est obligatoire.';
    case TYPE_COMPTE_STRING = 'Le type de compte doit être une chaîne de caractères.';
    case SOLDE_INITIAL_REQUIRED = 'Le solde initial est obligatoire.';
    case SOLDE_INITIAL_NUMERIC = 'Le solde initial doit être un nombre.';
    case SOLDE_INITIAL_MIN = 'Le solde initial doit être au moins 0.';
    case CLIENT_ID_REQUIRED = 'L\'identifiant du client est obligatoire.';
    case CLIENT_ID_EXISTS = 'L\'identifiant du client doit exister dans la table des clients.';
}
