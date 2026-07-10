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
// Gate: job seeker only
if (isset($_SESSION['type']) && $_SESSION['type'] !== "Job Seeker") {
  header("location: " . APPURL);
  exit;
}
if (!isset($_SESSION['username']) || !isset($_SESSION['type'])) {
  header("location: " . APPURL);
  exit;
}

$user_id = (int)$_SESSION['id'];

// CSRF
if (empty($_SESSION['csrf_token'])) {
  $_SESSION['csrf_token'] = bin2hex(random_bytes(16));
}

// Load availability (or defaults)
$stmt = $conn->prepare("SELECT * FROM availability WHERE user_id = :user_id LIMIT 1");
$stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
$stmt->execute();
$row = $stmt->fetch(PDO::FETCH_ASSOC);

$days = ['monday','tuesday','wednesday','thursday','friday','saturday','sunday'];
$availability_data = $row ?: array_fill_keys($days, 'none');

// Labels shown to the user
$labels = [
  'morning'  => 'Morning',
  'afternoon'=> 'Afternoon',
  'evening'  => 'Evening',
  'wholeday' => 'Anytime',
  'none'     => 'Not available'
];
?>

<style>
  /* ===== Hero ===== */
.availability-hero{
  position:relative; background-size:cover; background-position:center;
  padding: 60px 0; overflow:hidden;
}
.availability-hero .overlay-dark{ position:absolute; inset:0;
  background:
    radial-gradient(1200px 400px at 10% -10%, rgba(99,102,241,.22), transparent 60%),
    radial-gradient(1200px 400px at 90% 0%, rgba(6,182,212,.18), transparent 60%),
    linear-gradient(180deg, rgba(2,6,23,.65), rgba(2,6,23,.80));
}
.availability-hero .hero-inner{ position:relative; z-index:2; }
.ch-eyebrow{ color:#c7d2fe; text-transform:uppercase; letter-spacing:.16em; font-weight:700; font-size:.75rem; }
.ch-title{ color:#fff; font-weight:800; }
.ch-sub{ color:#e2e8f0; }

  /* Card polish */
  .av-card{ background:#fff; border:0; border-radius:14px; box-shadow:0 8px 24px rgba(0,0,0,.06); }
  .av-card .card-header{ background:#fff; border-bottom:1px solid rgba(0,0,0,.06); border-top-left-radius:14px; border-top-right-radius:14px; }
  .av-card h5{ margin:0; font-weight:800; }

  /* Day tiles */
  .day-tile{ border:1px solid rgba(0,0,0,.08); border-radius:12px; padding:14px; transition: box-shadow .15s ease, transform .05s ease; background:#fff; }
  .day-tile:hover{ box-shadow:0 8px 28px rgba(0,0,0,.08); transform: translateY(-1px); }
  .day-name{ font-weight:800; margin-bottom:8px; display:flex; align-items:center; gap:8px; }
  .day-name .fa{ opacity:.7; }

  /* Radio pill group */
  .pill-group .btn{ border-radius:999px; padding:.35rem .7rem; font-weight:700; font-size:.85rem; }
  .pill-group .btn input{ display:none; }
  .pill-group .btn{ border:1px solid #d1d5db; color:#374151; background:#fff; }
  .pill-group .btn.active{ background:#0d6efd; color:#fff; border-color:#0d6efd; }
  .pill-group .btn:not(.active):hover{ background:#f3f4f6; }

  /* Legend chips */
  .chip{ display:inline-flex; align-items:center; gap:8px; padding:.35rem .55rem; border-radius:999px; background:#f8fafc; border:1px solid #e5e7eb; font-weight:600; font-size:.85rem; margin-right:6px; margin-bottom:6px; }
  .chip .dot{ width:8px; height:8px; border-radius:999px; display:inline-block; }
  .dot-morning{ background:#fde68a; }
  .dot-afternoon{ background:#93c5fd; }
  .dot-evening{ background:#c4b5fd; }
  .dot-wholeday{ background:#86efac; }
  .dot-none{ background:#e5e7eb; }

  /* Live summary table */
  .av-summary table{ font-size:.95rem; }
  .badge-soft{ border-radius:999px; padding:.35rem .6rem; font-weight:700; }
  .b-morning{ background:#fff7ed; color:#a16207; border:1px solid #fde68a; }
  .b-afternoon{ background:#eff6ff; color:#1d4ed8; border:1px solid #93c5fd; }
  .b-evening{ background:#f5f3ff; color:#6d28d9; border:1px solid #c4b5fd; }
  .b-wholeday{ background:#ecfdf5; color:#065f46; border:1px solid #86efac; }
  .b-none{ background:#f9fafb; color:#6b7280; border:1px solid #e5e7eb; }

  .quick-btn{ border-radius:10px; }
</style>

<!-- HERO -->
<section class="availability-hero overlay inner-page bg-image" style="background-image:url('../images/tst.jpg');" id="home-section">
  <div class="overlay-dark"></div>
  <div class="container hero-inner text-center">
    <div class="ch-eyebrow mb-1">Availability</div>
<h2 class="ch-title mb-2">Manage Your Availability</h2>
<p class="ch-sub mb-0">Let employers know when you're open to work. Update your availability to stay top of mind for the right opportunities.</p>

  </div>
</section>

<section class="site-section" id="next-section">
  <div class="container">

    <?php
    // Flash alert (Bootstrap dismissible)
    if (session_status() !== PHP_SESSION_ACTIVE) { session_start(); }

    if (!empty($_SESSION['avail_flash'])) {
    $type = $_SESSION['avail_flash']['type'] ?? 'success';
    $text = $_SESSION['avail_flash']['text'] ?? 'Availability updated.';
    echo '<div class="alert alert-' . htmlspecialchars($type) . ' alert-dismissible fade show auto-close" role="alert">'
        . htmlspecialchars($text)
        . '<button type="button" class="close" data-dismiss="alert" aria-label="Close">'
        . '<span aria-hidden="true">&times;</span>'
        . '</button>'
        . '</div>';
    unset($_SESSION['avail_flash']);
    } elseif (isset($_GET['saved']) && $_GET['saved'] === '1') {
    
    echo '<div class="alert alert-success alert-dismissible fade show auto-close" role="alert">'
        . 'Your availability has been updated.'
        . '<button type="button" class="close" data-dismiss="alert" aria-label="Close">'
        . '<span aria-hidden="true">&times;</span>'
        . '</button>'
        . '</div>';
    }
    ?>


    <div class="row">
      <!-- Left: interactive editor -->
      <div class="col-lg-8 mb-4">
        <div class="card av-card mb-3">
          <div class="card-header d-flex align-items-center justify-content-between">
            <h5>Set your weekly availability</h5>
            <div class="text-muted small">Choose one option per day</div>
          </div>
          <div class="card-body pt-3">

            <!-- Quick presets -->
            <div class="mb-3">
              <span class="mr-2 font-weight-bold">Quick presets:</span>
              <button type="button" class="btn btn-outline-primary btn-sm quick-btn" data-preset="weekdays">Weekdays (Anytime)</button>
              <button type="button" class="btn btn-outline-primary btn-sm quick-btn" data-preset="weekends">Weekends (Anytime)</button>
              <button type="button" class="btn btn-outline-primary btn-sm quick-btn" data-preset="evenings">Evenings (All week)</button>
              <button type="button" class="btn btn-outline-secondary btn-sm quick-btn" data-preset="clear">Clear all</button>
              <button type="button" class="btn btn-outline-secondary btn-sm quick-btn" data-preset="copy-mon">Copy Monday → All</button>
            </div>

            <form method="POST" action="update_availability.php" id="availabilityForm">
              <input type="hidden" name="csrf" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
              <div class="row">
                <?php foreach ($days as $day):
                  $val = $availability_data[$day] ?? 'none';
                  $prettyDay = ucfirst($day);
                ?>
                <div class="col-md-6 mb-3">
                  <div class="day-tile">
                    <div class="day-name"><i class="fa fa-calendar-day"></i> <?php echo $prettyDay; ?></div>
                    <div class="btn-group btn-group-toggle d-flex flex-wrap pill-group" data-toggle="buttons">
                      <?php foreach (['morning','afternoon','evening','wholeday','none'] as $opt):
                        $active = ($val === $opt) ? 'active' : '';
                        $checked = ($val === $opt) ? 'checked' : '';
                      ?>
                        <label class="btn btn-sm mr-1 mb-1 <?php echo $active; ?>">
                          <input type="radio" name="<?php echo $day; ?>" value="<?php echo $opt; ?>" <?php echo $checked; ?>>
                          <?php echo $labels[$opt]; ?>
                        </label>
                      <?php endforeach; ?>
                    </div>
                    <div class="mt-2 small text-muted">Selected: <span class="js-selected" data-day="<?php echo $day; ?>">—</span></div>
                  </div>
                </div>
                <?php endforeach; ?>
              </div>
              <input type="hidden" name="update_availability" value="1">

              <div class="d-flex align-items-center">
                <button type="submit" name="update_availability" class="btn btn-success">
                  <i class="fa fa-save mr-1"></i> Save Availability
                </button>
                <span class="ml-3 text-muted small">Employers may use this to schedule interviews/time slots.</span>
              </div>
            </form>
          </div>
        </div>

        
      </div>

      <!-- Right: live summary -->
      <div class="col-lg-4">
        <div class="card av-card av-summary">
          <div class="card-header"><h5>Live Summary</h5></div>
          <div class="card-body">
            <table class="table table-sm table-borderless mb-0">
              <tbody id="summaryTable">
                <?php foreach ($days as $day):
                  $v = $availability_data[$day] ?? 'none';
                  $badgeCls = 'b-' . $v;
                ?>
                <tr>
                  <td class="font-weight-bold" style="width: 38%;"><?php echo ucfirst($day); ?></td>
                  <td><span class="badge-soft <?php echo $badgeCls; ?>" data-sum="<?php echo $day; ?>"><?php echo $labels[$v]; ?></span></td>
                </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
            <hr>
            <p class="small text-muted mb-0">
              Tip: Use **Quick presets** above to fill your week in one click, then tweak specific days.
            </p>
          </div>
        </div>

        <!-- Legend -->
        <div class="card av-card">
          <div class="card-header"><h5>Legend</h5></div>
          <div class="card-body">
            <span class="chip"><span class="dot dot-morning"></span> Morning (6am–12pm)</span>
            <span class="chip"><span class="dot dot-afternoon"></span> Afternoon (12–5pm)</span>
            <span class="chip"><span class="dot dot-evening"></span> Evening (5–10pm)</span>
            <span class="chip"><span class="dot dot-wholeday"></span> Anytime (whole day)</span>
            <span class="chip"><span class="dot dot-none"></span> Not available</span>
          </div>
        </div>
      </div>
    </div>

  </div>
</section>

<script>
// Utilities
function prettyLabel(v){
  var m = {morning:'Morning', afternoon:'Afternoon', evening:'Evening', wholeday:'Anytime', none:'Not available'};
  return m[v] || 'Not available';
}
function badgeClass(v){
  return 'b-' + (v || 'none');
}

// Initialize selected labels & summary
function refreshDayDisplay(){
  document.querySelectorAll('.day-tile').forEach(function(tile){
    var radios = tile.querySelectorAll('input[type=radio]');
    var chosen = 'none', dayKey = tile.querySelector('.js-selected')?.dataset.day;
    radios.forEach(function(r){ if(r.checked) chosen = r.value; });
    // Inline label
    var selEl = tile.querySelector('.js-selected');
    if(selEl){ selEl.textContent = prettyLabel(chosen); }
    // Summary badge
    if(dayKey){
      var b = document.querySelector('[data-sum="'+dayKey+'"]');
      if (b){
        b.textContent = prettyLabel(chosen);
        b.className = 'badge-soft ' + badgeClass(chosen);
      }
    }
  });
}

// Quick presets
function applyPreset(type){
  var sets = {
    weekdays: function(day){
      return (['monday','tuesday','wednesday','thursday','friday'].indexOf(day) >= 0) ? 'wholeday' : 'none';
    },
    weekends: function(day){
      return (day==='saturday' || day==='sunday') ? 'wholeday' : 'none';
    },
    evenings: function(day){ return 'evening'; },
    clear:    function(day){ return 'none'; }
  };
  var fn = sets[type]; if(!fn) return;
  ['monday','tuesday','wednesday','thursday','friday','saturday','sunday'].forEach(function(d){
    var val = fn(d);
    var input = document.querySelector('input[name="'+d+'"][value="'+val+'"]');
    if (input){ input.checked = true; }
  });
  refreshDayDisplay();
}

// Copy Monday to all
function copyMonday(){
  var mon = document.querySelector('input[name="monday"]:checked');
  var val = mon ? mon.value : 'none';
  ['tuesday','wednesday','thursday','friday','saturday','sunday'].forEach(function(d){
    var input = document.querySelector('input[name="'+d+'"][value="'+val+'"]');
    if (input){ input.checked = true; }
  });
  refreshDayDisplay();
}

document.addEventListener('DOMContentLoaded', function(){
  // Toggle active state for pills
  document.querySelectorAll('.pill-group').forEach(function(group){
    group.addEventListener('click', function(e){
      var label = e.target.closest('label.btn'); if(!label) return;
      // deactivate siblings
      group.querySelectorAll('label.btn').forEach(function(l){ l.classList.remove('active'); });
      // activate clicked
      label.classList.add('active');
      // ensure radio is checked
      var radio = label.querySelector('input[type=radio]'); if (radio) radio.checked = true;
      refreshDayDisplay();
    });
  });

  // Init
  refreshDayDisplay();

  // Presets
  document.querySelectorAll('[data-preset]').forEach(function(btn){
    btn.addEventListener('click', function(){
      var t = this.getAttribute('data-preset');
      if (t === 'copy-mon') copyMonday(); else applyPreset(t);
    });
  });
});
</script>
<script>
(function (w, $) {
  var el = document.querySelector('.alert.auto-close');
  if (!el) return;
  setTimeout(function () {
    if ($ && $.fn && $.fn.alert) { $(el).alert('close'); }
    else { el.classList.remove('show'); el.parentNode && el.parentNode.removeChild(el); }
  }, 7000);
})(window, window.jQuery);
</script>

<?php require "../includes/footer.php"; ?>
