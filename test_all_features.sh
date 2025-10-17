#!/bin/bash

# Script de test complet de toutes les fonctionnalités
# Couleurs pour l'affichage
GREEN='\033[0;32m'
RED='\033[0;31m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

API_URL="http://10.105.126.7:8080"
SESSION_API="${API_URL}/session_api.php"
API="${API_URL}/api.php"

echo "================================================"
echo "  TEST COMPLET DE L'APPLICATION"
echo "================================================"
echo ""

# Test 1: Page principale
echo "Test 1: Page principale accessible"
echo "-------------------------------------------"
response=$(curl -s -o /dev/null -w "%{http_code}" "${API_URL}/subnets.html")
if [ "$response" = "200" ]; then
  echo -e "${GREEN}✓ PASS${NC} - Page principale: HTTP $response"
else
  echo -e "${RED}✗ FAIL${NC} - Page principale: HTTP $response (attendu: 200)"
fi
echo ""

# Test 2: Session API - Vérification utilisateur non connecté
echo "Test 2: Session API - Utilisateur non connecté"
echo "-------------------------------------------"
response=$(curl -s "${SESSION_API}?action=me")
if echo "$response" | grep -q '"success":false'; then
  echo -e "${GREEN}✓ PASS${NC} - Utilisateur non connecté détecté correctement"
else
  echo -e "${RED}✗ FAIL${NC} - Réponse inattendue"
fi
echo ""

# Test 3: Session API - Login avec mauvais credentials
echo "Test 3: Login avec mauvais credentials"
echo "-------------------------------------------"
response=$(curl -s -X POST "${SESSION_API}?action=login" \
  -H "Content-Type: application/json" \
  -d '{"username":"wrong","password":"wrong"}')
if echo "$response" | grep -q '"success":false'; then
  echo -e "${GREEN}✓ PASS${NC} - Login refusé avec mauvais credentials"
else
  echo -e "${RED}✗ FAIL${NC} - Devrait refuser les mauvais credentials"
fi
echo ""

# Test 4: Session API - Login avec bons credentials
echo "Test 4: Login avec bons credentials"
echo "-------------------------------------------"
response=$(curl -s -c /tmp/cookies.txt -X POST "${SESSION_API}?action=login" \
  -H "Content-Type: application/json" \
  -d '{"username":"admin","password":"admin123"}')
if echo "$response" | grep -q '"success":true'; then
  echo -e "${GREEN}✓ PASS${NC} - Login réussi"
else
  echo -e "${RED}✗ FAIL${NC} - Login devrait réussir avec admin/admin"
fi
echo ""

# Test 5: API - Liste des configurations (avec session)
echo "Test 5: Liste des configurations"
echo "-------------------------------------------"
response=$(curl -s -b /tmp/cookies.txt "${API}?action=list")
if echo "$response" | grep -q '"success":true'; then
  count=$(echo "$response" | grep -o '"id":[0-9]*' | wc -l)
  echo -e "${GREEN}✓ PASS${NC} - Liste récupérée: $count configuration(s)"
else
  echo -e "${RED}✗ FAIL${NC} - Échec de récupération de la liste"
fi
echo ""

# Test 6: API - Sauvegarde configuration
echo "Test 6: Sauvegarde d'une configuration"
echo "-------------------------------------------"
response=$(curl -s -b /tmp/cookies.txt -X POST "${API}?action=save" \
  -H "Content-Type: application/json" \
  -d '{
    "siteName": "Test Complete",
    "adminNumber": "TEST-001",
    "networkAddress": "192.168.100.0/24",
    "maskBits": 24,
    "divisionData": "1.0",
    "vlanIds": "",
    "vlanNames": ""
  }')
if echo "$response" | grep -q '"success":true'; then
  config_id=$(echo "$response" | grep -o '"id":[0-9]*' | cut -d':' -f2)
  echo -e "${GREEN}✓ PASS${NC} - Configuration sauvegardée (ID: $config_id)"
else
  echo -e "${RED}✗ FAIL${NC} - Échec de sauvegarde"
  echo "$response"
fi
echo ""

