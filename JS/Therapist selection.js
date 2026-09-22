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
    let selectedId = null;

    // ─── SELECT THERAPIST & NAVIGATE TO PAYMENT ────────────
    const selectBtns = document.querySelectorAll('.select-btn');

    selectBtns.forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.stopPropagation();

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
            if (btn) btn.click();
        });
    });

});