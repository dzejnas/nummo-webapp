// Contacts: add by email, block/unblock, remove
Pages.contacts = {
  init() {
    document.getElementById('contact-form').addEventListener('submit', (event) => this.add(event));
    document.getElementById('contacts-table').addEventListener('click', (event) => this.handleAction(event));
    Validation.clearOnInput(document.getElementById('contact-form'));
    this.load();
  },

  async load() {
    const body = document.getElementById('contacts-table');
    try {
      const contacts = await ContactService.getAll();
      body.innerHTML = contacts.length === 0
        ? UI.emptyRow(5, 'No contacts yet — add someone by their email')
        : contacts.map(c => `<tr>
            <td>${UI.escape(c.friend_name)}</td>
            <td>${UI.escape(c.friend_email)}</td>
            <td>${UI.badge(c.status)}</td>
            <td>${UI.date(c.created_at)}</td>
            <td class="text-end text-nowrap">
              ${c.status === 'blocked'
                ? `<button class="btn btn-sm btn-outline-success" data-action="unblock" data-id="${c.id}">Unblock</button>`
                : `<button class="btn btn-sm btn-outline-secondary" data-action="block" data-id="${c.id}">Block</button>`}
              <button class="btn btn-sm btn-outline-danger" data-action="delete" data-id="${c.id}" title="Remove"><i class="bi bi-trash"></i></button>
            </td>
          </tr>`).join('');
    } catch (error) {
      UI.error(error);
    }
  },

  async add(event) {
    event.preventDefault();
    const form = event.target;
    if (!Validation.check(form)) return;
    try {
      await UI.busy(form.querySelector('[type=submit]'), () => ContactService.addByEmail(form.elements.email.value));
      UI.modal('contact-modal').hide();
      Validation.reset(form);
      UI.toast('Contact added');
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
      if (button.dataset.action === 'block') {
        await ContactService.setStatus(id, 'blocked');
      } else if (button.dataset.action === 'unblock') {
        await ContactService.setStatus(id, 'accepted');
      } else if (button.dataset.action === 'delete') {
        if (!confirm('Remove this contact?')) return;
        await ContactService.remove(id);
      }
      UI.toast('Contact updated');
      this.load();
    } catch (error) {
      UI.error(error);
    }
  },
};
