document.addEventListener('DOMContentLoaded', function () {

    /* ── PASSWORD STRENGTH ── */
    const pw   = document.getElementById('password');
    const bars = ['s1','s2','s3','s4'].map(id => document.getElementById(id));
    const colors = ['#ef4444','#f97316','#fcf408','#09ff00'];

    if (pw && bars[0]) {
        pw.addEventListener('input', () => {
            const v = pw.value;
            let score = 0;
            if (v.length >= 8)          score++;
            if (/[A-Z]/.test(v))        score++;
            if (/[0-9]/.test(v))        score++;
            if (/[^A-Za-z0-9]/.test(v)) score++;
            bars.forEach((b, i) => {
                if (b) b.style.background = i < score ? colors[score - 1] : '#e5e7eb';
            });
        });
    }

    /* ── SAFE FETCH HELPER ── */
    async function safeFetch(url, formData) {
        let rawText = '';
        try {
            const res = await fetch(url, { method: 'POST', body: formData, credentials: 'include' });
            rawText   = await res.text();
            return JSON.parse(rawText);
        } catch (err) {
            const preview = rawText
                ? rawText.replace(/<[^>]*>/g, '').trim().slice(0, 300)
                : 'No response from server.';
            alert('⚠️ Server Response Error:\n\n' + preview +
                  '\n\n—\nMake sure:\n• XAMPP Apache & MySQL are running\n• You are accessing via http://localhost/...\n• The database "sit_in_monitoring" exists');
            return null;
        }
    }

    /* ── LOGIN FORM ── */
    const loginForm = document.getElementById('login-form');
    if (loginForm) {
        loginForm.addEventListener('submit', async function (e) {
            e.preventDefault();
            const data = await safeFetch('login.php', new FormData(this));
            console.log('Login response:', data);
            if (!data) return;
            if (data.success) {
                if (data.role === 'admin') {
                    window.location.href = 'admin_dashboard.php';
                } else {
                    window.location.href = 'dashboard.php';
                }
            } else {
                alert(data.message || 'Login failed.');
            }
        });
    }

    /* ── REGISTER FORM ── */
    const registerForm = document.getElementById('register-form');
    if (registerForm) {
        registerForm.addEventListener('submit', async function (e) {
            e.preventDefault();

            const password       = document.getElementById('password').value;
            const repeatPassword = document.getElementById('repeatPassword').value;

            if (password !== repeatPassword) {
                alert('Passwords do not match!');
                return;
            }

            if (password.length < 8) {
                alert('Password must be at least 8 characters.');
                return;
            }

            const data = await safeFetch('register.php', new FormData(this));
            if (!data) return;

            if (data.success) {
                alert('Account created! Please login.');
                window.location.href = 'index.html';
            } else {
                alert(data.message || 'Registration failed.');
            }
        });
    }

    /* ── LOAD LEADERBOARD FOR HOME PAGE ── */
    const leaderboardList = document.getElementById('leaderboard-list');
    if (leaderboardList) {
        loadHomePageLeaderboard();
    }
});

