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

/* ====== Graphiques avec scroll horizontal ====== */
.chart-container-scrollable {
  width: 100%;
  overflow-x: auto;
  overflow-y: hidden;
  padding-bottom: 10px;
  margin: 1.1rem 0;
}

.chart-wrapper {
  min-width: 100%;
  display: inline-block;
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
<!-- Résumé de la classe -->
<div id="resume-classe" class="card-glass mb-3" style="display:none; background: rgba(255,255,255,0.9);">
  <div id="resume-content" class="d-flex flex-wrap gap-3"></div>
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
    <div class="card-glass mb-3">
      <h6>🆚 Comparaison de classes</h6>
      <div class="row g-2 mb-2">
        <div class="col-6">
          <select id="compare-classe1" class="form-select form-select-sm">
            <option value="">Classe 1</option>
            <?php
              $r = $conn->query("SELECT ID, nom_de_classe FROM classes ORDER BY nom_de_classe");
              while($c = $r->fetch_assoc()) {
                echo "<option value=\"{$c['ID']}\">".htmlspecialchars($c['nom_de_classe'])."</option>";
              }
            ?>
          </select>
        </div>
        <div class="col-6">
          <select id="compare-classe2" class="form-select form-select-sm">
            <option value="">Classe 2</option>
            <?php
              $r = $conn->query("SELECT ID, nom_de_classe FROM classes ORDER BY nom_de_classe");
              while($c = $r->fetch_assoc()) {
                echo "<option value=\"{$c['ID']}\">".htmlspecialchars($c['nom_de_classe'])."</option>";
              }
            ?>
          </select>
        </div>
      </div>
      <div class="chart-container" id="comparison-chart-container" style="height:250px;">
        <canvas id="comparison-chart"></canvas>
      </div>
    </div>

    <!-- Section détaillée -->
    <div class="card-glass mb-3" id="notes-detail-section" style="display:none">
      <div class="d-flex justify-content-between align-items-center">
        <h6>Notes — <span id="detail-title">Sélectionnez une classe et une matière</span></h6>
        <small id="note-count" class="text-muted-small"></small>
      </div>
      <div id="loading-msg"><span class="spinner"></span> Chargement…</div>

      <!-- ✅ Graphique de progression -->
      <div class="chart-container-scrollable" id="progression-chart-container" style="display:none;">
        <h6 class="mb-2">📈 Progression dans la matière</h6>
        <div class="chart-wrapper">
          <canvas id="progression-chart" height="200"></canvas>
        </div>
      </div>
      <!-- Histogramme de distribution -->
      <div class="chart-container-scrollable" id="distribution-chart-container" style="display:none;">
        <h6 class="mb-2">📊 Distribution des notes</h6>
        <div class="chart-wrapper">
          <canvas id="distribution-chart" height="200"></canvas>
        </div>
      </div>

      <div class="chart-container-scrollable">
        <div class="chart-wrapper">
          <canvas id="exam-notes-chart" height="280"></canvas>
        </div>
      </div>
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
    <div class="card-glass mb-3">
      <h6>⚠️ Alertes pédagogiques</h6>
      <div class="table-responsive" style="max-height:300px">
        <table class="table table-sm">
          <thead><tr><th>Note</th><th>Élève</th><th>Matière</th></tr></thead>
          <tbody id="alertes-tbody">
            <tr><td colspan="3" class="text-center"><span class="spinner"></span> Chargement…</td></tr>
          </tbody>
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
// ✅ Tout le code est maintenant dans DOMContentLoaded
document.addEventListener('DOMContentLoaded', () => {

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
  loadResumeClasse(e.target.value);
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

// ========= GRAPHIQUE DE PROGRESSION =========
let progressionChart = null;
async function loadProgressionChart(classeId, matiereId) {
  const container = document.getElementById('progression-chart-container');
  const chartEl = document.getElementById('progression-chart');

  try {
    const res = await fetchJSON(`api.php?action=notes_par_examen&classe=${classeId}&matiere=${matiereId}`);
    if (res.error || !Array.isArray(res) || res.length === 0) {
      container.style.display = 'none';
      return;
    }

    const labels = res.map(r => r.nom_examen);
    const data = res.map(r => r.moyenne);
    const counts = res.map(r => r.nb_notes);

    const ctx = chartEl.getContext('2d');
    if (progressionChart) progressionChart.destroy();

    const barWidth = 120;
    const chartWidth = Math.max(labels.length * barWidth, 500);
    chartEl.width = chartWidth;
    chartEl.height = 200;

    progressionChart = new Chart(ctx, {
      type: 'line',
      data: {
        labels,
        datasets: [{
          label: 'Moyenne par examen',
          data,
          borderColor: 'rgba(14,119,112,1)',
          backgroundColor: 'rgba(14,119,112,0.1)',
          borderWidth: 2,
          fill: true,
          tension: 0.3,
          pointRadius: 4,
          pointBackgroundColor: 'rgba(14,119,112,1)'
        }]
      },
      options: {
        responsive: false,
        maintainAspectRatio: false,
        plugins: {
          legend: { display: false },
          tooltip: {
            callbacks: {
              label: function(context) {
                const avg = context.parsed.y;
                const count = counts[context.dataIndex];
                return [
                  `Moyenne : ${avg.toFixed(2)}/20`,
                  `Notes : ${count}`
                ];
              }
            }
          }
        },
        scales: {
          y: {
            beginAtZero: true,
            max: 20,
            ticks: { stepSize: 2 }
          },
          x: {
            ticks: {
              autoSkip: false,
              maxRotation: 0,
              minRotation: 0
            }
          }
        }
      }
    });

    container.style.display = 'block';

  } catch (err) {
    console.error('Erreur progression:', err);
    container.style.display = 'none';
  }
}

// ========= GRAPHIQUE PAR ÉLÈVE =========
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

    const allNotes = [];
    Object.entries(notesByEleve).forEach(([name, notes]) => {
      notes.forEach(note => {
        allNotes.push({
          Username: note.Username,
          nom_examen: note.nom_examen || '—',
          note: parseFloat(note.note),
          nom_semestre: note.nom_semestre || '—'
        });
      });
    });

    allNotes.sort((a, b) => {
      if (b.note !== a.note) return b.note - a.note;
      return a.Username.localeCompare(b.Username);
    });

    tbody.innerHTML = '';
    allNotes.forEach(note => {
      const noteEmoji = note.note >= 15 ? '🟢' : (note.note >= 10 ? '🟡' : '🔴');
      const tr = document.createElement('tr');
      tr.innerHTML = `
        <td>${note.Username}</td>
        <td>${note.nom_examen}</td>
        <td>${noteEmoji} ${note.note.toFixed(1)}/20</td>
        <td>${note.nom_semestre}</td>
      `;
      tbody.appendChild(tr);
    });

    const classeNom = document.querySelector(`#filter-classe-main option[value="${classeId}"]`).textContent;
    const matiereNom = document.querySelector(`#filter-matiere option[value="${matiereId}"]`).textContent;
    document.getElementById('detail-title').textContent = `${classeNom} — ${matiereNom}`;
    noteCountEl.textContent = `${res.length} note${res.length > 1 ? 's' : ''}`;

    const labels = elevesSorted.map(e => e.name);
    const data = elevesSorted.map(e => parseFloat(e.avg.toFixed(2)));

    const ctx = chartEl.getContext('2d');
    if (examNotesChart) examNotesChart.destroy();

    const barWidth = 80;
    const chartWidth = Math.max(labels.length * barWidth, 600);
    chartEl.width = chartWidth;
    chartEl.height = 280;

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
        responsive: false,
        maintainAspectRatio: false,
        plugins: {
          legend: { display: false },
          tooltip: {
            callbacks: {
              label: function(context) {
                const avg = context.parsed.y;
                const emoji = avg >= 15 ? '🟢' : (avg >= 10 ? '🟡' : '🔴');
                return `Moyenne : ${avg.toFixed(2)}/20 ${emoji}`;
              },
              title: function(tooltipItems) {
                return tooltipItems[0].label;
              }
            }
          }
        },
        scales: {
          y: {
            beginAtZero: true,
            max: 20,
            ticks: { stepSize: 2 }
          },
          x: {
            ticks: {
              autoSkip: false,
              maxRotation: 0,
              minRotation: 0,
              padding: 8
            },
            grid: {
              display: false
            }
          }
        }
      }
    });

    loadProgressionChart(classeId, matiereId);
    loadDistributionChart(classeId, matiereId, examenId, semestreId);
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
      data: {
        labels: res.labels,
        datasets: [{
          label: 'Moyenne',
          data: res.data,
          backgroundColor: 'rgba(255,159,64,0.8)',
          borderColor: 'rgba(255,159,64,1)',
          borderWidth: 1
        }]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
          legend: { display: false },
          tooltip: {
            callbacks: {
              label: function(context) {
                const avg = context.parsed.y;
                const idx = context.dataIndex;
                const s = res.stats[idx];
                const emoji = avg >= 15 ? '🟢' : (avg >= 10 ? '🟡' : '🔴');
                
                return [
                  `Moyenne : ${avg}/20 ${emoji}`,
                  `Total notes : ${s.total}`,
                  `≥15 : ${s.hautes} (${s.pct_hautes}%)`,
                  `<10 : ${s.basses} (${s.pct_basses}%)`
                ];
              }
            }
          }
        },
        scales: {
          y: {
            beginAtZero: true,
            max: 20,
            ticks: { stepSize: 2 }
          }
        }
      }
    });
  } catch(e) {
    console.error(e);
  }
}

