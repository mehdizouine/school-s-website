<?php
require_once __DIR__.'/db.php';
require_once __DIR__.'/authorisation.php';
require_once __DIR__.'/config.php';
if(session_status()===PHP_SESSION_NONE) session_start();
require_login();
require_role('admin');
?>
<!doctype html>
<html lang="fr">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Dashboard Analytics - <?=htmlspecialchars(SITE_NAME)?></title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<style>
:root {
  --primary: #0E7770;
  --primary-light: #1BD1C2;
  --primary-dark: #0A5A55;
  --white: #ffffff;
  --light: #f8f9fa;
  --gray-100: #f1f3f5;
  --gray-200: #e9ecef;
  --gray-700: #495057;
  --gray-900: #212529;
  --card-bg: rgba(255, 255, 255, 0.85);
  --card-border: rgba(255, 255, 255, 0.3);
  --shadow: 0 4px 24px rgba(0, 0, 0, 0.08);
  --transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
}

* {
  margin: 0;
  padding: 0;
  box-sizing: border-box;
}

body {
  background: linear-gradient(135deg, var(--primary) 0%, var(--primary-light) 100%);
  font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
  padding: 1.25rem;
  color: var(--gray-900);
  line-height: 1.6;
}

.container {
  max-width: 1400px;
  margin: 0 auto;
}

/* ====== Titre ====== */
h1 {
  color: var(--white);
  font-weight: 800;
  margin-bottom: 1.5rem;
  text-shadow: 0 1px 2px rgba(0,0,0,0.15);
  letter-spacing: -0.5px;
}

/* ====== Filtres ====== */
.row.mb-4.g-2 {
  background: var(--card-bg);
  border-radius: 16px;
  padding: 1.25rem;
  box-shadow: var(--shadow);
  border: 1px solid var(--card-border);
  margin-bottom: 1.75rem !important;
}

.row.mb-4.g-2 .form-label {
  font-weight: 600;
  margin-bottom: 0.4rem;
  color: var(--gray-700);
  font-size: 0.9rem;
}

.row.mb-4.g-2 select {
  border: 1px solid var(--gray-200);
  padding: 0.5rem 0.75rem;
  border-radius: 10px;
  background-color: var(--white);
  transition: var(--transition);
  font-size: 0.95rem;
}

.row.mb-4.g-2 select:focus {
  outline: none;
  border-color: var(--primary);
  box-shadow: 0 0 0 3px rgba(14, 119, 112, 0.12);
}

.row.mb-4.g-2 select:disabled {
  background-color: var(--gray-100);
  color: #adb5bd;
  cursor: not-allowed;
}

/* ====== Cartes de stats ====== */
.grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
  gap: 1.25rem;
  margin-bottom: 1.75rem !important;
}

.card-glass {
  background: var(--card-bg);
  border-radius: 16px;
  padding: 1.25rem;
  box-shadow: var(--shadow);
  border: 1px solid var(--card-border);
  position: relative;
  overflow: hidden;
  backdrop-filter: blur(4px);
  transition: var(--transition);
}

.card-glass:hover {
  transform: translateY(-4px);
  box-shadow: 0 6px 28px rgba(0, 0, 0, 0.12);
}

.card-icon {
  position: absolute;
  top: 1rem;
  right: 1rem;
  font-size: 2rem;
  color: rgba(14, 119, 112, 0.12);
  z-index: 0;
}

.card-glass h6 {
  font-size: 1rem;
  font-weight: 700;
  margin-bottom: 0.5rem;
  color: var(--primary-dark);
}

/* ====== Chiffres clés ====== */
.stat-value {
  font-size: 2rem;
  font-weight: 800;
  margin: 0.5rem 0;
  color: var(--primary-dark);
  line-height: 1;
  transition: color 0.3s ease;
}

.stat-value.loading {
  color: var(--gray-700);
}

.stat-value.loaded {
  color: var(--primary-dark);
  animation: fadeInNumber 0.6s ease-out;
}

