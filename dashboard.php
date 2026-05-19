<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: index.html");
    exit();
}

/* ── Prepare display values ──────────────────────────────────── */
// Convert numeric year_level (1–5) to display string for the JS layer and selects
$yearLabels  = [1 => '1st Year', 2 => '2nd Year', 3 => '3rd Year', 4 => '4th Year', 5 => '5th Year'];
$yearRaw     = (int) ($_SESSION['year_level'] ?? 1);
$yearDisplay = $yearLabels[$yearRaw] ?? $yearRaw . 'th Year';

// Build avatar src: use saved photo when it exists, otherwise a deterministic dicebear fallback
$savedPic  = $_SESSION['profile_pic'] ?? '';
$avatarSrc = (!empty($savedPic) && file_exists($savedPic))
    ? htmlspecialchars($savedPic) . '?v=' . filemtime($savedPic)   // cache-bust on file change
    : 'https://api.dicebear.com/8.x/adventurer/svg?seed='
      . urlencode($_SESSION['id_number'] ?? 'default') . '&backgroundColor=b6e3f4';
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>CCS SIMS | Student Dashboard</title>

  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" />
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet" />
  <link href="style.css" rel="stylesheet" />
</head>
<body class="dashboard">

<!-- ══════════════════════════════════════════════════════════
     TOAST
══════════════════════════════════════════════════════════ -->
<div class="toast-container position-fixed top-0 end-0 p-3" style="margin-top:66px">
  <div id="liveToast" class="toast" role="alert" aria-live="assertive" aria-atomic="true">
    <div class="toast-body">
      <i class="fa-solid fa-circle-check" id="toastIcon"></i>
      <span id="toastMsg">Done!</span>
    </div>
  </div>
</div>

<!-- ══════════════════════════════════════════════════════════
     NAVBAR
══════════════════════════════════════════════════════════ -->
<nav class="navbar navbar-expand-lg ccs-navbar sticky-top">
  <div class="container-fluid px-0">

    <a class="navbar-brand" href="#">
      <span class="brand-pip"></span> CCS Sit-in Monitoring System: DASHBOARD
    </a>

    <button class="navbar-toggler border-0" type="button"
            data-bs-toggle="collapse" data-bs-target="#navbarMain"
            style="color:#fff">
      <i class="fa-solid fa-bars"></i>
    </button>

    <div class="collapse navbar-collapse" id="navbarMain">
      <ul class="navbar-nav ms-auto align-items-lg-center gap-1">

        <!-- Notification dropdown -->
        <li class="nav-item dropdown">
          <a class="nav-link dropdown-toggle" href="#" id="notifToggle"
             data-bs-toggle="dropdown" aria-expanded="false">
            <i class="fa-regular fa-bell"></i> Notification
            <span class="notif-badge" id="notifBadge" style="display:none">0</span>
          </a>
          <div class="dropdown-menu ccs-dropdown-menu p-0" aria-labelledby="notifToggle" style="width:290px">
            <div class="notif-header">
              Notifications
              <span class="notif-clear" onclick="clearNotifs()">Clear all</span>
            </div>
            <div id="notifItems">
              <div class="notif-empty">Loading notifications...</div>
            </div>
          </div>
        </li>

        <li class="nav-item">
          <a class="nav-link active" data-tab="home" onclick="switchTab('home')">
            <i class="fa-solid fa-house"></i> Home
          </a>
        </li>
        <li class="nav-item">
          <a class="nav-link" data-tab="profile" onclick="switchTab('profile')">
            <i class="fa-solid fa-user-pen"></i> Edit Profile
          </a>
        </li>
        <li class="nav-item">
          <a class="nav-link" data-tab="history" onclick="switchTab('history')">
            <i class="fa-solid fa-clock-rotate-left"></i> History
          </a>
        </li>
        <li class="nav-item">
          <a class="nav-link" data-tab="summary" onclick="switchTab('summary')">
            <i class="fa-solid fa-chart-pie"></i> My Summary
          </a>
        </li>
        <li class="nav-item">
          <a class="nav-link" data-tab="reservation" onclick="switchTab('reservation')">
            <i class="fa-solid fa-calendar-plus"></i> Reservation
          </a>
        </li>
        <li class="nav-item">
          <a class="nav-link btn-logout ms-1" onclick="confirmLogout()">
            <i class="fa-solid fa-right-from-bracket"></i> Log out
          </a>
        </li>

      </ul>
    </div>
  </div>
</nav>

<!-- ══════════════════════════════════════════════════════════
     PAGE
