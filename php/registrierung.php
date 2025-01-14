<!-- registrierung.php -->
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registrierung</title>
    <link rel="stylesheet" href="Sieper.css">
    <script src="../js/registrierung.js" defer></script>
</head>
<body>
    <header>
        <h1>Registrierung für das Börsenspiel</h1>
    </header>
    <main>
        <form id="registration-form">
            <label for="anrede">Anrede:</label>
            <select id="anrede" required>
                <option value="">Bitte wählen</option>
                <option value="Herr">Herr</option>
                <option value="Frau">Frau</option>
            </select>

            <label for="vorname">Vorname:</label>
            <input type="text" id="vorname" required>

            <label for="nachname">Nachname:</label>
            <input type="text" id="nachname" required>

            <label for="email">E-Mail:</label>
            <input type="email" id="email" required>

            <label for="confirm-email">E-Mail wiederholen:</label>
            <input type="email" id="confirm-email" required>

            <label for="geburtsdatum">Geburtsdatum:</label>
            <input type="date" id="geburtsdatum" required>

            <label for="plz">Postleitzahl:</label>
            <input type="text" id="plz" pattern="\d{5}" required>

            <button type="submit">Registrieren</button>
        </form>
        <div id="success-message" style="display: none;">
            <p>Registrierung erfolgreich! <a href="boersenspiel.html">Zum Börsenspiel</a></p>
        </div>
    </main>
</body>
</html>