@keyframes fadeInNumber {
  from {
    opacity: 0;
    transform: translateY(8px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

/* ====== Sections principales ====== */
.row.g-3 .card-glass {
  margin-bottom: 1.25rem !important;
}

.row.g-3 .card-glass h6 {
  font-size: 1.15rem;
  font-weight: 700;
  margin-bottom: 1rem;
  color: var(--primary-dark);
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

/* ====== Graphiques ====== */
.chart-container {
  position: relative;
  height: 220px;
  width: 100%;
  margin: 1.1rem 0;
}

#notes-detail-section .chart-container {
  height: 280px;
}

/* ====== Tableaux ====== */
.table-responsive {
  max-height: 400px;
  overflow: auto;
  margin-top: 0.75rem;
}

.table th {
  font-weight: 700;
  background-color: rgba(14, 119, 112, 0.03);
  color: var(--primary-dark);
  border-color: var(--gray-200);
  padding: 0.6rem 0.75rem;
}

.table td {
  border-color: var(--gray-200);
  padding: 0.6rem 0.75rem;
  font-size: 0.95rem;
}

/* ====== Loading ====== */
#loading-msg {
  display: none;
  text-align: center;
  margin: 1rem 0;
  color: var(--primary);
  font-weight: 600;
  font-size: 0.95rem;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 0.5rem;
}

.spinner {
  display: inline-block;
  width: 18px;
  height: 18px;
  border: 2px solid rgba(14, 119, 112, 0.3);
  border-top: 2px solid var(--primary);
  border-radius: 50%;
  animation: spin 1s linear infinite;
}

@keyframes spin {
  to { transform: rotate(360deg); }
}

/* ====== Responsive ====== */
@media (max-width: 1199px) {
  .grid {
    grid-template-columns: repeat(auto-fit, minmax(190px, 1fr));
  }
}

@media (max-width: 991px) {
  body {
    padding: 1rem;
  }
  
  .grid {
    grid-template-columns: repeat(2, 1fr);
  }
  
  .chart-container {
    height: 240px;
  }
  
  #notes-detail-section .chart-container {
    height: 300px;
  }
}

@media (max-width: 767px) {
  .grid {
    grid-template-columns: 1fr;
  }
  
  .row.mb-4.g-2 {
    padding: 1rem;
  }
  
  .chart-container {
    height: 260px;
  }
  
  #notes-detail-section .chart-container {
    height: 320px;
  }
}

/* ====== Micro-interactions ====== */
select.form-select:not(:disabled):hover {
  border-color: #86c8bc;
}
</style>
</head>
<body>
<div class="container">
<h1 class="text-white mb-4">Dashboard Analytics - <?=htmlspecialchars(SITE_NAME)?></h1>

<!-- Filtres principaux -->
<div class="row mb-4 g-2">
  <div class="col-md-6 col-lg-3">
    <label for="filter-classe-main" class="form-label">Classe</label>
    <select id="filter-classe-main" class="form-select">
      <option value="">Sélectionnez</option>
      <?php
        $r = $conn->query("SELECT ID, nom_de_classe FROM classes ORDER BY nom_de_classe");
        while($c = $r->fetch_assoc()) {
          echo "<option value=\"{$c['ID']}\">".htmlspecialchars($c['nom_de_classe'])."</option>";
        }
      ?>
    </select>
  </div>
  <div class="col-md-6 col-lg-3">
    <label for="filter-matiere" class="form-label">Matière</label>
    <select id="filter-matiere" class="form-select" disabled>
      <option value="">Sélectionnez</option>
      <?php
        $m = $conn->query("SELECT ID_matiere, matiere FROM matiere ORDER BY matiere");
        while($mat = $m->fetch_assoc()) {
          echo "<option value=\"{$mat['ID_matiere']}\">".htmlspecialchars($mat['matiere'])."</option>";
        }
      ?>
    </select>
  </div>
  <div class="col-md-6 col-lg-3">
    <label for="filter-examen" class="form-label">Examen</label>
    <select id="filter-examen" class="form-select" disabled>
      <option value="">Sélectionnez</option>
      <?php
        $e = $conn->query("SELECT ID_examen, nom_examen FROM examen ORDER BY nom_examen");
        while($ex = $e->fetch_assoc()) {
          echo "<option value=\"{$ex['ID_examen']}\">".htmlspecialchars($ex['nom_examen'])."</option>";
        }
      ?>
    </select>
  </div>
  <div class="col-md-6 col-lg-3">
    <label for="filter-semestre" class="form-label">Semestre</label>
    <select id="filter-semestre" class="form-select" disabled>
      <option value="">Sélectionnez</option>
      <?php
        $s = $conn->query("SELECT ID_semestre, nom_semestre FROM semestre ORDER BY nom_semestre");
        while($sem = $s->fetch_assoc()) {
          echo "<option value=\"{$sem['ID_semestre']}\">".htmlspecialchars($sem['nom_semestre'])."</option>";
        }
      ?>
    </select>
  </div>
