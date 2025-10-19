# 📥 Fonctionnalité d'Export CSV

## 🎯 Objectif

Permettre aux utilisateurs d'exporter les configurations de sous-réseaux sauvegardées dans un fichier CSV pour :
- **Documentation** : Créer des rapports pour les audits
- **Analyse** : Importer dans Excel/Google Sheets pour analyse
- **Backup** : Sauvegarder les données hors de la base de données
- **Partage** : Distribuer facilement les informations réseau

---

## 🚀 Utilisation

### Accès à la fonctionnalité

Le bouton **📥 Export** est situé dans la barre d'options, à côté des boutons "Save to Database" et "Load from Database".

```
Save options: Bookmark link | Save to Database | Load from Database | 📥 Export
```

### Options d'export

Lorsque vous cliquez sur le bouton **📥 Export**, un menu déroulant apparaît avec deux options :

#### 1️⃣ **🌍 Tous les sous-réseaux**
- Exporte **toutes** les configurations sauvegardées dans la base de données
- Aucun filtre appliqué
- Idéal pour : backup complet, rapports globaux, migration de données

**Exemple :**
```
Résultat : all_subnets_20251017_23_47_37.csv
Contenu : 5 configurations (tous les sites)
```

#### 2️⃣ **🏢 Site actuel**
- Exporte uniquement les configurations du site actuellement chargé
- Filtre par nom de site (Site Name)
- Idéal pour : documentation par site, rapports spécifiques

**Conditions :**
- Un site doit être chargé (via "Load from Database")
- Si aucun site n'est chargé, un message d'erreur s'affiche :
  ```
  Aucun site actuellement chargé. Veuillez d'abord charger une configuration.
  ```

---

## 📊 Format du fichier CSV

### Structure

Le fichier CSV généré contient les colonnes suivantes :

