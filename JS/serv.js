// All service data now lives directly in the HTML as <div class="service-card">
// elements with data-main / data-sub / data-name attributes, and the sub-category
// buttons live in #subTabs with data-main-group / data-sub attributes.
// This script only shows/hides what's already in the DOM — it no longer builds it.

// ─── STATE ──────────────────────────────────────────────────
let currentMain = 'all';
let currentSub = 'all';
let searchTerm = '';
const userDiscountRate = parseInt(document.body.dataset.discountRate || '5', 10);

function formatCurrency(amount) {
    return '₱' + Number(amount).toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ',');
}

function applyDiscount(price) {
    return Number((price * (100 - userDiscountRate) / 100).toFixed(2));
}

function renderDiscountedPrices() {
    document.querySelectorAll('.service-card').forEach(card => {
        const priceEl = card.querySelector('.meta .price');
        if (!priceEl) return;

        const original = parseFloat(card.dataset.price || priceEl.textContent.replace(/[₱,]/g, ''));
        if (!Number.isFinite(original)) return;

        const discounted = applyDiscount(original);
        card.dataset.discountedPrice = discounted.toFixed(2);
        priceEl.innerHTML = `
            <span class="service-price-original">₱${original.toFixed(2)}</span>
            <span class="service-price-current">${formatCurrency(discounted)}</span>
            <span class="service-price-note">${userDiscountRate}% member price</span>
        `;
    });
}

// ─── SHOW/HIDE SUB-CATEGORY TABS ───────────────────────────
function updateSubTabs() {
    const hint = document.getElementById('subTabsHint');
    hint.style.display = currentMain === 'all' ? '' : 'none';

    document.querySelectorAll('#subTabs button[data-main-group]').forEach(btn => {
        const belongsToCurrentMain = btn.dataset.mainGroup === currentMain;
        btn.style.display = belongsToCurrentMain ? '' : 'none';
        btn.classList.toggle('active', belongsToCurrentMain && btn.dataset.sub === currentSub);
    });
}

// ─── SHOW/HIDE SERVICE CARDS ────────────────────────────────
function updateCards() {
    const term = searchTerm.trim().toLowerCase();
    let anyVisible = false;

    document.querySelectorAll('.service-card').forEach(card => {
        const main = card.dataset.main;
        const sub = card.dataset.sub;
        const name = card.dataset.name.toLowerCase();

        let show = true;
        if (currentMain !== 'all' && main !== currentMain) show = false;
        if (currentSub !== 'all' && sub !== currentSub) show = false;
        if (term && !(name.includes(term) || sub.toLowerCase().includes(term))) show = false;

        card.style.display = show ? '' : 'none';
        if (show) anyVisible = true;
    });

    document.getElementById('noResults').style.display = anyVisible ? 'none' : '';
}

// ─── HANDLE MAIN TAB CLICK ──────────────────────────────────
function switchMain(main) {
    currentMain = main;
    currentSub = 'all';

    document.querySelectorAll('.main-tabs button').forEach(b => {
        b.classList.toggle('active', b.dataset.main === main);
    });

    updateSubTabs();
    updateCards();
}

// ─── EVENT LISTENERS: MAIN TABS ─────────────────────────────
document.querySelectorAll('.main-tabs button').forEach(btn => {
    btn.addEventListener('click', () => {
        switchMain(btn.dataset.main);
    });
});

// ─── EVENT LISTENERS: SUB TABS (event delegation) ───────────
document.getElementById('subTabs').addEventListener('click', function(e) {
    const btn = e.target.closest('button[data-sub]');
    if (!btn) return;
    currentSub = btn.dataset.sub;
    updateSubTabs();
    updateCards();
});

// ─── EVENT LISTENERS: BOOK BUTTON (event delegation) ────────
document.getElementById('servicesGrid').addEventListener('click', function(e) {
    const btn = e.target.closest('.book-btn');
    if (!btn) return;

    const card = btn.closest('.service-card');
    const serviceName = card.dataset.name || btn.dataset.name;
    const serviceCategory = card.dataset.main || 'Beauty Services';
    const fallbackPrice = card.dataset.discountedPrice || card.dataset.price || btn.dataset.price;

    console.log('Book button clicked - Service:', serviceName);
    console.log('Book button clicked - Category:', serviceCategory);
    console.log('Book button clicked - Card data-main:', card.dataset.main);

    // Fetch service_id from database and use the DB price for discount calculations
    fetch('../api/get_service_id.php?name=' + encodeURIComponent(serviceName))
        .then(response => response.json())
        .then(data => {
            console.log('Service lookup response:', data);
            if (data.success && data.service) {
                const serviceBasePrice = parseFloat(data.service.price) || parseFloat(fallbackPrice);
                const discountedPrice = applyDiscount(serviceBasePrice);

                const serviceData = {
                    id: data.service.id,
                    name: serviceName,
                    price: discountedPrice.toFixed(2),
                    original_price: serviceBasePrice.toFixed(2),
                    category: serviceCategory
                };
                console.log('Storing selectedService in sessionStorage:', serviceData);
                sessionStorage.setItem('selectedService', JSON.stringify(serviceData));

                // Redirect to therapist selection with category parameter
                const redirectUrl = '../Php/Therapist%20selection.php?category=' + encodeURIComponent(serviceCategory);
                console.log('Redirecting to:', redirectUrl);
                window.location.href = redirectUrl;
            } else {
                alert('Error: Service not found in database. Please try again.');
                console.error('Service lookup failed:', data.error);
            }
        })
        .catch(error => {
            console.error('Error fetching service ID:', error);
            alert('Error loading service. Please try again.');
        });
});

// ─── SEARCH ───────────────────────────────────────────────────
document.getElementById('searchInput').addEventListener('input', function() {
    searchTerm = this.value;
    updateCards();
});

// ─── HAMBURGER MENU ──────────────────────────────────────────
const hamburger = document.getElementById('hamburger');
const navLinks = document.getElementById('nav-links');

hamburger.addEventListener('click', function() {
    this.classList.toggle('active');
    navLinks.classList.toggle('open');
    const isOpen = navLinks.classList.contains('open');
    this.setAttribute('aria-expanded', isOpen);
});

// ─── INIT ──────────────────────────────────────────────────────
updateSubTabs();
renderDiscountedPrices();
updateCards();