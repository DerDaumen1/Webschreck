<?php
session_start();
// Prüfen, ob eingeloggt
if (!isset($_SESSION['angemeldet']) || $_SESSION['angemeldet'] !== true) {
    header('Location: login.php');
    exit;
}

// user_id aus Session
$user_id = $_SESSION['user_id'] ?? 0;

// DB-Verbindung
try {
    $pdo = new PDO("mysql:host=localhost;dbname=webdatabase;charset=utf8", "root", "");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("DB-Verbindung fehlgeschlagen: " . $e->getMessage());
}

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
  <title>Aktienübersicht (mit Carousel)</title>
  <link rel="stylesheet" href="styles.css">
</head>
<body>

<header>
  <div class="header-container">
    <div class="nav-left">
      <a href="index.php">Startseite</a>
      <a href="boersenspiel.php">Börsenspiel</a>
    </div>
    <div class="header-center">
      <h1>Aktienübersicht (mit Carousel)</h1>
    </div>
    <div class="nav-right">
      <!-- Depot Informationen direkt im Header -->
      <div id="depotContent" style="color:#fff; background-color:transparent; border:none; padding: 15px; border-radius: 8px; text-align: center;">
        <div class="depot-info">
          <strong>Aktien Gesamt:</strong> <?php echo $_SESSION['anzahl_aktien'] ?? 0; ?>
        </div>
        <div class="depot-info">
          <strong>Spielgeld:</strong> <?php echo number_format($_SESSION['spielgeld'], 2, ',', '.'); ?> €
        </div>
        <div class="depot-info">
          <strong>Aktienwert:</strong> <span id="aktienDepotDisplay">0,00 €</span>
        </div>
        <div class="depot-info">
          <strong>Portfolio-Wert:</strong> <span id="depotGesamtDisplay">0,00 €</span>
        </div>
        <div class="depot-info">
          <strong>Gewinn/Verlust:</strong> <span id="gewinnVerlustDisplay">0,00 €</span>
        </div>
      </div>
    </div>
  </div>
</header>

<!-- Carousel-Container -->
<div class="carousel-container">
  <!-- Blätter-Buttons -->
  <button id="prevBtn" class="carousel-btn">&lt;</button>
  <button id="nextBtn" class="carousel-btn">&gt;</button>

  <!-- Wrapper für die Aktienkarten -->
  <div class="carousel-wrapper" id="cardsWrapper">
    <?php foreach ($stocks as $st): ?>
      <div class="stock-card" id="card-<?= $st['id'] ?>">
        <h2><?= htmlspecialchars($st['name']) ?></h2>

        <?php
        // Serverseitige Bestandsabfrage
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
        <p>Aktueller Bestand: <?= $bestand ?> Stück</p>
        <!-- Neuer Platzhalter für den aktuellen Wert -->
        <p>Aktueller Wert: <span id="currentValue-<?= $st['id'] ?>">0,00</span> €</p>
        <h4>Letzte 10 Tage</h4>
        <div id="historyContainer-<?= $st['id'] ?>" class="history-container">
          <em>Lade Kursverlauf...</em>
        </div>
      </div>
    <?php endforeach; ?>
  </div>
</div>

<!-- Stocks-Array als globales JS-Objekt -->
<script>
  window.currentSpielgeld = <?= json_encode($_SESSION['spielgeld'] ?? 50000); ?>;
  window.startKapital = 50000;
  window.phpStocks = <?= json_encode($stocks) ?>;
</script>

<!-- JS-Datei für Carousel, AJAX etc. -->
<script src="stock_overview.js"></script>
</body>
</html>
