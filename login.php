<?php
session_start();

$fehler = '';
$dbHost = 'localhost';
$dbUser = 'root';
$dbPass = '';
$dbName = 'webdatabase';

try {
    $pdo = new PDO("mysql:host=$dbHost;dbname=$dbName;charset=utf8", $dbUser, $dbPass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Datenbankverbindung fehlgeschlagen: " . $e->getMessage());
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $email    = trim($_POST['email'] ?? '');
  $passwort = trim($_POST['passwort'] ?? '');
  $stmt = $pdo->prepare("SELECT id, email, passwort, spielgeld, anzahl_aktien, vorname, nachname FROM users WHERE email = ? AND passwort = ?");
  $stmt->execute([$email, $passwort]);
  $user = $stmt->fetch(PDO::FETCH_ASSOC);
  if ($user) {
      $_SESSION['angemeldet']   = true;
      $_SESSION['nutzer_email'] = $user['email'];
      $_SESSION['vorname']      = !empty($user['vorname']) ? $user['vorname'] : "Spieler";
      $_SESSION['nachname']     = !empty($user['nachname']) ? $user['nachname'] : "";
      $_SESSION['user_id']      = $user['id'];
      $_SESSION['spielgeld']    = $user['spielgeld'];
      $_SESSION['anzahl_aktien'] = $user['anzahl_aktien'];
      header('Location: index.php');
      exit;
  } else {
      $fehler = "Anmeldung fehlgeschlagen! E-Mail oder Passwort falsch.";
    }
}
?>
<!DOCTYPE html>
<html lang="de">
<head>
  <meta charset="UTF-8">
  <title>Anmeldung</title>
  <link rel="stylesheet" href="styles.css">
</head>
<body>
<h1>Anmeldung</h1>

<?php if ($fehler): ?>
  <div class="fehler"><?= htmlspecialchars($fehler) ?></div>
<?php endif; ?>

<form method="post">
  <label>E-Mail:
    <input type="email" name="email" required>
  </label>
  <label>Passwort:
    <input type="password" name="passwort" required>
  </label>
  <button type="submit">Anmelden</button>
</form>

<p style="text-align:center;">
  <a href="index.php">Zur Startseite</a> |
  <a href="registrierung.php">Noch kein Konto? Registrieren</a>
</p>
</body>
</html>