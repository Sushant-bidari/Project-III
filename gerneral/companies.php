<?php require "../config/config.php"; ?>
<?php require "../includes/header.php"; ?>

<?php
  /* ------------------------ Page & Filter ------------------------ */
  $limit  = 12;
  $page   = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
  $offset = ($page - 1) * $limit;
  $filter = isset($_GET['filter']) && in_array($_GET['filter'], ['all','hiring'], true) ? $_GET['filter'] : 'all';

  /* ------------------------ Counts for header stats ------------------------ */
  $allCountStmt = $conn->prepare("SELECT COUNT(*) FROM users WHERE type = 'Employer'");
  $allCountStmt->execute();
  $allEmployersCount = (int)$allCountStmt->fetchColumn();

  $hiringCountStmt = $conn->prepare("
    SELECT COUNT(DISTINCT u.id)
    FROM users u
    INNER JOIN jobs j ON u.id = j.company_id
    WHERE u.type='Employer'
      AND j.status = 1
      AND STR_TO_DATE(j.application_deadline, '%Y-%m-%d') >= CURDATE()
  ");
  $hiringCountStmt->execute();
  $hiringNowCount = (int)$hiringCountStmt->fetchColumn();

  /* ------------------------ Count for pagination (respect filter) ------------------------ */
  if ($filter === 'hiring') {
    $countStmt = $conn->prepare("
      SELECT COUNT(DISTINCT u.id)
      FROM users u
      INNER JOIN jobs j ON u.id = j.company_id
      WHERE u.type = 'Employer'
        AND j.status = 1
        AND STR_TO_DATE(j.application_deadline, '%Y-%m-%d') >= CURDATE()
    ");
  } else {
    $countStmt = $conn->prepare("SELECT COUNT(*) FROM users WHERE type = 'Employer'");
  }
  $countStmt->execute();
  $totalEmployers = (int)$countStmt->fetchColumn();
  $totalPages     = max(1, (int)ceil($totalEmployers / $limit));

  /* ------------------------ Fetch employers for current page ------------------------ */
  if ($filter === 'hiring') {
    $stmt = $conn->prepare("
      SELECT DISTINCT u.*
      FROM users u
      INNER JOIN jobs j ON u.id = j.company_id
      WHERE u.type = 'Employer'
        AND j.status = 1
        AND STR_TO_DATE(j.application_deadline, '%Y-%m-%d') >= CURDATE()
      ORDER BY u.created_at DESC
      LIMIT :limit OFFSET :offset
    ");
  } else {
    $stmt = $conn->prepare("
      SELECT *
      FROM users
      WHERE type = 'Employer'
      ORDER BY created_at DESC
      LIMIT :limit OFFSET :offset
    ");
  }
  $stmt->bindValue(':limit',  $limit,  PDO::PARAM_INT);
  $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
  $stmt->execute();
  $employers = $stmt->fetchAll(PDO::FETCH_OBJ);

  /* ------------------------ For the visible page: active job counts & details ------------------------ */
  $activeJobsMap = [];
  $detailsMap    = []; // user_id => ['company_website'=>..., 'industry'=>...]
  if (!empty($employers)) {
    $ids = array_map(function($r){ return (int)$r->id; }, $employers);
    $in  = implode(',', array_fill(0, count($ids), '?'));

    // Active job counts
    $aj = $conn->prepare("
      SELECT company_id, COUNT(*) AS active_jobs
      FROM jobs
      WHERE status = 1
        AND STR_TO_DATE(application_deadline, '%Y-%m-%d') >= CURDATE()
        AND company_id IN ($in)
      GROUP BY company_id
    ");
    $aj->execute($ids);
    while ($row = $aj->fetch(PDO::FETCH_ASSOC)) {
      $activeJobsMap[(int)$row['company_id']] = (int)$row['active_jobs'];
    }

    // Optional company details (industry, website)
    $cd = $conn->prepare("
      SELECT user_id, company_website, industry
      FROM company_details
      WHERE user_id IN ($in)
    ");
    $cd->execute($ids);
    while ($row = $cd->fetch(PDO::FETCH_ASSOC)) {
      $detailsMap[(int)$row['user_id']] = [
        'company_website' => $row['company_website'] ?? '',
        'industry'        => $row['industry'] ?? ''
      ];
    }
  }
?>
<style>
  .site-section{ padding-top:2rem; }

  /* ===== Hero ===== */
  .companies-hero{
    position:relative; background-size:cover; background-position:center;
    padding: 60px 0; overflow:hidden;
  }
  .companies-hero .overlay-dark{ position:absolute; inset:0;
    background:
      radial-gradient(1200px 400px at 10% -10%, rgba(99,102,241,.22), transparent 60%),
      radial-gradient(1200px 400px at 90% 0%, rgba(6,182,212,.18), transparent 60%),
      linear-gradient(180deg, rgba(2,6,23,.65), rgba(2,6,23,.80));
  }
  .companies-hero .hero-inner{ position:relative; z-index:2; }
  .ch-eyebrow{ color:#c7d2fe; text-transform:uppercase; letter-spacing:.16em; font-weight:700; font-size:.75rem; }
  .ch-title{ color:#fff; font-weight:800; }
  .ch-sub{ color:#e2e8f0; }

  /* ===== Toolbar / stats ===== */
  .company-toolbar{
    background:#fff; border-radius:14px; box-shadow:0 10px 24px rgba(0,0,0,.06);
    padding:14px; margin-top:-30px; position:relative; z-index:3;
    display:flex; align-items:center; flex-wrap:wrap; gap:10px;
  }
  .stat-pill{
    display:inline-flex; align-items:center; padding:.4rem .7rem; border-radius:999px;
    background:#f1f5f9; color:#0f172a; font-weight:600; margin-right:6px;
  }
  .stat-pill .dot{ width:8px; height:8px; border-radius:999px; display:inline-block; margin-right:8px; }
  .dot-all{ background:#64748b; } .dot-hiring{ background:#22c55e; }

  .segmented{
    background:#f8fafc; border:1px solid #e2e8f0; border-radius:999px; padding:2px;
    display:inline-flex; align-items:center;
  }
  .segmented .seg{ border-radius:999px; padding:.35rem .7rem; margin:2px; cursor:pointer; font-weight:600; color:#334155; }
  .segmented .seg.active{ background:#111827; color:#fff; }

  .search-mini{ min-width:220px; }
  .search-mini input{ border-radius:999px; }

  /* ===== Cards ===== */
  .company-card{ background:#fff; border-radius:14px; box-shadow:0 8px 24px rgba(0,0,0,.06); overflow:hidden; height:100%; transition:transform .2s ease, box-shadow .2s ease; }
  .company-card:hover{ transform:translateY(-4px); box-shadow:0 16px 36px rgba(0,0,0,.08); }
  .company-logo{
    height:130px; border-bottom:1px solid #eef2f7;
    display:flex; align-items:center; justify-content:center; background:#fff;
  }
  /* Fallback logo tile */
  .company-logo-initial{
    width:84px; height:84px; border-radius:12px; display:flex; align-items:center; justify-content:center;
    color:#fff; font-weight:800; font-size:34px; user-select:none; border:1px solid rgba(0,0,0,.06);
    box-shadow:0 4px 12px rgba(0,0,0,.06);
  }
  @media (max-width:576px){ .company-logo-initial{ width:64px; height:64px; font-size:26px; } }

  .hire-badge{
    position:absolute; top:10px; left:10px; border-radius:999px;
    padding:.25rem .55rem; font-weight:700; font-size:.75rem;
  }

  .company-body{ padding:14px; display:flex; flex-direction:column; height: calc(100% - 130px); }
  .company-title{ font-weight:700; color:#0f172a; margin-bottom:2px; }
  .company-sub{ color:#64748b; font-size:.9rem; margin-bottom:8px; }
  .company-bio{ color:#475569; font-size:.92rem; line-height:1.4; }

  .company-foot{
    margin-top:auto; display:flex; align-items:center; justify-content:space-between; padding-top:10px;
  }
  .job-chip{
    background:#eef2ff; color:#3730a3; border-radius:999px; padding:.25rem .55rem; font-weight:700; font-size:.8rem;
  }
  .card-actions a.btn{
    border-radius:999px; font-weight:600;
  }
  .btn-outline-slate{
    background:#fff; color:#0f172a; border:1px solid #e5e7eb;
  }
</style>

<!-- HERO -->
<section class="companies-hero overlay inner-page bg-image" style="background-image:url('../images/tst.jpg');" id="home-section">
  <div class="overlay-dark"></div>
  <div class="container hero-inner text-center">
    <div class="ch-eyebrow mb-1">Employers</div>
    <h2 class="ch-title mb-2">Discover Companies</h2>
    <p class="ch-sub mb-0">Browse verified employers and see who’s hiring right now.</p>
  </div>
</section>

<section class="site-section bg-light pb-5">
  <div class="container">

    <!-- Toolbar: stats + filters + search -->
    <div class="company-toolbar">
      <div class="d-flex align-items-center mr-auto">
        <span class="stat-pill mr-2"><span class="dot dot-all"></span> All: <strong class="ml-1"><?php echo number_format($allEmployersCount); ?></strong></span>
        <span class="stat-pill"><span class="dot dot-hiring"></span> Hiring now: <strong class="ml-1"><?php echo number_format($hiringNowCount); ?></strong></span>
      </div>

      <form method="get" class="form-inline m-0" id="filterForm">
        <div class="segmented mr-2">
          <label class="seg <?php echo ($filter==='all'?'active':''); ?>">
            <input type="radio" name="filter" value="all" class="d-none" <?php echo ($filter==='all'?'checked':''); ?>>
            All
          </label>
          <label class="seg <?php echo ($filter==='hiring'?'active':''); ?>">
            <input type="radio" name="filter" value="hiring" class="d-none" <?php echo ($filter==='hiring'?'checked':''); ?>>
            Hiring now
          </label>
        </div>

        <div class="search-mini input-group">
          <input type="text" class="form-control" id="companySearch" placeholder="Search companies…" aria-label="Search companies">
          <div class="input-group-append">
            <button class="btn btn-outline-slate" type="button" id="clearSearch">Clear</button>
          </div>
        </div>
      </form>
    </div>

    <!-- Grid -->
    <div class="row mt-4" id="companyGrid">
      <?php if (!empty($employers)): ?>
        <?php foreach ($employers as $company): ?>
          <?php
            $name     = trim($company->fullname ?? '');
            $initial  = mb_strtoupper(mb_substr($name, 0, 1, 'UTF-8'), 'UTF-8');
            $imgFile  = $company->img ?? '';
            $imgPath  = __DIR__ . "/../users/user-images/" . $imgFile;
            $hasLogo  = !empty($imgFile) && @file_exists($imgPath);
            $bio      = trim(strip_tags($company->bio ?? ''));

            $active   = $activeJobsMap[(int)$company->id] ?? 0;
            $det      = $detailsMap[(int)$company->id] ?? ['company_website'=>'','industry'=>''];
            $industry = trim($det['industry']);
            $website  = trim($det['company_website']);

            // stable color for fallback tile
            $palette = ['#0d6efd','#6f42c1','#20c997','#dc3545','#fd7e14','#198754','#0dcaf0','#6c757d'];
            $bg = $palette[hexdec(substr(md5(mb_strtolower($name,'UTF-8')),0,2)) % count($palette)];
          ?>
          <div class="col-md-6 col-lg-4 mb-4 company-col"
               data-name="<?php echo htmlspecialchars(mb_strtolower($name,'UTF-8')); ?>"
               data-industry="<?php echo htmlspecialchars(mb_strtolower($industry,'UTF-8')); ?>">
            <div class="company-card position-relative">

              <?php if ($active > 0): ?>
                <span class="badge badge-success hire-badge">Hiring now • <?php echo $active; ?></span>
              <?php endif; ?>

              <div class="company-logo">
                <?php if ($hasLogo): ?>
                  <img src="../users/user-images/<?php echo htmlspecialchars($imgFile); ?>"
                       alt="<?php echo htmlspecialchars($name); ?> logo"
                       class="img-fluid" loading="lazy"
                       style="max-height:100%;max-width:100%;object-fit:contain;">
                <?php else: ?>
                  <div class="company-logo-initial" style="background:<?php echo $bg; ?>;"><?php echo htmlspecialchars($initial); ?></div>
                <?php endif; ?>
              </div>

              <div class="company-body">
                <div class="company-title h5 mb-0"><?php echo htmlspecialchars($name); ?></div>
                <?php if ($industry !== ''): ?>
                  <div class="company-sub"><?php echo htmlspecialchars($industry); ?></div>
                <?php endif; ?>

                <div class="company-bio">
                  <?php
                    $snippet = (mb_strlen($bio,'UTF-8') > 110) ? (mb_substr($bio,0,107,'UTF-8').'…') : $bio;
                    echo htmlspecialchars($snippet);
                  ?>
                </div>

                <div class="company-foot">
                  <span class="job-chip"><?php echo $active; ?> active job<?php echo ($active===1?'':'s'); ?></span>
                  <div class="card-actions">
                    <?php if ($website): ?>
                      <a href="<?php echo htmlspecialchars($website); ?>" class="btn btn-light btn-sm mr-1" target="_blank" rel="noopener" title="Website">
                        <i class="fa fa-globe"></i>
                      </a>
                    <?php endif; ?>
                    <a href="../users/public-profile.php?id=<?php echo (int)$company->id; ?>"
                       target="_blank" class="btn btn-secondary btn-sm">View Profile</a>
                  </div>
                </div>
              </div>

            </div>
          </div>
        <?php endforeach; ?>
      <?php else: ?>
        <div class="col-12">
          <div class="alert alert-info text-center mb-0">No companies match the selected filter.</div>
        </div>
      <?php endif; ?>
    </div>

    <!-- Pagination -->
    <?php if ($totalPages > 1): ?>
      <nav aria-label="Employer Pagination" class="mt-2">
        <ul class="pagination justify-content-center">
          <li class="page-item <?php echo ($page <= 1 ? 'disabled' : ''); ?>">
            <a class="page-link" href="?page=<?php echo $page - 1; ?>&filter=<?php echo htmlspecialchars($filter); ?>">Previous</a>
          </li>
          <?php for ($i=1; $i<=$totalPages; $i++): ?>
            <li class="page-item <?php echo ($i===$page ? 'active' : ''); ?>">
              <a class="page-link" href="?page=<?php echo $i; ?>&filter=<?php echo htmlspecialchars($filter); ?>"><?php echo $i; ?></a>
            </li>
          <?php endfor; ?>
          <li class="page-item <?php echo ($page >= $totalPages ? 'disabled' : ''); ?>">
            <a class="page-link" href="?page=<?php echo $page + 1; ?>&filter=<?php echo htmlspecialchars($filter); ?>">Next</a>
          </li>
        </ul>
      </nav>
    <?php endif; ?>

  </div>
</section>

<script>
(function(){
  // Toggle pills auto-submit
  var form = document.getElementById('filterForm');
  if (form){
    var radios = form.querySelectorAll('input[name="filter"]');
    [].forEach.call(radios, function(r){
      r.addEventListener('change', function(){ form.submit(); });
      // visual "active" state
      r.closest('.seg').classList.toggle('active', r.checked);
      r.addEventListener('change', function(){
        [].forEach.call(form.querySelectorAll('.seg'), function(s){ s.classList.remove('active'); });
        r.closest('.seg').classList.add('active');
      });
    });
  }

  // Client-side search
  var q = document.getElementById('companySearch');
  var clear = document.getElementById('clearSearch');
  var cards = document.querySelectorAll('#companyGrid .company-col');

  function applyFilter(){
    var v = (q.value || '').toLowerCase().trim();
    cards.forEach(function(el){
      var name = el.getAttribute('data-name') || '';
      var ind  = el.getAttribute('data-industry') || '';
      var show = !v || name.indexOf(v) >= 0 || ind.indexOf(v) >= 0;
      el.style.display = show ? '' : 'none';
    });
  }

  if (q){
    q.addEventListener('input', applyFilter);
  }
  if (clear){
    clear.addEventListener('click', function(){
      if (q){ q.value=''; applyFilter(); q.focus(); }
    });
  }
})();
</script>

<?php require "../includes/footer.php"; ?>
