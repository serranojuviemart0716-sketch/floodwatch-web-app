# 🚀 FloodWatch + XAMPP Complete Setup Guide

## Current Issue
❌ URL shows: `file:///C:/xampp/htdocs/Flood%20Watch%20Community%20Based%20Monitoring%System/register.html`
- This is a **local file**, not going through XAMPP server
- PHP won't execute
- Database connections won't work
- Getting "Connection error" messages

---

## ✅ Quick Start (3 Steps)

### Step 1: Start XAMPP Services

**Option A: Using XAMPP Control Panel (Easiest)**
1. Open **XAMPP Control Panel** from Start Menu
2. Look for these services:
   - ☐ Apache → Click **"Start"** (should turn green ✓)
   - ☐ MySQL → Click **"Start"** (should turn green ✓)
3. Wait 5-10 seconds for both to initialize

**Option B: Using Command Prompt (Advanced)**
```bash
# Open Command Prompt as Administrator
# Then run:
net start Apache2.4
net start MySQL80
```

**How to verify services are running:**
- Apache should show a green checkmark
- MySQL should show a green checkmark
- Port 80 should be in use (Apache)
- Port 3306 should be in use (MySQL)

---

### Step 2: Import Database

1. Open **phpMyAdmin**: http://localhost/phpmyadmin/
2. Click **"Import"** (top menu)
3. Click **"Choose File"**
4. Select: `floodwatch_setup.sql`
   - Path: `C:\xampp\htdocs\Flood Watch Community Based Monitoring System\floodwatch_setup.sql`
5. Click **"Import"** button
6. ✅ Wait for green "Import successful" message

---

### Step 3: Access FloodWatch Correctly

**Now use these URLs (not `file://`):**

| Page | URL |
|------|-----|
| 🔓 Login | http://localhost/Flood%20Watch%20Community%20Based%20Monitoring%20System/login.html |
| 📝 Register | http://localhost/Flood%20Watch%20Community%20Based%20Monitoring%20System/register.html |
| 📊 Dashboard | http://localhost/Flood%20Watch%20Community%20Based%20Monitoring%20System/index.html |
| 🛠️ phpMyAdmin | http://localhost/phpmyadmin/ |

---

## 🎯 Complete Workflow

### Daily Development Workflow

**Morning:**
```
1. Open XAMPP Control Panel
2. Start Apache (green ✓)
3. Start MySQL (green ✓)
4. Open browser → http://localhost/phpmyadmin/
5. Verify tables exist (users, reports)
```

**Development:**
```
1. Open project files in VS Code
2. Edit HTML, CSS, PHP, JavaScript
3. Save files (automatically saved to C:\xampp\htdocs)
4. Refresh browser to see changes (Ctrl+R or F5)
```

**Testing:**
```
1. Test registration: http://localhost/Flood%20Watch%20Community%20Based%20Monitoring%20System/register.html
2. Create test account
3. Test login with new account
4. Check dashboard loads
5. Test API endpoints
```

**Before Stopping:**
```
1. Save all work in VS Code
2. Stop MySQL (click Stop in XAMPP)
3. Stop Apache (click Stop in XAMPP)
4. Close browser tabs
```

---

## 🔧 File Structure & Access

### How XAMPP Serves Files

```
XAMPP Document Root: C:\xampp\htdocs\

URL:  http://localhost/Flood%20Watch%20Community%20Based%20Monitoring%20System/login.html
      │                  │                                                      │
      └─ Server         └─ Folder in htdocs                                    └─ File to load

File Path: C:\xampp\htdocs\Flood Watch Community Based Monitoring System\login.html
```

### What Each Folder Contains

```
C:\xampp\htdocs\Flood Watch Community Based Monitoring System\
├── index.html              → Dashboard (requires login)
├── login.html              → Login page
├── register.html           → Registration page
├── api.php                 → Backend API endpoints
├── auth.php                → Authentication functions
├── database.php            → Database connection
├── styles.css              → Global styling
├── script.js               → JavaScript utilities
├── weather.php             → Weather dashboard
├── weather-api.php         → Weather API handler
├── admin.php               → Admin panel
├── api-client.js           → API client library
├── performance.js          → Performance monitoring
├── sw.js                   → Service worker
├── manifest.json           → PWA manifest
├── floodwatch_setup.sql    → Database setup file
└── XAMPP_SETUP_GUIDE.md    → This guide
```

---

## 🐛 Troubleshooting

### Problem: "Connection Error" After Import

**Solution:**
1. Check XAMPP Control Panel: Both Apache AND MySQL must be green ✓
2. Verify database was imported: http://localhost/phpmyadmin/
3. Look for "floodwatch_db" in left sidebar
4. Click it to see tables: "users", "reports"
5. If not there, re-import `floodwatch_setup.sql`

### Problem: Page Shows HTML Code Instead of Rendering

**Solution:**
- You're using `file://` instead of `http://localhost/`
- Use correct URL: `http://localhost/Flood%20Watch%20Community%20Based%20Monitoring%20System/login.html`
- Apache wasn't started
- Check XAMPP Control Panel for Apache (green ✓)

### Problem: Can't Connect to http://localhost

**Solution:**
1. Start Apache in XAMPP Control Panel
2. Wait 10 seconds
3. Try again
4. If still fails: Check Windows Firewall
   - Start → Windows Defender Firewall
   - Advanced Settings
   - Inbound Rules → Find Apache
   - Set to "Allow"

### Problem: "Access Denied" in phpMyAdmin

