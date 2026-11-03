// Profile: the logged-in user edits their own name, email, currency and password
Pages.profile = {
  async init() {
    const form = document.getElementById('profile-form');
    form.addEventListener('submit', (event) => this.save(event));
    Validation.clearOnInput(form);

    try {
      const profile = await ProfileService.get();
      this.show(profile);
    } catch (error) {
      UI.error(error);
    }
  },

  show(profile) {
    const form = document.getElementById('profile-form');
    document.getElementById('profile-title').textContent = profile.name;
    document.getElementById('profile-role').innerHTML = UI.badge(profile.role);
    document.getElementById('profile-balance').textContent = 'Balance: ' + UI.money(profile.balance);
    form.elements.name.value = profile.name;
    form.elements.email.value = profile.email;
    form.elements.currency.value = profile.currency;
    form.elements.password.value = '';
  },

  async save(event) {
    event.preventDefault();
    const form = event.target;
    if (!Validation.check(form)) return;
    const data = UI.formData(form);
    if (data.password === '') delete data.password;
    try {
      const profile = await UI.busy(form.querySelector('[type=submit]'), () => ProfileService.update(data));
      Auth.updateUser(profile);
      document.getElementById('nav-user-name').textContent = profile.name;
      this.show(profile);
      UI.toast('Profile saved');
    } catch (error) {
      UI.error(error);
    }
  },
};
