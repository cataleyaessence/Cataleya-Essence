document.addEventListener('DOMContentLoaded', function() {

    // ─── PROFILE DROPDOWN TOGGLE ─────────────────────────────
    const avatar = document.getElementById('profileAvatar');
    const dropdown = document.getElementById('profileDropdown');
    const overlay = document.getElementById('dropdownOverlay');

    if (avatar && dropdown && overlay) {
        function toggleDropdown(forceState) {
            const isOpen = typeof forceState === 'boolean' ? forceState : !dropdown.classList.contains('open');
            dropdown.classList.toggle('open', isOpen);
            avatar.setAttribute('aria-expanded', isOpen);
            overlay.classList.toggle('active', isOpen);
        }

        avatar.addEventListener('click', function(e) {
            e.stopPropagation();
            toggleDropdown();
        });

        overlay.addEventListener('click', function() {
            toggleDropdown(false);
        });

        document.addEventListener('click', function(e) {
            const wrapper = document.getElementById('profileWrapper');
            if (wrapper && !wrapper.contains(e.target) && dropdown.classList.contains('open')) {
                toggleDropdown(false);
            }
        });

        dropdown.addEventListener('click', function(e) {
            e.stopPropagation();
        });
    }

    // ─── HAMBURGER MENU ──────────────────────────────────────────
    const hamburger = document.getElementById('hamburger');
    const navLinks = document.getElementById('nav-links');
    if (hamburger && navLinks) {
        hamburger.addEventListener('click', function() {
            this.classList.toggle('active');
            navLinks.classList.toggle('open');
            const isOpen = navLinks.classList.contains('open');
            this.setAttribute('aria-expanded', isOpen);
        });
    }

    // ─── CALENDAR / TIME SLOT STATE ─────────────────────────────
    const MONTH_NAMES = ['JANUARY', 'FEBRUARY', 'MARCH', 'APRIL', 'MAY', 'JUNE', 'JULY', 'AUGUST', 'SEPTEMBER', 'OCTOBER', 'NOVEMBER', 'DECEMBER'];
    const MONTH_NAMES_FULL = ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'];
    const WEEKDAY_NAMES = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];

    const today = new Date();
    today.setHours(0, 0, 0, 0);

    let viewYear = today.getFullYear();
    let viewMonth = today.getMonth();
    let selectedDate = null;
    let selectedTime = null;
    let selectedTimeData = null; // Store full slot data
    let calendarData = {}; // Store calendar data from API
    let timeSlotsData = []; // Store time slots data from API

    // ─── Database API functions ─────────────────────────────
    async function fetchCalendarData(month, year) {
        try {
            const response = await fetch(`../api/get_calendar_slots.php?month=${month}&year=${year}`);
            const result = await response.json();
            if (result.success) {
                calendarData = result.data;
                return true;
            }
            return false;
        } catch (error) {
            console.error('Error fetching calendar data:', error);
            return false;
        }
    }

    async function fetchTimeSlots(date) {
        try {
            const dateStr = formatDateForAPI(date);
            const response = await fetch(`../api/get_time_slots.php?date=${dateStr}`);
            const result = await response.json();
            if (result.success) {
                timeSlotsData = result.data;
                return result;
            }
            return null;
        } catch (error) {
            console.error('Error fetching time slots:', error);
            return null;
        }
    }

    function dateKey(date) {
        return `${date.getFullYear()}-${String(date.getMonth() + 1).padStart(2, '0')}-${String(date.getDate()).padStart(2, '0')}`;
    }

    function formatDateForAPI(date) {
        return `${date.getFullYear()}-${String(date.getMonth() + 1).padStart(2, '0')}-${String(date.getDate()).padStart(2, '0')}`;
    }

    function getDateStatus(date) {
        if (date < today) return 'past';
        const key = dateKey(date);
        if (calendarData[key]) {
            return calendarData[key].status;
        }
        return 'available'; // Default if no data
    }

    function isSameDate(a, b) {
        return a && b &&
            a.getFullYear() === b.getFullYear() &&
            a.getMonth() === b.getMonth() &&
            a.getDate() === b.getDate();
    }

    function formatDateLong(date) {
        return `${WEEKDAY_NAMES[date.getDay()]}, ${MONTH_NAMES_FULL[date.getMonth()]} ${date.getDate()} ${date.getFullYear()}`;
    }

    function formatDateShort(date) {
        const month = String(date.getMonth() + 1).padStart(2, '0');
        const day = String(date.getDate()).padStart(2, '0');
        const year = date.getFullYear();
        return `${month}/${day}/${year}`;
    }

    function formatHour(hour, minute) {
        const period = hour >= 12 ? 'PM' : 'AM';
        let h12 = hour % 12;
        if (h12 === 0) h12 = 12;
        return `${h12}:${minute === 0 ? '00' : minute}`;
    }

    // ─── DOM refs ──────────────────────────────────────────────────
    const calendarMonthEl = document.getElementById('calendarMonth');
    const calendarDatesEl = document.getElementById('calendarDates');
    const timeslotsGridEl = document.getElementById('timeslotsGrid');
    const timeslotsDateEl = document.getElementById('timeslotsDate');
    const availCountEl = document.getElementById('availCount');
    const bookedCountEl = document.getElementById('bookedCount');
    const bookDateBtn = document.getElementById('bookDateBtn');

    // ─── RENDER CALENDAR ──────────────────────────────────────────
    async function renderCalendar() {
        if (!calendarMonthEl || !calendarDatesEl) return;

        calendarMonthEl.textContent = `${MONTH_NAMES[viewMonth]} ${viewYear}`;
        const grid = calendarDatesEl;
        grid.innerHTML = '';

        // Fetch calendar data from database
        await fetchCalendarData(viewMonth + 1, viewYear);

        const firstDay = new Date(viewYear, viewMonth, 1).getDay();
        const daysInMonth = new Date(viewYear, viewMonth + 1, 0).getDate();

        for (let i = 0; i < firstDay; i++) {
            const blank = document.createElement('span');
            blank.className = 'date-cell blank';
            grid.appendChild(blank);
        }

        for (let day = 1; day <= daysInMonth; day++) {
            const dateObj = new Date(viewYear, viewMonth, day);
            const status = getDateStatus(dateObj);

            const btn = document.createElement('button');
            btn.className = `date-cell ${status}`;
            btn.textContent = day;
            btn.disabled = (status === 'past' || status === 'booked');
            if (isSameDate(dateObj, selectedDate)) btn.classList.add('selected');

            btn.addEventListener('click', async function() {
                selectedDate = dateObj;
                selectedTime = null;
                renderCalendar();
                await renderTimeSlots();
            });

            grid.appendChild(btn);
        }

        if (selectedDate) {
            await renderTimeSlots();
        }
    }

    // ─── RENDER TIME SLOTS ──────────────────────────────────────────
    async function renderTimeSlots() {
        if (!timeslotsGridEl || !timeslotsDateEl) return;

        const grid = timeslotsGridEl;
        grid.innerHTML = '';

        if (!selectedDate) {
            timeslotsDateEl.textContent = 'Select a date to see available times';
            if (availCountEl) availCountEl.textContent = '0';
            if (bookedCountEl) bookedCountEl.textContent = '0';
            if (bookDateBtn) bookDateBtn.disabled = true;
            return;
        }

        timeslotsDateEl.textContent = formatDateLong(selectedDate);

        // Fetch time slots from database
        const result = await fetchTimeSlots(selectedDate);
        
        let availCount = 0, bookedCount = 0;

        if (result && result.data) {
            result.data.forEach(slot => {
                if (slot.status === 'available') {
                    availCount++;
                } else {
                    bookedCount++;
                }

                const btn = document.createElement('button');
                btn.className = `slot-btn ${slot.status === 'available' ? '' : 'booked'}`;
                btn.textContent = slot.time;
                btn.disabled = (slot.status !== 'available');
                if (selectedTime === slot.id) btn.classList.add('selected');

                btn.addEventListener('click', function() {
                    selectedTime = slot.id;
                    selectedTimeData = slot; // Store full slot data
                    renderTimeSlots();
                });

                grid.appendChild(btn);
            });

            if (availCountEl) availCountEl.textContent = result.available_count || availCount;
            if (bookedCountEl) bookedCountEl.textContent = result.booked_count || bookedCount;
        } else {
            // Fallback if API fails
            timeslotsDateEl.textContent = 'Unable to load time slots';
        }

        if (bookDateBtn) bookDateBtn.disabled = !selectedTime;
    }

    // ─── MONTH NAVIGATION ────────────────────────────────────────
    const prevBtn = document.getElementById('prevMonth');
    const nextBtn = document.getElementById('nextMonth');

    if (prevBtn) {
        prevBtn.addEventListener('click', async function() {
            viewMonth--;
            if (viewMonth < 0) { viewMonth = 11; viewYear--; }
            await renderCalendar();
        });
    }

    if (nextBtn) {
        nextBtn.addEventListener('click', async function() {
            viewMonth++;
            if (viewMonth > 11) { viewMonth = 0; viewYear++; }
            await renderCalendar();
        });
    }

    // ─── BOOK THIS DATE – STORE DATE/TIME & NAVIGATE ────────────
    if (bookDateBtn) {
        bookDateBtn.addEventListener('click', function() {
            if (!selectedDate || !selectedTime || !selectedTimeData) return;

            const dateStr = formatDateShort(selectedDate);
            const timeStr = selectedTimeData.time;

            // Get selected service from sessionStorage
            const selectedService = JSON.parse(sessionStorage.getItem('selectedService') || '{}');

            // Store date, time, and service in sessionStorage
            sessionStorage.setItem('bookingDateTime', JSON.stringify({
                date: formatDateLong(selectedDate),
                dateShort: dateStr,
                // Keep the local calendar day in a database-safe format. Do not
                // rely on UTC conversion because it can turn a PH date into the
                // previous day.
                dateISO: formatDateForAPI(selectedDate),
                time: timeStr,
                timeKey: selectedTime,
                slotId: selectedTime,
                slotTime: selectedTimeData.slot_time,
                service: selectedService
            }));

            // Navigate to therapist selection with service category
            const category = selectedService.category || 'Beauty Services';
            window.location.href = 'Therapist%20selection.php?category=' + encodeURIComponent(category);
        });
    }

    // ─── INIT ────────────────────────────────────────────────────
    renderCalendar();

});
