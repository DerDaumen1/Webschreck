/**
 * Mini-Charts für die Aktienübersicht
 * Diese Datei erstellt kleine Charts für jede Aktie in der Übersicht
 */

// Globaler Datenobjektspeicher für Charts
let miniChartData = {};

// Farbkonfiguration für Charts
const chartConfig = {
    positiveColor: 'rgba(75, 192, 192, 0.5)',
    negativeColor: 'rgba(255, 99, 132, 0.5)',
    neutralColor: 'rgba(128, 128, 128, 0.5)',
    lineBorderColor: 'rgba(0, 0, 0, 0.8)',
    pointBorderColor: '#222',
    gridColor: 'rgba(0, 0, 0, 0.1)'
};

// Charts für alle Aktien initialisieren
function initMiniCharts() {
    const stocks = window.phpStocks;
    if (!stocks) return;

    stocks.forEach(stock => {
        // Zuerst die Kursdaten für diese Aktie per AJAX laden
        fetchStockData(stock.id);
    });
}

// AJAX Anfrage für die Kursdaten einer Aktie
function fetchStockData(stockId) {
    fetch(`../includes/api.php?action=get_history&stock_id=${stockId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success && data.history.length > 0) {
                // Daten für Chart aufbereiten
                const chartData = prepareChartData(data.history);
                // Chart erstellen
                createMiniChart(stockId, chartData);
            }
        })
        .catch(error => console.error('Fehler beim Laden der Kursdaten:', error));
}

// Bereitet die Kursdaten für das Chart vor
function prepareChartData(historyData) {
    // History-Daten sind in umgekehrter Reihenfolge (neueste zuerst)
    // Für ein Chart müssen wir sie umdrehen, damit älteste Daten links und neueste rechts stehen
    const reversedData = [...historyData].reverse();

    // Daten extrahieren
    const labels = reversedData.map(item => item.tick_time);
    const prices = reversedData.map(item => parseFloat(item.kurs));

    // Gradient-Farbe basierend auf Kursverlauf (vergleicht erstes/ältestes mit letztem/neuestem)
    let backgroundColor = chartConfig.neutralColor;
    if (prices.length > 1) {
        if (prices[prices.length - 1] > prices[0]) {
            backgroundColor = chartConfig.positiveColor; // Neuester Kurs höher als ältester = positiv
        } else if (prices[prices.length - 1] < prices[0]) {
            backgroundColor = chartConfig.negativeColor; // Neuester Kurs niedriger als ältester = negativ
        }
    }

    return {
        labels: labels,
        prices: prices,
        backgroundColor: backgroundColor
    };
}

// Erstellt ein Mini-Chart für eine Aktie
function createMiniChart(stockId, chartData) {
    const canvasId = `miniChart-${stockId}`;
    const ctx = document.getElementById(canvasId)?.getContext('2d');
    if (!ctx) return;

    // Chart erstellen
    const chart = new Chart(ctx, {
        type: 'line',
        data: {
            labels: chartData.labels,
            datasets: [{
                label: 'Kurs (€)',
                data: chartData.prices,
                backgroundColor: chartData.backgroundColor,
                borderColor: chartConfig.lineBorderColor,
                borderWidth: 1,
                pointBorderColor: chartConfig.pointBorderColor,
                pointBackgroundColor: '#fff',
                pointRadius: 3,
                pointHoverRadius: 5,
                tension: 0.1,
                fill: true
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: {
                    beginAtZero: false,
                    grid: {
                        color: chartConfig.gridColor
                    }
                },
                x: {
                    grid: {
                        display: false
                    },
                    ticks: {
                        maxRotation: 45,
                        minRotation: 45
                    }
                }
            },
            plugins: {
                legend: {
                    display: false
                },
                tooltip: {
                    mode: 'index',
                    intersect: false,
                    callbacks: {
                        label: function (context) {
                            return `Kurs: ${context.parsed.y.toFixed(2)} €`;
                        }
                    }
                }
            }
        }
    });

    // Chart im globalen Objekt speichern
    miniChartData[stockId] = chart;
}

// Aktualisiert das Chart einer Aktie mit neuen Daten
function updateMiniChart(stockId, newData) {
    const chart = miniChartData[stockId];
    if (!chart) return;

    const chartData = prepareChartData(newData);

    chart.data.labels = chartData.labels;
    chart.data.datasets[0].data = chartData.prices;
    chart.data.datasets[0].backgroundColor = chartData.backgroundColor;

    chart.update();
}

// Event-Listener hinzufügen, um Charts zu initialisieren, sobald die Seite geladen ist
document.addEventListener('DOMContentLoaded', initMiniCharts);
