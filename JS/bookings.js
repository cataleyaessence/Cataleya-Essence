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

    // Open modal when clicking on booking card
    bookingCards.forEach(card => {
        card.addEventListener('click', function(e) {
            // Don't open modal if clicking cancel button
            if (e.target.closest('.btn-cancel-booking')) {
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
            <div class="booking-detail-item">
                <label>Service</label>
                <div class="detail-value service-detail">
                    ${booking.service_image ? `<img src="${booking.service_image}" alt="${booking.service_name}" />` : '<i class="fas fa-spa"></i>'}
                    <span>${booking.service_name}</span>
                </div>
            </div>
            
            <div class="booking-detail-item">
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
            
            <div class="booking-detail-item">
                <label>Status</label>
                <div class="detail-value">
                    <span class="status-badge ${statusBadgeClass}">
                        <i class="fas ${statusIcon}"></i> ${booking.status.charAt(0).toUpperCase() + booking.status.slice(1)}
                    </span>
                </div>
            </div>
            
            <div class="booking-detail-item">
                <label>Total Amount</label>
                <div class="detail-value price-value">
                    <i class="fas fa-tag"></i> ₱${parseFloat(booking.total_amount || booking.service_price).toFixed(2)}
                </div>
            </div>
            
            ${booking.notes ? `
            <div class="booking-detail-item">
                <label>Notes</label>
                <div class="detail-value notes-value">
                    ${booking.notes}
                </div>
            </div>
            ` : ''}
            
            <div class="booking-detail-item">
                <label>Booking ID</label>
                <div class="detail-value booking-id-value">
                    #${booking.id}
                </div>
            </div>
            
            <div class="booking-detail-item">
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
            'pending': 'status-pending',
            'confirmed': 'status-confirmed',
            'completed': 'status-completed',
            'cancelled': 'status-cancelled'
        };
        return classes[status] || 'status-default';
    }

    function getStatusIcon(status) {
        const icons = {
            'pending': 'fa-clock',
            'confirmed': 'fa-check-circle',
            'completed': 'fa-check-double',
            'cancelled': 'fa-times-circle'
        };
        return icons[status] || 'fa-circle';
    }

    // ─── CANCEL BOOKING ────────────────────────────────────────────
    const cancelButtons = document.querySelectorAll('.btn-cancel-booking');
    cancelButtons.forEach(btn => {
        btn.addEventListener('click', function (e) {
            e.stopPropagation();
            const bookingId = this.getAttribute('data-booking-id');
            if (confirm('Are you sure you want to cancel this booking?')) {
                // Here you would send an AJAX request to cancel
                // For demonstration, we'll just show an alert
                alert('Cancel request for booking #' + bookingId + ' (AJAX would be implemented)');
                // Example: window.location.href = 'cancel_booking.php?id=' + bookingId;
            }
        });
    });

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