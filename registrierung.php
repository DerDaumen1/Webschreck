<?php
// registrierung.php
session_start();
<<<<<<< Updated upstream

// Datenbank-Verbindungsparameter anpassen:
=======
>>>>>>> Stashed changes
$dbHost = 'localhost';
$dbUser = 'root';
$dbPass = '';
$dbName = 'webdatabase';

$pdo = new PDO("mysql:host=$dbHost;dbname=$dbName;charset=utf8", $dbUser, $dbPass);
<<<<<<< Updated upstream

// POST-Processing:
=======
>>>>>>> Stashed changes
$fehler = [];
$erfolg = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
<<<<<<< Updated upstream
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
=======
    $anrede = trim($_POST['anrede'] ?? '');
    $vorname = trim($_POST['vorname'] ?? '');
    $nachname = trim($_POST['nachname'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $emailWdh = trim($_POST['emailWdh'] ?? '');
    $geburtsdatum = trim($_POST['geburtsdatum'] ?? '');
    $plz = trim($_POST['plz'] ?? '');
    $passwort = trim($_POST['passwort'] ?? '');
    $passwortWdh = trim($_POST['passwortWdh'] ?? '');

    if ($email !== $emailWdh) {
        $fehler[] = "Die E-Mail-Adressen stimmen nicht überein.";
    }
    if ($passwort !== $passwortWdh) {
        $fehler[] = "Die Passwörter stimmen nicht überein.";
    }

    if (count($fehler) === 0) {
        $stmt = $pdo->prepare("INSERT INTO users (anrede, vorname, nachname, email, passwort, geburtsdatum, plz, spielgeld, anzahl_aktien) VALUES (:anrede, :vorname, :nachname, :email, :passwort, :geburtsdatum, :plz, 50000, 0)");
        $stmt->execute([
            ':anrede' => $anrede,
            ':vorname' => $vorname,
            ':nachname' => $nachname,
            ':email' => $email,
            ':passwort' => password_hash($passwort, PASSWORD_DEFAULT),
>>>>>>> Stashed changes
            ':geburtsdatum' => $geburtsdatum,
            ':plz' => $plz
        ]);

        $erfolg = true;
        // Um den User ggf. direkt einzuloggen:
        $_SESSION['angemeldet'] = true;
        $_SESSION['nutzer_email'] = $email;
<<<<<<< Updated upstream
        $_SESSION['spielgeld'] = 50000; // Startguthaben
=======
        $_SESSION['vorname'] = $vorname;
        $_SESSION['nachname'] = $nachname;
        $_SESSION['spielgeld'] = 50000;
        $_SESSION['anzahl_aktien'] = 0;
        header('Location: index.php');
        exit;
>>>>>>> Stashed changes
    }
}

?>
<!DOCTYPE html>
<html lang="de">
<head>
  <meta charset="UTF-8">
  <title>Registrierung Börsenspiel</title>
<<<<<<< Updated upstream
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

=======
  <link rel="stylesheet" href="styles.css">
  <style>
    form {
      display: flex;
      flex-direction: column;
    }
    label {
      margin-bottom: 10px;
    }
    input {
      width: 100%;
      padding: 8px;
      margin-top: 5px;
      border: 1px solid #ccc;
      border-radius: 4px;
    }
    button {
      margin-top: 15px;
      padding: 10px;
      background-color: #0d6efd;
      color: white;
      border: none;
      border-radius: 4px;
      cursor: pointer;
    }
    button:hover {
      background-color: #0b5ed7;
    }
  </style>
</head>
<body>
<div class="container">
  <div class="card">
    <h1>Registrierung Börsenspiel</h1>
    <?php if (!$erfolg): ?>
      <?php if (!empty($fehler)): ?>
        <div class="fehler">
          <ul>
            <?php foreach ($fehler as $f): ?>
              <li><?= htmlspecialchars($f) ?></li>
            <?php endforeach; ?>
          </ul>
        </div>
      <?php endif; ?>
    <form id="regForm" method="post" action="registrierung.php">
      <label>Anrede:<input type="text" name="anrede" required /></label>
      <label>Vorname:<input type="text" name="vorname" required /></label>
      <label>Nachname:<input type="text" name="nachname" required /></label>
      <label>E-Mail:<input type="email" name="email" required /></label>
      <label>E-Mail wiederholen:<input type="email" name="emailWdh" required /></label>
      <label>Geburtsdatum:<input type="date" name="geburtsdatum" required /></label>
      <label>PLZ:<input type="text" name="plz" required /></label>
      <label>Passwort:<input type="password" name="passwort" required /></label>
      <label>Passwort wiederholen:<input type="password" name="passwortWdh" required /></label>
      <button type="submit">Registrieren</button>
    </form>
    <p><a href="index.php">Zurück zur Startseite</a></p>
    <?php endif; ?>
  </div>
</div>
>>>>>>> Stashed changes
</body>
</html>
