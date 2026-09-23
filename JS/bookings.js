document.addEventListener('DOMContentLoaded', function () {

    // ─── FILTER TABS ──────────────────────────────────────────────
    const filterTabs = document.querySelectorAll('.filter-tab');
    const bookingCards = document.querySelectorAll('.booking-card');

    // Function to filter bookings
    function filterBookings(status) {
        bookingCards.forEach(card => {
            const cardStatus = card.getAttribute('data-status');
            if (status === 'all' || cardStatus === status) {
                card.classList.remove('hidden');
            } else {
                card.classList.add('hidden');
            }
        });

        // Update active tab
        filterTabs.forEach(tab => {
            tab.classList.remove('active');
            if (tab.getAttribute('data-status') === status) {
                tab.classList.add('active');
            }
        });
    }

    // Add click event to each tab
    filterTabs.forEach(tab => {
        tab.addEventListener('click', function () {
            const status = this.getAttribute('data-status');
            filterBookings(status);
        });
    });

    // ─── BOOKING DETAILS MODAL ───────────────────────────────────
    const bookingModal = document.getElementById('bookingModal');
    const bookingModalClose = document.getElementById('bookingModalClose');
    const closeBookingModal = document.getElementById('closeBookingModal');
    const bookingDetailsContent = document.getElementById('bookingDetailsContent');
    const rescheduleModal = document.getElementById('rescheduleModal');
    const rescheduleModalClose = document.getElementById('rescheduleModalClose');
    const cancelReschedule = document.getElementById('cancelReschedule');
    const rescheduleForm = document.getElementById('rescheduleForm');
    const rescheduleBookingId = document.getElementById('rescheduleBookingId');
    const rescheduleService = document.getElementById('rescheduleService');
    const rescheduleDate = document.getElementById('rescheduleDate');
    const rescheduleTime = document.getElementById('rescheduleTime');
    const rescheduleMessage = document.getElementById('rescheduleMessage');
    const confirmReschedule = document.getElementById('confirmReschedule');
    const bookingCsrfToken = window.bookingPageConfig?.csrfToken || '';
    const availableServices = Array.isArray(window.bookingPageConfig?.services) ? window.bookingPageConfig.services : [];
    const cancelBookingModal = document.getElementById('cancelBookingModal');
    const cancelBookingModalClose = document.getElementById('cancelBookingModalClose');
    const keepBooking = document.getElementById('keepBooking');
    const confirmCancelBooking = document.getElementById('confirmCancelBooking');
    const cancelBookingMessage = document.getElementById('cancelBookingMessage');
    let cancellationBookingId = '';
    let allowedReschedulePrice = null;

    // Open modal when clicking on booking card
    bookingCards.forEach(card => {
        card.addEventListener('click', function(event) {
            if (event.target.closest('.btn-reschedule-booking, .btn-cancel-booking')) {
                return;
            }
            const bookingId = this.getAttribute('data-booking-id');
            if (bookingId) {
                fetchBookingDetails(bookingId);
            }
        });
    });

    // Close modal handlers
    if (bookingModalClose) {
        bookingModalClose.addEventListener('click', closeBookingModalHandler);
    }
    if (closeBookingModal) {
        closeBookingModal.addEventListener('click', closeBookingModalHandler);
    }
    if (bookingModal) {
        bookingModal.addEventListener('click', function(e) {
            if (e.target === bookingModal) {
                closeBookingModalHandler();
            }
        });
    }

    function closeBookingModalHandler() {
        if (bookingModal) {
            bookingModal.classList.remove('active');
        }
    }

    function localDateValue() {
        const now = new Date();
        const offset = now.getTimezoneOffset() * 60000;
        return new Date(now.getTime() - offset).toISOString().slice(0, 10);
    }

    function setRescheduleMessage(message = '', type = '') {
        if (!rescheduleMessage) return;
        rescheduleMessage.textContent = message;
        rescheduleMessage.className = `reschedule-message${type ? ` ${type}` : ''}`;
    }

    function populateRescheduleServices(selectedServiceId, originalServicePrice) {
        if (!rescheduleService) return;

        rescheduleService.innerHTML = '<option value="">Choose a service</option>';
        const matchingServices = availableServices.filter(service => (
            Math.abs(Number(service.price) - Number(originalServicePrice)) < 0.005
        ));

        matchingServices.forEach(service => {
            const option = document.createElement('option');
            option.value = String(service.id);
            option.textContent = `${service.name} — ₱${Number(service.price).toFixed(2)}`;
            option.selected = Number(service.id) === Number(selectedServiceId);
            rescheduleService.appendChild(option);
        });

        rescheduleService.disabled = matchingServices.length === 0;
        return matchingServices.length > 0;
    }

    async function loadRescheduleTimeSlots() {
        if (!rescheduleDate || !rescheduleTime || !rescheduleDate.value) return;

        rescheduleTime.disabled = true;
        rescheduleTime.innerHTML = '<option value="">Loading available times...</option>';
        setRescheduleMessage('');

        try {
            const response = await fetch(`../api/get_availability.php?date=${encodeURIComponent(rescheduleDate.value)}`);
            const data = await response.json();
            if (!response.ok || !data.success || !Array.isArray(data.slots)) {
                throw new Error(data.error || 'Unable to load time slots.');
            }

            const now = new Date();
            const availableSlots = data.slots.filter(slot => {
                const current = Number(slot.current_bookings || 0);
                const maximum = Number(slot.max_bookings || 1);
                const slotDateTime = new Date(`${rescheduleDate.value}T${String(slot.slot_time).slice(0, 8)}`);
                return current < maximum
                    && !['booked', 'unavailable'].includes(slot.status)
                    && !Number.isNaN(slotDateTime.getTime())
                    && slotDateTime > now;
            });

            rescheduleTime.innerHTML = '<option value="">Select an available time</option>';
            availableSlots.forEach(slot => {
                const option = document.createElement('option');
                option.value = slot.slot_time;
                option.textContent = slot.display_time;
                rescheduleTime.appendChild(option);
            });
            rescheduleTime.disabled = availableSlots.length === 0;

            if (availableSlots.length === 0) {
                setRescheduleMessage('No available times for this date. Please choose another date.', 'error');
            }
        } catch (error) {
            rescheduleTime.innerHTML = '<option value="">Unable to load times</option>';
            setRescheduleMessage(error.message || 'Unable to load available times. Please try again.', 'error');
        }
    }

    function closeRescheduleModal() {
        if (!rescheduleModal) return;
        rescheduleModal.classList.remove('active');
        rescheduleModal.setAttribute('aria-hidden', 'true');
        setRescheduleMessage('');
    }

    function openRescheduleModal(button) {
        if (!rescheduleModal || !rescheduleBookingId || !rescheduleService || !rescheduleDate || !rescheduleTime) return;

        const today = localDateValue();
        const originalDate = button.dataset.bookingDate || '';
        const originalServicePrice = Number(button.dataset.servicePrice);
        rescheduleBookingId.value = button.dataset.bookingId || '';
        allowedReschedulePrice = Number.isFinite(originalServicePrice) ? originalServicePrice : null;
        const hasSamePriceService = populateRescheduleServices(button.dataset.serviceId || '', allowedReschedulePrice);
        rescheduleDate.min = today;
        rescheduleDate.value = originalDate >= today ? originalDate : today;
        rescheduleTime.disabled = true;
        rescheduleTime.innerHTML = '<option value="">Loading available times...</option>';
        setRescheduleMessage('');
        rescheduleModal.classList.add('active');
        rescheduleModal.setAttribute('aria-hidden', 'false');
        loadRescheduleTimeSlots();
        if (!hasSamePriceService) {
            setRescheduleMessage('No active services are available at the original booking price.', 'error');
        }
    }

    document.querySelectorAll('.btn-reschedule-booking').forEach(button => {
        button.addEventListener('click', function(event) {
            event.preventDefault();
            event.stopPropagation();
            openRescheduleModal(this);
        });
    });

    function setCancelBookingMessage(message = '', type = '') {
        if (!cancelBookingMessage) return;
        cancelBookingMessage.textContent = message;
        cancelBookingMessage.className = `cancel-message${type ? ` ${type}` : ''}`;
    }

    function closeCancelBookingModal() {
        if (!cancelBookingModal) return;
        cancelBookingModal.classList.remove('active');
        cancelBookingModal.setAttribute('aria-hidden', 'true');
        cancellationBookingId = '';
        setCancelBookingMessage('');
    }

    function openCancelBookingModal(button) {
        if (!cancelBookingModal) return;
        cancellationBookingId = button.dataset.bookingId || '';
        setCancelBookingMessage('');
        cancelBookingModal.classList.add('active');
        cancelBookingModal.setAttribute('aria-hidden', 'false');
    }

    document.querySelectorAll('.btn-cancel-booking').forEach(button => {
        button.addEventListener('click', function(event) {
            event.preventDefault();
            event.stopPropagation();
            openCancelBookingModal(this);
        });
    });

    if (rescheduleModalClose) rescheduleModalClose.addEventListener('click', closeRescheduleModal);
    if (cancelReschedule) cancelReschedule.addEventListener('click', closeRescheduleModal);
    if (rescheduleModal) {
        rescheduleModal.addEventListener('click', function(event) {
            if (event.target === rescheduleModal) closeRescheduleModal();
        });
    }
    if (cancelBookingModalClose) cancelBookingModalClose.addEventListener('click', closeCancelBookingModal);
    if (keepBooking) keepBooking.addEventListener('click', closeCancelBookingModal);
    if (cancelBookingModal) {
        cancelBookingModal.addEventListener('click', function(event) {
            if (event.target === cancelBookingModal) closeCancelBookingModal();
        });
    }
    if (rescheduleDate) rescheduleDate.addEventListener('change', loadRescheduleTimeSlots);

    if (rescheduleForm) {
        rescheduleForm.addEventListener('submit', async function(event) {
            event.preventDefault();
            if (!rescheduleBookingId.value || !rescheduleService.value || !rescheduleDate.value || !rescheduleTime.value) {
                setRescheduleMessage('Choose a service, date, and available time.', 'error');
                return;
            }

            const selectedService = availableServices.find(service => Number(service.id) === Number(rescheduleService.value));
            if (!selectedService || allowedReschedulePrice === null || Math.abs(Number(selectedService.price) - allowedReschedulePrice) >= 0.005) {
                setRescheduleMessage('Choose a service with the same price as your original booking.', 'error');
                return;
            }

            const originalButtonText = confirmReschedule.textContent;
            confirmReschedule.disabled = true;
            confirmReschedule.textContent = 'Saving...';
            setRescheduleMessage('');

            try {
                const response = await fetch('../api/reschedule_booking.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-Token': bookingCsrfToken
                    },
                    body: JSON.stringify({
                        booking_id: Number(rescheduleBookingId.value),
                        service_id: Number(rescheduleService.value),
                        booking_date: rescheduleDate.value,
                        booking_time: rescheduleTime.value
                    })
                });
                const data = await response.json();
                if (!response.ok || !data.success) {
                    throw new Error(data.error || 'Unable to reschedule this booking.');
                }

                setRescheduleMessage(data.message || 'Booking rescheduled successfully.', 'success');
                window.setTimeout(() => window.location.reload(), 500);
            } catch (error) {
                setRescheduleMessage(error.message || 'Unable to reschedule this booking. Please try again.', 'error');
                confirmReschedule.disabled = false;
                confirmReschedule.textContent = originalButtonText;
            }
        });
    }

    if (confirmCancelBooking) {
        confirmCancelBooking.addEventListener('click', async function() {
            if (!cancellationBookingId) return;

            const originalButtonText = confirmCancelBooking.textContent;
            confirmCancelBooking.disabled = true;
            confirmCancelBooking.textContent = 'Cancelling...';
            setCancelBookingMessage('');

            try {
                const response = await fetch('../api/cancel_booking.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-Token': bookingCsrfToken
                    },
                    body: JSON.stringify({ booking_id: Number(cancellationBookingId) })
                });
                const data = await response.json();
                if (!response.ok || !data.success) {
                    throw new Error(data.error || 'Unable to cancel this booking.');
                }

                setCancelBookingMessage(data.message || 'Booking cancelled.', 'success');
                window.setTimeout(() => window.location.reload(), 500);
            } catch (error) {
                setCancelBookingMessage(error.message || 'Unable to cancel this booking. Please try again.', 'error');
                confirmCancelBooking.disabled = false;
                confirmCancelBooking.textContent = originalButtonText;
            }
        });
    }

    // Fetch booking details from API
    function fetchBookingDetails(bookingId) {
        fetch(`../api/get_booking_details.php?booking_id=${bookingId}`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    displayBookingDetails(data.booking);
                    if (bookingModal) {
                        bookingModal.classList.add('active');
                    }
                } else {
                    alert('Error: ' + data.error);
                }
            })
            .catch(error => {
                console.error('Error fetching booking details:', error);
                alert('Error loading booking details. Please try again.');
            });
    }

    // Display booking details in modal
    function displayBookingDetails(booking) {
        if (!bookingDetailsContent) return;

        const statusBadgeClass = getStatusBadgeClass(booking.status);
        const statusIcon = getStatusIcon(booking.status);
        const formattedDate = new Date(booking.booking_date + 'T' + booking.booking_time).toLocaleDateString('en-US', {
            weekday: 'long',
            year: 'numeric',
            month: 'long',
            day: 'numeric'
        });
        const formattedTime = new Date('2000-01-01T' + booking.booking_time).toLocaleTimeString('en-US', {
            hour: 'numeric',
            minute: '2-digit',
            hour12: true
        });

        bookingDetailsContent.innerHTML = `
            <div class="booking-detail-item booking-service-summary">
                <label>Service</label>
                <div class="detail-value service-detail">
                    ${booking.service_image ? `<img src="${booking.service_image}" alt="${booking.service_name}" />` : '<i class="fas fa-spa"></i>'}
                    <span>${booking.service_name}</span>
                </div>
            </div>
            
            <div class="booking-detail-item booking-schedule-details">
                <label>Date & Time</label>
                <div class="detail-value">
                    <i class="fas fa-calendar-day"></i> ${formattedDate}
                    <i class="fas fa-clock"></i> ${formattedTime}
                </div>
            </div>
            
            ${booking.stylist_name ? `
            <div class="booking-detail-item">
                <label>Stylist</label>
                <div class="detail-value">
                    <i class="fas fa-user-tie"></i> ${booking.stylist_name}
                    ${booking.stylist_specialization ? `<span class="stylist-specialization">${booking.stylist_specialization}</span>` : ''}
                </div>
            </div>
            ` : ''}
            
            <div class="booking-detail-item booking-status-details">
                <label>Status</label>
                <div class="detail-value">
                    <span class="status-badge ${statusBadgeClass}">
                        <i class="fas ${statusIcon}"></i> ${booking.status.charAt(0).toUpperCase() + booking.status.slice(1)}
                    </span>
                </div>
            </div>
            
            <div class="booking-detail-item booking-price-details">
                <label>Total Amount</label>
                <div class="detail-value price-value">
                    <i class="fas fa-tag"></i> ₱${parseFloat(booking.total_amount || booking.service_price).toFixed(2)}
                </div>
            </div>
            
            ${booking.notes ? `
            <div class="booking-detail-item booking-notes-details">
                <label>Notes</label>
                <div class="detail-value notes-value">
                    ${booking.notes}
                </div>
            </div>
            ` : ''}
            
            <div class="booking-detail-item booking-reference-details">
                <label>Booking ID</label>
                <div class="detail-value booking-id-value">
                    #${booking.id}
                </div>
            </div>
            
            <div class="booking-detail-item booking-reference-details">
                <label>Booked On</label>
                <div class="detail-value">
                    ${new Date(booking.created_at).toLocaleDateString('en-US', {
                        year: 'numeric',
                        month: 'short',
                        day: 'numeric',
                        hour: '2-digit',
                        minute: '2-digit'
                    })}
                </div>
            </div>
        `;
    }

    // Helper functions for status
    function getStatusBadgeClass(status) {
        const classes = {
            'confirmed': 'status-confirmed',
            'completed': 'status-completed',
            'cancelled': 'status-cancelled'
        };
        return classes[status] || 'status-default';
    }

    function getStatusIcon(status) {
        const icons = {
            'confirmed': 'fa-check-circle',
            'completed': 'fa-check-double',
            'cancelled': 'fa-times-circle'
        };
        return icons[status] || 'fa-circle';
    }

    // ─── PROFILE DROPDOWN ─────────────────────────────────────────
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

        avatar.addEventListener('click', function (e) {
            e.stopPropagation();
            toggleDropdown();
        });

        overlay.addEventListener('click', function () {
            toggleDropdown(false);
        });

        document.addEventListener('click', function (e) {
            const wrapper = document.getElementById('profileWrapper');
            if (wrapper && !wrapper.contains(e.target) && dropdown.classList.contains('open')) {
                toggleDropdown(false);
            }
        });

        dropdown.addEventListener('click', function (e) {
            e.stopPropagation();
        });
    }

    // ─── HAMBURGER MENU ────────────────────────────────────────────
    const hamburger = document.getElementById('hamburger');
    const navLinks = document.getElementById('nav-links');

    if (hamburger && navLinks) {
        hamburger.addEventListener('click', function () {
            this.classList.toggle('active');
            navLinks.classList.toggle('open');
            const isOpen = navLinks.classList.contains('open');
            this.setAttribute('aria-expanded', isOpen);
        });
    }

});
