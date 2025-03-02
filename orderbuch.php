<?php
session_start();
if (!isset($_SESSION['angemeldet']) || $_SESSION['angemeldet'] !== true) {
    header('Location: login.php');
    exit;
}

// DB-Verbindung
try {
    $pdo = new PDO("mysql:host=localhost;dbname=webdatabase;charset=utf8", "root", "");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("DB-Verbindung fehlgeschlagen: " . $e->getMessage());
}

// Alle Orders des aktuellen Benutzers laden
$user_id = $_SESSION['user_id'];
$stmt = $pdo->prepare("SELECT * FROM orders WHERE user_id = :uid ORDER BY created_at DESC");
$stmt->execute([':uid' => $user_id]);
$orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="de">
<head>
  <meta charset="UTF-8">
  <title>Orderbuch</title>
  <link rel="stylesheet" href="styles.css">
</head>
<body>
<header>
  <h1>Orderbuch</h1>
  <nav>
    <a href="index.php">Startseite</a> |
    <a href="boersenspiel.php">Börsenspiel</a>
  </nav>
</header>

<?php if (!$orders): ?>
  <p style="text-align:center;">Noch keine Orders!</p>
<?php else: ?>
  <table border="1" cellpadding="8" style="margin: 1rem auto; border-collapse: collapse;">
    <thead>
      <tr>
        <th>Datum/Zeit</th>
        <th>Aktienname</th>
        <th>Typ</th>
        <th>Anzahl</th>
        <th>Preis</th>
        <th>Provision</th>
      </tr>
    </thead>
    <tbody>
    <?php foreach ($orders as $o): ?>
      <tr>
        <td><?= htmlspecialchars($o['created_at']) ?></td>
        <td><?= htmlspecialchars($o['stock_name']) ?></td>
        <td><?= htmlspecialchars($o['order_type']) ?></td>
        <td><?= htmlspecialchars($o['anzahl']) ?></td>
        <td><?= number_format($o['price'], 2, ',', '.') ?> €</td>
        <td><?= number_format($o['provision'], 2, ',', '.') ?> €</td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
<?php endif; ?>
</body>
</html>
