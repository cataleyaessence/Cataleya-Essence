/* ══════════════════════════════════════
   Admin-PaymentConfirmation.js
   Cataleya Essence of Beauty
══════════════════════════════════════ */

/* ── Sample Data ── */
const transactions = [
  { client: 'Sarah Johnson',  service: 'Deep Tissue Massage', date: 'Jan 14 10:00am', payment: 'Gcash',       amount: 350,  status: 'completed' },
  { client: 'Maria Santos',   service: 'Swedish Massage',     date: 'Jan 14 11:30am', payment: 'Credit Card', amount: 500,  status: 'completed' },
  { client: 'Anna Cruz',      service: 'Hot Stone Therapy',   date: 'Jan 14 01:00pm', payment: 'Debit Card',  amount: 750,  status: 'completed' },
  { client: 'Liza Reyes',     service: 'Facial Treatment',    date: 'Jan 14 02:30pm', payment: 'Cash',        amount: 400,  status: 'pending'   },
  { client: 'Jenny Villanueva', service: 'Aromatherapy',      date: 'Jan 14 03:00pm', payment: 'Gcash',       amount: 450,  status: 'completed' },
  { client: 'Carla Dizon',    service: 'Body Scrub',          date: 'Jan 14 04:00pm', payment: 'Credit Card', amount: 600,  status: 'failed'    },
  { client: 'Rosa Mendoza',   service: 'Deep Tissue Massage', date: 'Jan 15 09:00am', payment: 'Debit Card',  amount: 350,  status: 'completed' },
  { client: 'Tina Flores',    service: 'Swedish Massage',     date: 'Jan 15 10:30am', payment: 'Gcash',       amount: 500,  status: 'completed' },
  { client: 'Grace Uy',       service: 'Hot Stone Therapy',   date: 'Jan 15 12:00pm', payment: 'Cash',        amount: 750,  status: 'pending'   },
  { client: 'Paula Tan',      service: 'Facial Treatment',    date: 'Jan 15 01:30pm', payment: 'Credit Card', amount: 400,  status: 'completed' },
];

/* ── Payment method breakdown ── */
const methodData = [
  { label: 'Credit Card', count: 156, pct: 80 },
  { label: 'Debit Card',  count: 48,  pct: 50 },
  { label: 'GCash',       count: 24,  pct: 25 },
  { label: 'Other',       count: 48,  pct: 50 },
];

/* ══════════════════════════════════════
   COMPUTED STATS
══════════════════════════════════════ */
function computeStats(data) {
  const completed = data.filter(t => t.status === 'completed');
  const total     = data.reduce((s, t) => s + t.amount, 0);
  const avg       = data.length ? Math.round(total / data.length) : 0;
  const rate      = data.length ? Math.round((completed.length / data.length) * 100) : 0;
  return { total, count: data.length, avg, rate };
}

function renderStats(stats) {
  document.getElementById('statTotalRevenue').textContent  = '₱ ' + stats.total.toLocaleString();
  document.getElementById('statTransactions').textContent  = stats.count;
  document.getElementById('statAvgTransaction').textContent = '₱ ' + stats.avg.toLocaleString();
  document.getElementById('statSuccessRate').textContent   = stats.rate + '%';
}

/* ══════════════════════════════════════
   TRANSACTION TABLE
══════════════════════════════════════ */
function renderTable(data) {
  const tbody = document.getElementById('txnBody');
  if (!data.length) {
    tbody.innerHTML = `<tr><td colspan="6" class="empty-state">No transactions found.</td></tr>`;
    return;
  }
  tbody.innerHTML = data.map(t => `
    <tr>
      <td class="client-name">${t.client}</td>
      <td>${t.service}</td>
      <td>${t.date}</td>
      <td>${t.payment}</td>
      <td>₱${t.amount.toLocaleString()}</td>
      <td><span class="status-badge ${t.status}">${capitalize(t.status)}</span></td>
    </tr>
  `).join('');
}

/* ══════════════════════════════════════
   PAYMENT METHOD BARS
══════════════════════════════════════ */
function renderMethodBars(data) {
  const container = document.getElementById('methodBars');
  container.innerHTML = data.map(m => `
    <div class="method-row">
      <span class="method-label">${m.label}</span>
      <div class="method-bar-wrap">
        <div class="method-bar" style="width:${m.pct}%"></div>
      </div>
      <span class="method-count">${m.count}</span>
    </div>
  `).join('');
}

/* ══════════════════════════════════════
   FILTER
══════════════════════════════════════ */
let activeFilter = 'all';

