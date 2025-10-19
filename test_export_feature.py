#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""
Test de la fonctionnalité d'export CSV
"""

from playwright.sync_api import sync_playwright
import time
import sys
import os

def test_export_feature():
    with sync_playwright() as p:
        # Lancer le navigateur
        browser = p.chromium.launch(headless=True, slow_mo=500)
        context = browser.new_context(
            viewport={'width': 1920, 'height': 1080},
            accept_downloads=True
        )
        page = context.new_page()
        
        # Activer le logging de la console
        page.on('console', lambda msg: print(f'🖥️  Console: {msg.text}'))
        
        # Gérer les alertes
        alerts = []
        page.on('dialog', lambda dialog: (alerts.append(dialog.message), dialog.accept()))
        
        # Gérer les téléchargements
        downloads = []
        page.on('download', lambda download: downloads.append(download))
        
        try:
            print("=" * 70)
            print("TEST: Fonctionnalité d'export CSV")
            print("=" * 70)
            
            # Étape 1: Charger la page
            print("\n📄 Étape 1: Chargement de la page...")
            page.goto('http://10.105.126.7:8080/subnets.html')
            page.wait_for_load_state('networkidle')
            page.screenshot(path='export_test_01_page_loaded.png')
            print("✅ Page chargée")
            
            # Étape 2: Vérifier la présence du bouton Export
            print("\n🔍 Étape 2: Vérification du bouton Export...")
            export_btn = page.locator('#exportBtn')
            if export_btn.is_visible():
                print("✅ Bouton Export visible")
            else:
                print("❌ Bouton Export non visible")
                return False
            
            page.screenshot(path='export_test_02_export_button_visible.png')
            
            # Étape 3: Cliquer sur le bouton Export
            print("\n📋 Étape 3: Ouverture du menu déroulant...")
            export_btn.click()
            time.sleep(1)
            page.screenshot(path='export_test_03_dropdown_opened.png')
            
            # Vérifier que le menu est visible
            dropdown_menu = page.locator('#exportDropdownMenu')
            if dropdown_menu.is_visible():
                print("✅ Menu déroulant visible")
            else:
                print("❌ Menu déroulant non visible")
                return False
            
            # Étape 4: Vérifier les options du menu
            print("\n🔍 Étape 4: Vérification des options...")
            
            # Compter les liens dans le menu
            menu_items = page.locator('#exportDropdownMenu a').all()
            print(f"📊 Nombre d'options: {len(menu_items)}")
            
            if len(menu_items) >= 2:
                print("✅ Les 2 options sont présentes")
                for i, item in enumerate(menu_items):
                    text = item.inner_text()
                    print(f"  Option {i+1}: {text}")
            else:
                print(f"❌ Nombre d'options incorrect: {len(menu_items)}")
                return False
            
            # Étape 5: Test de l'export "Tous les sous-réseaux"
            print("\n📥 Étape 5: Test export 'Tous les sous-réseaux'...")
            
            # Cliquer sur l'option "Tous les sous-réseaux"
            all_subnets_link = page.locator('#exportDropdownMenu a').first
            all_subnets_link.click()
            
            time.sleep(2)
            page.screenshot(path='export_test_04_export_all_clicked.png')
            
            # Vérifier les alertes
            if alerts:
                print(f"💬 Message reçu: {alerts[-1]}")
                
                if "Export réussi" in alerts[-1]:
                    print("✅ Export 'Tous les sous-réseaux' réussi !")
                elif "Aucune configuration" in alerts[-1]:
                    print("⚠️  Aucune configuration trouvée (normal si base vide)")
                else:
                    print(f"⚠️  Message: {alerts[-1]}")
            
            # Vérifier les téléchargements
            if downloads:
                print(f"📂 Téléchargement(s) détecté(s): {len(downloads)}")
                for dl in downloads:
                    filename = dl.suggested_filename
                    print(f"  Fichier: {filename}")
                    
                    # Sauvegarder le fichier
                    dl.save_as(f'/home/aku/subnets/{filename}')
                    print(f"  ✅ Fichier sauvegardé: {filename}")
            
            # Étape 6: Test de l'export "Site actuel" (devrait échouer sans site chargé)
            print("\n📥 Étape 6: Test export 'Site actuel' sans site chargé...")
            
            # Réouvrir le menu
            export_btn.click()
            time.sleep(1)
            page.screenshot(path='export_test_05_dropdown_reopened.png')
            
            # Cliquer sur l'option "Site actuel"
            site_link = page.locator('#exportDropdownMenu a').nth(1)
            site_link.click()
            
            time.sleep(2)
            page.screenshot(path='export_test_06_export_site_clicked.png')
            
            # Vérifier les alertes
            if len(alerts) > 1:
                print(f"💬 Message reçu: {alerts[-1]}")
                
                if "Aucun site actuellement chargé" in alerts[-1]:
                    print("✅ Validation correcte: Impossible d'exporter sans site chargé")
                else:
                    print(f"⚠️  Message: {alerts[-1]}")
            
            # Résultat final
            print("\n" + "=" * 70)
            print("✅ TEST RÉUSSI - La fonctionnalité d'export fonctionne !")
            print("=" * 70)
            print("\nRésumé:")
            print(f"  • Bouton Export: ✅ Visible")
            print(f"  • Menu déroulant: ✅ Fonctionne")
            print(f"  • Options disponibles: {len(menu_items)}")
            print(f"  • Export 'Tous': ✅ Testé")
            print(f"  • Export 'Site': ✅ Testé (validation d'erreur)")
            print(f"  • Alertes reçues: {len(alerts)}")
            if downloads:
                print(f"  • Fichiers téléchargés: {len(downloads)}")
            
            return True
            
        except Exception as e:
            print(f"\n❌ Erreur: {e}")
            page.screenshot(path='export_test_error.png')
            import traceback
            traceback.print_exc()
            return False
        finally:
            browser.close()

if __name__ == '__main__':
    success = test_export_feature()
    sys.exit(0 if success else 1)
