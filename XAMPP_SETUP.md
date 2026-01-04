# XAMPP Setup Guide for DevShowcase

This guide will help you set up the DevShowcase backend using XAMPP.

## 🚀 Step 1: Start XAMPP Services

### Option A: Using XAMPP Control Panel (GUI)
1. Open XAMPP Control Panel:
   ```bash
   sudo /opt/lampp/manager-linux-x64.run
   ```
   Or if you have it in your applications menu, launch it from there.

2. Start **Apache** and **MySQL** services from the control panel.

### Option B: Using Command Line
```bash
# Start Apache and MySQL
sudo /opt/lampp/lampp start

# Or start individually:
sudo /opt/lampp/lampp startapache
sudo /opt/lampp/lampp startmysql

# Check status
sudo /opt/lampp/lampp status
```

## 🗄️ Step 2: Create Database

### Option A: Using Command Line

1. **Create the database:**
   ```bash
   /opt/lampp/bin/mysql -u root -e "CREATE DATABASE IF NOT EXISTS devshowcase_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
   ```

2. **Import the schema:**
   ```bash
   cd ~/DevShowcase
   /opt/lampp/bin/mysql -u root devshowcase_db < database.sql
   ```

   **Note:** XAMPP MySQL typically has **no password** for root by default. If prompted for a password and you haven't set one, just press Enter.

### Option B: Using phpMyAdmin (Web Interface)

1. **Open phpMyAdmin:**
   - Open your browser and go to: `http://localhost/phpmyadmin`

2. **Create database:**
   - Click on "New" in the left sidebar
   - Database name: `devshowcase_db`
   - Collation: `utf8mb4_unicode_ci`
   - Click "Create"

3. **Import schema:**
   - Select `devshowcase_db` database
   - Click on "Import" tab
   - Click "Choose File" and select `database.sql` from your DevShowcase folder
   - Click "Go" to import

## ⚙️ Step 3: Configure PHP Backend

Edit `config/config.php` and update these settings:

```php
// XAMPP typically uses these defaults:
define('DB_HOST', 'localhost');
define('DB_NAME', 'devshowcase_db');
define('DB_USER', 'root');          // XAMPP default
define('DB_PASS', '');              // XAMPP default (empty password)

// Update base URL to match your XAMPP setup:
define('BASE_URL', 'http://localhost/DevShowcase/');
```

**If you set a MySQL root password**, update `DB_PASS` accordingly.

## 📁 Step 4: Move Project to XAMPP htdocs (if needed)

XAMPP serves files from `/opt/lampp/htdocs/`. You have two options:

### Option A: Keep project in home directory (using symlink)
```bash
sudo ln -s /home/yassin/DevShowcase /opt/lampp/htdocs/DevShowcase
```

### Option B: Move project to htdocs
```bash
sudo cp -r /home/yassin/DevShowcase /opt/lampp/htdocs/
sudo chown -R $USER:$USER /opt/lampp/htdocs/DevShowcase
```

### Option C: Configure Apache virtual host (Advanced)
This allows you to keep the project anywhere and access it via a custom URL.

## 🌐 Step 5: Access Your Application

Once everything is set up:

- **Frontend:** `http://localhost/DevShowcase/` (or your configured URL)
- **phpMyAdmin:** `http://localhost/phpmyadmin`

## 🔧 Troubleshooting

### MySQL won't start
```bash
# Check if port 3306 is already in use
sudo netstat -tulpn | grep 3306

# Stop conflicting MySQL service
sudo systemctl stop mysql  # If system MySQL is running

# Then start XAMPP MySQL
sudo /opt/lampp/lampp startmysql
```

### Permission denied errors
```bash
# Fix upload directory permissions
cd ~/DevShowcase
mkdir -p uploads/profiles uploads/documents uploads/projects
chmod -R 755 uploads/
```

### phpMyAdmin access denied
If you get "Access denied" errors:
- XAMPP's MySQL root user should work with empty password
- If you set a password, update it in `config/config.php`

### File upload issues
Check PHP settings in `/opt/lampp/etc/php.ini`:
```ini
upload_max_filesize = 10M
post_max_size = 10M
```

Then restart Apache:
```bash
sudo /opt/lampp/lampp restartapache
```

## ✅ Quick Test

1. **Test database connection:**
   ```bash
   /opt/lampp/bin/mysql -u root devshowcase_db -e "SHOW TABLES;"
   ```
   You should see: `users`, `projects`, `documents`

2. **Test PHP:**
   Create a test file `test.php` in your project root:
   ```php
   <?php
   require_once 'config/config.php';
   $pdo = getDBConnection();
   echo "Database connection successful!";
   ?>
   ```
   Access: `http://localhost/DevShowcase/test.php`

## 📝 Quick Commands Reference

```bash
# Start XAMPP
sudo /opt/lampp/lampp start

# Stop XAMPP
sudo /opt/lampp/lampp stop

# Restart XAMPP
sudo /opt/lampp/lampp restart

# Check status
sudo /opt/lampp/lampp status

# Access MySQL CLI
/opt/lampp/bin/mysql -u root devshowcase_db

# Import database
/opt/lampp/bin/mysql -u root devshowcase_db < database.sql
```

## 🎯 Next Steps

1. ✅ Start XAMPP services
2. ✅ Create and import database
3. ✅ Update `config/config.php`
4. ✅ Test the application
5. 📖 See `INTEGRATION_GUIDE.md` for connecting frontend to backend

