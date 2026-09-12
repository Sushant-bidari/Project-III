<?php
// session_start();

$base_url = (isset($_SERVER['HTTPS']) ? "https" : "http") . "://" . $_SERVER['HTTP_HOST'];

$project_folder = explode('/', trim($_SERVER['SCRIPT_NAME'], '/'))[0];

$base_url .= '/' . $project_folder;
?>

<!doctype html>
<html lang="en">

<head>
  <title>Online Job Portal</title>

  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
  <meta name="description" content="Online Job Portal">
  <meta name="keywords" content="jobs, Nepal jobs, employment, job portal">
  <meta name="author" content="Prototype">

  <link rel="shortcut icon" href="<?php echo $base_url; ?>/images/logo.png">

  <link rel="stylesheet" href="<?php echo $base_url; ?>/css/custom-bs.css">

  <script src="<?php echo $base_url; ?>/js/jquery.min.js"></script>

  <link rel="stylesheet" href="<?php echo $base_url; ?>/css/jquery.fancybox.min.css">
  <link rel="stylesheet" href="<?php echo $base_url; ?>/css/bootstrap-select.min.css">
  <link rel="stylesheet" href="<?php echo $base_url; ?>/fonts/icomoon/style.css">
  <link rel="stylesheet" href="<?php echo $base_url; ?>/fonts/line-icons/style.css">
  <link rel="stylesheet" href="<?php echo $base_url; ?>/css/owl.carousel.min.css">
  <link rel="stylesheet" href="<?php echo $base_url; ?>/css/animate.min.css">
  <link rel="stylesheet" href="<?php echo $base_url; ?>/css/quill.snow.css">

  <!-- Font Awesome -->
  <link
    rel="stylesheet"
    href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css"
    integrity="sha512-z3gLpd7yknf1YoNbCzqRKc4qyor8gaKU1qmn+CShxbuBusANI9QpRohGBreCFkKxLhei6S9CQXFEbbKuqLg0DA=="
    crossorigin="anonymous"
    referrerpolicy="no-referrer"
  />

  <!-- Main CSS -->
  <link rel="stylesheet" href="<?php echo $base_url; ?>/css/style.css">

  <script src="<?php echo $base_url; ?>/plugin/ckeditor/ckeditor.js"></script>

  <style>

    /* =========================================
       NAVIGATION LINK
    ========================================= */

    .navbar-nav > .nav-item > a.nav-link:not(.dropdown-toggle):not(.btn) {
      position: relative;
      display: inline-block;
      padding-bottom: 4px;
      transition: color .3s ease;
    }

    .navbar-nav > .nav-item > a.nav-link:not(.dropdown-toggle):not(.btn)::after {
      content: "";
      position: absolute;
      left: 0;
      bottom: 0;
      height: 2px;
      width: 100%;
      background-color: #007bff;
      transform: scaleX(0);
      transform-origin: left;
      transition: transform .3s ease;
    }

    .navbar-nav > .nav-item > a.nav-link:not(.dropdown-toggle):not(.btn):hover::after {
      transform: scaleX(1);
    }

    .navbar-nav .dropdown-toggle::after {
      border-top-color: currentColor;
      margin-left: .35rem;
      border-top-width: .35em;
    }


    /* =========================================
       POST JOB BUTTON
    ========================================= */

    .btn-post {
      border: 0;
      color: #fff !important;
      background: linear-gradient(135deg, #6b7280, #4b5563);
      padding: .45rem .9rem;
      border-radius: 999px;
      box-shadow: 0 2px 8px rgba(0, 0, 0, .12);
      display: inline-flex;
      align-items: center;
      justify-content: center;
      transition: transform .15s ease,
                  box-shadow .15s ease,
                  filter .15s ease;
    }

    .btn-post:hover {
      transform: translateY(-1px);
      box-shadow: 0 6px 16px rgba(0, 0, 0, .18);
      filter: brightness(1.02);
    }

    .btn-post i {
      font-size: .95rem;
    }


    /* =========================================
       USER CHIP
    ========================================= */

    .user-chip {
      display: inline-flex;
      align-items: center;
      gap: 8px;
      padding: .35rem .6rem .35rem .4rem;
      border-radius: 999px;
      border: 1px solid rgba(0, 0, 0, .08);
      background: #f8fafc;
      transition: background .15s ease,
                  box-shadow .15s ease,
                  border-color .15s ease;
    }

    .user-chip:hover {
      background: #eef2ff;
      border-color: rgba(99, 102, 241, .25);
      box-shadow: 0 2px 10px rgba(99, 102, 241, .12);
    }

    .user-chip .avatar {
      width: 28px;
      height: 28px;
      border-radius: 50%;
      background-color: #e2e8f0;
      background-size: cover;
      background-position: center;
      display: inline-flex;
      align-items: center;
      justify-content: center;
      font-weight: 800;
      color: #334155;
    }

    .user-chip .avatar:not(.has-img)::after {
      content: attr(data-initial);
      font-size: .8rem;
    }

    .user-chip .name {
      font-weight: 600;
      color: #111827;
    }


    /* =========================================
       NOTIFICATIONS
    ========================================= */

    .navbar .nav-item#notifDropdown {
      margin-right: .5rem;
    }

    #notifDropdown .nav-link {
      position: relative;
      padding-right: 1.25rem;
    }

    #notifDropdown .fa-bell {
      font-size: 1.15rem;
      line-height: 1;
    }

    #notifCount {
      position: absolute;
      top: -6px;
      right: -10px;
      z-index: 10;
      display: none;
      min-width: 18px;
      height: 18px;
      padding: 0 4px;
      font-size: 11px;
      line-height: 18px;
      border-radius: 999px;
      box-shadow: 0 0 0 2px #fff;
    }

    #notifList .list-group-item {
      align-items: center !important;
    }

    #notifList .notif-date {
      white-space: nowrap;
      flex: 0 0 auto;
      text-align: right;
      min-width: 78px;
    }

    #notifList .list-group-item.notif-unseen {
      background-color: #eef2ff !important;
    }

    #notifList .list-group-item.notif-unseen .n-title {
      font-weight: 700;
      color: #0f172a;
    }

    #notifList .list-group-item.notif-unseen .small {
      color: #334155;
    }

    #notifList .list-group-item .notif-dot {
      width: 8px;
      height: 8px;
      border-radius: 999px;
      background: #3b82f6;
      display: inline-block;
      margin-right: 8px;
      flex: 0 0 8px;
      align-self: .6em;
    }


    /* =========================================
       SMOOTH FOOTER NAVIGATION
    ========================================= */

    html {
      scroll-behavior: smooth;
    }

    #about-footer,
    #contact-footer,
    #faq-footer {
      scroll-margin-top: 90px;
    }


    /* =========================================
       MOBILE
    ========================================= */

    @media (max-width: 420px) {

      .btn-post .d-sm-inline {
        display: none !important;
      }

      .user-chip .name {
        display: none;
      }

      #notifCount {
        right: -8px;
        top: -5px;
      }

    }

    @media (max-width: 480px) {

      #notifList .notif-date {
        display: none;
      }

    }

  </style>

