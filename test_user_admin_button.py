#!/usr/bin/env python3
"""
Test spécifique pour le bouton et modal de Gestion des utilisateurs
"""

import asyncio
from playwright.async_api import async_playwright
import os

# Configuration
TARGET_URL = "http://10.105.126.7:8080/subnets.html"
SCREENSHOT_DIR = "playwright_screenshots"
ADMIN_USERNAME = "admin"
ADMIN_PASSWORD = "admin123"

async def main():
    print("🚀 Test du bouton 'Gestion utilisateurs'\n")
    
    # Créer le répertoire pour les captures d'écran
    os.makedirs(SCREENSHOT_DIR, exist_ok=True)
    
    async with async_playwright() as p:
        browser = await p.chromium.launch(headless=True)
        context = await browser.new_context(viewport={'width': 1920, 'height': 1080})
        page = await context.new_page()
        
        # Capturer les erreurs console
        page.on("console", lambda msg: print(f"   🖥️  Console: {msg.text}"))
        page.on("pageerror", lambda err: print(f"   ❌ Error: {err}"))
        
        try:
            # 1. Charger la page
            print("📄 Étape 1: Chargement de la page...")
            await page.goto(TARGET_URL)
            await page.wait_for_load_state("networkidle")
            await page.screenshot(path=f"{SCREENSHOT_DIR}/user_admin_01_initial.png")
            print("   ✅ Page chargée\n")
            
            # 2. Se connecter
            print("🔐 Étape 2: Connexion en tant qu'admin...")
            await page.click("#loginBtn")
            await page.wait_for_selector("#loginModal", state="visible")
            await page.fill("#loginUsername", ADMIN_USERNAME)
            await page.fill("#loginPassword", ADMIN_PASSWORD)
            await page.screenshot(path=f"{SCREENSHOT_DIR}/user_admin_02_login_modal.png")
            
            # Soumettre le formulaire et attendre la réponse
            await page.click("#loginModal button[type='submit']")
            await page.wait_for_timeout(1000)
            await page.screenshot(path=f"{SCREENSHOT_DIR}/user_admin_03_after_login.png")
            
            # Vérifier que la connexion a réussi
            status = await page.text_content("#userStatus")
            print(f"   Statut: {status}")
            
            if "Connecté" not in status:
                print("   ❌ Échec de la connexion")
                return False
            print("   ✅ Connexion réussie\n")
            
            # 3. Vérifier la visibilité du bouton "Gestion utilisateurs"
            print("🔍 Étape 3: Vérification du bouton 'Gestion utilisateurs'...")
            admin_btn = page.locator("#adminUserBtn")
            is_visible = await admin_btn.is_visible()
            
            if not is_visible:
                print("   ❌ Le bouton n'est PAS visible")
                await page.screenshot(path=f"{SCREENSHOT_DIR}/user_admin_04_button_missing.png")
                return False
            
            print("   ✅ Le bouton est visible")
            
            # Obtenir le style du bouton
            style = await admin_btn.get_attribute("style")
            print(f"   Style du bouton: {style}\n")
            
            # 4. Cliquer sur le bouton
            print("🖱️  Étape 4: Clic sur 'Gestion utilisateurs'...")
            await admin_btn.click()
            await page.wait_for_timeout(500)
            await page.screenshot(path=f"{SCREENSHOT_DIR}/user_admin_05_modal_opened.png")
            
            # 5. Vérifier que le modal est visible
            print("📋 Étape 5: Vérification du modal...")
            modal = page.locator("#userAdminModal")
            modal_visible = await modal.is_visible()
            
            if not modal_visible:
                print("   ❌ Le modal n'est PAS visible")
                return False
            
            print("   ✅ Le modal est visible")
            
            # Vérifier le titre du modal
            modal_title = await page.text_content("#userAdminModal .modal-header")
            print(f"   Titre du modal: {modal_title}")
            
            # 6. Vérifier la présence de la table des utilisateurs
            print("\n📊 Étape 6: Vérification de la table des utilisateurs...")
            table = page.locator("#userListTable")
            table_visible = await table.is_visible()
            
            if not table_visible:
                print("   ❌ La table n'est PAS visible")
                return False
            
            print("   ✅ La table est visible")
            
            # Vérifier si la table a un tbody
            tbody = page.locator("#userListTable tbody")
            tbody_exists = await tbody.count() > 0
            print(f"   Tbody existe: {tbody_exists}")
            
            # Attendre que les données se chargent
            await page.wait_for_timeout(1000)
            
            # Compter les lignes de la table
            rows = await tbody.locator("tr").count()
            print(f"   Nombre d'utilisateurs dans la table: {rows}")
            
            if rows > 0:
                print("   ✅ Les utilisateurs sont chargés")
                # Afficher les utilisateurs
                for i in range(rows):
                    row = tbody.locator("tr").nth(i)
                    cells = await row.locator("td").all_text_contents()
                    print(f"      User {i+1}: {cells}")
            else:
                print("   ⚠️  Aucun utilisateur dans la table")
            
            await page.screenshot(path=f"{SCREENSHOT_DIR}/user_admin_06_table_loaded.png")
            
            # 7. Vérifier le formulaire d'ajout
            print("\n➕ Étape 7: Vérification du formulaire d'ajout...")
            add_form = page.locator("#addUserForm")
            form_visible = await add_form.is_visible()
            
            if not form_visible:
                print("   ❌ Le formulaire d'ajout n'est PAS visible")
                return False
            
            print("   ✅ Le formulaire d'ajout est visible")
            
            # Vérifier les champs
            username_field = page.locator("#newUsername")
            password_field = page.locator("#newUserPassword")
            role_field = page.locator("#newUserRole")
            
            username_exists = await username_field.count() > 0
            password_exists = await password_field.count() > 0
            role_exists = await role_field.count() > 0
            
            print(f"   Champ 'Nom d'utilisateur': {'✅' if username_exists else '❌'}")
            print(f"   Champ 'Mot de passe': {'✅' if password_exists else '❌'}")
            print(f"   Champ 'Rôle': {'✅' if role_exists else '❌'}")
            
            # 8. Tester l'ajout d'un utilisateur (simulation)
            print("\n🧪 Étape 8: Test du formulaire d'ajout...")
            test_username = f"testuser_{asyncio.get_event_loop().time():.0f}"
            test_password = "TestPass123"
            
            await username_field.fill(test_username)
            await password_field.fill(test_password)
            await role_field.select_option("user")
            
            await page.screenshot(path=f"{SCREENSHOT_DIR}/user_admin_07_form_filled.png")
            print(f"   Formulaire rempli avec: {test_username}")
            
            # Cliquer sur le bouton Ajouter
            add_button = page.locator("#addUserForm button[type='submit']")
            button_text = await add_button.text_content()
            print(f"   Bouton trouvé: '{button_text}'")
            
            # Attendre les alertes
            page.on("dialog", lambda dialog: asyncio.create_task(dialog.accept()))
            
            await add_button.click()
            await page.wait_for_timeout(1500)
            
            await page.screenshot(path=f"{SCREENSHOT_DIR}/user_admin_08_after_add.png")
            print("   ✅ Bouton 'Ajouter' cliqué\n")
            
            # 9. Vérifier si l'utilisateur a été ajouté
            print("🔍 Étape 9: Vérification de l'ajout...")
            await page.wait_for_timeout(500)
            new_rows = await tbody.locator("tr").count()
            print(f"   Nombre d'utilisateurs après ajout: {new_rows}")
            
            if new_rows > rows:
                print(f"   ✅ Nouvel utilisateur ajouté! ({rows} → {new_rows})")
            else:
                print(f"   ⚠️  Nombre d'utilisateurs inchangé")
            
            # 10. Fermer le modal
            print("\n🚪 Étape 10: Fermeture du modal...")
            close_button = page.locator("#userAdminModal button:has-text('Fermer')")
            await close_button.click()
            await page.wait_for_timeout(500)
            
            modal_still_visible = await modal.is_visible()
            if not modal_still_visible:
                print("   ✅ Modal fermé avec succès")
            else:
                print("   ⚠️  Modal toujours visible")
            
            await page.screenshot(path=f"{SCREENSHOT_DIR}/user_admin_09_modal_closed.png")
            
            print("\n" + "="*70)
            print("✅ TEST RÉUSSI - Le bouton 'Gestion utilisateurs' fonctionne !")
            print("="*70)
            
            # Garder le navigateur ouvert quelques secondes
            await page.wait_for_timeout(2000)
            
            return True
            
        except Exception as e:
            print(f"\n❌ ERREUR: {e}")
            await page.screenshot(path=f"{SCREENSHOT_DIR}/user_admin_ERROR.png")
            import traceback
            traceback.print_exc()
            return False
        finally:
            await browser.close()

if __name__ == "__main__":
    result = asyncio.run(main())
    exit(0 if result else 1)
