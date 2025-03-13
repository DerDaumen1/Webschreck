<?php
session_start();

// Prüfen, ob eingeloggt
if (!isset($_SESSION['angemeldet']) || $_SESSION['angemeldet'] !== true) {
    header('Location: registrierung.php');
    exit;
}

// Benutzer-ID aus der Session (wichtig für Bestandsabfrage)
$user_id = $_SESSION['user_id'] ?? 0;

// Falls stocks noch nicht gesetzt, initialisieren wir z. B. 10 Musteraktien
if (!isset($_SESSION['stocks'])) {
    $_SESSION['stocks'] = [
      [ 'id'=>1,  'name'=>'Mustermann AG', 'briefkurs'=>100.0, 'geldkurs'=>99.0 ],
      [ 'id'=>2,  'name'=>'Beispiel AG',   'briefkurs'=>100.0, 'geldkurs'=>99.0 ],
      [ 'id'=>3,  'name'=>'Test Inc.',     'briefkurs'=>100.0, 'geldkurs'=>99.0 ],
      [ 'id'=>4,  'name'=>'MegaCorp',      'briefkurs'=>100.0, 'geldkurs'=>99.0 ],
      [ 'id'=>5,  'name'=>'Future Ltd.',   'briefkurs'=>100.0, 'geldkurs'=>99.0 ],
      [ 'id'=>6,  'name'=>'Sample GmbH',   'briefkurs'=>100.0, 'geldkurs'=>99.0 ],
      [ 'id'=>7,  'name'=>'Hallo AG',      'briefkurs'=>100.0, 'geldkurs'=>99.0 ],
      [ 'id'=>8,  'name'=>'World Ind.',    'briefkurs'=>100.0, 'geldkurs'=>99.0 ],
      [ 'id'=>9,  'name'=>'Börsenspiel SE','briefkurs'=>100.0, 'geldkurs'=>99.0 ],
      [ 'id'=>10, 'name'=>'Fantasy PLC',   'briefkurs'=>100.0, 'geldkurs'=>99.0 ]
    ];
}

// Ebenfalls eine Session-Struktur für die Historie (letzte 10 Kurse)
if (!isset($_SESSION['stock_history'])) {
  $_SESSION['stock_history'] = [];
  foreach($_SESSION['stocks'] as $s) {
    $_SESSION['stock_history'][$s['id']] = [100.0]; // Start-Kurs in History
  }
}
?>
<!DOCTYPE html>
<html lang="de">
<head>
  <meta charset="UTF-8">
  <title>Börsenspiel (AJAX-Version mit ausgelagertem JS)</title>
  <link rel="stylesheet" href="styles.css">
  <!-- Optionales Inline-Styling, damit die Meldung weiter unten steht -->
  <style>
    .meldung {
      margin-top: 1rem; /* Abstand zwischen den Info-Zeilen und der Meldung */
    }
  </style>
</head>
<body onload="initGame();">

<header>
  <div class="header-container">
    <div class="nav-left">
      <a href="index.php">Startseite</a>
      <a href="stock_overview.php">Aktienübersicht</a>
      <a href="orderbuch.php">Orderbuch</a>
      <a href="logout.php">Logout</a>
    </div>
    <div class="header-center">
      <h1>Börsenspiel (AJAX-Version)</h1>
      <p>Herzlich willkommen im Börsenspiel – Erleben Sie spielerisch die Welt des Aktienhandels!</p>
    </div>
    <div class="nav-right">
      <!-- (Optional: Hier könnte man das Spielgeld oder den Usernamen platzieren) -->
    </div>
  </div>
</header>

<main class="boerse-main">
  <!-- Drei breite Cards/Monitore nebeneinander (Chart, Info, Aktionen) -->
  <div class="cards-container three-columns">
    
    <!-- Chart-Bereich -->
    <div class="card">
      <canvas id="chartCanvas" width="700" height="300"></canvas>
    </div>

    <!-- Info-Box + Meldung darunter -->
    <div class="card" style="position: relative;">
      <ul class="info-list">
        <li>
          <strong>Aktuelles Spielgeld:</strong>
          <span id="spielgeldDisplay">
            <?php echo number_format($_SESSION['spielgeld'] ?? 50000, 2, '.', ''); ?>
          </span> €
        </li>
        <li>
          <strong>Aktueller Bestand (gewählte Aktie):</strong>
          <span id="aktienBestandDisplay">0</span> Stück
        </li>
        <li>
          <strong>Aktueller Gewinn/Verlust:</strong>
          <span id="profitDisplay" class="profit-positive">0,00</span> €
        </li>
      </ul>

      <!-- Meldung jetzt UNTERHALB der Info-Zeilen -->
      

      <div id="marketPhaseDisplay"></div>
      <div id="timerDisplay"></div>

      <div class="meldung" id="meldungDisplay"></div>
    </div>

    <!-- Kauf/Verkauf-Steuerung -->
    <div class="card" style="width: 100%;">
      <h2>Kurse & Aktionen</h2>
      <div class="cards-container" style="justify-content: center;">
        <div class="card" style="text-align:center; max-width: 300px;">
          <label for="stockSelect">Aktie wählen:</label>
          <select id="stockSelect" onchange="updateStockHolding();">
            <option value="1">Mustermann AG</option>
            <option value="2">Beispiel AG</option>
            <option value="3">Test Inc.</option>
            <option value="4">MegaCorp</option>
            <option value="5">Future Ltd.</option>
            <option value="6">Sample GmbH</option>
            <option value="7">Hallo AG</option>
            <option value="8">World Ind.</option>
            <option value="9">Börsenspiel SE</option>
            <option value="10">Fantasy PLC</option>
          </select>
        </div>
      </div>

      <p id="briefkursDisplay">Briefkurs: 100.00 €</p>
      <p id="geldkursDisplay">Geldkurs: 99.00 €</p>

      <div class="form-group">
        <label for="anzahlInput">Anzahl:</label>
        <input type="number" id="anzahlInput" value="1" min="1" />
        <div class="trade-row">
          <button class="btn" onclick="trade('buy')">Aktien kaufen</button>
          <button class="btn" onclick="trade('sell')">Aktien verkaufen</button>
          <button class="btn btn-secondary" onclick="trade('beenden')">Spiel beenden</button>
        </div>
      </div>
    </div>

  </div>
</main>

<footer>
  <div class="footer-container">
    &copy; <?php echo date("Y"); ?> Mein Börsenspiel - Alle Rechte vorbehalten.
    <a href="impressum.php">Impressum</a>
  </div>
</footer>

<script src="boerse.js"></script>
</body>
</html>
