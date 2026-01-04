# DevShowcase - Backend Setup Guide

This document explains how to set up and use the PHP backend for the DevShowcase portfolio platform.

## 📋 Prerequisites

- PHP 7.4 or higher
- MySQL 5.7 or higher (or MariaDB 10.2+)
- Apache/Nginx web server
- PDO MySQL extension enabled
- File uploads enabled in PHP (`upload_max_filesize`, `post_max_size`)

## 🗄️ Database Setup

1. **Create Database:**
   ```sql
   CREATE DATABASE devshowcase_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
   ```

2. **Import Schema:**
   ```bash
   mysql -u root -p devshowcase_db < database.sql
   ```
   Or manually run the SQL commands from `database.sql` in your MySQL client.

## ⚙️ Configuration

1. **Edit Database Credentials:**
   
   Open `config/config.php` and update these constants:
   ```php
   define('DB_HOST', 'localhost');
   define('DB_NAME', 'devshowcase_db');
   define('DB_USER', 'your_username');  // Change this
   define('DB_PASS', 'your_password');  // Change this
   ```

2. **Set Base URL:**
   
   Update the `BASE_URL` constant in `config/config.php`:
   ```php
   define('BASE_URL', 'http://localhost/DevShowcase/');
   ```

3. **Create Upload Directories:**
   
   The upload directories are automatically created, but ensure they have write permissions:
   ```bash
   mkdir -p uploads/profiles uploads/documents uploads/projects
   chmod 755 uploads/profiles uploads/documents uploads/projects
   ```

## 📁 Project Structure

```
DevShowcase/
├── config/
│   └── config.php          # Database configuration
├── helpers/
│   └── helpers.php         # Utility functions
├── auth/
│   ├── register.php        # User registration
│   ├── login.php           # User authentication
│   └── logout.php          # Session termination
├── profile/
│   └── profile.php         # Profile management
├── projects/
│   └── projects.php        # Projects CRUD
├── documents/
│   └── documents.php       # Documents CRUD
├── uploads/                # Uploaded files (auto-created)
│   ├── profiles/
│   ├── documents/
│   └── projects/
└── database.sql            # Database schema
```

## 🔌 API Endpoints

### Authentication

#### Register
- **URL:** `auth/register.php`
- **Method:** POST
- **Content-Type:** `application/x-www-form-urlencoded` or `multipart/form-data`
- **Parameters:**
  - `username` (required)
  - `email` (required)
  - `password` (required)
  - `confirm_password` (required)
  - `first_name` (optional)
  - `last_name` (optional)
  - `bio` (optional)
  - `github_url` (optional)
  - `job_title` (optional)
  - `profile_photo` (optional, file upload)

#### Login
- **URL:** `auth/login.php`
- **Method:** POST
- **Parameters:**
  - `email` (required)
  - `password` (required)
  - `remember_me` (optional, checkbox)

#### Logout
- **URL:** `auth/logout.php`
- **Method:** GET

### Profile

#### Get/Update Profile
- **URL:** `profile/profile.php`
- **Method:** GET (fetch) or POST (update)
- **POST Parameters:**
  - `action` = `update_profile` or `update_skills`
  - Profile fields (for update_profile)
  - `skills` (JSON array or comma-separated, for update_skills)

### Projects

#### List Projects
- **URL:** `projects/projects.php?action=list`
- **Method:** GET

#### Get Single Project
- **URL:** `projects/projects.php?action=get&id={project_id}`
- **Method:** GET

#### Create Project
- **URL:** `projects/projects.php?action=add`
- **Method:** POST
- **Content-Type:** `multipart/form-data` (for file uploads)
- **Parameters:**
  - `title` (required)
  - `description` (required)
  - `technologies` (optional, comma-separated or JSON array)
  - `github_url` (optional)
  - `screenshot` (optional, file upload)

#### Update Project
- **URL:** `projects/projects.php?action=update`
- **Method:** POST
- **Content-Type:** `multipart/form-data`
- **Parameters:**
  - `id` (required)
  - Same as create + `id`

#### Delete Project
- **URL:** `projects/projects.php?action=delete&id={project_id}`
- **Method:** GET

### Documents

#### List Documents
- **URL:** `documents/documents.php?action=list`
- **Method:** GET

#### Get Single Document
- **URL:** `documents/documents.php?action=get&id={document_id}`
- **Method:** GET

