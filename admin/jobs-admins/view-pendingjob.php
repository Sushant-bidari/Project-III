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
// --- Auth guard ---
if (!isset($_SESSION['adminname'])) {
  header("location: " . ADMINURL . "/admins/login-admins.php");
  exit;
}

// --- Validate ID ---
if (!isset($_GET['id']) || !ctype_digit($_GET['id'])) {
  header("location: " . ADMINURL . "/jobs-admins/pending-jobs.php");
  exit;
}
$job_id = (int)$_GET['id'];

// --- Page context for header ---
$pageTitle  = "Pending Job Details";
$breadcrumb = "Jobs";

// --- Fetch job (must be pending: status = 0) ---
// $stmt = $conn->prepare("SELECT * FROM jobs WHERE id = :id AND status = 0 LIMIT 1");
$stmt = $conn->prepare("SELECT * FROM jobs WHERE id = :id LIMIT 1");
$stmt->bindValue(':id', $job_id, PDO::PARAM_INT);
$stmt->execute();
$job = $stmt->fetch(PDO::FETCH_OBJ);

if (!$job) {
  require "../layouts/header.php";
  echo '<div class="alert alert-danger">Job not found or already verified.</div>';
  require "../layouts/footer.php";
  exit;
}

// --- Fetch job-specific application questions ---
$qstmt = $conn->prepare("
  SELECT id, source, bank_id, question_text, qtype, is_required, options, sort_order
  FROM job_questions
  WHERE job_id = :jid
  ORDER BY sort_order, id
");
$qstmt->execute([':jid' => $job_id]);
$questions = $qstmt->fetchAll(PDO::FETCH_OBJ);

// Helpers
function h($v){ return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }
function qtype_label($t) {
  $map = [
    'text'     => 'Text',
    'textarea' => 'Textarea',
    'yesno'    => 'Yes / No',
    'mcq'      => 'Multiple Choice',
    'dropdown' => 'Dropdown',
    'file'     => 'File Upload'
  ];
  return $map[$t] ?? ucfirst((string)$t);
}
function dmy($s, $fmt = 'j M, Y'){
  if (!$s) return '—';
  $ts = strtotime($s);
  return $ts ? date($fmt, $ts) : '—';
}

// Build site root for public assets (users/user-images)
// ADMINURL ends with /admin -> strip that off for site root
$siteRoot = preg_replace('#/admin/?$#', '', rtrim(ADMINURL, '/'));

require "../layouts/header.php";
?>

<style>
  .admin-rich ul { padding-left:1.2rem; }
  .admin-rich li { margin-bottom:.25rem; }
</style>

<div class="row">
  <div class="col-md-12">
    <div class="card shadow-sm">
      <div class="card-header bg-warning text-dark font-weight-bold d-flex align-items-center justify-content-between">
        <span><i class="fas fa-info-circle mr-2"></i> Pending Job Details</span>
        <span class="badge badge-light">Awaiting Approval</span>
      </div>

      <div class="card-body">
        <h4 class="mb-3"><?= h($job->job_title) ?></h4>

        <div class="row">
          <div class="col-md-6">
            <p><strong>Region:</strong> <?= h($job->job_region ?: '—') ?></p>
            <p><strong>Type:</strong> <?= h($job->job_type ?: '—') ?></p>
            <p><strong>Work Arrangement:</strong> <?= h($job->work_arrangement ?: '—') ?></p>
            <p><strong>Vacancy:</strong> <?= (int)($job->vacancy ?? 0) ?></p>
            <p><strong>Category:</strong> <?= h($job->job_category ?: '—') ?></p>
            <p><strong>Experience:</strong> <?= h($job->experience ?: '—') ?></p>
            <p><strong>Salary:</strong> <?= h($job->salary ?: '—') ?></p>
            <p><strong>Inclusivity Notes:</strong> <?= h($job->inclusivity_notes ?: '—') ?></p>
            <p><strong>Deadline:</strong> <?= dmy($job->application_deadline) ?></p>
          </div>
          <div class="col-md-6">
            <p><strong>Company:</strong> <?= h($job->company_name ?: '—') ?></p>
            <p><strong>Email:</strong> <?= h($job->company_email ?: '—') ?></p>
            <p><strong>Company ID:</strong> <?= (int)($job->company_id ?? 0) ?></p>
            <p><strong>Posted At:</strong> <?= dmy($job->created_at, 'j M, Y h:i A') ?></p>
            <div>
              <p class="mb-2"><strong>Company Image:</strong></p>
              <?php if (!empty($job->company_image)): ?>
                <img
                  src="<?= $siteRoot ?>/users/user-images/<?= h($job->company_image) ?>"
                  alt="Company image"
                  class="img-thumbnail"
                  style="max-width:150px;">
              <?php else: ?>
                <p class="text-muted mb-0">Company image/logo not available</p>
              <?php endif; ?>
            </div>
          </div>
        </div>

        <hr>

        <h5 class="mt-4">Job Description</h5>
        <div class="admin-rich">
          <?= html_entity_decode((string)($job->job_description ?? ''), ENT_QUOTES, 'UTF-8') ?>
        </div>

        <h5 class="mt-4">Responsibilities</h5>
        <div class="admin-rich">
          <?= html_entity_decode((string)($job->responsibilities ?? ''), ENT_QUOTES, 'UTF-8') ?>
        </div>

        <h5 class="mt-4">Education &amp; Experience</h5>
        <div class="admin-rich">
          <?= html_entity_decode((string)($job->education_experience ?? ''), ENT_QUOTES, 'UTF-8') ?>
        </div>

        <h5 class="mt-4">Other Benefits</h5>
        <div class="admin-rich">
          <?= html_entity_decode((string)($job->other_benefits ?? ''), ENT_QUOTES, 'UTF-8') ?>
        </div>

        <hr>

        <h5 class="mt-4 d-flex align-items-center">
          Application Questions
          <?php if (empty($questions)): ?>
            <span class="badge badge-secondary ml-2">None</span>
          <?php endif; ?>
        </h5>

        <?php if (!empty($questions)): ?>
          <div class="table-responsive">
            <table class="table table-sm table-bordered">
              <thead class="thead-light">
                <tr>
                  <th style="width:55%">Question</th>
                  <th style="width:15%">Type</th>
                  <th style="width:10%">Required</th>
                  <th style="width:20%">Options (if any)</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($questions as $q): ?>
                  <tr>
                    <td>
                      <?= h($q->question_text) ?>
                      <?php if ($q->source === 'predefined'): ?>
                        <span class="badge badge-info ml-2">Standard</span>
                      <?php else: ?>
                        <span class="badge badge-primary ml-2">Custom</span>
                      <?php endif; ?>
                    </td>
                    <td><span class="badge badge-light"><?= h(qtype_label($q->qtype)) ?></span></td>
                    <td>
                      <?php if ((int)$q->is_required === 1): ?>
                        <span class="badge badge-success">Yes</span>
                      <?php else: ?>
                        <span class="badge badge-secondary">No</span>
                      <?php endif; ?>
                    </td>
                    <td>
                      <?php
                        $opts = null;
                        if (!empty($q->options)) {
                          $opts = json_decode($q->options, true);
                        }
                        if (is_array($opts) && count($opts)) {
                          echo '<ul class="mb-0 pl-3">';
                          foreach ($opts as $op) {
                            echo '<li>' . h($op) . '</li>';
                          }
                          echo '</ul>';
                        } else {
                          echo '<span class="text-muted">—</span>';
                        }
                      ?>
                    </td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        <?php endif; ?>

        <div class="mt-4 d-flex justify-content-between">
          <a
    href="status-jobs.php?id=<?= (int)$job->id ?>&status=0&r=<?= urlencode('pending-jobs.php') ?>"
    class="btn btn-success">
    <i class="fa fa-check-circle"></i> Verify &amp; Approve
</a>


          <a href="pending-jobs.php" class="btn btn-secondary">
            <i class="fa fa-arrow-left"></i> Back to Pending Jobs
          </a>
        </div>
      </div>
    </div>
  </div>
</div>

<?php require "../layouts/footer.php"; ?>
