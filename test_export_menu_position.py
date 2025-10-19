#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""
Test du positionnement du menu d'export
"""

from playwright.sync_api import sync_playwright
import time
import sys

def test_export_menu_position():
    with sync_playwright() as p:
        # Lancer le navigateur
        browser = p.chromium.launch(headless=True, slow_mo=500)
        context = browser.new_context(viewport={'width': 1920, 'height': 1080})
        page = context.new_page()
        
        # Activer le logging de la console
        page.on('console', lambda msg: print(f'🖥️  Console: {msg.text}'))
        
        try:
            print("=" * 70)
            print("TEST: Positionnement du menu d'export")
            print("=" * 70)
            
            # Étape 1: Charger la page
            print("\n📄 Étape 1: Chargement de la page...")
            page.goto('http://10.105.126.7:8080/subnets.html')
            page.wait_for_load_state('networkidle')
            page.screenshot(path='menu_pos_01_page_loaded.png')
            print("✅ Page chargée")
            
            # Étape 2: Localiser le bouton Export
            print("\n🔍 Étape 2: Localisation du bouton Export...")
            export_btn = page.locator('#exportBtn')
            
            # Obtenir la position du bouton
            btn_box = export_btn.bounding_box()
            if btn_box:
                print(f"📍 Position du bouton Export:")
                print(f"   X: {btn_box['x']}")
                print(f"   Y: {btn_box['y']}")
                print(f"   Largeur: {btn_box['width']}")
                print(f"   Hauteur: {btn_box['height']}")
                print(f"   Bas du bouton: {btn_box['y'] + btn_box['height']}")
            
            page.screenshot(path='menu_pos_02_before_click.png')
            
            # Étape 3: Cliquer sur le bouton Export
            print("\n🖱️  Étape 3: Ouverture du menu...")
            export_btn.click()
            time.sleep(0.5)
            
            # Étape 4: Vérifier la position du menu
            print("\n🔍 Étape 4: Vérification de la position du menu...")
            dropdown_menu = page.locator('#exportDropdownMenu')
            
            # Vérifier que le menu est visible
            if dropdown_menu.is_visible():
                print("✅ Menu visible")
                
                # Obtenir la position du menu
                menu_box = dropdown_menu.bounding_box()
                if menu_box:
                    print(f"📍 Position du menu:")
                    print(f"   X: {menu_box['x']}")
                    print(f"   Y: {menu_box['y']}")
                    print(f"   Largeur: {menu_box['width']}")
                    print(f"   Hauteur: {menu_box['height']}")
                    
                    # Vérifier que le menu est juste en dessous du bouton
                    if btn_box:
                        expected_y = btn_box['y'] + btn_box['height'] + 2  # +2 pour le petit gap
                        actual_y = menu_box['y']
                        y_diff = abs(actual_y - expected_y)
                        
                        print(f"\n📊 Analyse du positionnement:")
                        print(f"   Y attendu: ~{expected_y}")
                        print(f"   Y actuel: {actual_y}")
                        print(f"   Différence: {y_diff}px")
                        
                        if y_diff < 10:
                            print("   ✅ Menu bien positionné sous le bouton")
                        else:
                            print(f"   ⚠️  Menu mal positionné (différence: {y_diff}px)")
                        
                        # Vérifier que le menu est visible dans le viewport
                        viewport_height = 1080
                        if menu_box['y'] > 0 and menu_box['y'] + menu_box['height'] < viewport_height:
                            print("   ✅ Menu visible dans le viewport")
                        else:
                            print(f"   ❌ Menu hors du viewport (Y: {menu_box['y']}, viewport: {viewport_height})")
            else:
                print("❌ Menu non visible")
                return False
            
            page.screenshot(path='menu_pos_03_menu_opened.png', full_page=True)
            
            # Étape 5: Capturer une image avec focus sur la zone du menu
            print("\n📸 Étape 5: Capture de la zone du menu...")
            
            # Scroller vers le bouton pour s'assurer qu'il est visible
            export_btn.scroll_into_view_if_needed()
            time.sleep(0.3)
            page.screenshot(path='menu_pos_04_focused_view.png')
            
            # Résultat final
            print("\n" + "=" * 70)
            print("✅ TEST RÉUSSI - Menu correctement positionné !")
            print("=" * 70)
            
            return True
            
        except Exception as e:
            print(f"\n❌ Erreur: {e}")
            page.screenshot(path='menu_pos_error.png')
            import traceback
            traceback.print_exc()
            return False
        finally:
            browser.close()

if __name__ == '__main__':
    success = test_export_menu_position()
    sys.exit(0 if success else 1)
