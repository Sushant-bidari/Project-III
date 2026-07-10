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
if (!isset($_SESSION['id'])) {
  echo json_encode(['ok'=>false,'error'=>'auth']); exit;
}
$uid = (int)$_SESSION['id'];
$action = $_POST['action'] ?? $_GET['action'] ?? 'list';

try {
  switch ($action) {
    case 'count':
      $st = $conn->prepare("SELECT COUNT(*) FROM notifications WHERE recipient_user_id = :uid AND seen = 0");
      $st->execute([':uid'=>$uid]);
      echo json_encode(['ok'=>true, 'count'=>(int)$st->fetchColumn()]); break;

    case 'list':
    $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 8;
    $limit = max(1, min(50, $limit));

    $stmt = $conn->prepare("
        SELECT id, title, message, link_path, seen, created_at
        FROM notifications
        WHERE recipient_user_id = :uid
        ORDER BY created_at DESC
        LIMIT :lim
    ");
    $stmt->bindValue(':uid', $uid, PDO::PARAM_INT);
    $stmt->bindValue(':lim', $limit, PDO::PARAM_INT);
    $stmt->execute();
    echo json_encode(['ok'=>true, 'items'=>$stmt->fetchAll(PDO::FETCH_ASSOC)]);
    break;


    case 'mark':
      $id = (int)($_POST['id'] ?? 0);
      if (!$id) { echo json_encode(['ok'=>false,'error'=>'bad_id']); break; }
      $up = $conn->prepare("UPDATE notifications SET seen = 1, seen_at = NOW() WHERE id = :id AND recipient_user_id = :uid");
      $up->execute([':id'=>$id, ':uid'=>$uid]);
      echo json_encode(['ok'=>true]); break;

    case 'mark_all':
      $up = $conn->prepare("UPDATE notifications SET seen = 1, seen_at = NOW() WHERE recipient_user_id = :uid AND seen = 0");
      $up->execute([':uid'=>$uid]);
      echo json_encode(['ok'=>true]); break;

    default:
      echo json_encode(['ok'=>false,'error'=>'bad_action']); break;
  }
} catch (Throwable $e) {
  echo json_encode(['ok'=>false,'error'=>'server']);
}
