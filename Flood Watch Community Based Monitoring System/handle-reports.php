<?php
/**
 * FloodWatch - Report Handler
 * Handles report submission, session management, and real-time data updates
 */

require_once 'database.php';
require_once 'auth.php';

session_start();

// Initialize session data if not exists
if (!isset($_SESSION['reports'])) {
    $_SESSION['reports'] = [
        [
            'id' => 'r1',
            'location' => 'Barangay 1, Bacolod City',
            'severity' => 'High',
            'description' => 'River overflow detected near the bridge. Water level rising rapidly.',
            'time' => '2026-04-17 08:45:12',
            'reporter' => 'Maria Santos'
        ],
        [
            'id' => 'r2',
            'location' => 'Barangay 2, Talisay',
            'severity' => 'Medium',
            'description' => 'Minor flooding in low-lying residential area. Residents safe but need sandbags.',
            'time' => '2026-04-17 08:12:45',
            'reporter' => 'Juan Dela Cruz'
        ],
        [
            'id' => 'r3',
            'location' => 'Barangay 3, Silay City',
            'severity' => 'Low',
            'description' => 'Water accumulation on main road after heavy rain. No immediate danger.',
            'time' => '2026-04-17 07:58:33',
            'reporter' => 'Liza Mendoza'
        ]
    ];
}

if (!isset($_SESSION['alerts'])) {
    $_SESSION['alerts'] = [
        ['message' => '🚨 Flash flood warning for Bacolod City coastal areas', 'time' => '2m ago'],
        ['message' => '📍 New sensor spike detected at Banago River', 'time' => '11m ago'],
        ['message' => '🌧️ Heavy rainfall expected next 3 hours', 'time' => '27m ago']
    ];
}

// Handle report submission
$success = '';
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_report'])) {
    $location = trim($_POST['location'] ?? '');
    $severity = trim($_POST['severity'] ?? 'Medium');
    $description = trim($_POST['description'] ?? '');
    
    if (empty($location) || empty($description)) {
        $error = 'Location and description are required.';
    } else {
        $newReport = [
            'id' => 'r' . uniqid(),
            'location' => $location,
            'severity' => $severity,
            'description' => $description,
            'time' => date('Y-m-d H:i:s'),
            'reporter' => 'You (Community Member)'
        ];
        
        array_unshift($_SESSION['reports'], $newReport);
        
        // Simulate community notification
        $_SESSION['alerts'][] = [
            'message' => '✅ New community report submitted from ' . explode(',', $location)[0],
            'time' => 'Just now'
        ];
        
        $success = 'Report successfully submitted and broadcast to the community network!';
        
        // Clear form values after successful submission
        $_POST = [];
    }
}

// Simulate real-time data update (every page load adds slight variation)
$currentWaterLevel = rand(185, 245); // cm
$riskLevel = $currentWaterLevel > 220 ? 'CRITICAL' : ($currentWaterLevel > 180 ? 'HIGH' : 'MODERATE');
$activeSensors = rand(142, 158);
?>
