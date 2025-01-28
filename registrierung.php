<?php
// registrierung.php
session_start();

// Datenbank-Verbindungsparameter anpassen:
$dbHost = 'localhost';
$dbUser = 'root';
$dbPass = '';
$dbName = 'deine_datenbank';

$pdo = new PDO("mysql:host=$dbHost;dbname=$dbName;charset=utf8", $dbUser, $dbPass);

// POST-Processing:
$fehler = [];
$erfolg = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Auslesen der Formulardaten
    $anrede      = trim($_POST['anrede'] ?? '');
    $vorname     = trim($_POST['vorname'] ?? '');
    $nachname    = trim($_POST['nachname'] ?? '');
    $email       = trim($_POST['email'] ?? '');
    $emailWdh    = trim($_POST['emailWdh'] ?? '');
    $geburtsdatum= trim($_POST['geburtsdatum'] ?? '');
    $plz         = trim($_POST['plz'] ?? '');
    $passwort    = trim($_POST['passwort'] ?? '');
    $passwortWdh = trim($_POST['passwortWdh'] ?? '');

    // Serverseitige Grund-Validierung
    if ($anrede === '') {
        $fehler[] = "Bitte Anrede angeben.";
    }
    if ($vorname === '') {
        $fehler[] = "Bitte Vornamen angeben.";
    }
    if ($nachname === '') {
        $fehler[] = "Bitte Nachnamen angeben.";
    }
    if ($email === '' || $emailWdh === '') {
        $fehler[] = "E-Mail und Wiederholung dürfen nicht leer sein.";
    } elseif ($email !== $emailWdh) {
        $fehler[] = "Die beiden E-Mail-Felder stimmen nicht überein.";
    }
    // Geburtstagscheck (mind. 18 Jahre)
    $ageOk = false;
    if ($geburtsdatum !== '') {
        $diff = date_diff(date_create($geburtsdatum), date_create(date('Y-m-d')));
        $alter = $diff->y;
        if ($alter < 18) {
            $fehler[] = "Sie müssen mindestens 18 Jahre alt sein.";
        } else {
            $ageOk = true;
        }
    } else {
        $fehler[] = "Bitte Geburtsdatum angeben.";
    }

    // PLZ-Check (5 Ziffern)
    if (!preg_match('/^\d{5}$/', $plz)) {
        $fehler[] = "Bitte eine fünfstellige PLZ eingeben.";
    }

    // Passwort und Wiederholung
    if ($passwort === '' || $passwortWdh === '') {
        $fehler[] = "Passwort und Wiederholung dürfen nicht leer sein.";
    } elseif ($passwort !== $passwortWdh) {
        $fehler[] = "Passwort und Passwort-Wiederholung stimmen nicht überein.";
    }

    // Wenn keine Fehler -> Eintrag in DB
    if (count($fehler) === 0) {
        // Hier in echter Anwendung passwort_hash($passwort, PASSWORD_DEFAULT)
        $stmt = $pdo->prepare("INSERT INTO users
            (anrede, vorname, nachname, email, passwort, geburtsdatum, plz)
            VALUES (:anrede, :vorname, :nachname, :email, :passwort, :geburtsdatum, :plz)
        ");
        $stmt->execute([
            ':anrede'       => $anrede,
            ':vorname'      => $vorname,
            ':nachname'     => $nachname,
            ':email'        => $email,
            ':passwort'     => $passwort, // Achtung: in der Praxis gehasht speichern
            ':geburtsdatum' => $geburtsdatum,
            ':plz'          => $plz
        ]);

        $erfolg = true;
        // Um den User ggf. direkt einzuloggen:
        $_SESSION['angemeldet'] = true;
        $_SESSION['nutzer_email'] = $email;
        $_SESSION['spielgeld'] = 50000; // Startguthaben
    }
}

?>
<!DOCTYPE html>
<html lang="de">
<head>
  <meta charset="UTF-8">
  <title>Registrierung Börsenspiel</title>
  <style>
    form { max-width: 400px; margin: 2rem auto; }
    label { display: block; margin-top: 1rem; }
    input[type="text"], input[type="date"], input[type="email"], input[type="password"] {
      width: 100%; padding: 0.5rem;
    }
    .fehler {
      color: red;
      margin: 0.5rem 0;
    }
    .erfolg {
      color: green;
      margin: 0.5rem 0;
    }
    button {
      margin-top: 1rem; padding: 0.5rem 1rem;
    }
  </style>

  <!-- Clientseitige Validierung -->
  <script src="registrierung.js"></script>
</head>
<body>

<h1 style="text-align:center;">Registrierung Börsenspiel</h1>

<?php if (!$erfolg): ?>
  <?php 
    // Fehler ausgeben
    if (count($fehler) > 0) {
        echo '<div class="fehler"><ul>';
        foreach ($fehler as $f) {
            echo '<li>'.htmlspecialchars($f).'</li>';
        }
        echo '</ul></div>';
    }
  ?>

  <form id="regForm" method="post" action="registrierung.php" onsubmit="return validateForm();">
    <label>Anrede (Pflichtfeld)
      <input type="text" name="anrede" required />
    </label>
    <label>Vorname (Pflichtfeld)
      <input type="text" name="vorname" required />
    </label>
    <label>Nachname (Pflichtfeld)
      <input type="text" name="nachname" required />
    </label>
    <label>E-Mail (Pflichtfeld)
      <input type="email" name="email" required />
    </label>
    <label>E-Mail wiederholen (Pflichtfeld)
      <input type="email" name="emailWdh" required />
    </label>
    <label>Geburtsdatum (Pflichtfeld)
      <input type="date" name="geburtsdatum" required />
    </label>
    <label>PLZ (Pflichtfeld, 5 Ziffern)
      <input type="text" name="plz" required />
    </label>
    <label>Passwort (Pflichtfeld)
      <input type="password" name="passwort" required />
    </label>
    <label>Passwort wiederholen
      <input type="password" name="passwortWdh" required />
    </label>

    <button type="submit">Registrieren</button>
  </form>

  <p style="text-align:center;">
    <a href="index.html">Zurück zur Startseite</a>
  </p>
<?php else: ?>
  <div class="erfolg">
    <p>Registrierung erfolgreich!</p>
  </div>
  <p style="text-align:center;">
    <button onclick="window.location.href='boersenspiel.php'">Weiter zum Börsenspiel</button>
  </p>
<?php endif; ?>

</body>
</html>