</div>

<!-- 4 cartes dynamiques SANS graphique -->
<div class="grid">
  <div class="card-glass" id="card-users">
    <i class="card-icon">👤</i>
    <h6>Élèves</h6>
    <p id="users-count" class="stat-value">—</p>
  </div>
  <div class="card-glass" id="card-classes">
    <i class="card-icon">🏫</i>
    <h6>Classes</h6>
    <p id="classes-count" class="stat-value">—</p>
  </div>
  <div class="card-glass" id="card-notes">
    <i class="card-icon">📝</i>
    <h6>Notes</h6>
    <p id="notes-count" class="stat-value">—</p>
  </div>
  <div class="card-glass" id="card-devoirs">
    <i class="card-icon">📚</i>
    <h6>Devoirs</h6>
    <p id="devoirs-count" class="stat-value">—</p>
  </div>
</div>

<div class="row g-3">
  <div class="col-lg-8">
    <div class="card-glass mb-3">
      <h6>Élèves par classe</h6>
      <div class="chart-container"><canvas id="classChart"></canvas></div>
    </div>

    <div class="card-glass mb-3">
      <h6>Moyennes par matière</h6>
      <div class="chart-container"><canvas id="matiereChart"></canvas></div>
    </div>

    <div class="card-glass mb-3">
      <h6>Moyenne générale par élève</h6>
      <div class="table-responsive">
        <table class="table table-striped" id="eleves-table">
          <thead><tr><th>Élève</th><th>Classe</th><th>Moyenne</th></tr></thead>
          <tbody></tbody>
        </table>
      </div>
    </div>

    <!-- Section détaillée -->
    <div class="card-glass mb-3" id="notes-detail-section" style="display:none">
      <div class="d-flex justify-content-between align-items-center">
        <h6>Notes — <span id="detail-title">Sélectionnez une classe et une matière</span></h6>
        <small id="note-count" class="text-muted-small"></small>
      </div>
      <div id="loading-msg"><span class="spinner"></span> Chargement…</div>
      <div class="chart-container"><canvas id="exam-notes-chart"></canvas></div>
      <div class="table-responsive mt-3">
        <table class="table table-sm">
          <thead><tr><th>Élève</th><th>Examen</th><th>Note</th><th>Semestre</th></tr></thead>
          <tbody id="exam-notes-tbody"></tbody>
        </table>
      </div>
    </div>
  </div>

  <div class="col-lg-4">
    <div class="card-glass mb-3">
      <h6>Dernières notes / devoirs</h6>
      <select id="filter-classe" class="form-select form-select-sm mb-2">
        <option value="0">Toutes les classes</option>
        <?php
          $r = $conn->query("SELECT ID, nom_de_classe FROM classes ORDER BY nom_de_classe");
          while($c=$r->fetch_assoc()){
            echo "<option value=\"{$c['ID']}\">".htmlspecialchars($c['nom_de_classe'])."</option>";
          }
        ?>
      </select>
      <div class="table-responsive" style="max-height:400px">
        <table class="table table-sm" id="last-items">
          <thead><tr><th>Élève</th><th>Type</th><th>Détail</th><th>Valeur</th></tr></thead>
          <tbody></tbody>
        </table>
      </div>
    </div>

    <div class="card-glass">
      <h6>Actions rapides</h6>
      <a href="newsletter-admin.php" class="btn btn-success w-100 mb-2">Envoyer Newsletter</a>
      <a href="modif_class.php" class="btn btn-outline-secondary w-100">Gérer les classes</a>
    </div>
  </div>
