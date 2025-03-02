<?php
session_start();
// Prüfen, ob eingeloggt
if (!isset($_SESSION['angemeldet']) || $_SESSION['angemeldet'] !== true) {
    header('Location: login.php');
    exit;
}

// Falls wir die 10 Aktien wie in boerse.js definieren wollen, 
// legen wir sie ggf. in Session ab oder du hast sie fix. 
// Minimale Demo:
if (!isset($_SESSION['all_stocks'])) {
    $_SESSION['all_stocks'] = [
      ["name"=>"Mustermann AG",  "briefkurs"=>100, "geldkurs"=>99],
      ["name"=>"Beispiel AG",    "briefkurs"=>100, "geldkurs"=>99],
      ["name"=>"Test Inc.",      "briefkurs"=>100, "geldkurs"=>99],
      ["name"=>"MegaCorp",       "briefkurs"=>100, "geldkurs"=>99],
      ["name"=>"Future Ltd.",    "briefkurs"=>100, "geldkurs"=>99],
      ["name"=>"Sample GmbH",    "briefkurs"=>100, "geldkurs"=>99],
      ["name"=>"Hallo AG",       "briefkurs"=>100, "geldkurs"=>99],
      ["name"=>"World Ind.",     "briefkurs"=>100, "geldkurs"=>99],
      ["name"=>"Börsenspiel SE", "briefkurs"=>100, "geldkurs"=>99],
      ["name"=>"Fantasy PLC",    "briefkurs"=>100, "geldkurs"=>99]
    ];
}
$stocks = $_SESSION['all_stocks'];
?>
<!DOCTYPE html>
<html lang="de">
<head>
  <meta charset="UTF-8">
  <title>Aktienübersicht</title>
  <link rel="stylesheet" href="styles.css">
</head>
<body>
<header>
  <h1>Aktienübersicht</h1>
  <nav>
    <a href="index.php">Zur Startseite</a> |
    <a href="boersenspiel.php">Zum Börsenspiel</a>
  </nav>
</header>

<div class="cards-container">
  <?php foreach ($stocks as $st): ?>
    <div class="card">
      <h2><?= htmlspecialchars($st['name']) ?></h2>
      <p>Briefkurs: <?= number_format($st['briefkurs'],2,',','.') ?> €</p>
      <p>Geldkurs: <?= number_format($st['geldkurs'],2,',','.') ?> €</p>
      <!-- Link zur Detail-Seite etc. -> optional -->
    </div>
  <?php endforeach; ?>
</div>

</body>
</html>
