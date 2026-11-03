// Hash-based SPA router.
// "#transactions" loads views/transactions.html into <main id="app"> without a page reload,
// then runs Pages.transactions.init() (js/pages/transactions.js).
//
// access: 'guest' = only when logged out, 'user' = any logged-in user, 'admin' = admins only

const routes = {
  login:        { view: 'views/login.html',        title: 'Log in',           access: 'guest' },
  register:     { view: 'views/register.html',     title: 'Register',         access: 'guest' },
  dashboard:    { view: 'views/dashboard.html',    title: 'Dashboard',        access: 'user' },
  transactions: { view: 'views/transactions.html', title: 'Transactions',     access: 'user' },
  requests:     { view: 'views/requests.html',     title: 'Payment requests', access: 'user' },
  contacts:     { view: 'views/contacts.html',     title: 'Contacts',         access: 'user' },
  merchants:    { view: 'views/merchants.html',    title: 'Merchants',        access: 'user' },
  profile:      { view: 'views/profile.html',      title: 'Profile',          access: 'user' },
  categories:   { view: 'views/categories.html',   title: 'Categories',       access: 'admin' },
  users:        { view: 'views/users.html',        title: 'Users',            access: 'admin' },
};



async function loadRoute() {
  let name = location.hash.replace('#', '');

  if (name === 'logout') {
    AuthService.logout();
    UI.toast('You are logged out', 'info');
    location.hash = '#login';
    return;
  }

  if (!routes[name]) {
    name = Auth.isLoggedIn() ? 'dashboard' : 'login';
  }
  const route = routes[name];

  // Route guards
  if (route.access !== 'guest' && !Auth.isLoggedIn()) {
    location.hash = '#login';
    return;
  }
  if (route.access === 'guest' && Auth.isLoggedIn()) {
    location.hash = '#dashboard';
    return;
  }
  if (route.access === 'admin' && !Auth.isAdmin()) {
    UI.toast('This page is only for administrators', 'danger');
    location.hash = '#dashboard';
    return;
  }

  updateNavbar(name);

  const app = document.getElementById('app');
  try {
    const response = await fetch(route.view);
    if (!response.ok) throw new Error('View not found: ' + route.view);
    app.innerHTML = await response.text();
  } catch (error) {
    app.innerHTML = '<div class="alert alert-danger">Could not load this page.</div>';
    return;
  }

  document.title = route.title + ' · Nummo';
  closeMobileMenu();

  if (Pages[name]) {
    Pages[name].init();
  }
}

// Shows or hides navbar items for the current role, and highlights the active page
function updateNavbar(activeName) {
  const loggedIn = Auth.isLoggedIn();
  document.querySelectorAll('[data-show]').forEach(element => {
    const show = element.dataset.show;
    const visible = (show === 'guest' && !loggedIn)
      || (show === 'user' && loggedIn)
      || (show === 'admin' && Auth.isAdmin());
    element.classList.toggle('d-none', !visible);
  });

  const user = Auth.getUser();
  document.getElementById('nav-user-name').textContent = user ? user.name : 'Profile';

  document.querySelectorAll('#main-nav .nav-link, #main-nav .dropdown-item').forEach(link => {
    link.classList.toggle('active', link.getAttribute('href') === '#' + activeName);
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
