/* ============================================================
   Admin-Booking Analytics.js
   ============================================================ */

// ── Use PHP-fetched data or fallback to empty arrays ──
const serviceDataRaw = window.analyticsData?.serviceData || [];
const servicePeriods = window.analyticsData?.servicePeriods || {
    weekly: [],
    monthly: serviceDataRaw,
    annual: []
};
const monthlyOverview = window.analyticsData?.monthlyOverview || [];
const inventory = window.analyticsData?.inventory || { inStock: 0, lowStock: 0, critical: 0, total: 0 };
const periods = window.analyticsData?.periods || {
    weekly: { revenue: [], bookings: [] },
    monthly: { revenue: [], bookings: [] },
    annual: { revenue: [], bookings: [] }
};

// ── Transform service data for chart ──
function normalizeServiceData(rawServices) {
    return (Array.isArray(rawServices) ? rawServices : [])
        .filter((service) => Number(service.booking_count) > 0)
        .slice(0, 5)
        .map((service) => ({
            label: String(service.name || 'Unnamed service'),
            value: Number(service.booking_count),
            pct: Number(service.percentage) || 0
        }));
}

const servicePeriodLabels = {
    weekly: 'this week',
    monthly: 'this month',
    annual: 'this year'
};

// ── Transform booking data for period tabs ──
const bookingDataMap = {
    weekly: {
        labels: periods.weekly.bookings.map(d => d.date),
        data: periods.weekly.bookings.map(d => d.count)
    },
    monthly: {
        labels: periods.monthly.bookings.map(d => d.date),
        data: periods.monthly.bookings.map(d => d.count)
    },
    annual: {
        labels: periods.annual.bookings.map(d => d.date),
        data: periods.annual.bookings.map(d => d.count)
    }
};

function createBaseline(values) {
    if (!Array.isArray(values) || values.length === 0) return [];
    const avg = values.reduce((sum, v) => sum + (Number(v) || 0), 0) / values.length;
    return values.map(() => Number(avg.toFixed(2)));
}

const bookingBaselineMap = {
    weekly: createBaseline(bookingDataMap.weekly.data),
    monthly: createBaseline(bookingDataMap.monthly.data),
    annual: createBaseline(bookingDataMap.annual.data)
};

// ── Transform bookings data for period tabs ──
const bookingsDataMap = {
    weekly: {
        labels: periods.weekly.bookings.map(d => d.date),
        data: periods.weekly.bookings.map(d => d.count)
    },
    monthly: {
        labels: periods.monthly.bookings.map(d => d.date),
        data: periods.monthly.bookings.map(d => d.count)
    },
    annual: {
        labels: periods.annual.bookings.map(d => d.date),
        data: periods.annual.bookings.map(d => d.count)
    }
};

// ── Render Staff List ──
// ── Booking Trend Chart (Line) ──
const ctx = document.getElementById('revenueChart').getContext('2d');
const grad = ctx.createLinearGradient(0, 0, 0, 200);
grad.addColorStop(0, 'rgba(233, 30, 122, 0.18)');
grad.addColorStop(1, 'rgba(233, 30, 122, 0.01)');

