# DevShowcase

DevShowcase is a professional developer portfolio and showcase platform designed to help developers manage their profiles, showcase their projects, and share important documents in one centralized location.

## Features

- User Authentication: Secure registration and login system for developers.
- Profile Management: Customizable profiles including personal bio, technical skills, and GitHub integration.
- Project Portfolio: Display projects with titles, descriptions, and screenshots.
- Document Sharing: Upload and manage documents such as resumes, certifications, or project documentation.
- Integrated Search: Easily find projects and profiles within the platform.
- System Diagnostics: Built-in tools to verify server and database configurations.

## Technology Stack

- Backend: PHP
- Database: MySQL
- Frontend: JavaScript, HTML5, CSS3, Bootstrap
- Server Environment: XAMPP (Apache, MySQL)

## Prerequisites

- XAMPP installed on your system.
- Professional PHP environment (preferably Linux based for full script compatibility).
- Web browser (Chrome, Firefox, or Edge recommended).

## Installation and Setup

1. Start XAMPP Services:
   Ensure Apache and MySQL components are running in your XAMPP control panel.

2. Database Setup:
   Import the provided database.sql file into your MySQL server via phpMyAdmin or the command line.

3. File Placement:
   Move the project folder to your web server's root directory (usually /opt/lampp/htdocs/ on Linux).

4. Directory Permissions:
   Ensure the uploads directory and its subdirectories (profiles, documents, projects) have appropriate write permissions. You can use the provided fix_permissions.sh script on Linux systems.

5. Configuration:
   Verify the database connection settings in config/config.php match your local environment.

## Usage

- Access the application at http://localhost/DevShowcase/
- Use the Register page to create a new developer account.
- Complete your profile to showcase your expertise.
- Use the Projects and Documents pages to build your portfolio.
- Run test.php to verify your installation status at any time.

## Documentation

For more detailed information, please refer to:
- QUICK_START.md: Step-by-step setup guide.
- INTEGRATION_GUIDE.md: Technical details on project integration.
- TROUBLESHOOTING.md: Solutions for common issues.
- README_BACKEND.md: Information about the backend architecture.
