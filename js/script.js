/**
 * DevShowcase - Custom JavaScript
 * Frontend functionality with backend integration
 */

// Wait for DOM to be fully loaded
document.addEventListener('DOMContentLoaded', function () {
    initializeApp();
});

/**
 * Initialize the application
 */
function initializeApp() {
    setupPasswordToggles();
    setupFormValidation();
    setupNavbarActiveState();
    checkAuth();

    // Load page-specific data
    const currentPage = window.location.pathname.split('/').pop() || 'index.html';

    if (currentPage === 'index.html' || currentPage === '' || currentPage.includes('index')) {
        loadPublicShowcase();
    } else if (currentPage.includes('profile')) {
        loadProfile();
    } else if (currentPage.includes('projects')) {
        loadProjects();
    } else if (currentPage.includes('documents')) {
        loadDocuments();
    }
}

/**
 * Check authentication status
 */
async function checkAuth() {
    try {
        const response = await fetch('auth/check.php', {
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        });

        if (!response.ok) {
            return false;
        }

        const data = await response.json();

        // Update navbar based on auth status
        updateNavbarAuth(data.logged_in);

        return data.logged_in;
    } catch (error) {
        console.error('Auth check error:', error);
        return false;
    }
}

/**
 * Update navbar based on authentication status
 */
function updateNavbarAuth(isLoggedIn) {
    const navLinks = document.querySelectorAll('.navbar-nav');
    if (navLinks.length > 0) {
        const nav = navLinks[0];
        const loginLink = Array.from(nav.querySelectorAll('a')).find(a => a.textContent.includes('Login'));

        if (isLoggedIn) {
            // Replace Login with Logout
            if (loginLink) {
                loginLink.href = 'auth/logout.php';
                loginLink.textContent = 'Logout';
            }
        } else {
            if (loginLink) {
                loginLink.href = 'login.html';
                loginLink.textContent = 'Login';
            }
        }
    }
}

/**
 * Setup password visibility toggles
 */
function setupPasswordToggles() {
    // Login page password toggle
    const togglePassword = document.getElementById('togglePassword');
    const passwordInput = document.getElementById('password');

    if (togglePassword && passwordInput) {
        togglePassword.addEventListener('click', function () {
            const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
            passwordInput.setAttribute('type', type);

            const icon = this.querySelector('i');
            icon.classList.toggle('bi-eye');
            icon.classList.toggle('bi-eye-slash');
        });
    }

    // Register page password toggle
    const toggleRegPassword = document.getElementById('toggleRegPassword');
    const regPasswordInput = document.getElementById('regPassword');

    if (toggleRegPassword && regPasswordInput) {
        toggleRegPassword.addEventListener('click', function () {
            const type = regPasswordInput.getAttribute('type') === 'password' ? 'text' : 'password';
            regPasswordInput.setAttribute('type', type);

            const icon = this.querySelector('i');
            icon.classList.toggle('bi-eye');
            icon.classList.toggle('bi-eye-slash');
        });
    }
}

/**
 * Setup form validation and submission
 */
