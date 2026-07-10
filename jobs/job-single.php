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

/* ============================================================
   AJAX ENDPOINTS (run before any output)
   ============================================================ */
function table_exists(PDO $conn, string $t): bool {
  try { $conn->query("SELECT 1 FROM `$t` LIMIT 1"); return true; }
  catch (Throwable $e) { return false; }
}

if (!empty($_POST['ajax']) && isset($_POST['action'])) {
  header('Content-Type: application/json');

  // must be logged-in Job Seeker
  $meId   = $_SESSION['id']   ?? null;
  $meType = $_SESSION['type'] ?? null;

  if (!$meId || $meType !== 'Job Seeker') {
    echo json_encode(['success' => false, 'error' => 'Not authorized']); exit;
  }

  // basic helpers
  function safe_ext($name, $allowed) {
    $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
    return in_array($ext, $allowed, true) ? $ext : null;
  }

  $action = $_POST['action'];

  /* ---------- list_resumes (RESUMES TABLE ONLY) ---------- */
  if ($action === 'list_resumes') {
    try {
      if (!table_exists($conn, 'resumes')) {
        // Table missing - return empty list; Step 1  show Upload section
        echo json_encode(['success' => true, 'resumes' => []]); exit;
      }
      $q = $conn->prepare("SELECT id, label, filename, created_at
                           FROM resumes WHERE user_id = :uid
                           ORDER BY id DESC");
      $q->execute([':uid' => $meId]);
      $rows = $q->fetchAll(PDO::FETCH_ASSOC);

      echo json_encode(['success' => true, 'resumes' => $rows]); exit;
    } catch (Throwable $e) {
      echo json_encode(['success' => false, 'resumes' => [], 'error' => 'Unable to load resumes']); exit;
    }
  }

  /* ---------- upload_resume (RESUMES TABLE ONLY) ---------- */
  if ($action === 'upload_resume') {
    try {
      if (!isset($_FILES['resume_file']) || $_FILES['resume_file']['error'] !== UPLOAD_ERR_OK) {
        echo json_encode(['success'=>false,'error'=>'No file uploaded']); exit;
      }
      $allowed = ['pdf','doc','docx'];
      $ext = safe_ext($_FILES['resume_file']['name'], $allowed);
      if (!$ext) { echo json_encode(['success'=>false,'error'=>'Invalid file type']); exit; }

      // make sure of storage dir
      $dir = __DIR__ . '/../user-cvs/';
      if (!is_dir($dir)) @mkdir($dir, 0775, true);

      $newName = 'cv_'.$meId.'_'.time().'_'.bin2hex(random_bytes(2)).'.'.$ext;
      $dest = $dir.$newName;
      if (!move_uploaded_file($_FILES['resume_file']['tmp_name'], $dest)) {
        echo json_encode(['success'=>false,'error'=>'Upload failed']); exit;
      }

      // makes sure that resumes table exists (auto-create using the chosen schema)
      if (!table_exists($conn, 'resumes')) {
        $conn->exec("
          CREATE TABLE IF NOT EXISTS resumes (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            label VARCHAR(255) DEFAULT NULL,
            filename VARCHAR(255) NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX (user_id),
            CONSTRAINT fk_resumes_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
          ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
        ");
      }

      $label = trim($_POST['label'] ?? '');
      $ins = $conn->prepare("INSERT INTO resumes (user_id,label,filename) VALUES (:uid,:label,:fn)");
      $ins->execute([':uid'=>$meId, ':label'=>($label!==''?$label:null), ':fn'=>$newName]);
      $rid = (int)$conn->lastInsertId();

      echo json_encode(['success'=>true,'resume'=>[
        'id'=>$rid,'label'=>($label!==''?$label:null),'filename'=>$newName
      ]]); exit;

    } catch (Throwable $e) {
      echo json_encode(['success'=>false,'error'=>'Server error uploading resume']); exit;
    }
  }

  /* ---------- load_questions ---------- */
  if ($action === 'load_questions') {
    $jobId = (int)($_POST['job_id'] ?? 0);
    if (!$jobId) { echo json_encode(['success'=>false,'error'=>'Missing job id']); exit; }

    if (!table_exists($conn, 'job_questions')) {
      echo json_encode(['success'=>true,'questions'=>[]]); exit;
    }

    $q = $conn->prepare("
      SELECT id, source, question_text, qtype, is_required, options, sort_order
      FROM job_questions
      WHERE job_id = :jid
      ORDER BY sort_order, id
    ");
    $q->execute([':jid'=>$jobId]);
    $rows = $q->fetchAll(PDO::FETCH_ASSOC);
    foreach ($rows as &$r) {
      $r['is_required'] = (int)$r['is_required'];
      $opts = json_decode($r['options'] ?? '[]', true);
      $r['options'] = is_array($opts) ? array_values($opts) : [];
    }
    echo json_encode(['success'=>true,'questions'=>$rows]); exit;
  }

  /* ---------- submit_application (uses resumes.id ONLY) ---------- */
  if ($action === 'submit_application') {
    $jobId       = (int)($_POST['job_id'] ?? 0);
    $companyId   = (int)($_POST['company_id'] ?? 0);
    $jobTitle    = trim($_POST['job_title'] ?? '');
    $resumePick  = $_POST['resume_id'] ?? '';           // must be numeric resumes.id
    $answersMeta = json_decode($_POST['answers_meta'] ?? '[]', true); // [{id,qtype,required}]

    if (!$jobId || !$companyId || $jobTitle === '') {
      echo json_encode(['success'=>false,'error'=>'Missing core fields']); exit;
    }

    if (!ctype_digit((string)$resumePick)) {
      echo json_encode(['success'=>false,'error'=>'Please select a resume']); exit;
    }

    // prevent double applications
    $chk = $conn->prepare("SELECT 1 FROM job_applications WHERE worker_id = :uid AND job_id = :jid LIMIT 1");
    $chk->execute([':uid'=>$meId, ':jid'=>$jobId]);
    if ($chk->fetchColumn()) {
      echo json_encode(['success'=>false,'error'=>'You already applied to this job']); exit;
    }

    // resolve resume filename from resumes table
    $r = $conn->prepare("SELECT filename FROM resumes WHERE id = :rid AND user_id = :uid LIMIT 1");
    $r->execute([':rid'=>$resumePick, ':uid'=>$meId]);
    $rrow = $r->fetch(PDO::FETCH_OBJ);
    $resumeFile = $rrow ? $rrow->filename : '';
    if ($resumeFile === '') {
      echo json_encode(['success'=>false,'error'=>'Invalid resume selection']); exit;
    }

    // create application
    $ins = $conn->prepare("
      INSERT INTO job_applications
        (fullname, username, email, contact, cv, worker_id, job_id, job_title, company_id)
      VALUES
        (:fullname,:username,:email,:contact,:cv,:worker_id,:job_id,:job_title,:company_id)
    ");

    $ins->execute([
      ':fullname'   => $_SESSION['fullname'] ?? '',
      ':username'   => $_SESSION['username'] ?? '',
      ':email'      => $_SESSION['email'] ?? '',
      ':contact'    => $_SESSION['contact'] ?? '',
      ':cv'         => $resumeFile,
      ':worker_id'  => $meId,
      ':job_id'     => $jobId,
      ':job_title'  => $jobTitle,
      ':company_id' => $companyId
    ]);


    $appId = (int)$conn->lastInsertId();

    // store answers (supports file uploads for file-type questions)
    $filesDir = __DIR__ . '/job-app-files/';
    if (!is_dir($filesDir)) @mkdir($filesDir, 0775, true);

    foreach ($answersMeta as $meta) {
      $qid   = (int)($meta['id'] ?? 0);
      $qtype = $meta['qtype'] ?? 'text';
      if (!$qid) continue;

      $answerText = null;
      $answerFile = null;

      switch ($qtype) {
        case 'text':
        case 'textarea':
        case 'yesno':
        case 'dropdown':
          $field = 'q_' . $qid;
          $answerText = isset($_POST[$field]) ? trim((string)$_POST[$field]) : null;
          break;

        case 'mcq':
          $field = 'q_' . $qid;
          $vals = isset($_POST[$field]) && is_array($_POST[$field]) ? array_values($_POST[$field]) : [];
          $answerText = json_encode($vals, JSON_UNESCAPED_UNICODE);
          break;

        case 'file':
          $field = 'qfile_' . $qid;
          if (isset($_FILES[$field]) && $_FILES[$field]['error'] === UPLOAD_ERR_OK) {
            $ext = safe_ext($_FILES[$field]['name'], ['pdf','doc','docx','png','jpg','jpeg']);
            if ($ext) {
              $newName = 'ans_'.$appId.'_'.$qid.'_'.time().'_'.bin2hex(random_bytes(2)).'.'.$ext;
              if (move_uploaded_file($_FILES[$field]['tmp_name'], $filesDir.$newName)) {
                $answerFile = $newName;
              }
            }
          }
          break;
      }

      $ia = $conn->prepare("INSERT INTO job_app_answers
        (application_id, question_id, answer_text, answer_file)
        VALUES
        (:aid, :qid, :txt, :file)");
      $ia->execute([
        ':aid'  => $appId,
        ':qid'  => $qid,
        ':txt'  => $answerText,
        ':file' => $answerFile
      ]);
    }

    // --- NOTIFY EMPLOYER: new application received ---
    try {
      // build base url
      $scheme    = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
      $host      = $_SERVER['HTTP_HOST'] ?? 'localhost';
      $project   = explode('/', trim($_SERVER['SCRIPT_NAME'] ?? '', '/'))[0] ?? '';
      $base_url_ = $scheme . '://' . $host . '/' . $project;

      $nt = $conn->prepare("
        INSERT INTO notifications
          (recipient_user_id, actor_user_id, type, job_id, application_id, title, message, link_path)
        VALUES
          (:rid, :aid, 'new_application', :job, :app, :title, :msg, :link)
      ");
      $nt->execute([
        ':rid'   => $companyId,          // employer
        ':aid'   => $meId,               // job seeker
        ':job'   => $jobId,
        ':app'   => $appId,
        ':title' => 'New application received',
        ':msg'   => 'Your job "' . $jobTitle . '" has a new applicant.',
        ':link'  => $base_url_ . '/users/show-applicants.php?id=' . $companyId . '&job_id=' . $jobId
      ]);
    } catch (Throwable $e) {
      // swallow; notifications should never break the main flow
    }


    echo json_encode(['success'=>true,'application_id'=>$appId]); exit;
  }

  echo json_encode(['success'=>false,'error'=>'Unknown action']); exit;
}
/* ============================================================
   NORMAL PAGE FLOW
   ============================================================ */

require "../includes/header.php";

if (isset($_GET['id'])) {
  $id = (int)$_GET['id'];

  $select = $conn->prepare("
    SELECT j.*,
           u.fullname AS company_name,
           u.img       AS company_logo,
           r.name      AS region_name,
           r.code      AS region_code
    FROM jobs j
    JOIN users u            ON j.company_id = u.id
    LEFT JOIN job_regions r ON j.job_region = r.code
    WHERE j.id = :id
    LIMIT 1
  ");
  $select->bindParam(':id', $id, PDO::PARAM_INT);
  $select->execute();
  $row = $select->fetch(PDO::FETCH_OBJ);

  if (!$row) { header('Location: ' . APPURL . '/404.php'); exit; }

  // Safe logo fallback
  $userImage = !empty($row->company_logo) ? $row->company_logo : 'default-image.png';

  $postedTs   = strtotime($row->created_at);
  $deadlineTs = strtotime($row->application_deadline);
  $nowTs      = time();
  $daysLeft   = max(0, ceil(($deadlineTs - $nowTs) / 86400));

  $typeClass = 'jd-primary';
  if     ($row->job_type === 'Full Time') $typeClass = 'jd-success';
  elseif ($row->job_type === 'Part Time') $typeClass = 'jd-danger';
  elseif ($row->job_type === 'Contract')  $typeClass = 'jd-info';
  elseif ($row->job_type === 'Casual')    $typeClass = 'jd-secondary';

  $dueClass = $daysLeft <= 3 ? 'danger' : ($daysLeft <= 10 ? 'warn' : 'ok');

  //helper for workarrangement viewing
  $workArr = trim($row->work_arrangement ?? '');
  function jd_work_class($val){
    $v = strtolower((string)$val);
    if (strpos($v,'hybrid') !== false) return 'jd-work--hybrid';
    if (strpos($v,'remote') !== false) return 'jd-work--remote';
    return 'jd-work--onsite';
  }
  function jd_work_icon($val){
    $v = strtolower((string)$val);
    if (strpos($v,'hybrid') !== false) return 'fa-exchange';
    if (strpos($v,'remote') !== false) return 'fa-globe';
    return 'fa-building';
  }

  $totalWindow = max(1, $deadlineTs - $postedTs);
  $elapsed     = max(0, min($totalWindow, $nowTs - $postedTs));
  $pct         = round(($elapsed / $totalWindow) * 100);

  // view count
  // $jobId       = $id;
  // $jobOwnerId  = $row->company_id;
  // $currentUserId   = $_SESSION['id'] ?? null;
  // $currentUserType = $_SESSION['type'] ?? 'guest';

  // if ($currentUserId != $jobOwnerId && ($currentUserType === 'Job Seeker' || $currentUserId === null)) {
  //   $viewKey = "viewed_job_" . $jobId;
  //   $cooldownSeconds = 6 * 60 * 60;
  //   if (!isset($_SESSION[$viewKey]) || time() - $_SESSION[$viewKey] > $cooldownSeconds) {
  //     $updateViews = $conn->prepare("UPDATE jobs SET view_count = view_count + 1 WHERE id = :job_id");
  //     $updateViews->execute([':job_id' => $jobId]);
  //     $_SESSION[$viewKey] = time();
  //   }
  // }

  // --- View count (with per-user/day dedupe for signed-in users) ---
  $jobId          = $id;
  $jobOwnerId     = (int)$row->company_id;
  $currentUserId  = isset($_SESSION['id']) ? (int)$_SESSION['id'] : null;
  $currentUserType= $_SESSION['type'] ?? 'guest';

  if ($currentUserId !== $jobOwnerId && ($currentUserType === 'Job Seeker' || $currentUserId === null)) {
    $viewKey         = "viewed_job_" . $jobId;
    $cooldownSeconds = 6 * 60 * 60;

    if ($currentUserId) {
      // Signed-in (non-owner): one row per job/user/day -> only increment when its a new day-unique view
      $ins = $conn->prepare("
        INSERT INTO job_views (job_id, company_id, viewer_user_id, viewed_date)
        VALUES (:jid, :cid, :uid, CURDATE())
        ON DUPLICATE KEY UPDATE job_id = job_id
      ");
      $ins->execute([
        ':jid' => $jobId,
        ':cid' => $jobOwnerId,
        ':uid' => $currentUserId
      ]);

      if ($ins->rowCount() > 0) { // new unique view for this user today
        $updateViews = $conn->prepare("UPDATE jobs SET view_count = COALESCE(view_count,0) + 1 WHERE id = :job_id");
        $updateViews->execute([':job_id' => $jobId]);
        // (opt)  set session flag—harmless,    keeping parity with guest cooldown ux
        $_SESSION[$viewKey] = time();
      }

    } else {
      
      $allow = (!isset($_SESSION[$viewKey]) || time() - $_SESSION[$viewKey] > $cooldownSeconds);
      if ($allow) {
        $updateViews = $conn->prepare("UPDATE jobs SET view_count = COALESCE(view_count,0) + 1 WHERE id = :job_id");
        $updateViews->execute([':job_id' => $jobId]);
        $_SESSION[$viewKey] = time();

        // Log the view for trends
        $insGuest = $conn->prepare("
          INSERT INTO job_views (job_id, company_id, viewer_user_id, viewed_date)
          VALUES (:jid, :cid, NULL, CURDATE())
        ");
        $insGuest->execute([':jid' => $jobId, ':cid' => $jobOwnerId]);
      }
    }
  }


  // related jobs
  $related_jobs = $conn->prepare("SELECT * FROM jobs
    WHERE job_category = :cat AND status = 1 AND id != :id AND application_deadline >= CURDATE()");
  $related_jobs->execute([':cat'=>$row->job_category, ':id'=>$id]);
  $related_job = $related_jobs->fetchAll(PDO::FETCH_OBJ);

  $job_count = $conn->prepare("SELECT COUNT(*) as job_count FROM jobs WHERE job_category = :cat AND status = 1 AND id != :id AND application_deadline >= CURDATE()");
  $job_count->execute([':cat'=>$row->job_category, ':id'=>$id]);
  $job_num = $job_count->fetch(PDO::FETCH_OBJ);

  // Existing checks
  if (isset($_SESSION['id'])) {
    $checking_for_application = $conn->prepare("SELECT 1 FROM job_applications WHERE worker_id = :uid AND job_id = :jid");
    $checking_for_application->execute([':uid'=>$_SESSION['id'], ':jid'=>$id]);

    $checking_for_saved_jobs = $conn->prepare("SELECT 1 FROM saved_jobs WHERE worker_id = :uid AND job_id = :jid");
    $checking_for_saved_jobs->execute([':uid'=>$_SESSION['id'], ':jid'=>$id]);
  }

  // categories (right sidebar)
  $categories = $conn->query("SELECT * FROM categories");
  $categories->execute();
  $allCategories = $categories->fetchAll(PDO::FETCH_OBJ);

  // application count
  $appCountStmt = $conn->prepare("SELECT COUNT(*) as cnt FROM job_applications WHERE job_id = :job_id");
  $appCountStmt->execute([':job_id' => $row->id]);
  $appCountObj = $appCountStmt->fetch(PDO::FETCH_OBJ);
  $appCount = $appCountObj->cnt ?? 0;

} else {
  header("location: " . APPURL . "/404.php");
  exit;
}
?>

<link rel="stylesheet" type="text/css" href="jobsinglecss.css">

<section class="site-section">
  <div class="container">
    <?php if (isset($_GET['saved']) && $_GET['saved'] == 1): ?>
      <div class="alert alert-success alert-dismissible fade show" role="alert">
        <strong>Success!</strong> Job has been saved to your list.
        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
    <?php elseif (isset($_GET['removed']) && $_GET['removed'] == 1): ?>
      <div class="alert alert-warning alert-dismissible fade show" role="alert">
        <strong>Notice:</strong> Job has been removed from your saved list.
        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
    <?php endif; ?>

    <div class="row">
      <div class="jd-hero">
        <div class="jd-hero-row">
          <?php
            $name    = trim($row->company_name ?? '');
            $initial = mb_strtoupper(mb_substr($name, 0, 1, 'UTF-8'), 'UTF-8');
            $imgFile = $userImage ?? '';
            $lower   = strtolower($imgFile);
            $placeholders = ['default-image.png','emp_imgplaceholder.png','user_placeholderimg.jpg'];
            $hasLogo = !empty($imgFile)
                    && !in_array($lower, $placeholders, true)
                    && @file_exists(__DIR__ . "/../users/user-images/" . $imgFile);
            $palette = ['#0d6efd','#6f42c1','#20c997','#dc3545','#fd7e14','#198754','#0dcaf0','#6c757d'];
            $bg = $palette[ hexdec(substr(md5(mb_strtolower($name,'UTF-8')),0,2)) % count($palette) ];
          ?>
          <div class="jd-logo-wrap">
            <?php if ($hasLogo): ?>
              <img class="jd-logo"
                   src="../users/user-images/<?= htmlspecialchars($imgFile) ?>"
                   alt="<?= htmlspecialchars($name ?: 'Company') ?> logo">
            <?php else: ?>
              <div class="jd-logo-initial" style="background:<?= $bg ?>;">
                <?= htmlspecialchars($initial ?: '?') ?>
              </div>
            <?php endif; ?>
          </div>

          <div>
            <h1 class="jd-title"><?php echo htmlspecialchars($row->job_title); ?></h1>
            <div class="jd-meta">
              <span class="jd-company"><strong><?php echo htmlspecialchars($row->company_name); ?></strong></span>
              <span class="sep">•</span>
              <span><i class="icon-room"></i>
                <?php echo isset($row->region_name) ? htmlspecialchars($row->region_name)." (".htmlspecialchars($row->region_code).")" : htmlspecialchars($row->job_region); ?>
              </span>
              <span class="sep">•</span>
              <span class="jd-chip jd-chip--type <?php echo $typeClass; ?>">
                <?php echo htmlspecialchars($row->job_type); ?>
              </span>

              <?php if (!empty($workArr)) : ?>
                <span class="sep">•</span>
                <span class="jd-chip jd-chip--work <?php echo jd_work_class($workArr); ?>">
                  <i class="fa <?php echo jd_work_icon($workArr); ?>"></i>
                  <?php echo htmlspecialchars($workArr); ?>
                </span>
              <?php endif; ?>

              <?php if (!empty($row->salary)) : ?>
                <span class="sep">•</span>
                <span class="jd-chip" style="background:#f1f5f9;color:#0f172a;">💼 <?php echo htmlspecialchars($row->salary); ?></span>
              <?php endif; ?>
              <span class="sep">•</span>
              <span class="jd-chip jd-chip--due <?php echo $dueClass; ?>">
                <?php echo $daysLeft > 0 ? $daysLeft . ' day' . ($daysLeft>1?'s':'') . ' left' : 'Closed'; ?>
              </span>
            </div>
          </div>

          <div class="jd-hero-right">
            <small class="jd-muted">Posted: <?php echo date('M d, Y', $postedTs); ?></small>
            <span class="jd-deadline"><i class="fa fa-clock"></i> Apply before: <?php echo date('M d, Y', $deadlineTs); ?></span>
            <div class="jd-track"><div class="jd-fill" style="width:<?php echo $pct; ?>%"></div></div>
          </div>
        </div>
      </div>

      <div class="jd-stat jd-stat--hero mb-3">
        <span><i class="fas fa-eye"></i> <?php echo (int)$row->view_count; ?> views</span>
        <span><i class="fas fa-users"></i> <?php echo (int)$appCount; ?> applicants</span>
      </div>

      <div class="jd-grid">
        <!-- LEFT -->
        <div>
          <div class="jd-card jd-info">
            <h4>Basic Job Information</h4>
            <p><strong>Job Position:</strong> <?php echo htmlspecialchars($row->job_title); ?></p>
            <p><strong>Published on:</strong> <?php echo date('M d, Y', $postedTs); ?></p>
            <p><strong>Vacancy:</strong> <?php echo htmlspecialchars($row->vacancy); ?></p>
            <p><strong>Employment Type:</strong> <?php echo htmlspecialchars($row->job_type); ?></p>
            <p><strong>Experience:</strong> <?php echo htmlspecialchars($row->experience); ?></p>
            <?php if(!empty($workArr)): ?>
              <p><strong>Work Arrangement:</strong> <?php echo htmlspecialchars($workArr); ?></p>
            <?php endif; ?>
            <?php if(!empty($row->salary)): ?><p><strong>Salary:</strong> <?php echo htmlspecialchars($row->salary); ?></p><?php endif; ?>
            <?php if(!empty($row->inclusivity_notes)): ?><p><strong>Notes:</strong> <?php echo htmlspecialchars($row->inclusivity_notes); ?></p><?php endif; ?>
            <p><strong>Application Deadline:</strong> <?php echo date('M d, Y', $deadlineTs); ?></p>
            <p><strong>Job Category:</strong> <?php echo ucfirst(htmlspecialchars($row->job_category)); ?></p>
          </div>

          <div class="jd-card">
            <h4>Job Description</h4>
            <div><?php echo htmlspecialchars_decode($row->job_description); ?></div>
          </div>

          <div class="jd-card">
            <h4>Key Responsibilities</h4>
            <div><?php echo htmlspecialchars_decode($row->responsibilities); ?></div>
          </div>

          <div class="jd-card">
            <h4>Requirements & Skills</h4>
            <div><?php echo htmlspecialchars_decode($row->education_experience); ?></div>
          </div>

          <?php if(!empty($row->other_benefits)): ?>
          <div class="jd-card">
            <h4>Benefits</h4>
            <div><?php echo htmlspecialchars_decode($row->other_benefits); ?></div>
          </div>
          <?php endif; ?>
        </div>

        <!-- RIGHT -->
        <aside>
          <div class="jd-sticky jd-sticky--desktop">
            <?php if (isset($_SESSION['username']) && isset($_SESSION['type']) && $_SESSION['type']==="Job Seeker"): ?>
              <div class="jd-actions mb-2">
                <?php if ($checking_for_saved_jobs->rowCount() == 0) : ?>
                  <a href="job-save.php?job_id=<?php echo $id; ?>&worker_id=<?php echo $_SESSION['id']; ?>&status=save" class="btn btn-warning">
                    <i class="icon-heart"></i> Save
                  </a>
                <?php else : ?>
                  <a href="job-save.php?job_id=<?php echo $id; ?>&worker_id=<?php echo $_SESSION['id']; ?>&status=delete" class="btn btn-secondary">
                    <i class="icon-heart text-danger"></i> Saved
                  </a>
                <?php endif; ?>

                <?php if ($checking_for_application->rowCount() == 0) : ?>
                  <button type="button" id="openApplyWizardBtn" class="btn btn-primary" style="flex:1;">
                    <i class="icon-paper-plane"></i> Apply
                  </button>
                <?php else : ?>
                  <div class="alert alert-success w-100 mb-0"><i class="icon-check"></i> Already applied</div>
                <?php endif; ?>
              </div>
            <?php elseif (!isset($_SESSION['username'])): ?>
              <a class="btn btn-primary btn-block" href="<?php echo $base_url; ?>/auth/loginRegister.php"><i class="icon-user"></i> Log in to Apply</a>
            <?php endif; ?>

            <hr>

            <!-- Company blurb -->
            <div class="mb-3">
              <b><h6 class="mb-2">About the Company</h6></b>
              <div class="jd-muted">
                <?php
                  $user_id = $row->company_id;
                  $sql = "SELECT bio FROM users WHERE id = :userId";
                  $stmt = $conn->prepare($sql);
                  $stmt->bindParam(':userId', $user_id, PDO::PARAM_INT);
                  $stmt->execute();
                  $user = $stmt->fetch(PDO::FETCH_OBJ);
                  echo !empty($user->bio) ? htmlspecialchars($user->bio) : '—';
                ?>
              </div>
            </div>

            <div class="mb-3">
              <h6 class="mb-2">Share</h6>
              <div class="d-flex gap-2">
                <a href="https://www.facebook.com/sharer/sharer.php?u=<?php echo urlencode($base_url.'/jobs/job-single.php?id='.$row->id); ?>&quote=<?php echo urlencode($row->job_title); ?>" class="btn btn-fb btn-sm">Facebook</a>
                <a href="https://twitter.com/intent/tweet?text=<?php echo urlencode($row->job_title); ?>&url=<?php echo urlencode($base_url.'/jobs/job-single.php?id='.$row->id); ?>" class="btn btn-dark btn-sm">X</a>
                <a href="https://www.linkedin.com/sharing/share-offsite/?url=<?php echo urlencode($base_url.'/jobs/job-single.php?id='.$row->id); ?>" class="btn btn-linkedin btn-sm">LinkedIn</a>
              </div>
            </div>

            <div>
              <h6 class="mb-2">Categories</h6>
              <ul class="list-unstyled mb-0">
                <?php foreach($allCategories as $category): ?>
                  <li class="mb-1">
                    <a target="_blank" href="<?php echo $base_url; ?>/categories/show-jobs.php?name=<?php echo urlencode($category->name); ?>">
                      <?php echo ucfirst(htmlspecialchars($category->name)); ?>
                    </a>
                  </li>
                <?php endforeach; ?>
              </ul>
            </div>
          </div>

          <?php if (isset($_SESSION['username'], $_SESSION['type']) && $_SESSION['type']==="Employer" && $_SESSION['id']==$row->company_id): ?>
          <div class="row mt-3">
            <div class="col-12 col-md-6 mb-2 mb-md-0 pr-md-2">
              <a href="<?php echo $base_url; ?>/jobs/job-update.php?id=<?php echo $row->id; ?>" class="btn btn-warning btn-block">Update</a>
            </div>
            <div class="col-12 col-md-6 pl-md-2">
              <a href="<?php echo $base_url; ?>/jobs/job-delete.php?id=<?php echo $row->id; ?>" class="btn btn-danger btn-block">Delete</a>
            </div>
          </div>
        <?php endif; ?>


        </aside>
      </div>
    </div>

    <!-- Footer stats + CTA unchanged -->
    <div class="jd-stats jd-stats--footer">
      <span><i class="fas fa-eye"></i> <?php echo (int)$row->view_count; ?> views</span>
      <span><i class="fas fa-users"></i> <?php echo (int)$appCount; ?> applicants</span>
    </div>
  </div>
</section>

<section class="site-section" id="next">
  <div class="container">
    <div class="row mb-5 justify-content-center">
      <div class="col-md-7 text-center">
        <h2 class="mb-2"><?php echo (int)$job_num->job_count; ?> Similar Jobs</h2>
      </div>
    </div>
    <ul class="sj-list">
      <?php foreach ($related_job as $job) : ?>
        <?php
          $userImage = 'default-image.png';
          $stmt = $conn->prepare("SELECT img FROM users WHERE id = :company_id");
          $stmt->bindParam(':company_id', $job->company_id);
          $stmt->execute();
          $result = $stmt->fetch(PDO::FETCH_OBJ);
          if ($result && !empty($result->img)) $userImage = $result->img;

          $postedTs   = strtotime($job->created_at);
          $deadlineTs = strtotime($job->application_deadline);
          $nowTs      = time();
          $daysLeft   = max(0, ceil(($deadlineTs - $nowTs) / 86400));

          $typeChip = 'sj-chip--primary';
          if     ($job->job_type == 'Full Time')   $typeChip = 'sj-chip--success';
          elseif ($job->job_type == 'Part Time')   $typeChip = 'sj-chip--danger';
          elseif ($job->job_type == 'Contract')    $typeChip = 'sj-chip--info';
          elseif ($job->job_type == 'Casual')      $typeChip = 'sj-chip--secondary';

          $dueClass = $daysLeft <= 3 ? 'sj-due--danger'
                    : ($daysLeft <= 10 ? 'sj-due--warn' : 'sj-due--ok');

          $totalWindow = max(1, $deadlineTs - $postedTs);
          $elapsed     = max(0, min($totalWindow, $nowTs - $postedTs));
          $pct         = round(($elapsed / $totalWindow) * 100);
        ?>
        <li class="sj-card sj-lg">
          <a class="sj-link" href="<?php echo $base_url; ?>/jobs/job-single.php?id=<?php echo (int)$job->id; ?>">
            <span class="sj-accent" aria-hidden="true"></span>
            <div class="sj-media">
              <img class="sj-logo" src="../users/user-images/<?php echo htmlspecialchars($userImage); ?>" alt="logo" loading="lazy"/>
            </div>
            <div class="sj-body">
              <div class="sj-title-row">
                <h3 class="sj-title"><?php echo htmlspecialchars($job->job_title); ?></h3>
                <span class="sj-chip <?php echo $typeChip; ?>"><?php echo htmlspecialchars($job->job_type); ?></span>
              </div>
              <div class="sj-meta">
                <span class="sj-company"><?php echo htmlspecialchars($job->company_name); ?></span>
                <span class="sj-sep">•</span>
                <span class="sj-loc"><i class="icon-room"></i> <?php echo htmlspecialchars($job->job_region); ?></span>
              </div>
              <div class="sj-sub"><span class="posted">Posted: <?php echo date('M,d Y', $postedTs); ?></span></div>
            </div>
            <div class="sj-aside">
              <span class="sj-chip sj-due <?php echo $dueClass; ?>">
                <?php echo $daysLeft > 0 ? $daysLeft . ' day' . ($daysLeft>1?'s':'') . ' left' : 'Closed'; ?>
              </span>
              <small class="sj-deadline"><i class="fa fa-clock"></i> Apply before: <?php echo date('M,d Y', $deadlineTs); ?></small>
              <div class="sj-track" aria-hidden="true"><div class="sj-fill" style="width: <?php echo $pct; ?>%;"></div></div>
            </div>
          </a>
        </li>
      <?php endforeach; ?>
    </ul>
  </div>
</section>

<!-- ============================================================
     APPLY WIZARD MODAL
     ============================================================ -->
<div class="modal fade" id="applyWizardModal" tabindex="-1" role="dialog" aria-labelledby="applyWizardLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg" role="document">
    <form id="applyWizardForm" enctype="multipart/form-data">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="applyWizardLabel">Apply to "<?php echo htmlspecialchars($row->job_title); ?>"</h5>
          <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span>&times;</span></button>
        </div>

        <div class="modal-body">
          <!-- Stepper -->
          <ul class="nav nav-pills mb-3" id="wizardSteps">
            <li class="nav-item"><a class="nav-link active" data-step="1" href="#">1. Resume</a></li>
            <li class="nav-item"><a class="nav-link disabled" data-step="2" href="#">2. Questions</a></li>
            <li class="nav-item"><a class="nav-link disabled" data-step="3" href="#">3. Review</a></li>
            <li class="nav-item"><a class="nav-link disabled" data-step="4" href="#">4. Done</a></li>
          </ul>

          <!-- Step 1 -->
          <div class="wizard-step" data-step="1">
            <div class="form-group">
              <div id="resumeListWrap" class="mb-3" style="display:none;">
                <label>Select Existing CV</label>
                <div id="resumeList"></div>
              </div>
            </div>

            <div class="form-group mt-3">
              <label class="mb-1">Or upload a new resume (PDF/DOC/DOCX)</label>
              <div class="form-row">
                <div class="col-md-8 mb-2">
                  <input type="file" name="resume_file" id="resume_file" class="form-control">
                </div>
                <div class="col-md-4">
                  <input type="text" name="resume_label" id="resume_label" class="form-control" placeholder="Optional label">
                </div>
              </div>
              <button type="button" id="uploadResumeBtn" class="btn btn-sm btn-outline-primary mt-2">Upload & Use</button>
              <div id="resumeUploadMsg" class="small mt-1 text-muted"></div>
            </div>
          </div>

          <!-- Step 2 -->
          <div class="wizard-step d-none" data-step="2">
            <div id="questionsArea">
              <div class="text-muted">Loading questions…</div>
            </div>
          </div>

          <!-- Step 3 -->
          <div class="wizard-step d-none" data-step="3">
            <h6>Review your application</h6>
            <div class="border rounded p-2 mb-3">
              <div><strong>Resume:</strong> <span id="reviewResume"></span></div>
            </div>
            <div class="border rounded p-2">
              <strong>Answers</strong>
              <div id="reviewAnswers" class="mt-2"></div>
            </div>
          </div>

          <!-- Step 4 -->
          <div class="wizard-step d-none text-center" data-step="4">
            <div class="my-3">
              <h5 class="text-success"><i class="fa fa-check-circle"></i> Application Submitted</h5>
              <p class="text-muted mb-1">
                Thank you for applying. Your application has been successfully received.
                Our team (or the employer) will review your resume and responses. If your profile matches the role, you may be contacted for the next steps.
              </p>
              <p class="text-muted">In the meantime, feel free to explore other opportunities that align with your interests and experience.</p>
            </div>
            <div class="d-flex justify-content-center gap-2">
              <a id="viewAppBtn" href="#" class="btn btn-outline-secondary mr-2">View Application</a>
              <a href="<?php echo $base_url; ?>/findjobs.php" class="btn btn-primary">Find Similar Jobs</a>
            </div>
          </div>
        </div>

        <div class="modal-footer">
          <small class="text-muted mr-auto" id="wizardHint"></small>
          <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
          <button type="button" class="btn btn-outline-primary d-none" id="prevStepBtn">Back</button>
          <button type="button" class="btn btn-primary" id="nextStepBtn">Next</button>
          <button type="button" class="btn btn-success d-none" id="submitAppBtn">Submit Application</button>
        </div>

        <!-- hidden context -->
        <input type="hidden" id="job_id" value="<?php echo (int)$row->id; ?>">
        <input type="hidden" id="company_id" value="<?php echo (int)$row->company_id; ?>">
        <input type="hidden" id="job_title" value="<?php echo htmlspecialchars($row->job_title, ENT_QUOTES, 'UTF-8'); ?>">
        <input type="hidden" id="chosen_resume_id" value="">
        <input type="hidden" id="answers_meta" value="">
      </div>
    </form>
  </div>
</div>

<script>
(function(w, $){
  w.AppModal = {
    show(sel){
      const el = document.querySelector(sel);
      if (!el) return;
      if ($ && $.fn && $.fn.modal) { $(sel).modal('show'); return; }
      if (w.bootstrap && w.bootstrap.Modal) { new bootstrap.Modal(el).show(); return; }
      el.classList.add('show'); el.style.display='block';
      document.body.classList.add('modal-open');
      const bd = document.createElement('div');
      bd.className = 'modal-backdrop fade show';
      bd.id = 'modal-bd-'+el.id;
      document.body.appendChild(bd);
    },
    hide(sel){
      const el = document.querySelector(sel);
      if (!el) return;
      if ($ && $.fn && $.fn.modal) { $(sel).modal('hide'); return; }
      if (w.bootstrap && w.bootstrap.Modal) {
        const inst = bootstrap.Modal.getInstance(el) || new bootstrap.Modal(el);
        inst.hide(); return;
      }
      el.classList.remove('show'); el.style.display='none';
      document.body.classList.remove('modal-open');
      const bd = document.getElementById('modal-bd-'+el.id);
      if (bd) bd.remove();
    }
  };
})(window, window.jQuery);
</script>

<script>
(function($){
  var currentStep = 1;
  var jobId       = $('#job_id').val();
  var companyId   = $('#company_id').val();
  var jobTitle    = $('#job_title').val();
  var chosenResumeId = ''; // numeric resume id

  function setStep(s){
    currentStep = s;
    $('.wizard-step').addClass('d-none');
    $('.wizard-step[data-step="'+s+'"]').removeClass('d-none');
    $('#wizardSteps .nav-link').addClass('disabled').removeClass('active');
    $('#wizardSteps .nav-link[data-step="'+s+'"]').removeClass('disabled').addClass('active');

    $('#prevStepBtn').toggleClass('d-none', s===1 || s===4);
    $('#nextStepBtn').toggleClass('d-none', s===3 || s===4);
    $('#submitAppBtn').toggleClass('d-none', s!==3);

    var hints = {
      1: 'Pick a resume or upload a new one.',
      2: 'Answer job-specific questions.',
      3: 'Double-check everything before submitting.',
      4: 'All done!'
    };
    $('#wizardHint').text(hints[s] || '');
  }

  // Load resumes (RESUMES TABLE ONLY)
  function loadResumes() {
    $('#resumeList').empty();
    $('#resumeListWrap').hide();

    $.post('job-single.php?id=' + jobId, { ajax:1, action:'list_resumes' }, function(res){
      if (!res || !res.success) return;

      var list = res.resumes || [];
      if (!list.length) return;

      var html = '';
      list.forEach(function(it){
        var val = String(it.id);
        var rid = 'res_' + val;
        var nice = (it.label ? it.label + ' — ' : '') + (it.filename || '');
        html += '<div class="custom-control custom-radio mb-1">'
             +    '<input type="radio" class="custom-control-input" '
             +           'name="resume_pick" id="'+rid+'" value="'+val+'" '
             +           'data-label="'+$('<div>').text(nice).html()+'">'
             +    '<label class="custom-control-label" for="'+rid+'">'+$('<div>').text(nice).html()+'</label>'
             +  '</div>';
      });

      $('#resumeList').html(html);
      $('#resumeListWrap').show();
      $('input[name="resume_pick"]').first().prop('checked', true);
    }, 'json');
  }

  function loadQuestions(){
    $('#questionsArea').html('<div class="text-muted">Loading questions…</div>');
    $.post('job-single.php?id='+jobId, { ajax:1, action:'load_questions', job_id: jobId }, function(res){
      if(!res.success){ $('#questionsArea').html('<div class="text-danger">Failed to load questions.</div>'); return; }
      var qs = res.questions || [];
      if(!qs.length){
        $('#questionsArea').html('<div class="alert alert-info">No additional questions for this job. Click Next to continue.</div>');
        $('#answers_meta').val('[]');
        return;
      }
      var html = '';
      var meta = [];
      qs.forEach(function(q){
        meta.push({id:q.id, qtype:q.qtype, required: q.is_required ? 1:0});
        html += '<div class="form-group">';
        html +=   '<label>' + $('<div>').text(q.question_text).html() + (q.is_required? ' <span class="text-danger">*</span>':'') + '</label>';

        var name = 'q_'+q.id;
        if (q.qtype === 'text') {
          html += '<input type="text" name="'+name+'" class="form-control" '+(q.is_required?'required':'')+'>';
        } else if (q.qtype === 'textarea') {
          html += '<textarea name="'+name+'" rows="4" class="form-control" '+(q.is_required?'required':'')+'></textarea>';
        } else if (q.qtype === 'yesno') {
          html += '<div class="d-flex align-items-center mt-1">';
          html +=   '<div class="custom-control custom-radio mr-3"><input class="custom-control-input" type="radio" name="'+name+'" id="'+name+'_y" value="Yes" '+(q.is_required?'required':'')+'><label class="custom-control-label" for="'+name+'_y">Yes</label></div>';
          html +=   '<div class="custom-control custom-radio"><input class="custom-control-input" type="radio" name="'+name+'" id="'+name+'_n" value="No" '+(q.is_required?'required':'')+'><label class="custom-control-label" for="'+name+'_n">No</label></div>';
          html += '</div>';
        } else if (q.qtype === 'dropdown') {
          html += '<select name="'+name+'" class="form-control" '+(q.is_required?'required':'')+'>';
          html += '<option value="">-- Select --</option>';
          q.options.forEach(function(op){ html += '<option>'+ $('<div>').text(op).html() +'</option>'; });
          html += '</select>';
        } else if (q.qtype === 'mcq') {
          html += '<div class="mt-1">';
          q.options.forEach(function(op, i){
            var cid = name+'_'+i;
            html += '<div class="custom-control custom-checkbox">'
                 +    '<input class="custom-control-input" type="checkbox" name="'+name+'[]" id="'+cid+'" value="'+$('<div>').text(op).html()+'">'
                 +    '<label class="custom-control-label" for="'+cid+'">'+$('<div>').text(op).html()+'</label>'
                 +  '</div>';
          });
          html += '</div>';
        } else if (q.qtype === 'file') {
          html += '<input type="file" name="qfile_'+q.id+'" class="form-control-file" '+(q.is_required?'required':'')+'>';
          html += '<small class="text-muted d-block">Accepted: pdf, doc, docx, png, jpg</small>';
        } else {
          html += '<input type="text" name="'+name+'" class="form-control">';
        }

        html += '</div>';
      });
      $('#questionsArea').html(html);
      $('#answers_meta').val(JSON.stringify(meta));
    }, 'json');
  }

  function buildReview(){
    var picked = $('input[name="resume_pick"]:checked').val() || '';
    chosenResumeId = picked;
    var labelText = $('input[name="resume_pick"]:checked').data('label') || '(Unknown resume)';
    $('#reviewResume').text(labelText);

    var meta = JSON.parse($('#answers_meta').val() || '[]');
    var html = '';
    meta.forEach(function(m){
      var name = 'q_'+m.id;
      html += '<div class="mb-2">';
      var qLabel = $('[name="'+name+'"], [name="'+name+'[]"], [name="qfile_'+m.id+'"]').closest('.form-group').children('label').first().text() || ('Question #'+m.id);
      html += '<div><strong>'+ $('<div>').text(qLabel).html() +'</strong></div>';

      if (m.qtype === 'mcq') {
        var arr = [];
        $('[name="'+name+'[]"]:checked').each(function(){ arr.push($(this).val()); });
        html += '<div>' + (arr.length ? arr.join(', ') : '<span class="text-muted">No selection</span>') + '</div>';
      } else if (m.qtype === 'file') {
        var f = $('[name="qfile_'+m.id+'"]').val();
        html += '<div>' + (f ? f.split('\\').pop() : '<span class="text-muted">No file</span>') + '</div>';
      } else {
        var v = $('[name="'+name+'"]').val();
        html += '<div>' + (v ? $('<div>').text(v).html() : '<span class="text-muted">Empty</span>') + '</div>';
      }
      html += '</div>';
    });
    $('#reviewAnswers').html(html || '<div class="text-muted">No additional questions for this job.</div>');
  }

  // open wizard
  $('#openApplyWizardBtn').on('click', function(){
    AppModal.show('#applyWizardModal');
    setStep(1);
    loadResumes();
    loadQuestions();
  });
  $('#applyWizardModal [data-dismiss="modal"]').on('click', function(){
    AppModal.hide('#applyWizardModal');
  });

  // upload resume (then select it)
  $('#uploadResumeBtn').on('click', function(){
    var f = $('#resume_file')[0].files[0];
    if (!f) { $('#resumeUploadMsg').text('Please choose a file.'); return; }

    var fd = new FormData();
    fd.append('ajax', 1);
    fd.append('action','upload_resume');
    fd.append('resume_file', f);
    fd.append('label', $('#resume_label').val() || '');

    $('#resumeUploadMsg').text('Uploading…');

    $.ajax({
      url: 'job-single.php?id='+jobId,
      method: 'POST',
      data: fd, processData: false, contentType:false, dataType:'json'
    }).done(function(res){
      if (!res || !res.success || !res.resume) {
        $('#resumeUploadMsg').text(res && res.error ? res.error : 'Upload failed'); return;
      }
      $('#resumeUploadMsg').text('Uploaded.');

      // Prepend and select
      var it   = res.resume;
      var val  = String(it.id);
      var rid  = 'res_' + val;
      var nice = (it.label ? it.label + ' — ' : '') + (it.filename || '');

      if (!$('#resumeListWrap').is(':visible')) {
        loadResumes();
      } else {
        $('#resumeList').prepend(
          '<div class="custom-control custom-radio mb-1">'
          +  '<input type="radio" class="custom-control-input" name="resume_pick" id="'+rid+'" value="'+val+'" data-label="'+$('<div>').text(nice).html()+'">'
          +  '<label class="custom-control-label" for="'+rid+'">'+$('<div>').text(nice).html()+'</label>'
          +'</div>'
        );
        $('#'+rid).prop('checked', true);
      }
    }).fail(function(){
      $('#resumeUploadMsg').text('Upload failed');
    });
  });

  // navigation
  $('#nextStepBtn').on('click', function(){
    if (currentStep === 1) {
      var pick = $('input[name="resume_pick"]:checked').val();
      if(!pick){ alert('Please select a resume or upload one.'); return; }
      setStep(2);
    } else if (currentStep === 2) {
      var ok = true;
      $('.wizard-step[data-step="2"] [required]').each(function(){
        if ($(this).is(':file')) {
          if (!this.files || !this.files.length) { ok=false; return false; }
        } else if ($(this).is(':radio')) {
          var nm = $(this).attr('name');
          if ($('[name="'+nm+'"]:checked').length === 0) { ok=false; return false; }
        } else if (!$(this).val()) { ok=false; return false; }
      });
      if(!ok){ alert('Please fill all required answers.'); return; }
      buildReview();
      setStep(3);
    }
  });

  $('#prevStepBtn').on('click', function(){
    if (currentStep > 1) setStep(currentStep - 1);
  });

  // submit
  $('#submitAppBtn').on('click', function(){
    var pick = $('input[name="resume_pick"]:checked').val();
    if(!pick){ alert('Please select a resume.'); return; }

    var fd = new FormData(document.getElementById('applyWizardForm'));
    fd.append('ajax', 1);
    fd.append('action', 'submit_application');
    fd.append('job_id', jobId);
    fd.append('company_id', companyId);
    fd.append('job_title', jobTitle);
    fd.append('resume_id', pick);                          
    fd.append('answers_meta', $('#answers_meta').val() || '[]');

    $('#submitAppBtn').prop('disabled', true).text('Submitting…');

    $.ajax({
      url: 'job-single.php?id='+jobId,
      method: 'POST',
      data: fd, processData:false, contentType:false, dataType:'json'
    }).done(function(res){
      if(res && res.success){
        $('#viewAppBtn').attr('href', '<?php echo $base_url; ?>/users/applied_jobs.php?id=<?php echo $_SESSION['id']; ?>"');
        setStep(4);
      } else {
        alert(res && res.error ? res.error : 'Submission failed.');
      }
    }).fail(function(){
      alert('Network error while submitting.');
    }).always(function(){
      $('#submitAppBtn').prop('disabled', false).text('Submit Application');
    });
  });

})(jQuery);
</script>

<?php require "../includes/footer.php"; ?>
