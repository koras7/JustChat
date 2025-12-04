<?php
// api/directory.php - Student directory

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
    
    // Get current user's university
    $stmt = $pdo->prepare("SELECT university FROM users WHERE id = ?");
    $stmt->execute([$currentUserId]);
    $currentUser = $stmt->fetch();
    
    if (!$currentUser) {
        jsonResponse(['error' => 'User not found'], 404);
    }
    
    $university = $currentUser['university'];
    
    // Get filters
    $search = $_GET['search'] ?? '';
    $major = $_GET['major'] ?? '';
    $year = $_GET['year'] ?? '';
    
    // Build query
    $sql = "
        SELECT id, full_name, username, nickname, major, year, 
               pronouns, availability_status, profile_image_path
        FROM users
        WHERE university = ? 
        AND email_verified = TRUE
        AND id != ?
    ";
    
    $params = [$university, $currentUserId];
    
    // Add search filter
    if (!empty($search)) {
        $sql .= " AND (full_name LIKE ? OR username LIKE ? OR nickname LIKE ?)";
        $searchPattern = '%' . $search . '%';
        $params[] = $searchPattern;
        $params[] = $searchPattern;
        $params[] = $searchPattern;
    }
    
    // Add major filter
    if (!empty($major)) {
        $sql .= " AND major = ?";
        $params[] = $major;
    }
    
    // Add year filter
    if (!empty($year)) {
        $sql .= " AND year = ?";
        $params[] = $year;
    }
    
    $sql .= " ORDER BY full_name ASC LIMIT 50";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $students = $stmt->fetchAll();
    
    jsonResponse([
        'success' => true,
        'university' => $university,
        'students' => $students,
        'count' => count($students)
    ]);
    
} catch (PDOException $e) {
    jsonResponse(['error' => 'Failed to fetch directory'], 500);
}
?>