/* ── LOAD HOME PAGE LEADERBOARD ── */
async function loadHomePageLeaderboard() {
    const leaderboardList = document.getElementById('leaderboard-list');
    if (!leaderboardList) return;

    try {
        const response = await fetch('leaderboard_public_fetch.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            credentials: 'include',
            body: JSON.stringify({})
        });

        const data = await response.json();

        if (!data.success || !data.leaderboard || data.leaderboard.length === 0) {
            leaderboardList.innerHTML = `
                <div class="leaderboard-empty">
                    <i class="fa-solid fa-trophy"></i>
                    <p>Leaderboard not available yet</p>
                </div>
            `;
            return;
        }

        const leaderboard = data.leaderboard;
        const medals = ['🥇', '🥈', '🥉'];
        
        leaderboardList.innerHTML = leaderboard.map((student, index) => {
            const rank = index + 1;
            const medal = medals[index] || '';
            const rankClass = rank <= 3 ? `top-${rank}` : '';
            const hasPic = student.profile_pic && student.profile_pic.trim() !== '';
            const initials = (student.first_name.charAt(0) + student.last_name.charAt(0)).toUpperCase();
            
            return `
                <div class="leaderboard-item">
                    <div class="leaderboard-rank-num ${rankClass}">
                        ${medal || `<strong>#${rank}</strong>`}
                    </div>
                    <div class="leaderboard-avatar-wrapper">
                        ${
                            hasPic
                                ? `<img src="uploads/${student.profile_pic}" alt="${student.name}" class="leaderboard-avatar-img" onerror="this.style.display='none'; this.parentElement.querySelector('.leaderboard-avatar-placeholder').style.display='flex';" />`
                                : ''
                        }
                        <div class="leaderboard-avatar-placeholder" ${hasPic ? 'style="display:none;"' : ''}>
                            ${initials}
                        </div>
                    </div>
                    <div class="leaderboard-info">
                        <div class="leaderboard-name">${student.name}</div>
                        <div class="leaderboard-id">${student.id_number}</div>
                        <div class="leaderboard-course">${student.course}</div>
                    </div>
                    <div class="leaderboard-stats">
                        <div class="leaderboard-stat">
                            <div class="leaderboard-stat-label">Sessions</div>
                            <div class="leaderboard-stat-value">${student.sit_in_count}</div>
                        </div>
                        <div class="leaderboard-stat">
                            <div class="leaderboard-stat-label">Hours</div>
                            <div class="leaderboard-stat-value">${student.total_hours}</div>
                        </div>
                        <div class="leaderboard-stat">
                            <div class="leaderboard-stat-label">Points</div>
                            <div class="leaderboard-stat-value leaderboard-points">${student.points}</div>
                        </div>
                    </div>
                </div>
            `;
        }).join('');
    } catch (error) {
        console.error('Error loading leaderboard:', error);
        leaderboardList.innerHTML = `
            <div class="leaderboard-empty">
                <i class="fa-solid fa-triangle-exclamation"></i>
                <p>Failed to load leaderboard</p>
            </div>
        `;
    }
}


/* ============================================================
   CCS Sit-in Portal – Student Dashboard
   ============================================================
   S is populated from window.__SESSION__ which is injected
   by dashboard.php using real PHP session data.
   Falls back to defaults so the file still works standalone.
   ============================================================ */

const _sess = window.__SESSION__ || {};

const S = {
    first:        _sess.first        || '',
    middle:       _sess.middle       || '',
    last:         _sess.last         || '',
    id:           _sess.id           || '',
    email:        _sess.email        || '',
    address:      _sess.address      || '',
    course:       _sess.course       || '',
    year:         _sess.year         || '',
    year_raw:     _sess.year_raw     || 1,
    session:      _sess.session      ?? 30,
    totalSession: _sess.totalSession ?? 30,
    pic:          _sess.pic          || '',     // raw relative path stored in DB
    picSrc:       _sess.picSrc       || '',     // cache-busted src ready for <img> elements
};

let historyData   = [];
let reservations  = [];
let histPage      = 1;
let pendingDelIdx = null;
let feedbackSitId = null;

/* ── TOAST ── */
function showToast(msg, icon = 'fa-circle-check', color = null) {
    const toastEl = document.getElementById('liveToast');
    if (!toastEl) return;
    document.getElementById('toastMsg').textContent = msg;
    const ic = document.getElementById('toastIcon');
    ic.className   = 'fa-solid ' + icon;
    ic.style.color = color || '';
    bootstrap.Toast.getOrCreateInstance(toastEl, { delay: 3200 }).show();
}

function updateThemeButton(theme) {
    const btn = document.getElementById('themeToggleBtn');
    if (!btn) return;
    const label = theme === 'dark' ? 'Bright Mode' : 'Dark Mode';
    btn.innerHTML = `<i class="fa-solid ${theme === 'dark' ? 'fa-sun' : 'fa-moon'}"></i> ${label}`;
}

function applyTheme(theme) {
    document.body.classList.toggle('theme-dark', theme === 'dark');
    localStorage.setItem('dashboardTheme', theme);
    updateThemeButton(theme);
}

function toggleTheme() {
    const active = document.body.classList.contains('theme-dark') ? 'dark' : 'light';
    applyTheme(active === 'dark' ? 'light' : 'dark');
}

function initTheme() {
    const stored = localStorage.getItem('dashboardTheme') || 'light';
    applyTheme(stored);
}

/* ── TABS ── */
function switchTab(tab) {
    document.querySelectorAll('.view').forEach(v => v.classList.remove('active'));
    document.querySelectorAll('[data-tab]').forEach(a => a.classList.remove('active'));
    const view = document.getElementById('view-' + tab);
    if (view) view.classList.add('active');
    const link = document.querySelector('[data-tab="' + tab + '"]');
    if (link) link.classList.add('active');
    if (tab === 'history') renderHistory();
    if (tab === 'summary') { 
        // Ensure history data is loaded before rendering summary
        if (!historyData || historyData.length === 0) {
            loadHistoryData().then(() => {
                loadSummaryData(); 
                renderSummaryHistory();
            });
        } else {
            loadSummaryData(); 
            renderSummaryHistory();
        }
    }
    if (tab === 'reservation') loadMyReservations();
    window.scrollTo({ top: 0, behavior: 'smooth' });
}

/* ── NOTIFICATIONS ── */
async function loadNotifications() {
    const data = await postJSON('notifications_fetch.php', {});
    const itemsEl = document.getElementById('notifItems');
    const badgeEl = document.getElementById('notifBadge');
    if (!itemsEl || !badgeEl) return;

    if (!data || !data.success) {
        itemsEl.innerHTML = '<div class="notif-empty">Unable to load notifications</div>';
        badgeEl.style.display = 'none';
        return;
    }

    const items = data.notifications || [];
    if (!items.length) {
        itemsEl.innerHTML = '<div class="notif-empty">No new notifications</div>';
        badgeEl.style.display = 'none';
        return;
    }

    itemsEl.innerHTML = items.map(n => `
        <div class="notif-item">
          <div class="notif-icon ${n.type === 'success' ? 'green' : n.type === 'danger' ? 'red' : n.type === 'warning' ? 'gold' : 'blue'}">
            <i class="fa-solid ${n.type === 'success' ? 'fa-circle-check' : n.type === 'danger' ? 'fa-circle-xmark' : n.type === 'warning' ? 'fa-triangle-exclamation' : 'fa-envelope'}"></i>
          </div>
          <div>
            <div class="notif-title">${n.message}</div>
            <div class="notif-time">${new Date(n.created_at).toLocaleString()}</div>
          </div>
        </div>`).join('');

    badgeEl.textContent = items.length;
    badgeEl.style.display = 'inline-block';
}

async function clearNotifs() {
    document.getElementById('notifItems').innerHTML =
        '<div class="notif-empty">No new notifications</div>';
    document.getElementById('notifBadge').style.display = 'none';
    await postJSON('notifications_clear.php', {});
    showToast('All notifications cleared');
    const dd = bootstrap.Dropdown.getInstance(document.getElementById('notifToggle'));
    if (dd) dd.hide();
}

/* ── ANNOUNCEMENTS ── */
async function loadAnnouncements() {
    const data = await postJSON('announcement_fetch.php', {});
    
    // Try multiple times to find container (DOM timing issue)
    let container = document.getElementById('announcementsContainer');
    if (!container) {
        await new Promise(resolve => setTimeout(resolve, 300));
        container = document.getElementById('announcementsContainer');
    }
    
    if (!container) {
        return; // Element not found, exit silently
    }

    if (!data || !data.success) {
        container.innerHTML = '<div class="ann-item"><div class="ann-text ann-empty">Unable to load announcements</div></div>';
        return;
    }

    const announcements = data.announcements || [];
    if (!announcements.length) {
        container.innerHTML = '<div class="ann-item"><div class="ann-text ann-empty">No announcements yet.</div></div>';
        return;
    }

    container.innerHTML = announcements.map(ann => {
        const date = new Date(ann.created_at);
        const dateStr = `${date.getFullYear()} ${date.toLocaleString('en', { month: 'short' })} ${String(date.getDate()).padStart(2, '0')}`;
        return `
            <div class="ann-item">
              <div class="ann-meta">
                <span class="ann-tag">${ann.posted_by}</span>
                <span class="ann-date"><i class="fa-regular fa-calendar"></i> ${dateStr}</span>
              </div>
              <div class="ann-text">${ann.body}</div>
            </div>`;
    }).join('');
}

/* ── LABORATORY STATUS ── */
async function loadLabStatus() {
    try {
        const response = await fetch('labs_status_fetch.php', {
            credentials: 'include',
            headers: {
                'Accept': 'application/json'
            }
        });
        
        if (!response.ok) {
            throw new Error(`HTTP ${response.status}`);
        }
        
        const data = await response.json();
        const container = document.getElementById('labStatusContainer');
        if (!container) return;
        
        if (!data.success || !data.labs) {
            container.innerHTML = '<div style="text-align: center; padding: 20px; color: var(--text3);"><i class="fa-solid fa-info-circle"></i> Labs data unavailable</div>';
            return;
        }
        
        if (data.labs.length === 0) {
            container.innerHTML = '<div style="text-align: center; padding: 20px; color: var(--text3);">No laboratories available</div>';
            return;
        }
        
        // Create a responsive grid layout
        const gridHTML = `
            <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(140px, 1fr)); gap: 12px; max-height: 400px; overflow-y: auto; padding-right: 4px;">
                ${data.labs.map(lab => {
                    const capacity = lab.max_capacity;
                    const occupancy = ((lab.active_students / capacity) * 100).toFixed(0);
                    let statusColor = 'var(--green)';
                    let statusBgColor = 'rgba(34, 197, 94, 0.1)';
                    
                    // If lab is disabled by admin, show as CLOSED (red) regardless of occupancy
                    if (!lab.is_open) {
                        statusColor = 'var(--red)';
                        statusBgColor = 'rgba(239, 68, 68, 0.1)';
                    } else if (occupancy > 80) {
                        statusColor = 'var(--red)';
                        statusBgColor = 'rgba(239, 68, 68, 0.1)';
                    } else if (occupancy > 50) {
                        statusColor = '#f97316';
                        statusBgColor = 'rgba(249, 115, 22, 0.1)';
                    }
                    
                    const isOpen = lab.is_open; // Respect the is_open flag from backend (which checks disabled dates)
                    
                    return `
                        <div style="
                            background: linear-gradient(135deg, var(--bg2) 0%, ${statusBgColor} 100%);
                            border: 1px solid rgba(100, 150, 200, 0.2);
                            border-radius: 10px;
                            padding: 14px;
                            transition: all 0.3s ease;
                            cursor: pointer;
                            position: relative;
                            display: flex;
                            flex-direction: column;
                        "
                        onmouseover="this.style.transform='translateY(-4px)'; this.style.borderColor='${statusColor}'; this.style.boxShadow='0 8px 16px ${statusColor}22';"
                        onmouseout="this.style.transform='translateY(0)'; this.style.borderColor='rgba(100, 150, 200, 0.2)'; this.style.boxShadow='none';">
                            
                            <!-- Status Badge -->
                            <div style="
                                display: flex;
                                align-items: center;
                                justify-content: space-between;
                                margin-bottom: 10px;
                            ">
                                <i class="fa-solid fa-building" style="color: var(--text3); font-size: 0.9rem;"></i>
                                <span style="
                                    background: ${statusColor};
                                    color: white;
                                    padding: 2px 8px;
                                    border-radius: 4px;
                                    font-size: 0.65rem;
                                    font-weight: 600;
                                    display: flex;
                                    align-items: center;
                                    gap: 3px;
                                ">
                                    <i class="fa-solid ${isOpen ? 'fa-dot-circle' : 'fa-circle-xmark'}" style="font-size: 0.6rem;"></i>
                                    ${isOpen ? 'OPEN' : 'CLOSED'}
                                </span>
                            </div>
                            
                            <!-- Lab Name -->
                            <div style="
                                font-weight: 700;
                                color: var(--text);
                                font-size: 0.9rem;
                                margin-bottom: 10px;
                                line-height: 1.2;
                            ">${lab.name}</div>
                            
                            <!-- Progress Bar -->
                            <div style="
                                background: var(--border);
                                height: 6px;
                                border-radius: 3px;
                                overflow: hidden;
                                margin-bottom: 8px;
                            ">
                                <div style="
                                    height: 100%;
                                    background: ${statusColor};
                                    width: ${occupancy}%;
                                    transition: width 0.3s ease;
                                "></div>
                            </div>
                            
                            <!-- Stats -->
                            <div style="
                                display: flex;
                                justify-content: space-between;
                                margin-bottom: 8px;
                                font-size: 0.7rem;
                                color: var(--text3);
                            ">
                                <span><strong style="color: var(--text);">${lab.active_students}</strong>/${capacity}</span>
                                <span style="color: var(--text2); font-weight: 500;">${occupancy}%</span>
                            </div>
                            
                            <!-- Available Count -->
                            <div style="
                                background: ${isOpen ? 'rgba(100, 200, 100, 0.1)' : 'rgba(239, 68, 68, 0.1)'};
                                padding: 6px 8px;
                                border-radius: 6px;
                                font-size: 0.65rem;
                                color: ${isOpen ? 'var(--text3)' : 'var(--red)'};
                                text-align: center;
                                font-weight: 600;
                            ">
                                <i class="fa-solid ${isOpen ? 'fa-laptop' : 'fa-ban'}"></i> ${isOpen ? lab.availability + ' available' : 'UNAVAILABLE'}
                            </div>
                        </div>
                    `;
                }).join('')}
            </div>
        `;
        
        container.innerHTML = gridHTML;
    } catch (err) {
        console.error('Error loading lab status:', err);
        const container = document.getElementById('labStatusContainer');
        if (container) container.innerHTML = '<div style="text-align: center; padding: 20px; color: var(--text3);"><i class="fa-solid fa-exclamation-triangle"></i> Unable to load labs</div>';
    }
}

/* ── AVAILABLE SOFTWARE ── */
function getSoftwareIcon(softwareName) {
    const iconMap = {
        'MS Office 365': { icon: 'fa-file-word', color: '#2D5AA2' },
        'Visual Studio Code': { icon: 'fa-code', color: '#0078D4' },
        'Visual Studio': { icon: 'fa-code', color: '#9146FF' },
        'XAMPP': { icon: 'fa-server', color: '#FB7D1D' },
        'MySQL Workbench': { icon: 'fa-database', color: '#00758F' },
        'NetBeans IDE': { icon: 'fa-pen-nib', color: '#1B6AC6' },
        'IntelliJ IDEA': { icon: 'fa-bolt', color: '#F31B32' },
        'Android Studio': { icon: 'fa-mobile', color: '#3DDC84' },
        'Python': { icon: 'fa-brands fa-python', color: '#3776AB' },
        'Git': { icon: 'fa-code-branch', color: '#F1502F' },
        'Adobe Photoshop': { icon: 'fa-image', color: '#31A8FF' },
        'Figma': { icon: 'fa-paintbrush', color: '#F24E1E' },
        'Cisco Packet Tracer': { icon: 'fa-network-wired', color: '#1BA1E2' },
        'Oracle Virtual Box': { icon: 'fa-box', color: '#183153' },
        'VMware': { icon: 'fa-microchip', color: '#607078' },
        'Notepad++': { icon: 'fa-file-code', color: '#90E59B' }
    };
    
    // Try exact match first
    if (iconMap[softwareName]) {
        return iconMap[softwareName];
    }
    
    // Try partial match
    for (const [key, val] of Object.entries(iconMap)) {
        if (softwareName.includes(key) || key.includes(softwareName)) {
            return val;
        }
    }
    
    return { icon: 'fa-cube', color: '#DB79FF' };
}

async function loadAvailableSoftware() {
    try {
        const response = await fetch('software_list_fetch.php', {
            credentials: 'include',
            headers: {
                'Accept': 'application/json'
            }
        });
        
        if (!response.ok) {
            throw new Error(`HTTP ${response.status}`);
        }
        
        const data = await response.json();
        const container = document.getElementById('softwareListContainer');
        if (!container) return;
        
        if (!data.success || !data.software) {
            container.innerHTML = '<div style="text-align: center; padding: 20px; color: var(--text3);"><i class="fa-solid fa-info-circle"></i> Software data unavailable</div>';
            return;
        }
        
        if (data.software.length === 0) {
            container.innerHTML = '<div style="text-align: center; padding: 20px; color: var(--text3);">No software installed</div>';
            return;
        }
        
        // Create a responsive grid layout
        const gridHTML = `
            <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(160px, 1fr)); gap: 12px; max-height: 400px; overflow-y: auto; padding-right: 4px;">
                ${data.software.map(soft => {
                    const iconData = getSoftwareIcon(soft.name);
                    const labsList = soft.labs.split(', ').map(lab => 
                        `<span style="display: inline-block; background: rgba(219, 121, 255, 0.15); color: var(--violet); padding: 2px 8px; border-radius: 12px; font-size: 0.7rem; font-weight: 500; margin-right: 4px; margin-top: 2px;">${lab}</span>`
                    ).join('');
                    
                    return `
                        <div style="
                            background: linear-gradient(135deg, var(--bg2) 0%, rgba(219, 121, 255, 0.05) 100%);
                            border: 1px solid rgba(219, 121, 255, 0.2);
                            border-radius: 10px;
                            padding: 14px;
                            transition: all 0.3s ease;
                            cursor: pointer;
                            position: relative;
                            overflow: hidden;
                        "
                        onmouseover="this.style.transform='translateY(-4px)'; this.style.borderColor='rgba(219, 121, 255, 0.5)'; this.style.boxShadow='0 8px 16px rgba(219, 121, 255, 0.15)';"
                        onmouseout="this.style.transform='translateY(0)'; this.style.borderColor='rgba(219, 121, 255, 0.2)'; this.style.boxShadow='none';">
                            <!-- Icon Badge -->
                            <div style="
                                width: 40px;
                                height: 40px;
                                background: rgba(${hexToRgb(iconData.color).join(', ')}, 0.1);
                                border-radius: 8px;
                                display: flex;
                                align-items: center;
                                justify-content: center;
                                margin-bottom: 10px;
                            ">
                                <i class="fa-solid ${iconData.icon}" style="color: ${iconData.color}; font-size: 1.2rem;"></i>
                            </div>
                            
                            <!-- Status Badge -->
                            <div style="
                                position: absolute;
                                top: 10px;
                                right: 10px;
                                background: rgba(34, 197, 94, 0.8);
                                color: white;
                                padding: 2px 6px;
                                border-radius: 4px;
                                font-size: 0.65rem;
                                font-weight: 600;
                                display: flex;
                                align-items: center;
                                gap: 2px;
                            ">
                                <i class="fa-solid fa-check" style="font-size: 0.65rem;"></i>
                                INSTALLED
                            </div>
                            
                            <!-- Title -->
                            <div style="
                                font-weight: 600;
                                color: var(--text);
                                font-size: 0.85rem;
                                margin-bottom: 6px;
                                line-height: 1.3;
                            ">${soft.name}</div>
                            
                            <!-- Category -->
                            ${soft.description ? `<div style="color: var(--text3); font-size: 0.7rem; margin-bottom: 8px;">${soft.description}</div>` : ''}
                            
                            <!-- Labs -->
                            <div style="font-size: 0.65rem; color: var(--text3);">
                                <div style="margin-bottom: 4px; font-weight: 500;">Available:</div>
                                <div>${labsList}</div>
                            </div>
                        </div>
                    `;
                }).join('')}
            </div>
        `;
        
        container.innerHTML = gridHTML;
    } catch (err) {
        console.error('Error loading software:', err);
        const container = document.getElementById('softwareListContainer');
        if (container) container.innerHTML = '<div style="text-align: center; padding: 20px; color: var(--text3);"><i class="fa-solid fa-exclamation-triangle"></i> Unable to load software</div>';
    }
}

// Helper function to convert hex color to RGB
function hexToRgb(hex) {
    const result = /^#?([a-f\d]{2})([a-f\d]{2})([a-f\d]{2})$/i.exec(hex);
    return result ? [
        parseInt(result[1], 16),
        parseInt(result[2], 16),
        parseInt(result[3], 16)
    ] : [219, 121, 255];
}

/* ── LOGOUT ── */
function confirmLogout() {
    new bootstrap.Modal(document.getElementById('modalLogout')).show();
}
function doLogout() {
    bootstrap.Modal.getInstance(document.getElementById('modalLogout')).hide();
    showToast('Logging out…', 'fa-right-from-bracket');
    setTimeout(() => { window.location.href = 'logout.php'; }, 1200);
}

/* ── SESSION UI ── */
function updateSessionUI() {
    const pct = ((S.session / S.totalSession) * 100).toFixed(1);
    ['sNum','profSessNum'].forEach(id => {
        const el = document.getElementById(id);
        if (el) el.textContent = S.session;
    });
    const fill = document.getElementById('sessFill');
    if (fill) fill.style.width = pct + '%';
    const rSess  = document.getElementById('rSess');
    if (rSess)  rSess.value = S.session;
    const tipSess = document.getElementById('tipSess');
    if (tipSess) tipSess.textContent = S.session;
}

/* ── PROFILE DISPLAY ── */
function refreshDisplay() {
    const full    = [S.first, S.middle, S.last].filter(Boolean).join(' ');
    const yearNum = S.year.replace(/\D/g, '') || '?';
    setEl('dName',       full);
    setEl('profName',    full);
    setEl('welcomeName', full);
    setEl('dBadge',      `${S.course} · Year ${yearNum}`);
    setEl('profRole',    `${S.course} · ${S.year}`);
    setEl('dId',         S.id);
    setEl('dCourse',     S.course);
    setVal('rIdNumber',  S.id);
    setEl('dYear',       S.year);
    setEl('dEmail',      S.email);
    setEl('dAddr',       S.address);
    setVal('rName',      full);

    // Sync both avatar <img> elements whenever profile is refreshed.
    // S.picSrc is the cache-busted URL; fall back to dicebear if no photo saved.
    const fallback  = `https://api.dicebear.com/8.x/adventurer/svg?seed=${encodeURIComponent(S.id || 'default')}&backgroundColor=b6e3f4`;
    const avatarSrc = S.picSrc || fallback;
    ['mainAvatar', 'profAvatar'].forEach(id => {
        const img = document.getElementById(id);
        if (img && img.src !== avatarSrc) img.src = avatarSrc;
    });
}

