<?php require "../config/config.php";
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

    if(isset($_SESSION['type']) AND $_SESSION['type'] !== "Employer") {

        header("location: ".APPURL."");
        
    } 

    if (isset($_GET['id'])) {
    $id = intval($_GET['id']);

     // delete job
    $deleteJob = $conn->prepare("DELETE FROM jobs WHERE id = :id");
    $deleteJob->bindParam(':id', $id, PDO::PARAM_INT);
    $deleteJob->execute();

    // delete applications related to the job
    $deleteApps = $conn->prepare("DELETE FROM job_applications WHERE job_id = :id");
    $deleteApps->bindParam(':id', $id, PDO::PARAM_INT);
    $deleteApps->execute();

    header("location: ".APPURL."/users/postedjobs.php?msg=deleted");
    exit;
}
 else {
        echo "404";
    }
?>
<?php require "../includes/header.php"; ?>
<?php require "../includes/footer.php"; ?>
