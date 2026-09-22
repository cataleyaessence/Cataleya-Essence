/* ══════════════════════════════════════════
   Admin-SalesInventory.js
   Cataleya Essence of Beauty
══════════════════════════════════════════ */

/* ══════════════════════
   INVENTORY DATA
══════════════════════ */
const inventoryItems = [
  { name: "Lavender Essential Oil",    category: "Aromatherapy",    qty: 45,  minStock: 10, price: 320.00,  supplier: "Nature's Best PH",  status: "in-stock"  },
  { name: "Hot Stone Set (12pcs)",     category: "Massage Tools",   qty: 8,   minStock: 5,  price: 1850.00, supplier: "Zen Supplies Co.",   status: "low-stock" },
  { name: "Deep Cleansing Facial Gel", category: "Skincare",        qty: 3,   minStock: 8,  price: 595.00,  supplier: "GlowPro PH",         status: "critical"  },
  { name: "Swedish Massage Oil 1L",    category: "Massage Oils",    qty: 22,  minStock: 6,  price: 480.00,  supplier: "Wellness Depot",     status: "in-stock"  },
  { name: "Disposable Face Towels",    category: "Linens",          qty: 200, minStock: 50, price: 18.50,   supplier: "CleanCare Supplies", status: "in-stock"  },
  { name: "Vitamin C Serum 30ml",      category: "Skincare",        qty: 6,   minStock: 10, price: 750.00,  supplier: "GlowPro PH",         status: "low-stock" },
  { name: "Foot Spa Salt (5kg)",       category: "Foot Care",       qty: 14,  minStock: 4,  price: 290.00,  supplier: "Nature's Best PH",   status: "in-stock"  },
  { name: "Collagen Eye Patches",      category: "Skincare",        qty: 2,   minStock: 15, price: 125.00,  supplier: "BeautyEdge Supply",  status: "critical"  },
  { name: "Eucalyptus Oil 500ml",      category: "Aromatherapy",    qty: 18,  minStock: 5,  price: 265.00,  supplier: "Wellness Depot",     status: "in-stock"  },
  { name: "Exfoliating Body Scrub",    category: "Body Treatments", qty: 9,   minStock: 8,  price: 420.00,  supplier: "Nature's Best PH",   status: "low-stock" },
];

/* ══════════════════════
   REVENUE CHART DATA
══════════════════════ */
const dataMap = {
  30: {
    labels: ['June', 'July', 'Aug'],
    data:   [28000, 37500, 45800]
  },
  60: {
    labels: ['March', 'April', 'May', 'June', 'July', 'Aug'],
    data:   [18200, 22400, 31500, 28000, 37500, 45800]
  },
  90: {
    labels: ['Dec', 'Jan', 'Feb', 'March', 'April', 'May', 'June', 'July', 'Aug'],
    data:   [12000, 15500, 19800, 18200, 22400, 31500, 28000, 37500, 45800]
  }
};

/* ══════════════════════
   INVENTORY STATUS COUNTS
══════════════════════ */
function updateStatusCounts(items) {
  const counts = items.reduce((acc, item) => {
    acc[item.status] = (acc[item.status] || 0) + 1;
    return acc;
  }, {});

  document.getElementById('statInStock').textContent  = counts['in-stock']  || 0;
  document.getElementById('statLowStock').textContent = counts['low-stock'] || 0;
  document.getElementById('statCritical').textContent = counts['critical']  || 0;
  document.getElementById('statTotal').textContent    = inventoryItems.length;
}

/* ══════════════════════
   RENDER TABLE
══════════════════════ */
function renderTable(items) {
  const tbody = document.getElementById('inventoryBody');

  if (!items.length) {
    tbody.innerHTML = `<tr><td colspan="8" class="empty-state">No inventory items found.</td></tr>`;
    return;
  }

  tbody.innerHTML = items.map((item, index) => {
    const badgeLabel = item.status === 'in-stock'
      ? 'In Stock'
      : item.status === 'low-stock'
      ? 'Low Stock'
      : 'Critical';

    const price = '₱' + item.price.toLocaleString('en-PH', { minimumFractionDigits: 2 });

    return `
      <tr data-index="${index}">
        <td class="item-name">${item.name}</td>
        <td>${item.category}</td>
        <td>${item.qty}</td>
        <td>${item.minStock}</td>
        <td>${price}</td>
        <td>${item.supplier}</td>
        <td><span class="badge ${item.status}">${badgeLabel}</span></td>
        <td>
          <div class="action-btns">
            <button class="act-btn edit-btn" data-index="${index}">Edit</button>
            <button class="act-btn del delete-btn" data-index="${index}">Delete</button>
          </div>
        </td>
      </tr>`;
  }).join('');

  /* Attach row action listeners after rendering */
  attachRowActions();
}

