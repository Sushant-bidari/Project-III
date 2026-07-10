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
<?php require "layouts/header.php"; ?>

<?php 

  if(!isset($_SESSION['adminname'])) {

    header("location: ".ADMINURL."/admins/login-admins.php");

  }
  $jobs = $conn->query("SELECT COUNT(*) AS count_jobs FROM jobs");
  $jobs->execute();

  $counJobs = $jobs->fetch(PDO::FETCH_OBJ);


  $categories = $conn->query("SELECT COUNT(*) AS count_cats FROM categories");
  $categories->execute();

  $counCategories = $categories->fetch(PDO::FETCH_OBJ);


  $admins = $conn->query("SELECT COUNT(*) AS count_admins FROM admins");
  $admins->execute();

  $counAdmins = $admins->fetch(PDO::FETCH_OBJ);

  $jobSeeker = $conn->query("SELECT COUNT(*) AS count_jobSeeker FROM users WHERE type = 'Job Seeker'");
  $jobSeeker->execute();

  $countjobSeeker = $jobSeeker->fetch(PDO::FETCH_OBJ);

  $employer = $conn->query("SELECT COUNT(*) AS count_employer FROM users WHERE type = 'Employer'");
  $employer->execute();

  $countemployer = $employer->fetch(PDO::FETCH_OBJ);

  $pendingJobs = $conn->query("SELECT COUNT(*) AS count_pending FROM jobs WHERE status = 0");
$pendingJobs->execute();
$counPendingJobs = $pendingJobs->fetch(PDO::FETCH_OBJ);
?>  

<style>
/* --- Dashboard Metric Tiles (v2) --- */
.ap-grid [class*="col-"] { margin-bottom: 1rem; }

