// registrierung.js

function validateForm() {
    // Greife auf das Formular zu
    const form = document.getElementById('regForm');
    if (!form) return true; // Falls kein Formular gefunden wird
    
    // Felder abrufen
    const email     = form.elements['email'].value.trim();
    const emailWdh  = form.elements['emailWdh'].value.trim();
    const geburtsdatum = form.elements['geburtsdatum'].value.trim();
    const plz       = form.elements['plz'].value.trim();
    const passwort  = form.elements['passwort'].value;
    const passwortWdh = form.elements['passwortWdh'].value;
    
    // Fehler-Array
    let errors = [];
    
    // 1) E-Mail prüfen
    if (email !== emailWdh) {
      errors.push("E-Mail-Felder stimmen nicht überein (clientseitig).");
    }
    
    // 2) Geburtstagscheck (mind. 18 Jahre)
    if (geburtsdatum) {
      let birth = new Date(geburtsdatum);
      let today = new Date();
      let alter = today.getFullYear() - birth.getFullYear();
      let m = today.getMonth() - birth.getMonth();
      if (m < 0 || (m === 0 && today.getDate() < birth.getDate())) {
        alter--;
      }
      if (alter < 18) {
        errors.push("Sie müssen mindestens 18 Jahre alt sein (clientseitig).");
      }
    }
    
    // 3) PLZ: fünf Ziffern
    if (!/^\d{5}$/.test(plz)) {
      errors.push("Bitte eine fünfstellige PLZ eingeben (clientseitig).");
    }
    
    // 4) Passwort
    if (passwort !== passwortWdh) {
      errors.push("Passwort und Passwort-Wiederholung stimmen nicht überein (clientseitig).");
    }
    
    // Wenn Fehler vorhanden, alert ausgeben
    if (errors.length > 0) {
      alert(errors.join("\n"));
      return false; // verhindert Submit
    }
    
    return true; // keine Fehler -> Formular darf abgehen
  }
  