</div>
</div>

<script>
async function fetchJSON(url) {
  const res = await fetch(url);
  if (!res.ok) throw new Error(`HTTP ${res.status}`);
  return res.json();
}

// Gestion des filtres
document.getElementById('filter-classe-main').addEventListener('change', e => {
  const enabled = !!e.target.value;
  document.getElementById('filter-matiere').disabled = !enabled;
  document.getElementById('filter-examen').disabled = true;
  document.getElementById('filter-semestre').disabled = true;
  ['filter-matiere','filter-examen','filter-semestre'].forEach(id => document.getElementById(id).value = '');
  document.getElementById('notes-detail-section').style.display = 'none';
});

document.getElementById('filter-matiere').addEventListener('change', e => {
  const enabled = !!e.target.value;
  document.getElementById('filter-examen').disabled = !enabled;
  document.getElementById('filter-semestre').disabled = !enabled;
  ['filter-examen','filter-semestre'].forEach(id => document.getElementById(id).value = '');
  loadNotesForClassAndMatiere();
});

document.getElementById('filter-examen').addEventListener('change', loadNotesForClassAndMatiere);
document.getElementById('filter-semestre').addEventListener('change', loadNotesForClassAndMatiere);

let examNotesChart = null;
async function loadNotesForClassAndMatiere() {
  const classeId = document.getElementById('filter-classe-main').value;
  const matiereId = document.getElementById('filter-matiere').value;
  const examenId = document.getElementById('filter-examen').value;
  const semestreId = document.getElementById('filter-semestre').value;

  if (!classeId || !matiereId) {
    document.getElementById('notes-detail-section').style.display = 'none';
    return;
  }

  const loadingEl = document.getElementById('loading-msg');
  const chartEl = document.getElementById('exam-notes-chart');
  const tbody = document.getElementById('exam-notes-tbody');
  const noteCountEl = document.getElementById('note-count');

  loadingEl.style.display = 'block';
  chartEl.style.display = 'block';
  tbody.innerHTML = '';

  try {
    const params = new URLSearchParams({ action: 'notes_classe_matiere', classe: classeId, matiere: matiereId });
    if (examenId) params.append('examen', examenId);
    if (semestreId) params.append('semestre', semestreId);

    const res = await fetchJSON(`api.php?${params}`);
    if (res.error) throw new Error(res.error);

    const notesByEleve = {};
    res.forEach(note => {
      if (!notesByEleve[note.Username]) notesByEleve[note.Username] = [];
      notesByEleve[note.Username].push(note);
    });

    const elevesSorted = Object.entries(notesByEleve)
      .map(([name, notes]) => {
        const avg = notes.reduce((sum, n) => sum + parseFloat(n.note), 0) / notes.length;
        return { name, notes, avg };
      })
      .sort((a, b) => b.avg - a.avg);

    tbody.innerHTML = '';
    elevesSorted.forEach(({ name, notes }) => {
      notes.forEach(note => {
        const tr = document.createElement('tr');
        tr.innerHTML = `
          <td>${note.Username}</td>
          <td>${note.nom_examen || '—'}</td>
          <td>${parseFloat(note.note).toFixed(1)}/20</td>
          <td>${note.nom_semestre || '—'}</td>
        `;
        tbody.appendChild(tr);
      });
    });

    const classeNom = document.querySelector(`#filter-classe-main option[value="${classeId}"]`).textContent;
    const matiereNom = document.querySelector(`#filter-matiere option[value="${matiereId}"]`).textContent;
    document.getElementById('detail-title').textContent = `${classeNom} — ${matiereNom}`;
    noteCountEl.textContent = `${res.length} note${res.length > 1 ? 's' : ''}`;

    const labels = elevesSorted.map(e => e.name);
    const data = elevesSorted.map(e => parseFloat(e.avg.toFixed(2)));

    const ctx = chartEl.getContext('2d');
    if (examNotesChart) examNotesChart.destroy();
    examNotesChart = new Chart(ctx, {
      type: 'bar',
      data: {
        labels,
        datasets: [{
          label: 'Moyenne',
          data,
          backgroundColor: 'rgba(14,119,112,0.8)',
          borderColor: 'rgba(14,119,112,1)',
          borderWidth: 1
        }]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        scales: { y: { beginAtZero: true, max: 20 } },
        plugins: { legend: { display: false } }
      }
    });

    document.getElementById('notes-detail-section').style.display = 'block';

  } catch (err) {
    tbody.innerHTML = `<tr><td colspan="4" class="text-center text-danger">❌ ${err.message || 'Erreur'}</td></tr>`;
    console.error(err);
  } finally {
    loadingEl.style.display = 'none';
  }
}

