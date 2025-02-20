/************************************************************
 * Globale Variablen
 ************************************************************/
let briefkurs = 100.00;
let geldkurs = 99.00;
let chartData = [];
let ctx, chartCanvas;

let consecutiveUps = 0;
let consecutiveDowns = 0;
let currentPhase = "";

let gameTimerSeconds = 600; // z. B. 10 Minuten

/**
 * Wird beim Laden der Seite (onload) ausgeführt.
 * Initialisiert Canvas, startet Intervalle für Kursupdates und Timer.
 */
function initGame() {
  // Canvas-Element holen
  chartCanvas = document.getElementById("chartCanvas");
  ctx = chartCanvas.getContext("2d");

  // Ersten Kurswert in Array
  chartData.push(briefkurs);

  // Kurs-Update im Sekundentakt
  setInterval(updateKurs, 1000);

  // Timer
  setInterval(updateGameTimer, 1000);

  // Start-Zustand darstellen
  drawChart();
  updateAnzeigen();
}

/**
 * AJAX-Funktion zum Kaufen / Verkaufen / Beenden
 * @param {string} action - "kaufen", "verkaufen", "beenden"
 */
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
      // Nachricht anzeigen
      document.getElementById("meldungDisplay").textContent = data.message || "";

      // Falls Erfolg -> Spielgeld und Depot-Aktien im Frontend aktualisieren
      if (data.success) {
        document.getElementById("spielgeldDisplay").textContent =
          parseFloat(data.spielgeld).toFixed(2).replace('.', ',');
        document.getElementById("aktienDepotDisplay").textContent = data.anzahl_aktien;
      }

      // Falls Spiel beendet, Timer auf 0 setzen (kein Weiterzählen)
      if (action === 'beenden' && data.success) {
        gameTimerSeconds = 0;
      }

      // Aktualisierte Anzeige
      updateAnzeigen();
    })
    .catch(err => {
      console.error("AJAX-Fehler:", err);
      document.getElementById("meldungDisplay").textContent =
        "Fehler beim AJAX-Aufruf!";
    });
}

/**
 * Aktualisiert Brief- und Geldkurs zufällig (Sekundentakt).
 * Bullen-/Bärenmarkt: mind. 3x in dieselbe Richtung -> Wahrscheinlichkeiten ändern.
 */
function updateKurs() {
  // Wahrscheinlichkeiten
  let chanceUp = 0.5;
  if (currentPhase === "Bullenmarkt") chanceUp = 0.75;
  if (currentPhase === "Bärenmarkt") chanceUp = 0.25;

  // Schrittweite
  let delta = Math.random() * 0.04 + 0.01; // 0.01..0.05
  let rand = Math.random();

  if (rand < chanceUp) {
    // Kurs steigt
    briefkurs += delta;
    geldkurs += delta;
    consecutiveUps++;
    consecutiveDowns = 0;
  } else {
    // Kurs fällt
    briefkurs -= delta;
    geldkurs -= delta;
    consecutiveDowns++;
    consecutiveUps = 0;
  }

  // Sicherheitscheck
  if (briefkurs < 0.01) briefkurs = 0.01;
  if (geldkurs < 0) geldkurs = 0;

  // In den Chart-Verlauf übernehmen
  chartData.push(briefkurs);

  // Check für Bullen-/Bärenmarkt
  if (consecutiveUps >= 3) {
    currentPhase = "Bullenmarkt";
  } else if (consecutiveDowns >= 3) {
    currentPhase = "Bärenmarkt";
  }

  // Optional: Phasen neutralisieren, wenn Richtung wechselt
  if (consecutiveUps === 1 && currentPhase === "Bärenmarkt") {
    currentPhase = "";
  }
  if (consecutiveDowns === 1 && currentPhase === "Bullenmarkt") {
    currentPhase = "";
  }

  // Chart & Anzeigen updaten
  drawChart();
  updateAnzeigen();
}

/**
 * Zeichnet den Kursverlauf (briefkurs) in den Canvas
 */
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

/**
 * Aktualisiert Textanzeigen (Briefkurs, Geldkurs, Gewinn/Verlust, Marktphase)
 */
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

  // Gewinn/Verlust live berechnen
  // const spielgeldText = document.getElementById("spielgeldDisplay").textContent.replace(',', '.');
  const spielgeldText = document.getElementById("spielgeldDisplay").textContent.replace(/\./g, '').replace(',', '.');
  const spielgeld = parseFloat(spielgeldText) || 0;
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

/**
 * Countdown-Timer (z. B. 10 Minuten)
 * Wenn abgelaufen, automatisch 'beenden' aufrufen
 */
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
  let text = `Verbleibende Spielzeit: ${String(min).padStart(2, '0')}:${String(sec).padStart(2, '0')} min`;
  document.getElementById("timerDisplay").textContent = text;
}
