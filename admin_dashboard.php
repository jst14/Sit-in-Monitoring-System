<?php
ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);
error_reporting(0);
session_start();
/* ── AUTH GUARD ── redirect non-admins back to login */
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'admin') {
    header("Location: index.html");
    exit();
}
$admin = $_SESSION;
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>CCS SIMS | Admin Dashboard</title>

  <!-- Bootstrap 5 -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet"/>
  <!-- Font Awesome 6 -->
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet"/>
  <!-- Google Fonts: DM Sans (body) + Syne (display/headings) -->
  <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Syne:wght@600;700;800&display=swap" rel="stylesheet"/>
  <!-- Chart.js -->
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

  <style>
    /* ═══════════════════════════════════════════════════════
       DESIGN TOKENS  — single source of truth for every color,
       spacing, and radius value used throughout the UI.
    ═══════════════════════════════════════════════════════ */
    :root {
      /* backgrounds — layered dark surfaces */
      --bg:        #140726;          /* page background */
      --surface:   #1a151c;          /* card / panel background */
      --surface2:  #1d1626;          /* elevated surface (table headers, modal headers) */
      --surface3:  #291d33;          /* hover / input bg */

      /* accent — electric cyan */
      --accent:    #4e1a74;
      --accent2:   #7a00cc;
      --accent-glow: rgba(217, 0, 255, 0.15);
      --gold:     #FFD700;
      --

      /* semantic colors */
      --green:     #00e5a0;
      --red:       #ff4d6a;
      --amber:     #ffb347;
      --blue:      #4d9fff;

      /* text hierarchy */
      --text1:     #e8eaf0;          /* primary text */
      --text2:     #7a8499;          /* secondary / muted */
      --text3:     #FFD700;          /* placeholder / disabled */

      /* borders */
      --border:    rgba(255,255,255,0.06);
      --border2:   rgba(0,212,255,0.18);

      /* geometry */
      --radius:    10px;
      --radius-lg: 16px;
      --radius-xl: 22px;

      /* shadows */
      --shadow:    0 4px 24px rgba(0,0,0,0.55);
      --shadow-lg: 0 16px 56px rgba(0,0,0,0.7);
      --glow:      0 0 32px rgba(0,212,255,0.12);
    }

    /* Light mode overrides */
    .light-mode {
      /* backgrounds — light surfaces */
      --bg:        #f8f9fa;
      --surface:   #ffffff;
      --surface2:  #f1f3f4;
      --surface3:  #e9ecef;

      /* accent — keep similar but adjust */
      --accent:    #4e1a74;
      --accent2:   #7a00cc;
      --accent-glow: rgba(217, 0, 255, 0.15);
      --gold:     #FFD700;

      /* semantic colors — keep same */
      --green:     #00e5a0;
      --red:       #ff4d6a;
      --amber:     #ffb347;
      --blue:      #4d9fff;

      /* text hierarchy — dark text */
      --text1:     #212529;
      --text2:     #6c757d;
      --text3:     #495057;

      /* borders */
      --border:    rgba(0,0,0,0.06);
      --border2:   rgba(0,212,255,0.18);

      /* shadows — lighter */
      --shadow:    0 4px 24px rgba(0,0,0,0.15);
      --shadow-lg: 0 16px 56px rgba(0,0,0,0.25);
      --glow:      0 0 32px rgba(0,212,255,0.12);
    }

    .light-mode body {
      background: var(--bg);
    }
    .light-mode body::before {
      background-image: url("data:image/svg+xml,%3Csvg viewBox='0 0 200 200' xmlns='http://www.w3.org/2000/svg'%3E%3Cfilter id='n'%3E%3CfeTurbulence type='fractalNoise' baseFrequency='0.85' numOctaves='4' stitchTiles='stitch'/%3E%3C/filter%3E%3Crect width='100%25' height='100%25' filter='url(%23n)' opacity='0.02'/%3E%3C/svg%3E");
    }
    .light-mode ::-webkit-scrollbar-track { background: var(--bg); }
    .light-mode ::-webkit-scrollbar-thumb { background: var(--surface3); }

    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

    .reservation-card {
      cursor: grab;
      transition: box-shadow .2s ease, transform .2s ease, opacity .2s ease;
    }
    .reservation-card:active { cursor: grabbing; }
    .reservation-card.dragging {
      opacity: .55;
      transform: scale(0.98);
      box-shadow: 0 10px 30px rgba(0,0,0,0.25);
    }
    .computer-chip {
      padding: 10px;
      min-height: 30px;
      display: flex;
      align-items: center;
      justify-content: center;
      border-radius: 20px;
      text-align: center;
      font-size: .82rem;
      font-weight: 600;
      color: #fff;
      user-select: none;
      transition: background .2s ease, transform .2s ease, box-shadow .2s ease;
      aspect-ratio: 1 / 1;
    }
    .computer-chip:hover { transform: translateY(-1px); }
    .computer-chip.drag-over { transform: scale(1.05); box-shadow: 0 0 0 3px rgba(255,255,255,0.14); }
    .computer-chip.occupied { background: #ff4d6a !important; }
    .computer-chip.assigned { background: #FFD700 !important; }
    .computer-layout-header {
      display: flex;
      justify-content: center;
      margin: 1rem 0 0.75rem;
    }
    .computer-layout-header .instructor-desk {
      width: 100%;
      max-width: 125px;
      background: linear-gradient(135deg, #1f4fd4, #0e74ff);
      border-radius: 14px;
      padding: 0.85rem 1rem;
      text-align: center;
      color: #fff;
      font-weight: 700;
      letter-spacing: .03em;
      box-shadow: 0 12px 28px rgba(14, 116, 255, 0.18);
      border: 1px solid rgba(255,255,255,0.08);
    }
    #computerGrid {
      display: grid;
      grid-template-columns: repeat(8, minmax(0, 1fr));
      gap: 8px;
      max-height: 360px;
      overflow: auto;
      padding-bottom: 4px;
    }

    body {
      font-family: 'DM Sans', sans-serif;
      background: var(--bg);
      color: var(--text1);
      min-height: 100vh;
      overflow-x: hidden;
    }

    /* subtle noise texture on the body bg */
    body::before {
      content: '';
      position: fixed; inset: 0;
      background-image: url("data:image/svg+xml,%3Csvg viewBox='0 0 200 200' xmlns='http://www.w3.org/2000/svg'%3E%3Cfilter id='n'%3E%3CfeTurbulence type='fractalNoise' baseFrequency='0.85' numOctaves='4' stitchTiles='stitch'/%3E%3C/filter%3E%3Crect width='100%25' height='100%25' filter='url(%23n)' opacity='0.03'/%3E%3C/svg%3E");
      pointer-events: none;
      z-index: 0;
    }

    /* ── SCROLLBAR ── */
    ::-webkit-scrollbar { width: 4px; height: 4px; }
    ::-webkit-scrollbar-track { background: var(--bg); }
    ::-webkit-scrollbar-thumb { background: var(--surface3); border-radius: 4px; }
    ::-webkit-scrollbar-thumb:hover { background: var(--accent2); }

    /* ════════════════════════════════════════════════════════
       SIDEBAR LAYOUT
       The entire page is a 2-column grid: sidebar + main.
       On small screens the sidebar collapses to a top bar.
    ════════════════════════════════════════════════════════ */
    .layout {
      display: grid;
      grid-template-columns: 220px 1fr;
      grid-template-rows: 100vh;
      position: relative;
      z-index: 1;
    }

    /* ── SIDEBAR ── */
    .sidebar {
      background: var(--surface);
      border-right: 1px solid var(--border);
      display: flex;
      flex-direction: column;
      position: sticky;
      top: 0;
      height: 100vh;
      overflow-y: auto;
      overflow-x: hidden;
    }

    /* brand block at the top of the sidebar */
    .sidebar-brand {
      padding: 1.4rem 1.2rem 1rem;
      border-bottom: 1px solid var(--border);
      flex-shrink: 0;
    }
    .brand-logo {
      width: 36px; height: 36px;
      background: linear-gradient(135deg, var(--gold), var(--accent2));
      border-radius: 9px;
      display: flex; align-items: center; justify-content: center;
      font-family: 'Syne', sans-serif;
      font-size: 11px; font-weight: 800;
      color: var(--bg);
      box-shadow: 0 0 20px rgba(120, 21, 191, 0.35);
      flex-shrink: 0;
    }
    .brand-label {
      margin-top: .55rem;
      font-family: 'Syne', sans-serif;
      font-size: .72rem;
      font-weight: 700;
      color: var(--text2);
      letter-spacing: .04em;
      line-height: 1.3;
      text-transform: uppercase;
    }
    .brand-label strong {
      display: block;
      color: var(--text1);
      font-size: .82rem;
      letter-spacing: 0;
      text-transform: none;
    }

    /* admin badge in sidebar */
    .sidebar-admin {
      display: flex;
      align-items: center;
      gap: .6rem;
      padding: .75rem 1.2rem;
      border-bottom: 1px solid var(--border);
      flex-shrink: 0;
    }
    .admin-avatar {
      width: 32px; height: 32px;
      border-radius: 50%;
      background: linear-gradient(135deg, #2d1d3a, #1a0d2d);
      border: 1.5px solid var(--border2);
      display: flex; align-items: center; justify-content: center;
      font-size: .7rem; font-weight: 700; color: var(--accent);
    }
    .admin-meta { min-width: 0; }
    .admin-meta-name {
      font-size: .75rem; font-weight: 600; color: var(--text1);
      white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
    }
    .admin-meta-role {
      font-size: .65rem; color: var(--accent); font-weight: 600;
      text-transform: uppercase; letter-spacing: .05em;
    }

    /* nav list */
    .sidebar-nav {
      flex: 1;
      padding: .75rem 0;
      display: flex;
      flex-direction: column;
      gap: 2px;
    }
    .nav-section-label {
      font-size: .62rem;
      font-weight: 700;
      color: var(--text3);
      text-transform: uppercase;
      letter-spacing: .1em;
      padding: .6rem 1.2rem .3rem;
    }
    .nav-item {
      display: flex;
      align-items: center;
      gap: .7rem;
      padding: .5rem 1.2rem;
      cursor: pointer;
      border-radius: 0;
      font-size: .82rem;
      font-weight: 500;
      color: var(--text2);
      text-decoration: none;
      transition: all .15s;
      position: relative;
    }
    .nav-item i {
      width: 16px;
      text-align: center;
      font-size: .8rem;
      flex-shrink: 0;
      opacity: .65;
      transition: opacity .15s;
    }
    .nav-item:hover {
      background: var(--surface2);
      color: var(--text1);
    }
    .nav-item:hover i { opacity: 1; }
    .nav-item.active {
      background: rgba(204, 0, 255, 0.08);
      color: var(--accent);
      font-weight: 600;
    }
    .nav-item.active i { opacity: 1; color: var(--accent); }
    /* left accent bar on active nav item */
    .nav-item.active::before {
      content: '';
      position: absolute;
      left: 0; top: 0; bottom: 0;
      width: 3px;
      background: var(--accent);
      border-radius: 0 3px 3px 0;
      box-shadow: 0 0 12px rgba(153, 0, 255, 0.6);
    }

    /* logout button at bottom of sidebar */
    .sidebar-footer {
      padding: .85rem 1.2rem;
      border-top: 1px solid var(--border);
      flex-shrink: 0;
    }
    .btn-logout {
      width: 100%;
      display: flex; align-items: center; gap: .55rem;
      background: rgba(255,77,106,0.1);
      border: 1px solid rgba(255,77,106,0.2);
      border-radius: var(--radius);
      color: var(--red);
      font-size: .8rem; font-weight: 600;
      padding: .5rem .85rem;
      cursor: pointer;
      transition: all .15s;
    }
    .btn-logout:hover {
      background: rgba(255,77,106,0.18);
      border-color: rgba(255,77,106,0.4);
    }
    .btn-logout i { font-size: .75rem; }

    /* ── MAIN CONTENT AREA ── */
    .main {
      display: flex;
      flex-direction: column;
      overflow-y: auto;
      overflow-x: hidden;
      background: var(--bg);
    }

    /* top header bar inside main */
    .topbar {
      background: var(--surface);
      border-bottom: 1px solid var(--border);
      padding: .7rem 1.75rem;
      display: flex;
      align-items: center;
      justify-content: space-between;
      position: sticky;
      top: 0;
      z-index: 100;
      flex-shrink: 0;
    }
    .topbar-title {
      font-family: 'Syne', sans-serif;
      font-size: .9rem;
      font-weight: 700;
      color: var(--text1);
      display: flex; align-items: center; gap: .5rem;
    }
    .topbar-title i { color: var(--accent); font-size: .82rem; }

    /* hamburger for mobile */
    .topbar-menu-btn {
      display: none;
      background: none;
      border: 1px solid var(--border);
      border-radius: 7px;
      color: var(--text2);
      width: 34px; height: 34px;
      align-items: center; justify-content: center;
      cursor: pointer;
      font-size: .8rem;
    }

    /* search bar in topbar */
    .topbar-search {
      position: relative;
      cursor: pointer;
    }
    .topbar-search-btn {
      display: flex; align-items: center; gap: .5rem;
      background: var(--surface2);
      border: 1px solid var(--border);
      border-radius: 8px;
      padding: .38rem .85rem;
      font-size: .78rem;
      color: var(--text2);
      cursor: pointer;
      transition: all .15s;
      font-family: 'DM Sans', sans-serif;
    }
    .topbar-search-btn:hover { border-color: var(--border2); color: var(--text1); }
    .topbar-search-btn i { font-size: .72rem; }
    .topbar-search-kbd {
      background: var(--surface3);
      border: 1px solid var(--border);
      border-radius: 4px;
      padding: 0 .3rem;
      font-size: .65rem;
      color: var(--text3);
    }

    /* theme toggle button */
    .topbar-theme-btn {
      background: var(--surface2);
      border: 1px solid var(--border);
      border-radius: 8px;
      padding: .38rem .75rem;
      font-size: .78rem;
      color: var(--text2);
      cursor: pointer;
      transition: all .15s;
      font-family: 'DM Sans', sans-serif;
    }
    .topbar-theme-btn:hover { border-color: var(--border2); color: var(--text1); }
    .topbar-theme-btn i { font-size: .8rem; }

    /* content wrapper */
    .content-wrap {
      padding: 1.5rem 1.75rem;
      flex: 1;
    }

    /* ── VIEW TRANSITIONS ── */
    .view { display: none; animation: fadeUp .2s ease; }
    .view.active { display: block; }
    @keyframes fadeUp {
      from { opacity: 0; transform: translateY(6px); }
      to   { opacity: 1; transform: translateY(0); }
    }

    /* ── PAGE HEADER ── */
    .page-header {
      display: flex;
      align-items: center;
      justify-content: space-between;
      margin-bottom: 1.35rem;
      flex-wrap: wrap;
      gap: .6rem;
    }
    .page-header-left { display: flex; align-items: center; gap: .6rem; }
    .page-header-icon {
      width: 36px; height: 36px;
      border-radius: 9px;
      background: rgba(128, 0, 255, 0.1);
      border: 1px solid var(--border2);
      display: flex; align-items: center; justify-content: center;
      font-size: .8rem;
      color: var(--accent);
    }
    .page-header-title {
      font-family: 'Syne', sans-serif;
      font-size: 1.1rem;
      font-weight: 700;
      color: var(--text1);
    }
    .page-header-sub {
      font-size: .73rem;
      color: var(--text2);
      margin-top: 1px;
    }

    /* ════════════════════════════════════════════════════════
       STAT CARDS — 3-up grid on the home view
    ════════════════════════════════════════════════════════ */
    .stat-grid {
      display: grid;
      grid-template-columns: repeat(3, 1fr);
      gap: 14px;
      margin-bottom: 1.35rem;
    }
    .stat-card {
      background: var(--surface);
      border: 1px solid var(--border);
      border-radius: var(--radius-lg);
      padding: 1.15rem 1.25rem;
      position: relative;
      overflow: hidden;
      transition: transform .2s, box-shadow .2s;
    }
    .stat-card:hover {
      transform: translateY(-2px);
      box-shadow: var(--shadow);
    }
    /* glowing top edge line per card */
    .stat-card::before {
      content: '';
      position: absolute; top: 0; left: 0; right: 0;
      height: 2px;
    }
    .stat-card.s-blue::before  { background: linear-gradient(90deg, transparent, var(--blue), transparent); }
    .stat-card.s-cyan::before  { background: linear-gradient(90deg, transparent, var(--accent), transparent); }
    .stat-card.s-green::before { background: linear-gradient(90deg, transparent, var(--green), transparent); }

    /* subtle corner glow */
    .stat-card::after {
      content: '';
      position: absolute;
      top: -40px; right: -40px;
      width: 120px; height: 120px;
      border-radius: 50%;
      opacity: .06;
    }
    .stat-card.s-blue::after  { background: var(--blue); }
    .stat-card.s-cyan::after  { background: var(--accent); }
    .stat-card.s-green::after { background: var(--green); }

    .stat-row {
      display: flex;
      align-items: flex-start;
      justify-content: space-between;
    }
    .stat-icon {
      width: 40px; height: 40px;
      border-radius: 10px;
      display: flex; align-items: center; justify-content: center;
      font-size: .85rem;
      flex-shrink: 0;
    }
    .si-blue  { background: rgba(77,159,255,0.12); color: var(--blue); }
    .si-cyan  { background: rgba(0,212,255,0.12);  color: var(--accent); }
    .si-green { background: rgba(0,229,160,0.12);  color: var(--green); }

    .stat-label {
      font-size: .7rem;
      font-weight: 600;
      color: var(--text2);
      text-transform: uppercase;
      letter-spacing: .07em;
      margin-bottom: .35rem;
    }
    .stat-value {
      font-family: 'Syne', sans-serif;
      font-size: 2rem;
      font-weight: 800;
      color: var(--text1);
      line-height: 1;
    }
    .stat-trend {
      font-size: .68rem;
      color: var(--text3);
      margin-top: .3rem;
    }

    /* ════════════════════════════════════════════════════════
       CARD COMPONENT — generic container used everywhere
    ════════════════════════════════════════════════════════ */
    .a-card {
      background: var(--surface);
      border: 1px solid var(--border);
      border-radius: var(--radius-lg);
      overflow: hidden;
    }
    .a-card-header {
      background: var(--surface2);
      border-bottom: 1px solid var(--border);
      padding: .7rem 1.1rem;
      display: flex;
      align-items: center;
      gap: .5rem;
      font-size: .8rem;
      font-weight: 700;
      color: var(--text1);
    }
    .a-card-header i { color: var(--accent); font-size: .75rem; }
    .a-card-body { padding: 1.1rem; }

    /* ════════════════════════════════════════════════════════
       DATA TABLE
    ════════════════════════════════════════════════════════ */
    .a-table { width: 100%; border-collapse: collapse; font-size: .8rem; }
    .a-table thead tr { border-bottom: 1px solid var(--border); }
    .a-table th {
      background: var(--surface2);
      color: var(--text2);
      padding: .65rem .9rem;
      text-align: left;
      font-size: .68rem;
      font-weight: 700;
      text-transform: uppercase;
      letter-spacing: .08em;
      white-space: nowrap;
    }
    .a-table th:first-child { border-radius: 8px 0 0 8px; }
    .a-table th:last-child  { border-radius: 0 8px 8px 0; }
    .a-table td {
      padding: .6rem .9rem;
      border-bottom: 1px solid var(--border);
      color: var(--text1);
      vertical-align: middle;
    }
    .a-table tr:last-child td { border-bottom: none; }
    .a-table tr:hover td { background: rgba(255,255,255,0.02); }
    .a-table .no-data {
      text-align: center;
      color: var(--text3);
      font-style: italic;
      padding: 3rem;
    }

    /* ════════════════════════════════════════════════════════
       BUTTONS
    ════════════════════════════════════════════════════════ */
    .btn-a-primary {
      background: rgba(77,159,255,0.15);
      border: 1px solid rgba(77,159,255,0.3);
      color: var(--blue);
      border-radius: 7px; padding: .3rem .75rem;
      font-size: .75rem; font-weight: 600;
      cursor: pointer; transition: all .15s;
      display: inline-flex; align-items: center; gap: .3rem;
      font-family: 'DM Sans', sans-serif;
    }
    .btn-a-primary:hover { background: rgba(77,159,255,0.25); border-color: var(--blue); }

    .btn-a-danger {
      background: rgba(255,77,106,0.12);
      border: 1px solid rgba(255,77,106,0.25);
      color: var(--red);
      border-radius: 7px; padding: .3rem .75rem;
      font-size: .75rem; font-weight: 600;
      cursor: pointer; transition: all .15s;
      display: inline-flex; align-items: center; gap: .3rem;
      font-family: 'DM Sans', sans-serif;
    }
    .btn-a-danger:hover { background: rgba(255,77,106,0.22); border-color: var(--red); }

    .btn-a-success {
      background: rgba(0,229,160,0.12);
      border: 1px solid rgba(0,229,160,0.25);
      color: var(--green);
      border-radius: 7px; padding: .3rem .75rem;
      font-size: .75rem; font-weight: 600;
      cursor: pointer; transition: all .15s;
      display: inline-flex; align-items: center; gap: .3rem;
      font-family: 'DM Sans', sans-serif;
    }
    .btn-a-success:hover { background: rgba(0,229,160,0.22); border-color: var(--green); }

    .btn-a-accent {
      background: linear-gradient(135deg, var(--accent), var(--accent2));
      border: none;
      color: #000;
      border-radius: 8px; padding: .4rem 1rem;
      font-size: .8rem; font-weight: 700;
      cursor: pointer; transition: all .15s;
      display: inline-flex; align-items: center; gap: .35rem;
      font-family: 'DM Sans', sans-serif;
      box-shadow: 0 4px 16px rgba(0,212,255,0.25);
    }
    .btn-a-accent:hover { filter: brightness(1.08); transform: translateY(-1px); box-shadow: 0 6px 24px rgba(0,212,255,0.35); }

    /* ════════════════════════════════════════════════════════
       BADGES / PILLS
    ════════════════════════════════════════════════════════ */
    .badge-active {
      background: rgba(0,229,160,0.12);
      color: var(--green);
      border: 1px solid rgba(0,229,160,0.25);
      font-size: .65rem; padding: .2rem .6rem;
      border-radius: 20px; font-weight: 700;
      white-space: nowrap;
    }
    .badge-done {
      background: var(--surface3);
      color: var(--text3);
      border: 1px solid var(--border);
      font-size: .65rem; padding: .2rem .6rem;
      border-radius: 20px; font-weight: 600;
    }
    .badge-id {
      font-family: 'DM Sans', sans-serif;
      font-size: .72rem; font-weight: 700;
      background: var(--surface2);
      border: 1px solid var(--border);
      color: var(--accent);
      padding: 2px 8px;
      border-radius: 5px;
      letter-spacing: .02em;
    }
    .badge-course {
      background: rgba(77,159,255,0.1);
      color: var(--blue);
      font-size: .65rem; font-weight: 700;
      padding: 2px 8px; border-radius: 5px;
    }
    .badge-lab {
      background: rgba(0,212,255,0.1);
      color: var(--accent);
      font-size: .65rem; font-weight: 700;
      padding: 2px 8px; border-radius: 5px;
    }

    /* ════════════════════════════════════════════════════════
       TABLE TOOLBAR (search + entries)
    ════════════════════════════════════════════════════════ */
    .tbl-toolbar {
      display: flex;
      align-items: center;
      justify-content: space-between;
      margin-bottom: .85rem;
      flex-wrap: wrap;
      gap: .75rem;
    }
    .entries-ctrl {
      display: flex; align-items: center; gap: .45rem;
      font-size: .78rem; color: var(--text2);
    }
    .entries-ctrl select {
      background: var(--surface2);
      border: 1px solid var(--border);
      border-radius: 6px;
      color: var(--text1);
      padding: .25rem .5rem;
      font-size: .78rem;
      outline: none;
      font-family: 'DM Sans', sans-serif;
    }
    .report-toolbar-right {
      display: flex;
      align-items: center;
      justify-content: flex-end;
      gap: .75rem;
      flex-wrap: wrap;
    }
    .report-toolbar-right .date-filter,
    .report-toolbar-right .report-actions {
      display: flex;
      align-items: center;
      gap: .5rem;
      flex-wrap: wrap;
    }
    .report-toolbar-right .date-filter input,
    .report-toolbar-right .tbl-search-wrap input {
      min-width: 180px;
      width: 200px;
    }
    .report-toolbar-right .tbl-search-wrap {
      position: relative;
      min-width: 240px;
    }
    .tbl-search-wrap {
      position: relative;
    }
    .tbl-search-wrap input {
      background: var(--surface2);
      border: 1px solid var(--border);
      border-radius: 8px;
      color: var(--text1);
      font-family: 'DM Sans', sans-serif;
      font-size: .8rem;
      padding: .38rem .75rem .38rem 2rem;
      width: 100%;
      outline: none;
      transition: border-color .2s;
    }
    .tbl-search-wrap input::placeholder { color: var(--text3); }
    .tbl-search-wrap input:focus { border-color: var(--border2); }
    .tbl-search-wrap i {
      position: absolute;
      left: .65rem; top: 50%;
      transform: translateY(-50%);
      color: var(--text3); font-size: .72rem;
    }

    /* ── PAGINATION ── */
    .pg-wrap {
      display: flex;
      align-items: center;
      justify-content: space-between;
      font-size: .73rem;
      color: var(--text2);
      margin-top: .85rem;
      flex-wrap: wrap;
      gap: .5rem;
    }
    .pg-btn {
      border: 1px solid var(--border);
      background: var(--surface2);
      color: var(--text2);
      padding: .22rem .58rem;
      border-radius: 5px;
      cursor: pointer;
      font-size: .73rem;
      transition: all .15s;
      font-family: 'DM Sans', sans-serif;
    }
    .pg-btn:hover { border-color: var(--border2); color: var(--accent); }
    .pg-btn.active { background: var(--accent); color: #000; border-color: var(--accent); font-weight: 700; }

    /* ════════════════════════════════════════════════════════
       ANNOUNCEMENT COMPONENT
    ════════════════════════════════════════════════════════ */
    .ann-item {
      padding: .75rem .9rem;
      border-radius: 9px;
      background: var(--surface2);
      border-left: 3px solid var(--accent);
      margin-bottom: .55rem;
      animation: fadeUp .2s ease;
    }
    .ann-meta {
      display: flex; align-items: center; gap: .45rem;
      font-size: .68rem; color: var(--text3); font-weight: 600; margin-bottom: .3rem;
    }
    .ann-badge {
      background: rgba(0,212,255,0.12);
      color: var(--accent);
      border: 1px solid rgba(0,212,255,0.2);
      padding: 1px 7px; border-radius: 20px;
      font-size: .65rem; font-weight: 700;
    }
    .ann-text { font-size: .8rem; color: var(--text1); line-height: 1.5; }
    .ann-empty { color: var(--text3) !important; font-style: italic; }
    .ann-textarea {
      width: 100%;
      background: var(--surface2);
      border: 1px solid var(--border);
      border-radius: 9px;
      color: var(--text1);
      font-family: 'DM Sans', sans-serif;
      font-size: .82rem;
      padding: .7rem .9rem;
      resize: vertical;
      min-height: 75px;
      outline: none;
      transition: border-color .2s;
    }
    .ann-textarea::placeholder { color: var(--text3); }
    .ann-textarea:focus { border-color: var(--border2); }

    /* ════════════════════════════════════════════════════════
       SEARCH MODAL SPECIFIC STYLES
    ════════════════════════════════════════════════════════ */
    .search-input-wrap { position: relative; }
    .search-input-wrap i {
      position: absolute; left: .8rem; top: 50%;
      transform: translateY(-50%);
      color: var(--text3); font-size: .8rem;
    }
    .search-input-wrap input {
      width: 100%;
      background: var(--surface2);
      border: 1px solid var(--border);
      border-radius: 10px;
      color: var(--text1);
      font-family: 'DM Sans', sans-serif;
      font-size: .88rem;
      padding: .6rem .85rem .6rem 2.2rem;
      outline: none;
      transition: border-color .2s;
    }
    .search-input-wrap input::placeholder { color: var(--text3); }
    .search-input-wrap input:focus { border-color: var(--border2); box-shadow: 0 0 0 3px rgba(0,212,255,0.08); }

    .search-result-card {
      display: flex; align-items: center; gap: .75rem;
      padding: .7rem .9rem;
      border-radius: 9px;
      background: var(--surface2);
      border: 1px solid var(--border);
      margin-bottom: .45rem;
      cursor: pointer;
      transition: all .15s;
      animation: fadeUp .15s ease;
    }
    .search-result-card:hover { border-color: var(--border2); background: var(--surface3); }

    .src-avatar {
      width: 38px; height: 38px;
      border-radius: 50%;
      background: linear-gradient(135deg, #1a2540, #0d1d30);
      border: 1.5px solid var(--border2);
      display: flex; align-items: center; justify-content: center;
      font-weight: 700; font-size: 12px;
      color: var(--accent);
      flex-shrink: 0;
      text-transform: uppercase;
    }
    .src-info { flex: 1; min-width: 0; }
    .src-name { font-weight: 600; font-size: .82rem; color: var(--text1); }
    .src-meta {
      font-size: .7rem; color: var(--text3);
      display: flex; gap: 10px; margin-top: 2px; flex-wrap: wrap;
    }
    .src-session { font-size: .72rem; font-weight: 700; color: var(--accent); white-space: nowrap; }
    .search-hint {
      text-align: center; color: var(--text3);
      font-size: .82rem; padding: 1.5rem 0;
    }
    .search-hint i {
      font-size: 1.6rem; display: block;
      margin-bottom: .5rem; opacity: .25;
      color: var(--accent);
    }
    .loading-row {
      display: flex; align-items: center; justify-content: center;
      gap: 8px; padding: 1.5rem;
      color: var(--text3); font-size: .82rem;
    }
    .spinner {
      width: 16px; height: 16px;
      border: 2px solid var(--border);
      border-top-color: var(--accent);
      border-radius: 50%;
      animation: spin .6s linear infinite;
    }
    @keyframes spin { to { transform: rotate(360deg); } }

    /* ════════════════════════════════════════════════════════
       BOOTSTRAP MODAL DARK OVERRIDES
    ════════════════════════════════════════════════════════ */
    .modal-content {
      background: var(--surface);
      border: 1px solid var(--border);
      border-radius: var(--radius-xl);
      box-shadow: var(--shadow-lg);
    }
    .modal-header {
      background: var(--surface2);
      border-bottom: 1px solid var(--border);
      border-radius: var(--radius-xl) var(--radius-xl) 0 0;
      padding: .9rem 1.25rem;
    }
    .modal-title { font-size: .88rem; font-weight: 700; color: var(--text1); }
    .modal-title i { color: var(--accent); margin-right: .3rem; }
    .modal-header .btn-close { filter: invert(1) opacity(.4); }
    .modal-header .btn-close:hover { filter: invert(1) opacity(.8); }
    .modal-footer {
      background: var(--surface2);
      border-top: 1px solid var(--border);
      border-radius: 0 0 var(--radius-xl) var(--radius-xl);
      padding: .75rem 1.25rem;
    }
    .modal-body { padding: 1.25rem; }
    .form-label { font-size: .76rem; font-weight: 600; color: var(--text2); margin-bottom: .3rem; }
    .form-control, .form-select {
      background: var(--surface2) !important;
      border: 1px solid var(--border) !important;
      border-radius: 8px !important;
      color: var(--text1) !important;
      font-family: 'DM Sans', sans-serif !important;
      font-size: .83rem !important;
      padding: .45rem .8rem !important;
      outline: none !important;
      transition: border-color .2s !important;
    }
    .form-control::placeholder { color: var(--text3) !important; }
    .form-control:focus, .form-select:focus {
      border-color: var(--border2) !important;
      box-shadow: 0 0 0 3px rgba(0,212,255,0.08) !important;
    }
    .form-select option { background: var(--surface2); color: var(--text1); }

    /* modal buttons */
    .modal .btn-primary {
      background: linear-gradient(135deg, var(--accent), var(--accent2)) !important;
      border: none !important; border-radius: 8px !important;
      color: #000 !important; font-weight: 700 !important; font-size: .82rem;
    }
    .modal .btn-secondary {
      background: var(--surface3) !important;
      border: 1px solid var(--border) !important;
      color: var(--text2) !important;
      border-radius: 8px !important; font-size: .82rem;
    }
    .modal .btn-secondary:hover { color: var(--text1) !important; }
    .modal .btn-danger {
      background: linear-gradient(135deg, #c0182e, var(--red)) !important;
      border: none !important; border-radius: 8px !important;
      font-weight: 700 !important; font-size: .82rem;
    }
    .modal .btn-outline-secondary {
      background: transparent !important;
      border: 1px solid var(--border) !important;
      color: var(--text2) !important;
      border-radius: 8px !important; font-size: .82rem;
    }

    /* ════════════════════════════════════════════════════════
       EMPTY STATE
    ════════════════════════════════════════════════════════ */
    .empty-state {
      text-align: center; color: var(--text3);
      padding: 4rem 1rem;
    }
    .empty-state i {
      font-size: 2.2rem; display: block;
      margin-bottom: .75rem; opacity: .2;
      color: var(--accent);
    }
    .empty-state p { font-size: .85rem; }

    /* ════════════════════════════════════════════════════════
       CHART CONTAINER
    ════════════════════════════════════════════════════════ */
.chart-outer {
  display: flex;
  flex-direction: column;   /* stack chart + legend vertically */
  justify-content: center;
  align-items: center;
  padding: .5rem 0;
  max-height: none;         /* remove the height restriction */
  overflow: visible;        /* make sure nothing gets clipped */
}

#purposeChart {
  max-height: 220px;        /* constrain only the canvas, not the legend */
}

    /* Period buttons styling */
    .btn.btn-outline-secondary {
      background: var(--surface2);
      border: 1px solid var(--border);
      color: var(--text2);
      border-radius: 6px;
      padding: 0.4rem 0.8rem;
      font-size: 0.75rem;
      transition: all .15s;
    }
    .btn.btn-outline-secondary:hover {
      border-color: var(--border2);
      color: var(--text1);
    }
    .btn.btn-secondary {
      background: linear-gradient(135deg, var(--accent), var(--accent2));
      border: none;
      color: #000;
      border-radius: 6px;
      padding: 0.4rem 0.8rem;
      font-size: 0.75rem;
      font-weight: 600;
    }
    .btn.btn-secondary:hover {
      filter: brightness(1.08);
    }

    /* ════════════════════════════════════════════════════════
       LEADERBOARD
    ════════════════════════════════════════════════════════ */
    .leaderboard-card {
      background: var(--surface);
      border: 1px solid var(--border);
      border-radius: var(--radius-lg);
      overflow: hidden;
    }
    .leaderboard-header {
      background: var(--surface2);
      border-bottom: 1px solid var(--border);
      padding: .7rem 1.1rem;
      display: flex;
      align-items: center;
      gap: .5rem;
      font-size: .8rem;
      font-weight: 700;
      color: var(--text1);
    }
    .leaderboard-header i { color: var(--accent); font-size: .75rem; }
    .leaderboard-body { padding: 0; }

    .reward-section {
      margin-top: 1.5rem;
    }
    .reward-card {
      background: var(--surface);
      border: 1px solid var(--border);
      border-radius: var(--radius-lg);
      overflow: hidden;
      box-shadow: 0 12px 32px rgba(0,0,0,0.08);
    }
    .reward-card-header {
      background: var(--surface2);
      border-bottom: 1px solid var(--border);
      padding: .85rem 1.1rem;
      font-size: .9rem;
      font-weight: 700;
      color: var(--text1);
      display: flex;
      align-items: center;
      gap: .55rem;
    }
    .reward-card-body {
      padding: 1.25rem;
    }
    .reward-form {
      display: flex;
      flex-direction: column;
      gap: 1rem;
    }
    .reward-form label {
      font-size: .75rem;
      font-weight: 600;
      color: var(--text2);
      margin-bottom: .35rem;
      display: block;
    }
    .reward-form input {
      width: 100%;
      border-radius: 14px;
      border: 1px solid var(--border);
      background: var(--surface2);
      color: var(--text1);
      padding: .85rem 1rem;
      font-size: .92rem;
      transition: border-color .15s ease, box-shadow .15s ease;
    }
    .reward-form input:focus {
      outline: none;
      border-color: var(--accent);
      box-shadow: 0 0 0 4px rgba(78,26,116,0.08);
    }
    .reward-actions {
      display: flex;
      flex-wrap: wrap;
      gap: .75rem;
      margin-top: .5rem;
    }
    .reward-actions .btn {
      min-width: 140px;
      border-radius: 10px;
    }
    .reward-table {
      border-radius: 18px;
      overflow: hidden;
      border: 1px solid var(--border);
      background: var(--surface2);
    }
    .reward-table-head,
    .reward-table-row {
      display: grid;
      grid-template-columns: 0.55fr 1.9fr 1fr 0.8fr 1fr 1fr 0.95fr 1fr;
      gap: 1rem;
      align-items: center;
      padding: .85rem 1rem;
      font-size: .78rem;
    }
    .reward-table-head {
      background: var(--surface);
      color: var(--text3);
      text-transform: uppercase;
      letter-spacing: .08em;
      font-weight: 700;
      border-bottom: 1px solid var(--border);
    }
    .reward-table-row {
      border-bottom: 1px solid var(--border);
      background: transparent;
    }
    .reward-table-row:last-child { border-bottom: none; }
    .reward-rank {
      display: inline-flex;
      align-items: center;
      justify-content: center;
      width: 30px;
      height: 30px;
      border-radius: 10px;
      background: rgba(78,26,116,0.08);
      color: var(--accent);
      font-weight: 700;
    }
    .reward-action-btn {
      border-radius: 10px;
      padding: .4rem .7rem;
      font-size: .78rem;
      font-weight: 600;
      border: none;
      cursor: pointer;
      background: #0dae55;
      color: white;
      transition: transform .15s ease, filter .15s ease;
    }
    .reward-action-btn:hover { filter: brightness(1.05); transform: translateY(-1px); }
    .reward-name {
      display: flex;
      flex-direction: column;
      gap: .15rem;
    }
    .reward-name strong {
      font-size: .9rem;
      color: var(--text1);
    }
    .reward-name span {
      font-size: .72rem;
      color: var(--text2);
    }
    .reward-points {
      font-weight: 700;
      color: var(--text1);
    }
      display: flex;
      align-items: center;
      gap: .85rem;
      padding: .7rem 1.1rem;
      border-bottom: 1px solid var(--border);
      transition: background .15s;
    }
    .leaderboard-item:hover { background: rgba(0,212,255,0.04); }
    .leaderboard-item:last-child { border-bottom: none; }

    .leaderboard-rank {
      font-family: 'Syne', sans-serif;
      font-size: 1.2rem;
      font-weight: 800;
      color: var(--text2);
      width: 30px;
      text-align: center;
      min-width: 30px;
    }
    .leaderboard-rank.top-1 { color: #FFD700; }
    .leaderboard-rank.top-2 { color: #C0C0C0; }
    .leaderboard-rank.top-3 { color: #CD7F32; }

    .leaderboard-trophy {
      width: 32px;
      height: 32px;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 1.3rem;
      flex-shrink: 0;
      min-width: 32px;
      text-align: center;
    }

    .leaderboard-avatar {
      width: 36px;
      height: 36px;
      border-radius: 50%;
      background: linear-gradient(135deg, #2d1d3a, #1a0d2d);
      border: 1.5px solid var(--border2);
      display: flex;
      align-items: center;
      justify-content: center;
      font-weight: 700;
      font-size: .75rem;
      color: var(--accent);
      flex-shrink: 0;
      text-transform: uppercase;
    }

    .leaderboard-info {
      flex: 1;
      min-width: 0;
    }
    .leaderboard-name {
      font-weight: 600;
      font-size: .82rem;
      color: var(--text1);
      white-space: nowrap;
      overflow: hidden;
      text-overflow: ellipsis;
    }
    .leaderboard-id {
      font-size: .68rem;
      color: var(--text2);
      margin-top: 2px;
    }

    .leaderboard-stats {
      display: flex;
      align-items: center;
      gap: 1.2rem;
      flex-shrink: 0;
    }
    .leaderboard-stat {
      text-align: right;
    }
    .leaderboard-stat-label {
      font-size: .65rem;
      color: var(--text3);
      text-transform: uppercase;
      letter-spacing: .05em;
      font-weight: 600;
    }
    .leaderboard-stat-value {
      font-family: 'Syne', sans-serif;
      font-size: 1rem;
      font-weight: 800;
      color: var(--text1);
      margin-top: 2px;
    }

    .leaderboard-pic {
      width: 48px;
      height: 48px;
      border-radius: 50%;
      object-fit: cover;
      border: 2px solid var(--border2);
      flex-shrink: 0;
    }

    .leaderboard-course {
      font-size: .65rem;
      color: var(--accent);
      font-weight: 700;
      text-transform: uppercase;
      letter-spacing: .05em;
      margin-top: 3px;
    }

    /* Podium / Ladder for top 3 */
    .leaderboard-podium {
      display: flex;
      align-items: flex-end;
      justify-content: center;
      gap: 2rem;
      padding: 2rem 1.5rem 1.5rem;
      background: linear-gradient(135deg, rgba(0,212,255,0.06), rgba(217,0,255,0.06));
      border-bottom: 1px solid var(--border);
    }
    .podium-step {
      display: flex;
      flex-direction: column;
      align-items: center;
      gap: 1rem;
    }
    .podium-step.rank-2 { order: 1; }
    .podium-step.rank-1 { order: 2; }
    .podium-step.rank-3 { order: 3; }
    .podium-platform {
      display: flex;
      flex-direction: column;
      align-items: center;
      gap: .75rem;
    }
    .podium-card {
      text-align: center;
      background: var(--surface2);
      border: 1.5px solid var(--border);
      border-radius: var(--radius);
      padding: .9rem;
      min-width: 110px;
    }
    .podium-card.rank-1 {
      border-color: #FFD700;
      box-shadow: 0 0 20px rgba(255, 215, 0, 0.25);
    }
    .podium-card.rank-2 {
      border-color: #C0C0C0;
      box-shadow: 0 0 16px rgba(192, 192, 192, 0.20);
    }
    .podium-card.rank-3 {
      border-color: #CD7F32;
      box-shadow: 0 0 14px rgba(205, 127, 50, 0.20);
    }
    .podium-rank {
      font-family: 'Syne', sans-serif;
      font-size: 1.5rem;
      font-weight: 800;
      margin-bottom: .25rem;
    }
    .podium-rank.rank-1 { color: #FFD700; }
    .podium-rank.rank-2 { color: #C0C0C0; }
    .podium-rank.rank-3 { color: #CD7F32; }
    .podium-trophy {
      font-size: 2rem;
      margin-bottom: .35rem;
    }
    .podium-pic {
      width: 48px;
      height: 48px;
      border-radius: 50%;
      object-fit: cover;
      border: 2px solid var(--border2);
    }
    .podium-avatar {
      width: 48px;
      height: 48px;
      border-radius: 50%;
      background: linear-gradient(135deg, #2d1d3a, #1a0d2d);
      border: 2px solid var(--border2);
      display: flex;
      align-items: center;
      justify-content: center;
      font-weight: 700;
      font-size: .85rem;
      color: var(--accent);
    }
    .podium-name {
      font-weight: 700;
      font-size: .78rem;
      color: var(--text1);
      margin-bottom: .25rem;
      white-space: nowrap;
      overflow: hidden;
      text-overflow: ellipsis;
    }
    .podium-detail {
      font-size: .7rem;
      color: var(--text2);
      line-height: 1.4;
      margin-bottom: .25rem;
    }
    .podium-meta {
      display: flex;
      justify-content: space-between;
      gap: .5rem;
      margin-top: .75rem;
    }
    .podium-label {
      font-size: .62rem;
      text-transform: uppercase;
      letter-spacing: .05em;
      color: var(--text3);
      font-weight: 700;
      margin-bottom: .15rem;
    }
    .podium-points {
      font-family: 'Syne', sans-serif;
      font-size: .95rem;
      font-weight: 800;
    }
    .podium-points.rank-1 { color: #FFD700; }
    .podium-points.rank-2 { color: #C0C0C0; }
    .podium-points.rank-3 { color: #CD7F32; }
    .podium-step-indicator {
      width: 80px;
      height: 120px;
      background: linear-gradient(135deg, var(--surface2), var(--surface3));
      border: 1px solid var(--border);
      border-radius: var(--radius-sm) var(--radius-sm) 0 0;
      display: flex;
      align-items: flex-end;
      justify-content: center;
      padding-bottom: 1rem;
      font-weight: 700;
      font-size: 1.3rem;
    }
    .podium-step.rank-1 .podium-step-indicator { height: 140px; }
    .podium-step.rank-2 .podium-step-indicator { height: 120px; }
    .podium-step.rank-3 .podium-step-indicator { height: 100px; }
    .podium-step.rank-1 .podium-step-indicator { background: linear-gradient(135deg, rgba(255,215,0,0.1), rgba(255,215,0,0.05)); border-color: #FFD700; }
    .podium-step.rank-2 .podium-step-indicator { background: linear-gradient(135deg, rgba(192,192,192,0.1), rgba(192,192,192,0.05)); border-color: #C0C0C0; }
    .podium-step.rank-3 .podium-step-indicator { background: linear-gradient(135deg, rgba(205,127,50,0.1), rgba(205,127,50,0.05)); border-color: #CD7F32; }
    .podium-step.rank-1 .podium-step-indicator { color: #FFD700; }
    .podium-step.rank-2 .podium-step-indicator { color: #C0C0C0; }
    .podium-step.rank-3 .podium-step-indicator { color: #CD7F32; }

    .leaderboard-rest {
      padding: 0;
    }

    /* ════════════════════════════════════════════════════════
       REST PLAYERS TABLE STYLING (Rank 4+)
    ════════════════════════════════════════════════════════ */
    .rest-players-section {
      padding: 1.5rem;
      background: var(--surface);
    }
    .rest-players-table {
      width: 100%;
      border-collapse: collapse;
      table-layout: fixed;
      border-radius: var(--radius);
      overflow: hidden;
      border: 1px solid var(--border);
      background: var(--surface2);
    }
    .rest-table-head {
      background: linear-gradient(135deg, rgba(0,212,255,0.08), rgba(217,0,255,0.08));
      border-bottom: 2px solid var(--border);
    }
    .rest-table-head-cell {
      padding: .85rem 1rem;
      font-size: .78rem;
      font-weight: 700;
      color: var(--text3);
      text-transform: uppercase;
      letter-spacing: .08em;
      text-align: left;
      word-break: break-word;
      white-space: nowrap;
    }
    .rest-table-head-cell:nth-child(1) { width: 8%; text-align: center; }
    .rest-table-head-cell:nth-child(2) { width: 6%; text-align: center; }
    .rest-table-head-cell:nth-child(3) { width: 20%; }
    .rest-table-head-cell:nth-child(4) { width: 12%; text-align: center; }
    .rest-table-head-cell:nth-child(5) { width: 25%; text-align: center; }
    .rest-table-head-cell:nth-child(6) { width: 12%; text-align: center; }
    .rest-table-head-cell:nth-child(7) { width: 12%; text-align: center; }
    .rest-table-cell {
      padding: 1rem;
      font-size: .82rem;
      color: var(--text1);
      text-align: left;
      vertical-align: middle;
      word-break: break-word;
    }
    .rest-table-cell:nth-child(1) { width: 5%; text-align: center; }
    .rest-table-cell:nth-child(2) { width: 6%; text-align: center; }
    .rest-table-cell:nth-child(3) { width: 20%; }
    .rest-table-cell:nth-child(4) { width: 12%; text-align: center; }
    .rest-table-cell:nth-child(5) { width: 25%; text-align: center; }
    .rest-table-cell:nth-child(6) { width: 12%; text-align: center; }
    .rest-table-cell:nth-child(7) { width: 12%; text-align: center; }
    .rest-table-row {
      border-bottom: 1px solid var(--border);
      transition: background-color .2s ease;
    }
    .rest-table-row:hover {
      background: rgba(0, 212, 255, 0.04);
    }
    .rest-table-row:last-child {
      border-bottom: none;
    }
    .rest-table-cell-center {
      text-align: center;
    }
    .rest-table-rank {
      font-weight: 800;
      font-family: 'Syne', sans-serif;
      font-size: 1rem;
      color: var(--accent);
    }
    .rest-table-rank.rank-4,
    .rest-table-rank.rank-5,
    .rest-table-rank.rank-6 {
      color: var(--text2);
    }
    .rest-table-avatar {
      width: 40px;
      height: 40px;
      border-radius: 50%;
      object-fit: cover;
      border: 1.5px solid var(--border2);
    }
    .rest-table-avatar-placeholder {
      width: 40px;
      height: 40px;
      border-radius: 50%;
      background: linear-gradient(135deg, #2d1d3a, #1a0d2d);
      border: 1.5px solid var(--border2);
      display: flex;
      align-items: center;
      justify-content: center;
      font-weight: 700;
      font-size: .75rem;
      color: var(--accent);
      text-transform: uppercase;
      margin: 0 auto;
    }
    .rest-table-player-info {
      display: flex;
      flex-direction: column;
      gap: .25rem;
      min-width: 0;
    }
    .rest-table-player-name {
      font-weight: 600;
      color: var(--text1);
      white-space: nowrap;
      overflow: hidden;
      text-overflow: ellipsis;
      font-size: .85rem;
    }
    .rest-table-player-meta {
      font-size: .72rem;
      color: var(--text3);
      white-space: nowrap;
      overflow: hidden;
      text-overflow: ellipsis;
    }
    .rest-table-stat {
      font-weight: 600;
      color: var(--text1);
      font-size: .85rem;
    }
    .rest-table-stat.points {
      color: var(--accent);
      font-family: 'Syne', sans-serif;
      font-weight: 800;
    }
    .empty-rest-players {
      padding: 2rem 1rem;
      text-align: center;
      color: var(--text2);
      font-size: .85rem;
    }

    /* ════════════════════════════════════════════════════════
       TOAST
    ════════════════════════════════════════════════════════ */
    #adminToast {
      border-radius: 10px !important;
      font-size: .82rem !important;
      font-weight: 600 !important;
      font-family: 'DM Sans', sans-serif;
    }

    /* ════════════════════════════════════════════════════════
       MOBILE / RESPONSIVE
    ════════════════════════════════════════════════════════ */
    @media (max-width: 900px) {
      .stat-grid { grid-template-columns: repeat(2,1fr); }
    }
    @media (max-width: 768px) {
      /* stack layout vertically on mobile */
      .layout {
        grid-template-columns: 1fr;
        grid-template-rows: auto 1fr;
      }
      .sidebar {
        position: fixed;
        left: -240px;
        top: 0; bottom: 0;
        width: 240px;
        z-index: 500;
        transition: left .25s ease;
        height: 100vh;
      }
      .sidebar.open { left: 0; box-shadow: 4px 0 30px rgba(0,0,0,0.6); }
      .topbar-menu-btn { display: flex; }
      .content-wrap { padding: 1rem; }
    }
    @media (max-width: 480px) {
      .stat-grid { grid-template-columns: 1fr; }
      .tbl-search-wrap input { width: 150px; }
    }

    /* sidebar overlay backdrop on mobile */
    .sidebar-backdrop {
      display: none;
      position: fixed; inset: 0;
      background: rgba(0,0,0,0.55);
      z-index: 499;
    }
    .sidebar-backdrop.show { display: block; }

    /* ════════════════════════════════════════════════════════
       TOGGLE SWITCH STYLING
    ════════════════════════════════════════════════════════ */
    .toggle-switch {
      display: inline-flex;
      align-items: center;
      gap: 0.75rem;
    }
    .toggle-switch label {
      margin: 0;
      cursor: pointer;
    }
    .toggle-switch input[type="checkbox"] {
      appearance: none;
      width: 50px;
      height: 28px;
      background: var(--surface2);
      border: 1px solid var(--border);
      border-radius: 20px;
      cursor: pointer;
      position: relative;
      transition: background-color .3s ease;
      outline: none;
    }
    .toggle-switch input[type="checkbox"]::before {
      content: '';
      position: absolute;
      width: 24px;
      height: 24px;
      border-radius: 50%;
      background: var(--text1);
      top: 2px;
      left: 2px;
      transition: left .3s ease;
      box-shadow: 0 2px 4px rgba(0,0,0,0.2);
    }
    .toggle-switch input[type="checkbox"]:checked {
      background: linear-gradient(135deg, var(--accent), var(--accent2));
      border-color: var(--accent2);
    }
    .toggle-switch input[type="checkbox"]:checked::before {
      left: 24px;
    }
    .toggle-switch input[type="checkbox"]:focus {
      box-shadow: 0 0 0 3px rgba(0,212,255,0.1);
    }

    /* Settings Card */
    .settings-card {
      background: var(--surface);
      border: 1px solid var(--border);
      border-radius: var(--radius-lg);
      padding: 1.2rem;
      margin-bottom: 1.35rem;
    }
    .settings-card-title {
      font-size: 0.85rem;
      font-weight: 700;
      color: var(--text1);
      margin-bottom: 0.5rem;
      display: flex;
      align-items: center;
      gap: 0.5rem;
    }
    .settings-card-title i {
      color: var(--accent);
      font-size: 0.8rem;
    }
    .settings-card-description {
      font-size: 0.75rem;
      color: var(--text3);
      margin-top: 0.25rem;
    }
  </style>
</head>
<body>

<!-- ════════════════════════════════════════════════════════
     PAGE LAYOUT — sidebar + main column
════════════════════════════════════════════════════════ -->
<div class="layout">

  <!-- ── SIDEBAR ── -->
  <aside class="sidebar" id="sidebar">
    <!-- Brand -->
    <div class="sidebar-brand">
      <div class="brand-logo">CCS</div>
      <div class="brand-label">
        <strong>Sit-in Monitoring</strong>
        Admin Panel
      </div>
    </div>

    <!-- Admin identity -->
    <div class="sidebar-admin">
      <div class="admin-avatar">AD</div>
      <div class="admin-meta">
        <div class="admin-meta-name">CCS Administrator</div>
        <div class="admin-meta-role">Admin</div>
      </div>
    </div>

    <!-- Navigation links -->
    <nav class="sidebar-nav">
      <div class="nav-section-label">Overview</div>
      <a class="nav-item active" id="nav-home"        onclick="showView('home')">
        <i class="fa-solid fa-house-chimney"></i> Home
      </a>
      <a class="nav-item"        id="nav-leaderboard" onclick="showView('leaderboard')">
        <i class="fa-solid fa-trophy"></i> Leaderboard
      </a>
      <a class="nav-item"        id="nav-search"      onclick="openSearch()">
        <i class="fa-solid fa-magnifying-glass"></i> Search Student
      </a>

      <div class="nav-section-label">Management</div>
      <a class="nav-item"        id="nav-students"    onclick="showView('students')">
        <i class="fa-solid fa-users"></i> Students
      </a>
      <a class="nav-item"        id="nav-sitin"       onclick="showView('current-sitin')">
        <i class="fa-solid fa-chair"></i> Current Sit-in
      </a>
      <a class="nav-item"        id="nav-records"     onclick="showView('sitin-records')">
        <i class="fa-solid fa-table-list"></i> Sit-in Records
      </a>

      <div class="nav-section-label">Reports</div>
      <a class="nav-item"        id="nav-reports"     onclick="showView('reports')">
        <i class="fa-solid fa-chart-bar"></i> Sit-in Reports
      </a>
      <a class="nav-item"        id="nav-analytics"   onclick="showView('analytics')">
        <i class="fa-solid fa-chart-line"></i> Analytics & Reports
      </a>
      <a class="nav-item"        id="nav-feedback"    onclick="showView('feedback')">
        <i class="fa-solid fa-comment-dots"></i> Feedback
      </a>
      <a class="nav-item"        id="nav-reservation" onclick="showView('reservation')">
        <i class="fa-solid fa-calendar-check"></i> Reservation
      </a>

      <div class="nav-section-label">Lab Software</div>
      <a class="nav-item"        id="nav-software"    onclick="showView('software')">
        <i class="fa-solid fa-download"></i> Software Import/Upload
      </a>

      <div class="nav-section-label">System</div>
      <a class="nav-item"        id="nav-settings"    onclick="showView('settings')">
        <i class="fa-solid fa-gear"></i> Settings
      </a>
    </nav>

    <!-- Logout -->
    <div class="sidebar-footer">
      <button class="btn-logout" onclick="confirmLogout()">
        <i class="fa-solid fa-right-from-bracket"></i> Log Out
      </button>
    </div>
  </aside>

  <!-- Mobile sidebar backdrop -->
  <div class="sidebar-backdrop" id="sidebarBackdrop" onclick="closeSidebar()"></div>

  <!-- ── MAIN ── -->
  <main class="main">

    <!-- Top bar -->
    <div class="topbar">
      <div style="display:flex;align-items:center;gap:.75rem;">
        <!-- hamburger only visible on mobile -->
        <button class="topbar-menu-btn" onclick="toggleSidebar()">
          <i class="fa-solid fa-bars"></i>
        </button>
        <div class="topbar-title" id="topbarTitle">
          <i class="fa-solid fa-house-chimney"></i>
          <span>Home</span>
        </div>
      </div>
      <!-- Right side: search and theme -->
      <div style="display:flex;align-items:center;gap:.5rem;">
        <!-- Quick-search shortcut in topbar -->
        <button class="topbar-search-btn" onclick="openSearch()">
          <i class="fa-solid fa-magnifying-glass"></i>
          Search student…
          <span class="topbar-search-kbd">⌘K</span>
        </button>
        <!-- Theme toggle -->
        <button class="topbar-theme-btn" id="themeToggle" onclick="toggleTheme()">
          <i class="fa-solid fa-sun"></i>
        </button>
      </div>
    </div>

    <!-- ── VIEWS ── -->
    <div class="content-wrap">

      <!-- ████ HOME ████ -->
      <div class="view active" id="view-home">

        <!-- Stat cards row -->
        <div class="stat-grid">
          <div class="stat-card s-blue">
            <div class="stat-row">
              <div>
                <div class="stat-label">Students Registered</div>
                <div class="stat-value" id="statRegistered">—</div>
                <div class="stat-trend">Total enrolled students</div>
              </div>
              <div class="stat-icon si-blue"><i class="fa-solid fa-users"></i></div>
            </div>
          </div>
          <div class="stat-card s-cyan">
            <div class="stat-row">
              <div>
                <div class="stat-label">Currently Sitting In</div>
                <div class="stat-value" id="statCurrent">—</div>
                <div class="stat-trend">Active sessions right now</div>
              </div>
              <div class="stat-icon si-cyan"><i class="fa-solid fa-chair"></i></div>
            </div>
          </div>
          <div class="stat-card s-green">
            <div class="stat-row">
              <div>
                <div class="stat-label">Total Sit-in</div>
                <div class="stat-value" id="statTotal">—</div>
                <div class="stat-trend">All-time sessions</div>
              </div>
              <div class="stat-icon si-green"><i class="fa-solid fa-circle-check"></i></div>
            </div>
          </div>
        </div>

        <!-- Chart + Announcement row -->
        <div class="row g-3">
          <div class="col-lg-6">
            <div class="a-card h-100">
              <div class="a-card-header">
                <i class="fa-solid fa-chart-pie"></i> Language / Purpose Distribution
              </div>
              <div class="a-card-body">
                <div class="chart-outer"><canvas id="purposeChart"></canvas></div>
              </div>
            </div>
          </div>
          <div class="col-lg-6">
            <div class="a-card h-100">
              <div class="a-card-header">
                <i class="fa-solid fa-bullhorn"></i> Announcements
              </div>
              <div class="a-card-body d-flex flex-column gap-3">
                <!-- Post new announcement -->
                <div>
                  <textarea class="ann-textarea" id="annText" placeholder="Write a new announcement…"></textarea>
                  <button class="btn-a-success mt-2" onclick="postAnnouncement()">
                    <i class="fa-solid fa-paper-plane"></i> Post Announcement
                  </button>
                </div>
                <!-- Posted list -->
                <div>
                  <div style="font-size:.68rem;font-weight:700;color:var(--text3);text-transform:uppercase;letter-spacing:.08em;margin-bottom:.6rem;">Posted</div>
                  <div id="annList">
                    <!-- Announcements will be loaded here -->
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>

      </div><!-- /view-home -->

      <!-- ████ LEADERBOARD ████ -->
      <div class="view" id="view-leaderboard">
        <div class="page-header">
          <div class="page-header-left">
            <div class="page-header-icon"><i class="fa-solid fa-trophy"></i></div>
            <div>
              <div class="page-header-title">Top Sitters Leaderboard</div>
              <div class="page-header-sub">Students ranked by sit-in sessions (10 points per session)</div>
            </div>
          </div>
        </div>
        <div class="leaderboard-card">
          <div class="leaderboard-header">
            <i class="fa-solid fa-trophy"></i> Top Sitters
          </div>
          <div class="leaderboard-body" id="leaderboardBody">
            <!-- Leaderboard items will be loaded here -->
          </div>
        </div>

        <div class="reward-section">
          <div class="row g-4">
            <div class="col-lg-4">
              <div class="reward-card">
                <div class="reward-card-header"><i class="fa-solid fa-star"></i> Award Points to Student</div>
                <div class="reward-card-body">
                  <div class="reward-form">
                    <div>
                      <label for="rewardStudentId">Student ID Number</label>
                      <input id="rewardStudentId" type="text" placeholder="e.g. 20000014" />
                    </div>
                    <div>
                      <label for="rewardPoints">Points to Add</label>
                      <input id="rewardPoints" type="number" placeholder="10" />
                    </div>
                    <div>
                      <label for="rewardReason">Reason</label>
                      <input id="rewardReason" type="text" placeholder="e.g. Perfect attendance" />
                    </div>
                  </div>
                  <div class="reward-actions">
                    <button class="btn btn-success" onclick="awardPointsToStudent()">Add Points</button>
                  </div>
                </div>
              </div>
            </div>
            <div class="col-lg-8">
              <div class="reward-card">
                <div class="reward-card-header"><i class="fa-solid fa-medal"></i> Points Leaderboard</div>
                <div class="reward-card-body">
                  <div class="reward-table">
                    <div class="reward-table-head">
                      <div>Rank</div>
                      <div>Student</div>
                      <div>Course</div>
                      <div>Sit-Ins</div>
                      <div>Total Hrs</div>
                      <div>Avg Session</div>
                      <div>Points</div>
                      <div>Action</div>
                    </div>
                    <div id="rewardTableBody">
                      <div class="reward-table-row">
                        <div class="reward-rank">—</div>
                        <div class="reward-name"><strong>Loading...</strong><span></span></div>
                        <div>—</div>
                        <div>—</div>
                        <div>—</div>
                        <div>—</div>
                        <div class="reward-points">—</div>
                        <div><button class="reward-action-btn" disabled>+ Points</button></div>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div><!-- /view-leaderboard -->

      <!-- ████ STUDENTS ████ -->
      <div class="view" id="view-students">
        <div class="page-header">
          <div class="page-header-left">
            <div class="page-header-icon"><i class="fa-solid fa-users"></i></div>
            <div>
              <div class="page-header-title">Students Information</div>
              <div class="page-header-sub">Manage enrolled student records</div>
            </div>
          </div>
          <div style="display:flex;gap:.5rem;">
            <button class="btn-a-accent" onclick="openAddStudent()">
              <i class="fa-solid fa-user-plus"></i> Add Student
            </button>
            <button class="btn-a-danger" onclick="confirmResetAll()">
              <i class="fa-solid fa-rotate"></i> Reset Sessions
            </button>
          </div>
        </div>
        <div class="a-card">
          <div class="a-card-body">
            <div class="tbl-toolbar">
              <div class="entries-ctrl">
                Show
                <select id="stuEntries" onchange="renderStudents()">
                  <option>10</option><option>25</option><option>50</option>
                </select>
                entries
              </div>
              <div class="tbl-search-wrap">
                <i class="fa-solid fa-magnifying-glass"></i>
                <input type="text" id="stuSearch" placeholder="Search students…" oninput="renderStudents()"/>
              </div>
            </div>
            <div class="table-responsive">
              <table class="a-table">
                <thead>
                  <tr>
                    <th>ID Number</th>
                    <th>Name</th>
                    <th>Year</th>
                    <th>Course</th>
                    <th>Sessions Left</th>
                    <th>Actions</th>
                  </tr>
                </thead>
                <tbody id="stuBody"></tbody>
              </table>
            </div>
            <div class="pg-wrap">
              <span id="stuInfo" style="color:var(--text3);">Showing 0 entries</span>
              <div id="stuPagination" style="display:flex;gap:.25rem;flex-wrap:wrap;"></div>
            </div>
          </div>
        </div>
      </div><!-- /view-students -->

      <!-- ████ CURRENT SIT-IN ████ -->
      <div class="view" id="view-current-sitin">
        <div class="page-header">
          <div class="page-header-left">
            <div class="page-header-icon"><i class="fa-solid fa-chair"></i></div>
            <div>
              <div class="page-header-title">Current Sit-in</div>
              <div class="page-header-sub">Active sessions in progress</div>
            </div>
          </div>
          <button class="btn-a-accent" onclick="openSitInModal()">
            <i class="fa-solid fa-plus"></i> New Sit-in
          </button>
        </div>
        <div class="a-card">
          <div class="a-card-body">
            <div class="tbl-toolbar">
              <div class="entries-ctrl">
                Show
                <select id="curEntries" onchange="renderCurrentSitIn()">
                  <option>10</option><option>25</option><option>50</option>
                </select>
                entries
              </div>
              <div class="tbl-search-wrap">
                <i class="fa-solid fa-magnifying-glass"></i>
                <input type="text" id="curSearch" placeholder="Search…" oninput="renderCurrentSitIn()"/>
              </div>
            </div>
            <div class="table-responsive">
              <table class="a-table">
                <thead>
                  <tr>
                    <th>Sit ID</th><th>ID Number</th><th>Name</th>
                    <th>Purpose</th><th>Lab</th><th>Session</th>
                    <th>Status</th><th>Actions</th>
                  </tr>
                </thead>
                <tbody id="curBody"></tbody>
              </table>
            </div>
            <div class="pg-wrap">
              <span id="curInfo" style="color:var(--text3);">Showing 0 entries</span>
              <div id="curPagination" style="display:flex;gap:.25rem;flex-wrap:wrap;"></div>
            </div>
          </div>
        </div>
      </div><!-- /view-current-sitin -->

      <!-- ████ SIT-IN RECORDS ████ -->
      <div class="view" id="view-sitin-records">
        <div class="page-header">
          <div class="page-header-left">
            <div class="page-header-icon"><i class="fa-solid fa-table-list"></i></div>
            <div>
              <div class="page-header-title">All Sit-in Records</div>
              <div class="page-header-sub">Complete history of all sessions</div>
            </div>
          </div>
        </div>
        <div class="a-card">
          <div class="a-card-body">
            <div class="tbl-toolbar">
              <div class="entries-ctrl">
                Show
                <select id="recEntries" onchange="renderRecords()">
                  <option>10</option><option>25</option><option>50</option>
                </select>
                entries
              </div>
              <div class="tbl-search-wrap">
                <i class="fa-solid fa-magnifying-glass"></i>
                <input type="text" id="recSearch" placeholder="Search records…" oninput="renderRecords()"/>
              </div>
            </div>
            <div class="table-responsive">
              <table class="a-table">
                <thead>
                  <tr>
                    <th>Sit ID</th><th>ID Number</th><th>Name</th>
                    <th>Purpose</th><th>Lab</th><th>Session</th>
                    <th>Status</th>
                  </tr>
                </thead>
                <tbody id="recBody"></tbody>
              </table>
            </div>
            <div class="pg-wrap">
              <span id="recInfo" style="color:var(--text3);">Showing 0 entries</span>
              <div id="recPagination" style="display:flex;gap:.25rem;flex-wrap:wrap;"></div>
            </div>
          </div>
        </div>
      </div><!-- /view-sitin-records -->

      <!-- ████ REPORTS ████ -->
      <div class="view" id="view-reports">
        <div class="page-header">
          <div class="page-header-left">
            <div class="page-header-icon"><i class="fa-solid fa-chart-bar"></i></div>
            <div>
              <div class="page-header-title">Sit-in Reports</div>
              <div class="page-header-sub">Generate printable sit-in history and export data</div>
            </div>
          </div>
        </div>

        <!-- Charts Row -->
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 24px;">
          <!-- Languages Chart -->
          <div class="a-card">
            <div class="a-card-body" style="padding: 20px;">
              <h5 style="margin-bottom: 16px; color: var(--text1); font-weight: 600;">Programming Languages Used</h5>
              <canvas id="languagesChart" style="max-height: 300px;"></canvas>
            </div>
          </div>

          <!-- Rooms Chart -->
          <div class="a-card">
            <div class="a-card-body" style="padding: 20px;">
              <h5 style="margin-bottom: 16px; color: var(--text1); font-weight: 600;">Most Used Rooms/Labs</h5>
              <canvas id="roomsChart" style="max-height: 300px;"></canvas>
            </div>
          </div>
        </div>

        <div class="a-card">
          <div class="a-card-body">
            <div class="tbl-toolbar report-toolbar">
              <div class="entries-ctrl">
                Show
                <select id="reportEntries" onchange="renderReports()">
                  <option>10</option><option>25</option><option>50</option>
                </select>
                entries
              </div>
              <div class="report-toolbar-right">
                <div class="date-filter">
                  <input type="text" id="reportDate" placeholder="mm/dd/yyyy" class="form-control form-control-sm" />
                  <button class="btn btn-sm btn-primary" onclick="handleReportDate()">Search</button>
                  <button class="btn btn-sm btn-danger" onclick="resetReportDate()">Reset</button>
                </div>
                <div class="tbl-search-wrap">
                  <i class="fa-solid fa-magnifying-glass"></i>
                  <input type="text" id="reportSearch" placeholder="    Filter ID or Name" class="form-control form-control-sm" oninput="renderReports()" />
                </div>
                <div class="report-actions">
                  <button class="btn btn-sm btn-outline-success" onclick="exportReportsCSV()">CSV</button>
                  <button class="btn btn-sm btn-outline-secondary" onclick="exportReportsExcel()">Excel</button>
                  <button class="btn btn-sm btn-outline-primary" onclick="exportReportsPDF()">PDF</button>
                  <button class="btn btn-sm btn-outline-dark" onclick="printReports()">Print</button>
                </div>
              </div>
            </div>
            <div class="table-responsive">
              <table class="a-table" id="reportTable">
                <thead>
                  <tr>
                    <th>ID Number</th><th>Name</th><th>Purpose</th>
                    <th>Laboratory</th><th>Login</th><th>Logout</th><th>Date</th>
                  </tr>
                </thead>
                <tbody id="reportBody"></tbody>
              </table>
            </div>
            <div class="pg-wrap">
              <span id="reportInfo" style="color:var(--text3);">Showing 0 entries</span>
              <div id="reportPagination" style="display:flex;gap:.25rem;flex-wrap:wrap;"></div>
            </div>
          </div>
        </div>
      </div>

      <!-- ████ ANALYTICS & REPORTS ████ -->
      <div class="view" id="view-analytics">
        <div class="page-header">
          <div class="page-header-left">
            <div class="page-header-icon"><i class="fa-solid fa-chart-line"></i></div>
            <div>
              <div class="page-header-title">Analytics & Reports</div>
              <div class="page-header-sub">Comprehensive statistics and sit-in insights</div>
            </div>
          </div>
        </div>

        <!-- Stat Cards Row -->
        <div class="stat-grid" style="margin-bottom: 1.35rem;">
          <div class="stat-card s-blue">
            <div class="stat-row">
              <div>
                <div class="stat-label">Registered</div>
                <div class="stat-value" id="analyticsRegistered">—</div>
                <div class="stat-trend">Total students</div>
              </div>
              <div class="stat-icon si-blue"><i class="fa-solid fa-users"></i></div>
            </div>
          </div>
          <div class="stat-card s-cyan">
            <div class="stat-row">
              <div>
                <div class="stat-label">Total Sit-ins</div>
                <div class="stat-value" id="analyticsTotalSitins">—</div>
                <div class="stat-trend">All-time sessions</div>
              </div>
              <div class="stat-icon si-cyan"><i class="fa-solid fa-chair"></i></div>
            </div>
          </div>
          <div class="stat-card s-green">
            <div class="stat-row">
              <div>
                <div class="stat-label">Active Now</div>
                <div class="stat-value" id="analyticsActive">—</div>
                <div class="stat-trend">Currently sitting</div>
              </div>
              <div class="stat-icon si-green"><i class="fa-solid fa-circle-check"></i></div>
            </div>
          </div>
        </div>

        <!-- Charts Row -->
        <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 1.35rem; margin-bottom: 1.35rem;">
          <!-- Sit-ins Over Time Chart -->
          <div class="a-card">
            <div class="a-card-header">
              <i class="fa-solid fa-chart-line"></i> Sit-Ins Over Time
            </div>
            <div class="a-card-body">
              <div style="display: flex; gap: 0.5rem; margin-bottom: 1rem; justify-content: center;">
                <button class="btn btn-sm btn-outline-secondary" onclick="loadAnalyticsData('daily')" id="btn-period-daily" style="border-radius: 6px; padding: 0.4rem 0.8rem; font-size: 0.75rem;">Daily</button>
                <button class="btn btn-sm btn-outline-secondary" onclick="loadAnalyticsData('weekly')" id="btn-period-weekly" style="border-radius: 6px; padding: 0.4rem 0.8rem; font-size: 0.75rem;">Weekly</button>
                <button class="btn btn-sm btn-outline-secondary" onclick="loadAnalyticsData('monthly')" id="btn-period-monthly" style="border-radius: 6px; padding: 0.4rem 0.8rem; font-size: 0.75rem;">Monthly</button>
              </div>
              <div class="chart-outer"><canvas id="analyticsTimeChart"></canvas></div>
            </div>
          </div>

          <!-- By Purpose Chart -->
          <div class="a-card">
            <div class="a-card-header">
              <i class="fa-solid fa-tag"></i> By Purpose
            </div>
            <div class="a-card-body">
              <div class="chart-outer"><canvas id="analyticsPurposeChart"></canvas></div>
            </div>
          </div>

          <!-- By Lab Chart -->
          <div class="a-card">
            <div class="a-card-header">
              <i class="fa-solid fa-building"></i> By Lab
            </div>
            <div class="a-card-body">
              <div class="chart-outer"><canvas id="analyticsLabChart"></canvas></div>
            </div>
          </div>
        </div>
      </div>

      <!-- ████ FEEDBACK ████ -->
      <div class="view" id="view-feedback">
        <div class="page-header">
          <div class="page-header-left">
            <div class="page-header-icon"><i class="fa-solid fa-comments"></i></div>
            <div>
              <div class="page-header-title">Feedback Reports</div>
              <div class="page-header-sub">Student feedback submissions</div>
            </div>
          </div>
        </div>
        <div class="a-card">
          <div class="a-card-body">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
              <div class="tbl-entries d-flex align-items-center gap-2" style="font-size:.9rem;color:#adb5bd;">
                Show
                <select id="feedbackEntries" class="form-select form-select-sm" style="width: auto;" onchange="setFeedbackPageSize(this.value)">
                  <option value="5">5</option>
                  <option value="10" selected>10</option>
                  <option value="25">25</option>
                </select>
                entries
              </div>
              <div class="tbl-search d-flex align-items-center gap-2" style="font-size:.9rem;color:#adb5bd;">
                <i class="fa-solid fa-magnifying-glass"></i>
                <input type="text" id="feedbackSearch" class="form-control form-control-sm" placeholder="Filter feedback…" oninput="handleFeedbackSearch()" style="width:220px;" />
              </div>
              <div class="d-flex gap-2">
                <button class="btn btn-sm btn-outline-primary" onclick="printFeedbackReport()">Print</button>
                <button class="btn btn-sm btn-outline-secondary" onclick="exportFeedbackPDF()">Export PDF</button>
              </div>
            </div>
            <div class="d-flex justify-content-between align-items-center mb-2">
              <span id="feedbackInfo" style="font-size:.88rem;color:#adb5bd;">Showing 0 entries</span>
              <div id="feedbackPagination" style="display:flex;gap:.25rem;flex-wrap:wrap;"></div>
            </div>
            <div class="table-responsive" id="feedbackTableContainer">
              <table class="table table-bordered table-hover align-middle" id="feedbackTable">
                <thead class="table-light">
                  <tr>
                    <th>ID Number</th>
                    <th>Laboratory Rm.</th>
                    <th>Date</th>
                    <th>Message</th>
                  </tr>
                </thead>
                <tbody id="feedbackBody"></tbody>
              </table>
            </div>
            <div class="empty-state" id="feedbackEmpty" style="display:none;">
              <i class="fa-solid fa-comments"></i>
              <p>No feedback data available yet.</p>
            </div>
          </div>
        </div>
      </div>

      <!-- ████ RESERVATION ████ -->
      <div class="view" id="view-reservation">
        <div class="page-header">
          <div class="page-header-left">
            <div class="page-header-icon"><i class="fa-solid fa-calendar-check"></i></div>
            <div>
              <div class="page-header-title">Reservation Control</div>
              <div class="page-header-sub">Manage computer usage and pending requests</div>
            </div>
          </div>
        </div>

        <div class="row g-4">
          <div class="col-lg-3">
            <div class="a-card">
              <div class="a-card-header">Computer Control</div>
              <div class="a-card-body">
                <div class="mb-3">
                  <label class="form-label">Laboratory</label>
                  <select class="form-select" id="resLab" onchange="onLabChange()">
                    <option value="">Select lab…</option>
                    <option value="3">Lab 524</option>
                    <option value="4">Lab 526</option>
                    <option value="5">Lab 528</option>
                    <option value="6">Lab 530</option>
                    <option value="7">Lab 542</option>
                    <option value="8">MAC</option>
                  </select>
                </div>
                <div class="mb-3">
                  <label class="form-label">Reservation Date</label>
                  <input type="date" class="form-control" id="resDate" onchange="onLabChange()" />
                </div>
                <button class="btn btn-success w-100 mb-2" onclick="loadReservationRequests(); toast('Data refreshed', 'success');">
                  <i class="fa-solid fa-arrows-rotate"></i> Refresh
                </button>
                <div class="mt-2" id="reservationDisabledNotice" style="display:none;">
                  <div class="alert alert-danger py-2 px-3 mb-2" role="alert" style="border-radius:10px;background:rgba(255,77,106,0.12);border:1px solid rgba(255,77,106,0.3);color:#ffccd5;font-size:.88rem;">
                    Reservations for this lab and date are disabled.
                  </div>
                </div>
                
                <!-- Color Legend -->
                <div class="status-legend mt-3 p-2" style="background:rgba(255,255,255,0.02);border-radius:8px;font-size:0.85rem;">
                  <div class="d-flex align-items-center gap-2 mb-2">
                    <div style="width:16px;height:16px;background:#22c55e;border-radius:3px;"></div>
                    <span>Available</span>
                  </div>
                  <div class="d-flex align-items-center gap-2 mb-2">
                    <div style="width:16px;height:16px;background:#ff4d6a;border-radius:3px;"></div>
                    <span>Occupied</span>
                  </div>
                  <div class="d-flex align-items-center gap-2 mb-2">
                    <div style="width:16px;height:16px;background:#FFD700;border-radius:3px;"></div>
                    <span>Reserved (Pending)</span>
                  </div>
                  <div class="d-flex align-items-center gap-2">
                    <div style="width:16px;height:16px;background:#9CA3AF;border-radius:3px;"></div>
                    <span>Unavailable</span>
                  </div>
                </div>
                
                <div class="d-flex gap-2 mt-3">
                  <button class="btn btn-success w-50" id="resAvailableCount">Available 40</button>
                  <button class="btn btn-danger w-50" id="resUsedCount">Used 0</button>
                </div>
                <div class="computer-layout-header mt-3">
                  <div class="instructor-desk">Instructor&#39;s Desk</div>
                </div>
                <div class="mt-3" id="computerGrid">
                  <!-- computer chips go here -->
                </div>
              </div>
            </div>
          </div>

          <div class="col-lg-6">
            <div class="a-card">
              <div class="a-card-header">Reservation Requests</div>
              <div class="a-card-body" id="resRequests">
                <div class="no-data">No reservation requests yet.</div>
              </div>
            </div>
          </div>

          <div class="col-lg-3">
            <div class="a-card">
              <div class="a-card-header">Logs</div>
              <div class="a-card-body" id="reservationLogs" style="max-height:520px;overflow:auto;">
                <div class="no-data">No logs available yet.</div>
              </div>
            </div>
          </div>
        </div>

        <!-- Disabled Dates Management -->
        <div class="row g-4 mt-2">
          <div class="col-lg-6">
            <div class="a-card">
              <div class="a-card-header">
                <i class="fa-solid fa-calendar-xmark"></i> Disable Reservations
              </div>
              <div class="a-card-body">
                <div class="mb-3">
                  <label class="form-label">Laboratory</label>
                  <select class="form-select" id="disableLab">
                    <option value="">Select lab…</option>
                    <option value="3">Lab 524</option>
                    <option value="4">Lab 526</option>
                    <option value="5">Lab 528</option>
                    <option value="6">Lab 530</option>
                    <option value="7">Lab 542</option>
                    <option value="8">MAC</option>
                  </select>
                </div>
                <div class="mb-3">
                  <label class="form-label">Date to Disable</label>
                  <input type="date" class="form-control" id="disableDate" />
                </div>
                <div class="mb-3">
                  <label class="form-label">Reason</label>
                  <input type="text" class="form-control" id="disableReason" placeholder="e.g., No classes" value="No classes" />
                </div>
                <button class="btn btn-danger w-100" onclick="submitDisableDate()">
                  <i class="fa-solid fa-ban"></i> Disable Reservations
                </button>
              </div>
            </div>
          </div>

          <div class="col-lg-6">
            <div class="a-card">
              <div class="a-card-header">
                <i class="fa-solid fa-calendar-check"></i> Disabled Dates
              </div>
              <div class="a-card-body" id="disabledDatesList">
                <div class="no-data">No disabled dates.</div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- ████ SOFTWARE IMPORT/UPLOAD ████ -->
      <div class="view" id="view-software">
        <div class="page-header">
          <div class="page-header-left">
            <div class="page-header-icon"><i class="fa-solid fa-download"></i></div>
            <div>
              <div class="page-header-title">Software Import/Upload</div>
              <div class="page-header-sub">Upload and manage lab software</div>
            </div>
          </div>
        </div>

        <div class="row g-4">
          <!-- Add Software Section -->
          <div class="col-lg-4">
            <div class="a-card">
              <div class="a-card-header">
                <i class="fa-solid fa-plus"></i> Add Software
              </div>
              <div class="a-card-body">
                <!-- Software Name -->
                <div class="mb-3">
                  <label class="form-label">Software / File Name</label>
                  <input type="text" class="form-control" id="softwareName" placeholder="e.g., Visual Studio Code" />
                </div>

                <!-- Category -->
                <div class="mb-3">
                  <label class="form-label">Category</label>
                  <select class="form-select" id="softwareCategory">
                    <option value="">Select category</option>
                    <option value="IDE">IDE</option>
                    <option value="Programming">Programming</option>
                    <option value="Database">Database</option>
                    <option value="Tools">Tools</option>
                    <option value="Office">Office</option>
                    <option value="Mobile">Mobile (APK)</option>
                    <option value="Documentation">Documentation</option>
                    <option value="Other">Other</option>
                  </select>
                </div>

                <!-- Available in Labs -->
                <div class="mb-3">
                  <label class="form-label">Available in Labs</label>
                  <div id="labCheckboxes" style="display: grid; grid-template-columns: 1fr 1fr; gap: 0.5rem; max-height: 180px; overflow-y: auto; padding: 0.75rem; background: var(--surface3); border-radius: 8px;">
                    <!-- Labs will be populated here -->
                  </div>
                </div>

                <button class="btn btn-primary w-100" onclick="submitSoftwareUpload()">
                  <i class="fa-solid fa-check"></i> Add Software
                </button>
              </div>
            </div>
          </div>

          <!-- Registered Software -->
          <div class="col-lg-8">
            <div class="a-card">
              <div class="a-card-header">
                <div style="display: flex; justify-content: space-between; align-items: center;">
                  <span>Registered Software</span>
                  <input type="text" id="softwareSearch" placeholder="Search software..." style="width: 200px; padding: 0.5rem; border: 1px solid var(--border); border-radius: 6px; background: var(--surface3); color: var(--text1); font-size: 0.85rem;" onkeyup="filterRegisteredSoftware()" />
                </div>
              </div>
              <div class="a-card-body" id="registeredSoftware">
                <div class="no-data">No software registered yet.</div>
              </div>
            </div>
          </div>
        </div>

        <!-- Lab Software Overview -->
        <div class="row g-4 mt-2">
          <div class="col-12">
            <div class="a-card">
              <div class="a-card-header">
                <i class="fa-solid fa-building"></i> Lab Software Overview
              </div>
              <div class="a-card-body">
                <div class="row g-3" id="labOverview">
                  <!-- Cards will be populated here -->
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- ████ SETTINGS ████ -->
      <div class="view" id="view-settings">
        <div class="page-header">
          <div class="page-header-left">
            <div class="page-header-icon"><i class="fa-solid fa-gear"></i></div>
            <div>
              <div class="page-header-title">System Settings</div>
              <div class="page-header-sub">Configure system features and options</div>
            </div>
          </div>
        </div>

        <div class="row g-4">
          <div class="col-lg-6">
            <div class="settings-card">
              <div class="settings-card-title">
                <i class="fa-solid fa-trophy"></i> Leaderboard Visibility
              </div>
              <div class="settings-card-description">
                Enable or disable the leaderboard display on the student dashboard home page.
              </div>
              <div style="margin-top: 1rem; display: flex; align-items: center; justify-content: space-between;">
                <label style="font-size: 0.82rem; color: var(--text1); font-weight: 500;">Show leaderboard to students</label>
                <div class="toggle-switch">
                  <input type="checkbox" id="leaderboardToggle" onchange="saveLeaderboardSetting()">
                </div>
              </div>
              <div style="margin-top: 0.75rem; font-size: 0.75rem; color: var(--text3); padding: 0.75rem; background: var(--surface2); border-radius: 8px; border-left: 3px solid var(--accent);">
                <i class="fa-solid fa-info-circle" style="margin-right: 0.4rem;"></i>
                When enabled, the top sit-in students by points will be visible on the student dashboard.
              </div>
            </div>
          </div>
        </div>
      </div><!-- /view-settings -->

    </div><!-- /content-wrap -->
  </main>
</div><!-- /layout -->


<!-- ══════════════════════════════════════════════════════════
     MODALS
══════════════════════════════════════════════════════════ -->

<!-- Search Student -->
<div class="modal fade" id="modalSearch" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title"><i class="fa-solid fa-magnifying-glass"></i>Search Student</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <div class="search-input-wrap">
          <i class="fa-solid fa-magnifying-glass"></i>
          <input type="text" id="searchInput"
            placeholder="Search by ID or Name…"
            oninput="liveSearch()"
            onkeydown="if(event.key==='Enter') liveSearch()"/>
        </div>
        <div id="searchResults" class="mt-3">
          <div class="search-hint">
            <i class="fa-solid fa-user-magnifying-glass"></i>
            Type to search students from the database
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- Sit-in Form -->
<div class="modal fade" id="modalSitIn" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title"><i class="fa-solid fa-chair"></i>Sit In Form</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <div class="row g-3">
          <div class="col-12">
            <label class="form-label">ID Number</label>
            <input type="text" class="form-control" id="siIdNum"
              placeholder="Enter student ID…" oninput="lookupStudent()"/>
          </div>
          <div class="col-12">
            <label class="form-label">Student Name</label>
            <input type="text" class="form-control" id="siName" readonly placeholder="Auto-filled…"/>
          </div>
          <div class="col-12">
            <label class="form-label">Purpose / Language</label>
            <select class="form-select" id="siPurpose">
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
          <div class="col-12">
            <label class="form-label">Laboratory</label>
            <select class="form-select" id="siLab">
              <option value="">Select Lab…</option>
              <option value="3">524</option><option value="4">526</option>
              <option value="5">528</option><option value="6">530</option>
              <option value="7">542</option><option value="8">MAC</option>
            </select>
          </div>
          <div class="col-12">
            <label class="form-label">PC No.</label>
            <input type="number" class="form-control" id="siPCNo" placeholder="Enter PC number (optional)…" min="1"/>
          </div>
          <div class="col-12">
            <label class="form-label">Remaining Sessions</label>
            <input type="text" class="form-control" id="siSession" readonly placeholder="Auto-filled…"/>
          </div>
        </div>
      </div>
      <div class="modal-footer">
        <button class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
        <button class="btn btn-primary" onclick="submitSitIn()">
          <i class="fa-solid fa-check me-1"></i>Sit In
        </button>
      </div>
    </div>
  </div>
</div>

<!-- Add Student -->
<div class="modal fade" id="modalAddStudent" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title"><i class="fa-solid fa-user-plus"></i>Add Student</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <div class="row g-3">
          <div class="col-md-6"><label class="form-label">ID Number</label><input type="text"  class="form-control" id="asId"/></div>
          <div class="col-md-6"><label class="form-label">First Name</label><input type="text"  class="form-control" id="asFn"/></div>
          <div class="col-md-6"><label class="form-label">Last Name</label> <input type="text"  class="form-control" id="asLn"/></div>
          <div class="col-md-6"><label class="form-label">Middle Name</label><input type="text" class="form-control" id="asMn"/></div>
          <div class="col-md-6">
            <label class="form-label">Course</label>
            <select class="form-select" id="asCo">
              <option>BSIT</option><option>BSCS</option><option>BSIS</option><option>ACT</option>
            </select>
          </div>
          <div class="col-md-6">
            <label class="form-label">Year Level</label>
            <select class="form-select" id="asYr">
              <option value="1">1st Year</option><option value="2">2nd Year</option>
              <option value="3">3rd Year</option><option value="4">4th Year</option>
            </select>
          </div>
          <div class="col-12"><label class="form-label">Email</label>   <input type="email"    class="form-control" id="asEm"/></div>
          <div class="col-12"><label class="form-label">Password</label><input type="password" class="form-control" id="asPw"/></div>
        </div>
      </div>
      <div class="modal-footer">
        <button class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
        <button class="btn btn-primary"   onclick="submitAddStudent()">Add Student</button>
      </div>
    </div>
  </div>
</div>

<!-- Edit Student -->
<div class="modal fade" id="modalEditStudent" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title"><i class="fa-solid fa-user-pen"></i>Edit Student</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <input type="hidden" id="editId"/>
        <div class="row g-3">
          <div class="col-md-6"><label class="form-label">First Name</label><input type="text" class="form-control" id="editFn"/></div>
          <div class="col-md-6"><label class="form-label">Last Name</label> <input type="text" class="form-control" id="editLn"/></div>
          <div class="col-md-6">
            <label class="form-label">Course</label>
            <select class="form-select" id="editCo">
              <option>BSIT</option><option>BSCS</option><option>BSIS</option><option>ACT</option>
            </select>
          </div>
          <div class="col-md-6">
            <label class="form-label">Year Level</label>
            <select class="form-select" id="editYr">
              <option value="1">1st Year</option><option value="2">2nd Year</option>
              <option value="3">3rd Year</option><option value="4">4th Year</option>
            </select>
          </div>
          <div class="col-md-6">
            <label class="form-label">Remaining Sessions</label>
            <input type="number" class="form-control" id="editSess" min="0" max="30"/>
          </div>
        </div>
      </div>
      <div class="modal-footer">
        <button class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
        <button class="btn btn-primary"   onclick="submitEditStudent()">Save Changes</button>
      </div>
    </div>
  </div>
</div>

<!-- Logout Confirm -->
<div class="modal fade" id="modalLogout" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered modal-sm">
    <div class="modal-content">
      <div class="modal-body text-center py-4">
        <div style="width:52px;height:52px;background:rgba(255,77,106,0.12);border:1px solid rgba(255,77,106,0.25);border-radius:14px;display:flex;align-items:center;justify-content:center;margin:0 auto .85rem;">
          <i class="fa-solid fa-right-from-bracket" style="font-size:1.3rem;color:var(--red);"></i>
        </div>
        <div class="fw-bold mb-1" style="color:var(--text1);font-family:'Syne',sans-serif;font-size:.95rem;">Log Out?</div>
        <p style="color:var(--text2);font-size:.8rem;margin-top:.3rem;">Are you sure you want to end your session?</p>
      </div>
      <div class="modal-footer justify-content-center gap-2 border-0 pt-0">
        <button class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
        <button class="btn btn-danger btn-sm" onclick="doLogout()">Yes, Log Out</button>
      </div>
    </div>
  </div>
</div>

<!-- Mark Computer Unavailable -->
<div class="modal fade" id="modalMarkUnavailable" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header border-0">
        <h5 class="modal-title"><i class="fa-solid fa-exclamation-triangle"></i> Mark Computer Unavailable</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <div class="alert alert-warning" role="alert" style="background:rgba(255,193,7,0.12);border:1px solid rgba(255,193,7,0.3);color:#ffc107;">
          <strong>Computer:</strong> <span id="unavailablePCNumber" style="font-weight:600;font-size:1.1rem;">PC 1</span>
        </div>
        <div class="mb-3">
          <label class="form-label">Reason for unavailability</label>
          <select class="form-select" id="unavailableReason">
            <option value="">Select reason...</option>
            <option value="broken">Broken / Damaged</option>
            <option value="maintenance">Maintenance</option>
            <option value="no_display">No Display</option>
            <option value="no_keyboard">No Keyboard / Mouse</option>
            <option value="network_issue">Network Issue</option>
            <option value="software_error">Software Error</option>
            <option value="other">Other</option>
          </select>
        </div>
        <div class="mb-3">
          <label class="form-label">Additional notes (optional)</label>
          <textarea class="form-control" id="unavailableNotes" rows="3" placeholder="Describe the issue or when it will be fixed..."></textarea>
        </div>
      </div>
      <div class="modal-footer border-0">
        <button class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
        <button class="btn btn-warning" onclick="submitMarkUnavailable()">Mark as Unavailable</button>
      </div>
    </div>
  </div>
</div>


<!-- ══════════════════════════════════════════════════════════
     TOAST NOTIFICATION
══════════════════════════════════════════════════════════ -->
<div class="position-fixed top-0 end-0 p-3" style="z-index:9999;margin-top:12px;">
  <div id="adminToast" class="toast align-items-center text-white border-0" role="alert">
    <div class="d-flex">
      <div class="toast-body" id="adminToastMsg">Done!</div>
      <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
    </div>
  </div>
</div>


<!-- ══════════════════════════════════════════════════════════
     SCRIPTS
══════════════════════════════════════════════════════════ -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
// ── STATE ──────────────────────────────────────────────────
let students       = [];  // array of student objects from admin_students.php
let sitInRecs      = [];  // array of sit-in records from admin_sitin_fetch.php
let feedbackItems  = [];  // array of feedback report rows
let reportPage     = 1;   // current pagination page for sit-in reports
let reportPerPage = 10;   // rows per page for sit-in reports
let reportQuery   = '';
let reportDate    = '';
let stuPage        = 1;   // current pagination page for students table
let recPage        = 1;   // current pagination page for all-records table
let curPage        = 1;   // current pagination page for active sit-ins table
let feedbackPage   = 1;   // current pagination page for feedback report
let feedbackPerPage = 10; // feedback rows per page
let feedbackQuery  = '';  // current filter text for feedback
let searchTimer    = null; // debounce timer for live search
let currentDraggedReservation = null;
let reservationComputerAssignments = {};
let currentReservationStats = {};
let liveLogoutTimeInterval = null; // interval for updating live logout times in reports
let reservationLogsInterval = null; // interval for updating live durations in reservation logs
let currentUnavailablePC = null; // track which PC is being marked unavailable

// ── MOBILE SIDEBAR TOGGLE ──────────────────────────────────
function toggleSidebar() {
  document.getElementById('sidebar').classList.toggle('open');
  document.getElementById('sidebarBackdrop').classList.toggle('show');
}
function closeSidebar() {
  document.getElementById('sidebar').classList.remove('open');
  document.getElementById('sidebarBackdrop').classList.remove('show');
}

// ── LEADERBOARD ────────────────────────────────────────────
function loadLeaderboard() {
  fetch('admin_leaderboard_fetch.php')
    .then(r => r.json())
    .then(data => renderLeaderboard(data))
    .catch(() => renderLeaderboard([]));
}

function renderLeaderboard(leaderboard) {
  const container = document.getElementById('leaderboardBody');
  if (!container) return;

  if (!leaderboard || leaderboard.length === 0) {
    container.innerHTML = `
      <div class="empty-state" style="padding: 2rem 1rem;">
        <i class="fa-solid fa-chart-line"></i>
        <p>No leaderboard data available yet.</p>
      </div>
    `;
    return;
  }

  // Limit to top 50 for display (all available)
  const topPlayers = leaderboard.slice(0, 50);
  const podiumPlayers = topPlayers.slice(0, 3);
  const restPlayers = topPlayers.slice(3);

  const renderPodiumStep = (player, rank) => {
    const trophyEmoji = rank === 1 ? '🥇' : rank === 2 ? '🥈' : '🥉';
    const hasPlayer = !!player;
    const initials = hasPlayer ? player.name.split(' ').map(n => n[0]).join('').toUpperCase() : '---';
    const profilePic = hasPlayer && player.profile_pic && player.profile_pic.trim() ? player.profile_pic + '?v=' + Math.random() : '';
    const name = hasPlayer ? player.name : 'TBD';
    const idNumber = hasPlayer ? player.id_number : '----';
    const courseDisplay = hasPlayer && player.course ? player.course.toUpperCase() : 'N/A';
    const yearDisplay = hasPlayer && player.year_level ? `Year ${player.year_level}` : 'N/A';
    const points = hasPlayer ? player.points : '0';
    const sessions = hasPlayer ? player.sit_in_count : '0';

    return `
      <div class="podium-step rank-${rank}">
        <div class="podium-step-indicator">#${rank}</div>
        <div class="podium-platform">
          <div class="podium-card rank-${rank}">
            <div class="podium-trophy">${trophyEmoji}</div>
            ${profilePic ? `<img src="${escapeHtml(profilePic)}" alt="${escapeHtml(name)}" class="podium-pic" onerror="this.src='data:image/svg+xml,%3Csvg xmlns=%22http://www.w3.org/2000/svg%22 viewBox=%220 0 100 100%22%3E%3Crect fill=%22%231a151c%22 width=%22100%22 height=%22100%22/%3E%3Ctext x=%2250%22 y=%2255%22 font-size=%2240%22 text-anchor=%22middle%22 fill=%22%234d9fff%22%3E${escapeHtml(initials)}%3C/text%3E%3C/svg%3E'"/>` : `<div class="podium-avatar">${initials}</div>`}
            <div class="podium-name">${escapeHtml(name)}</div>
            <div class="podium-detail">${escapeHtml(idNumber)}</div>
            <div class="podium-detail">${courseDisplay} • ${yearDisplay}</div>
            <div class="podium-meta">
              <div>
                <div class="podium-label">Sessions</div>
                <div class="podium-points rank-${rank}">${sessions}</div>
              </div>
              <div>
                <div class="podium-label">Points</div>
                <div class="podium-points rank-${rank}">${points}</div>
              </div>
            </div>
          </div>
        </div>
      </div>
    `;
  };

  const podiumHtml = `
    <div class="leaderboard-podium">
      ${[2, 1, 3].map(rank => renderPodiumStep(podiumPlayers[rank - 1], rank)).join('')}
    </div>
  `;

  // Render rest players (4th onwards) as a professional table
  const restTableHtml = `
    <div class="rest-players-section">
      ${restPlayers.length > 0 ? `
        <table class="rest-players-table">
          <thead class="rest-table-head">
            <tr>
              <th class="rest-table-head-cell">Rank</th>
              <th class="rest-table-head-cell"></th>
              <th class="rest-table-head-cell">Name</th>
              <th class="rest-table-head-cell">ID No.</th>
              <th class="rest-table-head-cell">Course & Year</th>
              <th class="rest-table-head-cell">Sessions</th>
              <th class="rest-table-head-cell">Points</th>
            </tr>
          </thead>
          <tbody>
            ${restPlayers.map(player => {
              const initials = player.name.split(' ').map(n => n[0]).join('').toUpperCase();
              const profilePic = player.profile_pic && player.profile_pic.trim() 
                ? player.profile_pic + '?v=' + Math.random() 
                : '';
              const courseDisplay = player.course ? player.course.toUpperCase() : 'N/A';
              const yearDisplay = player.year_level ? player.year_level : 'N/A';
              return `
                <tr class="rest-table-row">
                  <td class="rest-table-cell rest-table-cell-center">
                    <span class="rest-table-rank rank-${player.rank}">#${player.rank}</span>
                  </td>
                  <td class="rest-table-cell rest-table-cell-center">
                    ${profilePic ? `<img src="${escapeHtml(profilePic)}" alt="${escapeHtml(player.name)}" class="rest-table-avatar" onerror="this.replaceWith(Object.assign(document.createElement('div'), {className: 'rest-table-avatar-placeholder', textContent: '${initials}'}))"/>` : `<div class="rest-table-avatar-placeholder">${initials}</div>`}
                  </td>
                  <td class="rest-table-cell">
                    <div class="rest-table-player-info">
                      <div class="rest-table-player-name">${escapeHtml(player.name)}</div>
                    </div>
                  </td>
                  <td class="rest-table-cell rest-table-cell-center">
                    <span class="rest-table-stat">${escapeHtml(player.id_number)}</span>
                  </td>
                  <td class="rest-table-cell rest-table-cell-center">
                    <span class="rest-table-stat">${courseDisplay} • Year ${yearDisplay}</span>
                  </td>
                  <td class="rest-table-cell rest-table-cell-center">
                    <span class="rest-table-stat">${player.sit_in_count}</span>
                  </td>
                  <td class="rest-table-cell rest-table-cell-center">
                    <span class="rest-table-stat points">${player.points}</span>
                  </td>
                </tr>
              `;
            }).join('')}
          </tbody>
        </table>
      ` : `
        <div class="empty-rest-players">
          <i class="fa-solid fa-inbox" style="font-size: 2rem; margin-bottom: 0.5rem; opacity: 0.5;"></i>
          <p>No additional leaderboard entries.</p>
        </div>
      `}
    </div>
  `;

  container.innerHTML = podiumHtml + restTableHtml;
  renderRewardTable(leaderboard);
}

// ── THEME TOGGLE ───────────────────────────────────────────
function toggleTheme() {
  const html = document.documentElement;
  const isLight = html.classList.toggle('light-mode');
  const icon = document.querySelector('#themeToggle i');
  if (isLight) {
    icon.className = 'fa-solid fa-moon';
    localStorage.setItem('theme', 'light');
  } else {
    icon.className = 'fa-solid fa-sun';
    localStorage.setItem('theme', 'dark');
  }
}

// Initialize theme on load
document.addEventListener('DOMContentLoaded', () => {
  const savedTheme = localStorage.getItem('theme') || 'dark';
  const html = document.documentElement;
  const icon = document.querySelector('#themeToggle i');
  if (savedTheme === 'light') {
    html.classList.add('light-mode');
    icon.className = 'fa-solid fa-moon';
  } else {
    icon.className = 'fa-solid fa-sun';
  }
});

// ── TOAST ──────────────────────────────────────────────────
function toast(msg, type = 'success') {
  const el = document.getElementById('adminToast');
  const colors = {
    success: '#00c896',
    danger:  '#ff4d6a',
    warning: '#ffb347',
    info:    '#4d9fff'
  };
  el.style.background   = colors[type] || colors.success;
  el.style.borderRadius = '10px';
  document.getElementById('adminToastMsg').textContent = msg;
  bootstrap.Toast.getOrCreateInstance(el, { delay: 2800 }).show();
}

// ── VIEW SWITCHER ──────────────────────────────────────────
// Maps view names → nav element IDs and topbar icon/labels
const viewMeta = {
  'home':           { nav: 'nav-home',        icon: 'fa-house-chimney',  label: 'Home' },
  'leaderboard':    { nav: 'nav-leaderboard', icon: 'fa-trophy',         label: 'Leaderboard' },
  'students':       { nav: 'nav-students',     icon: 'fa-users',          label: 'Students' },
  'current-sitin':  { nav: 'nav-sitin',        icon: 'fa-chair',          label: 'Current Sit-in' },
  'sitin-records':  { nav: 'nav-records',      icon: 'fa-table-list',     label: 'Sit-in Records' },
  'reports':        { nav: 'nav-reports',      icon: 'fa-chart-bar',      label: 'Sit-in Reports' },
  'analytics':      { nav: 'nav-analytics',    icon: 'fa-chart-line',     label: 'Analytics & Reports' },
  'feedback':       { nav: 'nav-feedback',     icon: 'fa-comment-dots',   label: 'Feedback' },
  'reservation':    { nav: 'nav-reservation',  icon: 'fa-calendar-check', label: 'Reservation' },
  'software':       { nav: 'nav-software',     icon: 'fa-download',       label: 'Software Import/Upload' },
};

function showView(name) {
  // Hide all views
  document.querySelectorAll('.view').forEach(v => v.classList.remove('active'));
  // Remove active from all nav items
  document.querySelectorAll('.nav-item').forEach(a => a.classList.remove('active'));

  // Activate target view
  document.getElementById('view-' + name).classList.add('active');

  // Update active nav item
  const meta = viewMeta[name];
  if (meta) {
    const navEl = document.getElementById(meta.nav);
    if (navEl) navEl.classList.add('active');
    // Update topbar breadcrumb title
    document.getElementById('topbarTitle').innerHTML =
      `<i class="fa-solid ${meta.icon}"></i><span>${meta.label}</span>`;
  }

  // Stop live logout time updates when leaving reports view
  if (liveLogoutTimeInterval && name !== 'reports') {
    clearInterval(liveLogoutTimeInterval);
    liveLogoutTimeInterval = null;
  }

  // Stop reservation logs updates when leaving reservation view
  if (reservationLogsInterval && name !== 'reservation') {
    stopReservationLogsUpdates();
  }

  // Trigger data loads for each view
  if (name === 'students')      renderStudents();
  if (name === 'sitin-records') renderRecords();
  if (name === 'current-sitin') renderCurrentSitIn();
  if (name === 'reports') {
    loadReportData();
    // Start live logout time updates for active sessions
    if (!liveLogoutTimeInterval) {
      liveLogoutTimeInterval = setInterval(updateLiveLogoutTimes, 1000); // update every 1 second
    }
  }
  if (name === 'analytics')     loadAnalyticsData('daily');
  if (name === 'feedback')      loadFeedbackReports();
  if (name === 'reservation') {
    loadReservationRequests();
    loadDisabledDates();
    // Start live duration updates for reservation logs
    startReservationLogsUpdates();
  }
  if (name === 'software') {
    loadSoftwareLabs();
    loadSoftwareOverview();
    loadRegisteredSoftware();
  }
  if (name === 'settings') {
    loadLeaderboardSettings();
  }
  if (name === 'leaderboard')   loadLeaderboard();
  if (name === 'home')          loadStats();

  // Close sidebar on mobile after navigation
  closeSidebar();
}

async function postJSON(url, payload) {
  try {
    const res = await fetch(url, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(payload)
    });
    return await res.json();
  } catch (err) {
    console.error('postJSON error:', err);
    return null;
  }
}

async function loadReservationRequests() {
  const labId = parseInt(document.getElementById('resLab')?.value || 0, 10);
  const date = document.getElementById('resDate')?.value || '';
  const data = await postJSON('reservation_fetch.php', { lab_id: labId, date });
  if (!data || !data.success) {
    toast(data?.message || 'Unable to load reservation requests.', 'danger');
    return;
  }
  currentReservationStats = data.stats || {};
  renderDisabledNotice(currentReservationStats);
  renderReservationRequests(data.requests || []);
  renderReservationLogs(data.logs || []);
  renderComputerControl(currentReservationStats);
}

// Auto-load reservation data when lab or date changes
let resLoadTimeout = null;
function onLabChange() {
  clearTimeout(resLoadTimeout);
  resLoadTimeout = setTimeout(() => {
    const labId = parseInt(document.getElementById('resLab')?.value || 0, 10);
    const date = document.getElementById('resDate')?.value || '';
    if (labId && date) {
      loadReservationRequests();
    }
  }, 500); // Debounce by 500ms
}

function renderDisabledNotice(stats) {
  const notice = document.getElementById('reservationDisabledNotice');
  if (!notice) return;
  if (stats.disabled) {
    notice.style.display = 'block';
    notice.innerHTML = `
      <div class="alert alert-danger py-2 px-3 mb-3" role="alert" style="border-radius:10px;background:rgba(255,77,106,0.12);border:1px solid rgba(255,77,106,0.3);color:#ffccd5;font-size:.88rem;">
        <strong>Reservations disabled:</strong> ${escapeHtml(stats.disabled_reason || 'No classes')}<br>
        Students will not be able to submit new reservations for this lab and date.
      </div>`;
  } else {
    notice.style.display = 'none';
    notice.innerHTML = '';
  }
}

function renderReservationRequests(items) {
  const body = document.getElementById('resRequests');
  if (!body) return;
  if (!items.length) {
    body.innerHTML = '<div class="no-data">No reservation requests yet.</div>';
    return;
  }
  body.innerHTML = items.map(req => {
    const requested = req.computer_number ? `Requested computer ${req.computer_number}` : 'No computer requested';
    const assignedValue = reservationComputerAssignments[req.id] || req.computer_number || '';
    return `
    <div class="reservation-card mb-3 p-3 border rounded" draggable="true" data-reservation-id="${req.id}">
      <div class="d-flex justify-content-between align-items-center">
        <div>
          <div class="mb-1"><strong>ID Number:</strong> ${req.id_number}</div>
          <div><strong>Student:</strong> ${req.student_name}</div>
        </div>
        <span class="badge bg-warning text-dark">${req.status}</span>
      </div>
      <div class="mt-3" style="font-size:.93rem;line-height:1.5;">
        <div><strong>Lab:</strong> ${req.lab_name}</div>
        <div><strong>Date:</strong> ${req.reserved_date}</div>
        <div><strong>Time:</strong> ${req.time_start} - ${req.time_end}</div>
        <div><strong>Purpose:</strong> ${req.purpose}</div>
        <div><strong>${requested}</strong></div>
      </div>
      <div class="mt-3">
        <label class="form-label">Assign Computer</label>
        <select class="form-select form-select-sm mb-3" id="assignComputer_${req.id}"
                onchange="reservationComputerAssignments[${req.id}] = Number(this.value); renderComputerControl(currentReservationStats);">
          ${Array.from({ length: 40 }, (_, i) => `
            <option value="${i+1}" ${(assignedValue == i+1) ? 'selected' : ''}>Computer ${i+1}</option>`).join('')}
        </select>
      </div>
      <div class="d-flex gap-2">
        <button class="btn btn-success btn-sm" onclick="confirmReservationAction(${req.id}, 'approve')">Accept</button>
        <button class="btn btn-danger btn-sm" onclick="confirmReservationAction(${req.id}, 'deny')">Deny</button>
      </div>
    </div>`;
  }).join('');
  attachReservationDragHandlers();
}

function attachReservationDragHandlers() {
  document.querySelectorAll('.reservation-card[draggable="true"]').forEach(card => {
    card.ondragstart = event => {
      currentDraggedReservation = card.dataset.reservationId;
      event.dataTransfer.effectAllowed = 'move';
      event.dataTransfer.setData('text/plain', currentDraggedReservation);
      card.classList.add('dragging');
    };
    card.ondragend = () => {
      currentDraggedReservation = null;
      card.classList.remove('dragging');
    };
  });
}

function attachComputerDropTargets() {
  document.querySelectorAll('.computer-chip').forEach(chip => {
    // Add click handler for toggling PC availability
    chip.onclick = async event => {
      event.stopPropagation();
      const computerNumber = parseInt(chip.dataset.computerNumber, 10);
      const labId = parseInt(document.getElementById('resLab')?.value || 0, 10);
      
      if (!labId) {
        toast('Please select a laboratory first.', 'warning');
        return;
      }
      
      // Check if computer is occupied - cannot toggle occupied computers
      const style = window.getComputedStyle(chip);
      const bg = style.backgroundColor;
      const isOccupied = bg.includes('rgb(255, 77, 106)');
      const isPending = bg.includes('rgb(0, 212, 255)') || bg.includes('rgb(255, 215, 0)');
      
      if (isOccupied || isPending) {
        toast('Cannot toggle occupied or pending computers.', 'danger');
        return;
      }
      
      // Toggle availability
      const isUnavailable = bg.includes('rgb(156, 163, 175)');
      await toggleComputerAvailability(computerNumber, labId, !isUnavailable);
    };
    
    chip.ondragover = event => {
      // Prevent dropping on occupied or unavailable computers
      const style = window.getComputedStyle(chip);
      const bg = style.backgroundColor;
      const isOccupiedOrUnavailable = bg.includes('rgb(255, 77, 106)') || bg.includes('rgb(156, 163, 175)');
      
      if (!isOccupiedOrUnavailable) {
        event.preventDefault();
        chip.classList.add('drag-over');
      }
    };
    chip.ondragleave = () => chip.classList.remove('drag-over');
    chip.ondrop = event => {
      event.preventDefault();
      chip.classList.remove('drag-over');
      
      // Check if computer is occupied or unavailable
      const style = window.getComputedStyle(chip);
      const bg = style.backgroundColor;
      const isOccupiedOrUnavailable = bg.includes('rgb(255, 77, 106)') || bg.includes('rgb(156, 163, 175)');
      if (isOccupiedOrUnavailable) {
        toast('Cannot assign to occupied or unavailable computers.', 'danger');
        return;
      }
      
      const reservationId = event.dataTransfer.getData('text/plain') || currentDraggedReservation;
      if (!reservationId) return;
      const computerNumber = chip.dataset.computerNumber;
      if (!computerNumber) return;
      reservationComputerAssignments[reservationId] = Number(computerNumber);
      const select = document.getElementById(`assignComputer_${reservationId}`);
      if (select) select.value = computerNumber;
      toast(`Computer ${computerNumber} assigned to the dragged reservation.`, 'info');
      renderComputerControl(currentReservationStats);
    };
  });
}

// Toggle computer availability (mark as unavailable or available)
async function toggleComputerAvailability(computerNumber, labId, markUnavailable) {
  try {
    const response = await fetch('toggle_computer_availability.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({
        computer_number: computerNumber,
        lab_id: labId,
        unavailable: markUnavailable ? 1 : 0
      })
    });
    
    const data = await response.json();
    
    if (!data.success) {
      toast(data.message || 'Failed to toggle computer availability.', 'danger');
      return;
    }
    
    toast(
      markUnavailable 
        ? `Computer ${computerNumber} marked as unavailable.` 
        : `Computer ${computerNumber} marked as available.`,
      'success'
    );
    
    // Reload reservation data to refresh the UI
    loadReservationRequests();
  } catch (err) {
    console.error('Error toggling computer availability:', err);
    toast('Error toggling computer availability.', 'danger');
  }
}

// Helper: Calculate duration in minutes between two time strings (HH:MM)
function calculateDuration(timeStart, timeEnd) {
  const [sh, sm] = timeStart.split(':').map(Number);
  const [eh, em] = timeEnd.split(':').map(Number);
  const startMin = sh * 60 + sm;
  const endMin = eh * 60 + em;
  return Math.max(0, endMin - startMin);
}

// Helper: Format duration in "Xh Ym" format
function formatDuration(minutes) {
  const h = Math.floor(minutes / 60);
  const m = minutes % 60;
  if (h === 0) return `${m}m`;
  if (m === 0) return `${h}h`;
  return `${h}h ${m}m`;
}

// Helper: Get real-time elapsed time since reservation started (unlimited session)
function getReservationElapsedTime(reservedDate, timeStart) {
  const now = new Date();
  
  // Parse time
  const [sh, sm] = timeStart.split(':').map(Number);
  
  // Create datetime for reservation date at timeStart
  const startTime = new Date(reservedDate);
  startTime.setHours(sh, sm, 0, 0);
  
  // If reservation hasn't started yet
  if (now < startTime) {
    return `Not started`;
  }
  
  // Calculate elapsed time since start
  const elapsedMin = Math.floor((now - startTime) / 60000);
  return `${formatDuration(elapsedMin)} elapsed`;
}

function renderReservationLogs(logs) {
  const body = document.getElementById('reservationLogs');
  if (!body) return;
  if (!logs.length) {
    body.innerHTML = '<div class="no-data">No activity history available for this date.</div>';
    return;
  }
  
  // Group logs by status for better visualization
  const approvedLogs = logs.filter(l => l.status === 'approved');
  const rejectedLogs = logs.filter(l => l.status === 'rejected');
  const pendingLogs = logs.filter(l => l.status === 'pending');
  
  let html = '';
  
  if (approvedLogs.length > 0) {
    html += '<div class="mb-3"><h6 style="color:var(--green);font-weight:600;margin-bottom:10px;"><i class="fa-solid fa-check"></i> Completed</h6>';
    approvedLogs.forEach(req => {
      const elapsedStatus = getReservationElapsedTime(req.reserved_date, req.time_start);
      html += `
        <div class="reservation-log mb-2 p-3 border rounded" data-reservation-id="${req.id}" style="border-color:rgba(34,197,94,0.3);background:rgba(34,197,94,0.05);">
          <div class="d-flex justify-content-between align-items-center mb-2">
            <div><strong>${req.lab_name}</strong> · PC ${req.computer_number || 'TBD'}</div>
            <span class="badge bg-success">Completed</span>
          </div>
          <div style="font-size:.85rem;line-height:1.4;">
            <div><strong>Student:</strong> ${req.id_number} - ${req.student_name}</div>
            <div><strong>Purpose:</strong> ${req.purpose}</div>
            <div><strong>Time:</strong> ${req.time_start} to ${req.time_end}</div>
          </div>
        </div>`;
    });
    html += '</div>';
  }
  
  if (pendingLogs.length > 0) {
    html += '<div class="mb-3"><h6 style="color:#FFD700;font-weight:600;margin-bottom:10px;"><i class="fa-solid fa-hourglass-half"></i> Pending</h6>';
    pendingLogs.forEach(req => {
      html += `
        <div class="reservation-log mb-2 p-3 border rounded" data-reservation-id="${req.id}" style="border-color:rgba(255,215,0,0.3);background:rgba(255,215,0,0.05);">
          <div class="d-flex justify-content-between align-items-center mb-2">
            <div><strong>${req.lab_name}</strong> · ${req.reserved_date}</div>
            <span class="badge bg-warning text-dark">Pending</span>
          </div>
          <div style="font-size:.85rem;line-height:1.4;">
            <div><strong>Student:</strong> ${req.id_number} - ${req.student_name}</div>
            <div><strong>Purpose:</strong> ${req.purpose}</div>
            <div><strong>Time:</strong> ${req.time_start} to ${req.time_end}</div>
            <div><strong>Computer:</strong> ${req.computer_number ? req.computer_number : 'Not assigned'}</div>
          </div>
        </div>`;
    });
    html += '</div>';
  }
  
  if (rejectedLogs.length > 0) {
    html += '<div class="mb-3"><h6 style="color:#ff4d6a;font-weight:600;margin-bottom:10px;"><i class="fa-solid fa-xmark"></i> Rejected</h6>';
    rejectedLogs.forEach(req => {
      html += `
        <div class="reservation-log mb-2 p-3 border rounded" data-reservation-id="${req.id}" style="border-color:rgba(255,77,106,0.3);background:rgba(255,77,106,0.05);">
          <div class="d-flex justify-content-between align-items-center mb-2">
            <div><strong>${req.lab_name}</strong> · ${req.reserved_date}</div>
            <span class="badge bg-danger">Rejected</span>
          </div>
          <div style="font-size:.85rem;line-height:1.4;">
            <div><strong>Student:</strong> ${req.id_number} - ${req.student_name}</div>
            <div><strong>Purpose:</strong> ${req.purpose}</div>
            <div><strong>Time:</strong> ${req.time_start} to ${req.time_end}</div>
          </div>
        </div>`;
    });
    html += '</div>';
  }
  
  body.innerHTML = html;
}

// Update live durations every 10 seconds
function updateReservationLogsDurations() {
  const elements = document.querySelectorAll('.live-duration');
  elements.forEach(el => {
    const timeStart = el.getAttribute('data-time-start');
    const reservedDate = el.getAttribute('data-reserved-date');
    if (timeStart && reservedDate) {
      const elapsedStatus = getReservationElapsedTime(reservedDate, timeStart);
      el.textContent = elapsedStatus;
    }
  });
}

// Set up interval for live duration updates
function startReservationLogsUpdates() {
  if (!reservationLogsInterval) {
    reservationLogsInterval = setInterval(updateReservationLogsDurations, 10000); // Update every 10 seconds
  }
}
function stopReservationLogsUpdates() {
  if (reservationLogsInterval) {
    clearInterval(reservationLogsInterval);
    reservationLogsInterval = null;
  }
}

function renderComputerControl(stats) {
  const availableEl = document.getElementById('resAvailableCount');
  const usedEl = document.getElementById('resUsedCount');
  const grid = document.getElementById('computerGrid');
  if (!availableEl || !usedEl || !grid) return;

  if (stats.disabled) {
    availableEl.textContent = 'Available 0';
    usedEl.textContent = 'Used 0';
    grid.innerHTML = `
      <div class="alert alert-danger" role="alert" style="border-radius:12px;background:rgba(255,77,106,0.12);border:1px solid rgba(255,77,106,0.3);color:#ffccd5;padding:1rem;">
        <strong>Lab closed:</strong> Reservations are disabled for the selected date.
      </div>`;
    return;
  }

  const occupied = new Set(stats.occupied || []);
  const pending = new Set(stats.pending_computers || []);
  const unavailable = new Set(stats.unavailable || []);
  const assignedNumbers = new Set(Object.values(reservationComputerAssignments));
  const used = occupied.size;
  const available = Math.max(0, 40 - used - pending.size - unavailable.size);
  availableEl.textContent = `Available ${available}`;
  usedEl.textContent = `Used ${used}`;

  const chips = [];
  for (let i = 1; i <= 40; i++) {
    const isOccupied = occupied.has(i);
    const isPending = pending.has(i);
    const isUnavailable = unavailable.has(i);
    const isAssigned = assignedNumbers.has(i) && !isOccupied && !isPending && !isUnavailable;
    
    let bgStyle = 'background:#22c55e;'; // available - green
    let tooltip = 'Available';
    
    if (isOccupied) {
      bgStyle = 'background:#ff4d6a;'; // occupied - red
      tooltip = 'Occupied';
    } else if (isPending) {
      bgStyle = 'background:#FFD700;'; // pending - yellow
      tooltip = 'Reserved (Pending)';
    } else if (isUnavailable) {
      bgStyle = 'background:#9CA3AF;'; // unavailable - grey
      tooltip = 'Unavailable / Broken';
    } else if (isAssigned) {
      bgStyle = 'background:#FFD700;'; // assigned - taxi yellow
      tooltip = 'Assigned';
    }
    
    chips.push(`
      <div class="computer-chip" data-computer-number="${i}" 
           style="${bgStyle}" 
           title="${tooltip}"
           ${isOccupied || isUnavailable ? 'style="' + bgStyle + 'cursor:not-allowed;opacity:0.7;"' : ''}>
        ${i}
      </div>`);
  }
  grid.innerHTML = chips.join('');
  attachComputerDropTargets();
}

async function loadFeedbackReports() {
  const data = await postJSON('feedback_fetch.php', {});
  if (!data || !data.success) {
    toast(data?.message || 'Unable to load feedback reports.', 'danger');
    return;
  }
  feedbackItems = data.feedback || [];
  feedbackPage = 1;
  feedbackQuery = document.getElementById('feedbackSearch')?.value.trim() || '';
  renderFeedbackReports(feedbackItems);
}

function renderFeedbackReports(items) {
  const container = document.getElementById('feedbackTableContainer');
  const body = document.getElementById('feedbackBody');
  const empty = document.getElementById('feedbackEmpty');
  const info = document.getElementById('feedbackInfo');
  const pagination = document.getElementById('feedbackPagination');
  if (!container || !body || !empty || !info || !pagination) return;

  const query = feedbackQuery.toLowerCase();
  const filtered = items.filter(item =>
    Object.values(item).some(value =>
      String(value).toLowerCase().includes(query)
    )
  );

  const total = filtered.length;
  const pages = Math.max(1, Math.ceil(total / feedbackPerPage));
  if (feedbackPage > pages) feedbackPage = pages;

  const start = (feedbackPage - 1) * feedbackPerPage;
  const end = start + feedbackPerPage;
  const pageRows = filtered.slice(start, end);

  if (!pageRows.length) {
    container.style.display = 'none';
    empty.style.display = 'flex';
  } else {
    empty.style.display = 'none';
    container.style.display = 'block';
  }

  body.innerHTML = pageRows.map(item => `
    <tr>
      <td>${item.id_number}</td>
      <td>${item.lab_name}</td>
      <td>${item.date}</td>
      <td>${item.message}</td>
    </tr>`).join('');

  if (total === 0) {
    info.textContent = 'Showing 0 entries';
  } else {
    info.textContent = `Showing ${start + 1} to ${Math.min(end, total)} of ${total} entries`;
  }

  renderPagination('feedbackPagination', feedbackPage, pages, p => { feedbackPage = p; renderFeedbackReports(feedbackItems); });
}

function setFeedbackPageSize(value) {
  feedbackPerPage = parseInt(value, 10) || 10;
  feedbackPage = 1;
  renderFeedbackReports(feedbackItems);
}

function handleFeedbackSearch() {
  feedbackQuery = document.getElementById('feedbackSearch')?.value.trim() || '';
  feedbackPage = 1;
  renderFeedbackReports(feedbackItems);
}

function generateFormalFeedbackHTML() {
  const tableHtml = document.getElementById('feedbackTable')?.outerHTML || '';
  const now = new Date();
  const dateStr = now.toLocaleDateString('en-US', { year: 'numeric', month: 'long', day: 'numeric' });
  const timeStr = now.toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit' });
  
  // Calculate stats from displayed data
  const rows = document.querySelectorAll('#feedbackBody tr');
  const totalFeedback = rows.length;
  
  return `
    <!DOCTYPE html>
    <html>
      <head>
        <meta charset="UTF-8">
        <title>Feedback Report</title>
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
            margin-top: 10px;
          }
          .report-info span {
            display: inline-block;
            margin-right: 30px;
          }
          .stats-section {
            display: flex;
            justify-content: center;
            gap: 40px;
            margin: 25px 0;
            padding: 20px;
            background-color: #f5f5f5;
            border-radius: 4px;
          }
          .stat-box {
            text-align: center;
          }
          .stat-label {
            font-size: 12px;
            color: #666;
            margin-bottom: 6px;
          }
          .stat-value {
            font-size: 24px;
            font-weight: bold;
            color: #003366;
          }
          table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 25px;
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
          tr:hover {
            background-color: #f0f0f0;
          }
          .signature-section {
            margin-top: 40px;
            display: flex;
            justify-content: space-between;
            padding-top: 30px;
          }
          .signature-block {
            width: 45%;
          }
          .signature-line {
            border-top: 1px solid #1a1a1a;
            margin-top: 40px;
            padding-top: 5px;
            font-size: 11px;
            font-weight: bold;
            text-align: center;
          }
          .signature-title {
            font-size: 11px;
            font-weight: bold;
            margin-bottom: 50px;
            text-align: center;
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
          <div class="report-title">SIT-IN MONITORING SYSTEM</div>
          <div class="report-subtitle">College of Computer Studies — University of Cebu</div>
          <div class="report-info">
            <span><strong>Student Feedback Report</strong></span>
            <span>Generated: ${dateStr} at ${timeStr}</span>
          </div>
        </div>
        
        <div class="stats-section">
          <div class="stat-box">
            <div class="stat-label">Total Feedback</div>
            <div class="stat-value">${totalFeedback}</div>
          </div>
        </div>
        
        ${tableHtml}
        
        <div class="signature-section">
          <div class="signature-block">
            <div class="signature-title">Prepared by</div>
            <div class="signature-line">LAB-IN-CHARGE</div>
          </div>
          <div class="signature-block">
            <div class="signature-title">Noted by</div>
            <div class="signature-line">CCS DEAN / DEPARTMENT HEAD</div>
          </div>
        </div>
        
        <div class="footer">
          CCS Sit-in Monitoring System | UC — College of Computer Studies<br>
          Printed: ${dateStr} at ${timeStr}
        </div>
      </body>
    </html>
  `;
}

function printFeedbackReport() {
  const html = generateFormalFeedbackHTML();
  const printWindow = window.open('', '_blank');
  if (!printWindow) return;
  printWindow.document.write(html);
  printWindow.document.close();
  printWindow.focus();
  printWindow.print();
}

function exportFeedbackPDF() {
  const html = generateFormalFeedbackHTML();
  const printWindow = window.open('', '_blank');
  if (!printWindow) return;
  printWindow.document.write(html);
  printWindow.document.close();
  printWindow.focus();
  printWindow.print();
}

async function confirmReservationAction(reservationId, action) {
  const verb = action === 'approve' ? 'approve' : 'deny';
  const message = `Are you sure you want to ${verb} this reservation request?`;
  if (!confirm(message)) return;

  const assignedComputer = document.getElementById(`assignComputer_${reservationId}`)?.value;
  const payload = { id: reservationId, action };
  if (action === 'approve') payload.computer_number = assignedComputer;

  const data = await postJSON('reservation_update.php', payload);
  if (!data) {
    toast('Server error. Please try again.', 'danger');
    return;
  }
  if (!data.success) {
    toast(data.message || 'Unable to update reservation.', 'danger');
    return;
  }
  toast(data.message || 'Reservation updated.', 'success');
  loadReservationRequests();
}

// ── STATS ──────────────────────────────────────────────────
function loadStats() {
  fetch('admin_stats.php')
    .then(r => r.json())
    .then(d => {
      document.getElementById('statRegistered').textContent = d.registered ?? students.length;
      document.getElementById('statCurrent').textContent    = d.current    ?? sitInRecs.filter(r => r.status === 'Active').length;
      document.getElementById('statTotal').textContent      = d.total      ?? sitInRecs.length;
      renderChart(d.purposes ?? {});
    })
    .catch(() => {
      // Fallback to in-memory counts if fetch fails
      document.getElementById('statRegistered').textContent = students.length;
      document.getElementById('statCurrent').textContent    = sitInRecs.filter(r => r.status === 'Active').length;
      document.getElementById('statTotal').textContent      = sitInRecs.length;
      renderChart({});
    });
}

// ── UTILITY ────────────────────────────────────────────────
function escapeHtml(str) {
  const div = document.createElement('div');
  div.textContent = str;
  return div.innerHTML;
}

// ── CHART ──────────────────────────────────────────────────
let chartInst = null;
function renderChart(purposes) {
  // Use real data or demo placeholder if empty
  const labels = Object.keys(purposes).length  ? Object.keys(purposes)   : ['C#','C','Java','ASP.Net','PHP'];
  const vals   = Object.values(purposes).length ? Object.values(purposes) : [30, 20, 20, 15, 15];
  const colors = ['#00d4ff','#4d9fff','#00e5a0','#ffb347','#ff4d6a','#a78bfa','#34d399'];
  const ctx    = document.getElementById('purposeChart').getContext('2d');
  if (chartInst) chartInst.destroy();
  chartInst = new Chart(ctx, {
    type: 'doughnut',
    data: {
      labels,
      datasets: [{
        data: vals,
        backgroundColor: colors.slice(0, labels.length),
        borderWidth: 3,
        borderColor: '#10141c',
        hoverOffset: 6
      }]
    },
    options: {
      cutout: '60%',
      responsive: true,
      plugins: {
        legend: {
          position: 'right',
          labels: {
            color: '#7a8499',
            font: { size: 11, family: 'DM Sans' },
            padding: 14, boxWidth: 10, boxHeight: 10, usePointStyle: true
          }
        },
        tooltip: {
          backgroundColor: '#161b26',
          borderColor: 'rgba(0,212,255,0.2)',
          borderWidth: 1,
          titleColor: '#e8eaf0',
          bodyColor: '#7a8499',
          padding: 10
        }
      }
    }
  });
}

// ── ANALYTICS ──────────────────────────────────────────────
let analyticsPeriod = 'daily';
let analyticsCharts = {
  time: null,
  purpose: null,
  lab: null
};

function loadAnalyticsData(period = 'daily') {
  analyticsPeriod = period;
  
  // Update button states
  document.querySelectorAll('[id^="btn-period-"]').forEach(btn => {
    btn.classList.remove('active', 'btn-secondary');
    btn.classList.add('btn-outline-secondary');
  });
  document.getElementById(`btn-period-${period}`).classList.remove('btn-outline-secondary');
  document.getElementById(`btn-period-${period}`).classList.add('btn-secondary', 'active');

  fetch(`admin_analytics.php?period=${period}`)
    .then(r => {
      if (!r.ok) throw new Error(`HTTP ${r.status}`);
      return r.json();
    })
    .then(d => {
      if (d && d.success) {
        renderAnalyticsCards(d);
        renderAnalyticsCharts(d);
      } else {
        console.error('Analytics response error:', d);
        toast((d?.message || 'Failed to load analytics data'), 'danger');
      }
    })
    .catch(err => {
      console.error('Analytics fetch error:', err);
      toast('Error loading analytics: ' + err.message, 'danger');
    });
}

function renderAnalyticsCards(data) {
  document.getElementById('analyticsRegistered').textContent = data.registered ?? '—';
  document.getElementById('analyticsTotalSitins').textContent = data.total ?? '—';
  document.getElementById('analyticsActive').textContent = data.current ?? '—';
}

function renderAnalyticsCharts(data) {
  // Time series chart
  renderAnalyticsTimeChart(data.sitinsOverTime || []);
  
  // Purpose chart
  renderAnalyticsPurposeChart(data.purposes || {});
  
  // Lab chart
  renderAnalyticsLabChart(data.labs || {});
}

function renderAnalyticsTimeChart(timeData) {
  const ctx = document.getElementById('analyticsTimeChart');
  if (!ctx) return;
  
  if (analyticsCharts.time) analyticsCharts.time.destroy();
  
  const labels = timeData.map(d => d.label);
  const values = timeData.map(d => d.value);
  
  analyticsCharts.time = new Chart(ctx, {
    type: 'line',
    data: {
      labels: labels.length ? labels : ['No data'],
      datasets: [{
        label: 'Sit-in Sessions',
        data: values.length ? values : [0],
        borderColor: '#4d9fff',
        backgroundColor: 'rgba(77, 159, 255, 0.1)',
        borderWidth: 2,
        fill: true,
        tension: 0.4,
        pointRadius: 4,
        pointBackgroundColor: '#4d9fff',
        pointBorderColor: '#fff',
        pointBorderWidth: 2
      }]
    },
    options: {
      responsive: true,
      plugins: {
        legend: {
          display: true,
          labels: {
            color: '#7a8499',
            font: { size: 11, family: 'DM Sans' }
          }
        },
        tooltip: {
          backgroundColor: '#161b26',
          borderColor: 'rgba(77, 159, 255, 0.3)',
          borderWidth: 1,
          titleColor: '#e8eaf0',
          bodyColor: '#7a8499',
          padding: 10
        }
      },
      scales: {
        y: {
          beginAtZero: true,
          ticks: { color: '#7a8499' },
          grid: { color: 'rgba(255,255,255,0.06)' },
          border: { display: false }
        },
        x: {
          ticks: { color: '#7a8499' },
          grid: { color: 'rgba(255,255,255,0.06)' },
          border: { display: false }
        }
      }
    }
  });
}

function renderAnalyticsPurposeChart(purposes) {
  const ctx = document.getElementById('analyticsPurposeChart');
  if (!ctx) return;
  
  if (analyticsCharts.purpose) analyticsCharts.purpose.destroy();
  
  const labels = Object.keys(purposes).length ? Object.keys(purposes) : ['No data'];
  const values = Object.values(purposes).length ? Object.values(purposes) : [1];
  const colors = ['#00d4ff','#4d9fff','#00e5a0','#ffb347','#ff4d6a','#a78bfa','#34d399'];
  
  analyticsCharts.purpose = new Chart(ctx, {
    type: 'doughnut',
    data: {
      labels,
      datasets: [{
        data: values,
        backgroundColor: colors.slice(0, labels.length),
        borderWidth: 2,
        borderColor: '#10141c',
        hoverOffset: 6
      }]
    },
    options: {
      cutout: '60%',
      responsive: true,
      plugins: {
        legend: {
          position: 'right',
          labels: {
            color: '#7a8499',
            font: { size: 10, family: 'DM Sans' },
            padding: 10,
            boxWidth: 10,
            boxHeight: 10,
            usePointStyle: true
          }
        },
        tooltip: {
          backgroundColor: '#161b26',
          borderColor: 'rgba(0,212,255,0.2)',
          borderWidth: 1,
          titleColor: '#e8eaf0',
          bodyColor: '#7a8499',
          padding: 8
        }
      }
    }
  });
}

function renderAnalyticsLabChart(labs) {
  const ctx = document.getElementById('analyticsLabChart');
  if (!ctx) return;
  
  if (analyticsCharts.lab) analyticsCharts.lab.destroy();
  
  const labels = Object.keys(labs).length ? Object.keys(labs) : ['No data'];
  const values = Object.values(labs).length ? Object.values(labs) : [1];
  const colors = ['#00d4ff','#4d9fff','#00e5a0','#ffb347','#ff4d6a','#a78bfa','#34d399'];
  
  analyticsCharts.lab = new Chart(ctx, {
    type: 'bar',
    data: {
      labels,
      datasets: [{
        label: 'Sessions',
        data: values,
        backgroundColor: colors.slice(0, labels.length),
        borderRadius: 6,
        borderSkipped: false
      }]
    },
    options: {
      indexAxis: 'y',
      responsive: true,
      plugins: {
        legend: { display: false },
        tooltip: {
          backgroundColor: '#161b26',
          borderColor: 'rgba(0,212,255,0.2)',
          borderWidth: 1,
          titleColor: '#e8eaf0',
          bodyColor: '#7a8499',
          padding: 8
        }
      },
      scales: {
        x: {
          beginAtZero: true,
          ticks: { color: '#7a8499' },
          grid: { color: 'rgba(255,255,255,0.06)' },
          border: { display: false }
        },
        y: {
          ticks: { color: '#7a8499' },
          grid: { display: false },
          border: { display: false }
        }
      }
    }
  });
}

function renderRewardTable(leaderboard) {
  const tableBody = document.getElementById('rewardTableBody');
  if (!tableBody) return;

  if (!leaderboard || leaderboard.length === 0) {
    tableBody.innerHTML = `
      <div class="reward-table-row">
        <div class="reward-rank">—</div>
        <div class="reward-name"><strong>No data</strong><span></span></div>
        <div>—</div>
        <div>—</div>
        <div>—</div>
        <div>—</div>
        <div class="reward-points">—</div>
        <div></div>
      </div>
    `;
    return;
  }

  const rows = leaderboard.slice(0, 6).map(player => {
    const courseDisplay = player.course ? player.course.toUpperCase() : 'N/A';
    return `
      <div class="reward-table-row">
        <div class="reward-rank">${player.rank}</div>
        <div class="reward-name"><strong>${escapeHtml(player.name)}</strong><span>${escapeHtml(player.id_number)}</span></div>
        <div>${escapeHtml(courseDisplay)}</div>
        <div>${player.sit_in_count}</div>
        <div>${Number(player.total_hours || 0).toFixed(2)}</div>
        <div>${Number(player.avg_session || 0).toFixed(2)}</div>
        <div class="reward-points">${player.points} PTS</div>
        <div><button class="reward-action-btn" onclick="populateStudentId('${escapeHtml(player.id_number)}', '${escapeHtml(player.name)}' )">+ Points</button></div>
      </div>
    `;
  }).join('');

  tableBody.innerHTML = rows;
}

// ── RENDER LANGUAGES CHART ────────────────────────────────
let languagesChartInst = null;
function renderLanguagesChart(data = null) {
  const records = data || sitInRecs;
  const languages = {
    'C#': 0,
    'C': 0,
    'Java': 0,
    'ASP.NET': 0,
    'PHP': 0
  };
  
  // Count languages from records
  records.forEach(record => {
    const purpose = (record.purpose || '').toLowerCase();
    if (purpose.includes('c#')) languages['C#']++;
    else if (purpose.includes('c ') || purpose === 'c') languages['C']++;
    else if (purpose.includes('java')) languages['Java']++;
    else if (purpose.includes('asp')) languages['ASP.NET']++;
    else if (purpose.includes('php')) languages['PHP']++;
  });

  const ctx = document.getElementById('languagesChart');
  if (!ctx) return;
  
  if (languagesChartInst) languagesChartInst.destroy();
  languagesChartInst = new Chart(ctx.getContext('2d'), {
    type: 'bar',
    data: {
      labels: Object.keys(languages),
      datasets: [{
        label: 'Usage Count',
        data: Object.values(languages),
        backgroundColor: ['#00d4ff', '#4d9fff', '#00e5a0', '#ffb347', '#ff4d6a'],
        borderRadius: 4,
        borderSkipped: false
      }]
    },
    options: {
      indexAxis: 'y',
      responsive: true,
      maintainAspectRatio: false,
      plugins: {
        legend: { display: false },
        tooltip: {
          backgroundColor: '#161b26',
          borderColor: 'rgba(0,212,255,0.2)',
          borderWidth: 1,
          titleColor: '#e8eaf0',
          bodyColor: '#7a8499'
        }
      },
      scales: {
        x: {
          beginAtZero: true,
          ticks: { color: '#7a8499' },
          grid: { color: 'rgba(255,255,255,0.05)' }
        },
        y: {
          ticks: { color: '#7a8499' },
          grid: { display: false }
        }
      }
    }
  });
}

// ── RENDER ROOMS CHART ────────────────────────────────────
let roomsChartInst = null;
function renderRoomsChart(data = null) {
  const records = data || sitInRecs;
  const rooms = {
    '524': 0,
    '526': 0,
    '528': 0,
    '530': 0,
    '542': 0,
    'MAC': 0
  };
  
  // Count rooms from records
  records.forEach(record => {
    const lab = (record.lab || '').toUpperCase();
    if (lab.includes('524')) rooms['524']++;
    else if (lab.includes('526')) rooms['526']++;
    else if (lab.includes('528')) rooms['528']++;
    else if (lab.includes('530')) rooms['530']++;
    else if (lab.includes('542')) rooms['542']++;
    else if (lab.includes('MAC')) rooms['MAC']++;
  });

  const ctx = document.getElementById('roomsChart');
  if (!ctx) return;
  
  if (roomsChartInst) roomsChartInst.destroy();
  roomsChartInst = new Chart(ctx.getContext('2d'), {
    type: 'bar',
    data: {
      labels: Object.keys(rooms),
      datasets: [{
        label: 'Usage Count',
        data: Object.values(rooms),
        backgroundColor: ['#00d4ff', '#4d9fff', '#00e5a0', '#ffb347', '#ff4d6a', '#a78bfa'],
        borderRadius: 4,
        borderSkipped: false
      }]
    },
    options: {
      indexAxis: 'y',
      responsive: true,
      maintainAspectRatio: false,
      plugins: {
        legend: { display: false },
        tooltip: {
          backgroundColor: '#161b26',
          borderColor: 'rgba(0,212,255,0.2)',
          borderWidth: 1,
          titleColor: '#e8eaf0',
          bodyColor: '#7a8499'
        }
      },
      scales: {
        x: {
          beginAtZero: true,
          ticks: { color: '#7a8499' },
          grid: { color: 'rgba(255,255,255,0.05)' }
        },
        y: {
          ticks: { color: '#7a8499' },
          grid: { display: false }
        }
      }
    }
  });
}

// ── FETCH STUDENTS ─────────────────────────────────────────
function fetchStudents() {
  fetch('admin_students.php')
    .then(r => r.json())
    .then(d => { students = d; renderStudents(); })
    .catch(() => renderStudents());
}

// ── RENDER STUDENTS TABLE ──────────────────────────────────
function renderStudents() {
  const q   = (document.getElementById('stuSearch').value || '').toLowerCase();
  const pp  = parseInt(document.getElementById('stuEntries').value || 10);
  const data = students.filter(s =>
    (s.id_number + ' ' + s.first_name + ' ' + s.last_name + ' ' + s.course).toLowerCase().includes(q)
  );
  const total = data.length, pages = Math.max(1, Math.ceil(total / pp));
  if (stuPage > pages) stuPage = pages;
  const slice = data.slice((stuPage - 1) * pp, stuPage * pp);
  const tbody = document.getElementById('stuBody');

  if (!total) {
    tbody.innerHTML = `<tr><td colspan="6" class="no-data">
      <i class="fa-solid fa-user-slash" style="margin-right:.4rem;opacity:.4;"></i>No students found.
    </td></tr>`;
  } else {
    tbody.innerHTML = slice.map(s => {
      const sess = s.remaining_sessions ?? 30;
      // Color-code sessions: red if ≤5, amber if ≤10, green otherwise
      const sessColor = sess <= 5 ? 'var(--red)' : sess <= 10 ? 'var(--amber)' : 'var(--green)';
      return `<tr>
        <td><span class="badge-id">${s.id_number}</span></td>
        <td style="font-weight:600;">${s.first_name} ${s.middle_name ? s.middle_name[0] + '.' : ''} ${s.last_name}</td>
        <td style="color:var(--text2);">${s.year_level}</td>
        <td><span class="badge-course">${s.course}</span></td>
        <td><span style="font-weight:700;color:${sessColor};">${sess}</span></td>
        <td style="display:flex;gap:.3rem;">
          <button class="btn-a-primary" onclick="openEditStudent(${JSON.stringify(s).replace(/"/g,'&quot;')})">
            <i class="fa-solid fa-pen"></i> Edit
          </button>
          <button class="btn-a-danger" onclick="deleteStudent(${s.id})">
            <i class="fa-solid fa-trash"></i> Delete
          </button>
        </td>
      </tr>`;
    }).join('');
  }

  document.getElementById('stuInfo').textContent = total
    ? `Showing ${(stuPage - 1) * pp + 1}–${Math.min(stuPage * pp, total)} of ${total} entries`
    : 'Showing 0 entries';
  renderPagination('stuPagination', stuPage, pages, p => { stuPage = p; renderStudents(); });
}

// ── RENDER ALL SIT-IN RECORDS ──────────────────────────────
function renderRecords() {
  const q   = (document.getElementById('recSearch').value || '').toLowerCase();
  const pp  = parseInt(document.getElementById('recEntries').value || 10);
  const data = sitInRecs.filter(r =>
    (r.id_number + ' ' + r.name + ' ' + r.purpose + ' ' + r.lab).toLowerCase().includes(q)
  );
  const total = data.length, pages = Math.max(1, Math.ceil(total / pp));
  if (recPage > pages) recPage = pages;
  const slice = data.slice((recPage - 1) * pp, recPage * pp);
  const tbody = document.getElementById('recBody');

  if (!total) {
    tbody.innerHTML = `<tr><td colspan="7" class="no-data">
      <i class="fa-solid fa-circle-info" style="margin-right:.4rem;opacity:.4;"></i>No sit-in data available.
    </td></tr>`;
  } else {
    tbody.innerHTML = slice.map(r => `
      <tr>
        <td><span class="badge-id">${r.sit_id}</span></td>
        <td><span class="badge-id">${r.id_number}</span></td>
        <td style="font-weight:600;">${r.name}</td>
        <td style="color:var(--text2);">${r.purpose}</td>
        <td><span class="badge-lab">${r.lab}</span></td>
        <td style="color:var(--text2);">${r.session}</td>
        <td><span class="badge-${r.status === 'Active' ? 'active' : 'done'}">${r.status}</span></td>
      </tr>`).join('');
  }

  document.getElementById('recInfo').textContent = total
    ? `Showing ${(recPage - 1) * pp + 1}–${Math.min(recPage * pp, total)} of ${total} entries`
    : 'Showing 0 entries';
  renderPagination('recPagination', recPage, pages, p => { recPage = p; renderRecords(); });
}

// ── RENDER CURRENT SIT-IN (Active sessions only) ───────────
function loadReportData() {
  reportDate = document.getElementById('reportDate')?.value.trim() || '';
  reportQuery = document.getElementById('reportSearch')?.value.trim() || '';
  reportPage = 1;
  reportPerPage = parseInt(document.getElementById('reportEntries')?.value || 10);
  renderReports();
}

function renderReports() {
  const q = (document.getElementById('reportSearch')?.value || '').toLowerCase();
  const dd = document.getElementById('reportDate')?.value.trim();
  const pp = parseInt(document.getElementById('reportEntries')?.value || 10);
  const data = sitInRecs.filter(r => {
    const matchesText = (r.id_number + ' ' + r.name).toLowerCase().includes(q);
    const matchesDate = dd ? (
      formatDate(r.date) === dd ||
      formatDate(r.login) === dd ||
      formatDate(r.logout) === dd
    ) : true;
    return matchesText && matchesDate;
  });

  const total = data.length;
  const pages = Math.max(1, Math.ceil(total / pp));
  if (reportPage > pages) reportPage = pages;
  const start = (reportPage - 1) * pp;
  const slice = data.slice(start, start + pp);
  const tbody = document.getElementById('reportBody');

  if (!total) {
    tbody.innerHTML = `<tr><td colspan="7" class="no-data">
      <i class="fa-solid fa-circle-info" style="margin-right:.4rem;opacity:.4;"></i>No sit-in report data available.
    </td></tr>`;
  } else {
    tbody.innerHTML = slice.map(r => `
      <tr>
        <td>${r.id_number}</td>
        <td style="font-weight:600;">${r.name}</td>
        <td>${r.purpose}</td>
        <td>${r.lab}</td>
        <td>${formatTime(r.login)}</td>
        <td><span class="logout-time" data-sit-id="${r.sit_id}" data-status="${r.status}">${formatLogoutTime(r.logout, r.status)}</span></td>
        <td>${formatDate(r.date)}</td>
      </tr>`).join('');
  }

  document.getElementById('reportInfo').textContent = total
    ? `Showing ${start + 1} to ${Math.min(start + pp, total)} of ${total} entries`
    : 'Showing 0 entries';
  renderPagination('reportPagination', reportPage, pages, p => { reportPage = p; renderReports(); });
  
  // Update charts with filtered data
  renderLanguagesChart(data);
  renderRoomsChart(data);
}

function formatDate(value) {
  if (!value) return '—';
  const d = new Date(value);
  if (Number.isNaN(d.getTime())) return value;
  const mm = String(d.getMonth() + 1).padStart(2, '0');
  const dd = String(d.getDate()).padStart(2, '0');
  const yyyy = d.getFullYear();
  return `${mm}/${dd}/${yyyy}`;
}

function formatTime(value) {
  if (!value) return '—';
  const d = new Date(value);
  if (Number.isNaN(d.getTime())) return value;
  return d.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
}

// Format logout time - for active sessions, show current live time; for completed sessions, show actual logout time
function formatLogoutTime(logout, status) {
  if (status === 'Active') {
    // For active sessions, show current time (will update in real-time)
    return formatTime(new Date());
  }
  // For completed sessions, show the recorded logout time
  return formatTime(logout);
}

// Update live logout times for active sessions (called periodically)
function updateLiveLogoutTimes() {
  const logoutElements = document.querySelectorAll('.logout-time');
  logoutElements.forEach(el => {
    const status = el.getAttribute('data-status');
    if (status === 'Active') {
      el.textContent = formatTime(new Date());
    }
  });
}

function handleReportDate() {
  const raw = document.getElementById('reportDate')?.value.trim() || '';
  const parsed = normalizeReportDate(raw);
  if (!parsed && raw) {
    toast('Enter date as mm/dd/yyyy', 'warning');
    return;
  }
  document.getElementById('reportDate').value = parsed || '';
  reportPage = 1;
  renderReports();
}

function resetReportDate() {
  document.getElementById('reportDate').value = '';
  reportPage = 1;
  renderReports();
}

function normalizeReportDate(value) {
  const parts = value.split('/').map(p => p.trim());
  if (parts.length !== 3) return '';
  let [m, d, y] = parts;
  if (!/^[0-9]{1,2}$/.test(m) || !/^[0-9]{1,2}$/.test(d) || !/^[0-9]{2,4}$/.test(y)) return '';
  if (y.length === 2) y = '20' + y;
  const mm = String(Number(m)).padStart(2, '0');
  const dd = String(Number(d)).padStart(2, '0');
  const yyyy = y;
  const parsed = new Date(`${yyyy}-${mm}-${dd}`);
  if (Number.isNaN(parsed.getTime())) return '';
  return `${mm}/${dd}/${yyyy}`;
}

function exportReportsCSV() {
  exportReportTable('sit-in-report.csv');
}

function exportReportsExcel() {
  exportReportTable('sit-in-report.xls');
}

function generateFormalReportHTML() {
  const tableHtml = document.getElementById('reportTable')?.outerHTML || '';
  const now = new Date();
  const dateStr = now.toLocaleDateString('en-US', { year: 'numeric', month: 'long', day: 'numeric' });
  const timeStr = now.toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit' });
  
  // Calculate stats from the sitInRecs data directly
  const q = (document.getElementById('reportSearch')?.value || '').toLowerCase();
  const dd = document.getElementById('reportDate')?.value.trim();
  
  const filteredData = sitInRecs.filter(r => {
    const matchesText = (r.id_number + ' ' + r.name).toLowerCase().includes(q);
    const matchesDate = dd ? (
      formatDate(r.date) === dd ||
      formatDate(r.login) === dd ||
      formatDate(r.logout) === dd
    ) : true;
    return matchesText && matchesDate;
  });
  
  let totalRecords = filteredData.length;
  let activeCount = 0;
  let completedCount = 0;
  
  filteredData.forEach(r => {
    if (r.status === 'Active') activeCount++;
    else if (r.status === 'Done') completedCount++;
  });
  
  return `
    <!DOCTYPE html>
    <html>
      <head>
        <meta charset="UTF-8">
        <title>Sit-in Activity Report</title>
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
            margin-top: 10px;
          }
          .report-info span {
            display: inline-block;
            margin-right: 30px;
          }
          .stats-section {
            display: flex;
            justify-content: center;
            gap: 40px;
            margin: 25px 0;
            padding: 20px;
            background-color: #f5f5f5;
            border-radius: 4px;
          }
          .stat-box {
            text-align: center;
          }
          .stat-label {
            font-size: 12px;
            color: #666;
            margin-bottom: 6px;
          }
          .stat-value {
            font-size: 24px;
            font-weight: bold;
            color: #003366;
          }
          table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 25px;
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
          tr:hover {
            background-color: #f0f0f0;
          }
          .signature-section {
            margin-top: 40px;
            display: flex;
            justify-content: space-between;
            padding-top: 30px;
          }
          .signature-block {
            width: 45%;
          }
          .signature-line {
            border-top: 1px solid #1a1a1a;
            margin-top: 40px;
            padding-top: 5px;
            font-size: 11px;
            font-weight: bold;
            text-align: center;
          }
          .signature-title {
            font-size: 11px;
            font-weight: bold;
            margin-bottom: 50px;
            text-align: center;
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
          <div class="report-title">SIT-IN MONITORING SYSTEM</div>
          <div class="report-subtitle">College of Computer Studies — University of Cebu</div>
          <div class="report-info">
            <span><strong>Sit-in Activity Report</strong></span>
            <span>Generated: ${dateStr} at ${timeStr}</span>
          </div>
        </div>
        
        <div class="stats-section">
          <div class="stat-box">
            <div class="stat-label">Total Records</div>
            <div class="stat-value">${totalRecords}</div>
          </div>
          <div class="stat-box">
            <div class="stat-label">Active</div>
            <div class="stat-value">${activeCount}</div>
          </div>
          <div class="stat-box">
            <div class="stat-label">Completed</div>
            <div class="stat-value">${completedCount}</div>
          </div>
        </div>
        
        ${tableHtml}
        
        <div class="signature-section">
          <div class="signature-block">
            <div class="signature-title">Prepared by</div>
            <div class="signature-line">LAB-IN-CHARGE</div>
          </div>
          <div class="signature-block">
            <div class="signature-title">Noted by</div>
            <div class="signature-line">CCS DEAN / DEPARTMENT HEAD</div>
          </div>
        </div>
        
        <div class="footer">
          CCS Sit-in Monitoring System | UC — College of Computer Studies<br>
          Printed: ${dateStr} at ${timeStr}
        </div>
      </body>
    </html>
  `;
}

function exportReportsPDF() {
  const html = generateFormalReportHTML();
  const printWindow = window.open('', '_blank');
  if (!printWindow) return;
  printWindow.document.write(html);
  printWindow.document.close();
  printWindow.focus();
  printWindow.print();
}

function printReports() {
  exportReportsPDF();
}

function exportReportTable(filename) {
  const q = (document.getElementById('reportSearch')?.value || '').toLowerCase();
  const dd = document.getElementById('reportDate')?.value.trim();
  
  // Filter data same way as renderReports
  const filteredData = sitInRecs.filter(r => {
    const matchesText = (r.id_number + ' ' + r.name).toLowerCase().includes(q);
    const matchesDate = dd ? (
      formatDate(r.date) === dd ||
      formatDate(r.login) === dd ||
      formatDate(r.logout) === dd
    ) : true;
    return matchesText && matchesDate;
  });

  if (!filteredData.length) {
    alert('No data to export');
    return;
  }

  // Build CSV with proper formatting
  const headers = ['ID Number', 'Name', 'Purpose', 'Laboratory', 'Login', 'Logout', 'Date'];
  const rows = filteredData.map(r => {
    // Extract and format date - try multiple sources
    let dateStr = '';
    
    // Try r.date first
    if (r.date && r.date !== 'null' && r.date !== '') {
      const formatted = formatDate(r.date);
      if (formatted !== '—') dateStr = formatted;
    }
    
    // Fallback to extracting date from login time if date is empty
    if (!dateStr && r.login && r.login !== 'null' && r.login !== '') {
      const formatted = formatDate(r.login);
      if (formatted !== '—') dateStr = formatted;
    }
    
    // If still empty, provide a default "N/A" for clarity
    if (!dateStr) {
      dateStr = 'N/A';
    }
    
    return [
      (r.id_number || '').toString().trim(),
      (r.name || '').toString().trim(),
      (r.purpose || '').toString().trim(),
      (r.lab || '').toString().trim(),
      (formatTime(r.login) || '').toString().trim(),
      (formatTime(r.logout) || '').toString().trim(),
      dateStr.toString().trim()
    ];
  });
  
  const csv = [headers, ...rows]
    .map(row => row.map(cell => '"' + (cell || '').replace(/"/g, '""') + '"').join(','))
    .join('\n');
  
  const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
  const link = document.createElement('a');
  link.href = URL.createObjectURL(blob);
  link.download = filename;
  document.body.appendChild(link);
  link.click();
  document.body.removeChild(link);
}

function renderCurrentSitIn() {
  const q   = (document.getElementById('curSearch').value || '').toLowerCase();
  const pp  = parseInt(document.getElementById('curEntries').value || 10);
  const data = sitInRecs.filter(r =>
    r.status === 'Active' &&
    (r.id_number + ' ' + r.name + ' ' + r.purpose + ' ' + r.lab).toLowerCase().includes(q)
  );
  const total = data.length, pages = Math.max(1, Math.ceil(total / pp));
  if (curPage > pages) curPage = pages;
  const slice = data.slice((curPage - 1) * pp, curPage * pp);
  const tbody = document.getElementById('curBody');

  if (!total) {
    tbody.innerHTML = `<tr><td colspan="8" class="no-data">
      <i class="fa-solid fa-circle-info" style="margin-right:.4rem;opacity:.4;"></i>No active sit-in sessions right now.
    </td></tr>`;
  } else {
    tbody.innerHTML = slice.map(r => `
      <tr>
        <td><span class="badge-id">${r.sit_id}</span></td>
        <td><span class="badge-id">${r.id_number}</span></td>
        <td style="font-weight:600;">${r.name}</td>
        <td style="color:var(--text2);">${r.purpose}</td>
        <td><span class="badge-lab">${r.lab}</span></td>
        <td style="color:var(--text2);">${r.session}</td>
        <td><span class="badge-active">Active</span></td>
        <td>
          <button class="btn-a-danger" onclick="timeOutAndRefresh(${r.sit_id})">
            <i class="fa-solid fa-clock"></i> Time Out
          </button>
        </td>
      </tr>`).join('');
  }

  document.getElementById('curInfo').textContent = total
    ? `Showing ${(curPage - 1) * pp + 1}–${Math.min(curPage * pp, total)} of ${total} active sessions`
    : 'No active sessions';
  renderPagination('curPagination', curPage, pages, p => { curPage = p; renderCurrentSitIn(); });
}

// Time out a specific sit-in, then refresh data from DB
function timeOutAndRefresh(sitId) {
  fetch('admin_sitin_timeout.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ sit_id: sitId })
  })
  .then(r => r.json())
  .then(d => {
    if (!d.success) { toast('Could not time out.', 'danger'); return; }
    toast('Student timed out.', 'warning');
    fetchSitInRecords(); // reload from DB so data stays accurate
  })
  .catch(() => {
    // Offline fallback — update in-memory state
    const rec = sitInRecs.find(r => r.sit_id === sitId);
    if (rec) rec.status = 'Done';
    renderCurrentSitIn();
    renderRecords();
    loadStats();
    toast('Student timed out.', 'warning');
  });
}

// ── PAGINATION RENDERER ────────────────────────────────────
function renderPagination(id, cur, total, cb) {
  const el = document.getElementById(id);
  let h = `<button class="pg-btn" ${cur === 1 ? 'disabled' : ''} onclick="(${cb})(${cur - 1})">‹</button>`;
  const start = Math.max(1, cur - 2), end = Math.min(total, cur + 2);
  if (start > 1) h += `<button class="pg-btn" onclick="(${cb})(1)">1</button><span style="color:var(--text3);padding:0 3px;">…</span>`;
  for (let i = start; i <= end; i++) {
    h += `<button class="pg-btn${i === cur ? ' active' : ''}" onclick="(${cb})(${i})">${i}</button>`;
  }
  if (end < total) h += `<span style="color:var(--text3);padding:0 3px;">…</span><button class="pg-btn" onclick="(${cb})(${total})">${total}</button>`;
  h += `<button class="pg-btn" ${cur === total ? 'disabled' : ''} onclick="(${cb})(${cur + 1})">›</button>`;
  el.innerHTML = h;
}

// ── LIVE SEARCH (fetches from search_student.php) ──────────
function openSearch() {
  document.getElementById('searchInput').value = '';
  document.getElementById('searchResults').innerHTML = `
    <div class="search-hint">
      <i class="fa-solid fa-user-magnifying-glass"></i>
      Type to search students from the database
    </div>`;
  new bootstrap.Modal(document.getElementById('modalSearch')).show();
  setTimeout(() => document.getElementById('searchInput').focus(), 300);
}

function liveSearch() {
  const q   = document.getElementById('searchInput').value.trim();
  const res = document.getElementById('searchResults');
  if (!q) {
    res.innerHTML = `<div class="search-hint"><i class="fa-solid fa-user-magnifying-glass"></i>Type to search students from the database</div>`;
    return;
  }
  clearTimeout(searchTimer);
  // Debounce 300ms to avoid hammering the server on every keystroke
  searchTimer = setTimeout(async () => {
    res.innerHTML = `<div class="loading-row"><div class="spinner"></div> Searching database…</div>`;
    try {
      const r    = await fetch(`search_student.php?q=${encodeURIComponent(q)}`);
      const data = await r.json();
      if (!data.length) {
        res.innerHTML = `<div class="search-hint"><i class="fa-solid fa-userz-slash"></i>No student found for "<strong style="color:var(--text1);">${q}</strong>"</div>`;
        return;
      }
      res.innerHTML = data.map(s => {
        const initials = ((s.firstname || '?')[0] + (s.lastname || '?')[0]).toUpperCase();
        const safeData = encodeURIComponent(JSON.stringify(s));
        return `<div class="search-result-card" onclick="selectStudentForSitIn(JSON.parse(decodeURIComponent('${safeData}')))">
          <div class="src-avatar">${initials}</div>
          <div class="src-info">
            <div class="src-name">${s.firstname} ${s.lastname}</div>
            <div class="src-meta">
              <span><i class="fa-solid fa-id-card" style="margin-right:3px;"></i>${s.id}</span>
              <span><i class="fa-solid fa-book" style="margin-right:3px;"></i>${s.course}</span>
              <span><i class="fa-solid fa-layer-group" style="margin-right:3px;"></i>Year ${s.year}</span>
            </div>
          </div>
          <div class="src-session"><i class="fa-solid fa-rotate" style="margin-right:3px;"></i>${s.remaining_sessions ?? 30} sess.</div>
          <div style="margin-left:8px;color:var(--accent);font-size:.7rem;font-weight:700;white-space:nowrap;">
            <i class="fa-solid fa-chair"></i> Sit In
          </div>
        </div>`;
      }).join('');
    } catch (e) {
      // Fallback: filter local students array when DB unreachable
      const found = students.filter(s =>
        (s.id_number + ' ' + s.first_name + ' ' + s.last_name).toLowerCase().includes(q.toLowerCase())
      );
      if (!found.length) {
        res.innerHTML = `<div class="search-hint"><i class="fa-solid fa-user-slash"></i>No results found.</div>`;
        return;
      }
      res.innerHTML = found.map(s => {
        const initials  = ((s.first_name || '?')[0] + (s.last_name || '?')[0]).toUpperCase();
        const mapped    = { id: s.id_number, firstname: s.first_name, lastname: s.last_name, course: s.course, year: s.year_level, remaining_sessions: s.remaining_sessions ?? 30 };
        const safeData  = encodeURIComponent(JSON.stringify(mapped));
        return `<div class="search-result-card" onclick="selectStudentForSitIn(JSON.parse(decodeURIComponent('${safeData}')))">
          <div class="src-avatar">${initials}</div>
          <div class="src-info">
            <div class="src-name">${s.first_name} ${s.last_name}</div>
            <div class="src-meta">
              <span><i class="fa-solid fa-id-card" style="margin-right:3px;"></i>${s.id_number}</span>
              <span><i class="fa-solid fa-book" style="margin-right:3px;"></i>${s.course}</span>
              <span><i class="fa-solid fa-layer-group" style="margin-right:3px;"></i>Year ${s.year_level}</span>
            </div>
          </div>
          <div class="src-session"><i class="fa-solid fa-rotate" style="margin-right:3px;"></i>${s.remaining_sessions ?? 30} sess.</div>
          <div style="margin-left:8px;color:var(--accent);font-size:.7rem;font-weight:700;white-space:nowrap;">
            <i class="fa-solid fa-chair"></i> Sit In
          </div>
        </div>`;
      }).join('');
    }
  }, 300);
}

// ── FETCH ALL SIT-IN RECORDS FROM DB ──────────────────────
function fetchSitInRecords() {
  fetch('admin_sitin_fetch.php?filter=all')
    .then(r => r.json())
    .then(d => {
      sitInRecs = d.map(r => ({
        sit_id:     r.sit_id,
        id_number:  r.id_number,
        name:       r.name,
        purpose:    r.purpose,
        lab:        r.lab,
        session:    r.session,
        status:     r.status,
        login:      r.login,
        logout:     r.logout,
        date:       r.date,
        created_at: r.created_at
      }));
      renderRecords();
      renderCurrentSitIn();
      loadReportData();
      loadStats();
    })
    .catch(() => { /* keep existing sitInRecs if offline */ });
}

// ── SIT-IN MODAL ───────────────────────────────────────────
function openSitInModal() {
  // Clear form fields before opening
  ['siIdNum', 'siName', 'siSession', 'siPCNo'].forEach(id => document.getElementById(id).value = '');
  document.getElementById('siPurpose').value = '';
  document.getElementById('siLab').value     = '';
  new bootstrap.Modal(document.getElementById('modalSitIn')).show();
}

// Triggered from search modal: pre-fill the sit-in form with selected student
function selectStudentForSitIn(s) {
  const searchModalEl = document.getElementById('modalSearch');
  const searchModal   = bootstrap.Modal.getInstance(searchModalEl);
  if (searchModal) searchModal.hide();
  searchModalEl.addEventListener('hidden.bs.modal', function handler() {
    searchModalEl.removeEventListener('hidden.bs.modal', handler);
    document.getElementById('siIdNum').value   = s.id            ?? '';
    document.getElementById('siName').value    = (s.firstname + ' ' + s.lastname).trim();
    document.getElementById('siSession').value = s.remaining_sessions ?? 30;
    document.getElementById('siPurpose').value = '';
    document.getElementById('siLab').value     = '';
    document.getElementById('siPCNo').value    = '';
    new bootstrap.Modal(document.getElementById('modalSitIn')).show();
  });
}

// Auto-fill name & sessions when admin types an ID number directly
function lookupStudent() {
  const id  = document.getElementById('siIdNum').value.trim();
  const stu = students.find(s => s.id_number === id);
  if (stu) {
    document.getElementById('siName').value    = stu.first_name + ' ' + stu.last_name;
    document.getElementById('siSession').value = stu.remaining_sessions ?? 30;
  } else {
    document.getElementById('siName').value    = '';
    document.getElementById('siSession').value = '';
  }
}

function submitSitIn() {
  const id      = document.getElementById('siIdNum').value.trim();
  const name    = document.getElementById('siName').value;
  const purpose = document.getElementById('siPurpose').value.trim();
  const lab     = document.getElementById('siLab').value;
  const pcNo    = document.getElementById('siPCNo').value.trim();
  if (!id || !name || !purpose || !lab) { alert('Please fill in all fields.'); return; }

  const payload = { id_number: id, purpose, lab_id: lab };
  if (pcNo) payload.computer_number = parseInt(pcNo);

  fetch('admin_sitin_submit.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify(payload)
  })
  .then(r => r.json())
  .then(d => {
    if (!d.success) { alert(d.message || 'Could not save sit-in.'); return; }
    bootstrap.Modal.getInstance(document.getElementById('modalSitIn')).hide();
    toast('Student sat in successfully!');
    fetchSitInRecords(); // reload from DB so table is fresh
    showView('current-sitin');
    fetchStudents();     // refresh session counts
  })
  .catch(() => {
    // Offline fallback — store in memory only
    sitInRecs.push({
      sit_id: sitInRecs.length + 1, id_number: id, name, purpose, lab,
      session: document.getElementById('siSession').value, status: 'Active'
    });
    bootstrap.Modal.getInstance(document.getElementById('modalSitIn')).hide();
    toast('Saved locally (DB unavailable).', 'warning');
    renderCurrentSitIn();
    showView('current-sitin');
  });
}

function timeOut(sitId) { timeOutAndRefresh(sitId); }

// ── ADD STUDENT ────────────────────────────────────────────
function openAddStudent() {
  ['asId','asFn','asLn','asMn','asEm','asPw'].forEach(id => document.getElementById(id).value = '');
  new bootstrap.Modal(document.getElementById('modalAddStudent')).show();
}

function submitAddStudent() {
  const id = document.getElementById('asId').value.trim();
  const fn = document.getElementById('asFn').value.trim();
  const ln = document.getElementById('asLn').value.trim();
  const mn = document.getElementById('asMn').value.trim();
  const co = document.getElementById('asCo').value;
  const yr = document.getElementById('asYr').value;
  const em = document.getElementById('asEm').value.trim();
  const pw = document.getElementById('asPw').value;
  if (!id || !fn || !ln || !em || !pw) { alert('Please fill in all required fields.'); return; }

  fetch('admin_add_student.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ id_number: id, first_name: fn, last_name: ln, middle_name: mn, course: co, year_level: yr, email: em, password: pw })
  })
  .then(r => r.json())
  .then(d => {
    if (d.success) {
      bootstrap.Modal.getInstance(document.getElementById('modalAddStudent')).hide();
      toast('Student added!');
      fetchStudents();
    } else { alert(d.message); }
  })
  .catch(() => {
    // Offline fallback
    students.push({ id: Date.now(), id_number: id, first_name: fn, last_name: ln, middle_name: mn, course: co, year_level: yr, email: em, remaining_sessions: 30 });
    bootstrap.Modal.getInstance(document.getElementById('modalAddStudent')).hide();
    toast('Student added!');
    renderStudents();
  });
}

// ── EDIT STUDENT ───────────────────────────────────────────
function openEditStudent(s) {
  document.getElementById('editId').value   = s.id;
  document.getElementById('editFn').value   = s.first_name;
  document.getElementById('editLn').value   = s.last_name;
  document.getElementById('editCo').value   = s.course;
  document.getElementById('editYr').value   = s.year_level;
  document.getElementById('editSess').value = s.remaining_sessions ?? 30;
  new bootstrap.Modal(document.getElementById('modalEditStudent')).show();
}

function submitEditStudent() {
  const id   = document.getElementById('editId').value;
  const fn   = document.getElementById('editFn').value.trim();
  const ln   = document.getElementById('editLn').value.trim();
  const co   = document.getElementById('editCo').value;
  const yr   = document.getElementById('editYr').value;
  const sess = document.getElementById('editSess').value;

  fetch('admin_edit_student.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ id, first_name: fn, last_name: ln, course: co, year_level: yr, remaining_sessions: sess })
  })
  .then(r => r.json())
  .then(d => {
    if (d.success) { toast('Student updated!'); fetchStudents(); }
    else { alert(d.message); }
  })
  .catch(() => {
    // Offline fallback
    const s = students.find(x => x.id == id);
    if (s) { s.first_name = fn; s.last_name = ln; s.course = co; s.year_level = yr; s.remaining_sessions = sess; }
    toast('Student updated!');
    renderStudents();
  });
  bootstrap.Modal.getInstance(document.getElementById('modalEditStudent')).hide();
}

// ── DELETE STUDENT ─────────────────────────────────────────
function deleteStudent(id) {
  if (!confirm('Delete this student? This cannot be undone.')) return;
  fetch('admin_delete_student.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ id })
  })
  .then(r => r.json())
  .then(() => { toast('Student deleted.', 'danger'); fetchStudents(); })
  .catch(() => { students = students.filter(s => s.id != id); toast('Student deleted.', 'danger'); renderStudents(); });
}

// ── RESET ALL SESSIONS ─────────────────────────────────────
function confirmResetAll() {
  if (!confirm('Reset all student sessions to 30?')) return;
  students.forEach(s => s.remaining_sessions = 30);
  toast('All sessions reset to 30.');
  renderStudents();
}

// ── ANNOUNCEMENTS ──────────────────────────────────────────
function postAnnouncement() {
  const text = document.getElementById('annText').value.trim();
  if (!text) { alert('Please enter an announcement.'); return; }

  fetch('admin_announcement_post.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ body: text })
  })
  .then(res => res.json())
  .then(data => {
    if (data.success) {
      document.getElementById('annText').value = '';
      toast('Announcement posted!');
      fetchAnnouncements(); // Refresh the list
    } else {
      alert('Error: ' + data.message);
    }
  })
  .catch(err => {
    console.error('Error posting announcement:', err);
    alert('Failed to post announcement.');
  });
}

function fetchAnnouncements() {
  fetch('announcement_fetch.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' }
  })
  .then(res => res.json())
  .then(data => {
    if (data.success) {
      const annList = document.getElementById('annList');
      annList.innerHTML = '';
      data.announcements.forEach(ann => {
        const date = new Date(ann.created_at);
        const label = `${date.getFullYear()}-${date.toLocaleString('en', { month: 'short' })}-${String(date.getDate()).padStart(2, '0')}`;
        const item = document.createElement('div');
        item.className = 'ann-item';
        item.innerHTML = `<div class="ann-meta"><span class="ann-badge">${ann.posted_by}</span> ${label}</div><div class="ann-text">${ann.body}</div>`;
        annList.appendChild(item);
      });
      if (data.announcements.length === 0) {
        annList.innerHTML = '<div class="ann-item"><div class="ann-meta"><span class="ann-badge">System</span></div><div class="ann-text ann-empty">No announcements yet.</div></div>';
      }
    }
  })
  .catch(err => console.error('Error fetching announcements:', err));
}

// ── DISABLED DATES MANAGEMENT ─────────────────────────────
async function submitDisableDate() {
  const labId = document.getElementById('disableLab').value;
  const date = document.getElementById('disableDate').value;
  const reason = document.getElementById('disableReason').value.trim();

  if (!labId || !date) {
    toast('Please select a lab and date.', 'warning');
    return;
  }

  const data = await postJSON('reservation_disabled_dates.php', {
    action: 'disable',
    lab_id: labId,
    date: date,
    reason: reason || 'No classes'
  });

  if (!data || !data.success) {
    toast(data?.message || 'Failed to disable date.', 'danger');
    return;
  }

  toast(data.message || 'Date disabled successfully.', 'success');
  loadDisabledDates();
  document.getElementById('disableDate').value = '';
}

async function enableDate(labId, date) {
  if (!confirm(`Enable reservations for ${date}?`)) return;

  const data = await postJSON('reservation_disabled_dates.php', {
    action: 'enable',
    lab_id: labId,
    date: date
  });

  if (!data || !data.success) {
    toast(data?.message || 'Failed to enable date.', 'danger');
    return;
  }

  toast(data.message || 'Date enabled successfully.', 'success');
  loadDisabledDates();
}

async function loadDisabledDates() {
  const data = await postJSON('reservation_disabled_dates.php', { action: 'fetch' });
  if (!data || !data.success) return;

  const container = document.getElementById('disabledDatesList');
  const disabledDates = data.disabled_dates || [];

  if (!disabledDates.length) {
    container.innerHTML = '<div class="no-data">No disabled dates.</div>';
    return;
  }

  container.innerHTML = disabledDates.map(item => {
    const labName = item.lab_name || `Lab ${item.lab_id}`;
    const reason = item.reason || 'No classes';
    return `
      <div class="d-flex justify-content-between align-items-center mb-2 p-2 border rounded">
        <div>
          <div><strong>${labName}</strong> - ${item.disabled_date}</div>
          <div style="font-size:.8rem;color:var(--text2);">${reason}</div>
        </div>
        <button class="btn btn-success btn-sm" onclick="enableDate(${item.lab_id || 'null'}, '${item.disabled_date}')">
          <i class="fa-solid fa-check"></i> Enable
        </button>
      </div>
    `;
  }).join('');
}

// ── LOGOUT ─────────────────────────────────────────────────
function confirmLogout() { new bootstrap.Modal(document.getElementById('modalLogout')).show(); }
function doLogout()      { window.location.href = 'logout.php'; }

// ── KEYBOARD SHORTCUT: Cmd/Ctrl+K → open search ────────────
document.addEventListener('keydown', e => {
  if ((e.metaKey || e.ctrlKey) && e.key === 'k') {
    e.preventDefault();
    openSearch();
  }
});

// ── REWARD PANEL FUNCTIONS ────────────────────────────────
function populateStudentId(studentId, studentName) {
  document.getElementById('rewardStudentId').value = studentId;
  document.getElementById('rewardStudentId').focus();
}

async function awardPointsToStudent() {
  const studentId = document.getElementById('rewardStudentId')?.value?.trim();
  const points = document.getElementById('rewardPoints')?.value?.trim();
  const reason = document.getElementById('rewardReason')?.value?.trim();

  if (!studentId) {
    toast('Please enter a student ID number.', 'warning');
    return;
  }
  if (!points || isNaN(points) || Number(points) <= 0) {
    toast('Please enter a valid number of points to add.', 'warning');
    return;
  }

  const payload = {
    id_number: studentId,
    points: Number(points),
    reason: reason
  };

  const response = await postJSON('admin_award_points.php', payload);
  if (!response) {
    toast('Unable to communicate with the server.', 'danger');
    return;
  }

  if (!response.success) {
    toast(response.message || 'Failed to award points.', 'danger');
    return;
  }

  toast(response.message || `Added ${points} points to student ${studentId}.`, 'success');
  loadLeaderboard();

  document.getElementById('rewardStudentId').value = '';
  document.getElementById('rewardPoints').value = '';
  document.getElementById('rewardReason').value = '';
}

// ── INIT: load all data on page ready ─────────────────────
document.addEventListener('DOMContentLoaded', () => {
  fetchStudents();
  fetchSitInRecords();
  loadStats();
  fetchAnnouncements();
  
  // Periodically refresh sit-in records every 10 seconds
  setInterval(fetchSitInRecords, 10000);
  setInterval(loadStats, 30000);

  const hash = window.location.hash.toLowerCase();
  if (hash === '#reservation' || hash === '#reservations') {
    showView('reservation');
  }

  const searchParams = new URLSearchParams(window.location.search);
  if (searchParams.get('view') === 'reservation') {
    showView('reservation');
  }
});

// ════════════════════════════════════════════════════════════
// SOFTWARE IMPORT/UPLOAD FUNCTIONS
// ════════════════════════════════════════════════════════════

// Global variables for software management
let selectedLabs = [];
let allRegisteredSoftware = []; // Store for search filtering

// Load labs on page init
async function loadSoftwareLabs() {
  try {
    const response = await fetch('admin_software_upload.php?action=get_labs');
    const data = await response.json();
    
    if (data.success && data.labs) {
      const container = document.getElementById('labCheckboxes');
      container.innerHTML = '';
      
      data.labs.forEach(lab => {
        const checkboxHtml = `
          <label style="display: flex; align-items: center; cursor: pointer; padding: 0.5rem; gap: 0.5rem; user-select: none;">
            <input type="checkbox" value="${lab.id}" class="lab-checkbox" onchange="updateSelectedLabs()" style="cursor: pointer;" />
            <span style="font-size: 0.85rem; color: var(--text1);">${lab.name}</span>
          </label>
        `;
        container.innerHTML += checkboxHtml;
      });
    }
  } catch (err) {
    console.error('Error loading labs:', err);
  }
}

// Update selected labs array
function updateSelectedLabs() {
  const checkboxes = document.querySelectorAll('.lab-checkbox:checked');
  selectedLabs = Array.from(checkboxes).map(cb => cb.value);
}

// Submit software registration
async function submitSoftwareUpload() {
  const softwareName = document.getElementById('softwareName').value.trim();
  const category = document.getElementById('softwareCategory').value.trim();
  
  if (!softwareName) {
    toast('Please enter software name', 'warning');
    return;
  }
  
  if (!category) {
    toast('Please select a category', 'warning');
    return;
  }
  
  if (selectedLabs.length === 0) {
    toast('Please select at least one lab', 'warning');
    return;
  }
  
  const payload = {
    software_name: softwareName,
    category: category,
    labs: JSON.stringify(selectedLabs)
  };
  
  try {
    const response = await fetch('admin_software_upload.php?action=upload', {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body: new URLSearchParams(payload)
    });
    
    const data = await response.json();
    
    if (data.success) {
      toast(data.message, 'success');
      
      // Reset form
      document.getElementById('softwareName').value = '';
      document.getElementById('softwareCategory').value = '';
      document.querySelectorAll('.lab-checkbox').forEach(cb => cb.checked = false);
      selectedLabs = [];
      document.getElementById('softwareSearch').value = '';
      
      // Refresh lists
      loadSoftwareOverview();
      loadRegisteredSoftware();
    } else {
      toast(data.message || 'Failed to add software', 'danger');
    }
  } catch (err) {
    console.error('Add software error:', err);
    toast('Error adding software', 'danger');
  }
}

// Load software overview
async function loadSoftwareOverview() {
  try {
    const response = await fetch('admin_software_upload.php?action=get_overview');
    const data = await response.json();
    
    if (data.success && data.overview) {
      const container = document.getElementById('labOverview');
      container.innerHTML = '';
      
      data.overview.forEach(lab => {
        const card = document.createElement('div');
        card.className = 'col-md-4 col-sm-6';
        card.innerHTML = `
          <div style="background: var(--surface2); border: 1px solid var(--border); border-radius: var(--radius); padding: 1.5rem; text-align: center;">
            <div style="color: var(--text1); font-weight: 600; margin-bottom: 0.75rem;">${lab.lab_name}</div>
            <div style="font-size: 2rem; font-weight: 700; color: var(--accent); margin-bottom: 0.5rem;">${lab.software_count}</div>
            <div style="font-size: 0.85rem; color: var(--text2);">Software</div>
          </div>
        `;
        container.appendChild(card);
      });
    }
  } catch (err) {
    console.error('Error loading overview:', err);
  }
}

// Helper function to get software icon
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

// Load registered software list
async function loadRegisteredSoftware() {
  try {
    const response = await fetch('admin_software_upload.php?action=get_software');
    const data = await response.json();
    
    if (data.success && data.software) {
      allRegisteredSoftware = data.software;
      renderRegisteredSoftware(data.software);
    }
  } catch (err) {
    console.error('Error loading software:', err);
  }
}

// Render registered software with icons
function renderRegisteredSoftware(softwareList) {
  const container = document.getElementById('registeredSoftware');
  
  if (!softwareList || softwareList.length === 0) {
    container.innerHTML = '<div class="no-data">No software registered yet.</div>';
    return;
  }
  
  let html = '<div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(140px, 1fr)); gap: 12px;">';
  
  softwareList.forEach(soft => {
    const iconData = getSoftwareIcon(soft.name);
    const uploadDate = new Date(soft.uploaded_at).toLocaleDateString();
    const labs = soft.lab_name ? soft.lab_name.split(', ').slice(0, 2).join(', ') : 'N/A';
    const labsMore = soft.lab_name ? soft.lab_name.split(', ').length > 2 ? `+${soft.lab_name.split(', ').length - 2}` : '' : '';
    
    html += `
      <div style="
        background: var(--surface2);
        border: 1px solid var(--border);
        border-radius: 10px;
        padding: 12px;
        text-align: center;
        transition: all 0.3s ease;
        position: relative;
      "
      onmouseover="this.style.borderColor='var(--accent2)'; this.style.boxShadow='0 4px 12px rgba(122, 0, 204, 0.2)';"
      onmouseout="this.style.borderColor='var(--border)'; this.style.boxShadow='none';">
        <div style="
          width: 48px;
          height: 48px;
          background: rgba(${hexToRgb(iconData.color).join(', ')}, 0.1);
          border-radius: 8px;
          display: flex;
          align-items: center;
          justify-content: center;
          margin: 0 auto 8px;
        ">
          <i class="fa-solid ${iconData.icon}" style="color: ${iconData.color}; font-size: 1.5rem;"></i>
        </div>
        <div style="color: var(--text1); font-weight: 600; font-size: 0.85rem; margin-bottom: 4px; word-break: break-word;">${soft.name}</div>
        <div style="color: var(--text2); font-size: 0.75rem; margin-bottom: 6px;">${soft.category}</div>
        <div style="color: var(--text2); font-size: 0.7rem; margin-bottom: 8px;">${labs} ${labsMore}</div>
        <button class="btn btn-sm btn-outline-danger" onclick="deleteSoftware(${soft.id})" style="font-size: 0.75rem; padding: 2px 6px;">
          <i class="fa-solid fa-trash"></i>
        </button>
      </div>
    `;
  });
  
  html += '</div>';
  container.innerHTML = html;
}

// Filter registered software by search
function filterRegisteredSoftware() {
  const query = document.getElementById('softwareSearch').value.toLowerCase();
  
  if (!query) {
    renderRegisteredSoftware(allRegisteredSoftware);
    return;
  }
  
  const filtered = allRegisteredSoftware.filter(soft => 
    soft.name.toLowerCase().includes(query) || 
    soft.category.toLowerCase().includes(query) ||
    (soft.lab_name && soft.lab_name.toLowerCase().includes(query))
  );
  
  renderRegisteredSoftware(filtered);
}

// Helper function to convert hex to rgb
function hexToRgb(hex) {
  const result = /^#?([a-f\d]{2})([a-f\d]{2})([a-f\d]{2})$/i.exec(hex);
  return result ? [
    parseInt(result[1], 16),
    parseInt(result[2], 16),
    parseInt(result[3], 16)
  ] : [219, 121, 255];
}

// Delete software
async function deleteSoftware(id) {
  if (!confirm('Are you sure you want to delete this software?')) return;
  
  try {
    const formData = new URLSearchParams();
    formData.append('id', id);
    
    const response = await fetch('admin_software_upload.php?action=delete', {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body: formData
    });
    
    const data = await response.json();
    
    if (data.success) {
      toast('Software deleted successfully', 'success');
      loadRegisteredSoftware();
      loadSoftwareOverview();
    } else {
      toast(data.message || 'Delete failed', 'danger');
    }
  } catch (err) {
    console.error('Delete error:', err);
    toast('Error deleting software', 'danger');
  }
}

// ════════════════════════════════════════════════════════════
// LEADERBOARD SETTINGS FUNCTIONS
// ════════════════════════════════════════════════════════════

async function loadLeaderboardSettings() {
  try {
    const response = await fetch('admin_settings_toggle.php', {
      method: 'GET'
    });
    const data = await response.json();
    
    if (data.success && data.settings) {
      const toggle = document.getElementById('leaderboardToggle');
      if (toggle) {
        toggle.checked = data.settings.leaderboard_enabled === 'true';
      }
    }
  } catch (err) {
    console.error('Error loading leaderboard settings:', err);
  }
}

async function saveLeaderboardSetting() {
  const toggle = document.getElementById('leaderboardToggle');
  const isEnabled = toggle ? toggle.checked : false;
  
  try {
    // Save the setting
    const response = await fetch('admin_settings_toggle.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({
        setting_name: 'leaderboard_enabled',
        setting_value: isEnabled ? 'true' : 'false'
      })
    });
    
    const data = await response.json();
    
    if (data.success) {
      // Manage top 3 notifications
      const notifyResponse = await fetch('manage_top3_notifications.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
          action: isEnabled ? 'enable' : 'disable'
        })
      });

      const notifyData = await notifyResponse.json();
      toast(isEnabled ? 'Leaderboard enabled and top 3 notified.' : 'Leaderboard disabled and notifications removed.', 'success');
    } else {
      toast('Failed to save setting.', 'danger');
      // Revert toggle on failure
      if (toggle) toggle.checked = !isEnabled;
    }
  } catch (err) {
    console.error('Error saving leaderboard setting:', err);
    toast('Error saving setting.', 'danger');
    if (toggle) toggle.checked = !isEnabled;
  }
}
</script>
</body>
</html>