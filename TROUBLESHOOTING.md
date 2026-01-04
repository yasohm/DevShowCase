# Troubleshooting Guide

## Common Error: "An error occurred. Please try again."

This generic error can occur for several reasons. Follow these steps to diagnose:

### 1. **Check Browser Console**
Open your browser's Developer Tools (F12) and check the Console tab for detailed error messages.

### 2. **Verify Access via HTTP**
**Important:** The application MUST be accessed via HTTP, not file://

❌ Wrong: `file:///home/yassin/DevShowcase/index.html`  
✅ Correct: `http://localhost/DevShowcase/index.html`

If accessing via file://, AJAX requests will fail due to CORS restrictions.

### 3. **Check XAMPP is Running**
```bash
sudo /opt/lampp/lampp status
```

Start XAMPP if not running:
```bash
sudo /opt/lampp/lampp start
```

### 4. **Verify Database Connection**
Check if database exists and is accessible:
```bash
/opt/lampp/bin/mysql -u root -e "USE devshowcase_db; SHOW TABLES;"
```

If database doesn't exist, run the setup:
```bash
./setup_xampp.sh
```

### 5. **Check PHP Errors**
Enable error display in `config/config.php` temporarily:
```php
// Add at the top of config.php
ini_set('display_errors', 1);
error_reporting(E_ALL);
```

Or check XAMPP error logs:
```bash
tail -f /opt/lampp/logs/error_log
tail -f /opt/lampp/logs/php_error_log
```

### 6. **Verify File Permissions**
```bash
chmod -R 755 /home/yassin/DevShowcase
chmod -R 777 uploads/  # If uploads directory exists
```

### 7. **Check PHP File Paths**
Ensure PHP files are accessible. Test directly in browser:
- `http://localhost/DevShowcase/auth/check.php`
- `http://localhost/DevShowcase/config/config.php`

If you see "Database connection failed", check database credentials in `config/config.php`.

### 8. **Network Tab Check**
In browser DevTools → Network tab:
- Check if requests are being sent
- Check response status codes (200 = OK, 404 = Not Found, 500 = Server Error)
- Check response content for PHP errors

### 9. **Specific Error Scenarios**

#### Login Fails
- Check database connection
- Verify user exists in database
- Check password hashing

#### Registration Fails
- Verify all required fields are present
- Check file upload permissions
- Verify username/email don't already exist

#### Projects/Documents Not Loading
- Check user is logged in (session)
- Verify database tables exist
- Check PHP file permissions

### 10. **Quick Debug Test**

Create `test.php` in root directory:
```php
<?php
require_once 'config/config.php';
try {
    $pdo = getDBConnection();
    echo "✓ Database connection successful!<br>";
    echo "✓ Config loaded successfully!<br>";
    echo "✓ Session started: " . (session_status() === PHP_SESSION_ACTIVE ? "Yes" : "No");
} catch (Exception $e) {
    echo "✗ Error: " . $e->getMessage();
}
?>
```

Access: `http://localhost/DevShowcase/test.php`

### 11. **Enable Detailed Error Messages**

The updated JavaScript now shows more specific error messages. Check:
- Browser console for detailed error logs
- Network tab for HTTP status codes
- Response content for PHP error messages

## Still Having Issues?

1. **Check all logs:**
   ```bash
   # PHP errors
   tail -f /opt/lampp/logs/php_error_log
   
   # Apache errors
   tail -f /opt/lampp/logs/error_log
   
   # Check PHP version
   /opt/lampp/bin/php -v
   ```

2. **Verify project location:**
   - Project should be in `/opt/lampp/htdocs/DevShowcase/` OR
   - Create symlink: `sudo ln -s /home/yassin/DevShowcase /opt/lampp/htdocs/DevShowcase`

3. **Test PHP separately:**
   ```bash
   /opt/lampp/bin/php -r "echo 'PHP is working';"
   ```

## Contact for Support

Include these details when asking for help:
- Browser console errors
- Network tab response
- PHP error logs
- Database connection status
- XAMPP status

