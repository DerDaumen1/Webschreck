// registrierung.js

function validateForm() {
  // Greife auf das Formular zu
  const form = document.getElementById('regForm');
  if (!form) {
    return true;  // Kein Formular = kein Check
  }

  // Felder abrufen
  const email       = form.elements['email'].value.trim();
  const emailWdh    = form.elements['emailWdh'].value.trim();
  const geburtsdatum= form.elements['geburtsdatum'].value.trim();
  const plz         = form.elements['plz'].value.trim();
  const passwort    = form.elements['passwort'].value;
  const passwortWdh = form.elements['passwortWdh'].value;

  let errors = [];

  // 1) E-Mail-Abgleich
  if (email !== emailWdh) {
    errors.push("E-Mail-Felder stimmen nicht überein.");
  }

  // 2) Geburtstag: mind. 18 Jahre
  if (geburtsdatum) {
    let birth = new Date(geburtsdatum + "T00:00:00"); // UTC-Korrektur
    let today = new Date();
    let alter = today.getFullYear() - birth.getFullYear();
    let m     = today.getMonth() - birth.getMonth();

    if (m < 0 || (m === 0 && today.getDate() < birth.getDate())) {
      alter--;
    }
    if (alter < 18) {
      errors.push("Sie müssen mindestens 18 Jahre alt sein.");
    }
  } else {
    errors.push("Bitte ein gültiges Geburtsdatum eingeben.");
  }

  // 3) PLZ: genau 5 Ziffern
  if (!/^\d{5}$/.test(plz)) {
    errors.push("PLZ muss genau 5 Ziffern lang sein (nur Zahlen).");
  }

  // 4) Passwort: Wiederholung gleich?
  if (passwort !== passwortWdh) {
    errors.push("Passwort und Wiederholung stimmen nicht überein.");
  }

  // Fehler ausgeben
  if (errors.length > 0) {
    alert(errors.join("\n"));
    return false;  // Formular-Abbruch
  }

  return true; // Keine Fehler -> Formular darf gesendet werden
}
