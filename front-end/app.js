/* ============================================================
   GestPFE — JavaScript complet
   Connexion, Inscription, Dashboard, Navigation
============================================================ */

document.addEventListener('DOMContentLoaded', function () {
  const body = document.body;
  if (body.classList.contains('home-page'))      initHomePage();
  else if (body.classList.contains('auth-page')) initAuthPage();
  else if (body.classList.contains('dashboard-page')) initDashboard();
});

/* ── HOME PAGE ─────────────────────────────────────────── */
function initHomePage() {
  const navbar = document.getElementById('navbar');
  if (navbar) {
    window.addEventListener('scroll', () => {
      navbar.classList.toggle('scrolled', window.scrollY > 30);
    });
  }

  const hamburger = document.getElementById('hamburger');
  const navLinks  = document.getElementById('nav-links');
  if (hamburger && navLinks) {
    hamburger.addEventListener('click', () => navLinks.classList.toggle('open'));
  }

  document.querySelectorAll('a[href^="#"]').forEach(a => {
    a.addEventListener('click', function (e) {
      const target = document.querySelector(this.getAttribute('href'));
      if (target) {
        e.preventDefault();
        window.scrollTo({ top: target.getBoundingClientRect().top + window.scrollY - 80, behavior: 'smooth' });
        if (navLinks) navLinks.classList.remove('open');
      }
    });
  });
}

/* ── AUTH PAGE ─────────────────────────────────────────── */
function initAuthPage() {
  const params = new URLSearchParams(window.location.search);
  if (params.get('mode') === 'register') switchTab('register');

  const pwInput = document.getElementById('reg-password');
  if (pwInput) pwInput.addEventListener('input', function () { checkPasswordStrength(this.value); });
}

function switchTab(tab) {
  document.querySelectorAll('.auth-tab').forEach(t => t.classList.remove('active'));
  document.querySelectorAll('.auth-form').forEach(f => f.classList.remove('active'));
  document.getElementById('tab-' + tab)?.classList.add('active');
  document.getElementById('form-' + tab)?.classList.add('active');
  hideAlerts();
}

function handleLogin() {
  const email    = document.getElementById('login-email')?.value.trim();
  const password = document.getElementById('login-password')?.value.trim();

  if (!email || !password)      { showError('Veuillez remplir tous les champs.'); return; }
  if (!validateEmail(email))    { showError('Veuillez entrer un email valide.'); return; }

  setBtnLoading('login-btn-text', 'login-spinner', true);

  // Appel API PHP
  fetch('login.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ email, mot_de_passe: password })
  })
  .then(r => r.json())
  .then(data => {
    setBtnLoading('login-btn-text', 'login-spinner', false);
    if (data.success) {
      if (data.etudiant) localStorage.setItem('pfe_user', JSON.stringify(data.etudiant));
      showSuccess('Connexion réussie ! Redirection...');
      setTimeout(() => window.location.href = 'dashboard.html', 1000);
    } else {
      showError(data.message || 'Erreur de connexion.');
    }
  })
  .catch(() => {
    // Mode démo si pas de backend
    setBtnLoading('login-btn-text', 'login-spinner', false);
    localStorage.setItem('pfe_user', JSON.stringify({
      email,
      prenom: email.split('.')[0] || 'Étudiant',
      nom:    email.split('.')[1]?.split('@')[0] || '',
      filiere: 'Licence Informatique'
    }));
    showSuccess('Connexion réussie ! (mode démo)');
    setTimeout(() => window.location.href = 'dashboard.html', 1000);
  });
}

function handleRegister() {
  const prenom   = document.getElementById('reg-prenom')?.value.trim();
  const nom      = document.getElementById('reg-nom')?.value.trim();
  const email    = document.getElementById('reg-email')?.value.trim();
  const cin      = document.getElementById('reg-cin')?.value.trim();
  const filiere  = document.getElementById('reg-filiere')?.value;
  const password = document.getElementById('reg-password')?.value;
  const confirm  = document.getElementById('reg-confirm')?.value;
  const agree    = document.getElementById('agree-terms')?.checked;

  if (!prenom || !nom || !email || !cin || !filiere || !password || !confirm) {
    showError('Veuillez remplir tous les champs obligatoires.'); return;
  }
  if (!validateEmail(email))        { showError('Email invalide.'); return; }
  if (!/^\d{8}$/.test(cin))        { showError('Le CIN doit contenir exactement 8 chiffres.'); return; }
  if (password.length < 8)          { showError('Mot de passe : minimum 8 caractères.'); return; }
  if (password !== confirm)         { showError('Les mots de passe ne correspondent pas.'); return; }
  if (!agree)                       { showError("Vous devez accepter les conditions d'utilisation."); return; }

  setBtnLoading('reg-btn-text', 'reg-spinner', true);

  fetch('register.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ prenom, nom, email, cin, filiere, mot_de_passe: password })
  })
  .then(r => r.json())
  .then(data => {
    setBtnLoading('reg-btn-text', 'reg-spinner', false);
    if (data.success) {
      if (data.etudiant) localStorage.setItem('pfe_user', JSON.stringify(data.etudiant));
      showSuccess('Compte créé avec succès ! Redirection...');
      setTimeout(() => window.location.href = 'dashboard.html', 1200);
    } else {
      showError(data.message || 'Erreur lors de l\'inscription.');
    }
  })
  .catch(() => {
    setBtnLoading('reg-btn-text', 'reg-spinner', false);
    localStorage.setItem('pfe_user', JSON.stringify({ prenom, nom, email, filiere }));
    showSuccess('Compte créé ! (mode démo)');
    setTimeout(() => window.location.href = 'dashboard.html', 1200);
  });
}

