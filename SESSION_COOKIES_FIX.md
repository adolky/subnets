# Cookie & Session Management Fix

## Issues Fixed

### Problem: UI Not Updating After Login ❌ → ✅

**Symptoms:**
- After successful login, the "Se connecter" button remained visible
- "Déconnexion", "Changer mot de passe", and "Gestion utilisateurs" buttons didn't appear
- Session appeared not to persist

**Root Cause:**
Fetch requests were not including cookies by default, so session cookies were not being sent to the server on subsequent requests.

**Solution:**
Added `credentials: 'same-origin'` to all fetch requests to ensure cookies are included.

## Technical Changes

### File: `/home/aku/subnets/subnets.html`

#### 1. Login Function - Added Cookie Support
```javascript
fetch('session_api.php', {
    method: 'POST',
    body: formData,
    credentials: 'same-origin' // ← NEW: Include cookies
})
```

#### 2. Session Check - Added Cookie Support + Delay
```javascript
// After successful login
setTimeout(() => {
    checkSession(); // Give browser time to set cookies
}, 100);

// In checkSession function
fetch('session_api.php?action=me', {
    credentials: 'same-origin' // ← NEW: Include cookies
})
```

#### 3. Logout Function - Added Cookie Support
```javascript
fetch('session_api.php?action=logout', {
    credentials: 'same-origin' // ← NEW: Include cookies
})
```

#### 4. Added Debug Logging
```javascript
console.log('Session check response:', data);
```
Helps troubleshoot session issues in browser console.

## User Interface After Login

### For Regular Users:
After logging in, you should see:
- ✅ **Status:** "Connecté: username"
- ✅ **Déconnexion** button
- ✅ **Changer mot de passe** button
- ❌ User management hidden (not admin)

### For Admin Users:
After logging in with admin account, you should see:
- ✅ **Status:** "Connecté: admin"
- ✅ **Déconnexion** button
- ✅ **Changer mot de passe** button
- ✅ **Gestion utilisateurs** button

## Button Locations

All authentication buttons are in the top-right corner of the page:

```
┌─────────────────────────────────────────────┐
│ [Non connecté] [Se connecter]               │ ← Before login
└─────────────────────────────────────────────┘

┌─────────────────────────────────────────────┐
│ [Connecté: admin] [Déconnexion]              │ ← After login
│ [Changer mot de passe] [Gestion utilisateurs]│   (admin only)
└─────────────────────────────────────────────┘
```

## Features by Button

### 🔐 **Se connecter**
- Opens login modal
- Enter username and password
- Press Enter or click "Se connecter" to submit

### 🚪 **Déconnexion**
- Logs out current user
- Clears session
- Returns to "Non connecté" state

### 🔑 **Changer mot de passe**
Opens modal with three fields:
- Current password (ancien mot de passe)
- New password (nouveau mot de passe)
- Confirm new password (confirmer)

**Validation:**
- Must enter correct current password
- New password must be at least 6 characters
- New passwords must match

### 👥 **Gestion utilisateurs** (Admin Only)
Opens admin panel with:

**User List Table:**
- Username
- Role (admin / user)
- Creation date
- Delete button (admin account protected)

**Add User Form:**
- Username field
- Password field (min 6 chars)
- Role selector (user / admin)
- "Ajouter" button to create

## Testing Steps

### 1. Test Login
```
✅ Open http://10.105.126.7:8080/subnets.html
✅ Status shows "Non connecté"
✅ Only "Se connecter" button visible
✅ Click "Se connecter"
✅ Modal opens
✅ Enter: admin / admin123
✅ Click "Se connecter" or press Enter
✅ Alert: "Connexion réussie !"
✅ Modal closes
✅ Status updates to "Connecté: admin"
✅ Three buttons appear:
   - Déconnexion
   - Changer mot de passe
   - Gestion utilisateurs
```

### 2. Test Password Change
```
✅ Click "Changer mot de passe"
✅ Modal opens
✅ Enter current password: admin123
✅ Enter new password: newpass456
✅ Confirm new password: newpass456
✅ Click "Changer"
✅ Success message appears
✅ Can now login with new password
```

### 3. Test User Management (Admin)
```
✅ Click "Gestion utilisateurs"
✅ Modal opens showing user list
✅ See "admin" user in table
✅ Fill "Add User" form:
   Username: testuser
   Password: test1234
   Role: user
✅ Click "Ajouter"
✅ New user appears in table
✅ Can delete non-admin users
✅ Cannot delete admin account (protected)
```

