<?php
session_start();

// Beispielhaft: Prüfen, ob eingeloggt
if (!isset($_SESSION['angemeldet']) || $_SESSION['angemeldet'] !== true) {
    header('Location: registrierung.php');
    exit;
}
// Initialwerte, falls nicht vorhanden
if (!isset($_SESSION['spielgeld'])) {
    $_SESSION['spielgeld'] = 50000;
}
if (!isset($_SESSION['anzahl_aktien'])) {
    $_SESSION['anzahl_aktien'] = 0;
}
?>
<!DOCTYPE html>
<html lang="de">
<head>
  <meta charset="UTF-8">
  <title>Börsenspiel (PHP & CSS getrennt)</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <!-- Externe CSS-Datei einbinden -->
  <link rel="stylesheet" href="styles.css">
</head>
<body onload="initGame();">

<header>
  <h1>Börsenspiel (AJAX-Version)</h1>
  <nav>
    <a href="index.html">Startseite</a>
    <a href="logout.php">Logout</a>
  </nav>
</header>

<div class="container">
  <div id="chartContainer" class="card">
    <canvas id="chartCanvas" width="700" height="300"></canvas>
  </div>

  <div class="meldung" id="meldungDisplay"></div>

  <div class="card">
    <ul class="info-list">
      <li><strong>Aktuelles Spielgeld:</strong> 
          <span id="spielgeldDisplay">50000.00</span> €</li>
      <li><strong>Anzahl Aktien im Depot:</strong> 
          <span id="aktienDepotDisplay">0</span></li>
      <li><strong>Aktueller Gewinn/Verlust:</strong> 
          <span id="profitDisplay" class="profit-positive">0,00</span> €</li>
    </ul>
    <div class="market-phase" id="marketPhaseDisplay"></div>
    <div id="timerDisplay"></div>
  </div>

  <div class="card" style="text-align:center;">
    <h2>Kurse & Aktionen</h2>
    <p id="briefkursDisplay" style="margin-bottom:0.5rem;">Briefkurs: 100.00 €</p>
    <p id="geldkursDisplay" style="margin-bottom:0.5rem;">Geldkurs: 99.00 €</p>

    <div class="form-group">
      <label for="anzahlInput">Anzahl:</label>
      <input type="number" id="anzahlInput" value="1" min="1" />
      <button class="btn" onclick="trade('kaufen')">Aktien kaufen</button>
      <button class="btn" onclick="trade('verkaufen')">Aktien verkaufen</button>
      <button class="btn btn-secondary" onclick="trade('beenden')">Spiel beenden</button>
    </div>
  </div>
</div>

<footer>
  &copy; <?php echo date("Y"); ?> Mein Börsenspiel - Alle Rechte vorbehalten.
</footer>

<script>
/************************************************************
 * Globale Variablen & Initialisierung
 ************************************************************/
let briefkurs = 100.00;
let geldkurs  = 99.00;
let chartData = [];
let ctx, chartCanvas;

let consecutiveUps   = 0;
let consecutiveDowns = 0;
let currentPhase     = "";

let gameTimerSeconds = 600; // 10 Minuten

function initGame() {
  chartCanvas = document.getElementById("chartCanvas");
  ctx = chartCanvas.getContext("2d");

  chartData.push(briefkurs);

  setInterval(updateKurs, 1000);
  setInterval(updateGameTimer, 1000);

  drawChart();
  updateAnzeigen();
}

/************************************************************
 * Kaufen / Verkaufen / Beenden (AJAX)
 ************************************************************/
function trade(action) {
  const anzahl = document.getElementById("anzahlInput").value;

  let fd = new FormData();
  fd.append("typ", action);
  fd.append("anzahl", anzahl);
  fd.append("briefkurs", briefkurs.toFixed(2));
  fd.append("geldkurs", geldkurs.toFixed(2));

  fetch("trade.php", {
    method: "POST",
    body: fd
  })
  .then(res => res.json())
  .then(data => {
    document.getElementById("meldungDisplay").textContent = data.message || "";
    if (data.success) {
      document.getElementById("spielgeldDisplay").textContent = 
        parseFloat(data.spielgeld).toFixed(2).replace('.', ',');
      document.getElementById("aktienDepotDisplay").textContent = data.anzahl_aktien;
    }
    if (action === 'beenden' && data.success) {
      gameTimerSeconds = 0;
    }
    updateAnzeigen();
  })
  .catch(err => {
    console.error("AJAX-Fehler:", err);
    document.getElementById("meldungDisplay").textContent = 
      "Fehler beim AJAX-Aufruf!";
  });
}

/************************************************************
 * Zufällige Kursänderung
 ************************************************************/
