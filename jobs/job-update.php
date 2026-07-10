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
/* -----------------------------------------------------------
   Auth: Employers only
----------------------------------------------------------- */
if (!isset($_SESSION['type']) || $_SESSION['type'] !== "Employer") {
  header("Location: " . APPURL . "");
  exit();
}
$employerId = (int)($_SESSION['id'] ?? 0);

/* -----------------------------------------------------------
   Helpers (same as post-job.php; no type hints)
----------------------------------------------------------- */
function salary_symbol($code){
  $map = array('USD'=>'$', 'NZD'=>'$', 'AUD'=>'$', 'CAD'=>'$', 'EUR'=>'€', 'GBP'=>'£', 'INR'=>'₹', 'JPY'=>'¥');
  return isset($map[$code]) ? $map[$code] : '$';
}
function salary_unit($period){
  $p = strtolower(trim((string)$period));
  if ($p === 'hourly')  return 'per hour';
  if ($p === 'monthly') return 'per month';
  if ($p === 'yearly')  return 'annually';
  return '';
}
function salary_text($currency,$min,$max,$period){
  $min = is_numeric($min) ? (float)$min : null;
  $max = is_numeric($max) ? (float)$max : null;
  if ($min === null) return '';
  $sym   = salary_symbol($currency);
  $unit  = salary_unit($period);
  $fmt   = function($n){ return number_format($n, 0, '.', ','); };
  $core  = $sym.$fmt($min) . ($max && $max > 0 ? '–'.$sym.$fmt($max) : '');
  return trim($core.' '.$unit);
}

/* ---- Rich-text sanitizers (same as post-job) ---- */
function is_rich_empty($html){
  $norm = preg_replace('/\xC2\xA0/u', ' ', $html);
  $norm = preg_replace('#<(br|BR)\s*/?>#', '', $norm);
  return trim(strip_tags($norm)) === '';
}
function looks_like_html($s){
  return (bool)preg_match('/<\s*(?:[a-zA-Z][\w:-]*|\/[a-zA-Z][\w:-]*|!--)/', $s);
}
function strip_trailing_dangling_lt($s){
  $s = preg_replace('/\xC2\xA0/u', ' ', $s);
  return preg_replace('/(?:<|&lt;)\s*$/i', '', trim($s));
}
function plain_to_list($text){
  $text = preg_replace("/\r\n?/", "\n", $text);
  $parts = preg_split('/\n+/', $text);
  $lines = array();
  foreach ($parts as $p){
    $p = trim($p);
    if ($p !== '' && $p !== '<' && strcasecmp($p,'&lt;') !== 0) $lines[] = $p;
  }
  if (!$lines) return '';
  return "<ul>\n<li>" . implode("</li>\n<li>", $lines) . "</li>\n</ul>";
}
function plain_to_paragraphs($text){
  $text = preg_replace('/\xC2\xA0/u', ' ', $text);
  $text = preg_replace("/\r\n?/", "\n", $text);
  $text = preg_replace('/(?:<|&lt;)\s*$/i', '', trim($text));
  $rawParas = preg_split('/\n{2,}/', $text);
  $out = array();
  foreach ($rawParas as $p){
    $p = trim($p);
    if ($p==='') continue;
    $lines = preg_split('/\n+/', $p);
    $clean = array();
    foreach ($lines as $l){
      $l = trim($l);
      if ($l !== '' && $l !== '<' && strcasecmp($l,'&lt;') !== 0) $clean[] = $l;
    }
    if ($clean) $out[] = '<p>'.implode('<br>', $clean).'</p>';
  }
  return $out ? implode("\n", $out) : '';
}
function clean_ckeditor_html($html){
  if ($html === '') return '';
  $html = preg_replace('/\xC2\xA0/u', ' ', $html);
  $html = preg_replace('#<(script|style|iframe|object|embed|link|meta)\b[^>]*>.*?</\1>#is', '', $html);
  $html = preg_replace('#<li>(?:\s|&nbsp;|<br\s*/?>)*</li>#i', '', $html);
  $html = preg_replace('#<(p|div|h[1-6]|span)>(?:\s|&nbsp;|<br\s*/?>)*</\1>#i', '', $html);
  $html = preg_replace('#<(ul|ol)>\s*</\1>#i', '', $html);
  $html = preg_replace('#<li>(?:\s|&nbsp;|<br\s*/?>|&lt;)+</li>#i', '', $html);
  $html = preg_replace('#<(p|div|h[1-6]|span)>(?:\s|&nbsp;|<br\s*/?>|&lt;)+</\1>#i', '', $html);
  $html = preg_replace_callback('#<([a-z0-9]+)\b([^>]*)>#i', function ($m) {
    $tag = strtolower($m[1]); $attrs = $m[2];
    $attrs = preg_replace('/\s+(on\w+|style)\s*=\s*(["\']).*?\2/si', '', $attrs);
    $attrs = preg_replace('/\s+(href|src)\s*=\s*(["\'])\s*javascript:[^"\']*\2/si', '', $attrs);
    return "<{$tag}{$attrs}>";
  }, $html);
  $html = preg_replace('#(?:<br\s*/?>\s*){3,}#i', "<br><br>", $html);
  $html = preg_replace('#<(p|div)>(?:\s|&nbsp;|<br\s*/?>)*</\1>#i', '', $html);
  return trim($html);
}

