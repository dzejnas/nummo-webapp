// =========================================================
// NUMMO APP — Main Controller
// Handles routing, theme switching, and global view events
// =========================================================

import { initDashboard } from './dashboard.js';

// ---------------------------------------------------------
// Mock Data (for demo before backend is connected)
// ---------------------------------------------------------
const demoTx = [
  { id: 1, sender: 'Alice', receiver: 'Me', amount: 50.00, category: 'Salary', date: '2025-09-01' },
  { id: 2, sender: 'Me', receiver: 'Bob', amount: 12.50, category: 'Food', date: '2025-09-02' },
  { id: 3, sender: 'Me', receiver: 'Charlie', amount: 7.20, category: 'Coffee', date: '2025-09-03' }
];
window.demoTx = demoTx; // make available globally

// =========================================================
// Global App Initializer
// =========================================================
document.addEventListener('DOMContentLoaded', () => {
  console.log('🌐 Nummo App Loaded');
  setupThemeToggle();
  setupRouting();
  loadCurrentView();
});

// =========================================================
// Theme Toggle (Dark / Light Mode)
// =========================================================
function setupThemeToggle() {
  const toggle = document.createElement('button');
  toggle.textContent = '🌙';
  toggle.classList.add('theme-toggle', 'btn', 'btn-light');
  document.body.appendChild(toggle);

  toggle.addEventListener('click', () => {
    const dark = document.body.classList.toggle('dark-mode');
    toggle.textContent = dark ? '☀️' : '🌙';
    localStorage.setItem('nummo-theme', dark ? 'dark' : 'light');
  });

  // Restore saved theme
  if (localStorage.getItem('nummo-theme') === 'dark') {
    document.body.classList.add('dark-mode');
    toggle.textContent = '☀️';
  }
}

// =========================================================
// Simple Client-side Routing
// =========================================================
function setupRouting() {
  window.addEventListener('hashchange', loadCurrentView);
}

async function loadCurrentView() {
  const route = location.hash.replace('#', '') || 'login';
  const app = document.getElementById('app');
  app.classList.add('fade-out');

  try {
    const res = await fetch(`views/${route}.html`);
    if (!res.ok) throw new Error('View not found');
    const html = await res.text();
    app.innerHTML = html;

    // Animate in
    app.classList.remove('fade-out');
    app.classList.add('fade-in');

    // Trigger view-specific logic
    handleViewLoaded(route);
  } catch (err) {
    app.innerHTML = `<div class="p-5 text-center text-danger">
                      <h4>404</h4>
                      <p>View "${route}" not found.</p>
                    </div>`;
  }
}

// =========================================================
// View-Specific Hooks
// =========================================================
function handleViewLoaded(route) {
  switch (route) {
    case 'dashboard':
      initDashboard();
      break;
    case 'transactions':
      renderTransactions();
      break;
    case 'profile':
      loadProfile();
      break;
    default:
      console.log(`Loaded view: ${route}`);
  }
}

// =========================================================
// Placeholder functions for upcoming modules
// =========================================================
function renderTransactions() {
  console.log('📄 Transactions page logic will go here.');
}

function loadProfile() {
  console.log('👤 Profile view logic will go here.');
}
