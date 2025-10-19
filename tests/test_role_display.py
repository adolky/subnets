#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""
Test de l'affichage du rôle dans la gestion des utilisateurs
"""

from playwright.sync_api import sync_playwright
import time
import sys

def test_role_display():
    with sync_playwright() as p:
        # Lancer le navigateur
        browser = p.chromium.launch(headless=True, slow_mo=500)
        context = browser.new_context(viewport={'width': 1920, 'height': 1080})
        page = context.new_page()
        
        # Activer le logging de la console
        page.on('console', lambda msg: print(f'🖥️  Console: {msg.text}'))
        
        # Gérer les alertes
        alerts = []
        page.on('dialog', lambda dialog: (alerts.append(dialog.message), dialog.accept()))
        
        try:
            print("=" * 70)
            print("TEST: Affichage du rôle dans la gestion utilisateurs")
            print("=" * 70)
            
            # Étape 1: Charger la page
            print("\n📄 Étape 1: Chargement de la page...")
            page.goto('http://10.105.126.7:8080/subnets.html')
            page.wait_for_load_state('networkidle')
            page.screenshot(path='role_test_01_page_loaded.png')
            print("✅ Page chargée")
            
            # Étape 2: Connexion
            print("\n🔐 Étape 2: Connexion en tant qu'admin...")
            page.fill('#username', 'admin')
            page.fill('#password', 'admin123')
            page.screenshot(path='role_test_02_before_login.png')
            page.click('button[type="submit"]')
            time.sleep(1)
            page.screenshot(path='role_test_03_after_login.png')
            print("✅ Connexion réussie")
            
            # Étape 3: Ouvrir la gestion utilisateurs
            print("\n👥 Étape 3: Ouverture de la gestion utilisateurs...")
            admin_btn = page.locator('#adminUserBtn')
            if admin_btn.is_visible():
                print("✅ Bouton visible")
            else:
                print("❌ Bouton non visible")
                return False
            
            admin_btn.click()
            time.sleep(1)
            page.screenshot(path='role_test_04_modal_opened.png')
            print("✅ Modal ouverte")
            
            # Étape 4: Vérifier le contenu du tableau
            print("\n🔍 Étape 4: Vérification du tableau...")
            
            # Attendre que le tableau soit rempli
            page.wait_for_selector('#userListBody tr', timeout=5000)
            
            # Lire toutes les lignes du tableau
            rows = page.locator('#userListBody tr').all()
            print(f"📊 Nombre de lignes: {len(rows)}")
            
            # Analyser chaque ligne
            all_roles_ok = True
            for i, row in enumerate(rows):
                cells = row.locator('td').all()
                if len(cells) >= 3:
                    username = cells[0].inner_text()
                    role = cells[1].inner_text()
                    created = cells[2].inner_text()
                    
                    print(f"  Ligne {i+1}:")
                    print(f"    - Utilisateur: {username}")
                    print(f"    - Rôle: {role}")
                    print(f"    - Créé le: {created}")
                    
                    # Vérifier que le rôle n'est pas "undefined"
                    if role.lower() == "undefined":
                        print(f"    ❌ ERREUR: Rôle est 'undefined'")
                        all_roles_ok = False
                    else:
                        print(f"    ✅ Rôle OK")
            
            # Capture d'écran finale
            page.screenshot(path='role_test_05_table_analyzed.png')
            
            # Résultat final
            print("\n" + "=" * 70)
            if all_roles_ok:
                print("✅ TEST RÉUSSI - Tous les rôles s'affichent correctement !")
                print("=" * 70)
                return True
            else:
                print("❌ TEST ÉCHOUÉ - Certains rôles sont 'undefined'")
                print("=" * 70)
                return False
            
        except Exception as e:
            print(f"\n❌ Erreur: {e}")
            page.screenshot(path='role_test_error.png')
            import traceback
            traceback.print_exc()
            return False
        finally:
            browser.close()

if __name__ == '__main__':
    success = test_role_display()
    sys.exit(0 if success else 1)
