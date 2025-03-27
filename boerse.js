/************************************************************
 * Globale Variablen (mit Stocks und zusätzlicher Kurs-Historie)
 ************************************************************/
let stocksArray = [];
let stockHistory = {};  // Speichert den Verlauf pro Aktie

// Standard: Nutzer sieht Aktie ID=1 zuerst (Mustermann AG)
let selectedStockId = 1;
let ctx, chartCanvas;

let consecutiveUps = 0;
let consecutiveDowns = 0;
let currentPhase = "";

let gameTimerSeconds = 600; // 10 Minuten Spielzeit
let updateInterval;         // Handle für updateAllKurse
let gameTimerInterval;      // Handle für den Timer

// Add global variables for cost basis tracking:
let averageCost = {};
let holdings = {};

// Neue globale Variable, um die Session zu kennzeichnen (optional)
let gameSessionActive = false;

/**
 * Wird beim Laden der Seite (onload) ausgeführt.
 * Initialisiert Canvas, startet Hintergrund-Updates, Timer und History-Reload.
 */
function initGame() {
  chartCanvas = document.getElementById("chartCanvas");
  ctx = chartCanvas.getContext("2d");

  selectedStockId = parseInt(document.getElementById('stockSelect').value) || 1;

  // Starte Hintergrund-Updates (alle Aktien, jede Sekunde)
  updateInterval = setInterval(updateAllKurse, 1000);
  // Timer in einer Variable speichern
  gameTimerInterval = setInterval(updateGameTimer, 1000);
  // Alle 5 Sekunden: History der aktuell ausgewählten Aktie neu laden
  setInterval(updateHistoryDisplay, 5000);

  // Beispielhaft 10 Aktien
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
  // Initialize cost basis and holdings per stock:
  stocksArray.forEach(s => {
    averageCost[s.id] = 100;
    holdings[s.id] = 0;
  });

  // Für jede Aktie ein eigenes History-Array anlegen
  for (let s of stocksArray) {
    stockHistory[s.id] = [s.briefkurs];
  }

  // Erstes Zeichnen & Anzeigen
  drawChart();
  updateAnzeigen();
  updateHistoryDisplay();
  updateStockHolding();  // Bestand der aktuell gewählten Aktie initial laden

  // Listener: Wenn der Nutzer eine andere Aktie auswählt, werden Chart und Bestand neu geladen
  document.getElementById('stockSelect').addEventListener('change', e => {
    selectedStockId = parseInt(e.target.value);
    consecutiveUps = 0;
    consecutiveDowns = 0;
    currentPhase = "";
    updateAnzeigen();
    drawChart();
    updateHistoryDisplay();
    updateStockHolding();
  });

  // Beim Verlassen der Seite Updates stoppen
  window.addEventListener('beforeunload', () => {
    clearInterval(updateInterval);
  });
}

/**
 * Aktualisiert den aktuellen Bestand der ausgewählten Aktie per AJAX.
 * Erwartet, dass get_holding.php den Bestand als JSON liefert.
 */
function updateStockHolding() {
  // Hier wird der Aktien-Selektor direkt ausgelesen, falls nötig
  const stockId = document.getElementById("stockSelect").value || selectedStockId;
  fetch("api.php?action=get_holding&stock_id=" + stockId)
    .then(res => res.json())
    .then(data => {
      if (data.success) {
        document.getElementById("aktienBestandDisplay").textContent = data.bestand;
      } else {
        console.error("Fehler beim Laden des Bestandes:", data.message);
      }
    })
    .catch(err => console.error("Fehler beim AJAX-Aufruf:", err));
}

/**
 * AJAX-Funktion zum Kaufen / Verkaufen / Beenden.
 * Nach einem erfolgreichen Trade wird updateStockHolding() aufgerufen.
 */
function trade(action) {
  const anzahl = parseInt(document.getElementById("anzahlInput").value);
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

  // Ersetze den folgenden fetch-Aufruf:
  // fetch("trade.php", {
  // Neuer fetch-Aufruf:
  fetch("api.php?action=trade", {
    method: "POST",
    body: fd
  })
    .then(res => res.json())
    .then(data => {
      document.getElementById("meldungDisplay").innerHTML = data.message || "";
      if (data.success) {
        document.getElementById("spielgeldDisplay").textContent =
          parseFloat(data.spielgeld).toFixed(2).replace('.', ',');
        // Update cost basis and holdings on successful trade:
        if (action === 'buy') {
          // Calculate commission similar to the server:
          let orderwert = anzahl * stock.briefkurs;
          let provision = Math.max(Math.min(orderwert * 0.0025 + 4.95, 59.99), 9.99);
          // Effective cost per share including commission:
          let effectiveCost = stock.briefkurs + (provision / anzahl);
          let oldQty = holdings[selectedStockId] || 0;
          let oldTotalCost = averageCost[selectedStockId] * oldQty;
          let newTotalCost = oldTotalCost + (effectiveCost * anzahl);
          holdings[selectedStockId] = oldQty + anzahl;
          averageCost[selectedStockId] = newTotalCost / holdings[selectedStockId];
        } else if (action === 'sell') {
          let oldQty = holdings[selectedStockId] || 0;
          holdings[selectedStockId] = Math.max(oldQty - anzahl, 0);
        }
        // Bestand nach einem erfolgreichen Trade neu laden
        updateStockHolding();
        // Bei Beenden: stoppe Timer und Chart und berechne Gewinn/Verlust
        if (action === 'beenden') {
          gameTimerSeconds = 0;
          clearInterval(updateInterval);
          clearInterval(gameTimerInterval);
          // Gewinn/Verlust berechnen relativ zum Session-Start
          let currentCash = parseFloat(document.getElementById("spielgeldDisplay").textContent.replace(',', '.'));
          let gainLoss = currentCash - window.startKapital;
          let finalElem = document.getElementById("finalResultDisplay");
          if (!finalElem) {
            finalElem = document.createElement("div");
            finalElem.id = "finalResultDisplay";
            finalElem.style.marginTop = "1rem";
            finalElem.style.fontWeight = "bold";
            // Neues Element direkt nach #meldungDisplay einfügen
            document.getElementById("meldungDisplay").insertAdjacentElement("afterend", finalElem);
          }
          finalElem.textContent = "Gesamte Gewinn/Verlust: " + gainLoss.toFixed(2).replace('.', ',') + " €";
          gameSessionActive = false;
          // Zeige den Start-Button für eine neue Session an
          document.getElementById("startGameBtn").style.display = "block";
        }
      }
      updateAnzeigen();
    })
    .catch(err => {
      console.error("AJAX-Fehler:", err);
      document.getElementById("meldungDisplay").textContent = "Fehler beim AJAX-Aufruf!";
    });
}

