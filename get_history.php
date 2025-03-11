<?php
session_start();

// Prüfen, ob eingeloggt
if (!isset($_SESSION['angemeldet']) || $_SESSION['angemeldet'] !== true) {
    header('Content-Type: application/json');
    echo json_encode(["success" => false, "message" => "Nicht eingeloggt!"]);
    exit;
}

// JSON-Header setzen
header('Content-Type: application/json');

try {
    // DB-Verbindung aufbauen
    $pdo = new PDO("mysql:host=localhost;dbname=webdatabase;charset=utf8", "root", "");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    echo json_encode(["success" => false, "message" => "DB-Fehler: " . $e->getMessage()]);
    exit;
}

// Stock-ID aus GET lesen, Standard 1
$stockId = isset($_GET['stock_id']) ? (int)$_GET['stock_id'] : 1;
if ($stockId < 1) {
    echo json_encode(["success" => false, "message" => "Ungültige stock_id"]);
    exit;
}

// Query: Letzte 10 Kurse abrufen, tick_time als Datum formatieren
$stmt = $pdo->prepare("
    SELECT kurs, DATE_FORMAT(tick_time, '%d.%m.%Y') AS tick_time
    FROM stock_history
    WHERE stock_id = ?
    ORDER BY tick_time DESC
    LIMIT 10
");
$stmt->execute([$stockId]);
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

// JSON-Ausgabe
echo json_encode(["success" => true, "history" => $rows]);