</head>


<body id="top">

<div class="site-wrap">


  <!-- =========================================
       MOBILE MENU
  ========================================= -->

  <div class="site-mobile-menu site-navbar-target">

    <div class="site-mobile-menu-header">

      <div class="site-mobile-menu-close mt-3">
        <span class="icon-close2 js-menu-toggle"></span>
      </div>

    </div>

    <div class="site-mobile-menu-body"></div>

  </div>


  <!-- =========================================
       NAVBAR
  ========================================= -->

  <header class="site-navbar mt-3">

    <div class="container-fluid">

      <div class="row align-items-center">

      </div>

    </div>

  </header>


  <nav class="navbar navbar-expand-lg navbar-light bg-light static-top">

    <div class="container">


      <!-- LOGO -->

      <a href="<?php echo $base_url; ?>" class="navbar-brand">

        <img
          src="<?php echo $base_url; ?>/images/logo.png"
          alt="Online Job Portal Logo"
          width="30"
          height="30"
        >

        Online Job Portal

      </a>


      <!-- MOBILE TOGGLE -->

      <button
        class="navbar-toggler"
        type="button"
        data-toggle="collapse"
        data-target="#navbarSupportedContent"
        aria-controls="navbarSupportedContent"
        aria-expanded="false"
        aria-label="Toggle navigation"
      >

        <span class="navbar-toggler-icon"></span>

      </button>


      <!-- MENU -->

      <div class="collapse navbar-collapse" id="navbarSupportedContent">

        <ul class="navbar-nav me-auto mb-2 mb-lg-0 ml-auto">


          <!-- HOME -->

          <li class="nav-item">

            <a
              class="nav-link"
              href="<?php echo $base_url; ?>"
            >
              Home
            </a>

          </li>


          <!-- ABOUT US -->

          <li class="nav-item">

            <a
              href="<?php echo $base_url; ?>/#about-footer"
              class="nav-link"
            >
              About Us
            </a>

          </li>


          <!-- CONTACT US -->

          <li class="nav-item">

            <a
              href="<?php echo $base_url; ?>/#contact-footer"
              class="nav-link"
            >
              Contact Us
            </a>

          </li>


          <!-- FAQS -->

          <li class="nav-item">

            <a
              href="<?php echo $base_url; ?>/#faq-footer"
              class="nav-link"
            >
              FAQs
            </a>

          </li>


          <!-- COMPANIES -->

          <li class="nav-item">

            <a
              href="<?php echo $base_url; ?>/gerneral/companies.php"
              class="nav-link"
            >
              Companies
            </a>

          </li>


          <!-- EXPLORE JOBS -->

          <li class="nav-item">

            <a
              href="<?php echo $base_url; ?>/findjobs.php"
              class="nav-link"
            >
              Explore Jobs
            </a>

          </li>


          <?php if (isset($_SESSION['username'])): ?>


            <!-- EMPLOYER DASHBOARD -->

            <?php if (
              isset($_SESSION['type']) &&
              $_SESSION['type'] === "Employer"
            ): ?>

              <li class="nav-item mr-2">

                <a
                  href="<?php echo $base_url; ?>/users/employer_dashboard.php"
                  class="btn btn-post"
                >

                  <i class="fa-solid fa-gauge mr-1"></i>

                  <span class="d-none d-sm-inline">
                    Dashboard
                  </span>

                </a>

              </li>

            <?php endif; ?>


            <!-- =========================================
                 NOTIFICATIONS
            ========================================= -->

            <li
              class="nav-item dropdown"
              id="notifDropdown"
            >

              <a
                class="nav-link position-relative"
                href="#"
                id="notifBell"
                role="button"
                data-toggle="dropdown"
                aria-haspopup="true"
                aria-expanded="false"
              >

                <i class="fa-regular fa-bell"></i>

                <span
                  id="notifCount"
                  class="badge badge-danger badge-pill"
                  style="position:absolute; top:0; right:6px; display:none;"
                >
                  0
                </span>

              </a>


              <div
                class="dropdown-menu dropdown-menu-right p-0"
                aria-labelledby="notifBell"
                style="min-width:320px;"
              >

                <div
                  class="dropdown-header d-flex justify-content-between align-items-center"
                >

                  <span>
                    Notifications
                  </span>

                  <button
                    class="btn btn-link btn-sm p-0"
                    id="notifMarkAll"
                  >
                    Mark all read
                  </button>

                </div>


                <div
                  id="notifList"
                  style="max-height:360px; overflow:auto;"
                >

                  <div class="p-3 text-muted small">
                    Loading...
                  </div>

                </div>


                <div class="dropdown-footer text-center p-2">

                  <a
                    href="<?php echo $base_url; ?>/users/notifications_center.php"
                    class="small"
                  >
                    View all
                  </a>

                </div>

              </div>

            </li>


            <!-- =========================================
                 USER DROPDOWN
            ========================================= -->

            <?php

              $initial = strtoupper(
                substr($_SESSION['username'] ?? 'U', 0, 1)
              );

              $avatar = !empty($_SESSION['img'])
                ? $base_url . '/users/user-images/' . $_SESSION['img']
                : '';

            ?>


            <li class="nav-item dropdown">

              <a
                class="nav-link dropdown-toggle user-chip"
                href="#"
                id="navbarDropdown"
                role="button"
                data-toggle="dropdown"
                aria-haspopup="true"
                aria-expanded="false"
              >

                <span
                  class="avatar <?php echo $avatar ? 'has-img' : ''; ?>"

                  <?php

                    if ($avatar) {

                      echo 'style="background-image:url(\'' .
                        htmlspecialchars(
                          $avatar,
                          ENT_QUOTES,
                          'UTF-8'
                        ) .
                        '\')"';

                    }

                  ?>

                  data-initial="<?php
                    echo htmlspecialchars(
                      $initial,
                      ENT_QUOTES,
                      'UTF-8'
                    );
                  ?>"
                ></span>


                <span class="name d-none d-md-inline">

                  <?php

                    echo htmlspecialchars(
                      $_SESSION['username'],
                      ENT_QUOTES,
                      'UTF-8'
                    );

                  ?>

                </span>

              </a>


              <div
                class="dropdown-menu dropdown-menu-right"
                aria-labelledby="navbarDropdown"
              >


                <a
                  class="dropdown-item"
                  href="<?php echo $base_url; ?>/users/public-profile.php?id=<?php echo $_SESSION['id']; ?>"
                >
                  Public profile
                </a>


                <a
                  class="dropdown-item"
                  href="<?php echo $base_url; ?>/users/update-profile.php?upd_id=<?php echo $_SESSION['id']; ?>"
                >
                  Update profile
                </a>


                <!-- JOB SEEKER -->

                <?php if ($_SESSION['type'] == "Job Seeker"): ?>


                  <a
                    class="dropdown-item"
                    href="<?php echo $base_url; ?>/users/recommended_jobs.php"
                  >

                    <i class="fa-solid fa-wand-magic-sparkles mr-2"></i>

                    Recommended Jobs

                  </a>


                  <a
                    class="dropdown-item"
                    href="<?php echo $base_url; ?>/users/my_availability.php?id=<?php echo $_SESSION['id']; ?>"
                  >

                    <i class="fa-regular fa-calendar-check mr-2"></i>

                    My Availability

                  </a>


                  <a
                    class="dropdown-item"
                    href="<?php echo $base_url; ?>/users/saved_jobs.php?id=<?php echo $_SESSION['id']; ?>"
                  >

                    <i class="fa-regular fa-bookmark mr-2"></i>

                    Saved Jobs

                  </a>


                  <a
                    class="dropdown-item"
                    href="<?php echo $base_url; ?>/users/applied_jobs.php?id=<?php echo $_SESSION['id']; ?>"
                  >

                    <i class="fa-solid fa-file-circle-check mr-2"></i>

                    Applied Jobs

                  </a>


                <?php endif; ?>


                <!-- EMPLOYER -->

                <?php if ($_SESSION['type'] == "Employer"): ?>

                  <a
                    class="dropdown-item"
                    href="<?php echo $base_url; ?>/users/employer_dashboard.php"
                  >
                    Employer Dashboard
                  </a>

                <?php endif; ?>


                <a
                  class="dropdown-item"
                  href="<?php echo $base_url; ?>/users/change_password.php"
                >
                  Change Password
                </a>


                <div class="dropdown-divider"></div>


                <a
                  class="dropdown-item"
                  href="<?php echo $base_url; ?>/auth/logout.php"
                >
                  Logout
                </a>


              </div>

            </li>


          <?php else: ?>


            <!-- LOGIN / REGISTER -->

            <li class="nav-item">

              <a
                href="<?php echo $base_url; ?>/auth/loginRegister.php"
                class="nav-link"
              >
                Log In/Register
              </a>

            </li>


          <?php endif; ?>


        </ul>

      </div>

    </div>

  </nav>


