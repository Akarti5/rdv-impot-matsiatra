<?php $year = date('Y'); ?>
</main>
<footer class="app-footer">
  <div class="container footer-inner">
    <div class="footer-brand">
      <span class="logo">RIM</span>
      <span>RDV Impôts Matsiatra</span>
    </div>
    <div class="footer-links">
      <a href="#">Contact</a>
      <a href="#">FAQ</a>
      <a href="#">Mentions légales</a>
    </div>
    <div class="footer-copy">© <?= s($year) ?> - Tous droits réservés</div>
  </div>
</footer>
<script>
  const BASE_URL = "<?= s(BASE_URL) ?>";
</script>
<script src="<?= s(BASE_URL) ?>assets/js/script.js" defer></script>
</body>
</html>
