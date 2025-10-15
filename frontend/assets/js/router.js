// assets/js/router.js

const app = document.getElementById('app');
const pageTitle = document.getElementById('page-title');
const pageHeader = document.getElementById('page-header');

// Define routes and page titles
const routes = {
  dashboard: { view: 'views/dashboard.html', title: 'Dashboard' },
  transactions: { view: 'views/transactions.html', title: 'Transactions' },
  profile: { view: 'views/profile.html', title: 'Profile' },
  login: { view: 'views/login.html', title: 'Login' },
  register: { view: 'views/register.html', title: 'Register' },
};

// Load a route (with fade transition)
function loadRoute(route) {
  const routeData = routes[route] || routes.dashboard;

  // Fade out content
  app.classList.add('fade-out');
  pageHeader.classList.add('fade-out');

  // Wait for fade, then fetch view
  setTimeout(() => {
    fetch(routeData.view)
      .then((res) => {
        if (!res.ok) throw new Error('View not found');
        return res.text();
      })
      .then((html) => {
        app.innerHTML = html;
        pageTitle.textContent = routeData.title;

        // Fade in content
        app.classList.remove('fade-out');
        pageHeader.classList.remove('fade-out');

        // Highlight active nav
        highlightNav(route);

        // Run page-specific logic
        if (route === 'dashboard' && typeof renderDashboard === 'function') {
          renderDashboard();
        }
        if (route === 'transactions' && typeof renderTransactions === 'function') {
          renderTransactions();
        }
      })
      .catch((error) => {
        console.error(error);
        app.innerHTML = `<h1>404</h1><p>View "${route}" not found.</p>`;
      });
  }, 300);
}

// Highlight active navbar link
function highlightNav(activeRoute) {
  const links = document.querySelectorAll('#nav-links .nav-link');
  links.forEach((link) => {
    const linkRoute = link.getAttribute('href').replace('#', '');
    link.classList.toggle('active', linkRoute === activeRoute);
  });
}

// Handle navigation
window.addEventListener('hashchange', () => {
  const route = location.hash.replace('#', '');
  loadRoute(route);
});

// Initial load
document.addEventListener('DOMContentLoaded', () => {
  const initialRoute = location.hash.replace('#', '') || 'dashboard';
  loadRoute(initialRoute);
});
