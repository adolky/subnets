# 📚 Tutorial Complet - Subnet Calculator

> **Guide pratique pour maîtriser toutes les fonctionnalités de Subnet Calculator**

---

## 📋 Table des Matières

1. [Premiers Pas](#-premiers-pas)
2. [Calculateur de Sous-Réseaux](#-calculateur-de-sous-réseaux)
3. [Gestion VLAN](#-gestion-vlan)
4. [Sauvegarde et Chargement](#-sauvegarde-et-chargement)
5. [Recherche IP](#-recherche-ip)
6. [Export CSV](#-export-csv)
7. [Gestion des Utilisateurs](#-gestion-des-utilisateurs)
8. [Cas d'Usage Pratiques](#-cas-dusage-pratiques)

---

## 🚀 Premiers Pas

### Connexion à l'Application

1. **Ouvrir votre navigateur**
   - URL : `http://localhost:8080` (ou votre domaine)

2. **Page de connexion**
   ```
   ┌──────────────────────────┐
   │  🌐 Subnet Calculator    │
   ├──────────────────────────┤
   │  Username: [___________] │
   │  Password: [___________] │
   │           [  Login  ]    │
   └──────────────────────────┘
   ```

3. **Entrer vos identifiants**
   - Username: `admin` (ou votre compte)
   - Password: `VotreMotDePasse`

4. **Accéder à l'interface principale**

---

## 🧮 Calculateur de Sous-Réseaux

### Exemple 1 : Créer un Réseau Simple

**Objectif :** Créer un réseau `192.168.1.0/24`

1. **Entrer le réseau**
   ```
   Network: [192.168.1.0/24        ] [Update]
   ```

2. **Cliquer "Update"**
   - L'application calcule automatiquement :
     - Netmask : `255.255.255.0`
     - Broadcast : `192.168.1.255`
     - First IP : `192.168.1.0`
     - Last IP : `192.168.1.255`
     - Usable IPs : `192.168.1.1` - `192.168.1.254` (254 hôtes)

3. **Visualisation**
   ```
   ┌────────────────────────────────────────┐
   │ 192.168.1.0/24                         │
   │ 256 hosts (254 usable)                 │
   │ [Divide]                               │
   └────────────────────────────────────────┘
   ```

---

### Exemple 2 : Diviser un Réseau

**Objectif :** Diviser `192.168.1.0/24` en deux sous-réseaux `/25`

1. **Cliquer sur "Divide"** à côté du réseau parent

2. **Résultat automatique :**
   ```
   ┌────────────────────────────────────────┐
   │ 192.168.1.0/24 (256 hosts)             │
   │  ├─ 192.168.1.0/25                     │
   │  │  128 hosts (126 usable)             │
   │  │  [Divide] [Join] [VLAN]             │
   │  │                                      │
   │  └─ 192.168.1.128/25                   │
   │     128 hosts (126 usable)             │
   │     [Divide] [VLAN]                    │
   └────────────────────────────────────────┘
   ```

3. **Continuer la division** si nécessaire :
   - Cliquer "Divide" sur `192.168.1.0/25`
   - Obtenir : `192.168.1.0/26` et `192.168.1.64/26`

---

### Exemple 3 : Fusionner des Sous-Réseaux

**Objectif :** Fusionner `192.168.1.0/25` et `192.168.1.128/25` en `/24`

1. **Cliquer sur "Join"** à côté du premier sous-réseau

2. **Résultat :**
   ```
   ┌────────────────────────────────────────┐
   │ 192.168.1.0/24 (256 hosts)             │
   │ [Divide]                               │
   └────────────────────────────────────────┘
   ```

**⚠️ Note :** Le join n'est possible que si :
- Les deux sous-réseaux sont adjacents
- Ils ont la même taille
- Aucun VLAN n'est configuré

---

### Exemple 4 : Réseau Complexe Multi-Niveaux

**Objectif :** Créer une structure réseau pour une entreprise

**Réseau de départ :** `10.0.0.0/8`

1. **Diviser pour obtenir les sites principaux :**
   ```
   10.0.0.0/8  →  Divide  →  10.0.0.0/9 (Site A)
                             10.128.0.0/9 (Site B)
   ```

2. **Diviser Site A pour les départements :**
   ```
   10.0.0.0/9  →  Divide  →  10.0.0.0/10 (IT)
                             10.64.0.0/10 (HR)
   ```

3. **Diviser IT pour les sous-services :**
   ```
   10.0.0.0/10  →  Divide  →  10.0.0.0/11 (Servers)
                              10.32.0.0/11 (Clients)
   ```

**Structure finale :**
```
10.0.0.0/8
├─ 10.0.0.0/9 (Site A)
│  ├─ 10.0.0.0/10 (IT)
│  │  ├─ 10.0.0.0/11 (Servers)
│  │  └─ 10.32.0.0/11 (Clients)
│  └─ 10.64.0.0/10 (HR)
└─ 10.128.0.0/9 (Site B)
```

---

## 🏷️ Gestion VLAN

### Ajouter un VLAN à un Sous-Réseau

**Objectif :** Assigner VLAN 100 "Production" à `192.168.1.0/25`

1. **Cliquer sur "VLAN"** à côté du sous-réseau

2. **Remplir le formulaire :**
   ```
   ┌──────────────────────────────┐
   │  Configure VLAN              │
   ├──────────────────────────────┤
   │  VLAN ID:   [100        ]    │
   │  VLAN Name: [Production ]    │
   │                              │
   │  [  Save  ]   [  Cancel  ]   │
   └──────────────────────────────┘
   ```

3. **Cliquer "Save"**

4. **Résultat :**
   ```
   192.168.1.0/25 (VLAN 100: Production)
   128 hosts (126 usable)
   [Divide] [Join] [VLAN]
   ```

### Règles VLAN

- **ID valides :** 1 à 4094
- **VLAN 1 :** Réservé (VLAN par défaut)
- **VLAN 1002-1005 :** Réservés (Token Ring, FDDI)
- **VLAN 4095 :** Réservé (extension)

### Exemples de Conventions VLAN

| Plage | Usage Typique |
|-------|---------------|
| 1-99 | VLANs par défaut, management |
| 100-199 | Serveurs, production |
| 200-299 | Utilisateurs (étages, départements) |
| 300-399 | VoIP, téléphonie |
| 400-499 | IoT, building automation |
| 500-599 | Invités, WiFi public |
| 600-999 | Stockage, backup |
| 1000+ | Services spéciaux |

---

## 💾 Sauvegarde et Chargement

### Sauvegarder une Configuration

**Objectif :** Sauvegarder le réseau créé

1. **Remplir les métadonnées :**
   ```
   Site Name:    [Siège Social Paris      ]
   Admin Number: [ADM-2025-001            ]
   Notes:        [Réseau principal datacenter]
   ```

2. **Cliquer "💾 Save to Database"**

3. **Message de confirmation :**
   ```
   ✅ Configuration saved successfully!
   ID: 15
   ```

---

### Charger une Configuration

**Objectif :** Charger une configuration existante

1. **Cliquer sur "Load Config ▼"**

2. **Sélectionner dans la liste :**
   ```
   ┌────────────────────────────────────────┐
   │  Load Configuration                    │
   ├────────────────────────────────────────┤
   │  ◯ Siège Social Paris (10.0.0.0/8)    │
   │     ADM-2025-001                       │
   │     Updated: 2025-10-19 10:30          │
   │                                        │
   │  ◯ Agence Lyon (192.168.0.0/16)       │
   │     ADM-2025-002                       │
   │     Updated: 2025-10-18 15:45          │
   │                                        │
   │  [  Load  ]   [  Cancel  ]             │
   └────────────────────────────────────────┘
   ```

3. **Cliquer "Load"**

4. **La configuration est chargée** avec tous les sous-réseaux et VLANs

---

### Supprimer une Configuration

1. **Charger la configuration** à supprimer
2. **Cliquer sur "🗑️"** (bouton supprimer)
3. **Confirmer la suppression :**
   ```
   ⚠️ Are you sure you want to delete this configuration?
   
   Site: Agence Lyon
   Network: 192.168.0.0/16
   
   [  Yes, Delete  ]   [  Cancel  ]
   ```

---

## 🔍 Recherche IP

### Rechercher à Quel Sous-Réseau Appartient une IP

**Objectif :** Trouver où se trouve l'IP `10.32.15.42`

1. **Cliquer sur "🔍 Search IP"**

2. **Entrer l'IP :**
   ```
   ┌──────────────────────────────┐
   │  IP Address Search           │
   ├──────────────────────────────┤
   │  IP: [10.32.15.42      ]     │
   │                              │
   │  [  Search  ]                │
   └──────────────────────────────┘
   ```

3. **Résultats :**
   ```
   ┌──────────────────────────────────────────┐
   │  Search Results for 10.32.15.42          │
   ├──────────────────────────────────────────┤
   │  ✅ Found in Configuration               │
   │                                          │
   │  Site:        Siège Social Paris         │
   │  Admin:       ADM-2025-001               │
   │  Subnet:      10.32.0.0/11               │
   │  Netmask:     255.224.0.0                │
   │  Range:       10.32.0.1 - 10.63.255.254  │
   │  Usable IPs:  2,097,150                  │
   │  VLAN:        100 (IT Clients)           │
   │                                          │
   │  [  Load Config  ]   [  Close  ]         │
   └──────────────────────────────────────────┘
   ```

4. **Charger la configuration** directement depuis les résultats

---

### Recherche Multi-Configurations

Si une IP est trouvée dans plusieurs configurations, tous les résultats sont affichés :

```
┌──────────────────────────────────────────┐
│  Search Results for 192.168.1.50         │
├──────────────────────────────────────────┤
│  ✅ Found in 2 configurations            │
│                                          │
│  1️⃣  Site: Agence Paris                  │
│      Subnet: 192.168.1.0/25              │
│      VLAN: 200 (Users)                   │
│      [Load]                              │
│                                          │
│  2️⃣  Site: Test Lab                      │
│      Subnet: 192.168.1.0/24              │
│      VLAN: None                          │
│      [Load]                              │
└──────────────────────────────────────────┘
```

---

## 📥 Export CSV

### Export de Tous les Sous-Réseaux

**Objectif :** Exporter toutes les configurations en CSV

1. **Cliquer sur "📥 Export"**

2. **Choisir "🌍 Tous les sous-réseaux"**
   ```
   ┌──────────────────────────────┐
   │  Export Options              │
   ├──────────────────────────────┤
   │  📄 Configuration actuelle    │
   │  🌍 Tous les sous-réseaux    │
   │  🏢 Site spécifique          │
   └──────────────────────────────┘
   ```

3. **Attendre le téléchargement**
   - Message : `Export réussi ! 47 sous-réseau(x) exporté(s)`
   - Fichier : `all_subnets_YYYYMMDD_HHMMSS.csv`

4. **Ouvrir dans Excel/Google Sheets**

---

### Format du Fichier CSV

**Colonnes (15 au total) :**

| # | Colonne | Exemple |
|---|---------|---------|
| 1 | Site Name | `Siège Social Paris` |
| 2 | Admin Number | `ADM-2025-001` |
| 3 | Parent Network | `10.0.0.0/8` |
| 4 | Subnet | `10.32.15.0/24` |
| 5 | Netmask | `255.255.255.0` |
| 6 | First IP | `10.32.15.0` |
| 7 | Last IP | `10.32.15.255` |
| 8 | Usable First | `10.32.15.1` |
| 9 | Usable Last | `10.32.15.254` |
| 10 | Usable Count | `254` |
| 11 | Total Hosts | `256` |
| 12 | VLAN ID | `100` |
| 13 | VLAN Name | `IT Clients` |
| 14 | Created At | `2025-10-19 10:30:00` |
| 15 | Updated At | `2025-10-19 14:20:15` |

---

### Export d'un Site Spécifique

1. **Cliquer "📥 Export"**
2. **Choisir "🏢 Site spécifique"**
3. **Sélectionner le site :**
   ```
   ┌──────────────────────────────┐
   │  Select Site to Export       │
   ├──────────────────────────────┤
   │  ◯ Siège Social Paris        │
   │  ◯ Agence Lyon               │
   │  ◯ Datacenter Marseille      │
   │                              │
   │  [  Export  ]  [  Cancel  ]  │
   └──────────────────────────────┘
   ```

---

### Utilisation du CSV Exporté

#### Analyse avec Excel

```excel
=COUNTIF(B:B, "ADM-2025-*")     ' Nombre de configs par admin
=SUM(J:J)                        ' Total IPs utilisables
=AVERAGE(J:J)                    ' Moyenne IPs par subnet
```

#### Analyse avec Python

```python
import pandas as pd

# Charger le CSV
df = pd.read_csv('all_subnets_20251019_143000.csv')

# Statistiques par site
site_stats = df.groupby('Site Name').agg({
    'Subnet': 'count',
    'Usable Count': 'sum'
})
print(site_stats)

# Trouver les plus gros sous-réseaux
top_subnets = df.nlargest(10, 'Usable Count')
print(top_subnets[['Site Name', 'Subnet', 'Usable Count']])
```

---

## 👥 Gestion des Utilisateurs

### Créer un Nouvel Utilisateur (Admin uniquement)

1. **Cliquer sur l'icône utilisateur** (👤) en haut à droite

2. **Choisir "👥 Manage Users"**

3. **Cliquer "➕ Add User"**

4. **Remplir le formulaire :**
   ```
   ┌──────────────────────────────┐
   │  Create New User             │
   ├──────────────────────────────┤
   │  Username:  [jdupont     ]   │
   │  Password:  [********    ]   │
   │  Role:      [Viewer ▼   ]    │
   │                              │
   │  [  Create  ]  [  Cancel  ]  │
   └──────────────────────────────┘
   ```

5. **Rôles disponibles :**
   - **Admin :** Lecture + Écriture + Gestion utilisateurs
   - **Viewer :** Lecture seule

---

### Modifier un Utilisateur

1. **Dans la liste des utilisateurs**
2. **Cliquer "✏️ Edit"** à côté de l'utilisateur
3. **Modifier le rôle ou réinitialiser le mot de passe**

---

### Changer Votre Mot de Passe

1. **Cliquer sur l'icône utilisateur** (👤)
2. **Choisir "🔑 Change Password"**
3. **Remplir le formulaire :**
   ```
   ┌──────────────────────────────┐
   │  Change Password             │
   ├──────────────────────────────┤
   │  Current:    [********   ]   │
   │  New:        [********   ]   │
   │  Confirm:    [********   ]   │
   │                              │
   │  [  Change  ]  [  Cancel  ]  │
   └──────────────────────────────┘
   ```

---

## 🎯 Cas d'Usage Pratiques

### Cas 1 : Réseau d'Entreprise Multi-Sites

**Contexte :** Entreprise avec 3 sites, besoin de segmentation

**Solution :**

```
172.16.0.0/12 (Réseau global entreprise)
├─ 172.16.0.0/14 (Site Paris)
│  ├─ 172.16.0.0/16 (Serveurs)
│  │  ├─ 172.16.0.0/24 (VLAN 100: DC Principal)
│  │  └─ 172.16.1.0/24 (VLAN 101: DC Backup)
│  └─ 172.17.0.0/16 (Utilisateurs)
│     ├─ 172.17.0.0/24 (VLAN 200: Étage 1)
│     ├─ 172.17.1.0/24 (VLAN 201: Étage 2)
│     └─ 172.17.2.0/24 (VLAN 202: Étage 3)
│
├─ 172.20.0.0/14 (Site Lyon)
│  └─ 172.20.0.0/16 (...)
│
└─ 172.24.0.0/14 (Site Marseille)
   └─ 172.24.0.0/16 (...)
```

**Étapes :**
1. Créer `172.16.0.0/12`
2. Diviser en `/14` pour chaque site
3. Sous-diviser chaque site en départements
4. Assigner les VLANs correspondants
5. Sauvegarder chaque site comme configuration séparée
6. Exporter tout en CSV pour documentation

---

### Cas 2 : Datacenter avec Segmentation Réseau

**Contexte :** Datacenter nécessitant isolation des services

**Solution :**

```
10.0.0.0/16 (Datacenter)
├─ 10.0.0.0/20 (DMZ - VLAN 10)
├─ 10.0.16.0/20 (Frontend - VLAN 20)
├─ 10.0.32.0/20 (Backend - VLAN 30)
├─ 10.0.48.0/20 (Database - VLAN 40)
├─ 10.0.64.0/20 (Storage - VLAN 50)
└─ 10.0.80.0/20 (Management - VLAN 99)
```

---

### Cas 3 : Migration Réseau

**Contexte :** Migration de `192.168.0.0/16` vers `10.0.0.0/8`

**Étapes :**
1. Créer nouvelle config `10.0.0.0/8`
2. Dupliquer structure de `192.168.0.0/16`
3. Exporter les deux en CSV
4. Créer table de mapping :
   ```
   Ancien          →  Nouveau
   192.168.0.0/24  →  10.0.0.0/24
   192.168.1.0/24  →  10.0.1.0/24
   ...
   ```
5. Implémenter migration progressive
6. Garder ancienne config en lecture seule

---

### Cas 4 : Audit IP

**Objectif :** Inventorier toutes les IPs disponibles

**Méthode :**
1. Charger toutes les configurations
2. Exporter en CSV
3. Analyser avec Excel/Python :
   ```python
   total_ips = df['Usable Count'].sum()
   print(f"Total IPs utilisables : {total_ips:,}")
   
   # IPs par site
   ips_by_site = df.groupby('Site Name')['Usable Count'].sum()
   print(ips_by_site)
   ```

---

## 🎓 Bonnes Pratiques

### Nomenclature

✅ **Bonne pratique :**
```
Site Name: "Paris-Siège-Production"
Admin Number: "ADM-2025-PARIS-001"
VLAN Name: "100-Production-Servers"
```

❌ **À éviter :**
```
Site Name: "test"
Admin Number: "123"
VLAN Name: "vlan1"
```

### Sauvegardes

- Exporter CSV **hebdomadaire**
- Versionner les exports (dossier avec date)
- Sauvegarder la base de données MySQL

### Documentation

- Utiliser le champ "Notes" pour documenter
- Créer un fichier Excel récapitulatif
- Maintenir un schéma réseau visuel (Visio/Draw.io)

---

## ❓ Questions Fréquentes

**Q : Puis-je importer un CSV ?**
A : Pas encore, feature en développement pour v2.0

**Q : Puis-je exporter en JSON ?**
A : Pas encore, uniquement CSV actuellement

**Q : Maximum de subdivisions ?**
A : Limité par CIDR (/8 → /30), soit jusqu'à 4,194,304 sous-réseaux théoriques

**Q : Support IPv6 ?**
A : Pas encore, prévu pour v2.0

---

**Félicitations ! Vous maîtrisez maintenant Subnet Calculator ! 🎉**

**Prochaine étape :** [MAINTENANCE.md](MAINTENANCE.md) pour l'administration système
