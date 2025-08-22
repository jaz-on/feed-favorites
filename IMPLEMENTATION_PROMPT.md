# 🎯 **Feed Favorites Plugin - Prompt d'Implémentation**

## **Contexte et Objectif**

Tu es un développeur WordPress senior expérimenté. Tu dois implémenter les améliorations critiques du plugin Feed Favorites pour le rendre conforme aux standards WordPress.org et sécurisé pour la production.

**Statut actuel** : Plugin fonctionnel mais avec des vulnérabilités de sécurité critiques (score 44/100) et 300+ violations des standards de code.

**Objectif** : Transformer le plugin en un outil professionnel, sécurisé et conforme aux standards WordPress.

---

## 🚨 **Actions Critiques à Implémenter IMMÉDIATEMENT**

### **Phase 1 : Sécurité Critique (À FAIRE EN PREMIER)**

#### 1.1 Vérification des Nonces
**Fichiers à modifier** : `includes/sync.php`, `includes/ajax.php`, `includes/import.php`

**Instructions** :
- Ajouter `wp_verify_nonce()` dans toutes les fonctions qui traitent des données POST
- Créer des nonces uniques pour chaque action avec `wp_create_nonce()`
- Vérifier que chaque nonce correspond à l'action attendue

**Exemple de code à implémenter** :
```php
// Dans includes/sync.php - fonction manual_sync()
public function manual_sync() {
    // Vérification de sécurité CRITIQUE
    if (!wp_verify_nonce($_POST['feed_favorites_sync_nonce'], 'feed_favorites_manual_sync')) {
        wp_die(__('Security check failed', 'feed-favorites'));
    }
    
    if (!current_user_can('manage_options')) {
        wp_die(__('Insufficient permissions', 'feed-favorites'));
    }
    
    // Code existant de synchronisation...
}
```

#### 1.2 Vérifications de Capacités
**Fichiers à modifier** : `includes/sync.php`, `includes/import.php`, `includes/admin.php`

**Instructions** :
- Ajouter `current_user_can('manage_options')` au début de chaque fonction admin
- Vérifier les capacités AVANT tout traitement de données
- Utiliser `wp_die()` pour arrêter l'exécution si les capacités sont insuffisantes

#### 1.3 Sécurisation des Uploads
**Fichier à modifier** : `includes/import.php`

**Instructions** :
- Ajouter `is_uploaded_file()` pour vérifier que le fichier provient d'un upload légitime
- Améliorer la validation MIME avec des vérifications strictes
- Utiliser `in_array()` avec le troisième paramètre `true` pour une comparaison stricte

### **Phase 2 : Standards WordPress (Après la sécurité)**

#### 2.1 Renommage des Fichiers
**Instructions** :
- Renommer tous les fichiers dans `includes/` selon la convention `class-*.php`
- Faire une sauvegarde avant de commencer
- Renommer un fichier à la fois et tester

**Commandes à exécuter** :
```bash
# Sauvegarde
cp -r includes/ includes_backup/

# Renommage des fichiers
mv includes/validator.php includes/class-validator.php
mv includes/http.php includes/class-http.php
mv includes/import.php includes/class-import.php
mv includes/sync.php includes/class-sync.php
mv includes/admin.php includes/class-admin.php
mv includes/components.php includes/class-components.php
mv includes/config.php includes/class-config.php
mv includes/core.php includes/class-core.php
mv includes/logger.php includes/class-logger.php
mv includes/ajax.php includes/class-ajax.php
```

#### 2.2 Mise à Jour des Includes
**Fichier à modifier** : `includes/core.php`

**Instructions** :
- Mettre à jour tous les `require_once` avec les nouveaux noms de fichiers
- Tester après chaque modification pour éviter les erreurs fatales
- Vérifier que le plugin se charge correctement

---

## 📋 **Checklist d'Implémentation**

### **Jour 1 (Aujourd'hui)**
- [ ] **Sécuriser `includes/sync.php`** - Ajouter nonces et capacités
- [ ] **Sécuriser `includes/import.php`** - Ajouter nonces, capacités et validation uploads
- [ ] **Tester les modifications** - Vérifier que le plugin fonctionne
- [ ] **Relancer le test de sécurité** - Vérifier l'amélioration du score

### **Jour 2**
- [ ] **Sécuriser `includes/ajax.php`** - Ajouter nonces et capacités
- [ ] **Sécuriser `includes/admin.php`** - Ajouter vérifications de capacités
- [ ] **Tester toutes les fonctionnalités** - Validation complète
- [ ] **Relancer le test de sécurité** - Objectif score >= 60/100

### **Jour 3**
- [ ] **Commencer le renommage des fichiers** - Faire la sauvegarde
- [ ] **Renommer 5 fichiers** - Tester après chaque renommage
- [ ] **Mettre à jour les includes** - Modifier `core.php`
- [ ] **Tester la stabilité** - Vérifier qu'il n'y a pas d'erreurs

