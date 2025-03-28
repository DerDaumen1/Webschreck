<?php
session_start();
// Prüfen, ob eingeloggt
if (!isset($_SESSION['angemeldet']) || $_SESSION['angemeldet'] !== true) {
    header('Location: login.php');
    exit;
}

// user_id aus Session
$user_id = $_SESSION['user_id'] ?? 0;

require_once __DIR__ . '/../includes/db.php';

// Minimal-Demo: Falls noch keine Stocks in Session
if (!isset($_SESSION['all_stocks'])) {
    $_SESSION['all_stocks'] = [
        ["id"=>1,  "name"=>"Mustermann AG",  "briefkurs"=>100, "geldkurs"=>99],
        ["id"=>2,  "name"=>"Beispiel AG",    "briefkurs"=>100, "geldkurs"=>99],
        ["id"=>3,  "name"=>"Test Inc.",      "briefkurs"=>100, "geldkurs"=>99],
        ["id"=>4,  "name"=>"MegaCorp",       "briefkurs"=>100, "geldkurs"=>99],
        ["id"=>5,  "name"=>"Future Ltd.",    "briefkurs"=>100, "geldkurs"=>99],
        ["id"=>6,  "name"=>"Sample GmbH",    "briefkurs"=>100, "geldkurs"=>99],
        ["id"=>7,  "name"=>"Hallo AG",       "briefkurs"=>100, "geldkurs"=>99],
        ["id"=>8,  "name"=>"World Ind.",     "briefkurs"=>100, "geldkurs"=>99],
        ["id"=>9,  "name"=>"Börsenspiel SE", "briefkurs"=>100, "geldkurs"=>99],
        ["id"=>10, "name"=>"Fantasy PLC",    "briefkurs"=>100, "geldkurs"=>99]
    ];
}
$stocks = $_SESSION['all_stocks'];
?>
<!DOCTYPE html>
<html lang="de">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Aktienübersicht</title>
  <link rel="stylesheet" href="../assets/css/styles.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>

<header>
  <div class="header-container">
    <div class="nav-left">
      <a href="../index.php"><i class="fas fa-home"></i> Startseite</a>
      <a href="boersenspiel.php"><i class="fas fa-chart-line"></i> Börsenspiel</a>
      <a href="orderbuch.php"><i class="fas fa-book"></i> Orderbuch</a>
    </div>
    <div class="header-center">
      <h1><i class="fas fa-chart-pie"></i> Aktienportfolio-Übersicht</h1>
    </div>
    <div class="nav-right">
      <!-- Portfolio-Zusammenfassung mit verbessertem Layout -->
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
        </div>
      </div>
    </div>
  </div>
</header>

<!-- Verbesserte Überschrift und Einführung -->
<div class="stock-overview-intro">
  <h2>Ihre Aktienübersicht</h2>
  <p>Hier sehen Sie alle verfügbaren Aktien und Ihre aktuellen Investitionen. Nutzen Sie die Pfeile, um durch die Aktien zu navigieren.</p>
</div>

<!-- Verbesserter Carousel-Container mit moderneren Buttons -->
<div class="carousel-container">
  <button id="prevBtn" class="carousel-btn"><i class="fas fa-chevron-left"></i></button>
  <button id="nextBtn" class="carousel-btn"><i class="fas fa-chevron-right"></i></button>

  <!-- Wrapper für die Aktienkarten -->
  <div class="carousel-wrapper" id="cardsWrapper">
    <?php foreach ($stocks as $st): ?>
      <div class="stock-card" id="card-<?= $st['id'] ?>">
        <div class="stock-card-header">
          <h2><?= htmlspecialchars($st['name']) ?></h2>
          <?php
          // Serverseitige Bestandsabfrage wie im Original
          $stmt = $pdo->prepare("
            SELECT 
              COALESCE(SUM(CASE WHEN order_type = 'buy' THEN anzahl ELSE 0 END), 0)
              - COALESCE(SUM(CASE WHEN order_type = 'sell' THEN anzahl ELSE 0 END), 0)
              AS bestand
            FROM orders
            WHERE user_id = :uid
              AND stock_name = :sname
          ");
          $stmt->execute([
            'uid' => $user_id,
            'sname' => $st['name']
          ]);
          $bestand = (int)$stmt->fetchColumn();
          ?>
          <!-- Verbesserte Kennzahlen-Anzeige -->
          <div class="stock-stats">
            <div class="stock-stat">
              <span class="stat-label"><i class="fas fa-cubes"></i> Bestand:</span>
              <span class="stat-value"><?= $bestand ?> Stück</span>
            </div>
            <div class="stock-stat">
              <span class="stat-label"><i class="fas fa-euro-sign"></i> Wert:</span>
              <span class="stat-value" id="currentValue-<?= $st['id'] ?>">0,00</span> €
            </div>
          </div>
        </div>
        
        <!-- Mini-Chart für Kursentwicklung -->
        <div class="stock-chart-container">
          <canvas id="miniChart-<?= $st['id'] ?>" width="250" height="100"></canvas>
        </div>

        <h4><i class="fas fa-history"></i> Kursverlauf (10 Tage)</h4>
        <div id="historyContainer-<?= $st['id'] ?>" class="history-container">
          <em>Lade Kursverlauf...</em>
        </div>
        
        <!-- Handelsbuttons -->
        <div class="stock-actions">
          <button class="action-btn buy-btn" onclick="location.href='boersenspiel.php?stock=<?= $st['id'] ?>'">
            <i class="fas fa-shopping-cart"></i> Handeln
          </button>
        </div>
      </div>
    <?php endforeach; ?>
  </div>
</div>

<!-- Verbesserte Fußzeile mit Legende -->
<div class="stock-legend">
  <div class="legend-item">
    <span class="color-square up"></span> Kursanstieg
  </div>
  <div class="legend-item">
    <span class="color-square down"></span> Kursabfall
  </div>
  <div class="legend-item">
    <span class="color-square neutral"></span> Unverändert
  </div>
</div>

<!-- Stocks-Array als globales JS-Objekt -->
<script>
  window.currentSpielgeld = <?= json_encode($_SESSION['spielgeld'] ?? 50000); ?>;
  window.startKapital = 50000;
  window.phpStocks = <?= json_encode($stocks) ?>;
</script>

<!-- JS-Datei für Carousel, AJAX etc. -->
<script src="../assets/js/stock_overview.js"></script>
<script src="../assets/js/stock_mini_charts.js"></script>
</body>
</html>