async function loadElevesTable(){
  try {
    const res = await fetchJSON('api.php?action=eleves_moyenne');
    const tbody = document.querySelector('#eleves-table tbody');
    tbody.innerHTML = '';
    res.forEach(u => {
      const moyenne = parseFloat(u.moyenne) || 0;
      const emoji = moyenne >= 15 ? '🟢' : (moyenne >= 10 ? '🟡' : '🔴');
      const tr = document.createElement('tr');
      tr.innerHTML = `
        <td><a href="eleve.php?username=${encodeURIComponent(u.Username)}" target="_blank">${u.Username}</a></td>
        <td>${u.nom_de_classe || '—'}</td>
        <td>${moyenne.toFixed(2)} ${emoji}</td>
      `;
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

async function loadAlertesPedagogiques() {
  try {
    const alertes = await fetchJSON('api.php?action=alertes_pedagogiques');
    const tbody = document.getElementById('alertes-tbody');
    
    if (!alertes || alertes.length === 0) {
      tbody.innerHTML = `<tr><td colspan="3" class="text-center text-muted">Aucune alerte</td></tr>`;
      return;
    }

    tbody.innerHTML = '';
    alertes.forEach(a => {
      const note = parseFloat(a.note);
      const noteStr = `🔴 ${note.toFixed(1)}/20`;
      const tr = document.createElement('tr');
      tr.innerHTML = `
        <td>${noteStr}</td>
        <td>${a.Username} (${a.nom_de_classe || '—'})</td>
        <td>${a.matiere || '—'}</td>
      `;
      tbody.appendChild(tr);
    });
  } catch (err) {
    console.error('Erreur alertes:', err);
    document.getElementById('alertes-tbody').innerHTML = 
      `<tr><td colspan="3" class="text-center text-danger">❌ Erreur</td></tr>`;
  }
}

async function loadResumeClasse(classeId) {
  const container = document.getElementById('resume-classe');
  const content = document.getElementById('resume-content');

  if (!classeId) {
    container.style.display = 'none';
    return;
  }

  try {
    const res = await fetchJSON(`api.php?action=resume_classe&classe=${classeId}`);
    
    const moyenneEmoji = res.moyenne_generale >= 15 ? '🟢' : (res.moyenne_generale >= 10 ? '🟡' : '🔴');
    const meilleureEmoji = res.meilleure_moyenne >= 15 ? '🟢' : (res.meilleure_moyenne >= 10 ? '🟡' : '🔴');

    const items = [
      `🏫 <strong>${res.nom_classe}</strong>`,
      `👥 <strong>${res.nb_eleves}</strong> élève${res.nb_eleves > 1 ? 's' : ''}`,
      `📊 Moy. <strong>${res.moyenne_generale}</strong> ${moyenneEmoji}`,
      `📉 <strong>${res.nb_alertes}</strong> en alerte 🔴`,
      `📈 Meilleure : <strong>${res.meilleure_matiere}</strong> (${res.meilleure_moyenne} ${meilleureEmoji})`
    ];

    content.innerHTML = items.map(item => `<div>${item}</div>`).join('');
    container.style.display = 'block';

  } catch (err) {
    console.error('Erreur résumé classe:', err);
    container.style.display = 'none';
  }
}

let distributionChart = null;
async function loadDistributionChart(classeId, matiereId, examenId = null, semestreId = null) {
  const container = document.getElementById('distribution-chart-container');
  const chartEl = document.getElementById('distribution-chart');

  try {
    const params = new URLSearchParams({
      action: 'distribution_notes',
      classe: classeId,
      matiere: matiereId
    });
    if (examenId) params.append('examen', examenId);
    if (semestreId) params.append('semestre', semestreId);

    const tranches = await fetchJSON(`api.php?${params}`);
    if (tranches.error) throw new Error(tranches.error);

    const labels = tranches.map(t => t.label);
    const data = tranches.map(t => t.count);
    const colors = [
      'rgba(220,53,69,0.8)',   // 🔴 0–5
      'rgba(255,193,7,0.8)',   // 🟡 6–9
      'rgba(25,135,84,0.8)',   // 🟢 10–14
      'rgba(13,202,240,0.8)'   // 🟦 15–20
    ];

    const ctx = chartEl.getContext('2d');
    if (distributionChart) distributionChart.destroy();

    chartEl.width = 500;
    chartEl.height = 200;

    distributionChart = new Chart(ctx, {
      type: 'bar',
      data: {
        labels,
        datasets: [{
          label: 'Nombre de notes',
          data,
          backgroundColor: colors,
          borderColor: colors.map(c => c.replace('0.8', '1')),
          borderWidth: 1
        }]
      },
      options: {
        responsive: false,
        maintainAspectRatio: false,
        plugins: {
          legend: { display: false },
          tooltip: {
            callbacks: {
              label: function(context) {
                return `${context.parsed.y} note${context.parsed.y !== 1 ? 's' : ''}`;
              }
            }
          }
        },
        scales: {
          y: {
            beginAtZero: true,
            ticks: { stepSize: 1 }
          },
          x: {
            grid: { display: false }
          }
        }
      }
    });

    container.style.display = 'block';

  } catch (err) {
    console.error('Erreur distribution:', err);
    container.style.display = 'none';
  }
}

let comparisonChart = null;
async function loadComparisonChart() {
  const canvas = document.getElementById('comparison-chart');
  if (!canvas) {
    console.warn('Canvas #comparison-chart non trouvé. Action ignorée.');
    return;
  }

  const c1 = document.getElementById('compare-classe1')?.value;
  const c2 = document.getElementById('compare-classe2')?.value;
  const container = document.getElementById('comparison-chart-container');

  if (!c1 || !c2) {
    if (container) container.innerHTML = '<div class="text-center text-muted mt-3">Sélectionnez deux classes</div>';
    return;
  }

  try {
    const res = await fetchJSON(`api.php?action=comparaison_classes&classe1=${c1}&classe2=${c2}`);
    if (res.error) throw new Error(res.error);

    const ctx = canvas.getContext('2d');
    if (comparisonChart) comparisonChart.destroy();

    comparisonChart = new Chart(ctx, {
      type: 'bar',
      data: {
        labels: res.labels,
        datasets: [
          {
            label: res.classe1.nom,
            data: res.classe1.data,
            backgroundColor: 'rgba(14,119,112,0.8)',
            borderColor: 'rgba(14,119,112,1)',
            borderWidth: 1
          },
          {
            label: res.classe2.nom,
            data: res.classe2.data,
            backgroundColor: 'rgba(255,159,64,0.8)',
            borderColor: 'rgba(255,159,64,1)',
            borderWidth: 1
          }
        ]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        scales: {
          y: {
            beginAtZero: true,
            max: 20,
            ticks: { stepSize: 2 }
          }
        },
        plugins: {
          legend: { position: 'top' }
        }
      }
    });

  } catch (err) {
    if (container) {
      container.innerHTML = `<div class="text-center text-danger mt-3">❌ ${err.message || 'Erreur'}</div>`;
    }
    console.error('Erreur comparaison:', err);
  }
}

// Écouter les changements
document.getElementById('compare-classe1').addEventListener('change', loadComparisonChart);
document.getElementById('compare-classe2').addEventListener('change', loadComparisonChart);

// ✅ Réinitialiser pour éviter le déclenchement automatique
document.getElementById('compare-classe1').value = '';
document.getElementById('compare-classe2').value = '';

// Lancement
loadStats();
loadClassChart();
loadMatiereChart();
loadElevesTable();
loadLastItems();
loadAlertesPedagogiques();

});
</script>
</body>
</html>