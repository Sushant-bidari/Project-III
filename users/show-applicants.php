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
// employer gate (shared)
if (!isset($_SESSION['type']) || $_SESSION['type'] !== "Employer") {
  header("location: " . APPURL);
  exit;
}
$employerId = (int)($_SESSION['id'] ?? 0);

// CSRF helper
if (empty($_SESSION['csrf_token'])) {
  $_SESSION['csrf_token'] = bin2hex(random_bytes(16));
}

// ---------- AJAX ----------
if (!empty($_POST['ajax']) && isset($_POST['action'])) {
  header('Content-Type: application/json');

  if (!$employerId) { echo json_encode(['ok'=>false,'error'=>'Not authorized']); exit; }

  $action = $_POST['action'];

  // get_app
  if ($action === 'get_app') {
    $appId = (int)($_POST['app_id'] ?? 0);
    if (!$appId) { echo json_encode(['ok'=>false,'error'=>'Missing app id']); exit; }

    $sql = "
      SELECT a.id AS app_id,
             a.job_id, a.job_title, a.created_at AS applied_at,
             a.application_status, a.withdrawn_at,
             a.cv AS app_cv,
             u.id AS user_id, u.fullname, u.email, u.contact, u.img, u.title, u.bio, u.skills, u.education
      FROM job_applications a
      JOIN users u ON u.id = a.worker_id
      WHERE a.id = :aid AND a.company_id = :cid
      LIMIT 1
    ";
    $st = $conn->prepare($sql);
    $st->execute([':aid'=>$appId, ':cid'=>$employerId]);
    $app = $st->fetch(PDO::FETCH_ASSOC);
    if (!$app) { echo json_encode(['ok'=>false,'error'=>'Application not found']); exit; }

    $pr = $conn->prepare("
      SELECT filename, label, original_name, created_at
      FROM resumes
      WHERE user_id = :uid
      ORDER BY is_primary DESC, created_at DESC, id DESC
      LIMIT 1
    ");
    $pr->execute([':uid'=>$app['user_id']]);
    $primary = $pr->fetch(PDO::FETCH_ASSOC);

    $ans = $conn->prepare("
      SELECT qa.id, qa.answer_text, qa.answer_file,
             q.question_text, q.qtype, q.is_required
      FROM job_app_answers qa
      JOIN job_questions q ON q.id = qa.question_id
      WHERE qa.application_id = :aid
      ORDER BY q.sort_order, q.id
    ");
    $ans->execute([':aid'=>$appId]);
    $answers = $ans->fetchAll(PDO::FETCH_ASSOC);

    $av = $conn->prepare("SELECT monday,tuesday,wednesday,thursday,friday,saturday,sunday
                          FROM availability WHERE user_id = :u LIMIT 1");
    $av->execute([':u'=>$app['user_id']]);
    $availability = $av->fetch(PDO::FETCH_ASSOC) ?: [];

    echo json_encode([
      'ok' => true,
      'app' => $app,
      'primary_resume' => $primary,
      'answers' => $answers,
      'availability' => $availability,
      'csrf' => $_SESSION['csrf_token'],
      'file_roots' => [
        'resumes'      => $base_url . '/users/user-cvs/',
        'answer_files' => $base_url . '/jobs/job-app-files/'
      ]
    ]);
    exit;
  }

  // update_status
  if ($action === 'update_status') {
    if (empty($_POST['csrf']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf'])) {
      echo json_encode(['ok'=>false,'error'=>'CSRF']); exit;
    }
    $appId = (int)($_POST['app_id'] ?? 0);
    $new   = (string)($_POST['status'] ?? '');
    if (!$appId || !in_array($new, ['0','1','2','3','4'], true)) {
      echo json_encode(['ok'=>false,'error'=>'Invalid input']); exit;
    }

    $chk = $conn->prepare("SELECT 1 FROM job_applications WHERE id=:id AND company_id=:cid");
    $chk->execute([':id'=>$appId, ':cid'=>$employerId]);
    if (!$chk->fetchColumn()) { echo json_encode(['ok'=>false,'error'=>'Not found']); exit; }

    if ($new === '4') {
      $upd = $conn->prepare("UPDATE job_applications
                             SET application_status = 4, withdrawn_at = NOW()
                             WHERE id = :id");
      $upd->execute([':id'=>$appId]);
    } else {
      $upd = $conn->prepare("UPDATE job_applications
                             SET application_status = :st
                             WHERE id = :id");
      $upd->execute([':st'=>$new, ':id'=>$appId]);
    }

    // notify job seeker
    try {
      $meta = $conn->prepare("
        SELECT a.worker_id, a.job_id, a.job_title,
               COALESCE(j.company_name, '') AS company_name
        FROM job_applications a
        LEFT JOIN jobs j ON j.id = a.job_id
        WHERE a.id = :id AND a.company_id = :cid
        LIMIT 1
      ");
      $meta->execute([':id' => $appId, ':cid' => $employerId]);

      if ($m = $meta->fetch(PDO::FETCH_ASSOC)) {
        $jobTitle    = trim($m['job_title'] ?? '');
        $companyName = trim($m['company_name'] ?? '');

      
        $friendlyTitle = [
          '0' => 'Application Received',
          '1' => 'In Review',
          '2' => 'Shortlisted',
          '3' => 'Not Selected',
          '4' => 'Withdrawn',
        ][$new] ?? 'Application Update';

        // Message body per status
        switch ($new) {
          case '0':
            $message = "Your application for “{$jobTitle}” at {$companyName} has been successfully received. The employer has been notified and may review your application soon.";
            break;

          case '1': 
            $message = "Your application for “{$jobTitle}” at {$companyName} is currently under review. The employer is assessing applications and will contact shortlisted candidates.";
            break;

          case '2': 
            $message = "Great news! You’ve been shortlisted for “{$jobTitle}” at {$companyName}. The employer may reach out soon with next steps.";
            break;

          case '3': 
            $message = "Your application for “{$jobTitle}” at {$companyName} was not successful this time. We appreciate your interest and the time you invested—please keep an eye on new roles that match your skills, and feel free to apply again.";
            break;

          case '4': 
            $message = "Your application for “{$jobTitle}” at {$companyName} has been marked as withdrawn.";
            break;

          default:
            $message = "Your application for “{$jobTitle}” at {$companyName} has been updated.";
        }

        // Title + link
        $title = "Application update: {$friendlyTitle}";
        
        $link  = APPURL . '/users/applied_jobs.php';

        $ins = $conn->prepare("
          INSERT INTO notifications
            (recipient_user_id, actor_user_id, type, job_id, application_id, title, message, link_path)
          VALUES
            (:rid, :aid, 'app_status_update', :job, :app, :title, :msg, :link)
        ");
        $ins->execute([
          ':rid'  => (int)$m['worker_id'],
          ':aid'  => $employerId,
          ':job'  => (int)$m['job_id'],
          ':app'  => $appId,
          ':title'=> $title,
          ':msg'  => $message,
          ':link' => $link
        ]);
      }
    } catch (Throwable $e) { /* ignore notify errors */ }

    echo json_encode(['ok'=>true]); exit;
  }

  echo json_encode(['ok'=>false,'error'=>'Unknown action']); exit;
}

// ---------- PAGE DATA ----------
$id = (int)($_GET['id'] ?? 0);
if ($id !== $employerId) { header('Location: '.APPURL); exit; }

$limit  = 10;
$page   = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $limit;

/* Job filter (optional) */
$jobFilter = '';
$job_id = null;
if (isset($_GET['job_id']) && is_numeric($_GET['job_id'])) {
  $job_id = (int)$_GET['job_id'];
  $jobFilter = " AND a.job_id = :job_id ";
}

/* which tab are we on???? */
$tab = isset($_GET['tab']) ? strtolower(trim($_GET['tab'])) : 'active';
$validTabs = ['active','review','shortlisted','rejected','all'];
if (!in_array($tab, $validTabs, true)) $tab = 'active';

/* Counts per status for badges */
$cntSql = "
  SELECT a.application_status, COUNT(*) AS c
  FROM job_applications a
  WHERE a.company_id = :company_id {$jobFilter}
  GROUP BY a.application_status
";
$cntStmt = $conn->prepare($cntSql);
$cntStmt->bindValue(':company_id', $employerId, PDO::PARAM_INT);
if ($job_id) $cntStmt->bindValue(':job_id', $job_id, PDO::PARAM_INT);
$cntStmt->execute();
$raw = $cntStmt->fetchAll(PDO::FETCH_KEY_PAIR) ?: [];

$counts = ['0'=>0,'1'=>0,'2'=>0,'3'=>0,'4'=>0];
foreach ($raw as $k=>$v) { $counts[(string)$k] = (int)$v; }

$activeCount            = $counts['0'] + $counts['1'] + $counts['2']; 
$inReviewCount          = $counts['1'];
$shortlistedCount       = $counts['2'];
$rejectedArchivedCount  = $counts['3'] + $counts['4']; // Rejected + Withdrawn
$allCount               = array_sum($counts);

/* Status WHERE by tab */
$statusWhere = '';
switch ($tab) {
  case 'active':     $statusWhere = " AND a.application_status IN (0,1,2) "; break;
  case 'review':     $statusWhere = " AND a.application_status = 1 ";        break;
  case 'shortlisted':$statusWhere = " AND a.application_status = 2 ";        break;
  case 'rejected':   $statusWhere = " AND a.application_status IN (3,4) ";   break;
  case 'all':        $statusWhere = "";                                      break;
}

/* Optional job title (for subtitle) */
$jobTitle = '';
if ($job_id) {
  $jt = $conn->prepare("SELECT job_title FROM jobs WHERE id = :jid AND company_id = :cid");
  $jt->execute([':jid'=>$job_id, ':cid'=>$employerId]);
  if ($rowJT = $jt->fetch(PDO::FETCH_OBJ)) $jobTitle = $rowJT->job_title;
}

/* Total rows for current tab */
$countSql = "SELECT COUNT(*) 
             FROM job_applications a 
             WHERE a.company_id = :company_id {$jobFilter} {$statusWhere}";
$countStmt = $conn->prepare($countSql);
$countStmt->bindValue(':company_id', $employerId, PDO::PARAM_INT);
if ($job_id) $countStmt->bindValue(':job_id', $job_id, PDO::PARAM_INT);
$countStmt->execute();
$totalApplicants = (int)$countStmt->fetchColumn();
$totalPages = max(1, ceil($totalApplicants / $limit));

/* Page data for current tab */
$sql = "
  SELECT a.id AS app_id, a.job_id, a.job_title, a.created_at AS applied_at,
         a.application_status, a.withdrawn_at,
         a.cv AS app_cv,
         u.id AS user_id, u.fullname, u.email, u.contact, u.img, u.title,
         pr.filename AS primary_cv
  FROM job_applications a
  JOIN users u ON u.id = a.worker_id
  LEFT JOIN (
    SELECT r1.user_id, r1.filename
    FROM resumes r1
    JOIN (
      SELECT user_id,
             MAX(CASE WHEN is_primary=1 THEN id ELSE 0 END) AS pid,
             MAX(id) AS maxid
      FROM resumes
      GROUP BY user_id
    ) pick ON pick.user_id = r1.user_id
    WHERE r1.id = CASE WHEN pick.pid>0 THEN pick.pid ELSE pick.maxid END
  ) pr ON pr.user_id = u.id
  WHERE a.company_id = :company_id
  {$jobFilter}
  {$statusWhere}
  ORDER BY a.created_at DESC
  LIMIT :limit OFFSET :offset
";
$getApplicants = $conn->prepare($sql);
$getApplicants->bindValue(':company_id', $employerId, PDO::PARAM_INT);
if ($job_id) $getApplicants->bindValue(':job_id', $job_id, PDO::PARAM_INT);
$getApplicants->bindValue(':limit',  $limit,  PDO::PARAM_INT);
$getApplicants->bindValue(':offset', $offset, PDO::PARAM_INT);
$getApplicants->execute();
$getApplicant = $getApplicants->fetchAll(PDO::FETCH_OBJ);

//count pending applications
$pendingStmt = $conn->prepare("SELECT COUNT(*) FROM job_applications WHERE company_id = :cid AND application_status = 0");
$pendingStmt->execute([':cid'=>$employerId]);
$pendingApplications = (int)$pendingStmt->fetchColumn();

require "../includes/header.php";
?>
<style>
  .emp-wrap { padding-top: 2rem; }
  .emp-sidebar {
    background: #fff; border: 0; border-radius: 14px;
    box-shadow: 0 8px 24px rgba(0,0,0,.06);
    position: sticky; top: 82px;
  }
  .emp-sidebar .list-group-item{
    border:0; border-left:3px solid transparent; border-radius: 0;
  }
  .emp-sidebar .list-group-item:hover{ background:#f8fafc; text-decoration: none;}
  .emp-sidebar .list-group-item.active{
    background:#eef2ff; color:#111827; border-left-color:#6366f1; font-weight:700; text-decoration: none;
  }
  .emp-card{
    background:#fff; border:0; border-radius:14px; box-shadow:0 8px 24px rgba(0,0,0,.06);
  }

  .av-week{
    display:grid;
    grid-template-columns:repeat(auto-fit, minmax(120px, 1fr));
    gap:10px;
  }
  .av-day{
    background:#fff;
    border:1px solid #e5e7eb;
    border-radius:12px;
    padding:10px;
    box-shadow:0 1px 4px rgba(0,0,0,.03);
  }
  .av-day .title{
    font-weight:600;
    font-size:.9rem;
    margin-bottom:6px;
    color:#111827;
  }
  .av-slots{
    display:grid;
    grid-template-columns:repeat(3,1fr);
    gap:6px;
  }
  .av-slot{
    border-radius:10px;
    border:1px dashed #d1d5db;
    padding:6px 4px;
    text-align:center;
    font-size:.78rem;
    color:#6b7280;
    user-select:none;
  }
  .av-slot.on{
    background:#eef2ff;
    border-color:#6366f1;
    color:#1f2937;
    font-weight:600;
  }
  .av-none{
    font-size:.85rem;
    color:#6b7280;
    padding-top:6px;
  }

</style>

<div class="container-fluid emp-wrap">
  <div class="row">
    <!-- Sidebar -->
    <aside class="col-lg-3 mb-4">
      <div class="emp-sidebar p-2">
        <div class="list-group list-group-flush">
          <a href="<?php echo $base_url; ?>/users/employer_dashboard.php" class="list-group-item">
            <i class="fa-solid fa-gauge mr-2"></i> Dashboard
          </a>
          <a href="<?php echo $base_url; ?>/jobs/post-job.php" class="list-group-item">
            <i class="fa-solid fa-plus mr-2"></i> Post a Job
          </a>
          <a href="<?php echo $base_url; ?>/users/show-applicants.php?id=<?php echo $employerId; ?>" class="list-group-item active">
            <i class="fa-solid fa-users-line mr-2"></i> Show Applicants
          </a>
          <a href="<?php echo $base_url; ?>/users/postedJobs.php?id=<?php echo $employerId; ?>" class="list-group-item">
            <i class="fa-solid fa-briefcase mr-2"></i> Posted Jobs
          </a>
          <a href="<?php echo $base_url; ?>/users/employer_insights.php" class="list-group-item">
            <i class="fa-solid fa-chart-line mr-2"></i> Insights
          </a>
        </div>
      </div>
    </aside>

    <!-- Main -->
    <main class="col-lg-9">
      <div class="emp-card p-3 mb-3 d-flex align-items-center justify-content-between">
        <div>
          <h4 class="mb-0">Job Applicants</h4>
          <?php if (!empty($jobTitle)): ?>
            <div class="small text-muted mt-1">
              Showing applicants for: <strong><?= htmlspecialchars($jobTitle) ?></strong>
              &nbsp;|&nbsp;
              <a href="show-applicants.php?id=<?= $_SESSION['id'] ?>">Show All</a>
            </div>
          <?php endif; ?>
        </div>
      </div>

      <div class="emp-card p-3">
        <?php if (!empty($_SESSION['success_message'])): ?>
          <div class="alert alert-success"><?php echo $_SESSION['success_message']; unset($_SESSION['success_message']); ?></div>
        <?php endif; ?>
        <?php if (!empty($_SESSION['error_message'])): ?>
          <div class="alert alert-danger"><?php echo $_SESSION['error_message']; unset($_SESSION['error_message']); ?></div>
        <?php endif; ?>
        <!-- AJAX flash spot -->
        <div id="ajaxFlash" class="mt-2"></div>
          <!-- Tabs / Buckets -->
        <ul class="nav nav-pills mb-3">
          <li class="nav-item">
            <a class="nav-link <?= $tab==='active'?'active':'' ?>"
              href="?id=<?= $id ?><?= $job_id ? '&job_id='.$job_id : '' ?>&tab=active">
              Active
              <span class="badge badge-light ml-1"><?= $activeCount ?></span>
            </a>
          </li>
          <li class="nav-item">
            <a class="nav-link <?= $tab==='review'?'active':'' ?>"
              href="?id=<?= $id ?><?= $job_id ? '&job_id='.$job_id : '' ?>&tab=review">
              In Review
              <span class="badge badge-light ml-1"><?= $inReviewCount ?></span>
            </a>
          </li>
          <li class="nav-item">
            <a class="nav-link <?= $tab==='shortlisted'?'active':'' ?>"
              href="?id=<?= $id ?><?= $job_id ? '&job_id='.$job_id : '' ?>&tab=shortlisted">
              Shortlisted
              <span class="badge badge-light ml-1"><?= $shortlistedCount ?></span>
            </a>
          </li>
          <li class="nav-item">
            <a class="nav-link <?= $tab==='rejected'?'active':'' ?>"
              href="?id=<?= $id ?><?= $job_id ? '&job_id='.$job_id : '' ?>&tab=rejected">
              Rejected / Archived
              <span class="badge badge-light ml-1"><?= $rejectedArchivedCount ?></span>
            </a>
          </li>
          <li class="nav-item">
            <a class="nav-link <?= $tab==='all'?'active':'' ?>"
              href="?id=<?= $id ?><?= $job_id ? '&job_id='.$job_id : '' ?>&tab=all">
              All
              <span class="badge badge-light ml-1"><?= $allCount ?></span>
            </a>
          </li>
        </ul>

        <div class="table-responsive">
          <table class="table table-striped table-hover table-bordered w-100">
            <thead>
              <tr>
                <th>Job Title</th>
                <th>Applicant</th>
                <th>Email</th>
                <th>Applied</th>
                <th>Resume</th>
                <th>Status</th>
                <th style="width: 210px;">Actions</th>
              </tr>
            </thead>
            <tbody>
              <?php if (!empty($getApplicant)) : ?>
                <?php foreach($getApplicant as $jobApp) : ?>
                  <tr>
                    <th>
                      <a style="text-decoration: none;" target="_blank" href="<?php echo $base_url; ?>/jobs/job-single.php?id=<?php echo $jobApp->job_id; ?>">
                        <?php echo htmlspecialchars($jobApp->job_title); ?>
                      </a>
                    </th>
                    <td><?php echo htmlspecialchars($jobApp->fullname); ?></td>
                    <td><?php echo htmlspecialchars($jobApp->email); ?></td>
                    <td><?php echo date('d M, Y', strtotime($jobApp->applied_at)); ?></td>
                    <td>
                      <?php $cvFile = $jobApp->app_cv ?: $jobApp->primary_cv; ?>
                      <a class="btn btn-outline-dark btn-sm <?php echo $cvFile ? '' : 'disabled'; ?>"
                         href="<?php echo $cvFile ? $base_url . '/users/user-cvs/' . htmlspecialchars($cvFile) : '#'; ?>"
                         <?php echo $cvFile ? 'download' : 'aria-disabled="true"'; ?>>
                        Download
                      </a>
                    </td>
                    <td>
                      <?php
                        $opts = ['0'=>'Pending','1'=>'Processing','2'=>'Approved','3'=>'Rejected','4'=>'Withdrawn'];
                      ?>
                      <select class="form-control form-control-sm js-status"
                              data-app="<?php echo (int)$jobApp->app_id; ?>">
                        <?php foreach ($opts as $v => $label): ?>
                          <option value="<?php echo $v; ?>" <?php echo ((string)$jobApp->application_status===(string)$v?'selected':''); ?>>
                            <?php echo $label; ?>
                          </option>
                        <?php endforeach; ?>
                      </select>
                    </td>
                    <td>
                      <a class="btn btn-primary btn-sm"
                         href="<?php echo $base_url; ?>/users/public-profile.php?id=<?php echo (int)$jobApp->user_id; ?>&app=<?php echo (int)$jobApp->app_id; ?>"
                         target="_blank">
                        View Profile
                      </a>
                      <button type="button"
                              class="btn btn-secondary btn-sm js-view-app"
                              data-app="<?php echo (int)$jobApp->app_id; ?>">
                        View Application
                      </button>
                    </td>
                  </tr>
                <?php endforeach; ?>
              <?php else : ?>
                <tr>
                  <td colspan="7" class="text-center py-4">
                    <i class="fas fa-user-slash fa-lg mb-2 d-block"></i>
                    No applicants found for this job.
                  </td>
                </tr>
              <?php endif; ?>
            </tbody>
          </table>
        </div>

        <?php if ($totalPages > 1): ?>
          <nav aria-label="Applicant pagination">
            <ul class="pagination justify-content-center mb-0">
              <?php if ($page > 1): ?>
              <li class="page-item">
                <a class="page-link"
                  href="?id=<?= $id ?><?= $job_id ? '&job_id='.$job_id : '' ?>&tab=<?= htmlspecialchars($tab) ?>&page=<?= $page - 1 ?>">&laquo; Prev</a>
              </li>
            <?php endif; ?>
            <?php for ($i = 1; $i <= $totalPages; $i++): ?>
              <li class="page-item <?= ($page == $i) ? 'active' : '' ?>">
                <a class="page-link"
                  href="?id=<?= $id ?><?= $job_id ? '&job_id='.$job_id : '' ?>&tab=<?= htmlspecialchars($tab) ?>&page=<?= $i ?>"><?= $i ?></a>
              </li>
            <?php endfor; ?>
            <?php if ($page < $totalPages): ?>
              <li class="page-item">
                <a class="page-link"
                  href="?id=<?= $id ?><?= $job_id ? '&job_id='.$job_id : '' ?>&tab=<?= htmlspecialchars($tab) ?>&page=<?= $page + 1 ?>">Next &raquo;</a>
              </li>
            <?php endif; ?>

            </ul>
          </nav>
        <?php endif; ?>
      </div>
    </main>
  </div>
</div>

<!-- Application Viewer -->
<div class="modal fade" id="appViewerModal" tabindex="-1" role="dialog" aria-labelledby="appViewerLabel" aria-hidden="true">
  <div class="modal-dialog modal-xl" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">
          Application <span id="av-job-title" class="text-muted small"></span>
        </h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span>&times;</span></button>
      </div>
      <div class="modal-body">
        <ul class="nav nav-tabs mb-3" role="tablist">
          <li class="nav-item"><a class="nav-link active" data-toggle="tab" href="#av-summary" role="tab">Summary</a></li>
          <li class="nav-item"><a class="nav-link" data-toggle="tab" href="#av-answers" role="tab">Answers</a></li>
          <li class="nav-item"><a class="nav-link" data-toggle="tab" href="#av-availability" role="tab">Availability</a></li>
        </ul>
        <div class="tab-content">
          <div class="tab-pane fade show active" id="av-summary" role="tabpanel">
            <div class="d-flex align-items-center mb-3">
              <img id="av-photo" src="" class="rounded mr-3" style="width:64px;height:64px;object-fit:cover;border:1px solid #eee;">
              <div>
                <div id="av-name" class="font-weight-bold"></div>
                <div class="text-muted small">
                  <span id="av-email"></span> • <span id="av-contact"></span>
                </div>
                <div class="text-muted small">Applied: <span id="av-applied"></span></div>
              </div>
              <div class="ml-auto">
                <a id="av-download" href="#" class="btn btn-outline-dark btn-sm" download>Download Resume</a>
              </div>
            </div>
            <div class="border rounded p-2">
              <div class="mb-2"><strong>Title:</strong> <span id="av-title"></span></div>
              <div class="mb-2"><strong>Skills:</strong> <span id="av-skills"></span></div>
              <div class="mb-0"><strong>Bio:</strong> <div id="av-bio" class="small text-muted"></div></div>
            </div>
          </div>
          <div class="tab-pane fade" id="av-answers" role="tabpanel">
            <div id="av-answers-wrap" class="border rounded p-2">
              <div class="text-muted">Loading…</div>
            </div>
          </div>
          <div class="tab-pane fade" id="av-availability" role="tabpanel">
          <div class="d-flex align-items-center av-legend mb-2">
            <span class="badge badge-light border mr-2">M</span>
            <small class="text-muted mr-3">Morning</small>
            <span class="badge badge-light border mr-2">A</span>
            <small class="text-muted mr-3">Afternoon</small>
            <span class="badge badge-light border mr-2">E</span>
            <small class="text-muted">Evening</small>
          </div>

          <div id="av-calendar" class="av-week" aria-live="polite" aria-label="Weekly availability calendar"></div>
        </div>

        </div>
      </div>
      <div class="modal-footer">
        <select class="form-control form-control-sm mr-auto" style="max-width:220px" id="av-status">
          <option value="0">Pending</option>
          <option value="1">Processing</option>
          <option value="2">Approved</option>
          <option value="3">Rejected</option>
          <option value="4">Withdrawn</option>
        </select>

        <button type="button" class="btn btn-primary" id="av-save">Save Status</button>
        <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
        <input type="hidden" id="av-app-id" value="">
        <input type="hidden" id="av-csrf" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
      </div>
    </div>
  </div>
</div>

<?php if (!empty($pendingApplications) && $pendingApplications > 0): ?>
<div class="toast show" role="alert" aria-live="assertive" aria-atomic="true" data-delay="35000"
     style="position: fixed; bottom: 20px; right: 20px; min-width: 250px; z-index: 9999;">
  <div class="toast-header bg-danger text-white">
    <strong class="mr-auto">Job Applications</strong>
    <small>Just now</small>
    <button type="button" class="ml-2 mb-1 close" data-dismiss="toast" aria-label="Close">
      <span aria-hidden="true">&times;</span>
    </button>
  </div>
  <div class="toast-body">
    You have <strong><?= $pendingApplications ?></strong> new job application<?= $pendingApplications > 1 ? 's' : '' ?> pending review.
  </div>
</div>
<?php endif; ?>

<script>
(function (w, $) {
  function getBsModal(el){
    if (w.bootstrap && w.bootstrap.Modal && w.bootstrap.Modal.getOrCreateInstance) {
      return w.bootstrap.Modal.getOrCreateInstance(el);
    }
    if (w.bootstrap && w.bootstrap.Modal) {
      var inst = w.bootstrap.Modal.getInstance ? w.bootstrap.Modal.getInstance(el) : null;
      return inst || new w.bootstrap.Modal(el);
    }
    return null;
  }
  w.AppModal = {
    show(sel){
      var el = document.querySelector(sel);
      if (!el) return;
      if ($ && $.fn && $.fn.modal) { $(sel).modal('show'); return; }
      var inst = getBsModal(el);
      if (inst) { inst.show(); return; }
      el.classList.add('show');
      el.style.display = 'block';
      document.body.classList.add('modal-open');
      var bd = document.createElement('div');
      bd.className = 'modal-backdrop fade show';
      bd.id = 'modal-bd-' + el.id;
      document.body.appendChild(bd);
    },
    hide(sel){
      var el = document.querySelector(sel);
      if (!el) return;
      if ($ && $.fn && $.fn.modal) { $(sel).modal('hide'); return; }
      var inst = getBsModal(el);
      if (inst) { inst.hide(); return; }
      el.classList.remove('show');
      el.style.display = 'none';
      document.body.classList.remove('modal-open');
      var bd = document.getElementById('modal-bd-' + el.id);
      if (bd) bd.remove();
    }
  };
})(window, window.jQuery);
</script>

<script>
(function($){
  function statusText(v){
    return ({
      '0':'Pending',
      '1':'Processing',
      '2':'Approved',
      '3':'Rejected',
      '4':'Withdrawn'
    })[String(v)] || 'Unknown';
  }

  // Bootstrap 4 flash helper
  function showFlash(type, text){
    var $host = $('#ajaxFlash');
    if (!$host.length){ $host = $('<div id="ajaxFlash" class="mt-2"></div>').appendTo('body'); }
    var id = 'af_' + Date.now() + '_' + Math.floor(Math.random()*1000);
    var $alert = $(
      '<div id="'+id+'" class="alert alert-'+type+' alert-dismissible fade show" role="alert" style="box-shadow:0 6px 18px rgba(0,0,0,.06)">'+
        $('<div>').text(text).html()+
        '<button type="button" class="close" data-dismiss="alert" aria-label="Close">'+
          '<span aria-hidden="true">&times;</span>'+
        '</button>'+
      '</div>'
    );
    $host.prepend($alert);
    // Auto dismiss after 5s
    setTimeout(function(){
      if ($.fn.alert){ $alert.alert('close'); } else { $alert.remove(); }
    }, 5000);
  }

  // Inline status change
  $(document).on('change', '.js-status', function(){
    var appId = $(this).data('app');
    var val   = $(this).val();
    var sel   = $(this);

    $.post('show-applicants.php?id=<?php echo (int)$id; ?>', {
      ajax:1, action:'update_status',
      app_id: appId, status: val, csrf: '<?php echo $_SESSION['csrf_token']; ?>'
    }, function(res){
      if(!res || !res.ok){
        showFlash('danger', (res && res.error) ? res.error : 'Update failed');
        return;
      }
      sel.addClass('border-success');
      setTimeout(function(){ sel.removeClass('border-success'); }, 1000);
      showFlash('success', "The requested application status has been updated to '"+ statusText(val) +"'.");
    }, 'json').fail(function(){
      showFlash('danger', 'Network error while updating status.');
    });
  });

  // View Application
  $(document).on('click', '.js-view-app', function(){
    var appId = $(this).data('app');
    AppModal.show('#appViewerModal');
    $('#av-answers-wrap').html('<div class="text-muted">Loading…</div>');

    $.post('show-applicants.php?id=<?php echo (int)$id; ?>', { ajax:1, action:'get_app', app_id: appId }, function(res){
      if(!res || !res.ok){ $('#av-answers-wrap').html('<div class="text-danger">Unable to load.</div>'); return; }

      var a  = res.app;
      var pr = res.primary_resume || {};
      var img = a.img ? ('<?php echo $base_url; ?>/users/user-images/'+a.img) : '<?php echo $base_url; ?>/images/user-placeholder.png';
      var cv  = a.app_cv ? a.app_cv : (pr.filename || '');
      var cvUrl = cv ? (res.file_roots.resumes + cv) : '#';

      $('#av-app-id').val(a.app_id);
      $('#av-csrf').val(res.csrf || '');
      $('#av-job-title').text('— ' + a.job_title);
      $('#av-photo').attr('src', img);
      $('#av-name').text(a.fullname);
      $('#av-email').text(a.email || '—');
      $('#av-contact').text(a.contact || '—');
      $('#av-title').text(a.title || '—');
      $('#av-skills').text(a.skills || '—');
      $('#av-bio').text(a.bio || '—');
      $('#av-applied').text(a.applied_at ? new Date(a.applied_at.replace(' ', 'T')).toLocaleDateString(undefined, {day:'2-digit', month:'short', year:'numeric'}) : '');
      $('#av-download').attr('href', cvUrl).toggleClass('disabled', !cv);

      $('#av-status').val(String(a.application_status));

      if (!res.answers || !res.answers.length){
        $('#av-answers-wrap').html('<div class="text-muted">No answers.</div>');
      } else {
        var html = '';
        res.answers.forEach(function(x){
          var q = $('<div>').text(x.question_text || '(Question)').html();
          var aHtml = '';
          if (x.answer_file){
            var url = res.file_roots.answer_files + x.answer_file;
            aHtml = '<a href="'+url+'" target="_blank" class="btn btn-sm btn-outline-dark">Download file</a>';
          } else if (x.answer_text){
            var t = x.answer_text;
            try { var arr = JSON.parse(t); if (Array.isArray(arr)) t = arr.join(', '); } catch(e){}
            aHtml = $('<div>').text(t).html();
          } else {
            aHtml = '<span class="text-muted">—</span>';
          }
          html += '<div class="mb-2"><div><strong>'+q+'</strong></div><div>'+aHtml+'</div></div>';
        });
        $('#av-answers-wrap').html(html);
      }

      // Availability grid
      function renderAvailabilityGrid(av){
        var days = [
          { key:'monday',    label:'Monday' },
          { key:'tuesday',   label:'Tuesday' },
          { key:'wednesday', label:'Wednesday' },
          { key:'thursday',  label:'Thursday' },
          { key:'friday',    label:'Friday' },
          { key:'saturday',  label:'Saturday' },
          { key:'sunday',    label:'Sunday' }
        ];
        var slotMap = { 'morning':['m'], 'afternoon':['a'], 'evening':['e'], 'wholeday':['m','a','e'], 'none':[] };
        function activeSlots(v){ if(!v) return []; v=String(v).toLowerCase(); return slotMap[v] || []; }

        var html = '';
        days.forEach(function(d){
          var on = activeSlots((res.availability||{})[d.key]);
          html += ''
            + '<div class="av-day">'
            +   '<div class="title">' + d.label + '</div>'
            +   '<div class="av-slots">'
            +     '<div class="av-slot ' + (on.indexOf('m')>-1?'on':'') + '" title="Morning">M</div>'
            +     '<div class="av-slot ' + (on.indexOf('a')>-1?'on':'') + '" title="Afternoon">A</div>'
            +     '<div class="av-slot ' + (on.indexOf('e')>-1?'on':'') + '" title="Evening">E</div>'
            +   '</div>'
            +   (on.length === 0 ? '<div class="av-none">Not Available</div>' : '')
            + '</div>';
        });
        $('#av-calendar').html(html);
      }
      renderAvailabilityGrid(res.availability || {});

    }, 'json').fail(function(){
      $('#av-answers-wrap').html('<div class="text-danger">Network error.</div>');
    });
  });

  // Save status from modal
  $('#av-save').on('click', function(){
    var appId = $('#av-app-id').val();
    var st    = $('#av-status').val();
    var csrf  = $('#av-csrf').val();

    $.post('show-applicants.php?id=<?php echo (int)$id; ?>', {
      ajax:1, action:'update_status', app_id: appId, status: st, csrf: csrf
    }, function(res){
      if(!res || !res.ok){
        showFlash('danger', (res && res.error) ? res.error : 'Update failed');
        return;
      }
      $('.js-status[data-app="'+appId+'"]').val(st);
      AppModal.hide('#appViewerModal');
      showFlash('success', "The requested application status has been updated to '"+ statusText(st) +"'.");
    }, 'json').fail(function(){
      showFlash('danger', 'Network error while updating status.');
    });
  });

})(jQuery);
</script>


<?php require "../includes/footer.php"; ?>
