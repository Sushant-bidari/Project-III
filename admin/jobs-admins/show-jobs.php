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
$pageTitle  = "All Jobs";
$breadcrumb = "Jobs";

// Helpers
function h($v){ return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }

// Inputs (filters)
$q         = trim($_GET['q'] ?? '');
$categoryF = trim($_GET['category'] ?? '');
$employerF = trim($_GET['employer'] ?? '');

// Pagination
$limit   = 10;
$page    = (isset($_GET['page']) && is_numeric($_GET['page']) && $_GET['page'] > 0) ? (int)$_GET['page'] : 1;
$offset  = ($page - 1) * $limit;
$counter = $offset + 1;

// Build WHERE
$where  = [];
$params = [];
if ($categoryF !== '') { $where[] = "job_category = :cat";      $params[':cat'] = $categoryF; }
if ($employerF !== '') { $where[] = "company_name = :emp";      $params[':emp'] = $employerF; }
if ($q !== '')         { $where[] = "(job_title LIKE :q OR job_category LIKE :q OR company_name LIKE :q)"; $params[':q'] = '%'.$q.'%'; }
$whereSql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';

// Distinct filters (dropdowns)
$categories = $conn->query("SELECT DISTINCT job_category FROM jobs WHERE TRIM(job_category) <> '' ORDER BY job_category ASC")->fetchAll(PDO::FETCH_COLUMN);
$employers  = $conn->query("SELECT DISTINCT company_name FROM jobs WHERE TRIM(company_name) <> '' ORDER BY company_name ASC")->fetchAll(PDO::FETCH_COLUMN);

// Totals (with filters)
$totalStmt = $conn->prepare("SELECT COUNT(*) AS total FROM jobs $whereSql");
$totalStmt->execute($params);
$totalRecords = (int)$totalStmt->fetch(PDO::FETCH_OBJ)->total;
$totalPages   = max(1, (int)ceil($totalRecords / $limit));

// Clamp page
if ($page > $totalPages) {
  $page   = $totalPages;
  $offset = ($page - 1) * $limit;
  $counter = $offset + 1;
}

// Page of jobs (with filters)
$listSql = "
  SELECT id, job_title, job_category, company_name, application_deadline, status
  FROM jobs
  $whereSql
  ORDER BY id DESC
  LIMIT :limit OFFSET :offset
