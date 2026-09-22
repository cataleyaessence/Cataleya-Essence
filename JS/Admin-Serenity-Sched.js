// ════════════════════════════════════════
//  Cataleya Essence – Realtime Calendar JS
// ════════════════════════════════════════

// ── Data from PHP ──
const calendarData = window.calendarData || {
    currentDate: new Date().toISOString().split('T')[0],
    displayDate: new Date().toLocaleDateString('en-US', { month: 'long', day: 'numeric', year: 'numeric' }),
    bookings: [],
    timeSlots: [],
    staff: [],
    services: [],
    users: [],
    previousBookings: []
};

// ── Transform bookings ──
let appointments = calendarData.bookings.map(booking => ({
    time: normalizeTimeKey(booking.booking_time ? booking.booking_time.substring(0, 5) : '00:00'),
    duration: booking.duration_minutes || 30,
    label: `${booking.customer_name || 'Unknown'} – ${booking.service_name || 'Service'}`,
    status: booking.status || 'confirmed',
    id: booking.id,
    staffName: booking.staff_name,
    staffImage: booking.staff_image,
    notes: booking.notes,
    totalAmount: booking.total_amount || 0,
    paidAmount: booking.paid_amount || 0
}));

// ── State ──
let currentDate = parseLocalDate(calendarData.currentDate);
let currentMonth = new Date(currentDate.getFullYear(), currentDate.getMonth(), 1);
let refreshTimer = null;

// ── DOM refs ──
const grid = document.getElementById('timeGrid');
const calDateEl = document.getElementById('calDate');
const monthLabelEl = document.getElementById('monthLabel');
const datePicker = document.getElementById('datePicker');
const previousBookingsContainer = document.getElementById('previousBookingsList');

// ── Helpers ──
function pad(n) { return String(n).padStart(2, '0'); }

function timeKey(h, m) {
    return `${pad(h)}:${m === 0 ? '00' : '30'}`;
}

function localDateString(date) {
    return `${date.getFullYear()}-${pad(date.getMonth() + 1)}-${pad(date.getDate())}`;
}

function parseLocalDate(dateString) {
    const parts = dateString.split('-').map(Number);
    if (parts.length !== 3 || parts.some(isNaN)) {
        return new Date();
    }
    return new Date(parts[0], parts[1] - 1, parts[2]);
}

function normalizeTimeKey(timeString) {
    if (!timeString) return '00:00';
    const match = timeString.match(/^(\d{1,2}):(\d{2})/);
    if (!match) return '00:00';
    const hours = pad(parseInt(match[1], 10));
    const minutes = pad(parseInt(match[2], 10));
    return `${hours}:${minutes}`;
}

function formatTime(h, m) {
    const period = h < 12 ? 'am' : 'pm';
    const hour = h > 12 ? h - 12 : h === 0 ? 12 : h;
    const min = m === 0 ? '00' : '30';
    return `${hour}:${min} ${period}`;
}

function formatDate(date) {
    return date.toLocaleDateString('en-US', { month: 'long', day: 'numeric', year: 'numeric' });
}

function formatMonth(date) {
    return date.toLocaleDateString('en-US', { month: 'long', year: 'numeric' });
}

function generateTimeSlots() {
    if (calendarData.timeSlots && calendarData.timeSlots.length > 0) {
        return calendarData.timeSlots.map(slot => {
            const [h, m] = slot.slot_time.split(':').map(Number);
            return { h, m };
        });
    }
    // Fallback: 8:00 AM – 7:30 PM 30-min increments
    const slots = [];
    for (let h = 8; h <= 19; h++) {
        slots.push({ h, m: 0 });
        if (h < 19) slots.push({ h, m: 30 });
    }
    return slots;
}

