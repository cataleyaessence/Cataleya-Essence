// ─── WAIT FOR DOM ─────────────────────────────────────────────
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

    // ─── THERAPIST SELECTION LOGIC ─────────────────────────────
    const cards = document.querySelectorAll('.therapist-card');
    const therapistGrid = document.getElementById('therapistGrid');
    let selectedId = null;

    // ─── SELECT THERAPIST & NAVIGATE TO PAYMENT ────────────
    const selectBtns = document.querySelectorAll('.select-btn');

    function setTherapistAvailability(card, available, unavailableReason = '') {
        const button = card.querySelector('.select-btn');
        const availabilityLabel = card.querySelector('.therapist-availability');
        card.classList.toggle('unavailable', !available);
        card.setAttribute('aria-disabled', String(!available));

        if (!button) {
            return;
        }

        button.disabled = !available;
        if (available) {
            button.textContent = button.classList.contains('selected') ? 'Selected' : 'Select';
            if (availabilityLabel) availabilityLabel.textContent = 'Available for your selected time';
        } else {
            button.classList.remove('selected');
            button.textContent = 'Unavailable';
            if (availabilityLabel) {
                availabilityLabel.textContent = unavailableReason === 'daily_limit'
                    ? 'Fully booked for this day (3 of 3)'
                    : 'Already booked at this time';
            }
            card.classList.remove('selected');
            if (selectedId === Number(button.dataset.id)) selectedId = null;
        }
    }

    async function loadTherapistAvailability() {
        const bookingDateTime = JSON.parse(sessionStorage.getItem('bookingDateTime') || '{}');
        const date = bookingDateTime.dateISO || '';
        const time = bookingDateTime.slotTime || bookingDateTime.time || '';
        const category = therapistGrid?.dataset.category || '';

        if (!date || !time || !category) {
            cards.forEach((card) => {
                setTherapistAvailability(card, false);
                const label = card.querySelector('.therapist-availability');
                if (label) label.textContent = 'Select a date and time first';
            });
            return;
        }

        try {
            const response = await fetch(
                `../api/get_staff_availability.php?category=${encodeURIComponent(category)}&date=${encodeURIComponent(date)}&time=${encodeURIComponent(time)}`,
                { cache: 'no-store', credentials: 'same-origin' }
            );
            const result = await response.json();
            if (!response.ok || !result.success || !Array.isArray(result.data)) {
                throw new Error(result.error || 'Unable to check therapist availability.');
            }

            const availabilityById = new Map(result.data.map((staff) => [Number(staff.id), staff]));
            cards.forEach((card) => {
                const staffAvailability = availabilityById.get(Number(card.dataset.id));
                setTherapistAvailability(
                    card,
                    staffAvailability?.available === true,
                    staffAvailability?.unavailable_reason || ''
                );
            });
        } catch (error) {
            cards.forEach((card) => {
                setTherapistAvailability(card, false);
                const label = card.querySelector('.therapist-availability');
                if (label) label.textContent = 'Availability check failed. Please try again.';
            });
        }
    }

    selectBtns.forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.stopPropagation();

            if (this.disabled) {
                return;
            }

            const card = this.closest('.therapist-card');
            const id = parseInt(this.dataset.id);

            // Deselect all
            cards.forEach(c => c.classList.remove('selected'));
            selectBtns.forEach(b => {
                b.classList.remove('selected');
                b.textContent = 'Select';
            });

            // Select this one
            card.classList.add('selected');
            this.classList.add('selected');
            this.textContent = 'Selected';
            selectedId = id;

            // ─── GET THERAPIST DATA ──────────────────────
            const therapistName = card.querySelector('.therapist-name').textContent.trim();
            const therapistTitle = card.querySelector('.therapist-title').textContent.trim();

            // ─── GET BOOKING DATA FROM SESSIONSTORAGE ──────────
            const bookingDateTime = JSON.parse(sessionStorage.getItem('bookingDateTime') || '{}');
            const selectedService = bookingDateTime.service || JSON.parse(sessionStorage.getItem('selectedService') || '{}');

            console.log('Therapist selection - bookingDateTime:', bookingDateTime);
            console.log('Therapist selection - selectedService from sessionStorage:', selectedService);
            console.log('Therapist selection - service.id:', selectedService.id);

            // ─── COMBINE ALL BOOKING DATA ─────────────────────
            const completeBookingData = {
                service: {
                    id: selectedService.id || 0,
                    name: selectedService.name || 'Service',
                    price: selectedService.price || '0',
                    category: selectedService.category || 'Beauty Services'
                },
                dateTime: {
                    date: bookingDateTime.date || '',
                    dateISO: bookingDateTime.dateISO || '',
                    time: bookingDateTime.time || ''
                },
                therapist: {
                    id: id,
                    name: therapistName,
                    title: therapistTitle
                }
            };

            console.log('Therapist selection - completeBookingData:', completeBookingData);
            console.log('Therapist selection - service_id being stored:', completeBookingData.service.id);

            // ─── STORE COMPLETE BOOKING DATA ──────────────────
            sessionStorage.setItem('bookingData', JSON.stringify(completeBookingData));

            // ─── NAVIGATE TO PAYMENT PAGE ──────────────────
            window.location.href = 'Confirmpayment.php';
        });
    });

    // Also allow clicking the card itself
    cards.forEach(card => {
        card.addEventListener('click', function() {
            const btn = this.querySelector('.select-btn');
            if (btn && !btn.disabled) btn.click();
        });
    });

    loadTherapistAvailability();
    window.setInterval(loadTherapistAvailability, 30000);

});
