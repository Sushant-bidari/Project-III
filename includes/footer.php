<?php
$base_url = (isset($_SERVER['HTTPS']) ? "https" : "http") . "://" . $_SERVER['HTTP_HOST'];

$project_folder = explode('/', trim($_SERVER['SCRIPT_NAME'], '/'))[0];

$base_url .= '/' . $project_folder;
?>
<footer class="site-footer modern-footer">
  <!-- <a href="#top" class="smoothscroll scroll-top" aria-label="Back to top">
    <span class="icon-keyboard_arrow_up"></span>
  </a> -->

  <div class="footer-top">
    <div class="container">
      <div class="row align-items-center py-4">
        <div class="col-md-6 d-flex align-items-center">
          <img src="<?php echo $base_url; ?>/images/logo.png" alt="" width="36" height="36" class="mr-2">
          <div>
            <div class="h5 mb-0 text-white">Online Job Portal</div>
            <small class="text-muted">Connecting careers. Creating futures.</small>
          </div>
        </div>
        <div class="col-md-6 mt-3 mt-md-0">
          <form class="form-inline justify-content-md-end">
            <label class="sr-only" for="nlEmail">Email</label>
            <input id="nlEmail" type="email" class="form-control mr-2 mb-2 mb-md-0" placeholder="Get occasional updates">
            <button class="btn btn-success">Subscribe</button>
          </form>
        </div>
      </div>
    </div>
  </div>

  <div class="footer-main">
    <div class="container">
      <div class="row">
        <div class="col-6 col-md-3 mb-4">
          <h5 class="ft-head">For Job Seekers</h5>
          <ul class="list-unstyled ft-links">
            <li><a href="<?php echo APPURL; ?>/loginRegister.php">Register</a></li>
            <li><a href="<?php echo APPURL; ?>/findjobs.php">Search Jobs</a></li>
            <li><a href="<?php echo APPURL; ?>/loginRegister.php">Login</a></li>
            <li><a href="<?php echo APPURL; ?>/faqs.php">FAQs</a></li>
          </ul>
        </div>
        <div class="col-6 col-md-3 mb-4">
          <h5 class="ft-head">For Employers</h5>
          <ul class="list-unstyled ft-links">
            <li><a href="<?php echo APPURL; ?>/users/employer_dashboard.php">Employer Dashboard</a></li>
            <li><a href="<?php echo APPURL; ?>/jobs/post-job.php">Post a Job</a></li>
            <li><a href="<?php echo APPURL; ?>/loginRegister.php">Login</a></li>
            <li><a href="<?php echo APPURL; ?>/faqs.php">FAQs</a></li>
          </ul>
        </div>
        <div class="col-6 col-md-3 mb-4">
          <h5 class="ft-head">Company</h5>
          <ul class="list-unstyled ft-links">
            <li><a href="<?php echo APPURL; ?>/about.php">About Us</a></li>
            <li><a href="<?php echo APPURL; ?>/careers.php">Careers</a></li>
            <li><a href="<?php echo APPURL; ?>/blog.php">Blog</a></li>
            <li><a href="<?php echo APPURL; ?>/resources.php">Resources</a></li>
          </ul>
        </div>
        <div class="col-6 col-md-3 mb-4">
          <h5 class="ft-head">Contact</h5>
          <div class="footer-social mb-2">
            <a href="#"><span class="icon-facebook"></span></a>
            <a href="#"><span class="icon-twitter"></span></a>
            <a href="#"><span class="icon-instagram"></span></a>
            <a href="#"><span class="icon-linkedin"></span></a>
          </div>
          <div class="text-muted small">
            77 Test Street, XYZ<br>
            <a href="mailto:hello@example.com">hello@example.com</a><br>
            <a href="https://codeastro.com" target="_blank" rel="noopener">codeastro.com</a>
          </div>
        </div>
      </div>
      <hr class="ft-hr">
      <div class="d-flex flex-column flex-md-row justify-content-between align-items-center pb-3">
        <div class="text-muted small order-2 order-md-1">
          © <?php echo date('Y'); ?> Online Job Portal. Developed by <a href="https://codeastro.com" target="_blank" rel="noopener">CodeAstro</a>.
          <br>
          Licensed for educational use only.
        </div>
        <ul class="list-inline mb-2 mb-md-0 order-1 order-md-2">
          <li class="list-inline-item"><a href="<?php echo APPURL; ?>/terms.php">Terms</a></li>
          <li class="list-inline-item"><a href="<?php echo APPURL; ?>/privacy.php">Privacy</a></li>
          <li class="list-inline-item"><a href="<?php echo APPURL; ?>/contact.php">Contact</a></li>
        </ul>
      </div>
    </div>
  </div>
