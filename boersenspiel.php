<?php
session_start();

if (!isset($_SESSION['angemeldet']) || $_SESSION['angemeldet'] !== true) {
    header('Location: login.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="de">
<head>
  <meta charset="UTF-8">
  <title>Börsenspiel</title>
  <link rel="stylesheet" href="styles.css">
</head>
<body onload="initGame();">
<header>
  <h1>Börsenspiel</h1>
  <nav>
    <a href="index.php">Startseite</a>
    <a href="logout.php">Logout</a>
  </nav>
</header>

<div class="jumbotron">
  <h2>Willkommen, <?= htmlspecialchars($_SESSION['vorname']) ?>!</h2>
  <p>Verwalten Sie Ihr virtuelles Depot und handeln Sie mit Aktien.</p>
</div>

<div class="cards-container">
  <div class="card">
    <canvas id="chartCanvas" width="700" height="300"></canvas>
  </div>

  <div class="card">
    <div class="meldung" id="meldungDisplay"></div>
    <ul class="info-list">
      <li>
        <strong>Aktuelles Spielgeld:</strong>
        <span id="spielgeldDisplay"><?= number_format($_SESSION['spielgeld'], 2, ',', '.') ?></span> €
      </li>
      <li>
        <strong>Anzahl Aktien im Depot:</strong>
        <span id="aktienDepotDisplay"><?= $_SESSION['anzahl_aktien'] ?></span>
      </li>
      <li>
        <strong>Aktueller Gewinn/Verlust:</strong>
        <span id="profitDisplay" class="profit-positive">0,00</span> €
      </li>
    </ul>
    <div class="market-phase" id="marketPhaseDisplay"></div>
    <div id="timerDisplay"></div>
  </div>
</div>

<div class="cards-container">
  <div class="card" style="width: 100%;">
    <h2>Kurse & Aktionen</h2>
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
  &copy; <?= date("Y") ?> Mein Börsenspiel - Alle Rechte vorbehalten.
</footer>

<script src="boerse.js"></script>
</body>
</html>