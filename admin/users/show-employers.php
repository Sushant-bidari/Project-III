<?php require "../../config/config.php";
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
// Auth
if (!isset($_SESSION['adminname'])) {
  header("location: " . ADMINURL . "/admins/login-admins.php");
  exit;
}

// Page context
$pageTitle  = "Registered Employers";
$breadcrumb = "Users";

// Helpers
function h($v){ return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }

// Inputs
$q        = trim($_GET['q'] ?? '');
$industry = trim($_GET['industry'] ?? '');

// Pagination
$limit   = 10;
$page    = (isset($_GET['page']) && is_numeric($_GET['page']) && $_GET['page'] > 0) ? (int)$_GET['page'] : 1;
$offset  = ($page - 1) * $limit;
$counter = $offset + 1;

// Build WHERE + params
$where = ["UPPER(u.type) = 'EMPLOYER'"];
$params = [];

if ($industry !== '') {
  $where[] = "c.industry = :industry";
  $params[':industry'] = $industry;
}
if ($q !== '') {
  $where[] = "(u.fullname LIKE :q OR c.industry LIKE :q)";
  $params[':q'] = '%'.$q.'%';
}
$whereSql = $where ? 'WHERE '.implode(' AND ', $where) : '';

// Get distinct industries for filter
$industries = [];
try {
  $indStmt = $conn->query("SELECT DISTINCT industry FROM company_details WHERE industry IS NOT NULL AND TRIM(industry) <> '' ORDER BY industry ASC");
  $industries = $indStmt->fetchAll(PDO::FETCH_COLUMN);
} catch (Exception $e) { /* ignore */ }

// Totals (with filters)
$totalSql = "
  SELECT COUNT(*) AS total
  FROM users u
  JOIN company_details c ON u.id = c.user_id
  $whereSql
";
$totalStmt = $conn->prepare($totalSql);
$totalStmt->execute($params);
$totalRecords = (int)$totalStmt->fetch(PDO::FETCH_OBJ)->total;
$totalPages   = max(1, (int)ceil($totalRecords / $limit));
if ($page > $totalPages) {
  $page   = $totalPages;
  $offset = ($page - 1) * $limit;
  $counter = $offset + 1;
}

// Fetch page (with filters)
$listSql = "
  SELECT 
    u.id, u.fullname, u.username, u.email, u.contact, u.img, u.created_at AS user_created_at,
    c.company_website, c.industry, c.address_line, c.postal_code, c.established_year,
    c.operating_hours, c.business_reg_no, c.company_size, c.org_type,
    c.created_at AS company_created_at, c.updated_at
  FROM users u
  JOIN company_details c ON u.id = c.user_id
  $whereSql
  ORDER BY u.id ASC
  LIMIT :limit OFFSET :offset
