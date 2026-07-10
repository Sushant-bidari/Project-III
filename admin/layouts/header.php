<?php
  // session_start();
  

  $hasSidebar    = isset($_SESSION['adminname']);
  $currentPage   = basename($_SERVER['SCRIPT_NAME']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title>Admin Panel</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">

  <!-- Fonts & libs -->
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/4.6.2/css/bootstrap.min.css" crossorigin="anonymous" />
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" crossorigin="anonymous" />

  
  <link rel="stylesheet" href="<?= ADMINURL ?>/styles/style.css?v=2.0.0">
  <style>
    /* Topbar user icon trigger (dark topbar) */
.topbar .user-trigger{
  display:inline-flex; align-items:center; justify-content:center;
 
  color:#e5e7eb; background:transparent;
  
  text-decoration:none; transition:.2s;
}
.topbar .user-trigger i{ font-size:20px; line-height:1; }
.topbar .user-trigger:hover{ background:rgba(255,255,255,.08); color:#fff; }

  </style>
</head>

<body class="app">
  <!-- Topbar (no search, no notifications) -->
  <nav class="topbar">
    <button class="icon-btn d-inline-flex d-lg-none" id="sidebarToggle" aria-label="Toggle sidebar">
      <i class="fas fa-bars"></i>
    </button>

    <a class="brand" href="<?= ADMINURL ?>/index.php">
      <i class="fas fa-briefcase"></i> Admin Panel
    </a>

    <div class="ml-auto d-flex align-items-center">
      <?php if ($hasSidebar): ?>
  <div class="dropdown">
    <a class="user-trigger dropdown-toggle" href="#" id="userMenu" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false" aria-label="Account menu">
      <i class="fas fa-user-circle"></i>
    </a>
    <div class="dropdown-menu dropdown-menu-right" aria-labelledby="userMenu">
      <span class="dropdown-item-text small text-muted px-3">
        Signed in as<br><strong><?= htmlspecialchars($_SESSION['adminname']) ?></strong>
      </span>
      <div class="dropdown-divider"></div>
      <a class="dropdown-item text-danger" href="<?= ADMINURL ?>/admins/change-adminpassword.php">
        <i class="fas fa-lock"></i> Change Password
      </a>
      <a class="dropdown-item text-danger" href="<?= ADMINURL ?>/admins/logout-admins.php">
        <i class="fas fa-sign-out-alt"></i> Logout
      </a>
    </div>
  </div>
<?php else: ?>
  <a class="btn btn-sm btn-primary" href="<?= ADMINURL ?>/admins/login-admins.php">
    <i class="fas fa-sign-in-alt"></i> Login as Admin
  </a>
<?php endif; ?>

    </div>
  </nav>

  <div class="app-shell">
    <?php if ($hasSidebar): ?>
      <!-- Sidebar menu: synced to link names/paths -->
      <aside class="sidebar" id="sidebar">
        <div class="sidebar-inner">
          <div class="sidebar-section mt-2">
            <div class="sidebar-label">MAIN</div>

            <a class="sidebar-link <?= $currentPage==='index.php'?'active':'' ?>"
               href="<?= ADMINURL ?>/index.php">
              <i class="fas fa-tachometer-alt"></i><span>Home</span>
            </a>

            <a class="sidebar-link <?= $currentPage==='admins.php'?'active':'' ?>"
               href="<?= ADMINURL ?>/admins/admins.php">
              <i class="fas fa-users-cog"></i><span>Admins</span>
            </a>

            <a class="sidebar-link <?= $currentPage==='show-categories.php'?'active':'' ?>"
               href="<?= ADMINURL ?>/categories-admins/show-categories.php">
              <i class="fas fa-list-ul"></i><span>Categories</span>
            </a>

            <a class="sidebar-link <?= $currentPage==='show-employers.php'?'active':'' ?>"
               href="<?= ADMINURL ?>/users/show-employers.php">
              <i class="far fa-building"></i><span>Employers</span>
            </a>

            <a class="sidebar-link <?= $currentPage==='show-jobseekers.php'?'active':'' ?>"
               href="<?= ADMINURL ?>/users/show-jobseekers.php">
              <i class="far fa-user"></i><span>Job Seekers</span>
            </a>

            <a class="sidebar-link <?= $currentPage==='show-jobregions.php'?'active':'' ?>"
               href="<?= ADMINURL ?>/job-regions/show-jobregions.php">
              <i class="fas fa-map-marker-alt"></i><span>Job Regions</span>
            </a>

            <a class="sidebar-link <?= $currentPage==='show-jobs.php'?'active':'' ?>"
               href="<?= ADMINURL ?>/jobs-admins/show-jobs.php">
              <i class="far fa-clipboard"></i><span>Jobs</span>
            </a>
          </div>
        </div>
      </aside>
    <?php endif; ?>

    <!-- Content -->
<main class="content">
  <?php if (empty($suppressPageHead)): ?>
  <div class="page-head">
    <div>
      <h1 class="h4 mb-1"><?= isset($pageTitle) ? htmlspecialchars($pageTitle) : 'Overview' ?></h1>
      <nav aria-label="breadcrumb">
        <ol class="breadcrumb mb-0">
          <li class="breadcrumb-item"><a href="<?= ADMINURL ?>/index.php">Home</a></li>
          <li class="breadcrumb-item active" aria-current="page"><?= isset($breadcrumb) ? htmlspecialchars($breadcrumb) : 'Dashboard' ?></li>
        </ol>
      </nav>
    </div>
  </div>
  <?php endif; ?>

