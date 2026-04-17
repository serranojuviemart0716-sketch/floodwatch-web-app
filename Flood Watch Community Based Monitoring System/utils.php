<?php
/**
 * FloodWatch - Utility Functions
 * Helper functions and utilities for the application
 */

// Email notification helper
function sendAlertEmail($email, $location, $severity, $message) {
    $to = $email;
    $subject = "[FloodWatch Alert] " . $severity . " - " . $location;
    
    $body = "
    <html>
        <head>
            <style>
                body { font-family: Arial, sans-serif; }
                .container { max-width: 600px; margin: 0 auto; }
                .header { background: #00f0ff; padding: 20px; color: #0a0a2e; }
                .content { padding: 20px; background: #f5f5f5; }
                .severity-high { color: #f6465d; }
                .severity-medium { color: #ffc107; }
                .severity-low { color: #4caf50; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h1>🌊 FloodWatch Alert</h1>
                </div>
                <div class='content'>
                    <h2 class='severity-$severity'>$severity Severity Alert</h2>
                    <p><strong>Location:</strong> $location</p>
                    <p><strong>Message:</strong> $message</p>
                    <p><strong>Time:</strong> " . date('Y-m-d H:i:s') . "</p>
                    <hr>
                    <p><a href='http://localhost/Flood%20Watch%20Community%20Based%20Monitoring%20System/index.php'>View Dashboard</a></p>
                </div>
            </div>
        </body>
    </html>
    ";
    
    $headers = "MIME-Version: 1.0" . "\r\n";
    $headers .= "Content-type: text/html; charset=UTF-8" . "\r\n";
    $headers .= "From: alerts@floodwatch.local" . "\r\n";
    
    // Uncomment to enable email sending
    // mail($to, $subject, $body, $headers);
}

// Format file size
function formatBytes($bytes, $precision = 2) {
    $units = ['B', 'KB', 'MB', 'GB', 'TB'];
    
    for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
        $bytes /= 1024;
    }
    
    return round($bytes, $precision) . ' ' . $units[$i];
}

// Sanitize input
function sanitize($input) {
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}

// Validate coordinates
function validateCoordinates($latitude, $longitude) {
    return is_numeric($latitude) && is_numeric($longitude) && 
           $latitude >= -90 && $latitude <= 90 && 
           $longitude >= -180 && $longitude <= 180;
}

// Get severity color
function getSeverityColor($severity) {
    $colors = [
        'Low' => '#4caf50',
        'Medium' => '#ffc107',
        'High' => '#ff9800',
        'Critical' => '#f6465d'
    ];
    
    return $colors[$severity] ?? '#999';
}

// Get severity icon
function getSeverityIcon($severity) {
    $icons = [
        'Low' => '🟢',
        'Medium' => '🟠',
        'High' => '🔴',
        'Critical' => '🚨'
    ];
    
    return $icons[$severity] ?? '❓';
}

// Calculate distance between two points (Haversine formula)
function getDistance($lat1, $lon1, $lat2, $lon2) {
    $earth_radius = 6371; // km
    
    $dLat = deg2rad($lat2 - $lat1);
    $dLon = deg2rad($lon2 - $lon1);
    
    $a = sin($dLat / 2) * sin($dLat / 2) +
         cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
         sin($dLon / 2) * sin($dLon / 2);
    
    $c = 2 * asin(sqrt($a));
    return $earth_radius * $c;
}

// Generate unique ID
function generateUniqueId($prefix = '') {
    return $prefix . '_' . uniqid() . '_' . bin2hex(random_bytes(4));
}

// Check if IP is allowed
function isIpAllowed($ip) {
    $blocklist = []; // Add blocked IPs here
    return !in_array($ip, $blocklist);
}

// Rate limiting
function checkRateLimit($identifier, $limit = 10, $window = 3600) {
    $cache_file = sys_get_temp_dir() . '/ratelimit_' . md5($identifier);
    
    if (file_exists($cache_file)) {
        $data = unserialize(file_get_contents($cache_file));
        
        if ($data['count'] >= $limit && (time() - $data['time']) < $window) {
            return false; // Rate limit exceeded
        }
        
        if ((time() - $data['time']) >= $window) {
            $data['count'] = 1;
            $data['time'] = time();
        } else {
            $data['count']++;
        }
    } else {
        $data = ['count' => 1, 'time' => time()];
    }
    
    file_put_contents($cache_file, serialize($data));
    return true;
}

// Parse weather data
function parseWeatherData($data) {
    return [
        'temperature' => $data['temp'] ?? 0,
        'humidity' => $data['humidity'] ?? 0,
        'rainfall' => $data['rainfall'] ?? 0,
        'wind_speed' => $data['wind_speed'] ?? 0
    ];
}

// Calculate flood risk
function calculateFloodRisk($water_level, $rainfall, $temperature) {
    $risk_score = 0;
    
    // Water level factor (0-40 points)
    if ($water_level > 250) $risk_score += 40;
    elseif ($water_level > 220) $risk_score += 30;
    elseif ($water_level > 180) $risk_score += 20;
    else $risk_score += 0;
    
    // Rainfall factor (0-40 points)
    if ($rainfall > 100) $risk_score += 40;
    elseif ($rainfall > 50) $risk_score += 25;
    elseif ($rainfall > 20) $risk_score += 10;
    
    // Temperature factor (0-20 points) - affects melting/evaporation
    if ($temperature > 35) $risk_score += 15;
    elseif ($temperature > 25) $risk_score += 10;
    
    return min($risk_score, 100);
}

// Determine risk level
function getRiskLevel($score) {
    if ($score >= 80) return 'CRITICAL';
    elseif ($score >= 60) return 'HIGH';
    elseif ($score >= 40) return 'MODERATE';
    else return 'LOW';
}

// Format datetime
function formatDateTime($datetime) {
    return date('Y-m-d H:i:s', strtotime($datetime));
}

// Get time ago
function getTimeAgo($datetime) {
    $time = strtotime($datetime);
    $diff = time() - $time;
    
    if ($diff < 60) return "$diff seconds ago";
    elseif ($diff < 3600) return floor($diff / 60) . " minutes ago";
    elseif ($diff < 86400) return floor($diff / 3600) . " hours ago";
    elseif ($diff < 604800) return floor($diff / 86400) . " days ago";
    else return floor($diff / 604800) . " weeks ago";
}

// Export report to CSV
function exportReportsToCSV($reports) {
    $csv = "ID,Location,Severity,Status,Reporter,Description,Created At\n";
    
    foreach ($reports as $report) {
        $csv .= '"' . $report['id'] . '",';
        $csv .= '"' . $report['location'] . '",';
        $csv .= '"' . $report['severity'] . '",';
        $csv .= '"' . $report['status'] . '",';
        $csv .= '"' . ($report['reporter'] ?? 'Anonymous') . '",';
        $csv .= '"' . str_replace('"', '""', $report['description']) . '",';
        $csv .= '"' . $report['created_at'] . "\"\n";
    }
    
    return $csv;
}

// Get system status
function getSystemStatus() {
    return [
        'timestamp' => date('Y-m-d H:i:s'),
        'server' => $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown',
        'php_version' => phpversion(),
        'memory_usage' => memory_get_usage(true),
        'max_memory' => ini_get('memory_limit'),
        'uptime' => function_exists('php_uname') ? php_uname() : 'Unknown'
    ];
}
?>