";
$stmt = $conn->prepare($listSql);
foreach ($params as $k => $v) { $stmt->bindValue($k, $v); }
$stmt->bindValue(':limit',  $limit,  PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$employers = $stmt->fetchAll(PDO::FETCH_OBJ);

// Build base querystring for pagination (preserve filters/search)
$qs = [];
if ($q !== '')        $qs['q'] = $q;
if ($industry !== '') $qs['industry'] = $industry;
$baseQS = http_build_query($qs);
$sep    = $baseQS ? '&' : '';
?>

<?php require "../layouts/header.php"; ?>

<div class="row">
  <div class="col">
    <div class="card">
      <div class="card-header">
        <div class="d-flex align-items-center justify-content-between flex-wrap">
          <h2 class="card-title h6 mb-2 mb-md-0">Registered Employers</h2>

          <!-- Filters/Search -->
          <form class="form-inline my-2 my-md-0" method="get" action="show-employers.php">
            <div class="form-group mr-2 mb-2 mb-md-0">
              <label for="industry" class="sr-only">Industry</label>
              <select class="form-control" id="industry" name="industry">
                <option value="">All industries</option>
                <?php foreach ($industries as $opt): ?>
                  <option value="<?= h($opt) ?>" <?= ($opt === $industry ? 'selected' : '') ?>>
                    <?= h($opt) ?>
                  </option>
                <?php endforeach; ?>
              </select>
            </div>

            <div class="form-group mr-2 mb-2 mb-md-0">
              <label for="q" class="sr-only">Search</label>
              <input type="text" class="form-control" id="q" name="q"
                     placeholder="Search name or industry…" value="<?= h($q) ?>">
            </div>

            <button type="submit" class="btn btn-primary">Filter</button>
            <?php if ($q !== '' || $industry !== ''): ?>
              <a href="show-employers.php" class="btn btn-outline-secondary ml-2">Reset</a>
            <?php endif; ?>
          </form>
        </div>
      </div>

      <div class="card-body p-0">
        <div class="table-responsive">
          <table class="table table-hover table-bordered mb-0">
            <thead>
              <tr>
                <th style="width:80px">#</th>
                <th>Name</th>
                <th>Email</th>
                <th>Contact</th>
                <th>Industry</th>
                <th>Address</th>
                <th>Established</th>
                <th style="width:120px;">Actions</th>
              </tr>
            </thead>
            <tbody>
              <?php if (!$employers): ?>
                <tr><td colspan="8" class="text-center text-muted py-4">No employers to show.</td></tr>
              <?php else: ?>
                <?php foreach($employers as $u): ?>
                  <tr>
                    <td><?= $counter++; ?></td>
                    <td><?= h($u->fullname); ?></td>
                    <td><?= h($u->email); ?></td>
                    <td><?= h($u->contact); ?></td>
                    <td><?= h($u->industry); ?></td>
                    <td><?= h($u->address_line); ?></td>
                    <td><?= h($u->established_year); ?></td>
                    <td>
                      <button 
                        type="button"
                        class="btn btn-sm btn-info"
                        data-toggle="modal"
                        data-target="#employerModal"
                        title="View details"
                        data-id="<?= (int)$u->id; ?>"
                        data-fullname="<?= h($u->fullname); ?>"
                        data-username="<?= h($u->username); ?>"
                        data-email="<?= h($u->email); ?>"
                        data-contact="<?= h($u->contact); ?>"
                        data-usercreated="<?= h($u->user_created_at); ?>"
                        data-img="<?= h($u->img); ?>"
                        data-website="<?= h($u->company_website); ?>"
                        data-industry="<?= h($u->industry); ?>"
                        data-address="<?= h($u->address_line); ?>"
                        data-postal="<?= h($u->postal_code); ?>"
                        data-estyear="<?= h($u->established_year); ?>"
                        data-hours="<?= h($u->operating_hours); ?>"
                        data-brn="<?= h($u->business_reg_no); ?>"
                        data-csize="<?= h($u->company_size); ?>"
                        data-orgtype="<?= h($u->org_type); ?>"
                        data-ccreated="<?= h($u->company_created_at); ?>"
                        data-cupdated="<?= h($u->updated_at); ?>"
                      >View Details</button>
                    </td>
                  </tr>
                <?php endforeach; ?>
              <?php endif; ?>
            </tbody>
          </table> 
        </div>

        <!-- Pagination -->
        <nav class="p-3">
          <ul class="pagination justify-content-center mb-0">
            <li class="page-item <?= ($page <= 1 ? 'disabled' : '') ?>">
              <a class="page-link" href="?<?= $baseQS . $sep ?>page=<?= max(1, $page - 1) ?>">Previous</a>
            </li>
            <?php for ($i = 1; $i <= $totalPages; $i++): ?>
              <li class="page-item <?= ($page === $i ? 'active' : '') ?>">
                <a class="page-link" href="?<?= $baseQS . $sep ?>page=<?= $i ?>"><?= $i ?></a>
              </li>
            <?php endfor; ?>
            <li class="page-item <?= ($page >= $totalPages ? 'disabled' : '') ?>">
              <a class="page-link" href="?<?= $baseQS . $sep ?>page=<?= min($totalPages, $page + 1) ?>">Next</a>
            </li>
          </ul>
        </nav>
      </div>
    </div>
  </div>
</div>

<!-- Modal: Employer Details -->
<div class="modal fade" id="employerModal" tabindex="-1" role="dialog" aria-labelledby="employerModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-scrollable" role="document">
    <div class="modal-content">
      <div class="modal-header bg-light">
        <h5 class="modal-title" id="employerModalLabel">Employer Details</h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span>&times;</span></button>
      </div>

      <div class="modal-body">
        <div class="media mb-3">
          <img id="empLogo" src="" class="mr-3 rounded" alt="Logo" style="width:64px;height:64px;object-fit:cover;border:1px solid rgba(0,0,0,.1)">
          <div class="media-body">
            <h5 class="mt-0 mb-1"><span id="empFullname">—</span> <small class="text-muted">(<span id="empUsername">—</span>)</small></h5>
            <div class="text-muted small">Created: <span id="empUserCreated">—</span></div>
          </div>
        </div>

        <div class="row">
          <div class="col-md-6">
            <h6 class="border-bottom pb-2">Account</h6>
            <dl class="row mb-0">
              <dt class="col-sm-5">Email</dt>
              <dd class="col-sm-7" id="empEmail">—</dd>
              <dt class="col-sm-5">Contact</dt>
              <dd class="col-sm-7" id="empContact">—</dd>
            </dl>
          </div>
          <div class="col-md-6">
            <h6 class="border-bottom pb-2">Company</h6>
            <dl class="row mb-0">
              <dt class="col-sm-5">Website</dt>
              <dd class="col-sm-7" id="empWebsite">—</dd>
              <dt class="col-sm-5">Industry</dt>
              <dd class="col-sm-7" id="empIndustry">—</dd>
            </dl>
          </div>
        </div>

        <hr>

        <div class="row">
          <div class="col-md-6">
            <dl class="row mb-0">
              <dt class="col-sm-5">Address</dt>
              <dd class="col-sm-7" id="empAddress">—</dd>
              <dt class="col-sm-5">Postal Code</dt>
              <dd class="col-sm-7" id="empPostal">—</dd>
              <dt class="col-sm-5">Established Year</dt>
              <dd class="col-sm-7" id="empEstYear">—</dd>
              <dt class="col-sm-5">Operating Hours</dt>
              <dd class="col-sm-7" id="empHours">—</dd>
            </dl>
          </div>
          <div class="col-md-6">
            <dl class="row mb-0">
              <dt class="col-sm-5">Business Reg. No</dt>
              <dd class="col-sm-7" id="empBRN">—</dd>
              <dt class="col-sm-5">Company Size</dt>
              <dd class="col-sm-7" id="empCSize">—</dd>
              <dt class="col-sm-5">Organization Type</dt>
              <dd class="col-sm-7" id="empOrgType">—</dd>
              <dt class="col-sm-5">Company Details Created:</dt>
              <dd class="col-sm-7" id="empCrt">—</dd>
              <dt class="col-sm-5">Company Details Updated:</dt>
              <dd class="col-sm-7" id="empUpd">—</dd>
            </dl>
          </div>
        </div>
      </div>

      <div class="modal-footer">
        <button type="button" class="btn btn-outline-secondary" data-dismiss="modal">Close</button>
      </div>
    </div>
  </div>
</div>

<script>
window.addEventListener('load', function(){
  var $ = window.jQuery;

  // Open modal
  $(document).on('click', 'button[data-target="#employerModal"]', function(e){
    e.preventDefault();
    $('#employerModal').modal('show').data('trigger', this);
  });

  // Populate modal
  $('#employerModal').on('show.bs.modal', function (event) {
    var trigger = $(this).data('trigger') || event.relatedTarget;
    var btn     = $(trigger);
    var modal   = $(this);

    function display(v){ v = (v==null? '' : String(v)).trim(); return v.length? v : '—'; }
    function imgSrc(filename){
      filename = (filename||'').trim();
      return '../../users/user-images/' + (filename || 'emp_imgplaceholder.png');
    }
    function fmt12(dtStr){
      if (!dtStr) return '—';
      var s = String(dtStr).replace('T',' ').trim();
      var m = s.match(/^(\d{4})-(\d{2})-(\d{2})\s+(\d{2}):(\d{2})(?::(\d{2}))?$/);
      if (!m) return s;
      var date = new Date(+m[1], +m[2]-1, +m[3], +m[4], +m[5], +(m[6]||0));
      var mons = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];
      var h = date.getHours(), ap = h>=12?'PM':'AM'; h = h%12 || 12;
      return mons[date.getMonth()]+' '+String(date.getDate()).padStart(2,'0')+', '+date.getFullYear()+
             ' '+String(h).padStart(2,'0')+':'+String(date.getMinutes()).padStart(2,'0')+' '+ap;
    }

    modal.find('#empFullname').text(display(btn.data('fullname')));
    modal.find('#empUsername').text(display(btn.data('username')));
    modal.find('#empUserCreated').text(display(fmt12(btn.data('usercreated'))));
    modal.find('#empLogo').attr('src', imgSrc(btn.data('img')));

    modal.find('#empEmail').text(display(btn.data('email')));
    modal.find('#empContact').text(display(btn.data('contact')));

    modal.find('#empWebsite').text(display(btn.data('website')));
    modal.find('#empIndustry').text(display(btn.data('industry')));
    modal.find('#empAddress').text(display(btn.data('address')));
    modal.find('#empPostal').text(display(btn.data('postal')));
    modal.find('#empEstYear').text(display(btn.data('estyear')));
    modal.find('#empHours').text(display(btn.data('hours')));

    modal.find('#empBRN').text(display(btn.data('brn')));
    modal.find('#empCSize').text(display(btn.data('csize')));
    modal.find('#empOrgType').text(display(btn.data('orgtype')));
    modal.find('#empCrt').text(display(fmt12(btn.data('ccreated'))));
    modal.find('#empUpd').text(display(fmt12(btn.data('cupdated'))));
  });

  $('.dropdown-toggle').dropdown();
});
</script>

<?php require "../layouts/footer.php"; ?>
