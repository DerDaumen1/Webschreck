<?php
// login.php
session_start();

$fehler = '';
$dbHost = 'localhost';
$dbUser = 'root';
$dbPass = '';
$dbName = 'webdatabase';

$pdo = new PDO("mysql:host=$dbHost;dbname=$dbName;charset=utf8", $dbUser, $dbPass);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $passwort = trim($_POST['passwort'] ?? '');

    // Nutzer in DB suchen
    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ? AND passwort = ?");
    $stmt->execute([$email, $passwort]);
    $user = $stmt->fetch();

    if ($user) {
        $_SESSION['angemeldet'] = true;
        $_SESSION['nutzer_email'] = $email;
        $_SESSION['spielgeld'] = $user['spielgeld'] ?? 50000; // Fallback
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
    form { max-width: 400px; margin: 2rem auto; }
    label { display: block; margin-top: 1rem; }
    input[type="email"], input[type="password"] {
      width: 100%; padding: 0.5rem;
    }
    .fehler { color: red; }
  </style>
</head>
<body>
  <h1 style="text-align:center;">Anmeldung</h1>

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
    <a href="index.html">Zur Startseite</a> |
    <a href="registrierung.php">Noch kein Konto? Registrieren</a>
  </p>
</body>
</html>