// === Chargement initial ===
async function loadStats(){
  try {
    // Ajoute la classe "loading"
    ['users-count', 'classes-count', 'notes-count', 'devoirs-count'].forEach(id => {
      document.getElementById(id).classList.add('loading');
    });

    const s = await fetchJSON('api.php?action=stats');
    
    const updateStat = (id, value) => {
      const el = document.getElementById(id);
      el.textContent = value.toLocaleString();
      el.classList.remove('loading');
      el.classList.add('loaded');
      setTimeout(() => el.classList.remove('loaded'), 600);
    };

    updateStat('users-count', s.users);
    updateStat('classes-count', s.classes);
    updateStat('notes-count', s.notes);
    updateStat('devoirs-count', s.devoirs);

  } catch(e) { 
    console.error(e);
    ['users-count', 'classes-count', 'notes-count', 'devoirs-count'].forEach(id => {
      document.getElementById(id).classList.remove('loading');
    });
  }
}

let classChart = null;
async function loadClassChart(){
  try {
    const res = await fetchJSON('api.php?action=class_counts');
    const ctx = document.getElementById('classChart').getContext('2d');
    if(classChart) classChart.destroy();
    classChart = new Chart(ctx, {
      type: 'bar',
      data: { labels: res.labels, datasets: [{ label: 'Élèves', data: res.data, backgroundColor: 'rgba(14,119,112,0.8)' }] },
      options: { responsive: true, plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true } } }
    });
  } catch(e) { console.error(e); }
}

let matiereChart = null;
async function loadMatiereChart(){
  try {
    const res = await fetchJSON('api.php?action=matiere_counts');
    const ctx = document.getElementById('matiereChart').getContext('2d');
    if(matiereChart) matiereChart.destroy();
    matiereChart = new Chart(ctx, {
      type: 'bar',
      data: { labels: res.labels, datasets: [{ label: 'Moyenne', data: res.data, backgroundColor: 'rgba(255,159,64,0.8)' }] },
      options: { responsive: true, plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true, max: 20 } } }
    });
  } catch(e) { console.error(e); }
}

async function loadElevesTable(){
  try {
    const res = await fetchJSON('api.php?action=eleves_moyenne');
    const tbody = document.querySelector('#eleves-table tbody');
    tbody.innerHTML = '';
    res.forEach(u => {
      const tr = document.createElement('tr');
      tr.innerHTML = `<td>${u.Username}</td><td>${u.nom_de_classe || '—'}</td><td>${parseFloat(u.moyenne).toFixed(2)}</td>`;
      tbody.appendChild(tr);
    });
  } catch(e) { console.error(e); }
}

async function loadLastItems(classe = 0){
  try {
    const url = `api.php?action=last_items${classe ? '&classe='+classe : ''}`;
    const res = await fetchJSON(url);
    const tbody = document.querySelector('#last-items tbody');
    tbody.innerHTML = '';
    res.forEach(i => {
      const tr = document.createElement('tr');
      tr.innerHTML = `<td>${i.Username || '—'}</td><td>${i.type}</td><td>${i.detail || '—'}</td><td>${i.value || '—'}</td>`;
      tbody.appendChild(tr);
    });
  } catch(e) { console.error(e); }
}

document.getElementById('filter-classe').addEventListener('change', e => loadLastItems(e.target.value));

// Lancement
loadStats();
loadClassChart();
loadMatiereChart();
loadElevesTable();
loadLastItems();
</script>
</body>
</html>