══════════════════════════════════════════════════════════ -->
<div class="page-wrap">

  <!-- ██████████████  HOME VIEW  ██████████████ -->
  <div class="view active" id="view-home">
    <!-- Top Row: Main Dashboard Cards -->
    <div class="row g-4 mb-4">

      <!-- LEFT: Student Info -->
      <div class="col-lg-3">
        <div class="ccs-card">
          <div class="ccs-card-header">
            <i class="fa-solid fa-id-card"></i> Student Information
          </div>

          <div class="stu-avatar-wrap">
            <!-- src is rendered by PHP so the photo shows on first load without JS flicker -->
            <img id="mainAvatar"
                 src="<?= $avatarSrc ?>"
                 alt="Student Avatar" />
            <div class="stu-name" id="dName">Nacht Faust</div>
            <span class="stu-badge" id="dBadge">BSIT · Year 3</span>
          </div>

          <ul class="info-list">
            <li>
              <span class="info-icon"><i class="fa-solid fa-hashtag"></i></span>
              <div>
                <div class="info-label">ID Number</div>
                <div class="info-value" id="dId">20210300</div>
              </div>
            </li>
            <li>
              <span class="info-icon"><i class="fa-solid fa-graduation-cap"></i></span>
              <div>
                <div class="info-label">Course</div>
                <div class="info-value" id="dCourse">BSIT</div>
              </div>
            </li>
            <li>
              <span class="info-icon"><i class="fa-solid fa-layer-group"></i></span>
              <div>
                <div class="info-label">Year Level</div>
                <div class="info-value" id="dYear">3rd Year</div>
              </div>
            </li>
            <li>
              <span class="info-icon"><i class="fa-solid fa-envelope"></i></span>
              <div>
                <div class="info-label">Email</div>
                <div class="info-value" id="dEmail">nacht.faust@email.com</div>
              </div>
            </li>
            <li>
              <span class="info-icon"><i class="fa-solid fa-location-dot"></i></span>
              <div>
                <div class="info-label">Address</div>
                <div class="info-value" id="dAddr">Black Bulls Hideout, Hage Village</div>
              </div>
            </li>
          </ul>

          <div class="session-block">
            <div class="s-label"><i class="fa-regular fa-hourglass"></i>&nbsp; Remaining Sessions</div>
            <div class="s-num" id="sNum">30</div>
            <div class="s-sub">out of 30 total sessions</div>
            <div class="session-bar">
              <div class="session-bar-fill" id="sessFill" style="width:100%"></div>
            </div>
          </div>
        </div>
      </div><!-- /col -->

      <!-- MIDDLE -->
      <div class="col-lg-6 d-flex flex-column gap-3">

        <!-- Status strip -->
        <div class="status-strip off" id="statusStrip">
          <span class="pulse-dot off" id="pulseD"></span>
          <span id="statusMsg">
            You are <strong>not currently sitting in.</strong>
            Use <strong>Reservation</strong> to book a lab session.
          </span>
        </div>

        <!-- Quick Actions -->
        <div class="ccs-card">
          <div class="ccs-card-header"><i class="fa-solid fa-bolt"></i> Quick Actions</div>
          <div class="ccs-card-body">
            <div class="row g-3">
              <div class="col-4">
                <button class="qa-btn primary" onclick="switchTab('reservation')">
                  <i class="fa-solid fa-calendar-plus"></i> Reserve a Lab
                </button>
              </div>
              <div class="col-4">
                <button class="qa-btn" onclick="switchTab('history')">
                  <i class="fa-solid fa-clock-rotate-left"></i> View History
                </button>
              </div>
              <div class="col-4">
                <button class="qa-btn" onclick="switchTab('profile')">
                  <i class="fa-solid fa-user-pen"></i> Edit Profile
                </button>
              </div>
            </div>
          </div>
        </div>

        <!-- Sit-in Summary -->
        <div class="ccs-card">
          <div class="ccs-card-header"><i class="fa-solid fa-chart-simple"></i> Sit-in Summary</div>
          <div class="ccs-card-body">
            <div class="summary-grid">
              <div class="summary-card">
                <div class="summary-label">Total sit-in hours</div>
                <div class="summary-value" id="summaryHours">0.00</div>
              </div>
              <div class="summary-card">
                <div class="summary-label">Total sessions</div>
                <div class="summary-value" id="summarySessions">0</div>
              </div>
              <div class="summary-card">
                <div class="summary-label">Average duration</div>
                <div class="summary-value" id="summaryAvg">0.00</div>
                <div class="summary-meta">hours per session</div>
              </div>
              <div class="summary-card">
                <div class="summary-label">Longest session</div>
                <div class="summary-value" id="summaryLongest">0.00</div>
                <div class="summary-meta">hours</div>
              </div>
            </div>
          </div>
        </div>

        <!-- Announcements -->
        <div class="ccs-card">
          <div class="ccs-card-header"><i class="fa-solid fa-bullhorn"></i> Announcements</div>
          <div class="ccs-card-body" id="announcementsContainer">
            <!-- Announcements will be loaded here -->
          </div>
        </div>

        <!-- Leaderboard (if enabled by admin) -->
        <div class="ccs-card" id="leaderboardCard" style="display: none;">
          <div class="ccs-card-header"><i class="fa-solid fa-trophy"></i> Top Sit-in Students Leaderboard</div>
          <div class="leaderboard-container" id="leaderboardContainer">
            <!-- Leaderboard will be loaded here -->
          </div>
        </div>

        <!-- Recent Session Details -->
        <div class="ccs-card">
          <div class="ccs-card-header"><i class="fa-solid fa-table-list"></i> Session Details</div>
          <div class="table-responsive">
            <table class="ccs-table">
              <thead>
                <tr>
                  <th>Date</th>
                  <th>Time In</th>
                  <th>Time Out</th>
                  <th>Duration</th>
                  <th>PC No.</th>
                  <th>Status</th>
                </tr>
              </thead>
              <tbody id="sessionTableBody">
                <tr class="no-data-row">
                  <td colspan="6">
                    <i class="fa-regular fa-folder-open" style="font-size:1.3rem;display:block;margin-bottom:8px;opacity:.35"></i>
                    No session records yet
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>

      </div><!-- /col mid -->

      <!-- RIGHT: Rules -->
      <div class="col-lg-3 d-none d-lg-block">
        <div class="d-flex justify-content-end mb-3">
          <button class="theme-toggle-btn" id="themeToggleBtn" onclick="toggleTheme()" type="button">
            <i class="fa-solid fa-moon"></i> Dark Mode
          </button>
        </div>
        <div class="ccs-card">
          <div class="ccs-card-header"><i class="fa-solid fa-shield-halved"></i> Rules &amp; Regulations</div>
          <div class="rules-scroll">
            <div class="rules-uni">University of Cebu</div>
            <div class="rules-dept">College of Information &amp; Computer Studies</div>
            <div class="rules-sec">Laboratory Rules and Regulations</div>
            <p class="mb-2" style="font-size:.82rem">
              To avoid embarrassment and maintain camaraderie with your friends and superiors
              at our laboratories, please observe the following:
            </p>
            <ol>
              <li>Maintain silence, proper decorum, and discipline inside the laboratory. Mobile phones and personal equipment must be switched off.</li>
              <li>Games are not allowed inside the lab — computer-related, card games, or anything that may disturb operations.</li>
              <li>Surfing the Internet is allowed only with the instructor's permission. Downloading and installing software are strictly prohibited.</li>
              <li>Deleting computer files and changing computer setup is not allowed.</li>
              <li>Observe proper sitting posture at all times.</li>
              <li>Laboratory users must sign in the logbook before using any computer unit.</li>
              <li>All bags must be deposited at the bag deposit area outside the laboratory.</li>
              <li>Eating and drinking inside the laboratory is strictly prohibited.</li>
              <li>Students must present their valid ID and log in to the sit-in monitoring system.</li>
              <li>Violations will subject the student to disciplinary action.</li>
            </ol>
          </div>
        </div>
      </div><!-- /col right -->

    </div><!-- /row main -->

    <!-- Bottom Row: Lab Status & Software -->
    <div class="row g-4">
      <!-- Laboratory Status -->
      <div class="col-lg-6 col-xl-4">
        <div class="ccs-card h-100">
          <div class="ccs-card-header"><i class="fa-solid fa-building"></i> Laboratory Status</div>
          <div class="ccs-card-body">
            <div id="labStatusContainer">
              <div style="text-align: center; padding: 20px; color: var(--text3);">
                <i class="fa-solid fa-spinner fa-spin" style="font-size: 1.5rem;"></i>
                <p style="margin-top: 10px; font-size: 0.85rem;">Loading labs...</p>
              </div>
            </div>
          </div>
        </div>
      </div><!-- /col -->

      <!-- Available Software -->
      <div class="col-lg-6 col-xl-4">
        <div class="ccs-card h-100">
          <div class="ccs-card-header"><i class="fa-solid fa-microchip"></i> Available Software</div>
          <div class="ccs-card-body">
            <div id="softwareListContainer">
              <div style="text-align: center; padding: 20px; color: var(--text3);">
                <i class="fa-solid fa-spinner fa-spin" style="font-size: 1.5rem;"></i>
                <p style="margin-top: 10px; font-size: 0.85rem;">Loading software...</p>
              </div>
            </div>
          </div>
        </div>
      </div><!-- /col -->
    </div><!-- /row bottom -->
  </div><!-- /home view -->


  <!-- ██████████████  HISTORY VIEW  ██████████████ -->
  <div class="view" id="view-history">

    <div class="view-header">
      <div class="view-title">
        <i class="fa-solid fa-clock-rotate-left"></i> History Information
      </div>
    </div>

    <div class="ccs-card">
      <div class="ccs-card-body p-4">

        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
          <div class="tbl-entries d-flex align-items-center gap-2" style="font-size:.82rem;color:var(--text2)">
            Show
            <select id="histEntries" onchange="renderHistory()">
              <option value="5">5</option>
              <option value="10" selected>10</option>
              <option value="25">25</option>
            </select>
            entries per page
          </div>
          <div class="tbl-search">
            <i class="fa-solid fa-magnifying-glass"></i>
            <input type="text" id="histSearch" placeholder="Search…" oninput="renderHistory()" />
          </div>
        </div>

        <div class="table-responsive">
          <table class="ccs-table">
            <thead>
              <tr>
                <th>ID Number</th><th>Name</th><th>Purpose</th>
                <th>Laboratory</th><th>Login</th><th>Logout</th>
                <th>Date</th><th>Feedback</th><th>Action</th>
              </tr>
            </thead>
            <tbody id="histBody"></tbody>
          </table>
        </div>

        <div class="tbl-footer mt-3">
          <span id="histInfo">Showing 0 entries</span>
          <div class="d-flex gap-1" id="histPagination"></div>
        </div>

      </div>
    </div>
  </div><!-- /history view -->


  <!-- ██████████████  MY SUMMARY VIEW  ██████████████ -->
  <div class="view" id="view-summary">

    <div class="view-header">
      <div class="view-title">
        <i class="fa-solid fa-chart-pie"></i> My Sit-in Summary
      </div>
      <button class="btn-ccs-primary" onclick="exportSummaryPDF()">
        <i class="fa-solid fa-file-pdf"></i> Export PDF
      </button>
    </div>

    <!-- Summary Statistics Cards -->
    <div class="row g-3 mb-4">
      <div class="col-md-6 col-lg-3">
        <div class="ccs-summary-card" style="border-left: 4px solid #ffff00;">
          <div class="summary-label">Total Sit-in Hours</div>
          <div class="summary-value" id="totalHours">0.0h</div>
          <div class="summary-subtext">cumulative time</div>
        </div>
      </div>
      <div class="col-md-6 col-lg-3">
        <div class="ccs-summary-card" style="border-left: 4px solid #b21111;">
          <div class="summary-label">Total Sessions</div>
          <div class="summary-value" id="totalSessions">0</div>
          <div class="summary-subtext">completed</div>
        </div>
      </div>
      <div class="col-md-6 col-lg-3">
        <div class="ccs-summary-card" style="border-left: 4px solid #db79ff;">
          <div class="summary-label">Avg Session Duration</div>
          <div class="summary-value" id="avgDuration">0m</div>
          <div class="summary-subtext">average length</div>
        </div>
      </div>
      <div class="col-md-6 col-lg-3">
        <div class="ccs-summary-card" style="border-left: 4px solid #ffbf00;">
          <div class="summary-label">Longest Session</div>
          <div class="summary-value" id="longestSession">0m</div>
          <div class="summary-subtext">maximum duration</div>
        </div>
      </div>
    </div>

    <!-- Session History Table -->
    <div class="ccs-card">
      <div class="ccs-card-header">
        <i class="fa-solid fa-history"></i> Session History
      </div>

      <div class="ccs-card-body p-4">

        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
          <div class="tbl-entries d-flex align-items-center gap-2" style="font-size:.82rem;color:var(--text2)">
            Show
            <select id="summEntries" onchange="renderSummaryHistory()">
              <option value="5">5</option>
              <option value="10" selected>10</option>
              <option value="25">25</option>
              <option value="50">50</option>
            </select>
            entries per page
          </div>
          <div class="tbl-search">
            <i class="fa-solid fa-magnifying-glass"></i>
            <input type="text" id="summSearch" placeholder="Search by lab, purpose, date…" oninput="renderSummaryHistory()" />
          </div>
        </div>

        <div class="table-responsive">
          <table class="ccs-table">
            <thead>
              <tr>
                <th>Date</th>
                <th>Time In</th>
                <th>Time Out</th>
                <th>Duration</th>
                <th>PC #</th>
                <th>Laboratory</th>
                <th>Purpose</th>
                <th>Status</th>
              </tr>
            </thead>
            <tbody id="summBody"></tbody>
          </table>
        </div>

        <div class="tbl-footer mt-3">
          <span id="summInfo">Showing 0 entries</span>
          <div class="d-flex gap-1" id="summPagination"></div>
        </div>

      </div>
    </div>

  </div><!-- /summary view -->


  <!-- ██████████████  RESERVATION VIEW  ██████████████ -->
  <div class="view" id="view-reservation">

    <div class="view-header">
      <div class="view-title">
        <i class="fa-solid fa-calendar-plus"></i> Reservation
      </div>
    </div>

    <div class="row g-4">

      <!-- Form -->
      <div class="col-lg-8">
        <div class="ccs-card">
          <div class="ccs-card-header"><i class="fa-solid fa-pen-to-square"></i> New Reservation</div>
          <div class="ccs-card-body p-4">

            <div class="row g-3">
              <div class="col-md-6">
                <label class="form-label-ccs">ID Number</label>
                <input class="form-control-ccs" type="text" id="rIdNumber" readonly />
              </div>
              <div class="col-md-6">
                <label class="form-label-ccs">Student Name</label>
                <input class="form-control-ccs" type="text" id="rName" value="Kimmy D. Yammy" readonly />
              </div>
              <div class="col-12">
                <label class="form-label-ccs">Purpose / Language <span style="color:var(--red)">*</span></label>
                <select class="form-select-ccs" id="rPurpose">
                  <option value="">Select purpose / language…</option>
                  <optgroup label="Programming Languages">
                    <option>C Programming</option><option>C++ Programming</option>
                    <option>Java</option><option>Python</option><option>PHP</option>
                    <option>JavaScript</option><option>ASP.Net</option>
                    <option>C# (.NET)</option><option>Visual Basic</option>
                    <option>SQL / Database</option>
                  </optgroup>
                  <optgroup label="Academic Work">
                    <option>Thesis / Capstone</option><option>Research Paper</option>
                    <option>Assignment</option><option>Laboratory Exercise</option>
                    <option>Online Class</option>
                  </optgroup>
                  <optgroup label="Other">
                    <option>Personal Project</option>
                    <option>Browsing / Research</option>
                    <option>Other</option>
                  </optgroup>
                </select>
              </div>
              <div class="col-md-6">
                <label class="form-label-ccs">Laboratory <span style="color:var(--red)">*</span></label>
                <select class="form-select-ccs" id="rLab">
                  <option value="">Select lab…</option>
                  <option value="3">Lab 524</option>
                  <option value="4">Lab 526</option>
                  <option value="5">Lab 528</option>
                  <option value="6">Lab 530</option>
                  <option value="7">Lab 542</option>
                  <option value="8">MAC</option>
                </select>
              </div>
              <div class="col-md-6">
                <label class="form-label-ccs">Computer Number <span style="color:var(--red)">*</span></label>
                <select class="form-select-ccs" id="rComputer" onchange="updateComputerStatus()">
                  <option value="">Select computer…</option>
                  <option value="1">1</option><option value="2">2</option><option value="3">3</option><option value="4">4</option><option value="5">5</option><option value="6">6</option><option value="7">7</option><option value="8">8</option><option value="9">9</option><option value="10">10</option>
                  <option value="11">11</option><option value="12">12</option><option value="13">13</option><option value="14">14</option><option value="15">15</option><option value="16">16</option><option value="17">17</option><option value="18">18</option><option value="19">19</option><option value="20">20</option>
                  <option value="21">21</option><option value="22">22</option><option value="23">23</option><option value="24">24</option><option value="25">25</option><option value="26">26</option><option value="27">27</option><option value="28">28</option><option value="29">29</option><option value="30">30</option>
                  <option value="31">31</option><option value="32">32</option><option value="33">33</option><option value="34">34</option><option value="35">35</option><option value="36">36</option><option value="37">37</option><option value="38">38</option><option value="39">39</option><option value="40">40</option>
                </select>
                <div id="computerStatus" style="margin-top: 0.5rem; font-size: 0.85rem; padding: 0.5rem; border-radius: 6px; display: none;" class="alert alert-info"></div>
              </div>
              <div class="col-md-6">
                <label class="form-label-ccs">Date <span style="color:var(--red)">*</span></label>
                <input class="form-control-ccs" type="date" id="rDate" />
              </div>
              <div class="col-md-6">
                <label class="form-label-ccs">Time In <span style="color:var(--red)">*</span></label>
                <input class="form-control-ccs" type="time" id="rTime" />
              </div>
              <div class="col-md-6">
                <label class="form-label-ccs">Remaining Sessions</label>
                <input class="form-control-ccs" type="text" id="rSess" value="30" readonly />
              </div>
              <div class="col-12 mt-1">
                <button class="btn-ccs-primary" onclick="submitReservation()">
                  <i class="fa-solid fa-calendar-check"></i> Submit Reservation
                </button>
              </div>
            </div>

          </div>
        </div>
      </div><!-- /form col -->

      <!-- Guidelines + My Reservations -->
      <div class="col-lg-4 d-flex flex-column gap-3">

        <div class="ccs-card">
          <div class="ccs-card-header"><i class="fa-solid fa-circle-info"></i> Guidelines</div>
          <div class="ccs-card-body">
            <div class="res-tip">
              <i class="fa-solid fa-clock"></i>
              <p><strong>Arrive on time.</strong> Reservation cancelled if you don't check in within 15 minutes.</p>
            </div>
            <div class="res-tip">
              <i class="fa-solid fa-laptop"></i>
              <p><strong>Each session costs 1 session point.</strong> You have <strong id="tipSess">30</strong> remaining.</p>
            </div>
            <div class="res-tip">
              <i class="fa-solid fa-ban"></i>
              <p><strong>No games or unauthorized software.</strong> Violations may result in forfeiture.</p>
            </div>
            <div class="res-tip">
              <i class="fa-solid fa-id-card"></i>
              <p>Bring your <strong>valid school ID</strong> when claiming your reservation.</p>
            </div>
            
            <!-- Computer Status Legend -->
            <div style="margin-top: 14px; padding-top: 14px; border-top: 1px solid var(--border); font-size: 0.82rem;">
              <div style="font-weight: 600; color: var(--text); margin-bottom: 8px;"><i class="fa-solid fa-circle-info"></i> Computer Status Guide</div>
              <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 6px;">
                <div style="width: 14px; height: 14px; background: #22c55e; border-radius: 3px;"></div>
                <span style="color: var(--text3);">Available</span>
              </div>
              <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 6px;">
                <div style="width: 14px; height: 14px; background: #ff4d6a; border-radius: 3px;"></div>
                <span style="color: var(--text3);">Occupied</span>
              </div>
              <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 6px;">
                <div style="width: 14px; height: 14px; background: #FFD700; border-radius: 3px;"></div>
                <span style="color: var(--text3);">Reserved (Pending)</span>
              </div>
              <div style="display: flex; align-items: center; gap: 8px;">
                <div style="width: 14px; height: 14px; background: #9CA3AF; border-radius: 3px;"></div>
                <span style="color: var(--text3);">Unavailable (Disabled)</span>
              </div>
            </div>
          </div>
        </div>

        <div class="ccs-card">
          <div class="ccs-card-header"><i class="fa-solid fa-list-check"></i> My Reservations</div>
          <div class="ccs-card-body">
            <div id="myResList">
              <p class="text-center" style="font-size:.82rem;color:var(--text3);font-style:italic;padding:8px 0">
                No reservations yet.
              </p>
            </div>
          </div>
        </div>

      </div>
    </div><!-- /row -->
  </div><!-- /reservation view -->


  <!-- ██████████████  EDIT PROFILE VIEW  ██████████████ -->
  <div class="view" id="view-profile">

    <div class="view-header">
      <div class="view-title"><i class="fa-solid fa-user-pen"></i> Edit Profile</div>
    </div>

    <div class="row g-4">

      <!-- Avatar card -->
      <div class="col-lg-3">
        <div class="ccs-card">
          <div class="profile-av-card">
            <!-- src rendered by PHP — shows saved photo immediately before JS runs -->
            <div class="prof-avatar-wrap" id="profAvatarWrap">
              <img id="profAvatar"
                   src="<?= $avatarSrc ?>"
                   alt="Profile Avatar" />
              <!-- Upload spinner overlay — shown while photo is being sent to server -->
              <div class="avatar-upload-overlay" id="avatarOverlay" style="display:none">
                <div class="avatar-spinner"></div>
              </div>
            </div>
            <div class="profile-name" id="profName">Nacht Faust</div>
            <div class="profile-role" id="profRole">BSIT · 3rd Year</div>
            <button class="btn-photo" onclick="triggerPhotoInput()">
              <i class="fa-solid fa-camera"></i> Change Photo
            </button>
            <input type="file" id="photoInput" accept="image/*" style="display:none" onchange="previewPhoto(event)" />
            <div class="profile-sess-stat">
              <div class="sml">Remaining Sessions</div>
              <div class="big" id="profSessNum">30</div>
              <div class="sml">out of 30</div>
            </div>
          </div>
        </div>
      </div>

      <!-- Edit form -->
      <div class="col-lg-9">
        <div class="ccs-card">
          <div class="ccs-card-header"><i class="fa-solid fa-pen-to-square"></i> Personal Information</div>
          <div class="ccs-card-body p-4">
            <div class="row g-3">
              <div class="col-md-6">
                <label class="form-label-ccs">First Name</label>
                <input class="form-control-ccs" type="text" id="pFn" value="Nacht" />
              </div>
              <div class="col-md-6">
                <label class="form-label-ccs">Last Name</label>
                <input class="form-control-ccs" type="text" id="pLn" value="Faust" />
              </div>
              <div class="col-md-6">
                <label class="form-label-ccs">Middle Name <span style="color:var(--text3)">(optional)</span></label>
                <input class="form-control-ccs" type="text" id="pMn" value="D." />
              </div>
              <div class="col-md-6">
                <label class="form-label-ccs">ID Number</label>
                <input class="form-control-ccs" type="text" value="20210300" readonly />
              </div>
              <div class="col-md-6">
                <label class="form-label-ccs">Email Address</label>
                <input class="form-control-ccs" type="email" id="pEm" value="nacht.faust@email.com" />
              </div>
              <div class="col-md-6">
                <label class="form-label-ccs">Address</label>
                <input class="form-control-ccs" type="text" id="pAd" value="Black Bulls Hideout,Hage Village" />
              </div>
              <div class="col-md-6">
                <label class="form-label-ccs">Course</label>
                <select class="form-select-ccs" id="pCo">
                    <option value="BSIT">BSIT</option>
                    <option value="BSCS">BSCS</option>
                    <option value="BSCRIM">BSCRIM</option>
                    <option value="BSCA">BSCA</option>
                    <option value="BSED">BSED</option>
                    <option value="BSCE">BSCE</option>
                    <option value="BSN">BSN</option>
                    <option value="BSHM">BSHM</option>
                    <option value="BSA">BSA</option>
                    <option value="BSBA">BSBA</option>
                    <option value="BSME">BSME</option>
                    <option value="BSOA">BSOA</option>
                    <option value="BSREM">BSREM</option>
                    <option value="BSTM">BSTM</option>
                    </select>
              </div>
              <div class="col-md-6">
                <label class="form-label-ccs">Year Level</label>
                <select class="form-select-ccs" id="pYr">
                  <option>1st Year</option>
                  <option>2nd Year</option>
                  <option selected>3rd Year</option>
                  <option>4th Year</option>
                </select>
              </div>

              <div class="col-12"><hr class="divider" /></div>

              <div class="col-md-6">
                <label class="form-label-ccs">New Password</label>
                <input class="form-control-ccs" type="password" id="pPw" placeholder="Leave blank to keep current" />
              </div>
              <div class="col-md-6">
                <label class="form-label-ccs">Confirm Password</label>
                <input class="form-control-ccs" type="password" id="pPw2" placeholder="Repeat new password" />
              </div>

              <div class="col-12 mt-1">
                <button class="btn-ccs-primary" onclick="saveProfile()">
                  <i class="fa-solid fa-floppy-disk"></i> Save Changes
                </button>
              </div>
            </div>
          </div>
        </div>
      </div>

    </div><!-- /row -->
  </div><!-- /profile view -->

