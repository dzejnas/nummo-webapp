// Demo transactions
const demoTx = [
    {id:1, sender:'alice', receiver:'me', amount:120.50, category:'salary', date:'2025-09-01'},
    {id:2, sender:'me', receiver:'bob', amount:50.00, category:'food', date:'2025-09-02'},
    {id:3, sender:'me', receiver:'charlie', amount:30.25, category:'coffee', date:'2025-09-03'},
    {id:4, sender:'david', receiver:'me', amount:200.00, category:'freelance', date:'2025-09-05'}
  ];
  
  // Calculate balance, income, expenses
  function updateDashboard() {
    let income = 0;
    let expenses = 0;
  
    demoTx.forEach(t => {
      if(t.receiver === 'me') income += t.amount;
      if(t.sender === 'me') expenses += t.amount;
    });
  
    const balance = income - expenses;
  
    document.getElementById('balance').textContent = `$${balance.toFixed(2)}`;
    document.getElementById('income').textContent = `$${income.toFixed(2)}`;
    document.getElementById('expenses').textContent = `$${expenses.toFixed(2)}`;
  
    renderChart();
  }
  
  // Simple chart by category
  function renderChart() {
    const ctx = document.getElementById('transactionsChart').getContext('2d');
  
    // Aggregate expenses per category
    const categories = {};
    demoTx.forEach(t => {
      if(t.sender === 'me') {
        categories[t.category] = (categories[t.category] || 0) + t.amount;
      }
    });
  
    new Chart(ctx, {
      type: 'pie',
      data: {
        labels: Object.keys(categories),
        datasets: [{
          data: Object.values(categories),
          backgroundColor: ['#FF6384', '#36A2EB', '#FFCE56', '#4BC0C0'],
        }]
      },
      options: {
        responsive: true,
        plugins: {
          legend: {
            position: 'bottom'
          },
          title: {
            display: true,
            text: 'Expenses by Category'
          }
        }
      }
    });
  }
  
  // Call this when dashboard view is loaded
  function initDashboard() {
    updateDashboard();
  }
  