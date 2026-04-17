<?php
/**
 * FloodWatch Authentication System
 * Handles user registration, login, and session management
 */

// Ensure session is started only once
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function register($fullName, $email, $password, $barangay = '') {
    require_once 'database.php';
    
    // Validate inputs
    if (empty($fullName) || empty($email) || empty($password)) {
        return ['success' => false, 'message' => 'All fields are required'];
    }
    
    // Validate email format
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return ['success' => false, 'message' => 'Invalid email format'];
    }
    
    // Validate password strength
    if (strlen($password) < 8) {
        return ['success' => false, 'message' => 'Password must be at least 8 characters'];
    }
    
    if (!preg_match('/[a-zA-Z]/', $password) || !preg_match('/[0-9]/', $password)) {
        return ['success' => false, 'message' => 'Password must contain both letters and numbers'];
    }
    
    // Check if email already exists
    $conn = connectDatabase();
    if (!$conn) {
        return ['success' => false, 'message' => 'Database connection failed'];
    }
    
    $stmt = $conn->prepare("SELECT user_id FROM users WHERE email = ?");
    if (!$stmt) {
        $conn->close();
        return ['success' => false, 'message' => 'Database error'];
    }
    
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $stmt->close();
        $conn->close();
        return ['success' => false, 'message' => 'Email already registered'];
    }
    
    $stmt->close();
    
    // Hash password with bcrypt
    $password_hash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 10]);
    
    // Insert new user
    $stmt = $conn->prepare("INSERT INTO users (fullName, email, password_hash, barangay, is_active) VALUES (?, ?, ?, ?, 1)");
    if (!$stmt) {
        $conn->close();
        return ['success' => false, 'message' => 'Database error'];
    }
    
    $stmt->bind_param("ssss", $fullName, $email, $password_hash, $barangay);
    
    if ($stmt->execute()) {
        $stmt->close();
        $conn->close();
        return ['success' => true, 'message' => 'Registration successful. Please login to continue.'];
    } else {
        $stmt->close();
        $conn->close();
        return ['success' => false, 'message' => 'Registration failed. Please try again.'];
    }
}

function login($email, $password) {
    require_once 'database.php';
    
    if (empty($email) || empty($password)) {
        return ['success' => false, 'message' => 'Email and password required'];
    }
    
    $conn = connectDatabase();
    if (!$conn) {
        return ['success' => false, 'message' => 'Database connection failed'];
    }
    
    $stmt = $conn->prepare("SELECT user_id, fullName, email, barangay, password_hash, is_active FROM users WHERE email = ?");
    if (!$stmt) {
        $conn->close();
        return ['success' => false, 'message' => 'Database error'];
    }
    
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        $stmt->close();
        $conn->close();
        return ['success' => false, 'message' => 'Invalid email or password'];
    }
    
    $user = $result->fetch_assoc();
    $stmt->close();
    
    // Check if account is active
    if (!$user['is_active']) {
        $conn->close();
        return ['success' => false, 'message' => 'Account is inactive. Please contact support.'];
    }
    
    // Verify password
    if (!password_verify($password, $user['password_hash'])) {
        $conn->close();
        return ['success' => false, 'message' => 'Invalid email or password'];
    }
    
    // Set session variables
    $_SESSION['user_id'] = $user['user_id'];
    $_SESSION['email'] = $user['email'];
    $_SESSION['fullName'] = $user['fullName'];
    $_SESSION['barangay'] = $user['barangay'];
    $_SESSION['login_time'] = time();
    
    // Update last login timestamp
    $update_stmt = $conn->prepare("UPDATE users SET updated_at = NOW() WHERE user_id = ?");
    if ($update_stmt) {
        $update_stmt->bind_param("i", $user['user_id']);
        $update_stmt->execute();
        $update_stmt->close();
    }
    
    $conn->close();
    
    return [
        'success' => true,
        'message' => 'Login successful',
        'user' => [
            'user_id' => $user['user_id'],
            'fullName' => $user['fullName'],
            'email' => $user['email'],
            'barangay' => $user['barangay']
        ]
    ];
}

function logout() {
    // Clear all session data
    $_SESSION = [];
    
    // Destroy the session
    if (session_id() !== '') {
        setcookie(session_name(), '', time() - 3600, '/');
    }
    session_destroy();
    
    return ['success' => true, 'message' => 'Logged out successfully'];
}

function isLoggedIn() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

function getCurrentUser() {
    if (!isLoggedIn()) {
        return null;
    }
    
    return [
        'user_id' => $_SESSION['user_id'] ?? null,
        'fullName' => $_SESSION['fullName'] ?? 'User',
        'email' => $_SESSION['email'] ?? '',
        'barangay' => $_SESSION['barangay'] ?? ''
    ];
}

function requireLogin() {
    if (!isLoggedIn()) {
        http_response_code(401);
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Unauthorized']);
        exit;
    }
}

