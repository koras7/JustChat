<?php
// api/upload.php - Profile picture upload

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

$currentUserId = $_SESSION['user_id'];

// Check if file was uploaded
if (!isset($_FILES['profile_image']) || $_FILES['profile_image']['error'] !== UPLOAD_ERR_OK) {
    jsonResponse(['error' => 'No file uploaded or upload error'], 400);
}

$file = $_FILES['profile_image'];
$fileName = $file['name'];
$fileTmpName = $file['tmp_name'];
$fileSize = $file['size'];
$fileType = $file['type'];

// Validate file type
$allowedTypes = ['image/jpeg', 'image/jpg', 'image/png'];
if (!in_array($fileType, $allowedTypes)) {
    jsonResponse(['error' => 'Only JPG and PNG images are allowed'], 400);
}

// Validate file size (max 5MB)
$maxSize = 5 * 1024 * 1024; // 5MB
if ($fileSize > $maxSize) {
    jsonResponse(['error' => 'File size must be less than 5MB'], 400);
}

try {
    $pdo = getDBConnection();
    
    // Use absolute paths
    $uploadsDir = dirname(__DIR__) . '/uploads';
    $profilesDir = $uploadsDir . '/profiles';
    $thumbnailsDir = $uploadsDir . '/thumbnails';
    
    // Create directories if they don't exist
    if (!file_exists($uploadsDir)) {
        mkdir($uploadsDir, 0777, true);
    }
    if (!file_exists($profilesDir)) {
        mkdir($profilesDir, 0777, true);
    }
    if (!file_exists($thumbnailsDir)) {
        mkdir($thumbnailsDir, 0777, true);
    }
    
    // Generate unique filename
    $extension = pathinfo($fileName, PATHINFO_EXTENSION);
    $newFileName = 'profile_' . $currentUserId . '_' . time() . '.' . $extension;
    $uploadPath = $profilesDir . '/' . $newFileName;
    
    // Move uploaded file
    if (!move_uploaded_file($fileTmpName, $uploadPath)) {
        jsonResponse(['error' => 'Failed to save file'], 500);
    }
    
    // Create thumbnail
    $thumbnailPath = $thumbnailsDir . '/' . $newFileName;
    createThumbnail($uploadPath, $thumbnailPath, 150, 150);
    
    // Update user profile (use relative path for database)
    $dbPath = 'justchat/uploads/profiles/' . $newFileName;
    $stmt = $pdo->prepare("UPDATE users SET profile_image_path = ? WHERE id = ?");
    $stmt->execute([$dbPath, $currentUserId]);
    
    // Record upload in uploads table
    $stmt = $pdo->prepare("
        INSERT INTO uploads (user_id, file_path, file_type, file_size) 
        VALUES (?, ?, ?, ?)
    ");
    $stmt->execute([$currentUserId, $dbPath, $fileType, $fileSize]);
    
    jsonResponse([
        'success' => true,
        'message' => 'Profile picture uploaded successfully',
        'image_path' => $dbPath
    ]);
    
} catch (Exception $e) {
    error_log("Upload error: " . $e->getMessage());
    jsonResponse(['error' => 'Failed to upload image: ' . $e->getMessage()], 500);
}
?>