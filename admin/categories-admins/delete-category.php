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
// Auth guard
if (!isset($_SESSION['adminname'])) {
  header("location: " . ADMINURL . "/admins/login-admins.php");
  exit;
}

// Expect id param
if (!isset($_GET['id']) || !ctype_digit($_GET['id'])) {
  header("Location: " . ADMINURL . "/categories-admins/show-categories.php?error=" . urlencode("Invalid category ID."));
  exit;
}

$id = (int)$_GET['id'];

try {
  // Optional: verify it exists first (gives nicer error)
  $chk = $conn->prepare("SELECT 1 FROM categories WHERE id = :id LIMIT 1");
  $chk->execute([':id' => $id]);
  if (!$chk->fetch()) {
    header("Location: " . ADMINURL . "/categories-admins/show-categories.php?error=" . urlencode("Category not found."));
    exit;
  }

  $del = $conn->prepare("DELETE FROM categories WHERE id = :id");
  $del->execute([':id' => $id]);

  // Success
  header("Location: " . ADMINURL . "/categories-admins/show-categories.php?deleted=1", true, 303);
  exit;

} catch (PDOException $e) {
  // Handle foreign key constraint (category in use)
  // MySQL/MariaDB: SQLSTATE 23000, driver-specific code 1451
  $msg = "Unable to delete category.";
  if ($e->getCode() === '23000') {
    $msg = "Cannot delete: this category is in use.";
  }
  header("Location: " . ADMINURL . "/categories-admins/show-categories.php?error=" . urlencode($msg), true, 303);
  exit;

} catch (Exception $e) {
  header("Location: " . ADMINURL . "/categories-admins/show-categories.php?error=" . urlencode("Unexpected error."), true, 303);
  exit;
}
