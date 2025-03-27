// stocks: Array aller Aktien aus PHP
const stocks = window.phpStocks;

// Neue globale Variable zur Speicherung aktueller Aktienwerte
window.currentStockValues = {};

// --- Carousel-Logik ---
const cardsWrapper = document.getElementById("cardsWrapper");
const totalCards = stocks.length;
const cardsPerPage = 5;
const totalPages = Math.ceil(totalCards / cardsPerPage);
let currentIndex = 0;

const prevBtn = document.getElementById("prevBtn");
const nextBtn = document.getElementById("nextBtn");

function showPage(index) {
    const offset = -index * 100;
    cardsWrapper.style.transform = `translateX(${offset}%)`;
    updateArrows();
}

function updateArrows() {
    prevBtn.style.backgroundColor = (currentIndex <= 0) ? "#666" : "orange";
    nextBtn.style.backgroundColor = (currentIndex >= totalPages - 1) ? "#666" : "orange";
}

prevBtn.addEventListener("click", () => {
    if (currentIndex > 0) {
        currentIndex--;
        showPage(currentIndex);
    }
});

nextBtn.addEventListener("click", () => {
    if (currentIndex < totalPages - 1) {
        currentIndex++;
        showPage(currentIndex);
    }
});

// --- History per AJAX laden ---
function loadAllHistories() {
    stocks.forEach(stock => {
        const stockId = stock.id;
        fetch("get_history.php?stock_id=" + stockId)
            .then(res => res.json())
            .then(data => {
                if (!data.success) {
                    console.error("Fehler beim Laden der History für Aktie " + stockId, data.message);
                    return;
                }
                const container = document.getElementById("historyContainer-" + stockId);
                if (!container) return;
                let html = "<table class='history-table'><thead><tr><th>Datum</th><th>Kurs</th></tr></thead><tbody>";
                data.history.forEach(row => {
                    html += `<tr><td>${row.tick_time}</td><td>${parseFloat(row.kurs).toFixed(2)} €</td></tr>`;
                });
                html += "</tbody></table>";
                container.innerHTML = html;
            })
            .catch(err => console.error("Fehler beim AJAX für Aktie " + stockId, err));
    });
}
function updateCurrentValues() {
    stocks.forEach(stock => {
        // Zuerst den aktuellen Kurs aus der History abrufen
        fetch("get_history.php?stock_id=" + stock.id)
            .then(res => res.json())
            .then(historyData => {
                if (historyData.success && historyData.history.length > 0) {
                    // Nehmen wir an, der erste Eintrag ist der neueste Kurs
                    let latestPrice = parseFloat(historyData.history[0].kurs);
                    // Jetzt den aktuellen Bestand abfragen
                    fetch("get_holding.php?stock_id=" + stock.id)
                        .then(res => res.json())
                        .then(holdingData => {
                            if (holdingData.success) {
                                let currentValue = holdingData.bestand * latestPrice;
                                let elem = document.getElementById("currentValue-" + stock.id);
                                if (elem) {
                                    elem.textContent = currentValue.toFixed(2).replace('.', ',');
                                }
                                // Speichern des berechneten Werts
                                window.currentStockValues[stock.id] = currentValue;
                                // Aktualisiere das globale Portfolio
                                updateGlobalPortfolio();
                            } else {
                                console.error("Bestand-Fehler für Aktie " + stock.id + ":", holdingData.message);
                            }
                        })
                        .catch(err => console.error("AJAX-Fehler bei Bestandsabfrage:", err));
                } else {
                    console.error("History-Fehler für Aktie " + stock.id + ":", historyData.message);
                }
            })
            .catch(err => console.error("AJAX-Fehler bei History-Abfrage:", err));
    });
}

function updateGlobalPortfolio() {
    let depotValue = 0;
    for (let id in window.currentStockValues) {
        depotValue += window.currentStockValues[id];
    }
    // Nutze das per PHP eingebundene aktuelle Spielgeld
    let spielgeld = parseFloat(window.currentSpielgeld);
    let depotGesamt = spielgeld + depotValue;
    let gewinnVerlust = depotGesamt - window.startKapital;

    // Aktualisierung der globalen Anzeige – jetzt ohne doppelte Labels
    document.getElementById("aktienDepotDisplay").textContent = depotValue.toFixed(2).replace('.', ',') + " €";
    document.getElementById("depotGesamtDisplay").textContent = depotGesamt.toFixed(2).replace('.', ',') + " €";
    document.getElementById("gewinnVerlustDisplay").textContent = gewinnVerlust.toFixed(2).replace('.', ',') + " €";
}

// Rufe die Funktion zusammen mit den anderen Initialisierungen auf
document.addEventListener("DOMContentLoaded", () => {
    loadAllHistories();
    showPage(0);
    updateArrows();
    updateCurrentValues(); // Neuer Aufruf, um den aktuellen Wert zu berechnen und anzuzeigen

    // Toggle event removed because depot info is now always visible in the header.
});