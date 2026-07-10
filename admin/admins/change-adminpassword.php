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
/* ---------- Admin Auth Guard ---------- */
if (!isset($_SESSION['adminname'])) {
    header("location: " . ADMINURL . "/admins/login-admins.php");
    exit;
}

$pageTitle  = "Change Password";
$breadcrumb = "System";

/* ---------- Feedback ---------- */
$success = $error = "";
$forceLogout = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $current = $_POST['current_password'] ?? '';
    $new     = $_POST['new_password'] ?? '';
    $confirm = $_POST['confirm_password'] ?? '';

    if ($current === '' || $new === '' || $confirm === '') {
        $error = "All fields are required.";
    } elseif ($new !== $confirm) {
        $error = "New password and confirmation do not match.";
    } elseif (strlen($new) < 6) {
        $error = "Password must be at least 6 characters.";
    } else {

        /* Get current admin password */
        $stmt = $conn->prepare("
            SELECT id, mypassword 
            FROM admins 
            WHERE adminname = :adminname 
            LIMIT 1
        ");
        $stmt->execute([
            ':adminname' => $_SESSION['adminname']
        ]);
        $admin = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$admin || !password_verify($current, $admin['mypassword'])) {
            $error = "Current password is incorrect.";
        } else {

            $newHash = password_hash($new, PASSWORD_DEFAULT);

            $update = $conn->prepare("
                UPDATE admins 
                SET mypassword = :pass 
                WHERE id = :id
            ");
            $update->execute([
                ':pass' => $newHash,
                ':id'   => $admin['id']
            ]);

            $success = "Password updated successfully.";
            $forceLogout = true;
        }
    }
}

require "../layouts/header.php";
?>

<div class="row justify-content-center">
  <div class="col-lg-6 col-md-8">
    <div class="card">
      <div class="card-header">
        <h2 class="card-title h6 mb-0">Change Admin Password</h2>
      </div>

      <div class="card-body">

        <?php if ($error): ?>
          <div class="alert alert-danger">
            <i class="fas fa-exclamation-triangle mr-1"></i>
            <?= htmlspecialchars($error); ?>
          </div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle mr-1"></i>
                <?= htmlspecialchars($success); ?>
                <br>
                <small class="d-block mt-1">
                For security reasons, please sign in again to continue.
                </small>
            </div>
            <?php endif; ?>


        <form method="POST" autocomplete="off" <?= $forceLogout ? 'style="display:none"' : '' ?>>

          <div class="form-group">
            <label>Current Password</label>
            <input type="password" name="current_password" class="form-control" required>
          </div>

          <div class="form-group">
            <label>New Password</label>
            <input type="password" name="new_password" class="form-control" required>
          </div>

          <div class="form-group">
            <label>Confirm New Password</label>
            <input type="password" name="confirm_password" class="form-control" required>
          </div>

          <button class="btn btn-primary">
            <i class="fas fa-lock mr-1"></i> Update Password
          </button>

        </form>

      </div>
    </div>
  </div>
</div>

<?php if ($forceLogout): ?>
<script>
  setTimeout(function () {
    window.location.href = "<?= ADMINURL; ?>/admins/logout-admins.php";
  }, 1000);
</script>
<?php endif; ?>


<?php require "../layouts/footer.php"; ?>
