<?php
session_start();

if (!isset($_SESSION['angemeldet']) || $_SESSION['angemeldet'] !== true) {
    echo json_encode(["success" => false, "message" => "Nicht eingeloggt!"]);
    exit;
}

if (!isset($_SESSION['user_id'])) {
    echo json_encode(["success" => false, "message" => "Fehlende user_id in Session!"]);
    exit;
}

try {
    $pdo = new PDO("mysql:host=localhost;dbname=webdatabase;charset=utf8", "root", "");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    echo json_encode(["success" => false, "message" => "DB-Fehler: " . $e->getMessage()]);
    exit;
}

// NEU: Komma-zu-Punkt-Konvertierung für alle Geldwerte
function parseCurrency($value) {
    return round((float)str_replace(',', '.', $value), 2);
}

$typ = $_POST['typ'] ?? '';
$response = ["success" => false, "message" => "", "spielgeld" => $_SESSION['spielgeld'] ?? 50000];

// Kauf-Logik
if ($typ === 'buy' && !empty($_POST['anzahl'])) {
    $anzahl = (int)$_POST['anzahl'];
    $stockName = $_POST['stock_name'] ?? 'unbekannt';
    $briefkurs = parseCurrency($_POST['briefkurs'] ?? 100.0);
    
    $orderwert = $anzahl * $briefkurs;
    $provision = max(min($orderwert * 0.0025 + 4.95, 59.99), 9.99);
    $gesamt = $orderwert + $provision;

    if ($gesamt <= $_SESSION['spielgeld']) {
        $_SESSION['spielgeld'] -= $gesamt;
        // Aktualisierung des globalen Depotbestands (für Gesamtanzeige)
        $_SESSION['anzahl_aktien'] = ($_SESSION['anzahl_aktien'] ?? 0) + $anzahl;

        try {
            $ins = $pdo->prepare("INSERT INTO orders 
                (user_id, stock_name, order_type, anzahl, price, provision, created_at)
                VALUES (:uid, :sname, 'buy', :anz, :prc, :prov, NOW())");
            $ins->execute([
                'uid' => $_SESSION['user_id'],
                'sname' => $stockName,
                'anz' => $anzahl,
                'prc' => $briefkurs,
                'prov' => $provision
            ]);
            $response["success"] = true;
            $response["message"] = "Kauf erfolgreich! {$anzahl}× {$stockName}<br>"
                . "Provision: " . number_format($provision, 2, ',', '.') . " €";
        } catch (PDOException $e) {
            $response["message"] = "DB-Fehler: " . $e->getMessage();
        }
    } else {
        $response["message"] = "Nicht genug Guthaben!";
    }
}

// Verkauf-Logik – angepasst: Abfrage des aktuellen Bestands pro Aktie aus der orders-Tabelle
elseif ($typ === 'sell' && !empty($_POST['anzahl'])) {
    $anzahl = (int)$_POST['anzahl'];
    $stockName = $_POST['stock_name'] ?? 'unbekannt';
    
    // Bestandsabfrage: Ermittelt den Bestand (Käufe - Verkäufe) für diese spezifische Aktie
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
        'uid'   => $_SESSION['user_id'],
        'sname' => $stockName
    ]);
    $currentHeld = (int)$stmt->fetchColumn();

    if ($anzahl <= $currentHeld) {
        $geldkurs = parseCurrency($_POST['geldkurs'] ?? 99.0);
        $orderwert = $anzahl * $geldkurs;
        $provision = max(min($orderwert * 0.0025 + 4.95, 59.99), 9.99);
        $erlös = max($orderwert - $provision, 0);

        $_SESSION['spielgeld'] += $erlös;
        // Aktualisiere den globalen Depotbestand (falls genutzt)
        $_SESSION['anzahl_aktien'] -= $anzahl;

        try {
            $ins = $pdo->prepare("INSERT INTO orders 
                (user_id, stock_name, order_type, anzahl, price, provision, created_at)
                VALUES (:uid, :sname, 'sell', :anz, :prc, :prov, NOW())");
            $ins->execute([
                'uid' => $_SESSION['user_id'],
                'sname' => $stockName,
                'anz' => $anzahl,
                'prc' => $geldkurs,
                'prov' => $provision
            ]);
            $response["success"] = true;
            $response["message"] = "Verkauf erfolgreich! {$anzahl} Aktien<br>Provision: " 
                . number_format($provision, 2, ',', '.') . " €";
        } catch (PDOException $e) {
            $response["message"] = "DB-Fehler: " . $e->getMessage();
        }
    } else {
        $response["message"] = "Nicht genug Aktien!";
    }
}

// Hühner-Spiel Logik
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
// Spiel beenden
elseif ($typ === 'beenden') {
    $response["success"] = true;
    $response["message"] = "Spiel beendet! Guthaben: " 
        . number_format($_SESSION['spielgeld'] ?? 0, 2, ',', '.') . " €";
}

// DB-Update mit Rundung
try {
    $update = $pdo->prepare("UPDATE users SET 
        spielgeld = ROUND(:sg, 2), 
        anzahl_aktien = :aktien 
        WHERE id = :id");
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