function setEl(id, val) { const el = document.getElementById(id); if (el) el.textContent = val; }
function setVal(id, val) { const el = document.getElementById(id); if (el) el.value = val; }

/* ── SAVE PROFILE ──
   POSTs to update_profile.php and syncs S + DOM on success.
   Field names must match what update_profile.php reads ($data['firstname'], etc.)
── */
async function saveProfile() {
    const pw  = document.getElementById('pPw').value;
    const pw2 = document.getElementById('pPw2').value;

    // Client-side password validation before hitting the server
    if (pw && pw !== pw2) {
        showToast('Passwords do not match!', 'fa-circle-xmark', '#ef4444');
        return;
    }
    if (pw && pw.length < 6) {
        showToast('Password must be at least 6 characters.', 'fa-circle-xmark', '#ef4444');
        return;
    }

    // Collect form values
    const payload = {
        firstname:        document.getElementById('pFn').value.trim(),
        lastname:         document.getElementById('pLn').value.trim(),
        middlename:       document.getElementById('pMn').value.trim(),
        email:            document.getElementById('pEm').value.trim(),
        address:          document.getElementById('pAd').value.trim(),
        course:           document.getElementById('pCo').value,
        year_level:       document.getElementById('pYr').value,   // e.g. "3rd Year"
        new_password:     pw,
        confirm_password: pw2,
    };

    // Required-field check (mirrors server-side validation for faster feedback)
    if (!payload.firstname || !payload.lastname || !payload.email || !payload.course || !payload.year_level) {
        showToast('Please fill in all required fields.', 'fa-circle-xmark', '#ef4444');
        return;
    }

    // Disable the button while request is in flight
    const btn = document.querySelector('[onclick="saveProfile()"]');
    if (btn) { btn.disabled = true; btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Saving…'; }

    try {
        const res  = await fetch('update_profile.php', {
            method:  'POST',
            headers: { 'Content-Type': 'application/json' },
            credentials: 'include',
            body:    JSON.stringify(payload),
        });
        const data = await res.json();

        if (data.success) {
            // Update the in-memory S object so refreshDisplay() shows new values immediately
            S.first   = data.firstname  || payload.firstname;
            S.last    = data.lastname   || payload.lastname;
            S.middle  = payload.middlename;
            S.email   = payload.email;
            S.address = payload.address;
            S.course  = data.course     || payload.course;
            S.year    = data.year_level || payload.year_level;

            // Clear password fields after successful save
            document.getElementById('pPw').value  = '';
            document.getElementById('pPw2').value = '';

            refreshDisplay();
            showToast('Profile updated successfully!', 'fa-circle-check', '#16a34a');
        } else {
            showToast(data.message || 'Update failed. Please try again.', 'fa-circle-xmark', '#ef4444');
        }
    } catch (err) {
        // Network or JSON parse error
        showToast('Network error — check server connection.', 'fa-circle-xmark', '#ef4444');
        console.error('saveProfile error:', err);
    } finally {
        // Always re-enable the button
        if (btn) { btn.disabled = false; btn.innerHTML = '<i class="fa-solid fa-floppy-disk"></i> Save Changes'; }
    }
}

function triggerPhotoInput() {
    // Reset input so the same file can be re-selected after an error
    const input = document.getElementById('photoInput');
    if (input) { input.value = ''; input.click(); }
}

/* ── PREVIEW & UPLOAD PHOTO ──
   1. Validates file type/size client-side (fast feedback, no round-trip).
   2. Shows an immediate local preview (good UX — user sees change instantly).
   3. POSTs the file to upload_photo.php via FormData.
   4. On success → updates S.pic / S.picSrc so all displays stay in sync.
   5. On failure → reverts the preview to the previous avatar.
── */
async function previewPhoto(ev) {
    const file = ev.target.files[0];
    if (!file) return;

    /* ── Client-side validation ── */
    const allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    if (!allowedTypes.includes(file.type)) {
        showToast('Only JPG, PNG, GIF, or WEBP images allowed.', 'fa-circle-xmark', '#ef4444');
        return;
    }
    const maxBytes = 2 * 1024 * 1024; // 2 MB — matches server limit
    if (file.size > maxBytes) {
        showToast('Image must be under 2 MB.', 'fa-circle-xmark', '#ef4444');
        return;
    }

    // Keep a snapshot of the old src so we can revert on failure
    const prevSrc = S.picSrc || document.getElementById('profAvatar')?.src || '';

    /* ── Immediate local preview (DataURL) ── */
    const reader = new FileReader();
    reader.onload = async (e) => {
        const localSrc = e.target.result;

        // Show preview in both avatars right away
        ['mainAvatar', 'profAvatar'].forEach(id => {
            const img = document.getElementById(id);
            if (img) img.src = localSrc;
        });

        /* ── Show loading overlay on the profile avatar ── */
        const overlay = document.getElementById('avatarOverlay');
        const photoBtn = document.querySelector('.btn-photo');
        if (overlay) overlay.style.display = 'flex';
        if (photoBtn) { photoBtn.disabled = true; photoBtn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Uploading…'; }

        /* ── POST to server ── */
        try {
            const formData = new FormData();
            formData.append('profile_photo', file);   // key expected by upload_photo.php

            const res  = await fetch('upload_photo.php', { method: 'POST', body: formData, credentials: 'include' });
            const data = await res.json();

            if (data.success) {
                // Update S so all future refreshDisplay() calls use the new photo
                S.pic    = data.raw  || S.pic;       // raw path for storage
                S.picSrc = data.path || localSrc;    // cache-busted src for <img>

                // Final src swap to the server path (kicks out the DataURL blob)
                ['mainAvatar', 'profAvatar'].forEach(id => {
                    const img = document.getElementById(id);
                    if (img) img.src = S.picSrc;
                });

                showToast('Profile photo saved!', 'fa-circle-check', '#16a34a');
            } else {
                // Revert to previous avatar on server-side failure
                ['mainAvatar', 'profAvatar'].forEach(id => {
                    const img = document.getElementById(id);
                    if (img) img.src = prevSrc;
                });
                showToast(data.message || 'Upload failed. Please try again.', 'fa-circle-xmark', '#ef4444');
            }
        } catch (err) {
            // Network or JSON parse error — revert avatar
            ['mainAvatar', 'profAvatar'].forEach(id => {
                const img = document.getElementById(id);
                if (img) img.src = prevSrc;
            });
            showToast('Network error during upload.', 'fa-circle-xmark', '#ef4444');
            console.error('previewPhoto upload error:', err);
        } finally {
            // Always hide the loading overlay and re-enable the button
            if (overlay) overlay.style.display = 'none';
            if (photoBtn) { photoBtn.disabled = false; photoBtn.innerHTML = '<i class="fa-solid fa-camera"></i> Change Photo'; }
        }
    };

    reader.readAsDataURL(file);
}

/* ── RESERVATION ── */

// Global variable to track computer availability
let computerAvailability = {};

// Check computer availability when lab or date changes
async function updateComputerStatus() {
    const labId = document.getElementById('rLab').value;
    const date = document.getElementById('rDate').value;
    const computerNumber = document.getElementById('rComputer').value;
    const statusDiv = document.getElementById('computerStatus');

    if (!labId || !date || !computerNumber) {
        statusDiv.style.display = 'none';
        return;
    }

    try {
        const response = await fetch('check_computer_availability.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            credentials: 'include',
            body: JSON.stringify({ lab_id: labId, date: date })
        });

        const data = await response.json();
        if (data.success) {
            computerAvailability = data.computers;
            
            // Get selected computer status
            const selectedComputer = parseInt(computerNumber);
            const status = computerAvailability[selectedComputer] || 'available';

            statusDiv.style.display = 'block';
            
            if (status === 'unavailable') {
                statusDiv.className = 'alert alert-secondary';
                statusDiv.innerHTML = '<i class="fa-solid fa-circle"></i> Computer ' + selectedComputer + ' is UNAVAILABLE';
                statusDiv.style.backgroundColor = 'rgba(156, 163, 175, 0.12)';
                statusDiv.style.borderColor = 'rgba(156, 163, 175, 0.3)';
                statusDiv.style.color = '#d1d5db';
            } else if (status === 'occupied') {
                statusDiv.className = 'alert alert-danger';
                statusDiv.innerHTML = '<i class="fa-solid fa-circle"></i> Computer ' + selectedComputer + ' is CURRENTLY OCCUPIED';
                statusDiv.style.backgroundColor = 'rgba(255, 77, 106, 0.12)';
                statusDiv.style.borderColor = 'rgba(255, 77, 106, 0.3)';
                statusDiv.style.color = '#ffccd5';
            } else if (status === 'reserved') {
                statusDiv.className = 'alert alert-warning';
                statusDiv.innerHTML = '<i class="fa-solid fa-circle"></i> Computer ' + selectedComputer + ' is RESERVED for this date';
                statusDiv.style.backgroundColor = 'rgba(255, 179, 71, 0.12)';
                statusDiv.style.borderColor = 'rgba(255, 179, 71, 0.3)';
                statusDiv.style.color = '#ffe0b2';
            } else {
                statusDiv.className = 'alert alert-success';
                statusDiv.innerHTML = '<i class="fa-solid fa-circle-check"></i> Computer ' + selectedComputer + ' is available';
                statusDiv.style.backgroundColor = 'rgba(0, 229, 160, 0.12)';
                statusDiv.style.borderColor = 'rgba(0, 229, 160, 0.3)';
                statusDiv.style.color = '#b2f5d9';
            }
        }
    } catch (err) {
        console.error('Error checking computer availability:', err);
    }
}



async function submitReservation() {
    // Check if student has an active sit-in session (case-insensitive)
    const hasActiveSession = historyData && historyData.length > 0 && 
        historyData.some(r => r && r.status && r.status.toLowerCase() === 'active');
    if (hasActiveSession) {
        showToast('You cannot make a new reservation while you are currently sitting in. Please complete your current session first.', 'fa-circle-xmark', '#ef4444');
        return;
    }

    const purpose        = document.getElementById('rPurpose').value.trim();
    const labId          = document.getElementById('rLab').value;
    const computerNumber = document.getElementById('rComputer').value;
    const date           = document.getElementById('rDate').value;
    const time           = document.getElementById('rTime').value;
    if (!purpose) { showToast('Please enter a purpose',      'fa-circle-xmark', '#ef4444'); return; }
    if (!labId)   { showToast('Please select a laboratory',  'fa-circle-xmark', '#ef4444'); return; }
    if (!computerNumber) { showToast('Please select a computer number', 'fa-circle-xmark', '#ef4444'); return; }
    if (!date)    { showToast('Please pick a date',          'fa-circle-xmark', '#ef4444'); return; }
    if (!time)    { showToast('Please enter a time',         'fa-circle-xmark', '#ef4444'); return; }

    // Validate computer availability one more time before submission
    const selectedComputer = parseInt(computerNumber);
    
    // If we have cached availability data, check it
    if (Object.keys(computerAvailability).length > 0) {
        const computerStatus = computerAvailability[selectedComputer] || 'available';
        
        if (computerStatus === 'unavailable') {
            showToast('Computer ' + selectedComputer + ' is unavailable (disabled by admin). Please pick a different PC.', 'fa-circle-xmark', '#ef4444');
            return;
        }
        
        if (computerStatus === 'occupied') {
            showToast('Computer ' + selectedComputer + ' is occupied. Please pick a different PC.', 'fa-circle-xmark', '#ef4444');
            return;
        }
        
        if (computerStatus === 'reserved') {
            showToast('Computer ' + selectedComputer + ' is already reserved for this date. Please pick a different PC.', 'fa-circle-xmark', '#ef4444');
            return;
        }
    } else {
        // If no cached data, fetch fresh availability
        try {
            const response = await fetch('check_computer_availability.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                credentials: 'include',
                body: JSON.stringify({ lab_id: labId, date: date })
            });

            const availData = await response.json();
            if (availData.success) {
                computerAvailability = availData.computers;
                const computerStatus = computerAvailability[selectedComputer] || 'available';
                
                if (computerStatus === 'unavailable') {
                    showToast('Computer ' + selectedComputer + ' is unavailable (disabled by admin). Please pick a different PC.', 'fa-circle-xmark', '#ef4444');
                    return;
                }
                
                if (computerStatus === 'occupied') {
                    showToast('Computer ' + selectedComputer + ' is occupied. Please pick a different PC.', 'fa-circle-xmark', '#ef4444');
                    return;
                }
                
                if (computerStatus === 'reserved') {
                    showToast('Computer ' + selectedComputer + ' is already reserved for this date. Please pick a different PC.', 'fa-circle-xmark', '#ef4444');
                    return;
                }
            }
        } catch (err) {
            console.error('Error verifying computer availability:', err);
            showToast('Error verifying computer availability. Please try again.', 'fa-circle-xmark', '#ef4444');
            return;
        }
    }

    const data = await postJSON('submit_reservation.php', {
        lab_id: labId,
        computer_number: computerNumber,
        purpose,
        date,
        time
    });
    if (!data) {
        showToast('Network error submitting request.', 'fa-circle-xmark', '#ef4444');
        return;
    }
    if (!data.success) {
        showToast(data.message || 'Reservation failed.', 'fa-circle-xmark', '#ef4444');
        return;
    }

    document.getElementById('rPurpose').value = '';
    document.getElementById('rLab').value     = '';
    document.getElementById('rComputer').value = '';
    document.getElementById('rDate').value    = todayStr();
    document.getElementById('rTime').value    = '';
    document.getElementById('computerStatus').style.display = 'none';
    computerAvailability = {};
    await loadMyReservations();
    await loadNotifications();
    showToast('Reservation submitted! Waiting for admin approval.', 'fa-circle-check', '#00e5a0');
}

