/************************************************************
 * Globale Variablen (mit Stocks und zusätzlicher Kurs-Historie)
 ************************************************************/
let stocksArray = [];
// Dictionary/Objekt, das für jede Stock-ID ein Array an Kurswerten speichert
let stockHistory = {};

// Standard: Nutzer sieht Aktie ID=1 zuerst (Mustermann AG)
let selectedStockId = 1;
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
  chartCanvas = document.getElementById("chartCanvas");
  ctx = chartCanvas.getContext("2d");

  // Welche Aktie ist per <select> vorausgewählt?
  selectedStockId = parseInt(document.getElementById('stockSelect').value) || 1;

  // Starte Kurs-Update + Timer
  setInterval(updateKurs, 1000);
  setInterval(updateGameTimer, 1000);

  // Beispielhaft 10 Stocks (kannst du auch aus der Session o.ä. laden):
  stocksArray = [
    { id: 1, name: "Mustermann AG", briefkurs: 100, geldkurs: 99 },
    { id: 2, name: "Beispiel AG", briefkurs: 100, geldkurs: 99 },
    { id: 3, name: "Test Inc.", briefkurs: 100, geldkurs: 99 },
    { id: 4, name: "MegaCorp", briefkurs: 100, geldkurs: 99 },
    { id: 5, name: "Future Ltd.", briefkurs: 100, geldkurs: 99 },
    { id: 6, name: "Sample GmbH", briefkurs: 100, geldkurs: 99 },
    { id: 7, name: "Hallo AG", briefkurs: 100, geldkurs: 99 },
    { id: 8, name: "World Ind.", briefkurs: 100, geldkurs: 99 },
    { id: 9, name: "Börsenspiel SE", briefkurs: 100, geldkurs: 99 },
    { id: 10, name: "Fantasy PLC", briefkurs: 100, geldkurs: 99 }
  ];

  // Für jede Aktie ein eigenes History-Array anlegen und den Startwert eintragen
  for (let s of stocksArray) {
    stockHistory[s.id] = [s.briefkurs];
  }

  // Erstes Zeichnen + Anzeigen
  drawChart();
  updateAnzeigen();

  // Listener: Wenn der Nutzer eine andere Aktie auswählt
  document.getElementById('stockSelect').addEventListener('change', e => {
    selectedStockId = parseInt(e.target.value);
    // Resette Bullen-/Bären-Zähler
    consecutiveUps = 0;
    consecutiveDowns = 0;
    currentPhase = "";
    updateAnzeigen();
    drawChart();
  });
}

/**
 * AJAX-Funktion zum Kaufen / Verkaufen / Beenden
 * @param {string} action - "kaufen", "verkaufen", "beenden"
 */
function trade(action) {
  const anzahl = document.getElementById("anzahlInput").value;

  // Finde die aktuell ausgewählte Aktie und hole deren Kurse
  const stock = stocksArray.find(s => s.id === selectedStockId);
  if (!stock) {
    alert("Aktie nicht gefunden!");
    return;
  }

  let fd = new FormData();
  fd.append("typ", action);
  fd.append("anzahl", anzahl);
  fd.append("stock_name", stock.name); // Damit im Orderbuch nicht mehr "Mustermann" steht
  fd.append("briefkurs", stock.briefkurs.toFixed(2));
  fd.append("geldkurs", stock.geldkurs.toFixed(2));

  fetch("trade.php", {
    method: "POST",
    body: fd
  })
    .then(res => res.json())
    .then(data => {
      // Nachricht anzeigen
      document.getElementById("meldungDisplay").innerHTML = data.message || "";

      // Falls Erfolg -> Spielgeld im Frontend aktualisieren
      if (data.success) {
        document.getElementById("spielgeldDisplay").textContent =
          parseFloat(data.spielgeld).toFixed(2).replace('.', ',');
      }

      // Falls Spiel beendet, Timer auf 0 setzen (kein Weiterzählen)
      if (action === 'beenden' && data.success) {
        gameTimerSeconds = 0;
      }

      updateAnzeigen();
    })
    .catch(err => {
      console.error("AJAX-Fehler:", err);
      document.getElementById("meldungDisplay").textContent = "Fehler beim AJAX-Aufruf!";
    });
}

/**
 * Aktualisiert Brief- und Geldkurs zufällig (Sekundentakt)
 * für die aktuell ausgewählte Aktie.
 */
