<?php
// login.php
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

    // Nutzer in DB suchen
    // Hier vergleichen wir noch klartext-passwort (unsicher!):
    $stmt = $pdo->prepare("
        SELECT id, email, passwort, spielgeld, anzahl_aktien 
        FROM users 
        WHERE email = ? 
          AND passwort = ?
    ");
    $stmt->execute([$email, $passwort]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user) {
        // Falls Email & Passwort stimmen
        $_SESSION['angemeldet']   = true;
        $_SESSION['nutzer_email'] = $user['email'];

        // Damit trade.php die passende Zeile updaten kann:
        $_SESSION['user_id']      = $user['id'];

        // Spielgeld/Aktien aus DB übernehmen
        $_SESSION['spielgeld']    = isset($user['spielgeld']) 
                                    ? $user['spielgeld'] 
                                    : 50000;
        $_SESSION['anzahl_aktien'] = isset($user['anzahl_aktien']) 
                                     ? $user['anzahl_aktien'] 
                                     : 0;

        // Weiterleitung zur Spielseite
        header('Location: boersenspiel.php');
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
  <style>
    form {
      max-width: 400px; 
      margin: 2rem auto;
    }
    label {
      display: block; 
      margin-top: 1rem;
    }
    input[type="email"], 
    input[type="password"] {
      width: 100%; 
      padding: 0.5rem;
    }
    .fehler { 
      color: red; 
      text-align: center;
    }
    h1 {
      text-align: center;
    }
  </style>
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
