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
// -------- Auth guard --------
if (!isset($_SESSION['adminname'])) {
  header("location: " . ADMINURL . "/admins/login-admins.php");
  exit;
}

// -------- Helpers --------
function h($v){ return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }

// -------- Validate & fetch category --------
if (!isset($_GET['id']) || !ctype_digit($_GET['id'])) {
  header("Location: " . ADMINURL . "/categories-admins/show-categories.php?error=" . urlencode("Invalid category ID."));
  exit;
}
$id = (int)$_GET['id'];

try {
  $stmt = $conn->prepare("SELECT id, name FROM categories WHERE id = :id LIMIT 1");
  $stmt->execute([':id' => $id]);
  $category = $stmt->fetch(PDO::FETCH_OBJ);
  if (!$category) {
    header("Location: " . ADMINURL . "/categories-admins/show-categories.php?error=" . urlencode("Category not found."));
    exit;
  }
} catch (Exception $e) {
  header("Location: " . ADMINURL . "/categories-admins/show-categories.php?error=" . urlencode("Unable to load category."));
  exit;
}

// -------- Page context for header --------
$pageTitle  = "Update Category";
$breadcrumb = "System";

$errors = [];
$nameVal = $category->name; // sticky with current value by default

// -------- Handle submit --------
if (isset($_POST['submit'])) {
  $name = trim($_POST['name'] ?? '');
  $nameVal = $name;

  if ($name === '') {
    $errors[] = "Please enter a category name.";
  } else {
    try {
      // Optional: prevent duplicates (case-insensitive), excluding current ID
      $dup = $conn->prepare("SELECT 1 FROM categories WHERE LOWER(name) = LOWER(:name) AND id <> :id LIMIT 1");
      $dup->execute([':name' => $name, ':id' => $id]);

      if ($dup->fetch()) {
        $errors[] = "Another category with this name already exists.";
      } else {
        $update = $conn->prepare("UPDATE categories SET name = :name WHERE id = :id");
        $update->execute([':name' => $name, ':id' => $id]);

        header("Location: " . ADMINURL . "/categories-admins/show-categories.php?updated=1");
        exit;
      }
    } catch (Exception $e) {
      $errors[] = "Unable to update category. Please try again.";
    }
  }
}

require "../layouts/header.php";
?>

<div class="row">
  <div class="col-lg-8">
    <div class="card">
      <div class="card-header d-flex align-items-center justify-content-between">
        <h2 class="card-title h6 mb-0">Update Category</h2>
        <a href="<?= ADMINURL ?>/categories-admins/show-categories.php" class="btn btn-outline-secondary">
          <i class="fas fa-arrow-left mr-1"></i> Back to Categories
        </a>
      </div>

      <div class="card-body">
        <?php if ($errors): ?>
          <div class="alert alert-danger" role="alert">
            <ul class="mb-0">
              <?php foreach ($errors as $err): ?>
                <li><?= h($err) ?></li>
              <?php endforeach; ?>
            </ul>
          </div>
        <?php endif; ?>

        <form method="POST" action="update-category.php?id=<?= (int)$id ?>" novalidate>
          <div class="form-group">
            <label for="catName" class="mb-1">Category Name</label>
            <input
              type="text"
              name="name"
              id="catName"
              class="form-control"
              placeholder="e.g., Engineering"
              value="<?= h($nameVal) ?>"
              required
            >
          </div>

          <button type="submit" name="submit" class="btn btn-primary">
            <i class="fas fa-save mr-1"></i> Update
          </button>
        </form>
      </div>
    </div>
  </div>
</div>

<?php require "../layouts/footer.php"; ?>