";
$stmt = $conn->prepare($listSql);
foreach ($params as $k=>$v) { $stmt->bindValue($k, $v); }
$stmt->bindValue(':limit',  $limit,  PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$jobs = $stmt->fetchAll(PDO::FETCH_OBJ);

// Preserve filters in pagination
$qs = [];
if ($q !== '')         $qs['q'] = $q;
if ($categoryF !== '') $qs['category'] = $categoryF;
if ($employerF !== '') $qs['employer'] = $employerF;
$baseQS = http_build_query($qs);
$sep    = $baseQS ? '&' : '';

require "../layouts/header.php";
?>

<div class="row">
  <div class="col">
    <div class="card">
      <div class="card-header">
        <div class="d-flex align-items-center justify-content-between flex-wrap">
          <div class="mb-2 mb-md-0">
            <h2 class="card-title h6 mb-0">All Jobs</h2>
            <small class="text-muted">Total: <?= (int)$totalRecords ?></small>
          </div>

          <!-- Filters/Search -->
          <form class="form-inline my-2 my-md-0" method="get" action="show-jobs.php">
            <div class="form-group mr-2 mb-2 mb-md-0">
              <label for="category" class="sr-only">Category</label>
              <select class="form-control" id="category" name="category">
                <option value="">All categories</option>
                <?php foreach ($categories as $c): ?>
                  <option value="<?= h($c) ?>" <?= ($c === $categoryF ? 'selected' : '') ?>><?= h($c) ?></option>
                <?php endforeach; ?>
              </select>
            </div>

            <div class="form-group mr-2 mb-2 mb-md-0">
              <label for="employer" class="sr-only">Employer</label>
              <select class="form-control" id="employer" name="employer">
                <option value="">All employers</option>
                <?php foreach ($employers as $e): ?>
                  <option value="<?= h($e) ?>" <?= ($e === $employerF ? 'selected' : '') ?>><?= h($e) ?></option>
                <?php endforeach; ?>
              </select>
            </div>

            <div class="form-group mr-2 mb-2 mb-md-0">
              <label for="q" class="sr-only">Search</label>
              <input type="text" class="form-control" id="q" name="q" placeholder="Search title, category, employer…" value="<?= h($q) ?>">
            </div>

            <button type="submit" class="btn btn-primary">Filter</button>
            <?php if ($q !== '' || $categoryF !== '' || $employerF !== ''): ?>
              <a href="show-jobs.php" class="btn btn-outline-secondary ml-2">Reset</a>
            <?php endif; ?>
          </form>
        </div>
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
                <th style="width:120px">Action</th>
              </tr>
            </thead>
            <tbody>
              <?php if (!$jobs): ?>
                <tr>
                  <td colspan="7" class="text-center text-muted py-4">No jobs found.</td>
                </tr>
              <?php else: ?>
                <?php foreach ($jobs as $job): ?>
                  <tr>
                    <td><?= $counter++; ?></td>
                    <td><?= h($job->job_title); ?></td>
                    <td><?= h($job->job_category); ?></td>
                    <td><?= h($job->company_name); ?></td>
                    <td>
                      <?php $ts = strtotime($job->application_deadline ?: ''); echo $ts ? date('j M, Y', $ts) : '—'; ?>
                    </td>
                    <td>
                      <?php if ((int)$job->status === 1): ?>
                        <a
                          href="<?= ADMINURL ?>/jobs-admins/status-jobs.php?id=<?= (int)$job->id; ?>&status=1&r=<?= urlencode($_SERVER['REQUEST_URI']); ?>"
                          class="btn btn-outline-danger btn-sm"
                          title="Mark as Unverified">Unverify</a>
                      <?php else: ?>
                        <a
                          href="<?= ADMINURL ?>/jobs-admins/status-jobs.php?id=<?= (int)$job->id; ?>&status=0&r=<?= urlencode($_SERVER['REQUEST_URI']); ?>"
                          class="btn btn-outline-success btn-sm"
                          title="Mark as Verified">Verify</a>
                      <?php endif; ?>
                    </td>
                    <td>
                      <a
                        href="<?= ADMINURL ?>/jobs-admins/delete-jobs.php?id=<?= (int)$job->id; ?>"
                        class="btn btn-danger btn-sm"
                        title="Delete job"
                        onclick="return confirm('Delete this job posting? This action cannot be undone.');">
                        <i class="fa fa-trash"></i>
                      </a>
                    </td>
                  </tr>
                <?php endforeach; ?>
              <?php endif; ?>
            </tbody>
          </table>
        </div>

        <!-- Pagination -->
        <nav class="p-3">
          <ul class="pagination justify-content-center mb-0">
            <li class="page-item <?= ($page <= 1 ? 'disabled' : '') ?>">
              <a class="page-link" href="?<?= $baseQS . $sep ?>page=<?= max(1, $page - 1) ?>">Previous</a>
            </li>
            <?php for ($i = 1; $i <= $totalPages; $i++): ?>
              <li class="page-item <?= ($page === $i ? 'active' : '') ?>">
                <a class="page-link" href="?<?= $baseQS . $sep ?>page=<?= $i ?>"><?= $i ?></a>
              </li>
            <?php endfor; ?>
            <li class="page-item <?= ($page >= $totalPages ? 'disabled' : '') ?>">
              <a class="page-link" href="?<?= $baseQS . $sep ?>page=<?= min($totalPages, $page + 1) ?>">Next</a>
            </li>
          </ul>
        </nav>

      </div>
    </div>
  </div>
</div>

<?php require "../layouts/footer.php"; ?>
