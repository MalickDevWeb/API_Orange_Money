#!/bin/bash

# Script pour basculer entre les bases de données selon la branche Git

BRANCH=$(git branch --show-current)
ENV_FILE=".env"

echo "🔄 Basculement de base de données pour la branche: $BRANCH"

case $BRANCH in
    "prod")
        echo "🎯 Utilisation de la base PRODUCTION"
        sed -i 's|DATABASE_URL=.*|DATABASE_URL=$DATABASE_URL_PROD|' $ENV_FILE
        ;;
    "dev/1.1.3"|"ompay_api_dart"|"dev/"*)
        echo "🛠️  Utilisation de la base DÉVELOPPEMENT"
        sed -i 's|DATABASE_URL=.*|DATABASE_URL=$DATABASE_URL_DEV|' $ENV_FILE
        ;;
    *)
        echo "⚠️  Branche inconnue, utilisation de la base par défaut"
        ;;
esac

echo "✅ Configuration mise à jour"
echo "🔄 Redémarrage du cache..."
php artisan config:clear
php artisan cache:clear

echo "🚀 Base de données prête pour la branche $BRANCH"
