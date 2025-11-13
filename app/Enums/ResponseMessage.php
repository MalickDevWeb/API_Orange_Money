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
    case USER_REGISTERED = 'Utilisateur enregistré avec succès';
    case OTP_SENT = 'OTP envoyé';
    case LOGIN_SUCCESS = 'Connexion réussie';
    case LOGOUT_SUCCESS = 'Déconnexion réussie';
    case USER_RETRIEVED = 'Utilisateur récupéré avec succès';
    case PENDING_USERS_RETRIEVED = 'Utilisateurs en attente récupérés';
    case USER_APPROVED = 'Utilisateur approuvé avec succès';
    case USER_ALREADY_APPROVED = 'Utilisateur déjà approuvé';
    case USER_REJECTED = 'Utilisateur rejeté';
    case PENDING_BALANCE_REQUESTS_RETRIEVED = 'Demandes de solde en attente récupérées';
    case BALANCE_REQUEST_APPROVED = 'Demande de solde approuvée';
    case BALANCE_REQUEST_REJECTED = 'Demande de solde rejetée';
    case DEPOSIT_SUCCESS = 'Dépôt effectué avec succès';
    case BALANCE_REQUEST_CREATED = 'Demande de solde créée avec succès';
    case PROFILE_UPDATED = 'Profil mis à jour avec succès';
}
