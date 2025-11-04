// =========================================================
// NUMMO APP — Router
// Handles single-page navigation and dynamic view loading
// =========================================================

// Wait until DOM is ready
document.addEventListener('DOMContentLoaded', () => {
  initRouter();
});

// ---------------------------------------------------------
// Router Setup
// ---------------------------------------------------------
function initRouter() {
  const app = document.getElementById('app');
  const pageTitle = document.getElementById('page-title');
  const pageHeader = document.getElementById('page-header');

  // Ensure required DOM elements exist
  if (!app) {
    console.error('❌ Router Error: #app container missing');
    return;
  }

  // Define routes and metadata
  const routes = {
    dashboard: { view: 'views/dashboard.html', title: 'Dashboard' },
    transactions: { view: 'views/transactions.html', title: 'Transactions' },
    profile: { view: 'views/profile.html', title: 'Profile' },
    login: { view: 'views/login.html', title: 'Login' },
    register: { view: 'views/register.html', title: 'Register' },
  };

  // Handle hash changes
  window.addEventListener('hashchange', () => {
    const route = getRoute();
    loadRoute(route, routes, app, pageHeader, pageTitle);
  });

  // Initial load
  const initialRoute = getRoute();
  loadRoute(initialRoute, routes, app, pageHeader, pageTitle);
}

// ---------------------------------------------------------
// Get current hash route (default: dashboard)
// ---------------------------------------------------------
function getRoute() {
  return location.hash.replace('#', '') || 'dashboard';
}

// ---------------------------------------------------------
// Load a route with fade transition
// ---------------------------------------------------------
function loadRoute(route, routes, app, pageHeader, pageTitle) {
  const routeData = routes[route] || routes.dashboard;

  // Fade-out animation
  app.classList.add('fade-out');
  if (pageHeader) pageHeader.classList.add('fade-out');

  setTimeout(() => {
    fetch(routeData.view)
      .then(res => {
        if (!res.ok) throw new Error(`View not found: ${routeData.view}`);
        return res.text();
      })
      .then(html => {
        app.innerHTML = html;
        if (pageTitle) pageTitle.textContent = routeData.title;

        // Fade-in content
        app.classList.remove('fade-out');
        app.classList.add('fade-in');
        if (pageHeader) pageHeader.classList.remove('fade-out');

        // Highlight active navbar link
        highlightNav(route);

        // Run view-specific logic
        runViewLogic(route);
      })
      .catch(err => {
        console.error('❌ Router failed:', err);
        app.innerHTML = `<div class="text-center p-5 text-danger">
                           <h1>404</h1><p>View "${route}" not found.</p>
                         </div>`;
      });
  }, 250); // Delay for fade-out
}

// ---------------------------------------------------------
// Highlight the active navbar link
// ---------------------------------------------------------
function highlightNav(activeRoute) {
  const links = document.querySelectorAll('#nav-links .nav-link');
  links.forEach(link => {
    const linkRoute = link.getAttribute('href').replace('#', '');
    link.classList.toggle('active', linkRoute === activeRoute);
  });
}

// ---------------------------------------------------------
// Run page-specific logic (integrated with main.js exports)
// ---------------------------------------------------------
function runViewLogic(route) {
  // Check global functions that main.js or other modules expose
  if (route === 'dashboard' && typeof window.initDashboard === 'function') {
    window.initDashboard();
  }
  if (route === 'transactions' && typeof window.renderTransactions === 'function') {
    window.renderTransactions();
  }
  if (route === 'profile' && typeof window.loadProfile === 'function') {
    window.loadProfile();
  }
}