function setupFormValidation() {
    // Login form
    const loginForm = document.getElementById('loginForm');
    if (loginForm) {
        loginForm.addEventListener('submit', async function (e) {
            e.preventDefault();

            const formData = new FormData(this);
            const submitBtn = this.querySelector('button[type="submit"]');
            const originalBtnText = submitBtn.innerHTML;

            setButtonLoading(submitBtn, true);

            try {
                const response = await fetch('auth/login.php', {
                    method: 'POST',
                    body: formData,
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                });

                // Clone response BEFORE reading to avoid "body already read" error
                const responseClone = response.clone();

                // Check if response is OK - read from clone if not OK
                if (!response.ok) {
                    const errorText = await responseClone.text();
                    throw new Error(`HTTP error! status: ${response.status} - ${errorText}`);
                }

                // Try to parse JSON
                let data;
                try {
                    data = await response.json();
                } catch (jsonError) {
                    // If JSON parsing fails, try to read as text from the clone
                    const text = await responseClone.text();
                    console.error('JSON parse error. Response:', text);
                    throw new Error('Invalid response from server: ' + text.substring(0, 200));
                }

                if (data.success) {
                    showNotification('Login successful! Redirecting...', 'success');
                    setTimeout(() => {
                        console.log('Redirecting to:', data.redirect || 'profile.html');
                        window.location.replace(data.redirect || 'profile.html');
                    }, 1000);
                } else {
                    showNotification(data.errors ? data.errors.join('\n') : 'Login failed', 'error');
                }
            } catch (error) {
                console.error('Login error:', error);
                const errorMsg = error.message || 'An error occurred. Please try again.';
                showNotification(errorMsg, 'error');
            } finally {
                setButtonLoading(submitBtn, false);
                submitBtn.innerHTML = originalBtnText;
            }
        });
    }

    // Register form
    const registerForm = document.getElementById('registerForm');
    if (registerForm) {
        registerForm.addEventListener('submit', async function (e) {
            e.preventDefault();

            const password = document.getElementById('regPassword').value;
            const confirmPassword = document.getElementById('confirmPassword').value;

            if (password !== confirmPassword) {
                showNotification('Passwords do not match!', 'error');
                return;
            }

            const formData = new FormData(this);
            const submitBtn = this.querySelector('button[type="submit"]');
            const originalBtnText = submitBtn.innerHTML;

            setButtonLoading(submitBtn, true);

            try {
                const response = await fetch('auth/register.php', {
                    method: 'POST',
                    body: formData,
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                });

                // Clone response BEFORE reading to avoid "body already read" error
                const responseClone = response.clone();

                if (!response.ok) {
                    const errorText = await responseClone.text();
                    throw new Error(`HTTP error! status: ${response.status} - ${errorText}`);
                }

                // Try to parse JSON
                let data;
                try {
                    data = await response.json();
                } catch (jsonError) {
                    // If JSON parsing fails, try to read as text from the clone
                    const text = await responseClone.text();
                    console.error('JSON parse error. Response:', text);
                    throw new Error('Invalid response from server: ' + text.substring(0, 200));
                }

                if (data.success) {
                    showNotification('Registration successful! Redirecting to login...', 'success');
                    setTimeout(() => {
                        window.location.replace(data.redirect || 'login.html');
                    }, 2000);
                } else {
                    showNotification(data.errors ? data.errors.join('\n') : 'Registration failed', 'error');
                }
            } catch (error) {
                console.error('Registration error:', error);
                const errorMsg = error.message || 'An error occurred. Please try again.';
                showNotification(errorMsg, 'error');
            } finally {
                setButtonLoading(submitBtn, false);
                submitBtn.innerHTML = originalBtnText;
            }
        });
    }
}

/**
 * Set active state for navigation items
 */
function setupNavbarActiveState() {
    const currentPage = window.location.pathname.split('/').pop() || 'index.html';
    const navLinks = document.querySelectorAll('.navbar-nav .nav-link');

    navLinks.forEach(link => {
        link.classList.remove('active');
        const linkHref = link.getAttribute('href');
        if (linkHref === currentPage ||
            (currentPage === '' && linkHref === 'index.html') ||
            (currentPage === 'index.html' && linkHref === 'index.html')) {
            link.classList.add('active');
        }
    });
}

/**
 * Load public showcase (index page)
 */
async function loadPublicShowcase() {
    try {
        // Check if user is logged in to show their profile
        const authCheck = await fetch('auth/check.php', {
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        });
        const authData = await authCheck.json();

        if (authData.logged_in) {
            // Load logged-in user's profile for showcase
            const profileResponse = await fetch('profile/profile.php', {
                method: 'GET',
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });

            if (!profileResponse.ok) {
                throw new Error(`HTTP ${profileResponse.status}: Failed to load profile`);
            }

            const profileData = await profileResponse.json();

            if (profileData.success && profileData.user) {
                displayShowcase(profileData.user, profileData.skills || []);

                // Load projects
                const projectsResponse = await fetch('projects/projects.php?action=list', {
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                });

                if (!projectsResponse.ok) {
                    throw new Error(`HTTP ${projectsResponse.status}: Failed to load projects`);
                }

                const projectsData = await projectsResponse.json();
                if (projectsData.success) {
                    displayProjects(projectsData.data.projects || [], 3); // Show max 3 on homepage
                }
            }
        } else {
            // Show placeholder or message to login
            showLoginPrompt();
        }
    } catch (error) {
        console.error('Error loading showcase:', error);
    }
}

/**
 * Display showcase data
 */
function displayShowcase(user, skills) {
    // Update profile picture
    const profileImg = document.querySelector('.profile-img, .profile-img-large');
    if (profileImg && user.profile_photo) {
        profileImg.src = user.profile_photo;
    } else if (profileImg) {
        profileImg.src = 'https://via.placeholder.com/300x300/4a90e2/ffffff?text=Profile';
    }

    // Update name
    const nameElements = document.querySelectorAll('h2.fw-bold, h3.fw-bold');
    if (nameElements.length > 0) {
        const fullName = `${user.first_name || ''} ${user.last_name || ''}`.trim() || user.username;
        nameElements[0].textContent = fullName;
    }

    // Update job title
    const jobTitleElements = document.querySelectorAll('.lead');
    if (jobTitleElements.length > 0 && user.job_title) {
        jobTitleElements[0].textContent = user.job_title;
    }

    // Update bio
    const bioElements = document.querySelectorAll('.lead.mb-4, p.lead.mb-4');
    if (bioElements.length > 0 && user.bio) {
        bioElements[bioElements.length - 1].textContent = user.bio;
    }

    // Update GitHub link
    const githubLinks = document.querySelectorAll('a[href*="github"]');
    githubLinks.forEach(link => {
        if (user.github_url) {
            link.href = user.github_url;
        } else {
            link.style.display = 'none';
        }
    });

    // Update skills
    if (skills && Array.isArray(skills) && skills.length > 0) {
        const skillsContainer = document.querySelector('.hero-section .mb-4, .mb-4 .badge').parentElement;
        if (skillsContainer) {
            skillsContainer.innerHTML = skills.map(skill =>
                `<span class="badge bg-light text-primary me-2 mb-2 px-3 py-2">${escapeHtml(skill)}</span>`
            ).join('');
        }
    }
}