function setBtnLoading(textId, spinnerId, loading) {
  const text    = document.getElementById(textId);
  const spinner = document.getElementById(spinnerId);
  if (text)    text.style.display    = loading ? 'none' : 'inline-flex';
  if (spinner) spinner.style.display = loading ? 'inline-block' : 'none';
}

function togglePw(inputId, btn) {
  const input = document.getElementById(inputId);
  if (!input) return;
  const isPassword = input.type === 'password';
  input.type = isPassword ? 'text' : 'password';
  btn.innerHTML = isPassword
    ? '<i class="fas fa-eye-slash"></i>'
    : '<i class="fas fa-eye"></i>';
}

function checkPasswordStrength(pw) {
  const bar   = document.getElementById('pw-bar-fill');
  const label = document.getElementById('pw-strength-label');
  if (!bar || !label) return;
  let score = 0;
  if (pw.length >= 8)          score++;
  if (/[A-Z]/.test(pw))       score++;
  if (/[0-9]/.test(pw))       score++;
  if (/[^A-Za-z0-9]/.test(pw)) score++;
  const cfgs = [
    { width:'0%',    color:'transparent', text:'' },
    { width:'25%',   color:'#ff4f6d',     text:'⚠ Très faible' },
    { width:'50%',   color:'#f5a623',     text:'⚡ Faible' },
    { width:'75%',   color:'#f5e623',     text:'✓ Moyen' },
    { width:'100%',  color:'#10e88a',     text:'✅ Fort' },
  ];
  const c = cfgs[score] || cfgs[0];
  bar.style.width      = c.width;
  bar.style.background = c.color;
  label.textContent    = c.text;
  label.style.color    = c.color;
}

function showError(msg) {
  const el  = document.getElementById('alert-error');
  const msg2 = document.getElementById('alert-error-msg');
  if (el && msg2) { msg2.textContent = msg; el.style.display = 'flex'; }
  const s = document.getElementById('alert-success');
  if (s) s.style.display = 'none';
}

function showSuccess(msg) {
  const el  = document.getElementById('alert-success');
  const msg2 = document.getElementById('alert-success-msg');
  if (el && msg2) { msg2.textContent = msg; el.style.display = 'flex'; }
  const e = document.getElementById('alert-error');
  if (e) e.style.display = 'none';
}

function hideAlerts() {
  document.getElementById('alert-success')?.style && (document.getElementById('alert-success').style.display = 'none');
  document.getElementById('alert-error')?.style   && (document.getElementById('alert-error').style.display   = 'none');
}

/* ── DASHBOARD ─────────────────────────────────────────── */
function initDashboard() {
  const user = getUser();
  if (user) {
    const prenom  = capitalize(user.prenom) || 'Étudiant';
    const nom     = capitalize(user.nom)    || '';
    const full    = prenom + (nom ? ' ' + nom : '');
    const initials = (prenom[0]||'E').toUpperCase() + (nom[0]||'T').toUpperCase();

    setEl('sidebar-name',    full);
    setEl('sidebar-email',   user.email || '');
    setEl('sidebar-initials', initials);
    setEl('topbar-name',     prenom);
    setEl('welcome-name',    prenom);
    setEl('profil-name',     full);
    setEl('profil-filiere',  user.filiere || '');
    setEl('profil-prenom',   prenom);
    setEl('profil-nom',      nom);
    setEl('profil-email',    user.email || '');
    setEl('profil-filiere2', user.filiere || '');

    document.querySelectorAll('.topbar-user-avatar, #profile-avatar-letters').forEach(el => {
      el.textContent = initials;
    });
  }

  // Sidebar navigation
  document.querySelectorAll('.sidebar-link[data-page]').forEach(link => {
    link.addEventListener('click', function () {
      const page  = this.dataset.page;
      const label = this.textContent.trim().replace(/[0-9\/]+$/, '').trim();
      navigateTo(page, label);
      if (window.innerWidth < 860) document.getElementById('sidebar')?.classList.remove('open');
    });
  });

  // Close notif panel on outside click
  document.addEventListener('click', function (e) {
    const panel = document.getElementById('notif-panel');
    const btn   = document.getElementById('notif-btn');
    if (panel && !panel.contains(e.target) && btn && !btn.contains(e.target)) {
      panel.classList.remove('show');
    }
  });
}

function setEl(id, val) {
  const el = document.getElementById(id);
  if (el) el.textContent = val;
}

