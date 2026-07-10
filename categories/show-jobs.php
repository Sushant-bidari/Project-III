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
if (!isset($_GET['name'])) { header("location: " . APPURL); exit; }

$name = trim($_GET['name']);

// --- Pagination (14 per page) ---
$perPage = 14;
$page = (isset($_GET['page']) && ctype_digit((string)$_GET['page'])) ? (int)$_GET['page'] : 1;
if ($page < 1) $page = 1;

// Total rows for this category
$countQ = $conn->prepare("SELECT COUNT(*) FROM jobs WHERE status = 1 AND job_category = :cat");
$countQ->execute([':cat' => $name]);
$totalRows  = (int)$countQ->fetchColumn();
$totalPages = max(1, (int)ceil($totalRows / $perPage));

// Clamp page to range and compute offset
if ($page > $totalPages) $page = $totalPages;
$offset = ($page - 1) * $perPage;

// Paged rows (join employer for name/logo)
$q = $conn->prepare("
  SELECT j.*,
         u.fullname AS company_name,
         u.img      AS company_img
  FROM jobs j
  JOIN users u ON j.company_id = u.id
  WHERE j.status = 1
    AND j.job_category = :cat
  ORDER BY j.created_at DESC
  LIMIT :limit OFFSET :offset
");
$q->bindValue(':cat', $name);
$q->bindValue(':limit',  $perPage, PDO::PARAM_INT);
$q->bindValue(':offset', $offset,  PDO::PARAM_INT);
$q->execute();
$jobs = $q->fetchAll(PDO::FETCH_OBJ);
?>
<style>
  .site-section{ padding-top:2rem; }

  /* ===== Hero (consistent) ===== */
  .companies-hero{
    position:relative; background-size:cover; background-position:center;
    padding: 60px 0; overflow:hidden;
  }
  .companies-hero .overlay-dark{ position:absolute; inset:0; background:
    radial-gradient(1200px 400px at 10% -10%, rgba(99,102,241,.22), transparent 60%),
    radial-gradient(1200px 400px at 90% 0%, rgba(6,182,212,.18), transparent 60%),
    linear-gradient(180deg, rgba(2,6,23,.65), rgba(2,6,23,.80));
  }
  .companies-hero .hero-inner{ position:relative; z-index:2; }
  .ch-eyebrow{ color:#c7d2fe; text-transform:uppercase; letter-spacing:.16em; font-weight:700; font-size:.75rem; }
  .ch-title{ color:#fff; font-weight:800; }
  .ch-sub{ color:#e2e8f0; }

  /* ===== Cards (same language as search results) ===== */
  .job-listings{ margin:0; list-style:none; padding:0; }
  .job-card{
    position:relative; border-radius:14px; background:#fff;
    box-shadow:0 2px 10px rgba(0,0,0,.04); transition:transform .18s ease, box-shadow .18s ease;
    overflow:hidden; margin-bottom:16px;
  }
  .job-card:hover{ transform:translateY(-2px); box-shadow:0 8px 24px rgba(0,0,0,.08); }
  .job-link{
    display:grid; grid-template-columns:72px 1fr auto; gap:16px; text-decoration:none !important; color:inherit;
    padding:16px 18px 16px 0;
  }
  .job-card .accent{ position:absolute; left:0; top:0; bottom:0; width:0; background:linear-gradient(90deg,#06b6d4,#6366f1); transition:width .28s ease; }
  .job-card:hover .accent{ width:6px; }

  /* logo / initial */
  .job-media{ display:flex; align-items:center; justify-content:center; width:72px; }
  .job-logo{
    width:66px; height:66px; object-fit:cover; border-radius:12px; border:1px solid rgba(0,0,0,.06); background:#f7f8fa;
  }
  .job-logo-initial{
    width:66px; height:66px; border-radius:12px; display:flex; align-items:center; justify-content:center;
    color:#fff; font-weight:800; font-size:24px; line-height:1; user-select:none;
    border:1px solid rgba(0,0,0,.06); box-shadow:0 2px 8px rgba(0,0,0,.06);
  }

  /* body */
  .job-body{ display:flex; flex-direction:column; justify-content:center; min-width:0; }
  .job-title-row{ display:flex; align-items:center; gap:10px; }
  .job-title{
    font-size:1.125rem; line-height:1.25; font-weight:700; margin:0;
    display:-webkit-box; -webkit-line-clamp:1; -webkit-box-orient:vertical; overflow:hidden;
  }
  .job-type{ white-space:nowrap; }
  .job-meta{ color:#6c757d; font-size:.95rem; display:flex; align-items:center; gap:8px; }
  .job-company{ font-weight:600; color:#6c757d; }
  .sep{ opacity:.5; }
  .job-sub{ color:#98a2b3; font-size:.9rem; }

  /* aside */
  .job-aside{ display:flex; flex-direction:column; align-items:flex-end; justify-content:center; gap:8px; min-width:180px; }
  .due-chip{ font-size:.875rem; font-weight:700; padding:6px 10px; border-radius:999px; background:#eef2ff; color:#3730a3; }
  .due-ok{ background:#ecfdf5; color:#065f46; }
  .due-warn{ background:#fff7ed; color:#9a3412; }
  .due-danger{ background:#fef2f2; color:#991b1b; }
  .deadline{ color:#6c757d; }
  .deadline-track{ position:relative; width:160px; height:6px; background:#eef2f7; border-radius:999px; overflow:hidden; }
  .deadline-fill{ height:100%; background:linear-gradient(90deg,#22c55e,#f59e0b,#ef4444); }

  /* empty state */
  .empty{
    background:#fff; border-radius:16px; box-shadow:0 2px 16px rgba(0,0,0,.06);
    padding:28px; text-align:center;
  }

  /* responsive */
  @media (max-width: 768px){
    .job-link{ grid-template-columns:56px 1fr; padding-right:14px; }
    .job-aside{ grid-column:1 / -1; align-items:flex-start; margin-top:8px; }
    .deadline-track{ width:100%; }
  }
</style>

<!-- HERO -->
<section class="companies-hero overlay inner-page bg-image" style="background-image:url('../images/tst.jpg');" id="home-section">
  <div class="overlay-dark"></div>
  <div class="container hero-inner text-center">
    <div class="ch-eyebrow mb-1">Category</div>
    <h2 class="ch-title mb-2">Jobs in ‘<?php echo htmlspecialchars(ucfirst($name)); ?>’</h2>
    <p class="ch-sub mb-0"><?php echo (int)$totalRows; ?> job<?php echo $totalRows==1?'':'s'; ?> found</p>
  </div>
</section>

<section class="site-section" id="next">
  <div class="container">
    <?php if (!$jobs): ?>
      <div class="empty">
        <h5 class="mb-1">No open roles in this category</h5>
        <p class="text-muted mb-3">Try another category or check back soon.</p>
        <a class="btn btn-primary" href="<?php echo APPURL; ?>/findjobs.php">Browse all jobs</a>
      </div>
    <?php else: ?>
      <ul class="job-listings mb-5">
        <?php foreach ($jobs as $job): ?>
          <?php
            // company image or initial fallback
            $cname   = trim((string)($job->company_name ?? 'Company'));
            $initial = mb_strtoupper(mb_substr($cname, 0, 1, 'UTF-8'), 'UTF-8');
            $imgFile = trim((string)($job->company_img ?? ''));
            $fsPath  = __DIR__ . '/../users/user-images/' . $imgFile; // categories/ is one level down
            $hasLogo = ($imgFile !== '' && @is_file($fsPath));
            $palette = ['#0d6efd','#6f42c1','#20c997','#dc3545','#fd7e14','#198754','#0dcaf0','#6c757d'];
            $bg      = $palette[hexdec(substr(md5(mb_strtolower($cname,'UTF-8')),0,2)) % count($palette)];

            // dates + urgency
            $postedTs   = strtotime($job->created_at);
            $deadlineTs = strtotime($job->application_deadline);
            $nowTs      = time();
            $daysLeft   = max(0, ceil(($deadlineTs - $nowTs) / 86400));
            $dueChipClass = $daysLeft <= 3 ? 'due-danger' : ($daysLeft <= 10 ? 'due-warn' : 'due-ok');

            // progress
            $totalWindow = max(1, $deadlineTs - $postedTs);
            $elapsed     = max(0, min($totalWindow, $nowTs - $postedTs));
            $pct         = round(($elapsed / $totalWindow) * 100);

            // badge color
            $typeClass = 'primary';
            if ($job->job_type === 'Full Time')     $typeClass = 'success';
            elseif ($job->job_type === 'Part Time') $typeClass = 'danger';
            elseif ($job->job_type === 'Contract')  $typeClass = 'info';
            elseif ($job->job_type === 'Casual')    $typeClass = 'secondary';
          ?>
          <li class="job-card">
            <a class="job-link" href="<?php echo $base_url; ?>/jobs/job-single.php?id=<?php echo (int)$job->id; ?>">
              <span class="accent" aria-hidden="true"></span>

              <div class="job-media">
                <?php if ($hasLogo): ?>
                  <img class="job-logo"
                       src="../users/user-images/<?php echo htmlspecialchars($imgFile); ?>"
                       alt="<?php echo htmlspecialchars($cname); ?> logo" loading="lazy">
                <?php else: ?>
                  <div class="job-logo-initial" style="background:<?php echo $bg; ?>;" aria-label="<?php echo htmlspecialchars($cname); ?> logo">
                    <?php echo htmlspecialchars($initial); ?>
                  </div>
                <?php endif; ?>
              </div>

              <div class="job-body">
                <div class="job-title-row">
                  <h3 class="job-title"><?php echo htmlspecialchars($job->job_title); ?></h3>
                  <span class="badge badge-<?php echo $typeClass; ?> job-type"><?php echo htmlspecialchars($job->job_type); ?></span>
                </div>

                <div class="job-meta">
                  <span class="job-company"><?php echo htmlspecialchars($cname); ?></span>
                  <span class="sep">•</span>
                  <span class="job-loc"><i class="icon-room"></i> <?php echo htmlspecialchars($job->job_region); ?></span>
                </div>

                <div class="job-sub">
                  Posted: <?php echo date('M d, Y', $postedTs); ?>
                </div>
              </div>

              <div class="job-aside">
                <span class="due-chip <?php echo $dueChipClass; ?>">
                  <?php echo $daysLeft > 0 ? $daysLeft . ' day' . ($daysLeft>1?'s':'') . ' left' : 'Closed'; ?>
                </span>
                <small class="deadline">
                  <i class="fa fa-clock"></i> Apply before: <?php echo date('M d, Y', $deadlineTs); ?>
                </small>
                <div class="deadline-track" aria-hidden="true">
                  <div class="deadline-fill" style="width: <?php echo $pct; ?>%;"></div>
                </div>
              </div>
            </a>
          </li>
        <?php endforeach; ?>
      </ul>
      <?php if (!empty($totalPages) && $totalPages > 1): ?>
        <nav aria-label="Category pages">
          <ul class="pagination justify-content-center">
            <li class="page-item <?php echo ($page <= 1) ? 'disabled' : ''; ?>">
              <a class="page-link" href="?name=<?php echo rawurlencode($name); ?>&page=<?php echo $page - 1; ?>">Previous</a>
            </li>
            <?php for ($i = 1; $i <= $totalPages; $i++): ?>
              <li class="page-item <?php echo ($page === $i) ? 'active' : ''; ?>">
                <a class="page-link" href="?name=<?php echo rawurlencode($name); ?>&page=<?php echo $i; ?>"><?php echo $i; ?></a>
              </li>
            <?php endfor; ?>
            <li class="page-item <?php echo ($page >= $totalPages) ? 'disabled' : ''; ?>">
              <a class="page-link" href="?name=<?php echo rawurlencode($name); ?>&page=<?php echo $page + 1; ?>">Next</a>
            </li>
          </ul>
        </nav>
      <?php endif; ?>
    <?php endif; ?>
  </div>
</section>

<?php require "../includes/footer.php"; ?>
