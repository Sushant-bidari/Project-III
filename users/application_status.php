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
<?php require "../includes/header.php"; ?>
<?php 

    if(!isset($_SESSION['type']) AND $_SESSION['type'] !== "Employer") {
                
      header("location: ".APPURL."");

    }

    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        if (isset($_POST["job_id"], $_POST["status"])) {
            $jobId = $_POST["job_id"];
            $status = $_POST["status"];
    
            try {
                // Update the application status in the database
                $updateStatus = $conn->prepare("UPDATE job_applications SET application_status = :status WHERE job_id = :jobId");
                $updateStatus->execute([
                    ':status' => $status,
                    ':jobId' => $jobId
                ]);
    
                // to check if any rows were affected
                if ($updateStatus->rowCount() > 0) {
                    // redirecting back to  list of applicants with a success message using js
                    echo '<script type="text/javascript">
                        alert("Status updated successfully");
                        window.location.href = "show-applicants.php?id=' . $_SESSION['id'] . '";
                    </script>';
                } else {
                    $errorMessage = "Failed to update the status. The job ID may not exist.";
                }
            } catch (PDOException $e) {
                // handle database errors
                $errorMessage = "Database error: " . $e->getMessage();
            }
        }
    }
    
    // checking if the 'id' query parameter is set
    if (isset($_GET["id"])) {
        $jobId = $_GET["id"];
    
        // retrieveing the applicant details based on $jobId from the database
        $getApplicant = $conn->query("SELECT * FROM job_applications WHERE job_id = '$jobId'");
        $getApplicant->execute();
        $applicant = $getApplicant->fetch(PDO::FETCH_OBJ);
    
        // to check if an applicant with the given job ID exists
        if (!$applicant) {
            $errorMessage = "Applicant not found.";
        }
    } else {
        // handle the case where 'id' is not set
        $errorMessage = "No applicant ID provided.";
    }
?>


<style>
  .site-section{
    padding-top: 3rem;
  }
</style>

<!-- <section class="section-hero overlay inner-page bg-image" style="background-image: url('<?php echo $base_url; ?>/images/tst.jpg');" id="home-section">
      
</section> -->

<div class="container">
        <div class="row">
          <div class="col-md-12 text-center">
            <br>
            <h3>Update Application Status</h3>
            
          </div>
        </div>
      </div>

<section class="site-section">
<div class="container d-flex justify-content-center align-items-center">
        <div class="text-center">
            <?php if (isset($errorMessage)) : ?>
                <p style="color: red;"><?php echo $errorMessage; ?></p>
            <?php endif; ?>
        </div>
        <form method="post">
            <div class="form-group">
                <input type="hidden" name="job_id" value="<?php echo $jobId; ?>">
                <label for="status">Select Status:</label>
                <select class="form-control" name="status">
                    <option value="0">Pending</option>
                    <option value="1">Processing</option>
                    <option value="2">Rejected</option>
                    <option value="3">Success</option>
                </select>
            </div>
            <div class="text-center">
                <button type="submit" class="btn btn-primary">Update Status</button>
            </div>
        </form>
    </div>
</section>

<?php require "../includes/footer.php"; ?>
