// Page controllers register themselves here, e.g. Pages.dashboard = { init() {...} }
const Pages = {};

// Small helpers shared by all pages.
const UI = {
  // Bootstrap toast in the bottom-right corner. type: success | danger | warning | info
  toast(message, type = 'success') {
    const container = document.getElementById('toast-container');
    const toast = document.createElement('div');
    toast.className = `toast align-items-center text-bg-${type} border-0`;
    toast.setAttribute('role', 'alert');
    toast.innerHTML = `<div class="d-flex">
        <div class="toast-body">${UI.escape(message)}</div>
        <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
      </div>`;
    container.appendChild(toast);
    new bootstrap.Toast(toast, { delay: 4000 }).show();
    toast.addEventListener('hidden.bs.toast', () => toast.remove());
  },

  // Shows an error from the API (403 included) as a red toast.
  // 401 errors were already handled by Api (redirect to login), so they are skipped.
  error(error) {
    if (!error.handled) {
      UI.toast(error.message, 'danger');
    }
  },

  // Prevents HTML injection when putting user data into innerHTML
  escape(value) {
    const div = document.createElement('div');
    div.textContent = value ?? '';
    return div.innerHTML;
  },

  money(amount, currency = 'EUR') {
    return new Intl.NumberFormat('en-IE', { style: 'currency', currency }).format(Number(amount));
  },

  date(value) {
    return new Date(value.replace(' ', 'T')).toLocaleDateString('en-GB');
  },

  badge(status) {
    const colors = {
      completed: 'success', paid: 'success', accepted: 'success',
      pending: 'warning', failed: 'danger', declined: 'secondary', blocked: 'danger',
      admin: 'dark', user: 'light',
    };
    return `<span class="badge text-bg-${colors[status] || 'secondary'}">${UI.escape(status)}</span>`;
  },

  // Fills a <select> with options. items: [{id, name}]
  fillSelect(select, items, emptyLabel = null) {
    select.innerHTML = (emptyLabel !== null ? `<option value="">${UI.escape(emptyLabel)}</option>` : '')
      + items.map(item => `<option value="${item.id}">${UI.escape(item.name)}</option>`).join('');
  },

  // Reads all named fields of a form into an object
  formData(form) {
    return Object.fromEntries(new FormData(form).entries());
  },

  emptyRow(columns, text) {
    return `<tr><td colspan="${columns}" class="text-center text-muted py-4">${UI.escape(text)}</td></tr>`;
  },

  modal(id) {
    return bootstrap.Modal.getOrCreateInstance(document.getElementById(id));
  },

  // Disables a submit button while a request is running
  async busy(button, action) {
    button.disabled = true;
    try {
      return await action();
    } finally {
      button.disabled = false;
    }
  },
};