function navigateTo(page, title) {
  document.querySelectorAll('.sidebar-link').forEach(l => l.classList.remove('active'));
  const link = document.querySelector(`.sidebar-link[data-page="${page}"]`);
  if (link) link.classList.add('active');

  document.querySelectorAll('.page').forEach(p => p.classList.add('hidden'));
  document.getElementById('page-' + page)?.classList.remove('hidden');

  const titleEl = document.getElementById('page-title');
  if (titleEl) titleEl.textContent = title.replace(/\s*\d+$/, '').trim();
}

function toggleSidebar() {
  document.getElementById('sidebar')?.classList.toggle('open');
}

function toggleNotifications() {
  const panel = document.getElementById('notif-panel');
  if (panel) {
    panel.classList.toggle('show');
    const dot = document.querySelector('.notif-dot');
    if (dot && panel.classList.contains('show')) dot.style.display = 'none';
  }
}

function markAllRead() {
  document.querySelectorAll('.notif-item.unread').forEach(item => item.classList.remove('unread'));
  showToast('✅ Toutes les notifications marquées comme lues');
}

/* ── Proposition ────────────────────────────────────────── */
function submitProposition() {
  const titre     = document.getElementById('prop-titre')?.value.trim();
  const objectifs = document.getElementById('prop-objectifs')?.value.trim();
  const tech      = document.getElementById('prop-tech')?.value.trim();
  const theme     = document.getElementById('prop-theme')?.value;

  if (!titre || !objectifs || !tech || !theme) {
    showToast('⚠ Veuillez remplir tous les champs obligatoires.'); return;
  }

  const formData = new FormData();
  formData.append('titre', titre);
  formData.append('theme', theme);
  formData.append('objectifs', objectifs);
  formData.append('technologies', tech);
  formData.append('entreprise', document.getElementById('prop-entreprise')?.value.trim() || '');
  formData.append('resume', document.getElementById('prop-resume')?.value.trim() || '');

  const file = document.getElementById('prop-file')?.files[0];
  if (file) formData.append('fichier', file);

  fetch('proposition.php', { method: 'POST', body: formData })
    .then(r => r.json())
    .then(data => {
      if (data.success) showToast('✅ Proposition soumise avec succès !');
      else showToast('⚠ ' + (data.message || 'Erreur'));
    })
    .catch(() => showToast('✅ Proposition soumise ! (mode démo)'));
}

/* ── Compte Rendu ───────────────────────────────────────── */
function submitCR() {
  const numero   = document.getElementById('cr-numero')?.value;
  const travaux  = document.getElementById('cr-travaux')?.value.trim();
  const avenir   = document.getElementById('cr-avenir')?.value.trim();
  const problemes = document.getElementById('cr-problemes')?.value.trim();

  if (!numero || !travaux) {
    showToast('⚠ Le numéro et les travaux réalisés sont obligatoires.'); return;
  }

  fetch('comptes_rendus.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({
      numero: parseInt(numero),
      travaux_realises: travaux,
      travaux_a_venir: avenir,
      problemes
    })
  })
  .then(r => r.json())
  .then(data => {
    if (data.success) showToast('✅ Compte rendu soumis au tuteur !');
    else showToast('⚠ ' + (data.message || 'Erreur'));
  })
  .catch(() => showToast('✅ Compte rendu soumis ! (mode démo)'));
}

function saveDraft() {
  showToast('💾 Brouillon sauvegardé !');
}

/* ── File upload ────────────────────────────────────────── */
function handleFileSelect(input) {
  if (input.files && input.files[0]) {
    const file = input.files[0];
    const area = input.closest('.file-upload-area');
    if (area) {
      area.querySelector('p').innerHTML  = `<strong>${file.name}</strong> sélectionné`;
      area.querySelector('small').textContent = formatBytes(file.size);
      const icon = area.querySelector('i');
      if (icon) { icon.className = 'fas fa-file-check'; icon.style.color = 'var(--success)'; }
    }
  }
}

function handleLogout() {
  fetch('logout.php').catch(() => {});
  localStorage.removeItem('pfe_user');
}

/* ── Toast ──────────────────────────────────────────────── */
function showToast(msg) {
  const toast = document.getElementById('toast');
  const msgEl = document.getElementById('toast-msg');
  if (!toast || !msgEl) return;
  msgEl.textContent = msg;
  toast.classList.add('show');
  clearTimeout(toast._t);
  toast._t = setTimeout(() => toast.classList.remove('show'), 3200);
}

/* ── Helpers ────────────────────────────────────────────── */
function validateEmail(email) {
  return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);
}

function getUser() {
  try { return JSON.parse(localStorage.getItem('pfe_user')); }
  catch { return null; }
}

function capitalize(str) {
  if (!str) return '';
  return str.charAt(0).toUpperCase() + str.slice(1).toLowerCase();
}

function formatBytes(bytes) {
  if (!bytes) return '0 Bytes';
  const k = 1024, sizes = ['Bytes','KB','MB','GB'];
  const i = Math.floor(Math.log(bytes) / Math.log(k));
  return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
}