/**
 * Show login prompt on showcase page
 */
function showLoginPrompt() {
    const heroSection = document.querySelector('.hero-section');
    if (heroSection) {
        heroSection.innerHTML = `
            <div class="container py-5 text-center">
                <h1 class="display-4 fw-bold mb-4">Welcome to DevShowcase</h1>
                <p class="lead mb-4">Please login or register to view your portfolio</p>
                <a href="login.html" class="btn btn-light btn-lg me-3">Login</a>
                <a href="register.html" class="btn btn-outline-light btn-lg">Register</a>
            </div>
        `;
    }

    // Hide projects section
    const projectsSection = document.querySelector('.bg-light');
    if (projectsSection) {
        projectsSection.style.display = 'none';
    }
}

/**
 * Display projects
 */
function displayProjects(projects, maxCount = null) {
    const container = document.querySelector('.row.g-4');
    if (!container) return;

    container.innerHTML = '';

    const projectsToShow = maxCount ? projects.slice(0, maxCount) : projects;

    if (projectsToShow.length === 0) {
        container.innerHTML = '<div class="col-12 text-center"><p class="text-muted">No projects yet. Add your first project!</p></div>';
        return;
    }

    projectsToShow.forEach(project => {
        const projectCard = createProjectCard(project);
        container.appendChild(projectCard);
    });
}

/**
 * Create project card element
 */
function createProjectCard(project) {
    const col = document.createElement('div');
    col.className = 'col-md-6 col-lg-4';

    const technologies = Array.isArray(project.technologies)
        ? project.technologies
        : (project.technologies ? project.technologies.split(',').map(t => t.trim()).filter(t => t) : []);

    const badges = technologies.length > 0
        ? technologies.map(tech => `<span class="badge bg-primary-subtle text-primary me-1">${escapeHtml(tech)}</span>`).join('')
        : '';

    const screenshot = project.screenshot_url
        ? `<img src="${project.screenshot_url}" class="card-img-top" alt="${escapeHtml(project.title)}" onerror="this.src='https://via.placeholder.com/400x250/4a90e2/ffffff?text=${encodeURIComponent(project.title)}'">`
        : `<img src="https://via.placeholder.com/400x250/4a90e2/ffffff?text=${encodeURIComponent(project.title)}" class="card-img-top" alt="${escapeHtml(project.title)}">`;

    const githubBtn = project.github_url
        ? `<a href="${project.github_url}" target="_blank" class="btn btn-outline-primary btn-sm flex-fill">
            <i class="bi bi-github me-1"></i>GitHub
          </a>`
        : '';

    const editDeleteBtns = window.location.pathname.includes('projects.html')
        ? `<button class="btn btn-outline-secondary btn-sm" onclick="editProject(${project.id})">
            <i class="bi bi-pencil"></i>
          </button>
          <button class="btn btn-outline-danger btn-sm" onclick="deleteProjectById(${project.id})">
            <i class="bi bi-trash"></i>
          </button>`
        : '';

    col.innerHTML = `
        <div class="card h-100 shadow-sm hover-shadow border-0">
            ${screenshot}
            <div class="card-body d-flex flex-column">
                <h5 class="card-title fw-bold">${escapeHtml(project.title)}</h5>
                <p class="card-text text-muted flex-grow-1">${escapeHtml(project.description)}</p>
                ${badges ? `<div class="mb-3">${badges}</div>` : ''}
                ${githubBtn || editDeleteBtns ? `<div class="d-flex gap-2">${githubBtn}${editDeleteBtns}</div>` : ''}
            </div>
        </div>
    `;

    return col;
}

/**
 * Load user profile
 */
async function loadProfile() {
    try {
        const response = await fetch('profile/profile.php', {
            method: 'GET',
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            }
        });

        if (!response.ok) {
            throw new Error(`HTTP ${response.status}: Failed to load profile`);
        }

        const data = await response.json();

        if (data.success && data.user) {
            displayProfile(data.user, data.skills || []);
        } else {
            // Redirect to login if not authenticated
            window.location.href = 'login.html';
        }
    } catch (error) {
        console.error('Error loading profile:', error);
        showNotification('Failed to load profile', 'error');
    }
}