</div><!-- /page-wrap -->


<!-- ══════════════════════════════════════════════════════════
     MODALS
══════════════════════════════════════════════════════════ -->

<!-- Login Success -->
<div class="modal fade ccs-modal" id="modalLogin" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-body">
        <div class="m-icon"><i class="fa-solid fa-check"></i></div>
        <div class="m-title">Successful Login!</div>
        <p class="m-sub">Welcome back, <strong id="welcomeName">Nacht Faust</strong>! 👋</p>
      </div>
      <div class="modal-footer">
        <button class="btn-m-ok" data-bs-dismiss="modal">OK</button>
      </div>
    </div>
  </div>
</div>

<!-- Logout Confirm -->
<div class="modal fade ccs-modal" id="modalLogout" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-body">
        <div class="m-icon warn"><i class="fa-solid fa-right-from-bracket"></i></div>
        <div class="m-title">Log Out?</div>
        <p class="m-sub">Are you sure you want to end your session?</p>
      </div>
      <div class="modal-footer">
        <button class="btn-m-cancel" data-bs-dismiss="modal">Cancel</button>
        <button class="btn-m-ok" onclick="doLogout()">Yes, Log Out</button>
      </div>
    </div>
  </div>
</div>

<!-- Success Generic -->
<div class="modal fade ccs-modal" id="modalSuccess" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-body">
        <div class="m-icon"><i class="fa-solid fa-check"></i></div>
        <div class="m-title" id="successTitle">Done!</div>
        <p class="m-sub" id="successSub">Action completed successfully.</p>
      </div>
      <div class="modal-footer">
        <button class="btn-m-ok" data-bs-dismiss="modal">OK</button>
      </div>
    </div>
  </div>
