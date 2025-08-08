// Utilities
function getCsrf() {
  const meta = document.querySelector('meta[name="csrf-token"]');
  return meta ? meta.getAttribute('content') : '';
}
async function apiPost(path, formData) {
  // path can be relative to api/ or a full path
  const url = path.startsWith('http') ? path
    : path.startsWith('../') ? path.replace('../', BASE_URL)
    : path.startsWith('./') ? path.replace('./', BASE_URL + 'api/')
    : path.includes('.php') ? (BASE_URL + 'api/' + path) : (BASE_URL + 'api/' + path);
  const csrf = getCsrf();
  if (formData && !formData.has('_csrf')) formData.append('_csrf', csrf);
  
  const res = await fetch(url, {
    method: 'POST',
    headers: { 'X-CSRF-Token': csrf },
    body: formData
  });
  
  return res.json();
}
function showMessage(id, payload) {
  const el = document.getElementById(id);
  if (!el) return;
  el.textContent = payload.message || '';
  el.style.color = payload.ok ? '#065f46' : '#991b1b';
}

// Global logout
document.addEventListener('DOMContentLoaded', () => {
  const btnLogout = document.getElementById('btn-logout');
  if (btnLogout) {
    btnLogout.addEventListener('click', async () => {
      const fd = new FormData();
      fd.append('_csrf', getCsrf());
      const res = await apiPost('auth.php?action=logout', fd);
      if (res.ok) window.location.href = BASE_URL;
    });
  }

  // Login form handler
  const loginForm = document.getElementById('login-form');
  if (loginForm) {
    loginForm.addEventListener('submit', async (e) => {
      e.preventDefault();
      const fd = new FormData(loginForm);
      const res = await apiPost('auth.php?action=login', fd);
      showMessage('login-message', res);
      if (res.ok) {
        window.location.href = res.redirect || BASE_URL;
      }
    });
  }

  // Register form handler
  const regForm = document.getElementById('register-form');
  if (regForm) {
    const pwd1 = document.getElementById('reg-password');
    const pwd2 = document.getElementById('reg-password2');
    
    pwd2.addEventListener('input', () => {
      if (pwd1.value !== pwd2.value) {
        pwd2.setCustomValidity('Les mots de passe ne correspondent pas.');
      } else {
        pwd2.setCustomValidity('');
      }
    });
    
    regForm.addEventListener('submit', async (e) => {
      e.preventDefault();
      const fd = new FormData(regForm);
      const res = await apiPost('auth.php?action=register', fd);
      showMessage('register-message', res);
      if (res.ok) {
        setTimeout(() => window.location.href = BASE_URL + '?page=login', 800);
      }
    });
  }
});
