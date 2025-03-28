// stocks: Array aller Aktien aus PHP
const stocks = window.phpStocks;

// Neue globale Variable zur Speicherung aktueller Aktienwerte
window.currentStockValues = {};
window.currentStockShares = {};  // Neu hinzugefügt

// --- Carousel-Logik ---
const cardsWrapper = document.getElementById("cardsWrapper");
const totalCards = stocks.length;
let cardsPerPage = getCardsPerPage(); // Dynamisch basierend auf Bildschirmbreite
let totalPages = Math.ceil(totalCards / cardsPerPage);
let currentIndex = 0;

const prevBtn = document.getElementById("prevBtn");
const nextBtn = document.getElementById("nextBtn");

// Funktion zur Bestimmung der Anzahl von Karten pro Seite basierend auf Bildschirmbreite
function getCardsPerPage() {
    const width = window.innerWidth;
    if (width <= 767) return 1;     // Alle Handys - jetzt nur 1 Karte
    if (width <= 1024) return 3;    // Tablets
    return 5;                       // Desktop (Original)
}

function showPage(index) {
    // Standard offset calculation
    let offset = -index * 100;

    // Special handling for the last page, specifically on tablets
    const width = window.innerWidth;
    if (width <= 1024 && width > 767 && index === totalPages - 1) {
        // On a tablet and on the last page
        const remainingCards = totalCards % cardsPerPage;

        // Only adjust if not a full page of cards remains
        if (remainingCards !== 0 && remainingCards !== cardsPerPage) {
            // Calculate the maximum offset so that the last card is visible
            // For example, with 10 cards total and 3 per page, the last page has 1 card
            // We want to show cards 7, 8, 9 (index 6,7,8) where 9 is the last one
            const totalCardWidth = 100 / cardsPerPage; // Width per card as percentage
            const visibleCards = Math.min(cardsPerPage, totalCards - (index * cardsPerPage));
            offset = -((totalCards - visibleCards) * totalCardWidth);
        }
    }

    cardsWrapper.style.transform = `translateX(${offset}%)`;
    updateArrows();
}

function updateArrows() {
    prevBtn.style.backgroundColor = (currentIndex <= 0) ? "#666" : "#3f51b5";
    nextBtn.style.backgroundColor = (currentIndex >= totalPages - 1) ? "#666" : "#3f51b5";
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

// Recalculate on window resize
window.addEventListener('resize', () => {
    const newCardsPerPage = getCardsPerPage();
    if (newCardsPerPage !== cardsPerPage) {
        cardsPerPage = newCardsPerPage;
        totalPages = Math.ceil(totalCards / cardsPerPage);
        // Adjust currentIndex if it's now out of bounds
        currentIndex = Math.min(currentIndex, totalPages - 1);
        showPage(currentIndex);
    }
});

// --- History per AJAX laden mit verbesserten visuellen Indikatoren ---
function loadAllHistories() {
    stocks.forEach(stock => {
        const stockId = stock.id;
        fetch("../includes/api.php?action=get_history&stock_id=" + stockId)
            .then(res => res.json())
            .then(data => {
                if (!data.success) {
                    console.error("Fehler beim Laden der History für Aktie " + stockId, data.message);
                    return;
                }
                const container = document.getElementById("historyContainer-" + stockId);
                if (!container) return;

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

                        // Newest first: descending order
                        return dateB - dateA;
                    });
                } catch (e) {
                    console.error("Error sorting dates:", e);
                    // Use original unsorted data if sorting fails
                }

                // HTML mit verbesserten visuellen Indikatoren erstellen
                let html = "<table class='history-table'><thead><tr><th>Datum</th><th>Kurs</th><th>Trend</th></tr></thead><tbody>";

                // Use the sorted data with newest first
                sortedHistory.forEach((row, i) => {
                    let price = parseFloat(row.kurs);
                    let trendClass = "";
                    let trendIcon = "";

                    // Vergleich mit dem neueren Eintrag (i-1) statt mit dem älteren (i+1)
                    if (i > 0) {
                        let newerPrice = parseFloat(sortedHistory[i - 1].kurs);
                        if (price > newerPrice) {
                            trendClass = "trend-down"; // Kurs ist gefallen (neuerer Kurs ist niedriger)
                            trendIcon = '<i class="fas fa-arrow-down" style="color:red;"></i>';
                        } else if (price < newerPrice) {
                            trendClass = "trend-up"; // Kurs ist gestiegen (neuerer Kurs ist höher)
                            trendIcon = '<i class="fas fa-arrow-up" style="color:green;"></i>';
                        } else {
                            trendClass = "trend-neutral";
                            trendIcon = '<i class="fas fa-minus" style="color:gray;"></i>';
                        }
                    }

                    html += `<tr class="${trendClass}">
                        <td>${row.tick_time}</td>
                        <td>${price.toFixed(2)} €</td>
                        <td>${trendIcon}</td>
                    </tr>`;
                });

                html += "</tbody></table>";
                container.innerHTML = html;

                // Wenn Mini-Charts verwendet werden, aktualisieren wir diese auch
                if (typeof updateMiniChart === 'function') {
                    updateMiniChart(stockId, sortedHistory);
                }
            })
            .catch(err => console.error("Fehler beim AJAX für Aktie " + stockId, err));
    });
}

