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
if (!isset($_GET['user_id']) || !ctype_digit($_GET['user_id'])) {
  echo json_encode(['ok'=>false,'error'=>'bad user']); exit;
}
$uid = (int)$_GET['user_id'];

function resumes_table_exists($conn){
  try { $conn->query("SELECT 1 FROM resumes LIMIT 1"); return true; }
  catch (PDOException $e){ return false; }
}

$items = [];
$dir   = "../users/user-cvs/";

if (resumes_table_exists($conn)) {
  $stmt = $conn->prepare("SELECT id, filename, original_name, is_primary, created_at 
                          FROM resumes WHERE user_id=:u
                          ORDER BY is_primary DESC, created_at DESC");
  $stmt->execute([':u'=>$uid]);
  while ($r = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $size = (file_exists($dir.$r['filename'])) ? filesize($dir.$r['filename']) : 0;
    $items[] = [
      'id'           => (int)$r['id'],
      'filename'     => $r['filename'],
      'original_name'=> $r['original_name'],
      'is_primary'   => (int)$r['is_primary']===1,
      'size_h'       => $size ? (round($size/1024)).' KB' : ''
    ];
  }
} 

if (!$items) {
  // fallback to users.cv
  $u = $conn->prepare("SELECT cv FROM users WHERE id=:u LIMIT 1");
  $u->execute([':u'=>$uid]);
  $cv = $u->fetchColumn();
  if ($cv) {
    $size = (file_exists($dir.$cv)) ? (round(filesize($dir.$cv)/1024)).' KB' : '';
    $items[] = [
      'id'=>null, 'filename'=>$cv, 'original_name'=>$cv,
      'is_primary'=>true, 'size_h'=>$size
    ];
  }
}

echo json_encode(['ok'=>true,'items'=>$items]); 
