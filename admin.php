<?php
/**
 * FloodWatch - Admin Functions
 * Moderation, user management, and analytics
 */

require_once 'database.php';
require_once 'auth.php';

// Get all users
function getAllUsers($limit = 50, $offset = 0) {
    $conn = connectDatabase();
    if (!$conn) return [];
    
    $stmt = $conn->prepare("SELECT id, username, email, location, role, created_at FROM users LIMIT ? OFFSET ?");
    $stmt->bind_param("ii", $limit, $offset);
    $stmt->execute();
    
    $users = [];
    while ($row = $stmt->get_result()->fetch_assoc()) {
        $users[] = $row;
    }
    
    $conn->close();
    return $users;
}

// Get total user count
function getTotalUserCount() {
    $conn = connectDatabase();
    if (!$conn) return 0;
    
    $count = $conn->query("SELECT COUNT(*) as count FROM users")->fetch_assoc()['count'];
    $conn->close();
    return $count;
}

// Ban/suspend user
function suspendUser($user_id, $reason = '') {
    $conn = connectDatabase();
    if (!$conn) return false;
    
    $stmt = $conn->prepare("UPDATE users SET role = 'suspended' WHERE id = ?");
    $stmt->bind_param("i", $user_id);
    $result = $stmt->execute();
    
    logActivity(getCurrentUser()['id'] ?? null, 'user_suspended', "User ID: $user_id, Reason: $reason");
    
    $conn->close();
    return $result;
}

// Verify report
function verifyReport($report_id, $moderator_id) {
    $conn = connectDatabase();
    if (!$conn) return false;
    
    $stmt = $conn->prepare("UPDATE reports SET status = 'verified' WHERE id = ?");
    $stmt->bind_param("i", $report_id);
    $result = $stmt->execute();
    
    logActivity($moderator_id, 'report_verified', "Report ID: $report_id verified");
    
    $conn->close();
    return $result;
}

// Reject report
function rejectReport($report_id, $moderator_id, $reason = '') {
    $conn = connectDatabase();
    if (!$conn) return false;
    
    $stmt = $conn->prepare("UPDATE reports SET status = 'rejected' WHERE id = ?");
    $stmt->bind_param("i", $report_id);
    $result = $stmt->execute();
    
    logActivity($moderator_id, 'report_rejected', "Report ID: $report_id rejected. Reason: $reason");
    
    $conn->close();
    return $result;
}

// Resolve report
function resolveReport($report_id) {
    $conn = connectDatabase();
    if (!$conn) return false;
    
    $stmt = $conn->prepare("UPDATE reports SET status = 'resolved' WHERE id = ?");
    $stmt->bind_param("i", $report_id);
    $result = $stmt->execute();
    
    $conn->close();
    return $result;
}

