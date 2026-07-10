<?php require "../../config/config.php";
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
// --- Auth ---
if (!isset($_SESSION['adminname'])) {
  header("location: " . ADMINURL . "/admins/login-admins.php");
  exit;
}

// Page context
$pageTitle  = "Pending Job Postings";
$breadcrumb = "Jobs";

// Helper
function h($v){ return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }

// Pagination
$limit   = 10;
$page    = (isset($_GET['page']) && is_numeric($_GET['page']) && $_GET['page'] > 0) ? (int)$_GET['page'] : 1;
$offset  = ($page - 1) * $limit;
$counter = $offset + 1;

// Totals (status = 0 pending)
$totalStmt    = $conn->query("SELECT COUNT(*) AS total FROM jobs WHERE status = 0");
$totalRecords = (int)$totalStmt->fetch(PDO::FETCH_OBJ)->total;
$totalPages   = max(1, (int)ceil($totalRecords / $limit));

// Clamp page if overshoot
if ($page > $totalPages) {
  $page   = $totalPages;
  $offset = ($page - 1) * $limit;
  $counter = $offset + 1;
}

// Page of pending jobs
$stmt = $conn->prepare("
  SELECT id, job_title, job_category, company_name, application_deadline, status
  FROM jobs
  WHERE status = 0
  ORDER BY id DESC
  LIMIT :limit OFFSET :offset
");
$stmt->bindValue(':limit',  $limit,  PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$jobs = $stmt->fetchAll(PDO::FETCH_OBJ);

require "../layouts/header.php";
?>

<div class="row">
  <div class="col">
    <div class="card">
      <div class="card-header d-flex align-items-center justify-content-between">
        <div>
          <h2 class="card-title h6 mb-0">Pending Job Postings</h2>
          <small class="text-muted">Awaiting approval</small>
        </div>
        <span class="badge badge-warning p-2">Total: <?= (int)$totalRecords ?> pending</span>
      </div>

      <div class="card-body p-0">
        <?php if (!empty($_SESSION['admin_flash'])): ?>
          <?php $f = $_SESSION['admin_flash']; unset($_SESSION['admin_flash']); ?>
          <div class="p-3">
            <div class="alert alert-<?= h($f['type']) ?> alert-dismissible fade show mb-0" role="alert">
              <?= h($f['text']) ?>
              <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                <span aria-hidden="true">&times;</span>
              </button>
            </div>
          </div>
        <?php endif; ?>

        <div class="table-responsive">
          <table class="table table-hover table-bordered table-striped mb-0">
            <thead>
              <tr>
                <th style="width:80px">#</th>
                <th>Title</th>
                <th>Category</th>
                <th>Employer</th>
                <th style="width:140px">Deadline</th>
                <th style="width:120px">Status</th>
                <th style="width:200px">Actions</th>
              </tr>
            </thead>
            <tbody>
              <?php if (!$jobs): ?>
                <tr>
                  <td colspan="7" class="text-center text-muted py-4">No pending jobs.</td>
                </tr>
              <?php else: ?>
                <?php foreach ($jobs as $job): ?>
                  <tr>
                    <td><?= $counter++; ?></td>
                    <td><?= h($job->job_title); ?></td>
                    <td><?= h($job->job_category); ?></td>
                    <td><?= h($job->company_name); ?></td>
                    <td><?php $ts = strtotime($job->application_deadline ?: ''); echo $ts ? date('j M, Y', $ts) : '—'; ?></td>
                    <td>
                      <!-- status = 0 (pending) => show Verify -->
                      <a
                        href="<?= ADMINURL ?>/jobs-admins/status-jobs.php?id=<?= (int)$job->id ?>&status=0&r=<?= urlencode($_SERVER['REQUEST_URI']) ?>"
                        class="btn btn-outline-success btn-sm"
                        title="Mark as Verified">Verify</a>
                    </td>
                    <td>
                      <a
                        href="<?= ADMINURL ?>/jobs-admins/view-pendingjob.php?id=<?= (int)$job->id; ?>"
                        class="btn btn-primary btn-sm"
                        title="View job details"><i class="fa fa-eye"></i> View</a>
                      <a
                        href="<?= ADMINURL ?>/jobs-admins/delete-jobs.php?id=<?= (int)$job->id; ?>"
                        class="btn btn-danger btn-sm"
                        title="Delete job"
                        onclick="return confirm('Delete this pending job posting? This action cannot be undone.');"><i class="fa fa-trash"></i></a>
                    </td>
                  </tr>
                <?php endforeach; ?>
              <?php endif; ?>
            </tbody>
          </table>
        </div>

        <!-- Pagination -->
        <?php if ($totalPages > 1): ?>
          <nav class="p-3">
            <ul class="pagination justify-content-center mb-0">
              <li class="page-item <?= ($page <= 1 ? 'disabled' : '') ?>">
                <a class="page-link" href="?page=<?= max(1, $page - 1) ?>">Previous</a>
              </li>
              <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                <li class="page-item <?= ($page === $i ? 'active' : '') ?>">
                  <a class="page-link" href="?page=<?= $i ?>"><?= $i ?></a>
                </li>
              <?php endfor; ?>
              <li class="page-item <?= ($page >= $totalPages ? 'disabled' : '') ?>">
                <a class="page-link" href="?page=<?= min($totalPages, $page + 1) ?>">Next</a>
              </li>
            </ul>
          </nav>
        <?php endif; ?>

      </div>
    </div>
  </div>
</div>

<?php require "../layouts/footer.php"; ?>
