<?php $user = currentUser(); ?>
<section class="dashboard">
  <h2>Bienvenue, <?= s($user['prenom']) ?> 👋</h2>

  <div class="grid-2">
    <div class="card">
      <h3>Prendre un rendez-vous</h3>
      <form id="appointment-form" class="form">
        <input type="hidden" name="_csrf" value="<?= s(csrf_token()) ?>">
        <div class="form-group">
          <label for="appt-type">Motif</label>
          <select id="appt-type" name="motif" required></select>
        </div>
        <div class="grid-2">
          <div class="form-group">
            <label for="appt-date">Date</label>
            <input id="appt-date" name="date_rdv" type="date" required min="<?= s(date('Y-m-d')) ?>">
          </div>
          <div class="form-group">
            <label for="appt-agent">Agent</label>
            <select id="appt-agent" name="agent_id" required></select>
          </div>
        </div>
        <div class="form-group">
          <label for="appt-slot">Créneau disponible</label>
          <select id="appt-slot" name="heure_rdv" required>
            <option value="">Choisissez une date et un agent</option>
          </select>
        </div>
        <div class="form-group">
          <label for="appt-notes">Notes</label>
          <textarea id="appt-notes" name="notes_client" rows="3" placeholder="Informations complémentaires (facultatif)"></textarea>
        </div>
        <button class="btn">Confirmer le rendez-vous</button>
        <div id="appointment-message" class="form-message" aria-live="polite"></div>
      </form>
    </div>

    <div class="card">
      <h3>Mes rendez-vous</h3>
      <div class="table-responsive">
        <table class="table" id="client-appointments-table" aria-describedby="Mes rendez-vous">
          <thead>
            <tr>
              <th>Date</th>
              <th>Heure</th>
              <th>Agent</th>
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
  loadAppointmentTypes();
  loadAgents();
  loadClientAppointments();

  const dateEl = document.getElementById('appt-date');
  const agentEl = document.getElementById('appt-agent');
  dateEl.addEventListener('change', loadSlots);
  agentEl.addEventListener('change', loadSlots);

  document.getElementById('appointment-form').addEventListener('submit', async (e) => {
    e.preventDefault();
    const form = e.currentTarget;
    const fd = new FormData(form);
    console.log('Submitting appointment form...');
    const res = await apiPost('appointments.php?action=create', fd);
    console.log('Appointment creation response:', res);
    showMessage('appointment-message', res);
    if (res.ok) {
      form.reset();
      console.log('Appointment created successfully, refreshing list...');
      loadClientAppointments();
    }
  });
});

async function loadAppointmentTypes() {
  const sel = document.getElementById('appt-type');
  try {
    const res = await fetch(`${BASE_URL}api/appointment_types.php`);
    const data = await res.json();
    sel.innerHTML = '';
    if (data.ok && data.types && data.types.length > 0) {
      for (const t of data.types) {
        const opt = document.createElement('option');
        opt.value = t.nom_motif;
        opt.textContent = `${t.nom_motif} (${t.duree_estimee} min)`;
        sel.appendChild(opt);
      }
    } else {
      sel.innerHTML = '<option value="">Aucun motif disponible</option>';
    }
  } catch (error) {
    console.error('Error loading appointment types:', error);
    sel.innerHTML = '<option value="">Erreur de chargement</option>';
  }
}

async function loadAgents() {
  const sel = document.getElementById('appt-agent');
  try {
    const res = await fetch(`${BASE_URL}api/users.php?action=list-agents`);
    const data = await res.json();
    sel.innerHTML = '';
    if (data.ok && data.users && data.users.length > 0) {
      for (const a of data.users) {
        const opt = document.createElement('option');
        opt.value = a.id;
        opt.textContent = `${a.prenom} ${a.nom}`;
        sel.appendChild(opt);
      }
    } else {
      sel.innerHTML = '<option value="">Aucun agent disponible</option>';
    }
  } catch (error) {
    console.error('Error loading agents:', error);
    sel.innerHTML = '<option value="">Erreur de chargement</option>';
  }
}

async function loadSlots() {
  const date = document.getElementById('appt-date').value;
  const agentId = document.getElementById('appt-agent').value;
  const sel = document.getElementById('appt-slot');
  sel.innerHTML = '';
  if (!date || !agentId) {
    sel.innerHTML = '<option value="">Choisissez une date et un agent</option>';
    return;
  }
  const res = await fetch(`${BASE_URL}crud/time_slots/read.php?date=${encodeURIComponent(date)}&agent_id=${encodeURIComponent(agentId)}&available=1`);
  const data = await res.json();
  if (data.ok && data.slots.length) {
    for (const s of data.slots) {
      const opt = document.createElement('option');
      opt.value = s.heure_debut;
      opt.textContent = `${s.heure_debut} - ${s.heure_fin}`;
      sel.appendChild(opt);
    }
  } else {
    sel.innerHTML = '<option value="">Aucun créneau disponible</option>';
  }
}

async function loadClientAppointments() {
  const tableBody = document.querySelector('#client-appointments-table tbody');
  tableBody.innerHTML = '<tr><td colspan="6">Chargement...</td></tr>';
  console.log('Loading client appointments...');
  const res = await fetch(`${BASE_URL}api/appointments.php?action=read&scope=mine`);
  const data = await res.json();
  console.log('Appointments data:', data);
  tableBody.innerHTML = '';
  if (data.ok && data.appointments.length) {
    console.log('Found', data.appointments.length, 'appointments');
    for (const a of data.appointments) {
      const tr = document.createElement('tr');
      tr.innerHTML = `
        <td>${a.date_rdv}</td>
        <td>${a.heure_rdv}</td>
        <td>${a.agent_name ?? ''}</td>
        <td>${a.motif}</td>
        <td><span class="badge status-${a.status}">${a.status}</span></td>
        <td>
          ${a.cancellable ? `<button class="btn btn-sm btn-outline" data-cancel="${a.id}">Annuler</button>` : ''}
        </td>
      `;
      tableBody.appendChild(tr);
    }
    tableBody.querySelectorAll('[data-cancel]').forEach(btn => {
      btn.addEventListener('click', async (e) => {
        const id = e.currentTarget.getAttribute('data-cancel');
        if (!confirm('Confirmer l’annulation du rendez-vous ?')) return;
        const fd = new FormData();
        fd.append('id', id);
        fd.append('_csrf', getCsrf());
        const res = await apiPost('appointments.php?action=cancel', fd);
        alert(res.message);
        loadClientAppointments();
      });
    });
  } else {
    tableBody.innerHTML = '<tr><td colspan="6">Aucun rendez-vous.</td></tr>';
  }
}
</script>
