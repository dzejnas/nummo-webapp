// Client-side form validation with Bootstrap styles.
// Rules come from the HTML attributes on each field: required, type="email", minlength,
// maxlength, min, max and step. The backend validates everything again (server-side).
//
// Usage in a page:  if (!Validation.check(form)) return;
const Validation = {
  // Returns true if every field is valid; otherwise marks the invalid fields and returns false
  check(form) {
    let valid = true;
    form.querySelectorAll('input, select, textarea').forEach(field => {
      if (field.type === 'hidden' || field.disabled || field.closest('.d-none')) return;
      const message = this.messageFor(field);
      this.show(field, message);
      if (message) valid = false;
    });

    // Put the cursor in the first invalid field
    const firstInvalid = form.querySelector('.is-invalid');
    if (firstInvalid) firstInvalid.focus();
    return valid;
  },

  // Error message for one field, or '' when it is valid
  messageFor(field) {
    const value = field.value.trim();

    if (field.required && value === '') {
      return field.tagName === 'SELECT' ? 'Please choose an option.' : 'This field is required.';
    }
    if (value === '') {
      return ''; // optional and empty is fine
    }
    if (field.type === 'email' && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(value)) {
      return 'Enter a valid email address, e.g. name@example.com.';
    }
    if (field.minLength > 0 && value.length < field.minLength) {
      return `Use at least ${field.minLength} characters.`;
    }
    if (field.maxLength > 0 && value.length > field.maxLength) {
      return `Use at most ${field.maxLength} characters.`;
    }
    if (field.type === 'number') {
      const number = Number(value);
      if (Number.isNaN(number)) return 'Enter a number.';
      if (field.min !== '' && number < Number(field.min)) {
        return Number(field.min) === 0.01 ? 'The amount must be greater than 0.' : `The minimum is ${field.min}.`;
      }
      if (field.max !== '' && number > Number(field.max)) return `The maximum is ${field.max}.`;
      if (field.step === '0.01' && !/^\d+(\.\d{1,2})?$/.test(value)) return 'Use at most 2 decimals.';
    }
    return '';
  },

  show(field, message) {
    field.classList.toggle('is-invalid', message !== '');
    const feedback = field.parentElement.querySelector('.invalid-feedback');
    if (feedback) feedback.textContent = message;
  },

  // Removes the red marking as soon as the user edits a field
  clearOnInput(form) {
    form.addEventListener('input', (event) => this.show(event.target, ''));
    form.addEventListener('change', (event) => this.show(event.target, ''));
  },

  reset(form) {
    form.reset();
    form.querySelectorAll('.is-invalid').forEach(field => field.classList.remove('is-invalid'));
  },
};
