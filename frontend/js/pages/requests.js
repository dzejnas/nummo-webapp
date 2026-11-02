// Payment requests: ask a contact for money; the payer can pay or decline, the requester can cancel
Pages.requests = {
  init() {
    document.getElementById('new-request-button').addEventListener('click', () => this.openNew());
    document.getElementById('request-form').addEventListener('submit', (event) => this.create(event));
    document.getElementById('requests-table').addEventListener('click', (event) => this.handleAction(event));
    Validation.clearOnInput(document.getElementById('request-form'));
    this.load();
  },

  async load() {
    const body = document.getElementById('requests-table');
    try {
      const requests = await PaymentRequestService.getAll();
      body.innerHTML = requests.length === 0
        ? UI.emptyRow(7, 'No payment requests yet')
        : requests.map(r => this.row(r)).join('');
    } catch (error) {
      UI.error(error);
    }
  },

  row(r) {
    const me = Auth.getUser();
    let actions = '';
    if (r.status === 'pending' && r.payer_id === me.id) {
      actions = `<button class="btn btn-sm btn-success" data-action="pay" data-id="${r.id}">Pay</button>
                 <button class="btn btn-sm btn-outline-secondary" data-action="decline" data-id="${r.id}">Decline</button>`;
    } else if (r.requester_id === me.id || Auth.isAdmin()) {
      actions = `<button class="btn btn-sm btn-outline-danger" data-action="delete" data-id="${r.id}" title="Delete"><i class="bi bi-trash"></i></button>`;
    }
    return `<tr>
      <td>${UI.date(r.created_at)}</td>
      <td>${UI.escape(r.requester_name)}</td>
      <td>${UI.escape(r.payer_name)}</td>
      <td>${UI.escape(r.note || '')}</td>
      <td>${UI.badge(r.status)}</td>
      <td class="text-end">${UI.money(r.amount)}</td>
      <td class="text-end text-nowrap">${actions}</td>
    </tr>`;
  },

  async openNew() {
    const form = document.getElementById('request-form');
    Validation.reset(form);
    try {
      const contacts = await ContactService.getAll();
      const people = contacts
        .filter(c => c.status === 'accepted')
        .map(c => ({ id: c.friend_user_id, name: `${c.friend_name} (${c.friend_email})` }));
      UI.fillSelect(document.getElementById('req-payer'), people, people.length ? 'Choose a contact' : 'Add a contact first');
      UI.modal('request-modal').show();
    } catch (error) {
      UI.error(error);
    }
  },

  async create(event) {
    event.preventDefault();
    const form = event.target;
    if (!Validation.check(form)) return;
    try {
      await UI.busy(form.querySelector('[type=submit]'), () => PaymentRequestService.create(UI.formData(form)));
      UI.modal('request-modal').hide();
      UI.toast('Request sent');
      this.load();
    } catch (error) {
      UI.error(error);
    }
  },

  async handleAction(event) {
    const button = event.target.closest('button[data-action]');
    if (!button) return;
    const id = Number(button.dataset.id);

    try {
      if (button.dataset.action === 'pay') {
        if (!confirm('Pay this request now?')) return;
        await PaymentRequestService.update(id, { status: 'paid' });
        UI.toast('Request paid');
      } else if (button.dataset.action === 'decline') {
        await PaymentRequestService.update(id, { status: 'declined' });
        UI.toast('Request declined', 'info');
      } else if (button.dataset.action === 'delete') {
        if (!confirm('Delete this request?')) return;
        await PaymentRequestService.remove(id);
        UI.toast('Request deleted');
      }
      this.load();
    } catch (error) {
      UI.error(error);
    }
  },
};
