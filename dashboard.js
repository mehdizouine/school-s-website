// =============== UTILITAIRE : requête AJAX =================
function fetchJSON(url, callback, errorCallback) {
  var xhr = new XMLHttpRequest();
  xhr.open('GET', url, true);
  xhr.setRequestHeader('Content-Type', 'application/json');
  xhr.onreadystatechange = function() {
    if (xhr.readyState === 4) {
      if (xhr.status >= 200 && xhr.status < 300) {
        try {
          var data = JSON.parse(xhr.responseText);
          callback(data);
        } catch (e) {
          if (errorCallback) errorCallback(new Error('JSON invalide'));
        }
      } else {
        if (errorCallback) errorCallback(new Error('Erreur ' + xhr.status));
      }
    }
  };
  xhr.onerror = function() {
    if (errorCallback) errorCallback(new Error('Erreur réseau'));
  };
  xhr.send();
}

// =============== CONFIGURATION =================
function loadUserConfig() {
  var configText = localStorage.getItem('dashboardConfig');
  var config = configText ? JSON.parse(configText) : {
    progression: true,
    derniers: true,
    actions: true
  };
  
  document.getElementById('show-progression').checked = config.progression;
  document.getElementById('show-derniers').checked = config.derniers;
  document.getElementById('show-actions').checked = config.actions;

  document.getElementById('progression-section').style.display = config.progression ? 'block' : 'none';
  document.getElementById('last-items-section').style.display = config.derniers ? 'block' : 'none';
  document.getElementById('actions-section').style.display = config.actions ? 'block' : 'none';
}

var configInputs = document.querySelectorAll('#configModal input');
for (var i = 0; i < configInputs.length; i++) {
  configInputs[i].addEventListener('change', function() {
    var config = {
      progression: document.getElementById('show-progression').checked,
      derniers: document.getElementById('show-derniers').checked,
      actions: document.getElementById('show-actions').checked
    };
    localStorage.setItem('dashboardConfig', JSON.stringify(config));
    loadUserConfig();
  });
}

// =============== FILTRES =================
document.getElementById('filter-classe-main').addEventListener('change', function(e) {
  var enabled = !!e.target.value;
  document.getElementById('filter-matiere').disabled = !enabled;
  document.getElementById('filter-examen').disabled = true;
  document.getElementById('filter-semestre').disabled = true;
  var resetIds = ['filter-matiere','filter-examen','filter-semestre'];
  for (var j = 0; j < resetIds.length; j++) {
    document.getElementById(resetIds[j]).value = '';
  }
  document.getElementById('notes-detail-section').style.display = 'none';
  document.getElementById('progression-section').style.display = 'none';
  
  // 🔑 Détruire les graphiques détaillés
  if (window.examNotesChart) {
    window.examNotesChart.destroy();
    window.examNotesChart = null;
  }
  if (window.progressionChart) {
    window.progressionChart.destroy();
    window.progressionChart = null;
  }
});

document.getElementById('filter-matiere').addEventListener('change', function(e) {
  var enabled = !!e.target.value;
  document.getElementById('filter-examen').disabled = !enabled;
  document.getElementById('filter-semestre').disabled = !enabled;
  var resetIds = ['filter-examen','filter-semestre'];
  for (var j = 0; j < resetIds.length; j++) {
    document.getElementById(resetIds[j]).value = '';
  }
  
  var classeId = document.getElementById('filter-classe-main').value;
  var matiereId = e.target.value;
  
  loadNotesForClassAndMatiere();
  loadProgressionChart(classeId, matiereId);
});

document.getElementById('filter-examen').addEventListener('change', loadNotesForClassAndMatiere);
document.getElementById('filter-semestre').addEventListener('change', loadNotesForClassAndMatiere);

// =============== CHARGEMENT DES NOTES DÉTAILLÉES =================
var examNotesChart = null;

