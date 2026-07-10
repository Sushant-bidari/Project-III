<?php
require "../config/config.php";
header('Content-Type: application/json');
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
if (!isset($_POST['worker_id']) || !ctype_digit($_POST['worker_id'])) {
  echo json_encode(['success'=>false,'error'=>'bad user']); exit;
}
$uid = (int)$_POST['worker_id'];

function resumes_table_exists($conn){
  try { $conn->query("SELECT 1 FROM resumes LIMIT 1"); return true; }
  catch (PDOException $e){ return false; }
}

$upload_dir = "../users/user-cvs/";
if (!is_dir($upload_dir)) { @mkdir($upload_dir, 0775, true); }

$chosenFilename = trim($_POST['selected_resume'] ?? '');
$uploadedFilename = '';

// (1) Handle file upload (opt)
if (!empty($_FILES['cv_file']['name'])) {
  $orig = $_FILES['cv_file']['name'];
  $tmp  = $_FILES['cv_file']['tmp_name'];
  $err  = $_FILES['cv_file']['error'];
  $ext  = strtolower(pathinfo($orig, PATHINFO_EXTENSION));
  $allowed = ['pdf','doc','docx'];

  if ($err === UPLOAD_ERR_OK && in_array($ext, $allowed, true)) {
    $safe = preg_replace('/[^a-zA-Z0-9._-]/','_', $orig);
    $final = 'cv_' . $uid . '_' . time() . '_' . $safe;
    if (move_uploaded_file($tmp, $upload_dir.$final)) {
      $uploadedFilename = $final;
    } else {
      echo json_encode(['success'=>false,'error'=>'upload move failed']); exit;
    }
  } else if ($err !== UPLOAD_ERR_NO_FILE) {
    echo json_encode(['success'=>false,'error'=>'invalid file']); exit;
  }
}

// decide final filename to set as current
$currentFile = $uploadedFilename ?: $chosenFilename;

// (2) Persist CV selection
try {
  if (resumes_table_exists($conn)) {
    if ($uploadedFilename) {
      // insert new as primary, unset others
      $conn->prepare("UPDATE resumes SET is_primary=0 WHERE user_id=:u")->execute([':u'=>$uid]);
      $ins = $conn->prepare("INSERT INTO resumes (user_id, filename, original_name, is_primary) 
                             VALUES (:u,:f,:o,1)");
      $ins->execute([':u'=>$uid, ':f'=>$uploadedFilename, ':o'=>($_FILES['cv_file']['name'] ?? $uploadedFilename)]);
      $currentFile = $uploadedFilename;
    } else if ($chosenFilename) {
      // make chosen primary if it exists in resumes
      $conn->prepare("UPDATE resumes SET is_primary=0 WHERE user_id=:u")->execute([':u'=>$uid]);
      $upd = $conn->prepare("UPDATE resumes SET is_primary=1 WHERE user_id=:u AND filename=:f");
      $upd->execute([':u'=>$uid, ':f'=>$chosenFilename]);
    }
  }

  // always keep users.cv in sync so that existing application INSERT picks it
  if ($currentFile) {
    $conn->prepare("UPDATE users SET cv=:f WHERE id=:u")->execute([':f'=>$currentFile, ':u'=>$uid]);
  }
} catch (PDOException $e) {
  echo json_encode(['success'=>false,'error'=>'db error']); exit;
}

// (3) Save availability (upsert)
$days = ['monday','tuesday','wednesday','thursday','friday','saturday','sunday'];
$vals = [];
foreach ($days as $d) { $vals[$d] = $_POST[$d] ?? 'none'; }

try {
  $conn->exec("CREATE TABLE IF NOT EXISTS user_availability (
    user_id INT PRIMARY KEY,
    monday VARCHAR(20) NOT NULL DEFAULT 'none',
    tuesday VARCHAR(20) NOT NULL DEFAULT 'none',
    wednesday VARCHAR(20) NOT NULL DEFAULT 'none',
    thursday VARCHAR(20) NOT NULL DEFAULT 'none',
    friday VARCHAR(20) NOT NULL DEFAULT 'none',
    saturday VARCHAR(20) NOT NULL DEFAULT 'none',
    sunday VARCHAR(20) NOT NULL DEFAULT 'none',
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_availability_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
  )");

  $ins = $conn->prepare("
    INSERT INTO user_availability (user_id, monday,tuesday,wednesday,thursday,friday,saturday,sunday)
    VALUES (:u,:mo,:tu,:we,:th,:fr,:sa,:su)
    ON DUPLICATE KEY UPDATE
      monday=VALUES(monday), tuesday=VALUES(tuesday), wednesday=VALUES(wednesday),
      thursday=VALUES(thursday), friday=VALUES(friday), saturday=VALUES(saturday), sunday=VALUES(sunday)
  ");
  $ins->execute([
    ':u'=>$uid, ':mo'=>$vals['monday'], ':tu'=>$vals['tuesday'], ':we'=>$vals['wednesday'],
    ':th'=>$vals['thursday'], ':fr'=>$vals['friday'], ':sa'=>$vals['saturday'], ':su'=>$vals['sunday']
  ]);
} catch (PDOException $e) {
  echo json_encode(['success'=>false,'error'=>'availability save failed']); exit;
}

echo json_encode(['success'=>true, 'cv'=>$currentFile ?: null]);