### **Jour 4**
- [ ] **Finaliser le renommage** - Renommer les 5 fichiers restants
- [ ] **Finaliser les includes** - Mettre à jour tous les chemins
- [ ] **Tests complets** - Validation de toutes les fonctionnalités
- [ ] **Relancer le test de sécurité** - Objectif score >= 70/100

### **Jour 5**
- [ ] **Révision complète** - Vérifier tous les changements
- [ ] **Tests de sécurité finaux** - Validation du score
- [ ] **Tests de stabilité** - Vérifier qu'il n'y a pas de régression
- [ ] **Préparation de la semaine 2** - Planifier les prochaines actions

---

## 🧪 **Tests et Validation**

### **Après Chaque Modification**
1. **Tester le chargement du plugin** :
   ```bash
   # Vérifier qu'il n'y a pas d'erreurs fatales
   php -l includes/core.php
   php -l includes/class-*.php
   ```

2. **Tester la fonctionnalité** :
   - Activer/désactiver le plugin
   - Accéder aux pages d'administration
   - Tester l'import JSON
   - Tester la synchronisation RSS

3. **Vérifier la sécurité** :
   ```bash
   # Relancer le test de sécurité
   php security-test.php
   ```

### **Tests de Sécurité Critiques**
```bash
# Vérifier que le score de sécurité s'améliore
php security-test.php

# Objectif : Score >= 70/100 à la fin de la semaine 1
# Objectif final : Score >= 90/100
```

---

## 🎯 **Objectifs et Critères de Succès**

### **Objectifs de la Semaine 1**
- [ ] **Sécurité** : Score >= 70/100 (actuellement 44/100)
- [ ] **Stabilité** : Plugin se charge sans erreur fatale
- [ ] **Standards** : Tous les fichiers renommés selon la convention WordPress
- [ ] **Fonctionnalité** : Toutes les fonctionnalités principales fonctionnent

### **Critères de Validation**
1. **Test de sécurité** : Score >= 70/100
2. **Test de stabilité** : Plugin se charge et fonctionne
3. **Test de fonctionnalité** : Import JSON et synchronisation RSS fonctionnent
4. **Test de standards** : Fichiers correctement nommés et includes fonctionnels

---

## 🚀 **Lancement des Actions**

**Tu es maintenant prêt à commencer l'implémentation !**

**Commence par** :
1. **Lire et comprendre** ce prompt complet
2. **Faire une sauvegarde** du plugin actuel
3. **Implémenter la sécurité critique** en premier
4. **Tester après chaque modification**
5. **Suivre la checklist** jour par jour

**Rappel important** : La sécurité est la priorité absolue. Ne passez à la phase suivante que lorsque tous les problèmes de sécurité critiques sont résolus.

**Bonne chance pour transformer ce plugin en un outil professionnel et sécurisé !** 🎯

---

## 📁 **Documents de Référence Disponibles**

Tu as accès à ces documents dans le répertoire du plugin :
- `NEXT_ACTIONS.md` - Plan d'actions détaillé
- `ACTION_TRACKER.md` - Suivi des progrès
- `IMPROVEMENT_PLAN.md` - Plan d'amélioration complet
- `FINAL_TEST_SUMMARY.md` - Résumé des tests effectués
- `security-test.php` - Script de test de sécurité
- `simple-test.php` - Tests de base du plugin

**Utilise ces ressources pour guider ton implémentation et valider tes progrès !**

---

## 🔧 **Outils et Commandes Utiles**

### **Tests de Sécurité**
```bash
# Test de sécurité complet
php security-test.php

# Test de base du plugin
php simple-test.php

# Test avancé
php advanced-test.php
```

### **Validation du Code**
```bash
# Vérifier la syntaxe PHP
php -l includes/core.php

# Vérifier les standards WordPress
vendor/bin/phpcs --standard=WordPress includes/

# Corriger automatiquement (attention !)
vendor/bin/phpcbf --standard=WordPress includes/
```

### **Sauvegarde et Restauration**
```bash
# Sauvegarde complète
cp -r . ../feed-favorites-backup-$(date +%Y%m%d)

# Restauration si nécessaire
cp -r ../feed-favorites-backup-YYYYMMDD/* .
```

---

## 📊 **Métriques de Suivi**

### **Score de Sécurité**
- **Actuel** : 44/100
- **Objectif Jour 1** : 50/100
- **Objectif Jour 2** : 60/100
- **Objectif Jour 3** : 65/100
- **Objectif Jour 4** : 70/100
- **Objectif Final** : 90/100

### **Progression des Standards**
- **Fichiers renommés** : 0/10
- **Includes mis à jour** : 0/1
- **Nonces ajoutés** : 0/5
- **Capacités vérifiées** : 0/5

---

**Ce prompt te donne toutes les informations nécessaires pour commencer l'implémentation des améliorations critiques du plugin Feed Favorites. Suis la checklist jour par jour et teste régulièrement pour t'assurer que le plugin reste stable et sécurisé.**
