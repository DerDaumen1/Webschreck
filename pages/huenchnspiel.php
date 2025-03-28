<?php
session_start();
if (!isset($_SESSION['angemeldet']) || $_SESSION['angemeldet'] !== true) {
    header('Location: ../pages/registrierung.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="de">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Hühner-Roulette</title>
  <link rel="stylesheet" href="../assets/css/styles.css">
</head>
<body class="game-page">
  <header>
    <div class="header-container">
      <div class="nav-left">
        <a href="../index.php">Startseite</a>
        <a href="../pages/stock_overview.php">Aktienübersicht</a>
        <a href="../pages/logout.php">Logout</a>
      </div>
      <div class="header-center">
        <h1>Hühner-Roulette</h1>
        <p>Lassen Sie das Huhn die Straße überqueren und gewinnen Sie!</p>
      </div>
      <div class="nav-right">
        <div class="depot-content">
          <div>
            <?php 
              echo isset($_SESSION['vorname']) ? htmlspecialchars($_SESSION['vorname']) : "";
              echo " ";
              echo isset($_SESSION['nachname']) ? htmlspecialchars($_SESSION['nachname']) : "";
            ?>
          </div>
        </div>
      </div>
    </div>
  </header>

  <div id="gameContainer" class="container">
    <div class="card">
      <h2>Spielgeld: <span id="currentMoney"><?= number_format($_SESSION['spielgeld'], 2, ',', '.') ?> €</span></h2>
      
      <div class="form-group">
        <label for="betAmount">Einsatz (€):</label>
        <input type="number" id="betAmount" min="0.01" max="1000" step="0.01" value="10">
        <button class="btn" id="startBtn" onclick="startGame()">Spiel starten</button>
        <button class="btn" id="nextBtn" onclick="nextStep()" disabled>Weiter!</button>
      </div>

      <div id="road">
        <img id="chicken" src="../assets/images/chicken.png" alt="Huhn">
        <div id="steps"></div>
      </div>

      <div id="gameInfo">
        <p>Aktueller Gewinn: <span id="currentWin">0,00 €</span></p>
        <button class="btn" id="cashOutBtn" onclick="cashOut()" disabled>Ausbezahlen</button>
      </div>
      
      <div id="gameMessage"></div>
      
      <div class="game-instructions">
        <h3>Spielregeln:</h3>
        <ol>
          <li>Setzen Sie einen Betrag zwischen 0,01€ und 1.000€</li>
          <li>Klicken Sie auf "Weiter!" um das Huhn einen Schritt weiter zu bringen</li>
          <li>Bei jedem Schritt verdoppelt sich Ihr möglicher Gewinn</li>
          <li>Sie können jederzeit "Ausbezahlen" klicken, um Ihren Gewinn zu sichern</li>
          <li>Aber Achtung: Bei jedem Schritt besteht eine 50% Chance, dass ein Auto kommt!</li>
        </ol>
      </div>
    </div>
  </div>

  <footer>
    <div class="footer-container">
     &copy; <?= date("Y") ?> Privatbank Mustermann | <a href="impressum.php">Impressum</a>
    </div>
  </footer>

  <script src="../assets/js/huenchnspiel.js"></script>
</body>
</html>