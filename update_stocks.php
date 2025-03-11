<?php
session_start();

// Nur fortfahren, wenn eingeloggt
if (!isset($_SESSION['angemeldet']) || $_SESSION['angemeldet'] !== true) {
    header('Content-Type: application/json');
    echo json_encode(["success" => false, "message" => "Nicht eingeloggt!"]);
    exit;
}

// Wir geben JSON zurück
header('Content-Type: application/json');

// DB-Verbindung herstellen
try {
    $pdo = new PDO("mysql:host=localhost;dbname=webdatabase;charset=utf8", "root", "");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    // Schlägt die Verbindung fehl, geben wir eine JSON-Fehlermeldung
    echo json_encode(["success" => false, "message" => "DB-Fehler: " . $e->getMessage()]);
    exit;
}

// Daten empfangen (JSON):
$data = json_decode(file_get_contents("php://input"), true);
if (!$data || !isset($data['stock_id']) || !isset($data['briefkurs'])) {
    echo json_encode(["success" => false, "message" => "Fehlende Parameter (stock_id, briefkurs)"]);
    exit;
}

$stockId   = (int)$data['stock_id'];
$briefkurs = (float)$data['briefkurs'];

try {
    // 1) In stock_history einfügen
    $ins = $pdo->prepare("
      INSERT INTO stock_history (stock_id, tick_time, kurs)
      VALUES (?, NOW(), ?)
    ");
    $ins->execute([$stockId, $briefkurs]);

    // 2) Nur die letzten 10 Einträge behalten
    $countStmt = $pdo->prepare("SELECT COUNT(*) FROM stock_history WHERE stock_id = ?");
    $countStmt->execute([$stockId]);
    $total = $countStmt->fetchColumn();

    if ($total > 10) {
        // Zu viele Einträge -> die ältesten löschen
        $toDelete = $total - 10;
        $del = $pdo->prepare("
          DELETE FROM stock_history
          WHERE stock_id = ?
          ORDER BY tick_time ASC
          LIMIT $toDelete
        ");
        $del->execute([$stockId]);
    }

    // (Optional) Wenn du eine Tabelle 'stocks' hast, um den aktuellen Kurs zu speichern:
    $upd = $pdo->prepare("
      UPDATE stocks SET aktueller_kurs = ?
      WHERE id = ?
    ");
    $upd->execute([$briefkurs, $stockId]);

    // Erfolgsmeldung
    echo json_encode(["success" => true, "message" => "Kurs gespeichert"]);
} catch (PDOException $e) {
    // Falls beim Einfügen oder Updaten ein Fehler auftritt
    echo json_encode(["success" => false, "message" => $e->getMessage()]);
    exit;
}
