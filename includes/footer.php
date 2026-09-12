<?php
$base_url = (isset($_SERVER['HTTPS']) ? "https" : "http") . "://" . $_SERVER['HTTP_HOST'];

$project_folder = explode('/', trim($_SERVER['SCRIPT_NAME'], '/'))[0];

$base_url .= '/' . $project_folder;
?>

<footer class="site-footer modern-footer">

  <!-- FOOTER TOP -->
  <div class="footer-top">
    <div class="container">
      <div class="row align-items-center py-4">

        <div class="col-md-6 d-flex align-items-center">
          <img src="<?php echo $base_url; ?>/images/logo.png"
               alt="Online Job Portal"
               width="36"
               height="36"
               class="mr-2">

          <div>
            <div class="h5 mb-0 text-white">
              Online Job Portal
            </div>

            <small class="text-muted">
              Connecting careers. Creating futures.
            </small>
          </div>
        </div>

        <div class="col-md-6 mt-3 mt-md-0">
          <form class="form-inline justify-content-md-end">
            <label class="sr-only" for="nlEmail">
              Email
            </label>

            <input
              id="nlEmail"
              type="email"
              class="form-control mr-2 mb-2 mb-md-0"
              placeholder="Get occasional updates"
            >

            <button type="submit" class="btn btn-success">
              Subscribe
            </button>
          </form>
        </div>

      </div>
    </div>
  </div>


  <!-- FOOTER MAIN -->
  <div class="footer-main">
    <div class="container">

      <div class="row">

        <!-- JOB SEEKERS -->
        <div class="col-6 col-md-3 mb-4">

          <h5 class="ft-head">
            For Job Seekers
          </h5>

          <ul class="list-unstyled ft-links">

            <li>
              <a href="<?php echo APPURL; ?>/loginRegister.php">
                Register
              </a>
            </li>

            <li>
              <a href="<?php echo APPURL; ?>/findjobs.php">
                Search Jobs
              </a>
            </li>

            <li>
              <a href="<?php echo APPURL; ?>/loginRegister.php">
                Login
              </a>
            </li>

            <!-- FAQ LINK GOES TO FOOTER FAQ -->
            <li>
              <a href="#faq-footer">
                FAQs
              </a>
            </li>

          </ul>

        </div>


        <!-- EMPLOYERS -->
        <div class="col-6 col-md-3 mb-4">

          <h5 class="ft-head">
            For Employers
          </h5>

          <ul class="list-unstyled ft-links">

            <li>
              <a href="<?php echo APPURL; ?>/users/employer_dashboard.php">
                Employer Dashboard
              </a>
            </li>

            <li>
              <a href="<?php echo APPURL; ?>/jobs/post-job.php">
                Post a Job
              </a>
            </li>

            <li>
              <a href="<?php echo APPURL; ?>/loginRegister.php">
                Login
              </a>
            </li>

            <!-- FAQ LINK GOES TO FOOTER FAQ -->
            <li>
              <a href="#faq-footer">
                FAQs
              </a>
            </li>

          </ul>

        </div>


        <!-- ABOUT US -->
        <div class="col-6 col-md-3 mb-4" id="about-footer">

          <h5 class="ft-head">
            About Us
          </h5>

          <p class="footer-description">
            Online Job Portal is a platform that connects
            job seekers with employers and helps users
            find suitable employment opportunities.
          </p>

          <ul class="list-unstyled ft-links">

            <li>
              <a href="#about-footer">
                About Us
              </a>
            </li>

          </ul>

        </div>


        <!-- CONTACT -->
        <div class="col-6 col-md-3 mb-4" id="contact-footer">

          <h5 class="ft-head">
            Contact Us
          </h5>

          <div class="footer-social mb-3">

            <a href="#" aria-label="Facebook">
              <span class="icon-facebook"></span>
            </a>

            <a href="#" aria-label="Twitter">
              <span class="icon-twitter"></span>
            </a>

            <a href="#" aria-label="Instagram">
              <span class="icon-instagram"></span>
            </a>

            <a href="#" aria-label="LinkedIn">
              <span class="icon-linkedin"></span>
            </a>

          </div>

          <div class="text-muted small">

            Hetauda, Nepal<br>

            <a href="mailto:onlinejobportal@gmail.com">
              onlinejobportal@gmail.com
            </a><br>

            <a href="tel:+9779804225576">
              +977 9804225576
            </a>

          </div>

        </div>

      </div>


      <!-- FAQ SECTION -->
      <div class="row mt-3">

        <div class="col-md-8" id="faq-footer">

          <h5 class="ft-head">
            Frequently Asked Questions
          </h5>

          <div class="faq-item">

            <strong>
              How can I apply for a job?
            </strong>

            <p>
              Search for a suitable job and click the
              Apply button to submit your application.
            </p>

          </div>


          <div class="faq-item">

            <strong>
              Can employers post jobs?
            </strong>

            <p>
              Yes. Registered employers can post job
              vacancies through the employer dashboard.
            </p>

          </div>


          <div class="faq-item">

            <strong>
              How can I register?
            </strong>

            <p>
              Click the Register option in the navigation
              menu and create your account.
            </p>

          </div>

        </div>

      </div>


      <hr class="ft-hr">


      <!-- FOOTER BOTTOM -->
      <div class="d-flex flex-column flex-md-row
                  justify-content-between
                  align-items-center pb-3">

        <div class="text-muted small order-2 order-md-1">
          Online Job Portal
        </div>

        <ul class="list-inline mb-2 mb-md-0 order-1 order-md-2">

          <li class="list-inline-item">
            <a href="<?php echo APPURL; ?>/terms.php">
              Terms
            </a>
          </li>

          <li class="list-inline-item">
            <a href="<?php echo APPURL; ?>/privacy.php">
              Privacy
            </a>
          </li>

          <!-- CONTACT NOW SCROLLS TO FOOTER CONTACT -->
          <li class="list-inline-item">
            <a href="#contact-footer">
              Contact
            </a>
          </li>

        </ul>

      </div>

    </div>
  </div>

