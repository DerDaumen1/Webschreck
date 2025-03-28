<?php
session_start();
if (!isset($_SESSION['angemeldet']) || $_SESSION['angemeldet'] !== true) {
    header('Location: ../pages/login.php');
    exit;
}
require_once __DIR__ . '/../includes/db.php'; // Pfad ist korrekt

// Alle Orders des aktuellen Benutzers laden
$user_id = $_SESSION['user_id'];
$stmt = $pdo->prepare("SELECT * FROM orders WHERE user_id = :uid ORDER BY created_at DESC");
$stmt->execute([':uid' => $user_id]);
$orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Statistiken berechnen
$totalBuy = 0;
$totalSell = 0;
$totalProvision = 0;
$buyCount = 0;
$sellCount = 0;

foreach ($orders as $order) {
    $totalProvision += $order['provision'];
    
    if ($order['order_type'] === 'buy') {
        $totalBuy += ($order['price'] * $order['anzahl']);
        $buyCount++;
    } else if ($order['order_type'] === 'sell') {
        $totalSell += ($order['price'] * $order['anzahl']);
        $sellCount++;
    }
}

$orderCount = count($orders);
?>
<!DOCTYPE html>
<html lang="de">
<head>
  <meta charset="UTF-8">
  <title>Orderbuch</title>
  <link rel="stylesheet" href="../assets/css/styles.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
  <style>
    .orders-table {
      width: 90%;
      max-width: 1200px;
      margin: 2rem auto;
      border-collapse: collapse;
      box-shadow: 0 0 20px rgba(0, 0, 0, 0.15);
      border-radius: 8px;
      overflow: hidden;
    }
    
    .orders-table thead tr {
      background-color: #2c3e50;
      color: #ffffff;
      text-align: left;
    }
    
    .orders-table th,
    .orders-table td {
      padding: 12px 15px;
    }
    
    .orders-table tbody tr {
      border-bottom: 1px solid #dddddd;
    }
    
    .orders-table tbody tr:nth-of-type(even) {
      background-color: #f3f3f3;
    }
    
    .orders-table tbody tr:last-of-type {
      border-bottom: 2px solid #2c3e50;
    }
    
    .buy-order {
      color: #27ae60;
      font-weight: bold;
    }
    
    .sell-order {
      color: #e74c3c;
      font-weight: bold;
    }
    
    .stats-container {
      display: flex;
      flex-wrap: wrap;
      justify-content: space-around;
      max-width: 1200px;
      margin: 2rem auto;
      background-color: #f8f9fa;
      border-radius: 8px;
      padding: 1rem;
      box-shadow: 0 0 15px rgba(0, 0, 0, 0.1);
    }
    
    .stat-card {
      flex: 1 1 200px;
      margin: 0.5rem;
      padding: 1rem;
      background-color: white;
      border-radius: 8px;
      text-align: center;
      box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
    }
    
    .stat-card i {
      font-size: 2rem;
      margin-bottom: 0.5rem;
      color: #2c3e50;
    }
    
    .stat-card .stat-value {
      font-size: 1.5rem;
      font-weight: bold;
    }
    
    .nav-left a {
      margin-right: 15px;
    }
    
    .filter-controls {
      display: flex;
      justify-content: center;
      margin: 1rem 0;
    }
    
    .filter-btn {
      background-color: #f8f9fa;
      border: 1px solid #ddd;
      padding: 8px 16px;
      margin: 0 5px;
      border-radius: 4px;
      cursor: pointer;
      transition: all 0.3s;
    }
    
    .filter-btn:hover, .filter-btn.active {
      background-color: #2c3e50;
      color: white;
    }
    
    .empty-orders {
      text-align: center;
      padding: 3rem;
      background-color: #f8f9fa;
      border-radius: 8px;
      margin: 2rem auto;
      max-width: 600px;
      box-shadow: 0 0 15px rgba(0, 0, 0, 0.1);
    }
    
    .empty-orders i {
      font-size: 4rem;
      color: #95a5a6;
      margin-bottom: 1rem;
    }
  </style>
</head>
<body>
<header>
  <div class="header-container orderbuch-header-container">
    <div class="nav-left">
      <a href="../index.php"><i class="fas fa-home"></i> Startseite</a>
      <a href="../pages/boersenspiel.php"><i class="fas fa-chart-line"></i> Börsenspiel</a>
      <a href="../pages/stock_overview.php"><i class="fas fa-chart-pie"></i> Aktienübersicht</a>
    </div>
    <div class="header-center orderbuch-header-center">
      <h1><i class="fas fa-book"></i> Orderbuch</h1>
    </div>
  </div>
