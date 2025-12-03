<?php
require_once __DIR__.'/db.php';
require_once __DIR__.'/authorisation.php';
require_once __DIR__.'/config.php';
if(session_status() === PHP_SESSION_NONE) session_start();
require_login();

$username = $_GET['username'] ?? '';
if (!$username) {
  http_response_code(400);
  die('Élève non spécifié');
}

$stmt = $conn->prepare("
  SELECT l.ID, l.Username, c.nom_de_classe
  FROM login l
  LEFT JOIN classes c ON l.classe_id = c.ID
  WHERE l.Username = ? AND l.role = 'eleve'
");
$stmt->bind_param("s", $username);
$stmt->execute();
$eleve = $stmt->get_result()->fetch_assoc();

if (!$eleve) {
  http_response_code(404);
  die('Élève non trouvé');
}
$eleveId = $eleve['ID'];
?>

<!doctype html>
<html lang="fr">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= htmlspecialchars($eleve['Username']) ?> - <?= htmlspecialchars(SITE_NAME) ?></title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
  <style>
    body {
      background: #f8f9fa;
      padding: 1.5rem;
    }
    .card-profile {
      background: linear-gradient(135deg, #0E7770 0%, #1BD1C2 100%);
      color: white;
      margin-bottom: 1.5rem;
    }
    .chart-container {
      height: 250px;
      margin: 1rem 0;
    }
    .stat-badge {
      display: inline-block;
      padding: 0.25rem 0.75rem;
      border-radius: 20px;
      font-weight: 600;
      margin: 0.25rem;
    }
    .alert-red { background: #ffebee; color: #c62828; }
    .alert-green { background: #e8f5e9; color: #2e7d32; }
  </style>
</head>
<body>

<div class="container">
  <!-- En-tête élève -->
  <div class="card-profile text-center py-4 mb-4">
    <h1><?= htmlspecialchars($eleve['Username']) ?></h1>
    <h4><?= htmlspecialchars($eleve['nom_de_classe'] ?? 'Classe inconnue') ?></h4>
    <a href="dashboard.php" class="btn btn-light mt-2">← Retour au dashboard</a>
  </div>

  <div class="row">
    <!-- Graphique : Moyennes par matière -->
    <div class="col-md-6">
      <div class="card mb-3">
        <div class="card-header">
          📊 Moyennes par matière
        </div>
        <div class="card-body">
          <div class="chart-container">
            <canvas id="matiereChart"></canvas>
          </div>
        </div>
      </div>
    </div>

    <!-- Évolution dans une matière -->
    <div class="col-md-6">
      <div class="card mb-3">
        <div class="card-header">
          📈 Évolution (sélectionnez une matière)
        </div>
        <div class="card-body">
          <select id="matiere-select" class="form-select form-select-sm mb-2">
            <option value="">Chargement...</option>
          </select>
          <div class="chart-container">
            <canvas id="evolutionChart"></canvas>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Dernières notes -->
  <div class="card mb-3">
    <div class="card-header">📝 Dernières notes</div>
    <div class="card-body">
      <div class="table-responsive">
        <table class="table table-sm">
          <thead><tr><th>Matière</th><th>Examen</th><th>Note</th><th>Semestre</th></tr></thead>
          <tbody id="notes-tbody">
            <tr><td colspan="4" class="text-center"><span class="spinner-border spinner-border-sm"></span> Chargement…</td></tr>
          </tbody>
        </table>
      </div>
    </div>
  </div>

  <!-- Statut pédagogique -->
  <div class="card mb-3">
    <div class="card-header">🔔 Statut pédagogique</div>
    <div class="card-body" id="statut-body">
      <div class="text-center"><span class="spinner-border spinner-border-sm"></span> Analyse en cours…</div>
    </div>
  </div>
</div>

<script>
async function fetchJSON(url) {
  const res = await fetch(url);
  if (!res.ok) throw new Error(`HTTP ${res.status}`);
  return res.json();
}

const username = <?= json_encode($username) ?>;
const eleveId = <?= json_encode($eleveId) ?>;

let matiereChart = null;
async function loadMatiereChart() {
  try {
    const res = await fetchJSON(`api.php?action=eleve_moyennes_matiere&eleve_id=${eleveId}`);
    if (!res || res.length === 0) {
      document.getElementById('matiereChart').closest('.card-body').innerHTML = '<p class="text-center text-muted">Aucune note</p>';
      return;
    }

    // Trier par moyenne décroissante
    res.sort((a, b) => b.moyenne - a.moyenne);
    
    const labels = res.map(r => r.matiere);
    const data = res.map(r => r.moyenne);
    const colors = res.map(r => 
      r.moyenne >= 15 ? 'rgba(76, 175, 80, 0.8)' :
      r.moyenne >= 10 ? 'rgba(255, 193, 7, 0.8)' :
      'rgba(244, 67, 54, 0.8)'
    );

    const ctx = document.getElementById('matiereChart').getContext('2d');
    if (matiereChart) matiereChart.destroy();

    matiereChart = new Chart(ctx, {
      type: 'bar',
      data: {
        labels,
        datasets: [{
          label: 'Moyenne',
          data,
          backgroundColor: colors,
          borderColor: colors.map(c => c.replace('0.8', '1')),
          borderWidth: 1
        }]
      },
      options: {
        indexAxis: 'y', // barres horizontales
        responsive: true,
        maintainAspectRatio: false,
        scales: {
          x: {
            beginAtZero: true,
            max: 20,
            ticks: { stepSize: 2 }
          }
        },
        plugins: {
          legend: { display: false },
          tooltip: {
            callbacks: {
              label: function(context) {
                const avg = context.parsed.x;
                const emoji = avg >= 15 ? '🟢' : (avg >= 10 ? '🟡' : '🔴');
                return `Moyenne : ${avg.toFixed(2)}/20 ${emoji}`;
              }
            }
          }
        }
      }
    });
  } catch (err) {
    console.error(err);
    document.getElementById('matiereChart').closest('.card-body').innerHTML = '<p class="text-center text-danger">Erreur</p>';
  }
}

let evolutionChart = null;
async function loadEvolutionChart(matiereId) {
  const container = document.getElementById('evolutionChart').closest('.card-body');
  const canvas = document.getElementById('evolutionChart');
  
  if (!matiereId) {
    container.innerHTML = '<p class="text-center text-muted">Sélectionnez une matière</p>';
    return;
  }

  try {
    const res = await fetchJSON(`api.php?action=eleve_evolution_matiere&eleve_id=${eleveId}&matiere_id=${matiereId}`);
    if (!res || res.length === 0) {
      container.innerHTML = '<p class="text-center text-muted">Aucune note dans cette matière</p>';
      return;
    }

    // Labels = noms d'examens (ou fallback)
    const labels = res.map(r => r.nom_examen || `Note ${r.ID_note}`);
    const data = res.map(r => r.note);

    const ctx = canvas.getContext('2d');
    if (evolutionChart) evolutionChart.destroy();

    evolutionChart = new Chart(ctx, {
      type: 'line',
       data:{
        labels,
        datasets: [{
          label: 'Note',
           data,
          borderColor: 'rgba(14,119,112,1)',
           backgroundColor: 'rgba(14,119,112,0.1)',
           borderWidth: 2,
           fill: true,
           tension: 0.3,
           pointRadius: 5,
           pointBackgroundColor: 'white'
        }]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        scales: {
          y: {
            beginAtZero: true,
            max: 20,
            ticks: { stepSize: 2 }
          },
          x: {
            grid: { display: false }
          }
        },
        plugins: {
          legend: { display: false },
          tooltip: {
            callbacks: {
              label: function(context) {
                return `Note : ${context.parsed.y.toFixed(1)}/20`;
              },
              title: function(tooltipItems) {
                return tooltipItems[0].label; // nom de l'examen
              }
            }
          }
        }
      }
    });

  } catch (err) {
    console.error('Erreur évolution:', err);
    container.innerHTML = '<p class="text-center text-danger">❌ Erreur</p>';
  }
}

// Charger la liste des matières pour le select
async function loadMatiereSelect() {
  try {
    const res = await fetchJSON(`api.php?action=eleve_moyennes_matiere&eleve_id=${eleveId}`);
    const select = document.getElementById('matiere-select');
    select.innerHTML = '<option value="">Sélectionnez une matière</option>';
    res.forEach(m => {
      const opt = document.createElement('option');
      opt.value = m.ID_matiere; // ✅ utiliser l'ID, pas le nom
      opt.textContent = m.matiere;
      select.appendChild(opt);
    });
    select.addEventListener('change', e => {
      loadEvolutionChart(e.target.value);
    });
  } catch (err) {
    console.error(err);
    document.getElementById('matiere-select').innerHTML = '<option>Erreur</option>';
  }
}

// Dernières notes
async function loadDernieresNotes() {
  try {
    const res = await fetchJSON(`api.php?action=eleve_dernieres_notes&eleve_id=${eleveId}`);
    const tbody = document.getElementById('notes-tbody');
    if (res.error || res.length === 0) {
      tbody.innerHTML = `<tr><td colspan="4" class="text-center text-muted">Aucune note récente</td></tr>`;
      return;
    }
    tbody.innerHTML = '';
    res.forEach(n => {
      const emoji = n.note >= 15 ? '🟢' : (n.note >= 10 ? '🟡' : '🔴');
      const tr = document.createElement('tr');
      tr.innerHTML = `
        <td>${n.matiere || '—'}</td>
        <td>${n.nom_examen || '—'}</td>
        <td>${n.note.toFixed(1)}/20 ${emoji}</td>
        <td>${n.nom_semestre || '—'}</td>
      `;
      tbody.appendChild(tr);
    });
  } catch (err) {
    document.getElementById('notes-tbody').innerHTML = `<tr><td colspan="4" class="text-center text-danger">Erreur</td></tr>`;
  }
}

// Statut pédagogique
async function loadStatutPedagogique() {
  try {
    const res = await fetchJSON(`api.php?action=eleve_statut&eleve_id=${eleveId}`);
    const el = document.getElementById('statut-body');
    if (res.error) {
      el.innerHTML = `<div class="text-danger">Impossible d’analyser le statut.</div>`;
      return;
    }

    let html = '';
    if (res.nb_alertes > 0) {
      html += `<div class="alert alert-red"><strong>🔴 Alertes :</strong> ${res.nb_alertes} matière(s) avec moyenne &lt; 10</div>`;
    }
    if (res.nb_excellentes > 0) {
      html += `<div class="alert alert-green"><strong>🟢 Excellentes :</strong> ${res.nb_excellentes} matière(s) avec moyenne ≥ 15</div>`;
    }
    html += `<p><strong>Moyenne générale :</strong> ${res.moyenne_generale.toFixed(2)}/20</p>`;
    if (!html) html = `<p class="text-muted">Statut stable — aucune alerte ni excellence détectée.</p>`;

    el.innerHTML = html;
  } catch (err) {
    document.getElementById('statut-body').innerHTML = `<div class="text-danger">Erreur d’analyse.</div>`;
  }
}

// Lancement
loadMatiereChart();
loadMatiereSelect();
loadDernieresNotes();
loadStatutPedagogique();
</script>
</body>
</html>
<style>
  :root {
    --primary: #0E7770;
    --primary-light: #1BD1C2;
    --primary-dark: #0A5A55;
    --success: #4CAF50;
    --success-light: #E8F5E9;
    --warning: #FF9800;
    --warning-light: #FFF3E0;
    --danger: #F44336;
    --danger-light: #FFEBEE;
    --info: #2196F3;
    --info-light: #E3F2FD;
    --purple: #9C27B0;
    --gray-100: #f8f9fa;
    --gray-200: #e9ecef;
    --gray-700: #495057;
    --gray-900: #212529;
    --card-bg: white;
    --shadow: 0 6px 16px rgba(0,0,0,0.08);
    --transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
  }

  body {
    background: linear-gradient(135deg, #f5f7fa 0%, #e4edf5 100%);
    font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
    color: var(--gray-900);
    margin: 0;
    padding: 0;
    line-height: 1.6;
  }

  .container {
    max-width: 1200px;
    margin: 0 auto;
    padding: 1.5rem;
  }

  /* ====== En-tête élève ====== */
  .card-profile {
    background: linear-gradient(135deg, var(--primary) 0%, var(--primary-light) 100%);
    color: white;
    border-radius: 20px;
    padding: 2.25rem 1.5rem;
    margin-bottom: 2rem;
    box-shadow: var(--shadow);
    text-align: center;
    position: relative;
    overflow: hidden;
  }

  .card-profile::before {
    content: "";
    position: absolute;
    top: -50%;
    right: -50%;
    width: 100%;
    height: 200%;
    background: radial-gradient(circle, rgba(255,255,255,0.1) 0%, transparent 70%);
    transform: rotate(30deg);
  }

  .card-profile h1 {
    font-weight: 800;
    font-size: 2.5rem;
    margin-bottom: 0.5rem;
    letter-spacing: -0.5px;
    text-shadow: 0 1px 2px rgba(0,0,0,0.15);
  }

  .card-profile h4 {
    font-weight: 600;
    opacity: 0.95;
    font-size: 1.25rem;
    margin-bottom: 1.5rem;
  }

  .card-profile .btn {
    background: white;
    color: var(--primary-dark);
    font-weight: 700;
    border: none;
    padding: 0.6rem 1.5rem;
    border-radius: 50px;
    transition: var(--transition);
    box-shadow: 0 4px 12px rgba(0,0,0,0.15);
    font-size: 1rem;
  }

  .card-profile .btn:hover {
    transform: translateY(-3px) scale(1.03);
    box-shadow: 0 6px 18px rgba(0,0,0,0.2);
    background: #f8f9fa;
  }

  /* ====== Cartes ====== */
  .card {
    background: var(--card-bg);
    border-radius: 18px;
    box-shadow: var(--shadow);
    margin-bottom: 1.75rem;
    transition: var(--transition);
    border: 1px solid rgba(0,0,0,0.03);
  }

  .card:hover {
    transform: translateY(-4px);
    box-shadow: 0 10px 24px rgba(0,0,0,0.12);
  }

  .card-header {
    background: linear-gradient(to right, var(--primary-light), var(--primary)) !important;
    color: white !important;
    border: none;
    font-weight: 700;
    padding: 1.1rem 1.35rem;
    font-size: 1.2rem;
    border-radius: 18px 18px 0 0 !important;
    display: flex;
    align-items: center;
    gap: 0.75rem;
  }

  .card-header i {
    font-size: 1.4rem;
  }

  .card-body {
    padding: 1.35rem;
  }

  /* ====== Graphiques ====== */
  .chart-container {
    height: 250px;
    width: 100%;
    margin: 1rem 0;
    position: relative;
  }

  /* ====== Tableau ====== */
  .table {
    margin-bottom: 0;
    border-collapse: collapse;
  }

  .table th {
    font-weight: 700;
    color: var(--primary-dark);
    background-color: rgba(14, 119, 112, 0.04);
    padding: 0.75rem 0.85rem;
    text-align: left;
  }

  .table td {
    padding: 0.75rem 0.85rem;
    border-bottom: 1px solid var(--gray-200);
  }

  .table tr:last-child td {
    border-bottom: none;
  }

  /* ====== Statut pédagogique ====== */
  .alert-badge {
    display: inline-flex;
    align-items: center;
    padding: 0.55rem 1.25rem;
    border-radius: 50px;
    font-weight: 700;
    margin: 0.4rem;
    font-size: 1.05rem;
    box-shadow: 0 2px 6px rgba(0,0,0,0.08);
  }

  .alert-danger-bg {
    background: var(--danger-light);
    color: var(--danger);
  }

  .alert-success-bg {
    background: var(--success-light);
    color: var(--success);
  }

  .stat-general {
    background: var(--info-light);
    color: var(--info);
  }

  #statut-body {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: 1rem;
    padding: 0.5rem 0;
  }

  /* ====== Sélecteur matière ====== */
  #matiere-select {
    width: 100%;
    padding: 0.6rem 1rem;
    border-radius: 12px;
    border: 2px solid var(--gray-200);
    background-color: white;
    font-size: 1rem;
    font-weight: 600;
    color: var(--gray-900);
    transition: var(--transition);
    appearance: none;
    background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 16 16'%3e%3cpath fill='none' stroke='%23495057' stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='m2 5 6 6 6-6'/%3e%3c/svg%3e");
    background-repeat: no-repeat;
    background-position: right 1rem center;
    background-size: 16px;
    padding-right: 2.5rem;
  }

  #matiere-select:hover,
  #matiere-select:focus {
    border-color: var(--primary-light);
    box-shadow: 0 0 0 3px rgba(14, 119, 112, 0.15);
    outline: none;
  }

  /* ====== Responsive ====== */
  @media (max-width: 991px) {
    .chart-container {
      height: 230px;
    }
    .card-profile h1 {
      font-size: 2rem;
    }
    .card-header {
      font-size: 1.1rem;
    }
  }

  @media(max-width: 767px) {
    .container {
      padding: 1rem;
    }
    .card-profile {
      padding: 1.75rem 1rem;
    }
    .card-profile h1 {
      font-size: 1.75rem;
    }
    .chart-container {
      height: 210px;
    }
    #statut-body {
      flex-direction: column;
      align-items: flex-start;
      gap: 0.75rem;
    }
    .alert-badge {
      width: 100%;
      justify-content: center;
    }
  }

  /* ====== Loading ====== */
  .spinner-border-sm {
    width: 1.1rem;
    height: 1.1rem;
    border-width: 2px;
  }

  /* ====== Micro-interactions ====== */
  canvas {
    transition: opacity 0.2s ease;
  }

  canvas:hover {
    opacity: 0.95;
  }
</style>