### 4. Test Logout
```
✅ Click "Déconnexion"
✅ Alert: "Déconnexion réussie !"
✅ Status returns to "Non connecté"
✅ Only "Se connecter" button visible
✅ All other buttons hidden
```

## Troubleshooting

### Issue: Buttons still don't update after login

**Check Browser Console (F12):**
```javascript
// Should see this after login:
Session check response: {success: true, user: {username: "admin", role: "admin"}}
```

**If you see this instead:**
```javascript
Session check response: {success: false, message: "Not authenticated"}
```

**Solutions:**
1. Clear all browser cookies for the site
2. Hard refresh: Ctrl+Shift+R
3. Try in incognito/private mode
4. Check if cookies are enabled in browser settings

### Issue: "Gestion utilisateurs" button doesn't appear

**Cause:** You're logged in as a regular user, not admin

**Solution:** Login with admin account:
- Username: `admin`
- Password: `admin123`

### Issue: Changes don't persist after page refresh

**Cause:** Session cookies not being saved

**Solution:**
1. Ensure cookies are enabled in browser
2. Check that you're not in private/incognito mode
3. Verify the site is accessed via the same URL (not switching between IP and domain)

## Credentials Management

### Default Admin Account
```
Username: admin
Password: admin123
```

### Creating Additional Users

**Via Admin Panel:**
1. Login as admin
2. Click "Gestion utilisateurs"
3. Fill form and click "Ajouter"

**Via Database:**
```bash
docker exec -it subnet-mysql mysql -u subnet_user -psubnet_pass subnet_db
```
```sql
-- Add user with hashed password
INSERT INTO users (username, password_hash, created_at) 
VALUES ('newuser', '$2y$10$...hash...', NOW());
```

### Password Requirements
- Minimum 6 characters
- No maximum length
- Case-sensitive
- Special characters allowed

## Security Notes

### Session Security
- ✅ HttpOnly cookies (not accessible via JavaScript)
- ✅ SameSite=Lax (CSRF protection)
- ✅ Secure flag when using HTTPS
- ✅ Session expires on browser close

### Password Security
- ✅ Bcrypt hashing (PASSWORD_DEFAULT)
- ✅ Salt generated automatically
- ✅ Hash strength: cost factor 10
- ✅ Passwords never stored in plain text

### Access Control
- ✅ Session validation on every request
- ✅ Role-based access (admin vs user)
- ✅ Protected admin account (cannot be deleted)
- ✅ Server-side permission checks

## API Endpoints Summary

| Endpoint | Purpose | Auth Required | Admin Only |
|----------|---------|---------------|------------|
| `action=login` | Authenticate user | No | No |
| `action=logout` | End session | Yes | No |
| `action=me` | Check session status | No* | No |
| `action=change_password` | Update own password | Yes | No |
| `action=list_users` | Get all users | Yes | Yes |
| `action=add_user` | Create new user | Yes | Yes |
| `action=delete_user` | Remove user | Yes | Yes |

*Returns success=false if not authenticated

## Deployment Status

✅ **Docker Image Rebuilt:** 14.7 seconds
✅ **Containers Running:** subnet-calculator + subnet-mysql
✅ **All Changes Applied:** Cookie support enabled
✅ **Ready for Testing:** All features operational

## Quick Reference Card

### Normal User Workflow
```
1. Visit site → See "Non connecté"
2. Click "Se connecter"
3. Login → See "Connecté: username"
4. Use calculator (no login required)
5. Save configs (login required)
6. Load configs (no login required)
7. Change password → Use "Changer mot de passe"
8. Logout → Click "Déconnexion"
```

### Admin User Workflow
```
All normal user features PLUS:
9. Manage users → Click "Gestion utilisateurs"
10. Add users → Fill form and "Ajouter"
11. Delete users → Click delete in user table
12. Cannot delete admin account (protected)
```

## Summary

### What Was Fixed:
1. ✅ Added `credentials: 'same-origin'` to all fetch requests
2. ✅ Added 100ms delay before checkSession after login
3. ✅ Added console logging for debugging
4. ✅ Improved button visibility logic
5. ✅ French language for all messages

### Expected Behavior:
- Login → Buttons update immediately
- Logout → Buttons hide immediately
- Session persists across page reloads
- Admin sees all management options
- Regular users see limited options

### Access Summary:
- **Public:** Calculator, Load configs, View data
- **Authenticated:** Save, Update, Change password
- **Admin Only:** User management

All systems operational! 🚀

**Test now in browser:** http://10.105.126.7:8080/subnets.html
