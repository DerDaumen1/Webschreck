<?php
session_start();

// Optional: Nur fortfahren, wenn eingeloggt
if (!isset($_SESSION['angemeldet']) || $_SESSION['angemeldet'] !== true) {
    echo json_encode(["error" => "Nicht eingeloggt!"]);
    exit;
}

// Standardwerte
if (!isset($_SESSION['spielgeld'])) {
    $_SESSION['spielgeld'] = 50000;
}
if (!isset($_SESSION['anzahl_aktien'])) {
    $_SESSION['anzahl_aktien'] = 0;
}

// POST-Daten
$typ       = $_POST['typ'] ?? '';
$anzahl    = (int)($_POST['anzahl'] ?? 0);
$briefkurs = (float)($_POST['briefkurs'] ?? 100.0);
$geldkurs  = (float)($_POST['geldkurs'] ?? 99.0);

function berechneProvision($orderwert) {
    $p = 4.95 + ($orderwert * 0.0025);
    if ($p < 9.99)  $p = 9.99;
    if ($p > 59.99) $p = 59.99;
    return $p;
}

$response = [
    "success" => false,
    "message" => "",
    "spielgeld" => $_SESSION['spielgeld'],
    "anzahl_aktien" => $_SESSION['anzahl_aktien']
];

if ($typ === 'kaufen' && $anzahl > 0) {
    $orderwert = $anzahl * $briefkurs;
    $prov = berechneProvision($orderwert);
    $gesamt = $orderwert + $prov;
    if ($gesamt <= $_SESSION['spielgeld']) {
        $_SESSION['spielgeld'] -= $gesamt;
        $_SESSION['anzahl_aktien'] += $anzahl;
        $response["success"] = true;
        $response["message"] = "Kauf erfolgreich! ($anzahl Aktien)";
    } else {
        $response["message"] = "Fehler: Nicht genügend Spielgeld!";
    }
}
elseif ($typ === 'verkaufen' && $anzahl > 0) {
    if ($anzahl <= $_SESSION['anzahl_aktien']) {
        $orderwert = $anzahl * $geldkurs;
        $prov = berechneProvision($orderwert);
        $erlös = $orderwert - $prov;
        if ($erlös < 0) $erlös = 0;
        $_SESSION['spielgeld'] += $erlös;
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

// Aktuelle Werte zurückgeben
$response["spielgeld"] = $_SESSION['spielgeld'];
$response["anzahl_aktien"] = $_SESSION['anzahl_aktien'];

echo json_encode($response);
