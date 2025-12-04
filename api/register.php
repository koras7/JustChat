<?php
// api/register.php - User registration

header('Content-Type: application/json');
require_once '../config.php';
require_once '../helpers.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(['error' => 'Method not allowed'], 405);
}

$data = json_decode(file_get_contents('php://input'), true);

$fullName = $data['full_name'] ?? '';
$username = $data['username'] ?? '';
$email = $data['email'] ?? '';
$password = $data['password'] ?? '';
$phone = $data['phone'] ?? null;

// Validation
$errors = [];

if (empty($fullName) || strlen($fullName) < 2) {
    $errors[] = 'Full name is required';
}

if (empty($username) || strlen($username) < 3) {
    $errors[] = 'Username must be at least 3 characters';
}

if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $errors[] = 'Valid email is required';
}

if (!isEduEmail($email)) {
    $errors[] = 'Email must be from a .edu domain';
}

if (empty($password) || strlen($password) < 8) {
    $errors[] = 'Password must be at least 8 characters';
}

if (!empty($errors)) {
    jsonResponse(['error' => 'Validation failed', 'details' => $errors], 400);
}

try {
    $pdo = getDBConnection();
    
    // Check if username exists
    $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ?");
    $stmt->execute([$username]);
    if ($stmt->fetch()) {
        jsonResponse(['error' => 'Username already taken'], 409);
    }
    
    // Check if email exists
    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->execute([$email]);
    if ($stmt->fetch()) {
        jsonResponse(['error' => 'Email already registered'], 409);
    }
    
    // Hash password
    $passwordHash = hashPassword($password);

    // Extract university from email
    $university = extractUniversity($email);
    
    // Insert user
    $stmt = $pdo->prepare("
        INSERT INTO users (full_name, username, email, university, password_hash, phone) 
        VALUES (?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute([$fullName, $username, $email,$university, $passwordHash, $phone]);
    
    $userId = $pdo->lastInsertId();
    
    // Generate verification code
    $verificationCode = generateVerificationCode();
    $expiresAt = date('Y-m-d H:i:s', time() + 3600); // 1 hour
    
    $stmt = $pdo->prepare("
        INSERT INTO email_verifications (user_id, code, expires_at) 
        VALUES (?, ?, ?)
    ");
    $stmt->execute([$userId, $verificationCode, $expiresAt]);
    
    jsonResponse([
        'success' => true,
        'message' => 'Registration successful',
        'user_id' => $userId,
        'verification_code' => $verificationCode // For testing only!
    ], 201);
    
} catch (PDOException $e) {
    jsonResponse(['error' => 'Registration failed: ' . $e->getMessage()], 500);
}
?>