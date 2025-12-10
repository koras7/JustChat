<?php
// api/activity-logs.php - View login activity

session_start();
header('Content-Type: application/json');
require_once '../config.php';
require_once '../helpers.php';

// Check auth
if (!isset($_SESSION['user_id'])) {
    jsonResponse(['error' => 'Unauthorized'], 401);
}

$currentUserId = $_SESSION['user_id'];

try {
    $pdo = getDBConnection();
    
    // Get last 20 login activities for current user
    $stmt = $pdo->prepare("
        SELECT event_type, ip_address, created_at
        FROM auth_logs
        WHERE user_id = ?
        ORDER BY created_at DESC
        LIMIT 20
    ");
    $stmt->execute([$currentUserId]);
    $logs = $stmt->fetchAll();
    
    jsonResponse(['success' => true, 'logs' => $logs, 'count' => count($logs)]);
    
} catch (PDOException $e) {
    error_log("Activity logs error: " . $e->getMessage());
    jsonResponse(['error' => 'Failed to fetch logs'], 500);
}
?>