</div>

<!-- Cancel Reservation Confirm -->
<div class="modal fade ccs-modal" id="modalCancelRes" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-body">
        <div class="m-icon danger"><i class="fa-solid fa-trash"></i></div>
        <div class="m-title">Cancel Reservation?</div>
        <p class="m-sub">This reservation will be permanently removed.</p>
      </div>
      <div class="modal-footer">
        <button class="btn-m-cancel" data-bs-dismiss="modal">Keep it</button>
        <button class="btn-m-ok danger" onclick="doDeleteReservation()">Yes, Cancel</button>
      </div>
    </div>
  </div>
</div>

<!-- Feedback Modal -->
<div class="modal fade ccs-modal" id="modalFeedback" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <div class="m-icon"><i class="fa-solid fa-comment-dots"></i></div>
        <div class="m-title">Sit-in Feedback</div>
        <p class="m-sub">Tell us how your sit-in session went.</p>
        <div class="mb-3">
          <label class="form-label">Session</label>
          <div id="feedbackSessionInfo" style="font-weight:600;color:#480741;"></div>
        </div>
        <div class="mb-3 form-check form-check-inline">
          <input class="form-check-input" type="radio" name="feedbackRating" id="feedbackSatisfied" value="satisfied">
          <label class="form-check-label" for="feedbackSatisfied">Satisfied</label>
        </div>
        <div class="mb-3 form-check form-check-inline">
          <input class="form-check-input" type="radio" name="feedbackRating" id="feedbackUnsatisfied" value="unsatisfied">
          <label class="form-check-label" for="feedbackUnsatisfied">Unsatisfied</label>
        </div>
        <div class="mb-3">
          <label class="form-label">Comments (optional)</label>
          <textarea class="form-control" id="feedbackText" rows="4" placeholder="Share what went well or what could improve..."></textarea>
        </div>
        <input type="hidden" id="feedbackSitId" />
      </div>
      <div class="modal-footer">
        <button class="btn-m-cancel" data-bs-dismiss="modal">Close</button>
        <button class="btn-m-ok" onclick="submitFeedback()">Save Feedback</button>
      </div>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<!-- ══════════════════════════════════════════════════════════
     SESSION BRIDGE — PHP session data → JS global
     All values are JSON-escaped to prevent XSS.
     script.js reads window.__SESSION__ on load.
