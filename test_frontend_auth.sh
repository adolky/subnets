#!/bin/bash

echo "================================================"
echo "Test de l'Authentification Frontend vers API"
echo "================================================"
echo ""

# Couleurs
GREEN='\033[0;32m'
RED='\033[0;31m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

API_URL="http://localhost:8080/api.php?action=save"

echo "1. Test SANS credentials (devrait échouer)"
echo "-------------------------------------------"
response=$(curl -s -X POST "$API_URL" \
  -H "Content-Type: application/json" \
  -d '{
    "siteName": "Test Site",
    "adminNumber": "ADM-001",
    "networkAddress": "192.168.100.0/24",
    "maskBits": 24,
    "divisionData": "",
    "vlanIds": "",
    "vlanNames": ""
  }')

if echo "$response" | grep -q "authentication"; then
  echo -e "${GREEN}✓ PASS${NC} - API rejette les requêtes sans credentials"
  echo "  Réponse: $response"
else
  echo -e "${RED}✗ FAIL${NC} - API devrait rejeter les requêtes sans credentials"
  echo "  Réponse: $response"
fi

echo ""
echo "2. Test AVEC mauvais credentials (devrait échouer)"
echo "--------------------------------------------------"
response=$(curl -s -X POST "$API_URL" \
  -H "Content-Type: application/json" \
  -d '{
    "siteName": "Test Site",
    "adminNumber": "ADM-001",
    "networkAddress": "192.168.100.0/24",
    "maskBits": 24,
    "divisionData": "",
    "vlanIds": "",
    "vlanNames": "",
    "username": "wrong",
    "password": "wrong"
  }')

if echo "$response" | grep -q "invalid username or password"; then
  echo -e "${GREEN}✓ PASS${NC} - API rejette les mauvais credentials"
  echo "  Réponse: $response"
else
  echo -e "${RED}✗ FAIL${NC} - API devrait rejeter les mauvais credentials"
  echo "  Réponse: $response"
fi

echo ""
echo "3. Test AVEC bons credentials (devrait réussir)"
echo "-----------------------------------------------"
response=$(curl -s -X POST "$API_URL" \
  -H "Content-Type: application/json" \
  -d '{
    "siteName": "Test Frontend Auth",
    "adminNumber": "ADM-FRONTEND-001",
    "networkAddress": "192.168.100.0/24",
    "maskBits": 24,
    "divisionData": "",
    "vlanIds": "",
    "vlanNames": "",
    "username": "admin",
    "password": "admin123"
  }')

if echo "$response" | grep -q "success.*true"; then
  echo -e "${GREEN}✓ PASS${NC} - Configuration sauvegardée avec succès"
  echo "  Réponse: $response"
else
  echo -e "${RED}✗ FAIL${NC} - La sauvegarde devrait réussir avec les bons credentials"
  echo "  Réponse: $response"
fi

echo ""
echo "4. Vérification dans la base de données"
echo "----------------------------------------"
config_id=$(echo "$response" | grep -o '"id":[0-9]*' | cut -d':' -f2)

if [ -n "$config_id" ]; then
  echo -e "${GREEN}✓ PASS${NC} - Configuration créée avec ID: $config_id"
  
  # Vérifier dans la base de données
  db_check=$(docker exec subnet-mysql mysql -usubnets_user -psubnets_password subnets \
    -e "SELECT id, site_name, admin_number FROM subnet_configurations WHERE id = $config_id;" 2>/dev/null)
  
  if echo "$db_check" | grep -q "Test Frontend Auth"; then
    echo -e "${GREEN}✓ PASS${NC} - Configuration trouvée dans la base de données"
    echo "$db_check"
  else
    echo -e "${RED}✗ FAIL${NC} - Configuration non trouvée dans la base de données"
  fi
else
  echo -e "${YELLOW}⚠ WARN${NC} - Impossible d'extraire l'ID de la configuration"
fi

echo ""
echo "5. Test de MISE À JOUR avec credentials"
echo "----------------------------------------"
if [ -n "$config_id" ]; then
  update_response=$(curl -s -X POST "$API_URL" \
    -H "Content-Type: application/json" \
    -d "{
      \"siteName\": \"Test Frontend Auth UPDATED\",
      \"adminNumber\": \"ADM-FRONTEND-002\",
      \"networkAddress\": \"192.168.100.0/24\",
      \"maskBits\": 24,
      \"divisionData\": \"1010\",
      \"vlanIds\": \"\",
      \"vlanNames\": \"\",
      \"configId\": $config_id,
      \"username\": \"admin\",
      \"password\": \"admin123\"
    }")
  
  if echo "$update_response" | grep -q "success.*true"; then
    echo -e "${GREEN}✓ PASS${NC} - Configuration mise à jour avec succès"
    echo "  Réponse: $update_response"
    
    # Vérifier la mise à jour
    db_check=$(docker exec subnet-mysql mysql -usubnets_user -psubnets_password subnets \
      -e "SELECT admin_number FROM subnet_configurations WHERE id = $config_id;" 2>/dev/null)
    
    if echo "$db_check" | grep -q "ADM-FRONTEND-002"; then
      echo -e "${GREEN}✓ PASS${NC} - Mise à jour confirmée dans la base de données"
    else
      echo -e "${RED}✗ FAIL${NC} - Mise à jour non reflétée dans la base"
    fi
  else
    echo -e "${RED}✗ FAIL${NC} - La mise à jour devrait réussir"
    echo "  Réponse: $update_response"
  fi
else
  echo -e "${YELLOW}⚠ SKIP${NC} - Pas d'ID de configuration pour tester la mise à jour"
fi

echo ""
echo "================================================"
echo "Résumé des Tests"
echo "================================================"
echo ""
echo -e "${YELLOW}IMPORTANT:${NC} Pour tester le frontend complet:"
echo "1. Ouvrir http://localhost:8080/subnets.html dans un navigateur"
echo "2. Rafraîchir la page avec Ctrl+F5 (vider le cache)"
echo "3. Configurer un réseau et cliquer sur 'Save to Database'"
echo "4. Vérifier que le modal d'authentification s'affiche"
echo "5. Utiliser les credentials: admin / admin123"
echo ""
echo "Credentials de test:"
echo "  Username: admin"
echo "  Password: admin123"
echo ""