function updateKurs() {
  let chanceUp = 0.5;
  if (currentPhase === "Bullenmarkt") chanceUp = 0.75;
  if (currentPhase === "Bärenmarkt")  chanceUp = 0.25;

  let rand = Math.random();
  let delta = Math.random() * 0.04 + 0.01; // 0.01..0.05

  if (rand < chanceUp) {
    briefkurs += delta;
    geldkurs  += delta;
    consecutiveUps++;
    consecutiveDowns = 0;
  } else {
    briefkurs -= delta;
    geldkurs  -= delta;
    consecutiveDowns++;
    consecutiveUps = 0;
  }

  if (briefkurs < 0.01) briefkurs = 0.01;
  if (geldkurs < 0.00)  geldkurs  = 0.00;

  chartData.push(briefkurs);

  if (consecutiveUps >= 3) {
    currentPhase = "Bullenmarkt";
  } else if (consecutiveDowns >= 3) {
    currentPhase = "Bärenmarkt";
  }
  // Optional neutralisieren bei erstem Richtungswechsel
  if (consecutiveUps === 1 && currentPhase === "Bärenmarkt") {
    currentPhase = "";
  }
  if (consecutiveDowns === 1 && currentPhase === "Bullenmarkt") {
    currentPhase = "";
  }

  drawChart();
  updateAnzeigen();
}

/************************************************************
 * Chart zeichnen
 ************************************************************/
function drawChart() {
  ctx.clearRect(0, 0, chartCanvas.width, chartCanvas.height);

  let padding = 20;
  let w = chartCanvas.width - 2 * padding;
  let h = chartCanvas.height - 2 * padding;

  let minValue = Math.min(...chartData);
  let maxValue = Math.max(...chartData);
  if (minValue === maxValue) {
    minValue -= 1;
    maxValue += 1;
  }

  let scaleX = w / (chartData.length - 1);
  let scaleY = h / (maxValue - minValue);

  ctx.beginPath();
  ctx.strokeStyle = "#3f51b5";
  ctx.lineWidth = 2;

  for (let i = 0; i < chartData.length; i++) {
    let x = padding + i * scaleX;
    let y = padding + (maxValue - chartData[i]) * scaleY;

    if (i === 0) {
      ctx.moveTo(x, y);
    } else {
      ctx.lineTo(x, y);
    }
  }
  ctx.stroke();
}

/************************************************************
 * Anzeigen aktualisieren
 ************************************************************/
function updateAnzeigen() {
  document.getElementById("briefkursDisplay").textContent = 
    "Briefkurs: " + briefkurs.toFixed(2) + " €";
  document.getElementById("geldkursDisplay").textContent = 
    "Geldkurs: " + geldkurs.toFixed(2) + " €";

  // Marktphase
  const mp = document.getElementById("marketPhaseDisplay");
  if (currentPhase === "Bullenmarkt") {
    mp.textContent = "Bullenmarkt (Chance auf Steigerung: 75%)";
    mp.style.color = "green";
  } else if (currentPhase === "Bärenmarkt") {
    mp.textContent = "Bärenmarkt (Chance auf Fallen: 75%)";
    mp.style.color = "red";
  } else {
    mp.textContent = "";
  }

  // Live-Gewinn/Verlust
  const spielgeldText = document.getElementById("spielgeldDisplay").textContent.replace(',', '.');
  const spielgeld   = parseFloat(spielgeldText) || 0;
  const depotAnzahl = parseInt(document.getElementById("aktienDepotDisplay").textContent) || 0;

  let liveProfit = (spielgeld + depotAnzahl * geldkurs) - 50000;

  const profitEl = document.getElementById("profitDisplay");
  let profitText = liveProfit.toFixed(2).replace('.', ',');
  profitEl.textContent = profitText;

  if (liveProfit >= 0) {
    profitEl.classList.add("profit-positive");
    profitEl.classList.remove("profit-negative");
  } else {
    profitEl.classList.add("profit-negative");
    profitEl.classList.remove("profit-positive");
  }
}

/************************************************************
 * Countdown-Timer
 ************************************************************/
function updateGameTimer() {
  if (gameTimerSeconds <= 0) {
    return;
  }
  gameTimerSeconds--;

  if (gameTimerSeconds === 0) {
    trade('beenden');
  }

  let min = Math.floor(gameTimerSeconds / 60);
  let sec = gameTimerSeconds % 60;
  let text = `Verbleibende Spielzeit: ${String(min).padStart(2,'0')}:${String(sec).padStart(2,'0')} min`;
  document.getElementById("timerDisplay").textContent = text;
}
</script>
</body>
</html>
