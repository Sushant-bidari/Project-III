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
  if (!isset($_SESSION['adminname'])) {
    header("location: " . ADMINURL . "/admins/login-admins.php");
    exit;
  }

  $pageTitle = "Admins";
  $breadcrumb = "System";

  require "../layouts/header.php";

  $stmt = $conn->query("SELECT id, adminname, email FROM admins ORDER BY id ASC");
  $stmt->execute();
  $admins = $stmt->fetchAll(PDO::FETCH_OBJ);

  function h($v){ return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }

  // ---- Flash alerts via query flags ----
  $flash = null; // ['class' => 'alert-success', 'msg' => '...']

  if (isset($_GET['created']) && $_GET['created'] === '1') {
    $flash = ['class' => 'alert-success', 'msg' => 'Admin created successfully.'];
  } elseif (isset($_GET['updated']) && $_GET['updated'] === '1') {
    $flash = ['class' => 'alert-info', 'msg' => 'Admin updated successfully.'];
  } elseif (isset($_GET['deleted']) && $_GET['deleted'] === '1') {
    $flash = ['class' => 'alert-danger', 'msg' => 'Admin deleted successfully.'];
  } elseif (!empty($_GET['error'])) {
    // Optional: pass a brief error reason as ?error=...
    $flash = ['class' => 'alert-warning', 'msg' => h($_GET['error'])];
  }
?>
<div class="row">
  <div class="col">
    <div class="card">
      <div class="card-header d-flex align-items-center justify-content-between">
        <h2 class="card-title h6 mb-0">Admins</h2>
        <a href="<?php echo ADMINURL; ?>/admins/create-admins.php" class="btn btn-primary">
          <i class="fas fa-plus mr-1"></i> Create Admins
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
          <table class="table mb-0 table-hover">
            <thead>
              <tr>
                <th style="width:80px">#</th>
                <th>Admin Name</th>
                <th>Email</th>
              </tr>
            </thead>
            <tbody>
              <?php if (!$admins): ?>
                <tr>
                  <td colspan="3" class="text-center text-muted py-4">No admins found.</td>
                </tr>
              <?php else: ?>
                <?php foreach ($admins as $admin): ?>
                  <tr>
                    <td><?= (int)$admin->id; ?></td>
                    <td><?= h($admin->adminname); ?></td>
                    <td><?= h($admin->email); ?></td>
                  </tr>
                <?php endforeach; ?>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</div>

<?php require "../layouts/footer.php"; ?>
