<?php
session_start();
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

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  header("Location: " . APPURL);
  exit;
}

// Gate: Job Seeker only
if (!isset($_SESSION['id'], $_SESSION['type']) || $_SESSION['type'] !== 'Job Seeker') {
  header("Location: " . APPURL);
  exit;
}

if (empty($_POST['csrf']) || !hash_equals($_SESSION['csrf_token'] ?? '', $_POST['csrf'])) {
  $_SESSION['avail_flash'] = ['type' => 'danger', 'text' => 'Security check failed. Please try again.'];
  header("Location: my_availability.php");
  exit;
}

$user_id = (int) $_SESSION['id'];

// Valid fields/options
$days    = ['monday','tuesday','wednesday','thursday','friday','saturday','sunday'];
$allowed = ['morning','afternoon','evening','wholeday','none'];

// Sanitize values (fallback to 'none')
$vals = [];
foreach ($days as $d) {
  $v = strtolower(trim($_POST[$d] ?? 'none'));
  $vals[$d] = in_array($v, $allowed, true) ? $v : 'none';
}

try {
  $chk = $conn->prepare("SELECT id FROM availability WHERE user_id = :uid LIMIT 1");
  $chk->execute([':uid' => $user_id]);
  $exists = (bool) $chk->fetchColumn();

  if ($exists) {
    $sql = "UPDATE availability
            SET monday=:monday, tuesday=:tuesday, wednesday=:wednesday, thursday=:thursday,
                friday=:friday,  saturday=:saturday,  sunday=:sunday,
                updated_at = CURRENT_TIMESTAMP
            WHERE user_id = :uid";
  } else {
    $sql = "INSERT INTO availability
              (user_id, monday, tuesday, wednesday, thursday, friday, saturday, sunday, created_at, updated_at)
            VALUES
              (:uid,    :monday, :tuesday, :wednesday, :thursday, :friday, :saturday, :sunday, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)";
  }

  $stmt = $conn->prepare($sql);
  $ok = $stmt->execute([
    ':uid'       => $user_id,
    ':monday'    => $vals['monday'],
    ':tuesday'   => $vals['tuesday'],
    ':wednesday' => $vals['wednesday'],
    ':thursday'  => $vals['thursday'],
    ':friday'    => $vals['friday'],
    ':saturday'  => $vals['saturday'],
    ':sunday'    => $vals['sunday'],
  ]);

  $_SESSION['avail_flash'] = [
    'type' => $ok ? 'success' : 'danger',
    'text' => $ok ? 'Availability updated.' : 'Could not update your availability. Please try again.'
  ];
} catch (Throwable $e) {
  $_SESSION['avail_flash'] = ['type' => 'danger', 'text' => 'Error: '.$e->getMessage()];
}

header("Location: my_availability.php?saved=1");
exit;