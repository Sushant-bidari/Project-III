<?php
session_start();

$base_url = (isset($_SERVER['HTTPS']) ? "https" : "http") . "://" . $_SERVER['HTTP_HOST'];
$project_folder = explode('/', trim($_SERVER['SCRIPT_NAME'], '/'))[0];
$base_url .= '/' . $project_folder;

// Database config
try {
    $host = "localhost";
    $dbname = "project_iii";
    $user = "root";
    $pass = "";

    $conn = new PDO("mysql:host=$host;dbname=$dbname", $user, $pass);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Use base_url instead of hardcoded APPURL
    define("APPURL", $base_url);

} catch (PDOException $e) {
    echo $e->getMessage();
}

// Employer pending application count
$pendingApplications = 0;
if (isset($_SESSION['id']) && $_SESSION['type'] === 'Employer') {
    $employer_id = $_SESSION['id'];

    $stmt = $conn->prepare("SELECT COUNT(*) as pending_count FROM job_applications WHERE company_id = :employer_id AND application_status = 0");
    $stmt->execute(['employer_id' => $employer_id]);
    $result = $stmt->fetch(PDO::FETCH_OBJ);

    $pendingApplications = $result->pending_count;
}

// defining ADMINURL globally for all admin pages (once)
if (!defined('ADMINURL')) {
  $protocol      = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ? "https" : "http";
  $host          = $_SERVER['HTTP_HOST'];
  $scriptDir     = dirname($_SERVER['SCRIPT_NAME']);      //  /online-job-portal-php-mysql/admin-panel/jobs-admins
  $projectFolder = explode('/', trim($scriptDir, '/'))[0]; // online-job-portal-php-mysql
  define('ADMINURL', "$protocol://$host/$projectFolder/admin");
}