async function loadMyReservations() {
    const data = await postJSON('reservation_list.php', {});
    if (!data || !data.success) {
        if (data && data.message) showToast(data.message, 'fa-circle-xmark', '#ef4444');
        return;
    }
    reservations = data.reservations || [];
    renderMyReservations();
}

function renderMyReservations() {
    const el = document.getElementById('myResList');
    if (!el) return;
    if (!reservations.length) {
        el.innerHTML = '<p class="text-center" style="font-size:.82rem;color:var(--text3);font-style:italic;padding:8px 0">No reservations yet.</p>';
        return;
    }
    el.innerHTML = reservations.map((r) => {
        const status = r.status.charAt(0).toUpperCase() + r.status.slice(1);
        const canCancel = r.status === 'pending';
        const computer = r.computer_number ? `Computer ${r.computer_number}` : 'Computer TBD';
        return `
        <div class="res-row">
          <div>
            <div class="res-row-lab"><i class="fa-solid fa-computer me-1"></i>${r.lab_name}</div>
            <div class="res-row-meta">${computer} · ${r.purpose} · ${fmtDate(r.reserved_date)} · ${fmtTime(r.time_start)}</div>
            <div class="res-row-status">Status: <strong>${status}</strong></div>
          </div>
          <button class="btn-ccs-outline btn-ccs-danger" ${canCancel ? '' : 'disabled'} onclick="askCancelReservation(${r.id})">
            <i class="fa-solid fa-xmark"></i> Cancel
          </button>
        </div>`;
    }).join('');
}

