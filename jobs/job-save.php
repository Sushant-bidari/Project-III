<?php require "../config/config.php";



    if(isset($_GET['job_id']) AND isset($_GET['worker_id']) AND isset($_GET['status'])) {

        $job_id = $_GET['job_id'];
        $worker_id = $_GET['worker_id'];
        $status = $_GET['status'];

        if ($status == 'save') {
    $insert = $conn->prepare("INSERT INTO saved_jobs(job_id, worker_id) VALUES(:job_id, :worker_id)");
    $insert->execute([
        ':job_id' => $job_id,
        ':worker_id' => $worker_id,
    ]);

    header("Location: " . APPURL . "/jobs/job-single.php?id=" . $job_id . "&saved=1");
    exit;
    } else {
        $delete = $conn->prepare("DELETE FROM saved_jobs WHERE job_id = :job_id AND worker_id = :worker_id");
        $delete->execute([
            ':job_id' => $job_id,
            ':worker_id' => $worker_id,
        ]);

        header("Location: " . APPURL . "/jobs/job-single.php?id=" . $job_id . "&removed=1");
        exit;
    }

       
    }

require "../includes/header.php";
require "../includes/footer.php"; ?>
