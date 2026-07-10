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
// Auth guard
if (!isset($_SESSION['adminname'])) {
  header("location: " . ADMINURL . "/admins/login-admins.php");
  exit;
}

// Page context for header (title/breadcrumb)
$pageTitle  = "Create Admin";
$breadcrumb = "System";

require "../layouts/header.php";

// Helpers
function h($v){ return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }

// Defaults for sticky form
$errors   = [];
$success  = false;
$adminnameVal = '';
$emailVal     = '';

if (isset($_POST['submit'])) {
  $adminname = trim($_POST['adminname'] ?? '');
  $email     = trim($_POST['email'] ?? '');
  $password  = $_POST['password'] ?? '';

  // Sticky
  $adminnameVal = $adminname;
  $emailVal     = $email;

  // Basic validation
  if ($adminname === '' || $email === '' || $password === '') {
    $errors[] = "Please fill in all fields.";
  } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $errors[] = "Please enter a valid email address.";
  } else {
    try {
      // Optional: check for duplicate email/adminname
      $dup = $conn->prepare("SELECT 1 FROM admins WHERE email = :email OR adminname = :adminname LIMIT 1");
      $dup->execute([':email' => $email, ':adminname' => $adminname]);

      if ($dup->fetch()) {
        $errors[] = "An admin with that email or username already exists.";
      } else {
        $insert = $conn->prepare("
          INSERT INTO admins (adminname, email, mypassword)
          VALUES (:adminname, :email, :mypassword)
        ");
        $insert->execute([
          ':adminname'  => $adminname,
          ':email'      => $email,
          ':mypassword' => password_hash($password, PASSWORD_DEFAULT),
        ]);

        // Success → redirect to list
        $success = true;
        header("Location: " . ADMINURL . "/admins/admins.php?created=1");
        exit;
      }
    } catch (Exception $e) {
      // Log if you have a logger; show generic error
      $errors[] = "Something went wrong while creating the admin. Please try again.";
    }
  }
}
?>

<div class="row">
  <div class="col-lg-8">
    <div class="card">
      <div class="card-header d-flex align-items-center justify-content-between">
        <h2 class="card-title h6 mb-0">Create Admin</h2>
        <a href="<?= ADMINURL ?>/admins/admins.php" class="btn btn-outline-secondary">
          <i class="fas fa-arrow-left mr-1"></i> Back to Admins
        </a>
      </div>

      <div class="card-body">
        <?php if ($errors): ?>
          <div class="alert alert-danger">
            <ul class="mb-0">
              <?php foreach ($errors as $err): ?>
                <li><?= h($err) ?></li>
              <?php endforeach; ?>
            </ul>
          </div>
        <?php endif; ?>

        <form method="POST" action="create-admins.php" novalidate>
          <div class="form-group">
            <label for="adminEmail" class="mb-1">Email</label>
            <input
              type="email"
              name="email"
              id="adminEmail"
              class="form-control"
              placeholder="name@example.com"
              value="<?= h($emailVal) ?>"
              required
            >
          </div>

          <div class="form-group">
            <label for="adminName" class="mb-1">Username</label>
            <input
              type="text"
              name="adminname"
              id="adminName"
              class="form-control"
              placeholder="username"
              value="<?= h($adminnameVal) ?>"
              required
            >
          </div>

          <div class="form-group">
            <label for="adminPassword" class="mb-1">Password</label>
            <input
              type="password"
              name="password"
              id="adminPassword"
              class="form-control"
              placeholder="Password"
              minlength="6"
              required
            >
            <small class="form-text text-muted">Minimum 6 characters is recommended.</small>
          </div>

          <button type="submit" name="submit" class="btn btn-primary">
            <i class="fas fa-check mr-1"></i> Create
          </button>
        </form>
      </div>
    </div>
  </div>
</div>

<?php require "../layouts/footer.php"; ?>
