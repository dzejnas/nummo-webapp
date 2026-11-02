// Page-specific setup code. The router calls Views[pageName]() after a view is loaded.
// Milestone 1: static demo data only. Real data comes from the REST API in Milestone 4.

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
  },
};
