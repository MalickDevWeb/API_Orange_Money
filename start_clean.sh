#!/bin/bash

# Script pour lancer Laravel sans les variables d'environnement système problématiques

# Nettoyer les variables d'environnement système qui interferent
unset DATABASE_URL
unset DB_HOST
unset DB_USER
unset DB_PASSWORD
unset DB_NAME
unset DB_PORT

# Lancer la commande passée en argument
exec "$@"
