<?php
session_start();

// Prüfen, ob eingeloggt
if (!isset($_SESSION['angemeldet']) || $_SESSION['angemeldet'] !== true) {
    header('Location: registrierung.php');
    exit;
}

// Falls stocks noch nicht gesetzt, initialisieren wir z. B. 10 Musteraktien
if (!isset($_SESSION['stocks'])) {
    $_SESSION['stocks'] = [
      [ 'id'=>1, 'name'=>'Mustermann AG', 'briefkurs'=>100.0, 'geldkurs'=>99.0 ],
      [ 'id'=>2, 'name'=>'Beispiel AG',   'briefkurs'=>100.0, 'geldkurs'=>99.0 ],
      [ 'id'=>3, 'name'=>'Test Inc.',     'briefkurs'=>100.0, 'geldkurs'=>99.0 ],
      [ 'id'=>4, 'name'=>'MegaCorp',      'briefkurs'=>100.0, 'geldkurs'=>99.0 ],
      [ 'id'=>5, 'name'=>'Future Ltd.',   'briefkurs'=>100.0, 'geldkurs'=>99.0 ],
      [ 'id'=>6, 'name'=>'Sample GmbH',   'briefkurs'=>100.0, 'geldkurs'=>99.0 ],
      [ 'id'=>7, 'name'=>'Hallo AG',      'briefkurs'=>100.0, 'geldkurs'=>99.0 ],
      [ 'id'=>8, 'name'=>'World Ind.',    'briefkurs'=>100.0, 'geldkurs'=>99.0 ],
      [ 'id'=>9, 'name'=>'Börsenspiel SE', 'briefkurs'=>100.0, 'geldkurs'=>99.0 ],
      [ 'id'=>10,'name'=>'Fantasy PLC',   'briefkurs'=>100.0, 'geldkurs'=>99.0 ]
    ];
}
// Ebenfalls eine Session-Struktur für die Historie (letzte 10 Kurse)
if (!isset($_SESSION['stock_history'])) {
  $_SESSION['stock_history'] = [];
  foreach($_SESSION['stocks'] as $s) {
    $_SESSION['stock_history'][$s['id']] = [100.0]; // Start-Kurs in History
  }
}

// Ab hier nur noch HTML
?>
<!DOCTYPE html>
<html lang="de">
<head>
  <meta charset="UTF-8">
  <title>Börsenspiel (AJAX-Version mit ausgelagertem JS)</title>
  <link rel="stylesheet" href="styles.css">
</head>
<body onload="initGame();">

<header>
  <h1>Börsenspiel (AJAX-Version)</h1>
  <nav>
    <a href="index.php">Startseite</a> |
    <a href="stock_overview.php">Aktienübersicht</a> |
    <a href="orderbuch.php">Orderbuch</a> |
    <a href="logout.php">Logout</a>
  </nav>
</header>

<div class="jumbotron">
  <h2>Herzlich willkommen im Börsenspiel!</h2>
  <p>Erleben Sie spielerisch die Welt des Aktienhandels.</p>
</div>

<div class="cards-container">
  <!-- Chart, Meldung, Info-Box wie gehabt -->
  <div class="card">
    <canvas id="chartCanvas" width="700" height="300"></canvas>
  </div>

  <div class="card" style="position: relative;">
    <div class="meldung" id="meldungDisplay"></div>

    <ul class="info-list">
      <li><strong>Aktuelles Spielgeld:</strong> <span id="spielgeldDisplay">
        <?php echo number_format($_SESSION['spielgeld'] ?? 50000, 2, '.', ''); ?></span> €
      </li>
      <li><strong>Gesamt-Aktien (alter Wert):</strong> <span id="aktienDepotDisplay">
        <?php echo $_SESSION['anzahl_aktien'] ?? 0; ?></span>
      </li>
      <li><strong>Aktueller Gewinn/Verlust:</strong> <span id="profitDisplay" class="profit-positive">0,00</span> €
      </li>
    </ul>

    <div id="marketPhaseDisplay"></div>
    <div id="timerDisplay"></div>
  </div>
</div>

<!-- Kauf/Verkauf-Steuerung -->
<div class="cards-container">
  <div class="card" style="width: 100%;">
    <h2>Kurse & Aktionen</h2>
    <!-- Hier: Auswahl der Aktie -->
    <label for="stockSelect">Aktie wählen:</label>
    <select id="stockSelect">
      <?php foreach($_SESSION['stocks'] as $s): ?>
        <option value="<?= $s['id'] ?>"><?= htmlspecialchars($s['name']) ?></option>
      <?php endforeach; ?>
    </select>

    <p id="briefkursDisplay">Briefkurs: 100.00 €</p>
    <p id="geldkursDisplay">Geldkurs: 99.00 €</p>

    <div class="form-group">
      <label for="anzahlInput">Anzahl:</label>
      <input type="number" id="anzahlInput" value="1" min="1" />
      <button class="btn" onclick="trade('kaufen')">Aktien kaufen</button>
      <button class="btn" onclick="trade('verkaufen')">Aktien verkaufen</button>
      <button class="btn btn-secondary" onclick="trade('beenden')">Spiel beenden</button>
    </div>
  </div>
</div>

<footer>
  &copy; <?php echo date("Y"); ?> Mein Börsenspiel - Alle Rechte vorbehalten.
</footer>

<!-- Externe JavaScript-Datei laden -->
<script src="boerse.js"></script>
</body>
</html>
