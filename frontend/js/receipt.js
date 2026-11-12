// Receipt dialog for one transaction (#receipt-modal in index.html).
// Shown after a payment and from the Transactions and Requests pages. Receipt.open(id) loads the
// transaction from the API, which only allows the sender, the receiver or an admin (403 otherwise).
const Receipt = {
  async open(id) {
    try {
      this.show(await TransactionService.get(id));
    } catch (error) {
      UI.error(error);
    }
  },

  show(t) {
    const to = t.merchant_name ? `${t.merchant_name} (merchant)` : t.receiver_name;
    const rows = [
      ['Reference', t.reference],
      ['From', t.sender_name],
      ['To', to],
      ['Date', new Date(t.created_at.replace(' ', 'T')).toLocaleString('en-GB')],
      ['Category', t.category_name || '—'],
      ['Note', t.note || '—'],
    ];
    document.getElementById('receipt-amount').textContent = UI.money(t.amount, t.currency);
    document.getElementById('receipt-status').innerHTML = 'Status: ' + UI.badge(t.status);
    document.getElementById('receipt-details').innerHTML = rows
      .map(([label, value]) => `<dt>${label}</dt><dd>${UI.escape(value)}</dd>`).join('');

    // Bootstrap only restores focus for dialogs opened with data attributes, so do it here
    const opener = document.activeElement;
    const element = document.getElementById('receipt-modal');
    element.addEventListener('hidden.bs.modal', () => {
      // After a payment the opener is inside the closed payment dialog (hidden), so use a visible button
      const visible = opener && opener.offsetParent !== null;
      (visible ? opener : document.querySelector('#new-transaction-button, #new-request-button'))?.focus();
    }, { once: true });
    UI.modal('receipt-modal').show();
  },
};