</header>

<?php if (!$orders): ?>
  <div class="empty-orders">
    <i class="fas fa-file-alt"></i>
    <h2>Noch keine Orders!</h2>
    <p>Starten Sie Ihr Börsenspiel und kaufen Sie Ihre ersten Aktien.</p>
    <a href="../pages/boersenspiel.php" class="filter-btn">Zum Börsenspiel</a>
  </div>
<?php else: ?>
  <!-- Statistik-Bereich -->
  <div class="stats-container">
    <div class="stat-card">
      <i class="fas fa-file-invoice"></i>
      <div class="stat-label">Anzahl Orders</div>
      <div class="stat-value"><?= $orderCount ?></div>
    </div>
    <div class="stat-card">
      <i class="fas fa-shopping-cart"></i>
      <div class="stat-label">Kauforders</div>
      <div class="stat-value"><?= $buyCount ?></div>
    </div>
    <div class="stat-card">
      <i class="fas fa-cash-register"></i>
      <div class="stat-label">Verkaufsorders</div>
      <div class="stat-value"><?= $sellCount ?></div>
    </div>
    <div class="stat-card">
      <i class="fas fa-money-bill-wave"></i>
      <div class="stat-label">Gesamtprovisionen</div>
      <div class="stat-value"><?= number_format($totalProvision, 2, ',', '.') ?> €</div>
    </div>
  </div>
  
  <!-- Filter-Buttons -->
  <div class="filter-controls">
    <button class="filter-btn active" data-filter="all">Alle Orders</button>
    <button class="filter-btn" data-filter="buy">Nur Käufe</button>
    <button class="filter-btn" data-filter="sell">Nur Verkäufe</button>
  </div>

  <table class="orders-table">
    <thead>
      <tr>
        <th><i class="far fa-calendar-alt"></i> Datum/Zeit</th>
        <th><i class="fas fa-tag"></i> Aktienname</th>
        <th><i class="fas fa-exchange-alt"></i> Typ</th>
        <th><i class="fas fa-sort-amount-up"></i> Anzahl</th>
        <th><i class="fas fa-euro-sign"></i> Preis</th>
        <th><i class="fas fa-hand-holding-usd"></i> Provision</th>
        <th><i class="fas fa-money-bill-wave"></i> Gesamtwert</th>
      </tr>
    </thead>
    <tbody>
    <?php foreach ($orders as $o): 
      $orderTypeClass = $o['order_type'] === 'buy' ? 'buy-order' : 'sell-order';
      $orderTypeIcon = $o['order_type'] === 'buy' ? '<i class="fas fa-arrow-circle-down"></i>' : '<i class="fas fa-arrow-circle-up"></i>';
      $orderTypeText = $o['order_type'] === 'buy' ? 'Kauf' : 'Verkauf';
      $totalValue = $o['price'] * $o['anzahl'];
    ?>
      <tr class="order-row" data-type="<?= $o['order_type'] ?>">
        <td><?= date('d.m.Y H:i', strtotime($o['created_at'])) ?></td>
        <td><?= htmlspecialchars($o['stock_name']) ?></td>
        <td class="<?= $orderTypeClass ?>"><?= $orderTypeIcon ?> <?= $orderTypeText ?></td>
        <td><?= htmlspecialchars($o['anzahl']) ?></td>
        <td><?= number_format($o['price'], 2, ',', '.') ?> €</td>
        <td><?= number_format($o['provision'], 2, ',', '.') ?> €</td>
        <td><?= number_format($totalValue, 2, ',', '.') ?> €</td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
  
  <script>
    // Filter-Funktionalität
    document.addEventListener('DOMContentLoaded', function() {
      const filterButtons = document.querySelectorAll('.filter-btn');
      const orderRows = document.querySelectorAll('.order-row');
      
      filterButtons.forEach(button => {
        button.addEventListener('click', function() {
          const filter = this.getAttribute('data-filter');
          
          // Aktiven Button markieren
          filterButtons.forEach(btn => btn.classList.remove('active'));
          this.classList.add('active');
          
          // Orders filtern
          orderRows.forEach(row => {
            if (filter === 'all' || row.getAttribute('data-type') === filter) {
              row.style.display = '';
            } else {
              row.style.display = 'none';
            }
          });
        });
      });
    });
  </script>
<?php endif; ?>

<footer>
    <div class="footer-container">
     &copy; <?= date("Y") ?> Privatbank Mustermann | <a href="impressum.php">Impressum</a>
    </div>
</footer>
</body>
</html>