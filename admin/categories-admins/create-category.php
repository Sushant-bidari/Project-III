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

// Page context (used by header.php)
$pageTitle  = "Create Category";
$breadcrumb = "System";

function h($v){ return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }

$errors = [];
$nameVal = "";

// Handle submit
if (isset($_POST['submit'])) {
  $name = trim($_POST['name'] ?? '');
  $nameVal = $name;

  if ($name === '') {
    $errors[] = "Please enter a category name.";
  } else {
    try {
      // Optional: duplicate check (case-insensitive)
      $dup = $conn->prepare("SELECT 1 FROM categories WHERE LOWER(name) = LOWER(:name) LIMIT 1");
      $dup->execute([':name' => $name]);
      if ($dup->fetch()) {
        $errors[] = "A category with this name already exists.";
      } else {
        $insert = $conn->prepare("INSERT INTO categories (name) VALUES (:name)");
        $insert->execute([':name' => $name]);
        header("Location: " . ADMINURL . "/categories-admins/show-categories.php?created=1");
        exit;
      }
    } catch (Exception $e) {
      $errors[] = "Unable to create category. Please try again.";
    }
  }
}

require "../layouts/header.php";
?>

<div class="row">
  <div class="col-lg-8">
    <div class="card">
      <div class="card-header d-flex align-items-center justify-content-between">
        <h2 class="card-title h6 mb-0">Create Category</h2>
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

        <form method="POST" action="create-category.php" novalidate>
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
            <i class="fas fa-check mr-1"></i> Add
          </button>
        </form>
      </div>
    </div>
  </div>
</div>

<?php require "../layouts/footer.php"; ?>
