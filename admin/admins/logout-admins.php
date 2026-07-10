<?php 
session_start();
session_unset();
session_destroy();

// Dynamically get the base URL
$base_url = (isset($_SERVER['HTTPS']) ? "https" : "http") . "://" . $_SERVER['HTTP_HOST'];

// Get the project folder name dynamically
$project_folder = explode('/', trim($_SERVER['SCRIPT_NAME'], '/'))[0];

// Build full redirect path to login-admins.php
$redirect_url = $base_url . '/' . $project_folder . '/admin/admins/login-admins.php';

// Redirect
header("Location: $redirect_url");
exit;
?>