#### Upload Document
- **URL:** `documents/documents.php?action=upload`
- **Method:** POST
- **Content-Type:** `multipart/form-data`
- **Parameters:**
  - `title` (required)
  - `description` (optional)
  - `document_type` (optional)
  - `document_file` (required, file upload)

#### Update Document
- **URL:** `documents/documents.php?action=update`
- **Method:** POST
- **Parameters:**
  - `id` (required)
  - `title` (required)
  - `description` (optional)
  - `document_type` (optional)

#### Delete Document
- **URL:** `documents/documents.php?action=delete&id={document_id}`
- **Method:** GET

#### Download Document
- **URL:** `documents/documents.php?action=download&id={document_id}`
- **Method:** GET

## 🔗 Frontend Integration

### Example: AJAX Login

```javascript
// In your frontend JavaScript
async function login(email, password) {
    const formData = new FormData();
    formData.append('email', email);
    formData.append('password', password);
    
    const response = await fetch('auth/login.php', {
        method: 'POST',
        body: formData
    });
    
    const data = await response.json();
    
    if (data.success) {
        window.location.href = data.redirect || 'profile/profile.php';
    } else {
        // Display errors
        console.error(data.errors);
    }
}
```

### Example: Fetch Projects

```javascript
async function loadProjects() {
    const response = await fetch('projects/projects.php?action=list');
    const data = await response.json();
    
    if (data.success) {
        // Display projects
        data.data.projects.forEach(project => {
            // Render project card
        });
    }
}
```

### Example: Create Project with File Upload

```javascript
async function createProject(projectData) {
    const formData = new FormData();
    formData.append('action', 'add');
    formData.append('title', projectData.title);
    formData.append('description', projectData.description);
    formData.append('technologies', projectData.technologies);
    formData.append('github_url', projectData.githubUrl);
    
    if (projectData.screenshot) {
        formData.append('screenshot', projectData.screenshot);
    }
    
    const response = await fetch('projects/projects.php?action=add', {
        method: 'POST',
        body: formData
    });
    
    const data = await response.json();
    return data;
}
```

## 🔒 Security Features

- ✅ Password hashing using `password_hash()` (bcrypt)
- ✅ Prepared statements (PDO) to prevent SQL injection
- ✅ Input sanitization and validation
- ✅ File type and size validation
- ✅ Session management with regeneration
- ✅ CSRF protection (TODO: implement tokens)
- ✅ XSS prevention via `htmlspecialchars()`

## 📝 Session Management

Sessions are automatically started when `config.php` is included. User data is stored in `$_SESSION`:

```php
$_SESSION['user_id']
$_SESSION['username']
$_SESSION['email']
$_SESSION['first_name']
$_SESSION['last_name']
// ... etc
```

Use `isLoggedIn()` helper function to check authentication status.

## 🛠️ Helper Functions

Common helper functions available in `helpers/helpers.php`:

- `sanitizeInput($data)` - Sanitize user input
- `validateEmail($email)` - Validate email format
- `validateURL($url)` - Validate URL format
- `isLoggedIn()` - Check if user is logged in
- `requireLogin($redirectUrl)` - Require authentication
- `uploadFile($file, $uploadDir, $prefix)` - Upload file securely
- `deleteFile($filePath)` - Delete file from server
- `jsonResponse($success, $message, $data)` - Send JSON response

## ⚠️ TODOs for Future Enhancement

1. **CSRF Protection:** Add CSRF tokens to forms
2. **Email Verification:** Send verification emails on registration
3. **Password Reset:** Implement password reset functionality
4. **Remember Me:** Complete remember token implementation
5. **Rate Limiting:** Add rate limiting to prevent abuse
6. **File Compression:** Compress images on upload
7. **API Versioning:** Implement API versioning system
8. **Logging:** Enhanced error logging system
9. **Caching:** Add caching for frequently accessed data
10. **Search:** Add search functionality for projects/documents

## 🐛 Troubleshooting

### Database Connection Error
- Check database credentials in `config/config.php`
- Verify MySQL service is running
- Ensure database exists

### File Upload Fails
- Check `php.ini` settings: `upload_max_filesize` and `post_max_size`
- Verify upload directories have write permissions (755 or 777)
- Check file size limits in `config.php`

### Session Not Working
- Ensure sessions are enabled in PHP
- Check `session.save_path` in `php.ini`
- Verify cookie settings

### 404 Errors on API Calls
- Check URL paths match your server structure
- Verify `.htaccess` rules if using Apache
- Check PHP routing configuration

## 📄 License

This project is part of the DevShowcase portfolio platform.

