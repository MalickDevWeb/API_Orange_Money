<?php

namespace App\Enums;

enum ResponseMessage: string
{
    case SUCCESS = 'Opération réussie';
    case CREATED = 'Ressource créée avec succès';
    case BAD_REQUEST = 'Requête invalide';
    case UNAUTHORIZED = 'Non autorisé';
    case FORBIDDEN = 'Accès interdit';
    case NOT_FOUND = 'Ressource non trouvée';
    case METHOD_NOT_ALLOWED = 'Méthode non autorisée';
    case CONFLICT = 'Conflit de données';
    case UNPROCESSABLE_ENTITY = 'Données non traitables';
    case INTERNAL_SERVER_ERROR = 'Erreur interne du serveur';
    case COMPTE_NOT_FOUND = 'Compte non trouvé';
    case INSUFFICIENT_BALANCE = 'Solde insuffisant';
    case COMPTE_BLOCKED = 'Compte bloqué';
    case TRANSACTION_FAILED = 'Transaction échouée';
}
