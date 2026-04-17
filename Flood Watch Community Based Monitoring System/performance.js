/**
 * FloodWatch - Performance & Optimization Module
 * Handles caching, debouncing, request deduplication, and smooth animations
 */

// ===== REQUEST CACHING SYSTEM =====
const apiCache = {
    data: {},
    ttl: {},
    
    set: function(key, value, ttlSeconds = 60) {
        this.data[key] = value;
        this.ttl[key] = Date.now() + (ttlSeconds * 1000);
    },
    
    get: function(key) {
        if (!this.data[key]) return null;
        if (Date.now() > this.ttl[key]) {
            delete this.data[key];
            delete this.ttl[key];
            return null;
        }
        return this.data[key];
    },
    
    clear: function() {
        this.data = {};
        this.ttl = {};
    }
};

// ===== REQUEST DEDUPLICATION =====
const pendingRequests = {};

async function cachedApiRequest(endpoint, method = 'GET', data = null, cacheSeconds = 60) {
    const cacheKey = `${method}:${endpoint}`;
    
    // Return cached data if available
    const cached = apiCache.get(cacheKey);
    if (cached) return cached;
    
    // Return pending request if one is in flight
    if (pendingRequests[cacheKey]) {
        return pendingRequests[cacheKey];
    }
    
    // Create new request
    const requestPromise = apiRequest(endpoint, method, data);
    pendingRequests[cacheKey] = requestPromise;
    
    try {
        const result = await requestPromise;
        apiCache.set(cacheKey, result, cacheSeconds);
        return result;
    } finally {
        delete pendingRequests[cacheKey];
    }
}

// ===== DEBOUNCING & THROTTLING =====
function debounce(func, delay) {
    let timeoutId;
    return function(...args) {
        clearTimeout(timeoutId);
        timeoutId = setTimeout(() => func(...args), delay);
    };
}

function throttle(func, delay) {
    let lastRun = 0;
    return function(...args) {
        const now = Date.now();
        if (now - lastRun >= delay) {
            func(...args);
            lastRun = now;
        }
    };
}

// ===== SMOOTH DOM UPDATES WITH RAF =====
function smoothDOMUpdate(callback) {
    requestAnimationFrame(() => {
        requestAnimationFrame(callback);
    });
}

// ===== BATCH DOM UPDATES =====
function batchDOMUpdates(updates) {
    return new Promise(resolve => {
        requestAnimationFrame(() => {
            updates.forEach(update => update());
            resolve();
        });
    });
}

// ===== VISIBILITY-AWARE REFRESH =====
let isPageVisible = true;

document.addEventListener('visibilitychange', () => {
    isPageVisible = !document.hidden;
    if (isPageVisible) {
        // Refresh data when tab becomes visible
        console.log('%c🔄 Tab visible - refreshing data', 'color:#00f0ff; font-weight:bold');
        refreshReportsFromAPI();
        refreshAlertsFromAPI();
        refreshDashboardStatsFromAPI();
    }
});

// Only refresh if page is visible
function conditionalRefresh(refreshFunction) {
    if (isPageVisible) {
        smoothDOMUpdate(refreshFunction);
    }
}

