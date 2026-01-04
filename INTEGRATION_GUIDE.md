# Frontend-Backend Integration Guide

This guide shows how to connect your existing HTML/CSS/JS frontend with the PHP backend.

## 🔗 Quick Integration Steps

### 1. Update Form Actions

#### Update `register.html`:
```html
<!-- Change form tag to: -->
<form id="registerForm" action="auth/register.php" method="POST" enctype="multipart/form-data">
```

#### Update `login.html`:
```html
<!-- Change form tag to: -->
<form id="loginForm" action="auth/login.php" method="POST">
```

### 2. Update JavaScript AJAX Calls

#### In `js/script.js`, update the login function:

```javascript
// Replace existing login form handler
const loginForm = document.getElementById('loginForm');
if (loginForm) {
    loginForm.addEventListener('submit', async function(e) {
        e.preventDefault();
        
        const formData = new FormData(this);
        
        try {
            const response = await fetch('auth/login.php', {
                method: 'POST',
                body: formData
            });
            
            const data = await response.json();
            
            if (data.success) {
                window.location.href = data.redirect || 'profile/profile.php';
            } else {
                // Display errors
                alert(data.errors.join('\n'));
            }
        } catch (error) {
            console.error('Login error:', error);
            alert('An error occurred. Please try again.');
        }
    });
}
```

#### Update registration function:

```javascript
const registerForm = document.getElementById('registerForm');
if (registerForm) {
    registerForm.addEventListener('submit', async function(e) {
        e.preventDefault();
        
        // Validate password match
        const password = document.getElementById('regPassword').value;
        const confirmPassword = document.getElementById('confirmPassword').value;
        
        if (password !== confirmPassword) {
            alert('Passwords do not match!');
            return;
        }
        
        const formData = new FormData(this);
        
        try {
            const response = await fetch('auth/register.php', {
                method: 'POST',
                body: formData
            });
            
            const data = await response.json();
            
            if (data.success) {
                alert('Registration successful! Redirecting to login...');
                window.location.href = data.redirect || 'login.html';
            } else {
                alert(data.errors.join('\n'));
            }
        } catch (error) {
            console.error('Registration error:', error);
            alert('An error occurred. Please try again.');
        }
    });
}
```

### 3. Load Projects Dynamically

#### Update `projects.html` to fetch projects from backend:

```javascript
// Add this function to js/script.js
async function loadProjects() {
    try {
        const response = await fetch('projects/projects.php?action=list');
        const data = await response.json();
        
        if (data.success) {
            const projectsContainer = document.querySelector('.row.g-4');
            if (projectsContainer) {
                projectsContainer.innerHTML = '';
                
                data.data.projects.forEach(project => {
                    const projectCard = createProjectCard(project);
                    projectsContainer.appendChild(projectCard);
                });
            }
        }
    } catch (error) {
        console.error('Error loading projects:', error);
    }
}

function createProjectCard(project) {
    const col = document.createElement('div');
    col.className = 'col-md-6 col-lg-4';
    
    const technologies = Array.isArray(project.technologies) 
        ? project.technologies 
        : project.technologies.split(',').map(t => t.trim());
    
    const badges = technologies.map(tech => 
        `<span class="badge bg-primary-subtle text-primary me-1">${tech}</span>`
    ).join('');
    
    const screenshot = project.screenshot_url 
        ? `<img src="${project.screenshot_url}" class="card-img-top" alt="${project.title}">`
        : `<img src="https://via.placeholder.com/400x250/4a90e2/ffffff?text=${encodeURIComponent(project.title)}" class="card-img-top" alt="${project.title}">`;
    
    col.innerHTML = `
        <div class="card h-100 shadow-sm hover-shadow border-0">
            ${screenshot}
            <div class="card-body d-flex flex-column">
                <h5 class="card-title fw-bold">${escapeHtml(project.title)}</h5>
                <p class="card-text text-muted flex-grow-1">${escapeHtml(project.description)}</p>
                <div class="mb-3">${badges}</div>
                <div class="d-flex gap-2">
                    ${project.github_url ? `<a href="${project.github_url}" target="_blank" class="btn btn-outline-primary btn-sm flex-fill">
                        <i class="bi bi-github me-1"></i>GitHub
                    </a>` : ''}
                    <button class="btn btn-outline-secondary btn-sm" onclick="editProject(${project.id})">
                        <i class="bi bi-pencil"></i>
                    </button>
                    <button class="btn btn-outline-danger btn-sm" onclick="deleteProject(${project.id})">
                        <i class="bi bi-trash"></i>
                    </button>
                </div>
            </div>
        </div>
    `;
    
    return col;
}

// Call on page load
document.addEventListener('DOMContentLoaded', () => {
    if (window.location.pathname.includes('projects.html')) {
        loadProjects();
    }
});
```