function applyFilter(filter) {
  activeFilter = filter;
  const filtered = filter === 'all'
    ? transactions
    : transactions.filter(t => t.status === filter);
  renderTable(filtered);
  renderStats(computeStats(filtered));
}

/* ── Filter Modal ── */
function openFilterModal() {
  const existing = document.getElementById('filterModal');
  if (existing) { existing.remove(); return; }

  const modal = document.createElement('div');
  modal.id = 'filterModal';
  modal.className = 'modal-overlay';
  modal.innerHTML = `
    <div class="modal-box">
      <div class="modal-header">
        <span class="modal-title">Filter Transactions</span>
        <button class="modal-close" id="closeFilter">✕</button>
      </div>
      <div class="modal-body">
        <p class="modal-label">Status</p>
        <div class="filter-options">
          <button class="filter-opt ${activeFilter === 'all'       ? 'active' : ''}" data-val="all">All</button>
          <button class="filter-opt ${activeFilter === 'completed' ? 'active' : ''}" data-val="completed">Completed</button>
          <button class="filter-opt ${activeFilter === 'pending'   ? 'active' : ''}" data-val="pending">Pending</button>
          <button class="filter-opt ${activeFilter === 'failed'    ? 'active' : ''}" data-val="failed">Failed</button>
        </div>
      </div>
      <div class="modal-footer">
        <button class="modal-btn cancel" id="cancelFilter">Cancel</button>
        <button class="modal-btn apply" id="applyFilter">Apply</button>
      </div>
    </div>
  `;
  document.body.appendChild(modal);

  let selected = activeFilter;
  modal.querySelectorAll('.filter-opt').forEach(btn => {
    btn.addEventListener('click', () => {
      modal.querySelectorAll('.filter-opt').forEach(b => b.classList.remove('active'));
      btn.classList.add('active');
      selected = btn.dataset.val;
    });
  });

  document.getElementById('applyFilter').addEventListener('click', () => {
    applyFilter(selected);
    modal.remove();
  });
  document.getElementById('cancelFilter').addEventListener('click', () => modal.remove());
  document.getElementById('closeFilter').addEventListener('click',  () => modal.remove());
  modal.addEventListener('click', e => { if (e.target === modal) modal.remove(); });
}

/* ══════════════════════════════════════
   EXPORT CSV
══════════════════════════════════════ */
function exportCSV() {
  const data = activeFilter === 'all'
    ? transactions
    : transactions.filter(t => t.status === activeFilter);

  const headers = ['Client', 'Service', 'Date & Time', 'Payment', 'Amount', 'Status'];
  const rows    = data.map(t =>
    [t.client, t.service, t.date, t.payment, t.amount, capitalize(t.status)]
      .map(v => `"${v}"`).join(',')
  );
  const csv  = [headers.join(','), ...rows].join('\n');
  const blob = new Blob([csv], { type: 'text/csv' });
  const url  = URL.createObjectURL(blob);
  const a    = document.createElement('a');
  a.href = url;
  a.download = 'payment-confirmation.csv';
  a.click();
  URL.revokeObjectURL(url);

  showToast('CSV exported successfully!');
}

/* ══════════════════════════════════════
   ACTION BUTTONS
══════════════════════════════════════ */
function processRefund() {
  showToast('Refund processed successfully!');
}

function generateInvoice() {
  const data = activeFilter === 'all'
    ? transactions
    : transactions.filter(t => t.status === activeFilter);

  const stats = computeStats(data);
  const lines = data.map(t =>
    `${t.client.padEnd(20)} ${t.service.padEnd(22)} ₱${String(t.amount).padStart(6)}`
  ).join('\n');

  const invoice = `
CATALEYA ESSENCE OF BEAUTY
Payment Invoice
${'─'.repeat(52)}
${lines}
${'─'.repeat(52)}
Total Transactions : ${stats.count}
Total Revenue      : ₱${stats.total.toLocaleString()}
Average Amount     : ₱${stats.avg.toLocaleString()}
Success Rate       : ${stats.rate}%
${'─'.repeat(52)}
Generated: ${new Date().toLocaleString()}
  `.trim();

  const blob = new Blob([invoice], { type: 'text/plain' });
  const url  = URL.createObjectURL(blob);
  const a    = document.createElement('a');
  a.href = url;
  a.download = 'invoice.txt';
  a.click();
  URL.revokeObjectURL(url);

  showToast('Invoice generated!');
}

function sendReceipt() {
  showToast('Receipt sent to client!');
}

