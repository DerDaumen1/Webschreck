<?php
session_start();

// Nur fortfahren, wenn eingeloggt
if (!isset($_SESSION['angemeldet']) || $_SESSION['angemeldet'] !== true) {
    echo json_encode(["success" => false, "message" => "Nicht eingeloggt!"]);
    exit;
}

// Prüfen, ob wir überhaupt wissen, welcher Benutzer es ist
if (!isset($_SESSION['user_id'])) {
    echo json_encode(["success" => false, "message" => "Fehlende user_id in Session!"]);
    exit;
}

// DB-Verbindung (Beispiel mit PDO)
try {
    $pdo = new PDO("mysql:host=localhost;dbname=webdatabase;charset=utf8", "root", "");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    echo json_encode(["success" => false, "message" => "DB-Verbindung fehlgeschlagen: " . $e->getMessage()]);
    exit;
}

// POST-Daten
$typ        = $_POST['typ'] ?? '';
$anzahl     = (int)($_POST['anzahl'] ?? 0);
$stockName  = $_POST['stock_name'] ?? 'unbekannt';
$briefkurs  = (float)($_POST['briefkurs'] ?? 100.0);
$geldkurs   = (float)($_POST['geldkurs'] ?? 99.0);

// Hilfsfunktion zur Orderprovision
function berechneProvision($orderwert) {
    $p = 4.95 + ($orderwert * 0.0025);
    if ($p < 9.99)  $p = 9.99;
    if ($p > 59.99) $p = 59.99;
    return $p;
}

// Basis-Response
$response = [
    "success"       => false,
    "message"       => "",
    "spielgeld"     => $_SESSION['spielgeld'] ?? 50000
];

// Kauf/Verkauf/Beenden
if ($typ === 'kaufen' && $anzahl > 0) {
    $orderwert = $anzahl * $briefkurs;
    $provision = berechneProvision($orderwert);
    $gesamt    = $orderwert + $provision;

    if ($gesamt <= $_SESSION['spielgeld']) {
        $_SESSION['spielgeld'] -= $gesamt;

        // Optional: wenn du pro Aktie Lager brauchst: $_SESSION['anzahl_pro_aktie'][$stockName] += $anzahl;
        // Hier belassen wir es bei der alten "anzahl_aktien"-Variable, 
        // was aber natürlich nur 1 Aktie abbildet. 
        $_SESSION['anzahl_aktien'] = ($_SESSION['anzahl_aktien'] ?? 0) + $anzahl;

        $response["success"] = true;
        $response["message"] = "Kauf erfolgreich! ({$anzahl} Aktien)<br>Orderprovision: " 
                               . number_format($provision, 2, ',', '.') . " €";

        // DB-Eintrag (Orderbuch)
        try {
          $ins = $pdo->prepare("
            INSERT INTO orders (user_id, stock_name, order_type, anzahl, price, provision, created_at)
            VALUES (:uid, :sname, 'buy', :anz, :prc, :prov, NOW())
          ");
          $ins->execute([
            'uid'   => $_SESSION['user_id'],
            'sname' => $stockName,
            'anz'   => $anzahl,
            'prc'   => $briefkurs,
            'prov'  => $provision
          ]);
        } catch (PDOException $e) {
          $response["message"] .= " (DB-Fehler: ".$e->getMessage().")";
        }

    } else {
        $response["message"] = "Fehler: Nicht genügend Spielgeld!";
    }

} elseif ($typ === 'verkaufen' && $anzahl > 0) {
    // Bei einer Mehr-Aktien-Logik solltest du aus DB/Session abfragen, wie viel 
    // der Nutzer von $stockName hat. Hier vereinfachen wir's:
    $currentHeld = $_SESSION['anzahl_aktien'] ?? 0;

    if ($anzahl <= $currentHeld) {
        $orderwert = $anzahl * $geldkurs;
        $provision = berechneProvision($orderwert);
        $erlös     = $orderwert - $provision;
        if ($erlös < 0) $erlös = 0;

        $_SESSION['spielgeld'] += $erlös;
        $_SESSION['anzahl_aktien'] = $currentHeld - $anzahl;

        $response["success"] = true;
        $response["message"] = "Verkauf erfolgreich! ({$anzahl} Aktien)";

        // DB-Eintrag (Orderbuch)
        try {
          $ins = $pdo->prepare("
            INSERT INTO orders (user_id, stock_name, order_type, anzahl, price, provision, created_at)
            VALUES (:uid, :sname, 'sell', :anz, :prc, :prov, NOW())
          ");
          $ins->execute([
            'uid'   => $_SESSION['user_id'],
            'sname' => $stockName,
            'anz'   => $anzahl,
            'prc'   => $geldkurs,
            'prov'  => $provision
          ]);
        } catch (PDOException $e) {
          $response["message"] .= " (DB-Fehler: ".$e->getMessage().")";
        }

    } else {
        $response["message"] = "Fehler: Sie besitzen nicht genug Aktien!";
    }

} elseif ($typ === 'beenden') {
    $response["success"] = true;
    $endguthaben = number_format($_SESSION['spielgeld'] ?? 0, 2, ',', '.');
    $response["message"] = "Spiel beendet! Ihr Endguthaben: {$endguthaben} €";

} else {
    $response["message"] = "Ungültige Aktion!";
}

// Aktualisierte Werte in die Response
$response["spielgeld"] = $_SESSION['spielgeld'] ?? 50000;
echo json_encode($response);
