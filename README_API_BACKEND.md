# API Orange Money Backend - Documentation Complète

## 📋 Vue d'ensemble

Cette API Laravel fournit un système complet de gestion de comptes bancaires et de transactions pour Orange Money. Elle implémente une architecture SOLID avec Clean Architecture et supporte l'authentification Passport, les transactions sécurisées, et la gestion administrative.

## 🚀 Technologies Utilisées

- **Laravel 10/11** - Framework PHP
- **PostgreSQL** - Base de données
- **Laravel Passport** - Authentification OAuth2
- **Docker** - Conteneurisation
- **Swagger/OpenAPI** - Documentation API
- **Clean Architecture** - Architecture logicielle
- **SOLID Principles** - Bonnes pratiques OOP

## 🔧 Installation & Configuration

### Prérequis

```bash
PHP >= 8.1
Composer
PostgreSQL >= 13
Node.js & NPM (pour les assets)
Docker & Docker Compose (optionnel)
```

### Installation

```bash
# Cloner le repository
git clone <repository-url>
cd api_orange_money

# Installer les dépendances PHP
composer install

# Installer les dépendances Node.js
npm install

# Copier le fichier d'environnement
cp .env.example .env

# Générer la clé d'application
php artisan key:generate

# Configurer la base de données dans .env
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=orange_money_db
DB_USERNAME=your_username
DB_PASSWORD=your_password

# Exécuter les migrations
php artisan migrate

# Exécuter les seeders (optionnel)
php artisan db:seed

# Installer Passport
php artisan passport:install

# Générer la documentation Swagger
php artisan l5-swagger:generate
```

### Configuration Docker (Optionnel)

```bash
# Construire et démarrer les conteneurs
docker-compose up -d --build

# Exécuter les migrations dans le conteneur
docker-compose exec app php artisan migrate
docker-compose exec app php artisan passport:install
```

## 🔐 Authentification

L'API utilise **Laravel Passport** pour l'authentification OAuth2.

### Endpoints d'authentification

#### 1. Inscription
```http
POST /api/v1/register
Content-Type: application/json

{
  "nom": "Dupont",
  "prenom": "Jean",
  "telephone": "770000000",
  "email": "jean.dupont@email.com",
  "password": "password123",
  "password_confirmation": "password123",
  "type": "client" // client, commercant, fournisseur
}
```

#### 2. Envoi OTP de connexion
```http
POST /api/v1/sendOTP
Content-Type: application/json

{
  "telephone": "770000000"
}
```

#### 3. Vérification OTP
```http
POST /api/v1/login/otp
Content-Type: application/json

{
  "telephone": "770000000",
  "otp_code": "123456"
}
```

#### 4. Envoi OTP Email
```http
POST /api/v1/send-otp
Content-Type: application/json

{
  "email": "jean.dupont@email.com"
}
```

#### 5. Vérification OTP Email
```http
POST /api/v1/verify-otp-email
Content-Type: application/json

{
  "email": "jean.dupont@email.com",
  "code": "ABC123"
}
```

### Utilisation du Token

Tous les endpoints protégés nécessitent un header Authorization :

```http
Authorization: Bearer {access_token}
```

## 👥 Gestion des Utilisateurs

### Types d'utilisateurs

- **client** : Utilisateur standard
- **commercant** : Marchand qui peut recevoir des paiements
- **fournisseur** : Fournisseur de services
- **admin** : Administrateur système

### Statuts utilisateur

- **en_attente** : En attente d'approbation (commercants/fournisseurs)
- **actif** : Compte actif
- **inactif** : Compte désactivé
- **suspendu** : Compte temporairement suspendu
- **banni** : Compte définitivement banni

## 💰 Gestion des Comptes

### Création automatique

Lors de l'inscription d'un client, un compte principal est créé automatiquement.

### Comptes secondaires

Les utilisateurs peuvent créer des comptes secondaires via l'API admin.

### Structure compte

