// boerse.js

let canvas, ctx;
let briefKurs = 100.0;
let geldKurs  = 99.0;
let kursVerlauf = []; // Array von Zahlen
let steigungenInFolge = 0;
let faelleInFolge = 0;

// Bullen- oder Bärenmarkt?
// Bär: fallende Kurse, Bulle: steigende Kurse
// Wahrscheinlichkeiten anpassen (z.B. 75% vs 25%)
let marktphase = "neutral"; // kann "baer" oder "bulle" sein

function initChart() {
  canvas = document.getElementById("chartContainer");
  if (!canvas) return;

  // Da wir keinen "canvas" Tag, sondern ein div haben, 
  // könnte man hier in echt <canvas> benutzen:
  // Für Demo: Wir zeichnen in ein leeres DIV als "Pseudo-Chart"
  // Besser: <canvas id="chartCanvas"></canvas>
  // Dann ctx = document.getElementById("chartCanvas").getContext("2d");
  
  updateAnzeige();
  
  // Start-Kurs in kursVerlauf
  kursVerlauf.push(briefKurs);

  // Jede Sekunde einen neuen Kurs berechnen
  setInterval(() => {
    aktualisiereKurse();
    zeichneChart();
  }, 1000);
}

function aktualisiereKurse() {
  // Normal: +/- 0,01 bis 0,05
  // Falls 3x in Folge fallend => "Bärenmarkt" => steigungWahrscheinlichkeit = 25%
  // Falls 3x in Folge steigend => "Bullenmarkt" => steigungWahrscheinlichkeit = 75%
  
  let steigungChance = 0.5; // 50%
  if (faelleInFolge >= 3) {
    marktphase = "baer";
    steigungChance = 0.25;
  } else if (steigungenInFolge >= 3) {
    marktphase = "bulle";
    steigungChance = 0.75;
  } else {
    marktphase = "neutral";
  }
  
  // Bestimmen ob steigt oder fällt
  let zufall = Math.random();
  let delta = (Math.random() * 0.04) + 0.01; // 0,01 bis 0,05
  if (zufall <= steigungChance) {
    // steigt
    briefKurs += delta;
    geldKurs += delta;
    
    steigungenInFolge++;
    faelleInFolge = 0;
  } else {
    // fällt
    briefKurs -= delta;
    geldKurs -= delta;
    
    faelleInFolge++;
    steigungenInFolge = 0;
  }
  
  // Speichern im Verlauf
  kursVerlauf.push(briefKurs);
  
  // Anzeige in HTML
  updateAnzeige();
  
  // Hidden-Felder aktualisieren, damit PHP beim Kauf/Verkauf den aktuellen Kurs hat
  document.getElementById('briefkursHidden').value = briefKurs.toFixed(2);
  document.getElementById('geldkursHidden').value = geldKurs.toFixed(2);
}

function updateAnzeige() {
  let briefSpan = document.getElementById("briefkursDisplay");
  let geldSpan  = document.getElementById("geldkursDisplay");
  if (briefSpan) briefSpan.textContent = "Briefkurs: " + briefKurs.toFixed(2) + " €";
  if (geldSpan)  geldSpan.textContent  = "Geldkurs: " + geldKurs.toFixed(2) + " €";
}

function zeichneChart() {
  // In echtem Projekt auf <canvas> zeichnen. Hier als Demo:
  let chartDiv = document.getElementById("chartContainer");
  if (!chartDiv) return;
  
  // Einfachen Text oder ASCII-art
  let maxPunkte = 50; // wie viele Datenpunkte wir im "Chart" zeigen
  let start = kursVerlauf.length - maxPunkte;
  if (start < 0) start = 0;
  let relevantePunkte = kursVerlauf.slice(start);
  
  // Erstelle eine einfache ASCII-Kurve als String
  // (Das ist nur symbolisch, besser echte Canvas-Zeichnungen!)
  let lines = [];
  let max = Math.max(...relevantePunkte);
  let min = Math.min(...relevantePunkte);
  let range = max - min || 1;
  
  // Höhe: 10 Zeilen
  let chartHeight = 10;
  
  for (let y = 0; y < chartHeight; y++) {
    lines.push("");
  }
  
  relevantePunkte.forEach((kp, index) => {
    // Mappe kp auf den Bereich 0..chartHeight-1
    let normalized = (kp - min) / range;
    let row = chartHeight - 1 - Math.round(normalized * (chartHeight - 1));
    // Fülle Zeile
    for (let r = 0; r < chartHeight; r++) {
      if (r === row) {
        lines[r] += "*";
      } else {
        lines[r] += " ";
      }
    }
  });
  
  let asciiChart = lines.map(line => line).join("\n");
  chartDiv.textContent = asciiChart;
}
