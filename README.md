# API Orange Money - Mobile Money System

Une API REST complète pour un système de paiement mobile (Orange Money) développée avec Laravel. Cette API permet la gestion des utilisateurs, comptes bancaires, transactions financières et administration avec contrôles de sécurité avancés.

## 🚀 Fonctionnalités

- **Authentification multi-étapes** avec OTP (SMS/Email)
- **Gestion des comptes** avec QR codes et codes marchands
- **Transactions financières** : dépôts, retraits, transferts, paiements marchands
- **Contrôles administratifs** : droits utilisateurs, taxes personnalisées, frais globaux
- **Notifications** : SMS (Twilio), Email (Brevo/Sendinblue)
- **Documentation API** avec Swagger/OpenAPI
- **Architecture SOLID** avec interfaces et services

## 📋 Prérequis

- PHP 8.1 ou supérieur
- Composer
- MySQL 5.7+ ou PostgreSQL
- Node.js & npm (pour les assets frontend)

## 🛠 Installation

1. **Cloner le repository**
   ```bash
   git clone <repository-url>
   cd api_orange_money
   ```

2. **Installer les dépendances PHP**
   ```bash
   composer install
   ```

3. **Installer les dépendances Node.js**
   ```bash
   npm install
   ```

4. **Configuration de l'environnement**
   ```bash
   cp .env.example .env
   php artisan key:generate
   ```

5. **Configuration de la base de données**
   - Créer une base de données MySQL/PostgreSQL
   - Modifier les variables dans `.env` :
     ```env
     DB_CONNECTION=mysql
     DB_HOST=127.0.0.1
     DB_PORT=3306
     DB_DATABASE=orange_money_api
     DB_USERNAME=votre_username
     DB_PASSWORD=votre_password
     ```

6. **Exécuter les migrations**
   ```bash
   php artisan migrate
   ```

7. **Exécuter les seeders (optionnel)**
   ```bash
   php artisan db:seed
   ```

8. **Générer la clé Passport pour l'authentification**
   ```bash
   php artisan passport:install
   ```

9. **Démarrer le serveur**
   ```bash
   php artisan serve
   ```

## ⚙️ Configuration

### Variables d'environnement importantes

```env
# Application
APP_NAME="API Orange Money"
APP_ENV=production
APP_DEBUG=false
APP_URL=https://api.orangemoney.com

# Base de données
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=orange_money_api
DB_USERNAME=your_db_user
DB_PASSWORD=your_db_password

# Services externes
TWILIO_SID=your_twilio_sid
TWILIO_AUTH_TOKEN=your_twilio_token
TWILIO_PHONE_NUMBER=+221XXXXXXXXX

BREVO_API_KEY=your_brevo_api_key
BREVO_SENDER_NAME="Orange Money"
BREVO_SENDER_EMAIL=noreply@orangemoney.com

# Sécurité
PASSPORT_PERSONAL_ACCESS_CLIENT_ID=1
PASSPORT_PERSONAL_ACCESS_CLIENT_SECRET=your_secret
```

### Configuration des services

1. **Twilio** : Pour les SMS OTP
2. **Brevo/Sendinblue** : Pour les emails
3. **QR Code** : Génération automatique des QR codes pour les comptes

## 🔐 Authentification

L'API utilise une authentification à deux facteurs avec Laravel Passport :

### 1. Inscription
```http
POST /api/register
Content-Type: application/json

{
  "nom": "Dupont",
  "prenom": "Jean",
  "telephone": "705334611",
  "email": "jean.dupont@example.com",
  "password": "password123",
  "type": "client"
}
```

### 2. Connexion (Étape 1)
```http
POST /api/login
Content-Type: application/json

{
  "telephone": "705334611"
}
```

### 3. Vérification OTP (Étape 2)
```http
POST /api/login/otp
Content-Type: application/json

{
  "telephone": "705334611",
  "otp_code": "123456"
}
```

**Réponse réussie :**
```json
{
  "status": "success",
  "message": "Connexion réussie",
  "access_token": "Bearer eyJ0eXAiOiJKV1QiLCJhbGciOiJSUzI1NiJ9...",
  "token_type": "Bearer",
  "user": {
    "id": "uuid",
    "nom": "Dupont",
    "prenom": "Jean",
    "telephone": "705334611",
    "email": "jean.dupont@example.com",
    "type": "client",
    "statut": "actif"
  }
}
```

## 📚 API Endpoints

### Authentification