```json
{
  "id": "uuid",
  "numero_compte": "CMPT-001234",
  "nom_compte": "compte principal",
  "titulaire": "Jean Dupont",
  "solde": 15000.50,
  "statut": "actif",
  "type_compte": "courant",
  "devise": "XOF",
  "code_marchand": "MRC001",
  "utilisateur_id": "uuid",
  "created_at": "2025-01-01T00:00:00Z",
  "updated_at": "2025-01-01T00:00:00Z"
}
```

## 💸 Transactions

### Types de transactions

- **depot** : Dépôt d'argent
- **retrait** : Retrait d'argent
- **transfert** : Transfert entre comptes
- **paiement** : Paiement à un marchand

### Statuts transaction

- **en_attente** : En cours de traitement
- **reussie** : Transaction réussie
- **echouee** : Transaction échouée
- **annulee** : Transaction annulée

### Structure transaction

```json
{
  "id": "uuid",
  "type": "transfert",
  "montant": 5000.00,
  "reference": "TXN-20250101-ABC123",
  "statut": "reussie",
  "frais": 125.00,
  "note": "Paiement facture",
  "compte_emetteur_id": "uuid",
  "compte_recepteur_id": "uuid",
  "date_transaction": "2025-01-01T12:00:00Z",
  "created_at": "2025-01-01T12:00:00Z"
}
```

## 📡 Endpoints API

### Authentification

| Méthode | Endpoint | Description |
|---------|----------|-------------|
| POST | `/api/v1/register` | Inscription utilisateur |
| POST | `/api/v1/sendOTP` | Envoi OTP connexion |
| POST | `/api/v1/login/otp` | Vérification OTP connexion |
| POST | `/api/v1/send-otp` | Envoi OTP email |
| POST | `/api/v1/verify-otp-email` | Vérification OTP email |
| POST | `/api/v1/logout` | Déconnexion |

### Comptes (Authentifié)

| Méthode | Endpoint | Description |
|---------|----------|-------------|
| GET | `/api/v1/comptes/me` | Liste comptes utilisateur |
| GET | `/api/v1/balance` | Solde compte actif |
| PUT | `/api/v1/comptes/{compte}/activate` | Activer compte |
| POST | `/api/v1/comptes` | Créer compte |
| PUT | `/api/v1/comptes/{compte}` | Modifier compte |
| DELETE | `/api/v1/comptes/{compte}` | Supprimer compte |

### Transactions (Authentifié)

| Méthode | Endpoint | Description |
|---------|----------|-------------|
| GET | `/api/v1/transactions` | Liste transactions |
| POST | `/api/v1/transactions` | Nouvelle transaction |
| POST | `/api/v1/transactions/retrait` | Demande retrait |
| POST | `/api/v1/transactions/confirm-retrait` | Confirmer retrait |
| POST | `/api/v1/transactions/achat-virtuel` | Achat virtuel |
| POST | `/api/v1/transactions/demande` | Demande solde fournisseur |
| POST | `/api/v1/transactions/unified` | Transaction unifiée |

### Administration (Admin seulement)

| Méthode | Endpoint | Description |
|---------|----------|-------------|
| GET | `/api/v1/admin/users/pending` | Utilisateurs en attente |
| POST | `/api/v1/admin/users/{telephone}/action` | Action utilisateur |
| PUT | `/api/v1/admin/users/{user}/rights` | Modifier droits |
| PUT | `/api/v1/admin/users/{user}/tax` | Définir taxe |
| POST | `/api/v1/admin/users/{user}/comptes` | Créer compte utilisateur |
| GET | `/api/v1/admin/comptes` | Tous les comptes |
| GET | `/api/v1/admin/statistics/daily` | Statistiques journalières |
| PUT | `/api/v1/admin/fees/global` | Modifier frais globaux |
| GET | `/api/v1/admin/balance-requests/pending` | Demandes solde en attente |
| GET | `/api/v1/admin/actions` | Historique actions admin |
| POST | `/api/v1/admin/balance-requests/{telephone}/action` | Action demande solde |

