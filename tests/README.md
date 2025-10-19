# 🧪 Tests - Subnet Calculator

> **Suite de tests automatisés pour valider toutes les fonctionnalités**

---

## 📋 Vue d'Ensemble

Cette suite de tests utilise **Playwright** (Python) pour valider automatiquement :
- ✅ Export CSV détaillé (47 sous-réseaux)
- ✅ Positionnement précis du menu export (0.0px)
- ✅ Authentification utilisateur
- ✅ Changement de mot de passe
- ✅ Gestion des rôles (Admin/Viewer)
- ✅ Interface d'administration

---

## 🚀 Installation

### Prérequis

- Python 3.8+
- pip
- Application Subnet Calculator en cours d'exécution

### Installation des Dépendances

```bash
# Installer Playwright et dépendances
pip install playwright pytest requests

# Installer les navigateurs
playwright install

# Installer les dépendances système
playwright install-deps
```

---

## 🎯 Tests Disponibles

### 1. Test Export Détaillé
**Fichier:** `test_export_detailed.py`

**Objectif:** Valider que l'export CSV exporte TOUS les sous-réseaux (pas seulement les parents)

**Ce qui est testé:**
- Comptage des configurations en base de données
- Déclenchement de l'export via l'interface
- Téléchargement du fichier CSV
- Validation de la structure (15 colonnes)
- Vérification de la complétude des données (100%)
- Analyse par site

**Résultats attendus:**
```
✅ 47 sous-réseaux exportés (vs 5 configurations)
✅ Amélioration de 840% en quantité de données
✅ Toutes les colonnes présentes et renseignées à 100%
✅ Détail par site validé
```

**Exécution:**
```bash
python test_export_detailed.py
```

---

### 2. Test Positionnement Menu Export
**Fichier:** `test_export_menu_position.py`

**Objectif:** Vérifier que le menu d'export s'affiche exactement sous le bouton (ergonomie)

**Ce qui est testé:**
- Position du bouton Export
- Position du menu déroulant
- Calcul de la différence (doit être 2px exactement)

**Résultats attendus:**
```
✅ Menu positionné à 2px sous le bouton
✅ Différence: 0.0px (précision parfaite)
```

**Exécution:**
```bash
python test_export_menu_position.py
```

---

### 3. Test Authentification
**Fichier:** `test_authentication.sh`

**Objectif:** Valider le système de connexion/déconnexion

**Ce qui est testé:**
- Affichage de la page de login
- Connexion avec identifiants valides
- Connexion avec identifiants invalides
- Déconnexion

**Exécution:**
```bash
./test_authentication.sh
```

---

### 4. Test Changement de Mot de Passe
**Fichier:** `test_change_password.py`

**Objectif:** Valider la fonctionnalité de changement de mot de passe

**Ce qui est testé:**
- Accès à l'interface de changement
- Validation mot de passe actuel incorrect
- Changement avec nouveau mot de passe
- Restauration du mot de passe original

**Exécution:**
```bash
python test_change_password.py
```

---

### 5. Test Gestion des Rôles
**Fichier:** `test_role_display.py`

**Objectif:** Vérifier l'affichage et la gestion des rôles utilisateurs

**Ce qui est testé:**
- Affichage du rôle dans l'interface
- Restrictions selon le rôle (Admin vs Viewer)

**Exécution:**
```bash
python test_role_display.py
```

---

### 6. Test Administration Utilisateurs
**Fichier:** `test_user_admin_button.py`

**Objectif:** Valider l'interface d'administration des utilisateurs

**Ce qui est testé:**
- Accès à la liste des utilisateurs
- Création de nouveaux utilisateurs
- Modification de rôles
- Suppression d'utilisateurs

**Exécution:**
```bash
python test_user_admin_button.py
```

---

### 7. Tests Visuels
**Fichier:** `visual_test.py`

**Objectif:** Capturer l'état visuel de l'application pour détecter les régressions

**Ce qui est testé:**
- Rendu de la page principale
- États des composants
- Capture d'écran pour comparaison

**Exécution:**
```bash
python visual_test.py
```

---

## 🏃 Exécution des Tests

### Test Individuel

```bash
# Export détaillé
python test_export_detailed.py

# Positionnement menu
python test_export_menu_position.py

# Authentification
./test_authentication.sh
```

---

### Tous les Tests

```bash
# Utiliser le script de test complet
./test_all_features.sh

# Ou exécuter manuellement
python test_export_detailed.py
python test_export_menu_position.py
python test_change_password.py
python test_role_display.py
python test_user_admin_button.py
python visual_test.py
```

---

## 📊 Résultats des Tests

### Test Export Détaillé

**Dernière exécution:** 2025-10-19

