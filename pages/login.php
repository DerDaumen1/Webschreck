<?php
session_start();

$fehler = '';
require_once '../includes/db.php'; // Pfad anpassen

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = trim($_POST['email'] ?? '');
    $passwort = trim($_POST['passwort'] ?? '');

    // Nutzer anhand der E-Mail suchen
    $stmt = $pdo->prepare("SELECT id, email, vorname, nachname, passwort, spielgeld, anzahl_aktien FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    // Passwort prüfen
    if ($user && password_verify($passwort, $user['passwort'])) {
        $_SESSION['angemeldet']   = true;
        $_SESSION['nutzer_email'] = $user['email'];
        $_SESSION['user_id']      = $user['id'];
        $_SESSION['spielgeld']    = $user['spielgeld'] ?? 50000;
        $_SESSION['anzahl_aktien'] = $user['anzahl_aktien'] ?? 0;
        $_SESSION['vorname']      = $user['vorname'];
        $_SESSION['nachname']     = $user['nachname'];

        header('Location: ../index.php');
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
  <link rel="stylesheet" href="../assets/css/registrierung.css">
</head>
<body>
  <div class="container">
    <div class="card">
      <h1>Anmeldung</h1>

      <?php if ($fehler): ?>
        <div class="fehler"><?= htmlspecialchars($fehler) ?></div>
      <?php endif; ?>

      <form method="post">
        <div class="form-group">
          <label for="email">E-Mail</label>
          <input type="email" id="email" name="email" required placeholder="beispiel@domain.de">
        </div>
        <div class="form-group">
          <label for="passwort">Passwort</label>
          <input type="password" id="passwort" name="passwort" required placeholder="••••••">
        </div>
        <button type="submit">Anmelden</button>
      </form>

      <p class="back-link">
        <a href="../index.php">Zurück zur Startseite</a> |
        <a href="registrierung.php">Noch kein Konto? Registrieren</a>
      </p>
    </div>
  </div>
</body>
</html>
