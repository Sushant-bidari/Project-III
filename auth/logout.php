<?php
session_start();
session_unset();
session_destroy();

// Dynamically get base project folder from the script path
$base_url = (isset($_SERVER['HTTPS']) ? "https" : "http") . "://" . $_SERVER['HTTP_HOST'];
$project_folder = explode('/', trim($_SERVER['SCRIPT_NAME'], '/'))[0];
$base_url .= '/' . $project_folder;

header("Location: $base_url");
exit;
?>