.ap-card{
  border:0; border-radius:14px;
  background: linear-gradient(180deg,#fbfcfe 0%, #f3f6fb 100%);
  box-shadow: 0 10px 24px rgba(20, 27, 45, .06), 0 1px 0 rgba(20, 27, 45, .04);
  transition: transform .15s ease, box-shadow .2s ease;
}
.ap-card:hover{
  transform: translateY(-2px);
  box-shadow: 0 16px 34px rgba(20, 27, 45, .10), 0 2px 0 rgba(20, 27, 45, .05);
}

.ap-body{ padding: 18px 18px; }
.ap-meta{ display:flex; align-items:center; }

.ap-ring{
  position: relative;
  width:56px; height:56px; min-width:56px;
  display:flex; align-items:center; justify-content:center;
  border-radius:12px;
  background:#eef2ff;
}
.ap-ring::after{
  content:""; position:absolute; inset:-6px;
  border-radius:14px;
  border:2px solid rgba(39,46,62,.08);   /* subtle ring echo of your #272e3e */
}

.ap-icon{ font-size:1.25rem; line-height:1; }
.ap-kicker{
  margin:0; color:#6b7280; font-size:.85rem; letter-spacing:.01em;
}
.ap-value{
  font-weight:800; font-size:1.6rem; line-height:1.1; color:#111827;
}

/* Color accents for icons */
.ap-primary   .ap-ring{ background:#eef2ff; }
.ap-primary   .ap-icon{ color:#4f46e5; }

.ap-danger    .ap-ring{ background:#ffeef0; }
.ap-danger    .ap-icon{ color:#e11d48; }

.ap-success   .ap-ring{ background:#ecfdf5; }
.ap-success   .ap-icon{ color:#059669; }

.ap-warning   .ap-ring{ background:#fff7ed; }
.ap-warning   .ap-icon{ color:#d97706; }

/* Attention / Pending variant */
.ap-attn{
  background: linear-gradient(180deg,#fff9e6 0%, #fff4cc 100%);
  border-left: .4rem solid #f59e0b;
}
.ap-attn .ap-kicker{ color:#92400e; }
.ap-attn .ap-value{ color:#b45309; }

/* Make cards feel clickable but keep links unstyled */
a.ap-link{ text-decoration:none; color:inherit; }
a.ap-link:hover{ text-decoration:none; }

/* Mobile breathing */
@media (max-width: 575.98px){
  .ap-value{ font-size:1.45rem; }
}

</style>

      <section class="content">
  <div class="container-fluid">

    <!-- Metrics -->
    <div class="row ap-grid">
      <!-- Jobs Posted -->
      <div class="col-12 col-sm-6 col-lg-3">
        <a href="<?= ADMINURL ?>/jobs-admins/show-jobs.php" class="ap-link">
          <div class="card ap-card ap-primary h-100">
            <div class="ap-body">
              <div class="ap-meta">
                <div class="ap-ring mr-3">
                  <i class="fas fa-briefcase ap-icon"></i>
                </div>
                <div>
                  <p class="ap-kicker mb-1">Jobs Posted</p>
                  <div class="ap-value"><?= (int)$counJobs->count_jobs ?></div>
                </div>
              </div>
            </div>
          </div>
        </a>
      </div>

      <!-- Available Categories -->
      <div class="col-12 col-sm-6 col-lg-3">
        <a href="<?= ADMINURL ?>/categories-admins/show-categories.php" class="ap-link">
          <div class="card ap-card ap-danger h-100">
            <div class="ap-body">
              <div class="ap-meta">
                <div class="ap-ring mr-3">
                  <i class="fas fa-box-open ap-icon"></i>
                </div>
                <div>
                  <p class="ap-kicker mb-1">Available Categories</p>
                  <div class="ap-value"><?= (int)$counCategories->count_cats ?></div>
                </div>
              </div>
            </div>
          </div>
        </a>
      </div>

      <!-- Employers -->
      <div class="col-12 col-sm-6 col-lg-3">
        <a href="<?= ADMINURL ?>/users/show-employers.php" class="ap-link">
          <div class="card ap-card ap-success h-100">
            <div class="ap-body">
              <div class="ap-meta">
                <div class="ap-ring mr-3">
                  <i class="fas fa-building ap-icon"></i>
                </div>
                <div>
                  <p class="ap-kicker mb-1">Employers</p>
                  <div class="ap-value"><?= (int)$countemployer->count_employer ?></div>
                </div>
              </div>
            </div>
          </div>
        </a>
      </div>

      <!-- Job Seekers -->
      <div class="col-12 col-sm-6 col-lg-3">
        <a href="<?= ADMINURL ?>/users/show-jobseekers.php" class="ap-link">
          <div class="card ap-card ap-warning h-100">
            <div class="ap-body">
              <div class="ap-meta">
                <div class="ap-ring mr-3">
                  <i class="fas fa-users ap-icon"></i>
                </div>
                <div>
                  <p class="ap-kicker mb-1">Job Seekers</p>
                  <div class="ap-value"><?= (int)$countjobSeeker->count_jobSeeker ?></div>
                </div>
              </div>
            </div>
          </div>
        </a>
      </div>
    </div>

    <!-- Attention / Pending -->
    <div class="row ap-grid">
      <div class="col-12 col-sm-6 col-lg-4">
        <a href="<?= ADMINURL ?>/jobs-admins/pending-jobs.php" class="ap-link">
          <div class="card ap-card ap-attn h-100">
            <div class="ap-body">
              <div class="ap-meta">
                <div class="ap-ring mr-3" style="background:#fff; border:1px dashed rgba(217,119,6,.35)">
                  <i class="fas fa-exclamation-circle ap-icon" style="color:#b45309;"></i>
                </div>
                <div>
                  <p class="ap-kicker text-uppercase mb-1">Pending Jobs</p>
                  <div class="ap-value"><?= (int)$counPendingJobs->count_pending ?></div>
                  <small class="text-muted">Require immediate review</small>
                </div>
              </div>
            </div>
          </div>
        </a>
      </div>
    </div>

  </div>
</section>

   
<?php require "layouts/footer.php"; ?>           