function askCancelReservation(resId) {
    pendingDelIdx = resId;
    new bootstrap.Modal(document.getElementById('modalCancelRes')).show();
}

async function doDeleteReservation() {
    if (pendingDelIdx === null) return;
    const data = await postJSON('reservation_cancel.php', { id: pendingDelIdx });
    bootstrap.Modal.getInstance(document.getElementById('modalCancelRes')).hide();
    pendingDelIdx = null;
    if (!data) {
        showToast('Network error cancelling reservation.', 'fa-circle-xmark', '#ef4444');
        return;
    }
    if (!data.success) {
        showToast(data.message || 'Unable to cancel reservation.', 'fa-circle-xmark', '#ef4444');
        return;
    }
    await loadMyReservations();
    showToast(data.message || 'Reservation cancelled', 'fa-circle-check', '#00c896');
}

/* ── SUMMARY ── */
function loadSummaryData() {
    try {
        const totalHoursEl = document.getElementById('totalHours');
        const totalSessionsEl = document.getElementById('totalSessions');
        const avgDurationEl = document.getElementById('avgDuration');
        const longestSessionEl = document.getElementById('longestSession');
        
        if (!totalHoursEl || !totalSessionsEl || !avgDurationEl || !longestSessionEl) {
            return;
        }

        if (!historyData || !historyData.length) {
            totalHoursEl.textContent = '0.0h';
            totalSessionsEl.textContent = '0';
            avgDurationEl.textContent = '0';
            longestSessionEl.textContent = '0m';
            return;
        }

        // Calculate statistics
        let totalMinutes = 0;
        let completedCount = 0;
        let longestMinutes = 0;

        historyData.forEach(record => {
            try {
                if (record.status === 'completed') {
                    // Use time_in/time_out if available, otherwise login/logout
                    const timeInStr = record.time_in || record.login;
                    const timeOutStr = record.time_out || record.logout;
                    
                    if (!timeInStr || timeInStr === '—') return;
                    
                    const timeIn = new Date(timeInStr);
                    const timeOut = (timeOutStr && timeOutStr !== '—') ? new Date(timeOutStr) : new Date();
                    
                    if (isNaN(timeIn.getTime())) return;
                    
                    const durationMs = timeOut - timeIn;
                    const durationMin = Math.max(0, Math.floor(durationMs / 60000));
                    
                    totalMinutes += durationMin;
                    completedCount++;
                    longestMinutes = Math.max(longestMinutes, durationMin);
                }
            } catch (e) {
                console.warn('Error processing record:', record, e);
            }
        });

        // Format and display
        const hours = (totalMinutes / 60).toFixed(1);
        const avgMin = completedCount > 0 ? Math.floor(totalMinutes / completedCount) : 0;

        totalHoursEl.textContent = hours + 'h';
        totalSessionsEl.textContent = completedCount;
        avgDurationEl.textContent = avgMin + 'm';
        longestSessionEl.textContent = longestMinutes + 'm';
    } catch (e) {
        console.error('Error loading summary data:', e);
    }
}

function renderSummaryHistory() {
    try {
        const tbody = document.getElementById('summBody');
        const summInfo = document.getElementById('summInfo');
        const summPagination = document.getElementById('summPagination');
        
        if (!tbody || !summInfo || !summPagination) return;
        
        const q = (document.getElementById('summSearch')?.value || '').toLowerCase();
        const pp = parseInt(document.getElementById('summEntries')?.value || 10);
        let summPage = parseInt(tbody.dataset.page || 1);
        
        if (!historyData || !historyData.length) {
            tbody.innerHTML = `<tr class="no-data-row"><td colspan="9" style="text-align: center; padding: 2.5rem 1rem;">
                <i class="fa-regular fa-folder-open" style="font-size:2.2rem;display:block;margin-bottom:0.8rem;opacity:.25;color:var(--text3)"></i>
                <p style="color: var(--text3); font-style: italic; margin: 0; font-size: 0.95rem;">No sessions yet. Start a sit-in to see your history here.</p>
            </td></tr>`;
            summInfo.textContent = 'Showing 0 entries';
            summPagination.innerHTML = '';
            return;
        }
        
        const data = historyData.filter(r => {
            const searchStr = `${r.purpose || ''} ${r.lab_name || ''} ${r.date || ''}`.toLowerCase();
            return searchStr.includes(q);
        });
        
        const total = data.length;
        const pages = Math.max(1, Math.ceil(total / pp));
        if (summPage > pages) summPage = pages;
        if (summPage < 1) summPage = 1;
        
        const start = (summPage - 1) * pp;
        const slice = data.slice(start, start + pp);
        
        if (!total) {
            tbody.innerHTML = `<tr class="no-data-row"><td colspan="9" style="text-align: center; padding: 2rem;">
                <i class="fa-solid fa-magnifying-glass" style="font-size:1.8rem;display:block;margin-bottom:0.8rem;opacity:.25;color:var(--text3)"></i>
                <p style="color: var(--text3); font-style: italic; margin: 0; font-size: 0.95rem;">No sessions match your search.</p>
            </td></tr>`;
        } else {
            tbody.innerHTML = slice.map(r => {
                try {
                    const timeInStr = r.time_in || r.login;
                    const timeOutStr = r.time_out || r.logout;
                    const isActive = r.status && r.status.toLowerCase() === 'active';
                    
                    let durationStr = '0m';
                    if (timeInStr && timeInStr !== '—') {
                        durationStr = calculateDuration(timeInStr, isActive ? null : timeOutStr);
                    }
                    
                    const statusBadge = r.status === 'completed' ? 'completed' : r.status === 'active' ? 'active' : 'cancelled';
                    const statusLabel = r.status === 'completed' ? 'Completed' : r.status === 'active' ? 'Active' : 'Cancelled';
                    const statusIcon = r.status === 'completed' ? 'fa-circle-check' : r.status === 'active' ? 'fa-clock' : 'fa-circle-xmark';
                    
                    return `<tr data-session-id="${r.sit_id}" data-session-status="${r.status || 'unknown'}" data-time-in="${timeInStr}">
                        <td><code>${r.date || 'N/A'}</code></td>
                        <td>${r.login || 'N/A'}</td>
                        <td>${(r.logout && r.logout !== '—') ? r.logout : '—'}</td>
                        <td class="session-duration">${durationStr.includes('<strong>') ? durationStr : `<strong>${durationStr}</strong>`}</td>
                        <td><strong>${r.computer_number || 'N/A'}</strong></td>
                        <td><small>${r.lab_name || 'N/A'}</small></td>
                        <td><em>${r.purpose || 'N/A'}</em></td>
                        <td><span class="badge-${statusBadge}"><i class="fa-solid ${statusIcon}"></i> ${statusLabel}</span></td>
                        <td>—</td>
                    </tr>`;
                } catch (e) {
                    console.warn('Error rendering row:', r, e);
                    return '';
                }
            }).join('');
        }

        summInfo.textContent = total
            ? `Showing ${start + 1}–${Math.min(start + pp, total)} of ${total} session${total === 1 ? '' : 's'}`
            : 'Showing 0 entries';

        // Render pagination
        if (pages > 1) {
            let pgHtml = `<button class="ccs-pgbtn" onclick="goSummaryPage(1)" title="First">«</button>
                          <button class="ccs-pgbtn" onclick="goSummaryPage(${Math.max(1, summPage - 1)})" title="Previous">‹</button>`;
            for (let i = 1; i <= pages; i++)
                pgHtml += `<button class="ccs-pgbtn${i === summPage ? ' active' : ''}" onclick="goSummaryPage(${i})">${i}</button>`;
            pgHtml += `<button class="ccs-pgbtn" onclick="goSummaryPage(${Math.min(pages, summPage + 1)})" title="Next">›</button>
                       <button class="ccs-pgbtn" onclick="goSummaryPage(${pages})" title="Last">»</button>`;
            summPagination.innerHTML = pgHtml;
        } else {
            summPagination.innerHTML = '';
        }
        
        // Store current page
        tbody.dataset.page = summPage;
    } catch (e) {
        console.error('Error rendering summary history:', e);
    }
}