/**
 * Display profile data
 */
function displayProfile(user, skills) {
    // Profile picture
    const profileImg = document.querySelector('.profile-img-large');
    if (profileImg) {
        profileImg.src = user.profile_photo || 'https://via.placeholder.com/200x200/4a90e2/ffffff?text=Profile';
    }

    // Name
    const nameElement = document.getElementById('profileName');
    if (nameElement) {
        const fullName = `${user.first_name || ''} ${user.last_name || ''}`.trim() || user.username;
        nameElement.textContent = fullName;
    }

    // Job title
    const jobTitleElement = document.getElementById('profileJobTitle');
    if (jobTitleElement) {
        if (user.job_title) {
            jobTitleElement.textContent = user.job_title;
        } else {
            jobTitleElement.textContent = 'No job title added';
        }
    }

    // GitHub link
    const githubLink = document.querySelector('a[href*="github"]');
    if (githubLink) {
        if (user.github_url) {
            githubLink.href = user.github_url;
            githubLink.style.display = '';
        } else {
            githubLink.style.display = 'none';
        }
    }

    // Bio
    const bioElements = document.querySelectorAll('.card-body p.mb-0');
    if (bioElements.length > 0 && user.bio) {
        bioElements[0].textContent = user.bio;
    } else if (bioElements.length > 0 && !user.bio) {
        bioElements[0].textContent = 'No bio yet. Click Edit Bio to add one.';
    }

    // Skills
    const skillsContainer = document.getElementById('skillsContainer');
    if (skillsContainer) {
        if (skills && Array.isArray(skills) && skills.length > 0) {
            skillsContainer.innerHTML = skills.map(skill =>
                `<span class="badge bg-primary me-2 mb-2 px-3 py-2">${escapeHtml(skill)}</span>`
            ).join('');
        } else {
            skillsContainer.innerHTML = '<p class="text-muted">No skills added yet. Click "Add Skills" to add them.</p>';
        }
    }

    // Contact Information
    const contactEmail = document.getElementById('contactEmail');
    if (contactEmail) {
        contactEmail.textContent = user.email || 'Not provided';
    }

    const contactGithub = document.getElementById('contactGithub');
    if (contactGithub) {
        if (user.github_url) {
            contactGithub.innerHTML = `<a href="${user.github_url}" target="_blank" class="text-decoration-none">${user.github_url}</a>`;
        } else {
            contactGithub.textContent = 'Not provided';
        }
    }

    // Populate edit modals
    populateEditModals(user, skills);
}

/**
 * Populate edit modals with current data
 */
function populateEditModals(user, skills) {
    // Profile edit modal
    const editName = document.getElementById('editName');
    const editTitle = document.getElementById('editTitle');
    const editGitHub = document.getElementById('editGitHub');

    if (editName) {
        const fullName = `${user.first_name || ''} ${user.last_name || ''}`.trim() || user.username;
        editName.value = fullName;
    }
    if (editTitle) editTitle.value = user.job_title || '';
    if (editGitHub) editGitHub.value = user.github_url || '';

    // Bio edit modal
    const editBio = document.getElementById('editBio');
    if (editBio) editBio.value = user.bio || '';

    // Skills edit modal
    const editSkills = document.getElementById('editSkills');
    if (editSkills) {
        if (skills && Array.isArray(skills)) {
            editSkills.value = skills.join(', ');
        } else {
            editSkills.value = '';
        }
    }
}

/**
 * Save profile changes (Name, Title, GitHub)
 */
async function saveProfileChanges() {
    const editName = document.getElementById('editName').value.trim();
    const editTitle = document.getElementById('editTitle').value.trim();
    const editGitHub = document.getElementById('editGitHub').value.trim();

    // Split name into first and last
    const nameParts = editName.split(' ');
    const firstName = nameParts[0] || '';
    const lastName = nameParts.slice(1).join(' ') || '';

    const formData = new FormData();
    formData.append('action', 'update_profile');
    formData.append('first_name', firstName);
    formData.append('last_name', lastName);
    formData.append('job_title', editTitle);
    formData.append('github_url', editGitHub);

    await sendProfileUpdate(formData, 'editProfileModal', 'Profile updated successfully!');
}

/**
 * Save Bio
 */
async function saveBio() {
    const editBio = document.getElementById('editBio').value.trim();

    const formData = new FormData();
    formData.append('action', 'update_profile');
    formData.append('bio', editBio);

    await sendProfileUpdate(formData, 'editBioModal', 'Bio updated successfully!');
}

/**
 * Save Skills
 */
async function saveSkills() {
    const editSkills = document.getElementById('editSkills').value.trim();

    // Convert comma-separated string to array
    const skillsArray = editSkills.split(',').map(skill => skill.trim()).filter(skill => skill !== '');

    const formData = new FormData();
    formData.append('action', 'update_skills');
    formData.append('skills', JSON.stringify(skillsArray));

    await sendProfileUpdate(formData, 'editSkillsModal', 'Skills updated successfully!');
}

