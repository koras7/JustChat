<?php
// api/forgot-password.php - Password reset

session_start();
header('Content-Type: application/json');
require_once '../config.php';
require_once '../helpers.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(['error' => 'Method not allowed'], 405);
}

$data = json_decode(file_get_contents('php://input'), true);
$email = $data['email'] ?? '';

if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    jsonResponse(['error' => 'Valid email required'], 400);
}

try {
    $pdo = getDBConnection();
    
    // Check if user exists
    $stmt = $pdo->prepare("SELECT id, full_name FROM users WHERE email = ? AND email_verified = TRUE");
    $stmt->execute([$email]);
    $user = $stmt->fetch();
    
    if (!$user) {
        // Don't reveal if email exists or not (security)
        jsonResponse(['success' => true, 'message' => 'If that email exists, a reset code has been sent']);
    }
    
    // Generate 6-digit reset code
    $resetCode = generateVerificationCode();
    $expiresAt = date('Y-m-d H:i:s', time() + 1800); // 30 minutes
    
    // Store reset code
    $stmt = $pdo->prepare("
        INSERT INTO password_resets (user_id, reset_code, expires_at) 
        VALUES (?, ?, ?)
    ");
    $stmt->execute([$user['id'], $resetCode, $expiresAt]);
    
    // Send email (for now, just return the code - in production, send actual email)
    // sendEmail($email, "Password Reset", "Your reset code is: $resetCode");
    
    jsonResponse([
        'success' => true, 
        'message' => 'Reset code sent to your email',
        'reset_code' => $resetCode, // Remove this in production!
        'user_id' => $user['id']
    ]);
    
} catch (PDOException $e) {
    error_log("Forgot password error: " . $e->getMessage());
    jsonResponse(['error' => 'Failed to process request'], 500);
}
?>