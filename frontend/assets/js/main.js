// main.js

// Shared demo transactions
const demoTx = [
  {id: 1, sender: 'Alice', receiver: 'Me', amount: 50.00, category: 'Salary', date: '2025-09-01'},
  {id: 2, sender: 'Me', receiver: 'Bob', amount: 12.50, category: 'Food', date: '2025-09-02'},
  {id: 3, sender: 'Me', receiver: 'Charlie', amount: 7.20, category: 'Coffee', date: '2025-09-03'}
];

// Function to calculate totals
function calculateTotals() {
  let income = 0;
  let expenses = 0;

  demoTx.forEach(tx => {
    if (tx.receiver === 'Me') {
      income += tx.amount;
    } 
    if (tx.sender === 'Me') {
      expenses += tx.amount;
    }
  });

  const balance = income - expenses;

  // Update dashboard DOM
  const balanceEl = document.getElementById('balance');
  const incomeEl = document.getElementById('income');
  const expensesEl = document.getElementById('expenses');

  if (balanceEl) balanceEl.textContent = balance.toFixed(2);
  if (incomeEl) incomeEl.textContent = income.toFixed(2);
  if (expensesEl) expensesEl.textContent = expenses.toFixed(2);
}

// Call totals when dashboard is loaded
function renderDashboard() {
  calculateTotals();
  // later we’ll add a chart here
}

// Run dashboard update when loaded
window.addEventListener('hashchange', () => {
  if (location.hash === '#dashboard') renderDashboard();
});
window.addEventListener('load', () => {
  if (location.hash === '#dashboard') renderDashboard();
});