</footer>

<style>
.modern-footer{
  color:#cbd5e1; background:#0b1220; position:relative; z-index:1;
}
.modern-footer .scroll-top{
  position:fixed; right:14px; bottom:14px; z-index:1080;
  width:42px; height:42px; border-radius:999px; display:flex; align-items:center; justify-content:center;
  background:#111827; color:#fff; border:1px solid rgba(255,255,255,.08);
  box-shadow:0 8px 16px rgba(0,0,0,.25);
}
.modern-footer .scroll-top:hover{ background:#0f172a; color:#fff; }

/* Top bar */
.footer-top{
  background:
    radial-gradient(600px 180px at 0% 0%, rgba(99,102,241,.18), transparent 60%),
    radial-gradient(600px 180px at 100% 0%, rgba(34,211,238,.15), transparent 60%),
    #0b1220;
  border-bottom:1px solid rgba(255,255,255,.06);
}
.btn-gradient{
  background: linear-gradient(135deg,#6366f1,#22d3ee);
  color:#0b1220; font-weight:700; border:0; border-radius:999px; padding:.45rem 1rem;
}
.btn-gradient:hover{ filter:brightness(1.05); color:#0b1220; }
.footer-top .form-control{
  background:#0f172a; border:1px solid rgba(255,255,255,.12); color:#e5e7eb; border-radius:999px;
}

/* Main columns */
.footer-main{ padding: 28px 0 8px; }
.ft-head{ color:#fff; font-weight:700; margin-bottom:12px; }
.ft-links li{ margin-bottom:.35rem; }
.ft-links a{
  color:#cbd5e1; text-decoration:none; position:relative; display:inline-block;
}
.ft-links a:hover{ color:#fff; }
.ft-links a::after{
  content:""; position:absolute; left:0; bottom:-3px; width:100%; height:2px;
  background:linear-gradient(90deg,#6366f1,#22d3ee); transform:scaleX(0); transform-origin:left; transition:transform .2s ease;
}
.ft-links a:hover::after{ transform:scaleX(1); }

.footer-social a{
  display:inline-flex; align-items:center; justify-content:center;
  width:36px; height:36px; margin-right:6px; border-radius:8px;
  background:#0f172a; color:#e5e7eb; border:1px solid rgba(255,255,255,.08);
}
.footer-social a:hover{ background:#111827; color:#fff; }

.ft-hr{ border-color: rgba(255,255,255,.06); }
</style>


</div>

<!-- SCRIPTS -->
<script src="<?php echo $base_url; ?>/js/jquery.min.js"></script>
<script src="<?php echo $base_url; ?>/js/bootstrap.bundle.min.js"></script>
<script src="<?php echo $base_url; ?>/js/isotope.pkgd.min.js"></script>
<script src="<?php echo $base_url; ?>/js/stickyfill.min.js"></script>
<script src="<?php echo $base_url; ?>/js/jquery.fancybox.min.js"></script>
<script src="<?php echo $base_url; ?>/js/jquery.easing.1.3.js"></script>
<script src="<?php echo $base_url; ?>/js/jquery.waypoints.min.js"></script>
<script src="<?php echo $base_url; ?>/js/jquery.animateNumber.min.js"></script>
<script src="<?php echo $base_url; ?>/js/owl.carousel.min.js"></script>
<script src="<?php echo $base_url; ?>/js/quill.min.js"></script>
<script src="<?php echo $base_url; ?>/js/bootstrap-select.min.js"></script>
<script src="<?php echo $base_url; ?>/js/custom.js"></script>


</body>
</html>