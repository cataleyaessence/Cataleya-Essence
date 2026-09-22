document.addEventListener('DOMContentLoaded', function() {

    // ─── HAMBURGER MENU ──────────────────────────────────────
    const hamburger = document.getElementById('hamburger');
    const navLinks = document.getElementById('nav-links');
    if (hamburger && navLinks) {
        hamburger.addEventListener('click', function() {
            this.classList.toggle('active');
            navLinks.classList.toggle('open');
            this.setAttribute('aria-expanded', navLinks.classList.contains('open'));
        });
    }

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

    // ─── RETRIEVE BOOKING DATA FROM SESSIONSTORAGE ──────────
    let bookingData = {
        service: { name: 'Celebrity Drip', price: '₱1,799.00' },
        dateTime: { date: 'July 15, 2026', time: '2:30 PM' },
        therapist: { name: 'Jhacel' }
    };

    try {
        const stored = sessionStorage.getItem('bookingData');
        console.log('Confirmpayment - Raw sessionStorage.bookingData:', stored);
        if (stored) {
            const parsed = JSON.parse(stored);
            console.log('Confirmpayment - Parsed bookingData:', parsed);
            if (parsed && parsed.service && parsed.dateTime && parsed.therapist) {
                bookingData = parsed;
            }
        }
    } catch (e) {
        console.warn('Could not retrieve booking data, using defaults');
    }

    console.log('Confirmpayment - Final bookingData:', bookingData);
    console.log('Confirmpayment - bookingData.service.id:', bookingData.service?.id);
    console.log('Confirmpayment - bookingData.service.name:', bookingData.service?.name);

    const serviceDisplay = bookingData.service.name || 'Celebrity Drip';
    const servicePrice = bookingData.service.price || '₱1,799.00';
    const dateDisplay = bookingData.dateTime.date || 'July 15, 2026';
    const timeDisplay = bookingData.dateTime.time || '2:30 PM';
    const therapistDisplay = bookingData.therapist.name || 'Jhacel';

    // ─── CALCULATE 50% DOWNPAYMENT ─────────────────────────────
    function calculateDownpayment(priceString) {
        const numericPrice = parseFloat(priceString.replace(/[₱,]/g, ''));
        if (isNaN(numericPrice)) return priceString;
        const downpayment = numericPrice * 0.5;
        return '₱' + downpayment.toLocaleString('en-PH', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    }

    // ─── FORMAT DATE FOR DATABASE ─────────────────────────────
    function formatDateForDB(dateString) {
        // Parse various date formats and convert to YYYY-MM-DD
        const date = new Date(dateString);
        if (isNaN(date)) {
            // Try to extract date parts from strings like "Monday, July 27 2026"
            const parts = dateString.match(/(\w+)\s+(\w+)\s+(\d+),?\s+(\d+)/);
            if (parts) {
                const monthNames = ['January', 'February', 'March', 'April', 'May', 'June',
                                   'July', 'August', 'September', 'October', 'November', 'December'];
                const monthIndex = monthNames.indexOf(parts[2]);
                if (monthIndex !== -1) {
                    return `${parts[4]}-${String(monthIndex + 1).padStart(2, '0')}-${String(parts[3]).padStart(2, '0')}`;
                }
            }
            return dateString;
        }
        return date.toISOString().split('T')[0];
    }

    // ─── FORMAT TIME FOR DATABASE ─────────────────────────────
    function formatTimeForDB(timeString) {
        // Convert "11:00 AM" / "2:30 PM" style strings to "HH:MM:SS" 24-hour format
        const match = timeString.match(/(\d{1,2}):(\d{2})\s*(AM|PM)/i);
        if (!match) return timeString; // already in a DB-compatible format
        let hours = parseInt(match[1], 10);
        const minutes = match[2];
        const period = match[3].toUpperCase();
        if (period === 'PM' && hours !== 12) hours += 12;
        if (period === 'AM' && hours === 12) hours = 0;
        return `${String(hours).padStart(2, '0')}:${minutes}:00`;
    }

    const downpaymentAmount = calculateDownpayment(servicePrice);

    // ─── POPULATE PAYMENT AMOUNT ─────────────────────────────
    const paymentAmount = document.getElementById('paymentAmount');
    const servicePriceElement = document.getElementById('servicePrice');
    if (paymentAmount) paymentAmount.textContent = downpaymentAmount;
    if (servicePriceElement) servicePriceElement.textContent = servicePrice;

    // ─── POPULATE CONFIRMATION DETAILS (Step 2 preview) ──────
    const confService = document.getElementById('confService');
    const confDate = document.getElementById('confDate');
    const confTime = document.getElementById('confTime');
    const confTherapist = document.getElementById('confTherapist');

    if (confService) confService.textContent = serviceDisplay;
    if (confDate) confDate.textContent = dateDisplay;
    if (confTime) confTime.textContent = timeDisplay;
    if (confTherapist) confTherapist.textContent = therapistDisplay;

    // ─── GET ELEMENTS ─────────────────────────────────────────
    const step1 = document.getElementById('step1');
    const step2 = document.getElementById('step2');
    const step3 = document.getElementById('step3'); // Confirmation

    const step1Indicator = document.getElementById('step1Indicator');
    const step2Indicator = document.getElementById('step2Indicator');
    const step3Indicator = document.getElementById('step3Indicator');
    const stepLine1 = document.getElementById('stepLine1');
    const stepLine2 = document.getElementById('stepLine2');

    const nextBtn = document.getElementById('nextBtn');
    const backBtn = document.getElementById('backBtn');
    const verifyBtn = document.getElementById('verifyBtn'); // Step 2 -> Step 3 (Confirmation)
    const reviewBackBtn = document.getElementById('reviewBackBtn'); // Step 3 -> Step 2
    const confirmBookingBtn = document.getElementById('confirmBookingBtn'); // Submits booking

    // ─── STEP 1 → STEP 2 (Next) ──────────────────────────────
    if (nextBtn) {
        nextBtn.addEventListener('click', function(e) {
            e.preventDefault();

            const name = document.getElementById('fullName').value.trim();
            const email = document.getElementById('email').value.trim();
            const phone = document.getElementById('phone').value.trim();

            if (!name || !email || !phone) {
                alert('Please fill in all required fields (Name, Email, and Contact Number).');
                return;
            }
            if (!email.includes('@') || !email.includes('.')) {
                alert('Please enter a valid email address.');
                return;
            }
            const phoneDigits = phone.replace(/\D/g, '');
            if (phoneDigits.length < 7) {
                alert('Please enter a valid contact number (at least 7 digits).');
                return;
            }

            // Transition
            step1.style.display = 'none';
            step2.style.display = 'flex';

            step1Indicator.classList.remove('active');
            step1Indicator.classList.add('completed');
            step2Indicator.classList.remove('active');
            step2Indicator.classList.add('active');
            stepLine1.classList.add('completed');

            step2.scrollIntoView({ behavior: 'smooth', block: 'start' });
        });
    }

    // ─── STEP 2 → STEP 1 (Back) ──────────────────────────────
    if (backBtn) {
        backBtn.addEventListener('click', function() {
            step2.style.display = 'none';
            step1.style.display = 'block';

            step2Indicator.classList.remove('active');
            step1Indicator.classList.remove('completed');
            step1Indicator.classList.add('active');
            stepLine1.classList.remove('completed');

            step1.scrollIntoView({ behavior: 'smooth', block: 'start' });
        });
    }

    // ─── POPULATE REVIEW (Step 3) ──────────────────────────────
    function populateReview() {
        const fullName = document.getElementById('fullName').value.trim();
        const email = document.getElementById('email').value.trim();
        const phone = document.getElementById('phone').value.trim();
        const requests = document.getElementById('requests').value.trim();

        document.getElementById('revService').textContent = serviceDisplay;
        document.getElementById('revDate').textContent = dateDisplay;
        document.getElementById('revTime').textContent = timeDisplay;
        document.getElementById('revTherapist').textContent = therapistDisplay;

        document.getElementById('revName').textContent = fullName;
        document.getElementById('revEmail').textContent = email;
        document.getElementById('revPhone').textContent = phone;
        document.getElementById('revRequests').textContent = requests || 'None';

        document.getElementById('revServicePrice').textContent = servicePrice;
        document.getElementById('revDownpayment').textContent = downpaymentAmount;
        // 50% downpayment means the remaining balance equals the same amount
        document.getElementById('revBalance').textContent = downpaymentAmount;
    }

    // ─── POPULATE RECEIPT (Step 4) ──────────────────────────────
    function populateReceipt(bookingDataFromAPI) {
        // If real booking data is provided, use it
        if (bookingDataFromAPI) {
            // Service info from database
            if (bookingDataFromAPI.service_name) {
                document.getElementById('confService').textContent = bookingDataFromAPI.service_name;
            }

            // Date and time from database
            if (bookingDataFromAPI.booking_date) {
                const dateObj = new Date(bookingDataFromAPI.booking_date);
                const dateStr = dateObj.toLocaleDateString('en-US', { month: 'long', day: 'numeric', year: 'numeric' });
                document.getElementById('confDate').textContent = dateStr;
            }
            if (bookingDataFromAPI.booking_time) {
                document.getElementById('confTime').textContent = bookingDataFromAPI.booking_time;
            }
            // Update combined date/time element
            const dateTimeElem = document.getElementById('confDateTime');
            if (dateTimeElem) {
                const date = document.getElementById('confDate').textContent;
                const time = document.getElementById('confTime').textContent;
                dateTimeElem.innerHTML = date + ' at ' + time;
            }

            // Therapist from database
            if (bookingDataFromAPI.therapist_name) {
                document.getElementById('confTherapist').textContent = bookingDataFromAPI.therapist_name;
            }

            // User info from database
            if (bookingDataFromAPI.customer_name) {
                document.getElementById('confUserName').textContent = bookingDataFromAPI.customer_name;
            }

            // Special requests from database
            if (bookingDataFromAPI.notes) {
                document.getElementById('confRequests').textContent = bookingDataFromAPI.notes;
            }
        } else {
            // Fallback to sessionStorage if no real data
            const stored = sessionStorage.getItem('bookingData');
            if (stored) {
                try {
                    const data = JSON.parse(stored);
                    if (data.service) {
                        document.getElementById('confService').textContent = data.service.name || 'Celebrity Drip';
                    }
                    if (data.dateTime) {
                        const date = data.dateTime.date || 'Mar 28, 2026';
                        const time = data.dateTime.time || '5:00 PM';
                        document.getElementById('confDate').textContent = date;
                        document.getElementById('confTime').textContent = time;
                        const dateTimeElem = document.getElementById('confDateTime');
                        if (dateTimeElem) {
                            dateTimeElem.innerHTML = date + ' at ' + time;
                        }
                    }
                    if (data.therapist) {
                        document.getElementById('confTherapist').textContent = data.therapist.name || 'Jhacel';
                    }
                } catch (e) {}
            }

            const fullName = document.getElementById('fullName').value.trim();
            document.getElementById('confUserName').textContent = fullName || '<?php echo htmlspecialchars($full_name); ?>';

            const requests = document.getElementById('requests').value.trim();
            document.getElementById('confRequests').textContent = requests || 'None';
        }

        // ─── ADD TO CALENDAR (Google Calendar) ──────────────────
        const calendarBtn = document.getElementById('addToCalendar');
        if (calendarBtn) {
            const serviceName = document.getElementById('confService')?.textContent || 'Spa Appointment';
            const dateText = document.getElementById('confDate')?.textContent || '';
            const timeText = document.getElementById('confTime')?.textContent || '';
            // Try to parse a date
            let startDate = new Date(dateText + ' ' + timeText);
            if (isNaN(startDate)) {
                // Try a fallback: use today + 1 hour
                startDate = new Date();
                startDate.setHours(startDate.getHours() + 1);
            }
            if (!isNaN(startDate)) {
                const endDate = new Date(startDate.getTime() + 60 * 60 * 1000); // 1 hour
                const startStr = startDate.toISOString().replace(/[-:]/g, '').slice(0, 15);
                const endStr = endDate.toISOString().replace(/[-:]/g, '').slice(0, 15);
                const location = 'Cataleya Essence of Beauty and Wellness Center, Gapan City';
                const details = 'Appointment at Cataleya Essence.';
                const url = `https://calendar.google.com/calendar/render?action=TEMPLATE&text=${encodeURIComponent(serviceName)}&dates=${startStr}/${endStr}&details=${encodeURIComponent(details)}&location=${encodeURIComponent(location)}`;
                calendarBtn.href = url;
                calendarBtn.target = '_blank';
            }
        }
    }

    // ─── STEP 2 → STEP 3 (Verify Payment → Confirmation) ────────────
    if (verifyBtn) {
        verifyBtn.addEventListener('click', function() {
            populateReview();

            step2.style.display = 'none';
            step3.style.display = 'flex';

            step2Indicator.classList.remove('active');
            step2Indicator.classList.add('completed');
            step3Indicator.classList.add('active');
            stepLine2.classList.add('completed');

            step3.scrollIntoView({ behavior: 'smooth', block: 'start' });
        });
    }

    // ─── STEP 3 → STEP 2 (Back to Payment) ────────────────────
    if (reviewBackBtn) {
        reviewBackBtn.addEventListener('click', function() {
            step3.style.display = 'none';
            step2.style.display = 'flex';

            step3Indicator.classList.remove('active');
            step2Indicator.classList.remove('completed');
            step2Indicator.classList.add('active');
            stepLine2.classList.remove('completed');

            step2.scrollIntoView({ behavior: 'smooth', block: 'start' });
        });
    }

    // ─── STEP 3: Confirm Booking (Show Success Popup) ────────────
    if (confirmBookingBtn) {
        confirmBookingBtn.addEventListener('click', function() {
            const fullName = document.getElementById('fullName').value.trim();
            const email = document.getElementById('email').value.trim();
            const phone = document.getElementById('phone').value.trim();
            const requests = document.getElementById('requests').value.trim();

            const bookingDataPayload = {
                full_name: fullName,
                email: email,
                phone: phone,
                special_requests: requests,
                service_name: bookingData.service?.name || 'Celebrity Drip',
                service_id: bookingData.service?.id || 0,
                service_price: parseFloat(servicePrice.replace(/[₱,]/g, '')),
                booking_date: formatDateForDB(dateDisplay),
                booking_time: formatTimeForDB(timeDisplay),
                staff_id: bookingData.therapist?.id || null
            };

            console.log('Confirmpayment - bookingDataPayload:', bookingDataPayload);
            console.log('Confirmpayment - service_id being sent:', bookingDataPayload.service_id);
            console.log('Confirmpayment - service_id type:', typeof bookingDataPayload.service_id);
            console.log('Confirmpayment - service_id === 0:', bookingDataPayload.service_id === 0);
            console.log('Confirmpayment - service_id === null:', bookingDataPayload.service_id === null);
            console.log('Confirmpayment - service_id === undefined:', bookingDataPayload.service_id === undefined);

            confirmBookingBtn.disabled = true;
            confirmBookingBtn.innerHTML = '<i class="fas fa-spinner fa-spin" style="margin-right:8px;"></i> Processing...';

            fetch('../api/create_booking.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(bookingDataPayload)
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Show success popup
                    showSuccessPopup(data.booking);
                    
                    // Reset button
                    confirmBookingBtn.disabled = false;
                    confirmBookingBtn.innerHTML = '<i class="fas fa-check-circle" style="margin-right:8px;"></i> Confirm Booking';
                } else {
                    alert('Error creating booking: ' + (data.error || 'Unknown error'));
                    confirmBookingBtn.disabled = false;
                    confirmBookingBtn.innerHTML = '<i class="fas fa-check-circle" style="margin-right:8px;"></i> Confirm Booking';
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error creating booking. Please try again.');
                confirmBookingBtn.disabled = false;
                confirmBookingBtn.innerHTML = '<i class="fas fa-check-circle" style="margin-right:8px;"></i> Confirm Booking';
            });
        });
    }

    // ─── SHOW SUCCESS POPUP ──────────────────────────────────
    function showSuccessPopup(bookingData) {
        // Create popup element if it doesn't exist
        let popup = document.getElementById('successPopup');
        if (!popup) {
            popup = document.createElement('div');
            popup.id = 'successPopup';
            popup.className = 'success-popup';
            document.body.appendChild(popup);
        }

        const bookingRef = 'CAT-' + String(bookingData.booking_id).padStart(6, '0');
        
        popup.innerHTML = `
            <div class="success-popup-content">
                <div class="success-icon">
                    <i class="fas fa-check-circle"></i>
                </div>
                <h2>Booking Confirmed!</h2>
                <p>Your appointment has been successfully booked.</p>
                <div class="booking-details-popup">
                    <div class="popup-detail">
                        <span class="popup-label">Booking Reference:</span>
                        <span class="popup-value">${bookingRef}</span>
                    </div>
                    <div class="popup-detail">
                        <span class="popup-label">Service:</span>
                        <span class="popup-value">${bookingData.service_name || serviceDisplay}</span>
                    </div>
                    <div class="popup-detail">
                        <span class="popup-label">Date:</span>
                        <span class="popup-value">${dateDisplay} at ${timeDisplay}</span>
                    </div>
                </div>
                <p class="email-notice">
                    <i class="fas fa-envelope"></i> A booking receipt has been sent to your email.
                </p>
                <button class="btn-close-popup" onclick="closeSuccessPopup()">
                    <i class="fas fa-home"></i> Back to Home
                </button>
            </div>
        `;

        popup.style.display = 'flex';
    }

    // ─── CLOSE SUCCESS POPUP ──────────────────────────────────
    window.closeSuccessPopup = function() {
        const popup = document.getElementById('successPopup');
        if (popup) {
            popup.style.display = 'none';
            window.location.href = 'home.php';
        }
    };

    // ─── ENTER KEY SUPPORT ──────────────────────────────────
    const form = document.getElementById('paymentForm');
    if (form) {
        form.addEventListener('keydown', function(e) {
            if (e.key === 'Enter' && e.target.tagName !== 'TEXTAREA') {
                e.preventDefault();
                if (nextBtn) nextBtn.click();
            }
        });
    }

});