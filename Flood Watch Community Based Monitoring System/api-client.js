/**
 * FloodWatch - API Client
 * JavaScript utilities for interacting with the REST API
 * 
 * ⚡ OPTIMIZED: Works with performance.js for caching, deduplication, and visibility-aware refresh
 */

const API_BASE = '/Flood%20Watch%20Community%20Based%20Monitoring%20System/api.php';

// API Request Helper
async function apiRequest(endpoint, method = 'GET', data = null) {
    const options = {
        method: method,
        headers: {
            'Content-Type': 'application/json',
        }
    };
    
    if (data) {
        options.body = JSON.stringify(data);
    }
    
    try {
        // Convert endpoint to action parameter (e.g., '/reports' -> '?action=reports')
        const action = endpoint.startsWith('/') ? endpoint.substring(1) : endpoint;
        const url = `${API_BASE}?action=${action}`;
        
        const response = await fetch(url, options);
        
        if (!response.ok) {
            throw new Error(`API Error: ${response.status}`);
        }
        
        return await response.json();
    } catch (error) {
        console.error(`API Request Failed (${endpoint}):`, error);
        return null;
    }
}

// ===== REPORTS API =====

// Get all reports
async function getReports() {
    return await apiRequest('/reports', 'GET');
}

// Create new report
async function submitReport(location, severity, description, latitude = null, longitude = null) {
    const reportData = {
        location: location,
        severity: severity,
        description: description,
        latitude: latitude,
        longitude: longitude
    };
    
    return await apiRequest('/reports', 'POST', reportData);
}

// ===== ALERTS API =====

// Get active alerts
async function getAlerts() {
    return await apiRequest('/alerts', 'GET');
}

// ===== SENSOR DATA API =====

// Get sensor data
async function getSensorData() {
    return await apiRequest('/sensors', 'GET');
}

// Log sensor reading
async function logSensorData(sensor_id, water_level, rainfall, temperature, humidity, location = '', latitude = null, longitude = null) {
    const sensorData = {
        sensor_id: sensor_id,
        water_level: water_level,
        rainfall: rainfall,
        temperature: temperature,
        humidity: humidity,
        location: location,
        latitude: latitude,
        longitude: longitude
    };
    
    return await apiRequest('/sensors', 'POST', sensorData);
}

// ===== STATISTICS API =====

// Get dashboard stats
async function getDashboardStats() {
    return await apiRequest('/stats', 'GET');
}

// ===== UI UPDATE FUNCTIONS =====

// Update reports in real-time
async function refreshReportsFromAPI() {
    const result = await getReports();
    
    if (!result || !result.reports) return;
    
    const container = document.getElementById('reports-container');
    if (!container) return;
    
    container.innerHTML = '';
    
    result.reports.forEach(report => {
        const severityEmoji = report.severity === 'High' ? '🔴' : 
                             report.severity === 'Medium' ? '🟠' : '🟢';
        
        const severityBg = report.severity === 'High' ? 'bg-red-500/10 text-red-400' :
                          report.severity === 'Medium' ? 'bg-amber-400/10 text-amber-400' :
                          'bg-emerald-400/10 text-emerald-400';
        
        const reportHTML = `
            <div class="glass rounded-3xl p-6 flex gap-6 card-hover">
                <div class="flex-shrink-0 text-4xl">${severityEmoji}</div>
                <div class="flex-1">
                    <div class="flex justify-between">
                        <div class="font-semibold">${escapeHtml(report.location)}</div>
                        <div class="text-xs text-white/50">${report.created_at}</div>
                    </div>
                    <div class="text-cyan-400 text-sm mt-px">Community Report</div>
                    <p class="mt-3 text-white/80">${escapeHtml(report.description)}</p>
                    <div class="text-xs uppercase mt-4 inline-block px-4 py-1 rounded-3xl ${severityBg}">
                        ${report.severity.toUpperCase()} SEVERITY
                    </div>
                </div>
            </div>
        `;
        
        container.insertAdjacentHTML('beforeend', reportHTML);
    });
}

