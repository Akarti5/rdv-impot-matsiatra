<?php $user = currentUser(); ?>
<section class="dashboard">
  <h2>Bonjour, Agent <?= s($user['prenom']) ?> 👮</h2>

  <div class="grid-2">
    <div class="card">
      <h3>Rendez-vous du jour</h3>
      <div class="table-responsive">
        <table class="table" id="agent-today-table">
          <thead>
            <tr>
              <th>Heure</th>
              <th>Client</th>
              <th>Motif</th>
              <th>Statut</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody></tbody>
        </table>
      </div>
    </div>
    <div class="card">
      <h3>Statistiques (7 jours)</h3>
      <canvas id="agent-chart" height="200"></canvas>
    </div>
  </div>

  <div class="grid-2">
    <div class="card">
      <h3>Gérer mes créneaux</h3>
      <form id="slot-form" class="form">
        <input type="hidden" name="_csrf" value="<?= s(csrf_token()) ?>">
        <div class="grid-3">
          <div class="form-group"><label for="slot-date">Date</label><input id="slot-date" name="date" type="date" required></div>
          <div class="form-group"><label for="slot-start">Début</label><input id="slot-start" name="heure_debut" type="time" required></div>
          <div class="form-group"><label for="slot-end">Fin</label><input id="slot-end" name="heure_fin" type="time" required></div>
        </div>
        <div class="form-group"><label for="slot-max">Max RDV</label><input id="slot-max" name="max_appointments" type="number" min="1" max="20" value="1"></div>
        <button class="btn">Ajouter le créneau</button>
        <div id="slot-message" class="form-message"></div>
      </form>
      <div class="table-responsive">
        <table class="table" id="agent-slots-table">
          <thead>
            <tr><th>Date</th><th>Début</th><th>Fin</th><th>Max</th><th>Dispo</th><th>Actions</th></tr>
          </thead>
          <tbody></tbody>
        </table>
      </div>
    </div>

    <div class="card">
      <h3>Rendez-vous à venir</h3>
      <div class="table-responsive">
        <table class="table" id="agent-upcoming-table">
          <thead>
            <tr>
              <th>Date</th>
              <th>Heure</th>
              <th>Client</th>
              <th>Motif</th>
              <th>Statut</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody></tbody>
        </table>
      </div>
    </div>
  </div>
</section>

<script>
document.addEventListener('DOMContentLoaded', () => {
  loadAgentToday();
  loadAgentUpcoming();
  loadAgentSlots();
  renderAgentChart();

  document.getElementById('slot-form').addEventListener('submit', async (e) => {
    e.preventDefault();
    const fd = new FormData(e.currentTarget);
    const res = await apiPost('../crud/time_slots/create.php', fd);
    showMessage('slot-message', res);
    if (res.ok) {
      e.currentTarget.reset();
      loadAgentSlots();
    }
  });
});

async function loadAgentToday() {
  const tbody = document.querySelector('#agent-today-table tbody');
  tbody.innerHTML = '<tr><td colspan="5">Chargement...</td></tr>';
  const res = await fetch(`${BASE_URL}api/appointments.php?action=read&scope=agent&range=today`);
  const data = await res.json();
  renderAgentApptsTable(tbody, data);
}

async function loadAgentUpcoming() {
  const tbody = document.querySelector('#agent-upcoming-table tbody');
  tbody.innerHTML = '<tr><td colspan="6">Chargement...</td></tr>';
  const res = await fetch(`${BASE_URL}api/appointments.php?action=read&scope=agent&range=upcoming`);
  const data = await res.json();
  renderAgentApptsTable(tbody, data, true);
}