/**
 * Helper to send profile updates
 */
async function sendProfileUpdate(formData, modalId, successMessage) {
    try {
        const response = await fetch('profile/profile.php', {
            method: 'POST',
            body: formData,
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            }
        });

        const data = await response.json();

        if (data.success) {
            showNotification(successMessage, 'success');
            // Close modal
            const modalElement = document.getElementById(modalId);
            const modal = bootstrap.Modal.getInstance(modalElement);
            if (modal) {
                modal.hide();
            }
            // Reload profile
            loadProfile();
        } else {
            const errors = data.errors ? data.errors.join('<br>') : (data.error || 'Failed to update profile');
            showNotification(errors, 'error');
        }
    } catch (error) {
        console.error('Error updating profile:', error);
        showNotification('An error occurred. Please try again.', 'error');
    }
}

/**
 * Upload profile photo
 */
async function uploadProfilePhoto() {
    const fileInput = document.getElementById('profilePhotoInput');
    if (!fileInput || !fileInput.files || fileInput.files.length === 0) {
        showNotification('Please select a file to upload.', 'warning');
        return;
    }

    const formData = new FormData();
    formData.append('action', 'update_profile');
    formData.append('profile_photo', fileInput.files[0]);

    try {
        const response = await fetch('profile/profile.php', {
            method: 'POST',
            body: formData,
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            }
        });

        const data = await response.json();

        if (data.success) {
            showNotification('Profile photo updated successfully!', 'success');
            // Close modal
            const modalElement = document.getElementById('uploadPhotoModal');
            const modal = bootstrap.Modal.getInstance(modalElement);
            if (modal) {
                modal.hide();
            }
            // Clear input
            fileInput.value = '';
            // Reload profile
            loadProfile();
        } else {
            const errors = data.errors ? data.errors.join('<br>') : (data.error || 'Failed to update profile photo');
            showNotification(errors, 'error');
        }
    } catch (error) {
        console.error('Error uploading photo:', error);
        showNotification('An error occurred while uploading. Please try again.', 'error');
    }
}

/**
 * Load projects
 */
async function loadProjects() {
    try {
        const response = await fetch('projects/projects.php?action=list', {
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            }
        });

        if (!response.ok) {
            throw new Error(`HTTP ${response.status}: Failed to load projects`);
        }

        const data = await response.json();

        if (data.success) {
            displayProjects(data.data.projects || [], null);
        } else {
            showNotification('Failed to load projects', 'error');
        }
    } catch (error) {
        console.error('Error loading projects:', error);
        showNotification('Failed to load projects', 'error');
    }
}

/**
 * Add a new project
 */
async function addProject() {
    const form = document.getElementById('addProjectForm');
    if (!form) return;

    const formData = new FormData(form);
    formData.append('action', 'add');

    const modal = document.getElementById('addProjectModal');
    const submitBtn = modal ? modal.querySelector('.modal-footer .btn-primary') : null;
    let originalBtnText = '';

    if (submitBtn) {
        originalBtnText = submitBtn.innerHTML;
        setButtonLoading(submitBtn, true);
    }

    try {
        const response = await fetch('projects/projects.php?action=add', {
            method: 'POST',
            body: formData,
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        });

        // Clone response to avoid "body already read" error
        const responseClone = response.clone();

        if (!response.ok) {
            const errorText = await responseClone.text();
            throw new Error(`HTTP ${response.status}: ${errorText}`);
        }

        const data = await response.json();

        if (data.success) {
            showNotification('Project added successfully!', 'success');
            const modal = bootstrap.Modal.getInstance(document.getElementById('addProjectModal'));
            if (modal) modal.hide();
            form.reset();
            loadProjects(); // Reload projects
        } else {
            showNotification(data.errors ? data.errors.join('\n') : data.message, 'error');
        }
    } catch (error) {
        console.error('Error adding project:', error);
        const errorMsg = error.message || 'An error occurred. Please try again.';
        showNotification(errorMsg, 'error');
    } finally {
        if (submitBtn) {
            setButtonLoading(submitBtn, false);
            submitBtn.innerHTML = originalBtnText;
        }
    }
}

/**
 * Edit project
 */
