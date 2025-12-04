# JUSTCHAT - Security Features Report

## Project Overview
JUSTCHAT is a secure profile management system for university students that demonstrates best practices in web application security, authentication, and data protection.

**Technologies Used:**
- Backend: PHP 7.4+
- Database: MySQL
- Frontend: HTML5, CSS3, JavaScript (Vanilla)
- Server: Apache (XAMPP)

---

## Security Features Implemented

### 1. Authentication & Authorization

#### 1.1 Secure Password Storage
- **Implementation:** Passwords are hashed using PHP's `password_hash()` function with bcrypt algorithm (cost factor: 12)
- **Location:** `helpers.php` - `hashPassword()` function
- **Why:** Bcrypt is a slow, adaptive hashing algorithm that protects against brute-force attacks. Even if the database is compromised, passwords cannot be reversed.
```php
function hashPassword($password) {
    return password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
}
```

#### 1.2 Email Verification
- **Implementation:** Users must verify their .edu email before accessing the system
- **Location:** `api/register.php`, `api/verify-email.php`
- **Why:** Ensures only legitimate university students can create accounts and prevents fake registrations.

#### 1.3 Session Management
- **Implementation:** Server-side sessions with unique tokens stored in database
- **Location:** `api/login.php`, sessions table
- **Features:**
  - Session tokens are randomly generated (64 characters)
  - Sessions expire after 1 hour
  - Sessions are invalidated on logout
- **Why:** Prevents session hijacking and ensures proper user tracking.

#### 1.4 Session Timeout
- **Implementation:** Client-side auto-logout after 30 minutes of inactivity
- **Location:** `app.js` - session timeout logic
- **Why:** Protects users who leave their computers unattended.

### 2. Input Validation & Sanitization

#### 2.1 Server-Side Validation
- **Implementation:** All user inputs are validated before processing
- **Location:** All API endpoints
- **Validations:**
  - Email format validation
  - .edu domain enforcement
  - Password minimum length (8 characters)
  - Username format (alphanumeric + underscore only)
  - Required field checks

#### 2.2 Input Sanitization
- **Implementation:** XSS prevention through HTML entity encoding
- **Location:** `helpers.php` - `sanitizeInput()` function
- **Why:** Prevents Cross-Site Scripting (XSS) attacks by escaping special HTML characters.
```php
function sanitizeInput($data) {
    return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
}
```

### 3. SQL Injection Prevention

#### 3.1 Prepared Statements
- **Implementation:** All database queries use PDO prepared statements with parameterized queries
- **Location:** All API endpoints
- **Example:**
```php
$stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
$stmt->execute([$email]);
```
- **Why:** Prevents SQL injection by treating user input as data, not executable code.

### 4. Access Control

#### 4.1 Authentication Checks
- **Implementation:** All protected endpoints verify user authentication before processing
- **Location:** Beginning of each API file (profile.php, friends.php, messages.php, etc.)
- **Why:** Ensures only authenticated users can access protected resources.
```php
if (!isset($_SESSION['user_id'])) {
    jsonResponse(['error' => 'Unauthorized'], 401);
}
```

#### 4.2 Authorization Rules
- **Friendship Verification:** Users can only message friends
- **Profile Privacy:** Users can only edit their own profiles
- **Data Isolation:** Users can only see data from their own university

### 5. Account Security

#### 5.1 Account Lockout
- **Implementation:** Account locked for 15 minutes after 5 failed login attempts
- **Location:** `api/login.php`
- **Why:** Prevents brute-force password attacks.

#### 5.2 Activity Logging
- **Implementation:** All authentication events are logged with timestamps and IP addresses
- **Location:** `auth_logs` table, `helpers.php` - `logAuthEvent()`
- **Events Logged:**
  - Successful logins
  - Failed login attempts
  - Logouts
  - Password changes
  - Account lockouts
- **Why:** Enables security auditing and suspicious activity detection.

#### 5.3 Password Change
- **Implementation:** Users can change password after verifying current password
- **Location:** `api/change-password.php`
- **Security:** Requires current password verification before allowing change.

### 6. Data Protection

#### 6.1 Sensitive Data Handling
- **Implementation:**
  - Email and phone numbers are hidden from other users' profile views
  - Passwords never sent to frontend
  - Session tokens are randomly generated and unique
- **Location:** `api/profile.php`

#### 6.2 University Isolation
- **Implementation:** Users can only see and interact with students from their own university
- **Location:** `api/directory.php`
- **Why:** Provides data segmentation and privacy by educational institution.

### 7. Communication Security

#### 7.1 Friendship Requirement
- **Implementation:** Users must be friends before they can message each other
- **Location:** `api/messages.php`
- **Why:** Prevents spam and unauthorized communication.

#### 7.2 Content Sanitization
- **Implementation:** All message content is sanitized before storage and display
- **Location:** `api/messages.php`
- **Why:** Prevents XSS attacks through message content.

---

## Security Best Practices Followed

1. **Principle of Least Privilege:** Users can only access their own data and data they're authorized to see
2. **Defense in Depth:** Multiple layers of security (client-side validation + server-side validation + database constraints)
3. **Secure by Default:** All new features require authentication by default
4. **Input Validation:** Never trust user input - validate everything
5. **Error Handling:** Generic error messages to avoid information disclosure
6. **Logging:** Comprehensive audit trail for security events

---

## Known Limitations & Future Improvements

### Current Limitations:
1. **No HTTPS:** Currently running on HTTP (localhost). Production deployment MUST use HTTPS.
2. **No CSRF Protection:** Cross-Site Request Forgery tokens not implemented yet.
3. **No Rate Limiting on Non-Auth Endpoints:** Search and messaging endpoints could benefit from rate limiting.
4. **No Two-Factor Authentication:** Additional security layer not yet implemented.
5. **No End-to-End Encryption:** Messages are encrypted in transit but not end-to-end encrypted.

### Recommended Improvements:
1. Implement CSRF tokens for all state-changing operations
2. Add rate limiting to prevent API abuse
3. Implement two-factor authentication (TOTP)
4. Add end-to-end encryption for messages
5. Implement Content Security Policy (CSP) headers
6. Add CAPTCHA to prevent automated attacks
7. Implement password complexity requirements
8. Add email notifications for security events

---

## Testing Security Features

### Test Cases Performed:

1. **SQL Injection Test:** Attempted SQL injection in login form - Prevented ✅
2. **XSS Test:** Attempted script injection in profile fields - Sanitized ✅
3. **Brute Force Test:** Multiple failed login attempts - Account locked ✅
4. **Session Test:** Session expires after timeout - Working ✅
5. **Authorization Test:** Attempted to access other user's data - Blocked ✅
6. **Password Test:** Weak passwords during registration - Indicator shown ✅

---

## Conclusion

JUSTCHAT demonstrates a solid foundation of web application security principles including secure authentication, input validation, SQL injection prevention, and access control. While additional features like HTTPS, CSRF protection, and 2FA would further enhance security, the current implementation follows industry best practices for secure web development.

**Key Takeaways:**
- Never trust user input
- Always hash passwords
- Use prepared statements
- Implement proper authentication and authorization
- Log security events
- Follow the principle of least privilege
