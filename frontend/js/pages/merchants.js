// Merchants: everyone can see them; admins can add, edit and delete
Pages.merchants = {
  merchants: [],

  async init() {
    const isAdmin = Auth.isAdmin();
    document.getElementById('new-merchant-button').classList.toggle('d-none', !isAdmin);
    document.getElementById('new-merchant-button').addEventListener('click', () => this.openForm(null));
    document.getElementById('merchant-form').addEventListener('submit', (event) => this.save(event));
    document.getElementById('merchants-table').addEventListener('click', (event) => this.handleAction(event));

    if (isAdmin) {
      CategoryService.getAll()
        .then(categories => UI.fillSelect(document.getElementById('merchant-category'), categories, 'No category'))
        .catch(UI.error);
    }
    this.load();
  },

  async load() {
    const body = document.getElementById('merchants-table');
    const isAdmin = Auth.isAdmin();
    try {
      this.merchants = await MerchantService.getAll();
      body.innerHTML = this.merchants.length === 0
        ? UI.emptyRow(4, 'No merchants yet')
        : this.merchants.map(m => `<tr>
            <td>${UI.escape(m.name)}</td>
            <td>${UI.escape(m.category_name || '—')}</td>
            <td>${UI.escape(m.contact_email || '')}</td>
            <td class="text-end text-nowrap">${isAdmin ? `
              <button class="btn btn-sm btn-outline-secondary" data-action="edit" data-id="${m.id}" title="Edit"><i class="bi bi-pencil"></i></button>
              <button class="btn btn-sm btn-outline-danger" data-action="delete" data-id="${m.id}" title="Delete"><i class="bi bi-trash"></i></button>` : ''}
            </td>
          </tr>`).join('');
    } catch (error) {
      UI.error(error);
    }
  },

  openForm(merchant) {
    const form = document.getElementById('merchant-form');
    form.reset();
    form.elements.id.value = merchant ? merchant.id : '';
    if (merchant) {
      form.elements.name.value = merchant.name;
      form.elements.category_id.value = merchant.category_id || '';
      form.elements.contact_email.value = merchant.contact_email || '';
    }
    document.getElementById('merchant-modal-title').textContent = merchant ? 'Edit merchant' : 'Add merchant';
    UI.modal('merchant-modal').show();
  },

  async save(event) {
    event.preventDefault();
    const form = event.target;
    const data = UI.formData(form);
    try {
      await UI.busy(form.querySelector('[type=submit]'), () => data.id
        ? MerchantService.update(data.id, data)
        : MerchantService.create(data));
      UI.modal('merchant-modal').hide();
      UI.toast('Merchant saved');
      this.load();
    } catch (error) {
      UI.error(error);
    }
  },

  async handleAction(event) {
    const button = event.target.closest('button[data-action]');
    if (!button) return;
    const merchant = this.merchants.find(m => m.id === Number(button.dataset.id));

    if (button.dataset.action === 'edit') {
      this.openForm(merchant);
      return;
    }
    if (!confirm(`Delete ${merchant.name}?`)) return;
    try {
      await MerchantService.remove(merchant.id);
      UI.toast('Merchant deleted');
      this.load();
    } catch (error) {
      UI.error(error);
    }
  },
};
