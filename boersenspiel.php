<?php
// boersenspiel.php
session_start();

// Wenn nicht registriert/eingeloggt -> zurück zur Registrierung
if (!isset($_SESSION['angemeldet']) || $_SESSION['angemeldet'] !== true) {
    header('Location: registrierung.php');
    exit;
}

// Spielgeld aus Session
if (!isset($_SESSION['spielgeld'])) {
    $_SESSION['spielgeld'] = 50000;
}
if (!isset($_SESSION['anzahl_aktien'])) {
    $_SESSION['anzahl_aktien'] = 0;
}

// Prüfen, ob Kauf/Verkauf via POST gesendet wurde
$meldung = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Minimaler Kauf/Verkauf-Mechanismus:
    $typ = $_POST['typ'] ?? '';
    $anzahl = (int)($_POST['anzahl'] ?? 0);
    $briefkurs = (float)($_POST['briefkurs'] ?? 100.0);
    $geldkurs  = (float)($_POST['geldkurs'] ?? 99.0);
    
    // Orderprovision berechnen:
    // 4,95 Euro + 0,25% vom Orderwert (min. 9,99, max. 59,99)
    // Orderwert = anzahl * (Briefkurs oder Geldkurs)
    if ($typ === 'kaufen' && $anzahl > 0) {
        $orderwert = $anzahl * $briefkurs;
        $provision = 4.95 + ($orderwert * 0.0025);
        if ($provision < 9.99) $provision = 9.99;
        if ($provision > 59.99) $provision = 59.99;
        
        $gesamtKosten = $orderwert + $provision;
        
        if ($gesamtKosten <= $_SESSION['spielgeld']) {
            $_SESSION['spielgeld'] -= $gesamtKosten;
            $_SESSION['anzahl_aktien'] += $anzahl;
            $meldung = "Kauf erfolgreich! ($anzahl Aktien)";
        } else {
            $meldung = "Fehler: Nicht genügend Spielgeld vorhanden!";
        }
    }
    elseif ($typ === 'verkaufen' && $anzahl > 0) {
        if ($anzahl <= $_SESSION['anzahl_aktien']) {
            $orderwert = $anzahl * $geldkurs;
            $provision = 4.95 + ($orderwert * 0.0025);
            if ($provision < 9.99) $provision = 9.99;
            if ($provision > 59.99) $provision = 59.99;
            
            $erlös = $orderwert - $provision;
            if ($erlös < 0) $erlös = 0; // rein zur Sicherheit
            $_SESSION['spielgeld'] += $erlös;
            $_SESSION['anzahl_aktien'] -= $anzahl;
            $meldung = "Verkauf erfolgreich! ($anzahl Aktien)";
        } else {
            $meldung = "Fehler: Sie besitzen nicht genug Aktien zum Verkauf!";
        }
    }
    // Optional: Spiel beenden, etc.
    elseif ($typ === 'beenden') {
        // Hier z.B. Ergebnis anzeigen oder Session zurücksetzen
        $meldung = "Spiel beendet! Ihr Endguthaben: ".$_SESSION['spielgeld']." €";
        // z.B. Session-Variablen resetten:
        // session_destroy();
    }
}

?>
<!DOCTYPE html>
<html lang="de">
<head>
  <meta charset="UTF-8">
  <title>Börsenspiel</title>
  <style>
    #chartContainer {
      width: 600px; 
      height: 300px; 
      border: 1px solid #ccc; 
      margin: 1rem auto;
    }
    .info {
      text-align: center;
      margin: 1rem;
    }
    .meldung {
      color: blue;
      font-weight: bold;
      text-align: center;
    }
    .controls {
      text-align: center;
      margin: 1rem;
    }
    input[type="number"] {
      width: 60px;
    }
  </style>
  <script src="boerse.js"></script>
</head>
<body onload="initChart();">

<h1 style="text-align:center;">Börsenspiel</h1>
<div id="chartContainer"></div>

<div class="meldung">
  <?php echo htmlspecialchars($meldung); ?>
</div>

<div class="info">
  <p><strong>Aktuelles Spielgeld:</strong> <?php echo number_format($_SESSION['spielgeld'], 2, ',', '.'); ?> €</p>
  <p><strong>Anzahl Aktien im Depot:</strong> <?php echo $_SESSION['anzahl_aktien']; ?></p>
  
  <!-- Hier könnte man Gewinn/Verlust berechnen, wenn man den aktuellen Brief-/Geldkurs kennt -->
</div>

<div class="controls">
  <!-- Werte für JS-Simulation (Brief-/Geldkurs) -->
  <!-- Wir legen hier mal Standardwerte ab, boerse.js wird sie dynamisch ändern -->
  <span id="briefkursDisplay">Briefkurs: 100.00 €</span> - 
  <span id="geldkursDisplay">Geldkurs: 99.00 €</span> 
  
  <form method="post" style="margin-top:1rem;">
    <input type="hidden" name="briefkurs" id="briefkursHidden" value="100" />
    <input type="hidden" name="geldkurs" id="geldkursHidden" value="99" />
    
    <label>Anzahl:
      <input type="number" name="anzahl" value="1" min="1" />
    </label>
    
    <button type="submit" name="typ" value="kaufen">Aktien kaufen</button>
    <button type="submit" name="typ" value="verkaufen">Aktien verkaufen</button>
    <button type="submit" name="typ" value="beenden">Spiel beenden</button>
  </form>
</div>

<p style="text-align:center;">
  <a href="index.html">Zur Startseite</a> | 
  <a href="logout.php">Logout</a>
</p>

</body>
</html>
