# Étape 1: Build des assets frontend
FROM node:18-alpine AS frontend-build

WORKDIR /app

# Copier les fichiers package
COPY package*.json ./

# Installer les dépendances Node.js
RUN npm ci

# Copier les fichiers source
COPY . .

# Build des assets
RUN npm run build

# Étape 2: Build des dépendances PHP
FROM composer:2.6 AS composer-build

WORKDIR /app

# Installer PECL et MongoDB pour Composer
RUN apk add --no-cache bash zlib-dev gcc musl-dev make autoconf g++ \
  && pecl install mongodb \
  && echo "extension=mongodb.so" > /usr/local/etc/php/conf.d/mongodb.ini

# Copier composer files
COPY composer.json composer.lock ./

# Installer les dépendances
RUN composer install --no-dev --optimize-autoloader --no-interaction --prefer-dist --no-scripts

# Étape 3: Image finale pour l'application
FROM php:8.3-cli-alpine

# Installer les extensions PHP nécessaires et outils
RUN apk add --no-cache postgresql-dev bash postgresql-client \
  && apk add --no-cache zlib-dev gcc musl-dev make autoconf g++ \
  && pecl install mongodb \
  && docker-php-ext-enable mongodb \
  && docker-php-ext-install pdo pdo_pgsql

# Créer un utilisateur non-root
RUN addgroup -g 1000 laravel && adduser -G laravel -g laravel -s /bin/sh -D laravel

# Définir le répertoire de travail
WORKDIR /var/www/html

# Copier les dépendances installées depuis l'étape de build
COPY --from=composer-build /app/vendor ./vendor

# Copier les assets buildés
COPY --from=frontend-build /app/public/build ./public/build

# Copier le reste du code de l'application
COPY . .

# Créer les répertoires nécessaires et définir les permissions
RUN mkdir -p storage/framework/{cache,data,sessions,testing,views} \
  && mkdir -p storage/logs \
  && mkdir -p bootstrap/cache \
  && chown -R laravel:laravel /var/www/html \
  && chmod -R 775 storage bootstrap/cache

# Copier et rendre exécutable le script de démarrage
COPY start.sh /usr/local/bin/start.sh
RUN chmod +x /usr/local/bin/start.sh

USER laravel

# Exposer le port (Render utilise la variable d'environnement PORT)
EXPOSE 10000

# Commande par défaut pour Render
CMD ["/usr/local/bin/start.sh"]

