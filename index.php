<?php
session_start();
?>
<!DOCTYPE html>
<html lang="de">
<head>
  <meta charset="UTF-8">
  <title>Privatbank Mustermann</title>
  <link rel="stylesheet" href="styles.css">
</head>

<body>
  <header>
    <nav>
      <a href="index.php">Startseite</a>
      <?php if(isset($_SESSION['angemeldet']) && $_SESSION['angemeldet']): ?>
        <a href="logout.php">Logout</a>
      <?php else: ?>
        <a href="registrierung.php">Registrieren</a>
        <a href="login.php">Anmelden</a>
      <?php endif; ?>
    </nav>
    <?php if(isset($_SESSION['angemeldet']) && $_SESSION['angemeldet']): ?>
      <div class="user-info">
        <span><?= htmlspecialchars($_SESSION['vorname']) ?> <?= htmlspecialchars($_SESSION['nachname']) ?></span>
        <span>Spielgeld: <?= number_format($_SESSION['spielgeld'], 2, ',', '.') ?> €</span>
        <span>Aktien: <?= $_SESSION['anzahl_aktien'] ?></span>
      </div>
    <?php endif; ?>
  </header>

  <div class="jumbotron">
    <h1>Privatbank Mustermann</h1>
    <p>Willkommen beim Börsenspiel - Testen Sie Ihr Investmentgeschick!</p>
    <?php if(!isset($_SESSION['angemeldet']) || !$_SESSION['angemeldet']): ?>
      <div class="button-container">
        <button onclick="location.href='registrierung.php'">Registrieren</button>
        <button onclick="location.href='login.php'">Anmelden</button>
      </div>
    <?php endif; ?>
  </div>

  <div class="cards-container">
    <div class="card">
      <h3>Willkommen beim Börsenspiel</h3>
      <?php if(!isset($_SESSION['angemeldet']) || !$_SESSION['angemeldet']): ?>
        <p>Melden Sie sich an oder registrieren Sie sich, um mit dem Spiel zu beginnen!</p>
      <?php else: ?>
        <p>Verwalten Sie Ihr virtuelles Depot und handeln Sie mit Aktien!</p>
        <button onclick="location.href='boersenspiel.php'">Zum Börsenspiel</button>
      <?php endif; ?>
    </div>

    <div class="card">
      <h3>Börsenspiel</h3>
      <p>Handeln Sie virtuell mit Aktien und testen Sie Ihre Strategien.</p>
      <?php if(isset($_SESSION['angemeldet']) && $_SESSION['angemeldet']): ?>
        <button onclick="location.href='boersenspiel.php'">Spiel starten</button>
      <?php else: ?>
        <button disabled>Bitte zuerst anmelden</button>
      <?php endif; ?>
    </div>

    <div class="card">
      <h3>Börsencrashspiel <span style="color: orange;">Coming Soon</span></h3>
      <p>Ein neues Spielmodul ist in Vorbereitung.</p>
      <button disabled>Verfügbar in Kürze</button>
    </div>
  </div>

  <footer>
    &copy; Privatbank Mustermann <?= date("Y") ?>
  </footer>
</body>
</html>