const revenueChart = new Chart(ctx, {
    type: 'line',
    data: {
        labels: bookingDataMap.weekly.labels,
        datasets: [{
            label: 'Bookings',
            data: bookingDataMap.weekly.data,
            borderColor: '#e91e7a',
            borderWidth: 2.5,
            pointBackgroundColor: '#e91e7a',
            pointBorderColor: '#fff',
            pointBorderWidth: 2,
            pointRadius: 4,
            pointHoverRadius: 6,
            fill: true,
            backgroundColor: grad,
            tension: 0.4,
        }, {
            label: 'Average',
            data: bookingBaselineMap.weekly,
            borderColor: '#9e9e9e',
            borderWidth: 1.5,
            borderDash: [6, 6],
            pointRadius: 0,
            fill: false,
            tension: 0,
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
                    label: ctx => ctx.parsed.y + ' bookings'
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

// ── Services Pie Chart ──
const servicesCanvas = document.getElementById('servicesPieChart');
const servicesEmpty = document.getElementById('servicesEmpty');
const servicesPeriodSubtitle = document.getElementById('servicesPeriodSubtitle');
const serviceTabs = Array.from(document.querySelectorAll('[data-chart="services"]'));
const pieColors = ['#e91e7a', '#f06292', '#f8a4b8', '#fcc9d6', '#fce8ef'];
let servicesPieChart = null;

function renderServicesChart(period) {
    const serviceData = normalizeServiceData(servicePeriods[period]);
    const periodLabel = servicePeriodLabels[period] || 'the selected period';

    if (servicesPeriodSubtitle) {
        servicesPeriodSubtitle.textContent = `Top 5 booked services ${periodLabel}`;
    }

    if (!servicesCanvas) {
        return;
    }

    if (serviceData.length === 0) {
        servicesCanvas.hidden = true;
        if (servicesEmpty) {
            servicesEmpty.textContent = `No service bookings recorded ${periodLabel}.`;
            servicesEmpty.hidden = false;
        }
        return;
    }

    servicesCanvas.hidden = false;
    if (servicesEmpty) {
        servicesEmpty.hidden = true;
    }

    const labels = serviceData.map((service) => `${service.label} (${service.pct}%)`);
    const values = serviceData.map((service) => service.value);

    if (servicesPieChart) {
        servicesPieChart.data.labels = labels;
        servicesPieChart.data.datasets[0].data = values;
        servicesPieChart.update();
        return;
    }

    servicesPieChart = new Chart(servicesCanvas.getContext('2d'), {
        type: 'doughnut',
        data: {
            labels,
            datasets: [{
                data: values,
                backgroundColor: pieColors,
                borderColor: '#fff',
                borderWidth: 2,
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'right',
                    labels: {
                        boxWidth: 12,
                        padding: 10,
                        font: { size: 11, family: 'Inter' },
                        color: '#5a2035',
                        usePointStyle: true,
                        pointStyle: 'circle',
                    }
                },
                tooltip: {
                    backgroundColor: '#fff',
                    titleColor: '#1a0a10',
                    bodyColor: '#5a2035',
                    borderColor: '#f0d0da',
                    borderWidth: 1,
                    padding: 10,
                    callbacks: {
                        label: (context) => `${context.label}: ${context.parsed} bookings`
                    }
                }
            },
            cutout: '60%',
        }
    });
}

serviceTabs.forEach((tab) => {
    tab.addEventListener('click', () => {
        const period = tab.dataset.period;
        if (!servicePeriods[period]) {
            return;
        }

        serviceTabs.forEach((button) => {
            const isSelected = button === tab;
            button.classList.toggle('active', isSelected);
            button.setAttribute('aria-pressed', String(isSelected));
        });
        renderServicesChart(period);
    });
});

renderServicesChart('monthly');

// ── Overview Bar Chart ──
const barCtx = document.getElementById('overviewBarChart').getContext('2d');
const overviewBarChart = new Chart(barCtx, {
    type: 'bar',
    data: {
        labels: bookingsDataMap.weekly.labels,
        datasets: [{
            label: 'Bookings',
            data: bookingsDataMap.weekly.data,
            backgroundColor: 'rgba(233, 30, 122, 0.55)',
            borderColor: '#e91e7a',
            borderWidth: 1.5,
            borderRadius: 4,
            hoverBackgroundColor: 'rgba(233, 30, 122, 0.8)',
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
                    label: ctx => ctx.parsed.y + ' bookings'
                }
            }
        },
        scales: {
            x: {
                grid: { display: false },
                ticks: { color: '#a07080', font: { size: 10, family: 'Inter' } },
                border: { display: false }
            },
            y: {
                grid: { color: '#fce8ef' },
                ticks: { color: '#a07080', font: { size: 10, family: 'Inter' } },
                border: { display: false },
                beginAtZero: true,
            }
        }
    }
});

function updatePeriodTabs(selector, chart, dataMap, onPeriodChange) {
    const tabs = Array.from(document.querySelectorAll(selector));

    tabs.forEach((tab) => {
        tab.addEventListener('click', (event) => {
            event.preventDefault();
            const period = tab.dataset.period;
            const periodData = dataMap[period];

            if (!periodData) {
                return;
            }

            tabs.forEach((button) => {
                const isSelected = button === tab;
                button.classList.toggle('active', isSelected);
                button.setAttribute('aria-pressed', String(isSelected));
            });

            chart.stop();
            chart.data.labels = [...periodData.labels];
            chart.data.datasets[0].data = [...periodData.data];
            onPeriodChange(period);
            chart.update();
        });
    });
}

// Keep the controls independent: changing Booking Trend never changes the
// Bookings Overview period, and vice versa.
updatePeriodTabs('[data-chart="trend"]', revenueChart, bookingDataMap, (period) => {
    revenueChart.data.datasets[1].data = [...bookingBaselineMap[period]];
});

updatePeriodTabs('[data-chart="overview"]', overviewBarChart, bookingsDataMap, () => {});

// ── Button Handlers ──
const addServiceBtn = document.getElementById('addServiceBtn');
const addServiceModal = document.getElementById('addServiceModal');
const closeModal = document.getElementById('closeModal');
const cancelBtn = document.getElementById('cancelBtn');

if (addServiceBtn && addServiceModal) {
    addServiceBtn.addEventListener('click', () => {
        addServiceModal.classList.add('active');
    });

    closeModal.addEventListener('click', () => {
        addServiceModal.classList.remove('active');
    });

    cancelBtn.addEventListener('click', () => {
        addServiceModal.classList.remove('active');
    });

    addServiceModal.addEventListener('click', (e) => {
        if (e.target === addServiceModal) {
            addServiceModal.classList.remove('active');
        }
    });
}

document.getElementById('growthCtaBtn')?.addEventListener('click', () => {
    alert('🚀 Let\'s grow your beauty business!');
});

// ── Inventory counts (from PHP data) ──
if (document.getElementById('statInStock')) {
    document.getElementById('statInStock').textContent = inventory.inStock;
    document.getElementById('statLowStock').textContent = inventory.lowStock;
    document.getElementById('statCritical').textContent = inventory.critical;
    document.getElementById('statTotal').textContent = inventory.total;
}
