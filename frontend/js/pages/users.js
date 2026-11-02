// Users (admin page): add, edit (role, balance, currency), delete
Pages.users = {
  users: [],

  init() {
    document.getElementById('new-user-button').addEventListener('click', () => this.openForm(null));
    document.getElementById('user-form').addEventListener('submit', (event) => this.save(event));
    document.getElementById('users-table').addEventListener('click', (event) => this.handleAction(event));
    Validation.clearOnInput(document.getElementById('user-form'));
    this.load();
  },

  async load() {
    const body = document.getElementById('users-table');
    const me = Auth.getUser();
    try {
      this.users = await UserService.getAll();
      body.innerHTML = this.users.map(u => `<tr>
          <td>${u.id}</td>
          <td>${UI.escape(u.name)}</td>
          <td>${UI.escape(u.email)}</td>
          <td>${UI.badge(u.role)}</td>
          <td>${UI.escape(u.currency)}</td>
          <td class="text-end">${UI.money(u.balance)}</td>
          <td class="text-end text-nowrap">
            <button class="btn btn-sm btn-outline-secondary" data-action="edit" data-id="${u.id}" title="Edit"><i class="bi bi-pencil"></i></button>
            ${u.id !== me.id ? `<button class="btn btn-sm btn-outline-danger" data-action="delete" data-id="${u.id}" title="Delete"><i class="bi bi-trash"></i></button>` : ''}
          </td>
        </tr>`).join('');
    } catch (error) {
      UI.error(error);
    }
  },

  openForm(user) {
    const form = document.getElementById('user-form');
    Validation.reset(form);
    form.elements.id.value = user ? user.id : '';
    if (user) {
      form.elements.name.value = user.name;
      form.elements.email.value = user.email;
      form.elements.role.value = user.role;
      form.elements.currency.value = user.currency;
      form.elements.balance.value = user.balance;
    }
    // A password is required for a new user, optional when editing
    form.elements.password.required = !user;
    document.getElementById('user-password-help').textContent = user
      ? 'Leave empty to keep the current password.' : 'At least 6 characters.';
    document.getElementById('user-modal-title').textContent = user ? 'Edit user' : 'Add user';
    UI.modal('user-modal').show();
  },

  async save(event) {
    event.preventDefault();
    const form = event.target;
    if (!Validation.check(form)) return;
    const data = UI.formData(form);
    if (data.password === '') delete data.password;
    try {
      await UI.busy(form.querySelector('[type=submit]'), () => data.id
        ? UserService.update(data.id, data)
        : UserService.create(data));
      UI.modal('user-modal').hide();
      UI.toast('User saved');
      this.load();
    } catch (error) {
      UI.error(error);
    }
  },

  async handleAction(event) {
    const button = event.target.closest('button[data-action]');
    if (!button) return;
    const user = this.users.find(u => u.id === Number(button.dataset.id));

    if (button.dataset.action === 'edit') {
      this.openForm(user);
      return;
    }
    if (!confirm(`Delete ${user.name}? Their contacts, payments and requests are deleted too.`)) return;
    try {
      await UserService.remove(user.id);
      UI.toast('User deleted');
      this.load();
    } catch (error) {
      UI.error(error);
    }
  },
};
