// Dashboard: balance, this month's totals, spending chart, recent payments, currency converter
Pages.dashboard = {
  async init() {
    const user = Auth.getUser();
    document.getElementById('dashboard-name').textContent = user.name;
    this.setupConverter(user.currency);

    try {
      const [profile, transactions, spending] = await Promise.all([
        ProfileService.get(),
        TransactionService.getAll(),
        ProfileService.spending(),
      ]);
      Auth.updateUser(profile);
      this.showStats(profile, transactions);
      this.showChart(spending);
      this.showRecent(profile, transactions.slice(0, 5));
    } catch (error) {
      UI.error(error);
    }
  },

  showStats(profile, transactions) {
    document.getElementById('stat-balance').textContent = UI.money(profile.balance);

    const thisMonth = new Date().toISOString().slice(0, 7); // "2026-11"
    let received = 0;
    let spent = 0;
    transactions
      .filter(t => t.status === 'completed' && t.created_at.startsWith(thisMonth))
      .forEach(t => {
        if (t.receiver_id === profile.id) received += Number(t.amount);
        if (t.sender_id === profile.id) spent += Number(t.amount);
      });
    document.getElementById('stat-received').textContent = UI.money(received);
    document.getElementById('stat-spent').textContent = UI.money(spent);

    // Balance in the user's display currency, using the external API
    if (profile.currency !== 'EUR' && Number(profile.balance) > 0) {
      RateService.convert(profile.balance, 'EUR', profile.currency)
        .then(result => {
          document.getElementById('stat-balance-converted').textContent = '≈ ' + UI.money(result.result, profile.currency);
        })
        .catch(() => {}); // the balance in EUR is still shown if the rate API is down
    }
  },

  showChart(spending) {
    if (spending.length === 0) {
      document.getElementById('spending-empty').classList.remove('d-none');
      return;
    }
    new Chart(document.getElementById('spending-chart'), {
      type: 'doughnut',
      data: {
        labels: spending.map(row => row.category),
        datasets: [{
          data: spending.map(row => Number(row.total)),
          backgroundColor: ['#0d6efd', '#20c997', '#ffc107', '#dc3545', '#6f42c1', '#fd7e14', '#6c757d'],
        }],
      },
      options: { maintainAspectRatio: false, plugins: { legend: { position: 'bottom' } } },
    });
  },

  showRecent(profile, transactions) {
    const body = document.getElementById('recent-transactions');
    if (transactions.length === 0) {
      body.innerHTML = UI.emptyRow(4, 'No transactions yet');
      return;
    }
    body.innerHTML = transactions.map(t => {
      const outgoing = t.sender_id === profile.id;
      const other = outgoing ? (t.receiver_name || t.merchant_name) : t.sender_name;
      return `<tr>
        <td>${UI.date(t.created_at)}</td>
        <td>${UI.escape(other)} ${t.status !== 'completed' ? UI.badge(t.status) : ''}</td>
        <td>${UI.escape(t.category_name || '—')}</td>
        <td class="text-end ${outgoing ? 'amount-out' : 'amount-in'}">${outgoing ? '−' : '+'}${UI.money(t.amount)}</td>
      </tr>`;
    }).join('');
  },

  setupConverter(userCurrency) {
    const form = document.getElementById('converter-form');
    const result = document.getElementById('converter-result');
    const date = document.getElementById('converter-date');
    if (userCurrency !== 'EUR') {
      form.elements.to.value = userCurrency;
    }

    form.addEventListener('submit', async (event) => {
      event.preventDefault();
      result.textContent = 'Converting…';
      date.textContent = '';
      try {
        const data = await RateService.convert(form.elements.amount.value, form.elements.from.value, form.elements.to.value);
        result.textContent = `${data.amount.toFixed(2)} ${data.from} = ${data.result.toFixed(2)} ${data.to}`;
        date.textContent = `1 ${data.from} = ${data.rate} ${data.to} · rate date ${data.date}`;
      } catch (error) {
        // e.g. 503 when the exchange rate API is down — the rest of the page keeps working
        result.textContent = error.message;
      }
    });
    form.requestSubmit();
  },
};
