// Hühnerspiel - überarbeitet mit neuer Grafik und Mechanik

// Laden des Huhn-Bildes
const chickenImg = new Image();
chickenImg.src = "A_cartoon-style_chicken.png"; // Stelle sicher, dass das Bild im selben Ordner liegt wie die Datei!

// Spielfeld-Setup
const canvas = document.getElementById('gameCanvas');
const ctx = canvas.getContext('2d');

let chicken = {
    x: 50,
    y: canvas.height / 2,
    width: 50,
    height: 50,
    speed: 5,
    isHit: false
};

// Auto-Setup
let car = {
    x: canvas.width,
    y: canvas.height / 2,
    width: 70,
    height: 40,
    speed: 7
};

// Nachrichtenelement für Status-Anzeige
const messageDiv = document.createElement('div');
messageDiv.id = "message";
messageDiv.style.position = "absolute";
messageDiv.style.top = "10px";
messageDiv.style.left = "50%";
messageDiv.style.transform = "translateX(-50%)";
messageDiv.style.fontSize = "24px";
messageDiv.style.fontWeight = "bold";
messageDiv.style.color = "red";
document.body.appendChild(messageDiv);

// Spiellogik
function update() {
    if (!chicken.isHit) {
        chicken.x += chicken.speed;
        car.x -= car.speed;
    }

    // Kollisionserkennung
    if (chicken.x + chicken.width > car.x &&
        chicken.x < car.x + car.width &&
        chicken.y + chicken.height > car.y &&
        chicken.y < car.y + car.height) {
        
        chicken.isHit = true;
        messageDiv.innerText = "Oops! Das Huhn ist erschrocken!";
        chickenImg.src = "A_cartoon-style_chicken.png";  // Bild für "erschrockenes" Huhn setzen
    }

    // Reset Auto
    if (car.x + car.width < 0) {
        car.x = canvas.width;
    }

    draw();
}

// Zeichnen des Spiels
function draw() {
    ctx.clearRect(0, 0, canvas.width, canvas.height);

    // Auto zeichnen (einfacher Platzhalter)
    ctx.fillStyle = "blue";
    ctx.fillRect(car.x, car.y, car.width, car.height);

    // Huhn zeichnen
    ctx.drawImage(chickenImg, chicken.x, chicken.y, chicken.width, chicken.height);
}

// Spiel-Loop
setInterval(update, 1000 / 60);