/* -----------------------------------------------------------
   Load categories/regions, question bank, and job
----------------------------------------------------------- */
$get_categories = $conn->query("SELECT * FROM categories");
$get_categories->execute();
$get_category = $get_categories->fetchAll(PDO::FETCH_OBJ);

$regionsStmt = $conn->prepare("SELECT * FROM job_regions WHERE status = 1 ORDER BY name ASC");
$regionsStmt->execute();
$jobRegions = $regionsStmt->fetchAll(PDO::FETCH_OBJ);

$bankStmt = $conn->prepare("SELECT * FROM question_bank WHERE is_active=1 ORDER BY id ASC");
$bankStmt->execute();
$questionBank = $bankStmt->fetchAll(PDO::FETCH_OBJ);

if (!isset($_GET['id']) || !ctype_digit($_GET['id'])) {
  header("Location: " . APPURL . "/404.php");
  exit();
}
$id = (int)$_GET['id'];

$select = $conn->prepare("SELECT * FROM jobs WHERE id = :id LIMIT 1");
$select->execute(array(':id'=>$id));
$singleJob = $select->fetch(PDO::FETCH_OBJ);
if (!$singleJob) {
  header("Location: " . APPURL . "/404.php");
  exit();
}
if (!isset($_SESSION['id']) || (int)$singleJob->company_id !== (int)$_SESSION['id']) {
  header("Location: " . APPURL . "");
  exit();
}

/* Existing job_questions */
$jqStmt = $conn->prepare("SELECT * FROM job_questions WHERE job_id = :jid ORDER BY sort_order, id");
$jqStmt->execute(array(':jid'=>$id));
$existingQuestions = $jqStmt->fetchAll(PDO::FETCH_OBJ);
$existingPredefByBank = array();  // bank_id => row
$existingCustom       = array();  // list of custom rows
foreach ($existingQuestions as $q){
  if ($q->source === 'predefined' && $q->bank_id){
    $existingPredefByBank[(int)$q->bank_id] = $q;
  } else if ($q->source === 'custom') {
    $existingCustom[] = $q;
  }
}

/* Preselects */
$selectedRegion       = $singleJob->job_region ?: '';
$jobTypeSelected      = $singleJob->job_type ?: '';
$workArrangementSel   = $singleJob->work_arrangement ?: '';
$jobCategorySelected  = $singleJob->job_category ?: '';
$experienceSelected   = $singleJob->experience ?: '';
$inclusivityNotes     = $singleJob->inclusivity_notes ?: '';
$existingSalary       = trim((string)$singleJob->salary);

