// Page-specific setup code. The router calls Views[pageName]() after a view is loaded.
// Most pages still show static demo data; they are connected to the REST API in Milestone 4.
// The currency converter already uses the backend's exchange rate endpoint (external API).

const Views = {
  dashboard() {
    const canvas = document.getElementById('spending-chart');
    if (!canvas || typeof Chart === 'undefined') return;

    new Chart(canvas, {
      type: 'doughnut',
      data: {
        labels: ['Food', 'Transport', 'Shopping', 'Bills', 'Entertainment'],
        datasets: [{
          data: [12.5, 1.8, 35.9, 40, 20],
          backgroundColor: ['#0d6efd', '#20c997', '#ffc107', '#dc3545', '#6f42c1'],
        }],
      },
      options: { maintainAspectRatio: false, plugins: { legend: { position: 'bottom' } } },
    });

    setupCurrencyConverter();
  },
};

// Dashboard card: converts an amount with live rates from GET /api/rates/convert
function setupCurrencyConverter() {
  const form = document.getElementById('converter-form');
  const result = document.getElementById('converter-result');
  const date = document.getElementById('converter-date');

  form.addEventListener('submit', async (event) => {
    event.preventDefault();
    const params = new URLSearchParams({
      amount: document.getElementById('converter-amount').value,
      from: document.getElementById('converter-from').value,
      to: document.getElementById('converter-to').value,
    });

    result.textContent = 'Converting…';
    date.textContent = '';

    try {
      const response = await fetch(`${API_BASE_URL}/rates/convert?${params}`);
      const data = await response.json();
      if (!response.ok) {
        throw new Error(data.error || 'Conversion failed');
      }
      result.textContent = `${data.amount.toFixed(2)} ${data.from} = ${data.result.toFixed(2)} ${data.to}`;
      date.textContent = `1 ${data.from} = ${data.rate} ${data.to} · rate date ${data.date}`;
    } catch (error) {
      // Covers both API errors (e.g. 503 when the rate service is down) and network errors
      result.textContent = error.message === 'Failed to fetch' ? 'The server is not reachable.' : error.message;
    }
  });

  form.requestSubmit(); // show a first conversion right away
}