// Create alert
function createAlert($message, $type = 'warning', $priority = 'high', $expiry_hours = 24) {
    $conn = connectDatabase();
    if (!$conn) return false;
    
    $expires_at = date('Y-m-d H:i:s', strtotime("+$expiry_hours hours"));
    
    $stmt = $conn->prepare("INSERT INTO alerts (message, alert_type, priority, expires_at) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("ssss", $message, $type, $priority, $expires_at);
    $result = $stmt->execute();
    
    logActivity(getCurrentUser()['id'] ?? null, 'alert_created', "Alert: $message");
    
    $conn->close();
    return $result;
}

// Deactivate alert
function deactivateAlert($alert_id) {
    $conn = connectDatabase();
    if (!$conn) return false;
    
    $stmt = $conn->prepare("UPDATE alerts SET is_active = false WHERE id = ?");
    $stmt->bind_param("i", $alert_id);
    $result = $stmt->execute();
    
    $conn->close();
    return $result;
}

// Get pending reports
function getPendingReports($limit = 20) {
    $conn = connectDatabase();
    if (!$conn) return [];
    
    $stmt = $conn->prepare("SELECT * FROM reports WHERE status = 'pending' ORDER BY created_at DESC LIMIT ?");
    $stmt->bind_param("i", $limit);
    $stmt->execute();
    
    $reports = [];
    while ($row = $stmt->get_result()->fetch_assoc()) {
        $reports[] = $row;
    }
    
    $conn->close();
    return $reports;
}

// Get high-priority reports
function getHighPriorityReports() {
    $conn = connectDatabase();
    if (!$conn) return [];
    
    $stmt = $conn->prepare("SELECT * FROM reports WHERE severity IN ('High', 'Critical') AND status != 'resolved' ORDER BY created_at DESC LIMIT 20");
    $stmt->execute();
    
    $reports = [];
    while ($row = $stmt->get_result()->fetch_assoc()) {
        $reports[] = $row;
    }
    
    $conn->close();
    return $reports;
}

// Get analytics
function getAnalytics($days = 30) {
    $conn = connectDatabase();
    if (!$conn) return [];
    
    $since = date('Y-m-d', strtotime("-$days days"));
    
    $analytics = [
        'total_reports' => 0,
        'reports_by_severity' => [],
        'reports_by_day' => [],
        'active_users' => 0,
        'high_risk_areas' => []
    ];
    
    // Total reports
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM reports WHERE created_at >= ?");
    $stmt->bind_param("s", $since);
    $stmt->execute();
    $analytics['total_reports'] = $stmt->get_result()->fetch_assoc()['count'];
    
    // Reports by severity
    $result = $conn->query("SELECT severity, COUNT(*) as count FROM reports WHERE created_at >= '$since' GROUP BY severity");
    while ($row = $result->fetch_assoc()) {
        $analytics['reports_by_severity'][$row['severity']] = $row['count'];
    }
    
    // Reports by day
    $result = $conn->query("SELECT DATE(created_at) as date, COUNT(*) as count FROM reports WHERE created_at >= '$since' GROUP BY DATE(created_at)");
    while ($row = $result->fetch_assoc()) {
        $analytics['reports_by_day'][$row['date']] = $row['count'];
    }
    
    // Active users
    $stmt = $conn->prepare("SELECT COUNT(DISTINCT user_id) as count FROM reports WHERE created_at >= ?");
    $stmt->bind_param("s", $since);
    $stmt->execute();
    $analytics['active_users'] = $stmt->get_result()->fetch_assoc()['count'];
    
    // High risk areas
    $result = $conn->query("SELECT location, COUNT(*) as count, MAX(severity) as worst_severity FROM reports WHERE created_at >= '$since' AND severity IN ('High', 'Critical') GROUP BY location ORDER BY count DESC LIMIT 10");
    while ($row = $result->fetch_assoc()) {
        $analytics['high_risk_areas'][] = $row;
    }
    
    $conn->close();
    return $analytics;
}

// Export analytics to JSON
function exportAnalyticsJSON($analytics) {
    return json_encode($analytics, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
}

// Get user statistics
function getUserStatistics() {
    $conn = connectDatabase();
    if (!$conn) return [];
    
    $stats = [
        'total_users' => 0,
        'users_by_role' => [],
        'new_users_today' => 0,
        'active_users_today' => 0
    ];
    
    // Total users
    $stats['total_users'] = $conn->query("SELECT COUNT(*) as count FROM users")->fetch_assoc()['count'];
    
    // Users by role
    $result = $conn->query("SELECT role, COUNT(*) as count FROM users GROUP BY role");
    while ($row = $result->fetch_assoc()) {
        $stats['users_by_role'][$row['role']] = $row['count'];
    }
    
    // New users today
    $stats['new_users_today'] = $conn->query("SELECT COUNT(*) as count FROM users WHERE DATE(created_at) = CURDATE()")->fetch_assoc()['count'];
    
    // Active users today
    $stats['active_users_today'] = $conn->query("SELECT COUNT(DISTINCT user_id) as count FROM activity_logs WHERE DATE(created_at) = CURDATE()")->fetch_assoc()['count'];
    
    $conn->close();
    return $stats;
}

// System health check
function systemHealthCheck() {
    return [
        'database' => connectDatabase() ? 'OK' : 'ERROR',
        'disk_space' => disk_free_space('/') > 1000000000 ? 'OK' : 'LOW',
        'memory_limit' => ini_get('memory_limit'),
        'max_execution_time' => ini_get('max_execution_time'),
        'file_uploads' => ini_get('file_uploads') ? 'Enabled' : 'Disabled'
    ];
}
?>
