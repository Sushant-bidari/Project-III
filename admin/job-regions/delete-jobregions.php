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
// Auth
if (!isset($_SESSION['adminname'])) {
  header("location: " . ADMINURL . "/admins/login-admins.php");
  exit;
}

// Validate id
if (!isset($_GET['id']) || !ctype_digit($_GET['id'])) {
  header("Location: " . ADMINURL . "/job-regions/show-jobregions.php?error=" . urlencode("Invalid region ID."));
  exit;
}
$id = (int)$_GET['id'];

try {
  // Optional: verify existence first
  $chk = $conn->prepare("SELECT 1 FROM job_regions WHERE id = :id LIMIT 1");
  $chk->execute([':id' => $id]);
  if (!$chk->fetch()) {
    header("Location: " . ADMINURL . "/job-regions/show-jobregions.php?error=" . urlencode("Job region not found."));
    exit;
  }

  // Delete
  $del = $conn->prepare("DELETE FROM job_regions WHERE id = :id");
  $del->execute([':id' => $id]);

  header("Location: " . ADMINURL . "/job-regions/show-jobregions.php?deleted=1", true, 303);
  exit;

} catch (PDOException $e) {
  // FK constraint (in use) -> SQLSTATE 23000
  $msg = "Unable to delete job region.";
  if ($e->getCode() === '23000') {
    $msg = "Cannot delete: this region is referenced by other records.";
  }
  header("Location: " . ADMINURL . "/job-regions/show-jobregions.php?error=" . urlencode($msg), true, 303);
  exit;

} catch (Exception $e) {
  header("Location: " . ADMINURL . "/job-regions/show-jobregions.php?error=" . urlencode("Unexpected error."), true, 303);
  exit;
}
