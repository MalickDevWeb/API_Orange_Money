# Déploiement sur Render

## Configuration Docker

Le projet est maintenant dockerisé et prêt pour le déploiement sur Render.

### Fichiers de configuration

- `Dockerfile` : Configuration Docker multi-étapes pour production
- `start.sh` : Script de démarrage qui :
  - Attend que la base de données soit prête
  - Exécute les migrations
  - Lance les seeders
  - Démarre le serveur Laravel

### Variables d'environnement pour Render

Configurez ces variables dans votre service Render :

```
DB_CONNECTION=pgsql
DB_HOST=votre-host-postgresql
DB_USERNAME=votre-username
DB_PASSWORD=votre-password
DB_DATABASE=votre-database
DB_PORT=5432

APP_NAME=Laravel
APP_ENV=production
APP_KEY=votre-app-key-générée
APP_DEBUG=false
APP_URL=https://votre-domaine.render.com

# Autres variables (Brevo, Twilio, etc.)
BREVO_API_KEY=votre-cle
# ...
```

### Déploiement

1. Poussez votre code sur GitHub
2. Créez un nouveau service Web sur Render
3. Connectez votre repository GitHub
4. Configurez le service :
   - **Runtime** : Docker
   - **Build Command** : (laissé vide, Docker gère le build)
   - **Start Command** : (laissé vide, défini dans Dockerfile)
5. Ajoutez les variables d'environnement
6. Déployez

### Base de données

Assurez-vous que votre base de données PostgreSQL est accessible depuis Render. Vous pouvez utiliser :
- PostgreSQL managé par Render
- Neon.tech (recommandé pour ce projet)
- Supabase
- AWS RDS

### Notes importantes

- Les seeders sont configurés pour créer des données de test avec des montants de 500000
- Le build inclut la compilation des assets Vite
- Le conteneur utilise l'utilisateur non-root `laravel` pour la sécurité