// ===== OPTIMIZED REPORT RENDERING =====
async function optimizedRefreshReports() {
    const result = await cachedApiRequest('/reports', 'GET', null, 45);
    
    if (!result || !result.reports) return;
    
    const container = document.getElementById('reports-container');
    if (!container) return;
    
    const fragment = document.createDocumentFragment();
    
    result.reports.forEach(report => {
        const div = document.createElement('div');
        div.className = 'glass rounded-3xl p-6 flex gap-6 card-hover animate-fadeIn';
        
        const severityData = {
            'High': { emoji: '🔴', bg: 'bg-red-500/10 text-red-400' },
            'Medium': { emoji: '🟠', bg: 'bg-amber-400/10 text-amber-400' },
            'Low': { emoji: '🟢', bg: 'bg-emerald-400/10 text-emerald-400' }
        }[report.severity] || { emoji: '🟢', bg: 'bg-emerald-400/10 text-emerald-400' };
        
        div.innerHTML = `
            <div class="flex-shrink-0 text-4xl">${severityData.emoji}</div>
            <div class="flex-1 min-w-0">
                <div class="flex justify-between gap-2 flex-wrap">
                    <div class="font-semibold truncate">${escapeHtml(report.location)}</div>
                    <div class="text-xs text-white/50 flex-shrink-0">${report.created_at}</div>
                </div>
                <div class="text-cyan-400 text-sm mt-px">Community Report</div>
                <p class="mt-3 text-white/80 line-clamp-2">${escapeHtml(report.description)}</p>
                <div class="text-xs uppercase mt-4 inline-block px-4 py-1 rounded-3xl ${severityData.bg}">
                    ${report.severity.toUpperCase()} SEVERITY
                </div>
            </div>
        `;
        
        fragment.appendChild(div);
    });
    
    smoothDOMUpdate(() => {
        container.innerHTML = '';
        container.appendChild(fragment);
    });
}

// ===== OPTIMIZED ALERTS RENDERING =====
async function optimizedRefreshAlerts() {
    const result = await cachedApiRequest('/alerts', 'GET', null, 30);
    
    if (!result || !result.alerts) return;
    
    const alertsContainer = document.querySelector('[id="alerts"] .grid');
    if (!alertsContainer) return;
    
    const fragment = document.createDocumentFragment();
    
    result.alerts.forEach(alert => {
        const div = document.createElement('div');
        div.className = 'glass rounded-3xl p-6 flex gap-4 card-hover animate-fadeIn';
        div.innerHTML = `
            <div class="text-4xl flex-shrink-0">🚨</div>
            <div class="flex-1 min-w-0">
                <p class="font-medium truncate">${escapeHtml(alert.message)}</p>
                <div class="text-xs text-cyan-300 mt-3">${alert.alert_type} • ${alert.priority}</div>
            </div>
        `;
        
        fragment.appendChild(div);
    });
    
    smoothDOMUpdate(() => {
        alertsContainer.innerHTML = '';
        alertsContainer.appendChild(fragment);
    });
}

// ===== OPTIMIZED DASHBOARD STATS =====
async function optimizedRefreshDashboardStats() {
    const result = await cachedApiRequest('/stats', 'GET', null, 30);
    
    if (!result) return;
    
    smoothDOMUpdate(() => {
        const waterEl = document.getElementById('dash-water');
        if (waterEl) {
            const newValue = result.avg_water_level || '0';
            if (waterEl.textContent !== newValue) {
                waterEl.textContent = newValue;
                waterEl.style.animation = 'none';
                setTimeout(() => waterEl.style.animation = '', 10);
            }
        }
        
        const activeEl = document.getElementById('active-count');
        if (activeEl) {
            const newValue = result.total_users || '0';
            if (activeEl.textContent !== newValue) {
                activeEl.textContent = newValue;
                activeEl.style.animation = 'none';
                setTimeout(() => activeEl.style.animation = '', 10);
            }
        }
    });
}

// ===== PERFORMANCE MONITORING =====
class PerformanceMonitor {
    constructor() {
        this.metrics = {};
    }
    
    mark(name) {
        performance.mark(name);
    }
    
    measure(name, startMark, endMark) {
        try {
            performance.measure(name, startMark, endMark);
            const measure = performance.getEntriesByName(name)[0];
            this.metrics[name] = measure.duration;
            
            if (measure.duration > 100) {
                console.warn(`⚠️ Slow operation: ${name} took ${measure.duration.toFixed(2)}ms`);
            }
        } catch (e) {
            console.error('Performance measurement error:', e);
        }
    }
    
    log() {
        console.table(this.metrics);
    }
}

const perfMonitor = new PerformanceMonitor();

// ===== INTERSECTION OBSERVER FOR LAZY ANIMATION =====
const observerOptions = {
    threshold: 0.1,
    rootMargin: '50px'
};

