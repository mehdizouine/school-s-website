<?php
require_once __DIR__.'/db.php';
require_once __DIR__.'/authorisation.php';
require_once __DIR__.'/config.php';
if(session_status() === PHP_SESSION_NONE) session_start();
require_login();

$username = $_GET['username'] ?? '';
if (!$username) { http_response_code(400); die('Élève non spécifié'); }

$stmt = $conn->prepare("
  SELECT l.ID, l.Username, c.nom_de_classe, p.Photo
  FROM login l
  LEFT JOIN classes c ON l.classe_id = c.ID
  LEFT JOIN profil p ON l.ID = p.ID
  WHERE l.Username = ? AND l.role = 'eleve'
");
$stmt->bind_param("s", $username);
$stmt->execute();
$eleve = $stmt->get_result()->fetch_assoc();
if (!$eleve) { http_response_code(404); die('Élève non trouvé'); }
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
:root {
  --primary:#0E7770; --primary-light:#1BD1C2; --primary-dark:#0A5A55;
  --success:#4CAF50; --success-light:#E8F5E9;
  --warning:#FF9800; --warning-light:#FFF3E0;
  --danger:#F44336; --danger-light:#FFEBEE;
  --info:#2196F3; --info-light:#E3F2FD;
  --gray-100:#f8f9fa; --gray-200:#e9ecef; --gray-700:#495057; --gray-900:#212529;
  --card-bg:white; --shadow:0 6px 16px rgba(0,0,0,0.08); --transition:all 0.3s cubic-bezier(0.4,0,0.2,1);
}
body {
  background: linear-gradient(135deg, #f5f7fa 0%, #e4edf5 100%);
  font-family:'Inter',sans-serif; color: var(--gray-900); margin:0; padding:0; line-height:1.6;
}
.container{max-width:1200px;margin:0 auto;padding:1.5rem;}
.card-profile {
  background: linear-gradient(135deg,var(--primary),var(--primary-light));
  color:white; border-radius:20px; padding:2.25rem 1.5rem; margin-bottom:2rem;
  box-shadow: var(--shadow); text-align:center; position:relative; overflow:hidden;
  transition: var(--transition);
}
.card-profile:hover{transform:translateY(-4px) scale(1.01); box-shadow:0 10px 24px rgba(0,0,0,0.15);}
.card-profile img{width:120px;height:120px;object-fit:cover;border-radius:50%;border:4px solid rgba(255,255,255,0.7);margin-bottom:1rem;}
.card-profile h1{font-weight:800;font-size:2.5rem;margin-bottom:0.5rem;letter-spacing:-0.5px;text-shadow:0 1px 2px rgba(0,0,0,0.15);}
.card-profile h4{font-weight:600; opacity:0.95; font-size:1.25rem; margin-bottom:1rem;}
.card-profile .btn{
  background:white;color:var(--primary-dark); font-weight:700;border:none;padding:0.6rem 1.5rem;
  border-radius:50px; transition: var(--transition); box-shadow:0 4px 12px rgba(0,0,0,0.15); font-size:1rem;
}
.card-profile .btn:hover{transform: translateY(-3px) scale(1.03); box-shadow:0 6px 18px rgba(0,0,0,0.2); background:#f8f9fa;}
.card{background: var(--card-bg); border-radius:18px; box-shadow:var(--shadow); margin-bottom:1.75rem; transition: var(--transition); border:1px solid rgba(0,0,0,0.03);}
.card:hover{transform:translateY(-4px); box-shadow:0 10px 24px rgba(0,0,0,0.12);}
.card-header{background:linear-gradient(to right,var(--primary-light),var(--primary)) !important; color:white !important; border:none; font-weight:700; padding:1.1rem 1.35rem; font-size:1.2rem; border-radius:18px 18px 0 0 !important; display:flex; align-items:center; gap:0.75rem;}
.card-body{padding:1.35rem;}
.chart-container{height:250px;width:100%; margin:1rem 0; position:relative;}
.table{margin-bottom:0; border-collapse:collapse;}
.table th{font-weight:700;color:var(--primary-dark); background-color:rgba(14,119,112,0.04); padding:0.75rem 0.85rem; text-align:left;}
.table td{padding:0.75rem 0.85rem; border-bottom:1px solid var(--gray-200);}
.table tbody tr:nth-child(even){background-color:rgba(14,119,112,0.03);}
.table tbody tr:hover{background-color:rgba(14,119,112,0.08); transition:0.2s;}
.alert-badge{display:inline-flex;align-items:center;padding:0.55rem 1.25rem;border-radius:50px;font-weight:700;margin:0.4rem;font-size:1.05rem;box-shadow:0 2px 6px rgba(0,0,0,0.08); opacity:0; transform:translateY(20px); animation:fadeInBadge 0.5s forwards;}
@keyframes fadeInBadge{to{opacity:1; transform:translateY(0);}}
.alert-danger-bg{background:var(--danger-light); color:var(--danger);}
.alert-success-bg{background:var(--success-light); color:var(--success);}
.alert-info-bg{background:var(--info-light); color:var(--info);}
.stat-general{background:var(--info-light); color:var(--info);}
#statut-body{display:flex; flex-wrap:wrap; align-items:center; gap:1rem; padding:0.5rem 0;}
#matiere-select{width:100%; padding:0.6rem 1rem; border-radius:12px; border:2px solid var(--gray-200); background-color:white; font-size:1rem; font-weight:600; color:var(--gray-900); appearance:none; background-image:url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 16 16'%3e%3cpath fill='none' stroke='%23495057' stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='m2 5 6 6 6-6'/%3e%3c/svg%3e"); background-repeat:no-repeat; background-position:right 1rem center; background-size:16px; padding-right:2.5rem;}
#matiere-select:hover,#matiere-select:focus{border-color:var(--primary-light); box-shadow:0 0 0 3px rgba(14,119,112,0.15); outline:none;}
canvas{transition:opacity 0.2s ease;}
canvas:hover{opacity:0.95;}
@media(max-width:991px){.chart-container{height:230px}.card-profile h1{font-size:2rem}.card-header{font-size:1.1rem}}
@media(max-width:767px){.container{padding:1rem}.card-profile{padding:1.75rem 1rem}.card-profile h1{font-size:1.75rem}.chart-container{height:210px}#statut-body{flex-direction:column;align-items:flex-start;gap:0.75rem}.alert-badge{width:100%;justify-content:center}}
</style>
</head>
<body>
<div class="container">

<!-- Carte profil élève -->
<div class="card-profile text-center py-4 mb-4 position-relative">
  <img src="<?= htmlspecialchars($eleve['Photo'] ?? 'default.png') ?>" alt="Photo élève">
  <h1><?= htmlspecialchars($eleve['Username']) ?></h1>
  <h4><?= htmlspecialchars($eleve['nom_de_classe'] ?? 'Classe inconnue') ?></h4>
  <span class="badge bg-light text-primary fw-bold position-absolute top-0 end-0 m-3">Élève</span>
  <a href="dashboard.php" class="btn mt-2">← Retour au dashboard</a>
</div>

<!-- Statistiques rapides -->
<div class="row g-3 mb-3" id="quick-stats"></div>

<div class="row">
  <!-- Moyennes par matière -->
  <div class="col-md-6">
    <div class="card mb-3 position-relative">
      <div class="card-header">📊 Moyennes par matière</div>
      <div class="card-body">
        <div class="chart-container"><canvas id="matiereChart"></canvas></div>
      </div>
    </div>
  </div>

  <!-- Évolution par matière -->
  <div class="col-md-6">
    <div class="card mb-3 position-relative">
      <div class="card-header">📈 Évolution (sélectionnez une matière)</div>
      <div class="card-body">
        <select id="matiere-select" class="form-select mb-2"></select>
        <div class="chart-container"><canvas id="evolutionChart"></canvas></div>
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
        <tbody id="notes-tbody"><tr><td colspan="4" class="text-center"><span class="spinner-border spinner-border-sm"></span> Chargement…</td></tr></tbody>
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
// ===================== VARIABLES =====================
const username = <?= json_encode($username) ?>;
const eleveId = <?= json_encode($eleveId) ?>;

// ===================== FETCH JSON =====================
async function fetchJSON(url){
  const res=await fetch(url);
  if(!res.ok)throw new Error(`HTTP ${res.status}`);
  return res.json();
}

// ===================== BADGES =====================
function makeBadge(label, type){
  const colorClass = type==='danger'?'alert-danger-bg':type==='success'?'alert-success-bg':'alert-info-bg';
  const span = document.createElement('span');
  span.className = `alert-badge ${colorClass}`;
  span.textContent = label;
  return span;
}

// ===================== QUICK STATS =====================
async function loadQuickStats(){
  try{
    const res = await fetchJSON(`api.php?action=eleve_statut&eleve_id=${eleveId}`);
    const container = document.getElementById('quick-stats'); container.innerHTML='';
    if(res.error) return;

    const stats = [
      {title:'Moyenne générale', value:`${res.moyenne_generale.toFixed(2)}/20`, color:'info-light'},
      {title:'Alertes <10', value:res.nb_alertes, color:'danger-light'},
      {title:'Excellentes ≥15', value:res.nb_excellentes, color:'success-light'}
    ];

    stats.forEach(s=>{
      const div = document.createElement('div');
      div.className='col-md-3';
      div.innerHTML=`<div class="card text-center stat-card p-3" style="background:${s.color};font-weight:600;"><h5>${s.title}</h5><p>${s.value}</p></div>`;
      container.appendChild(div);
    });

  }catch(err){console.error(err);}
}

// ===================== GRAPHIQUES =====================
let matiereChart=null;
async function loadMatiereChart(){
  try{
    const res=await fetchJSON(`api.php?action=eleve_moyennes_matiere&eleve_id=${eleveId}`);
    if(!res||res.length===0){document.getElementById('matiereChart').closest('.card-body').innerHTML='<p class="text-center text-muted">Aucune note</p>'; return;}
    res.sort((a,b)=>b.moyenne-a.moyenne);
    const labels=res.map(r=>r.matiere);
    const data=res.map(r=>r.moyenne);
    const colors=res.map(r=>r.moyenne>=15?'rgba(76,175,80,0.8)':r.moyenne>=10?'rgba(255,193,7,0.8)':'rgba(244,67,54,0.8)');
    const ctx=document.getElementById('matiereChart').getContext('2d');
    if(matiereChart) matiereChart.destroy();
    matiereChart=new Chart(ctx,{
      type:'bar',
      data:{labels,datasets:[{label:'Moyenne',data,backgroundColor:colors,borderColor:colors.map(c=>c.replace('0.8','1')),borderWidth:1}]},
      options:{indexAxis:'y',responsive:true,maintainAspectRatio:false,
        scales:{x:{beginAtZero:true,max:20,ticks:{stepSize:2}}},
        plugins:{legend:{display:false},tooltip:{callbacks:{label:ctx=>`${ctx.parsed.x.toFixed(2)}/20 ${ctx.parsed.x>=15?'🟢':ctx.parsed.x>=10?'🟡':'🔴'}`}}}
      }
    });
  }catch(err){console.error(err);document.getElementById('matiereChart').closest('.card-body').innerHTML='<p class="text-center text-danger">Erreur</p>';}
}

let evolutionChart=null;
async function loadEvolutionChart(matiereId){
  const container=document.getElementById('evolutionChart').closest('.card-body');
  const canvas=document.getElementById('evolutionChart');
  if(!matiereId){container.innerHTML='<p class="text-center text-muted">Sélectionnez une matière</p>'; return;}
  try{
    const res=await fetchJSON(`api.php?action=eleve_evolution_matiere&eleve_id=${eleveId}&matiere_id=${matiereId}`);
    if(!res||res.length===0){container.innerHTML='<p class="text-center text-muted">Aucune note dans cette matière</p>'; return;}
    const labels=res.map(r=>r.nom_examen||`Note ${r.ID_note}`);
    const data=res.map(r=>r.note);
    const ctx=canvas.getContext('2d');
    if(evolutionChart) evolutionChart.destroy();
    evolutionChart=new Chart(ctx,{
      type:'line',
      data:{labels,datasets:[{label:'Note',data,borderColor:'rgba(14,119,112,1)',backgroundColor:'rgba(14,119,112,0.1)',borderWidth:2,fill:true,tension:0.3,pointRadius:5,pointBackgroundColor:'white'}]},
      options:{responsive:true,maintainAspectRatio:false,scales:{y:{beginAtZero:true,max:20,ticks:{stepSize:2}},x:{grid:{display:false}}},plugins:{legend:{display:false},tooltip:{callbacks:{label:ctx=>`Note : ${ctx.parsed.y.toFixed(1)}/20`,title:tip=>tip[0].label}}}}
    });
  }catch(err){console.error(err);container.innerHTML='<p class="text-center text-danger">❌ Erreur</p>';}
}

async function loadMatiereSelect(){
  try{
    const res=await fetchJSON(`api.php?action=eleve_moyennes_matiere&eleve_id=${eleveId}`);
    const select=document.getElementById('matiere-select'); select.innerHTML='<option value="">Sélectionnez une matière</option>';
    res.forEach(m=>{const opt=document.createElement('option');opt.value=m.ID_matiere;opt.textContent=m.matiere;select.appendChild(opt);});
    select.addEventListener('change',e=>loadEvolutionChart(e.target.value));
  }catch(err){console.error(err);document.getElementById('matiere-select').innerHTML='<option>Erreur</option>';}
}

// ===================== DERNIERES NOTES =====================
async function loadDernieresNotes(){
  try{
    const res=await fetchJSON(`api.php?action=eleve_dernieres_notes&eleve_id=${eleveId}`);
    const tbody=document.getElementById('notes-tbody');
    if(res.error||res.length===0){tbody.innerHTML=`<tr><td colspan="4" class="text-center text-muted">Aucune note récente</td></tr>`; return;}
    tbody.innerHTML='';
    res.forEach(n=>{
      const emoji=n.note>=15?'🟢':n.note>=10?'🟡':'🔴';
      const tr=document.createElement('tr'); 
      tr.innerHTML=`<td>${n.matiere||'—'}</td><td>${n.nom_examen||'—'}</td><td>${n.note.toFixed(1)}/20 ${emoji}</td><td>${n.nom_semestre||'—'}</td>`;
      tbody.appendChild(tr);
    });
  }catch(err){console.error(err);}
}

// ===================== STATUT PEDAGOGIQUE =====================
async function loadStatutPedagogique() {
  try {
    const res = await fetchJSON(`api.php?action=eleve_statut&eleve_id=${eleveId}`);
    const container = document.getElementById('statut-body');

    if (!res || typeof res.nb_alertes === 'undefined') {
      container.innerHTML = '<p class="text-center text-muted">Aucune donnée disponible</p>';
      return;
    }

    container.innerHTML = `
      <div class="row g-3">
        <!-- Alertes -->
        <div class="col-md-4 col-6">
          <div class="d-flex align-items-start border-start border-danger border-5 ps-3 py-2 h-100">
            <div class="me-3"><span class="fs-4">🔴</span></div>
            <div>
              <div class="fw-bold fs-5">${res.nb_alertes || 0}</div>
              <div class="text-muted small">Matières en alerte (&lt;10)</div>
            </div>
          </div>
        </div>

        <!-- Excellentes -->
        <div class="col-md-4 col-6">
          <div class="d-flex align-items-start border-start border-success border-5 ps-3 py-2 h-100">
            <div class="me-3"><span class="fs-4">🟢</span></div>
            <div>
              <div class="fw-bold fs-5">${res.nb_excellentes || 0}</div>
              <div class="text-muted small">Matières excellentes (≥15)</div>
            </div>
          </div>
        </div>

        <!-- Meilleure matière -->
        <div class="col-md-4 col-6">
          <div class="d-flex align-items-start border-start border-primary border-5 ps-3 py-2 h-100">
            <div class="me-3"><span class="fs-4">⭐</span></div>
            <div>
              <div class="fw-bold fs-6">${res.matiere_top || '—'}</div>
              <div class="text-muted small">Meilleure matière ${res.moyenne_top ? `(${res.moyenne_top}/20)` : ''}</div>
            </div>
          </div>
        </div>

        <!-- Matière à renforcer -->
        <div class="col-md-4 col-6">
          <div class="d-flex align-items-start border-start border-warning border-5 ps-3 py-2 h-100">
            <div class="me-3"><span class="fs-4">⚠️</span></div>
            <div>
              <div class="fw-bold fs-6">${res.matiere_faible || '—'}</div>
              <div class="text-muted small">À renforcer ${res.moyenne_faible ? `(${res.moyenne_faible}/20)` : ''}</div>
            </div>
          </div>
        </div>

        <!-- Classement -->
        <div class="col-md-4 col-6">
          <div class="d-flex align-items-start border-start border-info border-5 ps-3 py-2 h-100">
            <div class="me-3"><span class="fs-4">🏆</span></div>
            <div>
              <div class="fw-bold fs-5">${res.classement || '—'}/${res.total_eleves || '—'}</div>
              <div class="text-muted small">Classement dans la classe</div>
            </div>
          </div>
        </div>

        <!-- Devoirs rendus -->
        <div class="col-md-4 col-6">
          <div class="d-flex align-items-start border-start border-success border-5 ps-3 py-2 h-100">
            <div class="me-3"><span class="fs-4">✅</span></div>
            <div>
              <div class="fw-bold fs-5">${res.nb_devoirs_rendus || 0}</div>
              <div class="text-muted small">Devoirs rendus</div>
            </div>
          </div>
        </div>

        <!-- Devoirs manquants -->
        <div class="col-md-4 col-6">
          <div class="d-flex align-items-start border-start border-danger border-5 ps-3 py-2 h-100">
            <div class="me-3"><span class="fs-4">❌</span></div>
            <div>
              <div class="fw-bold fs-5">${res.nb_devoirs_non_rendus || 0}</div>
              <div class="text-muted small">Devoirs manquants</div>
            </div>
          </div>
        </div>
      </div>
    `;
  } catch (err) {
    console.error(err);
    document.getElementById('statut-body').innerHTML = '<p class="text-center text-danger">❌ Erreur de chargement</p>';
  }
}


// ===================== INIT =====================
loadQuickStats();
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
  /* ——— Amélioration visuelle du statut pédagogique ——— */
#statut-body .border-start {
  transition: transform 0.2s ease;
}
#statut-body .border-start:hover {
  transform: translateX(4px);
}
</style>