| Méthode | Endpoint | Description |
|---------|----------|-------------|
| POST | `/api/register` | Inscription nouvel utilisateur |
| POST | `/api/login` | Connexion (envoi OTP) |
| POST | `/api/login/otp` | Vérification OTP connexion |
| POST | `/api/send-otp` | Envoi OTP générique |
| POST | `/api/verify-otp-email` | Vérification OTP email |
| GET | `/api/logout/otp` | Envoi OTP déconnexion |
| POST | `/api/logout` | Vérification OTP déconnexion |
| GET | `/api/user` | Informations utilisateur connecté |
| PUT | `/api/user` | Mise à jour profil utilisateur |

### Comptes

| Méthode | Endpoint | Description |
|---------|----------|-------------|
| GET | `/api/comptes/me` | Comptes de l'utilisateur connecté |
| GET | `/api/comptes/me/balance` | Solde du compte actif |
| POST | `/api/comptes/{compte}/activate` | Activer un compte |
| GET | `/api/comptes` | Lister tous les comptes (Admin) |
| POST | `/api/comptes` | Créer un nouveau compte |
| GET | `/api/comptes/{compte}` | Détails d'un compte |
| PUT | `/api/comptes/{compte}` | Modifier un compte |
| DELETE | `/api/comptes/{compte}` | Supprimer un compte (soft delete) |
| POST | `/api/comptes/{compte}/restore` | Restaurer un compte supprimé |
| DELETE | `/api/comptes/{compte}/force-delete` | Supprimer définitivement |

### Transactions

| Méthode | Endpoint | Description |
|---------|----------|-------------|
| GET | `/api/transactions` | Lister les transactions |
| POST | `/api/transactions` | Créer une transaction |
| GET | `/api/transactions/{transaction}` | Détails d'une transaction |
| PUT | `/api/transactions/{transaction}` | Modifier une transaction |
| DELETE | `/api/transactions/{transaction}` | Supprimer une transaction |
| POST | `/api/transactions/depot` | Effectuer un dépôt |
| POST | `/api/transactions/retrait` | Effectuer un retrait |
| POST | `/api/transactions/transfert` | **Transaction unifiée** - Détection automatique du type |
| POST | `/api/transactions/paiement` | Effectuer un paiement marchand |
| POST | `/api/transactions/achat-virtuel` | Achat d'argent virtuel (Admin) |

### Administration

| Méthode | Endpoint | Description |
|---------|----------|-------------|
| GET | `/api/admin/users/pending` | Utilisateurs en attente d'approbation |
| POST | `/api/admin/users/{telephone}/approve` | Approuver un utilisateur |
| POST | `/api/admin/users/{telephone}/reject` | Rejeter un utilisateur |
| PUT | `/api/admin/users/{user}/rights` | Modifier droits utilisateur |
| POST | `/api/admin/users/{user}/ban` | Bannir un utilisateur |
| POST | `/api/admin/users/{user}/unban` | Débannir un utilisateur |
| GET | `/api/admin/statistics/daily` | Statistiques journalières |
| PUT | `/api/admin/fees/global` | Modifier frais globaux |
| GET | `/api/admin/balance-requests/pending` | Demandes de solde en attente |
| POST | `/api/admin/balance-requests/{id}/approve` | Approuver demande de solde |
| POST | `/api/admin/balance-requests/{id}/reject` | Rejeter demande de solde |
| PUT | `/api/admin/users/{user}/tax` | Définir taxe utilisateur |
| POST | `/api/admin/deposit` | Dépôt admin sur compte client |

### Fournisseurs

| Méthode | Endpoint | Description |
|---------|----------|-------------|
| POST | `/api/suppliers/balance-request` | Demander achat de solde |

## 💰 Transaction Unifiée - Endpoint Intelligent

### 🎯 **Un seul endpoint pour toutes les transactions !**

L'API détecte automatiquement le type de transaction selon :
- **Le type d'utilisateur connecté** (Admin, Fournisseur, Client, Commerçant)
- **Le type du destinataire**

### 📊 Matrice de Détection Automatique

| Émetteur → Destinataire | Type de Transaction | Description |
|-------------------------|-------------------|-------------|
| Admin → Client/Fournisseur | **Dépôt** | Crédit du compte destinataire |
| Fournisseur → Client | **Dépôt** | Crédit du compte client |
| Client → Client | **Transfert** | Transfert entre clients |
| Client → Commerçant | **Paiement** | Paiement marchand avec frais |

### 🚀 Utilisation Simplifiée

