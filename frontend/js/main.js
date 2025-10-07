// Simple SPA router + demo data for Milestone 1
const views = document.querySelectorAll('.app-view');
function showView(name){
  views.forEach(v=> v.style.display='none');
  const el = document.getElementById('view-'+name);
  if(el) el.style.display='block';
}
function router(){
  const h = location.hash || '#/dashboard';
  const route = h.replace('#/','') || 'dashboard';
  showView(route);
  if(route === 'transactions') renderTransactions();
}
window.addEventListener('hashchange', router);
window.addEventListener('load', router);

// Demo transactions
const demoTx = [
  {id:1,sender:'alice',receiver:'bob',amount:12.50,category:'food',date:'2025-09-01'},
  {id:2,sender:'me',receiver:'charlie',amount:7.20,category:'coffee',date:'2025-09-02'}
];
function renderTransactions(){
  const root = document.getElementById('transactions-list');
  root.innerHTML = '<table class="table"><thead><tr><th>ID</th><th>From</th><th>To</th><th>Amount</th><th>Category</th></tr></thead></table>';
  const tbody = document.createElement('tbody');
  demoTx.forEach(t=>{
    const r = document.createElement('tr');
    r.innerHTML = `<td>${t.id}</td><td>${t.sender}</td><td>${t.receiver}</td><td>${t.amount}</td><td>${t.category}</td>`;
    tbody.appendChild(r);
  });
  root.querySelector('table').appendChild(tbody);
}

// Minimal login handler (client-side only for Milestone 1)
document.getElementById('login-form')?.addEventListener('submit',e=>{
  e.preventDefault();
  const email = document.getElementById('login-email').value;
  // Simulate successful login
  localStorage.setItem('nummo_token','demo-token');
  location.hash = '#/dashboard';
});