| Colonne | Description | Exemple |
|---------|-------------|---------|
| **Site Name** | Nom du site | `Site A` |
| **Admin Number** | Numéro d'administration | `ADM-001` |
| **Network Address** | Adresse réseau avec CIDR | `10.0.0.0/8` |
| **Subnet** | Sous-réseau | `10.0.0.0/24` |
| **Netmask** | Masque de sous-réseau | `255.255.255.0` |
| **Range** | Plage d'adresses IP | `10.0.0.0 - 10.0.0.255` |
| **Usable IPs** | IPs utilisables | (vide pour l'instant) |
| **Hosts** | Nombre total d'hôtes | `256` |
| **VLAN ID** | Identifiant VLAN | (vide pour l'instant) |
| **VLAN Name** | Nom du VLAN | (vide pour l'instant) |
| **Created At** | Date de création | `2025-10-17 00:03:21` |
| **Updated At** | Date de modification | `2025-10-17 18:47:45` |

### Exemple de contenu

```csv
Site Name,Admin Number,Network Address,Subnet,Netmask,Range,Usable IPs,Hosts,VLAN ID,VLAN Name,Created At,Updated At
Site A,ADM-001,10.0.0.0/8,10.0.0.0/8,255.0.0.0,10.0.0.0 - 10.255.255.255,,16777216,,,2025-10-16 23:08:00,2025-10-17 18:21:11
Site B,ADM-002,172.16.0.0/12,172.16.0.0/12,255.240.0.0,172.16.0.0 - 172.31.255.255,,1048576,,,2025-10-16 23:08:00,2025-10-17 17:27:18
Site C,ADM-003,192.168.0.0/16,192.168.0.0/16,255.255.0.0,192.168.0.0 - 192.168.255.255,,65536,,,2025-10-16 23:08:00,2025-10-17 18:19:55
```

### Caractéristiques

- **Encodage** : UTF-8 avec BOM (pour compatibilité Excel)
- **Délimiteur** : Virgule (`,`)
- **Échappement** : Les valeurs contenant des virgules ou guillemets sont entre guillemets et échappées correctement
- **Nom de fichier** : `{prefix}_{timestamp}.csv`
  - Format timestamp : `AAAAMMJJ_HH_MM_SS`
  - Exemple : `all_subnets_20251017_23_47_37.csv`

---

## 💡 Cas d'utilisation

### Scenario 1 : Audit mensuel
```
Action : Export tous les sous-réseaux
Résultat : all_subnets_20251031_16_30_00.csv
Usage : Ouvrir dans Excel, créer un tableau croisé dynamique par site
```

### Scenario 2 : Documentation d'un site
```
1. Charger la configuration du site "Paris HQ"
2. Export site actuel
3. Résultat : site_Paris_HQ_20251017_14_15_00.csv
4. Usage : Joindre au dossier de documentation du site
```

### Scenario 3 : Migration vers un autre système
```
Action : Export tous les sous-réseaux
Résultat : all_subnets_20251120_10_00_00.csv
Usage : Importer dans le nouveau système de gestion IPAM
```

### Scenario 4 : Comparaison temporelle
```
1. Export tous (1er janvier) → all_subnets_20250101.csv
2. Export tous (1er février) → all_subnets_20250201.csv
3. Comparer les deux fichiers pour voir les changements
```

---

## 🔧 Implémentation technique

### Fichiers modifiés

- **subnets.html** :
  - Ajout du bouton Export avec menu déroulant (ligne ~2268)
  - Fonctions JavaScript d'export (ligne ~1510)
  - Styles CSS pour le menu déroulant (ligne ~2258)

### Fonctions JavaScript principales

1. **`showExportMenu()`** : Affiche/masque le menu déroulant
2. **`exportAllSubnets()`** : Lance l'export de toutes les configurations
3. **`exportCurrentSite()`** : Lance l'export du site actuel
4. **`exportToCSV(configurations, prefix)`** : Génère le fichier CSV
5. **`downloadCSV(content, filename)`** : Télécharge le fichier

### Dépendances

- **API** : Utilise `api.php?action=list` pour récupérer les configurations
- **Browser API** : Blob et URL.createObjectURL pour le téléchargement
- **Compatibilité** : IE10+, tous les navigateurs modernes

---

## ✅ Tests effectués

### Test automatisé

Script : `test_export_feature.py`

**Résultats :**
```
✅ Bouton Export visible
✅ Menu déroulant fonctionne
✅ 2 options disponibles
✅ Export "Tous les sous-réseaux" : 5 configurations exportées
✅ Export "Site actuel" : Validation d'erreur correcte (sans site chargé)
✅ Fichier CSV téléchargé : all_subnets_20251017_23_47_37.csv (764 bytes)
```

### Captures d'écran

- `export_test_01_page_loaded.png` : Page chargée
- `export_test_02_export_button_visible.png` : Bouton visible
- `export_test_03_dropdown_opened.png` : Menu déroulant ouvert
- `export_test_04_export_all_clicked.png` : Export en cours
- `export_test_05_dropdown_reopened.png` : Menu réouvert
- `export_test_06_export_site_clicked.png` : Export site sans chargement

---

## 🚧 Limitations actuelles

1. **Colonnes vides** :
   - "Usable IPs" : Non calculé pour l'instant
   - "VLAN ID" et "VLAN Name" : Données de division non encore parsées

2. **Une ligne par configuration** :
   - Actuellement, chaque configuration génère une seule ligne
   - Les subdivisions VLAN ne sont pas encore exportées individuellement

3. **Pas de sélection manuelle** :
   - Impossibilité de cocher des lignes spécifiques à exporter
   - Export tout ou rien par site

---

## 🔮 Améliorations futures possibles

### Phase 2 (optionnel)
- [ ] Parser les données de division pour exporter chaque sous-réseau VLAN
- [ ] Calculer les IPs utilisables (First IP - Last IP)
- [ ] Ajouter une option "Export VLAN sélectionné"
- [ ] Permettre la sélection manuelle de configurations

### Phase 3 (optionnel)
- [ ] Export en JSON
- [ ] Export en Excel (.xlsx)
- [ ] Export en Markdown
- [ ] Choix des colonnes à exporter

### Phase 4 (optionnel)
- [ ] Export par plage IP
- [ ] Export par date
- [ ] Export planifié automatique
- [ ] Envoi par email

---

## 📝 Changelog

### Version 1.0 - 2025-10-17
- ✨ Ajout du bouton "Export" avec menu déroulant
- ✨ Export de toutes les configurations en CSV
- ✨ Export du site actuel en CSV
- ✨ Format CSV compatible Excel avec UTF-8 BOM
- ✨ Nom de fichier avec timestamp
- ✨ Messages de confirmation
- ✅ Tests automatisés avec Playwright

---

## 🎓 Guide utilisateur rapide

1. **Exporter tout** :
   - Cliquer sur "📥 Export"
   - Choisir "🌍 Tous les sous-réseaux"
   - Le fichier CSV se télécharge automatiquement

2. **Exporter un site** :
   - D'abord : Charger une configuration via "Load from Database"
   - Cliquer sur "📥 Export"
   - Choisir "🏢 Site actuel"
   - Le fichier CSV se télécharge avec le nom du site

3. **Ouvrir le CSV** :
   - Double-cliquer sur le fichier → s'ouvre dans Excel
   - Ou : Importer dans Google Sheets
   - Ou : Ouvrir avec n'importe quel éditeur de texte

---

## 📞 Support

Pour toute question ou amélioration :
- Vérifier les tests : `python3 test_export_feature.py`
- Consulter les captures d'écran dans le dossier
- Vérifier la console du navigateur (F12) pour les erreurs JavaScript

---

**Status** : ✅ Fonctionnalité opérationnelle et testée
**Date** : 17 octobre 2025
**Version** : 1.0
