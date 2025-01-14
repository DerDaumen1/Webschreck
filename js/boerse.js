/* boerse.js */
const canvas = document.getElementById('chart');
const ctx = canvas.getContext('2d');
let currentPrice = 100;
const prices = [currentPrice];

function updateChart() {
    currentPrice += (Math.random() - 0.5) * 0.1;
    prices.push(currentPrice);

    if (prices.length > canvas.width / 10) {
        prices.shift();
    }

    ctx.clearRect(0, 0, canvas.width, canvas.height);
    ctx.beginPath();
    ctx.moveTo(0, canvas.height - prices[0]);

    prices.forEach((price, index) => {
        ctx.lineTo(index * 10, canvas.height - price);
    });

    ctx.stroke();
}

setInterval(updateChart, 1000);
