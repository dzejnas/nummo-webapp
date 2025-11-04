// =========================================================
// NUMMO APP — Transactions View (Milestone 3+)
// Fetches real data from backend, supports dynamic filters
// =========================================================

// ------------------------------
// Fetch data from API
// ------------------------------
async function fetchTransactions(userId = 1, category = "All", status = "All") {
  try {
    const params = new URLSearchParams({ user_id: userId });
    if (status && status !== "All") params.append("status", status);
    if (category && category !== "All") params.append("category", category);

    const response = await fetch(`http://localhost:8080/api/transactions?${params}`);
    if (!response.ok) throw new Error(`HTTP ${response.status}`);

    const data = await response.json();
    console.log("✅ Transactions fetched:", data);
    return data;
  } catch (error) {
    console.error("❌ Error fetching transactions:", error);
    return [];
  }
}

// ------------------------------
// Render Transactions Table
// ------------------------------
export async function renderTransactions(userId = 1, category = "All", status = "All") {
  const root = document.getElementById("transactions-list");
  if (!root) return;

  root.innerHTML = `
    <div class="text-center my-4 text-muted">
      <div class="spinner-border text-primary" role="status"></div>
      <p class="mt-2">Loading transactions...</p>
    </div>
  `;

  const transactions = await fetchTransactions(userId, category, status);
  root.innerHTML = "";

  if (transactions.length === 0) {
    root.innerHTML = `
      <div class="alert alert-light text-center mt-4" role="alert">
        No transactions found for your filters.
      </div>
    `;
    return;
  }

  // Build table
  const table = document.createElement("table");
  table.className = "table table-striped align-middle shadow-sm";
  table.innerHTML = `
    <thead class="table-primary">
      <tr>
        <th>#</th>
        <th>From</th>
        <th>To</th>
        <th>Amount</th>
        <th>Category</th>
        <th>Status</th>
        <th>Date</th>
      </tr>
    </thead>
  `;

  const tbody = document.createElement("tbody");

  transactions.forEach((tx) => {
    const isExpense = tx.sender_name === "Alice"; // temporary logged user
    const row = document.createElement("tr");
    row.innerHTML = `
      <td>${tx.id}</td>
      <td>${tx.sender_name}</td>
      <td>${tx.receiver_name}</td>
      <td class="${isExpense ? "text-danger" : "text-success"} fw-semibold">
        ${isExpense ? "-" : "+"}$${parseFloat(tx.amount).toFixed(2)}
      </td>
      <td>${tx.category_name ?? "—"}</td>
      <td><span class="badge bg-${tx.status === "completed" ? "success" : "secondary"}">${tx.status}</span></td>
      <td>${new Date(tx.created_at).toLocaleDateString()}</td>
    `;
    tbody.appendChild(row);
  });

  table.appendChild(tbody);
  root.appendChild(table);

  renderSummary(root, transactions);
}

// ------------------------------
// Render Summary Cards
// ------------------------------
function renderSummary(root, transactions) {
  const income = transactions
    .filter((t) => t.receiver_name === "Alice" && t.status === "completed")
    .reduce((sum, t) => sum + parseFloat(t.amount), 0);

  const expenses = transactions
    .filter((t) => t.sender_name === "Alice")
    .reduce((sum, t) => sum + parseFloat(t.amount), 0);

  const balance = income - expenses;

  const summary = document.createElement("div");
  summary.className = "mt-4 d-flex justify-content-around text-center flex-wrap gap-3";

  summary.innerHTML = `
    <div class="card text-bg-success shadow-sm px-4 py-3" style="min-width: 180px;">
      <h6>Income</h6>
      <h4>$${income.toFixed(2)}</h4>
    </div>
    <div class="card text-bg-danger shadow-sm px-4 py-3" style="min-width: 180px;">
      <h6>Expenses</h6>
      <h4>$${expenses.toFixed(2)}</h4>
    </div>
    <div class="card text-bg-primary shadow-sm px-4 py-3" style="min-width: 180px;">
      <h6>Net Balance</h6>
      <h4>$${balance.toFixed(2)}</h4>
    </div>
  `;

  root.appendChild(summary);
}

// ------------------------------
// Filter Button Event
// ------------------------------
document.addEventListener("DOMContentLoaded", () => {
  const btn = document.getElementById("apply-filters");
  if (btn) {
    btn.addEventListener("click", async () => {
      const category = document.getElementById("filter-category")?.value || "All";
      const status = document.getElementById("filter-status")?.value || "All";
      console.log("🔍 Applying filters:", { category, status });
      await renderTransactions(1, category, status);
    });
  }
});

// Expose for router
window.renderTransactions = renderTransactions;

// =========================================================
// Add New Transaction — POST to backend
// =========================================================
document.addEventListener("DOMContentLoaded", () => {
  const form = document.getElementById("add-transaction-form");
  if (form) {
    form.addEventListener("submit", async (e) => {
      e.preventDefault();

      const senderId = 1; // temporary fixed user (Alice)
      const receiverId = document.getElementById("receiver_id").value;
      const amount = document.getElementById("amount").value;
      const categoryId = document.getElementById("category").value;
      const note = document.getElementById("note").value;

      const data = {
        sender_id: senderId,
        receiver_id: receiverId,
        category_id: categoryId,
        amount: amount,
        note: note,
        status: "completed",
      };

      try {
        const res = await fetch("http://localhost:8080/api/transactions", {
          method: "POST",
          headers: { "Content-Type": "application/json" },
          body: JSON.stringify(data),
        });

        const result = await res.json();
        if (res.ok) {
          alert("✅ Transaction added successfully!");
          form.reset();
          renderTransactions(); // refresh table
        } else {
          alert("❌ Failed to add transaction: " + (result.error || "Unknown error"));
        }
      } catch (err) {
        console.error("❌ Error submitting transaction:", err);
        alert("⚠️ Error submitting transaction. Check console for details.");
      }
    });
  }
});

