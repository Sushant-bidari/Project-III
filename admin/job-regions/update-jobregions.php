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
// ---- Auth guard ----
if (!isset($_SESSION['adminname'])) {
  header("location: " . ADMINURL . "/admins/login-admins.php");
  exit;
}

// ---- Helpers ----
function h($v){ return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }

// ---- Validate ID ----
if (!isset($_GET['id']) || !ctype_digit($_GET['id'])) {
  header("Location: " . ADMINURL . "/job-regions/show-jobregions.php?error=" . urlencode("Invalid region ID."));
  exit;
}
$id = (int)$_GET['id'];

// ---- Load region ----
try {
  $stmt = $conn->prepare("SELECT id, name, code, status FROM job_regions WHERE id = :id LIMIT 1");
  $stmt->execute([':id' => $id]);
  $region = $stmt->fetch(PDO::FETCH_OBJ);
  if (!$region) {
    header("Location: " . ADMINURL . "/job-regions/show-jobregions.php?error=" . urlencode("Job region not found."));
    exit;
  }
} catch (Exception $e) {
  header("Location: " . ADMINURL . "/job-regions/show-jobregions.php?error=" . urlencode("Unable to load job region."));
  exit;
}

// ---- Page context for header ----
$pageTitle  = "Update Job Region";
$breadcrumb = "System";

// ---- Form state ----
$errors    = [];
$nameVal   = $region->name;
$codeVal   = $region->code;
$statusVal = (string)(int)$region->status;

// ---- Handle submit ----
if (isset($_POST['submit'])) {
  $nameVal   = trim($_POST['name'] ?? '');
  $codeVal   = strtoupper(trim($_POST['code'] ?? ''));
  $statusVal = (isset($_POST['status']) && $_POST['status'] === '0') ? '0' : '1';

  if ($nameVal === '' || $codeVal === '') {
    $errors[] = "Name and code are required.";
  } else {
    if (!preg_match('/^[A-Z0-9_-]+$/', $codeVal)) {
      $errors[] = "Code may contain only letters, numbers, underscores, and dashes.";
    }
  }

  if (!$errors) {
    try {
      // Duplicate checks (exclude current id)
      $dup = $conn->prepare("
        SELECT
          SUM(CASE WHEN LOWER(name) = LOWER(:n) AND id <> :id THEN 1 ELSE 0 END) AS name_dup,
          SUM(CASE WHEN UPPER(code) = UPPER(:c) AND id <> :id THEN 1 ELSE 0 END) AS code_dup
        FROM job_regions
      ");
      $dup->execute([':n' => $nameVal, ':c' => $codeVal, ':id' => $id]);
      $row = $dup->fetch(PDO::FETCH_ASSOC);

      if (!empty($row['name_dup'])) $errors[] = "Another region with this name already exists.";
      if (!empty($row['code_dup'])) $errors[] = "This region code is already in use.";

      if (!$errors) {
        $upd = $conn->prepare("UPDATE job_regions SET name = :name, code = :code, status = :status WHERE id = :id");
        $upd->execute([
          ':name'   => $nameVal,
          ':code'   => $codeVal,
          ':status' => (int)$statusVal,
          ':id'     => $id
        ]);

        header("Location: " . ADMINURL . "/job-regions/show-jobregions.php?updated=1");
        exit;
      }
    } catch (Exception $e) {
      $errors[] = "Unable to update job region. Please try again.";
    }
  }
}

require "../layouts/header.php";
?>

<div class="row">
  <div class="col-lg-8">
    <div class="card">
      <div class="card-header d-flex align-items-center justify-content-between">
        <h2 class="card-title h6 mb-0">Update Job Region</h2>
        <a href="<?= ADMINURL ?>/job-regions/show-jobregions.php" class="btn btn-outline-secondary">
          <i class="fas fa-arrow-left mr-1"></i> Back to Job Regions
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

        <form method="POST" action="update-jobregions.php?id=<?= (int)$id ?>" novalidate>
          <!-- Region Name -->
          <div class="form-group">
            <label for="regName" class="mb-1">Region Name</label>
            <input
              type="text"
              name="name"
              id="regName"
              class="form-control"
              placeholder="e.g., New South Wales"
              value="<?= h($nameVal) ?>"
              required
            >
          </div>

          <!-- Region Code -->
          <div class="form-group">
            <label for="regCode" class="mb-1">Region Code</label>
            <input
              type="text"
              name="code"
              id="regCode"
              class="form-control"
              placeholder="e.g., NSW"
              value="<?= h($codeVal) ?>"
              required
            >
            <small class="form-text text-muted">Saved in uppercase.</small>
          </div>

          <!-- Status -->
          <div class="form-group">
            <label for="regStatus" class="mb-1">Status</label>
            <select name="status" id="regStatus" class="form-control">
              <option value="1" <?= $statusVal === '1' ? 'selected' : '' ?>>Active</option>
              <option value="0" <?= $statusVal === '0' ? 'selected' : '' ?>>Inactive</option>
            </select>
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