/**
 * Aktualisiert alle Aktien: Für jede Aktie wird ein neuer Kurs berechnet,
 * in stockHistory gepusht und per AJAX in DB gespeichert.
 * Nur für die ausgewählte Aktie wird die Bullen-/Bärenmarkt-Logik geführt.
 */
function updateAllKurse() {
  stocksArray.forEach(stock => {
    let chanceUp = 0.5;
    if (stock.id === selectedStockId) {
      if (currentPhase === "Bullenmarkt") chanceUp = 0.75;
      if (currentPhase === "Bärenmarkt") chanceUp = 0.25;
    }

    let delta = Math.random() * 0.04 + 0.01;
    let rand = Math.random();

    if (rand < chanceUp) {
      stock.briefkurs += delta;
      stock.geldkurs += delta;
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

    if (stock.briefkurs < 0.01) stock.briefkurs = 0.01;
    if (stock.geldkurs < 0) stock.geldkurs = 0;

    if (stock.id === selectedStockId) {
      if (consecutiveUps >= 3) {
        currentPhase = "Bullenmarkt";
      } else if (consecutiveDowns >= 3) {
        currentPhase = "Bärenmarkt";
      } else {
        currentPhase = "";
      }
    }

    stockHistory[stock.id].push(stock.briefkurs);

    let payload = { stock_id: stock.id, briefkurs: stock.briefkurs };
    fetch("api.php?action=update_stocks", {
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

  drawChart();
  updateAnzeigen();
}

/**
 * Lädt per AJAX die letzten 10 Ticks der aktuell ausgewählten Aktie
 * und schreibt sie in den Container #historyContainer.
 */
function updateHistoryDisplay() {
  fetch("api.php?action=get_history&stock_id=" + selectedStockId)
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

  // Use holdings from our tracking; fallback to DOM if necessary.
  let currentHolding = holdings[selectedStockId] || parseInt(document.getElementById("aktienBestandDisplay").textContent) || 0;
  let costBasis = averageCost[selectedStockId] || 100;
  let profit = (stock.briefkurs - costBasis) * currentHolding;

  const profitEl = document.getElementById("profitDisplay");
  profitEl.textContent = profit.toFixed(2).replace('.', ',');
  if (profit >= 0) {
    profitEl.classList.add("profit-positive");
    profitEl.classList.remove("profit-negative");
  } else {
    profitEl.classList.add("profit-negative");
    profitEl.classList.remove("profit-positive");
  }

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
}

/**
 * Aktualisiert den Countdown-Timer
 */
function updateGameTimer() {
  gameTimerSeconds--;
  if (gameTimerSeconds <= 0) {
    clearInterval(gameTimerInterval);
    clearInterval(updateInterval);
    trade('beenden');
    return; // Spiel beenden, keine weitere Aktualisierung
  }
  let min = Math.floor(gameTimerSeconds / 60);
  let sec = gameTimerSeconds % 60;
  let text = `Verbleibende Spielzeit: ${String(min).padStart(2, '0')}:${String(sec).padStart(2, '0')} min`;
  document.getElementById("timerDisplay").textContent = text;
}

/************************************************************
 * Integration des Inline-Skriptteils aus der PHP-Datei
 ************************************************************/
// Anstelle des separaten Inline-Skripts in der PHP-Datei wird
// hier beim DOMContentLoaded direkt der Bestand abgefragt.
document.addEventListener("DOMContentLoaded", () => {
  updateStockHolding();
});

// Neue Funktion, um eine Börsenspiel-Session zu starten
function startGameSession() {
  // Neues: Aktienhistorie zurücksetzen
  fetch("reset_history.php")
    .then(res => res.json())
    .then(data => {
      if (!data.success) {
        document.getElementById("meldungDisplay").textContent = data.message;
        return;
      }
      initGame();
      window.startKapital = parseFloat(document.getElementById("spielgeldDisplay").textContent.replace(',', '.')) || 50000;
      gameSessionActive = true;
      gameTimerSeconds = 600;
      clearInterval(updateInterval);
      clearInterval(gameTimerInterval);
      updateInterval = setInterval(updateAllKurse, 1000);
      gameTimerInterval = setInterval(updateGameTimer, 1000);
      document.getElementById("meldungDisplay").textContent = "Session gestartet!";
      document.getElementById("startGameBtn").style.display = "none";
      updateAnzeigen();
    })
    .catch(err => {
      document.getElementById("meldungDisplay").textContent = "Fehler beim Zurücksetzen der Historie";
    });
}
