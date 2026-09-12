<?php
require "../config/config.php";
header('Content-Type: application/json');


if (!isset($_POST['worker_id']) || !ctype_digit($_POST['worker_id'])) {
  echo json_encode(['has_cv'=>false,'has_availability'=>false]); exit;
}
$uid = (int)$_POST['worker_id'];

function resumes_table_exists($conn){
  try { $conn->query("SELECT 1 FROM resumes LIMIT 1"); return true; }
  catch (PDOException $e){ return false; }
}

$has_cv = false;
if (resumes_table_exists($conn)) {
  $st = $conn->prepare("SELECT COUNT(*) FROM resumes WHERE user_id=:u");
  $st->execute([':u'=>$uid]);
  $has_cv = ($st->fetchColumn() > 0);
} else {
  $st = $conn->prepare("SELECT cv FROM users WHERE id=:u LIMIT 1");
  $st->execute([':u'=>$uid]);
  $cv = trim((string)$st->fetchColumn());
  $has_cv = ($cv !== '');
}

// availability?
try {
  $st2 = $conn->prepare("SELECT 1 FROM user_availability WHERE user_id=:u");
  $st2->execute([':u'=>$uid]);
  $has_availability = (bool)$st2->fetchColumn();
} catch (PDOException $e) {
  // table might not exist yet
  $has_availability = false;
}

echo json_encode(['has_cv'=>$has_cv, 'has_availability'=>$has_availability]);
