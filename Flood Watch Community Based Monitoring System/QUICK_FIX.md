# ⚡ QUICK FIX - FloodWatch Connection Error

## Problem
❌ "Connection error. Please check your internet."

## Root Cause
The FloodWatch database (`floodwatch_db`) doesn't exist in MySQL yet.

---

## 🚀 SOLUTION (2 steps - 2 minutes)

### STEP 1: Import Database
1. Open phpMyAdmin: **http://localhost/phpmyadmin/**
2. Click **"Import"** (top menu)
3. Click **"Choose File"**
4. Select: **`floodwatch_setup.sql`**
   - Path: `c:\xampp\htdocs\Flood Watch Community Based Monitoring System\floodwatch_setup.sql`
5. Click **"Import"** button
6. Wait for green "✅ Import successful"

### STEP 2: Test Login
1. Go to: **http://localhost/Flood%20Watch%20Community%20Based%20Monitoring%20System/login.html**
2. Login with:
   - **Email:** demo@floodwatch.local
   - **Password:** Demo1234
3. Should see "Welcome Back!" ✅

---

## ✅ What Gets Created

```
DATABASE: floodwatch_db
├── users (table)
│   └── Demo User (demo@floodwatch.local)
└── reports (table)
    └── 3 sample reports
```

---

## 🔍 Verify It Worked

### In phpMyAdmin:
1. Left sidebar → Look for **"floodwatch_db"**
2. Click to expand
3. Should see 2 tables: **users**, **reports**
4. Click "users" table
5. Should see 1 row: "Demo User"

### In Browser:
1. Login page should show no errors
2. Login succeeds with demo credentials
3. Dashboard loads after login

---

## ⚙️ If Still Getting Error

1. **Check MySQL is running:**
   - XAMPP Control Panel → MySQL should have green checkmark

2. **Verify import was successful:**
   - phpMyAdmin → Look for "floodwatch_db" in left sidebar

3. **Check database.php:**
   - Open: `c:\xampp\htdocs\Flood Watch Community Based Monitoring System\database.php`
   - Line 9 should say: `define('DB_NAME', 'floodwatch_db');`
   - Line 10 should say: `define('DB_USER', 'root');`
   - Line 11 should say: `define('DB_PASS', '');` (empty password)

4. **Restart MySQL:**
   - XAMPP Control Panel → MySQL "Stop" → Wait → MySQL "Start"

---

## 📱 Access Points

Once fixed, access these URLs:

| Purpose | URL |
|---------|-----|
| 🔓 Login | http://localhost/Flood%20Watch%20Community%20Based%20Monitoring%20System/login.html |
| 📝 Register | http://localhost/Flood%20Watch%20Community%20Based%20Monitoring%20System/register.html |
| 📊 Dashboard | http://localhost/Flood%20Watch%20Community%20Based%20Monitoring%20System/index.html |
| 🛠️ phpMyAdmin | http://localhost/phpmyadmin/ |

---

## Demo Credentials
```
Email:    demo@floodwatch.local
Password: Demo1234
```

**That's it!** 🎉 Your system should be working now.
