<?php

require "../config/config.php";
require "../includes/job-recommendation.php";

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}


/*
 * Only Job Seekers can access this page.
 */
if (
    !isset($_SESSION['id']) ||
    !isset($_SESSION['type']) ||
    $_SESSION['type'] !== "Job Seeker"
) {
    header("location: " . APPURL);
    exit;
}


$userId = (int) $_SESSION['id'];


/*
 * Get recommended jobs.
 */
$recommendedJobs = getRecommendedJobs(
    $conn,
    $userId,
    12
);


require "../includes/header.php";
?>


<style>

/* =========================================================
   RECOMMENDED JOB CARD
   ========================================================= */

.recommended-job-card {
    border: 1px solid #e9ecef;
    border-radius: 10px;
    overflow: hidden;
    transition: all 0.2s ease;
    background: #ffffff;
}

.recommended-job-card:hover {
    transform: translateY(-3px);
    box-shadow: 0 8px 25px rgba(0, 0, 0, 0.08);
}


/* =========================================================
   COMPANY HEADER
   ========================================================= */

.company-box {
    height: 120px;
    background: #f5f7fa;
    display: flex;
    align-items: center;
    justify-content: center;
    border-bottom: 1px solid #e9ecef;
}

.company-logo-circle {
    width: 70px;
    height: 70px;
    border-radius: 50%;
    background: #0d6efd;
    color: #ffffff;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 28px;
    font-weight: 700;
    text-transform: uppercase;
}

.company-name-header {
    font-size: 18px;
    font-weight: 600;
    color: #495057;
    text-align: center;
}


/* =========================================================
   MATCH BADGE
   ========================================================= */

.match-badge {
    display: inline-block !important;

    background-color: #198754 !important;
    color: #ffffff !important;

    font-size: 13px !important;
    font-weight: 700 !important;

    padding: 6px 10px !important;

    border-radius: 6px !important;

    line-height: 1.2 !important;

    opacity: 1 !important;
}


/* =========================================================
   JOB TITLE
   ========================================================= */

.recommended-job-title {
    color: #495057;
    font-size: 21px;
    font-weight: 500;
    margin-bottom: 15px;
}


/* =========================================================
   JOB INFORMATION
   ========================================================= */

.job-info {
    color: #6c757d;
    margin-bottom: 7px;
}

.job-info strong {
    color: #343a40;
}


/* =========================================================
   DESCRIPTION
   ========================================================= */

.job-description {
    color: #6c757d;
    line-height: 1.7;
    margin-top: 12px;
    margin-bottom: 20px;
}


/* =========================================================
   VIEW JOB BUTTON
   ========================================================= */

.view-job-btn {
    background-color: #0d6efd !important;
    border-color: #0d6efd !important;
    color: #ffffff !important;

    font-weight: 500;

    padding: 10px 15px;

    border-radius: 5px;
}

.view-job-btn:hover {
    background-color: #0b5ed7 !important;
    border-color: #0a58ca !important;
    color: #ffffff !important;
}

</style>