// ── Render grid ──
function buildGrid() {
    const slots = generateTimeSlots();
    grid.innerHTML = '';

    if (slots.length === 0) {
        grid.innerHTML = `<div class="empty-state"><i class="fas fa-clock"></i><span>No time slots configured</span></div>`;
        return;
    }

    // Build lookup: time => array of appointments
    const apptMap = {};
    appointments.forEach(appt => {
        const key = appt.time;
        if (!apptMap[key]) apptMap[key] = [];
        apptMap[key].push(appt);
    });

    slots.forEach(({ h, m }) => {
        const key = timeKey(h, m);
        const row = document.createElement('div');
        row.className = 'time-row';

        const label = document.createElement('div');
        label.className = 'time-label';
        label.textContent = formatTime(h, m);

        const slot = document.createElement('div');
        slot.className = 'time-slot';

        const appts = apptMap[key] || [];
        appts.forEach((appt, idx) => {
            const block = document.createElement('div');
            block.className = `appt-block ${appt.status}`;
            const rowHeight = 32;
            const durationRows = Math.max(1, Math.round(appt.duration / 30));
            const heightPx = durationRows * rowHeight - 6;
            block.style.height = `${heightPx}px`;
            block.style.top = '2px';
            block.style.zIndex = 2 + idx;

            const timeSpan = document.createElement('span');
            timeSpan.className = 'appt-time';
            timeSpan.textContent = appt.time;
            block.appendChild(timeSpan);
            block.appendChild(document.createTextNode(appt.label));

            const downText = appt.paidAmount > 0 ? ` · ₱${appt.paidAmount.toLocaleString()}` : '';
            block.title = `${appt.label} · ${appt.status} · ${appt.time}${downText}`;
            block.dataset.apptId = appt.id;

            block.addEventListener('click', (e) => {
                e.stopPropagation();
                showToast(`📋 ${appt.label} — ${appt.status} (${appt.time})`);
            });

            slot.appendChild(block);
        });

        row.appendChild(label);
        row.appendChild(slot);
        grid.appendChild(row);
    });

    if (appointments.length === 0) {
        const emptyHint = document.createElement('div');
        emptyHint.className = 'empty-state';
        emptyHint.style.padding = '16px 0 8px 0';
        emptyHint.style.fontSize = '12px';
        emptyHint.innerHTML = `<i class="fas fa-calendar-plus" style="font-size:20px;"></i> No appointments for this day`;
        grid.appendChild(emptyHint);
    }
}

function renderPreviousBookings() {
    if (!previousBookingsContainer) return;

    const bookings = Array.isArray(calendarData.previousBookings) ? calendarData.previousBookings : [];
    if (bookings.length === 0) {
        previousBookingsContainer.innerHTML = '<div class="empty-previous-bookings">No previous bookings found yet.</div>';
        return;
    }

    previousBookingsContainer.innerHTML = bookings.map(booking => {
        const status = booking.status ? booking.status.toLowerCase() : 'confirmed';
        const dateLabel = booking.booking_date ? new Date(booking.booking_date).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' }) : 'Unknown date';
        const timeLabel = booking.booking_time ? booking.booking_time.substring(0, 5) : '--:--';
        const amount = booking.total_amount ? Number(booking.total_amount).toLocaleString('en-US', { maximumFractionDigits: 0 }) : '0';
        const customerName = booking.customer_name || 'Unknown customer';
        const staffName = booking.staff_name ? `Therapist: ${booking.staff_name}` : '';

        return `
            <div class="previous-booking-item">
                <div class="booking-title">
                    <span class="booking-name">${customerName}</span>
                    <span class="booking-status ${status}">${status.charAt(0).toUpperCase() + status.slice(1)}</span>
                </div>
                <div class="booking-meta">
                    <span>${booking.service_name || 'Service'}</span>
                    <span>${dateLabel} • ${timeLabel}</span>
                    <span>₱${amount}</span>
                </div>
                ${status === 'cancelled' ? `<div class="booking-who">Cancelled booking for <strong>${customerName}</strong>${staffName ? ` • ${staffName}` : ''}</div>` : staffName ? `<div class="booking-who">${staffName}</div>` : ''}
            </div>`;
    }).join('');
}

// ── Update UI for current date ──
function updateDateDisplay() {
    calDateEl.textContent = formatDate(currentDate);
    monthLabelEl.textContent = formatMonth(currentDate);
    datePicker.value = localDateString(currentDate);
}

// ── Navigation functions ──
function navigateToDate(date) {
    const dateStr = localDateString(date);
    window.location.href = `Admin-Sched.php?date=${dateStr}`;
}

function navigateMonth(offset) {
    const newDate = new Date(currentDate);
    const day = newDate.getDate();
    newDate.setMonth(newDate.getMonth() + offset);
    if (newDate.getDate() !== day) {
        newDate.setDate(0);
    }
    navigateToDate(newDate);
}

function navigateDay(offset) {
    const d = new Date(currentDate);
    d.setDate(d.getDate() + offset);
    navigateToDate(d);
}

