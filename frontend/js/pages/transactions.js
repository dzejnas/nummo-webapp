// Transactions: list with filters, send money, edit/complete/delete pending payments
Pages.transactions = {
  categories: [],

  async init() {
    const filters = document.getElementById('transaction-filters');
    filters.addEventListener('change', () => this.load());
    document.getElementById('new-transaction-button').addEventListener('click', () => this.openNew());
    document.getElementById('tx-type').addEventListener('change', (event) => this.switchType(event.target.value));
    document.getElementById('transaction-form').addEventListener('submit', (event) => this.create(event));
    document.getElementById('transaction-edit-form').addEventListener('submit', (event) => this.saveEdit(event));
    document.getElementById('transactions-table').addEventListener('click', (event) => this.handleAction(event));
    Validation.clearOnInput(document.getElementById('transaction-form'));
    Validation.clearOnInput(document.getElementById('transaction-edit-form'));

    try {
      this.categories = await CategoryService.getAll();
      UI.fillSelect(document.getElementById('filter-category'), this.categories, 'All categories');
      UI.fillSelect(document.getElementById('tx-category'), this.categories, 'No category');
      UI.fillSelect(document.getElementById('tx-edit-category'), this.categories, 'No category');
    } catch (error) {
      UI.error(error);
    }
    this.load();
  },

  async load() {
    const body = document.getElementById('transactions-table');
    try {
      const transactions = await TransactionService.getAll(UI.formData(document.getElementById('transaction-filters')));
      this.transactions = transactions;
      body.innerHTML = transactions.length === 0
        ? UI.emptyRow(8, 'No transactions found')
        : transactions.map(t => this.row(t)).join('');
    } catch (error) {
      UI.error(error);
    }
  },

  row(t) {
    const me = Auth.getUser();
    const canChange = t.status === 'pending' && (t.sender_id === me.id || Auth.isAdmin());
    const receipt = `<button class="btn btn-sm btn-outline-primary" data-action="receipt" data-id="${t.id}"
      aria-label="Receipt ${UI.escape(t.reference)}" title="Receipt"><i class="bi bi-receipt"></i></button>`;
    const actions = canChange ? `
      <button class="btn btn-sm btn-success" data-action="complete" data-id="${t.id}" title="Send now"><i class="bi bi-check-lg"></i></button>
      <button class="btn btn-sm btn-outline-secondary" data-action="edit" data-id="${t.id}" title="Edit"><i class="bi bi-pencil"></i></button>
      <button class="btn btn-sm btn-outline-danger" data-action="delete" data-id="${t.id}" title="Delete"><i class="bi bi-trash"></i></button>` : '';
    return `<tr>
      <td>${UI.date(t.created_at)}</td>
      <td>${UI.escape(t.sender_name)}</td>
      <td>${UI.escape(t.receiver_name || t.merchant_name)}</td>
      <td>${UI.escape(t.category_name || '—')}</td>
      <td>${UI.escape(t.note || '')}</td>
      <td>${UI.badge(t.status)}</td>
      <td class="text-end">${UI.money(t.amount)}</td>
      <td class="text-end text-nowrap">${receipt} ${actions}</td>
    </tr>`;
  },

  async openNew() {
    const form = document.getElementById('transaction-form');
    Validation.reset(form);
    delete form.dataset.idempotencyKey; // a new payment gets a new key
    this.switchType('user');
    try {
      const [contacts, merchants] = await Promise.all([ContactService.getAll(), MerchantService.getAll()]);
      const people = contacts
        .filter(c => c.status === 'accepted')
        .map(c => ({ id: c.friend_user_id, name: `${c.friend_name} (${c.friend_email})` }));
      UI.fillSelect(document.getElementById('tx-receiver'), people, people.length ? 'Choose a contact' : 'Add a contact first');
      UI.fillSelect(document.getElementById('tx-merchant'), merchants, 'Choose a merchant');
      UI.modal('transaction-modal').show();
    } catch (error) {
      UI.error(error);
    }
  },

  switchType(type) {
    document.getElementById('tx-type').value = type;
    document.getElementById('tx-receiver-group').classList.toggle('d-none', type !== 'user');
    document.getElementById('tx-merchant-group').classList.toggle('d-none', type !== 'merchant');
    document.getElementById('tx-receiver').required = type === 'user';
    document.getElementById('tx-merchant').required = type === 'merchant';
  },

  async create(event) {
    event.preventDefault();
    const form = event.target;
    if (!Validation.check(form)) return;
    const data = UI.formData(form);
    const type = document.getElementById('tx-type').value;

    const payment = {
      amount: data.amount,
      category_id: data.category_id || null,
      note: data.note,
      status: data.pending ? 'pending' : 'completed',
    };
    if (type === 'user') payment.receiver_id = data.receiver_id;
    else payment.merchant_id = data.merchant_id;

    // Keep the same key while the outcome is unknown, so pressing Send again cannot pay twice.
    // UI.busy disables the button while the request runs (no double clicks).
    form.dataset.idempotencyKey ||= UI.newId();
    try {
      const transaction = await UI.busy(form.querySelector('[type=submit]'),
        () => TransactionService.create(payment, form.dataset.idempotencyKey));
      delete form.dataset.idempotencyKey;
      UI.modal('transaction-modal').hide();
      UI.toast(payment.status === 'completed' ? 'Payment sent' : 'Payment saved as pending');
      this.load();
      Receipt.show(transaction);
    } catch (error) {
      if (error.status === 0 || error.status >= 500) {
        UI.toast('We could not confirm this payment. Press Send again to retry; you will not be charged twice.', 'warning');
        return; // keep the key and the form as they are
      }
      delete form.dataset.idempotencyKey; // the server answered: a changed payment needs a new key
      UI.error(error);
    }
  },

  async handleAction(event) {
    const button = event.target.closest('button[data-action]');
    if (!button) return;
    const id = Number(button.dataset.id);

    if (button.dataset.action === 'receipt') {
      Receipt.open(id);
      return;
    }
    try {
      if (button.dataset.action === 'complete') {
        await TransactionService.update(id, { status: 'completed' });
        UI.toast('Payment sent');
      } else if (button.dataset.action === 'delete') {
        if (!confirm('Delete this pending payment?')) return;
        await TransactionService.remove(id);
        UI.toast('Payment deleted');
      } else if (button.dataset.action === 'edit') {
        const t = this.transactions.find(item => item.id === id);
        const form = document.getElementById('transaction-edit-form');
        form.elements.id.value = t.id;
        form.elements.category_id.value = t.category_id || '';
        form.elements.note.value = t.note || '';
        UI.modal('transaction-edit-modal').show();
        return;
      }
      this.load();
    } catch (error) {
      UI.error(error);
    }
  },

  async saveEdit(event) {
    event.preventDefault();
    const form = event.target;
    if (!Validation.check(form)) return;
    const data = UI.formData(form);
    try {
      await TransactionService.update(data.id, { category_id: data.category_id || null, note: data.note });
      UI.modal('transaction-edit-modal').hide();
      UI.toast('Payment updated');
      this.load();
    } catch (error) {
      UI.error(error);
    }
  },
};
