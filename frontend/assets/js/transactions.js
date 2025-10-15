// Demo transactions (can also be shared with dashboard)
const demoTx = [
    {id: 1, sender: 'Alice', receiver: 'Bob', amount: 12.50, category: 'Food', date: '2025-09-01'},
    {id: 2, sender: 'Me', receiver: 'Charlie', amount: 7.20, category: 'Coffee', date: '2025-09-02'}
  ];
  
  function renderTransactions() {
    const root = document.getElementById('transactions-list');
    if (!root) return;
  
    if (demoTx.length === 0) {
      root.innerHTML = '<p>No transactions yet.</p>';
      return;
    }
  
    // Build table
    const table = document.createElement('table');
    table.className = 'table table-striped';
    table.innerHTML = `
      <thead>
        <tr>
          <th>ID</th>
          <th>From</th>
          <th>To</th>
          <th>Amount</th>
          <th>Category</th>
          <th>Date</th>
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
        <td>${tx.amount.toFixed(2)}</td>
        <td>${tx.category}</td>
        <td>${tx.date}</td>
      `;
      tbody.appendChild(row);
    });
  
    table.appendChild(tbody);
    root.innerHTML = '';
    root.appendChild(table);
  }
  
  // Call when transactions view is loaded
  window.addEventListener('hashchange', () => {
    if (location.hash === '#transactions') renderTransactions();
  });
  window.addEventListener('load', () => {
    if (location.hash === '#transactions') renderTransactions();
  });
  