/* ══════════════════════════════════════
   TOAST NOTIFICATION
══════════════════════════════════════ */
function showToast(msg) {
  const existing = document.querySelector('.toast');
  if (existing) existing.remove();

  const toast = document.createElement('div');
  toast.className = 'toast';
  toast.textContent = msg;
  document.body.appendChild(toast);

  requestAnimationFrame(() => toast.classList.add('show'));
  setTimeout(() => {
    toast.classList.remove('show');
    setTimeout(() => toast.remove(), 300);
  }, 2500);
}

/* ══════════════════════════════════════
   HELPERS
══════════════════════════════════════ */
function capitalize(str) {
  return str.charAt(0).toUpperCase() + str.slice(1);
}

/* ══════════════════════════════════════
   INJECT DYNAMIC STYLES
══════════════════════════════════════ */
function injectStyles() {
  const style = document.createElement('style');
  style.textContent = `
    /* Empty state */
    .empty-state {
      text-align: center; padding: 40px 20px;
      color: var(--text-muted); font-size: 13px;
    }

    /* Toast */
    .toast {
      position: fixed; bottom: 28px; right: 28px;
      background: var(--text-dark); color: #fff;
      padding: 11px 20px; border-radius: 10px;
      font-size: 13px; font-weight: 500;
      box-shadow: 0 4px 16px rgba(0,0,0,0.18);
      opacity: 0; transform: translateY(10px);
      transition: opacity 0.25s, transform 0.25s;
      z-index: 9999;
    }
    .toast.show { opacity: 1; transform: translateY(0); }

    /* Modal overlay */
    .modal-overlay {
      position: fixed; inset: 0;
      background: rgba(26,10,16,0.35);
      display: flex; align-items: center; justify-content: center;
      z-index: 1000;
    }
    .modal-box {
      background: #fff; border-radius: 16px;
      width: 340px; padding: 24px;
      box-shadow: 0 8px 32px rgba(233,30,122,0.12);
    }
    .modal-header {
      display: flex; align-items: center;
      justify-content: space-between; margin-bottom: 18px;
    }
    .modal-title { font-family: 'Playfair Display', serif; font-size: 16px; font-weight: 600; color: var(--text-dark); }
    .modal-close {
      background: none; border: none; font-size: 16px;
      color: var(--text-muted); cursor: pointer; line-height: 1;
    }
    .modal-label { font-size: 12px; font-weight: 600; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.06em; margin-bottom: 10px; }
    .filter-options { display: flex; flex-wrap: wrap; gap: 8px; }
    .filter-opt {
      padding: 6px 16px; border-radius: 20px;
      border: 1.5px solid var(--border);
      background: #fff; color: var(--text-mid);
      font-size: 12.5px; font-weight: 500; cursor: pointer;
      font-family: 'Inter', sans-serif;
      transition: all 0.15s;
    }
    .filter-opt:hover { border-color: var(--pink-soft); color: var(--pink-accent); }
    .filter-opt.active { background: var(--pink-accent); color: #fff; border-color: var(--pink-accent); }
    .modal-footer { display: flex; justify-content: flex-end; gap: 8px; margin-top: 22px; }
    .modal-btn {
      padding: 8px 20px; border-radius: 8px;
      font-size: 13px; font-weight: 500; cursor: pointer;
      font-family: 'Inter', sans-serif; border: none;
      transition: all 0.15s;
    }
    .modal-btn.cancel { background: var(--pink-light); color: var(--text-mid); }
    .modal-btn.cancel:hover { background: var(--pink-mid); }
    .modal-btn.apply  { background: var(--pink-accent); color: #fff; }
    .modal-btn.apply:hover { opacity: 0.88; }
  `;
  document.head.appendChild(style);
}

/* ══════════════════════════════════════
   INIT
══════════════════════════════════════ */
document.addEventListener('DOMContentLoaded', () => {
  injectStyles();

  // Render initial data
  const stats = computeStats(transactions);
  renderStats(stats);
  renderTable(transactions);
  renderMethodBars(methodData);

  // Header buttons
  document.getElementById('btnFilter')?.addEventListener('click', openFilterModal);
  document.getElementById('btnExport')?.addEventListener('click', exportCSV);

  // Action buttons
  document.getElementById('btnProcessRefund')?.addEventListener('click', processRefund);
  document.getElementById('btnGenerateInvoice')?.addEventListener('click', generateInvoice);
  document.getElementById('btnSendReceipt')?.addEventListener('click', sendReceipt);
});