async function editProject(projectId) {
    console.log('editProject called with ID:', projectId);
    try {
        const response = await fetch(`projects/projects.php?action=get&id=${projectId}`, {
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            }
        });

        const data = await response.json();
        console.log('Edit project data received:', data);

        if (data.success && data.data && data.data.project) {
            const project = data.data.project;

            // Populate modal
            const form = document.getElementById('editProjectForm');
            if (form) {
                // Store project ID in a hidden field or data attribute
                // Check if hidden input exists, if not create it
                let idInput = form.querySelector('input[name="id"]');
                if (!idInput) {
                    idInput = document.createElement('input');
                    idInput.type = 'hidden';
                    idInput.name = 'id';
                    form.appendChild(idInput);
                }
                idInput.value = project.id;

                // Populate fields
                if (form.elements['title']) form.elements['title'].value = project.title || '';
                if (form.elements['description']) form.elements['description'].value = project.description || '';

                // Technologies is array, join it
                const tech = Array.isArray(project.technologies) ? project.technologies.join(', ') : project.technologies;
                if (form.elements['technologies']) form.elements['technologies'].value = tech || '';

                if (form.elements['github_url']) form.elements['github_url'].value = project.github_url || '';

                // Screenshot URL
                // If it's a full URL, use it. If relative, maybe prepend? 
                // But inputs are usually for display or editing. 
                // Currently backend saves provided URL directly.
                if (form.elements['screenshot_url']) form.elements['screenshot_url'].value = project.screenshot_url || '';
            }

            // Update Save button onclick
            const modalEl = document.getElementById('editProjectModal');
            const saveBtn = modalEl.querySelector('.modal-footer .btn-primary');
            saveBtn.onclick = () => saveProject(projectId);

            // Show modal
            const modal = new bootstrap.Modal(modalEl);
            modal.show();
        } else {
            showNotification('Failed to load project details', 'error');
        }
    } catch (error) {
        console.error('Error loading project:', error);
        showNotification('Failed to load project details', 'error');
    }
}

/**
 * Save project changes
 */
async function saveProject(projectId) {
    const form = document.getElementById('editProjectForm');
    if (!form) return;

    const formData = new FormData(form);
    formData.append('action', 'update');
    // Ensure ID is sent
    if (!formData.has('id')) {
        formData.append('id', projectId);
    }

    const modalEl = document.getElementById('editProjectModal');
    const submitBtn = modalEl.querySelector('.modal-footer .btn-primary');
    const originalBtnText = submitBtn.innerHTML;
    setButtonLoading(submitBtn, true);

    try {
        const response = await fetch('projects/projects.php?action=update', {
            method: 'POST',
            body: formData,
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        });

        const data = await response.json();

        if (data.success) {
            showNotification('Project updated successfully!', 'success');
            const modal = bootstrap.Modal.getInstance(modalEl);
            if (modal) modal.hide();
            loadProjects(); // Reload projects
        } else {
            showNotification(data.errors ? data.errors.join('\n') : (data.message || 'Update failed'), 'error');
        }
    } catch (error) {
        console.error('Error updating project:', error);
        showNotification('An error occurred. Please try again.', 'error');
    } finally {
        setButtonLoading(submitBtn, false);
        submitBtn.innerHTML = originalBtnText;
    }
}

/**
 * Delete project by ID
 */
async function deleteProjectById(projectId) {
    if (!confirm('Are you sure you want to delete this project?')) {
        return;
    }

    try {
        const response = await fetch(`projects/projects.php?action=delete&id=${projectId}`, {
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        });
        const data = await response.json();

        if (data.success) {
            showNotification('Project deleted successfully!', 'success');
            loadProjects(); // Reload projects
        } else {
            showNotification(data.message || 'Failed to delete project', 'error');
        }
    } catch (error) {
        console.error('Error deleting project:', error);
        showNotification('An error occurred. Please try again.', 'error');
    }
}

/**
 * Load documents
 */
async function loadDocuments() {
    try {
        const response = await fetch('documents/documents.php?action=list', {
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            }
        });

        if (!response.ok) {
            throw new Error(`HTTP ${response.status}: Failed to load documents`);
        }

        const data = await response.json();

        if (data.success) {
            displayDocuments(data.data.documents || []);
        } else {
            showNotification('Failed to load documents', 'error');
        }
    } catch (error) {
        console.error('Error loading documents:', error);
        showNotification('Failed to load documents', 'error');
    }
}

/**
 * Display documents
 */
function displayDocuments(documents) {
    const container = document.querySelector('.row.g-4');
    const emptyState = document.getElementById('emptyState');

    if (!container) return;

    container.innerHTML = '';

    if (documents.length === 0) {
        if (emptyState) emptyState.classList.remove('d-none');
        return;
    }

    if (emptyState) emptyState.classList.add('d-none');

    documents.forEach(doc => {
        const docCard = createDocumentCard(doc);
        container.appendChild(docCard);
    });
}

/**
 * Create document card element
 */
