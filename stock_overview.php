<?php
session_start();
// Prüfen, ob eingeloggt
if (!isset($_SESSION['angemeldet']) || $_SESSION['angemeldet'] !== true) {
    header('Location: login.php');
    exit;
}

// 1) user_id aus Session laden (wichtig, um Bestände pro Benutzer anzuzeigen)
$user_id = $_SESSION['user_id'] ?? 0;

// 2) DB-Verbindung herstellen
try {
    $pdo = new PDO("mysql:host=localhost;dbname=webdatabase;charset=utf8", "root", "");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("DB-Verbindung fehlgeschlagen: " . $e->getMessage());
}

// Minimal-Demo: Falls noch keine Stocks in der Session sind
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
    /* Carousel-spezifische Styles bleiben hier unverändert */
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
    .carousel-wrapper {
      display: flex;
      transition: transform 0.4s ease;
    }
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
    .stock-card {
      flex: 0 0 19%;
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
  <div class="header-container">
    <div class="nav-left">
      <a href="index.php">Zur Startseite</a>
      <a href="boersenspiel.php">Zum Börsenspiel</a>
    </div>
    <div class="header-center">
      <h1>Aktienübersicht (mit Carousel)</h1>
    </div>
  </div>
</header>

<!-- Carousel-Container -->
<div class="carousel-container">
  <button id="prevBtn" class="carousel-btn">&lt;</button>
  <button id="nextBtn" class="carousel-btn">&gt;</button>
  <div class="carousel-wrapper" id="cardsWrapper">
    <?php foreach ($stocks as $st): ?>
      <div class="stock-card" id="card-<?= $st['id'] ?>">
        <h2><?= htmlspecialchars($st['name']) ?></h2>
        <?php
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

<script>
const stocks = <?= json_encode($stocks) ?>;
function loadAllHistories() {
  stocks.forEach(stock => {
    const stockId = stock.id;
    fetch("get_history.php?stock_id=" + stockId)
      .then(res => res.json())
      .then(data => {
        if (!data.success) {
          console.error("Fehler beim Laden der History für Aktie " + stockId + ":", data.message);
          return;
        }
        const container = document.getElementById("historyContainer-" + stockId);
        if (!container) return;
        let html = "<table class='history-table'><thead><tr><th>Datum</th><th>Kurs</th></tr></thead><tbody>";
        data.history.forEach(row => {
          html += `<tr><td>${row.tick_time}</td><td>${parseFloat(row.kurs).toFixed(2)} €</td></tr>`;
        });
        html += "</tbody></table>";
        container.innerHTML = html;
      })
      .catch(err => console.error("Fehler beim AJAX für Aktie " + stockId + ":", err));
  });
}

const cardsWrapper = document.getElementById("cardsWrapper");
const totalCards = stocks.length;
const cardsPerPage = 5;
const totalPages = Math.ceil(totalCards / cardsPerPage);
let currentIndex = 0;
const prevBtn = document.getElementById("prevBtn");
const nextBtn = document.getElementById("nextBtn");
function showPage(index) {
  const offset = -index * 100; 
  cardsWrapper.style.transform = `translateX(${offset}%)`;
  updateArrows();
}
function updateArrows() {
  prevBtn.style.backgroundColor = currentIndex <= 0 ? "#666" : "orange";
  nextBtn.style.backgroundColor = currentIndex >= totalPages - 1 ? "#666" : "orange";
}
document.addEventListener("DOMContentLoaded", () => {
  loadAllHistories();
  showPage(0);
  updateArrows();
});
prevBtn.addEventListener("click", () => {
  if (currentIndex > 0) {
    currentIndex--;
    showPage(currentIndex);
  }
});
nextBtn.addEventListener("click", () => {
  if (currentIndex < totalPages - 1) {
    currentIndex++;
    showPage(currentIndex);
  }
});
</script>
</body>
</html>

<footer>
    <div class="footer-container">
     &copy; <?= date("Y") ?> Privatbank Mustermann | <a href="impressum.php">Impressum</a>
    </div>
  </footer>
</body>
</html>