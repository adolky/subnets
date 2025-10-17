#!/usr/bin/env python3
"""
Visual test script using Playwright to validate all application features
Takes screenshots at each step for visual verification
"""

import asyncio
import sys
from pathlib import Path
from playwright.async_api import async_playwright, Page, Browser, expect

# Configuration
BASE_URL = "http://10.105.126.7:8080"
SCREENSHOTS_DIR = Path("playwright_screenshots")
ADMIN_USER = "admin"
ADMIN_PASS = "admin123"

async def setup_screenshots_dir():
    """Create screenshots directory if it doesn't exist"""
    SCREENSHOTS_DIR.mkdir(exist_ok=True)
    print(f"📁 Screenshots will be saved to: {SCREENSHOTS_DIR}")

async def take_screenshot(page: Page, name: str, description: str = ""):
    """Take a screenshot and save it"""
    filepath = SCREENSHOTS_DIR / f"{name}.png"
    await page.screenshot(path=str(filepath), full_page=True)
    print(f"📸 Screenshot: {name}.png - {description}")

async def test_initial_load(page: Page):
    """Test 1: Initial page load"""
    print("\n🧪 Test 1: Initial Page Load")
    await page.goto(f"{BASE_URL}/subnets.html")
    await page.wait_for_load_state("networkidle")
    await take_screenshot(page, "01_initial_load", "Page loaded, user not connected")
    
    # Check status
    status = await page.locator("#userStatus").text_content()
    print(f"   Status: {status}")
    
    # Check login button visible
    login_btn = page.locator("#loginBtn")
    is_visible = await login_btn.is_visible()
    print(f"   Login button visible: {is_visible}")
    
    return status == "Non connecté" and is_visible

async def test_login(page: Page):
    """Test 2: Login functionality"""
    print("\n🧪 Test 2: Login Functionality")
    
    # Click login button
    await page.click("#loginBtn")
    await page.wait_for_timeout(500)
    await take_screenshot(page, "02_login_modal_opened", "Login modal opened")
    
    # Fill credentials
    await page.fill("#loginUsername", ADMIN_USER)
    await page.fill("#loginPassword", ADMIN_PASS)
    await take_screenshot(page, "03_credentials_filled", "Credentials entered")
    
    # Submit form
    await page.click("#loginForm button[type='submit']")
    
    # Wait for alert and accept it
    try:
        page.on("dialog", lambda dialog: dialog.accept())
        await page.wait_for_timeout(1000)
    except:
        pass
    
    await page.wait_for_timeout(1000)
    await take_screenshot(page, "04_after_login", "After login submitted")
    
    # Check status updated
    status = await page.locator("#userStatus").text_content()
    print(f"   Status after login: {status}")
    
    # Check buttons visibility
    login_btn_visible = await page.locator("#loginBtn").is_visible()
    logout_btn_visible = await page.locator("#logoutBtn").is_visible()
    change_pwd_visible = await page.locator("#changePwdBtn").is_visible()
    user_admin_visible = await page.locator("#userAdminBtn").is_visible()
    
    print(f"   Login button visible: {login_btn_visible}")
    print(f"   Logout button visible: {logout_btn_visible}")
    print(f"   Change password button visible: {change_pwd_visible}")
    print(f"   User admin button visible: {user_admin_visible}")
    
    return logout_btn_visible and change_pwd_visible