function createDocumentCard(doc) {
    const col = document.createElement('div');
    col.className = 'col-md-6 col-lg-4';

    const iconMap = {
        'pdf': 'bi-file-earmark-pdf text-danger',
        'doc': 'bi-file-earmark-word text-primary',
        'docx': 'bi-file-earmark-word text-primary',
        'xls': 'bi-file-earmark-excel text-success',
        'xlsx': 'bi-file-earmark-excel text-success',
        'png': 'bi-file-earmark-image text-success',
        'jpg': 'bi-file-earmark-image text-success',
        'jpeg': 'bi-file-earmark-image text-success'
    };

    const extension = doc.file_extension || doc.file_type.toLowerCase();
    const iconClass = iconMap[extension] || 'bi-file-earmark text-secondary';

    // Check if the file is an image
    const isImage = ['png', 'jpg', 'jpeg', 'gif', 'webp'].includes(extension.toLowerCase());
    const previewHtml = isImage && doc.file_url
        ? `<div class="bg-light rounded overflow-hidden mb-3" style="height: 160px;">
             <img src="${doc.file_url}" alt="${escapeHtml(doc.title)}" class="w-100 h-100 object-fit-cover">
           </div>`
        : `<div class="mb-3 text-center py-4">
             <i class="bi ${iconClass}" style="font-size: 4rem;"></i>
           </div>`;

    const typeBadge = doc.file_type
        ? `<span class="badge bg-${extension === 'pdf' ? 'danger' : extension.includes('doc') ? 'primary' : extension.includes('xls') ? 'success' : 'secondary'}-subtle text-${extension === 'pdf' ? 'danger' : extension.includes('doc') ? 'primary' : extension.includes('xls') ? 'success' : 'secondary'}">${doc.file_type.toUpperCase()}</span>`
        : '';

    col.innerHTML = `
        <div class="card h-100 shadow-sm hover-shadow border-0">
            <div class="card-body d-flex flex-column">
                ${previewHtml}
                <h5 class="card-title fw-bold">${escapeHtml(doc.title)}</h5>
                <p class="text-muted mb-2">
                    ${typeBadge}
                    <span class="ms-2 small">${doc.file_size_formatted || formatFileSize(doc.file_size || 0)}</span>
                </p>
                ${doc.description ? `<p class="card-text text-muted flex-grow-1 small">${escapeHtml(doc.description)}</p>` : ''}
                <div class="mt-auto d-flex gap-2">
                    <a href="documents/documents.php?action=download&id=${doc.id}" class="btn btn-outline-primary btn-sm flex-fill" download>
                        <i class="bi bi-download me-1"></i>Download
                    </a>
                    <button class="btn btn-outline-secondary btn-sm" onclick="editDocument(${doc.id})">
                        <i class="bi bi-pencil"></i>
                    </button>
                    <button class="btn btn-outline-danger btn-sm" onclick="deleteDocumentById(${doc.id})">
                        <i class="bi bi-trash"></i>
                    </button>
                </div>
            </div>
        </div>
    `;

    return col;
}

/**
 * Upload document
 */
async function uploadDocument() {
    const form = document.getElementById('uploadDocumentForm');
    if (!form) return;

    const formData = new FormData(form);
    formData.append('action', 'upload');

    // Debug logging for FormData
    console.log('--- Uploading Document ---');
    for (let pair of formData.entries()) {
        console.log(pair[0] + ': ' + (pair[1] instanceof File ? pair[1].name : pair[1]));
    }

    const modalElement = document.getElementById('uploadDocumentModal');
    const submitBtn = modalElement.querySelector('button[onclick="uploadDocument()"]');
    const originalBtnText = submitBtn.innerHTML;
    setButtonLoading(submitBtn, true);

    try {
        const response = await fetch('documents/documents.php?action=upload', {
            method: 'POST',
            body: formData,
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        });

        if (!response.ok) {
            const errorText = await response.text();
            throw new Error(`HTTP ${response.status}: ${errorText}`);
        }

        const data = await response.json();

        if (data.success) {
            showNotification('Document uploaded successfully!', 'success');
            const modal = bootstrap.Modal.getInstance(modalElement);
            if (modal) modal.hide();
            form.reset();
            loadDocuments(); // Reload documents
        } else {
            console.error('Document Upload Validation Error:', data);
            showNotification(data.message || 'Validation failed. Check console for details.', 'error');
        }
    } catch (error) {
        console.error('Error uploading document:', error);
        const errorMsg = error.message || 'An error occurred. Please try again.';
        showNotification(errorMsg, 'error');
    } finally {
        setButtonLoading(submitBtn, false);
        submitBtn.innerHTML = originalBtnText;
    }
}

/**
 * Save document changes
 */
