<?php
// api/profile.php - Profile management

session_start();
header('Content-Type: application/json');
require_once '../config.php';
require_once '../helpers.php';

// GET - View profile
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $userId = $_GET['user_id'] ?? null;
    
    if (!$userId) {
        jsonResponse(['error' => 'User ID required'], 400);
    }
    
    try {
        $pdo = getDBConnection();
        $stmt = $pdo->prepare("
            SELECT id, full_name, username, nickname, email,university, phone, major, year, 
                   pronouns, bio, hobbies, profile_image_path, status, 
                   availability_status, created_at
            FROM users 
            WHERE id = ?
        ");
        $stmt->execute([$userId]);
        $user = $stmt->fetch();
        
        if (!$user) {
            jsonResponse(['error' => 'User not found'], 404);
        }
        
        // Hide sensitive info if viewing someone else's profile
        if (!isset($_SESSION['user_id']) || $_SESSION['user_id'] != $userId) {
            unset($user['email']);
            unset($user['phone']);
        }
        
        jsonResponse(['success' => true, 'user' => $user]);
        
    } catch (PDOException $e) {
        jsonResponse(['error' => 'Failed to fetch profile'], 500);
    }
}

// PUT - Update own profile
else if ($_SERVER['REQUEST_METHOD'] === 'PUT') {
    // Check auth
    if (!isset($_SESSION['user_id'])) {
        jsonResponse(['error' => 'Unauthorized'], 401);
    }
    
    $data = json_decode(file_get_contents('php://input'), true);
    
    $updateFields = [];
    $updateValues = [];
    
    // Allowed fields
    $allowedFields = ['full_name', 'nickname', 'university' , 'phone', 'major', 'year', 
                      'pronouns', 'bio', 'hobbies', 'availability_status'];
    
    foreach ($allowedFields as $field) {
        if (isset($data[$field])) {
            $updateFields[] = "$field = ?";
            $updateValues[] = sanitizeInput($data[$field]);
        }
    }
    
    if (empty($updateFields)) {
        jsonResponse(['error' => 'No fields to update'], 400);
    }
    
    try {
        $pdo = getDBConnection();
        
        $updateValues[] = $_SESSION['user_id'];
        $sql = "UPDATE users SET " . implode(', ', $updateFields) . " WHERE id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($updateValues);
        
        // Return updated profile
        $stmt = $pdo->prepare("
            SELECT id, full_name, username, nickname, email, phone, major, year, 
                   pronouns, bio, hobbies, profile_image_path, status, availability_status
            FROM users WHERE id = ?
        ");
        $stmt->execute([$_SESSION['user_id']]);
        $user = $stmt->fetch();
        
        jsonResponse(['success' => true, 'message' => 'Profile updated', 'user' => $user]);
        
    } catch (PDOException $e) {
        jsonResponse(['error' => 'Failed to update profile'], 500);
    }
}

else {
    jsonResponse(['error' => 'Method not allowed'], 405);
}
?>