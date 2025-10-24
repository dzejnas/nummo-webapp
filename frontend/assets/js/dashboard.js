// =========================================================
// NUMMO APP — Dashboard Logic
// Handles balance calculations, income/expense summary,
// and expense-by-category chart rendering.
// =========================================================

// Mock demo transactions (replace later with backend data)
const demoTx = [
  { id: 1, sender: 'Alice', receiver: 'Me', amount: 120.50, category: 'Salary', date: '2025-09-01' },
  { id: 2, sender: 'Me', receiver: 'Bob', amount: 50.00, category: 'Food', date: '2025-09-02' },
  { id: 3, sender: 'Me', receiver: 'Charlie', amount: 30.25, category: 'Coffee', date: '2025-09-03' },
  { id: 4, sender: 'David', receiver: 'Me', amount: 200.00, category: 'Freelance', date: '2025-09-05' }
];

// =========================================================
// Main entry point
// =========================================================
export function initDashboard() {
  // Add fade effect on load
  const app = document.getElementById('app');
  if (app) app.classList.add('fade-in');

  updateDashboard();
  renderChart();
}

// =========================================================
// Calculate income, expenses, and balance
// =========================================================
function updateDashboard() {
  let income = 0;
  let expenses = 0;

  demoTx.forEach(t => {
    if (t.receiver === 'Me') income += t.amount;
    if (t.sender === 'Me') expenses += t.amount;
  });

  const balance = income - expenses;

  const balanceEl = document.getElementById('balance');
  const incomeEl = document.getElementById('income');
  const expensesEl = document.getElementById('expenses');

  if (balanceEl) balanceEl.textContent = `$${balance.toFixed(2)}`;
  if (incomeEl) incomeEl.textContent = `$${income.toFixed(2)}`;
  if (expensesEl) expensesEl.textContent = `$${expenses.toFixed(2)}`;
}

// =========================================================
// Render expense distribution chart by category
// =========================================================
function renderChart() {
  const canvas = document.getElementById('transactionsChart');
  if (!canvas) return;

  const ctx = canvas.getContext('2d');

  // Aggregate expenses per category
  const categories = {};
  demoTx.forEach(t => {
    if (t.sender === 'Me') {
      categories[t.category] = (categories[t.category] || 0) + t.amount;
    }
  });

  // Clean up previous chart if reloaded
  if (window._nummoChartInstance) {
    window._nummoChartInstance.destroy();
  }

  window._nummoChartInstance = new Chart(ctx, {
    type: 'pie',
    data: {
      labels: Object.keys(categories),
      datasets: [{
        data: Object.values(categories),
        backgroundColor: ['#36A2EB', '#FF6384', '#FFCE56', '#4BC0C0'],
        borderWidth: 1,
      }]
    },
    options: {
      responsive: true,
      plugins: {
        legend: {
          position: 'bottom',
          labels: {
            color: getComputedStyle(document.body).color
          }
        },
        title: {
          display: true,
          text: 'Expenses by Category',
          color: getComputedStyle(document.body).color
        }
      }
    }
  });
}