## 🔒 Sécurité

### Mesures de sécurité implémentées

- **Chiffrement des mots de passe** : Bcrypt
- **Authentification OAuth2** : Laravel Passport
- **OTP double facteur** : Email et SMS
- **Validation stricte** : Laravel Form Requests
- **Rate limiting** : Protection contre les attaques par déni de service
- **CORS** : Configuration sécurisée
- **Logs d'audit** : Traçabilité des actions admin

### Limites de taux (Rate Limiting)

- **Authentification** : 5 tentatives par minute
- **Transactions** : 10 transactions par heure
- **Administration** : 100 actions par heure

## 📊 Modèles de Données

### User
```json
{
  "id": "uuid",
  "nom": "Dupont",
  "prenom": "Jean",
  "telephone": "770000000",
  "email": "jean.dupont@email.com",
  "type": "client",
  "statut": "actif",
  "tax_percentage": 0.0,
  "transfer_enabled": true,
  "can_transfer_to_client": true,
  "can_pay_merchant": true,
  "email_verified_at": "2025-01-01T00:00:00Z",
  "created_at": "2025-01-01T00:00:00Z",
  "updated_at": "2025-01-01T00:00:00Z"
}
```

### Compte
```json
{
  "id": "uuid",
  "numero_compte": "CMPT-001234",
  "nom_compte": "compte principal",
  "titulaire": "Jean Dupont",
  "solde": 15000.50,
  "statut": "actif",
  "type_compte": "courant",
  "devise": "XOF",
  "code_marchand": null,
  "utilisateur_id": "uuid",
  "created_at": "2025-01-01T00:00:00Z",
  "updated_at": "2025-01-01T00:00:00Z"
}
```

### Transaction
```json
{
  "id": "uuid",
  "type": "transfert",
  "montant": 5000.00,
  "reference": "TXN-20250101-ABC123",
  "statut": "reussie",
  "frais": 125.00,
  "note": "Paiement facture",
  "compte_emetteur_id": "uuid",
  "compte_recepteur_id": "uuid",
  "date_transaction": "2025-01-01T12:00:00Z",
  "created_at": "2025-01-01T12:00:00Z",
  "updated_at": "2025-01-01T12:00:00Z"
}
```

## 🧪 Tests

### Exécution des tests

```bash
# Tests unitaires
php artisan test --testsuite=Unit

# Tests de fonctionnalités
php artisan test --testsuite=Feature

# Tests avec couverture
php artisan test --coverage
```

### Tests inclus

- **Unitaires** : Services, repositories, utilitaires
- **Fonctionnels** : Endpoints API, workflows complets
- **Intégration** : Base de données, files d'attente

## 📚 Documentation API

### Swagger/OpenAPI

La documentation interactive est disponible sur `/api/documentation` après génération :

```bash
php artisan l5-swagger:generate
```

### Postman Collection

Importez le fichier `Orange_Money_API.postman_collection.json` pour tester tous les endpoints.

## 🚀 Déploiement

### Variables d'environnement

```env
APP_NAME="Orange Money API"
APP_ENV=production
APP_KEY=base64:your_app_key
APP_DEBUG=false
APP_URL=https://api.orangemoney.com

DB_CONNECTION=pgsql
DB_HOST=your_db_host
DB_PORT=5432
DB_DATABASE=orange_money_prod
DB_USERNAME=your_db_user
DB_PASSWORD=your_db_password

MAIL_MAILER=smtp
MAIL_HOST=your_smtp_host
MAIL_PORT=587
MAIL_USERNAME=your_smtp_user
MAIL_PASSWORD=your_smtp_password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=noreply@orangemoney.com
MAIL_FROM_NAME="Orange Money"

PASSPORT_PERSONAL_ACCESS_CLIENT_ID=1
PASSPORT_PERSONAL_ACCESS_CLIENT_SECRET=your_secret

BREVO_API_KEY=your_brevo_api_key
TWILIO_SID=your_twilio_sid
TWILIO_TOKEN=your_twilio_token
TWILIO_FROM=your_twilio_number
```