/* -----------------------------------------------------------
   Handle UPDATE
----------------------------------------------------------- */
if (isset($_POST['submit'])) {

  // sanitize 4 CKEditor fields
  $raw_desc  = strip_trailing_dangling_lt(isset($_POST['job_description']) ? $_POST['job_description'] : '');
  $raw_resp  = strip_trailing_dangling_lt(isset($_POST['responsibilities']) ? $_POST['responsibilities'] : '');
  $raw_reqs  = strip_trailing_dangling_lt(isset($_POST['education_experience']) ? $_POST['education_experience'] : '');
  $raw_bens  = strip_trailing_dangling_lt(isset($_POST['other_benefits']) ? $_POST['other_benefits'] : '');

  if (!looks_like_html($raw_desc)) $raw_desc = plain_to_paragraphs($raw_desc);
  if (!looks_like_html($raw_resp)) $raw_resp = plain_to_list($raw_resp);
  if (!looks_like_html($raw_reqs)) $raw_reqs = plain_to_list($raw_reqs);
  if (!looks_like_html($raw_bens)) $raw_bens = plain_to_list($raw_bens);

  $raw_desc = clean_ckeditor_html($raw_desc);
  $raw_resp = clean_ckeditor_html($raw_resp);
  $raw_reqs = clean_ckeditor_html($raw_reqs);
  $raw_bens = clean_ckeditor_html($raw_bens);

  $rich_empty =
    is_rich_empty($raw_desc) ||
    is_rich_empty($raw_resp) ||
    is_rich_empty($raw_reqs) ||
    is_rich_empty($raw_bens);

  if (
    empty($_POST['job_title']) ||
    empty($_POST['job_region']) ||
    empty($_POST['job_type']) ||
    empty($_POST['work_arrangement']) ||
    empty($_POST['vacancy']) ||
    empty($_POST['experience']) ||
    empty($_POST['application_deadline']) ||
    $rich_empty ||
    empty($_POST['company_email']) ||
    empty($_POST['company_name']) ||
    empty($_POST['company_id']) ||
    empty($_POST['company_image']) ||
    empty($_POST['job_category'])
  ) {
    $_SESSION['alert'] = array('type'=>'danger','message'=>'One or more inputs are empty');
    header("Location: " . APPURL . "/jobs/job-update.php?id=".$id);
    exit();
  }

  // Salary handling
  $salary = trim(isset($_POST['salary']) ? $_POST['salary'] : '');
  if ($salary === '') {
    $salary = salary_text(
      isset($_POST['salary_currency']) ? $_POST['salary_currency'] : 'USD',
      isset($_POST['salary_min']) ? $_POST['salary_min'] : '',
      isset($_POST['salary_max']) ? $_POST['salary_max'] : '',
      isset($_POST['salary_period']) ? $_POST['salary_period'] : 'yearly'
    );
    if ($salary === '') {
      $salary = trim(isset($_POST['salary_custom']) ? $_POST['salary_custom'] : '');
    }
  }

  // Update JOB row
  $update = $conn->prepare("
    UPDATE jobs SET
      job_title = :job_title,
      job_region = :job_region,
      job_type = :job_type,
      work_arrangement = :work_arrangement,
      vacancy = :vacancy,
      job_category = :job_category,
      experience = :experience,
      salary = :salary,
      inclusivity_notes = :inclusivity_notes,
      application_deadline = :application_deadline,
      job_description = :job_description,
      responsibilities = :responsibilities,
      education_experience = :education_experience,
      other_benefits = :other_benefits,
      company_email = :company_email,
      company_name  = :company_name,
      company_id    = :company_id,
      company_image = :company_image
    WHERE id = :id
    LIMIT 1
  ");
  $update->execute(array(
    ':job_title'            => $_POST['job_title'],
    ':job_region'           => $_POST['job_region'],
    ':job_type'             => $_POST['job_type'],
    ':work_arrangement'     => $_POST['work_arrangement'],
    ':vacancy'              => $_POST['vacancy'],
    ':job_category'         => $_POST['job_category'],
    ':experience'           => $_POST['experience'],
    ':salary'               => $salary,
    ':inclusivity_notes'    => isset($_POST['inclusivity_notes']) ? $_POST['inclusivity_notes'] : '',
    ':application_deadline' => $_POST['application_deadline'],
    ':job_description'      => htmlentities($raw_desc, ENT_QUOTES, 'UTF-8'),
    ':responsibilities'     => htmlentities($raw_resp, ENT_QUOTES, 'UTF-8'),
    ':education_experience' => htmlentities($raw_reqs, ENT_QUOTES, 'UTF-8'),
    ':other_benefits'       => htmlentities($raw_bens, ENT_QUOTES, 'UTF-8'),
    ':company_email'        => $_POST['company_email'],
    ':company_name'         => $_POST['company_name'],
    ':company_id'           => $_POST['company_id'],
    ':company_image'        => $_POST['company_image'],
    ':id'                   => $id
  ));

  /* ----------------- Update Application Questions ----------------- */
  // Predefined selections
  $selPredef   = array();
  if (!empty($_POST['predef']) && is_array($_POST['predef'])) {
    foreach ($_POST['predef'] as $pid) {
      if (ctype_digit((string)$pid)) $selPredef[] = (int)$pid;
    }
  }
  $predefReq = isset($_POST['predef_required']) && is_array($_POST['predef_required'])
               ? $_POST['predef_required'] : array();

  // Existing predefined by bank_id for quick lookup
  $predefRowsStmt = $conn->prepare("SELECT * FROM job_questions WHERE job_id=:jid AND source='predefined'");
  $predefRowsStmt->execute(array(':jid'=>$id));
  $predefRows = $predefRowsStmt->fetchAll(PDO::FETCH_OBJ);
  $predefByBank = array();
  foreach ($predefRows as $r) { if ($r->bank_id) $predefByBank[(int)$r->bank_id] = $r; }

  // Insert/Update selected predefined (in bank order)
  $insPre = $conn->prepare("
    INSERT INTO job_questions (job_id, source, bank_id, question_text, qtype, is_required, options, sort_order)
    VALUES (:job_id, 'predefined', :bank_id, :question_text, :qtype, :is_required, :options, :sort_order)
  ");
  $updPre = $conn->prepare("
    UPDATE job_questions
    SET is_required=:is_required, sort_order=:sort_order
    WHERE id=:id AND job_id=:job_id
  ");
  $delPre = $conn->prepare("DELETE FROM job_questions WHERE id=:id AND job_id=:job_id");

  $order = 1;
  $keptBanks = array();
  foreach ($questionBank as $qb) {
    $bid = (int)$qb->id;
    if (in_array($bid, $selPredef, true)) {
      $keptBanks[] = $bid;
      $req = !empty($predefReq[$bid]) ? 1 : 0;
      if (isset($predefByBank[$bid])) {
        $updPre->execute(array(
          ':is_required'=>$req, ':sort_order'=>$order, ':id'=>$predefByBank[$bid]->id, ':job_id'=>$id
        ));
      } else {
        $insPre->execute(array(
          ':job_id'=>$id, ':bank_id'=>$bid, ':question_text'=>$qb->question_text,
          ':qtype'=>$qb->qtype, ':is_required'=>$req,
          ':options'=>$qb->options, ':sort_order'=>$order
        ));
      }
      $order++;
    }
  }
  // remove any predefined no longer selected
  foreach ($predefByBank as $bankId=>$row) {
    if (!in_array((int)$bankId, $keptBanks, true)) {
      $delPre->execute(array(':id'=>$row->id, ':job_id'=>$id));
    }
  }

  // custom existing updates + deletes
  $ecq       = isset($_POST['ecq']) && is_array($_POST['ecq']) ? $_POST['ecq'] : array();
  $ecqDelete = isset($_POST['ecq_delete']) && is_array($_POST['ecq_delete']) ? $_POST['ecq_delete'] : array();

  $delC = $conn->prepare("DELETE FROM job_questions WHERE id=:id AND job_id=:jid AND source='custom'");
  foreach ($ecqDelete as $delId) {
    if (ctype_digit((string)$delId)) { $delC->execute(array(':id'=>(int)$delId, ':jid'=>$id)); }
  }

  $updC = $conn->prepare("
    UPDATE job_questions
    SET question_text=:text, qtype=:qtype, is_required=:req, options=:opts, sort_order=:ord
    WHERE id=:id AND job_id=:jid AND source='custom'
  ");
  foreach ($ecq as $i=>$row) {
    if (empty($row['id']) || !ctype_digit((string)$row['id'])) continue;
    $qid  = (int)$row['id'];
    $text = isset($row['text']) ? trim($row['text']) : '';
    $type = isset($row['type']) ? trim($row['type']) : 'text';
    $req  = !empty($row['required']) ? 1 : 0;

    if ($text === '') {
      $delC->execute(array(':id'=>$qid, ':jid'=>$id));
      continue;
    }
    $optRaw = isset($row['options']) ? trim($row['options']) : '';
    $opts = NULL;
    if ($type==='mcq' || $type==='dropdown') {
      if ($optRaw !== '') {
        $parts = preg_split('/\s*,\s*/', $optRaw);
        $parts = array_values(array_filter($parts, 'strlen'));
        $opts  = $parts ? json_encode($parts) : NULL;
      }
    }
    $updC->execute(array(
      ':text'=>$text, ':qtype'=>$type, ':req'=>$req, ':opts'=>$opts, ':ord'=>$order,
      ':id'=>$qid, ':jid'=>$id
    ));
    $order++;
  }

  // new custom inserts
  $cq = isset($_POST['cq']) && is_array($_POST['cq']) ? $_POST['cq'] : array();
  $insC = $conn->prepare("
    INSERT INTO job_questions (job_id, source, bank_id, question_text, qtype, is_required, options, sort_order)
    VALUES (:jid, 'custom', NULL, :text, :qtype, :req, :opts, :ord)
  ");
  foreach ($cq as $idx=>$row) {
    $text = isset($row['text']) ? trim($row['text']) : '';
    if ($text === '') continue;
    $type = isset($row['type']) ? trim($row['type']) : 'text';
    $req  = !empty($row['required']) ? 1 : 0;
    $optRaw = isset($row['options']) ? trim($row['options']) : '';
    $opts = NULL;
    if ($type==='mcq' || $type==='dropdown') {
      if ($optRaw !== '') {
        $parts = preg_split('/\s*,\s*/', $optRaw);
        $parts = array_values(array_filter($parts, 'strlen'));
        $opts  = $parts ? json_encode($parts) : NULL;
      }
    }
    $insC->execute(array(
      ':jid'=>$id, ':text'=>$text, ':qtype'=>$type, ':req'=>$req, ':opts'=>$opts, ':ord'=>$order++
    ));
  }

  $_SESSION['alert'] = array('type'=>'success', 'message'=>'Job updated successfully.');
  header("Location: " . APPURL . "/jobs/job-update.php?id=".$id);
  exit();
}

/* -----------------------------------------------------------
   output HTML
----------------------------------------------------------- */
require "../includes/header.php";
?>

<style>
  .emp-wrap { padding-top: 2rem; }
  .emp-sidebar {
    background:#fff; border:0; border-radius:14px;
    box-shadow:0 8px 24px rgba(0,0,0,.06);
    position: sticky; top: 82px;
  }
  .emp-sidebar .list-group-item{
    border:0; border-left:3px solid transparent; border-radius:0;
  }
  .emp-sidebar .list-group-item:hover{ background:#f8fafc; text-decoration: none; }
  .emp-sidebar .list-group-item.active{
    background:#eef2ff; color:#111827; border-left-color:#6366f1; font-weight:700; text-decoration: none;
  }
  .emp-card{
    background:#fff; border:0; border-radius:14px; box-shadow:0 8px 24px rgba(0,0,0,.06);
  }

  .site-section{ padding-top: 0; }
  .pj-hero{
    border-radius:16px;background:
      radial-gradient(900px 300px at 0% -10%, rgba(99,102,241,.12), transparent 60%),
      radial-gradient(900px 300px at 100% 0%, rgba(6,182,212,.10), transparent 60%),
      #fff; padding:18px 20px; box-shadow:0 8px 26px rgba(0,0,0,.06); margin-bottom:18px;
  }
  .pj-card{ background:#fff;border-radius:14px;box-shadow:0 2px 12px rgba(0,0,0,.06);padding:18px;margin-bottom:16px; }
  .pj-grid{ display:grid; grid-template-columns: 1fr 1fr; gap:16px; }
  @media (max-width:992px){ .pj-grid{ grid-template-columns:1fr; } }
  .pj-fields{ display:grid; grid-template-columns:1fr 1fr; gap:14px; }
  .pj-fields .col-span-2{ grid-column:1 / -1; }
  @media (max-width:576px){ .pj-fields{ grid-template-columns:1fr; } }
  .pj-card .form-control, .pj-card .bootstrap-select .btn{ border-radius:10px; border:1px solid #e5e7eb; min-height:44px; }
  .pj-card textarea.form-control{ min-height:120px; }
  .pj-actions{ display:flex; gap:10px; justify-content:flex-end; align-items:center; }
  .btn-post-primary{ color:#fff; border:0; background:#6366f1; box-shadow:0 6px 18px rgba(99,102,241,.25); }
  .btn-post-ghost{ background:#aeaeae; color:#0f172a; border:1px solid #e5e7eb; }
  .pj-note{ color:#6b7280; font-size:.9rem; }
  .q-chip{ display:inline-block; font-size:.85rem; padding:.2rem .5rem; border-radius:999px; background:#eef2ff; }
  .q-row{ border:1px dashed #e5e7eb; border-radius:10px; padding:12px; margin-bottom:10px; position:relative; }
  .q-row .remove-q{ position:absolute; right:10px; top:10px; }
  .q-row small{ color:#6b7280; }
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
          <a href="<?php echo $base_url; ?>/users/show-applicants.php?id=<?php echo $employerId; ?>" class="list-group-item">
            <i class="fa-solid fa-users-line mr-2"></i> Show Applicants
          </a>
          <a href="<?php echo $base_url; ?>/users/postedJobs.php" class="list-group-item">
            <i class="fa-solid fa-briefcase mr-2"></i> Posted Jobs
          </a>
          <a href="<?php echo $base_url; ?>/jobs/job-update.php?id=<?php echo $id; ?>" class="list-group-item active">
            <i class="fa-solid fa-pen-to-square mr-2"></i> Update Job
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
        <h4 class="mb-0">Update Job</h4>
      </div>

      <section class="site-section">
        <div class="container px-0">
          <?php if (!empty($_SESSION['alert'])): $a = $_SESSION['alert']; ?>
            <div class="alert alert-<?php echo htmlspecialchars($a['type']); ?> alert-dismissible fade show" role="alert">
              <?php echo htmlspecialchars($a['message']); ?>
              <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>
            </div>
            <?php unset($_SESSION['alert']); ?>
          <?php endif; ?>

          <form action="job-update.php?id=<?php echo $id; ?>" method="post" class="pj-form">
            <div class="emp-card pj-hero">
              <h2>Edit Job Posting</h2>
              <div class="sub text-muted">Update the details below and save changes.</div>
            </div>

            <div class="pj-grid">
              <!-- LEFT -->
              <div>
                <div class="pj-card">
                  <h5>Job Details</h5>

                  <div class="pj-fields">
                    <div class="col-span-2">
                      <label>Job Title</label>
                      <input type="text" name="job_title" class="form-control" value="<?php echo htmlspecialchars($singleJob->job_title); ?>" required>
                    </div>

                    <div>
                      <label>Region</label>
                      <select name="job_region" class="selectpicker border rounded" data-style="btn-black" data-width="100%" data-live-search="true" title="Select Region" required>
                        <?php foreach ($jobRegions as $region): ?>
                          <option value="<?php echo htmlspecialchars($region->code); ?>" <?php echo ($region->code === $selectedRegion)?'selected':''; ?>>
                            <?php echo htmlspecialchars($region->name); ?>
                          </option>
                        <?php endforeach; ?>
                      </select>
                    </div>

                    <div>
                      <label>Employment Type</label>
                      <select name="job_type" class="selectpicker border rounded" data-style="btn-black" data-width="100%" data-live-search="true" title="Select Employment Type" required>
                        <?php foreach (array('Casual','Part Time','Full Time','Contract','Fixed Term') as $t): ?>
                          <option <?php echo ($t===$jobTypeSelected)?'selected':''; ?>><?php echo $t; ?></option>
                        <?php endforeach; ?>
                      </select>
                    </div>

                    <div>
                      <label>Work Arrangement</label>
                      <select name="work_arrangement" class="selectpicker border rounded" data-style="btn-black" data-width="100%" title="Select Work Arrangement" required>
                        <?php foreach (array('Fully Remote','Hybrid (Remote + On-site)','On-site Only','Flexible') as $w): ?>
                          <option <?php echo ($w===$workArrangementSel)?'selected':''; ?>><?php echo $w; ?></option>
                        <?php endforeach; ?>
                      </select>
                    </div>

                    <div>
                      <label>Number of Vacancy</label>
                      <input name="vacancy" type="number" min="1" class="form-control" value="<?php echo htmlspecialchars($singleJob->vacancy); ?>" required>
                    </div>

                    <div>
                      <label>Job Category</label>
                      <select name="job_category" class="selectpicker border rounded" data-style="btn-black" data-width="100%" data-live-search="true" title="Select Job Category" required>
                        <?php foreach($get_category as $category): ?>
                          <option value="<?php echo htmlspecialchars($category->name); ?>" <?php echo ($category->name===$jobCategorySelected)?'selected':''; ?>>
                            <?php echo htmlspecialchars($category->name); ?>
                          </option>
                        <?php endforeach; ?>
                      </select>
                    </div>

                    <div>
                      <label>Experience</label>
                      <select name="experience" class="selectpicker border rounded" data-style="btn-black" data-width="100%" data-live-search="true" title="Select Years of Experience" required>
                        <?php foreach (array('No experience needed','1-3 years','3-6 years','6-9 years') as $exp): ?>
                          <option <?php echo ($exp===$experienceSelected)?'selected':''; ?>><?php echo $exp; ?></option>
                        <?php endforeach; ?>
                      </select>
                    </div>

                    <!-- Salary -->
                    <div class="col-span-2">
                      <label class="d-flex align-items-center mb-2">Salary <small class="text-muted ml-2">(structured)</small></label>
                      <div class="form-row">
                        <div class="form-group col-6 col-md-3">
                          <label class="small mb-1">Min</label>
                          <input type="number" min="0" step="1" class="form-control" name="salary_min" id="salary_min" placeholder="e.g. 30">
                        </div>
                        <div class="form-group col-6 col-md-3">
                          <label class="small mb-1">Max <span class="text-muted">(optional)</span></label>
                          <input type="number" min="0" step="1" class="form-control" name="salary_max" id="salary_max" placeholder="e.g. 45">
                        </div>
                        <div class="form-group col-6 col-md-3">
                          <label class="small mb-1">Currency</label>
                          <select class="form-control" name="salary_currency" id="salary_currency">
                            <option value="USD">$ USD</option><option value="NZD">$ NZD</option><option value="AUD">$ AUD</option>
                            <option value="CAD">$ CAD</option><option value="EUR">€ EUR</option><option value="GBP">£ GBP</option>
                            <option value="INR">₹ INR</option><option value="JPY">¥ JPY</option>
                          </select>
                        </div>
                        <div class="form-group col-6 col-md-3">
                          <label class="small mb-1">Period</label>
                          <select class="form-control" name="salary_period" id="salary_period">
                            <option value="hourly">Hourly</option><option value="monthly">Monthly</option><option value="yearly" selected>Yearly</option>
                          </select>
                        </div>
                      </div>
                      <div class="d-flex align-items-center mb-2">
                        <small class="text-muted mr-2">Preview:</small>
                        <strong id="salaryPreview"><?php echo $existingSalary!==''?htmlspecialchars($existingSalary):'—'; ?></strong>
                      </div>
                      <input type="hidden" name="salary" id="salary_hidden" value="<?php echo htmlspecialchars($existingSalary); ?>">
                      <div class="form-check mb-2">
                        <input class="form-check-input" type="checkbox" id="salaryCustomToggle">
                        <label class="form-check-label small" for="salaryCustomToggle">Or enter a custom salary text</label>
                      </div>
                      <input type="text" class="form-control d-none" name="salary_custom" id="salary_custom" placeholder="e.g., Competitive + bonus, equity, allowances">
                    </div>

                    <div>
                      <label>Application Deadline</label>
                      <input name="application_deadline" type="date" class="form-control" value="<?php echo htmlspecialchars($singleJob->application_deadline); ?>" required>
                    </div>

                    <div class="col-span-2">
                      <label>Inclusivity Notes</label>
                      <textarea name="inclusivity_notes" class="form-control" rows="4" placeholder="Share DEI values, flexibility, accommodations…"><?php echo htmlspecialchars($inclusivityNotes, ENT_QUOTES, 'UTF-8'); ?></textarea>
                      <div class="pj-note mt-1">Optional note that encourages a diverse range of applicants.</div>
                    </div>
                  </div>
                </div>
              </div>

              <!-- RIGHT -->
              <div>
                <div class="pj-card">
                  <h5>Descriptions</h5>

                  <div class="form-group mb-3">
                    <label>Job Description</label>
                    <textarea name="job_description" id="job_description" class="form-control" rows="6" required><?php
                      echo html_entity_decode((string)$singleJob->job_description, ENT_QUOTES, 'UTF-8');
                    ?></textarea>
                  </div>

                  <div class="form-group mb-3">
                    <label>Responsibilities</label>
                    <textarea name="responsibilities" id="responsibilities" class="form-control" rows="6" required><?php
                      echo html_entity_decode((string)$singleJob->responsibilities, ENT_QUOTES, 'UTF-8');
                    ?></textarea>
                  </div>

                  <div class="form-group mb-3">
                    <label>Education & Experience</label>
                    <textarea name="education_experience" id="education_experience" class="form-control" rows="5" required><?php
                      echo html_entity_decode((string)$singleJob->education_experience, ENT_QUOTES, 'UTF-8');
                    ?></textarea>
                  </div>

                  <div class="form-group mb-0">
                    <label>Benefits</label>
                    <textarea name="other_benefits" id="other_benefits" class="form-control" rows="5"><?php
                      echo html_entity_decode((string)$singleJob->other_benefits, ENT_QUOTES, 'UTF-8');
                    ?></textarea>
                  </div>
                </div>

                <div class="pj-card">
                  <h5 class="mb-3">Application Questions</h5>

                  <!-- Standard questions -->
                  <div class="mb-3">
                    <strong>Use Standard Questions</strong>
                    <div id="standardBank" class="mt-2">
                      <?php foreach ($questionBank as $q):
                        $checked = isset($existingPredefByBank[(int)$q->id]);
                        $reqd    = $checked ? (int)$existingPredefByBank[(int)$q->id]->is_required === 1 : false;
                      ?>
                        <div class="custom-control custom-checkbox">
                          <input type="checkbox" class="custom-control-input" id="qbank_<?php echo $q->id; ?>" name="predef[]" value="<?php echo (int)$q->id; ?>" <?php echo $checked?'checked':''; ?>>
                          <label class="custom-control-label" for="qbank_<?php echo $q->id; ?>">
                            <?php echo htmlspecialchars($q->question_text); ?>
                            <span class="q-chip ml-1"><?php echo htmlspecialchars($q->qtype); ?></span>
                          </label>
                          <div class="custom-control custom-checkbox d-inline-block ml-3">
                            <input type="checkbox" class="custom-control-input" id="rq_<?php echo $q->id; ?>" name="predef_required[<?php echo (int)$q->id; ?>]" value="1" <?php echo $reqd?'checked':''; ?>>
                            <label class="custom-control-label" for="rq_<?php echo $q->id; ?>">Required</label>
                          </div>
                        </div>
                      <?php endforeach; ?>
                    </div>
                  </div>

                  <hr>

                  <!-- Existing custom questions -->
                  <div class="mb-2"><strong>Custom Questions</strong></div>
                  <div id="customQuestions">
                    <?php foreach ($existingCustom as $idx=>$cq):
                      $opts = '';
                      if ($cq->options) {
                        $arr = json_decode($cq->options, true);
                        if (is_array($arr)) $opts = implode(', ', $arr);
                      }
                    ?>
                      <div class="q-row" data-existing="1">
                        <button type="button" class="btn btn-sm btn-outline-danger remove-q">Remove</button>
                        <input type="hidden" name="ecq[<?php echo $idx; ?>][id]" value="<?php echo (int)$cq->id; ?>">
                        <div class="form-group mb-2">
                          <label>Question Text</label>
                          <input type="text" class="form-control" name="ecq[<?php echo $idx; ?>][text]" value="<?php echo htmlspecialchars($cq->question_text); ?>">
                        </div>
                        <div class="form-row">
                          <div class="form-group col-md-6">
                            <label>Type</label>
                            <select class="form-control js-qtype" name="ecq[<?php echo $idx; ?>][type]">
                              <?php foreach (array('text','textarea','yesno','mcq','dropdown','file') as $t): ?>
                                <option value="<?php echo $t; ?>" <?php echo ($t===$cq->qtype)?'selected':''; ?>><?php echo $t; ?></option>
                              <?php endforeach; ?>
                            </select>
                          </div>
                          <div class="form-group col-md-3 d-flex align-items-center">
                            <div class="custom-control custom-checkbox mt-3">
                              <input type="checkbox" class="custom-control-input" id="ecq_req_<?php echo $idx; ?>" name="ecq[<?php echo $idx; ?>][required]" value="1" <?php echo ((int)$cq->is_required===1)?'checked':''; ?>>
                              <label class="custom-control-label" for="ecq_req_<?php echo $idx; ?>">Required</label>
                            </div>
                          </div>
                          <div class="form-group col-md-12 js-opts-wrap <?php echo ($cq->qtype==='mcq' || $cq->qtype==='dropdown')?'':'d-none'; ?>">
                            <label>Options</label>
                            <input type="text" class="form-control js-options" name="ecq[<?php echo $idx; ?>][options]" value="<?php echo htmlspecialchars($opts); ?>" placeholder="Option 1, Option 2, Option 3">
                            <small>Shown when type is Multiple Choice or Dropdown.</small>
                          </div>
                        </div>
                      </div>
                    <?php endforeach; ?>
                  </div>

                  <!-- New custom -->
                  <button type="button" id="addQuestionBtn" class="btn btn-sm btn-outline-primary">
                    <i class="fa fa-plus mr-1"></i> Add Custom Question
                  </button>

                  <!-- Bucket to collect deletions for existing custom -->
                  <div id="deletedBucket"></div>
                </div>
              </div>
            </div>

            <!-- Hidden company/user fields -->
            <input type="hidden" name="company_email" value="<?php echo htmlspecialchars($_SESSION['email']); ?>">
            <input type="hidden" name="company_name"  value="<?php echo htmlspecialchars($_SESSION['fullname']); ?>">
            <input type="hidden" name="company_id"    value="<?php echo htmlspecialchars($_SESSION['id']); ?>">
            <input type="hidden" name="company_image" value="<?php echo htmlspecialchars($_SESSION['image']); ?>">

            <div class="pj-actions pj-card">
              <a href="<?php echo APPURL; ?>/jobs/job-single.php?id=<?php echo $id; ?>" class="btn btn-post-ghost">Cancel</a>
              <input type="submit" name="submit" value="Save Changes" class="btn btn-post-primary">
            </div>
          </form>
        </div>
      </section>
    </main>
  </div>
</div>

<script>
  // CKEditor
  if (window.CKEDITOR) {
    CKEDITOR.replace('job_description');
    CKEDITOR.replace('responsibilities');
    CKEDITOR.replace('education_experience');
    CKEDITOR.replace('other_benefits');
  }
</script>

<script>
/* Salary builder */
(function(){
  var minI  = document.getElementById('salary_min');
  var maxI  = document.getElementById('salary_max');
  var curI  = document.getElementById('salary_currency');
  var perI  = document.getElementById('salary_period');
  var prev  = document.getElementById('salaryPreview');
  var hid   = document.getElementById('salary_hidden');
  var tgl   = document.getElementById('salaryCustomToggle');
  var txt   = document.getElementById('salary_custom');

  var sym = { USD:'$', NZD:'$', AUD:'$', CAD:'$', EUR:'€', GBP:'£', INR:'₹', JPY:'¥' };
  var unit= { hourly:'per hour', monthly:'per month', yearly:'annually' };

  function fmt(n){ if(n === '' || isNaN(n)) return ''; return Number(n).toLocaleString(undefined,{maximumFractionDigits:0}); }
  function build(){
    if (tgl && tgl.checked) {
      txt.classList.remove('d-none');
      prev.textContent = txt.value.trim() || '—';
      hid.value = txt.value.trim();
      return;
    } else if (txt) { txt.classList.add('d-none'); }
    var min = fmt(minI.value), max = fmt(maxI.value), c = curI.value || 'USD', p = perI.value || 'yearly';
    var s = sym[c] || '$', u = unit[p] || '';
    var core = min ? (s + min + (max ? ('–' + s + max) : '')) : '';
    var out = core ? (core + ' ' + u) : (hid.value || '');
    prev.textContent = out || '—';
    if (core) hid.value = out;
  }
  [minI,maxI,curI,perI].forEach(function(el){ el && el.addEventListener('input', build); });
  if (tgl) tgl.addEventListener('change', build);
  if (txt) txt.addEventListener('input', build);
  build();
})();
</script>

<script>
/* questions UI: toggle options for mcq/dropdown; add/remove custom; track deletions */
(function(){
  var wrap = document.getElementById('customQuestions');
  var addBtn = document.getElementById('addQuestionBtn');
  var delBucket = document.getElementById('deletedBucket');
  var newIdx = 0;

  function attachRowEvents(row){
    var sel = row.querySelector('.js-qtype');
    var optsWrap = row.querySelector('.js-opts-wrap');
    var delBtn = row.querySelector('.remove-q');

    function onTypeChange(){
      var v = sel.value;
      if (v==='mcq' || v==='dropdown') optsWrap.classList.remove('d-none');
      else optsWrap.classList.add('d-none');
    }
    if (sel && optsWrap){ sel.addEventListener('change', onTypeChange); onTypeChange(); }

    if (delBtn){
      delBtn.addEventListener('click', function(){
        if (row.getAttribute('data-existing') === '1') {
          var idInput = row.querySelector('input[type=hidden][name*="[id]"]');
          if (idInput && delBucket){
            var h = document.createElement('input');
            h.type = 'hidden';
            h.name = 'ecq_delete[]';
            h.value = idInput.value;
            delBucket.appendChild(h);
          }
        }
        row.parentNode.removeChild(row);
      });
    }
  }

  if (wrap){
    [].forEach.call(wrap.querySelectorAll('.q-row'), attachRowEvents);
  }

  function newRowTemplate(i){
    return ''+
    '<div class="q-row">'+
      '<button type="button" class="btn btn-sm btn-outline-danger remove-q">Remove</button>'+
      '<div class="form-group mb-2">'+
        '<label>Question Text</label>'+
        '<input type="text" class="form-control" name="cq['+i+'][text]" placeholder="e.g., Why are you a great fit?">'+
      '</div>'+
      '<div class="form-row">'+
        '<div class="form-group col-md-6">'+
          '<label>Type</label>'+
          '<select class="form-control js-qtype" name="cq['+i+'][type]">'+
            '<option value="text">Text</option>'+
            '<option value="textarea">Textarea</option>'+
            '<option value="yesno">Yes / No</option>'+
            '<option value="mcq">Multiple Choice</option>'+
            '<option value="dropdown">Dropdown</option>'+
            '<option value="file">File upload</option>'+
          '</select>'+
        </div>'+
        '<div class="form-group col-md-3 d-flex align-items-center">'+
          '<div class="custom-control custom-checkbox mt-3">'+
            '<input type="checkbox" class="custom-control-input" id="cq_req_'+i+'" name="cq['+i+'][required]" value="1">'+
            '<label class="custom-control-label" for="cq_req_'+i+'">Required</label>'+
          '</div>'+
        '</div>'+
        '<div class="form-group col-md-12 js-opts-wrap d-none">'+
          '<label>Options</label>'+
          '<input type="text" class="form-control js-options" name="cq['+i+'][options]" placeholder="Option 1, Option 2, Option 3">'+
          '<small>Shown when type is Multiple Choice or Dropdown.</small>'+
        '</div>'+
      '</div>'+
    '</div>';
  }

  if (addBtn && wrap){
    addBtn.addEventListener('click', function(){
      wrap.insertAdjacentHTML('beforeend', newRowTemplate(newIdx));
      var row = wrap.lastElementChild;
      attachRowEvents(row);
      newIdx++;
    });
  }
})();
</script>

<?php require "../includes/footer.php"; ?>
