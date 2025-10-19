#!/usr/bin/env python3
"""
Test Spécifique : Validation du Bouton "Gestion utilisateurs"
=============================================================
Ce script valide que le bouton admin apparaît correctement après connexion
"""

import asyncio
from playwright.async_api import async_playwright
import sys
from datetime import datetime

# Configuration
BASE_URL = "http://10.105.126.7:8080"
SUBNETS_URL = f"{BASE_URL}/subnets.html"
USERNAME = "admin"
PASSWORD = "admin123"

async def main():
    print("=" * 80)
    print("🔍 TEST SPÉCIFIQUE : Validation du Bouton 'Gestion utilisateurs'")
    print("=" * 80)
    print(f"\nURL: {SUBNETS_URL}")
    print(f"User: {USERNAME}")
    print(f"Date: {datetime.now().strftime('%Y-%m-%d %H:%M:%S')}\n")
    
    async with async_playwright() as p:
        # Lancement du navigateur en mode headless
        browser = await p.chromium.launch(headless=True)
        context = await browser.new_context(
            viewport={'width': 1920, 'height': 1080}
        )
        page = await context.new_page()
        
        # Capture des erreurs console
        errors = []
        page.on("console", lambda msg: print(f"   🖥️  Console [{msg.type}]: {msg.text}"))
        page.on("pageerror", lambda err: errors.append(str(err)))
        
        try:
            # ========================================
            # ÉTAPE 1: Chargement de la page
            # ========================================
            print("📝 ÉTAPE 1: Chargement de la page...")
            await page.goto(SUBNETS_URL, wait_until="networkidle")
            await page.wait_for_timeout(1000)
            
            # Vérifier état initial
            initial_status = await page.locator("#userStatus").text_content()
            login_btn_visible = await page.locator("#loginBtn").is_visible()
            admin_btn_initial = await page.locator("#adminUserBtn").is_visible()
            
            print(f"   ✓ Page chargée")
            print(f"   ✓ Statut initial: '{initial_status}'")
            print(f"   ✓ Bouton login visible: {login_btn_visible}")
            print(f"   ✓ Bouton admin visible (avant login): {admin_btn_initial}")
            
            if admin_btn_initial:
                print("   ⚠️  ATTENTION: Le bouton admin est visible AVANT la connexion!")
            
            # ========================================
            # ÉTAPE 2: Ouverture du modal de connexion
            # ========================================
            print("\n📝 ÉTAPE 2: Ouverture du modal de connexion...")
            await page.click("#loginBtn")
            await page.wait_for_timeout(500)
            
            modal_visible = await page.locator("#loginModal").is_visible()
            print(f"   ✓ Modal de connexion visible: {modal_visible}")
            
            # ========================================
            # ÉTAPE 3: Saisie des identifiants
            # ========================================
            print("\n📝 ÉTAPE 3: Saisie des identifiants...")
            await page.fill("#loginUsername", USERNAME)
            await page.fill("#loginPassword", PASSWORD)
            print(f"   ✓ Utilisateur: {USERNAME}")
            print(f"   ✓ Mot de passe: {'*' * len(PASSWORD)}")
            
            # ========================================
            # ÉTAPE 4: Soumission du formulaire
            # ========================================
            print("\n📝 ÉTAPE 4: Soumission du formulaire de connexion...")
            
            # Attendre la réponse de l'API
            async with page.expect_response(
                lambda response: "session_api.php" in response.url and response.status == 200
            ) as response_info:
                await page.click("#loginModal button[type='submit']")
            
            response = await response_info.value
            response_body = await response.json()
            
            print(f"   ✓ Réponse API reçue")
            print(f"   ✓ Success: {response_body.get('success', False)}")
            if response_body.get('user'):
                print(f"   ✓ User: {response_body['user'].get('username', 'N/A')}")
                print(f"   ✓ Role: {response_body['user'].get('role', 'N/A')}")
            
            # Attendre que la page se mette à jour
            await page.wait_for_timeout(500)
            
            # ========================================
            # ÉTAPE 5: Vérification de l'état après connexion
            # ========================================
            print("\n📝 ÉTAPE 5: Vérification de l'interface après connexion...")
            
            # Attendre explicitement que le statut change
            await page.wait_for_function(
                "document.getElementById('userStatus').textContent.includes('Connecté')",
                timeout=5000
            )
            
            # Récupérer tous les états des boutons
            status_text = await page.locator("#userStatus").text_content()
            login_btn_hidden = not await page.locator("#loginBtn").is_visible()
            logout_btn_visible = await page.locator("#logoutBtn").is_visible()
            change_pwd_btn_visible = await page.locator("#changePwdBtn").is_visible()
            admin_btn_visible = await page.locator("#adminUserBtn").is_visible()
            
            # Récupérer les styles CSS
            admin_btn_style = await page.locator("#adminUserBtn").get_attribute("style")
            admin_btn_computed = await page.evaluate("""
                () => {
                    const btn = document.getElementById('adminUserBtn');
                    if (!btn) return null;
                    const style = window.getComputedStyle(btn);
                    return {
                        display: style.display,
                        visibility: style.visibility,
                        opacity: style.opacity,
                        position: style.position
                    };
                }
            """)
            
            print(f"\n   📊 RÉSULTATS:")
            print(f"   ├─ Statut utilisateur: '{status_text}'")
            print(f"   ├─ Bouton 'Se connecter': {'Caché ✓' if login_btn_hidden else 'Visible ✗'}")
            print(f"   ├─ Bouton 'Déconnexion': {'Visible ✓' if logout_btn_visible else 'Caché ✗'}")
            print(f"   ├─ Bouton 'Changer mot de passe': {'Visible ✓' if change_pwd_btn_visible else 'Caché ✗'}")
            print(f"   └─ Bouton 'Gestion utilisateurs': {'Visible ✓' if admin_btn_visible else 'Caché ✗'}")
            
            print(f"\n   🔍 DÉTAILS DU BOUTON ADMIN:")
            print(f"   ├─ Attribut style: {admin_btn_style}")
            print(f"   └─ Styles calculés:")
            if admin_btn_computed:
                for key, value in admin_btn_computed.items():
                    print(f"       ├─ {key}: {value}")
            
            # ========================================
            # ÉTAPE 6: Test de clic sur le bouton admin
            # ========================================
            print("\n📝 ÉTAPE 6: Test du clic sur 'Gestion utilisateurs'...")
            
            if admin_btn_visible:
                # Cliquer sur le bouton
                await page.click("#adminUserBtn")
                await page.wait_for_timeout(1000)
                
                # Vérifier que le modal s'ouvre
                user_admin_modal_visible = await page.locator("#userAdminModal").is_visible()
                
                print(f"   ✓ Bouton cliqué")
                print(f"   ✓ Modal 'Gestion utilisateurs' visible: {user_admin_modal_visible}")
                
                if user_admin_modal_visible:
                    # Vérifier le contenu du modal (avec timeout court)
                    try:
                        modal_title = await page.locator("#userAdminModal h3, #userAdminModal h2").first.text_content(timeout=3000)
                        print(f"   ✓ Titre du modal: '{modal_title}'")
                    except:
                        print(f"   ℹ️  Titre du modal: Non trouvé (pas critique)")
                    
                    try:
                        add_user_btn = await page.locator("#userAdminModal button:has-text('Ajouter')").is_visible(timeout=3000)
                        print(f"   ✓ Bouton 'Ajouter' visible: {add_user_btn}")
                    except:
                        print(f"   ℹ️  Bouton 'Ajouter': Non trouvé")
                    
                    try:
                        user_list = await page.locator("#userList").is_visible(timeout=3000)
                        print(f"   ✓ Liste d'utilisateurs visible: {user_list}")
                    except:
                        print(f"   ℹ️  Liste d'utilisateurs: Non trouvée")
                    
                    # Prendre une capture d'écran
                    await page.screenshot(path="admin_modal_success.png", full_page=True)
                    print(f"   📸 Capture d'écran: admin_modal_success.png")
                else:
                    print(f"   ✗ ÉCHEC: Le modal ne s'est pas ouvert!")
                    await page.screenshot(path="admin_modal_failed.png", full_page=True)
                    print(f"   📸 Capture d'écran: admin_modal_failed.png")
            else:
                print(f"   ✗ IMPOSSIBLE: Le bouton 'Gestion utilisateurs' n'est pas visible!")
                await page.screenshot(path="admin_button_not_visible.png", full_page=True)
                print(f"   📸 Capture d'écran: admin_button_not_visible.png")
            
            # ========================================
            # ÉTAPE 7: Rapport final
            # ========================================
            print("\n" + "=" * 80)
            print("📋 RAPPORT FINAL")
            print("=" * 80)
            
            all_checks_passed = True
            checks = {
                "Connexion réussie": response_body.get('success', False),
                "Statut affiché correctement": "Connecté" in status_text,
                "Bouton login caché": login_btn_hidden,
                "Bouton logout visible": logout_btn_visible,
                "Bouton changement MDP visible": change_pwd_btn_visible,
                "Bouton admin visible": admin_btn_visible,
            }
            
            if admin_btn_visible:
                try:
                    checks["Modal admin s'ouvre"] = user_admin_modal_visible
                except:
                    pass
            
            for check, passed in checks.items():
                icon = "✅" if passed else "❌"
                print(f"{icon} {check}")
                if not passed:
                    all_checks_passed = False
            
            print("\n" + "=" * 80)
            if all_checks_passed:
                print("🎉 RÉSULTAT: TOUS LES TESTS SONT RÉUSSIS!")
                print("✅ Le bouton 'Gestion utilisateurs' fonctionne correctement!")
            else:
                print("⚠️  RÉSULTAT: CERTAINS TESTS ONT ÉCHOUÉ")
                print("❌ Vérifiez les captures d'écran et les logs ci-dessus")
            print("=" * 80)
            
            # Afficher les erreurs JavaScript s'il y en a
            if errors:
                print("\n⚠️  ERREURS JAVASCRIPT DÉTECTÉES:")
                for i, error in enumerate(errors, 1):
                    print(f"   {i}. {error}")
            
            return 0 if all_checks_passed else 1
            
        except Exception as e:
            print(f"\n❌ ERREUR PENDANT LES TESTS: {e}")
            import traceback
            traceback.print_exc()
            await page.screenshot(path="error_screenshot.png", full_page=True)
            print(f"📸 Capture d'écran de l'erreur: error_screenshot.png")
            return 1
        
        finally:
            await browser.close()

if __name__ == "__main__":
    exit_code = asyncio.run(main())
    sys.exit(exit_code)