══════════════════════════════════════════════════════════ -->
<script>
window.__SESSION__ = {
    first:        <?= json_encode($_SESSION['first_name']  ?? '') ?>,
    middle:       <?= json_encode($_SESSION['middle_name'] ?? '') ?>,
    last:         <?= json_encode($_SESSION['last_name']   ?? '') ?>,
    id:           <?= json_encode($_SESSION['id_number']   ?? '') ?>,
    email:        <?= json_encode($_SESSION['email']       ?? '') ?>,
    address:      <?= json_encode($_SESSION['address']     ?? '') ?>,
    course:       <?= json_encode($_SESSION['course']      ?? '') ?>,
    year:         <?= json_encode($yearDisplay) ?>,              // formatted: "3rd Year"
    year_raw:     <?= (int) $yearRaw ?>,                         // numeric: 3
    session:      <?= (int) ($_SESSION['sessions_left'] ?? 30) ?>,
    totalSession: 30,
    pic:          <?= json_encode($savedPic) ?>,                 // raw relative path (no ?v=)
    picSrc:       <?= json_encode($avatarSrc) ?>,                // cache-busted src ready for <img>
    justLoggedIn: <?= json_encode(!empty($_SESSION['just_logged_in'])) ?>,  // Show modal only once
};
<?php
// Clear the just_logged_in flag after the page has loaded
unset($_SESSION['just_logged_in']);
?>
</script>

<script src="script.js"></script>

</body>
</html>