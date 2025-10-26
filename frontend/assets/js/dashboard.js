// =========================================================
// NUMMO APP — Dashboard Logic (Enhanced Milestone 3)
// Live backend integration for summary, chart, and transactions.
// Includes auto-refresh, graceful error handling, and smoother UI.
// =========================================================

const USER_ID = 1; // Placeholder (Alice). Later: dynamic user from session.
const API_URL = `http://localhost:8080/api/transactions?user_id=${USER_ID}`;

// =========================================================
// MAIN ENTRY POINT
// =========================================================
export async function initDashboard() {
  const app = document.getElementById("app");
  if (app) app.classList.add("fade-in");
  await loadDashboardData();
}

// =========================================================
// FETCH DASHBOARD DATA
// =========================================================
async function loadDashboardData() {
  const tbody = document.getElementById("recent-transactions");
  if (tbody) {
    tbody.innerHTML = `
      <tr><td colspan="6" class="text-center text-muted py-3">
        <div class="spinner-border spinner-border-sm text-primary me-2"></div>
        Loading transactions...
      </td></tr>`;
  }

  try {
    const res = await fetch(API_URL);
    if (!res.ok) throw new Error(`HTTP ${res.status}`);
    const transactions = await res.json();

    if (!Array.isArray(transactions))
      throw new Error("Unexpected response format from API");

    // Update UI components
    updateSummary(transactions);
    renderChart(transactions);
    renderRecentTransactions(transactions);
    renderAIInsight(transactions);


    console.log("✅ Dashboard loaded successfully:", transactions);
  } catch (err) {
    console.error("❌ Dashboard load error:", err);
    if (tbody) {
      tbody.innerHTML = `
        <tr><td colspan="6" class="text-danger text-center py-3">
          ⚠️ Failed to load transactions.
        </td></tr>`;
    }
  }
}

// =========================================================
// UPDATE SUMMARY (Balance / Income / Expenses)
// =========================================================
function updateSummary(transactions) {
  let income = 0;
  let expenses = 0;

  transactions.forEach((tx) => {
    const amount = parseFloat(tx.amount) || 0;
    if (tx.receiver_id === USER_ID) income += amount;
    if (tx.sender_id === USER_ID) expenses += amount;
  });

  const balance = income - expenses;

  setText("balance", `$${balance.toFixed(2)}`);
  setText("income", `$${income.toFixed(2)}`);
  setText("expenses", `$${expenses.toFixed(2)}`);
}

function setText(id, text) {
  const el = document.getElementById(id);
  if (el) el.textContent = text;
}

// =========================================================
// CHART: EXPENSES BY CATEGORY
// Auto-adapts to dark/light mode and reloads dynamically.
// =========================================================
function renderChart(transactions) {
  const canvas = document.getElementById("transactionsChart");
  if (!canvas) return;

  const ctx = canvas.getContext("2d");
  const categories = {};

  transactions.forEach((tx) => {
    if (tx.sender_id === USER_ID && tx.category_name) {
      categories[tx.category_name] =
        (categories[tx.category_name] || 0) + parseFloat(tx.amount);
    }
  });

  const textColor = getComputedStyle(document.body).color;
  const bgColors = [
    "#007bffcc", // Blue
    "#28a745cc", // Green
    "#ffc107cc", // Yellow
    "#dc3545cc", // Red
    "#17a2b8cc", // Teal
    "#6f42c1cc", // Purple
  ];

  if (window._nummoChartInstance) window._nummoChartInstance.destroy();

  window._nummoChartInstance = new Chart(ctx, {
    type: "pie",
    data: {
      labels: Object.keys(categories),
      datasets: [
        {
          data: Object.values(categories),
          backgroundColor: bgColors.slice(0, Object.keys(categories).length),
          borderWidth: 1,
        },
      ],
    },
    options: {
      responsive: true,
      plugins: {
        legend: {
          position: "bottom",
          labels: { color: textColor },
        },
        title: {
          display: true,
          text: "Expenses by Category",
          color: textColor,
        },
      },
    },
  });
}

// =========================================================
// RENDER RECENT TRANSACTIONS TABLE
// =========================================================
function renderRecentTransactions(transactions) {
  const tbody = document.getElementById("recent-transactions");
  if (!tbody) return;

  if (!transactions.length) {
    tbody.innerHTML = `
      <tr><td colspan="6" class="text-center text-muted py-3">
        No transactions yet.
      </td></tr>`;
    return;
  }

  const recent = transactions.slice(0, 6);
  tbody.innerHTML = recent
    .map(
      (tx, i) => `
      <tr>
        <td>${i + 1}</td>
        <td>${tx.sender_name || "Unknown"}</td>
        <td>${tx.receiver_name || "Unknown"}</td>
        <td>$${parseFloat(tx.amount).toFixed(2)}</td>
        <td>${tx.category_name || "-"}</td>
        <td>${new Date(tx.created_at).toLocaleDateString()}</td>
      </tr>
    `
    )
    .join("");
}
// =========================================================
// MOCK AI INSIGHT — Smart summary based on transactions
// =========================================================
function renderAIInsight(transactions) {
  const insightEl = document.getElementById("ai-insight-text");
  if (!insightEl) return;

  if (!transactions.length) {
    insightEl.textContent = "🤖 No transactions yet — start tracking your spending to get insights!";
    return;
  }

  const categories = {};
  let income = 0, expenses = 0;

  transactions.forEach(tx => {
    if (tx.receiver_id === 1) income += parseFloat(tx.amount);
    if (tx.sender_id === 1) {
      expenses += parseFloat(tx.amount);
      if (tx.category_name) {
        categories[tx.category_name] = (categories[tx.category_name] || 0) + parseFloat(tx.amount);
      }
    }
  });

  const topCategory = Object.entries(categories).sort((a, b) => b[1] - a[1])[0];
  const balance = income - expenses;
  let message = "";

  if (!topCategory) {
    message = "📊 You’re balanced so far — no major spending detected this week.";
  } else if (expenses > income) {
    message = `💸 You spent more than you earned this month. Most went to ${topCategory[0]} — maybe tighten it up next week.`;
  } else if (balance > 0) {
    message = `🌱 Great job! You saved $${balance.toFixed(2)} this month. Keep that ${topCategory[0]} spending under control.`;
  } else {
    message = `⚖️ Your spending and income are balanced. Watch out for ${topCategory[0]} creeping up.`;
  }

  // Animate the text change for a "smart" feel
  insightEl.style.opacity = 0;
  setTimeout(() => {
    insightEl.textContent = message;
    insightEl.style.opacity = 1;
  }, 300);
}


// =========================================================
// REFRESH BUTTON — Reloads Dashboard Data
// =========================================================
document.addEventListener("DOMContentLoaded", () => {
  const refreshBtn = document.getElementById("refresh-dashboard");
  if (!refreshBtn) return;

  refreshBtn.addEventListener("click", async () => {
    refreshBtn.disabled = true;
    refreshBtn.innerHTML = `
      <span class="spinner-border spinner-border-sm me-2"></span>
      Refreshing...`;

    await loadDashboardData();

    setTimeout(() => {
      refreshBtn.innerHTML = `<i class="bi bi-arrow-clockwise"></i> Refresh`;
      refreshBtn.disabled = false;
    }, 800);
  });
});
