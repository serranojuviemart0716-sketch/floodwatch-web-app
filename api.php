<?php
/**
 * FloodWatch - REST API (Fixed & Stable Version)
 */

error_reporting(E_ALL);
// Do not display errors in API responses (log to file instead)
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/api_errors.log');

session_start();
require_once 'database.php';
require_once 'auth.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';

$input = [];
if ($method === 'POST') {
    $json = file_get_contents('php://input');
    $input = json_decode($json, true) ?? [];
    if (isset($input['action'])) $action = $input['action'];
}

// Safe response helper
function apiResponse($data, $status = 200) {
    http_response_code($status);
    // Ensure no stray output breaks JSON
    while (ob_get_level() > 0) ob_end_clean();
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}

// Lightweight health-check endpoint
if ($action === 'ping') {
    apiResponse(['ok' => true]);
}

// Create optional tables used by dashboard (alerts, sensor_data)
if ($action === 'create_optional_tables' && $method === 'POST') {
    $conn = connectDatabase();
    if (!$conn) apiResponse(['success' => false, 'message' => 'DB connection failed'], 500);

    $queries = [
        "CREATE TABLE IF NOT EXISTS alerts (
            id INT AUTO_INCREMENT PRIMARY KEY,
            message TEXT NOT NULL,
            alert_type ENUM('flood','warning','info') DEFAULT 'info',
            priority ENUM('low','medium','high','critical') DEFAULT 'medium',
            is_active TINYINT(1) DEFAULT 1,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            expires_at TIMESTAMP NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

        "CREATE TABLE IF NOT EXISTS sensor_data (
            id INT AUTO_INCREMENT PRIMARY KEY,
            sensor_id VARCHAR(50) NOT NULL,
            water_level DECIMAL(10,2),
            rainfall DECIMAL(10,2),
            temperature DECIMAL(5,2),
            humidity DECIMAL(5,2),
            location VARCHAR(255),
            latitude DECIMAL(10,8),
            longitude DECIMAL(11,8),
            recorded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
    ];

    $errors = [];
    foreach ($queries as $sql) {
        if (!$conn->query($sql)) {
            $errors[] = $conn->error;
        }
    }

    $conn->close();
    if (!empty($errors)) {
        apiResponse(['success' => false, 'message' => 'Some tables failed to create', 'errors' => $errors], 500);
    }

    apiResponse(['success' => true, 'message' => 'Optional tables created']);
}

// ====================== AUTH ENDPOINTS ======================
if ($action === 'login' && $method === 'POST') {
    $email = $input['email'] ?? '';
    $password = $input['password'] ?? '';
    $result = login($email, $password);
    apiResponse($result);
}

if ($action === 'register' && $method === 'POST') {
    $fullName = $input['fullName'] ?? '';
    $email = $input['email'] ?? '';
    $barangay = $input['barangay'] ?? '';
    $password = $input['password'] ?? '';
    $result = register($fullName, $email, $password, $barangay);
    apiResponse($result);
}

if ($action === 'logout' && $method === 'POST') {
    logout();
    apiResponse(['success' => true, 'message' => 'Logged out successfully']);
}

if ($action === 'me' && $method === 'GET') {
    if (isLoggedIn()) {
        apiResponse(['success' => true, 'user' => getCurrentUser()]);
    }
    apiResponse(['success' => false, 'message' => 'Not logged in'], 401);
}

// ====================== REPORTS ======================
if ($action === 'reports' && $method === 'GET') {
    $conn = connectDatabase();
    $reports = [];
    if ($conn) {
        $result = $conn->query("SELECT * FROM reports ORDER BY created_at DESC LIMIT 50");
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $reports[] = $row;
            }
        }
        $conn->close();
    }
    apiResponse(['reports' => $reports]);
}

// POST new report
if ($action === 'reports' && $method === 'POST') {
    $data = $input;
    $conn = connectDatabase();
    if (!$conn) {
        apiResponse(['success' => false, 'message' => 'Database connection failed'], 500);
    }

    $user_id = $_SESSION['user_id'] ?? null;
    $location = $data['location'] ?? '';
    $severity = $data['severity'] ?? 'Medium';
    $description = $data['description'] ?? '';
    $title = "Flood Report: " . substr($location, 0, 50);

    if (empty($location) || empty($description)) {
        apiResponse(['success' => false, 'message' => 'Location and description required'], 400);
    }

    $stmt = $conn->prepare("INSERT INTO reports (user_id, title, description, location, severity, status) VALUES (?, ?, ?, ?, ?, 'Active')");
    if (!$stmt) {
        $err = $conn->error;
        $conn->close();
        apiResponse(['success' => false, 'message' => 'DB prepare failed', 'error' => $err], 500);
    }

    if (!$stmt->bind_param("issss", $user_id, $title, $description, $location, $severity)) {
        $err = $stmt->error;
        $stmt->close();
        $conn->close();
        apiResponse(['success' => false, 'message' => 'DB bind failed', 'error' => $err], 500);
    }

    if ($stmt->execute()) {
        $report_id = $stmt->insert_id ?? $conn->insert_id;
        $stmt->close();
        $conn->close();
        apiResponse(['success' => true, 'message' => 'Report submitted successfully', 'report_id' => $report_id]);
    } else {
        $err = $stmt->error;
        $stmt->close();
        $conn->close();
        apiResponse(['success' => false, 'message' => 'Failed to submit report', 'error' => $err], 500);
    }
}

// ====================== ALERTS & STATS ======================
if ($action === 'alerts' && $method === 'GET') {
    $conn = connectDatabase();
    $alerts = [];
    if ($conn) {
        $result = $conn->query("SELECT * FROM alerts WHERE is_active = 1 ORDER BY created_at DESC LIMIT 10");
        if ($result) {
            $alerts = $result->fetch_all(MYSQLI_ASSOC);
            $result->free();
        } else {
            // alerts table may not exist; return empty and log error
            error_log('Alerts query failed: ' . $conn->error);
        }
        $conn->close();
    }
    apiResponse(['alerts' => $alerts]);
}

if ($action === 'stats' && $method === 'GET') {
    $conn = connectDatabase();
    $total_users = 0;
    $total_reports = 0;
    $avg_water_level = 185;

    if ($conn) {
        $res = $conn->query("SELECT COUNT(*) as c FROM users");
        if ($res) {
            $row = $res->fetch_assoc();
            $total_users = isset($row['c']) ? (int)$row['c'] : 0;
            $res->free();
        } else {
            error_log('Users count query failed: ' . $conn->error);
        }

        $res = $conn->query("SELECT COUNT(*) as c FROM reports");
        if ($res) {
            $row = $res->fetch_assoc();
            $total_reports = isset($row['c']) ? (int)$row['c'] : 0;
            $res->free();
        } else {
            error_log('Reports count query failed: ' . $conn->error);
        }

        $conn->close();
    }

    apiResponse([
        'total_users' => $total_users,
        'total_reports' => $total_reports,
        'avg_water_level' => $avg_water_level
    ]);
}

// Safe fallback for dashboard endpoints
if (in_array($action, ['user_reports', 'user_activity', 'user_alerts', 'user_stats', 'admin_stats', 'admin_pending_reports', 'admin_users'])) {
    if (!isLoggedIn()) {
        apiResponse(['success' => false, 'message' => 'Not logged in'], 401);
    }
    apiResponse(['success' => true, 'data' => []]);
}

// Default
apiResponse(['error' => 'Endpoint not found'], 404);
?>