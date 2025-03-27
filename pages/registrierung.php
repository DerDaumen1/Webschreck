<?php
session_start();
require_once __DIR__ . '/../includes/db.php';

// Array für serverseitige Fehlermeldungen
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

    // Serverseitige Validierung: Wir prüfen nur, ob sich die Felder grundsätzlich unterscheiden.
    // (Die Inline-Validierung via JavaScript übernimmt das bereits für E-Mail.)
    if ($email !== $emailWdh) {
        $fehler[] = "Die E-Mail-Adressen stimmen nicht überein.";
    }
    if ($passwort !== $passwortWdh) {
        $fehler[] = "Die Passwörter stimmen nicht überein.";
    }

    // (Optional: Weitere serverseitige Validierungen, z.B. PLZ oder Mindestalter.)

    // Nur wenn keine Fehler vorliegen -> in DB schreiben
    if (count($fehler) === 0) {
        $stmt = $pdo->prepare("
            INSERT INTO users 
              (anrede, vorname, nachname, email, passwort, geburtsdatum, plz, spielgeld, anzahl_aktien)
            VALUES 
              (:anrede, :vorname, :nachname, :email, :passwort, :geburtsdatum, :plz, 50000, 0)
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

        $_SESSION['user_id'] = $pdo->lastInsertId();
        $_SESSION['angemeldet']    = true;
        $_SESSION['nutzer_email']  = $email;
        $_SESSION['vorname']       = $vorname;
        $_SESSION['nachname']      = $nachname;
        $_SESSION['spielgeld']     = 50000;
        $_SESSION['anzahl_aktien'] = 0;

        $erfolg = true;
        header('Location: ../index.php');
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="de">
<head>
  <meta charset="UTF-8">
  <title>Registrierung Börsenspiel</title>
  <link rel="stylesheet" href="../assets/css/registrierung.css">
</head>
<body>

<div class="container">
  <div class="card">
    <h1>Registrierung Börsenspiel</h1>

    <?php if (!$erfolg): ?>
      <!-- Serverseitige Fehler (allgemein) werden nur angezeigt, falls sie auftreten.
           Die Inline-Fehlermeldungen bei den Feldern werden via JS und HTML5 abgebildet. -->
      <?php if (!empty($fehler)): ?>
        <div class="fehler">
          <ul>
            <?php foreach ($fehler as $err): ?>
              <li><?= htmlspecialchars($err) ?></li>
            <?php endforeach; ?>
          </ul>
        </div>
      <?php endif; ?>

      <form id="regForm" method="post" action="registrierung.php">
        
        <div class="form-group">
          <label for="anrede">Anrede</label>
          <input 
            type="text" 
            id="anrede" 
            name="anrede" 
            required 
            placeholder="Herr / Frau / Divers"
            value="<?= htmlspecialchars($_POST['anrede'] ?? '') ?>" 
          />
        </div>

        <div class="form-group">
          <label for="vorname">Vorname</label>
          <input 
            type="text" 
            id="vorname" 
            name="vorname" 
            required 
            placeholder="Max"
            value="<?= htmlspecialchars($_POST['vorname'] ?? '') ?>" 
          />
        </div>

        <div class="form-group">
          <label for="nachname">Nachname</label>
          <input 
            type="text" 
            id="nachname" 
            name="nachname" 
            required 
            placeholder="Mustermann"
            value="<?= htmlspecialchars($_POST['nachname'] ?? '') ?>" 
          />
        </div>

        <div class="form-group">
          <label for="email">E-Mail</label>
          <input 
            type="email" 
            id="email" 
            name="email" 
            required 
            placeholder="beispiel@domain.de"
            value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" 
            oninput="checkEmail()" 
            oninvalid="checkEmail()"
          />
        </div>

        <div class="form-group">
          <label for="emailWdh">E-Mail wiederholen</label>
          <input 
            type="email" 
            id="emailWdh" 
            name="emailWdh" 
            required 
            placeholder="nochmal eingeben"
            value="<?= htmlspecialchars($_POST['emailWdh'] ?? '') ?>" 
            oninput="checkEmail()" 
            oninvalid="checkEmail()"
          />
        </div>

        <div class="form-group">
          <label for="geburtsdatum">Geburtsdatum</label>
          <input 
            type="date" 
            id="geburtsdatum" 
            name="geburtsdatum" 
            required
            value="<?= htmlspecialchars($_POST['geburtsdatum'] ?? '') ?>" 
            oninput="checkGeburtsdatum()" 
            oninvalid="checkGeburtsdatum()"
          />
        </div>

        <div class="form-group">
          <label for="plz">PLZ</label>
          <input 
            type="text" 
            id="plz" 
            name="plz" 
            required 
            placeholder="12345"
            value="<?= htmlspecialchars($_POST['plz'] ?? '') ?>" 
            pattern="\d{5}" 
            title="Die PLZ muss genau 5 Ziffern haben."
            oninput="checkPlz()" 
            oninvalid="checkPlz()"
          />
        </div>

        <div class="form-group">
          <label for="passwort">Passwort</label>
          <input 
            type="password" 
            id="passwort" 
            name="passwort" 
            required 
            placeholder="••••••"
            oninput="checkPasswort()" 
            oninvalid="checkPasswort()"
          />
        </div>

        <div class="form-group">
          <label for="passwortWdh">Passwort wiederholen</label>
          <input 
            type="password" 
            id="passwortWdh" 
            name="passwortWdh" 
            required 
            placeholder="••••••"
            oninput="checkPasswort()" 
            oninvalid="checkPasswort()"
          />
        </div>

        <button type="submit">Registrieren</button>
      </form>
      <p class="back-link"><a href="../index.php">Zurück zur Startseite</a></p>

    <?php else: ?>
      <p class="success">Erfolgreich registriert!</p>
      <p class="back-link"><a href="../index.php">Weiter zur Startseite</a></p>
    <?php endif; ?>
  </div>
</div>

<script src="../assets/js/registrierung.js"></script>
</body>
</html>
