<?php
require "../../config/config.php";
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
// Auth
if (!isset($_SESSION['adminname'])) {
  header("Location: " . ADMINURL . "/admins/login-admins.php");
  exit;
}

// Validate inputs
if (!isset($_GET['id'], $_GET['status']) || !ctype_digit($_GET['id'])) {
  header("Location: " . ADMINURL . "/404.php");
  exit;
}

$id        = (int)$_GET['id'];
$currStat  = (int)$_GET['status']; // current status reported by the list

// Build a safe redirect target (default back to jobs list)
$to = ADMINURL . "/jobs-admins/show-jobs.php";
if (!empty($_GET['r'])) {
  $candidate = $_GET['r'];
  // Allow only same-origin paths (no protocol/host change)
  $p = parse_url($candidate);
  if (is_array($p)) {
    $isRelative = !isset($p['scheme']) && !isset($p['host']);
    if ($isRelative && strpos($candidate, '..') === false) {
      $to = $candidate;
    }
  }
}

try {
  // Make sure job exists (and get title for flash)
  $jobStmt = $conn->prepare("SELECT job_title, status FROM jobs WHERE id = :id LIMIT 1");
  $jobStmt->execute([':id' => $id]);
  $jobRow = $jobStmt->fetch(PDO::FETCH_ASSOC);

  if (!$jobRow) {
    $_SESSION['admin_flash'] = ['type' => 'danger', 'text' => "Job #{$id} not found."];
    header("Location: " . $to, true, 303);
    exit;
  }

  $label     = $jobRow['job_title'] ? "“{$jobRow['job_title']}”" : "Job #{$id}";
  $newStatus = ($currStat === 1 ? 0 : 1); // toggle

  // Update
  $stmt = $conn->prepare("UPDATE jobs SET status = :s WHERE id = :id");
  $ok   = $stmt->execute([':s' => $newStatus, ':id' => $id]);

  if ($ok && $stmt->rowCount() > 0) {
    $verb = ($newStatus === 1) ? 'approved/published' : 'unpublished';
    $_SESSION['admin_flash'] = ['type' => 'success', 'text' => "{$label} has been {$verb}."];
  } else {
    $_SESSION['admin_flash'] = ['type' => 'warning', 'text' => "No changes were made to {$label}."];
  }

} catch (Exception $e) {
  $_SESSION['admin_flash'] = ['type' => 'danger', 'text' => "Failed to update {$label}. Please try again."];
}

// Redirect back
header("Location: " . $to, true, 303);
exit;
