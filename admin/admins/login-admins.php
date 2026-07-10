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
  // Hide the breadcrumb/page-header on this page
  $suppressPageHead = true;
?>
<?php require "../layouts/header.php"; ?> 
<?php 

    if(isset($_SESSION['adminname'])) {

      header("location: ".ADMINURL."");

    }

    $error = null;


    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($email === '' || $password === '') {
        $error = "Email and password are required.";
    } else {
        $stmt = $conn->prepare("SELECT adminname, email, mypassword FROM admins WHERE email = :email LIMIT 1");
        $stmt->execute([':email' => $email]);
        $select = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($select && password_verify($password, $select['mypassword'])) {
            $_SESSION['adminname'] = $select['adminname'];
            $_SESSION['email']     = $select['email'];
            header("Location: " . ADMINURL . "");
            exit;
        } else {
            $error = "Invalid email or password.";
        }
    }
}

?>

<style>
/* If your Bootstrap build doesn’t have min-vh-100 */
.min-vh-100{ min-height:100vh; }

.admin-auth-card{
  border:0; border-radius:16px;
  box-shadow:0 12px 36px rgba(0,0,0,.08);
}
.admin-auth-logo{ width:48px; height:48px; object-fit:contain; }
.admin-auth-title{ font-weight:800; letter-spacing:.2px; }
.admin-auth-sub{ color:#6b7280; }

/* Inputs with icons */
.input-group-lg .input-group-text{ background:#f9fafb; border-color:#e5e7eb; border-right:0; }
.input-group-lg .form-control{ border-left:0; border-color:#e5e7eb; min-height:46px; }
.input-group .form-control:focus{ box-shadow:none; border-color:#80bdff; }
#togglePwd{ border-color:#e5e7eb; }

/* Button */
.btn-auth{ border-radius:12px; font-weight:700; padding:.75rem 1rem; }

/* Tiny helpers */
.text-hint{ color:#94a3b8; }
.admin-auth-footer{ color:#94a3b8; font-size:.9rem; }
</style>

<style>
/* Centering helper + max width */
.auth-max {
  max-width: 420px;      /* <= controls the overall size */
}

/* Card look */
.admin-auth-card{
  border:0; border-radius:16px;
  box-shadow:0 12px 36px rgba(0,0,0,.08);
}
.admin-auth-logo{ width:40px; height:40px; object-fit:contain; }
.admin-auth-title{ font-weight:800; font-size:1.35rem; }
.admin-auth-sub{ color:#6b7280; }

/* Inputs with icons (normal size now, not -lg) */
.input-group .input-group-text{ background:#f9fafb; border-color:#e5e7eb; border-right:0; }
.input-group .form-control{ border-left:0; border-color:#e5e7eb; }
.input-group .form-control:focus{ box-shadow:none; border-color:#80bdff; }
#togglePwd{ border-color:#e5e7eb; }

/* Button */
.btn-auth{ border-radius:12px; font-weight:700; padding:.7rem 1rem; }

/* Small text */
.text-hint,.admin-auth-footer{ color:#94a3b8; }

/* Full-height section */
.min-vh-100{ min-height:100vh; }
</style>


      <div class="admin-auth-wrap">
  <div class="container d-flex align-items-center py-5">
  <div class="row w-100 justify-content-center">
    <!-- cap width + centered -->
    <div class="col-12 auth-max">

      <div class="card admin-auth-card">
        <div class="card-body p-4 p-md-5">

          <div class="text-center mb-3">
            <a href="<?php echo $base_url; ?>" class="d-inline-block mb-2">
              <img class="admin-auth-logo" src="<?php echo ADMINURL; ?>/images/logo.png" alt="Logo">
            </a>
            <h4 class="admin-auth-title mb-1">Admin Login</h4>
            <div class="admin-auth-sub small">Sign in to manage the dashboard</div>
          </div>

          <?php if ($error): ?>
          <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <strong>Error:</strong> <?php echo htmlspecialchars($error); ?>
            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
              <span aria-hidden="true">&times;</span>
            </button>
          </div>
        <?php endif; ?>


          <form method="POST" action="login-admins.php" novalidate>
            <!-- Email (normal size) -->
            <div class="form-group">
              <label for="email" class="sr-only">Email</label>
              <div class="input-group">
                <div class="input-group-prepend">
                  <span class="input-group-text"><i class="fas fa-envelope"></i></span>
                </div>
                <input type="email" name="email" id="email" class="form-control"
                       placeholder="Email address" autocomplete="username" required>
              </div>
            </div>

            <!-- Password (normal size) -->
            <div class="form-group mb-2">
              <label for="password" class="sr-only">Password</label>
              <div class="input-group">
                <div class="input-group-prepend">
                  <span class="input-group-text"><i class="fas fa-lock"></i></span>
                </div>
                <input type="password" name="password" id="password" class="form-control"
                       placeholder="Password" autocomplete="current-password" required minlength="6">
                <div class="input-group-append">
                  <button type="button" class="btn btn-outline-secondary" id="togglePwd" aria-label="Show/Hide password">
                    <i class="far fa-eye"></i>
                  </button>
                </div>
              </div>
              <small id="capsHint" class="text-warning d-none">
                <i class="fas fa-exclamation-triangle"></i> Caps Lock is on
              </small>
            </div>

            <div class="d-flex justify-content-between align-items-center mb-3">
              <div class="form-check mb-0">
                <input class="form-check-input" type="checkbox" id="remember" disabled>
                <label class="form-check-label text-hint" for="remember">Remember me</label>
              </div>
              <a href="#" class="small text-hint">Forgot password?</a>
            </div>

            <button type="submit" name="submit" class="btn btn-primary btn-block btn-auth" id="submitBtn">
              Sign in
            </button>
          </form>

          
        </div>
      </div>

    </div>
  </div>
</div>


</div>

<script>
(function(){
  var pwd = document.getElementById('password');
  var tgl = document.getElementById('togglePwd');
  var caps = document.getElementById('capsHint');
  var btn = document.getElementById('submitBtn');
  var form = document.querySelector('form[action="login-admins.php"]');

  if (tgl && pwd){
    tgl.addEventListener('click', function(){
      var icon = this.querySelector('i');
      var show = pwd.type === 'password';
      pwd.type = show ? 'text' : 'password';
      icon.classList.toggle('fa-eye', !show);
      icon.classList.toggle('fa-eye-slash', show);
    });
    pwd.addEventListener('keyup', function(e){
      if (!caps) return;
      var on = e.getModifierState && e.getModifierState('CapsLock');
      caps.classList.toggle('d-none', !on);
    });
  }
  if (form && btn){
    form.addEventListener('submit', function(){
      btn.disabled = true;
      btn.innerHTML = '<span class="spinner-border spinner-border-sm mr-2"></span> Signing in...';
    });
  }
})();
</script>



<?php require "../layouts/footer.php"; ?>           
