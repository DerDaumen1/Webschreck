<?php
session_start();
header('Content-Type: application/json');
if (!isset($_SESSION['angemeldet']) || $_SESSION['angemeldet'] !== true) {
    echo json_encode(["success" => false, "message" => "Nicht eingeloggt!"]);
    exit;
}

try {
    $pdo = new PDO("mysql:host=localhost;dbname=webdatabase;charset=utf8", "root", "");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    // Alle bisherigen Historieneinträge löschen
    $pdo->exec("DELETE FROM stock_history");
    // Session-Flag zurücksetzen, damit update_stocks.php eine frische Historie startet
    unset($_SESSION['session_started']);
    echo json_encode(["success" => true, "message" => "Historie zurückgesetzt"]);
} catch (PDOException $e) {
    echo json_encode(["success" => false, "message" => "DB-Fehler: " . $e->getMessage()]);
}
?>