function updateCurrentValues() {
    stocks.forEach(stock => {
        // Zuerst den aktuellen Kurs aus der History abrufen
        fetch("../includes/api.php?action=get_history&stock_id=" + stock.id) // Pfad korrigieren
            .then(res => res.json())
            .then(historyData => {
                if (historyData.success && historyData.history.length > 0) {
                    // Nehmen wir an, der erste Eintrag ist der neueste Kurs
                    let latestPrice = parseFloat(historyData.history[0].kurs);
                    // Jetzt den aktuellen Bestand abfragen:
                    fetch("../includes/api.php?action=get_holding&stock_id=" + stock.id) // Pfad korrigieren
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
                                window.currentStockShares[stock.id] = holdingData.bestand;  // Neu
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
    let totalShares = 0;
    for (let id in window.currentStockValues) {
        depotValue += window.currentStockValues[id];
    }
    for (let id in window.currentStockShares) {
        totalShares += window.currentStockShares[id];
    }
    // Nutze das per PHP eingebundene aktuelle Spielgeld
    let spielgeld = parseFloat(window.currentSpielgeld);
    let depotGesamt = spielgeld + depotValue;
    let gewinnVerlust = depotGesamt - window.startKapital;

    // Aktualisierung der globalen Anzeige – jetzt ohne doppelte Labels
    document.getElementById("aktienDepotDisplay").textContent = depotValue.toFixed(2).replace('.', ',') + " €";
    document.getElementById("depotGesamtDisplay").textContent = depotGesamt.toFixed(2).replace('.', ',') + " €";
    document.getElementById("gewinnVerlustDisplay").textContent = gewinnVerlust.toFixed(2).replace('.', ',') + " €";
    document.getElementById("totalSharesDisplay").textContent = totalShares.toString();

    // Setze die Textfarbe je nach Gewinn/Verlust
    const gvElem = document.getElementById("gewinnVerlustDisplay");
    if (gewinnVerlust >= 0) {
        gvElem.style.color = "#90ee90"; // helles grün
    } else {
        gvElem.style.color = "red";
    }
}

// Rufe die Funktion zusammen mit den anderen Initialisierungen auf
document.addEventListener("DOMContentLoaded", () => {
    loadAllHistories();
    showPage(0);
    updateArrows();
    updateCurrentValues(); // Neuer Aufruf, um den aktuellen Wert zu berechnen und anzuzeigen

    // Automatisches Rotieren des Carousels alle 10 Sekunden
    setInterval(() => {
        if (currentIndex < totalPages - 1) {
            currentIndex++;
        } else {
            currentIndex = 0;
        }
        showPage(currentIndex);
    }, 20000);
});