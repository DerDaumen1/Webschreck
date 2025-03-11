<?php
session_start();
// Prüfen, ob eingeloggt
if (!isset($_SESSION['angemeldet']) || $_SESSION['angemeldet'] !== true) {
    header('Location: login.php');
    exit;
}

// Minimal-Demo: Falls noch keine Stocks in der Session sind
if (!isset($_SESSION['all_stocks'])) {
    $_SESSION['all_stocks'] = [
      ["id"=>1, "name"=>"Mustermann AG",  "briefkurs"=>100, "geldkurs"=>99],
      ["id"=>2, "name"=>"Beispiel AG",    "briefkurs"=>100, "geldkurs"=>99],
      ["id"=>3, "name"=>"Test Inc.",      "briefkurs"=>100, "geldkurs"=>99],
      ["id"=>4, "name"=>"MegaCorp",       "briefkurs"=>100, "geldkurs"=>99],
      ["id"=>5, "name"=>"Future Ltd.",    "briefkurs"=>100, "geldkurs"=>99],
      ["id"=>6, "name"=>"Sample GmbH",    "briefkurs"=>100, "geldkurs"=>99],
      ["id"=>7, "name"=>"Hallo AG",       "briefkurs"=>100, "geldkurs"=>99],
      ["id"=>8, "name"=>"World Ind.",     "briefkurs"=>100, "geldkurs"=>99],
      ["id"=>9, "name"=>"Börsenspiel SE", "briefkurs"=>100, "geldkurs"=>99],
      ["id"=>10,"name"=>"Fantasy PLC",    "briefkurs"=>100, "geldkurs"=>99]
    ];
}
$stocks = $_SESSION['all_stocks'];
?>
<!DOCTYPE html>
<html lang="de">
<head>
  <meta charset="UTF-8">
  <title>Aktienübersicht</title>
  <link rel="stylesheet" href="styles.css">
</head>
<body>
<header>
  <h1>Aktienübersicht</h1>
  <nav>
    <a href="index.php">Zur Startseite</a> |
    <a href="boersenspiel.php">Zum Börsenspiel</a>
  </nav>
</header>

<div class="cards-container">
  <?php foreach ($stocks as $st): ?>
    <div class="card">
      <h2><?= htmlspecialchars($st['name']) ?></h2>
      <p>Briefkurs: <?= number_format($st['briefkurs'],2,',','.') ?> €</p>
      <p>Geldkurs: <?= number_format($st['geldkurs'],2,',','.') ?> €</p>

      <!-- Container für die History dieses Stocks -->
      <h3>Letzte 10 Ticks</h3>
      <div id="historyContainer-<?= $st['id'] ?>" style="min-height:80px;">
        <!-- Hier wird per AJAX die Tabelle eingefügt -->
      </div>
    </div>
  <?php endforeach; ?>
</div>

<script>
// stocks: Array aller Aktien aus PHP
const stocks = <?= json_encode($stocks) ?>;

/**
 * Lädt pro Aktie (stock_id) die letzten 10 Ticks
 * und schreibt sie in #historyContainer-<stock_id>.
 */
function loadAllHistories() {
  // Für jede Aktie einen AJAX-Call
  stocks.forEach(stock => {
    const stockId = stock.id;
    fetch("get_history.php?stock_id=" + stockId)
      .then(res => res.json())
      .then(data => {
        if (!data.success) {
          console.error("Fehler beim Laden der History:", data.message);
          return;
        }
        // Container für diese Aktie
        const container = document.getElementById("historyContainer-" + stockId);
        if (!container) return; // Sicherheit

        let html = "<table><tr><th>Datum</th><th>Kurs</th></tr>";
        data.history.forEach(row => {
          html += "<tr>"
                + "<td>" + row.tick_time + "</td>"
                + "<td>" + parseFloat(row.kurs).toFixed(2) + " €</td>"
                + "</tr>";
        });
        html += "</table>";

        container.innerHTML = html;
      })
      .catch(err => console.error("Fehler beim AJAX:", err));
  });
}

/**
 * Beim Laden der Seite:
 * 1) loadAllHistories() aufrufen
 * 2) optional: setInterval, um alle X Sekunden zu aktualisieren
 */
document.addEventListener("DOMContentLoaded", () => {
  loadAllHistories();
  // Alle 10 Sekunden neu laden:
  // setInterval(loadAllHistories, 10000);
});
</script>
</body>
</html>
