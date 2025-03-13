/**
 * Führt einen Handelsauftrag (Kauf, Verkauf oder Spiel beenden) aus.
 * Diese Funktion sendet per AJAX die Anfrage an trade.php und
 * nutzt globale Funktionen (wie updateStockHolding() und updateAnzeigen()),
 * die in boerse.js definiert sind.
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
                // Bestand nach einem erfolgreichen Trade neu laden
                updateStockHolding();
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
