<?php
require "../config/config.php";
/*
|--------------------------------------------------------------------------
| Educational Use License (EUL)
|--------------------------------------------------------------------------
| Copyright © 2026 CodeAstro
|
| This file is part of an educational project developed by CodeAstro.
| It is licensed for educational and academic use only.
|
| ❌ Redistribution, re-uploading, commercial use, or removal of this
|    notice is strictly prohibited without written permission.
|
| Author  : CodeAstro
| Website : https://codeastro.com
|--------------------------------------------------------------------------
*/
/* ---- Auth: Job Seeker only ---- */
if (!isset($_SESSION['id']) || ($_SESSION['type'] ?? '') !== "Job Seeker") {
  header("location: " . APPURL);
  exit;
}
$user_id = (int)$_SESSION['id'];

/* ---- Helpers ---- */
function table_has_column(PDO $conn, string $table, string $col): bool {
  try {
    $q = $conn->prepare("SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
                         WHERE TABLE_SCHEMA = DATABASE()
                           AND TABLE_NAME = :t
                           AND COLUMN_NAME = :c");
    $q->execute([':t'=>$table, ':c'=>$col]);
    return (bool)$q->fetchColumn();
  } catch (Throwable $e) {
    return false;
  }
}

//application status mapping with deadline logic
function applicant_status_badge($status, bool $deadlinePast){
  $status = (string)$status;

  // After deadline mappings
  if ($deadlinePast) {
    switch ($status) {
      case '0': return '<span class="badge badge-warning">Application Closed</span>';                 
      case '1': return '<span class="badge badge-primary">Under Review (After Deadline)</span>';     
      case '2': return '<span class="badge badge-success">Shortlisted</span>';                       
      case '3': return '<span class="badge badge-danger">Not Selected</span>';                      
      case '4': return '<span class="badge badge-secondary">Withdrawn</span>';                       
      default:  return '<span class="badge badge-secondary">Unknown</span>';
    }
  }

  // Before (or on) deadline mappings
  switch ($status) {
    case '0': return '<span class="badge badge-info">Application Received</span>';
    case '1': return '<span class="badge badge-primary">In Review</span>';
    case '2': return '<span class="badge badge-success">Shortlisted</span>';     // internal "Approved"
    case '3': return '<span class="badge badge-danger">Not Selected</span>';     // internal "Rejected"
    case '4': return '<span class="badge badge-secondary">Withdrawn</span>';
    default:  return '<span class="badge badge-secondary">Unknown</span>';
  }
}


/* ---- Filters (GET) ---- */
$statusFilter = isset($_GET['status']) && $_GET['status'] !== '' ? trim($_GET['status']) : '';
$search       = isset($_GET['q']) ? trim($_GET['q']) : '';

/* ---- Pagination ---- */
$perPage = 10;
$page = isset($_GET['page']) && ctype_digit((string)$_GET['page']) ? (int)$_GET['page'] : 1;
if ($page < 1) $page = 1;
$offset = ($page - 1) * $perPage;

/* ---- Dynamic “applied_at” selection ---- */
$hasCreatedAt = table_has_column($conn, 'job_applications', 'created_at');
$appliedSelect = $hasCreatedAt ? "a.created_at" : "NULL";
$appliedAlias  = "applied_at";

/* ---- Build query ---- */
$sql = "
  SELECT 
    a.id                 AS application_id,
    a.application_status AS application_status,
    a.cv                 AS resume_filename,
    j.id                 AS job_id,
    j.job_title          AS job_title,
    j.company_name       AS company_name,
    j.application_deadline AS application_deadline,
    $appliedSelect       AS $appliedAlias
  FROM job_applications a
  LEFT JOIN jobs j ON a.job_id = j.id
  WHERE a.worker_id = :uid
";
$params = [':uid'=>$user_id];

if ($statusFilter !== '') {
  $sql .= " AND a.application_status = :st";
  $params[':st'] = $statusFilter;
}
if ($search !== '') {
  $sql .= " AND (j.job_title LIKE :q OR j.company_name LIKE :q)";
  $params[':q'] = "%".$search."%";
}
// Finish base SQL
$sql .= " ORDER BY a.id DESC";

/* ---- Total count (for pagination) ---- */
$countWhere = "WHERE a.worker_id = :uid";
if ($statusFilter !== '') {
  $countWhere .= " AND a.application_status = :st";
}
if ($search !== '') {
  $countWhere .= " AND (j.job_title LIKE :q OR j.company_name LIKE :q)";
}
$countSql = "SELECT COUNT(*) 
             FROM job_applications a
             LEFT JOIN jobs j ON a.job_id = j.id
             $countWhere";
$countParams = [':uid' => $user_id];
if ($statusFilter !== '') $countParams[':st'] = $statusFilter;
if ($search !== '')       $countParams[':q']  = "%".$search."%";

$countStmt = $conn->prepare($countSql);
$countStmt->execute($countParams);
$totalRows   = (int)$countStmt->fetchColumn();
$totalPages  = (int)ceil($totalRows / $perPage);

/* ---- Paged query ---- */
$sql .= " LIMIT :limit OFFSET :offset";
$stmt = $conn->prepare($sql);
foreach ($params as $key => $val) { $stmt->bindValue($key, $val); }
$stmt->bindValue(':limit',  $perPage, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset,  PDO::PARAM_INT);
$stmt->execute();
$appliedJobs = $stmt->fetchAll(PDO::FETCH_OBJ);


/* ---- CSRF for withdraw ---- */
if (empty($_SESSION['csrf_token'])) {
  $_SESSION['csrf_token'] = bin2hex(random_bytes(16));
}
$CSRF = $_SESSION['csrf_token'];

require "../includes/header.php";
?>

<style>
  .site-section{ padding-top:2rem; }

  /* ===== Hero ===== */
  .companies-hero{
    position:relative; background-size:cover; background-position:center;
    padding: 60px 0; overflow:hidden;
  }
  .companies-hero .overlay-dark{ position:absolute; inset:0; background:
    radial-gradient(1200px 400px at 10% -10%, rgba(99,102,241,.22), transparent 60%),
    radial-gradient(1200px 400px at 90% 0%, rgba(6,182,212,.18), transparent 60%),
    linear-gradient(180deg, rgba(2,6,23,.65), rgba(2,6,23,.80));}
  .companies-hero .hero-inner{ position:relative; z-index:2; }
  .ch-eyebrow{ color:#c7d2fe; text-transform:uppercase; letter-spacing:.16em; font-weight:700; font-size:.75rem; }
  .ch-title{ color:#fff; font-weight:800; }
  .ch-sub{ color:#e2e8f0; }

  /* ===== Toolbar ===== */
  .aj-toolbar{
    background:#fff; border-radius:12px; box-shadow:0 2px 12px rgba(0,0,0,.06);
    padding:12px; margin-bottom:16px;
  }
  .aj-toolbar .form-control, .aj-toolbar .custom-select { min-height:42px; }

  /* ===== Table - modern cards ===== */
  .aj-table{ background:#fff; border-radius:14px; overflow:hidden; box-shadow:0 6px 24px rgba(0,0,0,.06); }
  .aj-table thead th{
    background:#f8fafc; color:#334155; border-bottom:1px solid #e5e7eb; font-weight:700;
  }
  .aj-table tbody tr{
    background:#fff;
    box-shadow: inset 4px 0 0 var(--aj-accent,transparent);
    transition: background-color .18s ease, box-shadow .18s ease;
  }
  .aj-table tbody tr:hover{ background:#fafafa; }
  .aj-table td, .aj-table th { vertical-align: middle; }

  /* status accent per row */
  tr[data-status="0"]{ --aj-accent:#f59e0b; } /* pending */
  tr[data-status="1"]{ --aj-accent:#3b82f6; } /* processing */
  tr[data-status="2"]{ --aj-accent:#ef4444; } /* rejected */
  tr[data-status="3"]{ --aj-accent:#22c55e; } /* selected */
  tr[data-status="4"]{ --aj-accent:#64748b; } /* withdrawn */

  .aj-job { font-weight:800; margin:0; }
  .aj-sub { color:#64748b; font-size:.9rem; }
  .aj-actions{ display:flex; flex-wrap:wrap; gap:6px; }

  /* deadline chip + tiny progress */
  .due-chip{ border-radius:999px; padding:6px 10px; font-size:.875rem; font-weight:700; line-height:1; }
  .due-ok{ background:#ecfdf5; color:#065f46; }
  .due-warn{ background:#fff7ed; color:#9a3412; }
  .due-danger{ background:#fef2f2; color:#991b1b; }
  .deadline-track{ width:140px; height:6px; background:#eef2f7; border-radius:999px; overflow:hidden; display:inline-block; vertical-align:middle; margin-left:8px; }
  .deadline-fill{ height:100%; background:linear-gradient(90deg,#22c55e,#f59e0b,#ef4444); }

  /* Empty state */
  .aj-empty{
    background:#fff; border-radius:16px; box-shadow:0 2px 16px rgba(0,0,0,.06);
    padding:28px; text-align:center;
  }

  /* Mobile: stack like cards */
  @media (max-width: 768px){
    .table.aj-table thead{ display:none; }
    .table.aj-table tbody tr{ display:block; padding:12px 12px; margin-bottom:12px; border-radius:12px; border:1px solid #e5e7eb; }
    .table.aj-table tbody td{ display:block; border:0; padding:4px 0; }
    .table.aj-table tbody td[data-label]:before{
      content: attr(data-label) ": ";
      font-weight:700; color:#334155; display:inline-block; width:auto;
    }
    .deadline-track{ width:100%; margin:8px 0 0 0; display:block; }
  }
</style>

<!-- HERO -->
<section class="companies-hero overlay inner-page bg-image" style="background-image:url('../images/tst.jpg');" id="home-section">
  <div class="overlay-dark"></div>
  <div class="container hero-inner text-center">
    <div class="ch-eyebrow mb-1">Dashboard</div>
    <h2 class="ch-title mb-2">My Applications</h2>
    <p class="ch-sub mb-0">Track every application, status, and deadline in one place.</p>
  </div>
</section>

<section class="site-section" id="next-section">
  <div class="container">

    <?php if (isset($_GET['msg'])): ?>
      <div class="alert alert-info alert-dismissible fade show" role="alert">
        <?php
          switch ($_GET['msg']) {
            case 'withdrawn':  echo "Application withdrawn successfully."; break;
            case 'notfound':   echo "Application not found or access denied."; break;
            case 'invalid':    echo "Invalid request."; break;
            case 'deadline':   echo "You can’t withdraw after the application deadline."; break;
            case 'locked':     echo "This application can no longer be withdrawn."; break;
            case 'csrf':       echo "Your session expired. Please try again."; break;
            case 'error':      echo "Something went wrong, please try again."; break;
            default:           echo "Status updated.";
          }
        ?>
        <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span>&times;</span></button>
      </div>
    <?php endif; ?>

    <!-- Toolbar -->
    <form class="aj-toolbar" method="get" action="applied_jobs.php">
      <div class="form-row align-items-end">
        <div class="col-md-5 mb-2">
          <label class="mb-1 font-weight-bold text-muted">Search</label>
          <input type="text" name="q" value="<?php echo htmlspecialchars($search); ?>" class="form-control" placeholder="Search job or company…">
        </div>
        <div class="col-md-3 mb-2">
          <label class="mb-1 font-weight-bold text-muted">Status</label>
          <select name="status" class="custom-select">
            <option value="">All statuses</option>
            <?php
            $opts = [
              '0' => 'Application Received',
              '1' => 'In Review',
              '2' => 'Shortlisted',
              '3' => 'Not Selected',
              '4' => 'Withdrawn',
            ];
            foreach ($opts as $k=>$lbl) {
              $sel = ($statusFilter !== '' && $statusFilter === $k) ? 'selected' : '';
              echo '<option value="'.$k.'" '.$sel.'>'.$lbl.'</option>';
            }
          ?>

          </select>
        </div>
        <div class="col-md-2 mb-2">
          <button class="btn btn-primary btn-block" type="submit"><i class="icon-search mr-1"></i> Filter</button>
        </div>
        <div class="col-md-2 mb-2">
          <?php if ($statusFilter !== '' || $search !== ''): ?>
            <a href="applied_jobs.php" class="btn btn-light btn-block">Reset</a>
          <?php endif; ?>
        </div>
      </div>
    </form>

    <?php
      // small helper for deadline UI
      function due_chip_class($daysLeft){
        if ($daysLeft === null) return '';
        if ($daysLeft <= 3) return 'due-danger';
        if ($daysLeft <= 10) return 'due-warn';
        return 'due-ok';
      }
    ?>

    <div class="table-responsive">
      <table class="table aj-table table-bordered">
        <thead>
          <tr>
            <th style="min-width:260px;">Job</th>
            <th>Company</th>
            <th>Applied</th>
            <th>Deadline</th>
            <th>Status</th>
            <th style="min-width:220px;">Actions</th>
          </tr>
        </thead>
        <tbody>
        <?php if (!$appliedJobs): ?>
          <tr>
            <td colspan="6" class="py-4">
              <div class="aj-empty">
                <h5 class="mb-1">No applications yet</h5>
                <p class="text-muted mb-3">Start exploring jobs and apply to see them listed here.</p>
                <a class="btn btn-primary" href="<?php echo $base_url; ?>/findjobs.php">Browse Jobs</a>
              </div>
            </td>
          </tr>
        <?php else: ?>
          <?php foreach ($appliedJobs as $job): ?>
            <?php
              $appliedAt   = $job->applied_at ? strtotime($job->applied_at) : null;
              $deadlineTs  = $job->application_deadline ? strtotime($job->application_deadline) : null;
              $appliedStr  = $appliedAt ? date('j M, Y', $appliedAt) : '—';
              $deadlineStr = $deadlineTs ? date('j M, Y', $deadlineTs) : '—';

              $status = (string)$job->application_status;
              $finalLocked  = in_array($status, ['2','3','4'], true);
              $deadlinePast = $deadlineTs ? (time() > $deadlineTs) : false;
              $withdrawDisabled = $finalLocked || $deadlinePast;

              $viewHref = $job->job_id ? ($base_url . "/jobs/job-single.php?id=" . (int)$job->job_id) : "#";
              $resumeHref = (!empty($job->resume_filename))
                  ? ($base_url . "/user-cvs/" . rawurlencode($job->resume_filename))
                  : "";

              $daysLeft = null;
              $pct = 0;
              if ($deadlineTs) {
                $daysLeft = max(0, ceil(($deadlineTs - time()) / 86400));
              }
              if ($deadlineTs && $appliedAt) {
                $totalWindow = max(1, $deadlineTs - $appliedAt);
                $elapsed     = max(0, min($totalWindow, time() - $appliedAt));
                $pct         = round(($elapsed / $totalWindow) * 100);
              }
              $chipClass = due_chip_class($daysLeft);
            ?>
            <tr class="aj-row" data-status="<?php echo htmlspecialchars($status); ?>">
              <td data-label="Job">
                <div class="aj-job"><?php echo htmlspecialchars($job->job_title ?: '[Deleted Job]'); ?></div>
                <?php if ($job->job_id): ?>
                  <div class="aj-sub">
                    <a href="<?php echo $viewHref; ?>" target="_blank">View job</a>
                  </div>
                <?php endif; ?>
              </td>
              <td data-label="Company"><?php echo htmlspecialchars($job->company_name ?: '—'); ?></td>
              <td data-label="Applied"><?php echo $appliedStr; ?></td>
              <td data-label="Deadline">
                <?php echo $deadlineStr; ?>
                <?php if ($daysLeft !== null): ?>
                  <div class="mt-1">
                    <span class="due-chip <?php echo $chipClass; ?>">
                      <?php echo $daysLeft > 0 ? ($daysLeft . ' day' . ($daysLeft>1?'s':'') . ' left') : 'Closed'; ?>
                    </span>
                  </div>
                <?php endif; ?>
              </td>
              <td data-label="Status">
                <?= applicant_status_badge($status, $deadlinePast) ?>
              </td>

              <td data-label="Actions">
                <div class="aj-actions">
                  <?php if ($resumeHref): ?>
                    <a class="btn btn-outline-secondary btn-sm" href="<?php echo $resumeHref; ?>" target="_blank">
                      <i class="fa fa-download"></i> Resume
                    </a>
                  <?php endif; ?>

                  <?php if ($job->job_id): ?>
                    <a class="btn btn-outline-primary btn-sm" href="<?php echo $viewHref; ?>" target="_blank">
                      <i class="icon-eye"></i> View
                    </a>
                  <?php endif; ?>

                  <form method="post" action="withdraw-application.php" onsubmit="return confirm('Withdraw this application?');" class="d-inline">
                    <input type="hidden" name="csrf" value="<?php echo htmlspecialchars($CSRF); ?>">
                    <input type="hidden" name="id"   value="<?php echo (int)$job->application_id; ?>">
                    <button class="btn btn-outline-danger btn-sm" type="submit"
                      <?php echo $withdrawDisabled ? 'disabled aria-disabled="true" title="Cannot withdraw after deadline or once finalized."' : ''; ?>>
                      <i class="icon-close"></i> Withdraw
                    </button>
                  </form>
                </div>
              </td>
            </tr>
          <?php endforeach; ?>
        <?php endif; ?>
        </tbody>
      </table>
      <?php if (!empty($totalPages) && $totalPages > 1): ?>
        <nav aria-label="Applications pages">
          <ul class="pagination justify-content-center mt-3">
            <li class="page-item <?php echo ($page <= 1) ? 'disabled' : ''; ?>">
              <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page - 1])); ?>">Previous</a>
            </li>
            <?php for ($i = 1; $i <= $totalPages; $i++): ?>
              <li class="page-item <?php echo ($page === $i) ? 'active' : ''; ?>">
                <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['page' => $i])); ?>">
                  <?php echo $i; ?>
                </a>
              </li>
            <?php endfor; ?>
            <li class="page-item <?php echo ($page >= $totalPages) ? 'disabled' : ''; ?>">
              <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page + 1])); ?>">Next</a>
            </li>
          </ul>
        </nav>
      <?php endif; ?>

    </div>

  </div>
</section>

<?php require "../includes/footer.php"; ?>
