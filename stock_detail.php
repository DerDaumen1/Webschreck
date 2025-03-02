<?php
session_start();
if (!isset($_SESSION['angemeldet']) || $_SESSION['angemeldet'] !== true) {
    header('Location: login.php');
    exit;
}

$stock_id = $_GET['stock_id'] ?? null;
if (!$stock_id) {
  echo "Fehlende Aktien-ID!";
  exit;
}

// Holen wir uns die Info:
$stocks = $_SESSION['stocks'] ?? [];
$stock = null;
foreach($stocks as $s) {
  if ($s['id'] == $stock_id) {
    $stock = $s;
    break;
  }
}

// Historie (10 Tage) - in $_SESSION['stock_history'][stock_id] => Array
$history = $_SESSION['stock_history'][$stock_id] ?? [];

?>
<!DOCTYPE html>
<html lang="de">
<head>
  <meta charset="UTF-8">
  <title>Aktiendetail</title>
  <link rel="stylesheet" href="styles.css">
</head>
<body>
<header>
  <nav>
    <a href="index.php">Startseite</a> |
    <a href="stocks_overview.php">Aktienübersicht</a>
  </nav>
</header>

<?php if ($stock): ?>
  <h1>Details: <?= htmlspecialchars($stock['name']) ?></h1>
  <p>Aktueller Briefkurs: <?= number_format($stock['briefkurs'], 2, ',', '.') ?> €<br>
     Aktueller Geldkurs: <?= number_format($stock['geldkurs'], 2, ',', '.') ?> €</p>

  <h2>Letzte 10 Kurse (Briefkurse)</h2>
  <ul>
    <?php foreach($history as $k): ?>
      <li><?= number_format($k, 2, ',', '.') ?> €</li>
    <?php endforeach; ?>
  </ul>
<?php else: ?>
  <p>Aktie nicht gefunden!</p>
<?php endif; ?>
</body>
</html>