// Update alerts in real-time
async function refreshAlertsFromAPI() {
    const result = await getAlerts();
    
    if (!result || !result.alerts) return;
    
    // Update alerts section
    const alertsContainer = document.querySelector('[id="alerts"] .grid');
    if (alertsContainer) {
        alertsContainer.innerHTML = '';
        
        result.alerts.forEach(alert => {
            const alertHTML = `
                <div class="glass rounded-3xl p-6 flex gap-4 card-hover">
                    <div class="text-4xl">🚨</div>
                    <div class="flex-1">
                        <p class="font-medium">${escapeHtml(alert.message)}</p>
                        <div class="text-xs text-cyan-300 mt-3">${alert.alert_type} • ${alert.priority}</div>
                    </div>
                </div>
            `;
            
            alertsContainer.insertAdjacentHTML('beforeend', alertHTML);
        });
    }
}

// Update dashboard stats from API
async function refreshDashboardStatsFromAPI() {
    const result = await getDashboardStats();
    
    if (!result) return;
    
    // Update water level
    const waterEl = document.getElementById('dash-water');
    if (waterEl) {
        waterEl.textContent = result.avg_water_level || '0';
    }
    
    // Update user count
    const activeEl = document.getElementById('active-count');
    if (activeEl) {
        activeEl.textContent = result.total_users || '0';
    }
}

// Update sensor data visualization
async function refreshSensorDataFromAPI() {
    const result = await getSensorData();
    
    if (!result || !result.sensors) return;
    
    console.log('Sensor data received:', result.sensors);
    
    // Update maps or visualizations based on sensor data
    result.sensors.forEach(sensor => {
        console.log(`Sensor ${sensor.sensor_id}: Water Level ${sensor.water_level}cm, Rainfall ${sensor.rainfall}mm`);
    });
}

// Escape HTML for security
function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

// ===== AUTO-REFRESH =====

// Start auto-refresh intervals
function startAPIRefreshIntervals() {
    // Refresh reports every 30 seconds
    setInterval(refreshReportsFromAPI, 30000);
    
    // Refresh alerts every 20 seconds
    setInterval(refreshAlertsFromAPI, 20000);
    
    // Refresh dashboard stats every 15 seconds
    setInterval(refreshDashboardStatsFromAPI, 15000);
    
    // Refresh sensor data every 60 seconds
    setInterval(refreshSensorDataFromAPI, 60000);
    
    // Initial load
    refreshReportsFromAPI();
    refreshAlertsFromAPI();
    refreshDashboardStatsFromAPI();
    refreshSensorDataFromAPI();
}

// ===== MANUAL REFRESH BUTTON =====

// Override manual refresh to use API
function refreshDashboardAPI() {
    refreshDashboardStatsFromAPI();
    refreshReportsFromAPI();
    refreshAlertsFromAPI();
    
    // Add toast
    const toast = document.createElement('div');
    toast.style.cssText = 'position:fixed; bottom:24px; right:24px; background:rgba(0,240,255,0.9); color:#000; padding:16px 24px; border-radius:9999px; font-weight:600; box-shadow:0 10px 15px -3px rgb(0 240 255)';
    toast.innerHTML = '✅ Live data synced from API';
    document.body.appendChild(toast);
    
    setTimeout(() => toast.remove(), 2800);
    
    console.log('%c🔄 Dashboard refreshed via API', 'color:#00f0ff; font-family:monospace');
}

// Override the original refreshDashboard function
if (typeof window !== 'undefined') {
    const originalRefresh = window.refreshDashboard;
    window.refreshDashboard = refreshDashboardAPI;
}

// Initialize on page load
window.addEventListener('DOMContentLoaded', () => {
    console.log('%c📡 FloodWatch API Client initialized', 'color:#00f0ff; font-weight:bold');
    startAPIRefreshIntervals();
});