</div>


<!-- =========================================
     AVAILABILITY REMINDER
========================================= -->

<?php

if (
  !empty($_SESSION['id']) &&
  ($_SESSION['type'] ?? '') === 'Job Seeker'
) {

  (function () use ($conn, $base_url) {

    $uid = (int) ($_SESSION['id'] ?? 0);

    $isAvailPage = (
      strpos(
        $_SERVER['SCRIPT_NAME'] ?? '',
        'my_availability.php'
      ) !== false
    );


    if (
      empty($_SESSION['saw_avail_toast']) &&
      !$isAvailPage
    ) {

      $avStmt = $conn->prepare("
        SELECT
          monday,
          tuesday,
          wednesday,
          thursday,
          friday,
          saturday,
          sunday
        FROM availability
        WHERE user_id = :uid
        LIMIT 1
      ");


      $avStmt->execute([
        ':uid' => $uid
      ]);


      $avRow = $avStmt->fetch(PDO::FETCH_ASSOC);

      $needsReminder = false;


      if (!$avRow) {

        $needsReminder = true;

      } else {

        $days = [
          'monday',
          'tuesday',
          'wednesday',
          'thursday',
          'friday',
          'saturday',
          'sunday'
        ];


        $anySet = false;


        foreach ($days as $day) {

          if (
            !empty($avRow[$day]) &&
            strtolower((string) $avRow[$day]) !== 'none'
          ) {

            $anySet = true;

            break;

          }

        }


        $needsReminder = !$anySet;

      }


      if ($needsReminder) {

        $_SESSION['saw_avail_toast'] = 1;


        $availUrl =
          rtrim($base_url, '/') .
          '/users/my_availability.php?id=' .
          $uid;

        ?>


        <!-- AVAILABILITY TOAST -->

        <div
          aria-live="polite"
          aria-atomic="true"
          style="position:fixed; right:1rem; bottom:1rem; z-index:1080;"
        >

          <div
            class="toast shadow avail-reminder-toast"
            role="alert"
            aria-live="assertive"
            aria-atomic="true"
            data-autohide="false"
            style="min-width:320px;"
          >

            <div class="toast-header">

              <i class="fa-regular fa-calendar-check mr-2 text-primary"></i>

              <strong class="mr-auto">
                Reminder
              </strong>

              <small class="text-muted">
                Just now
              </small>

              <button
                type="button"
                class="ml-2 mb-1 close"
                data-dismiss="toast"
                aria-label="Close"
              >

                <span aria-hidden="true">
                  &times;
                </span>

              </button>

            </div>


            <div class="toast-body">

              <div class="mb-2">

                <strong>
                  Availability information missing.
                </strong>

              </div>


              <div
                class="text-muted"
                style="line-height:1.4;"
              >

                Please add your weekly availability so employers
                know when you're available for interviews and shifts.

              </div>


              <a
                class="btn btn-sm btn-primary mt-2"
                href="<?php
                  echo htmlspecialchars(
                    $availUrl,
                    ENT_QUOTES,
                    'UTF-8'
                  );
                ?>"
              >
                Set availability now
              </a>

            </div>

          </div>

        </div>


        <script>

          (function (w, $) {

            var $toast =
              $('.avail-reminder-toast');


            if (
              $ &&
              $.fn &&
              $.fn.toast
            ) {

              $toast.toast('show');

            } else {

              $toast.addClass('show');

            }

          })(window, window.jQuery);

        </script>


        <?php

      }

      unset(
        $avStmt,
        $avRow,
        $needsReminder
      );

    }

  })();

}

?>


<!-- =========================================
     NOTIFICATION JAVASCRIPT
========================================= -->

<script>

(function () {

  var base =
    <?php echo json_encode($base_url); ?>;


  function refreshCount() {

    $.get(
      base + '/users/notifications_api.php',

      {
        action: 'count'
      },

      function (res) {

        if (!res || !res.ok) {
          return;
        }


        var count = res.count | 0;

        var $badge =
          $('#notifCount');


        if (count > 0) {

          $badge
            .text(count)
            .show();

        } else {

          $badge.hide();

        }

      },

      'json'
    );

  }


  function formatNotifDate(iso) {

    if (!iso) {
      return '';
    }


    var date =
      new Date(
        String(iso).replace(' ', 'T')
      );


    var now =
      new Date();


    if (
      date.toDateString() ===
      now.toDateString()
    ) {

      return 'Today';

    }


    var sameYear =
      date.getFullYear() ===
      now.getFullYear();


    return date.toLocaleDateString(
      undefined,

      sameYear

        ? {
            month: 'short',
            day: 'numeric'
          }

        : {
            month: 'short',
            day: 'numeric',
            year: 'numeric'
          }
    );

  }


  function loadList() {

    $('#notifList').html(
      '<div class="p-3 text-muted small">' +
      'Loading...' +
      '</div>'
    );


    $.get(
      base + '/users/notifications_api.php',

      {
        action: 'list',
        limit: 8
      },

      function (res) {

        if (!res || !res.ok) {

          $('#notifList').html(
            '<div class="p-3 text-danger small">' +
            'Failed to load.' +
            '</div>'
          );

          return;

        }


        var items =
          Array.isArray(res.items)
            ? res.items.slice(0, 8)
            : [];


        if (!items.length) {

          $('#notifList').html(
            '<div class="p-3 text-muted small">' +
            'No notifications.' +
            '</div>'
          );


          $('#notifCount').hide();

          return;

        }


        var html =
          '<div class="list-group list-group-flush">';


        items.forEach(
          function (notification) {

            var seen =
              Number(notification.seen) === 1;


            var safeTitle =
              $('<div>')
                .text(
                  notification.title || ''
                )
                .html();


            var safeMessage =
              $('<div>')
                .text(
                  notification.message || ''
                )
                .html();


            html +=

              '<a href="#" ' +

              'class="list-group-item ' +
              'list-group-item-action ' +
              'd-flex align-items-start' +

              (
                seen
                  ? ''
                  : ' notif-unseen'
              ) +

              '"' +

              ' data-id="' +
              notification.id +
              '"' +

              ' data-link="' +
              (
                notification.link_path || '#'
              ) +
              '">' +


              (

                seen

                  ? '<div class="mr-2">' +
                    '<i class="fa-regular fa-bell"></i>' +
                    '</div>'

                  : '<span class="notif-dot mr-2"></span>'

              ) +


              '<div class="flex-grow-1">' +

                '<div class="n-title">' +
                  safeTitle +
                '</div>' +

                '<div class="small text-muted">' +
                  safeMessage +
                '</div>' +

              '</div>' +


              '<div class="notif-date small text-muted ml-2">' +
                formatNotifDate(
                  notification.created_at
                ) +
              '</div>' +


              '</a>';

          }
        );


        html += '</div>';


        $('#notifList').html(html);

      },

      'json'
    );

  }


  $('#notifBell').on(
    'click',
    function () {
      loadList();
    }
  );


  $(document).on(
    'click',
    '#notifList .list-group-item',

    function (event) {

      event.preventDefault();


      var $row =
        $(this);


      var id =
        $row.data('id');


      var link =
        $row.data('link');


      $.post(

        base +
        '/users/notifications_api.php',

        {
          action: 'mark',
          id: id
        },

        function () {

          refreshCount();


          if (
            link &&
            link !== '#'
          ) {

            window.location = link;

          } else {

            $row.removeClass(
              'notif-unseen'
            );

          }

        },

        'json'

      );

    }
  );


  $('#notifMarkAll').on(
    'click',

    function (event) {

      event.preventDefault();


      $.post(

        base +
        '/users/notifications_api.php',

        {
          action: 'mark_all'
        },

        function () {

          refreshCount();

          loadList();

        },

        'json'

      );

    }
  );


  <?php if (isset($_SESSION['id'])): ?>

    refreshCount();

    setInterval(
      refreshCount,
      60000
    );

  <?php endif; ?>


})();

</script>