#!/bin/bash

# DevShowcase - Fix Upload Directory Permissions
# Run this script to create upload directories with proper permissions

echo "=========================================="
echo "DevShowcase - Fix Upload Directories"
echo "=========================================="
echo ""

# Detect project location
if [ -d "/opt/lampp/htdocs/DevShowcase" ]; then
    PROJECT_DIR="/opt/lampp/htdocs/DevShowcase"
    echo "✓ Found project in XAMPP htdocs: $PROJECT_DIR"
elif [ -d "$HOME/DevShowcase" ]; then
    PROJECT_DIR="$HOME/DevShowcase"
    echo "✓ Found project in home directory: $PROJECT_DIR"
else
    echo "✗ Could not find DevShowcase directory"
    echo "Please run this script from the DevShowcase directory"
    exit 1
fi

UPLOAD_DIR="$PROJECT_DIR/uploads"

echo ""
echo "Creating upload directories..."

# Create directories
sudo mkdir -p "$UPLOAD_DIR/profiles" 2>/dev/null || mkdir -p "$UPLOAD_DIR/profiles"
sudo mkdir -p "$UPLOAD_DIR/documents" 2>/dev/null || mkdir -p "$UPLOAD_DIR/documents"
sudo mkdir -p "$UPLOAD_DIR/projects" 2>/dev/null || mkdir -p "$UPLOAD_DIR/projects"

if [ $? -eq 0 ]; then
    echo "✓ Directories created"
else
    echo "✗ Failed to create directories"
    exit 1
fi

# Set permissions
echo "Setting permissions..."
sudo chmod -R 777 "$UPLOAD_DIR" 2>/dev/null || chmod -R 777 "$UPLOAD_DIR"

if [ $? -eq 0 ]; then
    echo "✓ Permissions set"
else
    echo "⚠ Could not set permissions automatically"
    echo "Please run manually: sudo chmod -R 777 $UPLOAD_DIR"
fi

# Try to set ownership (XAMPP typically uses daemon user)
if [ -d "/opt/lampp" ]; then
    echo "Setting ownership for XAMPP..."
    sudo chown -R daemon:daemon "$UPLOAD_DIR" 2>/dev/null || \
    sudo chown -R www-data:www-data "$UPLOAD_DIR" 2>/dev/null || \
    echo "⚠ Could not change ownership (this is OK if permissions are 777)"
fi

# Verify
echo ""
echo "Verifying..."
if [ -d "$UPLOAD_DIR/profiles" ] && [ -d "$UPLOAD_DIR/documents" ] && [ -d "$UPLOAD_DIR/projects" ]; then
    echo "✓ All directories exist"
    echo ""
    echo "Directory structure:"
    ls -la "$UPLOAD_DIR"
    echo ""
    echo "=========================================="
    echo "Done! Refresh test.php to verify."
    echo "=========================================="
else
    echo "✗ Some directories are missing"
    exit 1
fi

