<?php
// api/reset-password.php - Reset password with code

session_start();
header('Content-Type: application/json');
require_once '../config.php';
require_once '../helpers.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(['error' => 'Method not allowed'], 405);
}

$data = json_decode(file_get_contents('php://input'), true);
$userId = $data['user_id'] ?? 0;
$resetCode = $data['reset_code'] ?? '';
$newPassword = $data['new_password'] ?? '';

if (!$userId || empty($resetCode) || empty($newPassword)) {
    jsonResponse(['error' => 'All fields required'], 400);
}

if (strlen($newPassword) < 8) {
    jsonResponse(['error' => 'Password must be at least 8 characters'], 400);
}

try {
    $pdo = getDBConnection();
    
    // Verify reset code
    $stmt = $pdo->prepare("
        SELECT id, expires_at 
        FROM password_resets 
        WHERE user_id = ? AND reset_code = ? 
        ORDER BY created_at DESC 
        LIMIT 1
    ");
    $stmt->execute([$userId, $resetCode]);
    $reset = $stmt->fetch();
    
    if (!$reset) {
        jsonResponse(['error' => 'Invalid reset code'], 400);
    }
    
    if (strtotime($reset['expires_at']) < time()) {
        jsonResponse(['error' => 'Reset code has expired'], 400);
    }
    
    // Update password
    $passwordHash = hashPassword($newPassword);
    $stmt = $pdo->prepare("UPDATE users SET password_hash = ? WHERE id = ?");
    $stmt->execute([$passwordHash, $userId]);
    
    // Delete used reset code
    $stmt = $pdo->prepare("DELETE FROM password_resets WHERE user_id = ?");
    $stmt->execute([$userId]);
    
    // Log the event
    logAuthEvent($userId, null, 'password_reset', getClientIP(), $_SERVER['HTTP_USER_AGENT']);
    
    jsonResponse(['success' => true, 'message' => 'Password reset successfully']);
    
} catch (PDOException $e) {
    error_log("Reset password error: " . $e->getMessage());
    jsonResponse(['error' => 'Failed to reset password'], 500);
}
?>