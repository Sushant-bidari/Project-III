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
<?php require "../layouts/header.php"; ?>

<?php 

    if(!isset($_SESSION['adminname'])) {

        header("location: ".ADMINURL."/admins/login-admins.php");

    }

    if(isset($_GET['id'])) {

        $id = $_GET['id'];

        $delete = $conn->prepare("DELETE FROM jobs WHERE id = '$id'");
        $delete->execute();
        header("location: ".ADMINURL."/jobs-admins/show-jobs.php");

    } else {
        header("location: ".ADMINURL."/404.php");
    }


?>


<?php require "../layouts/footer.php"; ?>           
