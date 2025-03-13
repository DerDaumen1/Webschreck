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
    <div class="header-container">
      <div class="nav-left">
        <a href="index.php">Startseite</a>
        <?php if(isset($_SESSION['angemeldet']) && $_SESSION['angemeldet']): ?>
          <a href="logout.php">Logout</a>
        <?php else: ?>
          <a href="registrierung.php">Registrieren</a>
          <a href="login.php">Anmelden</a>
        <?php endif; ?>
      </div>
      <div class="header-center">
        <h1>Willkommen bei der Privatbank Mustermann</h1>
        <p>Erleben Sie die spannende Welt des virtuellen Handels und riskieren Sie Ihr Glück in unseren innovativen Spielen!</p>
      </div>
      <div class="nav-right">
        <?php if(isset($_SESSION['angemeldet']) && $_SESSION['angemeldet']): ?>
          <span class="user-name">
            <?php 
              echo isset($_SESSION['vorname']) ? htmlspecialchars($_SESSION['vorname']) : "Unbekannt"; 
              echo " ";
              echo isset($_SESSION['nachname']) ? htmlspecialchars($_SESSION['nachname']) : "";
            ?>
          </span>
          <span class="user-balance">Spielgeld: <?php echo number_format($_SESSION['spielgeld'] ?? 0, 2, ',', '.'); ?> €</span>
          <span class="user-stocks">Aktien: <?php echo $_SESSION['anzahl_aktien'] ?? 0; ?></span>
        <?php endif; ?>
      </div>
    </div>
  </header>

  <main>
    <section class="cards-container">
      <!-- Willkommen Card -->
      <div class="card">
        <h3>Willkommen</h3>
        <?php if(!isset($_SESSION['angemeldet']) || !$_SESSION['angemeldet']): ?>
          <p>Herzlich Willkommen bei der Privatbank Mustermann! Melden Sie sich an oder registrieren Sie sich, um in die faszinierende Welt des virtuellen Börsenhandels und unserer unterhaltsamen Spiele einzutauchen. Testen Sie Ihr Investmentgeschick und erleben Sie Nervenkitzel pur – Ihr virtuelles Glück wartet!</p>
        <?php else: ?>
          <p>Willkommen zurück! Diese Website wurde entwickelt, um Ihr Glück herauszufordern. Nutzen Sie unsere Spiele, um Ihr virtuelles Vermögen zu vergrößern – oder auch mal zu riskieren. Viel Spaß beim Spielen und Handeln!</p>
        <?php endif; ?>
      </div>

      <!-- Börsenspiel Card -->
      <div class="card">
        <h3>Börsenspiel</h3>
        <p>Handeln Sie virtuell mit Aktien und setzen Sie Ihr strategisches Geschick ein. Wählen Sie aus 10 verschiedenen Aktien, beobachten Sie den Markt in unserer übersichtlichen Aktienübersicht und prüfen Sie Ihre letzten Transaktionen im Orderbuch.</p>
        <?php if(isset($_SESSION['angemeldet']) && $_SESSION['angemeldet']): ?>
          <button onclick="location.href='boersenspiel.php'">Zum Börsenspiel</button>
        <?php else: ?>
          <button disabled>Bitte zuerst anmelden</button>
        <?php endif; ?>
      </div>

      <!-- Hühner-Roulette Card -->
      <div class="card">
        <h3>Hühner-Roulette</h3>
        <p>Riskieren Sie Ihr Spielgeld in unserem humorvollen 50/50-Spiel! Unser Huhn überquert die Straße – manchmal sicher, manchmal mit einer spektakulären Explosion. Ein einzigartiges Glücksspiel, das Spaß und Spannung miteinander verbindet!</p>
        <?php if(isset($_SESSION['angemeldet']) && $_SESSION['angemeldet']): ?>
          <button onclick="location.href='huenchnspiel.php'">Jetzt spielen</button>
        <?php else: ?>
          <button disabled>Bitte zuerst anmelden</button>
        <?php endif; ?>
      </div>
    </section>
  </main>

  <footer>
    <div class="footer-container">
     &copy; <?= date("Y") ?> Privatbank Mustermann | <a href="impressum.php">Impressum</a>
    </div>
  </footer>
</body>
</html>
