<?php
// api/friends.php - Friends management

session_start();
header('Content-Type: application/json');
require_once '../config.php';
require_once '../helpers.php';

// Check auth
if (!isset($_SESSION['user_id'])) {
    jsonResponse(['error' => 'Unauthorized'], 401);
}

$currentUserId = $_SESSION['user_id'];

// GET - List friends and requests
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    try {
        $pdo = getDBConnection();
        
        // Simple query - get accepted friends
        $stmt = $pdo->prepare("
            SELECT u.id, u.full_name, u.username, u.major, u.year
            FROM users u
            INNER JOIN friends f ON (
                (f.requester_id = ? AND f.addressee_id = u.id) OR
                (f.addressee_id = ? AND f.requester_id = u.id)
            )
            WHERE f.status = 'accepted'
        ");
        $stmt->execute([$currentUserId, $currentUserId]);
        $results = $stmt->fetchAll();
        
        jsonResponse(['success' => true, 'data' => $results, 'count' => count($results)]);
        
    } catch (PDOException $e) {
        jsonResponse(['error' => 'Database error: ' . $e->getMessage()], 500);
    }
}

// POST - Send friend request
else if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    $action = $data['action'] ?? '';
    
    if ($action === 'send') {
        $friendId = $data['friend_id'] ?? 0;
        
        if (!$friendId) {
            jsonResponse(['error' => 'Friend ID required'], 400);
        }
        
        if ($friendId == $currentUserId) {
            jsonResponse(['error' => 'Cannot send request to yourself'], 400);
        }
        
        try {
            $pdo = getDBConnection();
            
            // Check if friendship already exists (either direction)
            $stmt = $pdo->prepare("
                SELECT id, status FROM friends 
                WHERE (requester_id = ? AND addressee_id = ?)
                OR (requester_id = ? AND addressee_id = ?)
            ");
            $stmt->execute([$currentUserId, $friendId, $friendId, $currentUserId]);
            $existing = $stmt->fetch();
            
            if ($existing) {
                if ($existing['status'] === 'accepted') {
                    jsonResponse(['error' => 'Already friends'], 409);
                } else {
                    jsonResponse(['error' => 'Request already pending'], 409);
                }
            }
            
            // Send friend request (auto-accept for now to test)
            $stmt = $pdo->prepare("
                INSERT INTO friends (requester_id, addressee_id, status) 
                VALUES (?, ?, 'accepted')
            ");
            $stmt->execute([$currentUserId, $friendId]);
            
            jsonResponse(['success' => true, 'message' => 'Friend added!']);
            
        } catch (PDOException $e) {
            jsonResponse(['error' => 'Database error: ' . $e->getMessage()], 500);
        }
    }
    else {
        jsonResponse(['error' => 'Invalid action'], 400);
    }
}

else {
    jsonResponse(['error' => 'Method not allowed'], 405);
}
?>