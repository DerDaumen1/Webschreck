/************************************************************
 * Globale Variablen (mit Stocks und zusätzlicher Kurs-Historie)
 ************************************************************/
let stocksArray = [];
// Dictionary, das für jede Stock-ID ein Array an Kurswerten speichert
let stockHistory = {};

// Standard: Nutzer sieht Aktie ID=1 zuerst (Mustermann AG)
let selectedStockId = 1;
let ctx, chartCanvas;

// Bullen-/Bärenmarkt-Zähler und Phase gelten nur für die AKTUELLE Aktie
let consecutiveUps = 0;
let consecutiveDowns = 0;
let currentPhase = "";

let gameTimerSeconds = 600; // z. B. 10 Minuten
let updateInterval;         // Handle für setInterval(updateAllKurse)

/**
 * Wird beim Laden der Seite (onload) ausgeführt.
 * Initialisiert Canvas, startet Hintergrund-Update (für alle Aktien),
 * Timer und History-Reload.
 */
function initGame() {
  chartCanvas = document.getElementById("chartCanvas");
  ctx = chartCanvas.getContext("2d");

  selectedStockId = parseInt(document.getElementById('stockSelect').value) || 1;

  // Starte Hintergrund-Updates (alle Aktien, jede Sekunde)
  updateInterval = setInterval(updateAllKurse, 1000);

  // Countdown-Timer
  setInterval(updateGameTimer, 1000);

  // Alle 5 Sekunden History neu laden (für aktuell ausgewählte Aktie)
  setInterval(updateHistoryDisplay, 5000);

  // Beispielhaft 10 Stocks
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

  // Für jede Aktie ein eigenes History-Array anlegen
  for (let s of stocksArray) {
    stockHistory[s.id] = [s.briefkurs];
  }

  // Erstes Zeichnen & Anzeigen
  drawChart();
  updateAnzeigen();
  updateHistoryDisplay();

  // Listener: Wenn der Nutzer eine andere Aktie auswählt
  document.getElementById('stockSelect').addEventListener('change', e => {
    selectedStockId = parseInt(e.target.value);
    // Reset für Bullen-/Bären-Zähler, falls man eine andere Aktie wählt
    consecutiveUps = 0;
    consecutiveDowns = 0;
    currentPhase = "";
    updateAnzeigen();
    drawChart();
    updateHistoryDisplay();
  });

  // Beim Verlassen der Seite Updates stoppen
  window.addEventListener('beforeunload', () => {
    clearInterval(updateInterval);
  });
}

/**
 * Aktualisiert alle Aktien: Für jede Aktie wird ein neuer Kurs berechnet,
 * in stockHistory gepusht und per AJAX in DB geschrieben.
 * Nur für die 'selectedStockId' wird die Bullen-/Bärenmarkt-Logik geführt.
 */
function updateAllKurse() {
  stocksArray.forEach(stock => {
    let chanceUp = 0.5;

    // Nur für die aktuell ausgewählte Aktie => Bullen-/Bärenmarkt-Logik
    if (stock.id === selectedStockId) {
      if (currentPhase === "Bullenmarkt") chanceUp = 0.75;
      if (currentPhase === "Bärenmarkt") chanceUp = 0.25;
    }

    let delta = Math.random() * 0.04 + 0.01; // 0.01..0.05
    let rand = Math.random();

    // Kurs steigt / fällt
    if (rand < chanceUp) {
      stock.briefkurs += delta;
      stock.geldkurs += delta;

      // Bullen-/Bären-Zähler nur für die ausgewählte Aktie
      if (stock.id === selectedStockId) {
        consecutiveUps++;
        consecutiveDowns = 0;
      }
    } else {
      stock.briefkurs -= delta;
      stock.geldkurs -= delta;

      if (stock.id === selectedStockId) {
        consecutiveDowns++;
        consecutiveUps = 0;
      }
    }

    // Sicherheitscheck
    if (stock.briefkurs < 0.01) stock.briefkurs = 0.01;
    if (stock.geldkurs < 0) stock.geldkurs = 0;

    // Marktphasen-Ermittlung nur für ausgewählte Aktie
    if (stock.id === selectedStockId) {
      if (consecutiveUps >= 3) {
        currentPhase = "Bullenmarkt";
      } else if (consecutiveDowns >= 3) {
        currentPhase = "Bärenmarkt";
      } else {
        currentPhase = "";
      }
    }

    // In stockHistory pushen (max. 10)
    stockHistory[stock.id].push(stock.briefkurs);
    if (stockHistory[stock.id].length > 10) {
      stockHistory[stock.id].shift();
    }

    // AJAX in DB
    let payload = { stock_id: stock.id, briefkurs: stock.briefkurs };
    fetch("update_stocks.php", {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify(payload)
    })
      .then(r => r.json())
      .then(d => {
        if (!d.success) {
          console.error("Fehler beim Speichern in DB:", d.message);
        }
      })
      .catch(err => console.error("AJAX-Fehler:", err));
  });

  // Chart & Anzeigen für "selectedStockId" aktualisieren
  drawChart();
  updateAnzeigen();
  // Optional: updateHistoryDisplay();
}

/**
 * AJAX-Funktion zum Kaufen / Verkaufen / Beenden für die aktuell ausgewählte Aktie
 */
function trade(action) {
  const anzahl = document.getElementById("anzahlInput").value;

  const stock = stocksArray.find(s => s.id === selectedStockId);
  if (!stock) {
    alert("Aktie nicht gefunden!");
    return;
  }

  let fd = new FormData();
  fd.append("typ", action);
  fd.append("anzahl", anzahl);
  fd.append("stock_name", stock.name);
  fd.append("briefkurs", stock.briefkurs.toFixed(2));
  fd.append("geldkurs", stock.geldkurs.toFixed(2));

  fetch("trade.php", {
    method: "POST",
    body: fd
  })
    .then(res => res.json())
    .then(data => {
      document.getElementById("meldungDisplay").innerHTML = data.message || "";

      if (data.success) {
        document.getElementById("spielgeldDisplay").textContent =
          parseFloat(data.spielgeld).toFixed(2).replace('.', ',');
      }

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
 * Lädt die letzten 10 Ticks der aktuell ausgewählten Aktie
 * und schreibt sie in den Container #historyContainer.
 */
function updateHistoryDisplay() {
  fetch("get_history.php?stock_id=" + selectedStockId)
    .then(res => res.json())
    .then(data => {
      if (!data.success) {
        console.error("Fehler beim Laden der History:", data.message);
        return;
      }
      const container = document.getElementById("historyContainer");
      let html = "<table><tr><th>Tick-Time</th><th>Kurs</th></tr>";
      data.history.forEach(row => {
        html += `<tr><td>${row.tick_time}</td><td>${parseFloat(row.kurs).toFixed(2)} €</td></tr>`;
      });
      html += "</table>";
      container.innerHTML = html;
    })
    .catch(err => console.error("Fehler beim AJAX:", err));
}

/**
 * Zeichnet den Kursverlauf (der aktuell ausgewählten Aktie) in den Canvas.
 */
function drawChart() {
  if (!chartCanvas || !ctx) return;

  const points = stockHistory[selectedStockId];
  if (!points || points.length === 0) return;

  ctx.clearRect(0, 0, chartCanvas.width, chartCanvas.height);

  let w = chartCanvas.width;
  let h = chartCanvas.height;
  let padding = 20;

  let minVal = Math.min(...points);
  let maxVal = Math.max(...points);
  if (minVal === maxVal) {
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