function renderAgentApptsTable(tbody, data, showDate = false) {
  tbody.innerHTML = '';
  if (data.ok && data.appointments.length) {
    for (const a of data.appointments) {
      const tr = document.createElement('tr');
      tr.innerHTML = `
        ${showDate ? `<td>${a.date_rdv}</td>` : ''}
        <td>${a.heure_rdv}</td>
        <td>${a.client_name ?? ''}</td>
        <td>${a.motif}</td>
        <td><span class="badge status-${a.status}">${a.status}</span></td>
        <td>
          <div class="btn-group">
            <button class="btn btn-sm" data-status="confirmed" data-id="${a.id}">Confirmer</button>
            <button class="btn btn-sm" data-status="completed" data-id="${a.id}">Terminer</button>
            <button class="btn btn-sm btn-outline" data-status="cancelled" data-id="${a.id}">Annuler</button>
          </div>
        </td>
      `;
      tbody.appendChild(tr);
    }
    tbody.querySelectorAll('[data-status]').forEach(btn => {
      btn.addEventListener('click', async (e) => {
        const id = e.currentTarget.getAttribute('data-id');
        const status = e.currentTarget.getAttribute('data-status');
        const fd = new FormData();
        fd.append('id', id);
        fd.append('status', status);
        fd.append('_csrf', getCsrf());
        const res = await apiPost('appointments.php?action=update-status', fd);
        if (res.ok) {
          loadAgentToday();
          loadAgentUpcoming();
        } else {
          alert(res.message);
        }
      });
    });
  } else {
    const cols = showDate ? 6 : 5;
    tbody.innerHTML = `<tr><td colspan="${cols}">Aucun rendez-vous.</td></tr>`;
  }
}

async function loadAgentSlots() {
  const tbody = document.querySelector('#agent-slots-table tbody');
  tbody.innerHTML = '<tr><td colspan="6">Chargement...</td></tr>';
  const res = await fetch(`${BASE_URL}crud/time_slots/read.php?mine=1&future=1`);
  const data = await res.json();
  tbody.innerHTML = '';
  if (data.ok && data.slots.length) {
    for (const s of data.slots) {
      const tr = document.createElement('tr');
      tr.innerHTML = `
        <td>${s.date}</td>
        <td>${s.heure_debut}</td>
        <td>${s.heure_fin}</td>
        <td>${s.max_appointments}</td>
        <td>${s.is_available ? 'Oui' : 'Non'}</td>
        <td>
          <button class="btn btn-sm btn-outline" data-toggle="${s.id}">${s.is_available ? 'Bloquer' : 'Débloquer'}</button>
          <button class="btn btn-sm btn-outline danger" data-delete="${s.id}">Supprimer</button>
        </td>
      `;
      tbody.appendChild(tr);
    }
    tbody.querySelectorAll('[data-toggle]').forEach(btn => {
      btn.addEventListener('click', async (e) => {
        const id = e.currentTarget.getAttribute('data-toggle');
        const fd = new FormData();
        fd.append('id', id);
        fd.append('toggle', '1');
        fd.append('_csrf', getCsrf());
        const res = await apiPost('../crud/time_slots/update.php', fd);
        if (res.ok) loadAgentSlots();
      });
    });
    tbody.querySelectorAll('[data-delete]').forEach(btn => {
      btn.addEventListener('click', async (e) => {
        if (!confirm('Supprimer ce créneau ?')) return;
        const id = e.currentTarget.getAttribute('data-delete');
        const fd = new FormData();
        fd.append('id', id);
        fd.append('_csrf', getCsrf());
        const res = await apiPost('../crud/time_slots/delete.php', fd);
        if (res.ok) loadAgentSlots();
      });
    });
  } else {
    tbody.innerHTML = '<tr><td colspan="6">Aucun créneau.</td></tr>';
  }
}

async function renderAgentChart() {
  const ctx = document.getElementById('agent-chart').getContext('2d');
  const res = await fetch(`${BASE_URL}api/appointments.php?action=stats-7d`);
  const data = await res.json();
  if (!data.ok) return;
  const labels = data.labels;
  const series = data.series;
  new Chart(ctx, {
    type: 'line',
    data: {
      labels,
      datasets: [{
        label: 'RDV',
        data: series,
        borderColor: '#2196F3',
        backgroundColor: 'rgba(33,150,243,0.15)',
        tension: 0.25,
        fill: true,
      }]
    },
    options: {
      plugins: { legend: { display: false }},
      scales: {
        y: { beginAtZero: true, ticks: { precision: 0 } }
      }
    }
  });
}
</script>
