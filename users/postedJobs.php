<?php require "../config/config.php";
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
?>

<?php
// Gate: employer only
if(isset($_SESSION['type']) && $_SESSION['type'] !== "Employer") {
  header("location: ".APPURL);
  exit;
}

$employerId = (int)($_SESSION['id'] ?? 0);

//quick deadline extension ft
// CSRF helper (add)
if (empty($_SESSION['csrf_token'])) {
  $_SESSION['csrf_token'] = bin2hex(random_bytes(16));
}

// --- AJAX: extend_deadline (add) ---
if (!empty($_POST['ajax']) && ($_POST['action'] ?? '') === 'extend_deadline') {
  header('Content-Type: application/json');
  if (!$employerId) { echo json_encode(['ok'=>false,'error'=>'Not authorized']); exit; }

  if (empty($_POST['csrf']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf'])) {
    echo json_encode(['ok'=>false,'error'=>'CSRF']); exit;
  }

  $jobId = (int)($_POST['job_id'] ?? 0);
  $days  = (int)($_POST['days'] ?? 0);
  if (!$jobId || !in_array($days, [3,7,14,30], true)) {
    echo json_encode(['ok'=>false,'error'=>'Invalid input']); exit;
  }

  // makes sure  job belongs to this employer and is not expired (or has null deadline)
  $chk = $conn->prepare("
    SELECT id, application_deadline
    FROM jobs
    WHERE id = :jid AND company_id = :cid
      AND (application_deadline IS NULL OR application_deadline = '' OR DATE(application_deadline) >= CURDATE())
    LIMIT 1
  ");
  $chk->execute([':jid'=>$jobId, ':cid'=>$employerId]);
  $job = $chk->fetch(PDO::FETCH_ASSOC);
  if (!$job) { echo json_encode(['ok'=>false,'error'=>'Job not found or already expired']); exit; }

  // Extend: base is existing date or today if null/empty
  $upd = $conn->prepare("
    UPDATE jobs
    SET application_deadline = DATE_FORMAT(
      DATE_ADD(
        CASE WHEN application_deadline IS NULL OR application_deadline='' THEN CURDATE()
             ELSE DATE(application_deadline) END,
        INTERVAL :d DAY
      ), '%Y-%m-%d'
    )
    WHERE id = :jid AND company_id = :cid
    LIMIT 1
  ");
  $upd->execute([':d'=>$days, ':jid'=>$jobId, ':cid'=>$employerId]);

  // Fetch the new value to return
  $get = $conn->prepare("SELECT application_deadline FROM jobs WHERE id = :jid AND company_id = :cid LIMIT 1");
  $get->execute([':jid'=>$jobId, ':cid'=>$employerId]);
  $row = $get->fetch(PDO::FETCH_ASSOC);
  $newDeadline = $row ? $row['application_deadline'] : null;

  echo json_encode(['ok'=>true, 'new_deadline'=>$newDeadline]); exit;
}

//pagination stuffs
$limit  = 10; // Jobs per page
$page   = (isset($_GET['page']) && is_numeric($_GET['page'])) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $limit;

/* NEW: which tab? active|expired (default: active) */
$tab = (isset($_GET['tab']) && $_GET['tab'] === 'expired') ? 'expired' : 'active';

/* NEW: deadline WHERE by tab */
$deadlineWhere = ($tab === 'expired')
  ? " AND application_deadline IS NOT NULL AND DATE(application_deadline) < CURDATE() "
  : " AND (application_deadline IS NULL OR DATE(application_deadline) >= CURDATE()) ";

/* Query to retrieve job listings for the logged-in user */
$user_id = $_SESSION['id']; // current employer id
$sql = "SELECT * FROM jobs WHERE company_id = :id {$deadlineWhere} ORDER BY id DESC LIMIT :limit OFFSET :offset";
$stmt = $conn->prepare($sql);
$stmt->bindValue(':id', $user_id, PDO::PARAM_INT);
$stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$jobListings = $stmt->fetchAll(PDO::FETCH_OBJ);

/* Total count for current tab */
$countStmt = $conn->prepare("SELECT COUNT(*) FROM jobs WHERE company_id = :id {$deadlineWhere}");
$countStmt->bindParam(':id', $user_id, PDO::PARAM_INT);
$countStmt->execute();
$totalJobs  = (int)$countStmt->fetchColumn();
$totalPages = (int)ceil($totalJobs / $limit);


// Count applicants per job
$applicantCounts = [];
if (!empty($jobListings)) {
  $jobIds = array_map(function($job){ return $job->id; }, $jobListings);
  $inQuery = implode(',', array_fill(0, count($jobIds), '?'));
  $stmt = $conn->prepare("
    SELECT job_id, COUNT(*) as total_applicants
    FROM job_applications
    WHERE job_id IN ($inQuery)
    GROUP BY job_id
  ");
  $stmt->execute($jobIds);
  while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $applicantCounts[$row['job_id']] = $row['total_applicants'];
  }
}


$pendingStmt = $conn->prepare("SELECT COUNT(*) FROM job_applications WHERE company_id = :cid AND application_status = 0");
$pendingStmt->execute([':cid'=>$employerId]);
$pendingApplications = (int)$pendingStmt->fetchColumn();

require "../includes/header.php";
?>

<style>
  .emp-wrap { padding-top: 2rem; }
  .emp-sidebar {
    background:#fff; border:0; border-radius:14px;
    box-shadow:0 8px 24px rgba(0,0,0,.06);
    position: sticky; top: 82px;
  }
  .emp-sidebar .list-group-item{
    border:0; border-left:3px solid transparent; border-radius:0;
  }
  .emp-sidebar .list-group-item:hover{ background:#f8fafc; text-decoration: none; }
  .emp-sidebar .list-group-item.active{
    background:#eef2ff; color:#111827; border-left-color:#6366f1; font-weight:700; text-decoration: none;
  }
  .emp-card{
    background:#fff; border:0; border-radius:14px; box-shadow:0 8px 24px rgba(0,0,0,.06);
  }
</style>

<div class="container-fluid emp-wrap">
  <div class="row">
    <!-- Sidebar -->
    <aside class="col-lg-3 mb-4">
      <div class="emp-sidebar p-2">
        <div class="list-group list-group-flush">
          <a href="<?php echo $base_url; ?>/users/employer_dashboard.php" class="list-group-item">
            <i class="fa-solid fa-gauge mr-2"></i> Dashboard
          </a>
          <a href="<?php echo $base_url; ?>/jobs/post-job.php" class="list-group-item">
            <i class="fa-solid fa-plus mr-2"></i> Post a Job
          </a>
          <a href="<?php echo $base_url; ?>/users/show-applicants.php?id=<?php echo $employerId; ?>" class="list-group-item">
            <i class="fa-solid fa-users-line mr-2"></i> Show Applicants
          </a>
          <a href="<?php echo $base_url; ?>/users/postedJobs.php" class="list-group-item active">
            <i class="fa-solid fa-briefcase mr-2"></i> Posted Jobs
          </a>
          <a href="<?php echo $base_url; ?>/users/employer_insights.php" class="list-group-item">
            <i class="fa-solid fa-chart-line mr-2"></i> Insights
          </a>
        </div>
      </div>
    </aside>

    <!-- Main -->
    <main class="col-lg-9">
      <div class="emp-card p-3 mb-3 d-flex align-items-center justify-content-between">
        <h4 class="mb-0">Posted Jobs</h4>
      </div>

      <div class="emp-card p-3">
        <?php if (isset($_GET['msg']) && $_GET['msg'] == 'deleted'): ?>
          <div class="alert alert-success">Job deleted successfully.</div>
        <?php endif; ?>

        <?php if (isset($_GET['msg']) && $_GET['msg'] === 'extended'): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
          Deadline extended successfully.
          <button type="button" class="close" data-dismiss="alert" aria-label="Close">
            <span aria-hidden="true">&times;</span>
          </button>
        </div>
      <?php endif; ?>


        <div class="table-responsive">
          <!-- Tabs: Active / Expired -->
        <ul class="nav nav-pills mb-3">
          <li class="nav-item">
            <a class="nav-link <?= $tab==='active'?'active':'' ?>" href="?tab=active<?= $page>1 ? '&page='.((int)$page) : '' ?>">Active Postings</a>
          </li>
          <li class="nav-item">
            <a class="nav-link <?= $tab==='expired'?'active':'' ?>" href="?tab=expired<?= $page>1 ? '&page='.((int)$page) : '' ?>">Expired Postings</a>
          </li>
        </ul>

          <table class="table table-striped table-hover table-bordered w-100">
            <thead>
              <tr>
                <th>Job Title</th>
                <th>State</th>
                <th>Type</th>
                <th>Vacancy</th>
                <th>Applicants</th>
                <!-- <th>Salary</th> -->
                <th>Deadline</th>
                <th>Status</th>
                <th>Action</th>
              </tr>
            </thead>
            <tbody>
            <?php if (!empty($jobListings)) : ?>
              <?php foreach ($jobListings as $job) : ?>
                <tr>
                  <td><?php echo htmlspecialchars($job->job_title); ?></td>
                  <td><?php echo htmlspecialchars($job->job_region); ?></td>
                  <td><?php echo htmlspecialchars($job->job_type); ?></td>
                  <td><?php echo htmlspecialchars($job->vacancy); ?></td>
                  <td>
                    <?php $count = isset($applicantCounts[$job->id]) ? $applicantCounts[$job->id] : 0; ?>
                    <a href="show-applicants.php?id=<?= $_SESSION['id'] ?>&job_id=<?= $job->id ?>"><?= $count ?></a>
                  </td>
                  <!-- <td><?php echo htmlspecialchars($job->salary); ?></td> -->
                  <td>
                  <?php
                    $deadlineTs = $job->application_deadline ? strtotime($job->application_deadline) : null;
                    $dateStr    = $deadlineTs ? date('j M, Y', $deadlineTs) : '—';
                    echo htmlspecialchars($dateStr);

                    if ($deadlineTs) {
                      // days difference (rounded up for remaining days)
                      $diffDays = (int)ceil(($deadlineTs - time()) / 86400);
                      echo '<div class="small text-muted mt-1">';
                      if ($diffDays > 0) {
                        echo $diffDays . ' day' . ($diffDays === 1 ? '' : 's') . ' remaining';
                      } elseif ($diffDays === 0) {
                        echo 'Deadline today';
                      } else {
                        $ago = abs($diffDays);
                        echo 'Expired ' . $ago . ' day' . ($ago === 1 ? '' : 's') . ' ago';
                      }
                      echo '</div>';
                    }
                  ?>
                </td>

                  <td>
                    <?php if ($job->status == 1): ?>
                      <span class="badge badge-success text-white">Verified</span>
                    <?php else: ?>
                      <span class="badge badge-warning">Pending Verification</span>
                    <?php endif; ?>
                  </td>
                  <td>
                    <?php
                      // Existing update/delete buttons remain
                    ?>
                    <a href="../jobs/job-update.php?id=<?php echo $job->id;?>" class="btn btn-primary btn-sm">Update</a>
                    <a href="../jobs/job-delete.php?id=<?php echo $job->id;?>" class="btn btn-danger btn-sm" onclick="return confirm('Are you sure you want to delete this job?');">Delete</a>

                    <?php
                      // NEW: Quick Extend (only show on Active tab)
                      if ($tab === 'active') {
                        $deadlineTs = $job->application_deadline ? strtotime($job->application_deadline) : null;
                        $isExpired  = $deadlineTs ? (date('Y-m-d', $deadlineTs) < date('Y-m-d')) : false;
                    ?>
                      <div class="btn-group ml-1" data-toggle="tooltip" data-bs-toggle="tooltip">
                      <button type="button"
                              class="btn btn-outline-secondary btn-sm dropdown-toggle"
                              data-toggle="dropdown" data-bs-toggle="dropdown"
                              <?php echo $isExpired ? 'disabled aria-disabled="true"' : ''; ?>>
                        Extend Deadline
                      </button>
                      <div class="dropdown-menu dropdown-menu-right">
                        <a class="dropdown-item js-extend-deadline" href="#" data-job="<?php echo (int)$job->id; ?>" data-days="3">+3 days</a>
                        <a class="dropdown-item js-extend-deadline" href="#" data-job="<?php echo (int)$job->id; ?>" data-days="7">+7 days</a>
                        <a class="dropdown-item js-extend-deadline" href="#" data-job="<?php echo (int)$job->id; ?>" data-days="14">+14 days</a>
                        <a class="dropdown-item js-extend-deadline" href="#" data-job="<?php echo (int)$job->id; ?>" data-days="30">+30 days</a>
                      </div>
                    </div>

                    <?php } ?>
                  </td>

                </tr>
              <?php endforeach; ?>
            <?php else : ?>
              <tr>
                <td colspan="9" class="text-center py-4">
                  <i class="fas fa-briefcase fa-lg mb-2 d-block"></i>
                  No job listings found.
                </td>
              </tr>
            <?php endif; ?>
            </tbody>
          </table>
        </div>

        <?php if ($totalPages > 1): ?>
          <nav aria-label="Page navigation example">
            <ul class="pagination justify-content-center mb-0">
              <?php if ($page > 1): ?>
              <li class="page-item">
                <a class="page-link" href="?tab=<?= htmlspecialchars($tab) ?>&page=<?= $page - 1 ?>" aria-label="Previous">&laquo; Prev</a>
              </li>
            <?php endif; ?>

            <?php for ($i = 1; $i <= $totalPages; $i++): ?>
              <li class="page-item <?= ($page == $i) ? 'active' : '' ?>">
                <a class="page-link" href="?tab=<?= htmlspecialchars($tab) ?>&page=<?= $i ?>"><?= $i ?></a>
              </li>
            <?php endfor; ?>

            <?php if ($page < $totalPages): ?>
              <li class="page-item">
                <a class="page-link" href="?tab=<?= htmlspecialchars($tab) ?>&page=<?= $page + 1 ?>" aria-label="Next">Next &raquo;</a>
              </li>
            <?php endif; ?>

            </ul>
          </nav>
        <?php endif; ?>
      </div>
    </main>
  </div>
</div>

<?php if (!empty($pendingApplications) && $pendingApplications > 0): ?>
  <div class="toast show" role="alert" aria-live="assertive" aria-atomic="true" data-delay="35000"
      style="position: fixed; bottom: 20px; right: 20px; min-width: 250px; z-index: 9999;">
    <div class="toast-header bg-danger text-white">
      <strong class="mr-auto">Job Applications</strong>
      <small>Just now</small>
      <button type="button" class="ml-2 mb-1 close" data-dismiss="toast" aria-label="Close">
        <span aria-hidden="true">&times;</span>
      </button>
    </div>
    <div class="toast-body">
      You have <strong><?= $pendingApplications ?></strong> new job application<?= $pendingApplications > 1 ? 's' : '' ?> pending review.
    </div>
  </div>
<?php endif; ?>

<!-- Extend Deadline Modal (add) -->
<div class="modal fade" id="extendDeadlineModal" tabindex="-1" role="dialog" aria-labelledby="extendDeadlineLabel" aria-hidden="true">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="extendDeadlineLabel">Extend Deadline</h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span>&times;</span></button>
      </div>
      <div class="modal-body">
        Extend application deadline by <strong id="ext-days-text"></strong>?
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
        <button type="button" class="btn btn-primary" id="ext-confirm">Confirm</button>
      </div>
    </div>
  </div>
</div>

<script>
(function($){
  $(function(){
    if ($.fn && $.fn.tooltip) {
      $('[data-toggle="tooltip"]').tooltip();
    } else if (window.bootstrap && typeof bootstrap.Tooltip === 'function') {
      document.querySelectorAll('[data-bs-toggle="tooltip"],[data-toggle="tooltip"]').forEach(function(el){
        try { new bootstrap.Tooltip(el); } catch(_) {}
      });
    }
  });

  var extJobId = null, extDays = null;
  var bs5ModalInstance = null;

  function showModal(){
    var el = document.getElementById('extendDeadlineModal');
    if (!el) return false;

    if ($.fn && $.fn.modal) {
      try {
        $('#extendDeadlineModal').modal('show');
        return true;
      } catch(_) {}
    }

    if (window.bootstrap && typeof bootstrap.Modal === 'function') {
      try {
        // reuse instance if exists
        bs5ModalInstance = bs5ModalInstance || new bootstrap.Modal(el, {});
        bs5ModalInstance.show();
        return true;
      } catch(_) {}
    }

    return false;
  }

  function hideModal(){
    if ($.fn && $.fn.modal) {
      try { $('#extendDeadlineModal').modal('hide'); } catch(_) {}
      return;
    }
    if (bs5ModalInstance && typeof bs5ModalInstance.hide === 'function') {
      try { bs5ModalInstance.hide(); } catch(_) {}
    }
  }

  // Open confirm modal (or native confirm fallback if Bootstrap modal isn't available)
  $(document).on('click', '.js-extend-deadline', function(e){
    e.preventDefault();
    extJobId = $(this).data('job');
    extDays  = $(this).data('days');

    // Set the text inside modal
    document.getElementById('ext-days-text').textContent = '+' + extDays + ' days';

    if (!showModal()) {
      if (confirm('Extend application deadline by +' + extDays + ' days?')) {
        doExtend(extJobId, extDays);
      }
    }
  });

  // Confirm -> AJAX
  $('#ext-confirm').on('click', function(){
    if(!extJobId || !extDays){ return; }
    doExtend(extJobId, extDays);
    hideModal();
  });

  function doExtend(jobId, days){
    $.post('postedJobs.php?tab=<?= htmlspecialchars($tab) ?>&page=<?= (int)$page ?>', {
      ajax:1,
      action:'extend_deadline',
      job_id: jobId,
      days:   days,
      csrf:  '<?= $_SESSION['csrf_token']; ?>'
    }, function(res){
      if(!res || !res.ok){
        alert((res && res.error) ? res.error : 'Unable to extend deadline.');
        return;
      }
      // Reload to reflect new date, keep tab & page; show success flag
      window.location = '?tab=<?= htmlspecialchars($tab) ?>&page=<?= (int)$page ?>&msg=extended';
    }, 'json').fail(function(){
      alert('Network error.');
    });
  }
})(jQuery);
</script>



<?php require "../includes/footer.php"; ?>
