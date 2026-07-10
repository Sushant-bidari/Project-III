<?php
require "../config/config.php";
require "../includes/header.php";
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
if (!isset($_SESSION['id'])) { header("Location: " . APPURL); exit; }
$uid = (int)$_SESSION['id'];

$page    = max(1, (int)($_GET['p'] ?? 1));
$perPage = 10;
$offset  = ($page - 1) * $perPage;

// total
$stmt = $conn->prepare("SELECT COUNT(*) FROM notifications WHERE recipient_user_id = :uid");
$stmt->execute([':uid' => $uid]);
$total = (int)$stmt->fetchColumn();
$pages = max(1, (int)ceil($total / $perPage));

// fetch page
$q = $conn->prepare("
  SELECT id, title, message, link_path, seen, created_at
  FROM notifications
  WHERE recipient_user_id = :uid
  ORDER BY created_at DESC
  LIMIT :lim OFFSET :off
");
$q->bindValue(':uid', $uid, PDO::PARAM_INT);
$q->bindValue(':lim', $perPage, PDO::PARAM_INT);
$q->bindValue(':off', $offset, PDO::PARAM_INT);
$q->execute();
$rows = $q->fetchAll(PDO::FETCH_OBJ);

function h($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
?>
<style>
  .notif-page-card{ background:#fff; border:0; border-radius:14px; box-shadow:0 8px 24px rgba(0,0,0,.06); }
  .notif-page-card .card-header{ background:#fff; border-bottom:1px solid rgba(0,0,0,.08); border-top-left-radius:14px; border-top-right-radius:14px; }
  
.notif-item.notif-unseen{
  background:#eef2ff !important;
}
.notif-page-card .list-group-flush > .list-group-item.notif-unseen{
  border-left:3px solid #3b82f6 !important;
}
.notif-item.notif-unseen .n-title{
  font-weight:700; color:#0f172a;
}
.notif-item.notif-unseen .small{
  color:#334155;
}
/* blue dot for unseen */
.notif-dot{
  width:8px; height:8px; border-radius:999px; background:#3b82f6;
  display:inline-block; margin-right:8px; margin-top:.4rem; flex:0 0 8px;
}

  .notif-item.notif-unseen:hover{ background:#eef2ff; }
  .notif-item .notif-date{ white-space:nowrap; }
</style>

<section class="site-section" style="padding-top: 2rem;">
  <div class="container">
    <div class="card notif-page-card">
      <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0">All Notifications</h5>
        <button class="btn btn-link btn-sm p-0" id="notifCenterMarkAll">Mark all read</button>
      </div>
      <div class="list-group list-group-flush">
        <?php if (!$rows): ?>
          <div class="p-3 text-muted small">No notifications.</div>
        <?php else: ?>
          <?php foreach ($rows as $n):
            $isUnseen = ((string)$n->seen !== '1');
            $cls      = $isUnseen ? 'notif-unseen' : '';
            $date     = date('M j, Y', strtotime($n->created_at ?? 'now'));
            ?>
            <a href="#" class="list-group-item list-group-item-action d-flex align-items-start notif-item <?php echo $cls; ?>"
                data-id="<?php echo (int)$n->id; ?>" data-link="<?php echo h($n->link_path ?? '#'); ?>">
                <?php echo $isUnseen
                ? '<span class="notif-dot mr-2"></span>'
                : '<div class="mr-2"><i class="fa-regular fa-bell"></i></div>'; ?>
                <div class="flex-grow-1">
                <div class="n-title"><?php echo h($n->title ?? ''); ?></div>
                <div class="small text-muted"><?php echo h($n->message ?? ''); ?></div>
                </div>
                <div class="notif-date small text-muted ml-2"><?php echo h($date); ?></div>
            </a>
            <?php endforeach; ?>

        <?php endif; ?>
      </div>

      <?php if ($pages > 1): ?>
        <div class="card-footer">
          <nav aria-label="Notifications pagination">
            <ul class="pagination justify-content-center mb-0">
              <li class="page-item <?php echo $page<=1?'disabled':''; ?>">
                <a class="page-link" href="?p=<?php echo $page-1; ?>" tabindex="-1">&laquo; Prev</a>
              </li>
              <?php for ($i=1; $i<=$pages; $i++): ?>
                <li class="page-item <?php echo $i===$page?'active':''; ?>">
                  <a class="page-link" href="?p=<?php echo $i; ?>"><?php echo $i; ?></a>
                </li>
              <?php endfor; ?>
              <li class="page-item <?php echo $page>=$pages?'disabled':''; ?>">
                <a class="page-link" href="?p=<?php echo $page+1; ?>">Next &raquo;</a>
              </li>
            </ul>
          </nav>
        </div>
      <?php endif; ?>
    </div>
  </div>
</section>

<script>
(function(){
  var base = <?php echo json_encode($base_url); ?>;

  // Click: mark seen then navigate
  $(document).on('click', '.notif-item', function(e){
    e.preventDefault();
    var id = $(this).data('id');
    var link = $(this).data('link');
    $.post(base + '/users/notifications_api.php', {action:'mark', id:id}, function(){
      if (link && link !== '#') window.location = link;
      else location.reload();
    }, 'json');
  });

  // Mark all
  $('#notifCenterMarkAll').on('click', function(e){
    e.preventDefault();
    $.post(base + '/users/notifications_api.php', {action:'mark_all'}, function(){
      location.reload();
    }, 'json');
  });
})();
</script>

<?php require "../includes/footer.php"; ?>
