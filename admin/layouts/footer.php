      </div> <!-- /.container-fluid -->
      <footer class="app-footer">
        <div>© <?php echo date('Y'); ?> Job Portal Admin</div>
        <div class="text-muted small">Build <?php echo htmlspecialchars(basename($_SERVER['SCRIPT_NAME'])); ?></div>
      </footer>
    </main>
  </div> <!-- /.app-shell -->
<!-- jQuery 3.x -->
<script src="https://code.jquery.com/jquery-3.5.1.min.js" crossorigin="anonymous"></script>

<!-- Bootstrap 4.6 bundle (includes Popper) -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/4.6.2/js/bootstrap.bundle.min.js" crossorigin="anonymous"></script>

<!-- Your admin script (sidebar toggle etc.) -->
<script src="<?= ADMINURL ?>/assets/js/admin.js?v=2.0.0"></script>

  <script>
(function(){
  var sidebar = document.getElementById('sidebar');
  var toggle  = document.getElementById('sidebarToggle');
  if(toggle && sidebar){
    toggle.addEventListener('click', function(){
      sidebar.classList.toggle('open');
    });
  }
  document.addEventListener('click', function(e){
    if(!sidebar) return;
    if(window.innerWidth >= 992) return;
    if(!sidebar.contains(e.target) && (!toggle || !toggle.contains(e.target))){
      sidebar.classList.remove('open');
    }
  });
})();
</script>

<script>
  // Optional: ensure data-API is bound
  $(function(){ $('.dropdown-toggle').dropdown(); });
</script>


</body>
</html>
