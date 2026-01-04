#!/bin/bash

# DevShowcase XAMPP Setup Script
# This script helps set up the database for XAMPP

echo "=========================================="
echo "DevShowcase - XAMPP Database Setup"
echo "=========================================="
echo ""

# Check if XAMPP MySQL is available
if [ ! -f "/opt/lampp/bin/mysql" ]; then
    echo "ERROR: XAMPP MySQL not found at /opt/lampp/bin/mysql"
    echo "Please make sure XAMPP is installed."
    exit 1
fi

# Check if MySQL is running
echo "Checking XAMPP MySQL status..."
if ! /opt/lampp/bin/mysql -u root -e "SELECT 1;" > /dev/null 2>&1; then
    echo "WARNING: Cannot connect to MySQL. Make sure XAMPP MySQL is running:"
    echo "  sudo /opt/lampp/lampp startmysql"
    echo ""
    read -p "Do you want to try starting MySQL now? (y/n) " -n 1 -r
    echo
    if [[ $REPLY =~ ^[Yy]$ ]]; then
        echo "Starting XAMPP MySQL..."
        sudo /opt/lampp/lampp startmysql
        sleep 2
    else
        echo "Please start MySQL and run this script again."
        exit 1
    fi
fi

echo "✓ MySQL is running"
echo ""

# Check if database exists
echo "Checking if database exists..."
if /opt/lampp/bin/mysql -u root -e "USE devshowcase_db;" > /dev/null 2>&1; then
    echo "⚠ Database 'devshowcase_db' already exists."
    read -p "Do you want to drop and recreate it? (y/n) " -n 1 -r
    echo
    if [[ $REPLY =~ ^[Yy]$ ]]; then
        echo "Dropping existing database..."
        /opt/lampp/bin/mysql -u root -e "DROP DATABASE IF EXISTS devshowcase_db;"
    else
        echo "Skipping database creation."
        exit 0
    fi
fi

# Create database
echo "Creating database 'devshowcase_db'..."
/opt/lampp/bin/mysql -u root -e "CREATE DATABASE IF NOT EXISTS devshowcase_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"

if [ $? -eq 0 ]; then
    echo "✓ Database created successfully"
else
    echo "✗ Failed to create database"
    exit 1
fi

# Import schema
if [ -f "database.sql" ]; then
    echo "Importing database schema..."
    /opt/lampp/bin/mysql -u root devshowcase_db < database.sql
    
    if [ $? -eq 0 ]; then
        echo "✓ Database schema imported successfully"
    else
        echo "✗ Failed to import database schema"
        exit 1
    fi
else
    echo "⚠ database.sql not found in current directory"
    exit 1
fi

# Verify tables were created
echo ""
echo "Verifying tables..."
TABLES=$(/opt/lampp/bin/mysql -u root devshowcase_db -e "SHOW TABLES;" | wc -l)
if [ $TABLES -ge 4 ]; then  # Should have 3 tables + header row
    echo "✓ Tables created:"
    /opt/lampp/bin/mysql -u root devshowcase_db -e "SHOW TABLES;"
else
    echo "⚠ Expected 3 tables, but found less"
fi

echo ""
echo "=========================================="
echo "Setup Complete!"
echo "=========================================="
echo ""
echo "Next steps:"
echo "1. Make sure Apache is running: sudo /opt/lampp/lampp startapache"
echo "2. If needed, move project to htdocs or create symlink"
echo "3. Access your app at: http://localhost/DevShowcase/"
echo "4. See XAMPP_SETUP.md for more details"
echo ""

