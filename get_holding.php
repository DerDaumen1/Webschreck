<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['angemeldet']) || $_SESSION['angemeldet'] !== true) {
    echo json_encode(["success" => false, "message" => "Nicht eingeloggt"]);
    exit;
}

$user_id = $_SESSION['user_id'] ?? 0;
$stock_id = (int)($_GET['stock_id'] ?? 0);

try {
    $pdo = new PDO("mysql:host=localhost;dbname=webdatabase;charset=utf8", "root", "");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    echo json_encode(["success" => false, "message" => $e->getMessage()]);
    exit;
}

// Falls du in orders per stock_id speicherst -> anpassen. 
// Falls du 'stock_name' speicherst, müsstest du stock_id => stock_name mappen
// Minimales Beispiel:

// Hier z.B. ID -> Name:
$stockNameMap = [
  1 => "Mustermann AG",
  2 => "Beispiel AG",
  3 => "Test Inc.",
  4 => "MegaCorp",
  5 => "Future Ltd.",
  6 => "Sample GmbH",
  7 => "Hallo AG",
  8 => "World Ind.",
  9 => "Börsenspiel SE",
  10 => "Fantasy PLC"
];
$stock_name = $stockNameMap[$stock_id] ?? "???";

$stmt = $pdo->prepare("
  SELECT 
    COALESCE(SUM(CASE WHEN order_type='buy' THEN anzahl ELSE 0 END), 0)
    - COALESCE(SUM(CASE WHEN order_type='sell' THEN anzahl ELSE 0 END), 0)
    AS bestand
  FROM orders
  WHERE user_id = :uid
    AND stock_name = :sname
");
$stmt->execute([
  'uid' => $user_id,
  'sname' => $stock_name
]);
$bestand = (int)$stmt->fetchColumn();

echo json_encode(["success" => true, "bestand" => $bestand]);