### 4. Update Add Project Function

```javascript
async function addProject() {
    const form = document.getElementById('addProjectForm');
    const formData = new FormData(form);
    formData.append('action', 'add');
    
    try {
        const response = await fetch('projects/projects.php?action=add', {
            method: 'POST',
            body: formData
        });
        
        const data = await response.json();
        
        if (data.success) {
            alert('Project added successfully!');
            const modal = bootstrap.Modal.getInstance(document.getElementById('addProjectModal'));
            if (modal) modal.hide();
            form.reset();
            loadProjects(); // Reload projects
        } else {
            alert(data.errors ? data.errors.join('\n') : data.message);
        }
    } catch (error) {
        console.error('Error adding project:', error);
        alert('An error occurred. Please try again.');
    }
}
```

### 5. Load User Profile

#### Add to `profile.html`:

```javascript
async function loadProfile() {
    try {
        const response = await fetch('profile/profile.php');
        const data = await response.json();
        
        if (data.success) {
            const user = data.user;
            
            // Update profile picture
            if (user.profile_photo) {
                document.querySelector('.profile-img-large').src = user.profile_photo;
            }
            
            // Update name
            document.querySelector('h3.fw-bold').textContent = 
                `${user.first_name || ''} ${user.last_name || ''}`.trim() || user.username;
            
            // Update job title
            if (user.job_title) {
                document.querySelector('.text-muted.mb-3').textContent = user.job_title;
            }
            
            // Update bio
            if (user.bio) {
                document.querySelector('.card-body p.mb-0').textContent = user.bio;
            }
            
            // Update GitHub link
            if (user.github_url) {
                const githubLink = document.querySelector('a[href*="github"]');
                if (githubLink) githubLink.href = user.github_url;
            }
            
            // Update skills
            if (data.skills && Array.isArray(data.skills)) {
                const skillsContainer = document.getElementById('skillsContainer');
                skillsContainer.innerHTML = data.skills.map(skill => 
                    `<span class="badge bg-primary me-2 mb-2 px-3 py-2">${escapeHtml(skill)}</span>`
                ).join('');
            }
        }
    } catch (error) {
        console.error('Error loading profile:', error);
    }
}

// Call on page load
if (window.location.pathname.includes('profile.html')) {
    loadProfile();
}
```

### 6. Handle Session Messages

Add this to display success/error messages from backend:

```javascript
// Display session messages
document.addEventListener('DOMContentLoaded', () => {
    // This would require a PHP page that outputs session messages
    // Or use AJAX to check for messages on page load
});
```

### 7. Protect Pages with Authentication

Add this check at the top of protected pages (or create a PHP wrapper):

```javascript
// Check if user is logged in
async function checkAuth() {
    try {
        const response = await fetch('auth/check.php'); // You'll need to create this
        const data = await response.json();
        
        if (!data.logged_in) {
            window.location.href = 'login.html';
        }
    } catch (error) {
        console.error('Auth check error:', error);
    }
}
```

Or better, create a simple `auth/check.php`:

```php
<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../helpers/helpers.php';

header('Content-Type: application/json');
echo json_encode([
    'logged_in' => isLoggedIn(),
    'user' => getCurrentUser()
]);
```

## 📝 Notes

- All AJAX calls should include proper error handling
- Update form enctype to `multipart/form-data` when uploading files
- Remember to escape HTML output to prevent XSS
- Check user authentication before allowing CRUD operations
- Update logout link to point to `auth/logout.php`

## 🔄 Session Handling

Since PHP uses sessions, you may need to:
1. Include session checks in PHP wrapper files
2. Or create API endpoints that return JSON
3. Handle CORS if frontend and backend are on different domains