# Test 7: API - Chargement configuration
if [ -n "$config_id" ]; then
  echo "Test 7: Chargement d'une configuration"
  echo "-------------------------------------------"
  response=$(curl -s -b /tmp/cookies.txt "${API}?action=load&id=${config_id}")
  if echo "$response" | grep -q '"success":true' && echo "$response" | grep -q "Test Complete"; then
    echo -e "${GREEN}✓ PASS${NC} - Configuration chargée correctement"
  else
    echo -e "${RED}✗ FAIL${NC} - Échec de chargement"
  fi
  echo ""
fi

# Test 8: API - Recherche IP
echo "Test 8: Recherche d'IP dans les configurations"
echo "-------------------------------------------"
response=$(curl -s -b /tmp/cookies.txt "${API}?action=searchIP&ip=192.168.100.50")
if echo "$response" | grep -q '"success":true'; then
  echo -e "${GREEN}✓ PASS${NC} - Recherche IP fonctionnelle"
else
  echo -e "${YELLOW}⚠ WARN${NC} - IP non trouvée (normal si pas de configs)"
fi
echo ""

# Test 9: API - Mise à jour configuration
if [ -n "$config_id" ]; then
  echo "Test 9: Mise à jour d'une configuration"
  echo "-------------------------------------------"
  response=$(curl -s -b /tmp/cookies.txt -X POST "${API}?action=save" \
    -H "Content-Type: application/json" \
    -d "{
      \"siteName\": \"Test Complete\",
      \"adminNumber\": \"TEST-002\",
      \"networkAddress\": \"192.168.100.0/24\",
      \"maskBits\": 24,
      \"divisionData\": \"1010\",
      \"vlanIds\": \"\",
      \"vlanNames\": \"\",
      \"configId\": $config_id
    }")
  if echo "$response" | grep -q '"success":true'; then
    echo -e "${GREEN}✓ PASS${NC} - Configuration mise à jour"
  else
    echo -e "${RED}✗ FAIL${NC} - Échec de mise à jour"
  fi
  echo ""
fi

# Test 10: Session API - Liste des utilisateurs (admin uniquement)
echo "Test 10: Liste des utilisateurs"
echo "-------------------------------------------"
response=$(curl -s -b /tmp/cookies.txt "${SESSION_API}?action=list_users")
if echo "$response" | grep -q '"success":true'; then
  echo -e "${GREEN}✓ PASS${NC} - Liste utilisateurs récupérée"
else
  echo -e "${RED}✗ FAIL${NC} - Échec de récupération"
fi
echo ""

# Test 11: API - Suppression configuration
if [ -n "$config_id" ]; then
  echo "Test 11: Suppression d'une configuration"
  echo "-------------------------------------------"
  response=$(curl -s -b /tmp/cookies.txt -X DELETE "${API}?action=delete" \
    -H "Content-Type: application/json" \
    -d "{\"id\": $config_id}")
  if echo "$response" | grep -q '"success":true'; then
    echo -e "${GREEN}✓ PASS${NC} - Configuration supprimée"
  else
    echo -e "${RED}✗ FAIL${NC} - Échec de suppression"
  fi
  echo ""
fi

# Test 12: Session API - Logout
echo "Test 12: Déconnexion"
echo "-------------------------------------------"
response=$(curl -s -b /tmp/cookies.txt -X POST "${SESSION_API}?action=logout")
if echo "$response" | grep -q '"success":true'; then
  echo -e "${GREEN}✓ PASS${NC} - Déconnexion réussie"
else
  echo -e "${RED}✗ FAIL${NC} - Échec de déconnexion"
fi
echo ""

# Test 13: Vérification images GIF
echo "Test 13: Images GIF disponibles"
echo "-------------------------------------------"
response=$(curl -s -o /dev/null -w "%{http_code}" "${API_URL}/img/24.gif")
if [ "$response" = "200" ]; then
  echo -e "${GREEN}✓ PASS${NC} - Images GIF accessibles"
else
  echo -e "${RED}✗ FAIL${NC} - Images GIF non accessibles: HTTP $response"
fi
echo ""

# Nettoyage
rm -f /tmp/cookies.txt

echo "================================================"
echo "  TESTS TERMINÉS"
echo "================================================"
echo ""
echo "Pour tester l'interface web complète:"
echo "1. Ouvrir http://10.105.126.7:8080/subnets.html"
echo "2. Tester manuellement:"
echo "   - Création de subnets (Divide/Join)"
echo "   - Ajout de VLAN IDs"
echo "   - Sauvegarde/Chargement"
echo "   - Recherche IP"
echo ""
