// Categories (admin page): add, rename, delete
Pages.categories = {
  categories: [],

  init() {
    document.getElementById('new-category-button').addEventListener('click', () => this.openForm(null));
    document.getElementById('category-form').addEventListener('submit', (event) => this.save(event));
    document.getElementById('categories-table').addEventListener('click', (event) => this.handleAction(event));
    this.load();
  },

  async load() {
    const body = document.getElementById('categories-table');
    try {
      this.categories = await CategoryService.getAll();
      body.innerHTML = this.categories.length === 0
        ? UI.emptyRow(3, 'No categories yet')
        : this.categories.map(c => `<tr>
            <td>${c.id}</td>
            <td>${UI.escape(c.name)}</td>
            <td class="text-end text-nowrap">
              <button class="btn btn-sm btn-outline-secondary" data-action="edit" data-id="${c.id}" title="Rename"><i class="bi bi-pencil"></i></button>
              <button class="btn btn-sm btn-outline-danger" data-action="delete" data-id="${c.id}" title="Delete"><i class="bi bi-trash"></i></button>
            </td>
          </tr>`).join('');
    } catch (error) {
      UI.error(error);
    }
  },

  openForm(category) {
    const form = document.getElementById('category-form');
    form.reset();
    form.elements.id.value = category ? category.id : '';
    form.elements.name.value = category ? category.name : '';
    document.getElementById('category-modal-title').textContent = category ? 'Rename category' : 'Add category';
    UI.modal('category-modal').show();
  },

  async save(event) {
    event.preventDefault();
    const form = event.target;
    const data = UI.formData(form);
    try {
      await UI.busy(form.querySelector('[type=submit]'), () => data.id
        ? CategoryService.update(data.id, { name: data.name })
        : CategoryService.create({ name: data.name }));
      UI.modal('category-modal').hide();
      UI.toast('Category saved');
      this.load();
    } catch (error) {
      UI.error(error);
    }
  },

  async handleAction(event) {
    const button = event.target.closest('button[data-action]');
    if (!button) return;
    const category = this.categories.find(c => c.id === Number(button.dataset.id));

    if (button.dataset.action === 'edit') {
      this.openForm(category);
      return;
    }
    if (!confirm(`Delete ${category.name}? Payments in this category will have no category.`)) return;
    try {
      await CategoryService.remove(category.id);
      UI.toast('Category deleted');
      this.load();
    } catch (error) {
      UI.error(error);
    }
  },
};
