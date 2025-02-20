<?php
session_start();

$dbHost = 'localhost';
$dbUser = 'root';
$dbPass = '';
$dbName = 'webdatabase';

$pdo = new PDO("mysql:host=$dbHost;dbname=$dbName;charset=utf8", $dbUser, $dbPass);

$fehler = [];
$erfolg = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $anrede      = trim($_POST['anrede'] ?? '');
    $vorname     = trim($_POST['vorname'] ?? '');
    $nachname    = trim($_POST['nachname'] ?? '');
    $email       = trim($_POST['email'] ?? '');
    $emailWdh    = trim($_POST['emailWdh'] ?? '');
    $geburtsdatum= trim($_POST['geburtsdatum'] ?? '');
    $plz         = trim($_POST['plz'] ?? '');
    $passwort    = trim($_POST['passwort'] ?? '');
    $passwortWdh = trim($_POST['passwortWdh'] ?? '');

    if (count($fehler) === 0) {
        $stmt = $pdo->prepare("INSERT INTO users
            (anrede, vorname, nachname, email, passwort, geburtsdatum, plz, spielgeld, anzahl_aktien)
            VALUES (:anrede, :vorname, :nachname, :email, :passwort, :geburtsdatum, :plz, 50000, 0)
        ");
        $stmt->execute([
            ':anrede'       => $anrede,
            ':vorname'      => $vorname,
            ':nachname'     => $nachname,
            ':email'        => $email,
            ':passwort'     => $passwort,
            ':geburtsdatum' => $geburtsdatum,
            ':plz'          => $plz
        ]);

        $_SESSION['angemeldet'] = true;
        $_SESSION['nutzer_email'] = $email;
        $_SESSION['vorname'] = $vorname;
        $_SESSION['nachname'] = $nachname;
        $_SESSION['spielgeld'] = 50000;
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
  <link rel="stylesheet" href="styles.css">
  <script src="registrierung.js"></script>
</head>
<body>
<h1>Registrierung Börsenspiel</h1>

<?php if (!$erfolg): ?>
  <?php if (count($fehler) > 0): ?>
    <div class="fehler">
      <ul>
        <?php foreach ($fehler as $f): ?>
          <li><?= htmlspecialchars($f) ?></li>
        <?php endforeach; ?>
      </ul>
    </div>
  <?php endif; ?>

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
    <a href="index.php">Zurück zur Startseite</a>
  </p>
<?php else: ?>
  <div class="erfolg">
    <p>Registrierung erfolgreich!</p>
  </div>
  <p style="text-align:center;">
    <button onclick="window.location.href='index.php'">Weiter zur Startseite</button>
  </p>
<?php endif; ?>
</body>
</html>