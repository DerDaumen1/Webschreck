<?php
session_start();
date_default_timezone_set('Europe/Berlin'); // Setze die korrekte Zeitzone

// Nur fortfahren, wenn eingeloggt
if (!isset($_SESSION['angemeldet']) || $_SESSION['angemeldet'] !== true) {
    header('Content-Type: application/json');
    echo json_encode(["success" => false, "message" => "Nicht eingeloggt!"]);
    exit;
}

// JSON-Header setzen
header('Content-Type: application/json');

// DB-Verbindung herstellen
try {
    $pdo = new PDO("mysql:host=localhost;dbname=webdatabase;charset=utf8", "root", "");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    echo json_encode(["success" => false, "message" => "DB-Fehler: " . $e->getMessage()]);
    exit;
}

// Beim ersten Aufruf der Session alle alten Einträge entfernen
if (!isset($_SESSION['session_started'])) {
    $pdo->exec("DELETE FROM stock_history");
    $_SESSION['session_started'] = true;
}

// JSON-Daten empfangen
$data = json_decode(file_get_contents("php://input"), true);
if (!$data || !isset($data['stock_id']) || !isset($data['briefkurs'])) {
    echo json_encode(["success" => false, "message" => "Fehlende Parameter (stock_id, briefkurs)"]);
    exit;
}

$stockId   = (int)$data['stock_id'];
$briefkurs = (float)$data['briefkurs'];

try {
    // Statt den neuen Tick immer mit dem aktuellen Datum zu setzen,
    // prüfe, ob bereits ein letzter Tick vorhanden ist und addiere einen Tag darauf:
    $stmt = $pdo->prepare("SELECT MAX(tick_time) AS last_tick FROM stock_history WHERE stock_id = ?");
    $stmt->execute([$stockId]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($result && $result['last_tick']) {
        $newTickTime = date("Y-m-d H:i:s", strtotime($result['last_tick'] . " +1 day"));
    } else {
        $newTickTime = date("Y-m-d H:i:s");
    }

    // 1) In stock_history einfügen
    $ins = $pdo->prepare("
      INSERT INTO stock_history (stock_id, tick_time, kurs)
      VALUES (?, ?, ?)
    ");
    $ins->execute([$stockId, $newTickTime, $briefkurs]);

    // 2) Nur die letzten 10 Einträge behalten
    $countStmt = $pdo->prepare("SELECT COUNT(*) FROM stock_history WHERE stock_id = ?");
    $countStmt->execute([$stockId]);
    $total = $countStmt->fetchColumn();

    if ($total > 10) {
        // Lösche die ältesten Einträge, sodass nur die 10 neuesten übrig bleiben.
        $delStmt = $pdo->prepare("
            DELETE FROM stock_history
            WHERE stock_id = ? AND id NOT IN (
                SELECT id FROM (
                    SELECT id FROM stock_history WHERE stock_id = ? ORDER BY tick_time DESC LIMIT 10
                ) as tmp
            )
        ");
        $delStmt->execute([$stockId, $stockId]);
    }

    // 3) Optional: Aktualisiere in der Tabelle stocks (falls vorhanden) den aktuellen Kurs
    $upd = $pdo->prepare("
      UPDATE stocks SET aktueller_kurs = ?
      WHERE id = ?
    ");
    $upd->execute([$briefkurs, $stockId]);

    echo json_encode(["success" => true, "message" => "Kurs gespeichert"]);
} catch (PDOException $e) {
    echo json_encode(["success" => false, "message" => $e->getMessage()]);
    exit;
}
