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
if (!empty($_POST['ajax']) && ($_POST['action'] ?? '') === 'unsave') {
  header('Content-Type: application/json; charset=UTF-8');
  // Guard
  if (!isset($_SESSION['type']) || $_SESSION['type'] !== "Job Seeker") {
    echo json_encode(['ok'=>false, 'error'=>'unauthorized']); exit;
  }
  $uid = (int)($_SESSION['id'] ?? 0);
  $jid = (int)($_POST['job_id'] ?? 0);
  if (!$uid || !$jid) { echo json_encode(['ok'=>false]); exit; }

  $dq = $conn->prepare("DELETE FROM saved_jobs WHERE worker_id = :u AND job_id = :j");
  $dq->execute([':u'=>$uid, ':j'=>$jid]);

  echo json_encode(['ok'=>true]); exit;
}

/* ---------- Auth guard ---------- */
if (!isset($_SESSION['type']) || $_SESSION['type'] !== "Job Seeker") {
  header("location: " . APPURL); exit;
}

if (!isset($_GET['id'])) { header("location: " . APPURL); exit; }
$id = (int)$_GET['id'];
if ((int)($_SESSION['id'] ?? 0) !== $id) { header("location: " . APPURL); exit; }

/* ---------- Load data ---------- */
$select = $conn->prepare("SELECT id, fullname FROM users WHERE id = :id LIMIT 1");
$select->execute([':id' => $id]);
$profile = $select->fetch(PDO::FETCH_OBJ);

