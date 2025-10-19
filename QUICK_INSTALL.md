# 🚀 Installation Rapide - Subnet Calculator

## Installation Automatique en Une Ligne

### Linux / macOS / WSL

```bash
curl -sSL https://raw.githubusercontent.com/adolky/subnets/master/install.sh | bash
```

### Windows PowerShell

```powershell
iwr -useb https://raw.githubusercontent.com/adolky/subnets/master/install.ps1 | iex
```

---

## Ou avec le Repository Cloné

### Linux / macOS / WSL

```bash
git clone https://github.com/adolky/subnets.git
cd subnets
bash install.sh
```

### Windows PowerShell

```powershell
git clone https://github.com/adolky/subnets.git
cd subnets
.\install.ps1
```

---

## Ce que fait le script

1. ✅ Vérifie les prérequis Docker
2. ✅ Demande le choix entre **Staging** ou **Production**
3. ✅ Génère des mots de passe sécurisés pour la base de données
4. ✅ Démarre les conteneurs Docker
5. ✅ Initialise la base de données
6. ✅ Crée l'utilisateur administrateur (interactif)
7. ✅ Affiche les informations de connexion

---

## Accès à l'application

- **Staging** : http://localhost:8080
- **Production** : http://localhost

---

## Prérequis

- Docker 20.10+
- Docker Compose 2.0+

---

## Commandes Utiles Post-Installation

### Staging

```bash
# Voir les logs
docker compose logs -f

# Arrêter
docker compose down

# Redémarrer
docker compose restart

# Statut
docker compose ps
```

### Production

```bash
# Voir les logs
docker compose -f docker-compose.prod.yml logs -f

# Arrêter
docker compose -f docker-compose.prod.yml down

# Redémarrer
docker compose -f docker-compose.prod.yml restart

# Statut
docker compose -f docker-compose.prod.yml ps
```

---

## Documentation Complète

Pour plus de détails, consultez [INSTALLATION.md](INSTALLATION.md)

---

**🎉 Installation terminée en moins de 2 minutes !**
