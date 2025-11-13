# Guide d'Authentification - API Orange Money

## Vue d'ensemble

Le système d'authentification implémente une approche à **trois niveaux de sécurité** :

1. **Inscription** avec vérification des données
2. **Authentification OTP** (première connexion)
3. **Authentification PIN** (connexions suivantes)

## Types d'utilisateurs

- **ADMIN** : Accès complet au système
- **CLIENT** : Utilisateurs standards
- **COMMERCANT** : Commerçants (nécessitent approbation)
- **FOURNISSEUR** : Fournisseurs (nécessitent approbation)

## Scénario d'authentification détaillé

### 1. Inscription (Registration)

**Endpoint :** `POST /api/register`

**Corps de la requête :**
```json
{
  "nom": "Dupont",
  "prenom": "Jean",
  "telephone": "705334611",
  "email": "jean.dupont@example.com",
  "password": "password123",
  "type": "client"
}
```

**Réponse de succès :**
```json
{
  "status": "success",
  "message": "Utilisateur enregistré avec succès",
  "data": {
    "id": "uuid-user",
    "nom": "Dupont",
    "prenom": "Jean",
    "telephone": "705334611",
    "email": "jean.dupont@example.com",
    "type": "client",
    "statut": "actif"
  }
}
```

**Notes importantes :**
- Les commerçants et fournisseurs sont automatiquement mis en statut `en_attente`
- Les clients et admins sont directement `actif`
- Unicité vérifiée pour email et téléphone

### 2. Première connexion - Authentification OTP

**Endpoint :** `POST /api/login`

**Corps de la requête :**
```json
{
  "telephone": "705334611"
}
```

**Réponse - OTP envoyé :**
```json
{
  "status": "success",
  "message": "OTP envoyé",
  "data": {
    "requires_otp": true,
    "phone_number": "705334611",
    "otp_sent": true,
    "otp_code": "123456"  // Uniquement en développement
  }
}
```

**Vérification OTP :**
**Endpoint :** `POST /api/login/otp`

**Corps de la requête :**
```json
{
  "telephone": "705334611",
  "otp_code": "123456"
}
```

**Réponse de succès :**
```json
{
  "status": "success",
  "message": "Connexion réussie",
  "data": {
    "access_token": "bearer_token_here",
    "token_type": "Bearer",
    "user": {
      "id": "uuid-user",
      "nom": "Dupont",
      "prenom": "Jean",
      "telephone": "705334611",
      "email": "jean.dupont@example.com",
      "type": "client",
      "pin": "1234"  
    }
  }
}
```

**Actions lors de la vérification OTP :**
- ✅ Génération automatique d'un PIN à 4 chiffres
- ✅ Activation du compte utilisateur
- ✅ Création du token d'accès
- ✅ Vérification du statut pour commerçants/fournisseurs

### 3. Connexions suivantes - Authentification PIN

**Endpoint :** `POST /api/login`

**Corps de la requête :**
```json
{
  "telephone": "705334611",
  "pin": "1234"
}
```

**Réponse - PIN requis :**
```json
{
  "status": "success",
  "message": "Veuillez saisir votre code PIN",
  "data": {
    "requires_pin": true,
    "phone_number": "705334611"
  }
}
```

**Réponse - Connexion réussie :**
```json
{
  "status": "success",
  "message": "Connexion réussie avec PIN",
  "data": {
    "access_token": "bearer_token_here",
    "token_type": "Bearer",
    "user": {
      "id": "uuid-user",
      "nom": "Dupont",
      "prenom": "Jean",
      "telephone": "705334611",
      "email": "jean.dupont@example.com",
      "type": "client"
    }
  }
}
```

## Gestion des comptes par l'administrateur

### Approbation des comptes

**Récupérer les comptes en attente :**
`GET /api/admin/users/pending`

**Approuver un compte :**
`POST /api/admin/users/{id}/approve`

**Rejeter un compte :**
`POST /api/admin/users/{id}/reject`
```json
{
  "motif_rejet": "Documents insuffisants"
}
```

### Gestion des demandes de solde

**Récupérer les demandes en attente :**
`GET /api/admin/balance-requests/pending`

