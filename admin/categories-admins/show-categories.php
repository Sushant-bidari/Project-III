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
// Auth
if (!isset($_SESSION['adminname'])) {
  header("location: " . ADMINURL . "/admins/login-admins.php");
  exit;
}

// Page context for header
$pageTitle  = "Categories";
$breadcrumb = "System";

require "../layouts/header.php";

// Helpers
function h($v){ return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }

// Pagination
$limit   = 10;
$page    = (isset($_GET['page']) && is_numeric($_GET['page']) && $_GET['page'] > 0) ? (int)$_GET['page'] : 1;
$offset  = ($page - 1) * $limit;
$counter = $offset + 1;

// Totals
$totalStmt    = $conn->query("SELECT COUNT(*) AS total FROM categories");
$totalRecords = (int)$totalStmt->fetch(PDO::FETCH_OBJ)->total;
$totalPages   = max(1, (int)ceil($totalRecords / $limit));
if ($page > $totalPages) { // clamp overflow page param
  $page   = $totalPages;
  $offset = ($page - 1) * $limit;
  $counter = $offset + 1;
}

// Fetch page
$stmt = $conn->prepare("SELECT id, name FROM categories ORDER BY id DESC LIMIT :limit OFFSET :offset");
$stmt->bindValue(':limit',  $limit,  PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$categories = $stmt->fetchAll(PDO::FETCH_OBJ);

// Flash alerts (?created=1, ?updated=1, ?deleted=1, ?error=msg)
$flash = null;
if (isset($_GET['created']) && $_GET['created'] === '1') {
  $flash = ['class' => 'alert-success', 'msg' => 'Category created successfully.'];
} elseif (isset($_GET['updated']) && $_GET['updated'] === '1') {
  $flash = ['class' => 'alert-info', 'msg' => 'Category updated successfully.'];
} elseif (isset($_GET['deleted']) && $_GET['deleted'] === '1') {
  $flash = ['class' => 'alert-danger', 'msg' => 'Category deleted successfully.'];
} elseif (!empty($_GET['error'])) {
  $flash = ['class' => 'alert-warning', 'msg' => h($_GET['error'])];
}
?>

<div class="row">
  <div class="col">
    <div class="card">
      <div class="card-header d-flex align-items-center justify-content-between">
        <h2 class="card-title h6 mb-0">Categories</h2>
        <a href="<?= ADMINURL ?>/categories-admins/create-category.php" class="btn btn-primary">
          <i class="fas fa-plus mr-1"></i> Add New Category
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
                <th>Category</th>
                <th style="width:180px">Actions</th>
              </tr>
            </thead>
            <tbody>
              <?php if (!$categories): ?>
                <tr>
                  <td colspan="3" class="text-center text-muted py-4">No categories found.</td>
                </tr>
              <?php else: ?>
                <?php foreach ($categories as $category): ?>
                  <tr>
                    <td><?= (int)$counter++; ?></td>
                    <td><?= h($category->name); ?></td>
                    <td>
                      <a href="<?= ADMINURL ?>/categories-admins/update-category.php?id=<?= (int)$category->id ?>" class="btn btn-secondary btn-sm text-white">
                        Update
                      </a>
                      <a href="<?= ADMINURL ?>/categories-admins/delete-category.php?id=<?= (int)$category->id ?>"
                         class="btn btn-danger btn-sm"
                         onclick="return confirm('Delete this category? This action cannot be undone.');">
                        Delete
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
