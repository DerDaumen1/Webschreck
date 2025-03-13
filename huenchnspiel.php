<?php
session_start();
if (!isset($_SESSION['angemeldet']) || $_SESSION['angemeldet'] !== true) {
    header('Location: registrierung.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="de">
<head>
  <meta charset="UTF-8">
  <title>Hühner-Roulette</title>
  <link rel="stylesheet" href="styles.css">
</head>
<body class="game-page">
  <header>
    <div class="header-container">
      <div class="nav-left">
        <a href="index.php">Startseite</a>
        <a href="logout.php">Logout</a>
      </div>
      <div class="header-center">
        <h1>Hühner-Roulette</h1>
      </div>
      <div class="nav-right">
        <span class="user-name">
          <?php 
            // Hier evtl. noch Benutzername / Spielgeld ausgeben, falls gewünscht
            // (Hast du in boersenspiel.php ähnlich gemacht)
            echo isset($_SESSION['vorname']) ? htmlspecialchars($_SESSION['vorname']) : "";
            echo " ";
            echo isset($_SESSION['nachname']) ? htmlspecialchars($_SESSION['nachname']) : "";
          ?>
        </span>
      </div>
    </div>
  </header>

  <div id="gameContainer">
    <div class="card">
      <h2>Spielgeld: <span id="currentMoney"><?= number_format($_SESSION['spielgeld'], 2, ',', '.') ?> €</span></h2>
      
      <div class="form-group">
        <label for="betAmount">Einsatz (€):</label>
        <input type="number" id="betAmount" min="0.01" max="1000" step="0.01" value="10">
        <button class="btn" id="startBtn" onclick="startGame()">Starten</button>
        <button class="btn" id="nextBtn" onclick="nextStep()" disabled>Weiter!</button>
      </div>

      <div id="road">
        <!-- Huhn-Bild -->
        <img id="chicken" src="chicken.png" alt="Huhn">
        <!-- Schritt-Markierungen -->
        <div id="steps"></div>
      </div>

      <div id="gameInfo">
        <p>Aktueller Gewinn: <span id="currentWin">0 €</span></p>
        <button class="btn" id="cashOutBtn" onclick="cashOut()" disabled>Ausbezahlen</button>
      </div>
      <!-- Inline-Meldungen -->
      <div id="gameMessage"></div>
    </div>
  </div>

  <script src="huenchnspiel.js"></script>
</body>
</html>
<footer>
    <div class="footer-container">
     &copy; <?= date("Y") ?> Privatbank Mustermann | <a href="impressum.php">Impressum</a>
    </div>
  </footer>
</body>
</html>