function loadNotesForClassAndMatiere() {
  var classeId = document.getElementById('filter-classe-main').value;
  var matiereId = document.getElementById('filter-matiere').value;
  var examenId = document.getElementById('filter-examen').value;
  var semestreId = document.getElementById('filter-semestre').value;

  if (!classeId || !matiereId) {
    document.getElementById('notes-detail-section').style.display = 'none';
    return;
  }

  var loadingEl = document.getElementById('loading-msg');
  var chartEl = document.getElementById('exam-notes-chart');
  var tbody = document.getElementById('exam-notes-tbody');
  var noteCountEl = document.getElementById('note-count');

  // 🔑 Vérification de l'existence du canvas
  if (!chartEl) return;

  loadingEl.style.display = 'block';
  chartEl.style.display = 'block';
  tbody.innerHTML = '';

  var url = 'api.php?action=notes_classe_matiere&classe=' + encodeURIComponent(classeId) + '&matiere=' + encodeURIComponent(matiereId);
  if (examenId) url += '&examen=' + encodeURIComponent(examenId);
  if (semestreId) url += '&semestre=' + encodeURIComponent(semestreId);

  fetchJSON(url,
    function(res) {
      if (res.error) {
        tbody.innerHTML = '<tr><td colspan="4" class="text-center text-danger">❌ ' + (res.error || 'Erreur') + '</td></tr>';
        return;
      }

      var notesByEleve = {};
      for (var i = 0; i < res.length; i++) {
        var note = res[i];
        if (!notesByEleve[note.Username]) {
          notesByEleve[note.Username] = [];
        }
        notesByEleve[note.Username].push(note);
      }

      var elevesArray = [];
      for (var name in notesByEleve) {
        if (notesByEleve.hasOwnProperty(name)) {
          var notes = notesByEleve[name];
          var sum = 0;
          for (var k = 0; k < notes.length; k++) {
            sum += parseFloat(notes[k].note);
          }
          var avg = sum / notes.length;
          elevesArray.push({ name: name, notes: notes, avg: avg });
        }
      }

      elevesArray.sort(function(a, b) {
        return b.avg - a.avg;
      });

      tbody.innerHTML = '';
      for (var idx = 0; idx < elevesArray.length; idx++) {
        var item = elevesArray[idx];
        for (var nIdx = 0; nIdx < item.notes.length; nIdx++) {
          var note = item.notes[nIdx];
          var tr = document.createElement('tr');
          tr.innerHTML = 
            '<td>' + (note.Username || '—') + '</td>' +
            '<td>' + (note.nom_examen || '—') + '</td>' +
            '<td>' + (parseFloat(note.note).toFixed(1) || '—') + '/20</td>' +
            '<td>' + (note.nom_semestre || '—') + '</td>';
          tbody.appendChild(tr);
        }
      }

      var classeNom = document.querySelector('#filter-classe-main option[value="' + classeId + '"]').textContent;
      var matiereNom = document.querySelector('#filter-matiere option[value="' + matiereId + '"]').textContent;
      document.getElementById('detail-title').textContent = classeNom + ' — ' + matiereNom;
      noteCountEl.textContent = res.length + ' note' + (res.length > 1 ? 's' : '');

      // 🔑 Vérification avant création
      var ctx = chartEl.getContext('2d');
      if (examNotesChart && examNotesChart.canvas === chartEl) {
        examNotesChart.destroy();
      }
      examNotesChart = new Chart(ctx, {
        type: 'bar',
         {
          labels: elevesArray.map(function(e) { return e.name; }),
          datasets: [{
            label: 'Moyenne',
             elevesArray.map(function(e) { return parseFloat(e.avg.toFixed(2)); }),
            backgroundColor: 'rgba(14,119,112,0.8)',
            borderColor: 'rgba(14,119,112,1)',
            borderWidth: 1
          }]
        },
        options: {
          responsive: true,
          maintainAspectRatio: false,
          scales: {
            y: {
              beginAtZero: true,
              max: 20
            }
          },
          plugins: {
            legend: {
              display: false
            }
          }
        }
      });

      document.getElementById('notes-detail-section').style.display = 'block';
    },
    function(err) {
      tbody.innerHTML = '<tr><td colspan="4" class="text-center text-danger">❌ ' + (err.message || 'Erreur') + '</td></tr>';
      console.error(err);
    }
  );
}

// =============== PROGRESSION PAR EXAMEN =================
var progressionChart = null;

function loadProgressionChart(classeId, matiereId) {
  if (!classeId || !matiereId) {
    document.getElementById('progression-section').style.display = 'none';
    return;
  }

  var chartEl = document.getElementById('progressionChart');
  if (!chartEl) return;

  var url = 'api.php?action=notes_par_examen&classe=' + encodeURIComponent(classeId) + '&matiere=' + encodeURIComponent(matiereId);

  fetchJSON(url,
    function(res) {
      if (res.error || !res.length) {
        document.getElementById('progression-section').style.display = 'none';
        return;
      }

      var ctx = chartEl.getContext('2d');
      if (progressionChart && progressionChart.canvas === chartEl) {
        progressionChart.destroy();
      }

      progressionChart = new Chart(ctx, {
        type: 'line',
         {
          labels: res.map(function(r) { return r.nom_examen; }),
          datasets: [{
            label: 'Moyenne par examen',
             res.map(function(r) { return r.moyenne; }),
            borderColor: 'rgba(14,119,112,1)',
            backgroundColor: 'rgba(14,119,112,0.1)',
            borderWidth: 3,
            fill: true,
            tension: 0.3
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
                  var item = res[context.dataIndex];
                  return [
                    'Moyenne : ' + context.parsed.y.toFixed(2),
                    'Nombre de notes : ' + item.nb_notes
                  ];
                }
              }
            }
          },
          scales: {
            y: {
              beginAtZero: true,
              max: 20
            }
          }
        }
      });

      document.getElementById('progression-section').style.display = 'block';
    },
    function(err) {
      console.error(err);
      document.getElementById('progression-section').style.display = 'none';
    }
  );
}

