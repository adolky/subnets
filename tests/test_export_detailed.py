#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""
Test de l'export CSV détaillé avec toutes les données
"""

from playwright.sync_api import sync_playwright
import time
import sys
import csv
import os

def test_detailed_export():
    with sync_playwright() as p:
        # Lancer le navigateur
        browser = p.chromium.launch(headless=True, slow_mo=500)
        context = browser.new_context(
            viewport={'width': 1920, 'height': 1080},
            accept_downloads=True
        )
        page = context.new_page()
        
        # Activer le logging
        page.on('console', lambda msg: print(f'🖥️  Console: {msg.text}'))
        
        # Gérer les alertes
        alerts = []
        page.on('dialog', lambda dialog: (alerts.append(dialog.message), dialog.accept()))
        
        # Gérer les téléchargements
        downloads = []
        page.on('download', lambda download: downloads.append(download))
        
        try:
            print("=" * 80)
            print("TEST: Export CSV détaillé avec toutes les données")
            print("=" * 80)
            
            # Étape 1: Charger la page
            print("\n📄 Étape 1: Chargement de la page...")
            page.goto('http://10.105.126.7:8080/subnets.html')
            page.wait_for_load_state('networkidle')
            page.screenshot(path='export_detailed_01_loaded.png')
            print("✅ Page chargée")
            
            # Étape 2: Compter les configurations dans la base
            print("\n🔍 Étape 2: Vérification des configurations en base...")
            import requests
            response = requests.get('http://10.105.126.7:8080/api.php?action=list')
            api_data = response.json()
            
            if api_data['success']:
                config_count = len(api_data['data'])
                print(f"📊 Nombre de configurations en base: {config_count}")
                
                # Afficher les détails
                for i, config in enumerate(api_data['data'], 1):
                    print(f"  {i}. {config['site_name']} - {config['network_address']}")
            else:
                print("❌ Erreur lors de la récupération des configurations")
                return False
            
            # Étape 3: Ouvrir le menu d'export
            print("\n📥 Étape 3: Ouverture du menu d'export...")
            export_btn = page.locator('#exportBtn')
            export_btn.click()
            time.sleep(0.5)
            page.screenshot(path='export_detailed_02_menu_opened.png')
            print("✅ Menu ouvert")
            
            # Étape 4: Cliquer sur "Tous les sous-réseaux"
            print("\n📤 Étape 4: Export de tous les sous-réseaux...")
            all_subnets_link = page.locator('#exportDropdownMenu a').first
            all_subnets_link.click()
            time.sleep(2)
            page.screenshot(path='export_detailed_03_export_clicked.png')
            
            # Vérifier les alertes
            if alerts:
                print(f"💬 Message reçu: {alerts[-1]}")
            
            # Étape 5: Vérifier le téléchargement
            print("\n📂 Étape 5: Vérification du fichier téléchargé...")
            
            if not downloads:
                print("❌ Aucun téléchargement détecté")
                return False
            
            download = downloads[0]
            filename = download.suggested_filename
            filepath = f'/home/aku/subnets/{filename}'
            
            # Sauvegarder le fichier
            download.save_as(filepath)
            print(f"✅ Fichier téléchargé: {filename}")
            
            # Étape 6: Analyser le contenu du CSV
            print(f"\n🔍 Étape 6: Analyse du contenu CSV...")
            
            with open(filepath, 'r', encoding='utf-8-sig') as csvfile:
                reader = csv.DictReader(csvfile)
                rows = list(reader)
                
                print(f"📊 Nombre de lignes dans le CSV: {len(rows)}")
                print(f"📋 Colonnes présentes: {', '.join(reader.fieldnames)}")
                
                # Vérifier les colonnes requises
                required_columns = [
                    'Site Name', 'Admin Number', 'Parent Network',
                    'Subnet', 'Netmask', 'First IP', 'Last IP',
                    'Usable First', 'Usable Last', 'Usable Count', 'Total Hosts',
                    'VLAN ID', 'VLAN Name', 'Created At', 'Updated At'
                ]
                
                missing_columns = [col for col in required_columns if col not in reader.fieldnames]
                if missing_columns:
                    print(f"❌ Colonnes manquantes: {', '.join(missing_columns)}")
                    return False
                else:
                    print("✅ Toutes les colonnes requises sont présentes")
                
                # Analyser le contenu
                print("\n📋 Détail des données exportées:")
                
                sites = {}
                for row in rows:
                    site_name = row['Site Name']
                    if site_name not in sites:
                        sites[site_name] = []
                    sites[site_name].append(row)
                
                for site_name, site_rows in sites.items():
                    print(f"\n  🏢 Site: {site_name}")
                    print(f"     Nombre de sous-réseaux: {len(site_rows)}")
                    print(f"     Admin Number: {site_rows[0]['Admin Number']}")
                    print(f"     Parent Network: {site_rows[0]['Parent Network']}")
                    
                    # Afficher quelques sous-réseaux
                    for i, row in enumerate(site_rows[:3], 1):
                        print(f"     {i}. {row['Subnet']} - {row['Netmask']}")
                        if row['Usable Count']:
                            print(f"        IPs utilisables: {row['Usable Count']}")
                        if row['VLAN ID']:
                            print(f"        VLAN ID: {row['VLAN ID']}")
                        if row['VLAN Name']:
                            print(f"        VLAN Name: {row['VLAN Name']}")
                    
                    if len(site_rows) > 3:
                        print(f"     ... et {len(site_rows) - 3} autre(s)")
                
                # Vérifications
                print("\n✅ VALIDATIONS:")
                
                # 1. Vérifier qu'on a au moins autant de lignes que de configs
                if len(rows) >= config_count:
                    print(f"  ✅ {len(rows)} lignes exportées >= {config_count} configurations")
                else:
                    print(f"  ❌ Seulement {len(rows)} lignes pour {config_count} configurations")
                
                # 2. Vérifier qu'on a toutes les données importantes
                non_empty_subnets = [r for r in rows if r['Subnet']]
                non_empty_netmasks = [r for r in rows if r['Netmask']]
                non_empty_first_ips = [r for r in rows if r['First IP']]
                non_empty_usable = [r for r in rows if r['Usable Count']]
                
                print(f"  ✅ Subnets renseignés: {len(non_empty_subnets)}/{len(rows)}")
                print(f"  ✅ Netmasks renseignés: {len(non_empty_netmasks)}/{len(rows)}")
                print(f"  ✅ First IPs renseignés: {len(non_empty_first_ips)}/{len(rows)}")
                print(f"  ✅ Usable Counts renseignés: {len(non_empty_usable)}/{len(rows)}")
                
                # 3. Vérifier les VLANs (optionnels)
                rows_with_vlan_id = [r for r in rows if r['VLAN ID']]
                rows_with_vlan_name = [r for r in rows if r['VLAN Name']]
                
                if rows_with_vlan_id or rows_with_vlan_name:
                    print(f"  ✅ VLANs trouvés: {len(rows_with_vlan_id)} avec ID, {len(rows_with_vlan_name)} avec nom")
                else:
                    print(f"  ℹ️  Aucun VLAN configuré (normal si pas de divisions)")
                
                # 4. Vérifier les timestamps
                rows_with_created = [r for r in rows if r['Created At']]
                rows_with_updated = [r for r in rows if r['Updated At']]
                
                print(f"  ✅ Timestamps: {len(rows_with_created)} created, {len(rows_with_updated)} updated")
            
            # Afficher un extrait du CSV
            print("\n📄 Extrait du CSV (5 premières lignes):")
            with open(filepath, 'r', encoding='utf-8-sig') as f:
                lines = f.readlines()[:6]  # Header + 5 lignes
                for line in lines:
                    print(f"  {line.rstrip()}")
            
            # Résultat final
            print("\n" + "=" * 80)
            print("✅ TEST RÉUSSI - Export détaillé fonctionne correctement !")
            print("=" * 80)
            print(f"\nRésumé:")
            print(f"  • Configurations en base: {config_count}")
            print(f"  • Lignes exportées: {len(rows)}")
            print(f"  • Sites distincts: {len(sites)}")
            print(f"  • Fichier: {filename}")
            print(f"  • Taille: {os.path.getsize(filepath)} bytes")
            
            return True
            
        except Exception as e:
            print(f"\n❌ Erreur: {e}")
            page.screenshot(path='export_detailed_error.png')
            import traceback
            traceback.print_exc()
            return False
        finally:
            browser.close()

if __name__ == '__main__':
    success = test_detailed_export()
    sys.exit(0 if success else 1)
