<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['angemeldet']) || $_SESSION['angemeldet'] !== true) {
    echo json_encode(["success" => false, "message" => "Nicht eingeloggt!"]);
    exit;
}

require_once 'db.php';

function parseCurrency($value) {
    return round((float)str_replace(',', '.', $value), 2);
}

// Routing per GET/POST parameter "action"
$action = $_REQUEST['action'] ?? '';

switch ($action) {
    case 'get_holding':
        // Hier werden stock_id über $_GET erwartet
        $stock_id = (int)($_GET['stock_id'] ?? 0);
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
          'uid' => $_SESSION['user_id'],
          'sname' => $stock_name
        ]);
        $bestand = (int)$stmt->fetchColumn();
        echo json_encode(["success" => true, "bestand" => $bestand]);
        break;
        
    case 'get_history':
        $stockId = (int)($_GET['stock_id'] ?? 1);
        if ($stockId < 1) {
            echo json_encode(["success" => false, "message" => "Ungültige stock_id"]);
            exit;
        }
        $stmt = $pdo->prepare("
            SELECT kurs, DATE_FORMAT(tick_time, '%d.%m.%Y') AS tick_time
            FROM stock_history
            WHERE stock_id = ?
            ORDER BY tick_time DESC
            LIMIT 10
        ");
        $stmt->execute([$stockId]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode(["success" => true, "history" => $rows]);
        break;
        
    case 'update_stocks':
        // JSON-Daten empfangen
        $data = json_decode(file_get_contents("php://input"), true);
        if (!$data || !isset($data['stock_id']) || !isset($data['briefkurs'])) {
            echo json_encode(["success" => false, "message" => "Fehlende Parameter (stock_id, briefkurs)"]);
            exit;
        }
        $stockId   = (int)$data['stock_id'];
        $briefkurs = (float)$data['briefkurs'];
        // Bei erstem Session-Aufruf werden alte Einträge gelöscht
        if (!isset($_SESSION['session_started'])) {
            $pdo->exec("DELETE FROM stock_history");
            $_SESSION['session_started'] = true;
        }
        $stmt = $pdo->prepare("SELECT MAX(tick_time) AS last_tick FROM stock_history WHERE stock_id = ?");
        $stmt->execute([$stockId]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        $newTickTime = ($result && $result['last_tick']) ?
          date("Y-m-d H:i:s", strtotime($result['last_tick'] . " +1 day")) :
          date("Y-m-d H:i:s");
        $ins = $pdo->prepare("INSERT INTO stock_history (stock_id, tick_time, kurs) VALUES (?, ?, ?)");
        $ins->execute([$stockId, $newTickTime, $briefkurs]);
        $countStmt = $pdo->prepare("SELECT COUNT(*) FROM stock_history WHERE stock_id = ?");
        $countStmt->execute([$stockId]);
        $total = $countStmt->fetchColumn();
        if ($total > 10) {
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
        // Optionales Update in stocks-Tabelle
        $upd = $pdo->prepare("UPDATE stocks SET aktueller_kurs = ? WHERE id = ?");
        $upd->execute([$briefkurs, $stockId]);
        echo json_encode(["success" => true, "message" => "Kurs gespeichert"]);
        break;
        
    case 'trade':
        // Hier erfolgt der Handel (buy, sell, huhn_bet, huhn_win, beenden)
        $typ = $_POST['typ'] ?? '';
        $response = ["success" => false, "message" => "", "spielgeld" => $_SESSION['spielgeld'] ?? 50000];
        
        if ($typ === 'buy' && !empty($_POST['anzahl'])) {
            $anzahl = (int)$_POST['anzahl'];
            $stockName = $_POST['stock_name'] ?? 'unbekannt';
            $briefkurs = parseCurrency($_POST['briefkurs'] ?? 100.0);
            $orderwert = $anzahl * $briefkurs;
            $provision = max(min($orderwert * 0.0025 + 4.95, 59.99), 9.99);
            $gesamt = $orderwert + $provision;
            if ($gesamt <= $_SESSION['spielgeld']) {
                $_SESSION['spielgeld'] -= $gesamt;
                $_SESSION['anzahl_aktien'] = ($_SESSION['anzahl_aktien'] ?? 0) + $anzahl;
                $ins = $pdo->prepare("INSERT INTO orders (user_id, stock_name, order_type, anzahl, price, provision, created_at)
                    VALUES (:uid, :sname, 'buy', :anz, :prc, :prov, NOW())");
                $ins->execute([
                    'uid' => $_SESSION['user_id'],
                    'sname' => $stockName,
                    'anz' => $anzahl,
                    'prc' => $briefkurs,
                    'prov' => $provision
                ]);
                $response["success"] = true;
                $response["message"] = "Kauf erfolgreich! {$anzahl}× {$stockName}<br>Provision: " .
                  number_format($provision, 2, ',', '.') . " €";
            } else {
                $response["message"] = "Nicht genug Guthaben!";
            }
        }
        elseif ($typ === 'sell' && !empty($_POST['anzahl'])) {
            $anzahl = (int)$_POST['anzahl'];
            $stockName = $_POST['stock_name'] ?? 'unbekannt';
            $stmt = $pdo->prepare("
              SELECT COALESCE(SUM(CASE WHEN order_type = 'buy' THEN anzahl ELSE 0 END), 0)
              - COALESCE(SUM(CASE WHEN order_type = 'sell' THEN anzahl ELSE 0 END), 0)
              AS bestand FROM orders WHERE user_id = :uid AND stock_name = :sname
            ");
            $stmt->execute([
                'uid' => $_SESSION['user_id'],
                'sname' => $stockName
            ]);
            $currentHeld = (int)$stmt->fetchColumn();
            if ($anzahl <= $currentHeld) {
                $geldkurs = parseCurrency($_POST['geldkurs'] ?? 99.0);
                $orderwert = $anzahl * $geldkurs;
                $provision = max(min($orderwert * 0.0025 + 4.95, 59.99), 9.99);
                $erlös = max($orderwert - $provision, 0);
                $_SESSION['spielgeld'] += $erlös;
                $_SESSION['anzahl_aktien'] -= $anzahl;
                $ins = $pdo->prepare("INSERT INTO orders (user_id, stock_name, order_type, anzahl, price, provision, created_at)
                    VALUES (:uid, :sname, 'sell', :anz, :prc, :prov, NOW())");
                $ins->execute([
                    'uid' => $_SESSION['user_id'],
                    'sname' => $stockName,
                    'anz' => $anzahl,
                    'prc' => $geldkurs,
                    'prov' => $provision
                ]);
                $response["success"] = true;
                $response["message"] = "Verkauf erfolgreich! {$anzahl} Aktien<br>Provision: " .
                  number_format($provision, 2, ',', '.') . " €";
            } else {
                $response["message"] = "Nicht genug Aktien!";
            }
        }
        elseif ($typ === 'huhn_bet') {
            $bet = parseCurrency($_POST['bet'] ?? 0);
            if ($bet > $_SESSION['spielgeld']) {
                $response["message"] = "Nicht genug Guthaben!";
            } else {
                $_SESSION['spielgeld'] -= $bet;
                $response["success"] = true;
                $response["message"] = "Einsatz platziert!";
            }
        }
        elseif ($typ === 'huhn_win') {
            $amount = parseCurrency($_POST['amount'] ?? 0);
            $_SESSION['spielgeld'] += $amount;
            $response["success"] = true;
        }
        elseif ($typ === 'beenden') {
            $response["success"] = true;
            $response["message"] = "Spiel beendet! Guthaben: " .
                number_format($_SESSION['spielgeld'] ?? 0, 2, ',', '.') . " €";
        }
        // Update user table
        try {
            $update = $pdo->prepare("UPDATE users SET spielgeld = ROUND(:sg, 2), anzahl_aktien = :aktien WHERE id = :id");
            $update->execute([
                'sg' => round($_SESSION['spielgeld'] ?? 50000, 2),
                'aktien' => $_SESSION['anzahl_aktien'] ?? 0,
                'id' => $_SESSION['user_id']
            ]);
        } catch (PDOException $e) {
            $response["message"] .= " | DB-Update-Fehler: " . $e->getMessage();
        }
        $response["spielgeld"] = number_format($_SESSION['spielgeld'], 2, '.', '');
        echo json_encode($response);
        break;
        
    default:
        echo json_encode(["success" => false, "message" => "Unbekannte Aktion"]);
        break;
}
?>
