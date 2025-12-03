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