```
✅ TEST RÉUSSI - Export détaillé fonctionne correctement !

📊 Nombre de configurations en base: 5
💬 Message reçu: Export réussi ! 47 sous-réseau(x) exporté(s)
📊 Nombre de lignes dans le CSV: 47

Détail par site:
  🏢 Site A: 24 sous-réseaux
  🏢 TestAuth: 5 sous-réseaux
  🏢 Site C: 15 sous-réseaux
  🏢 Site B: 2 sous-réseaux
  🏢 TestFinal: 1 sous-réseau

✅ VALIDATIONS:
  ✅ 47 lignes exportées >= 5 configurations
  ✅ Subnets renseignés: 47/47 (100%)
  ✅ Netmasks renseignés: 47/47 (100%)
  ✅ First IPs renseignés: 47/47 (100%)
  ✅ Usable Counts renseignés: 47/47 (100%)
  ✅ Timestamps: 47 created, 47 updated

Résumé:
  • Configurations en base: 5
  • Lignes exportées: 47
  • Sites distincts: 5
  • Amélioration: +840% de données
```

---

### Test Positionnement Menu

**Dernière exécution:** 2025-10-19

```
✅ TEST RÉUSSI - Menu export positionné correctement !

Button Position:
  Top: 301.15625
  Bottom: 315.15625

Menu Position:
  Top: 317.15625

Expected Menu Top: 317.15625 (button bottom + 2px gap)
Actual Menu Top: 317.15625
Difference: 0.0 pixels

✅ Menu is positioned exactly 2px below the button!
```

---

## 🐛 Captures d'Écran

Les tests génèrent des captures d'écran automatiquement :

```
screenshots/
├── test_runs/
│   ├── export_detailed_01_loaded.png
│   ├── export_detailed_02_menu_opened.png
│   ├── export_detailed_03_export_clicked.png
│   ├── menu_pos_01_page_loaded.png
│   ├── menu_pos_02_before_click.png
│   ├── menu_pos_03_menu_opened.png
│   └── menu_pos_04_focused_view.png
└── playwright_screenshots/
    ├── changepwd_*.png
    ├── user_admin_*.png
    └── ...
```

---

## 📝 Configuration

### Variables d'Environnement

```bash
# URL de l'application (défaut: http://10.105.126.7:8080)
export APP_URL=http://localhost:8080

# Identifiants de test
export TEST_USERNAME=admin
export TEST_PASSWORD=password

# Mode headless (défaut: true)
export PLAYWRIGHT_HEADLESS=false  # Pour voir le navigateur
```

---

### Configuration Playwright

**pytest.ini:**

```ini
[pytest]
testpaths = tests
python_files = test_*.py
python_classes = Test*
python_functions = test_*
```

---

## 🔍 Débogage

### Mode Interactif (avec navigateur visible)

```python
# Dans votre test, modifier:
browser = playwright.chromium.launch(headless=False, slow_mo=1000)
```

### Capture d'Écran en Cas d'Erreur

```python
try:
    # ... test code ...
except Exception as e:
    page.screenshot(path="error_screenshot.png")
    raise
```

### Logs Détaillés

```bash
# Activer les logs Playwright
DEBUG=pw:api python test_export_detailed.py
```

---

## 📈 Métriques de Couverture

| Fonctionnalité | Test | Statut |
|----------------|------|--------|
| Export CSV Détaillé | ✅ | 100% |
| Menu Positioning | ✅ | 100% |
| Authentification | ✅ | 100% |
| Changement Mot de Passe | ✅ | 100% |
| Gestion Rôles | ✅ | 100% |
| Admin Utilisateurs | ✅ | 100% |
| Recherche IP | 🚧 | À implémenter |
| Sauvegarde Config | 🚧 | À implémenter |
| Division Subnet | 🚧 | À implémenter |
| Gestion VLAN | 🚧 | À implémenter |

**Couverture actuelle:** 6/10 fonctionnalités (60%)

---

## 🚀 CI/CD Integration

### GitHub Actions

**`.github/workflows/tests.yml`:**

```yaml
name: Tests

on: [push, pull_request]

jobs:
  test:
    runs-on: ubuntu-latest
    
    steps:
    - uses: actions/checkout@v3
    
    - name: Set up Python
      uses: actions/setup-python@v4
      with:
        python-version: '3.10'
    
    - name: Install dependencies
      run: |
        pip install playwright pytest requests
        playwright install --with-deps
    
    - name: Start application
      run: |
        docker-compose up -d
        sleep 10
    
    - name: Run tests
      run: |
        cd tests
        python test_export_detailed.py
        python test_export_menu_position.py
    
    - name: Upload screenshots
      if: always()
      uses: actions/upload-artifact@v3
      with:
        name: test-screenshots
        path: screenshots/
```

---

## 📚 Ressources

- [Playwright Documentation](https://playwright.dev/python/)
- [Pytest Documentation](https://docs.pytest.org/)
- [CSV Module Python](https://docs.python.org/3/library/csv.html)

---

## 🤝 Contribution

Pour ajouter un nouveau test :

1. Créer `test_nouvelle_fonctionnalite.py`
2. Suivre la structure des tests existants
3. Documenter le test dans ce README
4. Ajouter au script `test_all_features.sh`
5. Tester localement avant commit

---

## 📞 Support

**Problèmes avec les tests ?**
- 📖 Vérifier la documentation Playwright
- 🐛 Créer une issue GitHub
- 💬 Consulter les logs détaillés

---

**✅ Suite de tests complète et validée !**

**Dernière mise à jour:** 2025-10-19