/* ══════════════════════
   ROW ACTION HANDLERS
══════════════════════ */
function attachRowActions() {
  /* Edit buttons */
  document.querySelectorAll('.edit-btn').forEach(btn => {
    btn.addEventListener('click', () => {
      const idx = parseInt(btn.dataset.index);
      const item = inventoryItems[idx];
      const newQty = prompt(`Edit quantity for "${item.name}":`, item.qty);
      if (newQty === null) return;
      const parsed = parseInt(newQty);
      if (isNaN(parsed) || parsed < 0) {
        alert('Please enter a valid quantity.');
        return;
      }
      inventoryItems[idx].qty = parsed;
      /* Auto-update status based on new quantity */
      if (parsed <= 0 || parsed < item.minStock * 0.5) {
        inventoryItems[idx].status = 'critical';
      } else if (parsed < item.minStock) {
        inventoryItems[idx].status = 'low-stock';
      } else {
        inventoryItems[idx].status = 'in-stock';
      }
      renderTable(inventoryItems);
      updateStatusCounts(inventoryItems);
    });
  });

  /* Delete buttons */
  document.querySelectorAll('.delete-btn').forEach(btn => {
    btn.addEventListener('click', () => {
      const idx = parseInt(btn.dataset.index);
      const item = inventoryItems[idx];
      if (!confirm(`Delete "${item.name}" from inventory?`)) return;
      inventoryItems.splice(idx, 1);
      renderTable(inventoryItems);
      updateStatusCounts(inventoryItems);
    });
  });
}

/* ══════════════════════
   SEARCH FILTER
══════════════════════ */
document.getElementById('searchBtn').addEventListener('click', () => {
  const q = prompt('Search item name:');
  if (q === null) return;
  const filtered = inventoryItems.filter(i =>
    i.name.toLowerCase().includes(q.toLowerCase())
  );
  renderTable(filtered);
});

/* ══════════════════════
   CATEGORY FILTER
══════════════════════ */
const categories = ['All Categories', ...new Set(inventoryItems.map(i => i.category))];
let catIndex = 0;

document.getElementById('categoryBtn').addEventListener('click', () => {
  catIndex = (catIndex + 1) % categories.length;
  const cat = categories[catIndex];
  document.getElementById('categoryBtn').textContent = cat;
  const filtered = cat === 'All Categories'
    ? inventoryItems
    : inventoryItems.filter(i => i.category === cat);
  renderTable(filtered);
});

/* ══════════════════════
   ADD SERVICE BUTTON
══════════════════════ */
document.getElementById('addServiceBtn').addEventListener('click', () => {
  alert('Add Service — connect your modal or form here.');
});

/* ══════════════════════
   REVENUE CHART (Chart.js)
══════════════════════ */
const ctx = document.getElementById('revenueChart').getContext('2d');

const gradient = ctx.createLinearGradient(0, 0, 0, 200);
gradient.addColorStop(0, 'rgba(233, 30, 122, 0.18)');
gradient.addColorStop(1, 'rgba(233, 30, 122, 0.01)');

const chart = new Chart(ctx, {
  type: 'line',
  data: {
    labels: dataMap[60].labels,
    datasets: [{
      label: 'Revenue',
      data: dataMap[60].data,
      borderColor: '#e91e7a',
      borderWidth: 2,
      pointBackgroundColor: '#e91e7a',
      pointBorderColor: '#fff',
      pointBorderWidth: 2,
      pointRadius: 4,
      pointHoverRadius: 6,
      fill: true,
      backgroundColor: gradient,
      tension: 0.4,
    }]
  },
  options: {
    responsive: true,
    maintainAspectRatio: false,
    plugins: {
      legend: { display: false },
      tooltip: {
        backgroundColor: '#fff',
        titleColor: '#1a0a10',
        bodyColor: '#5a2035',
        borderColor: '#f0d0da',
        borderWidth: 1,
        padding: 10,
        callbacks: {
          label: ctx => '₱' + ctx.parsed.y.toLocaleString()
        }
      }
    },
    scales: {
      x: {
        grid: { color: '#fce8ef', drawBorder: false },
        ticks: { color: '#a07080', font: { size: 11, family: 'Inter' } },
        border: { display: false }
      },
      y: {
        display: false,
        grid: { display: false }
      }
    }
  }
});

/* ── Period Tab Switching ── */
document.querySelectorAll('.period-tab').forEach(btn => {
  btn.addEventListener('click', () => {
    document.querySelectorAll('.period-tab').forEach(b => b.classList.remove('active'));
    btn.classList.add('active');
    const period = btn.dataset.period;
    chart.data.labels = dataMap[period].labels;
    chart.data.datasets[0].data = dataMap[period].data;
    chart.update();
  });
});

/* ══════════════════════
   INIT
══════════════════════ */
updateStatusCounts(inventoryItems);
renderTable(inventoryItems);