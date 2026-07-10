<?php 
require "../config/config.php";
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
$activeTab     = 'login';   // which tab should be active on render
$empOld        = [];        // previously submitted employer values
$empErrors     = [];        // field => message
$empShowStep2  = false;     // which employer step to show on render (false=step1, true=step2)

if (isset($_SESSION['username'])) {
  header("location: " . APPURL . "");
  exit;
}

/* ===========================
   EMPLOYER REGISTER (2-step)
=========================== */
if (isset($_POST['employersubmit'])) {
  $activeTab = 'emp';
  $empOld    = $_POST; // keep submitted values to re-fill fields

  // ---- Step 1 (account)
  $fullname   = trim($_POST['fullname'] ?? '');
  $username   = trim($_POST['username'] ?? '');
  $email      = trim($_POST['email'] ?? '');
  $contact    = trim($_POST['contact'] ?? '');
  $password   = $_POST['password'] ?? '';
  $repassword = $_POST['re-password'] ?? '';
  $img        = 'emp_imgplaceholder.jpg';
  $type       = 'Employer';

  // ---- Step 2 (company)
  $company_website  = trim($_POST['company_website'] ?? '');
  $industry         = trim($_POST['industry'] ?? '');
  $address_line     = trim($_POST['address_line'] ?? '');
  $postal_code      = trim($_POST['postal_code'] ?? '');
  $established_year = trim($_POST['established_year'] ?? '');
  $operating_hours  = trim($_POST['operating_hours'] ?? '');

  // new fields
  $business_reg_no  = strtoupper(trim($_POST['business_reg_no'] ?? ''));
  $company_size     = trim($_POST['company_size'] ?? '');
  $org_type         = trim($_POST['org_type'] ?? '');

  $ALLOWED_SIZES = ['1–10','11–50','51–200','201–500','501–1000','1001–5000','5000+'];
  $ALLOWED_ORGS  = [
    'Startup','Small Business','Medium Business','Enterprise',
    'Recruitment Agency','Government','Non-Profit Organization',
    'Educational Institution','Freelancer / Independent Consultant'
  ];

  // required fields - per-field messages
  $labels = [
    'fullname' => 'Organization Name',
    'username' => 'Username',
    'email'    => 'Official Email',
    'contact'  => 'Official Contact',
    'password' => 'Password',
    're-password' => 'Re-type Password',
    'company_website'  => 'Company Website',
    'industry'         => 'Industry',
    'address_line'     => 'Address Line',
    'postal_code'      => 'Postal Code',
    'established_year' => 'Established Year',
    'operating_hours'  => 'Operating Hours',
    'business_reg_no'  => 'Business Registration Number',
  ];
  foreach ($labels as $name => $label) {
    if (empty($_POST[$name])) $empErrors[$name] = "$label is required.";
  }

  // specific validations
  if (!isset($empErrors['re-password']) && $password !== $repassword) {
    $empErrors['re-password'] = "Passwords do not match.";
  }
  if (!isset($empErrors['email']) && strlen($email) > 120) {
    $empErrors['email'] = "Email is too long (max 120).";
  }
  if (!isset($empErrors['username']) && strlen($username) > 30) {
    $empErrors['username'] = "Username is too long (max 30).";
  }
  if (!isset($empErrors['business_reg_no']) && !preg_match('/^[A-Za-z0-9\/-]{8,20}$/', $business_reg_no)) {
    $empErrors['business_reg_no'] = 'Use 8–20 chars (letters, numbers, "-" or "/").';
  }
  if ($company_size !== '' && !in_array($company_size, $ALLOWED_SIZES, true)) {
    $empErrors['company_size'] = 'Invalid company size.';
  }
  if ($org_type !== '' && !in_array($org_type, $ALLOWED_ORGS, true)) {
    $empErrors['org_type'] = 'Invalid organization type.';
  }

  // email/username uniqueness (field-specific)
  if (!isset($empErrors['email'])) {
    $stmt = $conn->prepare("SELECT 1 FROM users WHERE email = :email LIMIT 1");
    $stmt->execute([':email'=>$email]);
    if ($stmt->fetchColumn()) $empErrors['email'] = 'This email is already taken.';
  }
  if (!isset($empErrors['username'])) {
    $stmt = $conn->prepare("SELECT 1 FROM users WHERE username = :u LIMIT 1");
    $stmt->execute([':u'=>$username]);
    if ($stmt->fetchColumn()) $empErrors['username'] = 'This username is already taken.';
  }

  // BRN uniqueness
  if (!isset($empErrors['business_reg_no'])) {
    $dupe = $conn->prepare("SELECT 1 FROM company_details WHERE business_reg_no = :brn LIMIT 1");
    $dupe->execute([':brn' => $business_reg_no]);
    if ($dupe->fetchColumn()) $empErrors['business_reg_no'] = 'This business registration number already exists.';
  }

  // decide which step to show (if any error on step1  show step1, else step2)
  $step1Keys = ['fullname','username','email','contact','password','re-password'];
  $empShowStep2 = true;
  foreach ($step1Keys as $k) { if (isset($empErrors[$k])) { $empShowStep2 = false; break; } }

  if (empty($empErrors)) {
    try {
      $conn->beginTransaction();

      $insertUser = $conn->prepare("
        INSERT INTO users (fullname, username, email, contact, mypassword, img, type)
        VALUES (:fullname, :username, :email, :contact, :mypassword, :img, :type)
      ");
      $insertUser->execute([
        ':fullname'   => $fullname,
        ':username'   => $username,
        ':email'      => $email,
        ':contact'    => $contact,
        ':mypassword' => password_hash($password, PASSWORD_DEFAULT),
        ':img'        => $img,
        ':type'       => $type
      ]);
      $userId = (int)$conn->lastInsertId();

      $insertCompany = $conn->prepare("
        INSERT INTO company_details
          (user_id, company_website, industry, address_line, postal_code, established_year, operating_hours,
           business_reg_no, company_size, org_type)
        VALUES
          (:user_id, :company_website, :industry, :address_line, :postal_code, :established_year, :operating_hours,
           :business_reg_no, :company_size, :org_type)
      ");
      $insertCompany->execute([
        ':user_id'          => $userId,
        ':company_website'  => $company_website,
        ':industry'         => $industry,
        ':address_line'     => $address_line,
        ':postal_code'      => $postal_code,
        ':established_year' => $established_year,
        ':operating_hours'  => $operating_hours,
        ':business_reg_no'  => $business_reg_no,
        ':company_size'     => ($company_size !== '' ? $company_size : null),
        ':org_type'         => ($org_type !== '' ? $org_type : null),
      ]);

      $conn->commit();
      $_SESSION['successMsg'] = "Your employer account has been created.";
      header("location: loginRegister.php");
      exit;

    } catch (Exception $e) {
      $conn->rollBack();
      // show a generic inline error at top of employer pane
      $empErrors['_form'] = "Unable to create account. Please try again.";
      $empShowStep2 = true;
    }
  }
}

// fetch regions for dropdown selectionn
$regionRows = [];
try {
  $rs = $conn->query("SELECT id, name FROM job_regions WHERE status = 1 ORDER BY name");
  $regionRows = $rs->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) {
  $regionRows = [];
}

/* ===========================
   JOB SEEKER REGISTER
=========================== */
if (isset($_POST['submit'])) {
  if (empty($_POST['username']) || empty($_POST['email']) || empty($_POST['password']) || empty($_POST['re-password'])) {
    echo "<script>alert('some inputs are empty')</script>";
  } else {
    $fullname   = $_POST['fullname'];
    $username   = $_POST['username'];
    $email      = $_POST['email'];
    $contact    = $_POST['contact'];
    $regionId = isset($_POST['region_id']) ? (int)$_POST['region_id'] : 0;
    $address  = trim($_POST['address'] ?? '');
    $password   = $_POST['password'];
    $repassword = $_POST['re-password'];
    $img        = 'user_placeholderimg.jpg';
    $type       = $_POST['type'];

    if ($password == $repassword) {
      if (strlen($email) > 22 || strlen($username) > 15) {
        echo "<script>alert('email or username is too big')</script>";
      } else {
        $validate = $conn->prepare("SELECT 1 FROM users WHERE email = :email OR username = :username LIMIT 1");
        $validate->execute([':email'=>$email, ':username'=>$username]);

        // validating that region exists and is_active
        $regOK = false;
        if ($regionId > 0) {
          $chkReg = $conn->prepare("SELECT 1 FROM job_regions WHERE id = :id AND status = 1");
          $chkReg->execute([':id' => $regionId]);
          $regOK = (bool)$chkReg->fetchColumn();
        }
        if (!$regOK) {
          echo "<script>alert('Please select a valid region/state');</script>";
          exit;
        }

        if ($validate->rowCount() > 0) {
          echo "<script>alert('Email or username is aleardy taken')</script>";
        } else {
          $insert = $conn->prepare("
            INSERT INTO users (fullname, username, email, contact, region_id, address, mypassword, img, type)
            VALUES (:fullname, :username, :email, :contact, :region_id, :address, :mypassword, :img, :type)
          ");
          $insert->execute([
            ':fullname'   => $fullname,
            ':username'   => $username,
            ':email'      => $email,
            ':contact'    => $contact,
            ':region_id'  => $regionId,
            ':address'    => $address,
            ':mypassword' => password_hash($password, PASSWORD_DEFAULT),
            ':img'        => $img,
            ':type'       => $type,
          ]);

          if ($insert->rowCount() > 0) {
            $userId = (int)$conn->lastInsertId();
            $defaultAvailability = 'None';
            $stmt = $conn->prepare("
              INSERT INTO availability (user_id, monday, tuesday, wednesday, thursday, friday, saturday, sunday) 
              VALUES (:user_id, :a, :a, :a, :a, :a, :a, :a)
            ");
            $stmt->execute([':user_id' => $userId, ':a' => $defaultAvailability]);
          }
          $_SESSION['successMsg'] = "Your account has been created.";
          header("location: loginRegister.php");
          exit;
        }
      }
    } else {
      echo "<script>alert('Passwords does not match')</script>";
    }
  }
}

/* ===========================
   LOGIN
=========================== */
$error = "";
if (isset($_POST['login'])) {
  if (empty($_POST['email']) || empty($_POST['password'])) {
    echo "<script>alert('Some inputs are empty!!')</script>";
  } else {
    $email = $_POST['email'];
    $password = $_POST['password'];

    $login = $conn->prepare("SELECT * FROM users WHERE email = :email LIMIT 1");
    $login->execute([':email'=>$email]);
    $select = $login->fetch(PDO::FETCH_ASSOC);

    if ($select && password_verify($password, $select['mypassword'])) {
      $_SESSION['username'] = $select['username'];
      $_SESSION['fullname'] = $select['fullname'];
      $_SESSION['id']       = $select['id'];
      $_SESSION['type']     = $select['type'];
      $_SESSION['email']    = $select['email'];
      $_SESSION['contact']  = $select['contact'];
      $_SESSION['image']    = $select['img'];
      $_SESSION['cv']       = $select['cv'];
      header("location: " . APPURL . "");
      exit;
    } else {
      $error = "Invalid login credentials. Please try again.";
    }
  }
}

require "../includes/header.php";
?>

<style>
/* ===== Hero (consistent with your new pages) ===== */
.companies-hero{
  position:relative; background-size:cover; background-position:center;
  padding: 60px 0; overflow:hidden;
}
.companies-hero .overlay-dark{ position:absolute; inset:0;
  background:
    radial-gradient(1200px 400px at 10% -10%, rgba(99,102,241,.22), transparent 60%),
    radial-gradient(1200px 400px at 90% 0%, rgba(6,182,212,.18), transparent 60%),
    linear-gradient(180deg, rgba(2,6,23,.65), rgba(2,6,23,.80));
}
.companies-hero .hero-inner{ position:relative; z-index:2; }
.ch-eyebrow{ color:#c7d2fe; text-transform:uppercase; letter-spacing:.16em; font-weight:700; font-size:.75rem; }
.ch-title{ color:#fff; font-weight:800; }
.ch-sub{ color:#e2e8f0; }

/* ===== Auth card ===== */
.site-section{ padding-top:4rem; padding-bottom:2.5rem; }
.auth-shell{
  max-width: 800px; margin: 0 auto;
}
.card.auth-card{
  border:0; border-radius: 16px;
  overflow:hidden; background:#fff;
  box-shadow: 0 16px 44px rgba(0,0,0,.08);
  margin-top: -36px; /* overlap hero */
}

/* Tabs */
.auth-tabs{ display:flex; gap:0; border-bottom:1px solid #e9ecef; background:#f1f5f9; }
.auth-tabs .nav-item{ flex:1 1 0; }
.auth-tabs .nav-link{
  border-radius:0; min-height:64px;
  display:flex; align-items:center; justify-content:center; gap:.5rem;
  color:#6c757d; font-weight:700;
}
.auth-tabs .nav-link .lbl{ line-height:1.1; display:flex; flex-direction:column; }
.auth-tabs .nav-link .lbl small{ color:#8a97a6; font-weight:600; }
.auth-tabs .nav-link.active{
  background:#fff; color:#111827;
  box-shadow:0 -3px 0 0 #6366f1 inset;
}

/* Card body */
.auth-body{ padding: 1.25rem 1.25rem 1.75rem; }
@media (min-width:768px){ .auth-body{ padding: 1.75rem 2rem 2.25rem; } }

/* Inputs */
.form label{ font-weight:600; color:#495057; }
.form .form-control{ border-radius:.5rem; min-height:44px; }
.input-group-text{ background:#f8fafc; }

/* Helper */
.text-muted-sm{ color:#6b7280; font-size:.9rem; }

/* Buttons */
.btn-auth{ font-weight:700; border-radius:.6rem; }
.btn-block{ width:100%; }

/* Employer wizard */
.emp-wizard{ position:relative; overflow:hidden; }
.emp-track{ display:flex; width:200%; transform:translateX(0); transition:transform .28s ease; }
.emp-wizard.show-step-2 .emp-track{ transform:translateX(-50%); }
.emp-step{ width:50%; }
.emp-steps{ display:flex; align-items:center; justify-content:center; gap:.75rem; margin:.25rem 0 1rem; }
.emp-steps .badge{ padding:.45rem .65rem; border-radius:999px; }

/* Small responsiveness */
@media (max-width: 767.98px){
  .auth-tabs{ flex-wrap:wrap; }
  .auth-tabs .nav-item{ flex:0 0 50%; max-width:50%; }
}
@media (max-width: 480px){
  .auth-tabs .nav-item{ flex:0 0 100%; max-width:100%; }
  .auth-tabs .nav-link{ min-height:56px; }
}
</style>

<!-- HERO -->
<section class="companies-hero overlay inner-page bg-image" style="background-image:url('../images/tst.jpg');" id="home-section">
  <div class="overlay-dark"></div>
  <div class="container hero-inner text-center">
    <div class="ch-eyebrow mb-1">Account</div>
    <h2 class="ch-title mb-2">Welcome back</h2>
    <p class="ch-sub mb-0">Sign in or create an account — for Job Seekers & Employers</p>
  </div>
</section>

<section class="site-section">
  <div class="container auth-shell">
    <div class="card auth-card">
      <!-- Tabs -->
      <ul class="nav nav-pills nav-justified auth-tabs" id="authTabs" role="tablist">
        <li class="nav-item">
          <a class="nav-link <?php echo ($activeTab==='login' ? 'active' : ''); ?>" id="tab-login" data-toggle="pill" href="#pills-login" role="tab" aria-controls="pills-login" aria-selected="<?php echo ($activeTab==='login'?'true':'false'); ?>">
            <i class="fa fa-sign-in mr-2"></i><span class="lbl"><span>Login</span></span>
          </a>
        </li>
        <li class="nav-item">
          <a class="nav-link <?php echo ($activeTab==='js' ? 'active' : ''); ?>" id="tab-js" data-toggle="pill" href="#pills-js" role="tab" aria-controls="pills-js" aria-selected="<?php echo ($activeTab==='js'?'true':'false'); ?>">
            <i class="fa fa-user-plus mr-2"></i>
            <span class="lbl"><span>Register</span><small>(Job Seeker)</small></span>
          </a>
        </li>
        <li class="nav-item">
          <a class="nav-link <?php echo ($activeTab==='emp' ? 'active' : ''); ?>" id="tab-emp" data-toggle="pill" href="#pills-emp" role="tab" aria-controls="pills-emp" aria-selected="<?php echo ($activeTab==='emp'?'true':'false'); ?>">
            <i class="fa fa-building mr-2"></i>
            <span class="lbl"><span>Register</span><small>(Employer)</small></span>
          </a>
        </li>
      </ul>

      <div class="auth-body">
        <div class="tab-content" id="pills-tabContent">

          <!-- LOGIN -->
          <div class="tab-pane fade <?php echo ($activeTab==='login' ? 'show active' : ''); ?>" id="pills-login" role="tabpanel" aria-labelledby="tab-login">
            <?php if (!empty($_SESSION['successMsg'])): ?>
              <div class="alert alert-success alert-dismissible fade show auth-alert" role="alert">
                <i class="fa fa-check-circle mr-1"></i>
                <?= htmlspecialchars($_SESSION['successMsg']); ?>
                <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                  <span aria-hidden="true">&times;</span>
                </button>
              </div>
              <?php unset($_SESSION['successMsg']); ?>
            <?php endif; ?>

            <div class="text-center mb-3">
    <div class="text-muted small">
        Login with your registered Email & Password
    </div>

    <?php if (!empty($error)): ?>
        <div class="alert alert-danger alert-dismissible fade show mt-2" role="alert">
            <?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?>
            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
        </div>
    <?php endif; ?>
</div>


            <form action="loginRegister.php" method="POST" novalidate class="form">
              <div class="form-group">
                <label for="loginEmail">Email address</label>
                <div class="input-group">
                  <div class="input-group-prepend"><span class="input-group-text"><i class="fa fa-envelope"></i></span></div>
                  <input type="email" id="loginEmail" class="form-control" placeholder="you@example.com" name="email" required>
                </div>
              </div>

              <div class="form-group mb-2">
                <label for="loginPassword">Password</label>
                <div class="input-group">
                  <div class="input-group-prepend"><span class="input-group-text"><i class="fa fa-lock"></i></span></div>
                  <input type="password" id="loginPassword" class="form-control" placeholder="Password" name="password" required>
                  <div class="input-group-append">
                    <button class="btn btn-outline-secondary" type="button" data-toggle="password" data-target="#loginPassword">
                      <i class="far fa-eye"></i>
                    </button>
                  </div>
                </div>
              </div>

              <button class="btn btn-primary btn-auth btn-block mt-3" type="submit" name="login">Log in</button>
            </form>
          </div>

          <!-- REGISTER: JOB SEEKER -->
          <div class="tab-pane fade <?php echo ($activeTab==='js'    ? 'show active' : ''); ?>" id="pills-js" role="tabpanel" aria-labelledby="tab-js">
            <div class="text-center mb-3"><div class="text-muted-sm">Create your free Account</div></div>

            <form action="loginRegister.php" method="POST" novalidate class="form">
              <div class="form-group">
                <label>Full name</label>
                <input type="text" class="form-control" placeholder="Fullname" name="fullname" required>
              </div>
              <div class="form-row">
                <div class="form-group col-md-6">
                  <label>Username</label>
                  <input type="text" class="form-control" placeholder="@username" name="username" required>
                </div>
                <div class="form-group col-md-6">
                  <label>Email</label>
                  <input type="email" class="form-control" placeholder="Email address" name="email" required>
                </div>
              </div>
              <div class="form-group">
                <label>Contact</label>
                <input type="number" class="form-control" placeholder="Contact number" name="contact" required>
              </div>

              <div class="form-row">
                <div class="form-group col-md-6">
                  <label>Region / State</label>
                  <select class="form-control" name="region_id" required>
                    <option value="">Select region/state</option>
                    <?php foreach ($regionRows as $rg): ?>
                      <option value="<?= (int)$rg['id'] ?>"><?= htmlspecialchars($rg['name']) ?></option>
                    <?php endforeach; ?>
                  </select>
                </div>
                <div class="form-group col-md-6">
                  <label>Address</label>
                  <input type="text" class="form-control" placeholder="Street address" name="address" required>
                </div>
              </div>


              <input type="hidden" value="Job Seeker" name="type">

              <div class="form-row">
                <div class="form-group col-md-6">
                  <label>Password</label>
                  <input type="password" class="form-control" placeholder="Password" name="password" required>
                </div>
                <div class="form-group col-md-6">
                  <label>Re-type Password</label>
                  <input type="password" class="form-control" placeholder="Re-type Password" name="re-password" required>
                </div>
              </div>

              <button class="btn btn-primary btn-auth btn-block" type="submit" name="submit">Register</button>
            </form>
          </div>

          <!-- REGISTER: EMPLOYER (wizard) -->
          <div class="tab-pane fade <?php echo ($activeTab==='emp'   ? 'show active' : ''); ?>" id="pills-emp" role="tabpanel" aria-labelledby="tab-emp">
            <?php if(!empty($empErrors['_form'])): ?>
            <div class="alert alert-danger"><?php echo htmlspecialchars($empErrors['_form']); ?></div>
          <?php endif; ?>
            <div class="text-center mb-1"><div class="text-muted-sm">Create your free Employer Account</div></div>
            <div class="emp-steps">
              <span class="badge <?php echo ($activeTab==='emp' && $empShowStep2)?'badge-light':'badge-primary'; ?>" id="empStep1Dot">1</span> <span class="text-muted-sm">Account</span>
              <span class="mx-1">→</span>
              <span class="badge <?php echo ($activeTab==='emp' && $empShowStep2)?'badge-primary':'badge-light'; ?>" id="empStep2Dot">2</span> <span class="text-muted-sm">Company</span>
            </div>
            

            <form id="empForm" action="loginRegister.php" method="POST" novalidate class="form">
              <input type="hidden" name="type" value="Employer">

              <div class="emp-wizard mt-2 <?php echo ($activeTab==='emp' && $empShowStep2 ? 'show-step-2' : ''); ?>">
                <div class="emp-track">

                  <!-- STEP 1 -->
                  <div class="emp-step pr-md-3">
                    <!-- Organization Name -->
                    <div class="form-group">
                      <label>Organization Name</label>
                      <input type="text" class="form-control" name="fullname" placeholder="Name of Organization"
                            value="<?php echo htmlspecialchars($empOld['fullname'] ?? ''); ?>">
                      <?php if(!empty($empErrors['fullname'])): ?><small class="text-danger"><?php echo $empErrors['fullname']; ?></small><?php endif; ?>
                    </div>

                    <div class="form-row">
                      <div class="form-group col-md-6">
                        <label>Username</label>
                        <input type="text" class="form-control" name="username" placeholder="@username" maxlength="30"
                              value="<?php echo htmlspecialchars($empOld['username'] ?? ''); ?>">
                        <?php if(!empty($empErrors['username'])): ?><small class="text-danger"><?php echo $empErrors['username']; ?></small><?php endif; ?>
                      </div>
                      <div class="form-group col-md-6">
                        <label>Official Email</label>
                        <input type="email" class="form-control" name="email" placeholder="Official email" maxlength="120"
                              value="<?php echo htmlspecialchars($empOld['email'] ?? ''); ?>">
                        <?php if(!empty($empErrors['email'])): ?><small class="text-danger"><?php echo $empErrors['email']; ?></small><?php endif; ?>
                      </div>
                    </div>

                    <div class="form-row">
                      <div class="form-group col-md-6">
                        <label>Official Contact</label>
                        <input type="text" class="form-control" name="contact" placeholder="Official contact"
                              value="<?php echo htmlspecialchars($empOld['contact'] ?? ''); ?>">
                        <?php if(!empty($empErrors['contact'])): ?><small class="text-danger"><?php echo $empErrors['contact']; ?></small><?php endif; ?>
                      </div>
                      <div class="form-group col-md-6">
                        <label>Password</label>
                        <input type="password" class="form-control" name="password" placeholder="Password" minlength="6">
                        <?php if(!empty($empErrors['password'])): ?><small class="text-danger"><?php echo $empErrors['password']; ?></small><?php endif; ?>
                      </div>
                    </div>

                    <div class="form-group col-md-6 pl-0">
                      <label>Re-type Password</label>
                      <input type="password" class="form-control" name="re-password" placeholder="Re-type password" minlength="6">
                      <?php if(!empty($empErrors['re-password'])): ?><small class="text-danger"><?php echo $empErrors['re-password']; ?></small><?php endif; ?>
                    </div>

                    <div class="d-flex justify-content-end">
                      <button type="button" id="empNext" class="btn btn-primary btn-auth">Next</button>
                    </div>
                  </div>

                  <!-- STEP 2 -->
                  <div class="emp-step pl-md-3">
                    <div class="form-row">
                      <div class="form-group col-md-6">
                        <label>Company Website</label>
                        <input type="text" class="form-control" name="company_website" placeholder="https://codeastro.com"
                              value="<?php echo htmlspecialchars($empOld['company_website'] ?? ''); ?>">
                        <?php if(!empty($empErrors['company_website'])): ?><small class="text-danger"><?php echo $empErrors['company_website']; ?></small><?php endif; ?>
                      </div>
                      <div class="form-group col-md-6">
                        <label>Industry</label>
                        <input type="text" class="form-control" name="industry" placeholder="e.g., Software"
                              value="<?php echo htmlspecialchars($empOld['industry'] ?? ''); ?>">
                        <?php if(!empty($empErrors['industry'])): ?><small class="text-danger"><?php echo $empErrors['industry']; ?></small><?php endif; ?>
                      </div>
                    </div>

                    <div class="form-row">
                      <div class="form-group col-md-8">
                        <label>Address Line</label>
                        <input type="text" class="form-control" name="address_line" placeholder="Street, City, State"
                              value="<?php echo htmlspecialchars($empOld['address_line'] ?? ''); ?>">
                        <?php if(!empty($empErrors['address_line'])): ?><small class="text-danger"><?php echo $empErrors['address_line']; ?></small><?php endif; ?>
                      </div>
                      <div class="form-group col-md-4">
                        <label>Postal Code</label>
                        <input type="text" class="form-control" name="postal_code"
                              value="<?php echo htmlspecialchars($empOld['postal_code'] ?? ''); ?>">
                        <?php if(!empty($empErrors['postal_code'])): ?><small class="text-danger"><?php echo $empErrors['postal_code']; ?></small><?php endif; ?>
                      </div>
                    </div>

                    <!-- Business Registration Number -->
                    <div class="form-group">
                      <label>Business Registration Number <span class="text-danger">*</span></label>
                      <input type="text" class="form-control" name="business_reg_no"
                            placeholder="BR-2023-XYZ123" pattern="[A-Za-z0-9\/-]{8,20}" maxlength="20"
                            value="<?php echo htmlspecialchars($empOld['business_reg_no'] ?? ''); ?>" required>
                      <small class="form-text text-muted">Letters, numbers, dashes or slashes only. 8–20 characters.</small>
                      <?php if(!empty($empErrors['business_reg_no'])): ?><small class="text-danger d-block"><?php echo $empErrors['business_reg_no']; ?></small><?php endif; ?>
                    </div>

                    <div class="form-row">
                      <!-- Company Size -->
                      <div class="form-group col-md-6">
                        <label>Company Size / Number of Employees</label>
                        <select class="form-control" name="company_size">
                          <?php $cs = $empOld['company_size'] ?? ''; ?>
                          <option value="">Select…</option>
                          <option <?php echo ($cs==='1–10'      ? 'selected' : ''); ?>>1–10</option>
                          <option <?php echo ($cs==='11–50'     ? 'selected' : ''); ?>>11–50</option>
                          <option <?php echo ($cs==='51–200'    ? 'selected' : ''); ?>>51–200</option>
                          <option <?php echo ($cs==='201–500'   ? 'selected' : ''); ?>>201–500</option>
                          <option <?php echo ($cs==='501–1000'  ? 'selected' : ''); ?>>501–1000</option>
                          <option <?php echo ($cs==='1001–5000' ? 'selected' : ''); ?>>1001–5000</option>
                          <option <?php echo ($cs==='5000+'     ? 'selected' : ''); ?>>5000+</option>
                        </select>
                        <?php if(!empty($empErrors['company_size'])): ?><small class="text-danger"><?php echo $empErrors['company_size']; ?></small><?php endif; ?>
                      </div>

                      <!-- Type of Organization -->
                      <div class="form-group col-md-6">
                        <label>Type of Organization</label>
                        <?php $ot = $empOld['org_type'] ?? ''; ?>
                        <select class="form-control" name="org_type" id="orgType">
                          <option value="">Select…</option>
                          <?php
                            $orgs = ['Startup','Small Business','Medium Business','Enterprise','Recruitment Agency','Government','Non-Profit Organization','Educational Institution','Freelancer / Independent Consultant'];
                            foreach($orgs as $o){
                              $sel = ($ot===$o)?'selected':'';
                              echo "<option $sel>".htmlspecialchars($o)."</option>";
                            }
                          ?>
                        </select>
                        <small id="orgHelp" class="form-text text-muted" style="<?php echo ($ot==='Freelancer / Independent Consultant'?'display:block':'display:none'); ?>;">
                          Freelancers/Consultants must enter a valid tax or business registration number provided by their local authority.
                        </small>
                        <?php if(!empty($empErrors['org_type'])): ?><small class="text-danger d-block"><?php echo $empErrors['org_type']; ?></small><?php endif; ?>
                      </div>
                    </div>

                    <div class="form-row">
                      <div class="form-group col-md-4">
                        <label>Established Year</label>
                        <input type="number" class="form-control" name="established_year" min="1900" max="2099" step="1" placeholder="YYYY"
                              value="<?php echo htmlspecialchars($empOld['established_year'] ?? ''); ?>">
                        <?php if(!empty($empErrors['established_year'])): ?><small class="text-danger"><?php echo $empErrors['established_year']; ?></small><?php endif; ?>
                      </div>
                      <div class="form-group col-md-8">
                        <label>Operating Hours</label>
                        <input type="text" class="form-control" name="operating_hours" placeholder="Mon–Fri 9:00–17:00"
                              value="<?php echo htmlspecialchars($empOld['operating_hours'] ?? ''); ?>">
                        <?php if(!empty($empErrors['operating_hours'])): ?><small class="text-danger"><?php echo $empErrors['operating_hours']; ?></small><?php endif; ?>
                      </div>
                    </div>

                    <div class="d-flex justify-content-between">
                      <button type="button" id="empBack" class="btn btn-outline-secondary">Back</button>
                      <button type="submit" name="employersubmit" class="btn btn-success btn-auth">Create Account</button>
                    </div>
                  </div>

                </div>
              </div>
            </form>
          </div>

        </div><!-- /tab-content -->
      </div><!-- /auth-body -->
    </div><!-- /card -->
  </div>
</section>

<script>
(function(){
  // Toggle password visibility
  document.querySelectorAll('[data-toggle="password"]').forEach(function(btn){
    var targetSel = btn.getAttribute('data-target');
    var target = document.querySelector(targetSel);
    if(!target) return;
    btn.addEventListener('click', function(){
      var isPwd = target.type === 'password';
      target.type = isPwd ? 'text' : 'password';
      var icon = btn.querySelector('i');
      if(icon){
        icon.classList.toggle('fa-eye', !isPwd);
        icon.classList.toggle('fa-eye-slash', isPwd);
      }
    });
  });

  // Employer wizard
  var wizard = document.querySelector('.emp-wizard');
  if(!wizard) return;

  var nextBtn = document.getElementById('empNext');
  var backBtn = document.getElementById('empBack');
  var form    = document.getElementById('empForm');
  var dot1    = document.getElementById('empStep1Dot');
  var dot2    = document.getElementById('empStep2Dot');

  function setStep(step){
    if(step === 2){
      wizard.classList.add('show-step-2');
      dot1.className = 'badge badge-light';
      dot2.className = 'badge badge-primary';
    }else{
      wizard.classList.remove('show-step-2');
      dot1.className = 'badge badge-primary';
      dot2.className = 'badge badge-light';
    }
  }

  function validStep1() {
    var req = ['fullname','username','email','contact','password','re-password'];
    for (var i=0;i<req.length;i++){
      var el = form.querySelector('[name="'+req[i]+'"]');
      if(!el || !el.value.trim()){ el && el.focus(); return false; }
    }
    var p1 = form.querySelector('[name="password"]').value.trim();
    var p2 = form.querySelector('[name="re-password"]').value.trim();
    if (p1 !== p2){
      alert('Passwords do not match');
      form.querySelector('[name="re-password"]').focus();
      return false;
    }
    return true;
  }

  if(nextBtn){ nextBtn.addEventListener('click', function(){ if(validStep1()) setStep(2); }); }
  if(backBtn){ backBtn.addEventListener('click', function(){ setStep(1); }); }
})();
</script>
<script>
(function(){
  var orgSel  = document.getElementById('orgType');
  var orgHelp = document.getElementById('orgHelp');
  if (orgSel && orgHelp) {
    function toggleOrgHelp(){
      orgHelp.style.display = (orgSel.value === 'Freelancer / Independent Consultant') ? 'block' : 'none';
    }
    orgSel.addEventListener('change', toggleOrgHelp);
    toggleOrgHelp();
  }
})();
</script>

<?php require "../includes/footer.php"; ?>