function goSummaryPage(p) {
    const pp = parseInt(document.getElementById('summEntries')?.value || 10);
    const pages = Math.max(1, Math.ceil(historyData.length / pp));
    const summPage = Math.min(Math.max(1, p), pages);
    document.getElementById('summBody').dataset.page = summPage;
    renderSummaryHistory();
}

function exportSummaryPDF() {
    if (!historyData.length) {
        showToast('No data to export', 'fa-circle-xmark', '#ef4444');
        return;
    }

    // Get summary values with null checks
    const totalHoursEl = document.getElementById('totalHours');
    const totalSessionsEl = document.getElementById('totalSessions');
    const avgDurationEl = document.getElementById('avgDuration');
    const longestSessionEl = document.getElementById('longestSession');
    
    const totalHours = totalHoursEl ? totalHoursEl.textContent : '0h';
    const totalSessions = totalSessionsEl ? totalSessionsEl.textContent : '0';
    const avgDuration = avgDurationEl ? avgDurationEl.textContent : '0m';
    const longestSession = longestSessionEl ? longestSessionEl.textContent : '0m';

    const now = new Date();
    const dateStr = now.toLocaleDateString('en-US', { year: 'numeric', month: 'long', day: 'numeric' });
    const timeStr = now.toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit' });

    let html = `
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset="UTF-8">
        <style>
            @page {
                size: A4;
                margin: 0.5in;
            }
            * { margin: 0; padding: 0; box-sizing: border-box; }
            body {
                font-family: 'Calibri', 'Arial', sans-serif;
                padding: 40px;
                color: #1a1a1a;
                line-height: 1.6;
                print-color-adjust: exact;
                -webkit-print-color-adjust: exact;
                -moz-print-color-adjust: exact;
            }
            .header {
                text-align: center;
                margin-bottom: 30px;
                border-bottom: 3px solid #003366;
                padding-bottom: 20px;
            }
            .report-title {
                font-size: 28px;
                font-weight: bold;
                color: #003366;
                letter-spacing: 1px;
                margin-bottom: 8px;
            }
            .report-subtitle {
                font-size: 13px;
                color: #555;
                margin-bottom: 15px;
            }
            .report-info {
                font-size: 12px;
                color: #666;
            }
            .student-info-box {
                background-color: #d1eeff;
                border-left: 5px solid #1363b8;
                padding: 15px;
                margin-bottom: 25px;
                display: flex;
                justify-content: space-between;
                align-items: center;
            }
            .student-name {
                font-size: 16px;
                font-weight: bold;
                color: #1a1a1a;
            }
            .student-id {
                font-size: 12px;
                color: #666;
            }
            .stats-section {
                display: flex;
                justify-content: space-between;
                gap: 15px;
                margin-bottom: 25px;
            }
            .stat-box {
                flex: 1;
                border: 1px solid #ddd;
                padding: 15px;
                text-align: center;
                border-radius: 4px;
                background-color: #f9f9f9;
            }
            .stat-value {
                font-size: 24px;
                font-weight: bold;
                color: #003366;
                margin-bottom: 5px;
            }
            .stat-label {
                font-size: 11px;
                color: #666;
                text-transform: uppercase;
                letter-spacing: 0.5px;
            }
            table {
                width: 100%;
                border-collapse: collapse;
                margin-top: 20px;
                font-size: 12px;
            }
            th {
                background-color: #003366 !important;
                color: white !important;
                padding: 12px 8px;
                text-align: left;
                font-weight: bold;
                border: 1px solid #003366;
                print-color-adjust: exact;
                -webkit-print-color-adjust: exact;
                -moz-print-color-adjust: exact;
            }
            td {
                padding: 10px 8px;
                border: 1px solid #ddd;
                text-align: left;
            }
            tr:nth-child(even) {
                background-color: #f9f9f9;
            }
            .footer {
                margin-top: 30px;
                padding-top: 15px;
                border-top: 1px solid #ccc;
                text-align: center;
                font-size: 10px;
                color: #888;
            }
            @media print {
                body { padding: 20px; }
                table { font-size: 11px; }
            }
        </style>
    </head>
    <body>
        <div class="header">
            <div class="report-title">MY SIT-IN SUMMARY</div>
            <div class="report-subtitle">College of Computer Studies — University of Cebu</div>
            <div class="report-info">Generated: ${dateStr} at ${timeStr}</div>
        </div>

        <div class="student-info-box">
            <div>
                <div class="student-name">${window.__SESSION__.first} ${window.__SESSION__.last}</div>
                <div class="student-id">ID: ${window.__SESSION__.id}</div>
            </div>
        </div>

        <div class="stats-section">
            <div class="stat-box">
                <div class="stat-value">${totalHours}</div>
                <div class="stat-label">Total Sit-in Hours</div>
            </div>
            <div class="stat-box">
                <div class="stat-value">${totalSessions}</div>
                <div class="stat-label">Total Sessions</div>
            </div>
            <div class="stat-box">
                <div class="stat-value">${avgDuration}</div>
                <div class="stat-label">Avg Session</div>
            </div>
            <div class="stat-box">
                <div class="stat-value">${longestSession}</div>
                <div class="stat-label">Longest Session</div>
            </div>
        </div>

        <table>
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Time In</th>
                    <th>Time Out</th>
                    <th>Duration</th>
                    <th>Lab</th>
                    <th>PC #</th>
                    <th>Purpose</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
    `;

    historyData.forEach(r => {
        const timeIn = new Date(r.time_in);
        const timeOut = r.time_out ? new Date(r.time_out) : new Date();
        const durationMs = timeOut - timeIn;
        const durationMin = Math.floor(durationMs / 60000);
        const hours = Math.floor(durationMin / 60);
        const mins = durationMin % 60;
        const durationStr = hours > 0 ? `${hours}h ${mins}m` : `${mins}m`;
        const statusLabel = r.status === 'completed' ? 'Done' : r.status === 'active' ? 'Active' : 'Cancelled';

        html += `
            <tr>
                <td>${r.date || 'N/A'}</td>
                <td>${r.login || 'N/A'}</td>
                <td>${r.logout && r.logout !== '—' ? r.logout : 'N/A'}</td>
                <td>${durationStr}</td>
                <td>${r.lab_name || 'N/A'}</td>
                <td>${r.computer_number || '—'}</td>
                <td>${r.purpose || 'N/A'}</td>
                <td>${statusLabel}</td>
            </tr>
        `;
    });

    html += `
            </tbody>
        </table>
        <div class="footer">
            <p>CCS Sit-in Monitoring System | UC — College of Computer Studies</p>
            <p>Printed: ${dateStr} at ${timeStr}</p>
        </div>
    </body>
    </html>
    `;

    const printWindow = window.open('', '', 'width=800,height=600');
    printWindow.document.write(html);
    printWindow.document.close();
    setTimeout(() => printWindow.print(), 250);
}

