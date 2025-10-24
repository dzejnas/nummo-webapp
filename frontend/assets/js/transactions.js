// =========================================================
// NUMMO APP — Transactions View
// Renders a responsive table of all transactions
// =========================================================

// Temporary demo transactions (to be replaced with API data)
const demoTx = [
  { id: 1, sender: 'Alice', receiver: 'Bob', amount: 12.50, category: 'Food', date: '2025-09-01' },
  { id: 2, sender: 'Me', receiver: 'Charlie', amount: 7.20, category: 'Coffee', date: '2025-09-02' },
  { id: 3, sender: 'David', receiver: 'Me', amount: 25.00, category: 'Salary', date: '2025-09-03' }
];

// =========================================================
// Entry point for the transactions view
// =========================================================
export function renderTransactions() {
  const root = document.getElementById('transactions-list');
  if (!root) {
    console.warn('⚠️ Missing #transactions-list container.');
    return;
  }

  // Add fade animation for SPA transition
  root.classList.add('fade-in');

  if (demoTx.length === 0) {
    root.innerHTML = `
      <div class="alert alert-light text-center mt-4" role="alert">
        No transactions found yet.
      </div>
    `;
    return;
  }

  // Build a Bootstrap table
  const table = document.createElement('table');
  table.className = 'table table-striped align-middle';
  table.innerHTML = `
    <thead class="table-primary">
      <tr>
        <th scope="col">#</th>
        <th scope="col">From</th>
        <th scope="col">To</th>
        <th scope="col">Amount</th>
        <th scope="col">Category</th>
        <th scope="col">Date</th>
      </tr>
    </thead>
  `;

  const tbody = document.createElement('tbody');

  demoTx.forEach(tx => {
    const row = document.createElement('tr');
    row.innerHTML = `
      <td>${tx.id}</td>
      <td>${tx.sender}</td>
      <td>${tx.receiver}</td>
      <td class="${tx.sender === 'Me' ? 'text-danger' : 'text-success'} fw-semibold">
        ${tx.sender === 'Me' ? '-' : '+'}$${tx.amount.toFixed(2)}
      </td>
      <td>${tx.category}</td>
      <td>${new Date(tx.date).toLocaleDateString()}</td>
    `;
    tbody.appendChild(row);
  });

  table.appendChild(tbody);
  root.innerHTML = '';
  root.appendChild(table);

  // Add footer summary
  renderSummary(root);
}

// =========================================================
// Helper: show totals at bottom
// =========================================================
function renderSummary(root) {
  const income = demoTx.filter(t => t.receiver === 'Me')
                       .reduce((sum, t) => sum + t.amount, 0);
  const expenses = demoTx.filter(t => t.sender === 'Me')
                         .reduce((sum, t) => sum + t.amount, 0);

  const summary = document.createElement('div');
  summary.className = 'mt-3 text-end';
  summary.innerHTML = `
    <p class="mb-1"><strong>Total Income:</strong> <span class="text-success">$${income.toFixed(2)}</span></p>
    <p class="mb-1"><strong>Total Spent:</strong> <span class="text-danger">$${expenses.toFixed(2)}</span></p>
    <hr class="my-2">
    <p><strong>Net Balance:</strong> <span class="${income - expenses >= 0 ? 'text-success' : 'text-danger'}">
      $${(income - expenses).toFixed(2)}</span></p>
  `;
  root.appendChild(summary);
}
