#!/usr/bin/env python3
"""
Test spécifique pour le changement de mot de passe
"""

import asyncio
from playwright.async_api import async_playwright
import os

# Configuration
TARGET_URL = "http://10.105.126.7:8080/subnets.html"
SCREENSHOT_DIR = "playwright_screenshots"
ADMIN_USERNAME = "admin"
ADMIN_PASSWORD = "admin123"
NEW_PASSWORD = "NewPass123"

async def main():
    print("🔐 Test du changement de mot de passe\n")
    
    # Créer le répertoire pour les captures d'écran
    os.makedirs(SCREENSHOT_DIR, exist_ok=True)
    
    async with async_playwright() as p:
        browser = await p.chromium.launch(headless=True)
        context = await browser.new_context(viewport={'width': 1920, 'height': 1080})
        page = await context.new_page()
        
        # Capturer les erreurs console
        page.on("console", lambda msg: print(f"   🖥️  Console: {msg.text}"))
        page.on("pageerror", lambda err: print(f"   ❌ Error: {err}"))
        
        # Gérer les alertes
        alerts_received = []
        async def handle_dialog(dialog):
            alerts_received.append(dialog.message)
            print(f"   💬 Alert: {dialog.message}")
            await dialog.accept()
        page.on("dialog", handle_dialog)
        
        try:
            # 1. Charger la page
            print("📄 Étape 1: Chargement de la page...")
            await page.goto(TARGET_URL)
            await page.wait_for_load_state("networkidle")
            await page.screenshot(path=f"{SCREENSHOT_DIR}/changepwd_01_initial.png")
            print("   ✅ Page chargée\n")
            
            # 2. Se connecter
            print("🔐 Étape 2: Connexion en tant qu'admin...")
            await page.click("#loginBtn")
            await page.wait_for_selector("#loginModal", state="visible")
            await page.fill("#loginUsername", ADMIN_USERNAME)
            await page.fill("#loginPassword", ADMIN_PASSWORD)
            await page.screenshot(path=f"{SCREENSHOT_DIR}/changepwd_02_login_modal.png")
            
            # Soumettre le formulaire
            await page.click("#loginModal button[type='submit']")
            await page.wait_for_timeout(1000)
            await page.screenshot(path=f"{SCREENSHOT_DIR}/changepwd_03_after_login.png")
            
            # Vérifier que la connexion a réussi
            status = await page.text_content("#userStatus")
            print(f"   Statut: {status}")
            
            if "Connecté" not in status:
                print("   ❌ Échec de la connexion")
                return False
            print("   ✅ Connexion réussie\n")
            
            # 3. Vérifier le bouton "Changer mot de passe"
            print("🔍 Étape 3: Vérification du bouton 'Changer mot de passe'...")
            pwd_btn = page.locator("#changePwdBtn")
            is_visible = await pwd_btn.is_visible()
            
            if not is_visible:
                print("   ❌ Le bouton n'est PAS visible")
                await page.screenshot(path=f"{SCREENSHOT_DIR}/changepwd_04_button_missing.png")
                return False
            
            print("   ✅ Le bouton est visible\n")
            
            # 4. Ouvrir le modal de changement de mot de passe
            print("🖱️  Étape 4: Clic sur 'Changer mot de passe'...")
            await pwd_btn.click()
            await page.wait_for_timeout(500)
            await page.screenshot(path=f"{SCREENSHOT_DIR}/changepwd_05_modal_opened.png")
            
            # Vérifier que le modal est visible
            modal = page.locator("#changePwdModal")
            modal_visible = await modal.is_visible()
            
            if not modal_visible:
                print("   ❌ Le modal n'est PAS visible")
                return False
            
            print("   ✅ Le modal est visible")
            
            # Vérifier le titre
            modal_title = await page.text_content("#changePwdModal .modal-header")
            print(f"   Titre du modal: {modal_title}\n")
            
            # 5. Vérifier les champs du formulaire
            print("📋 Étape 5: Vérification des champs du formulaire...")
            current_pwd = page.locator("#currentPassword")
            new_pwd = page.locator("#newPassword")
            confirm_pwd = page.locator("#confirmPassword")
            
            current_exists = await current_pwd.count() > 0
            new_exists = await new_pwd.count() > 0
            confirm_exists = await confirm_pwd.count() > 0
            
            print(f"   Champ 'Ancien mot de passe': {'✅' if current_exists else '❌'}")
            print(f"   Champ 'Nouveau mot de passe': {'✅' if new_exists else '❌'}")
            print(f"   Champ 'Confirmer nouveau mot de passe': {'✅' if confirm_exists else '❌'}")
            
            if not (current_exists and new_exists and confirm_exists):
                print("   ❌ Certains champs sont manquants")
                return False
            print("   ✅ Tous les champs sont présents\n")
            
            # 6. Remplir le formulaire avec un mauvais ancien mot de passe (test négatif)
            print("🧪 Étape 6: Test avec mauvais ancien mot de passe...")
            await current_pwd.fill("WrongPassword")
            await new_pwd.fill(NEW_PASSWORD)
            await confirm_pwd.fill(NEW_PASSWORD)
            await page.screenshot(path=f"{SCREENSHOT_DIR}/changepwd_06_wrong_current_pwd.png")
            
            # Chercher le bouton submit
            submit_btn = page.locator("#changePwdForm button[type='submit']")
            button_text = await submit_btn.text_content()
            print(f"   Bouton trouvé: '{button_text}'")
            
            # Cliquer sur le bouton
            await submit_btn.click()
            await page.wait_for_timeout(1500)
            
            if alerts_received:
                print(f"   ⚠️  Erreur attendue: {alerts_received[-1]}")
                alerts_received.clear()
            else:
                print("   ⚠️  Aucune alerte reçue (peut-être une erreur silencieuse)")
            
            await page.screenshot(path=f"{SCREENSHOT_DIR}/changepwd_07_after_wrong_pwd.png")
            print("   ✅ Test négatif complété\n")
            
            # 7. Remplir le formulaire avec le bon mot de passe
            print("✅ Étape 7: Test avec bon mot de passe...")
            await current_pwd.fill(ADMIN_PASSWORD)
            await new_pwd.fill(NEW_PASSWORD)
            await confirm_pwd.fill(NEW_PASSWORD)
            await page.screenshot(path=f"{SCREENSHOT_DIR}/changepwd_08_correct_filled.png")
            
            # Cliquer sur le bouton
            await submit_btn.click()
            await page.wait_for_timeout(1500)
            
            if alerts_received:
                alert_msg = alerts_received[-1]
                print(f"   💬 Message reçu: {alert_msg}")
                
                if "successfully" in alert_msg.lower() or "succès" in alert_msg.lower():
                    print("   ✅ Changement de mot de passe réussi!")
                else:
                    print(f"   ⚠️  Message inattendu: {alert_msg}")
            else:
                print("   ⚠️  Aucune alerte reçue")
            
            await page.screenshot(path=f"{SCREENSHOT_DIR}/changepwd_09_after_change.png")
            
            # Vérifier si le modal est fermé
            modal_still_visible = await modal.is_visible()
            if not modal_still_visible:
                print("   ✅ Modal fermé automatiquement\n")
            else:
                print("   ⚠️  Modal toujours ouvert\n")
            
            # 8. Test de reconnexion avec le nouveau mot de passe
            print("🔄 Étape 8: Test de reconnexion avec nouveau mot de passe...")
            
            # Se déconnecter
            logout_btn = page.locator("#logoutBtn")
            if await logout_btn.is_visible():
                await logout_btn.click()
                await page.wait_for_timeout(500)
                print("   ✅ Déconnexion effectuée")
            
            await page.screenshot(path=f"{SCREENSHOT_DIR}/changepwd_10_logged_out.png")
            
            # Se reconnecter avec le nouveau mot de passe
            await page.click("#loginBtn")
            await page.wait_for_selector("#loginModal", state="visible")
            await page.fill("#loginUsername", ADMIN_USERNAME)
            await page.fill("#loginPassword", NEW_PASSWORD)
            await page.screenshot(path=f"{SCREENSHOT_DIR}/changepwd_11_relogin_new_pwd.png")
            
            await page.click("#loginModal button[type='submit']")
            await page.wait_for_timeout(1000)
            
            # Vérifier la reconnexion
            new_status = await page.text_content("#userStatus")
            print(f"   Nouveau statut: {new_status}")
            
            if "Connecté" in new_status:
                print("   ✅ Reconnexion réussie avec nouveau mot de passe!")
                await page.screenshot(path=f"{SCREENSHOT_DIR}/changepwd_12_relogin_success.png")
            else:
                print("   ❌ Échec de reconnexion avec nouveau mot de passe")
                await page.screenshot(path=f"{SCREENSHOT_DIR}/changepwd_12_relogin_failed.png")
                return False
            
            # 9. Remettre l'ancien mot de passe pour les prochains tests
            print("\n🔄 Étape 9: Restauration de l'ancien mot de passe...")
            await page.click("#changePwdBtn")
            await page.wait_for_timeout(500)
            
            await current_pwd.fill(NEW_PASSWORD)
            await new_pwd.fill(ADMIN_PASSWORD)
            await confirm_pwd.fill(ADMIN_PASSWORD)
            
            await submit_btn.click()
            await page.wait_for_timeout(1500)
            
            if alerts_received:
                print(f"   💬 {alerts_received[-1]}")
                if "successfully" in alerts_received[-1].lower():
                    print("   ✅ Mot de passe restauré!")
            
            await page.screenshot(path=f"{SCREENSHOT_DIR}/changepwd_13_restored.png")
            
            print("\n" + "="*70)
            print("✅ TEST RÉUSSI - Le changement de mot de passe fonctionne !")
            print("="*70)
            
            # Garder le navigateur ouvert quelques secondes
            await page.wait_for_timeout(2000)
            
            return True
            
        except Exception as e:
            print(f"\n❌ ERREUR: {e}")
            await page.screenshot(path=f"{SCREENSHOT_DIR}/changepwd_ERROR.png")
            import traceback
            traceback.print_exc()
            return False
        finally:
            await browser.close()

if __name__ == "__main__":
    result = asyncio.run(main())
    exit(0 if result else 1)
