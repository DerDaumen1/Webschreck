// stocks: Array aller Aktien aus PHP
const stocks = window.phpStocks;

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

// --- Seite initialisieren ---
document.addEventListener("DOMContentLoaded", () => {
    loadAllHistories();
    showPage(0);
    updateArrows();
});