function requireLoginPage() {
    if (!isLoggedIn()) {
        header('Location: login.html');
        exit;
    }
}

function getUserById($user_id) {
    require_once 'database.php';
    
    $conn = connectDatabase();
    if (!$conn) {
        return null;
    }
    
    $stmt = $conn->prepare("SELECT user_id, fullName, email, barangay, created_at FROM users WHERE user_id = ? AND is_active = 1");
    if (!$stmt) {
        $conn->close();
        return null;
    }
    
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        $stmt->close();
        $conn->close();
        return null;
    }
    
    $user = $result->fetch_assoc();
    $stmt->close();
    $conn->close();
    
    return $user;
}

function changePassword($user_id, $currentPassword, $newPassword) {
    require_once 'database.php';
    
    // Validate new password
    if (strlen($newPassword) < 8 || !preg_match('/[a-zA-Z]/', $newPassword) || !preg_match('/[0-9]/', $newPassword)) {
        return ['success' => false, 'message' => 'Password must be at least 8 characters with letters and numbers'];
    }
    
    $conn = connectDatabase();
    if (!$conn) {
        return ['success' => false, 'message' => 'Database connection failed'];
    }
    
    // Get current password hash
    $stmt = $conn->prepare("SELECT password_hash FROM users WHERE user_id = ?");
    if (!$stmt) {
        $conn->close();
        return ['success' => false, 'message' => 'Database error'];
    }
    
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        $stmt->close();
        $conn->close();
        return ['success' => false, 'message' => 'User not found'];
    }
    
    $user = $result->fetch_assoc();
    $stmt->close();
    
    // Verify current password
    if (!password_verify($currentPassword, $user['password_hash'])) {
        $conn->close();
        return ['success' => false, 'message' => 'Current password is incorrect'];
    }
    
    // Hash new password
    $newHash = password_hash($newPassword, PASSWORD_BCRYPT, ['cost' => 10]);
    
    // Update password
    $update_stmt = $conn->prepare("UPDATE users SET password_hash = ?, updated_at = NOW() WHERE user_id = ?");
    if (!$update_stmt) {
        $conn->close();
        return ['success' => false, 'message' => 'Database error'];
    }
    
    $update_stmt->bind_param("si", $newHash, $user_id);
    
    if ($update_stmt->execute()) {
        $update_stmt->close();
        $conn->close();
        return ['success' => true, 'message' => 'Password changed successfully'];
    } else {
        $update_stmt->close();
        $conn->close();
        return ['success' => false, 'message' => 'Password change failed'];
    }
}
?>
    
    // Hash password
    $hashedPassword = password_hash($password, PASSWORD_BCRYPT);
    
    // Insert new user
    $stmt = $conn->prepare("INSERT INTO users (username, email, password, location, role) VALUES (?, ?, ?, ?, 'user')");
    $stmt->bind_param("ssss", $username, $email, $hashedPassword, $location);
    
    if (!$stmt->execute()) {
        $conn->close();
        return ['success' => false, 'message' => 'Registration failed: ' . $conn->error];
    }
    
    $user_id = $conn->insert_id;
    
    logActivity($user_id, 'user_registration', 'New user registered');
    
    $conn->close();
    return ['success' => true, 'message' => 'Registration successful', 'user_id' => $user_id];
}

// User logout
function logout() {
    if (isset($_SESSION['user_id'])) {
        logActivity($_SESSION['user_id'], 'user_logout', 'User logged out');
    }
    
    session_destroy();
    return ['success' => true, 'message' => 'Logged out successfully'];
}

// Change password
function changePassword($user_id, $old_password, $new_password) {
    $conn = connectDatabase();
    if (!$conn) {
        return ['success' => false, 'message' => 'Database connection failed'];
    }
    
    $stmt = $conn->prepare("SELECT password FROM users WHERE id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    
    if (!$result || !password_verify($old_password, $result['password'])) {
        $conn->close();
        return ['success' => false, 'message' => 'Old password is incorrect'];
    }
    
    $hashedPassword = password_hash($new_password, PASSWORD_BCRYPT);
    
    $stmt = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
    $stmt->bind_param("si", $hashedPassword, $user_id);
    
    if (!$stmt->execute()) {
        $conn->close();
        return ['success' => false, 'message' => 'Password change failed'];
    }
    
    logActivity($user_id, 'password_changed', 'User changed password');
    
    $conn->close();
    return ['success' => true, 'message' => 'Password changed successfully'];
}

// Require login middleware
function requireLogin() {
    if (!isLoggedIn()) {
        header('HTTP/1.0 403 Forbidden');
        die(json_encode(['error' => 'Authentication required']));
    }
}

// Check if user is admin
function isAdmin() {
    return isLoggedIn() && ($_SESSION['role'] === 'admin' || $_SESSION['role'] === 'moderator');
}
?>