</footer>


<style>

/* FOOTER */
.modern-footer {
  color: #cbd5e1;
  background: #0b1220;
  position: relative;
  z-index: 1;
}


/* SMOOTH SCROLL */
html {
  scroll-behavior: smooth;
}


/* Prevent fixed navbar from covering section */
#about-footer,
#contact-footer,
#faq-footer {
  scroll-margin-top: 90px;
}


/* TOP BAR */
.footer-top {
  background:
    radial-gradient(
      600px 180px at 0% 0%,
      rgba(99,102,241,.18),
      transparent 60%
    ),
    radial-gradient(
      600px 180px at 100% 0%,
      rgba(34,211,238,.15),
      transparent 60%
    ),
    #0b1220;

  border-bottom: 1px solid rgba(255,255,255,.06);
}


/* NEWSLETTER */
.footer-top .form-control {
  background: #0f172a;
  border: 1px solid rgba(255,255,255,.12);
  color: #e5e7eb;
  border-radius: 999px;
}


/* MAIN FOOTER */
.footer-main {
  padding: 28px 0 8px;
}


/* HEADINGS */
.ft-head {
  color: #fff;
  font-weight: 700;
  margin-bottom: 12px;
}


/* LINKS */
.ft-links li {
  margin-bottom: .35rem;
}

.ft-links a {
  color: #cbd5e1;
  text-decoration: none;
  position: relative;
  display: inline-block;
}

.ft-links a:hover {
  color: #fff;
}


/* LINK ANIMATION */
.ft-links a::after {
  content: "";
  position: absolute;
  left: 0;
  bottom: -3px;
  width: 100%;
  height: 2px;

  background:
    linear-gradient(
      90deg,
      #6366f1,
      #22d3ee
    );

  transform: scaleX(0);
  transform-origin: left;
  transition: transform .2s ease;
}

.ft-links a:hover::after {
  transform: scaleX(1);
}


/* ABOUT DESCRIPTION */
.footer-description {
  color: #94a3b8;
  font-size: 14px;
  line-height: 1.7;
}


/* SOCIAL ICONS */
.footer-social a {
  display: inline-flex;
  align-items: center;
  justify-content: center;

  width: 36px;
  height: 36px;

  margin-right: 6px;
  border-radius: 8px;

  background: #0f172a;
  color: #e5e7eb;

  border: 1px solid rgba(255,255,255,.08);
}

.footer-social a:hover {
  background: #111827;
  color: #fff;
}


/* FAQ */
.faq-item {
  margin-bottom: 18px;
}

.faq-item strong {
  color: #fff;
  display: block;
  margin-bottom: 5px;
}

.faq-item p {
  color: #94a3b8;
  font-size: 14px;
  line-height: 1.6;
  margin-bottom: 0;
}


/* HORIZONTAL LINE */
.ft-hr {
  border-color: rgba(255,255,255,.06);
}

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