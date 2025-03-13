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
  <style>
    /* ---- Carousel-Container ---- */
    .carousel-container {
      position: relative;
      width: 80%;
      margin: 20px auto;
      overflow: hidden;
      border: 1px solid #ddd;
      border-radius: 8px;
      padding: 10px;
      background-color: #fff;
    }
    /* ---- Carousel-Wrapper ---- */
    .carousel-wrapper {
      display: flex;
      transition: transform 0.4s ease;
    }
    /* Buttons zum Blättern */
    .carousel-btn {
      position: absolute;
      top: 50%;
      transform: translateY(-50%);
      background-color: #666;
      color: #fff;
      border: none;
      border-radius: 50%;
      width: 40px;
      height: 40px;
      cursor: pointer;
      opacity: 0.8;
      font-size: 1.2em;
      z-index: 9999;
    }
    .carousel-btn:hover {
      opacity: 1;
    }
    #prevBtn {
      left: 1px;
    }
    #nextBtn {
      right: 1px;
    }

    /* ---- Einzelne Karten ---- */
    .stock-card {
      flex: 0 0 19%; /* 5 gleichzeitig => 100/5=20%, minimal weniger (19%) für Zwischenräume */
      box-sizing: border-box;
      margin: 0 0.5%;
      border: 1px solid #ccc;
      border-radius: 6px;
      padding: 10px;
      background-color: #fafafa;
      text-align: center;
      min-height: 220px;
      box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    }
    .stock-card h2 {
      margin-top: 0;
      font-size: 1rem;
    }

    /* History-Container in jeder Karte */
    .history-container {
      margin-top: 10px;
      overflow-x: auto;
    }
    .history-table {
      width: 100%;
      border-collapse: collapse;
      font-size: 0.9em;
    }
    .history-table th, .history-table td {
      border: 1px solid #ccc;
      padding: 6px 8px;
      text-align: center;
    }
    .history-table th {
      background-color: #f8f8f8;
    }
  </style>
</head>
<body>
<header>
  <h1>Aktienübersicht (mit Carousel)</h1>
  <nav>
    <a href="index.php">Zur Startseite</a> |
    <a href="boersenspiel.php">Zum Börsenspiel</a>
  </nav>
</header>

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
        // Optional: Du kannst hier serverseitig den Bestand abfragen
        // oder komplett auf AJAX gehen (-> s.u. in stock_overview.js).
        // Wenn du's rein clientseitig machen willst, weglassen.
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

        <h4>Letzte 10 Tage</h4>
        <div id="historyContainer-<?= $st['id'] ?>" class="history-container">
          <em>Lade Kursverlauf...</em>
        </div>
      </div>
    <?php endforeach; ?>
  </div>
</div>

<!-- Hier übergeben wir unser stocks-Array als globales JS-Objekt, 
     damit die stock_overview.js darauf zugreifen kann. -->
<script>
  window.phpStocks = <?= json_encode($stocks) ?>;
</script>

<!-- Externe JS-Datei für Carousel, AJAX etc. -->
<script src="stock_overview.js"></script>
</body>
</html>
