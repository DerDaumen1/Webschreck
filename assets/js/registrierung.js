//
// registrierung.js
//
// Wir nutzen nun HTML5-Validierung plus setCustomValidity,
// sodass bei Fehlern (auch bei den E-Mail-Feldern) die Fehlermeldungen inline
// direkt am jeweiligen Feld angezeigt werden.
//

/**
 * Prüft, ob beide E-Mail-Felder übereinstimmen.
 * Falls nicht, wird beim zweiten Feld (emailWdh) eine Fehlermeldung gesetzt.
 */
function checkEmail() {
  const email = document.getElementById('email');
  const emailWdh = document.getElementById('emailWdh');

  if (!email.value || !emailWdh.value) {
    emailWdh.setCustomValidity("");
    return;
  }

  if (email.value !== emailWdh.value) {
    emailWdh.setCustomValidity("Die E-Mail-Adressen stimmen nicht überein.");
  } else {
    emailWdh.setCustomValidity("");
  }
}

/**
 * Prüft das Geburtsdatum und erzwingt ein Mindestalter von 18.
 */
function checkGeburtsdatum() {
  const dateInput = document.getElementById('geburtsdatum');
  const value = dateInput.value;

  if (!value) {
    dateInput.setCustomValidity("");
    return;
  }

  const birth = new Date(value + "T00:00:00");
  const today = new Date();
  let alter = today.getFullYear() - birth.getFullYear();
  let m = today.getMonth() - birth.getMonth();
  if (m < 0 || (m === 0 && today.getDate() < birth.getDate())) {
    alter--;
  }

  if (alter < 18) {
    dateInput.setCustomValidity("Sie müssen mindestens 18 Jahre alt sein.");
  } else {
    dateInput.setCustomValidity("");
  }
}

/**
 * Prüft die PLZ – zusätzlich zum pattern="\d{5}" – und setzt eine eigene Fehlermeldung.
 */
function checkPlz() {
  const plzInput = document.getElementById('plz');
  if (!/^\d{5}$/.test(plzInput.value)) {
    plzInput.setCustomValidity("Die PLZ muss genau 5 Ziffern haben.");
  } else {
    plzInput.setCustomValidity("");
  }
}

/**
 * Vergleicht Passwort und Passwort-Wiederholung und zeigt direkt am Feld eine Fehlermeldung, falls sie nicht übereinstimmen.
 */
function checkPasswort() {
  const pass1 = document.getElementById('passwort');
  const pass2 = document.getElementById('passwortWdh');

  if (!pass1.value || !pass2.value) {
    pass2.setCustomValidity("");
    return;
  }

  if (pass1.value !== pass2.value) {
    pass2.setCustomValidity("Die Passwörter stimmen nicht überein.");
  } else {
    pass2.setCustomValidity("");
  }
}
