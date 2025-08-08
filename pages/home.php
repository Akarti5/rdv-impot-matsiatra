<?php $user = currentUser(); ?>
<section class="hero">
  <div class="hero-content">
    <h1 class="title">Prenez votre rendez-vous fiscal simplement</h1>
    <p class="subtitle">Réservez un créneau avec un agent des impôts de la région Matsiatra en quelques clics. Rapide, sécurisé, et efficace.</p>
    <div class="actions">
      <?php if (!$user): ?>
        <a class="btn btn-lg" href="<?= s(BASE_URL) ?>?page=login">Se connecter</a>
        <a class="btn btn-outline btn-lg" href="<?= s(BASE_URL) ?>?page=register">Créer un compte</a>
      <?php else: ?>
        <?php if ($user['type_user'] === 'client'): ?>
          <a class="btn btn-lg" href="<?= s(BASE_URL) ?>?page=dashboard-client">Mon espace client</a>
        <?php else: ?>
          <a class="btn btn-lg" href="<?= s(BASE_URL) ?>?page=dashboard-agent">Espace agent</a>
        <?php endif; ?>
      <?php endif; ?>
    </div>
  </div>
  <div class="hero-visual">
    <div class="card stat-card">
      <div class="stat-number" id="stat-appointments">0</div>
      <div class="stat-label">RDV aujourd'hui</div>
    </div>
    <div class="card stat-card">
      <div class="stat-number" id="stat-agents">0</div>
      <div class="stat-label">Agents disponibles</div>
    </div>
  </div>
</section>

<section class="features">
  <div class="feature-card">
    <h3>Calendrier interactif</h3>
    <p>Consultez les créneaux disponibles et réservez facilement selon vos disponibilités.</p>
  </div>
  <div class="feature-card">
    <h3>Notifications</h3>
    <p>Recevez des rappels automatiques pour ne manquer aucun rendez-vous.</p>
  </div>
  <div class="feature-card">
    <h3>Sécurité</h3>
    <p>Vos données sont protégées grâce au hachage des mots de passe et aux sessions sécurisées.</p>
  </div>
</section>

<script>
document.addEventListener('DOMContentLoaded', async () => {
  try {
    const res = await fetch(`${BASE_URL}api/appointments.php?action=stats-today`);
    const data = await res.json();
    if (data.ok) {
      document.getElementById('stat-appointments').textContent = data.stats.appointments_today ?? 0;
      document.getElementById('stat-agents').textContent = data.stats.agents ?? 0;
    }
  } catch(e) {}
});
</script>
