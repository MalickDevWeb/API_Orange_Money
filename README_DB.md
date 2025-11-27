# 🗄️ Gestion des Bases de Données Neon

## 📋 Vue d'ensemble

Le projet utilise **deux bases de données Neon distinctes** :
- **Production** : Données réelles, utilisateurs actifs
- **Développement** : Données de test, développempostgresql://user:pass@host/db_prod

# Développement
DATABASE_URL_DEV=postgresql://user:pass@host/db_dev

# URL active (changée automatiquement)
DATABASE_URL=postgresql://...
```

### Branches et bases associées

| Branche | Base de données | Usage |
|---------|----------------|-------|
| `prod` | **Production** | Code en ligne |
| `dev/*` | **Développement** | Nouvelles features |
| Autres | **Par défaut** | Selon configuration |

## 🚀 Utilisation

### Changement manuel de base

```bash
# Basculement automatique selon la branche
./switch-db.sh

# Ou spécifier manuellement
# Pour prod
sed -i 's|DATABASE_URL=.*|DATABASE_URL=$DATABASE_URL_PROD|' .env

# Pour dev
sed -i 's|DATABASE_URL=.*|DATABASE_URL=$DATABASE_URL_DEV|' .env
```

### Changement automatique (Hook Git)

Le hook `post-checkout` bascule automatiquement la base lors des changements de branche :

```bash
git checkout prod        # → Base production
git checkout dev/1.1.3   # → Base développement
```

## 📊 Gestion des données

### Vérifier les données actuelles

```bash
# Voir tous les utilisateurs
php artisan tinker --execute="
App\Models\User::all()->each(function(\$u) {
    echo \$u->id . ' | ' . \$u->nom . ' ' . \$u->prenom . ' | ' . \$u->telephone . ' | ' . \$u->type . PHP_EOL;
});
"
```

### Migrer et seeder

```bash
# Production (avec précaution!)
php artisan migrate:fresh --seed

# Développement (libre)
php artisan migrate:fresh --seed
```

## 🔒 Sécurité

- ✅ **Ne jamais** mélanger les données prod/dev
- ✅ **Toujours vérifier** la branche avant les migrations
- ✅ **Sauvegarder** avant les opérations importantes
- ✅ **Utiliser des credentials différents** pour chaque base

## 🐛 Dépannage

### Problème : Mauvaise base sélectionnée
```bash
./switch-db.sh  # Recalcule selon la branche
```

### Problème : Cache Laravel
```bash
php artisan config:clear
php artisan cache:clear
```

### Problème : Hook Git non exécuté
```bash
chmod +x .git/hooks/post-checkout
```

## 📝 Notes importantes

- Les **URLs des bases** doivent être configurées dans `.env`
- Le **hook Git** s'exécute automatiquement lors des `git checkout`
- Les **migrations** doivent être compatibles avec PostgreSQL
- **Ne pas commiter** les vraies URLs de base de données !