const animationObserver = new IntersectionObserver((entries) => {
    entries.forEach(entry => {
        if (entry.isIntersecting) {
            entry.target.classList.add('animate-fadeIn');
            animationObserver.unobserve(entry.target);
        }
    });
}, observerOptions);

// ===== BACKGROUND TAB OPTIMIZATION =====
let refreshIntervalIds = [];

function stopBackgroundRefreshes() {
    refreshIntervalIds.forEach(id => clearInterval(id));
    refreshIntervalIds = [];
    console.log('%c⏸️ Background refreshes paused', 'color:#ff9800');
}

function startBackgroundRefreshes() {
    console.log('%c▶️ Background refreshes resumed', 'color:#4caf50');
    // Re-initialize intervals
    setupOptimizedRefreshIntervals();
}

// ===== OPTIMIZED AUTO-REFRESH WITH VISIBILITY AWARENESS =====
function setupOptimizedRefreshIntervals() {
    if (!isPageVisible) return;
    
    // Clear existing intervals
    refreshIntervalIds.forEach(id => clearInterval(id));
    refreshIntervalIds = [];
    
    // Use longer intervals in background
    const reportInterval = isPageVisible ? 30000 : 60000;
    const alertInterval = isPageVisible ? 20000 : 45000;
    const statsInterval = isPageVisible ? 15000 : 30000;
    const sensorInterval = isPageVisible ? 60000 : 120000;
    
    refreshIntervalIds.push(
        setInterval(() => conditionalRefresh(optimizedRefreshReports), reportInterval),
        setInterval(() => conditionalRefresh(optimizedRefreshAlerts), alertInterval),
        setInterval(() => conditionalRefresh(optimizedRefreshDashboardStats), statsInterval),
        setInterval(() => conditionalRefresh(refreshSensorDataFromAPI), sensorInterval)
    );
    
    // Initial load
    optimizedRefreshReports();
    optimizedRefreshAlerts();
    optimizedRefreshDashboardStats();
    refreshSensorDataFromAPI();
}

// ===== DEBOUNCED FORM SUBMISSION =====
const debouncedSubmitReport = debounce(async function() {
    const location = document.getElementById('locationInput')?.value || '';
    const description = document.getElementById('descriptionInput')?.value || '';
    
    if (!location || !description) {
        showToast('Please fill in all fields', 'error');
        return;
    }
    
    const severityRadio = document.querySelector('input[name="severity"]:checked');
    const severity = severityRadio ? severityRadio.value : 'Medium';
    
    try {
        const result = await submitReport(location, severity, description, null, null);
        
        if (result && result.success) {
            document.getElementById('reportForm').reset();
            showToast('✅ Report submitted successfully!', 'success');
            optimizedRefreshReports();
        } else {
            showToast('❌ Failed to submit report', 'error');
        }
    } catch (error) {
        showToast('❌ Error submitting report', 'error');
        console.error('Report submission error:', error);
    }
}, 300);

// ===== THROTTLED WINDOW SCROLL HANDLER =====
const throttledScroll = throttle(() => {
    const scrolled = window.scrollY > 50;
    const navbar = document.querySelector('nav');
    if (navbar) {
        navbar.style.boxShadow = scrolled ? '0 10px 30px rgba(0, 240, 255, 0.2)' : '';
    }
}, 100);

window.addEventListener('scroll', throttledScroll);

// ===== INITIALIZE OPTIMIZATIONS =====
document.addEventListener('DOMContentLoaded', () => {
    setupOptimizedRefreshIntervals();
    console.log('%c⚡ Performance optimizations enabled', 'color:#4caf50; font-weight:bold; font-size:12px');
});

// Export for use in index.html
window.optimizedRefreshReports = optimizedRefreshReports;
window.optimizedRefreshAlerts = optimizedRefreshAlerts;
window.optimizedRefreshDashboardStats = optimizedRefreshDashboardStats;
window.apiCache = apiCache;
window.perfMonitor = perfMonitor;
