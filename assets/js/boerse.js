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
  fetch("../includes/api.php?action=get_holding&stock_id=" + stockId) // Pfad korrigieren
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
  fetch("../includes/api.php?action=trade", { // Pfad korrigieren
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

    // Add new price to the END of the array (oldest at index 0, newest at last)
    stockHistory[stock.id].push(stock.briefkurs);

    // Keep only the last 20 points to avoid performance issues
    if (stockHistory[stock.id].length > 20) {
      stockHistory[stock.id] = stockHistory[stock.id].slice(-20);
    }

    let payload = { stock_id: stock.id, briefkurs: stock.briefkurs };
    fetch("../includes/api.php?action=update_stocks", { // Pfad korrigieren
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
  fetch("../includes/api.php?action=get_history&stock_id=" + selectedStockId)
    .then(res => res.json())
    .then(data => {
      if (!data.success) {
        console.error("Fehler beim Laden der History:", data.message);
        return;
      }
      const container = document.getElementById("historyContainer");

      // Debugging: Log the raw dates before sorting
      console.log("Raw history data:", data.history.map(h => h.tick_time));

      // Sort the history array by date (newest first)
      let sortedHistory = [...data.history];
      try {
        sortedHistory.sort((a, b) => {
          // Parse the German date format DD.MM.YYYY
          const partsA = a.tick_time.split('.');
          const partsB = b.tick_time.split('.');

          if (partsA.length !== 3 || partsB.length !== 3) {
            console.warn("Invalid date format:", a.tick_time, b.tick_time);
            return 0;
          }

          // Create date strings in YYYY-MM-DD format (which JS can parse reliably)
          const dateStringA = `${partsA[2]}-${partsA[1]}-${partsA[0]}`;
          const dateStringB = `${partsB[2]}-${partsB[1]}-${partsB[0]}`;

          // Create Date objects
          const dateA = new Date(dateStringA);
          const dateB = new Date(dateStringB);

          // Debugging
          console.log(`Comparing: ${a.tick_time} (${dateA}) vs ${b.tick_time} (${dateB}) = ${dateB - dateA}`);

          // Newest first: descending order
          return dateB - dateA;
        });

        // Debugging: Log the sorted dates
        console.log("Sorted history data:", sortedHistory.map(h => h.tick_time));
      } catch (e) {
        console.error("Error sorting dates:", e);
        // Use original unsorted data if sorting fails
      }

      let html = `<table class="enhanced-history-table">
                    <thead>
                      <tr>
                        <th>Datum</th>
                        <th>Kurs</th>
                        <th>Änderung</th>
                      </tr>
                    </thead>
                    <tbody>`;
      // ...existing code to build html...
      sortedHistory.forEach((row, index) => {
        const currentKurs = parseFloat(row.kurs);
        let changeClass = "";
        let changeIcon = "";
        let changePercent = "";

        if (index < sortedHistory.length - 1) {
          const olderKurs = parseFloat(sortedHistory[index + 1].kurs);
          const diff = currentKurs - olderKurs;
          const percentChange = (diff / olderKurs) * 100;

          if (diff > 0) {
            changeClass = "trend-up";
            changeIcon = '<i class="fas fa-arrow-up"></i>';
            changePercent = `+${percentChange.toFixed(2)}%`;
          } else if (diff < 0) {
            changeClass = "trend-down";
            changeIcon = '<i class="fas fa-arrow-down"></i>';
            changePercent = `${percentChange.toFixed(2)}%`;
          } else {
            changeClass = "trend-neutral";
            changeIcon = '<i class="fas fa-minus"></i>';
            changePercent = "0.00%";
          }
        } else {
          changeClass = "trend-neutral";
          changeIcon = '<i class="fas fa-minus"></i>';
          changePercent = "--";
        }

        html += `<tr class="${changeClass}">
                   <td>${row.tick_time}</td>
                   <td>${currentKurs.toFixed(2)} €</td>
                   <td>${changeIcon} ${changePercent}</td>
                 </tr>`;
      });

      html += `</tbody></table>`;
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
  let leftPadding = 60; // Erhöht von 40 auf 60 für mehr Platz bei dreistelligen Zahlen

  // Make a copy to avoid modifying the original array
  const chronologicalPoints = [...points]; // No .reverse() call

  let minVal = Math.min(...chronologicalPoints);
  let maxVal = Math.max(...chronologicalPoints);
  if (minVal === maxVal) {
    minVal -= 1;
    maxVal += 1;
  }

  // Adjust scaleX to use leftPadding on the left side
  let scaleX = (w - leftPadding - padding) / (chronologicalPoints.length - 1);
  let scaleY = (h - 2 * padding) / (maxVal - minVal);

  // Hintergrund-Raster zeichnen
  ctx.beginPath();
  ctx.strokeStyle = "#e0e0e0";
  ctx.lineWidth = 0.5;

  // Horizontale Linien
  for (let i = 0; i <= 5; i++) {
    let y = padding + (i * (h - 2 * padding)) / 5;
    ctx.moveTo(leftPadding, y);
    ctx.lineTo(w - padding, y);
  }

  // Vertikale Linien
  for (let i = 0; i <= 5; i++) {
    let x = leftPadding + (i * (w - leftPadding - padding)) / 5;
    ctx.moveTo(x, padding);
    ctx.lineTo(x, h - padding);
  }
  ctx.stroke();

  // Farbverlauf unter der Linie
  const gradient = ctx.createLinearGradient(0, padding, 0, h - padding);
  if (currentPhase === "Bullenmarkt") {
    gradient.addColorStop(0, 'rgba(76, 175, 80, 0.2)');
    gradient.addColorStop(1, 'rgba(76, 175, 80, 0)');
  } else if (currentPhase === "Bärenmarkt") {
    gradient.addColorStop(0, 'rgba(244, 67, 54, 0.2)');
    gradient.addColorStop(1, 'rgba(244, 67, 54, 0)');
  } else {
    gradient.addColorStop(0, 'rgba(33, 150, 243, 0.2)');
    gradient.addColorStop(1, 'rgba(33, 150, 243, 0)');
  }

  // Fläche unter der Linie - adjusted to use leftPadding
  ctx.beginPath();
  ctx.moveTo(leftPadding, padding + (maxVal - chronologicalPoints[0]) * scaleY);

  for (let i = 0; i < chronologicalPoints.length; i++) {
    let x = leftPadding + i * scaleX;
    let y = padding + (maxVal - chronologicalPoints[i]) * scaleY;
    ctx.lineTo(x, y);
  }

  ctx.lineTo(leftPadding + (chronologicalPoints.length - 1) * scaleX, h - padding);
  ctx.lineTo(leftPadding, h - padding);
  ctx.closePath();
  ctx.fillStyle = gradient;
  ctx.fill();

  // Die Hauptlinie zeichnen - adjusted to use leftPadding
  ctx.beginPath();

  if (currentPhase === "Bullenmarkt") {
    ctx.strokeStyle = "#4caf50";  // Grün für Bullenmarkt
  } else if (currentPhase === "Bärenmarkt") {
    ctx.strokeStyle = "#f44336";  // Rot für Bärenmarkt
  } else {
    ctx.strokeStyle = "#2196f3";  // Blau für neutralen Markt
  }

  ctx.lineWidth = 2;
  ctx.lineJoin = "round";

  for (let i = 0; i < chronologicalPoints.length; i++) {
    let x = leftPadding + i * scaleX;
    let y = padding + (maxVal - chronologicalPoints[i]) * scaleY;
    if (i === 0) {
      ctx.moveTo(x, y);
    } else {
      ctx.lineTo(x, y);
    }
  }
  ctx.stroke();

  // Punkte auf der Linie zeichnen - adjusted to use leftPadding
  for (let i = 0; i < chronologicalPoints.length; i++) {
    let x = leftPadding + i * scaleX;
    let y = padding + (maxVal - chronologicalPoints[i]) * scaleY;

    ctx.beginPath();
    ctx.arc(x, y, 3, 0, Math.PI * 2);
    ctx.fillStyle = "#fff";
    ctx.fill();
    ctx.strokeStyle = ctx.strokeStyle;
    ctx.lineWidth = 1;
    ctx.stroke();
  }

  // Y-Achsen-Beschriftung - deutlich weiter nach links verschoben
  ctx.fillStyle = "#666";
  ctx.font = "11px Arial"; // Leicht vergrößert von 10px auf 11px
  ctx.textAlign = "right";

  for (let i = 0; i <= 5; i++) {
    let value = minVal + (maxVal - minVal) * (5 - i) / 5;
    let y = padding + (i * (h - 2 * padding)) / 5;
    // Move label much further left with more space
    ctx.fillText(value.toFixed(2) + " €", leftPadding - 12, y + 3);
  }
}

/**
 * Verbesserte Funktion für die Aktualisierung der Anzeigeelemente mit Animationen
 */
function updateAnzeigen() {
  const stock = stocksArray.find(s => s.id === selectedStockId);
  if (!stock) return;

  // Briefkurs und Geldkurs mit Animation aktualisieren
  animateValue("briefkursDisplay", "Briefkurs: ", stock.briefkurs.toFixed(2) + " €", "€");
  animateValue("geldkursDisplay", "Geldkurs: ", stock.geldkurs.toFixed(2) + " €", "€");

  // Bestandswert berechnen
  let currentHolding = holdings[selectedStockId] || parseInt(document.getElementById("aktienBestandDisplay").textContent) || 0;
  let costBasis = averageCost[selectedStockId] || 100;
  let profit = (stock.briefkurs - costBasis) * currentHolding;

  // Gewinn/Verlust aktualisieren mit Animation
  const profitEl = document.getElementById("profitDisplay");
  animateValue(profitEl, "", profit.toFixed(2).replace('.', ','), null);

  if (profit >= 0) {
    profitEl.classList.add("profit-positive");
    profitEl.classList.remove("profit-negative");
  } else {
    profitEl.classList.add("profit-negative");
    profitEl.classList.remove("profit-positive");
  }

  // Verbesserte Marktphasen-Anzeige mit visuellen Indikatoren
  const mp = document.getElementById("marketPhaseDisplay");
  mp.className = "market-phase-indicator";

  if (currentPhase === "Bullenmarkt") {
    mp.textContent = "BULLENMARKT (Steigerungs-Chance: 75%)";
    mp.classList.add("bull-market");
    document.getElementById("chartCanvas").classList.add("bull-chart");
    document.getElementById("chartCanvas").classList.remove("bear-chart");

    // Update alle Aktienbuttons
    updateStockButtons();
  } else if (currentPhase === "Bärenmarkt") {
    mp.textContent = "BÄRENMARKT (Fallen-Chance: 75%)";
    mp.classList.add("bear-market");
    document.getElementById("chartCanvas").classList.add("bear-chart");
    document.getElementById("chartCanvas").classList.remove("bull-chart");

    // Update alle Aktienbuttons
    updateStockButtons();
  } else {
    mp.textContent = "NEUTRALER MARKT";
    mp.classList.add("neutral-market");
    document.getElementById("chartCanvas").classList.remove("bull-chart", "bear-chart");

    // Update alle Aktienbuttons
    updateStockButtons();
  }
}

/**
 * Aktualisiert die Trendanzeige bei allen Aktien-Buttons
 */
function updateStockButtons() {
  stocksArray.forEach(stock => {
    const button = document.querySelector(`.stock-button[data-stock-id="${stock.id}"]`);
    if (!button) return;

    const trendDiv = button.querySelector('.stock-trend');
    if (!trendDiv) return;

    // Letzten zwei Kurswerte aus der History holen
    const history = stockHistory[stock.id];
    if (history && history.length >= 2) {
      const currentPrice = history[history.length - 1];
      const previousPrice = history[history.length - 2];

      trendDiv.classList.remove('up', 'down');
      if (currentPrice > previousPrice) {
        trendDiv.classList.add('up');
      } else if (currentPrice < previousPrice) {
        trendDiv.classList.add('down');
      }
    }
  });
}

/**
 * Animiert die Wertänderung eines Elements
 */
function animateValue(element, prefix, newValue, suffix) {
  const el = typeof element === 'string' ? document.getElementById(element) : element;
  if (!el) return;

  // Verbesserte Extraktion des aktuellen Wertes
  // Extrahiert die Zahl unabhängig von ihrer Größe (auch über 100€)
  let current = el.textContent;

  // Animationseffekt hinzufügen
  el.classList.add('value-changing');

  // Nach kurzer Verzögerung neuen Wert setzen
  setTimeout(() => {
    el.textContent = prefix + newValue + (suffix ? ` ${suffix}` : '');
    el.classList.remove('value-changing');
  }, 200);
}

/**
 * Verbesserte History-Anzeige mit farblichen Hervorhebungen und korrekter Datumssortierung
 */
function updateHistoryDisplay() {
  fetch("../includes/api.php?action=get_history&stock_id=" + selectedStockId)
    .then(res => res.json())
    .then(data => {
      if (!data.success) {
        console.error("Fehler beim Laden der History:", data.message);
        return;
      }

      const container = document.getElementById("historyContainer");

      // Sort the history array by date (newest first)
      let sortedHistory = [...data.history];
      try {
        sortedHistory.sort((a, b) => {
          let [dayA, monthA, yearA] = a.tick_time.split('.').map(Number);
          let [dayB, monthB, yearB] = b.tick_time.split('.').map(Number);
          // Create Date objects (adjust month by -1 since JS months are 0-indexed)
          let dateA = new Date(yearA, monthA - 1, dayA);
          let dateB = new Date(yearB, monthB - 1, dayB);
          // Newest first: descending
          return dateB - dateA;
        });
      } catch (e) {
        console.error("Error sorting dates:", e);
        // Use original unsorted data if sorting fails
      }

      let html = `<table class="enhanced-history-table">
                    <thead>
                      <tr>
                        <th>Datum</th>
                        <th>Kurs</th>
                        <th>Änderung</th>
                      </tr>
                    </thead>
                    <tbody>`;

      // Now process the sorted history for display
      sortedHistory.forEach((row, index) => {
        const currentKurs = parseFloat(row.kurs);
        let changeClass = "";
        let changeIcon = "";
        let changePercent = "";

        // Compare with next entry only if it exists
        // Since data is sorted by date, index+1 is an older entry
        if (index < sortedHistory.length - 1) {
          const olderKurs = parseFloat(sortedHistory[index + 1].kurs);
          const diff = currentKurs - olderKurs;
          const percentChange = (diff / olderKurs) * 100;

          if (diff > 0) {
            changeClass = "trend-up";
            changeIcon = '<i class="fas fa-arrow-up"></i>';
            changePercent = `+${percentChange.toFixed(2)}%`;
          } else if (diff < 0) {
            changeClass = "trend-down";
            changeIcon = '<i class="fas fa-arrow-down"></i>';
            changePercent = `${percentChange.toFixed(2)}%`;
          } else {
            changeClass = "trend-neutral";
            changeIcon = '<i class="fas fa-minus"></i>';
            changePercent = "0.00%";
          }
        } else {
          // For the oldest entry with no comparison
          changeClass = "trend-neutral";
          changeIcon = '<i class="fas fa-minus"></i>';
          changePercent = "--";
        }

        html += `<tr class="${changeClass}">
                  <td>${row.tick_time}</td>
                  <td>${currentKurs.toFixed(2)} €</td>
                  <td>${changeIcon} ${changePercent}</td>
                </tr>`;
      });

      html += `</tbody></table>`;
      container.innerHTML = html;
    })
    .catch(err => console.error("Fehler beim AJAX:", err));
}

/**
 * Fügt ein Mini-Portfolio-Widget hinzu
 */
function addPortfolioWidget() {
  const infoBox = document.querySelector('.info-list');
  if (!infoBox) return;

  const portfolioWidget = document.createElement('div');
  portfolioWidget.className = 'portfolio-widget';
  portfolioWidget.innerHTML = `
    <h4>Ihr Mini-Portfolio</h4>
    <div class="portfolio-bars"></div>
  `;

  infoBox.after(portfolioWidget);
  updatePortfolioWidget();
}

/**
 * Aktualisiert das Portfolio-Widget
 */
function updatePortfolioWidget() {
  const container = document.querySelector('.portfolio-bars');
  if (!container) return;

  container.innerHTML = '';

  // Top 5 Aktien nach Bestand ermitteln
  const topStocks = Object.keys(holdings)
    .filter(id => holdings[id] > 0)
    .sort((a, b) => holdings[b] - holdings[a])
    .slice(0, 5);

  if (topStocks.length === 0) {
    container.innerHTML = '<p class="no-stocks">Noch keine Aktien im Portfolio</p>';
    return;
  }

  topStocks.forEach(stockId => {
    const stock = stocksArray.find(s => s.id === parseInt(stockId));
    if (!stock) return;

    const quantity = holdings[stockId];
    const value = quantity * stock.briefkurs;

    const bar = document.createElement('div');
    bar.className = 'portfolio-bar';
    bar.innerHTML = `
      <div class="bar-label">${stock.name}</div>
      <div class="bar-container">
        <div class="bar-fill" style="width: ${Math.min(quantity, 100)}%;"></div>
      </div>
      <div class="bar-value">${quantity} St. (${value.toFixed(2)} €)</div>
    `;

    container.appendChild(bar);
  });
}

// Event-Listener am Ende hinzufügen
document.addEventListener("DOMContentLoaded", () => {
  // Bestehende Initialisierungen
  updateStockHolding();

  // Neue Initialisierungen
  if (document.querySelector('.info-list')) {
    addPortfolioWidget();
  }
});

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
  fetch("../includes/api.php?action=reset_history") // Pfad korrigieren
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

/**
 * Neue Funktion für die Stock-Button-Auswahl
 * Aktualisiert das versteckte Select-Element und ruft die bestehende Logik auf
 */
function selectStock(buttonElement, stockId) {
  // Alle Buttons auf inaktiv setzen
  document.querySelectorAll('.stock-button').forEach(btn => {
    btn.classList.remove('active');
  });

  // Angeklickten Button aktivieren
  buttonElement.classList.add('active');

  // Verstecktes Select-Element aktualisieren
  const stockSelect = document.getElementById('stockSelect');
  stockSelect.value = stockId;

  // Existierende Change-Event-Logik aufrufen
  selectedStockId = parseInt(stockId);
  consecutiveUps = 0;
  consecutiveDowns = 0;
  currentPhase = "";
  updateAnzeigen();
  drawChart();
  updateHistoryDisplay();
  updateStockHolding();
}
