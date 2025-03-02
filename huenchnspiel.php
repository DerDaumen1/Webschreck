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
  <style>
    #gameContainer {
      max-width: 600px;
      margin: 2rem auto;
      text-align: center;
    }
    #road {
      width: 100%;
      height: 100px;
      background: #4CAF50;
      position: relative;
      margin: 2rem 0;
      border-radius: 5px;
    }
    .step {
      position: absolute;
      bottom: 10px;
      width: 2px;
      height: 20px;
      background: white;
      opacity: 0.5;
    }
    #chicken {
      transition: left 0.5s ease-in-out;
    }
    #nextBtn {
      background: #ff9800;
    }
    #nextBtn:disabled {
      background: #ccc;
    }
  </style>
</head>
<body>
<header>
  <h1>Hühner-Roulette</h1>
  <nav>
    <a href="index.php">Startseite</a>
    <a href="logout.php">Logout</a>
  </nav>
</header>

<div id="gameContainer">
  <div class="card">
    <h2>Spielgeld: <span id="currentMoney"><?= number_format($_SESSION['spielgeld'], 2, ',', '.') ?> €</span></h2>
    
    <div class="form-group">
      <label for="betAmount">Einsatz (€):</label>
      <input type="number" id="betAmount" min="1" max="1000" value="10">
      <button class="btn" id="startBtn" onclick="startGame()">Starten</button>
      <button class="btn" id="nextBtn" onclick="nextStep()" disabled>Weiter!</button>
    </div>

    <div id="road">
      <!-- Huhn als SVG -->
      <svg id="chicken" width="50" height="50" style="position:absolute; left:10px; bottom:10px;">
        <circle cx="25" cy="25" r="20" fill="yellow" />
        <circle cx="15" cy="20" r="3" fill="black" />
        <circle cx="35" cy="20" r="3" fill="black" />
        <path d="M15 35 Q25 40 35 35" stroke="black" fill="none" />
      </svg>
      
      <!-- Schritt-Markierungen -->
      <div id="steps"></div>
    </div>

    <div id="gameInfo">
      <p>Aktueller Gewinn: <span id="currentWin">0 €</span></p>
      <button class="btn" id="cashOutBtn" onclick="cashOut()" disabled>Ausbezahlen</button>
    </div>
  </div>
</div>

<script src="huenchnspiel.js"></script>
</body>
</html>