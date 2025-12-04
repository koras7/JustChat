<?php
// api/verify-email.php - Email verification

header('Content-Type: application/json');
require_once '../config.php';
require_once '../helpers.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(['error' => 'Method not allowed'], 405);
}

$data = json_decode(file_get_contents('php://input'), true);

$userId = $data['user_id'] ?? 0;
$code = $data['code'] ?? '';

if (empty($userId) || empty($code)) {
    jsonResponse(['error' => 'User ID and code are required'], 400);
}

try {
    $pdo = getDBConnection();
    
    // Check if user exists
    $stmt = $pdo->prepare("SELECT id, email, email_verified FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    $user = $stmt->fetch();
    
    if (!$user) {
        jsonResponse(['error' => 'User not found'], 404);
    }
    
    if ($user['email_verified']) {
        jsonResponse(['error' => 'Email already verified'], 400);
    }
    
    // Check verification code
    $stmt = $pdo->prepare("
        SELECT id, expires_at 
        FROM email_verifications 
        WHERE user_id = ? AND code = ? 
        ORDER BY created_at DESC 
        LIMIT 1
    ");
    $stmt->execute([$userId, $code]);
    $verification = $stmt->fetch();
    
    if (!$verification) {
        jsonResponse(['error' => 'Invalid verification code'], 400);
    }
    
    // Check if expired
    if (strtotime($verification['expires_at']) < time()) {
        jsonResponse(['error' => 'Verification code expired'], 400);
    }
    
    // Verify user
    $stmt = $pdo->prepare("UPDATE users SET email_verified = TRUE WHERE id = ?");
    $stmt->execute([$userId]);
    
    // Delete verification code
    $stmt = $pdo->prepare("DELETE FROM email_verifications WHERE user_id = ?");
    $stmt->execute([$userId]);
    
    jsonResponse([
        'success' => true,
        'message' => 'Email verified successfully'
    ]);
    
} catch (PDOException $e) {
    jsonResponse(['error' => 'Verification failed: ' . $e->getMessage()], 500);
}
?>