### Commandes de déploiement

```bash
# Optimisation pour la production
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Génération des clés Passport
php artisan passport:keys

# Création des clients OAuth
php artisan passport:client --personal
php artisan passport:client --password
```

## 🔧 Maintenance

### Commandes Artisan

```bash
# Nettoyage des OTP expirés
php artisan otp:clean-expired

# Génération des statistiques
php artisan stats:generate-daily

# Sauvegarde base de données
php artisan db:backup

# Vérification santé système
php artisan health:check
```

### Monitoring

- **Logs** : Stockés dans `storage/logs/`
- **Métriques** : Via Laravel Telescope (optionnel)
- **Alertes** : Configuration email pour erreurs critiques

## 🤝 Support

### Contacts

- **Email** : support@orangemoney.com
- **Documentation** : https://docs.orangemoney.com
- **Issues** : GitHub repository

### Versions

- **API Version** : v1.0.0
- **Laravel** : 10.x
- **PHP** : 8.1+
- **PostgreSQL** : 13+

---

## 📝 Notes pour l'IA Dart Console

### Configuration de connexion

```dart
const String baseUrl = 'https://api.orangemoney.com/api/v1';
const String clientId = 'your_oauth_client_id';
const String clientSecret = 'your_oauth_client_secret';

// Headers requis pour toutes les requêtes authentifiées
Map<String, String> getAuthHeaders(String token) {
  return {
    'Authorization': 'Bearer $token',
    'Content-Type': 'application/json',
    'Accept': 'application/json',
  };
}
```

### Gestion des erreurs

```dart
// Codes d'erreur HTTP
const int HTTP_OK = 200;
const int HTTP_CREATED = 201;
const int HTTP_BAD_REQUEST = 400;
const int HTTP_UNAUTHORIZED = 401;
const int HTTP_FORBIDDEN = 403;
const int HTTP_NOT_FOUND = 404;
const int HTTP_UNPROCESSABLE_ENTITY = 422;
const int HTTP_TOO_MANY_REQUESTS = 429;
const int HTTP_INTERNAL_SERVER_ERROR = 500;

// Structure de réponse d'erreur
class ApiError {
  final int status;
  final String message;
  final Map<String, dynamic>? errors;

  ApiError({
    required this.status,
    required this.message,
    this.errors,
  });

  factory ApiError.fromJson(Map<String, dynamic> json) {
    return ApiError(
      status: json['status'] ?? 500,
      message: json['message'] ?? 'Unknown error',
      errors: json['errors'],
    );
  }
}
```

### Workflow d'authentification

```dart
// 1. Inscription
final registerResponse = await http.post(
  Uri.parse('$baseUrl/register'),
  headers: {'Content-Type': 'application/json'},
  body: jsonEncode({
    'nom': 'Dupont',
    'prenom': 'Jean',
    'telephone': '770000000',
    'email': 'jean@example.com',
    'password': 'password123',
    'password_confirmation': 'password123',
    'type': 'client',
  }),
);

// 2. Envoi OTP
await http.post(
  Uri.parse('$baseUrl/sendOTP'),
  headers: {'Content-Type': 'application/json'},
  body: jsonEncode({'telephone': '770000000'}),
);

// 3. Vérification OTP et récupération token
final loginResponse = await http.post(
  Uri.parse('$baseUrl/login/otp'),
  headers: {'Content-Type': 'application/json'},
  body: jsonEncode({
    'telephone': '770000000',
    'otp_code': '123456',
  }),
);

// Extraire le token de la réponse
final token = jsonDecode(loginResponse.body)['token'];
```

Cette documentation fournit toutes les informations nécessaires pour intégrer votre application Dart console avec l'API Orange Money backend de manière sécurisée et efficace.
