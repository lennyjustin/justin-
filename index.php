<?php
declare(strict_types=1);
require_once __DIR__ . '/auth.php';
if (!isset($_SESSION['user_id'])) {
    ?>
<!doctype html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta name="theme-color" content="#f5f7f4" />
    <meta
      name="description"
      content="FleetCare EA helps Kenyan businesses track vehicle maintenance, fuel, costs and important document renewals in one place."
    />
    <title>FleetCare EA — Smart fleet management</title>
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link
      href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Manrope:wght@400;500;600;700;800&display=swap"
      rel="stylesheet"
    />
    <link rel="stylesheet" href="styles.css" />
    <link rel="stylesheet" href="app-extra.css" />
  </head>
  <body class="landing-page">
    <header class="landing-header">
      <a class="brand landing-brand" href="index.php" aria-label="FleetCare EA home">
        <span class="brand-mark">FC</span>
        <span class="brand-name">fleetcare<span>EA</span></span>
      </a>
      <nav class="landing-nav" aria-label="Account">
        <a class="landing-signin" href="login.php">Sign in</a>
        <a class="button button-primary landing-cta" href="register.php">Get started</a>
      </nav>
    </header>
    <main>
      <div
        class="landing-hero-image"
        role="img"
        aria-label="FleetCare EA fleet management for East African businesses"
      ></div>
      <section class="landing-intro" aria-labelledby="landing-heading">
        <p class="eyebrow">SIMPLE FLEET MANAGEMENT</p>
        <h1 id="landing-heading">Keep your business vehicles ready for the road.</h1>
        <p>
          FleetCare EA brings your vehicle records together so you can plan servicing,
          track running costs and stay ahead of insurance, inspection and permit renewals.
        </p>
      </section>
      <section class="landing-features" aria-label="What FleetCare EA helps you manage">
        <article class="landing-feature">
          <span class="landing-feature-icon" aria-hidden="true">01</span>
          <h2>Stay ahead of maintenance</h2>
          <p>Record completed and scheduled services, then check mileage and dates to see what needs attention.</p>
        </article>
        <article class="landing-feature">
          <span class="landing-feature-icon" aria-hidden="true">02</span>
          <h2>Understand vehicle costs</h2>
          <p>Log fuel and maintenance spending and review monthly costs by vehicle.</p>
        </article>
        <article class="landing-feature">
          <span class="landing-feature-icon" aria-hidden="true">03</span>
          <h2>Remember important renewals</h2>
          <p>Keep insurance, inspection and permit expiry dates visible in your workspace.</p>
        </article>
      </section>
      <section class="landing-bottom-cta">
        <div>
          <h2>Bring your fleet records into one place.</h2>
          <p>Create a workspace for your business and start recording your vehicles.</p>
        </div>
        <a class="button button-primary landing-cta" href="register.php">Create your account</a>
      </section>
    </main>
    <footer class="landing-footer">FleetCare EA <span>·</span> Smart fleet management for East African businesses</footer>
  </body>
</html>
    <?php
    exit;
}

