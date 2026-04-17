# FloodWatch - XAMPP Setup Guide

## ✅ Quick Setup (5 minutes)

### Step 1: Verify XAMPP is Running
1. Open XAMPP Control Panel
2. Ensure **Apache** has a green checkmark ✓
3. Ensure **MySQL** has a green checkmark ✓
4. If not, click **Start** next to each service

### Step 2: Import Database Setup File

#### Option A: Using phpMyAdmin (Recommended)

1. Open phpMyAdmin: http://localhost/phpmyadmin/
2. Click **"Import"** in the top menu
3. Click **"Choose File"** and select: `floodwatch_setup.sql`
   - Located at: `c:\xampp\htdocs\Flood Watch Community Based Monitoring System\floodwatch_setup.sql`
4. Click **"Import"** button at the bottom
5. You should see: "Import successful" message ✅

#### Option B: Using MySQL Command Line

```bash
# Open Command Prompt and run:
cd C:\xampp\mysql\bin
mysql -u root -p < "C:\xampp\htdocs\Flood Watch Community Based Monitoring System\floodwatch_setup.sql"
# Press Enter when prompted for password (leave blank and just hit Enter)
```

### Step 3: Verify Database Created

1. Go to phpMyAdmin: http://localhost/phpmyadmin/
2. Look for **floodwatch_db** in the left sidebar
3. Click it to expand
4. Verify you see two tables:
   - ✓ users
   - ✓ reports

### Step 4: Test Login

1. Open FloodWatch: http://localhost/Flood%20Watch%20Community%20Based%20Monitoring%20System/login.html
2. Use demo credentials:
   - **Email**: demo@floodwatch.local
   - **Password**: Demo1234
3. You should see: "Welcome Back!" ✅

---

## 🔧 XAMPP Services Management

### Starting XAMPP Services

**Windows:**
- Option 1: XAMPP Control Panel → Click "Start" for Apache & MySQL
- Option 2: Command Prompt (as Administrator):
  ```bash
  net start Apache2.4
  net start MySQL80
  ```

### Stopping XAMPP Services

**Windows:**
- Option 1: XAMPP Control Panel → Click "Stop"
- Option 2: Command Prompt (as Administrator):
  ```bash
  net stop Apache2.4
  net stop MySQL80
  ```

---

## 📋 Troubleshooting

### Connection Error: "Connection failed"

**Fix:**
1. Verify MySQL is running (green checkmark in XAMPP Control Panel)
2. Verify database exists:
   - Go to phpMyAdmin → Check left sidebar for "floodwatch_db"
3. Re-import the SQL file if needed

### Error: "Access denied for user 'root'@'localhost'"

**Fix:**
1. The default password should be empty
2. In `database.php`, line 3 should be: `define('DB_PASS', '');`
3. Verify in phpMyAdmin it shows: "User: root@localhost"

### Error: "Unknown database 'floodwatch_db'"

**Fix:**
1. The database wasn't imported
2. Follow Step 2 above to import `floodwatch_setup.sql`

### Page won't load at all

**Fix:**
1. Check Apache is running (XAMPP Control Panel)
2. Verify URL: `http://localhost/Flood%20Watch%20Community%20Based%20Monitoring%20System/login.html`
3. Check browser console (F12) for errors
4. Check Apache error log: `C:\xampp\apache\logs\error.log`

---

## 📊 Database Details

### Users Table Fields
```
user_id          - Auto-increment ID
fullName         - User's full name
email            - Unique email address
password_hash    - Bcrypt hashed password
barangay         - Geographic barangay location
is_active        - 1=active, 0=inactive
created_at       - Account creation timestamp
updated_at       - Last update timestamp
```

### Reports Table Fields
```
report_id        - Auto-increment ID
user_id          - Foreign key to users table
title            - Report title
description      - Detailed description
location         - Text location
latitude         - Decimal latitude coordinate
longitude        - Decimal longitude coordinate
severity         - Low, Medium, High, Critical
status           - Active, Resolved, Under Review
image_url        - URL to report image
created_at       - Report creation timestamp
updated_at       - Last update timestamp
```

---

## ✨ Next Steps After Setup

1. ✅ Database configured
2. ✅ Demo user created (demo@floodwatch.local)
3. 📝 Create additional user accounts via registration
4. 📊 Add flood reports and test dashboard
5. 🌐 Configure weather API integration (if needed)

---

## 🆘 Getting Help

If you encounter issues:

1. Check XAMPP Control Panel for running services
2. View MySQL error log: `C:\xampp\mysql\data\*.err`
3. View Apache error log: `C:\xampp\apache\logs\error.log`
4. Check browser console: Press F12 in browser
5. Verify database.php has correct credentials

---

**Setup Complete!** 🎉 Your FloodWatch system is ready to use.