/* ── HISTORY ── */
function renderHistory() {
    const q   = (document.getElementById('histSearch')?.value || '').toLowerCase();
    const pp  = parseInt(document.getElementById('histEntries')?.value || 10);
    const data = historyData.filter(r => Object.values(r).join(' ').toLowerCase().includes(q));
    const total = data.length;
    const pages = Math.max(1, Math.ceil(total / pp));
    if (histPage > pages) histPage = pages;
    const slice = data.slice((histPage - 1) * pp, histPage * pp);
    const tbody = document.getElementById('histBody');
    if (!tbody) return;
    if (!total) {
        tbody.innerHTML = `<tr class="no-data-row"><td colspan="9">
            <i class="fa-regular fa-folder-open" style="font-size:1.4rem;display:block;margin-bottom:8px;opacity:.35"></i>
            No history records yet</td></tr>`;
    } else {
        tbody.innerHTML = slice.map(r => {
            const feedbackLabel = r.satisfaction ?
                `<span class="chip ${r.satisfaction === 'satisfied' ? 'chip-green' : 'chip-red'}">${r.satisfaction === 'satisfied' ? 'Satisfied' : 'Unsatisfied'}</span>` :
                '<span class="chip chip-gray">No feedback</span>';
            const action = r.status === 'completed'
                ? `<button class="btn-ccs-outline" onclick="openFeedbackModal(${r.sit_id})">${r.feedback ? 'Edit Feedback' : 'Give Feedback'}</button>`
                : `<button class="btn-ccs-outline" disabled>${r.status === 'active' ? 'In Progress' : 'Closed'}</button>`;
            return `
            <tr>
              <td>${r.id_number}</td><td>${r.name}</td>
              <td><span class="chip chip-blue">${r.purpose}</span></td>
              <td>${r.lab_name}</td><td>${r.login}</td><td>${r.logout}</td><td>${r.date}</td>
              <td>${feedbackLabel}</td>
              <td>${action}</td>
            </tr>`;
        }).join('');
    }
    document.getElementById('histInfo').textContent = total
        ? `Showing ${(histPage-1)*pp+1}–${Math.min(histPage*pp,total)} of ${total} entr${total===1?'y':'ies'}`
        : 'Showing 0 entries';
    let pgHtml = `<button class="ccs-pgbtn" onclick="goPage(1)">«</button>
                  <button class="ccs-pgbtn" onclick="goPage(${histPage-1})">‹</button>`;
    for (let i = 1; i <= pages; i++)
        pgHtml += `<button class="ccs-pgbtn${i===histPage?' active':''}" onclick="goPage(${i})">${i}</button>`;
    pgHtml += `<button class="ccs-pgbtn" onclick="goPage(${histPage+1})">›</button>
               <button class="ccs-pgbtn" onclick="goPage(${pages})">»</button>`;
    document.getElementById('histPagination').innerHTML = pgHtml;
}

function goPage(p) {
    const pp    = parseInt(document.getElementById('histEntries')?.value || 10);
    const pages = Math.max(1, Math.ceil(historyData.length / pp));
    histPage    = Math.min(Math.max(1, p), pages);
    renderHistory();
}

function openFeedbackModal(sitId) {
    const record = historyData.find(r => r.sit_id === sitId);
    if (!record) return;
    feedbackSitId = sitId;
    document.getElementById('feedbackSitId').value = sitId;
    document.getElementById('feedbackSessionInfo').textContent = `${record.lab_name} · ${record.purpose} · ${record.date}`;
    document.getElementById('feedbackText').value = record.feedback || '';
    document.getElementById('feedbackSatisfied').checked = record.satisfaction === 'satisfied';
    document.getElementById('feedbackUnsatisfied').checked = record.satisfaction === 'unsatisfied';
    new bootstrap.Modal(document.getElementById('modalFeedback')).show();
}

async function submitFeedback() {
    const sitId = parseInt(document.getElementById('feedbackSitId')?.value || 0, 10);
    const rating = document.querySelector('input[name="feedbackRating"]:checked')?.value;
    const comment = document.getElementById('feedbackText')?.value.trim();
    if (!sitId || !rating) {
        showToast('Please select whether you were satisfied or not.', 'fa-circle-xmark', '#ef4444');
        return;
    }

    const data = await postJSON('submit_feedback.php', {
        sit_id: sitId,
        satisfaction: rating,
        feedback: comment
    });
    if (!data) {
        showToast('Network error saving feedback.', 'fa-circle-xmark', '#ef4444');
        return;
    }
    if (!data.success) {
        showToast(data.message || 'Unable to save feedback.', 'fa-circle-xmark', '#ef4444');
        return;
    }
    showToast(data.message || 'Feedback saved.', 'fa-circle-check', '#00c896');
    setTimeout(() => {
        new bootstrap.Modal(document.getElementById('modalFeedback')).hide();
    }, 1000); // Delay to allow fade animation
    await loadHistoryData();
    renderHistory();
    await loadNotifications();
}

// Helper function to calculate and format duration
function calculateDuration(timeInStr, timeOutStr = null) {
    try {
        if (!timeInStr || timeInStr === '—') return '0m';
        const timeIn = new Date(timeInStr);
        const timeOut = (timeOutStr && timeOutStr !== '—') ? new Date(timeOutStr) : new Date();
        
        if (!isNaN(timeIn.getTime())) {
            const durationMs = timeOut - timeIn;
            const durationMin = Math.max(0, Math.floor(durationMs / 60000));
            const hours = Math.floor(durationMin / 60);
            const mins = durationMin % 60;
            return hours > 0 ? `${hours}h ${mins}m` : `${mins}m`;
        }
    } catch (e) {
        console.warn('Error calculating duration:', e);
    }
    return '0m';
}

// Update real-time durations for active sessions
function updateActiveSessions() {
    // Update summary history active sessions
    const summaryRows = document.querySelectorAll('tbody tr[data-session-id][data-session-status="active"]');
    summaryRows.forEach(row => {
        const timeInStr = row.getAttribute('data-time-in');
        const durationCell = row.querySelector('.session-duration');
        if (durationCell && timeInStr) {
            durationCell.innerHTML = `<strong>${calculateDuration(timeInStr)}</strong>`;
        }
    });
}

// Start real-time updates for active sessions
let activeSessionInterval = null;
function startActiveSessionUpdates() {
    if (!activeSessionInterval) {
        activeSessionInterval = setInterval(updateActiveSessions, 1000); // Update every second
    }
}
function stopActiveSessionUpdates() {
    if (activeSessionInterval) {
        clearInterval(activeSessionInterval);
        activeSessionInterval = null;
    }
}

async function loadHistoryData() {
    const data = await postJSON('history_fetch.php', {});
    if (!data || !data.success) {
        console.warn('Failed to load history:', data?.message);
        historyData = []; // Reset to empty array
        updateStatusStrip();
        return;
    }
    
    historyData = (data.history || []).map(r => ({
        ...r,
        canFeedback: r.status && r.status.toLowerCase() === 'completed'
    }));
    
    // Debug log to verify data is being loaded
    console.log('History data loaded:', historyData.length, 'records');
    if (historyData.length > 0) {
        console.log('First record status:', historyData[0].status);
        console.log('Active sessions:', historyData.filter(r => r.status && r.status.toLowerCase() === 'active').length);
    }
    
    updateStatusStrip(); // Update status strip when history data loads
    updateSummaryAndSessionTable();
    
    // Also update the My Summary view if it exists
    if (document.getElementById('view-summary')) {
        loadSummaryData();
        if (document.getElementById('view-summary').classList.contains('active')) {
            renderSummaryHistory();
        }
    }
}

function exportCSV() {
    if (!historyData.length) { showToast('No data to export', 'fa-circle-xmark', '#ef4444'); return; }
    const headers = ['ID Number','Name','Purpose','Lab','Login','Logout','Date','Rating','Feedback'];
    const rows    = historyData.map(r => [r.id_number,r.name,r.purpose,r.lab_name,r.login,r.logout,r.date,r.satisfaction||'','"'+(r.feedback||'')+'"']);
    const csv     = [headers,...rows].map(r => r.join(',')).join('\n');
    const a       = document.createElement('a');
    a.href        = 'data:text/csv;charset=utf-8,' + encodeURIComponent(csv);
    a.download    = 'sit-in-history.csv';
    a.click();
    showToast('History exported as CSV');
}

function getSessionDuration(record) {
    if (!record.time_in || !record.time_out) return 0;
    const start = new Date(record.time_in);
    const end   = new Date(record.time_out);
    if (Number.isNaN(start.getTime()) || Number.isNaN(end.getTime()) || end <= start) return 0;
    return (end - start) / 3600000;
}

function formatDuration(hours) {
    return hours > 0 ? `${hours.toFixed(2)} hrs` : '—';
}

function updateSummaryAndSessionTable() {
    renderSessionSummary();
    renderSessionTable();
}

function renderSessionSummary() {
    const totalSessions = historyData.length;
    const durations = historyData.map(getSessionDuration);
    const totalHours = durations.reduce((sum, value) => sum + value, 0);
    const average = totalSessions ? totalHours / totalSessions : 0;
    const longest = durations.length ? Math.max(...durations) : 0;
    setEl('summaryHours', totalHours.toFixed(2));
    setEl('summarySessions', totalSessions);
    setEl('summaryAvg', average.toFixed(2));
    setEl('summaryLongest', longest.toFixed(2));
}

function renderSessionTable() {
    const body = document.getElementById('sessionTableBody');
    if (!body) return;
    if (!historyData.length) {
        body.innerHTML = `<tr class="no-data-row"><td colspan="6">
            <i class="fa-regular fa-folder-open" style="font-size:1.3rem;display:block;margin-bottom:8px;opacity:.35"></i>
            No session records yet</td></tr>`;
        return;
    }
    body.innerHTML = historyData.slice(-8).reverse().map(r => {
        const duration = formatDuration(getSessionDuration(r));
        const computer = r.computer_number || 'N/A';
        const status = r.status ? r.status.charAt(0).toUpperCase() + r.status.slice(1) : 'Unknown';
        return `
        <tr>
          <td>${r.date || '—'}</td>
          <td>${r.login || '—'}</td>
          <td>${r.logout || '—'}</td>
          <td>${duration}</td>
          <td>${computer}</td>
          <td>${status}</td>
        </tr>`;
    }).join('');
}

