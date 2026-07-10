<?php
require "../config/config.php";
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
// Gate: employer only
if (!isset($_SESSION['type']) || $_SESSION['type'] !== "Employer") {
  header("location: " . APPURL);
  exit;
}
$employerId = (int)($_SESSION['id'] ?? 0);

// KPIs
$kpis = [
  'jobs'        => 0,
  'apps_total'  => 0,
  'apps_pending'=> 0,
  'apps_proc'   => 0,
  'apps_rej'    => 0,
  'apps_sel'    => 0,
  'apps_wd'     => 0
];

// Jobs posted
$st = $conn->prepare("SELECT COUNT(*) FROM jobs WHERE company_id = :cid");
$st->execute([':cid'=>$employerId]);
$kpis['jobs'] = (int)$st->fetchColumn();

// Applicants (total + by status)
$st = $conn->prepare("
  SELECT
    COUNT(*) AS total,
    SUM(CASE WHEN application_status=0 THEN 1 ELSE 0 END) AS pending,
    SUM(CASE WHEN application_status=1 THEN 1 ELSE 0 END) AS processing,
    SUM(CASE WHEN application_status=2 THEN 1 ELSE 0 END) AS rejected,
    SUM(CASE WHEN application_status=3 THEN 1 ELSE 0 END) AS selected,
    SUM(CASE WHEN application_status=4 THEN 1 ELSE 0 END) AS withdrawn
  FROM job_applications
  WHERE company_id = :cid
");
$st->execute([':cid'=>$employerId]);
$row = $st->fetch(PDO::FETCH_ASSOC) ?: [];
$kpis['apps_total']   = (int)($row['total'] ?? 0);
$kpis['apps_pending'] = (int)($row['pending'] ?? 0);
$kpis['apps_proc']    = (int)($row['processing'] ?? 0);
$kpis['apps_rej']     = (int)($row['rejected'] ?? 0);
$kpis['apps_sel']     = (int)($row['selected'] ?? 0);
$kpis['apps_wd']      = (int)($row['withdrawn'] ?? 0);

// Recent 5 applicants
$recent = $conn->prepare("
  SELECT a.id AS app_id, a.job_title, a.created_at, a.application_status,
         u.fullname
  FROM job_applications a
  JOIN users u ON u.id = a.worker_id
  WHERE a.company_id = :cid
  ORDER BY a.created_at DESC
  LIMIT 5
");
$recent->execute([':cid'=>$employerId]);
$recentApps = $recent->fetchAll(PDO::FETCH_OBJ);

require "../includes/header.php";
?>
<style>
  .emp-wrap { padding-top: 2rem; }
  .emp-sidebar {
    background: #fff; border: 0; border-radius: 14px;
    box-shadow: 0 8px 24px rgba(0,0,0,.06);
    position: sticky; top: 82px;
  }
  .emp-sidebar .list-group-item{
    border:0; border-left:3px solid transparent; border-radius: 0;
  }
  .emp-sidebar .list-group-item:hover{ background:#f8fafc; text-decoration: none;}
  .emp-sidebar .list-group-item.active{
    background:#eef2ff; color:#111827; border-left-color:#6366f1; font-weight:700; text-decoration: none;
  }
  .emp-card{
    background:#fff; border:0; border-radius:14px; box-shadow:0 8px 24px rgba(0,0,0,.06);
  }
  .kpi{
    border-radius:14px; background:#f8fafc; padding:1rem; border:1px solid #eef2f7;
  }
  .kpi h3{ margin:0; font-weight:800; }
  .kpi .sub{ color:#6b7280; font-size:.85rem; }

  /* KPI with right icon */
.kpi{
  display:flex; align-items:center; justify-content:space-between;
}
.kpi .kpi-icon{
  flex:0 0 44px; width:44px; height:44px;
  border-radius:10px; background:#eef2ff; color:#4f46e5;
  display:flex; align-items:center; justify-content:center;
}
.kpi .kpi-icon i{ font-size:1.1rem; }
.kpi .sub{ margin-bottom:2px; }

</style>

<div class="container-fluid emp-wrap">
  <div class="row">
    <!-- Sidebar -->
    <aside class="col-lg-3 mb-4">
      <div class="emp-sidebar p-2">
        <div class="list-group list-group-flush">
          <a href="<?php echo $base_url; ?>/users/employer_dashboard.php" class="list-group-item active">
            <i class="fa-solid fa-gauge mr-2"></i> Dashboard
          </a>
          <a href="<?php echo $base_url; ?>/jobs/post-job.php" class="list-group-item">
            <i class="fa-solid fa-plus mr-2"></i> Post a Job
          </a>
          <a href="<?php echo $base_url; ?>/users/show-applicants.php?id=<?php echo $employerId; ?>" class="list-group-item">
            <i class="fa-solid fa-users-line mr-2"></i> Show Applicants
          </a>
          <a href="<?php echo $base_url; ?>/users/postedJobs.php?id=<?php echo $employerId; ?>" class="list-group-item">
            <i class="fa-solid fa-briefcase mr-2"></i> Posted Jobs
          </a>
          <a href="<?php echo $base_url; ?>/users/employer_insights.php" class="list-group-item">
          <i class="fa-solid fa-chart-line mr-2"></i> Insights
        </a>

        </div>
      </div>
    </aside>

    <!-- Main -->
    <main class="col-lg-9">
      <div class="emp-card p-3 mb-3">
        <div class="d-flex align-items-center">
          <h4 class="mb-0">Employer Dashboard</h4>
        </div>
      </div>

      <div class="emp-card p-3">
        <div class="row">
  <div class="col-sm-6 col-md-4 mb-3">
    <div class="kpi">
      <div>
        <div class="sub">Jobs Posted</div>
        <h3><?php echo number_format($kpis['jobs']); ?></h3>
      </div>
      <div class="kpi-icon"><i class="fa-solid fa-briefcase"></i></div>
    </div>
  </div>

  <div class="col-sm-6 col-md-4 mb-3">
    <div class="kpi">
      <div>
        <div class="sub">Total Applicants</div>
        <h3><?php echo number_format($kpis['apps_total']); ?></h3>
      </div>
      <div class="kpi-icon"><i class="fa-solid fa-users"></i></div>
    </div>
  </div>

  <div class="col-sm-6 col-md-4 mb-3">
    <div class="kpi">
      <div>
        <div class="sub">Pending</div>
        <h3><?php echo number_format($kpis['apps_pending']); ?></h3>
      </div>
      <div class="kpi-icon"><i class="fa-solid fa-hourglass-half"></i></div>
    </div>
  </div>

  <div class="col-sm-6 col-md-4 mb-3">
    <div class="kpi">
      <div>
        <div class="sub">Processing</div>
        <h3><?php echo number_format($kpis['apps_proc']); ?></h3>
      </div>
      <div class="kpi-icon"><i class="fa-solid fa-spinner"></i></div>
    </div>
  </div>

  <div class="col-sm-6 col-md-4 mb-3">
    <div class="kpi">
      <div>
        <div class="sub">Selected</div>
        <h3><?php echo number_format($kpis['apps_sel']); ?></h3>
      </div>
      <div class="kpi-icon"><i class="fa-solid fa-circle-check"></i></div>
    </div>
  </div>

  <div class="col-sm-6 col-md-4 mb-3">
    <div class="kpi">
      <div>
        <div class="sub">Rejected</div>
        <h3><?php echo number_format($kpis['apps_rej']); ?></h3>
      </div>
      <div class="kpi-icon"><i class="fa-solid fa-circle-xmark"></i></div>
    </div>
  </div>

  <div class="col-sm-6 col-md-4 mb-3">
    <div class="kpi">
      <div>
        <div class="sub">Withdrawn</div>
        <h3><?php echo number_format($kpis['apps_wd']); ?></h3>
      </div>
      <div class="kpi-icon"><i class="fa-solid fa-arrow-rotate-left"></i></div>
    </div>
  </div>
</div>

      </div>

      <?php if ($recentApps): ?>
      <div class="emp-card p-3 mt-3">
        <h5 class="mb-3">Recent Applications</h5>
        <div class="table-responsive">
          <table class="table table-sm table-hover">
            <thead><tr>
              <th>Applicant</th><th>Job Title</th><th>Applied</th><th>Status</th>
            </tr></thead>
            <tbody>
              <?php
              $labels = ['Pending','Processing','Selected','Rejected','Withdrawn'];
              foreach ($recentApps as $r):
                $lbl = $labels[(int)$r->application_status] ?? 'Updated';
              ?>
                <tr>
                  <td><?php echo htmlspecialchars($r->fullname); ?></td>
                  <td><?php echo htmlspecialchars($r->job_title); ?></td>
                  <td><?php echo date('d M, Y', strtotime($r->created_at)); ?></td>
                  <td><?php echo htmlspecialchars($lbl); ?></td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
        <div class="text-right">
          <a href="<?php echo $base_url; ?>/users/show-applicants.php?id=<?php echo $employerId; ?>" class="btn btn-outline-primary btn-sm">
            View all applicants
          </a>
        </div>
      </div>
      <?php endif; ?>

    </main>
  </div>
</div>

<?php require "../includes/footer.php"; ?>