<div class="container py-5">


    <!-- =====================================================
         PAGE HEADER
         ===================================================== -->

    <div class="mb-4">

        <h2 class="fw-bold">
            Recommended Jobs
        </h2>

        <p class="text-muted">
            Jobs recommended based on your skills, profile,
            education, job title, and location.
        </p>

    </div>


    <?php if (empty($recommendedJobs)): ?>


        <!-- =================================================
             NO JOBS
             ================================================= -->

        <div class="alert alert-info">

            No recommended jobs are available yet.

            Update your profile and skills to receive
            better recommendations.

        </div>


    <?php else: ?>


        <div class="row g-4">


            <?php foreach ($recommendedJobs as $job): ?>


                <?php

                /*
                 * Get company name safely.
                 */
                $companyName = trim(
                    (string) ($job['company_name'] ?? '')
                );

                if ($companyName === '') {
                    $companyName = 'Company';
                }


                /*
                 * Get first letter of company name.
                 */
                $companyInitial = strtoupper(
                    mb_substr(
                        $companyName,
                        0,
                        1,
                        'UTF-8'
                    )
                );


                /*
                 * Clean job description.
                 *
                 * Example database value:
                 *
                 * &lt;p&gt;We are seeking...&lt;/p&gt;
                 *
                 * First decode entities.
                 */
                $description = html_entity_decode(
                    (string) ($job['job_description'] ?? ''),
                    ENT_QUOTES | ENT_HTML5,
                    'UTF-8'
                );


                /*
                 * Remove HTML tags.
                 */
                $description = strip_tags(
                    $description
                );


                /*
                 * Remove extra spaces.
                 */
                $description = preg_replace(
                    '/\s+/',
                    ' ',
                    $description
                );


                $description = trim(
                    $description
                );


                /*
                 * Limit description to 140 characters.
                 */
                if (
                    mb_strlen(
                        $description,
                        'UTF-8'
                    ) > 140
                ) {

                    $description = mb_strimwidth(
                        $description,
                        0,
                        140,
                        '...',
                        'UTF-8'
                    );
                }

                ?>


                <!-- =================================================
                     JOB CARD
                     ================================================= -->

                <div class="col-md-6 col-lg-4">

                    <div class="card h-100 shadow-sm recommended-job-card">


                        <!-- =================================================
                             COMPANY NAME / LOGO
                             ================================================= -->

                        <div class="company-box">

                            <div class="text-center">

                                <div class="company-logo-circle mx-auto mb-2">

                                    <?php
                                    echo htmlspecialchars(
                                        $companyInitial,
                                        ENT_QUOTES,
                                        'UTF-8'
                                    );
                                    ?>

                                </div>


                                <div class="company-name-header">

                                    <?php
                                    echo htmlspecialchars(
                                        $companyName,
                                        ENT_QUOTES,
                                        'UTF-8'
                                    );
                                    ?>

                                </div>

                            </div>

                        </div>


                        <!-- =================================================
                             CARD BODY
                             ================================================= -->

                        <div class="card-body d-flex flex-column">


                            <!-- MATCH SCORE -->

                            <div class="mb-3">

                                <span class="match-badge">

                                    <?php
                                    echo htmlspecialchars(
                                        $job['recommendation_score'],
                                        ENT_QUOTES,
                                        'UTF-8'
                                    );
                                    ?>% Match

                                </span>

                            </div>


                            <!-- JOB TITLE -->

                            <h5 class="recommended-job-title">

                                <?php
                                echo htmlspecialchars(
                                    $job['job_title'],
                                    ENT_QUOTES,
                                    'UTF-8'
                                );
                                ?>

                            </h5>


                            <!-- COMPANY -->

                            <p class="job-info">

                                <strong>
                                    Company:
                                </strong>

                                <?php
                                echo htmlspecialchars(
                                    $companyName,
                                    ENT_QUOTES,
                                    'UTF-8'
                                );
                                ?>

                            </p>


                            <!-- REGION -->

                            <p class="job-info">

                                <strong>
                                    Region:
                                </strong>

                                <?php
                                echo htmlspecialchars(
                                    $job['job_region'],
                                    ENT_QUOTES,
                                    'UTF-8'
                                );
                                ?>

                            </p>


                            <!-- JOB TYPE -->

                            <p class="job-info">

                                <strong>
                                    Type:
                                </strong>

                                <?php
                                echo htmlspecialchars(
                                    $job['job_type'],
                                    ENT_QUOTES,
                                    'UTF-8'
                                );
                                ?>

                            </p>


                            <!-- CATEGORY -->

                            <p class="job-info mb-3">

                                <strong>
                                    Category:
                                </strong>

                                <?php
                                echo htmlspecialchars(
                                    $job['job_category'],
                                    ENT_QUOTES,
                                    'UTF-8'
                                );
                                ?>

                            </p>


                            <!-- =================================================
                                 JOB DESCRIPTION
                                 ================================================= -->

                            <p class="job-description">

                                <?php
                                echo htmlspecialchars(
                                    $description,
                                    ENT_QUOTES,
                                    'UTF-8'
                                );
                                ?>

                            </p>


                            <!-- =================================================
                                 VIEW JOB
                                 ================================================= -->

                            <div class="mt-auto">

                                <a
                                    href="<?php
                                        echo APPURL;
                                    ?>/jobs/job-single.php?id=<?php
                                        echo (int) $job['id'];
                                    ?>"
                                    class="btn view-job-btn w-100"
                                >
                                    View Job
                                </a>

                            </div>


                        </div>

                    </div>

                </div>


            <?php endforeach; ?>


        </div>


    <?php endif; ?>


</div>


<?php require "../includes/footer.php"; ?>