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
// Auth first (before header include)
if (!isset($_SESSION['adminname'])) {
  header("location: " . ADMINURL . "/admins/login-admins.php");
  exit;
}

// Page context for header
$pageTitle  = "Job Seekers";
$breadcrumb = "Users";

// Helper
function h($v){ return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }

// Pagination
$limit   = 10;
$page    = (isset($_GET['page']) && is_numeric($_GET['page']) && $_GET['page'] > 0) ? (int)$_GET['page'] : 1;
$offset  = ($page - 1) * $limit;
$counter = $offset + 1;

// Totals (case-insensitive type match)
$totalStmt = $conn->query("SELECT COUNT(*) AS total FROM users WHERE UPPER(type) = 'JOB SEEKER'");
$totalRecords = (int)$totalStmt->fetch(PDO::FETCH_OBJ)->total;
$totalPages   = max(1, (int)ceil($totalRecords / $limit));

// Clamp page if it overshoots
if ($page > $totalPages) {
  $page   = $totalPages;
  $offset = ($page - 1) * $limit;
  $counter = $offset + 1;
}

// Page of job seekers
$stmt = $conn->prepare("
  SELECT id, fullname, username, email, contact
  FROM users
  WHERE UPPER(type) = 'JOB SEEKER'
  ORDER BY id ASC
  LIMIT :limit OFFSET :offset
");
$stmt->bindValue(':limit',  $limit,  PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$jobSeekers = $stmt->fetchAll(PDO::FETCH_OBJ);
?>

<?php require "../layouts/header.php"; ?>

<div class="row">
  <div class="col">
    <div class="card">
      <div class="card-header d-flex align-items-center justify-content-between">
        <h2 class="card-title h6 mb-0">Job Seekers</h2>
        <!-- Reserved for future actions (export, filters) -->
      </div>

      <div class="card-body p-0">
        <div class="table-responsive">
          <table class="table table-hover table-bordered mb-0">
            <thead>
              <tr>
                <th style="width:80px">#</th>
                <th>Full Name</th>
                <th>Username</th>
                <th>Email</th>
                <th>Contact</th>
              </tr>
            </thead>
            <tbody>
              <?php if (!$jobSeekers): ?>
                <tr>
                  <td colspan="5" class="text-center text-muted py-4">No job seekers found.</td>
                </tr>
              <?php else: ?>
                <?php foreach($jobSeekers as $user): ?>
                  <tr>
                    <td><?= $counter++; ?></td>
                    <td><?= h($user->fullname); ?></td>
                    <td><?= h($user->username); ?></td>
                    <td><?= h($user->email); ?></td>
                    <td><?= h($user->contact); ?></td>
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
