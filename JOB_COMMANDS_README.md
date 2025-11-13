# 🚀 Guide des Commandes et Jobs Planifiés

Ce guide explique comment utiliser les commandes Artisan et les jobs planifiés du système Orange Money.

## 📋 Commandes Disponibles

### 1. 🧹 Nettoyage des Codes OTP Expirés

**Commande :** `php artisan otp:clean-expired`

**Description :** Supprime automatiquement tous les codes OTP expirés de la base de données.

#### Options :
- `--dry-run` : Mode test - Affiche les codes qui seraient supprimés sans les supprimer

#### Exemples d'utilisation :

```bash
# Mode test (recommandé avant la première utilisation)
php artisan otp:clean-expired --dry-run

# Sortie attendue :
# 🔍 Mode test : affichage des codes expirés sans suppression
# 📊 5 code(s) OTP expiré(s) trouvé(s).
# +----+-----------+---------------+---------------------+---------------------+
# | ID | Téléphone | Type          | Expiré le          | Créé le            |
# +----+-----------+---------------+---------------------+---------------------+
# | 1  | 771234567 | delete_compte | 2025-11-13 16:45:00| 2025-11-13 16:35:00|
# ...
# ⚠️ Ces codes seraient supprimés en mode normal.

# Mode production (suppression effective)
php artisan otp:clean-expired

# Sortie attendue :
# 📊 5 code(s) OTP expiré(s) trouvé(s).
# 🗑️ 5 code(s) OTP expiré(s) supprimé(s) avec succès.
# ✅ Nettoyage terminé.
```

#### Ce que fait la commande :
- ✅ Recherche tous les codes OTP où `expires_at < now()`
- ✅ Compte le nombre de codes expirés
- ✅ En mode `--dry-run` : Affiche un tableau détaillé
- ✅ En mode normal : Supprime définitivement les codes expirés
- ✅ Fournit un rapport détaillé des actions effectuées

## ⏰ Jobs Planifiés (Tâches Automatiques)

### Configuration du Scheduler

Le système utilise le scheduler Laravel pour exécuter automatiquement les tâches de maintenance.

#### 1. Vérifier les tâches planifiées :
```bash
php artisan schedule:list
```

#### 2. Exécuter manuellement le scheduler (pour test) :
```bash
php artisan schedule:run
```

#### 3. Configuration du Cron (sur le serveur de production) :

Ajoutez cette ligne au cron du serveur :
```bash
* * * * * cd /path-to-your-project && php artisan schedule:run >> /dev/null 2>&1
```

### Job de Nettoyage Automatique

**Fréquence :** Toutes les 30 minutes
**Commande :** `otp:clean-expired`
**Options :** `--withoutOverlapping --runInBackground`

#### Fonctionnement :
```
Minute 0   → Création de codes OTP (expirent dans 10 min)
Minute 10  → Codes deviennent expirés
Minute 30  → 🧹 NETTOYAGE automatique exécuté
Minute 60  → 🧹 NETTOYAGE automatique exécuté
Minute 90  → 🧹 NETTOYAGE automatique exécuté
...
```

#### Avantages :
- ✅ **Zéro intervention manuelle**
- ✅ **Base de données toujours propre**
- ✅ **Performance optimisée**
- ✅ **Sécurité renforcée**
- ✅ **Exécution en arrière-plan**

## 📊 Monitoring et Logs

### Vérifier l'exécution des jobs :
```bash
# Consulter les logs Laravel
tail -f storage/logs/laravel.log

# Vérifier les tâches planifiées
php artisan schedule:list
```

### Métriques importantes :
- **Nombre de codes supprimés** par exécution
- **Fréquence d'exécution** (toutes les 30 minutes)
- **Durée d'exécution** (devrait être < 1 seconde)
- **Erreurs éventuelles** dans les logs

## 🔧 Dépannage

### Le job ne s'exécute pas ?
1. Vérifier que le cron est configuré sur le serveur
2. Vérifier les permissions des fichiers logs
3. Tester manuellement : `php artisan schedule:run`

### Trop de codes s'accumulent ?
1. Vérifier la fréquence du scheduler
2. Augmenter la fréquence si nécessaire
3. Vérifier les logs pour les erreurs

### Mode dry-run reste vide ?
- Normal si aucun code n'est expiré
- Les codes expirent 10 minutes après création

## 📈 Optimisations

### Index de base de données :
Assurez-vous que la colonne `expires_at` est indexée :
```sql
CREATE INDEX idx_otp_codes_expires_at ON otp_codes(expires_at);
```

### Performance :
- ✅ Requête optimisée : `WHERE expires_at < NOW()`
- ✅ Suppression en lot : `delete()` au lieu de boucle
- ✅ Exécution en arrière-plan : pas d'impact sur les utilisateurs

## 🎯 Bonnes Pratiques

1. **Toujours tester en dry-run** avant la première exécution en production
2. **Monitorer les logs** régulièrement pour détecter les anomalies
3. **Ajuster la fréquence** selon le volume de codes générés
4. **Sauvegarder** avant les modifications importantes
5. **Documenter** les changements de configuration

---

**📞 Support :** En cas de problème, consultez les logs Laravel et vérifiez la configuration du scheduler.
