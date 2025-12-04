<?php
// helpers.php - Helper functions

function sanitizeInput($data) {
    return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
}

function isEduEmail($email) {
    return preg_match('/\.edu$/i', $email) === 1;
}

function hashPassword($password) {
    return password_hash($password, PASSWORD_BCRYPT);
}

function verifyPassword($password, $hash) {
    return password_verify($password, $hash);
}

function generateVerificationCode() {
    return str_pad(rand(0, 999999), 6, '0', STR_PAD_LEFT);
}

function generateSessionToken() {
    return bin2hex(random_bytes(32));
}
function extractUniversity($email) {
    // Extract domain from email
    $parts = explode('@', $email);
    if (count($parts) != 2) {
        return 'Unknown University';
    }
    
    $domain = strtolower($parts[1]);
    
    // Remove common subdomains
    $domain = preg_replace('/^(mail\.|student\.|alumni\.)/', '', $domain);
    
    // Comprehensive university mapping
    $universityMap = [
        'isu.edu' => 'Idaho State University',
        'uidaho.edu' => 'University of Idaho',
        'boisestate.edu' => 'Boise State University',
        'stanford.edu' => 'Stanford University',
        'harvard.edu' => 'Harvard University',
        'mit.edu' => 'MIT',
        'berkeley.edu' => 'UC Berkeley',
        'ucla.edu' => 'UCLA',
        'usc.edu' => 'USC',
        'nyu.edu' => 'New York University',
        'columbia.edu' => 'Columbia University',
        'princeton.edu' => 'Princeton University',
        'yale.edu' => 'Yale University',
        'upenn.edu' => 'University of Pennsylvania',
        'cornell.edu' => 'Cornell University',
        'duke.edu' => 'Duke University',
        'northwestern.edu' => 'Northwestern University',
        'uchicago.edu' => 'University of Chicago',
        'caltech.edu' => 'Caltech',
        'jhu.edu' => 'Johns Hopkins University',
        'umich.edu' => 'University of Michigan',
        'utexas.edu' => 'University of Texas',
        'wisc.edu' => 'University of Wisconsin',
        'washington.edu' => 'University of Washington',
        'gatech.edu' => 'Georgia Tech'
    ];
    
    // Check if we have exact match
    if (isset($universityMap[$domain])) {
        return $universityMap[$domain];
    }
    
    // Try to extract a readable name from domain
    $domainParts = explode('.', $domain);
    $name = ucwords(str_replace(['-', '_'], ' ', $domainParts[0]));
    
    return $name . ' University';
}
function getClientIP() {
    if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
        return $_SERVER['HTTP_CLIENT_IP'];
    } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        return $_SERVER['HTTP_X_FORWARDED_FOR'];
    } else {
        return $_SERVER['REMOTE_ADDR'] ?? 'Unknown';
    }
}

function logAuthEvent($userId, $email, $eventType, $ipAddress, $userAgent) {
    try {
        $pdo = getDBConnection();
        $stmt = $pdo->prepare("
            INSERT INTO auth_logs (user_id, email, event_type, ip_address, user_agent) 
            VALUES (?, ?, ?, ?, ?)
        ");
        $stmt->execute([$userId, $email, $eventType, $ipAddress, $userAgent]);
    } catch (PDOException $e) {
        error_log("Failed to log auth event: " . $e->getMessage());
    }
}
function createThumbnail($sourcePath, $destinationPath, $width, $height) {
    list($origWidth, $origHeight, $type) = getimagesize($sourcePath);
    
    if (!$origWidth || !$origHeight) {
        return false;
    }
    
    switch ($type) {
        case IMAGETYPE_JPEG:
            $source = imagecreatefromjpeg($sourcePath);
            break;
        case IMAGETYPE_PNG:
            $source = imagecreatefrompng($sourcePath);
            break;
        default:
            return false;
    }
    
    $aspectRatio = $origWidth / $origHeight;
    
    if ($width / $height > $aspectRatio) {
        $width = $height * $aspectRatio;
    } else {
        $height = $width / $aspectRatio;
    }
    
    $thumbnail = imagecreatetruecolor($width, $height);
    
    if ($type == IMAGETYPE_PNG) {
        imagealphablending($thumbnail, false);
        imagesavealpha($thumbnail, true);
    }
    
    imagecopyresampled($thumbnail, $source, 0, 0, 0, 0, $width, $height, $origWidth, $origHeight);
    
    switch ($type) {
        case IMAGETYPE_JPEG:
            imagejpeg($thumbnail, $destinationPath, 85);
            break;
        case IMAGETYPE_PNG:
            imagepng($thumbnail, $destinationPath, 8);
            break;
    }
    
    imagedestroy($source);
    imagedestroy($thumbnail);
    
    return true;
}
?>