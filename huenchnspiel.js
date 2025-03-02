let currentBet = 0;
let currentStep = 0;
let maxSteps = 5;
let gameActive = false;

async function startGame() {
  if(gameActive) return;
  
  const betAmount = parseInt(document.getElementById('betAmount').value);
  if(betAmount < 1 || betAmount > 1000) {
    alert('Ungültiger Einsatz! (1-1000 €)');
    return;
  }

  // Einsatz vom Konto abziehen
  const response = await fetch('trade.php', {
    method: 'POST',
    body: new URLSearchParams({
      typ: 'huhn_bet',
      bet: betAmount
    })
  });
  
  const data = await response.json();
  if(!data.success) {
    alert(data.message);
    return;
  }

  document.getElementById('currentMoney').textContent = data.spielgeld;
  currentBet = betAmount;
  gameActive = true;
  document.getElementById('startBtn').disabled = true;
  document.getElementById('nextBtn').disabled = false;
  document.getElementById('cashOutBtn').disabled = false;
  
  // Straße mit Schritten zeichnen
  const stepsDiv = document.getElementById('steps');
  stepsDiv.innerHTML = '';
  const roadWidth = document.getElementById('road').offsetWidth - 70;
  for(let i = 0; i < maxSteps; i++) {
    const step = document.createElement('div');
    step.className = 'step';
    step.style.left = `${10 + (roadWidth/maxSteps)*i}px`;
    stepsDiv.appendChild(step);
  }

  currentStep = 0;
  updateChickenPosition();
}

function nextStep() {
  if(!gameActive) return;
  
  // 50% Chance zu scheitern
  if(Math.random() < 0.5) {
    endGame(false);
    return;
  }

  currentStep++;
  updateChickenPosition();

  if(currentStep >= maxSteps) {
    endGame(true);
  }
}

function cashOut() {
  endGame(true);
}

function endGame(success) {
  gameActive = false;
  document.getElementById('nextBtn').disabled = true;
  document.getElementById('cashOutBtn').disabled = true;
  
  let winAmount = 0;
  if(success) {
    winAmount = currentBet * Math.pow(2, currentStep);
    updateBalance(winAmount);
  }

  alert(success ? 
    `Gewonnen! Ausgezahlt: ${winAmount} €` : 
    'Verloren! Das Huhn wurde überfahren.');
  
  resetGame();
}

function updateBalance(amount) {
  fetch('trade.php', {
    method: 'POST',
    body: new URLSearchParams({
      typ: 'huhn_win',
      amount: amount
    })
  })
  .then(res => res.json())
  .then(data => {
    document.getElementById('currentMoney').textContent = data.spielgeld;
  });
}

function updateChickenPosition() {
  const chicken = document.getElementById('chicken');
  const roadWidth = document.getElementById('road').offsetWidth - 70;
  const stepSize = roadWidth / maxSteps;
  chicken.style.left = `${10 + (stepSize * currentStep)}px`;
  document.getElementById('currentWin').textContent = 
    `${currentBet * Math.pow(2, currentStep)} €`;
}

function resetGame() {
  currentBet = 0;
  currentStep = 0;
  document.getElementById('currentWin').textContent = '0 €';
  document.getElementById('chicken').style.left = '10px';
  document.getElementById('startBtn').disabled = false;
}