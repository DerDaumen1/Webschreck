<!-- boersenspiel.html -->
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Börsenspiel</title>
    <link rel="stylesheet" href="Sieper.css">
    <script src="boerse.js" defer></script>
</head>
<body>
    <header>
        <h1>Börsenspiel</h1>
    </header>
    <main>
        <canvas id="chart" width="600" height="400"></canvas>
        <div id="controls">
            <button id="buy">Kaufen</button>
            <button id="sell">Verkaufen</button>
            <button id="end-game">Spiel Beenden</button>
        </div>
        <div id="status">
            <p>Spielgeld: <span id="spielgeld">50000</span>€</p>
            <p>Depotbestand: <span id="depotbestand">0</span> Aktien</p>
            <p>Gewinn/Verlust: <span id="gewinn-verlust">0</span>€</p>
        </div>
    </main>
</body>
</html>