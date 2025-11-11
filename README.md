# 🏦 Orange Money API

Une API REST complète pour les services de paiement Orange Money, développée avec Laravel 11 et respectant les principes SOLID.

## 📋 Table des matières

- [Fonctionnalités](#fonctionnalités)
- [Architecture](#architecture)
- [Technologies](#technologies)
- [Installation](#installation)
- [Configuration](#configuration)
- [Déploiement](#déploiement)
- [API Documentation](#api-documentation)
- [Tests](#tests)
- [Sécurité](#sécurité)

## ✨ Fonctionnalités

### 👥 Gestion des utilisateurs
- Inscription et authentification
- Vérification OTP par SMS (Twilio)
- Gestion des rôles (Admin, Client, Commerçant)

### 💳 Gestion des comptes
- Création automatique de comptes
- Génération de numéros de compte uniques
- QR codes pour paiements mobiles
- Consultation des soldes (propriétaire uniquement)
- Soft deletes pour sécurité

### 💰 Transactions financières
- Dépôts sur comptes
- Retraits depuis comptes
- Transferts entre comptes
- Paiements aux commerçants
- Historique complet des transactions

### 🔒 Sécurité
- Authentification JWT/Sanctum
- Autorisation basée sur les rôles
- Validation des soldes avant suppression
- Logs d'audit complets

### 📊 API Avancée
- Pagination, tri et filtrage
- Documentation Swagger interactive
- Health checks pour monitoring
- Cache optimisé

## 🏗️ Architecture

### Principes SOLID
- **S**RP : Chaque classe a une responsabilité unique
- **O**CP : Extension sans modification
- **L**SP : Substitution de Liskov respectée
- **I**SP : Interfaces spécialisées
- **D**IP : Injection de dépendances

### Structure des dossiers
```
app/
├── Interfaces/          # Contrats des services
│   ├── Domain/         # Logique métier
│   ├── Services/       # Services applicatifs
│   └── Repositories/   # Accès aux données
├── Services/           # Implémentations des services
├── Repositories/       # Implémentations des repositories
├── DTOs/              # Objets de transfert de données
├── Enums/             # Énumérations typées
├── Traits/            # Traits réutilisables
├── Events/            # Événements Laravel
├── Listeners/         # Écouteurs d'événements
├── Observers/         # Observers de modèles
├── Http/
│   ├── Controllers/   # Contrôleurs REST
│   ├── Middleware/    # Middlewares personnalisés
│   └── Requests/      # Validation des requêtes
└── Models/            # Modèles Eloquent
```

## 🛠️ Technologies

- **PHP 8.2** avec Laravel 11
- **PostgreSQL** pour la base de données
- **Redis** pour le cache et sessions
- **Docker** pour la conteneurisation
- **Nginx** comme reverse proxy
- **Swagger/OpenAPI** pour la documentation
- **Twilio** pour les SMS
- **Brevo (Sendinblue)** pour les emails

## 🚀 Installation

### Prérequis
- Docker & Docker Compose
- Git

### Installation rapide
```bash
# Cloner le repository
git clone https://github.com/your-username/orange-money-api.git
cd orange-money-api

# Lancer l'application
./build.sh

# L'application sera disponible sur http://localhost
```

### Installation manuelle
```bash
# Installer les dépendances PHP
composer install

# Installer les dépendances Node.js
npm install && npm run build

# Copier le fichier d'environnement
cp .env.example .env

# Générer la clé d'application
php artisan key:generate

# Configurer la base de données dans .env
# Puis lancer les migrations
php artisan migrate

# Générer la documentation Swagger
php artisan l5-swagger:generate

# Démarrer le serveur
php artisan serve
```

## ⚙️ Configuration

### Variables d'environnement (.env)

```env
# Application
APP_NAME="Orange Money API"
APP_ENV=production
APP_KEY=your_app_key
APP_DEBUG=false
APP_URL=https://your-app.onrender.com

# Base de données (PostgreSQL)
DB_CONNECTION=pgsql
DB_HOST=your-db-host
DB_DATABASE=your-db-name
DB_USERNAME=your-db-user
DB_PASSWORD=your-db-password

# Redis
REDIS_HOST=your-redis-host
REDIS_PASSWORD=your-redis-password

# Services externes
BREVO_API_KEY=your_brevo_key
TWILIO_SID=your_twilio_sid
TWILIO_TOKEN=your_twilio_token
TWILIO_PHONE_NUMBER=your_twilio_number
```

## 🌐 Déploiement

### Sur Render

1. **Créer un compte Render** et lier votre repository GitHub
2. **Créer un service Web** avec les paramètres suivants :
   - **Runtime** : Docker
   - **Build Command** : `docker build -t orange-money-api .`
   - **Start Command** : `docker run -p $PORT:9000 orange-money-api`
3. **Configurer les variables d'environnement** dans Render
4. **Ajouter une base de données PostgreSQL** dans Render
5. **Déployer** l'application

### Variables Render requises
```
APP_KEY
DB_HOST
DB_DATABASE
DB_USERNAME
DB_PASSWORD
REDIS_HOST
REDIS_PASSWORD
BREVO_API_KEY
TWILIO_SID
TWILIO_TOKEN
TWILIO_PHONE_NUMBER
```

### Avec Docker Compose (local/production)
```bash
# Construire et démarrer tous les services
docker-compose up -d --build

# Suivre les logs
docker-compose logs -f

# Arrêter les services
docker-compose down
```

## 📚 API Documentation

### Swagger UI
Une fois déployée, la documentation interactive est disponible sur :
```
https://your-app-url/api/docs
```

### Endpoints principaux

#### Authentification
- `POST /api/auth/login` - Connexion
- `POST /api/auth/register` - Inscription
- `POST /api/auth/verify-otp` - Vérification OTP

#### Comptes (Admin seulement)
- `GET /api/comptes` - Lister tous les comptes
- `GET /api/comptes/{id}` - Détails d'un compte

#### Comptes (Propriétaire)
- `POST /api/comptes` - Créer un compte
- `GET /api/comptes/my/balance` - **Nouveau** : Voir son solde total
- `GET /api/comptes/{id}` - Détails de son compte
- `PUT /api/comptes/{id}` - Modifier son compte
- `DELETE /api/comptes/{id}` - Supprimer son compte (solde nul requis)

#### Transactions
- `GET /api/transactions` - Historique des transactions
- `POST /api/transactions/depot` - Effectuer un dépôt
- `POST /api/transactions/retrait` - Effectuer un retrait
- `POST /api/transactions/transfert` - Effectuer un transfert
- `POST /api/transactions/paiement` - Effectuer un paiement

#### Monitoring
- `GET /api/health` - Health check de l'application

## 🧪 Tests

```bash
# Exécuter tous les tests
php artisan test

# Tests avec couverture
php artisan test --coverage

# Tests spécifiques
php artisan test --filter=TransactionTest
```

## 🔒 Sécurité

### Mesures de sécurité implémentées
- ✅ **Authentification** : Sanctum/JWT avec tokens
- ✅ **Autorisation** : Middleware de propriété des comptes
- ✅ **Validation** : Règles métier strictes (soldes, types de comptes)
- ✅ **Logs** : Audit complet des opérations sensibles
- ✅ **Rate limiting** : Protection contre les abus
- ✅ **CORS** : Configuration sécurisée
- ✅ **Headers de sécurité** : XSS, CSRF, Content-Type sniffing

### Règles métier
- Un compte ne peut être supprimé que si son solde est nul
- Seuls les commerçants peuvent recevoir des paiements
- Les administrateurs ont accès à tous les comptes
- Les utilisateurs ne voient que leurs propres soldes

## 🤝 Contribution

1. Fork le projet
2. Créer une branche feature (`git checkout -b feature/AmazingFeature`)
3. Commit les changements (`git commit -m 'Add some AmazingFeature'`)
4. Push vers la branche (`git push origin feature/AmazingFeature`)
5. Ouvrir une Pull Request

## 📝 Licence

Ce projet est sous licence MIT. Voir le fichier `LICENSE` pour plus de détails.

## 📞 Support

Pour toute question ou problème :
- Ouvrir une issue sur GitHub
- Contacter l'équipe de développement

---

**Développé avec ❤️ pour Orange Money**
