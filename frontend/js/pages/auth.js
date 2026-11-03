// Login and register pages
Pages.login = {
  init() {
    const form = document.getElementById('login-form');
    Validation.clearOnInput(form);
    form.addEventListener('submit', async (event) => {
      event.preventDefault();
      if (!Validation.check(form)) return;
      const data = UI.formData(form);
      try {
        const user = await UI.busy(form.querySelector('[type=submit]'), () => AuthService.login(data.email, data.password));
        UI.toast(`Welcome back, ${user.name}!`);
        location.hash = '#dashboard';
      } catch (error) {
        UI.error(error);
      }
    });
  },
};

Pages.register = {
  init() {
    const form = document.getElementById('register-form');
    Validation.clearOnInput(form);
    form.addEventListener('submit', async (event) => {
      event.preventDefault();
      if (!Validation.check(form)) return;
      try {
        const user = await UI.busy(form.querySelector('[type=submit]'), () => AuthService.register(UI.formData(form)));
        UI.toast(`Welcome to Nummo, ${user.name}!`);
        location.hash = '#dashboard';
      } catch (error) {
        UI.error(error);
      }
    });
  },
};
