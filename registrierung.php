<?php
// registrierung.php
session_start();
$dbHost = 'localhost';
$dbUser = 'root';
$dbPass = '';
$dbName = 'webdatabase';

$pdo = new PDO("mysql:host=$dbHost;dbname=$dbName;charset=utf8", $dbUser, $dbPass);
$fehler = [];
$erfolg = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
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
            ':geburtsdatum' => $geburtsdatum,
            ':plz' => $plz
        ]);

        $erfolg = true;
        // Um den User ggf. direkt einzuloggen:
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
</body>
</html>
