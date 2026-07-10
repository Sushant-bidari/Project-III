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
*//*
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
if (!isset($_SESSION['username'])) {
    header("location: " . APPURL);
    exit();
}

/* ============================================================
   RESUMES AJAX ENDPOINTS
   ============================================================ */
if (!empty($_POST['ajax']) && isset($_POST['action'])) {
  header('Content-Type: application/json');

  $meId   = (int)($_SESSION['id'] ?? 0);
  $meType = $_SESSION['type'] ?? '';
  if (!$meId || $meType !== 'Job Seeker') {
    echo json_encode(['ok' => false, 'error' => 'Not authorized']); exit;
  }

  // Helpers
  function safe_ext($name, $allowed = ['pdf','doc','docx']) {
    $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
    return in_array($ext, $allowed, true) ? $ext : null;
  }
  function cv_dir_fs() { return __DIR__ . '/user-cvs/'; }
  function cv_dir_url() { return 'user-cvs/'; }

  $action = $_POST['action'];

  // List
  if ($action === 'list_resumes') {
    $q = $conn->prepare("
      SELECT id, filename, original_name, is_primary, created_at
      FROM resumes WHERE user_id = :u
      ORDER BY is_primary DESC, id DESC
    ");
    $q->execute([':u' => $meId]);
    $rows = $q->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(['ok' => true, 'items' => $rows]); exit;
  }

  // Upload new
  if ($action === 'upload_resume') {
    try {
      if (!isset($_FILES['resume_new']) || $_FILES['resume_new']['error'] !== UPLOAD_ERR_OK) {
        echo json_encode(['ok'=>false, 'error'=>'No file uploaded']); exit;
      }
      $ext = safe_ext($_FILES['resume_new']['name']);
      if (!$ext) { echo json_encode(['ok'=>false,'error'=>'Invalid file type']); exit; }

      $dir = cv_dir_fs();
      if (!is_dir($dir)) @mkdir($dir, 0775, true);

      $newName = 'cv_'.$meId.'_'.time().'_'.bin2hex(random_bytes(2)).'.'.$ext;
      if (!move_uploaded_file($_FILES['resume_new']['tmp_name'], $dir.$newName)) {
        echo json_encode(['ok'=>false,'error'=>'Upload failed']); exit;
      }

      $makePrimary = !empty($_POST['make_primary']) ? 1 : 0;

      if ($makePrimary) {
        $conn->prepare("UPDATE resumes SET is_primary = 0 WHERE user_id = :u")->execute([':u'=>$meId]);
      }

      // if user has zero resumes, force this as primary
      $hasAny = $conn->prepare("SELECT 1 FROM resumes WHERE user_id = :u LIMIT 1");
      $hasAny->execute([':u'=>$meId]);
      if (!$hasAny->fetchColumn()) $makePrimary = 1;

      $ins = $conn->prepare("
        INSERT INTO resumes (user_id, filename, original_name, is_primary)
        VALUES (:u, :fn, :on, :p)
      ");
      $ins->execute([
        ':u'  => $meId,
        ':fn' => $newName,
        ':on' => $_FILES['resume_new']['name'],
        ':p'  => $makePrimary
      ]);

      echo json_encode(['ok'=>true]); exit;
    } catch (Throwable $e) {
      echo json_encode(['ok'=>false, 'error'=>'Server error']); exit;
    }
  }

  // Set primary
  if ($action === 'set_primary') {
    $rid = (int)($_POST['id'] ?? 0);
    // verify ownership
    $own = $conn->prepare("SELECT 1 FROM resumes WHERE id = :id AND user_id = :u");
    $own->execute([':id'=>$rid, ':u'=>$meId]);
    if (!$own->fetchColumn()) { echo json_encode(['ok'=>false,'error'=>'Not found']); exit; }

    $conn->prepare("UPDATE resumes SET is_primary = 0 WHERE user_id = :u")->execute([':u'=>$meId]);
    $conn->prepare("UPDATE resumes SET is_primary = 1 WHERE id = :id AND user_id = :u")->execute([':id'=>$rid, ':u'=>$meId]);
    echo json_encode(['ok'=>true]); exit;
  }

  // Delete
  if ($action === 'delete_resume') {
    $rid = (int)($_POST['id'] ?? 0);

    $q = $conn->prepare("SELECT filename, is_primary FROM resumes WHERE id = :id AND user_id = :u");
    $q->execute([':id'=>$rid, ':u'=>$meId]);
    $rowR = $q->fetch(PDO::FETCH_ASSOC);
    if (!$rowR) { echo json_encode(['ok'=>false,'error'=>'Not found']); exit; }

    // delete file
    $path = cv_dir_fs() . $rowR['filename'];
    if (is_file($path)) @unlink($path);

    // delete row
    $conn->prepare("DELETE FROM resumes WHERE id = :id AND user_id = :u")->execute([':id'=>$rid, ':u'=>$meId]);

    // if primary removed => promote newest to primary
    if ((int)$rowR['is_primary'] === 1) {
      $next = $conn->prepare("SELECT id FROM resumes WHERE user_id = :u ORDER BY id DESC LIMIT 1");
      $next->execute([':u'=>$meId]);
      $nid = (int)($next->fetchColumn() ?: 0);
      if ($nid) $conn->prepare("UPDATE resumes SET is_primary = 1 WHERE id = :id")->execute([':id'=>$nid]);
    }

    echo json_encode(['ok'=>true]); exit;
  }

  // Replace file for an existing resume
  if ($action === 'replace_resume') {
    $rid = (int)($_POST['id'] ?? 0);

    if (!isset($_FILES['resume_file']) || $_FILES['resume_file']['error'] !== UPLOAD_ERR_OK) {
      echo json_encode(['ok'=>false,'error'=>'No file']); exit;
    }
    $ext = safe_ext($_FILES['resume_file']['name']);
    if (!$ext) { echo json_encode(['ok'=>false,'error'=>'Invalid file type']); exit; }

    // ownership + old file
    $q = $conn->prepare("SELECT filename FROM resumes WHERE id = :id AND user_id = :u");
    $q->execute([':id'=>$rid, ':u'=>$meId]);
    $old = $q->fetch(PDO::FETCH_ASSOC);
    if (!$old) { echo json_encode(['ok'=>false,'error'=>'Not found']); exit; }

    $dir = cv_dir_fs();
    if (!is_dir($dir)) @mkdir($dir, 0775, true);

    $newName = 'cv_'.$meId.'_'.time().'_'.bin2hex(random_bytes(2)).'.'.$ext;
    if (!move_uploaded_file($_FILES['resume_file']['tmp_name'], $dir.$newName)) {
      echo json_encode(['ok'=>false,'error'=>'Upload failed']); exit;
    }

    // remove old file
    $oldPath = $dir.$old['filename'];
    if (is_file($oldPath)) @unlink($oldPath);

    $upd = $conn->prepare("
      UPDATE resumes
      SET filename = :fn, original_name = :on
      WHERE id = :id AND user_id = :u
    ");
    $upd->execute([
      ':fn'=>$newName,
      ':on'=>$_FILES['resume_file']['name'],
      ':id'=>$rid,
      ':u' =>$meId
    ]);

    echo json_encode(['ok'=>true]); exit;
  }

  echo json_encode(['ok'=>false,'error'=>'Unknown action']); exit;
}

/* ============================================================
   NORMAL PAGE FLOW
   ============================================================ */
if (isset($_GET['upd_id'])) {
    $id = $_GET['upd_id'];

    if ($_SESSION['id'] !== $id) {
        header("location: " . APPURL);
        exit();
    }

    $select = $conn->prepare("SELECT * FROM users WHERE id=:id");
    $select->bindParam(':id', $id);
    $select->execute();
    $row = $select->fetch(PDO::FETCH_OBJ);

    /* fetch regions for Job Seekers only */
    $regionRows = [];
    if ($row && $row->type === 'Job Seeker') {
      try {
        $rs = $conn->query("SELECT id, name FROM job_regions WHERE status = 1 ORDER BY name");
        $regionRows = $rs->fetchAll(PDO::FETCH_ASSOC);
      } catch (Throwable $e) {
        $regionRows = [];
      }
    }


    $companyDetails = null;
    if ($row->type === 'Employer') {
        $detailsStmt = $conn->prepare("SELECT * FROM company_details WHERE user_id = :user_id LIMIT 1");
        $detailsStmt->bindParam(':user_id', $id);
        $detailsStmt->execute();
        $companyDetails = $detailsStmt->fetch(PDO::FETCH_OBJ);
    }

    if (isset($_POST['submit'])) {
        $fullname = $_POST['fullname'];
        $username = $_POST['username'];
        $email    = $_POST['email'];
        $title    = $_POST['title'];
        $contact  = $_POST['contact'];
        $bio      = $_POST['bio'];
        $facebook = $_POST['facebook'];
        $twitter  = $_POST['twitter'];
        $linkedin = $_POST['linkedin'];

        $isJobSeeker = $row->type == "Job Seeker";

        // Job seeker extras
        $skills    = $isJobSeeker ? ($_POST['skills'] ?? null)    : null;
        $education = $isJobSeeker ? ($_POST['education'] ?? null) : null;

        // region/address for Job Seekers
        $regionId = null;
        $address  = null;
        if ($isJobSeeker) {
          $regionId = isset($_POST['region_id']) ? (int)$_POST['region_id'] : null;
          $address  = trim($_POST['address'] ?? '');

          // validate region exists & active (allow null if none selected)
          if (!empty($regionId)) {
            $rgChk = $conn->prepare("SELECT 1 FROM job_regions WHERE id = :id AND status = 1");
            $rgChk->execute([':id' => $regionId]);
            if (!$rgChk->fetchColumn()) {
              $_SESSION['message'] = ['type' => 'danger', 'text' => 'Please select a valid region/state.'];
              header("Location: update-profile.php?upd_id=" . $id);
              exit();
            }
          } else {
            $regionId = null;
          }
        }


        // Profile image upload (unchanged)
        $dir_img = 'user-images/';
        if (!empty($_FILES['img']['name'])) {
            if (!empty($row->img) && file_exists("user-images/" . $row->img)) {
                @unlink("user-images/" . $row->img);
            }
            $img = $_FILES['img']['name'];
            $dir_img .= basename($img);
            if (!move_uploaded_file($_FILES['img']['tmp_name'], $dir_img)) {
                $_SESSION['message'] = ['type' => 'danger', 'text' => 'Image upload failed.'];
                header("Location: update-profile.php?upd_id=" . $id);
                exit();
            }
        } else {
            $img = $row->img;
        }

        try {
            // NOTE: cv removed from update completely
            $update = $conn->prepare("UPDATE users SET 
              fullname = :fullname, username = :username, email = :email, title = :title, 
              contact = :contact, bio = :bio, facebook = :facebook, twitter = :twitter, 
              linkedin = :linkedin, img = :img, skills = :skills, education = :education,
              region_id = :region_id, address = :address
              WHERE id = :id");


            $update->bindParam(':fullname', $fullname);
            $update->bindParam(':username', $username);
            $update->bindParam(':email',    $email);
            $update->bindParam(':title',    $title);
            $update->bindParam(':contact',  $contact);
            $update->bindParam(':bio',      $bio);
            $update->bindParam(':facebook', $facebook);
            $update->bindParam(':twitter',  $twitter);
            $update->bindParam(':linkedin', $linkedin);
            $update->bindParam(':img',      $img);
            $update->bindParam(':skills',   $skills);
            $update->bindParam(':education',$education);
            $update->bindValue(':region_id', $isJobSeeker ? $regionId : null, PDO::PARAM_INT);
            $update->bindValue(':address',   $isJobSeeker ? $address  : null, PDO::PARAM_STR);
            $update->bindParam(':id',       $id);
            $update->execute();

            if (!$isJobSeeker) {
              $company_website  = $_POST['company_website']  ?? null;
              $industry         = $_POST['industry']         ?? null;
              $address_line     = $_POST['address_line']     ?? null;
              $postal_code      = $_POST['postal_code']      ?? null;
              $established_year = $_POST['established_year'] ?? null;
              $operating_hours  = $_POST['operating_hours']  ?? null;

              $check = $conn->prepare("SELECT id FROM company_details WHERE user_id = :user_id");
              $check->bindParam(':user_id', $id);
              $check->execute();

              if ($check->rowCount() > 0) {
                  $updateDetails = $conn->prepare("UPDATE company_details SET 
                      company_website = :company_website, 
                      industry = :industry,
                      address_line = :address_line,
                      postal_code = :postal_code,
                      established_year = :established_year,
                      operating_hours = :operating_hours,
                      updated_at = CURRENT_TIMESTAMP
                      WHERE user_id = :user_id");
              } else {
                  $updateDetails = $conn->prepare("INSERT INTO company_details 
                      (company_website, industry, address_line, postal_code, established_year, operating_hours, user_id)
                      VALUES 
                      (:company_website, :industry, :address_line, :postal_code, :established_year, :operating_hours, :user_id)");
              }

              $updateDetails->bindParam(':company_website',  $company_website);
              $updateDetails->bindParam(':industry',         $industry);
              $updateDetails->bindParam(':address_line',     $address_line);
              $updateDetails->bindParam(':postal_code',      $postal_code);
              $updateDetails->bindParam(':established_year', $established_year);
              $updateDetails->bindParam(':operating_hours',  $operating_hours);
              $updateDetails->bindParam(':user_id',          $id);
              $updateDetails->execute();
            }

            $_SESSION['message'] = ['type' => 'success', 'text' => 'Your profile has been updated successfully!'];
        } catch (PDOException $e) {
            $_SESSION['message'] = ['type' => 'danger', 'text' => 'Failed to update profile: ' . $e->getMessage()];
        }

        header("Location: update-profile.php?upd_id=" . $id);
        exit();
    }
} else {
    echo "404";
    exit();
}

require "../includes/header.php";
?>

<style>
  .section-hero.inner-page, .section-hero.inner-page > .container > .row { height: 175px; }
  .site-section{ padding-top: 3rem; }
  .profile-wrap .card{ border:0; border-radius:14px; box-shadow:0 6px 24px rgba(0,0,0,.06); }
  .profile-wrap .card-header{ background:#fff; border-bottom:1px solid rgba(0,0,0,.06); border-top-left-radius:14px; border-top-right-radius:14px; padding:14px 16px; }
  .profile-wrap .card-header h6{ margin:0; font-weight:800; }
  .profile-wrap .help{ font-size:.875rem; color:#6b7280; }
  .avatar-box{ display:flex; align-items:center; gap:14px; padding:12px; border:1px dashed rgba(0,0,0,.12); border-radius:10px; background:#fafafa; }
  .avatar{ width:80px; height:80px; border-radius:10px; object-fit:cover; background:#f1f5f9; border:1px solid rgba(0,0,0,.06); }
  .resume-row .badge-primary{ background:#2563eb; }
  .resume-row .badge-success{ background:#16a34a; }
  .resume-row .badge-warning{ background:#f59e0b; color:#111827; }
  .resume-table td, .resume-table th { vertical-align: middle; }
  .resume-file { max-width: 360px; }
  .btn-icon {
    background: transparent; border: 0; padding: .25rem; line-height: 1;
  }
  .btn-icon i { font-size: 1rem; }
  .tags-input{
  display:flex; flex-wrap:wrap; gap:6px; padding:6px; border:1px solid rgba(0,0,0,.12);
  border-radius:.375rem; background:#fff; min-height:42px;
  }
  .tags-input-field{
    border:0; outline:0; min-width:180px; flex:1 0 160px; padding:4px 6px;
  }
  .tags-input .tag{
    display:inline-flex; align-items:center; gap:6px;
    background:#f1f5f9; color:#0f172a; padding:4px 10px; border-radius:999px; font-size:.875rem;
  }
  .tags-input .tag .x{
    cursor:pointer; opacity:.7;
  }
  .tags-input .tag .x:hover{ opacity:1; }


</style>
<style>
  /* ===== Hero ===== */
  .profile-hero{
    position:relative; background-size:cover; background-position:center;
    padding:60px 0; overflow:hidden;
  }
  .profile-hero .overlay-dark{ position:absolute; inset:0; background:
    radial-gradient(1200px 400px at 10% -10%, rgba(99,102,241,.22), transparent 60%),
    radial-gradient(1200px 400px at 90% 0%, rgba(6,182,212,.18), transparent 60%),
    linear-gradient(180deg, rgba(2,6,23,.65), rgba(2,6,23,.80));}
  .profile-hero .hero-inner{ position:relative; z-index:2; }
  .ch-eyebrow{ color:#c7d2fe; text-transform:uppercase; letter-spacing:.16em; font-weight:700; font-size:.75rem; }
  .ch-title{ color:#fff; font-weight:800; }
  .ch-sub{ color:#e2e8f0; }

  /* ===== Sticky subnav ===== */
  .profile-subnav{
    position:sticky; top:0; z-index:20;
    background:#fff; border-bottom:1px solid #e9ecef;
  }
  .profile-subnav .nav{ gap:.5rem; padding:.75rem 0; }
  .profile-subnav .nav-link{
    border:1px solid #e5e7eb; border-radius:999px; padding:.45rem .85rem;
    color:#475569; font-weight:700;
  }
  .profile-subnav .nav-link.active{ background:#0d6efd; border-color:#0d6efd; color:#fff; }

  /* ===== Cards & inputs ===== */
  .site-section{ padding-top:2rem; }
  .profile-wrap .card{ border:0; border-radius:14px; box-shadow:0 6px 24px rgba(0,0,0,.06); }
  .profile-wrap .card-header{ background:#fff; border-bottom:1px solid rgba(0,0,0,.06); border-top-left-radius:14px; border-top-right-radius:14px; padding:14px 16px; }
  .profile-wrap .card-header h6{ margin:0; font-weight:800; }
  .form-control, .custom-select{ min-height:46px; }
  .input-group-text{ background:#f8fafc; }

  /* Avatar uploader */
  .avatar-box{ display:flex; align-items:center; gap:14px; padding:12px; border:1px dashed rgba(0,0,0,.12); border-radius:10px; background:#fafafa; }
  .avatar{ width:80px; height:80px; border-radius:10px; object-fit:cover; background:#f1f5f9; border:1px solid rgba(0,0,0,.06); }

  /* Tags (skills/education) */
  .tags-input{ display:flex; flex-wrap:wrap; gap:6px; padding:6px; border:1px solid rgba(0,0,0,.12); border-radius:.375rem; background:#fff; min-height:42px; }
  .tags-input-field{ border:0; outline:0; min-width:180px; flex:1 0 160px; padding:4px 6px; }
  .tags-input .tag{ display:inline-flex; align-items:center; gap:6px; background:#f1f5f9; color:#0f172a; padding:4px 10px; border-radius:999px; font-size:.875rem; }
  .tags-input .tag .x{ cursor:pointer; opacity:.7; }
  .tags-input .tag .x:hover{ opacity:1; }

  /* Resume table actions */
  .resume-table td, .resume-table th { vertical-align: middle; }
  .resume-file { max-width: 360px; }
  .btn-icon{ background:transparent; border:0; padding:.25rem; line-height:1; }
  .btn-icon i{ font-size:1rem; }
  .resume-row .fa-star{ color:#0d6efd; }
</style>

<!-- HERO -->
<section class="profile-hero overlay inner-page bg-image" style="background-image:url('../images/tst.jpg');">
  <div class="overlay-dark"></div>
  <div class="container hero-inner text-center">
    <div class="ch-eyebrow mb-1">Account</div>
    <h2 class="ch-title mb-2">Update Profile</h2>
    <p class="ch-sub mb-0">Keep your details fresh to stand out.</p>
  </div>
</section>
<nav class="profile-subnav">
  <div class="container">
    <ul class="nav">
      <li class="nav-item"><a class="nav-link active" href="#sec-basic">Basic Info</a></li>

      <?php if ($row->type === "Job Seeker"): ?>
        <li class="nav-item"><a class="nav-link" href="#sec-prof">Professional</a></li>
        <li class="nav-item"><a class="nav-link" href="#sec-resumes">Resumes</a></li>
      <?php else: ?>
        <li class="nav-item"><a class="nav-link" href="#sec-company">Company</a></li>
      <?php endif; ?>

      <li class="nav-item"><a class="nav-link" href="#sec-company">Social</a></li>
    </ul>
  </div>
</nav>


<section class="site-section" id="next-section">
  <div class="container profile-wrap">
    <?php
      if (isset($_SESSION['message'])) {
        $type = $_SESSION['message']['type'];
        $text = $_SESSION['message']['text'];
        echo '<div class="alert alert-'.htmlspecialchars($type).' alert-dismissible fade show" role="alert">'
          . htmlspecialchars($text) .
          '<button type="button" class="close" data-dismiss="alert" aria-label="Close">
             <span aria-hidden="true">&times;</span>
           </button>
         </div>';
        unset($_SESSION['message']);
      }
    ?>

<form action="update-profile.php?upd_id=<?php echo $id; ?>" method="POST" enctype="multipart/form-data">
  <div class="row">
    <!-- LEFT -->
    <div class="col-lg-7 mb-4">
      <div class="card mb-4" id="sec-basic">
        <div class="card-header"><h6 class="mb-0">Basic Information</h6></div>
        <div class="card-body">
          <div class="form-group">
            <label for="fullname"><?php echo ($row->type === "Employer") ? "Employer Name" : "Full Name"; ?></label>
            <input type="text" id="fullname" name="fullname" value="<?php echo htmlspecialchars($row->fullname); ?>" class="form-control">
          </div>

          <div class="form-row">
            <div class="form-group col-md-6">
              <label for="username">Username</label>
              <div class="input-group">
                <div class="input-group-prepend"><span class="input-group-text"><i class="fa fa-user"></i></span></div>
                <input type="text" id="username" name="username" value="<?php echo htmlspecialchars($row->username); ?>" class="form-control">
              </div>
            </div>
            <div class="form-group col-md-6">
              <label for="email">Email</label>
              <div class="input-group">
                <div class="input-group-prepend"><span class="input-group-text"><i class="fa fa-envelope"></i></span></div>
                <input type="text" id="email" name="email" value="<?php echo htmlspecialchars($row->email); ?>" class="form-control">
              </div>
            </div>
          </div>

          <?php if($row->type === "Job Seeker") : ?>
            <div class="form-group">
              <label for="title">Title</label>
              <input type="text" id="title" name="title" value="<?php echo htmlspecialchars($row->title); ?>" class="form-control" placeholder="e.g., Frontend Developer">
            </div>
          <?php else: ?>
            <input type="hidden" name="title" value="NULL">
          <?php endif; ?>

          <div class="form-group">
            <label for="contact">Contact</label>
            <div class="input-group">
              <div class="input-group-prepend"><span class="input-group-text"><i class="fa fa-phone"></i></span></div>
              <input type="text" id="contact" name="contact" value="<?php echo htmlspecialchars($row->contact); ?>" class="form-control">
            </div>
          </div>

          <?php if($row->type === "Job Seeker") : ?>
            <div class="form-row">
              <div class="form-group col-md-6">
                <label for="region_id">Region / State</label>
                <select id="region_id" name="region_id" class="form-control">
                  <option value="">Select region/state</option>
                  <?php foreach (($regionRows ?? []) as $rg): 
                        $rid = (int)$rg['id'];
                        $sel = ($rid === (int)$row->region_id) ? 'selected' : ''; ?>
                    <option value="<?= $rid ?>" <?= $sel ?>><?= htmlspecialchars($rg['name']) ?></option>
                  <?php endforeach; ?>
                </select>
              </div>
              <div class="form-group col-md-6">
                <label for="address">Address</label>
                <input type="text" id="address" name="address"
                      value="<?= htmlspecialchars($row->address ?? '') ?>"
                      class="form-control" placeholder="Street address">
              </div>
            </div>
          <?php endif; ?>


          <div class="form-group">
            <label for="bio"><?php echo ($row->type === "Job Seeker") ? "Bio" : "About Employer"; ?></label>
            <textarea id="bio" name="bio" rows="6" class="form-control" placeholder="Provide a simple bio"><?php echo htmlspecialchars($row->bio); ?></textarea>
            <small class="text-muted d-block mt-1">A clear, concise summary looks great.</small>
          </div>
        </div>
      </div>

      <?php if($row->type === "Job Seeker") : ?>
        <div class="card mb-4" id="sec-prof">
          <div class="card-header"><h6 class="mb-0">Professional Details</h6></div>
          <div class="card-body">
            <!-- Skills -->
            <div class="form-group">
              <label>Skills</label>
              <div id="skillsChips" class="tags-input">
                <input type="text" class="tags-input-field" placeholder="Type a skill and press Enter" aria-label="Add skill">
              </div>
              <input type="hidden" name="skills" id="skillsHidden">
              <small class="text-muted d-block mt-1">Examples: HTML, CSS, JavaScript. Press <b>Enter</b> or <b>,</b> to add.</small>
            </div>

            <!-- Education -->
            <div class="form-group">
              <label>Education</label>
              <div id="eduChips" class="tags-input">
                <input type="text" class="tags-input-field" placeholder="Add education item and press Enter" aria-label="Add education">
              </div>
              <input type="hidden" name="education" id="educationHidden">
              <small class="text-muted d-block mt-1">Examples: BSc in IT, MSc in CS. Press <b>Enter</b> or <b>,</b> to add.</small>
            </div>
          </div>
        </div>
      <?php endif; ?>

    </div>

    <!-- RIGHT -->
    <div class="col-lg-5 mb-4">
      <div class="card mb-4">
        <div class="card-header"><h6 class="mb-0">Profile Image</h6></div>
        <div class="card-body">
          <div class="form-group">
            <label class="d-block">Upload Profile Image</label>
            <div class="avatar-box mb-2">
              <?php if (!empty($row->img) && file_exists("user-images/" . $row->img)) : ?>
                <img src="<?php echo 'user-images/' . htmlspecialchars($row->img); ?>" alt="Current Profile Image" class="avatar">
                <div class="text-muted small">Current image</div>
              <?php else: ?>
                <img src="../images/user-placeholder.png" alt="No Image" class="avatar">
                <div class="text-muted small">No image uploaded</div>
              <?php endif; ?>
            </div>
            <input type="file" name="img" class="form-control">
            <small class="text-muted d-block mt-1">JPG/PNG, square works best.</small>
          </div>
        </div>
      </div>

      <?php if($row->type === "Job Seeker") : ?>
      <!-- RESUMES MANAGER -->
      <div class="card mb-4" id="sec-resumes">
        <div class="card-header"><h6 class="mb-0">Resumes</h6></div>
        <div class="card-body">
          <div class="mb-3">
            <label class="mb-1">Upload New Resume (PDF/DOC/DOCX)</label>
            <div class="form-row">
              <div class="col-12">
                <input type="file" id="resume_new" class="form-control" accept=".pdf,.doc,.docx">
              </div>
            </div>
            <div class="form-check mt-2">
              <input class="form-check-input" type="checkbox" id="resume_make_primary">
              <label class="form-check-label" for="resume_make_primary">Make this my primary resume</label>
            </div>
            <button type="button" id="resumeUploadBtn" class="btn btn-sm btn-outline-primary mt-2">
              <i class="fa fa-upload"></i> Upload
            </button>
            <div id="resumeUploadMsg" class="small mt-1 text-muted"></div>
          </div>

          <div class="table-responsive">
          <table class="table table-sm table-hover align-middle resume-table mb-0">
            <thead class="thead-light">
              <tr>
                <th style="width:36px;"></th>            
                <th>Resume</th>
                <th style="width:160px;">Uploaded</th>
                <th class="text-right" style="width:160px;">Actions</th>
              </tr>
            </thead>
            <tbody id="resumeRows">
              <tr><td colspan="4" class="text-muted">Loading…</td></tr>
            </tbody>
          </table>
        </div>

        </div>
      </div>
      <?php endif; ?>

      <?php if($row->type === "Employer"): ?>
        <div class="card mb-4" id="sec-company">
          <div class="card-header"><h6 class="mb-0">Company Details</h6></div>
          <div class="card-body">
            <div class="form-group">
              <label for="company_website">Company Website</label>
              <div class="input-group">
                <div class="input-group-prepend"><span class="input-group-text"><i class="fa fa-globe"></i></span></div>
                <input type="text" id="company_website" name="company_website" value="<?php echo htmlspecialchars($companyDetails->company_website ?? ''); ?>" class="form-control" placeholder="https://">
              </div>
            </div>
            <div class="form-group">
              <label for="industry">Industry</label>
              <div class="input-group">
                <div class="input-group-prepend"><span class="input-group-text"><i class="fa fa-industry"></i></span></div>
                <input type="text" id="industry" name="industry" value="<?php echo htmlspecialchars($companyDetails->industry ?? ''); ?>" class="form-control">
              </div>
            </div>
            <div class="form-group">
              <label for="address_line">Address</label>
              <div class="input-group">
                <div class="input-group-prepend"><span class="input-group-text"><i class="fa-solid fa-location-dot"></i></span></div>
                <input type="text" id="address_line" name="address_line" value="<?php echo htmlspecialchars($companyDetails->address_line ?? ''); ?>" class="form-control">
              </div>
            </div>
            <div class="form-row">
              <div class="form-group col-md-6">
                <label for="postal_code">Postal Code</label>
                <input type="text" id="postal_code" name="postal_code" value="<?php echo htmlspecialchars($companyDetails->postal_code ?? ''); ?>" class="form-control">
              </div>
              <div class="form-group col-md-6">
                <label for="established_year">Established Year</label>
                <input type="text" id="established_year" name="established_year" value="<?php echo htmlspecialchars($companyDetails->established_year ?? ''); ?>" class="form-control">
              </div>
            </div>
            <div class="form-group">
              <label for="operating_hours">Operating Hours</label>
              <div class="input-group">
                <div class="input-group-prepend"><span class="input-group-text"><i class="fa-solid fa-clock"></i></span></div>
                <input type="text" id="operating_hours" name="operating_hours" value="<?php echo htmlspecialchars($companyDetails->operating_hours ?? ''); ?>" class="form-control" placeholder="e.g., Mon–Fri, 9am–5pm">
              </div>
            </div>
          </div>
        </div>
      <?php endif; ?>

      <div class="card mb-4" id="sec-company">
        <div class="card-header"><h6 class="mb-0">Social Links</h6></div>
        <div class="card-body">
          <div class="form-group">
            <label for="facebook">Facebook</label>
            <div class="input-group">
              <div class="input-group-prepend"><span class="input-group-text"><i class="fa-brands fa-facebook"></i></span></div>
              <input type="text" id="facebook" name="facebook" value="<?php echo htmlspecialchars($row->facebook); ?>" class="form-control" placeholder="https://facebook.com/…">
            </div>
          </div>
          <div class="form-group">
            <label for="twitter">Twitter</label>
            <div class="input-group">
              <div class="input-group-prepend"><span class="input-group-text"><i class="fa-brands fa-x-twitter"></i></span></div>
              <input type="text" id="twitter" name="twitter" value="<?php echo htmlspecialchars($row->twitter); ?>" class="form-control" placeholder="https://twitter.com/…">
            </div>
          </div>
          <div class="form-group">
            <label for="linkedin">LinkedIn</label>
            <div class="input-group">
              <div class="input-group-prepend"><span class="input-group-text"><i class="fa-brands fa-linkedin"></i></span></div>
              <input type="text" id="linkedin" name="linkedin" value="<?php echo htmlspecialchars($row->linkedin); ?>" class="form-control" placeholder="https://linkedin.com/in/…">
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Submit -->
    <div class="col-12 text-right">
      <button type="submit" name="submit" class="btn btn-primary">
        <i class="fa fa-save mr-1"></i> Update Profile Details
      </button>
    </div>
  </div>
</form>

  </div>
</section>

<script>
(function($){
  // Only for Job Seekers: Resume manager JS
  var isSeeker = <?php echo json_encode($row->type === 'Job Seeker'); ?>;
  if (!isSeeker) return;

  function loadResumes(){
  $('#resumeRows').html('<tr><td colspan="4" class="text-muted">Loading…</td></tr>');

  $.post('update-profile.php?upd_id=<?php echo (int)$id; ?>', {ajax:1, action:'list_resumes'}, function(res){
    if (!res || !res.ok) {
      $('#resumeRows').html('<tr><td colspan="4" class="text-danger">Failed to load.</td></tr>');
      return;
    }

    var items = res.items || [];
    if (!items.length) {
      $('#resumeRows').html('<tr><td colspan="4" class="text-muted">No resumes uploaded yet.</td></tr>');
      return;
    }

    function fmtDate(dstr){
      if (!dstr) return '';
      // handle "YYYY-MM-DD HH:MM:SS"
      var y = dstr.substr(0,4), m = dstr.substr(5,2), d = dstr.substr(8,2);
      var months = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];
      var mi = Math.max(1, Math.min(12, parseInt(m,10))) - 1;
      return parseInt(d,10) + ' ' + months[mi] + ', ' + y;
    }

    var html = '';
    items.forEach(function(it){
      var isPrimary = !!it.is_primary;                          // 1/0 from DB
      var viewUrl   = <?php echo json_encode('user-cvs/'); ?> + it.filename;
      var display   = it.original_name || it.filename || 'resume';
      var uploaded  = fmtDate(it.created_at);

      // primary star:
      var primaryCol = isPrimary
        ? '<i class="fa fa-star text-primary" title="Primary"></i>'
        : '<button type="button" class="btn-icon act-set" data-id="'+it.id+'" title="Set primary">'
          +   '<i class="far fa-star"></i>'
          + '</button>';

      // actions: replace (file input) + delete (icon)
      var actions =
        '<label class="btn-icon mb-0 mr-1" title="Replace">'
        +  '<i class="fa fa-sync-alt"></i>'
        +  '<input type="file" class="d-none act-replace" data-id="'+it.id+'" accept=".pdf,.doc,.docx">'
        + '</label>'
        + '<button type="button" class="btn-icon text-danger act-del" data-id="'+it.id+'" title="Delete">'
        +   '<i class="fa fa-trash"></i>'
        + '</button>';

      html += '<tr class="resume-row">'
           +    '<td class="text-center">'+ primaryCol +'</td>'
           +    '<td>'
           +      '<div class="text-truncate resume-file" title="'+$('<div>').text(display).html()+'">'
           +        '<a href="'+viewUrl+'" target="_blank">'+$('<div>').text(display).html()+'</a>'
           +      '</div>'
           +    '</td>'
           +    '<td>'+ uploaded +'</td>'
           +    '<td class="text-right">'+ actions +'</td>'
           +  '</tr>';
    });

    $('#resumeRows').html(html);
  }, 'json');
}


  // Upload new
  $('#resumeUploadBtn').on('click', function(){
    var f = $('#resume_new')[0].files[0];
    if (!f) { $('#resumeUploadMsg').text('Please choose a file.'); return; }
    var fd = new FormData();
    fd.append('ajax', 1);
    fd.append('action', 'upload_resume');
    fd.append('resume_new', f);
    fd.append('make_primary', $('#resume_make_primary').is(':checked') ? 1 : 0);
    $('#resumeUploadMsg').text('Uploading…');
    $.ajax({
      url: 'update-profile.php?upd_id=<?php echo (int)$id; ?>',
      method: 'POST',
      data: fd, processData:false, contentType:false, dataType:'json'
    }).done(function(res){
      if (res && res.ok) {
        $('#resumeUploadMsg').text('Uploaded.');
        $('#resume_new').val('');
        $('#resume_make_primary').prop('checked', false);
        loadResumes();
      } else {
        $('#resumeUploadMsg').text((res && res.error) ? res.error : 'Upload failed');
      }
    }).fail(function(){
      $('#resumeUploadMsg').text('Upload failed');
    });
  });

  // Delegate actions
  $('#resumeRows')
    .on('click', '.act-set', function(){
      var id = $(this).data('id');
      $.post('update-profile.php?upd_id=<?php echo (int)$id; ?>', {ajax:1, action:'set_primary', id:id}, function(res){
        if (res && res.ok) loadResumes();
        else alert(res.error || 'Failed to set primary');
      }, 'json');
    })
    .on('click', '.act-del', function(){
      if (!confirm('Delete this resume?')) return;
      var id = $(this).data('id');
      $.post('update-profile.php?upd_id=<?php echo (int)$id; ?>', {ajax:1, action:'delete_resume', id:id}, function(res){
        if (res && res.ok) loadResumes();
        else alert(res.error || 'Delete failed');
      }, 'json');
    })
    .on('change', '.act-replace', function(){
      var id = $(this).data('id');
      var f  = this.files[0];
      if (!f) return;
      var fd = new FormData();
      fd.append('ajax', 1);
      fd.append('action', 'replace_resume');
      fd.append('id', id);
      fd.append('resume_file', f);
      $.ajax({
        url: 'update-profile.php?upd_id=<?php echo (int)$id; ?>',
        method: 'POST',
        data: fd, processData:false, contentType:false, dataType:'json'
      }).done(function(res){
        if (res && res.ok) loadResumes();
        else alert(res.error || 'Replace failed');
      }).fail(function(){ alert('Replace failed'); });
    });

  // initial load
  loadResumes();
})(jQuery);


</script>
<script>
(function($){
  function makeChips($wrap, $hidden, initialCSV){
    var items = [];
    function normalize(str){
      return (str || '')
        .replace(/\s+/g, ' ')
        .replace(/[<>]/g,'')          
        .trim();
    }
    function toList(str){
      if(!str) return [];
      // split by comma or newline/semicolon • bullets
      return str.split(/[\n,;•]+/).map(normalize).filter(Boolean);
    }
    function render(){
      $wrap.find('.tag').remove();
      items.forEach(function(t, i){
        var $tag = $('<span class="tag"></span>').text(t);
        var $x = $('<span class="x" aria-label="Remove" title="Remove">&times;</span>');
        $x.on('click', function(){ items.splice(i,1); sync(); });
        $tag.append($x);
        $tag.insertBefore($input);
      });
      $hidden.val(items.join(', '));
    }
    function add(val){
      val = normalize(val);
      if(!val) return;
      // dedupe (case-insensitive)
      var exists = items.some(function(x){ return x.toLowerCase() === val.toLowerCase(); });
      if(!exists){ items.push(val); render(); }
    }
    function sync(){ render(); }

    var $input = $wrap.find('.tags-input-field');

    // init with existing values
    items = toList(initialCSV);
    render();

    // events
    $input.on('keydown', function(e){
      if (e.key === 'Enter' || e.key === ','){
        e.preventDefault();
        add($input.val());
        $input.val('');
      } else if (e.key === 'Backspace' && !$input.val() && items.length){
        // backspace on empty -> remove last
        items.pop(); render();
      }
    });

    // paste: split into multiple
    $input.on('paste', function(e){
      var text = (e.originalEvent.clipboardData || window.clipboardData).getData('text');
      if(text && /[,;\n]/.test(text)){
        e.preventDefault();
        toList(text).forEach(add);
      }
    });

    return { add:add, get: function(){ return items.slice(); } };
  }

  // Boot both fields
  $(function(){
    var initSkills = <?php echo json_encode((string)$row->skills); ?>;
    var initEdu    = <?php echo json_encode((string)$row->education); ?>;
    makeChips($('#skillsChips'), $('#skillsHidden'), initSkills);
    makeChips($('#eduChips'),    $('#educationHidden'), initEdu);
  });
})(jQuery);
</script>
<script>
  (function(){
    // Smooth scroll
    document.querySelectorAll('.profile-subnav .nav-link').forEach(function(a){
      a.addEventListener('click', function(e){
        var id = this.getAttribute('href');
        if(!id || id.charAt(0) !== '#') return;
        var el = document.querySelector(id);
        if(!el) return;
        e.preventDefault();
        var offset = 80; // room for sticky nav
        var top = el.getBoundingClientRect().top + window.pageYOffset - offset;
        window.scrollTo({ top: top, behavior: 'smooth' });
      });
    });

    // Active section highlight
    var links = Array.from(document.querySelectorAll('.profile-subnav .nav-link'));
    var map   = links.map(function(l){ return [l, document.querySelector(l.getAttribute('href'))]; });

    var obs = new IntersectionObserver(function(entries){
      entries.forEach(function(ent){
        if (ent.isIntersecting){
          var id = '#' + ent.target.id;
          links.forEach(function(l){ l.classList.toggle('active', l.getAttribute('href') === id); });
        }
      });
    }, { rootMargin: '-50% 0px -40% 0px', threshold: 0.01 });

    map.forEach(function(pair){ if(pair[1]) obs.observe(pair[1]); });
  })();
</script>

<?php require "../includes/footer.php"; ?>
