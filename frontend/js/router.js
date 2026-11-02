// Hash-based SPA router.
// "#transactions" loads views/transactions.html into <main id="app"> without a page reload.

const routes = {
  login:        { view: 'views/login.html',        title: 'Login' },
  register:     { view: 'views/register.html',     title: 'Register' },
  dashboard:    { view: 'views/dashboard.html',    title: 'Dashboard' },
  transactions: { view: 'views/transactions.html', title: 'Transactions' },
  requests:     { view: 'views/requests.html',     title: 'Payment requests' },
  contacts:     { view: 'views/contacts.html',     title: 'Contacts' },
  merchants:    { view: 'views/merchants.html',    title: 'Merchants' },
  categories:   { view: 'views/categories.html',   title: 'Categories' },
  users:        { view: 'views/users.html',        title: 'Users' },
  profile:      { view: 'views/profile.html',      title: 'Profile' },
};

const DEFAULT_ROUTE = 'dashboard';

function currentRoute() {
  const name = location.hash.replace('#', '');
  return routes[name] ? name : DEFAULT_ROUTE;
}

async function loadRoute() {
  const name = currentRoute();
  const route = routes[name];
  const app = document.getElementById('app');

  try {
    const response = await fetch(route.view);
    if (!response.ok) throw new Error('View not found: ' + route.view);
    app.innerHTML = await response.text();
  } catch (error) {
    app.innerHTML = '<div class="alert alert-danger">Could not load this page.</div>';
    console.error(error);
    return;
  }

  document.title = route.title + ' · Nummo';
  highlightNav(name);
  closeMobileMenu();

  // Run the page's own setup code, if it has any (see views.js)
  if (typeof Views[name] === 'function') {
    Views[name]();
  }
}

function highlightNav(name) {
  document.querySelectorAll('#main-nav .nav-link, #main-nav .dropdown-item').forEach(link => {
    link.classList.toggle('active', link.getAttribute('href') === '#' + name);
  });
}

function closeMobileMenu() {
  const menu = document.getElementById('main-nav');
  if (menu.classList.contains('show')) {
    bootstrap.Collapse.getOrCreateInstance(menu).hide();
  }
}

window.addEventListener('hashchange', loadRoute);
document.addEventListener('DOMContentLoaded', loadRoute);