function updateKurs() {
  // Finde die Aktie
  const stock = stocksArray.find(s => s.id === selectedStockId);
  if (!stock) return;

  // Wahrscheinlichkeiten
  let chanceUp = 0.5;
  if (currentPhase === "Bullenmarkt") chanceUp = 0.75;
  if (currentPhase === "Bärenmarkt") chanceUp = 0.25;

  // Schrittweite
  let delta = Math.random() * 0.04 + 0.01; // 0.01..0.05
  let rand = Math.random();

  if (rand < chanceUp) {
    // Kurs steigt
    stock.briefkurs += delta;
    stock.geldkurs += delta;
    consecutiveUps++;
    consecutiveDowns = 0;
  } else {
    // Kurs fällt
    stock.briefkurs -= delta;
    stock.geldkurs -= delta;
    consecutiveDowns++;
    consecutiveUps = 0;
  }

  // Sicherheitscheck
  if (stock.briefkurs < 0.01) stock.briefkurs = 0.01;
  if (stock.geldkurs < 0) stock.geldkurs = 0;

  // Check für Bullen-/Bärenmarkt
  if (consecutiveUps >= 3) {
    currentPhase = "Bullenmarkt";
  } else if (consecutiveDowns >= 3) {
    currentPhase = "Bärenmarkt";
  }

  // Phasen neutralisieren, wenn Richtung wechselt
  if (consecutiveUps === 1 && currentPhase === "Bärenmarkt") {
    currentPhase = "";
  }
  if (consecutiveDowns === 1 && currentPhase === "Bullenmarkt") {
    currentPhase = "";
  }

  // **NEU**: Neuen Kurs in die History pushen
  stockHistory[selectedStockId].push(stock.briefkurs);
  // Optional: Wenn wir nur die letzten 30 Punkte speichern wollen:
  if (stockHistory[selectedStockId].length > 30) {
    stockHistory[selectedStockId].shift();
  }

  // Zeichnen + Anzeigen
  drawChart();
  updateAnzeigen();
}

/**
 * Zeichnet den Kursverlauf (der aktuell ausgewählten Aktie) in den Canvas
 * Indem wir alle Werte aus stockHistory nehmen und eine Linie plotten
 */
function drawChart() {
  if (!chartCanvas || !ctx) return;

  // Array der Briefkurs-History für die ausgewählte Aktie
  const points = stockHistory[selectedStockId];
  if (!points || points.length === 0) return;

  ctx.clearRect(0, 0, chartCanvas.width, chartCanvas.height);

  let w = chartCanvas.width;
  let h = chartCanvas.height;
  let padding = 20;

  // Min- und Maxwerte finden
  let minVal = Math.min(...points);
  let maxVal = Math.max(...points);
  if (minVal === maxVal) {
    // Verhindert Division durch 0
    minVal -= 1;
    maxVal += 1;
  }

  let scaleX = (w - 2 * padding) / (points.length - 1);
  let scaleY = (h - 2 * padding) / (maxVal - minVal);

  ctx.beginPath();
  ctx.strokeStyle = "#3f51b5";
  ctx.lineWidth = 2;

  for (let i = 0; i < points.length; i++) {
    let x = padding + i * scaleX;
    // je größer der Kurs, desto weiter oben wollen wir die Linie 
    // => (maxVal - points[i])
    let y = padding + (maxVal - points[i]) * scaleY;

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
  const stock = stocksArray.find(s => s.id === selectedStockId);
  if (!stock) return;

  document.getElementById("briefkursDisplay").textContent =
    "Briefkurs: " + stock.briefkurs.toFixed(2) + " €";
  document.getElementById("geldkursDisplay").textContent =
    "Geldkurs: " + stock.geldkurs.toFixed(2) + " €";

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

  // Gewinn/Verlust live berechnen (nur für die "eine" Aktie).
  let spielgeldText = document.getElementById("spielgeldDisplay").textContent.replace(',', '.');
  let spielgeld = parseFloat(spielgeldText) || 0;
  let depotAnz = parseInt(document.getElementById("aktienDepotDisplay").textContent) || 0;

  let liveProfit = (spielgeld + depotAnz * stock.geldkurs) - 50000;

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
 * Countdown-Timer
 */
function updateGameTimer() {
  if (gameTimerSeconds <= 0) return;
  gameTimerSeconds--;

  if (gameTimerSeconds === 0) {
    trade('beenden');
  }

  let min = Math.floor(gameTimerSeconds / 60);
  let sec = gameTimerSeconds % 60;
  let text = `Verbleibende Spielzeit: ${String(min).padStart(2, '0')}:${String(sec).padStart(2, '0')} min`;
  document.getElementById("timerDisplay").textContent = text;
}