**Un seul endpoint pour tout :**
```http
POST /api/transactions/transfert
Authorization: Bearer {token}
Content-Type: application/json

{
  "montant": 50000,
  "telephone_recepteur_id": "770000000",
  "note": "Transaction automatique"
}
```

**Le système détecte automatiquement :**
- ✅ **Admin vers Client** = Dépôt
- ✅ **Fournisseur vers Client** = Dépôt
- ✅ **Client vers Client** = Transfert
- ✅ **Client vers Commerçant** = Paiement

### 💡 Avantages

- **1 seul endpoint** à retenir
- **Détection automatique** du type de transaction
- **Validation intelligente** selon les rôles
- **Frais appliqués automatiquement** pour les paiements
- **Références générées automatiquement** (DEP-, TRF-, PAY-)

### 📝 Exemples d'Utilisation

#### Admin créditant un client :
```json
{
  "montant": 100000,
  "telephone_recepteur_id": "770000000",
  "note": "Crédit client"
}
// → Type: dépôt, Référence: DEP-XXXXX
```

#### Client payant un commerçant :
```json
{
  "montant": 25000,
  "telephone_recepteur_id": "771234568",
  "note": "Paiement restaurant"
}
// → Type: paiement, Frais: 125 (0.5%), Référence: PAY-XXXXX
```

#### Client transférant à un autre client :
```json
{
  "montant": 50000,
  "telephone_recepteur_id": "772345678",
  "note": "Transfert ami"
}
// → Type: transfert, Référence: TRF-XXXXX
```

## 👥 Rôles et Permissions

### Types d'utilisateurs
- **Admin** : Accès complet à l'administration
- **Client** : Utilisateur standard
- **Commercant** : Marchand pouvant recevoir des paiements
- **Fournisseur** : Fournisseur de services (en attente d'approbation)

### Droits de transfert
- `transfer_enabled` : Autorisation générale de transfert
- `can_transfer_to_client` : Transfert vers autres clients
- `can_pay_merchant` : Paiement vers marchands

### Statuts utilisateur
- `en_attente` : En attente d'approbation admin
- `actif` : Compte actif
- `inactif` : Compte désactivé

## 💳 Gestion des Frais et Taxes

### Frais globaux
- **Frais de transaction** : Appliqués à toutes les transactions
- **Pourcentage marchand** : Part revenant aux marchands

### Taxes utilisateur
- **Taxe personnalisée** : Par utilisateur (en %)
- **Frais par téléphone** : 0.5% pour paiements par numéro

### Calcul automatique
Le système calcule automatiquement :
```
Montant total = Montant de base + Frais système + Taxe utilisateur
```

## 📊 Réponses API

### Réponse de succès
```json
{
  "status": "success",
  "message": "Opération réussie",
  "data": { ... }
}
```

### Réponse d'erreur
```json
{
  "status": "error",
  "message": "Description de l'erreur",
  "errors": { ... }
}
```

### Codes de statut HTTP
- `200` : Succès
- `201` : Créé
- `400` : Requête invalide
- `401` : Non autorisé
- `403` : Interdit
- `404` : Non trouvé
- `422` : Données invalides
- `500` : Erreur serveur

## 🔧 Commandes Artisan

```bash
# Générer documentation Swagger
php artisan l5-swagger:generate

# Tester l'envoi d'OTP
php artisan test:otp-send

# Tester l'envoi d'email de transaction
php artisan test:transaction-email

# Nettoyer les anciens OTP
php artisan otp:cleanup
```

## 🧪 Tests

```bash
# Exécuter tous les tests
php artisan test

# Tests avec couverture
php artisan test --coverage
```

## 📝 Logs et Monitoring

- **Logs Laravel** : `storage/logs/laravel.log`
- **Logs admin** : Actions administrateur tracées
- **Notifications** : Historique des SMS/Emails

## 🤝 Contribution

1. Fork le projet
2. Créer une branche feature (`git checkout -b feature/AmazingFeature`)
3. Commit les changements (`git commit -m 'Add some AmazingFeature'`)
4. Push vers la branche (`git push origin feature/AmazingFeature`)
5. Ouvrir une Pull Request

## 📄 Licence

Ce projet est sous licence MIT - voir le fichier [LICENSE](LICENSE) pour plus de détails.

## 📞 Support

Pour toute question ou support :
- Email : support@orangemoney.com
- Documentation : [https://api.orangemoney.com/docs](https://api.orangemoney.com/docs)

---

**Développé avec ❤️ par l'équipe Orange Money**
