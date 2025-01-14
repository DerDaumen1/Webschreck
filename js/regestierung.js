/* registrierung.js */
document.getElementById('registration-form').addEventListener('submit', function(event) {
    event.preventDefault();
    const email = document.getElementById('email').value;
    const confirmEmail = document.getElementById('confirm-email').value;
    const geburtsdatum = new Date(document.getElementById('geburtsdatum').value);
    const plz = document.getElementById('plz').value;
    const errors = [];

    if (email !== confirmEmail) {
        errors.push('Die E-Mails stimmen nicht überein.');
    }

    const age = new Date().getFullYear() - geburtsdatum.getFullYear();
    if (age < 18) {
        errors.push('Sie müssen mindestens 18 Jahre alt sein.');
    }

    if (!/\d{5}/.test(plz)) {
          errors.push('Die Postleitzahl muss fünfstellig sein.');
    }

    if (errors.length > 0) {
        alert(errors.join('\n'));
    } else {
        document.getElementById('registration-form').style.display = 'none';
        document.getElementById('success-message').style.display = 'block';
    }
});