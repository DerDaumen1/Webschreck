<?php
session_start();

$action = $_GET['action'] ?? '';
$stock_id = (int)($_GET['stock_id'] ?? 1);

if (!isset($_SESSION['stocks'])) {
  $_SESSION['stocks'] = [];
}
$stocks = &$_SESSION['stocks']; // Referenz, damit wir Änderungen zurückschreiben

switch($action) {
  case 'get_stocks':
    // Einfach alle Stocks als JSON zurück
    header('Content-Type: application/json');
    echo json_encode($stocks);
    exit;

  case 'get_history':
    $hist = $_SESSION['stock_history'][$stock_id] ?? [];
    header('Content-Type: application/json');
    echo json_encode($hist);
    exit;

  case 'update_one':
    // Aktie suchen
    $key = array_search($stock_id, array_column($stocks, 'id'));
    if ($key===false) {
      header('Content-Type: application/json');
      echo json_encode(["error"=>"Aktie nicht gefunden"]);
      exit;
    }
    // Kurz random auf/ab
    $chanceUp = 0.5;
    // Bullen/Bären-Logik: hier Minimalbeispiel
    $delta = (mt_rand(1,5)/100); // 0.01..0.05
    if (mt_rand(0,1000)/1000 < $chanceUp) {
      $stocks[$key]['briefkurs'] += $delta;
      $stocks[$key]['geldkurs']  += $delta;
      $phase = "Bullenmarkt";
    } else {
      $stocks[$key]['briefkurs'] -= $delta;
      $stocks[$key]['geldkurs']  -= $delta;
      $phase = "Bärenmarkt";
    }
    if ($stocks[$key]['briefkurs']<0.01) $stocks[$key]['briefkurs'] = 0.01;
    if ($stocks[$key]['geldkurs']<0) $stocks[$key]['geldkurs'] = 0;

    // In die History schreiben (max. 10 Einträge)
    $_SESSION['stock_history'][$stock_id][] = $stocks[$key]['briefkurs'];
    if (count($_SESSION['stock_history'][$stock_id])>10) {
      array_shift($_SESSION['stock_history'][$stock_id]); // ältesten Eintrag entfernen
    }

    header('Content-Type: application/json');
    echo json_encode([
      "briefkurs" => $stocks[$key]['briefkurs'],
      "geldkurs"  => $stocks[$key]['geldkurs'],
      "phase"     => $phase,
      "chart"     => $_SESSION['stock_history'][$stock_id],
    ]);
    exit;

  default:
    echo "Ungültige Aktion!";
}
