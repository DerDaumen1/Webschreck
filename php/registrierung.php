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
    <nav class="navbar navbar-expand-lg navbar-light bg-light">
        <div class="container-fluid">
            <a class="navbar-brand" href="../index.php">Privatbank Mustermann</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav">
                    <li class="nav-item">
                        <a class="nav-link active" aria-current="page" href="../index.php">Startseite</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#">Kredite</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#">Konto</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#">Aktien</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#">Service</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <header class="bg-light text-center py-5">
        <h1>Registrierung für das Börsenspiel</h1>
    </header>
    <main class="container mt-5">
        <form id="registration-form" class="shadow p-4 rounded bg-white">
            <h2 class="text-center mb-4">Jetzt registrieren</h2>

            <div class="mb-3">
                <label for="anrede" class="form-label">Anrede:</label>
                <select id="anrede" class="form-select" required>
                    <option value="">Bitte wählen</option>
                    <option value="Herr">Herr</option>
                    <option value="Frau">Frau</option>
                </select>
            </div>

            <div class="mb-3">
                <label for="vorname" class="form-label">Vorname:</label>
                <input type="text" id="vorname" class="form-control" required>
            </div>

            <div class="mb-3">
                <label for="nachname" class="form-label">Nachname:</label>
                <input type="text" id="nachname" class="form-control" required>
            </div>

            <div class="mb-3">
                <label for="email" class="form-label">E-Mail:</label>
                <input type="email" id="email" class="form-control" required>
            </div>

            <div class="mb-3">
                <label for="confirm-email" class="form-label">E-Mail wiederholen:</label>
                <input type="email" id="confirm-email" class="form-control" required>
            </div>

            <div class="mb-3">
                <label for="geburtsdatum" class="form-label">Geburtsdatum:</label>
                <input type="date" id="geburtsdatum" class="form-control" required>
            </div>

            <div class="mb-3">
                <label for="plz" class="form-label">Postleitzahl:</label>
                <input type="text" id="plz" class="form-control" pattern="\d{5}" required>
            </div>

            <button type="submit" class="btn btn-primary w-100">Registrieren</button>
        </form>

        <div id="success-message" class="alert alert-success mt-4 text-center" style="display: none;">
            <p>Registrierung erfolgreich! <a href="boersenspiel.html">Zum Börsenspiel</a></p>
        </div>
    </main>

    <footer class="bg-dark text-white text-center py-3 mt-5">
        <p>&copy; Privatbank Mustermann 2025. Alle Rechte vorbehalten.</p>
    </footer>
</body>
</html>