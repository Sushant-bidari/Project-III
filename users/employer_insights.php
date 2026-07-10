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

// ---------- Data: per-job aggregates ----------
$jobs = $conn->prepare("
  SELECT j.id, j.job_title, COALESCE(j.view_count,0) AS views
  FROM jobs j
  WHERE j.company_id = :cid
  ORDER BY j.created_at DESC, j.id DESC
");
$jobs->execute([':cid'=>$employerId]);
$jobRows = $jobs->fetchAll(PDO::FETCH_ASSOC);

$jobIds = array_map(fn($r)=> (int)$r['id'], $jobRows);
$appCounts = [];
if ($jobIds) {
  $in = implode(',', array_fill(0, count($jobIds), '?'));
  $q  = $conn->prepare("
    SELECT job_id, COUNT(*) AS apps
    FROM job_applications
    WHERE job_id IN ($in)
    GROUP BY job_id
  ");
  $q->execute($jobIds);
  while ($r = $q->fetch(PDO::FETCH_ASSOC)) {
    $appCounts[(int)$r['job_id']] = (int)$r['apps'];
  }
}


$table = [];
foreach ($jobRows as $r) {
  $jid   = (int)$r['id'];
  $views = (int)$r['views'];
  $apps  = $appCounts[$jid] ?? 0;
  $conv  = $views > 0 ? ($apps / $views) * 100 : 0.0;

  // Color: >=10% green, 3–10% orange, else red
  $band  = ($conv >= 10) ? 'success' : (($conv >= 3) ? 'warning' : 'danger');

  $table[] = [
    'id' => $jid,
    'title' => $r['job_title'],
    'views' => $views,
    'apps'  => $apps,
    'conv'  => round($conv, 2),
    'band'  => $band
  ];
}

// Default selected job for charts (respect ?job_id= if valid)
$selectedJobId = 0;
if (!empty($table)) {
  $requested = isset($_GET['job_id']) ? (int)$_GET['job_id'] : 0;
  $validIds  = array_column($table, 'id');
  $selectedJobId = $requested && in_array($requested, $validIds, true)
    ? $requested
    : (int)$table[0]['id'];
}


// Range: last 30 days
$days = 30;
$start = (new DateTime('today -'.($days-1).' days'))->format('Y-m-d');
$end   = (new DateTime('today'))->format('Y-m-d');

// ---------- Timeseries (views/applications) for selected job ----------
function daySpan($start, $end) {
  $out = [];
  $sd = new DateTime($start);
  $ed = new DateTime($end);
  for ($d = clone $sd; $d <= $ed; $d->modify('+1 day')) {
    $out[] = $d->format('Y-m-d');
  }
  return $out;
}
$labels = daySpan($start, $end);

// Views/day from job_views
$viewsByDay = array_fill_keys($labels, 0);
if ($selectedJobId) {
  $vst = $conn->prepare("
    SELECT viewed_date AS d, COUNT(*) AS c
    FROM job_views
    WHERE company_id = :cid AND job_id = :jid AND viewed_date BETWEEN :s AND :e
    GROUP BY viewed_date
    ORDER BY viewed_date
  ");
  $vst->execute([':cid'=>$employerId, ':jid'=>$selectedJobId, ':s'=>$start, ':e'=>$end]);
  foreach ($vst as $row) { $viewsByDay[$row['d']] = (int)$row['c']; }
}

// Applications/day from job_applications
$appsByDay = array_fill_keys($labels, 0);
if ($selectedJobId) {
  $ast = $conn->prepare("
    SELECT DATE(created_at) AS d, COUNT(*) AS c
    FROM job_applications
    WHERE company_id = :cid AND job_id = :jid AND DATE(created_at) BETWEEN :s AND :e
    GROUP BY DATE(created_at)
    ORDER BY d
  ");
  $ast->execute([':cid'=>$employerId, ':jid'=>$selectedJobId, ':s'=>$start, ':e'=>$end]);
  foreach ($ast as $row) { $appsByDay[$row['d']] = (int)$row['c']; }
}

// Pack chart payload
$chartPayload = [
  'labels' => array_map(fn($d)=>(new DateTime($d))->format('M j'), $labels),
  'views'  => array_values($viewsByDay),
  'apps'   => array_values($appsByDay)
];

// ---- opt AJAX for timeseries ----
if (!empty($_GET['ajax']) && $_GET['ajax']==='ts') {
  header('Content-Type: application/json');
  $jid = (int)($_GET['job_id'] ?? 0);

  $start = (new DateTime('today -29 days'))->format('Y-m-d');
  $end   = (new DateTime('today'))->format('Y-m-d');
  $labels = (function($start,$end){
    $out=[]; $sd=new DateTime($start); $ed=new DateTime($end);
    for($d=clone $sd;$d<=$ed;$d->modify('+1 day')) $out[]=$d->format('Y-m-d');
    return $out;
  })($start,$end);

  $views = array_fill_keys($labels, 0);
  $apps  = array_fill_keys($labels, 0);

  if ($jid) {
    $vst = $conn->prepare("
      SELECT viewed_date AS d, COUNT(*) AS c
      FROM job_views
      WHERE company_id=:cid AND job_id=:jid AND viewed_date BETWEEN :s AND :e
      GROUP BY viewed_date
    ");
    $vst->execute([':cid'=>$employerId, ':jid'=>$jid, ':s'=>$start, ':e'=>$end]);
    foreach ($vst as $r) { $views[$r['d']] = (int)$r['c']; }

    $ast = $conn->prepare("
      SELECT DATE(created_at) AS d, COUNT(*) AS c
      FROM job_applications
      WHERE company_id=:cid AND job_id=:jid AND DATE(created_at) BETWEEN :s AND :e
      GROUP BY DATE(created_at)
    ");
    $ast->execute([':cid'=>$employerId, ':jid'=>$jid, ':s'=>$start, ':e'=>$end]);
    foreach ($ast as $r) { $apps[$r['d']] = (int)$r['c']; }
  }

  echo json_encode([
    'labels' => array_map(fn($d)=>(new DateTime($d))->format('M j'), $labels),
    'views'  => array_values($views),
    'apps'   => array_values($apps)
  ]);
  exit;
}


require "../includes/header.php";
?>
<style>
  .emp-wrap { padding-top: 2rem; }
  .emp-sidebar {
    background:#fff;border:0;border-radius:14px;box-shadow:0 8px 24px rgba(0,0,0,.06);
    position:sticky;top:82px;
  }
  .emp-sidebar .list-group-item{
    border:0;border-left:3px solid transparent;border-radius:0;
  }
  .emp-sidebar .list-group-item:hover{ background:#f8fafc; text-decoration:none; }
  .emp-sidebar .list-group-item.active{
    background:#eef2ff;color:#111827;border-left-color:#6366f1;font-weight:700;text-decoration:none;
  }
  .emp-card{
    background:#fff;border:0;border-radius:14px;box-shadow:0 8px 24px rgba(0,0,0,.06);
  }

  /* Analytics */
  .insight-grid{ display:grid; grid-template-columns:repeat(auto-fit,minmax(260px,1fr)); gap:14px; }
  .insight{
    border:1px solid #eef2f7;border-radius:14px;padding:14px;background:#f9fafb;
  }
  .insight h6{ margin:0 0 6px 0; color:#6b7280; font-weight:600; letter-spacing:.2px; }
  .insight .big{ font-size:1.6rem; font-weight:800; }
  .insight .sub{ color:#6b7280; font-size:.85rem; margin-top:2px; }
  .badge-pill{ padding:.4rem .6rem; font-weight:600; }
  .chart-card{ padding:14px; }

  
</style>
<style>
  .chart-box{ position:relative; height:260px; } 
  .chart-card{ padding:14px; }
</style>


<div class="container-fluid emp-wrap">
  <div class="row">
    <!-- Sidebar -->
    <aside class="col-lg-3 mb-4">
      <div class="emp-sidebar p-2">
        <div class="list-group list-group-flush">
          <a href="<?php echo $base_url; ?>/users/employer_dashboard.php" class="list-group-item">
            <i class="fa-solid fa-gauge mr-2"></i> Dashboard
          </a>
          <a href="<?php echo $base_url; ?>/jobs/post-job.php" class="list-group-item">
            <i class="fa-solid fa-plus mr-2"></i> Post a Job
          </a>
          <a href="<?php echo $base_url; ?>/users/show-applicants.php?id=<?php echo $employerId; ?>" class="list-group-item">
            <i class="fa-solid fa-users-line mr-2"></i> Show Applicants
          </a>
          <a href="<?php echo $base_url; ?>/users/postedJobs.php" class="list-group-item">
            <i class="fa-solid fa-briefcase mr-2"></i> Posted Jobs
          </a>
          <a href="<?php echo $base_url; ?>/users/employer_insights.php" class="list-group-item active">
            <i class="fa-solid fa-chart-line mr-2"></i> Insights
          </a>
        </div>
      </div>
    </aside>

    <!-- Main -->
    <main class="col-lg-9">
      <div class="emp-card p-3 mb-3 d-flex align-items-center justify-content-between">
        <h4 class="mb-0">Insights & Analytics</h4>
        <div class="small text-muted">Last 30 days for charts</div>
      </div>

      <!-- Per-job KPIs table -->
      <div class="emp-card p-3 mb-3">
        <div class="table-responsive">
          <table class="table table-hover table-sm mb-0">
            <thead>
              <tr>
                <th>Job Title</th>
                <th>Views</th>
                <th>Applications</th>
                <th>Conversion</th>
              </tr>
            </thead>
            <tbody>
              <?php if ($table): foreach ($table as $r): ?>
                <tr>
                  <td>
                    <strong><?php echo htmlspecialchars($r['title']); ?></strong>
                    <div class="text-muted small">This job has received <?php echo (int)$r['views']; ?> views since posting.</div>
                    <div class="text-muted small">This job has received <?php echo (int)$r['apps']; ?> applications.</div>
                  </td>
                  <td><?php echo number_format($r['views']); ?></td>
                  <td><?php echo number_format($r['apps']); ?></td>
                  <td>
                    <span class="badge badge-<?php echo $r['band']; ?> badge-pill">
                      <?php echo number_format($r['conv'],2); ?>%
                    </span>
                  </td>
                </tr>
              <?php endforeach; else: ?>
                <tr><td colspan="4" class="text-center py-4 text-muted">No jobs found.</td></tr>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>

      <!-- Charts -->
      <div class="emp-card p-3">
        <div class="d-flex align-items-center justify-content-between mb-2">
          <h5 class="mb-0">Application & View Trend</h5>
          <div>
            <form id="jobPick" class="form-inline">
              <label class="mr-2 small text-muted">Job:</label>
              <select class="form-control form-control-sm" name="job_id" id="job_id">
                <?php foreach ($table as $r): ?>
                  <option value="<?php echo (int)$r['id']; ?>" <?php echo ((int)$r['id']===$selectedJobId?'selected':''); ?>>
                    <?php echo htmlspecialchars($r['title']); ?>
                  </option>
                <?php endforeach; ?>
              </select>
            </form>
          </div>
        </div>

        <div class="row">
         <div class="col-md-6 mb-3">
            <div class="chart-card border rounded">
                <h6 class="text-muted mb-2">Daily Views</h6>
                <div class="chart-box"><canvas id="viewsChart"></canvas></div>
            </div>
            </div>
            <div class="col-md-6 mb-3">
            <div class="chart-card border rounded">
                <h6 class="text-muted mb-2">Daily Applications</h6>
                <div class="chart-box"><canvas id="appsChart"></canvas></div>
            </div>
            </div>

        </div>

      </div>
    </main>
  </div>
</div>

<?php
// Embed chart data for initial load
$payloadJson = json_encode($chartPayload, JSON_NUMERIC_CHECK);
?>

<script src="<?php echo $base_url; ?>/plugin/chart.js-4.5.0/package/dist/chart.umd.min.js"></script>
<script>
(function(){
  var payload = <?php echo $payloadJson ?: '{"labels":[],"views":[],"apps":[]}'; ?>;

  function mkChart(ctxId, labels, data, label){
    var ctx = document.getElementById(ctxId).getContext('2d');
    return new Chart(ctx, {
      type: 'line',
      data: {
        labels: labels,
        datasets: [{
          label: label,
          data: data,
          borderWidth: 2,
          fill: false,
          tension: .25,
          pointRadius: 2
        }]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        scales: { y: { beginAtZero: true } },
        plugins: { legend: { display: true } }
      }
    });
  }

  var viewsChart = mkChart('viewsChart', payload.labels, payload.views, 'Views');
  var appsChart  = mkChart('appsChart',  payload.labels, payload.apps,  'Applications');

  // Job selector -> reload page with ?job_id=
  var sel = document.getElementById('job_id');
  if (sel) {
    sel.addEventListener('change', function(){
  var jid = this.value;
  var url = new URL(window.location.href);
  url.searchParams.set('job_id', jid);
  // Update address bar without reload
  window.history.replaceState({}, '', url);

  fetch(url.pathname + '?ajax=ts&job_id=' + encodeURIComponent(jid))
    .then(r=>r.json()).then(function(p){
      viewsChart.data.labels = p.labels;
      viewsChart.data.datasets[0].data = p.views;
      viewsChart.update();

      appsChart.data.labels = p.labels;
      appsChart.data.datasets[0].data = p.apps;
      appsChart.update();
    });
});

  }
})();
</script>

<?php require "../includes/footer.php"; ?>
