<?php
require "../config/config.php";

if (!isset($_SESSION['id']) || ($_SESSION['type'] ?? '') !== 'Job Seeker') {
  header("Location: " . APPURL);
  exit;
}

$user_id = (int)$_SESSION['id'];

/* ---- Validate CSRF ---- */
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  header("Location: applied_jobs.php?msg=invalid"); exit;
}
if (empty($_POST['csrf']) || !hash_equals($_SESSION['csrf_token'] ?? '', $_POST['csrf'])) {
  header("Location: applied_jobs.php?msg=csrf"); exit;
}

/* ---- Validate input ---- */
if (!isset($_POST['id']) || !ctype_digit((string)$_POST['id'])) {
  header("Location: applied_jobs.php?msg=invalid"); exit;
}
$application_id = (int)$_POST['id'];

/* ---- Load application + job ---- */
$sql = "
  SELECT a.id, a.worker_id, a.application_status, a.job_id,
         j.application_deadline
  FROM job_applications a
  LEFT JOIN jobs j ON a.job_id = j.id
  WHERE a.id = :id AND a.worker_id = :uid
  LIMIT 1
";
$stmt = $conn->prepare($sql);
$stmt->execute([':id'=>$application_id, ':uid'=>$user_id]);
$app = $stmt->fetch(PDO::FETCH_OBJ);

if (!$app) {
  header("Location: applied_jobs.php?msg=notfound"); exit;
}


$deadlineTs = $app->application_deadline ? strtotime($app->application_deadline) : null;
$deadlinePast = $deadlineTs ? (time() > $deadlineTs) : false;
if ($deadlinePast) {
  header("Location: applied_jobs.php?msg=deadline"); exit;
}
$finalLocked = in_array((string)$app->application_status, ['2','3','4'], true); // rejected/selected/withdrawn
if ($finalLocked) {
  header("Location: applied_jobs.php?msg=locked"); exit;
}

/* ---- Mark withdrawn + timestamp ---- */
$upd = $conn->prepare("
  UPDATE job_applications
  SET application_status = 4,
      withdrawn_at = NOW()
  WHERE id = :id AND worker_id = :uid
");
$ok = $upd->execute([':id'=>$application_id, ':uid'=>$user_id]);

header("Location: applied_jobs.php?msg=" . ($ok ? 'withdrawn' : 'error'));
exit;