**Approuver une demande :**
`POST /api/admin/balance-requests/{id}/approve`

**Rejeter une demande :**
`POST /api/admin/balance-requests/{id}/reject`
```json
{
  "motif_rejet": "Montant trop élevé"
}
```

### Dépôt pour les clients

**Endpoint :** `POST /api/admin/deposit`

**Corps de la requête :**
```json
{
  "telephone": "705334611",
  "montant": 50000,
  "note": "Dépôt client"
}
```

## Gestion du profil utilisateur

### Mise à jour du profil

**Endpoint :** `PUT /api/profile`

**Corps de la requête :**
```json
{
  "nom": "Dupont",
  "prenom": "Jean",
  "email": "jean.dupont@example.com",
  "current_pin": "1234",
  "new_pin": "5678"
}
```

**Réponse de succès :**
```json
{
  "status": "success",
  "message": "Profil mis à jour avec succès",
  "data": {
    "id": "uuid-user",
    "nom": "Dupont",
    "prenom": "Jean",
    "telephone": "705334611",
    "email": "jean.dupont@example.com",
    "type": "client",
    "pin": "5678"
  }
}
```

**Notes importantes :**
- Le PIN actuel est requis pour changer le PIN
- Seuls nom, prénom, email et PIN peuvent être modifiés
- Le téléphone, type et statut ne peuvent pas être changés
- Validation stricte du format PIN (4 chiffres)

## Flux complet d'authentification

### Pour un CLIENT :
1. Inscription → Statut `actif`
2. Login → OTP envoyé
3. Vérification OTP → PIN généré, connexion
4. Prochaines connexions → PIN requis

### Pour un COMMERCANT/FOURNISSEUR :
1. Inscription → Statut `en_attente`
2. Login → OTP envoyé
3. Vérification OTP → PIN généré, MAIS connexion BLOQUÉE
4. **Attendre approbation admin**
5. Admin approuve → Statut `actif`
6. Maintenant connexion possible avec PIN

## Sécurité implémentée

- ✅ **OTP à 6 chiffres** avec expiration (5 minutes)
- ✅ **PIN à 4 chiffres** généré aléatoirement
- ✅ **Blocage des comptes** non approuvés
- ✅ **Limitation des tentatives** OTP (3 max)
- ✅ **Hashage des mots de passe**
- ✅ **Tokens JWT** pour l'authentification API
- ✅ **Middleware admin** pour les endpoints protégés

## Codes d'erreur courants

| Code | Message | Description |
|------|---------|-------------|
| 400 | Code OTP invalide ou expiré | OTP incorrect ou expiré |
| 400 | Code PIN incorrect | PIN fourni incorrect |
| 400 | Trop de tentatives | Limite d'essais OTP atteinte |
| 403 | Accès non autorisé | Utilisateur non admin |
| 403 | Votre compte commerçant est en attente... | Compte non approuvé |
| 404 | Utilisateur non trouvé | Téléphone non enregistré |

## Exemple de session complète

```bash
# 1. Inscription
curl -X POST http://localhost:8000/api/register \
  -H "Content-Type: application/json" \
  -d '{"nom":"Test","prenom":"User","telephone":"700000000","email":"test@example.com","password":"123456","type":"client"}'

# 2. Première connexion (OTP)
curl -X POST http://localhost:8000/api/login \
  -H "Content-Type: application/json" \
  -d '{"telephone":"700000000"}'

# 3. Vérification OTP
curl -X POST http://localhost:8000/api/login/otp \
  -H "Content-Type: application/json" \
  -d '{"telephone":"700000000","otp_code":"123456"}'

# 4. Connexions suivantes (PIN)
curl -X POST http://localhost:8000/api/login \
  -H "Content-Type: application/json" \
  -d '{"telephone":"700000000","pin":"1234"}'
```

## Architecture technique

- **Laravel Sanctum** pour l'authentification API
- **Enums** pour la centralisation des messages et types
- **Traits** pour la réutilisabilité du code
- **Middleware** pour la protection des routes
- **Validation** côté serveur avec règles personnalisées
- **Gestion d'erreurs** unifiée avec ApiResponseTrait

---

*Ce système assure une sécurité maximale tout en maintenant une expérience utilisateur fluide.*
