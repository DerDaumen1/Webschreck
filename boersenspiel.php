<?php
session_start();

// Prüfen, ob eingeloggt
if (!isset($_SESSION['angemeldet']) || $_SESSION['angemeldet'] !== true) {
    header('Location: registrierung.php');
    exit;
}

// Die folgenden Zeilen entfernen wir, damit wir nicht mehr hart 50.000 / 0 setzen:
// if (!isset($_SESSION['spielgeld'])) {
//     $_SESSION['spielgeld'] = 50000;
// }
// if (!isset($_SESSION['anzahl_aktien'])) {
//     $_SESSION['anzahl_aktien'] = 0;
// }

// Ab hier nur noch HTML-Struktur
?>
<!DOCTYPE html>
<html lang="de">
<head>
  <meta charset="UTF-8">
  <title>Börsenspiel (AJAX-Version mit ausgelagertem JS)</title>
  <!-- Externe CSS-Datei -->
  <link rel="stylesheet" href="styles.css">

  <!-- Meta-Viewport für Responsive Design -->
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
</head>
<body onload="initGame();">

<header>
  <h1>Börsenspiel (AJAX-Version)</h1>
  <nav>
    <a href="index.html">Startseite</a>
    <a href="logout.php">Logout</a>
  </nav>
</header>

<!-- Optionales Jumbotron / Intro-Bereich -->
<div class="jumbotron">
  <h2>Herzlich willkommen im Börsenspiel!</h2>
  <p>Erleben Sie spielerisch die Welt des Aktienhandels.</p>
</div>

<!-- Hauptbereich (Chart, Meldung, Info-Box) -->
<div class="cards-container">
  <!-- Card 1: Chart -->
  <div class="card">
    <canvas id="chartCanvas" width="700" height="300"></canvas>
  </div>

  <!-- Card 2: Infos + Meldung -->
  <!-- ACHTUNG: Hier kommt "position: relative" auf das Elternelement -->
  <div class="card" style="position: relative;">
    <ul class="info-list">
      <li>
        <strong>Aktuelles Spielgeld:</strong>
        <span id="spielgeldDisplay"><?php echo number_format($_SESSION['spielgeld'], 2, '.', ''); ?></span> €
      </li>
      <li>
        <strong>Anzahl Aktien im Depot:</strong>
        <span id="aktienDepotDisplay"><?php echo $_SESSION['anzahl_aktien']; ?></span>
      </li>
      <li>
        <strong>Aktueller Gewinn/Verlust:</strong>
        <span id="profitDisplay" class="profit-positive">0,00</span> €
      </li>
    </ul>

    <div class="market-phase" id="marketPhaseDisplay"></div>
    <div id="timerDisplay"></div>

    <!-- Meldung-DIV direkt hier platzieren, damit wir sie relativ zur Card positionieren können -->
    <div class="meldung" id="meldungDisplay"></div>
  </div>
</div>


<!-- Kauf/Verkauf-Steuerung -->
<div class="cards-container">
  <div class="card" style="width: 100%;">
    <h2>Kurse & Aktionen</h2>
    <p id="briefkursDisplay" style="margin-bottom:0.5rem;">Briefkurs: 100.00 €</p>
    <p id="geldkursDisplay" style="margin-bottom:0.5rem;">Geldkurs: 99.00 €</p>

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
