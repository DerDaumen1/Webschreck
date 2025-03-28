<?php
session_start();
?>
<!DOCTYPE html>
<html lang="de">
<head>
  <meta charset="UTF-8">
  <title>Privatbank Mustermann</title>
  <link rel="stylesheet" href="assets/css/styles.css"> <!-- Pfad ist korrekt -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
  <header>
    <div class="header-container">
      <div class="nav-left">
        <a href="index.php">Startseite</a>
        <?php if(isset($_SESSION['angemeldet']) && $_SESSION['angemeldet']): ?>
          <a href="pages/logout.php">Logout</a>
        <?php else: ?>
          <a href="pages/registrierung.php">Registrieren</a>
          <a href="pages/login.php">Anmelden</a>
        <?php endif; ?>
      </div>
      <div class="header-center">
        <h1>Willkommen bei der Privatbank Mustermann</h1>
        <p>Erleben Sie die spannende Welt des virtuellen Handels und riskieren Sie Ihr Glück in unseren innovativen Spielen!</p>
      </div>
      <div class="nav-right">
        <?php if(isset($_SESSION['angemeldet']) && $_SESSION['angemeldet']): ?>
          <div id="portfolio-summary" class="portfolio-summary">
            <h3>Ihr Portfolio <i class="fas fa-wallet"></i></h3>
            <div class="portfolio-stats">
              <div class="stat-item">
                <i class="fas fa-coins"></i>
                <span class="stat-label">Spielgeld:</span> 
                <span class="stat-value"><?php echo number_format($_SESSION['spielgeld'], 2, ',', '.'); ?> €</span>
              </div>
              <div class="stat-item">
                <i class="fas fa-chart-bar"></i>
                <span class="stat-label">Aktienwert:</span>
                <span class="stat-value" id="aktienDepotDisplay">0,00 €</span>
              </div>
              <div class="stat-item">
                <i class="fas fa-piggy-bank"></i>
                <span class="stat-label">Portfolio-Wert:</span>
                <span class="stat-value" id="depotGesamtDisplay">0,00 €</span>
              </div>
              <div class="stat-item">
                <i class="fas fa-balance-scale"></i>
                <span class="stat-label">Gewinn/Verlust:</span>
                <span class="stat-value" id="gewinnVerlustDisplay">0,00 €</span>
              </div>
              <div class="stat-item">
                <i class="fas fa-boxes"></i>
                <span class="stat-label">Aktienanzahl:</span>
                <span class="stat-value" id="totalSharesDisplay">0</span>
              </div>
              <div class="stat-item">
                <i class="fas fa-user"></i>
                <span class="stat-label">Benutzer:</span>
                <span class="stat-value"><?php echo htmlspecialchars($_SESSION['vorname'] . ' ' . $_SESSION['nachname']); ?></span>
              </div>
            </div>
          </div>
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
          <button onclick="location.href='pages/boersenspiel.php'">Zum Börsenspiel</button>
        <?php else: ?>
          <button disabled>Bitte zuerst anmelden</button>
        <?php endif; ?>
      </div>

      <!-- Hühner-Roulette Card -->
      <div class="card">
        <h3>Hühner-Roulette</h3>
        <p>Riskieren Sie Ihr Spielgeld in unserem humorvollen 50/50-Spiel! Unser Huhn überquert die Straße – manchmal sicher, manchmal mit einer spektakulären Explosion. Ein einzigartiges Glücksspiel, das Spaß und Spannung miteinander verbindet!</p>
        <?php if(isset($_SESSION['angemeldet']) && $_SESSION['angemeldet']): ?>
          <button onclick="location.href='pages/huenchnspiel.php'">Jetzt spielen</button>
        <?php else: ?>
          <button disabled>Bitte zuerst anmelden</button>
        <?php endif; ?>
      </div>
    </section>
  </main>

  <footer>
    <div class="footer-container">
     &copy; <?= date("Y") ?> Privatbank Mustermann | <a href="pages/impressum.php">Impressum</a>
    </div>
  </footer>

  <!-- Script für Portfolio-Daten -->
  <?php if(isset($_SESSION['angemeldet']) && $_SESSION['angemeldet']): ?>
  <script>
    // Globale Variablen für Portfolio-Berechnungen
    window.currentStockValues = {};
    window.currentStockShares = {};
    window.currentSpielgeld = <?= json_encode($_SESSION['spielgeld'] ?? 50000); ?>;
    window.startKapital = 50000;

    // Aktualisiere Portfolio beim Laden der Seite
    document.addEventListener('DOMContentLoaded', function() {
      // Alle verfügbaren Aktien durchlaufen
      for (let stockId = 1; stockId <= 10; stockId++) {
        // Aktuellen Kurs laden
        fetch("includes/api.php?action=get_history&stock_id=" + stockId)
          .then(res => res.json())
          .then(historyData => {
            if (historyData.success && historyData.history.length > 0) {
              // Neuesten Kurs verwenden
              let latestPrice = parseFloat(historyData.history[0].kurs);
              
              // Bestand abfragen
              fetch("includes/api.php?action=get_holding&stock_id=" + stockId)
                .then(res => res.json())
                .then(holdingData => {
                  if (holdingData.success) {
                    let currentValue = holdingData.bestand * latestPrice;
                    window.currentStockValues[stockId] = currentValue;
                    window.currentStockShares[stockId] = holdingData.bestand;
                    
                    // Portfolio-Übersicht aktualisieren
                    updateGlobalPortfolio();
                  }
                })
                .catch(err => console.error("Fehler beim Laden des Bestands:", err));
            }
          })
          .catch(err => console.error("Fehler beim Laden der Kursdaten:", err));
      }
    });

    function updateGlobalPortfolio() {
      let depotValue = 0;
      let totalShares = 0;
      
      for (let id in window.currentStockValues) {
        depotValue += window.currentStockValues[id];
      }
      
      for (let id in window.currentStockShares) {
        totalShares += window.currentStockShares[id];
      }
      
      let spielgeld = parseFloat(window.currentSpielgeld);
      let depotGesamt = spielgeld + depotValue;
      let gewinnVerlust = depotGesamt - window.startKapital;

      // Anzeigen aktualisieren
      document.getElementById("aktienDepotDisplay").textContent = depotValue.toFixed(2).replace('.', ',') + " €";
      document.getElementById("depotGesamtDisplay").textContent = depotGesamt.toFixed(2).replace('.', ',') + " €";
      document.getElementById("gewinnVerlustDisplay").textContent = gewinnVerlust.toFixed(2).replace('.', ',') + " €";
      document.getElementById("totalSharesDisplay").textContent = totalShares.toString();

      // Gewinn/Verlust farblich markieren
      const gvElem = document.getElementById("gewinnVerlustDisplay");
      if (gewinnVerlust >= 0) {
        gvElem.style.color = "#90ee90"; // Hellgrün für Gewinn
      } else {
        gvElem.style.color = "red"; // Rot für Verlust
      }
    }
  </script>
  <?php endif; ?>
</body>
</html>