async def test_user_admin_modal(page: Page):
    """Test 3: User Administration Modal"""
    print("\n🧪 Test 3: User Administration Modal")
    
    # Check if user admin button exists and is visible
    user_admin_btn = page.locator("#userAdminBtn")
    is_visible = await user_admin_btn.is_visible()
    
    if not is_visible:
        print("   ⚠️  User admin button NOT VISIBLE")
        await take_screenshot(page, "05_admin_button_missing", "Admin button should be visible but isn't")
        
        # Check what buttons ARE visible
        all_buttons = await page.locator("button").all()
        print(f"   Total buttons found: {len(all_buttons)}")
        for i, btn in enumerate(all_buttons):
            btn_text = await btn.text_content()
            btn_visible = await btn.is_visible()
            print(f"   Button {i}: '{btn_text}' - Visible: {btn_visible}")
        
        return False
    
    print("   ✅ User admin button is visible!")
    
    # Click user admin button
    await user_admin_btn.click()
    await page.wait_for_timeout(1000)
    await take_screenshot(page, "06_user_admin_modal", "User administration modal opened")
    
    # Check modal content
    modal = page.locator("#userAdminModal")
    modal_visible = await modal.is_visible()
    print(f"   User admin modal visible: {modal_visible}")
    
    if modal_visible:
        # Check for user list table
        table = page.locator("#userListTable")
        table_visible = await table.is_visible()
        print(f"   User list table visible: {table_visible}")
        
        # Check for add user form
        add_form = page.locator("#addUserForm")
        form_visible = await add_form.is_visible()
        print(f"   Add user form visible: {form_visible}")
    
    return modal_visible

async def test_change_password_modal(page: Page):
    """Test 4: Change Password Modal"""
    print("\n🧪 Test 4: Change Password Modal")
    
    # Close user admin modal if open
    await page.keyboard.press("Escape")
    await page.wait_for_timeout(500)
    
    # Click change password button
    change_pwd_btn = page.locator("#changePwdBtn")
    is_visible = await change_pwd_btn.is_visible()
    
    if not is_visible:
        print("   ⚠️  Change password button NOT VISIBLE")
        return False
    
    await change_pwd_btn.click()
    await page.wait_for_timeout(500)
    await take_screenshot(page, "07_change_password_modal", "Change password modal opened")
    
    # Check modal
    modal = page.locator("#changePwdModal")
    modal_visible = await modal.is_visible()
    print(f"   Change password modal visible: {modal_visible}")
    
    return modal_visible

async def test_calculator_functions(page: Page):
    """Test 5: Subnet Calculator Functions"""
    print("\n🧪 Test 5: Subnet Calculator Functions")
    
    # Close any open modals
    await page.keyboard.press("Escape")
    await page.wait_for_timeout(500)
    
    # Enter a network
    network_input = page.locator("input[name='network']")
    await network_input.fill("192.168.1.0")
    
    # Select mask
    mask_input = page.locator("input[name='netbits']")
    await mask_input.fill("24")
    
    await take_screenshot(page, "08_network_entered", "Network 192.168.1.0/24 entered")
    
    # Click update button
    update_btn = page.locator("button:has-text('Update')")
    if await update_btn.count() > 0:
        await update_btn.first.click()
        await page.wait_for_timeout(1000)
        await take_screenshot(page, "09_network_calculated", "Network calculated")
    
    return True

async def test_save_functionality(page: Page):
    """Test 6: Save Configuration (requires auth)"""
    print("\n🧪 Test 6: Save Configuration")
    
    # Click save button
    save_btn = page.locator("button:has-text('Save')")
    if await save_btn.count() > 0:
        await save_btn.first.click()
        await page.wait_for_timeout(1000)
        await take_screenshot(page, "10_save_modal", "Save configuration modal")
        
        # Check if save modal appeared
        save_modal = page.locator("#saveModal")
        modal_visible = await save_modal.is_visible()
        print(f"   Save modal visible: {modal_visible}")
        
        if modal_visible:
            # Fill save form
            await page.fill("#siteName", "Test Site Playwright")
            await page.fill("#adminNumber", "TEST001")
            await take_screenshot(page, "11_save_form_filled", "Save form filled")
        
        return modal_visible
    
    return False

async def test_load_functionality(page: Page):
    """Test 7: Load Configuration (no auth required)"""
    print("\n🧪 Test 7: Load Configuration")
    
    # Close save modal if open
    await page.keyboard.press("Escape")
    await page.wait_for_timeout(500)
    
    # Click load button
    load_btn = page.locator("button:has-text('Load')")
    if await load_btn.count() > 0:
        await load_btn.first.click()
        await page.wait_for_timeout(1000)
        await take_screenshot(page, "12_load_modal", "Load configuration modal")
        
        # Check if load modal appeared
        load_modal = page.locator("#loadModal")
        modal_visible = await load_modal.is_visible()
        print(f"   Load modal visible: {modal_visible}")
        
        return modal_visible
    
    return False

