# DevShowcase - Quick Start Guide

## 🚀 How to Run the Project

### Prerequisites
- ✅ XAMPP installed (you have it at `/opt/lampp`)
- ✅ MySQL database created (you've already done this)
- ✅ Project files in place

### Step-by-Step Instructions

#### 1. **Start XAMPP Services**

Open your terminal and run:

```bash
sudo /opt/lampp/lampp start
```

This starts both Apache (web server) and MySQL (database).

**Verify services are running:**
```bash
sudo /opt/lampp/lampp status
```

You should see:
- Apache: **running**
- MySQL: **running**

#### 2. **Ensure Project is in XAMPP Directory**

Your project should be accessible at one of these locations:

**Option A:** If project is in `/opt/lampp/htdocs/DevShowcase/` (recommended)
- Access via: `http://localhost/DevShowcase/`

**Option B:** If project is in your home directory `/home/yassin/DevShowcase/`
- Create a symlink:
  ```bash
  sudo ln -s /home/yassin/DevShowcase /opt/lampp/htdocs/DevShowcase
  ```
- Then access via: `http://localhost/DevShowcase/`

#### 3. **Fix Upload Directory Permissions**

Run the fix script:

```bash
cd ~/DevShowcase  # or /opt/lampp/htdocs/DevShowcase
./fix_permissions.sh
```

Or manually:
```bash
sudo mkdir -p /opt/lampp/htdocs/DevShowcase/uploads/{profiles,documents,projects}
sudo chmod -R 777 /opt/lampp/htdocs/DevShowcase/uploads/
sudo chown -R daemon:daemon /opt/lampp/htdocs/DevShowcase/uploads/
```

#### 4. **Verify Database Connection**

Check if database is accessible:

```bash
/opt/lampp/bin/mysql -u root -e "USE devshowcase_db; SHOW TABLES;"
```

You should see: `documents`, `projects`, `users`

#### 5. **Access the Application**

Open your web browser and go to:

**Main URL:**
```
http://localhost/DevShowcase/
```

**Test/Debug Page:**
```
http://localhost/DevShowcase/test.php
```

**Direct Pages:**
- Login: `http://localhost/DevShowcase/login.html`
- Register: `http://localhost/DevShowcase/register.html`
- Profile: `http://localhost/DevShowcase/profile.html`
- Projects: `http://localhost/DevShowcase/projects.html`
- Documents: `http://localhost/DevShowcase/documents.html`

#### 6. **First Time Setup**

1. **Register a New Account:**
   - Go to `http://localhost/DevShowcase/register.html`
   - Fill in the form (username, email, password, etc.)
   - Upload a profile photo (optional)
   - Click "Create Account"

2. **Login:**
   - Go to `http://localhost/DevShowcase/login.html`
   - Enter your email and password
   - Click "Sign In"

3. **Complete Your Profile:**
   - Go to Profile page
   - Add bio, skills, GitHub URL, etc.
   - Click "Edit Profile" to update

4. **Add Projects:**
   - Go to Projects page
   - Click "Add New Project"
   - Fill in project details
   - Upload a screenshot (optional)

5. **Upload Documents:**
   - Go to Documents page
   - Click "Upload Document"
   - Select a file (PDF, DOCX, images, etc.)

## 🔧 Troubleshooting

### Problem: "This site can't be reached" or 404 error

**Solution:**
- Make sure XAMPP is running: `sudo /opt/lampp/lampp status`
- Check if project is in `/opt/lampp/htdocs/DevShowcase/`
- Try: `http://localhost/DevShowcase/test.php` to verify

### Problem: Database connection error

**Solution:**
- Check MySQL is running: `sudo /opt/lampp/lampp status`
- Verify database exists: `/opt/lampp/bin/mysql -u root -e "SHOW DATABASES;"`
- Check `config/config.php` has correct database credentials

### Problem: Upload directories not found

**Solution:**
- Run: `./fix_permissions.sh`
- Or manually create: `sudo mkdir -p /opt/lampp/htdocs/DevShowcase/uploads/{profiles,documents,projects}`
- Set permissions: `sudo chmod -R 777 /opt/lampp/htdocs/DevShowcase/uploads/`

### Problem: Permission denied errors

**Solution:**
```bash
# Fix upload directory permissions
sudo chmod -R 777 /opt/lampp/htdocs/DevShowcase/uploads/

# Fix file permissions
sudo chmod -R 755 /opt/lampp/htdocs/DevShowcase/
```

### Problem: Port 80 or 3306 already in use

**Solution:**
```bash
# Stop conflicting services
sudo systemctl stop apache2  # If system Apache is running
sudo systemctl stop mysql    # If system MySQL is running

# Then start XAMPP
sudo /opt/lampp/lampp start
```

## 📋 Quick Command Reference

```bash
# Start XAMPP
sudo /opt/lampp/lampp start

# Stop XAMPP
sudo /opt/lampp/lampp stop

# Restart XAMPP
sudo /opt/lampp/lampp restart

# Check status
sudo /opt/lampp/lampp status

# Access MySQL
/opt/lampp/bin/mysql -u root devshowcase_db

# View error logs
tail -f /opt/lampp/logs/error_log
tail -f /opt/lampp/logs/php_error_log
```

## ✅ Verification Checklist

Before using the app, verify:

- [ ] XAMPP Apache is running
- [ ] XAMPP MySQL is running
- [ ] Database `devshowcase_db` exists
- [ ] All 3 tables exist (users, projects, documents)
- [ ] Upload directories exist and are writable
- [ ] Can access `http://localhost/DevShowcase/test.php`
- [ ] All tests on test.php show ✓

## 🎯 Next Steps After Setup

1. ✅ Register your first user
2. ✅ Complete your profile
3. ✅ Add some projects
4. ✅ Upload documents
5. ✅ View your portfolio at the main page

## 📞 Need Help?

- Check `test.php` for system diagnostics
- See `TROUBLESHOOTING.md` for common issues
- Check browser console (F12) for JavaScript errors
- Check PHP error logs: `/opt/lampp/logs/php_error_log`

---

**Happy Coding! 🎉**

