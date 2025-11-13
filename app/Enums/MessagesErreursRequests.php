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
    case TYPE_ENUM = 'Le type d\'utilisateur doit être : admin, client, commercant ou fournisseur.';
    case EMAIL_EXISTS = 'L\'email est déjà utilisé.';
    case TELEPHONE_EXISTS = 'Le numéro de téléphone est déjà utilisé.';
    case OTP_INVALID = 'Code OTP invalide ou expiré';
    case OTP_EXCEEDED_ATTEMPTS = 'Trop de tentatives, veuillez réessayer plus tard';
    case ACCOUNT_PENDING_APPROVAL_COMMERCHANT = 'Votre compte commerçant est en attente d\'approbation par l\'administrateur.';
    case ACCOUNT_PENDING_APPROVAL_SUPPLIER = 'Votre compte fournisseur est en attente d\'approbation par l\'administrateur.';
    case UNAUTHORIZED_ACCESS = 'Accès non autorisé';
    case USER_NOT_FOUND = 'Utilisateur non trouvé';
    case APPROVABLE_TYPE_ERROR = 'Type d\'utilisateur non approuvable';
    case REJECTABLE_TYPE_ERROR = 'Type d\'utilisateur non rejetable';
    case USER_ALREADY_PROCESSED = 'Utilisateur déjà traité';
    case BALANCE_REQUEST_NOT_FOUND = 'Demande non trouvée';
    case CLIENT_NOT_FOUND = 'Client non trouvé';
    case ACCOUNT_NOT_FOUND_FOR_CLIENT = 'Aucun compte trouvé pour ce client';
    case SUPPLIER_ACCESS_DENIED = 'Accès réservé aux fournisseurs approuvés';
    case PIN_REQUIRED = 'Veuillez saisir votre code PIN';
    case PIN_INCORRECT = 'Code PIN incorrect';
    case LOGIN_SUCCESS_WITH_PIN = 'Connexion réussie avec PIN';

    // Validation Rules
    case VALIDATION_MOTIF_REJET = 'motif_rejet';
    case VALIDATION_MOTIF_REJET_RULES = 'required|string|max:255';
    case VALIDATION_MONTANT = 'montant';
    case VALIDATION_MONTANT_RULES = 'required|numeric|min:1000';
    case VALIDATION_TELEPHONE = 'telephone';
    case VALIDATION_TELEPHONE_RULES = 'required|string';
    case VALIDATION_NOTE = 'note';
    case VALIDATION_NOTE_RULES = 'nullable|string';
}