async def test_logout(page: Page):
    """Test 8: Logout functionality"""
    print("\n🧪 Test 8: Logout Functionality")
    
    # Close any open modals
    await page.keyboard.press("Escape")
    await page.wait_for_timeout(500)
    
    # Click logout button
    logout_btn = page.locator("#logoutBtn")
    if await logout_btn.is_visible():
        # Handle alert
        page.on("dialog", lambda dialog: dialog.accept())
        await logout_btn.click()
        await page.wait_for_timeout(1000)
        await take_screenshot(page, "13_after_logout", "After logout")
        
        # Check status
        status = await page.locator("#userStatus").text_content()
        print(f"   Status after logout: {status}")
        
        # Check buttons
        login_btn_visible = await page.locator("#loginBtn").is_visible()
        logout_btn_visible = await page.locator("#logoutBtn").is_visible()
        
        print(f"   Login button visible: {login_btn_visible}")
        print(f"   Logout button visible: {logout_btn_visible}")
        
        return login_btn_visible and not logout_btn_visible
    
    return False

async def debug_page_state(page: Page):
    """Debug: Print current page state"""
    print("\n🔍 Debug: Current Page State")
    
    # Get all button information
    buttons = await page.locator("button").all()
    print(f"\n   Total buttons on page: {len(buttons)}")
    
    for i, btn in enumerate(buttons):
        btn_id = await btn.get_attribute("id")
        btn_text = await btn.text_content()
        btn_visible = await btn.is_visible()
        btn_style = await btn.get_attribute("style")
        print(f"   Button {i}: ID='{btn_id}', Text='{btn_text}', Visible={btn_visible}, Style='{btn_style}'")
    
    # Check session status
    status = await page.locator("#userStatus").text_content()
    print(f"\n   User Status: {status}")
    
    # Take debug screenshot
    await take_screenshot(page, "14_debug_state", "Debug - Full page state")

async def main():
    """Main test runner"""
    print("🚀 Starting Visual Tests with Playwright\n")
    print(f"Target URL: {BASE_URL}/subnets.html")
    
    await setup_screenshots_dir()
    
    async with async_playwright() as p:
        # Launch browser in headless mode (no X server needed)
        browser = await p.chromium.launch(headless=True, slow_mo=500)
        context = await browser.new_context(
            viewport={"width": 1920, "height": 1080},
            accept_downloads=True
        )
        page = await context.new_page()
        
        # Enable console logging
        page.on("console", lambda msg: print(f"   🖥️  Console: {msg.text}"))
        page.on("pageerror", lambda err: print(f"   ❌ Error: {err}"))
        
        try:
            results = {}
            
            # Run tests
            results["initial_load"] = await test_initial_load(page)
            results["login"] = await test_login(page)
            
            # Debug state after login
            await debug_page_state(page)
            
            results["user_admin"] = await test_user_admin_modal(page)
            results["change_password"] = await test_change_password_modal(page)
            results["calculator"] = await test_calculator_functions(page)
            results["save"] = await test_save_functionality(page)
            results["load"] = await test_load_functionality(page)
            results["logout"] = await test_logout(page)
            
            # Final state
            await debug_page_state(page)
            
            # Summary
            print("\n" + "="*60)
            print("📊 TEST SUMMARY")
            print("="*60)
            
            passed = sum(1 for v in results.values() if v)
            total = len(results)
            
            for test_name, result in results.items():
                status = "✅ PASS" if result else "❌ FAIL"
                print(f"{status} - {test_name}")
            
            print(f"\nTotal: {passed}/{total} tests passed")
            print(f"\n📸 Screenshots saved to: {SCREENSHOTS_DIR}")
            print("="*60)
            
            # Keep browser open for manual inspection
            print("\n⏸️  Browser will stay open for 10 seconds for inspection...")
            await page.wait_for_timeout(10000)
            
        except Exception as e:
            print(f"\n❌ Error during tests: {e}")
            import traceback
            traceback.print_exc()
        finally:
            await browser.close()

if __name__ == "__main__":
    asyncio.run(main())