async function saveDocumentChanges() {
    const form = document.getElementById('editDocumentForm');
    if (!form) return;

    const formData = new FormData(form);
    formData.append('action', 'update');

    const modalElement = document.getElementById('editDocumentModal');
    const submitBtn = modalElement.querySelector('button[onclick="saveDocumentChanges()"]');
    const originalBtnText = submitBtn.innerHTML;
    setButtonLoading(submitBtn, true);

    try {
        const response = await fetch('documents/documents.php?action=update', {
            method: 'POST',
            body: formData,
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        });

        if (!response.ok) {
            const errorText = await response.text();
            throw new Error(`HTTP ${response.status}: ${errorText}`);
        }

        const data = await response.json();

        if (data.success) {
            showNotification('Document updated successfully!', 'success');
            const modal = bootstrap.Modal.getInstance(modalElement);
            if (modal) modal.hide();
            loadDocuments(); // Reload documents
        } else {
            console.error('Document Update Validation Error:', data);
            showNotification(data.message || 'Validation failed. Check console for details.', 'error');
        }
    } catch (error) {
        console.error('Error updating document:', error);
        showNotification('An error occurred. Please try again.', 'error');
    } finally {
        setButtonLoading(submitBtn, false);
        submitBtn.innerHTML = originalBtnText;
    }
}

/**
 * Delete document by ID
 */
async function deleteDocumentById(documentId) {
    if (!confirm('Are you sure you want to delete this document?')) {
        return;
    }

    try {
        const response = await fetch(`documents/documents.php?action=delete&id=${documentId}`, {
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        });
        const data = await response.json();

        if (data.success) {
            showNotification('Document deleted successfully!', 'success');
            loadDocuments(); // Reload documents
        } else {
            showNotification(data.message || 'Failed to delete document', 'error');
        }
    } catch (error) {
        console.error('Error deleting document:', error);
        showNotification('An error occurred. Please try again.', 'error');
    }
}

/**
 * Format file size for display
 */
function formatFileSize(bytes) {
    if (!bytes || bytes === 0) return '0 Bytes';
    const k = 1024;
    const sizes = ['Bytes', 'KB', 'MB', 'GB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    return Math.round(bytes / Math.pow(k, i) * 100) / 100 + ' ' + sizes[i];
}

/**
 * Show loading state on button
 */
function setButtonLoading(button, isLoading) {
    if (!button) return;
    if (isLoading) {
        button.disabled = true;
        button.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Loading...';
    } else {
        button.disabled = false;
    }
}

/**
 * Display notification/toast message
 */
function showNotification(message, type = 'info') {
    // Create toast container if it doesn't exist
    let toastContainer = document.getElementById('toastContainer');
    if (!toastContainer) {
        toastContainer = document.createElement('div');
        toastContainer.id = 'toastContainer';
        toastContainer.className = 'position-fixed top-0 end-0 p-3';
        toastContainer.style.zIndex = '9999';
        document.body.appendChild(toastContainer);
    }

    const bgColor = type === 'success' ? 'bg-success' : type === 'error' ? 'bg-danger' : 'bg-info';
    const toastId = 'toast-' + Date.now();

    const toastHtml = `
        <div id="${toastId}" class="toast ${bgColor} text-white" role="alert" aria-live="assertive" aria-atomic="true">
            <div class="toast-body">
                ${escapeHtml(message)}
            </div>
        </div>
    `;

    toastContainer.insertAdjacentHTML('beforeend', toastHtml);
    const toastElement = document.getElementById(toastId);
    const toast = new bootstrap.Toast(toastElement, { delay: 3000 });
    toast.show();

    toastElement.addEventListener('hidden.bs.toast', () => {
        toastElement.remove();
    });
}

/**
 * Escape HTML to prevent XSS
 */
function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

// Export functions for global access
window.DevShowcase = {
    addProject,
    deleteProjectById,
    uploadDocument,
    deleteDocumentById,
    formatFileSize,
    showNotification,
    loadProfile,
    loadProjects,
    loadDocuments
};

// Make functions globally available
window.addProject = addProject;
window.deleteProjectById = deleteProjectById;
window.uploadDocument = uploadDocument;
window.deleteDocumentById = deleteDocumentById;
window.editProject = function (id) {
    // TODO: Implement edit project functionality
    console.log('Edit project:', id);
};
window.editDocument = async function (id) {
    try {
        const response = await fetch(`documents/documents.php?action=get&id=${id}`, {
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        });

        if (!response.ok) {
            throw new Error(`HTTP ${response.status}: Failed to fetch document`);
        }

        const data = await response.json();

        if (data.success && data.data.document) {
            const doc = data.data.document;
            document.getElementById('editDocumentId').value = doc.id;
            document.getElementById('editDocumentTitle').value = doc.title;
            document.getElementById('editDocumentDescription').value = doc.description || '';
            document.getElementById('editDocumentType').value = doc.file_type || '';
            document.getElementById('editDocumentCurrentFile').textContent = doc.file_path.split('/').pop();

            const modal = new bootstrap.Modal(document.getElementById('editDocumentModal'));
            modal.show();
        } else {
            showNotification(data.message || 'Failed to load document details', 'error');
        }
    } catch (error) {
        console.error('Error fetching document:', error);
        showNotification('Failed to load document details', 'error');
    }
};
