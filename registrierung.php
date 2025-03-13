<?php
// registrierung.php
session_start();

$dbHost = 'localhost';
$dbUser = 'root';
$dbPass = '';
$dbName = 'webdatabase';

// PDO-Verbindung herstellen
$pdo = new PDO("mysql:host=$dbHost;dbname=$dbName;charset=utf8", $dbUser, $dbPass);

// Array für Fehlermeldungen
$fehler = [];
$erfolg = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Eingaben aus dem Formular lesen
    $anrede        = trim($_POST['anrede'] ?? '');
    $vorname       = trim($_POST['vorname'] ?? '');
    $nachname      = trim($_POST['nachname'] ?? '');
    $email         = trim($_POST['email'] ?? '');
    $emailWdh      = trim($_POST['emailWdh'] ?? '');
    $geburtsdatum  = trim($_POST['geburtsdatum'] ?? '');
    $plz           = trim($_POST['plz'] ?? '');
    $passwort      = trim($_POST['passwort'] ?? '');
    $passwortWdh   = trim($_POST['passwortWdh'] ?? '');

    // E-Mail-Abgleich (serverseitig)
    if ($email !== $emailWdh) {
        $fehler[] = "Die E-Mail-Adressen stimmen nicht überein.";
    }

    // Passwörter abgleichen (serverseitig)
    if ($passwort !== $passwortWdh) {
        $fehler[] = "Die Passwörter stimmen nicht überein.";
    }

    // TODO (Optional, empfehlenswert): weitere serverseitige Validierungen
    // z. B. PLZ muss 5 Ziffern haben, Mindestalter 18 Jahre etc.
    // if (!preg_match('/^\d{5}$/', $plz)) {
    //    $fehler[] = "Die PLZ muss genau 5 Ziffern haben (serverseitig).";
    // }
    // ... und Alter-Berechnung

    // Nur wenn keine Fehler vorliegen, in DB schreiben
    if (count($fehler) === 0) {
        // Nutzer registrieren (Passwort wird gehasht)
        $stmt = $pdo->prepare("
            INSERT INTO users (anrede, vorname, nachname, email, passwort, geburtsdatum, plz, spielgeld, anzahl_aktien)
            VALUES (:anrede, :vorname, :nachname, :email, :passwort, :geburtsdatum, :plz, 50000, 0)
        ");
        $stmt->execute([
            ':anrede'       => $anrede,
            ':vorname'      => $vorname,
            ':nachname'     => $nachname,
            ':email'        => $email,
            ':passwort'     => password_hash($passwort, PASSWORD_DEFAULT),
            ':geburtsdatum' => $geburtsdatum,
            ':plz'          => $plz
        ]);

        // Neuen User-ID in Session merken
        $_SESSION['user_id'] = $pdo->lastInsertId();

        // Direkt einloggen und zur Startseite
        $erfolg = true;
        $_SESSION['angemeldet']    = true;
        $_SESSION['nutzer_email']  = $email;
        $_SESSION['vorname']       = $vorname;
        $_SESSION['nachname']      = $nachname;
        $_SESSION['spielgeld']     = 50000;
        $_SESSION['anzahl_aktien'] = 0;

        header('Location: index.php');
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="de">
<head>
  <meta charset="UTF-8">
  <title>Registrierung Börsenspiel</title>
  <!-- Externes Stylesheet für Registrierungsseite -->
  <link rel="stylesheet" href="registrierung.css">
</head>
<body>
<div class="container">
  <div class="card">
    <h1>Registrierung Börsenspiel</h1>

    <?php if (!$erfolg): ?>
      <!-- Fehlermeldungen ausgeben -->
      <?php if (!empty($fehler)): ?>
        <div class="fehler">
          <ul>
            <?php foreach ($fehler as $f): ?>
              <li><?= htmlspecialchars($f) ?></li>
            <?php endforeach; ?>
          </ul>
        </div>
      <?php endif; ?>

      <!-- Wichtig: onsubmit="return validateForm()" damit JS prüft! -->
      <form id="regForm" method="post" action="registrierung.php" onsubmit="return validateForm()">
        <div class="form-group">
          <label for="anrede">Anrede</label>
          <input type="text" id="anrede" name="anrede" required placeholder="Herr / Frau / Divers" />
        </div>

        <div class="form-group">
          <label for="vorname">Vorname</label>
          <input type="text" id="vorname" name="vorname" required placeholder="Max" />
        </div>

        <div class="form-group">
          <label for="nachname">Nachname</label>
          <input type="text" id="nachname" name="nachname" required placeholder="Mustermann" />
        </div>

        <div class="form-group">
          <label for="email">E-Mail</label>
          <input type="email" id="email" name="email" required placeholder="beispiel@domain.de" />
        </div>

        <div class="form-group">
          <label for="emailWdh">E-Mail wiederholen</label>
          <input type="email" id="emailWdh" name="emailWdh" required placeholder="nochmal eingeben" />
        </div>

        <div class="form-group">
          <label for="geburtsdatum">Geburtsdatum</label>
          <input type="date" id="geburtsdatum" name="geburtsdatum" required />
        </div>

        <div class="form-group">
          <label for="plz">PLZ</label>
          <input type="text" id="plz" name="plz" required placeholder="12345" />
        </div>

        <div class="form-group">
          <label for="passwort">Passwort</label>
          <input type="password" id="passwort" name="passwort" required placeholder="••••••" />
        </div>

        <div class="form-group">
          <label for="passwortWdh">Passwort wiederholen</label>
          <input type="password" id="passwortWdh" name="passwortWdh" required placeholder="••••••" />
        </div>

        <button type="submit">Registrieren</button>
      </form>
      <p class="back-link"><a href="index.php">Zurück zur Startseite</a></p>
    <?php else: ?>
      <p class="success">Erfolgreich registriert!</p>
      <p class="back-link"><a href="index.php">Weiter zur Startseite</a></p>
    <?php endif; ?>
  </div>
</div>

<!-- Ganz unten: JavaScript einbinden -->
<script src="registrierung.js"></script>
</body>
</html>