**Solution:**
1. phpMyAdmin password should be empty
2. Username: `root`
3. Password: (leave blank)
4. In database.php, verify:
   ```php
   define('DB_USER', 'root');
   define('DB_PASS', '');  // Empty!
   ```

### Problem: MySQL Won't Start

**Solution:**
1. Kill existing MySQL: `taskkill /IM mysqld.exe /F`
2. Delete lock files: `C:\xampp\mysql\data\*.err`
3. Start MySQL again in XAMPP
4. If fails: Reinstall MySQL via XAMPP

---

## 📝 Making Changes

### Editing HTML/CSS/JavaScript

1. Open file in VS Code: `register.html`
2. Make changes (e.g., fix barangay dropdown styling)
3. Save file (Ctrl+S)
4. Refresh browser (F5)
5. Changes appear immediately ✓

### Editing PHP Files

1. Open file in VS Code: `api.php`
2. Make changes (e.g., add new API endpoint)
3. Save file (Ctrl+S)
4. Refresh browser or call API again
5. Changes take effect (might need to clear PHP cache)

### Testing API Endpoints

1. Open browser DevTools (F12)
2. Go to Console tab
3. Test API call:
   ```javascript
   fetch('http://localhost/Flood%20Watch%20Community%20Based%20Monitoring%20System/api.php?action=me')
     .then(r => r.json())
     .then(d => console.log(d))
   ```
4. Check response in console

---

## 📊 Database Management

### Access Database

**Via phpMyAdmin (GUI):**
- http://localhost/phpmyadmin/
- Select `floodwatch_db`
- View/edit tables, run SQL queries

**Via MySQL Command Line:**
```bash
# Open Command Prompt
cd C:\xampp\mysql\bin
mysql -u root -p
# Press Enter (no password)
USE floodwatch_db;
SELECT * FROM users;
```

### Common Database Tasks

**View all users:**
```sql
SELECT user_id, fullName, email, barangay, is_active FROM users;
```

**View all reports:**
```sql
SELECT * FROM reports ORDER BY created_at DESC;
```

**Delete test user:**
```sql
DELETE FROM users WHERE email = 'test@example.com';
```

**Reset user password (use bcrypt):**
```sql
UPDATE users SET password_hash = '$2y$10$N9qo8uLOickgx2ZMRZoMye6qmm5z5W1S7b5dEqR1g9O8x3d3D8W3S' WHERE email = 'demo@floodwatch.local';
-- Password now: Demo1234
```

---

## 🔍 Debugging Tips

### Check Browser Console (F12)

1. Press **F12** in browser
2. Click **"Console"** tab
3. Look for JavaScript errors (red text)
4. Look for API response errors
5. Test JavaScript:
   ```javascript
   console.log('test');  // Should print: test
   ```

### Check Network Tab (F12)

1. Press **F12**
2. Click **"Network"** tab
3. Perform an action (login, register)
4. Look for red requests = errors
5. Click request to see details:
   - Request URL
   - Response status (200 = success, 4xx/5xx = error)
   - Response body (error message)

### Check Server Errors

**Apache Error Log:**
```
C:\xampp\apache\logs\error.log
```

**MySQL Error Log:**
```
C:\xampp\mysql\data\*.err
```

**PHP Errors:**
- Usually show in browser or console
- Enable display_errors in `C:\xampp\php\php.ini`:
  ```ini
  display_errors = On
  error_reporting = E_ALL
  ```

---

## ⚡ Performance Tips

1. **Clear Browser Cache (Ctrl+Shift+Delete)**
   - When CSS/JS changes don't show up

2. **Disable Service Worker (for testing)**
   - DevTools → Application → Service Workers → Unregister

3. **Enable Developer Mode**
   - DevTools Open (F12) while developing
   - Check console for errors in real-time

4. **Monitor Database Queries**
   - Add logging to `api.php`:
   ```php
   error_log("Query: " . $query);
   error_log("Result: " . json_encode($result));
   ```

---

## 🎓 Learning Resources

### Useful Commands

```bash
# Start XAMPP services
net start Apache2.4
net start MySQL80

# Stop XAMPP services
net stop Apache2.4
net stop MySQL80

# Check if ports are in use
netstat -ano | findstr :80
netstat -ano | findstr :3306
```

### File Locations

| What | Location |
|------|----------|
| Project Root | `C:\xampp\htdocs\Flood Watch Community Based Monitoring System` |
| XAMPP Config | `C:\xampp\apache\conf\httpd.conf` |
| PHP Config | `C:\xampp\php\php.ini` |
| MySQL Data | `C:\xampp\mysql\data` |
| Apache Logs | `C:\xampp\apache\logs` |
| MySQL Logs | `C:\xampp\mysql\data` |

---

## ✅ Checklist Before Development

- [ ] XAMPP Control Panel open
- [ ] Apache running (green ✓)
- [ ] MySQL running (green ✓)
- [ ] Database imported (`floodwatch_setup.sql`)
- [ ] Can access phpMyAdmin: http://localhost/phpmyadmin/
- [ ] Can access login page: http://localhost/Flood%20Watch%20Community%20Based%20Monitoring%20System/login.html
- [ ] Can login with demo@floodwatch.local / Demo1234
- [ ] VS Code open with project folder
- [ ] Browser DevTools ready (F12)

---

## 🎯 Next Steps

1. ✅ Start XAMPP (both Apache & MySQL)
2. ✅ Import database
3. ✅ Access via http://localhost (not file://)
4. ✅ Test login & registration
5. ✅ Start developing!

**Ready?** Open http://localhost/Flood%20Watch%20Community%20Based%20Monitoring%20System/login.html now! 🌊
