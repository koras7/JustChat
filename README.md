# JUSTCHAT - Secure Profile Management System

A secure web application for university students to manage profiles, connect with peers, and communicate safely.

## Features

-  Secure authentication with email verification
-  Comprehensive profile management
-  University-based user directory
-  Friend system
-  Secure messaging
-  Activity logging and password management

## Installation

### Prerequisites
- XAMPP (Apache + MySQL + PHP 7.4+)
- Web browser

### Setup Steps

1. **Install XAMPP**
   - Download from https://www.apachefriends.org/
   - Start Apache and MySQL

2. **Import Database**
   - Open phpMyAdmin: http://localhost/phpmyadmin
   - Create database named `JustChat`
   - Import `schema.sql`

3. **Configure Application**
   - Copy files to `/Applications/XAMPP/htdocs/justchat/`
   - Update `config.php` with your settings

4. **Access Application**
   - Navigate to: http://localhost/justchat/

## Project Structure
```
justchat/
├── api/                    # Backend API endpoints
│   ├── register.php       # User registration
│   ├── login.php          # Authentication
│   ├── profile.php        # Profile management
│   ├── friends.php        # Friend system
│   ├── messages.php       # Messaging
│   ├── directory.php      # Student directory
│   ├── activity-logs.php  # Security logs
│   └── change-password.php
├── config.php             # Database & app configuration
├── helpers.php            # Utility functions
├── index.html             # Main application UI
├── app.js                 # Frontend logic
└── SECURITY_REPORT.md     # Security documentation
```

## Security Features

-  Bcrypt password hashing
- SQL injection prevention (prepared statements)
-  XSS protection (input sanitization)
-  Session management
-  Email verification
-  Account lockout after failed attempts
-  Activity logging
-  Session timeout
-  Access control and authorization

See `SECURITY_REPORT.md` for detailed security documentation.

## Usage

1. **Register:** Create account with .edu email
2. **Verify:** Enter verification code from email
3. **Login:** Access your profile
4. **Connect:** Browse directory and add friends
5. **Message:** Chat with your friends

## Technologies

- **Backend:** PHP, MySQL
- **Frontend:** HTML5, CSS3, JavaScript
- **Server:** Apache (XAMPP)
- **Security:** Bcrypt, PDO prepared statements, session management

## Team Members

- koras koirala
- Razat sangraula





Educational project - Not for commercial use
