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
if (isset($_GET['id'])) {
    $id = $_GET['id'];

    // Fetch profile
    $select = $conn->prepare("SELECT * FROM users WHERE id = :id");
    $select->bindParam(":id", $id);
    $select->execute();
    $profile = $select->fetch(PDO::FETCH_OBJ);
    if (!$profile) { echo "404 - Profile not found."; exit; }

    // Company details (employer only)
    $companyDetails = null;
    if ($profile->type === 'Employer') {
        $stmt = $conn->prepare("SELECT * FROM company_details WHERE user_id = :id LIMIT 1");
        $stmt->bindParam(':id', $profile->id);
        $stmt->execute();
        $companyDetails = $stmt->fetch(PDO::FETCH_OBJ);
    }

    // Resumes (job seeker only)
    $resumes = [];
    if ($profile->type === 'Job Seeker') {
        $rq = $conn->prepare("
            SELECT id, label, original_name, filename, is_primary, created_at
            FROM resumes
            WHERE user_id = :uid
            ORDER BY is_primary DESC, created_at DESC, id DESC
            LIMIT 5
        ");
        $rq->execute([':uid' => $profile->id]);
        $resumes = $rq->fetchAll(PDO::FETCH_OBJ);
    }

    // App-context resume (employer viewing a specific application)
    $appResume = null;
    $appResumeDisplay = null;
    if (
        isset($_GET['app']) && ctype_digit((string)$_GET['app']) &&
        isset($_SESSION['type']) && $_SESSION['type'] === 'Employer'
    ) {
        $appId = (int)$_GET['app'];
        $cid   = (int)($_SESSION['id'] ?? 0);

        $aq = $conn->prepare("
            SELECT cv
            FROM job_applications
            WHERE id = :app AND company_id = :cid AND worker_id = :uid
            LIMIT 1
        ");
        $aq->execute([':app'=>$appId, ':cid'=>$cid, ':uid'=>$profile->id]);
        $ar = $aq->fetch(PDO::FETCH_ASSOC);

        if ($ar && !empty($ar['cv'])) {
            $appResume = $ar['cv'];

            $mq = $conn->prepare("
                SELECT label, original_name
                FROM resumes
                WHERE user_id = :uid AND filename = :fn
                LIMIT 1
            ");
            $mq->execute([':uid'=>$profile->id, ':fn'=>$appResume]);
            $mr = $mq->fetch(PDO::FETCH_ASSOC);
            $appResumeDisplay = $mr
              ? ($mr['label'] ?: ($mr['original_name'] ?: $appResume))
              : $appResume;
        }
    }

    // Jobs by this employer (if employer)
    $moreJobs = [];
    if ($profile->type === 'Employer') {
        $jobs = $conn->prepare("
          SELECT * FROM jobs
          WHERE company_id = :id AND status = 1 AND application_deadline >= CURDATE()
          ORDER BY created_at DESC
          LIMIT 5
        ");
        $jobs->bindParam(":id", $id);
        $jobs->execute();
        $moreJobs = $jobs->fetchAll(PDO::FETCH_OBJ);
    }
} else {
    echo "404 - Profile not found.";
    exit;
}
?>

<style>
  .site-section{ padding-top:3rem; }

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

  /* ===== Profile header card ===== */
  .pp-hero{
    border-radius: 16px;
    background:
      radial-gradient(900px 300px at 0% -10%, rgba(99,102,241,.12), transparent 60%),
      radial-gradient(900px 300px at 100% 0%, rgba(6,182,212,.10), transparent 60%),
      #fff;
    padding: 18px 20px;
    box-shadow: 0 8px 26px rgba(0,0,0,.06);
    margin-top: -32px;
    margin-bottom: 18px;
  }
  .pp-hero-row{
    display: grid; grid-template-columns: 100px 1fr auto; gap: 16px; align-items: center;
  }
  .pp-name{ margin:0; font-weight: 800; font-size: 1.6rem; line-height: 1.2; }
  .pp-meta{ display:flex; flex-wrap:wrap; gap:8px; align-items:center; color:#64748b; margin-top:6px; }
  .pp-dot{ width:4px; height:4px; border-radius:999px; background:#cbd5e1; display:inline-block; }

  .pp-chip{
    display:inline-flex; align-items:center; gap:6px;
    border-radius:999px; padding:.35rem .6rem; font-weight:700; font-size:.85rem; line-height:1;
  }
  .pp-chip--emp{ background:#eef2ff; color:#3730a3; }
  .pp-chip--seeker{ background:#ecfdf5; color:#065f46; }
  .pp-title{ background:#f1f5f9; color:#0f172a; }

  .pp-social a{ font-size: 1.25rem; margin-right:10px; }
  .pp-actions{ display:flex; gap:10px; flex-wrap:wrap; }

  .pp-grid{ display:grid; grid-template-columns: 1fr 320px; gap: 16px; }

  .pp-card{
    background:#fff; border-radius:14px;
    box-shadow:0 2px 12px rgba(0,0,0,.06); padding:16px; margin-bottom:16px;
  }
  .pp-card h5{ font-weight:800; margin-bottom:10px; }
  .pp-badges .badge{ margin: 0 6px 6px 0; padding: .45rem .65rem; border-radius: 999px; font-weight:700; }
  .pp-small{ color:#6b7280; font-size:.95rem; }
  .pp-list{ list-style:none; padding:0; margin:0; }
  .pp-list li{ margin-bottom:8px; }
  .pp-body p{ color:#4b5563; white-space: pre-line; }

  /* Buttons */
  .pp-btn{
    padding:.28rem .7rem; border-radius: .3rem;
    display:inline-flex; align-items:center; gap:.3rem; text-decoration:none !important;
  }
  .pp-btn-primary{ color:#fff; background-color:#6366f1; border:1px solid #6366f1; }
  .pp-btn-primary:hover{ color:#fff; background-color:#4f46e5; border-color:#4f46e5; }
  .pp-btn-ghost{ color:#6c757d; background:transparent; border:1px solid #6c757d; }
  .pp-btn-ghost:hover{ color:#fff; background:#6c757d; }

  /* Avatar / initials */
  .pp-avatar,
  .pp-avatar-initial{
    width:96px; height:96px; border-radius:50%;
    border:4px solid #fff;
    box-shadow:0 8px 20px rgba(0,0,0,.12);
  }
  .pp-avatar{ object-fit:cover; }
  .pp-avatar-initial{
    display:flex; align-items:center; justify-content:center;
    color:#fff; font-weight:800; font-size:40px; user-select:none;
  }

  /* Jobs list (employer) */
  .custom-job-listings{ margin:0; padding:0; }
  .custom-job-listing-item{
    background:#fff; transition: transform .15s ease, box-shadow .15s ease, background-color .15s ease;
    border-radius: 14px; border:1px solid #eef2f7;
    position:relative; overflow:hidden;
  }
  .custom-job-listing-item:hover{ transform: translateY(-2px); box-shadow:0 14px 28px rgba(0,0,0,.08); background:#fcfcff; }
  .custom-job-listing-item::before{
    content:""; position:absolute; left:0; right:0; top:0; height:4px;
    background:linear-gradient(90deg,#06b6d4,#6366f1); opacity:.85;
  }
  .custom-job-listing-item h5{ font-size:1.1rem; color:#0f172a; }
  .custom-job-listing-item .badge{ font-size:.85rem; padding:.4em .7em; }
  .custom-job-listing-item small{ font-size:.85rem; color:#64748b; }

  @media (max-width: 992px){
    .pp-grid{ grid-template-columns:1fr; }
    .pp-hero-row{ grid-template-columns:72px 1fr; }
    .pp-avatar, .pp-avatar-initial{ width:64px; height:64px; border-width:3px; box-shadow:0 6px 14px rgba(0,0,0,.12); }
    .pp-avatar-initial{ font-size:28px; }
  }
</style>

<!-- HERO -->
<section class="companies-hero overlay inner-page bg-image" style="background-image:url('../images/tst.jpg');" id="home-section">
  <div class="overlay-dark"></div>
  <div class="container hero-inner text-center">
    <div class="ch-eyebrow mb-1"><?php echo ($profile->type==='Employer') ? 'Employer' : 'Candidate'; ?></div>
    <h2 class="ch-title mb-2"><?php echo htmlspecialchars($profile->fullname); ?></h2>
    <p class="ch-sub mb-0">
      <?php echo ($profile->type==='Employer')
        ? 'Company profile & open roles'
        : 'Public profile & resume snapshot'; ?>
    </p>
  </div>
</section>

<section class="site-section" style="padding-bottom: 3rem;">
  <div class="container">

    <!-- Profile header card -->
    <div class="pp-hero">
      <div class="pp-hero-row">
        <?php
          $fullName = trim($profile->fullname ?? '');
          $initial  = mb_strtoupper(mb_substr($fullName, 0, 1, 'UTF-8'), 'UTF-8');
          $hasPhoto = !empty($profile->img) && @file_exists(__DIR__ . "/user-images/" . $profile->img);

          $palette = ['#0d6efd','#6f42c1','#20c997','#dc3545','#fd7e14','#198754','#0dcaf0','#6c757d'];
          $bg = $palette[hexdec(substr(md5(mb_strtolower($fullName,'UTF-8')),0,2)) % count($palette)];
        ?>

        <?php if ($hasPhoto): ?>
          <img
            src="user-images/<?php echo htmlspecialchars($profile->img); ?>"
            alt="Profile photo"
            class="pp-avatar"
            loading="lazy">
        <?php else: ?>
          <div class="pp-avatar-initial"
               style="background: <?php echo $bg; ?>;"
               role="img"
               aria-label="Avatar"><?php echo htmlspecialchars($initial); ?></div>
        <?php endif; ?>

        <div>
          <h2 class="pp-name"><?php echo htmlspecialchars($profile->fullname); ?></h2>
          <div class="pp-meta">
            <span class="pp-chip <?php echo ($profile->type==='Employer') ? 'pp-chip--emp' : 'pp-chip--seeker'; ?>">
              <?php echo ($profile->type==='Employer') ? 'Employer' : 'Job Seeker'; ?>
            </span>
            <?php if ($profile->type === 'Job Seeker' && !empty($profile->title)): ?>
              <span class="pp-chip pp-title"><?php echo htmlspecialchars($profile->title); ?></span>
            <?php endif; ?>
            <span class="pp-dot"></span>
            <span><?php echo htmlspecialchars($profile->email); ?></span>
            <?php if(!empty($profile->contact)): ?>
              <span class="pp-dot"></span>
              <span><?php echo htmlspecialchars($profile->contact); ?></span>
            <?php endif; ?>
          </div>

          <div class="pp-actions mt-2">
            <?php
              $hasAppResume = ($profile->type === 'Job Seeker' && !empty($appResume));
              $hasAnyResume = ($profile->type === 'Job Seeker' && !empty($resumes));

              if ($hasAppResume || $hasAnyResume):
                if ($hasAppResume) {
                  $href  = $base_url . '/users/user-cvs/' . htmlspecialchars($appResume);
                  $label = $appResumeDisplay ?: $appResume;
                  $btnText = 'Download Resume (This Application)';
                } else {
                  $top   = $resumes[0];
                  $href  = $base_url . '/users/user-cvs/' . htmlspecialchars($top->filename);
                  $label = $top->label ?: ($top->original_name ?? $top->filename);
                  $btnText = $top->is_primary ? 'Download Primary Resume' : 'Download Resume';
                }
            ?>
              <a class="pp-btn pp-btn-primary btn-sm" href="<?php echo $href; ?>" download>
                <i class="fas fa-download"></i> <?php echo htmlspecialchars($btnText); ?>
              </a>
              <small class="pp-small ml-2 text-muted">
                <?php echo htmlspecialchars($label); ?>
              </small>
            <?php endif; ?>
          </div>
        </div>

        <!-- Social -->
        <div class="pp-social text-right">
          <?php
            function fixUrl($url){ if(empty($url)) return '#'; if(!preg_match('#^https?://#i',$url)) $url='https://'.$url; return $url; }
          ?>
          <?php if (!empty($profile->facebook)): ?>
            <a class="text-primary" target="_blank" href="<?php echo htmlspecialchars(fixUrl($profile->facebook)); ?>"><i class="fab fa-facebook-square"></i></a>
          <?php endif; ?>
          <?php if (!empty($profile->twitter)): ?>
            <a class="text-info" target="_blank" href="<?php echo htmlspecialchars(fixUrl($profile->twitter)); ?>"><i class="fab fa-twitter-square"></i></a>
          <?php endif; ?>
          <?php if (!empty($profile->linkedin)): ?>
            <a class="text-primary" target="_blank" href="<?php echo htmlspecialchars(fixUrl($profile->linkedin)); ?>"><i class="fab fa-linkedin"></i></a>
          <?php endif; ?>
        </div>
      </div>
    </div>

    <!-- CONTENT GRID -->
    <div class="pp-grid">

      <!-- LEFT / MAIN -->
      <div>
        <?php if (!empty($profile->bio)): ?>
          <div class="pp-card pp-body">
            <h5><?php echo ($profile->type==='Employer') ? 'Company Overview' : 'About Me'; ?></h5>
            <p><?php echo htmlspecialchars($profile->bio); ?></p>
          </div>
        <?php endif; ?>

        <?php if ($profile->type === 'Job Seeker'): ?>
          <div class="pp-card">
            <div class="row">
              <div class="col-md-6 mb-3 mb-md-0">
                <h5>Skills</h5>
                <?php if (!empty($profile->skills)): ?>
                  <div class="pp-badges">
                    <?php foreach (explode(',', $profile->skills) as $skill):
                      $skill = trim($skill); if (!$skill) continue; ?>
                      <span class="badge badge-dark"><?php echo htmlspecialchars($skill); ?></span>
                    <?php endforeach; ?>
                  </div>
                <?php else: ?>
                  <p class="pp-small"><em>No skills listed.</em></p>
                <?php endif; ?>
              </div>

              <div class="col-md-6">
                <h5>Education</h5>
                <?php if (!empty($profile->education)):
                  $parts = preg_split('/\r\n|\r|\n|,|;|•/u', $profile->education);
                  $parts = array_filter(array_map('trim', $parts));
                ?>
                  <?php if (!empty($parts)): ?>
                    <ul class="mb-0 pl-3">
                      <?php foreach ($parts as $ed): ?>
                        <li><?php echo htmlspecialchars($ed); ?></li>
                      <?php endforeach; ?>
                    </ul>
                  <?php else: ?>
                    <p class="pp-small"><em>No education info provided.</em></p>
                  <?php endif; ?>
                <?php else: ?>
                  <p class="pp-small"><em>No education info provided.</em></p>
                <?php endif; ?>
              </div>
            </div>
          </div>
        <?php endif; ?>

        <?php if ($profile->type === 'Employer' && $companyDetails):
          $hasDetails = $companyDetails->company_website || $companyDetails->industry ||
                        $companyDetails->address_line || $companyDetails->postal_code ||
                        $companyDetails->established_year || $companyDetails->operating_hours;
          if ($hasDetails): ?>
            <div class="pp-card">
              <h5>Company Details</h5>
              <ul class="pp-list">
                <?php if (!empty($companyDetails->company_website)): ?>
                  <li><strong>Website:</strong>
                    <a target="_blank" href="<?php echo htmlspecialchars(fixUrl($companyDetails->company_website)); ?>">
                      <?php echo htmlspecialchars($companyDetails->company_website); ?>
                    </a>
                  </li>
                <?php endif; ?>
                <?php if (!empty($companyDetails->industry)): ?>
                  <li><strong>Industry:</strong> <?php echo htmlspecialchars($companyDetails->industry); ?></li>
                <?php endif; ?>
                <?php if (!empty($companyDetails->address_line)): ?>
                  <li><strong>Address:</strong> <?php echo htmlspecialchars($companyDetails->address_line); ?></li>
                <?php endif; ?>
                <?php if (!empty($companyDetails->postal_code)): ?>
                  <li><strong>Postal Code:</strong> <?php echo htmlspecialchars($companyDetails->postal_code); ?></li>
                <?php endif; ?>
                <?php if (!empty($companyDetails->established_year)): ?>
                  <li><strong>Established:</strong> <?php echo htmlspecialchars($companyDetails->established_year); ?></li>
                <?php endif; ?>
                <?php if (!empty($companyDetails->operating_hours)): ?>
                  <li><strong>Operating Hours:</strong> <?php echo htmlspecialchars($companyDetails->operating_hours); ?></li>
                <?php endif; ?>
              </ul>
            </div>
        <?php endif; endif; ?>
      </div>

      <!-- RIGHT / SIDEBAR -->
      <aside>
        <div class="pp-card">
          <h5>Contact</h5>
          <ul class="pp-list pp-small mb-0">
            <li><strong>Email:</strong> <?php echo htmlspecialchars($profile->email); ?></li>
            <?php if(!empty($profile->contact)): ?>
              <li><strong>Phone:</strong> <?php echo htmlspecialchars($profile->contact); ?></li>
            <?php endif; ?>
          </ul>
        </div>

        <?php if ($profile->type === 'Job Seeker'): ?>
          <?php if (!empty($appResume)): ?>
            <div class="pp-card">
              <h5 class="mb-2">Resume</h5>
              <ul class="pp-list mb-0">
                <li class="d-flex align-items-center justify-content-between">
                  <span>
                    <a href="<?php echo $base_url; ?>/users/user-cvs/<?php echo htmlspecialchars($appResume); ?>" download>
                      <?php echo htmlspecialchars($appResumeDisplay ?: $appResume); ?>
                    </a>
                    <span class="badge badge-info ml-1">Used for this application</span>
                  </span>
                </li>
              </ul>
            </div>
          <?php elseif (!empty($resumes)): ?>
            <div class="pp-card">
              <h5 class="mb-2">Resumes</h5>
              <ul class="pp-list mb-0">
                <?php foreach ($resumes as $r):
                      $name = $r->label ?: ($r->original_name ?: $r->filename); ?>
                  <li class="d-flex align-items-center justify-content-between">
                    <span>
                      <a href="<?php echo $base_url; ?>/users/user-cvs/<?php echo htmlspecialchars($r->filename); ?>" download>
                        <?php echo htmlspecialchars($name); ?>
                      </a>
                      <?php if (!empty($r->is_primary)): ?>
                        <span class="badge badge-success ml-1">Primary</span>
                      <?php endif; ?>
                    </span>
                    <small class="text-muted ml-2">
                      <?php echo date('d M, Y', strtotime($r->created_at ?? 'now')); ?>
                    </small>
                  </li>
                <?php endforeach; ?>
              </ul>
            </div>
          <?php endif; ?>
        <?php endif; ?>

        <?php if (!empty($profile->facebook) || !empty($profile->twitter) || !empty($profile->linkedin)): ?>
          <div class="pp-card text-center">
            <h5>Social</h5>
            <div class="pp-social">
              <?php if (!empty($profile->facebook)): ?>
                <a class="text-primary" target="_blank" href="<?php echo htmlspecialchars(fixUrl($profile->facebook)); ?>"><i class="fab fa-facebook-square"></i></a>
              <?php endif; ?>
              <?php if (!empty($profile->twitter)): ?>
                <a class="text-info" target="_blank" href="<?php echo htmlspecialchars(fixUrl($profile->twitter)); ?>"><i class="fab fa-twitter-square"></i></a>
              <?php endif; ?>
              <?php if (!empty($profile->linkedin)): ?>
                <a class="text-primary" target="_blank" href="<?php echo htmlspecialchars(fixUrl($profile->linkedin)); ?>"><i class="fab fa-linkedin"></i></a>
              <?php endif; ?>
            </div>
          </div>
        <?php endif; ?>
      </aside>
    </div>
  </div>
</section>

<!-- Jobs Posted by Employer -->
<?php if ($profile->type === 'Employer' && count($moreJobs) > 0): ?>
<section class="site-section" style="padding-top: 2rem;">
  <div class="container">
    <div class="row mb-4 justify-content-center">
      <div class="col-md-8 text-center">
        <h3 class="section-title">Jobs Posted by <?php echo htmlspecialchars($profile->fullname); ?></h3>
      </div>
    </div>

    <ul class="custom-job-listings list-unstyled">
      <?php foreach($moreJobs as $job): ?>
        <li class="custom-job-listing-item mb-4 p-3 shadow-sm border">
          <a href="<?php echo $base_url; ?>/jobs/job-single.php?id=<?php echo $job->id; ?>" class="text-decoration-none text-dark d-block">
            <div class="row align-items-center">
              <div class="col-md-6 mb-2 mb-md-0">
                <h5 class="mb-1 font-weight-bold"><?php echo htmlspecialchars($job->job_title); ?></h5>
                <p class="mb-0 text-small"><?php echo htmlspecialchars($job->company_name); ?></p>
              </div>
              <div class="col-md-3 mb-2 mb-md-0">
                <span class="d-block">
                  <i class="icon-room mr-1"></i> <?php echo htmlspecialchars($job->job_region); ?>
                </span>
              </div>
              <div class="col-md-3 text-md-right">
                <span class="badge badge-<?php 
                  echo ($job->job_type == 'Part Time') ? 'danger' : (
                        ($job->job_type == 'Full Time') ? 'primary' : 'secondary');
                ?> text-white">
                  <?php echo htmlspecialchars($job->job_type); ?>
                </span>
                <div class="mt-1">
                  <small>
                    <i class="fa fa-calendar mr-1"></i>
                    Apply by <?php echo date("M d, Y", strtotime($job->application_deadline)); ?>
                  </small>
                </div>
              </div>
            </div>
          </a>
        </li>
      <?php endforeach; ?>
    </ul>
  </div>
</section>
<?php endif; ?>

<?php require "../includes/footer.php"; ?>