// =============== CHARGEMENT INITIAL =================
function loadStats() {
  var ids = ['users-count', 'classes-count', 'notes-count', 'devoirs-count'];
  for (var i = 0; i < ids.length; i++) {
    var el = document.getElementById(ids[i]);
    if (el) el.classList.add('loading');
  }

  fetchJSON('api.php?action=stats',
    function(s) {
      function updateStat(id, value) {
        var el = document.getElementById(id);
        if (!el) return;
        el.textContent = value.toLocaleString();
        el.classList.remove('loading');
        el.classList.add('loaded');
        setTimeout(function() { el.classList.remove('loaded'); }, 600);
      }

      updateStat('users-count', s.users || 0);
      updateStat('classes-count', s.classes || 0);
      updateStat('notes-count', s.notes || 0);
      updateStat('devoirs-count', s.devoirs || 0);
    },
    function(e) {
      console.error(e);
      var ids2 = ['users-count', 'classes-count', 'notes-count', 'devoirs-count'];
      for (var j = 0; j < ids2.length; j++) {
        var el = document.getElementById(ids2[j]);
        if (el) el.classList.remove('loading');
      }
    }
  );
}

var classChart = null;
function loadClassChart() {
  var canvas = document.getElementById('classChart');
  if (!canvas) return;

  fetchJSON('api.php?action=class_counts',
    function(res) {
      var ctx = canvas.getContext('2d');
      if (classChart && classChart.canvas === canvas) {
        classChart.destroy();
      }
      classChart = new Chart(ctx, {
        type: 'bar',
         {
          labels: res.labels || [],
          datasets: [{
            label: 'Élèves',
             res.data || [],
            backgroundColor: 'rgba(14,119,112,0.8)'
          }]
        },
        options: {
          responsive: true,
          plugins: {
            legend: { display: false },
            tooltip: {
              callbacks: {
                label: function(context) {
                  return 'Élèves : ' + context.parsed.y;
                }
              }
            }
          },
          scales: {
            y: {
              beginAtZero: true
            }
          }
        }
      });
    },
    function(e) { console.error(e); }
  );
}

var matiereChart = null;
function loadMatiereChart() {
  var canvas = document.getElementById('matiereChart');
  if (!canvas) return;

  fetchJSON('api.php?action=matiere_counts',
    function(res) {
      var ctx = canvas.getContext('2d');
      if (matiereChart && matiereChart.canvas === canvas) {
        matiereChart.destroy();
      }
      matiereChart = new Chart(ctx, {
        type: 'bar',
         {
          labels: res.labels || [],
          datasets: [{
            label: 'Moyenne',
             res.data || [],
            backgroundColor: 'rgba(255,159,64,0.8)'
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
                  return 'Moyenne de ' + context.label + ' : ' + context.parsed.y.toFixed(2);
                }
              }
            }
          },
          scales: {
            y: {
              beginAtZero: true,
              max: 20
            }
          }
        }
      });
    },
    function(e) { console.error(e); }
  );
}

function loadElevesTable() {
  fetchJSON('api.php?action=eleves_moyenne',
    function(res) {
      var tbody = document.querySelector('#eleves-table tbody');
      if (!tbody) return;
      tbody.innerHTML = '';
      for (var i = 0; i < res.length; i++) {
        var u = res[i];
        var moyenne = parseFloat(u.moyenne);
        var icone = '🟡';
        if (moyenne >= 15) icone = '🟢';
        else if (moyenne < 10) icone = '🔴';

        var tr = document.createElement('tr');
        tr.innerHTML = 
          '<td>' + (u.Username || '—') + '</td>' +
          '<td>' + (u.nom_de_classe || '—') + '</td>' +
          '<td>' +
            '<span title="Moyenne">' + moyenne.toFixed(2) + '</span>' +
            '<span style="margin-left:6px">' + icone + '</span>' +
          '</td>';
        tbody.appendChild(tr);
      }
    },
    function(e) { console.error(e); }
  );
}

// Recherche dynamique
document.getElementById('search-eleves').addEventListener('input', function(e) {
  var term = e.target.value.toLowerCase().trim();
  var rows = document.querySelectorAll('#eleves-table tbody tr');
  for (var i = 0; i < rows.length; i++) {
    var row = rows[i];
    var text = row.textContent.toLowerCase();
    row.style.display = text.indexOf(term) !== -1 ? '' : 'none';
  }
});

function loadLastItems(classe) {
  classe = classe || 0;
  var url = 'api.php?action=last_items' + (classe ? '&classe=' + classe : '');
  fetchJSON(url,
    function(res) {
      var tbody = document.querySelector('#last-items tbody');
      if (!tbody) return;
      tbody.innerHTML = '';
      for (var i = 0; i < res.length; i++) {
        var iItem = res[i];
        var tr = document.createElement('tr');
        tr.innerHTML = 
          '<td>' + (iItem.Username || '—') + '</td>' +
          '<td>' + (iItem.type || '—') + '</td>' +
          '<td>' + (iItem.detail || '—') + '</td>' +
          '<td>' + (iItem.value || '—') + '</td>';
        tbody.appendChild(tr);
      }
    },
    function(e) { console.error(e); }
  );
}

document.getElementById('filter-classe').addEventListener('change', function(e) {
  loadLastItems(e.target.value);
});

// Lancement
document.addEventListener('DOMContentLoaded', function() {
  loadStats();
  loadClassChart();
  loadMatiereChart();
  loadElevesTable();
  loadLastItems();
  loadUserConfig();
});