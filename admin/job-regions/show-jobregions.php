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
// Auth first
if (!isset($_SESSION['adminname'])) {
  header("location: " . ADMINURL . "/admins/login-admins.php");
  exit;
}

// Page context for header
$pageTitle  = "Job Regions";
$breadcrumb = "System";

// Helper
function h($v){ return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }

// ---- Flash alerts via query flags ----
$flash = null;
if (isset($_GET['created']) && $_GET['created'] === '1') {
  $flash = ['class' => 'alert-success', 'msg' => 'Job region created successfully.'];
} elseif (isset($_GET['updated']) && $_GET['updated'] === '1') {
  $flash = ['class' => 'alert-info', 'msg' => 'Job region updated successfully.'];
} elseif (isset($_GET['deleted']) && $_GET['deleted'] === '1') {
  $flash = ['class' => 'alert-danger', 'msg' => 'Job region deleted successfully.'];
} elseif (!empty($_GET['error'])) {
  $flash = ['class' => 'alert-warning', 'msg' => h($_GET['error'])];
}

// Pagination
$limit   = 10;
$page    = (isset($_GET['page']) && is_numeric($_GET['page']) && $_GET['page'] > 0) ? (int)$_GET['page'] : 1;
$offset  = ($page - 1) * $limit;
$counter = $offset + 1;

// Totals
$totalStmt    = $conn->query("SELECT COUNT(*) AS total FROM job_regions");
$totalRecords = (int)$totalStmt->fetch(PDO::FETCH_OBJ)->total;
$totalPages   = max(1, (int)ceil($totalRecords / $limit));

// Clamp page if overshoot
if ($page > $totalPages) {
  $page   = $totalPages;
  $offset = ($page - 1) * $limit;
  $counter = $offset + 1;
}

// Fetch page
$stmt = $conn->prepare("SELECT id, name, code, status FROM job_regions ORDER BY id ASC LIMIT :limit OFFSET :offset");
$stmt->bindValue(':limit',  $limit,  PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$jobRegions = $stmt->fetchAll(PDO::FETCH_OBJ);
?>

<?php require "../layouts/header.php"; ?>

<div class="row">
  <div class="col">
    <div class="card">
      <div class="card-header d-flex align-items-center justify-content-between">
        <h2 class="card-title h6 mb-0">Job Regions</h2>
        <a href="<?= ADMINURL ?>/job-regions/create-jobregions.php" class="btn btn-primary">
          <i class="fas fa-plus mr-1"></i> Add New Job Region
        </a>
      </div>

      <div class="card-body p-0">
        <?php if ($flash): ?>
          <div class="p-3">
            <div class="alert <?= $flash['class'] ?> alert-dismissible fade show mb-0" role="alert">
              <i class="fas fa-info-circle mr-1"></i> <?= $flash['msg'] ?>
              <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                <span aria-hidden="true">&times;</span>
              </button>
            </div>
          </div>
        <?php endif; ?>

        <div class="table-responsive">
          <table class="table table-hover table-bordered mb-0">
            <thead>
              <tr>
                <th style="width:80px">#</th>
                <th>Region Name</th>
                <th>Code</th>
                <th style="width:120px">Status</th>
                <th style="width:200px">Actions</th>
              </tr>
            </thead>
            <tbody>
              <?php if (!$jobRegions): ?>
                <tr>
                  <td colspan="5" class="text-center text-muted py-4">No job regions found.</td>
                </tr>
              <?php else: ?>
                <?php foreach($jobRegions as $region): ?>
                  <tr>
                    <td><?= $counter++; ?></td>
                    <td><?= h($region->name); ?></td>
                    <td><?= h($region->code); ?></td>
                    <td>
                      <?php if ((int)$region->status === 1): ?>
                        <span class="badge badge-success">Active</span>
                      <?php else: ?>
                        <span class="badge badge-secondary">Inactive</span>
                      <?php endif; ?>
                    </td>
                    <td>
                      <a href="<?= ADMINURL ?>/job-regions/update-jobregions.php?id=<?= (int)$region->id; ?>"
                         class="btn btn-secondary btn-sm text-white">Update</a>
                      <a href="<?= ADMINURL ?>/job-regions/delete-jobregions.php?id=<?= (int)$region->id; ?>"
                         class="btn btn-danger btn-sm"
                         onclick="return confirm('Delete this job region? This action cannot be undone.');">Delete</a>
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

      </div>
    </div>
  </div>
</div>

<?php require "../layouts/footer.php"; ?>
