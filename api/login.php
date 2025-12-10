<?php
// api/login.php - User login

session_start();
header('Content-Type: application/json');
require_once '../config.php';
require_once '../helpers.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(['error' => 'Method not allowed'], 405);
}

$data = json_decode(file_get_contents('php://input'), true);

$email = $data['email'] ?? '';
$password = $data['password'] ?? '';

if (empty($email) || empty($password)) {
    jsonResponse(['error' => 'Email and password are required'], 400);
}

try {
    $pdo = getDBConnection();
    
    // Get user
    $stmt = $pdo->prepare("
        SELECT id, full_name, username, email, password_hash, email_verified 
        FROM users 
        WHERE email = ?
    ");
    $stmt->execute([$email]);
    $user = $stmt->fetch();
    
    if (!$user) {
        // Log failed attempt
        logAuthEvent(null, $email, 'login_failure', getClientIP(), $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown');
        jsonResponse(['error' => 'Invalid email or password'], 401);
    }
    
    // Check if verified
    if (!$user['email_verified']) {
        jsonResponse(['error' => 'Please verify your email first'], 403);
    }
    
    // Check password
    if (!verifyPassword($password, $user['password_hash'])) {
        // Log failed attempt
        logAuthEvent($user['id'], $email, 'login_failure', getClientIP(), $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown');
        jsonResponse(['error' => 'Invalid email or password'], 401);
    }
    
    // Create session
    $sessionToken = generateSessionToken();
    $expiresAt = date('Y-m-d H:i:s', time() + 3600); // 1 hour
    
    $stmt = $pdo->prepare("
        INSERT INTO sessions (user_id, session_token, expires_at) 
        VALUES (?, ?, ?)
    ");
    $stmt->execute([$user['id'], $sessionToken, $expiresAt]);
    
    // Set session
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['session_token'] = $sessionToken;
    
    // Log successful login
    logAuthEvent($user['id'], $email, 'login_success', getClientIP(), $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown');
    
    jsonResponse([
        'success' => true,
        'message' => 'Login successful',
        'session_token' => $sessionToken,
        'user' => [
            'id' => $user['id'],
            'full_name' => $user['full_name'],
            'username' => $user['username'],
            'email' => $user['email']
        ]
    ]);
    
} catch (PDOException $e) {
    error_log("Login error: " . $e->getMessage());
    jsonResponse(['error' => 'Login failed'], 500);
}
?>