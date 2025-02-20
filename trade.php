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
$typ       = $_POST['typ'] ?? '';
$anzahl    = (int)($_POST['anzahl'] ?? 0);
$briefkurs = (float)($_POST['briefkurs'] ?? 100.0);
$geldkurs  = (float)($_POST['geldkurs'] ?? 99.0);

// Hilfsfunktion zur Orderprovision
function berechneProvision($orderwert) {
    $p = 4.95 + ($orderwert * 0.0025);
    if ($p < 9.99)  $p = 9.99;
    if ($p > 59.99) $p = 59.99;
    return $p;
}

// Basis-Response
$response = [
    "success" => false,
    "message" => "",
    "spielgeld"     => $_SESSION['spielgeld'],      // aus Session
    "anzahl_aktien" => $_SESSION['anzahl_aktien']   // aus Session
];

// Kauf/Verkauf/Beenden
if ($typ === 'kaufen' && $anzahl > 0) {
    $orderwert = $anzahl * $briefkurs;
    $provision = berechneProvision($orderwert);
    $gesamt    = $orderwert + $provision;

    if ($gesamt <= $_SESSION['spielgeld']) {
        $_SESSION['spielgeld']     -= $gesamt;
        $_SESSION['anzahl_aktien'] += $anzahl;

        $response["success"] = true;
        $response["message"] = "Kauf erfolgreich! ({$anzahl} Aktien)<br>Orderprovision: " . number_format($provision, 2, ',', '.') . " €";
    } else {
        $response["message"] = "Fehler: Nicht genügend Spielgeld!";
    }
}
elseif ($typ === 'verkaufen' && $anzahl > 0) {
    if ($anzahl <= $_SESSION['anzahl_aktien']) {
        $orderwert = $anzahl * $geldkurs;
        $provision = berechneProvision($orderwert);
        $erlös     = $orderwert - $provision;
        if ($erlös < 0) $erlös = 0;

        $_SESSION['spielgeld']     += $erlös;
        $_SESSION['anzahl_aktien'] -= $anzahl;

        $response["success"] = true;
        $response["message"] = "Verkauf erfolgreich! ($anzahl Aktien)";
    } else {
        $response["message"] = "Fehler: Sie besitzen nicht genug Aktien!";
    }
}
elseif ($typ === 'beenden') {
    $response["success"] = true;
    $endguthaben = number_format($_SESSION['spielgeld'], 2, ',', '.');
    $response["message"] = "Spiel beendet! Ihr Endguthaben: {$endguthaben} €";
    // Optional: session_destroy();
}
else {
    $response["message"] = "Ungültige Aktion!";
}

// Wenn Erfolg oder nicht – Session ist jetzt ggf. aktualisiert.
// => Speichere neue Werte in DB, damit es dauerhaft bleibt.
try {
    $update = $pdo->prepare("
        UPDATE users
        SET spielgeld = :sg, anzahl_aktien = :aktien
        WHERE id = :id
    ");
    $update->execute([
        'sg'     => $_SESSION['spielgeld'],
        'aktien' => $_SESSION['anzahl_aktien'],
        'id'     => $_SESSION['user_id']
    ]);
} catch (PDOException $e) {
    // Falls das Update fehlschlägt, bleibt Session zwar geändert,
    // aber DB ist evtl. veraltet. 
    // Du kannst hier eine Fehlermeldung ausgeben oder loggen.
    $response["message"] .= " (DB-Update-Fehler: " . $e->getMessage() . ")";
}

// Aktualisierte Werte in die Response
$response["spielgeld"]     = $_SESSION['spielgeld'];
$response["anzahl_aktien"] = $_SESSION['anzahl_aktien'];

// JSON-Ausgabe
echo json_encode($response);