// ── AJAX refresh (live) ──
function refreshCalendarData() {
    const dateStr = localDateString(currentDate);
    fetch(`Admin-Sched.php?date=${dateStr}`, {
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
    })
        .then(response => {
            if (!response.ok) throw new Error('Network error');
            return response.json();
        })
        .then(newData => {
            calendarData.bookings = newData.bookings || [];
            calendarData.timeSlots = newData.timeSlots || [];
            calendarData.previousBookings = newData.previousBookings || calendarData.previousBookings;
            calendarData.hasMorePreviousBookings = newData.hasMorePreviousBookings || false;
            calendarData.previousBookingsPage = newData.previousBookingsPage || 0;
            calendarData.currentDate = newData.currentDate || calendarData.currentDate;
            calendarData.displayDate = newData.displayDate || calendarData.displayDate;
            appointments = calendarData.bookings.map(booking => ({
                time: normalizeTimeKey(booking.booking_time ? booking.booking_time.substring(0, 5) : '00:00'),
                duration: booking.duration_minutes || 30,
                label: `${booking.customer_name || 'Unknown'} – ${booking.service_name || 'Service'}`,
                status: booking.status || 'confirmed',
                id: booking.id,
                staffName: booking.staff_name,
                staffImage: booking.staff_image,
                notes: booking.notes,
                totalAmount: booking.total_amount || 0,
                paidAmount: booking.paid_amount || 0
            }));
            currentDate = parseLocalDate(calendarData.currentDate);
            currentMonth = new Date(currentDate.getFullYear(), currentDate.getMonth(), 1);
            updateDateDisplay();
            buildGrid();
            renderPreviousBookings();
            updatePreviousHistoryButton();
            showToast('🔄 Calendar updated');
        })
        .catch(error => console.error('Refresh error:', error));
}

function updatePreviousHistoryButton() {
    const button = document.getElementById('loadMorePreviousBookings');
    if (!button) return;
    const hasMore = calendarData.hasMorePreviousBookings === true;
    button.style.display = hasMore ? 'block' : 'none';
}

function loadMoreHistory() {
    const nextPage = (Number(calendarData.previousBookingsPage) || 0) + 1;
    const dateStr = currentDate.toISOString().split('T')[0];
    fetch(`Admin-Sched.php?date=${dateStr}&previous_page=${nextPage}`, {
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
    })
        .then(response => {
            if (!response.ok) throw new Error('Network error');
            return response.json();
        })
        .then(newData => {
            const moreBookings = Array.isArray(newData.previousBookings) ? newData.previousBookings : [];
            calendarData.previousBookings = [...calendarData.previousBookings, ...moreBookings];
            calendarData.hasMorePreviousBookings = newData.hasMorePreviousBookings || false;
            calendarData.previousBookingsPage = newData.previousBookingsPage || nextPage;
            renderPreviousBookings();
            updatePreviousHistoryButton();
            showToast('📚 Loaded more history');
        })
        .catch(error => console.error('Load more error:', error));
}

// ── Toast ──
let toastTimeout = null;

function showToast(message) {
    const el = document.getElementById('toast');
    el.textContent = message;
    el.classList.add('show');
    clearTimeout(toastTimeout);
    toastTimeout = setTimeout(() => {
        el.classList.remove('show');
    }, 2800);
}

// ── Event listeners ──

// Month navigation
document.getElementById('prevMonth').addEventListener('click', () => navigateMonth(-1));
document.getElementById('nextMonth').addEventListener('click', () => navigateMonth(1));

// Day navigation
document.getElementById('prevDay').addEventListener('click', () => navigateDay(-1));
document.getElementById('nextDay').addEventListener('click', () => navigateDay(1));

// Today button
document.getElementById('todayBtn').addEventListener('click', () => {
    navigateToDate(new Date());
});

// Date picker
datePicker.addEventListener('change', function () {
    const parts = this.value.split('-');
    if (parts.length === 3) {
        const d = new Date(parseInt(parts[0]), parseInt(parts[1]) - 1, parseInt(parts[2]));
        navigateToDate(d);
    }
});

const loadMoreButton = document.getElementById('loadMorePreviousBookings');
if (loadMoreButton) {
    loadMoreButton.addEventListener('click', loadMoreHistory);
}

// ── Init ──
updateDateDisplay();
buildGrid();
renderPreviousBookings();

// Auto-refresh every 30 seconds
refreshTimer = setInterval(refreshCalendarData, 30000);
window.addEventListener('beforeunload', () => {
    if (refreshTimer) clearInterval(refreshTimer);
});

// Refresh when tab becomes visible
document.addEventListener('visibilitychange', () => {
    if (document.visibilityState === 'visible') {
        refreshCalendarData();
    }
});

console.log(`✅ Cataleya Essence · Admin Schedule loaded (${formatDate(currentDate)})`);
