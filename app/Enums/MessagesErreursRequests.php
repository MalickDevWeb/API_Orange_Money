<?php

namespace App\Enums;

enum MessagesErreursRequests: string
{
    case NOM_REQUIRED = 'Le nom est obligatoire.';
    case NOM_STRING = 'Le nom doit être une chaîne de caractères.';
    case PRENOM_REQUIRED = 'Le prénom est obligatoire.';
    case PRENOM_STRING = 'Le prénom doit être une chaîne de caractères.';
    case TELEPHONE_REQUIRED = 'Le numéro de téléphone est obligatoire.';
    case TELEPHONE_UNIQUE = 'Ce numéro de téléphone existe déjà.';
    case EMAIL_REQUIRED = 'L\'email est obligatoire.';
    case EMAIL_EMAIL = 'L\'email doit être valide.';
    case EMAIL_UNIQUE = 'Cet email existe déjà.';
    case PASSWORD_REQUIRED = 'Le mot de passe est obligatoire.';
    case PASSWORD_STRING = 'Le mot de passe doit être une chaîne de caractères.';
    case PASSWORD_MIN = 'Le mot de passe doit contenir au moins 6 caractères.';
    case TYPE_REQUIRED = 'Le type d\'utilisateur est obligatoire.';
    case TYPE_ENUM = 'Le type d\'utilisateur doit être : admin, client ou commercant.';
}