$currentUser = require_login();
$greetingName = explode(' ', $currentUser['name'])[0];
?>
<!doctype html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta name="theme-color" content="#f5f7f4" />
    <meta
      name="description"
      content="FleetCare EA helps you stay ahead of vehicle maintenance, fuel costs, mileage, and important renewals."
    />
    <title>FleetCare EA — Fleet overview</title>
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link
      href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Manrope:wght@400;500;600;700;800&display=swap"
      rel="stylesheet"
    />
    <link rel="stylesheet" href="styles.css" />
    <link rel="stylesheet" href="app-extra.css" />
  </head>
  <body>
    <svg class="icon-library" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
      <symbol id="i-grid" viewBox="0 0 24 24"><rect x="3.5" y="3.5" width="7" height="7" rx="1.5"/><rect x="13.5" y="3.5" width="7" height="7" rx="1.5"/><rect x="3.5" y="13.5" width="7" height="7" rx="1.5"/><rect x="13.5" y="13.5" width="7" height="7" rx="1.5"/></symbol>
      <symbol id="i-car" viewBox="0 0 24 24"><path d="m5 11 1.5-4.3A2.5 2.5 0 0 1 8.9 5h6.2a2.5 2.5 0 0 1 2.4 1.7L19 11"/><path d="M3.5 11h17v6h-17zM6 17v2m12-2v2M6 13.5h.01m12-.01h.01M3.5 14H2m20 0h-1.5"/></symbol>
      <symbol id="i-wrench" viewBox="0 0 24 24"><path d="M14.7 6.3a5 5 0 0 0-6.4 6.4l-5.1 5.1a2.1 2.1 0 0 0 3 3l5.1-5.1a5 5 0 0 0 6.4-6.4l-3 3-3-3z"/></symbol>
      <symbol id="i-fuel" viewBox="0 0 24 24"><path d="M4 21V5a2 2 0 0 1 2-2h8a2 2 0 0 1 2 2v16M3 21h14M7 7h6v5H7zM16 9h1l3 3v6a2 2 0 0 1-4 0v-4"/></symbol>
      <symbol id="i-chart" viewBox="0 0 24 24"><path d="M4 19.5h16M6.5 16v-4m5 4V6m5 10V9"/></symbol>
      <symbol id="i-settings" viewBox="0 0 24 24"><circle cx="12" cy="12" r="3"/><path d="m19.4 15 .1.1 1.1.9-1.1 1.9-1.4-.6a8 8 0 0 1-1.5.9l-.2 1.5h-2.2l-.3-1.5a8 8 0 0 1-1.7 0l-.3 1.5H9.7l-.2-1.5a8 8 0 0 1-1.5-.9l-1.4.6-1.1-1.9 1.1-.9a8 8 0 0 1-.3-1.7l-1.4-.6v-2.2l1.4-.4c.1-.6.3-1.2.6-1.7L6 7.3l1.1-1.9 1.5.6c.5-.4 1-.7 1.6-.9l.2-1.5h2.2l.3 1.5c.6.1 1.2.4 1.7.7l1.4-.6 1.1 1.9-1.1 1.1c.3.5.4 1.1.5 1.7l1.4.4v2.2l-1.4.6c0 .6-.1 1.1-.3 1.9z"/></symbol>
      <symbol id="i-search" viewBox="0 0 24 24"><circle cx="10.8" cy="10.8" r="6.8"/><path d="m16 16 4.5 4.5"/></symbol>
      <symbol id="i-bell" viewBox="0 0 24 24"><path d="M18 9a6 6 0 0 0-12 0c0 7-3 7-3 9h18c0-2-3-2-3-9M10 21h4"/></symbol>
      <symbol id="i-plus" viewBox="0 0 24 24"><path d="M12 5v14M5 12h14"/></symbol>
      <symbol id="i-arrow" viewBox="0 0 24 24"><path d="M5 12h14m-6-6 6 6-6 6"/></symbol>
      <symbol id="i-chevron" viewBox="0 0 24 24"><path d="m9 18 6-6-6-6"/></symbol>
      <symbol id="i-download" viewBox="0 0 24 24"><path d="M12 3v12m-5-5 5 5 5-5M4 18v3h16v-3"/></symbol>
      <symbol id="i-calendar" viewBox="0 0 24 24"><rect x="3" y="5" width="18" height="16" rx="2"/><path d="M16 3v4M8 3v4M3 10h18"/></symbol>
      <symbol id="i-odometer" viewBox="0 0 24 24"><path d="M4.9 19a9 9 0 1 1 14.2 0M12 13l4-4m-4 4-2-1M12 4v1m8 7h-1M5 12H4"/></symbol>
      <symbol id="i-shield" viewBox="0 0 24 24"><path d="M12 22s8-4 8-11V5l-8-3-8 3v6c0 7 8 11 8 11z"/><path d="m9 12 2 2 4-4"/></symbol>
      <symbol id="i-alert" viewBox="0 0 24 24"><path d="M10.3 4.3 2.5 18a2 2 0 0 0 1.7 3h15.6a2 2 0 0 0 1.7-3L13.7 4.3a2 2 0 0 0-3.4 0zM12 9v4m0 4h.01"/></symbol>
      <symbol id="i-menu" viewBox="0 0 24 24"><path d="M4 6h16M4 12h16M4 18h16"/></symbol>
      <symbol id="i-close" viewBox="0 0 24 24"><path d="m6 6 12 12M18 6 6 18"/></symbol>
      <symbol id="i-check" viewBox="0 0 24 24"><path d="m5 12 4 4L19 6"/></symbol>
      <symbol id="i-more" viewBox="0 0 24 24"><circle cx="5" cy="12" r="1"/><circle cx="12" cy="12" r="1"/><circle cx="19" cy="12" r="1"/></symbol>
    </svg>

    <div class="app-shell">
      <aside class="sidebar" id="sidebar">
        <a class="brand" href="#dashboard" aria-label="FleetCare EA home">
          <span class="brand-mark"><svg class="icon"><use href="#i-car"/></svg></span>
          <span class="brand-name">fleetcare<span>EA</span></span>
        </a>
        <div class="workspace-switcher">
          <span class="workspace-avatar"><?= e(strtoupper(substr($currentUser['workspace'], 0, 1))) ?></span>
          <span class="workspace-copy"><strong><?= e($currentUser['workspace']) ?></strong><small>Fleet workspace</small></span>
          <span class="switcher-dots">•••</span>
        </div>
        <p class="nav-label">WORKSPACE</p>
        <nav class="primary-nav" aria-label="Main navigation">
          <a class="nav-item active" href="#dashboard" data-view="dashboard"><svg class="icon"><use href="#i-grid"/></svg><span>Overview</span></a>
          <a class="nav-item" href="#vehicles" data-view="vehicles"><svg class="icon"><use href="#i-car"/></svg><span>Vehicles</span><span class="nav-count" id="vehicle-nav-count">8</span></a>
          <a class="nav-item" href="#maintenance" data-view="maintenance"><svg class="icon"><use href="#i-wrench"/></svg><span>Maintenance</span><span class="nav-count alert-count" id="maintenance-nav-count">0</span></a>
          <a class="nav-item" href="#fuel" data-view="fuel"><svg class="icon"><use href="#i-fuel"/></svg><span>Fuel & mileage</span></a>
          <a class="nav-item" href="#documents" data-view="documents"><svg class="icon"><use href="#i-shield"/></svg><span>Documents</span></a>
          <a class="nav-item" href="#reports" data-view="reports"><svg class="icon"><use href="#i-chart"/></svg><span>Reports</span></a>
        </nav>
        <div class="sidebar-bottom">
          <div class="plan-card">
            <div class="plan-icon"><svg class="icon"><use href="#i-shield"/></svg></div>
            <strong>Your fleet, in good hands.</strong>
            <p>Keep vehicles, services and renewals together in one place.</p>
            <a href="#reports" data-view="reports">Explore fleet insights <svg class="icon"><use href="#i-arrow"/></svg></a>
          </div>
          <a class="nav-item settings-link" href="#settings" data-view="settings"><svg class="icon"><use href="#i-settings"/></svg><span>Settings</span></a>
          <div class="profile">
            <span class="profile-avatar"><?= e(strtoupper(substr($currentUser['name'], 0, 1))) ?></span>
            <span class="profile-copy"><strong><?= e($currentUser['name']) ?></strong><small>Fleet manager</small></span>
            <form method="post" action="logout.php"><input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>"><button class="icon-button profile-more" aria-label="Sign out" title="Sign out"><svg class="icon"><use href="#i-arrow"/></svg></button></form>
          </div>
        </div>
      </aside>
      <button class="sidebar-backdrop" id="sidebar-backdrop" type="button" aria-label="Close navigation" aria-hidden="true" tabindex="-1"></button>

      <main class="main-content">
        <header class="topbar">
          <button class="icon-button mobile-menu" id="mobile-menu" aria-label="Open navigation" aria-expanded="false" aria-controls="sidebar"><svg class="icon"><use href="#i-menu"/></svg></button>
          <div class="breadcrumb"><span><?= e($currentUser['workspace']) ?></span><svg class="icon"><use href="#i-chevron"/></svg><strong id="breadcrumb-current">Overview</strong></div>
          <div class="topbar-actions">
            <label class="search-box"><svg class="icon"><use href="#i-search"/></svg><input id="global-search" type="search" placeholder="Search anything..." aria-label="Search vehicles"/><kbd>⌘ K</kbd></label>
            <button class="icon-button notification-button" id="notifications" aria-label="Show notifications"><svg class="icon"><use href="#i-bell"/></svg><span class="notification-dot"></span></button>
            <span class="topbar-divider"></span>
            <span class="topbar-avatar"><?= e(strtoupper(substr($currentUser['name'], 0, 1))) ?></span>
          </div>
        </header>

        <div class="page-content">
          <section class="view-panel active" id="view-dashboard" data-page="dashboard">
            <div class="page-heading">
              <div><p class="eyebrow" id="current-date"></p><h1>Good morning, <?= e($greetingName) ?> <span class="wave">✳</span></h1><p class="heading-subtitle">Here’s what’s happening with your fleet today.</p></div>
              <div class="heading-actions"><button class="button button-secondary" id="export-csv"><svg class="icon"><use href="#i-download"/></svg>Export report</button><button class="button button-primary" data-open-modal="vehicle-modal"><svg class="icon"><use href="#i-plus"/></svg>Add a vehicle</button></div>
            </div>

            <div class="metrics-grid">
              <article class="metric-card"><div class="metric-top"><span class="metric-label">Vehicles in fleet</span><span class="metric-icon green"><svg class="icon"><use href="#i-car"/></svg></span></div><div class="metric-value" id="metric-vehicles">08</div><div class="metric-foot"><span class="metric-note"><span class="status-dot dot-green"></span><span id="active-vehicle-count">7 active</span></span><span class="metric-caption">across your fleet</span></div><div class="metric-spark spark-green"><i></i><i></i><i></i><i></i><i></i><i></i><i></i><i></i><i></i><i></i><i></i><i></i></div></article>
              <article class="metric-card"><div class="metric-top"><span class="metric-label">Maintenance spend</span><span class="metric-icon blue"><svg class="icon"><use href="#i-wrench"/></svg></span></div><div class="metric-value"><span class="currency currency-code"><?= e($currentUser['currency']) ?></span><span id="metric-spend">0</span></div><div class="metric-foot"><span class="metric-caption">completed this month</span></div><div class="metric-spark spark-blue"><i></i><i></i><i></i><i></i><i></i><i></i><i></i><i></i><i></i><i></i><i></i><i></i></div></article>
              <article class="metric-card"><div class="metric-top"><span class="metric-label">Due soon</span><span class="metric-icon amber"><svg class="icon"><use href="#i-calendar"/></svg></span></div><div class="metric-value" id="metric-due">00</div><div class="metric-foot"><span class="metric-note">Services & documents · 30 days</span><a class="metric-link" href="#maintenance" data-view="maintenance">View schedule <svg class="icon"><use href="#i-arrow"/></svg></a></div><div class="metric-spark spark-amber"><i></i><i></i><i></i><i></i><i></i><i></i><i></i><i></i><i></i><i></i><i></i><i></i></div></article>
              <article class="metric-card"><div class="metric-top"><span class="metric-label">Fuel spend</span><span class="metric-icon violet"><svg class="icon"><use href="#i-fuel"/></svg></span></div><div class="metric-value"><span class="currency currency-code"><?= e($currentUser['currency']) ?></span><span id="metric-fuel">0</span></div><div class="metric-foot"><span class="metric-caption">this month</span></div><div class="metric-spark spark-violet"><i></i><i></i><i></i><i></i><i></i><i></i><i></i><i></i><i></i><i></i><i></i><i></i></div></article>
            </div>

            <div class="dashboard-grid">
              <section class="panel fleet-panel">
                <div class="panel-heading"><div><h2>Fleet overview</h2><p>A quick look at your vehicles and their health.</p></div><a class="text-link" href="#vehicles" data-view="vehicles">View all vehicles <svg class="icon"><use href="#i-arrow"/></svg></a></div>
                <div class="table-wrap"><table class="fleet-table"><thead><tr><th>VEHICLE</th><th>STATUS</th><th>ODOMETER</th><th>NEXT SERVICE</th><th></th></tr></thead><tbody id="fleet-table-body"></tbody></table></div>
                <div class="table-footer"><span id="table-count">Showing 5 of 8 vehicles</span><button class="pagination-button" id="view-all-vehicles">View all <svg class="icon"><use href="#i-arrow"/></svg></button></div>
              </section>
              <aside class="side-column">
                <section class="panel reminders-panel">
                  <div class="panel-heading"><div><h2>Coming up</h2><p>Don’t let these slip by.</p></div><button class="icon-button small-icon-button" id="reminders-menu" aria-label="Reminder options"><svg class="icon"><use href="#i-more"/></svg></button></div>
                  <div class="reminder-list" id="reminder-list"></div>
                  <button class="add-reminder" data-open-modal="maintenance-modal"><svg class="icon"><use href="#i-plus"/></svg>Add a reminder</button>
                </section>
                <section class="insight-card">
                  <div class="insight-top"><span class="insight-mark">✳</span><span class="insight-period">THIS MONTH</span><button class="icon-button insight-menu" aria-label="More insight options"><svg class="icon"><use href="#i-more"/></svg></button></div>
                  <p class="insight-title">A little upkeep goes a long way.</p>
                  <p class="insight-copy">Log services, fuel and document dates to build a useful picture of your fleet’s running costs.</p>
                  <a href="#reports" data-view="reports">See your fleet report <svg class="icon"><use href="#i-arrow"/></svg></a>
                  <div class="insight-decoration" aria-hidden="true"><span></span><span></span><span></span><span></span><span></span><span></span><span></span><span></span></div>
                </section>
              </aside>
            </div>
            <footer class="page-footer"><span>FleetCare EA <span class="footer-separator">·</span> Your fleet, moving forward.</span><span>Need a hand? <a href="mailto:support@fleetcare.ea">Talk to our team</a></span></footer>
          </section>

          <section class="view-panel" id="view-vehicles" data-page="vehicles">
            <div class="page-heading"><div><p class="eyebrow">YOUR FLEET</p><h1>Vehicles</h1><p class="heading-subtitle">Manage your vehicles, mileage, and service status.</p></div><div class="heading-actions"><button class="button button-primary" data-open-modal="vehicle-modal"><svg class="icon"><use href="#i-plus"/></svg>Add a vehicle</button></div></div>
            <div class="panel full-panel"><div class="panel-heading"><div><h2>All vehicles <span class="inline-count" id="vehicles-view-count">0</span></h2><p>Your fleet at a glance.</p></div><label class="table-search"><svg class="icon"><use href="#i-search"/></svg><input id="vehicle-filter" type="search" placeholder="Find a vehicle..." aria-label="Filter vehicles"/></label></div><div class="table-wrap"><table class="fleet-table"><thead><tr><th>VEHICLE</th><th>STATUS</th><th>ODOMETER</th><th>NEXT SERVICE</th><th></th></tr></thead><tbody id="vehicles-table-body"></tbody></table></div><div class="empty-state hidden" id="vehicle-empty">No vehicles match your search.</div></div>
          </section>

          <section class="view-panel" id="view-maintenance" data-page="maintenance">
            <div class="page-heading"><div><p class="eyebrow">STAY AHEAD OF THE ROAD</p><h1>Maintenance</h1><p class="heading-subtitle">Your upcoming services and recent workshop visits.</p></div><div class="heading-actions"><button class="button button-primary" data-open-modal="maintenance-modal"><svg class="icon"><use href="#i-plus"/></svg>Log maintenance</button></div></div>
            <div class="maintenance-summary"><div class="summary-tile"><span class="metric-icon amber"><svg class="icon"><use href="#i-calendar"/></svg></span><div><strong id="maintenance-due-count">0</strong><span>Scheduled services</span></div></div><div class="summary-tile"><span class="metric-icon green"><svg class="icon"><use href="#i-check"/></svg></span><div><strong id="maintenance-completed-count">0</strong><span>Completed records</span></div></div><div class="summary-tile"><span class="metric-icon blue"><svg class="icon"><use href="#i-wrench"/></svg></span><div><strong><span class="currency-code"><?= e($currentUser['currency']) ?></span> <span id="maintenance-spend">0</span></strong><span>Spent this month</span></div></div></div>
            <div class="panel full-panel"><div class="panel-heading"><div><h2>Upcoming maintenance</h2><p>Planned services and renewal reminders.</p></div></div><div class="schedule-list" id="schedule-list"></div></div>
            <div class="panel full-panel"><div class="panel-heading"><div><h2>Recent service history</h2><p>Completed workshop and repair records.</p></div></div><div class="schedule-list" id="maintenance-history"></div></div>
          </section>

          <section class="view-panel" id="view-fuel" data-page="fuel">
            <div class="page-heading"><div><p class="eyebrow">KEEP TRACK OF EVERY LITRE</p><h1>Fuel & mileage</h1><p class="heading-subtitle">Monitor fuel spend and distance across your fleet.</p></div><div class="heading-actions"><button class="button button-secondary" id="fuel-export"><svg class="icon"><use href="#i-download"/></svg>Export report</button><button class="button button-primary" data-open-modal="fuel-modal"><svg class="icon"><use href="#i-plus"/></svg>Add fuel entry</button></div></div>
            <div class="metrics-grid compact-metrics"><article class="metric-card"><div class="metric-top"><span class="metric-label">Fuel spend this month</span><span class="metric-icon blue"><svg class="icon"><use href="#i-fuel"/></svg></span></div><div class="metric-value"><span class="currency currency-code"><?= e($currentUser['currency']) ?></span><span id="fuel-page-spend">0</span></div><div class="metric-foot"><span class="metric-caption">calculated from litres × price</span></div></article><article class="metric-card"><div class="metric-top"><span class="metric-label">Distance recorded</span><span class="metric-icon violet"><svg class="icon"><use href="#i-odometer"/></svg></span></div><div class="metric-value"><span id="fuel-page-distance">0</span> <span class="unit">km</span></div><div class="metric-foot"><span class="metric-note">From odometer readings</span></div></article><article class="metric-card"><div class="metric-top"><span class="metric-label">Fuel entries</span><span class="metric-icon green"><svg class="icon"><use href="#i-chart"/></svg></span></div><div class="metric-value" id="fuel-entry-count">0</div><div class="metric-foot"><span class="metric-note">Across your fleet</span></div></article></div>
            <div class="panel full-panel"><div class="panel-heading"><div><h2>Recent fuel entries</h2><p>Latest fuel activity across your fleet.</p></div><span class="date-filter"><svg class="icon"><use href="#i-calendar"/></svg><span id="fuel-month-label"></span></span></div><div class="table-wrap"><table class="fleet-table fuel-table"><thead><tr><th>VEHICLE</th><th>DATE</th><th>FUEL</th><th>ODOMETER</th><th>AMOUNT</th></tr></thead><tbody id="fuel-table-body"></tbody></table></div><div class="empty-state hidden" id="fuel-empty">No fuel entries yet. Add a fill-up to get started.</div></div>
          </section>

          <section class="view-panel" id="view-documents" data-page="documents">
            <div class="page-heading"><div><p class="eyebrow">KEEP IMPORTANT DATES IN VIEW</p><h1>Documents</h1><p class="heading-subtitle">Track insurance, inspections, permits and renewal dates.</p></div><div class="heading-actions"><button class="button button-primary" data-open-modal="document-modal"><svg class="icon"><use href="#i-plus"/></svg>Add a document</button></div></div>
            <div class="panel full-panel"><div class="panel-heading"><div><h2>Vehicle documents <span class="inline-count" id="documents-count">0</span></h2><p>Upcoming expiry dates are highlighted for a timely renewal.</p></div></div><div class="table-wrap"><table class="fleet-table document-table"><thead><tr><th>DOCUMENT</th><th>VEHICLE</th><th>REFERENCE</th><th>EXPIRY DATE</th><th>STATUS</th></tr></thead><tbody id="documents-table-body"></tbody></table></div><div class="empty-state hidden" id="documents-empty">No vehicle documents saved yet. Add insurance, inspection, or permit details.</div></div>
          </section>

          <section class="view-panel" id="view-reports" data-page="reports">
            <div class="page-heading"><div><p class="eyebrow">SMARTER FLEET DECISIONS</p><h1>Fleet reports</h1><p class="heading-subtitle">A clearer picture of where your fleet costs are going.</p></div><div class="heading-actions"><button class="button button-secondary" id="reports-export"><svg class="icon"><use href="#i-download"/></svg>Download report</button></div></div>
            <div class="metrics-grid compact-metrics report-metrics"><article class="metric-card"><div class="metric-top"><span class="metric-label">Total running costs</span><span class="metric-icon blue"><svg class="icon"><use href="#i-chart"/></svg></span></div><div class="metric-value"><span class="currency currency-code"><?= e($currentUser['currency']) ?></span><span id="report-total-spend">0</span></div><div class="metric-foot"><span class="metric-caption">recorded this month</span></div></article><article class="metric-card"><div class="metric-top"><span class="metric-label">Maintenance costs</span><span class="metric-icon amber"><svg class="icon"><use href="#i-wrench"/></svg></span></div><div class="metric-value"><span class="currency currency-code"><?= e($currentUser['currency']) ?></span><span id="report-maintenance-spend">0</span></div><div class="metric-foot"><span class="metric-caption">completed services</span></div></article><article class="metric-card"><div class="metric-top"><span class="metric-label">Distance this month</span><span class="metric-icon violet"><svg class="icon"><use href="#i-odometer"/></svg></span></div><div class="metric-value"><span id="report-distance">0</span> <span class="unit">km</span></div><div class="metric-foot"><span class="metric-caption">from fuel odometer readings</span></div></article><article class="metric-card"><div class="metric-top"><span class="metric-label">Fleet availability</span><span class="metric-icon green"><svg class="icon"><use href="#i-car"/></svg></span></div><div class="metric-value"><span id="report-availability">0</span><span class="unit">%</span></div><div class="metric-foot"><span class="metric-note" id="report-vehicle-count">0 active vehicles</span></div></article></div>
            <div class="report-grid"><section class="panel report-panel"><div class="panel-heading"><div><h2>Monthly fleet costs</h2><p>Maintenance and fuel spend, <span class="currency-code"><?= e($currentUser['currency']) ?></span>.</p></div><span class="date-filter">Last 6 months</span></div><div class="chart-legend"><span><i class="legend-dot maintenance-dot"></i>Maintenance</span><span><i class="legend-dot fuel-dot"></i>Fuel</span></div><div class="bar-chart" aria-label="Bar chart showing recorded fleet costs over six months"><div class="chart-grid" id="chart-grid"></div><div class="chart-columns" id="chart-columns"></div></div></section><section class="panel cost-breakdown"><div class="panel-heading"><div><h2>Cost breakdown</h2><p>This month’s recorded expenses.</p></div></div><div class="donut-wrap"><div class="donut-chart" id="cost-donut"><span><strong id="donut-total">KES 0</strong><small>Total spend</small></span></div></div><div class="breakdown-list"><div><span><i class="legend-dot fuel-dot"></i>Fuel</span><strong><span id="breakdown-fuel">KES 0</span> <small id="breakdown-fuel-percent">0%</small></strong></div><div><span><i class="legend-dot maintenance-dot"></i>Maintenance</span><strong><span id="breakdown-maintenance">KES 0</span> <small id="breakdown-maintenance-percent">0%</small></strong></div></div></section></div>
            <section class="panel full-panel vehicle-cost-panel"><div class="panel-heading"><div><h2>Running costs by vehicle</h2><p>Compare this month’s fuel, maintenance and cost per recorded kilometre.</p></div><span class="date-filter" id="vehicle-cost-month"></span></div><div class="table-wrap"><table class="fleet-table"><thead><tr><th>VEHICLE</th><th>FUEL COST</th><th>MAINTENANCE</th><th>DISTANCE</th><th>TOTAL COST / KM</th></tr></thead><tbody id="vehicle-cost-table"></tbody></table></div><div class="empty-state hidden" id="vehicle-cost-empty">Add fuel entries and odometer readings to compare vehicle running costs.</div></section>
          </section>

          <section class="view-panel" id="view-settings" data-page="settings">
            <div class="page-heading"><div><p class="eyebrow">MAKE FLEETCARE YOURS</p><h1>Settings</h1><p class="heading-subtitle">Manage your workspace and notification preferences.</p></div></div>
            <div class="panel settings-panel"><div class="settings-section"><div><h2>Workspace details</h2><p>Your organization profile and default display settings.</p></div><form id="settings-form" class="settings-form"><label>Workspace name<input type="text" value="<?= e($currentUser['workspace']) ?>" id="workspace-name" required maxlength="120"/></label><label>Preferred currency<select id="currency-setting"><option value="KES"<?= $currentUser['currency'] === 'KES' ? ' selected' : '' ?>>Kenyan Shilling (KES)</option><option value="USD"<?= $currentUser['currency'] === 'USD' ? ' selected' : '' ?>>US Dollar (USD)</option><option value="TZS"<?= $currentUser['currency'] === 'TZS' ? ' selected' : '' ?>>Tanzanian Shilling (TZS)</option></select><small class="form-hint">Changing this label does not convert amounts already recorded.</small></label><button class="button button-primary" type="submit">Save changes</button></form></div><div class="settings-section"><div><h2>Notifications</h2><p>Scheduled services and expiry dates appear in your dashboard. Automated email or SMS is not configured.</p></div><div class="toggle-list"><label><span><strong>Maintenance reminders</strong><small>View scheduled service and renewal dates.</small></span><input type="checkbox" checked disabled/></label><label><span><strong>Document expiry alerts</strong><small>See upcoming insurance and inspection expiries.</small></span><input type="checkbox" checked disabled/></label></div></div></div>
          </section>
        </div>
      </main>
    </div>

    <div class="toast-region" id="toast-region" aria-live="polite"></div>
    <dialog class="form-dialog" id="vehicle-modal">
      <form id="vehicle-form" method="dialog">
        <div class="dialog-heading"><div><span class="dialog-icon"><svg class="icon"><use href="#i-car"/></svg></span><h2>Add a vehicle</h2><p>Add a vehicle to your fleet to start tracking it.</p></div><button type="button" class="icon-button close-dialog" aria-label="Close"><svg class="icon"><use href="#i-close"/></svg></button></div>
        <div class="form-grid"><label class="wide-field">Vehicle name<input name="name" placeholder="e.g. Nairobi Runner" required maxlength="100"/></label><label>Registration number<input name="registration" placeholder="KDA 123A" required maxlength="20"/></label><label>Vehicle type<select name="type"><option>Van</option><option>Pickup</option><option>Truck</option><option>Car</option><option>Bus</option><option>Motorcycle</option></select></label><label>Current odometer (km)<input name="odometer" type="number" min="0" step="1" value="0" required/></label><label>Next service (km)<input name="service" type="number" min="0" step="1" placeholder="e.g. 45000"/></label></div>
        <div class="dialog-actions"><button type="button" class="button button-secondary close-dialog">Cancel</button><button class="button button-primary" type="submit">Add vehicle</button></div>
      </form>
    </dialog>
    <dialog class="form-dialog" id="maintenance-modal">
      <form id="maintenance-form" method="dialog">
        <div class="dialog-heading"><div><span class="dialog-icon amber-dialog-icon"><svg class="icon"><use href="#i-calendar"/></svg></span><h2>Log maintenance</h2><p>Record a service or set a reminder for your fleet.</p></div><button type="button" class="icon-button close-dialog" aria-label="Close"><svg class="icon"><use href="#i-close"/></svg></button></div>
        <div class="form-grid"><label class="wide-field">Vehicle<select name="vehicleId" id="maintenance-vehicle" required></select></label><label>Service type<select name="serviceType"><option>Oil change</option><option>Tyre replacement</option><option>General inspection</option><option>Brake service</option><option>Insurance renewal</option><option>Vehicle inspection</option><option>Other repair</option></select></label><label>Date<input name="serviceDate" type="date" required/></label><label>Record type<select name="status"><option value="Scheduled">Schedule a service</option><option value="Completed">Record completed service</option></select></label><label>Cost (<span class="currency-code"><?= e($currentUser['currency']) ?></span>)<input name="cost" type="number" min="0" step="0.01" value="0" required/></label><label>Odometer (km)<input name="odometer" type="number" min="0"/></label><label>Next service (km)<input name="nextServiceOdometer" type="number" min="0"/></label><label class="wide-field">Notes (optional)<input name="notes" maxlength="500" placeholder="Add workshop or service details"/></label></div>
        <div class="dialog-actions"><button type="button" class="button button-secondary close-dialog">Cancel</button><button class="button button-primary" type="submit">Save reminder</button></div>
      </form>
    </dialog>
    <dialog class="form-dialog" id="fuel-modal">
      <form id="fuel-form" method="dialog">
        <div class="dialog-heading"><div><span class="dialog-icon"><svg class="icon"><use href="#i-fuel"/></svg></span><h2>Add fuel entry</h2><p>Record litres, price, and the latest odometer reading.</p></div><button type="button" class="icon-button close-dialog" aria-label="Close"><svg class="icon"><use href="#i-close"/></svg></button></div>
        <div class="form-grid"><label class="wide-field">Vehicle<select name="vehicleId" id="fuel-vehicle" required></select></label><label>Date filled<input name="filledAt" type="date" required/></label><label>Litres<input name="litres" type="number" min="0.01" step="0.01" required/></label><label>Price per litre (<span class="currency-code"><?= e($currentUser['currency']) ?></span>)<input name="pricePerLitre" type="number" min="0.01" step="0.01" required/></label><label>Odometer (km)<input name="odometer" type="number" min="0" required/></label></div>
        <div class="dialog-actions"><button type="button" class="button button-secondary close-dialog">Cancel</button><button class="button button-primary" type="submit">Save fuel entry</button></div>
      </form>
    </dialog>
    <dialog class="form-dialog" id="document-modal">
      <form id="document-form" method="dialog">
        <div class="dialog-heading"><div><span class="dialog-icon amber-dialog-icon"><svg class="icon"><use href="#i-shield"/></svg></span><h2>Add a document</h2><p>Keep an expiry date on your fleet calendar.</p></div><button type="button" class="icon-button close-dialog" aria-label="Close"><svg class="icon"><use href="#i-close"/></svg></button></div>
        <div class="form-grid"><label class="wide-field">Vehicle<select name="vehicleId" id="document-vehicle" required></select></label><label>Document type<select name="type"><option>Insurance</option><option>Inspection</option><option>Permit</option><option>Other</option></select></label><label>Expiry date<input name="expiryDate" type="date" required/></label><label class="wide-field">Reference number (optional)<input name="documentNumber" maxlength="100" placeholder="Policy or permit reference"/></label><label class="wide-field">Notes (optional)<input name="notes" maxlength="500" placeholder="Add a reminder or detail"/></label></div>
        <div class="dialog-actions"><button type="button" class="button button-secondary close-dialog">Cancel</button><button class="button button-primary" type="submit">Save document</button></div>
      </form>
    </dialog>
    <script>window.FLEETCARE = <?= json_encode(['csrfToken' => csrf_token(), 'workspace' => $currentUser['workspace'], 'userName' => $currentUser['name'], 'currency' => $currentUser['currency']], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;</script>
    <script src="app.js"></script>
  </body>
</html>
