<?php
// api/change-password.php - Change password

session_start();
header('Content-Type: application/json');
require_once '../config.php';
require_once '../helpers.php';

// Check auth
if (!isset($_SESSION['user_id'])) {
    jsonResponse(['error' => 'Unauthorized'], 401);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(['error' => 'Method not allowed'], 405);
}

$data = json_decode(file_get_contents('php://input'), true);

$currentPassword = $data['current_password'] ?? '';
$newPassword = $data['new_password'] ?? '';

if (empty($currentPassword) || empty($newPassword)) {
    jsonResponse(['error' => 'Current and new password required'], 400);
}

if (strlen($newPassword) < 8) {
    jsonResponse(['error' => 'New password must be at least 8 characters'], 400);
}

$currentUserId = $_SESSION['user_id'];

try {
    $pdo = getDBConnection();
    
    // Get current password hash
    $stmt = $pdo->prepare("SELECT password_hash FROM users WHERE id = ?");
    $stmt->execute([$currentUserId]);
    $user = $stmt->fetch();
    
    if (!$user) {
        jsonResponse(['error' => 'User not found'], 404);
    }
    
    // Verify current password
    if (!verifyPassword($currentPassword, $user['password_hash'])) {
        jsonResponse(['error' => 'Current password is incorrect'], 401);
    }
    
    // Hash new password
    $newPasswordHash = hashPassword($newPassword);
    
    // Update password
    $stmt = $pdo->prepare("UPDATE users SET password_hash = ? WHERE id = ?");
    $stmt->execute([$newPasswordHash, $currentUserId]);
    
    // Log the password change
    logAuthEvent($currentUserId, null, 'password_reset', getClientIP(), $_SERVER['HTTP_USER_AGENT']);
    
    jsonResponse(['success' => true, 'message' => 'Password changed successfully']);
    
} catch (PDOException $e) {
    jsonResponse(['error' => 'Failed to change password'], 500);
}
?>