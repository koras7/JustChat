<?php
// api/messages.php - Messaging system

session_start();
header('Content-Type: application/json');
require_once '../config.php';
require_once '../helpers.php';

// Check auth
if (!isset($_SESSION['user_id'])) {
    jsonResponse(['error' => 'Unauthorized'], 401);
}

$currentUserId = $_SESSION['user_id'];

// GET - Fetch messages with a friend
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $withUser = $_GET['with_user'] ?? 0;
    
    if (!$withUser) {
        jsonResponse(['error' => 'with_user parameter required'], 400);
    }
    
    try {
        $pdo = getDBConnection();
        
        // Verify friendship exists
        $stmt = $pdo->prepare("
            SELECT id FROM friends 
            WHERE status = 'accepted' 
            AND ((requester_id = ? AND addressee_id = ?) 
                 OR (requester_id = ? AND addressee_id = ?))
        ");
        $stmt->execute([$currentUserId, $withUser, $withUser, $currentUserId]);
        
        if (!$stmt->fetch()) {
            jsonResponse(['error' => 'Can only message friends'], 403);
        }
        
        // Fetch messages
        $stmt = $pdo->prepare("
            SELECT m.id, m.sender_id, m.recipient_id, m.content_text, 
                   m.created_at, m.read_at,
                   u.full_name as sender_name, u.username as sender_username
            FROM messages m
            JOIN users u ON m.sender_id = u.id
            WHERE ((m.sender_id = ? AND m.recipient_id = ?) 
                   OR (m.sender_id = ? AND m.recipient_id = ?))
            ORDER BY m.created_at ASC
            LIMIT 100
        ");
        $stmt->execute([$currentUserId, $withUser, $withUser, $currentUserId]);
        $messages = $stmt->fetchAll();
        
        // Mark messages as read
        $stmt = $pdo->prepare("
            UPDATE messages 
            SET read_at = NOW() 
            WHERE recipient_id = ? AND sender_id = ? AND read_at IS NULL
        ");
        $stmt->execute([$currentUserId, $withUser]);
        
        jsonResponse(['success' => true, 'messages' => $messages]);
        
    } catch (PDOException $e) {
        jsonResponse(['error' => 'Failed to fetch messages'], 500);
    }
}

// POST - Send message
else if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    
    $recipientId = $data['recipient_id'] ?? 0;
    $contentText = $data['content_text'] ?? '';
    
    if (!$recipientId || empty(trim($contentText))) {
        jsonResponse(['error' => 'Recipient and message text required'], 400);
    }
    
    if ($recipientId == $currentUserId) {
        jsonResponse(['error' => 'Cannot message yourself'], 400);
    }
    
    try {
        $pdo = getDBConnection();
        
        // Verify friendship exists
        $stmt = $pdo->prepare("
            SELECT id FROM friends 
            WHERE status = 'accepted' 
            AND ((requester_id = ? AND addressee_id = ?) 
                 OR (requester_id = ? AND addressee_id = ?))
        ");
        $stmt->execute([$currentUserId, $recipientId, $recipientId, $currentUserId]);
        
        if (!$stmt->fetch()) {
            jsonResponse(['error' => 'Can only message friends'], 403);
        }
        
        // Sanitize text content
        $contentText = sanitizeInput($contentText);
        
        // Insert message
        $stmt = $pdo->prepare("
            INSERT INTO messages (sender_id, recipient_id, content_text)
            VALUES (?, ?, ?)
        ");
        $stmt->execute([$currentUserId, $recipientId, $contentText]);
        
        $messageId = $pdo->lastInsertId();
        
        // Fetch created message
        $stmt = $pdo->prepare("
            SELECT m.id, m.sender_id, m.recipient_id, m.content_text, m.created_at,
                   u.full_name as sender_name, u.username as sender_username
            FROM messages m
            JOIN users u ON m.sender_id = u.id
            WHERE m.id = ?
        ");
        $stmt->execute([$messageId]);
        $message = $stmt->fetch();
        
        jsonResponse(['success' => true, 'message' => $message], 201);
        
    } catch (PDOException $e) {
        jsonResponse(['error' => 'Failed to send message'], 500);
    }
}

else {
    jsonResponse(['error' => 'Method not allowed'], 405);
}
?>