function renderMiniHistory() {
    const el = document.getElementById('miniHistBody');
    if (!el) return;
    if (!historyData.length) {
        el.innerHTML = `<tr class="no-data-row"><td colspan="6">
            <i class="fa-regular fa-folder-open" style="font-size:1.3rem;display:block;margin-bottom:8px;opacity:.35"></i>
            No records yet</td></tr>`;
        return;
    }
    el.innerHTML = historyData.slice(-3).reverse().map(r => {
        const computer = r.computer_number || 'N/A';
        const status = r.status ? r.status.charAt(0).toUpperCase() + r.status.slice(1) : 'Unknown';
        return `
        <tr>
          <td>${r.date || '—'}</td>
          <td>${r.login || '—'}</td>
          <td>${r.logout || '—'}</td>
          <td>${r.duration_display || '0m'}</td>
          <td>${computer}</td>
          <td>${status}</td>
        </tr>`;
    }).join('');
}

/* ── STATUS STRIP ── */
function updateStatusStrip() {
    const strip = document.getElementById('statusStrip');
    const pulse = document.getElementById('pulseD');
    const msg   = document.getElementById('statusMsg');
    if (!strip || !pulse || !msg) {
        console.warn('Status strip elements not found');
        return;
    }

    // Check if student has an active sit-in session (case-insensitive)
    const hasActiveSession = historyData && historyData.length > 0 && 
        historyData.some(r => r && r.status && r.status.toLowerCase() === 'active');

    console.log('Status update - Has active session:', hasActiveSession, 'Data count:', historyData?.length || 0);

    if (hasActiveSession) {
        strip.classList.remove('off');
        strip.classList.add('on');
        pulse.classList.remove('off');
        pulse.classList.add('on');
        msg.innerHTML = 'You are <strong>sit-in</strong>, please be responsible and careful of the equipments. <strong>Thank YOU!</strong>';
    } else {
        strip.classList.remove('on');
        strip.classList.add('off');
        pulse.classList.remove('on');
        pulse.classList.add('off');
        msg.innerHTML = 'You are <strong>not currently sitting in.</strong> Use <strong>Reservation</strong> to book a lab session.';
    }

    // Update reservation form availability
    updateReservationForm(hasActiveSession);
}

/* ── RESERVATION FORM CONTROL ── */
function updateReservationForm(hasActiveSession) {
    // Find the card containing "New Reservation" header
    let formCard = null;
    const cards = document.querySelectorAll('.ccs-card');
    for (const card of cards) {
        const header = card.querySelector('.ccs-card-header');
        if (header && header.textContent.includes('New Reservation')) {
            formCard = card;
            break;
        }
    }
    
    const formInputs = document.querySelectorAll('#rPurpose, #rLab, #rComputer, #rDate, #rTime');
    const submitBtn = document.querySelector('[onclick="submitReservation()"]');

    if (hasActiveSession) {
        // Disable form when student is currently sitting in
        formInputs.forEach(input => input.disabled = true);
        if (submitBtn) submitBtn.disabled = true;

        // Add overlay with message
        if (formCard && !formCard.querySelector('.reservation-disabled-overlay')) {
            const overlay = document.createElement('div');
            overlay.className = 'reservation-disabled-overlay';
            overlay.innerHTML = `
                <div class="reservation-disabled-message">
                    <i class="fa-solid fa-clock-rotate-left"></i>
                    <p>You cannot make a new reservation while you are currently sitting in.</p>
                    <small>Please complete your current session first.</small>
                </div>
            `;
            formCard.style.position = 'relative';
            formCard.appendChild(overlay);
        }
    } else {
        // Enable form when no active session
        formInputs.forEach(input => input.disabled = false);
        if (submitBtn) submitBtn.disabled = false;

        // Remove overlay
        const overlay = document.querySelector('.reservation-disabled-overlay');
        if (overlay) overlay.remove();
    }
}

/* ── FORMAT HELPERS ── */
function fmtDate(d) {
    if (!d) return '—';
    const [y,m,dy] = d.split('-');
    const months = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];
    return `${months[parseInt(m)-1]} ${parseInt(dy)}, ${y}`;
}
function fmtTime(t) {
    if (!t) return '—';
    const [h,m] = t.split(':');
    const hr = parseInt(h);
    return `${hr%12||12}:${m} ${hr>=12?'PM':'AM'}`;
}
function todayStr() { return new Date().toISOString().slice(0,10); }

async function postJSON(url, payload) {
    try {
        const res = await fetch(url, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            credentials: 'include',
            body: JSON.stringify(payload)
        });
        
        const text = await res.text();
        if (!text) return null;
        
        try {
            return JSON.parse(text);
        } catch (parseErr) {
            console.error(`postJSON parse error from ${url}:`, text.slice(0, 200));
            return null;
        }
    } catch (err) {
        console.error('postJSON error:', err);
        return null;
    }
}

/* ── LEADERBOARD ── */
async function loadLeaderboard() {
    try {
        const data = await postJSON('user_leaderboard_fetch.php', {});
        
        if (!data || !data.success) {
            // Leaderboard is disabled, hide the card
            const leaderboardCard = document.getElementById('leaderboardCard');
            if (leaderboardCard) leaderboardCard.style.display = 'none';
            return;
        }

        const leaderboard = data.leaderboard || [];
        const leaderboardCard = document.getElementById('leaderboardCard');
        const leaderboardContainer = document.getElementById('leaderboardContainer');

        if (!leaderboardCard || !leaderboardContainer) {
            return; // Elements not found
        }

        if (!leaderboard.length) {
            leaderboardCard.style.display = 'none';
            return;
        }

        // Show the leaderboard card
        leaderboardCard.style.display = 'block';

        // Render the leaderboard
        renderLeaderboard(leaderboard, leaderboardContainer);
    } catch (err) {
        console.error('Error loading leaderboard:', err);
    }
}

function renderLeaderboard(leaderboard, container) {
    if (!leaderboard || leaderboard.length === 0) {
        container.innerHTML = `
            <div class="leaderboard-empty">
                <i class="fa-solid fa-trophy"></i>
                <p>No leaderboard data yet</p>
            </div>`;
        return;
    }

    // Define medals for top 3
    const medals = {
        1: '🥇',
        2: '🥈',
        3: '🥉'
    };

    // Render all leaderboard entries in horizontal list format
    let html = '<div class="leaderboard-list">';
    leaderboard.forEach((student, index) => {
        const rank = index + 1;
        const medal = medals[rank] ? `<span class="leaderboard-medal">${medals[rank]}</span>` : '';
        
        const avatarHtml = student.profile_pic && student.profile_pic !== 'null'
            ? `<img src="${student.profile_pic}" alt="${student.name}" class="leaderboard-avatar-img">`
            : `<div class="leaderboard-avatar-placeholder">${(student.first_name[0] + student.last_name[0]).toUpperCase()}</div>`;

        html += `
            <div class="leaderboard-item">
                <div class="leaderboard-rank-num">#${rank}${medal}</div>
                ${avatarHtml}
                <div class="leaderboard-info">
                    <div class="leaderboard-name">${student.name}</div>
                    <div class="leaderboard-id">${student.id_number}</div>
                    <div class="leaderboard-course">${student.course}</div>
                </div>
                <div class="leaderboard-stats">
                    <div class="leaderboard-stat">
                        <div class="leaderboard-stat-label">Sessions</div>
                        <div class="leaderboard-stat-value">${student.sit_in_count}</div>
                    </div>
                    <div class="leaderboard-stat">
                        <div class="leaderboard-stat-label">Hours</div>
                        <div class="leaderboard-stat-value">${student.total_hours}</div>
                    </div>
                    <div class="leaderboard-stat">
                        <div class="leaderboard-stat-label">Points</div>
                        <div class="leaderboard-stat-value leaderboard-points">${student.points}</div>
                    </div>
                </div>
            </div>`;
    });
    html += '</div>';

    container.innerHTML = html;
}

/* ── INIT ── */
document.addEventListener('DOMContentLoaded', async () => {
    refreshDisplay();
    updateSessionUI();
    initTheme();
    // Load history first and wait for it to complete before rendering
    await loadHistoryData();
    renderHistory();
    renderMiniHistory();
    // Now load other data
    loadMyReservations();
    loadNotifications();
    loadAnnouncements();
    loadLeaderboard();
    loadLabStatus();
    loadAvailableSoftware();
    setInterval(loadNotifications, 20000);
    setInterval(loadHistoryData, 15000); // Refresh sit-in status every 15 seconds
    setInterval(loadLabStatus, 30000); // Refresh lab status every 30 seconds
    setInterval(loadAvailableSoftware, 60000); // Refresh software list every 60 seconds
    setInterval(loadLeaderboard, 60000); // Refresh leaderboard every 60 seconds

    // Set reservation date to today
    const rDate = document.getElementById('rDate');
    if (rDate) rDate.value = todayStr();
    
    // Add event listeners for computer availability updates
    const rLab = document.getElementById('rLab');
    if (rLab) rLab.addEventListener('change', updateComputerStatus);
    if (rDate) rDate.addEventListener('change', updateComputerStatus);

    /* ── Pre-populate Edit Profile form from session data ── */
    // Text inputs
    setVal('pFn', S.first);
    setVal('pLn', S.last);
    setVal('pMn', S.middle);
    setVal('pEm', S.email);
    setVal('pAd', S.address);

    // Course select — match option value to S.course
    const pCo = document.getElementById('pCo');
    if (pCo) pCo.value = S.course;

    // Year level select — match option text/value to S.year (e.g. "3rd Year")
    const pYr = document.getElementById('pYr');
    if (pYr) pYr.value = S.year;

    // Show login success modal only on first page load after login
    const loginModal = document.getElementById('modalLogin');
    if (loginModal && window.__SESSION__.justLoggedIn) {
        new bootstrap.Modal(loginModal).show();
    }
});