/* Include “applied?” in each saved job */
$q = $conn->prepare("
  SELECT j.id,
         j.job_title,
         j.job_region,
         j.job_type,
         j.created_at,
         j.application_deadline,
         u.fullname AS company_name,
         u.img      AS company_img,
         EXISTS(
           SELECT 1 FROM job_applications ja
           WHERE ja.job_id = j.id AND ja.worker_id = :uid
         ) AS has_applied
  FROM saved_jobs s
  JOIN jobs j ON s.job_id = j.id
  JOIN users u ON j.company_id = u.id
  WHERE s.worker_id = :uid
  ORDER BY s.id DESC
");
$q->execute([':uid' => $id]);
$jobs = $q->fetchAll(PDO::FETCH_OBJ);


require "../includes/header.php";
?>


<style>
.site-section{ padding-top:2rem; }

/* ===== Hero ===== */
.companies-hero{
  position:relative; background-size:cover; background-position:center;
  padding: 60px 0; overflow:hidden;
}
.companies-hero .overlay-dark{ position:absolute; inset:0; background:
    radial-gradient(1200px 400px at 10% -10%, rgba(99,102,241,.22), transparent 60%),
    radial-gradient(1200px 400px at 90% 0%, rgba(6,182,212,.18), transparent 60%),
    linear-gradient(180deg, rgba(2,6,23,.65), rgba(2,6,23,.80));}
.companies-hero .hero-inner{ position:relative; z-index:2; }
.ch-eyebrow{ color:#c7d2fe; text-transform:uppercase; letter-spacing:.16em; font-weight:700; font-size:.75rem; }
.ch-title{ color:#fff; font-weight:800; }
.ch-sub{ color:#e2e8f0; }

/* ===== Saved list ===== */
.sv-list{ list-style:none; padding:0; margin:0; }
.sv-card{
  position:relative; border-radius:16px; background:#fff;
  box-shadow:0 2px 12px rgba(0,0,0,.06); overflow:hidden;
  transition:transform .18s ease, box-shadow .18s ease; margin-bottom:16px;
}
.sv-card:hover{ transform:translateY(-2px); box-shadow:0 10px 26px rgba(0,0,0,.10); }
.sv-link{
  display:grid; grid-template-columns:72px 1fr auto; gap:16px;
  text-decoration:none !important; color:inherit; padding:16px 18px 16px 0;
}
.sv-card .accent{ position:absolute; left:0; top:0; bottom:0; width:0; background:linear-gradient(90deg,#06b6d4,#6366f1); transition:width .28s ease; }
.sv-card:hover .accent{ width:6px; }

/* logo / initial */
.sv-media{ display:flex; align-items:center; justify-content:center; width:72px; }
.sv-logo{ width:66px; height:66px; object-fit:cover; border-radius:12px; border:1px solid rgba(0,0,0,.06); background:#f7f8fa; }
.sv-initial{
  width:66px; height:66px; border-radius:12px; display:flex; align-items:center; justify-content:center;
  color:#fff; font-weight:800; font-size:24px; line-height:1; user-select:none;
  border:1px solid rgba(0,0,0,.06); box-shadow:0 2px 8px rgba(0,0,0,.06);
}

/* body */
.sv-body{ display:flex; flex-direction:column; justify-content:center; min-width:0; }
.sv-title{ font-size:1.125rem; line-height:1.25; font-weight:800; margin:0;
  display:-webkit-box; -webkit-line-clamp:1; -webkit-box-orient:vertical; overflow:hidden;
}
.sv-meta{ color:#6c757d; display:flex; align-items:center; gap:8px; font-size:.95rem; }
.sv-company{ font-weight:600; }
.sv-sub{ color:#98a2b3; font-size:.9rem; }

.sv-aside{ display:flex; flex-direction:column; align-items:flex-end; justify-content:center; gap:8px; min-width:190px; }

.badge-tight{ white-space:nowrap; }

.sv-chip{
  border-radius:999px; padding:6px 10px; font-size:.875rem; font-weight:700; line-height:1;
}
.chip-ok{ background:#ecfdf5; color:#065f46; }
.chip-warn{ background:#fff7ed; color:#9a3412; }
.chip-danger{ background:#fef2f2; color:#991b1b; }

.sv-track{ width:160px; height:6px; background:#eef2f7; border-radius:999px; overflow:hidden; }
.sv-fill{ height:100%; background:linear-gradient(90deg,#22c55e,#f59e0b,#ef4444); }

.sv-actions{ display:flex; gap:8px; }
.btn-ghost{ border:1px solid rgba(0,0,0,.18); background:#fff; color:#374151; }
.btn-ghost:hover{ background:#f8fafc; }

/* empty state */
.empty{
  background:#fff; border-radius:16px; box-shadow:0 2px 16px rgba(0,0,0,.06);
  padding:28px; text-align:center;
}

/* responsive */
@media (max-width: 768px){
  .sv-link{ grid-template-columns:56px 1fr; padding-right:14px; }
  .sv-aside{ grid-column:1 / -1; align-items:flex-start; margin-top:8px; }
  .sv-track{ width:100%; }
}
</style>

<!-- HERO -->
<section class="companies-hero overlay inner-page bg-image" style="background-image:url('../images/tst.jpg');" id="home-section">
  <div class="overlay-dark"></div>
  <div class="container hero-inner text-center">
    <div class="ch-eyebrow mb-1">Your list</div>
    <h2 class="ch-title mb-2">Saved Jobs</h2>
    <p class="ch-sub mb-0">Come back to roles you’ve bookmarked and apply when ready.</p>
  </div>
</section>

<section class="site-section">
  <div class="container">
    <?php if (!$jobs): ?>
      <div class="empty">
        <h5 class="mb-1">No saved jobs yet</h5>
        <p class="text-muted mb-3">Star jobs you like to keep them here for quick access.</p>
        <a href="<?php echo APPURL; ?>/findjobs.php" class="btn btn-primary">
          Browse jobs
        </a>
      </div>
    <?php else: ?>
      <ul class="sv-list" id="savedList">
        <?php foreach ($jobs as $j): ?>
          <?php
            // Logo or initial
            $cname   = trim((string)$j->company_name);
            $initial = mb_strtoupper(mb_substr($cname ?: 'C', 0, 1, 'UTF-8'), 'UTF-8');
            $imgFile = trim((string)$j->company_img);
            $fsPath  = __DIR__ . '/user-images/' . $imgFile;        // savedJobs.php sits in /users
            $hasLogo = ($imgFile !== '' && @is_file($fsPath));
            $palette = ['#0d6efd','#6f42c1','#20c997','#dc3545','#fd7e14','#198754','#0dcaf0','#6c757d'];
            $bg      = $palette[hexdec(substr(md5(mb_strtolower($cname,'UTF-8')),0,2)) % count($palette)];

     
            $postedTs   = $j->created_at ? strtotime($j->created_at) : null;
            $deadlineTs = $j->application_deadline ? strtotime($j->application_deadline) : null;
            $nowTs      = time();
            $daysLeft   = null;
            if ($deadlineTs) $daysLeft = max(0, ceil(($deadlineTs - $nowTs) / 86400));

            $chipClass = 'chip-ok';
            if ($daysLeft !== null) {
              if ($daysLeft <= 3) $chipClass = 'chip-danger';
              elseif ($daysLeft <= 10) $chipClass = 'chip-warn';
            }

            
            $typeClass = 'primary';
            if ($j->job_type === 'Full Time')     $typeClass = 'success';
            elseif ($j->job_type === 'Part Time') $typeClass = 'danger';
            elseif ($j->job_type === 'Contract')  $typeClass = 'info';
            elseif ($j->job_type === 'Casual')    $typeClass = 'secondary';

            // Progress bar %
            $pct = 0;
            if ($deadlineTs && $postedTs) {
              $totalWindow = max(1, $deadlineTs - $postedTs);
              $elapsed     = max(0, min($totalWindow, $nowTs - $postedTs));
              $pct         = round(($elapsed / $totalWindow) * 100);
            }
          ?>
          <li class="sv-card" data-job="<?php echo (int)$j->id; ?>">
            <a class="sv-link" href="<?php echo $base_url; ?>/jobs/job-single.php?id=<?php echo (int)$j->id; ?>">
              <div class="accent" aria-hidden="true"></div>

              <div class="sv-media">
                <?php if ($hasLogo): ?>
                  <img class="sv-logo" src="user-images/<?php echo htmlspecialchars($imgFile); ?>" alt="<?php echo htmlspecialchars($cname); ?> logo" loading="lazy">
                <?php else: ?>
                  <div class="sv-initial" style="background:<?php echo $bg; ?>;" aria-label="<?php echo htmlspecialchars($cname); ?> logo">
                    <?php echo htmlspecialchars($initial); ?>
                  </div>
                <?php endif; ?>
              </div>

              <div class="sv-body">
                <h3 class="sv-title"><?php echo htmlspecialchars($j->job_title); ?></h3>
                <div class="sv-meta">
                  <span class="sv-company"><?php echo htmlspecialchars($cname); ?></span>
                  <span>•</span>
                  <span><i class="icon-room"></i> <?php echo htmlspecialchars($j->job_region); ?></span>
                </div>
                <?php if ($postedTs): ?>
                  <div class="sv-sub">Posted: <?php echo date('M d, Y', $postedTs); ?></div>
                <?php endif; ?>
              </div>

              <div class="sv-aside">
                <span class="badge badge-<?php echo $typeClass; ?> badge-tight"><?php echo htmlspecialchars($j->job_type); ?></span>

                <?php if ($daysLeft !== null): ?>
                  <span class="sv-chip <?php echo $chipClass; ?>">
                    <?php echo $daysLeft > 0 ? ($daysLeft . ' day' . ($daysLeft>1?'s':'') . ' left') : 'Closed'; ?>
                  </span>
                  <small class="text-muted">Apply before: <?php echo date('M d, Y', $deadlineTs); ?></small>
                  <div class="sv-track" aria-hidden="true">
                    <div class="sv-fill" style="width: <?php echo $pct; ?>%;"></div>
                  </div>
                <?php endif; ?>
              </div>
            </a>

          
            <div class="px-3 pb-3">
              <?php $applied = !empty($j->has_applied); ?>
              <div class="sv-actions">
                <button class="btn btn-ghost btn-sm js-unsave" data-id="<?php echo (int)$j->id; ?>">
                  <i class="fa fa-bookmark-o mr-1"></i> Unsave
                </button>

                <?php if ($applied): ?>
                  <span class="badge badge-success align-self-center">Already applied</span>
                  <a class="btn btn-outline-secondary btn-sm" href="<?php echo $base_url; ?>/jobs/job-single.php?id=<?php echo (int)$j->id; ?>">
                    View Job
                  </a>
                <?php else: ?>
                  <a class="btn btn-outline-primary btn-sm" href="<?php echo $base_url; ?>/jobs/job-single.php?id=<?php echo (int)$j->id; ?>">
                    View & Apply
                  </a>
                <?php endif; ?>
              </div>

            </div>
          </li>
        <?php endforeach; ?>
      </ul>
    <?php endif; ?>
  </div>
</section>

<script>
(function(){
  var list = document.getElementById('savedList');
  if (!list) return;

  list.addEventListener('click', function(e){
    var btn = e.target.closest('.js-unsave');
    if (!btn) return;

    e.preventDefault();
    var id = btn.getAttribute('data-id');
    if (!id) return;

    if (!confirm('Remove this job from your saved list?')) return;

    var form = new FormData();
    form.append('ajax', '1');
    form.append('action', 'unsave');
    form.append('job_id', id);

    fetch(location.href, { method:'POST', body: form, headers:{ 'X-Requested-With':'fetch' } })
  .then(function(resp){
    if (!resp.ok) throw new Error('bad status');
    var ct = resp.headers.get('content-type') || '';
    if (ct.indexOf('application/json') === -1) throw new Error('not json');
    return resp.json();
  })
  .then(function(res){
    if (res && res.ok){
      var li = btn.closest('.sv-card');
      if (li) li.remove();
      if (!list.querySelector('.sv-card')) {
        list.insertAdjacentHTML('beforebegin',
          '<div class="empty"><h5 class="mb-1">No saved jobs</h5><p class="text-muted mb-3">Browse roles and save the ones you like.</p><a class="btn btn-primary" href="<?php echo APPURL; ?>/findjobs.php">Browse jobs</a></div>'
        );
      }
    } else {
      alert('Unable to unsave this job. Please try again.');
    }
  })
  .catch(function(){
    alert('Network error. Please try again.');
  });

  });
})();
</script>

<?php require "../includes/footer.php"; ?>
