<?php require "../config/config.php";
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
/* ---------- Auth guard ---------- */
if (
    !isset($_SESSION['id']) ||
    !isset($_SESSION['type']) ||
    !in_array($_SESSION['type'], ['Job Seeker', 'Employer'], true)
) {
    header("location: " . APPURL);
    exit;
}


$success = $error = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $current = $_POST['current_password'] ?? '';
    $new     = $_POST['new_password'] ?? '';
    $confirm = $_POST['confirm_password'] ?? '';

    if (empty($current) || empty($new) || empty($confirm)) {
        $error = "All fields are required.";
    } elseif ($new !== $confirm) {
        $error = "New password and confirmation do not match.";
    } elseif (strlen($new) < 6) {
        $error = "Password must be at least 6 characters long.";
    } else {

        // Fetch current password hash
        $stmt = $conn->prepare("SELECT mypassword FROM users WHERE id = :id LIMIT 1");
        $stmt->execute([':id' => $_SESSION['id']]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$user || !password_verify($current, $user['mypassword'])) {
            $error = "Current password is incorrect.";
        } else {

            $newHash = password_hash($new, PASSWORD_DEFAULT);

            $update = $conn->prepare("
                UPDATE users 
                SET mypassword = :pass 
                WHERE id = :id
            ");

            $update->execute([
                ':pass' => $newHash,
                ':id'   => $_SESSION['id']
            ]);

            $success = "Password updated successfully.";
        }
    }
}

require "../includes/header.php";
?>

<style>
.cp-card{
  max-width:520px;
  margin:0 auto;
  background:#fff;
  border-radius:16px;
  box-shadow:0 2px 16px rgba(0,0,0,.08);
  padding:28px;
}
</style>

<style>
.site-section{ padding-top:2rem; }

/* ===== Hero ===== */
.companies-hero{
  position:relative; background-size:cover; background-position:center;
  padding: 60px 0; overflow:hidden;
}
.companies-hero .overlay-dark{ position:absolute; inset:0; background:
    radial-gradient(1200px 400px at 10% -10%, rgba(99,102,241,.22), transparent 60%),
    radial-gradient(1200px 400px at 90% 0%, rgba(6,182,212,.18), transparent 60%),
    linear-gradient(180deg, rgba(2,6,23,.65), rgba(2,6,23,.80));}
.companies-hero .hero-inner{ position:relative; z-index:2; }
.ch-eyebrow{ color:#c7d2fe; text-transform:uppercase; letter-spacing:.16em; font-weight:700; font-size:.75rem; }
.ch-title{ color:#fff; font-weight:800; }
.ch-sub{ color:#e2e8f0; }

/* ===== Saved list ===== */
.sv-list{ list-style:none; padding:0; margin:0; }
.sv-card{
  position:relative; border-radius:16px; background:#fff;
  box-shadow:0 2px 12px rgba(0,0,0,.06); overflow:hidden;
  transition:transform .18s ease, box-shadow .18s ease; margin-bottom:16px;
}
.sv-card:hover{ transform:translateY(-2px); box-shadow:0 10px 26px rgba(0,0,0,.10); }
.sv-link{
  display:grid; grid-template-columns:72px 1fr auto; gap:16px;
  text-decoration:none !important; color:inherit; padding:16px 18px 16px 0;
}
.sv-card .accent{ position:absolute; left:0; top:0; bottom:0; width:0; background:linear-gradient(90deg,#06b6d4,#6366f1); transition:width .28s ease; }
.sv-card:hover .accent{ width:6px; }

/* logo / initial */
.sv-media{ display:flex; align-items:center; justify-content:center; width:72px; }
.sv-logo{ width:66px; height:66px; object-fit:cover; border-radius:12px; border:1px solid rgba(0,0,0,.06); background:#f7f8fa; }
.sv-initial{
  width:66px; height:66px; border-radius:12px; display:flex; align-items:center; justify-content:center;
  color:#fff; font-weight:800; font-size:24px; line-height:1; user-select:none;
  border:1px solid rgba(0,0,0,.06); box-shadow:0 2px 8px rgba(0,0,0,.06);
}

/* body */
.sv-body{ display:flex; flex-direction:column; justify-content:center; min-width:0; }
.sv-title{ font-size:1.125rem; line-height:1.25; font-weight:800; margin:0;
  display:-webkit-box; -webkit-line-clamp:1; -webkit-box-orient:vertical; overflow:hidden;
}
.sv-meta{ color:#6c757d; display:flex; align-items:center; gap:8px; font-size:.95rem; }
.sv-company{ font-weight:600; }
.sv-sub{ color:#98a2b3; font-size:.9rem; }

.sv-aside{ display:flex; flex-direction:column; align-items:flex-end; justify-content:center; gap:8px; min-width:190px; }

.badge-tight{ white-space:nowrap; }

.sv-chip{
  border-radius:999px; padding:6px 10px; font-size:.875rem; font-weight:700; line-height:1;
}
.chip-ok{ background:#ecfdf5; color:#065f46; }
.chip-warn{ background:#fff7ed; color:#9a3412; }
.chip-danger{ background:#fef2f2; color:#991b1b; }

.sv-track{ width:160px; height:6px; background:#eef2f7; border-radius:999px; overflow:hidden; }
.sv-fill{ height:100%; background:linear-gradient(90deg,#22c55e,#f59e0b,#ef4444); }

.sv-actions{ display:flex; gap:8px; }
.btn-ghost{ border:1px solid rgba(0,0,0,.18); background:#fff; color:#374151; }
.btn-ghost:hover{ background:#f8fafc; }

/* empty state */
.empty{
  background:#fff; border-radius:16px; box-shadow:0 2px 16px rgba(0,0,0,.06);
  padding:28px; text-align:center;
}

/* responsive */
@media (max-width: 768px){
  .sv-link{ grid-template-columns:56px 1fr; padding-right:14px; }
  .sv-aside{ grid-column:1 / -1; align-items:flex-start; margin-top:8px; }
  .sv-track{ width:100%; }
}
</style>

<!-- HERO -->
<section class="companies-hero overlay inner-page bg-image"
  style="background-image:url('../images/tst.jpg');">
  <div class="overlay-dark"></div>
  <div class="container hero-inner text-center">
    <div class="ch-eyebrow mb-1">Account</div>
    <h2 class="ch-title mb-2">Change Password</h2>
    <p class="ch-sub mb-0">Keep your account secure.</p>
  </div>
</section>

<section class="site-section">
  <div class="container">

    <div class="cp-card">

      <?php if ($error): ?>
        <div class="alert alert-danger"><?php echo $error; ?></div>
      <?php endif; ?>

      <?php if ($success): ?>
        <div class="alert alert-success"><?php echo $success; ?></div>
      <?php endif; ?>

      <form method="POST">
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

        <button class="btn btn-primary btn-block">
          Update Password
        </button>
      </form>

    </div>

  </div>
</section>

<?php require "../includes/footer.php"; ?>
