document.addEventListener('DOMContentLoaded', () => {
    const body = document.body;
    const csrfToken = body.dataset.paymentCsrf || '';

    const hamburger = document.getElementById('hamburger');
    const navLinks = document.getElementById('nav-links');
    if (hamburger && navLinks) {
        hamburger.addEventListener('click', () => {
            hamburger.classList.toggle('active');
            navLinks.classList.toggle('open');
            hamburger.setAttribute('aria-expanded', String(navLinks.classList.contains('open')));
        });
    }

    const avatar = document.getElementById('profileAvatar');
    const dropdown = document.getElementById('profileDropdown');
    const overlay = document.getElementById('dropdownOverlay');
    if (avatar && dropdown && overlay) {
        const toggleDropdown = (open) => {
            const isOpen = typeof open === 'boolean' ? open : !dropdown.classList.contains('open');
            dropdown.classList.toggle('open', isOpen);
            overlay.classList.toggle('active', isOpen);
            avatar.setAttribute('aria-expanded', String(isOpen));
        };
        avatar.addEventListener('click', (event) => {
            event.stopPropagation();
            toggleDropdown();
        });
        overlay.addEventListener('click', () => toggleDropdown(false));
        document.addEventListener('click', (event) => {
            const wrapper = document.getElementById('profileWrapper');
            if (wrapper && !wrapper.contains(event.target)) toggleDropdown(false);
        });
    }

    let bookingData = {
        service: { id: 0, name: 'Selected service', price: 0 },
        dateTime: { date: '', dateISO: '', time: '' },
        therapist: { id: null, name: 'To be assigned' }
    };

    try {
        const savedBooking = JSON.parse(sessionStorage.getItem('bookingData') || 'null');
        if (savedBooking && savedBooking.service && savedBooking.dateTime && savedBooking.therapist) {
            bookingData = savedBooking;
        }
    } catch (error) {
        console.warn('The booking selection could not be restored.', error);
    }

    const step1 = document.getElementById('step1');
    const step2 = document.getElementById('step2');
    const step3 = document.getElementById('step3');
    const indicators = [
        document.getElementById('step1Indicator'),
        document.getElementById('step2Indicator'),
        document.getElementById('step3Indicator')
    ];
    const lines = [document.getElementById('stepLine1'), document.getElementById('stepLine2')];
    const nextButton = document.getElementById('nextBtn');
    const backButton = document.getElementById('backBtn');
    const payNowButton = document.getElementById('payNowBtn');
    const paymentMessage = document.getElementById('paymentMessage');
    const form = document.getElementById('paymentForm');
    const qrPaymentModal = document.getElementById('qrPaymentModal');
    const qrPaymentImage = document.getElementById('qrPaymentImage');
    const qrPaymentAmount = document.getElementById('qrPaymentAmount');
    const qrPaymentReference = document.getElementById('qrPaymentReference');
    const qrPaymentStatus = document.getElementById('qrPaymentStatus');
    const closeQrPaymentModal = document.getElementById('closeQrPaymentModal');
    const cancelQrPayment = document.getElementById('cancelQrPayment');
    let activeQrPayment = null;
    let qrPollTimer = null;

    const price = Number(String(bookingData.service?.price ?? 0).replace(/[^0-9.]/g, '')) || 0;
    const downpayment = Math.round(price * 50) / 100;
    setText('servicePrice', formatCurrency(price));
    setText('paymentAmount', formatCurrency(downpayment));

    function setText(id, value) {
        const element = document.getElementById(id);
        if (element) element.textContent = value;
    }

    function formatCurrency(value) {
        return `₱${Number(value || 0).toLocaleString('en-PH', {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2
        })}`;
    }

    function formatDateForDatabase(value) {
        const dateValue = String(value || '').trim();
        if (/^\d{4}-\d{2}-\d{2}$/.test(dateValue)) return dateValue;

        const numericParts = dateValue.match(/^(\d{1,2})\/(\d{1,2})\/(\d{4})$/);
        if (numericParts) {
            return `${numericParts[3]}-${numericParts[1].padStart(2, '0')}-${numericParts[2].padStart(2, '0')}`;
        }

        const namedParts = dateValue.match(/(?:\w+,\s*)?(\w+)\s+(\d{1,2}),?\s+(\d{4})/);
        if (namedParts) {
            const months = ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'];
            const month = months.indexOf(namedParts[1]);
            if (month >= 0) return `${namedParts[3]}-${String(month + 1).padStart(2, '0')}-${namedParts[2].padStart(2, '0')}`;
        }

        const fallback = new Date(dateValue);
        if (Number.isNaN(fallback.getTime())) return '';
        return `${fallback.getFullYear()}-${String(fallback.getMonth() + 1).padStart(2, '0')}-${String(fallback.getDate()).padStart(2, '0')}`;
    }

    function formatTimeForDatabase(value) {
        const timeValue = String(value || '').trim();
        if (/^\d{2}:\d{2}(:\d{2})?$/.test(timeValue)) return timeValue;

        const match = timeValue.match(/(\d{1,2}):(\d{2})\s*(AM|PM)/i);
        if (!match) return '';

        let hour = Number(match[1]);
        if (match[3].toUpperCase() === 'PM' && hour !== 12) hour += 12;
        if (match[3].toUpperCase() === 'AM' && hour === 12) hour = 0;
        return `${String(hour).padStart(2, '0')}:${match[2]}:00`;
    }

    function normalizePhilippineMobile(value) {
        const digits = String(value || '').replace(/\D/g, '');
        if (/^09\d{9}$/.test(digits)) return `+63${digits.slice(1)}`;
        if (/^639\d{9}$/.test(digits)) return `+${digits}`;
        return '';
    }

    function showStep(number, scroll = true) {
        step1.style.display = number === 1 ? 'block' : 'none';
        step2.style.display = number === 2 ? 'flex' : 'none';
        step3.style.display = number === 3 ? 'flex' : 'none';

        indicators.forEach((indicator, index) => {
            if (!indicator) return;
            indicator.classList.toggle('active', index === number - 1);
            indicator.classList.toggle('completed', index < number - 1);
        });
        lines.forEach((line, index) => line?.classList.toggle('completed', index < number - 1));

        if (scroll) {
            const target = number === 1 ? step1 : number === 2 ? step2 : step3;
            target?.scrollIntoView({ behavior: 'smooth', block: 'start' });
        }
    }

    function setPaymentMessage(message = '', kind = '') {
        if (!paymentMessage) return;
        paymentMessage.textContent = message;
        paymentMessage.className = `payment-message${kind ? ` payment-message--${kind}` : ''}`;
    }

    function customerDetails() {
        const fullName = document.getElementById('fullName')?.value.trim() || '';
        const email = document.getElementById('email')?.value.trim() || '';
        const phoneInput = document.getElementById('phone');
        const phone = normalizePhilippineMobile(phoneInput?.value || '');
        const specialRequests = document.getElementById('requests')?.value.trim() || '';

        if (!fullName || !email || !phone) {
            throw new Error('Fill in your name, email, and valid Philippine mobile number.');
        }
        if (!/^\S+@\S+\.\S+$/.test(email)) {
            throw new Error('Please enter a valid email address.');
        }
        if (phoneInput) phoneInput.value = phone;

        return { full_name: fullName, email, phone, special_requests: specialRequests };
    }

    function checkoutPayload() {
        const customer = customerDetails();
        const bookingDate = bookingData.dateTime?.dateISO || formatDateForDatabase(bookingData.dateTime?.date);
        const bookingTime = formatTimeForDatabase(bookingData.dateTime?.time);
        const serviceId = Number(bookingData.service?.id || 0);
        const staffId = Number(bookingData.therapist?.id || 0);

        if (!serviceId || !bookingDate || !bookingTime) {
            throw new Error('Your booking selection is incomplete. Please choose a service, date, time, and therapist again.');
        }

        return {
            ...customer,
            service_id: serviceId,
            booking_date: bookingDate,
            booking_time: bookingTime,
            staff_id: staffId || null
        };
    }

    async function readJson(response) {
        const responseData = await response.json().catch(() => ({}));
        if (!response.ok) throw new Error(responseData.error || 'Something went wrong. Please try again.');
        return responseData;
    }

    function setQrPaymentStatus(message, kind = '') {
        if (!qrPaymentStatus) return;
        qrPaymentStatus.textContent = message;
        qrPaymentStatus.className = `qr-payment-modal__status${kind ? ` qr-payment-modal__status--${kind}` : ''}`;
    }

    function openQrPaymentPopup(payment) {
        if (!qrPaymentModal || !qrPaymentImage) return;
        qrPaymentImage.src = payment.imageUrl;
        setText('qrPaymentAmount', formatCurrency(payment.amount));
        setText('qrPaymentReference', payment.referenceNumber || '—');
        setQrPaymentStatus('Waiting for PayMongo to confirm your QR payment…');
        qrPaymentModal.hidden = false;
        document.body.classList.add('qr-payment-modal-open');
        closeQrPaymentModal?.focus();
    }

    function closeQrPaymentPopup() {
        if (!qrPaymentModal) return;
        qrPaymentModal.hidden = true;
        document.body.classList.remove('qr-payment-modal-open');
    }

    function stopQrPaymentPolling() {
        if (qrPollTimer) {
            window.clearTimeout(qrPollTimer);
            qrPollTimer = null;
        }
    }

    function qrImageSource(value) {
        const source = String(value || '').trim();
        if (/^data:image\/(png|jpeg|webp);base64,/i.test(source)) return source;
        if (/^[A-Za-z0-9+/=\r\n]+$/.test(source)) return `data:image/png;base64,${source.replace(/\s/g, '')}`;
        throw new Error('PayMongo did not return a usable QR image. Please try again.');
    }

    async function paymongoPublicRequest(url, publicKey, payload) {
        const response = await fetch(url, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Authorization': `Basic ${btoa(`${publicKey}:`)}`
            },
            body: JSON.stringify(payload)
        });
        const data = await response.json().catch(() => ({}));
        if (!response.ok) {
            const detail = data?.errors?.[0]?.detail || data?.errors?.[0]?.code || 'PayMongo could not prepare the QR code.';
            throw new Error(detail);
        }
        return data;
    }

    async function attachQrPaymentMethod(payment, customer) {
        const paymentMethod = await paymongoPublicRequest('https://api.paymongo.com/v1/payment_methods', payment.publicKey, {
            data: {
                attributes: {
                    type: 'qrph',
                    billing: {
                        name: customer.full_name,
                        email: customer.email,
                        phone: customer.phone
                    },
                    expiry_seconds: 1800
                }
            }
        });
        const paymentMethodId = paymentMethod?.data?.id;
        if (!paymentMethodId) throw new Error('PayMongo could not create the QR payment method. Please try again.');

        const paymentIntent = await paymongoPublicRequest(
            `https://api.paymongo.com/v1/payment_intents/${encodeURIComponent(payment.paymentIntentId)}/attach`,
            payment.publicKey,
            {
                data: {
                    attributes: {
                        payment_method: paymentMethodId,
                        client_key: payment.clientKey
                    }
                }
            }
        );
        return qrImageSource(paymentIntent?.data?.attributes?.next_action?.code?.image_url);
    }

    async function pollQrPayment(token) {
        if (!activeQrPayment || activeQrPayment.token !== token) return;
        try {
            const response = await fetch('../api/verify_paymongo_checkout.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-Token': csrfToken
                },
                body: JSON.stringify({ token })
            });
            const data = await response.json().catch(() => ({}));

            if (response.ok && data.success && data.booking) {
                stopQrPaymentPolling();
                closeQrPaymentPopup();
                showConfirmedBooking(data.booking);
                return;
            }

            if (data.state === 'pending') {
                setQrPaymentStatus('QR is ready. Waiting for PayMongo to confirm your payment…');
                qrPollTimer = window.setTimeout(() => pollQrPayment(token), 2500);
                return;
            }

            setQrPaymentStatus(data.error || 'Unable to check the payment yet. Retrying securely…', 'error');
            qrPollTimer = window.setTimeout(() => pollQrPayment(token), 5000);
        } catch (error) {
            setQrPaymentStatus('Connection interrupted. Retrying payment confirmation…', 'error');
            qrPollTimer = window.setTimeout(() => pollQrPayment(token), 5000);
        }
    }

    function startQrPaymentPolling(token) {
        stopQrPaymentPolling();
        pollQrPayment(token);
    }

    [closeQrPaymentModal, cancelQrPayment, qrPaymentModal?.querySelector('[data-qr-close]')]
        .filter(Boolean)
        .forEach((element) => element.addEventListener('click', closeQrPaymentPopup));

    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape' && !qrPaymentModal?.hidden) closeQrPaymentPopup();
    });

    nextButton?.addEventListener('click', (event) => {
        event.preventDefault();
        try {
            customerDetails();
            setPaymentMessage('');
            showStep(2);
        } catch (error) {
            alert(error.message);
        }
    });

    backButton?.addEventListener('click', () => showStep(1));

    // Temporary placeholder-QR flow. This is separate from the PayMongo
    // checkout handler below, which uses #payNowBtn when that UI is restored.
    const placeholderVerifyButton = document.getElementById('verifyBtn');
    const placeholderReviewBackButton = document.getElementById('reviewBackBtn');
    const placeholderConfirmBookingButton = document.getElementById('confirmBookingBtn');
    let placeholderBookingSubmissionInProgress = false;

    function showPlaceholderReview() {
        let customer;
        try {
            customer = customerDetails();
        } catch (error) {
            alert(error.message);
            return;
        }

        const bookingDate = bookingData.dateTime?.dateISO || formatDateForDatabase(bookingData.dateTime?.date);
        const bookingTime = formatTimeForDatabase(bookingData.dateTime?.time);
        setText('revService', bookingData.service?.name || 'Selected service');
        setText('revDate', formattedBookingDate(bookingDate) || bookingData.dateTime?.date || '-');
        setText('revTime', formattedBookingTime(bookingTime) || bookingData.dateTime?.time || '-');
        setText('revTherapist', bookingData.therapist?.name || 'To be assigned');
        setText('revName', customer.full_name);
        setText('revEmail', customer.email);
        setText('revPhone', customer.phone);
        setText('revRequests', customer.special_requests || 'None');
        setText('revServicePrice', formatCurrency(price));
        setText('revDownpayment', formatCurrency(downpayment));
        setText('revBalance', formatCurrency(price - downpayment));

        showStep(3);
    }

    placeholderVerifyButton?.addEventListener('click', (event) => {
        event.preventDefault();
        showPlaceholderReview();
    });

    placeholderReviewBackButton?.addEventListener('click', (event) => {
        event.preventDefault();
        showStep(2);
    });

    placeholderConfirmBookingButton?.addEventListener('click', async (event) => {
        event.preventDefault();
        if (placeholderBookingSubmissionInProgress) return;

        let payload;
        try {
            payload = checkoutPayload();
        } catch (error) {
            alert(error.message);
            return;
        }

        placeholderBookingSubmissionInProgress = true;
        const originalLabel = placeholderConfirmBookingButton.innerHTML;
        placeholderConfirmBookingButton.disabled = true;
        placeholderConfirmBookingButton.innerHTML = '<i class="fas fa-spinner fa-spin" style="margin-right:8px;"></i> Confirming Booking...';

        try {
            const response = await fetch('../api/create_placeholder_booking.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-Token': csrfToken
                },
                body: JSON.stringify(payload)
            });
            const data = await readJson(response);

            if (!data.success || !data.booking) {
                throw new Error(data.error || 'Unable to confirm the booking. Please try again.');
            }

            showConfirmedBooking(data.booking);
            const actionArea = placeholderConfirmBookingButton.closest('.payment-actions');
            if (actionArea) {
                actionArea.innerHTML = '<a href="bookings.php" class="btn-verify confirmed-booking-link"><i class="fas fa-calendar-check" style="margin-right:8px;"></i> View My Bookings</a>';
            }
        } catch (error) {
            alert(error.message || 'Unable to confirm the booking. Please try again.');
            placeholderBookingSubmissionInProgress = false;
            placeholderConfirmBookingButton.disabled = false;
            placeholderConfirmBookingButton.innerHTML = originalLabel;
        }
    });

    payNowButton?.addEventListener('click', async () => {
        if (activeQrPayment?.imageUrl) {
            openQrPaymentPopup(activeQrPayment);
            startQrPaymentPolling(activeQrPayment.token);
            return;
        }

        let qrPayload;
        let customer;
        try {
            qrPayload = checkoutPayload();
            customer = customerDetails();
        } catch (error) {
            setPaymentMessage(error.message, 'error');
            return;
        }

        const legacyOriginalLabel = payNowButton.innerHTML;
        payNowButton.disabled = true;
        payNowButton.innerHTML = '<i class="fas fa-spinner fa-spin" style="margin-right:8px;"></i> Preparing QR code...';
        setPaymentMessage('Preparing your one-time QR code securely...', 'loading');

        try {
            const response = await fetch('../api/create_paymongo_checkout.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-Token': csrfToken
                },
                body: JSON.stringify(qrPayload)
            });
            const data = await readJson(response);
            if (!data.payment_intent_id || !data.client_key || !data.public_key || !data.token) {
                throw new Error('PayMongo could not prepare the QR payment. Please try again.');
            }

            const payment = {
                paymentIntentId: data.payment_intent_id,
                clientKey: data.client_key,
                publicKey: data.public_key,
                token: data.token,
                referenceNumber: data.reference_number,
                amount: Number(data.downpayment_amount || downpayment)
            };
            const imageUrl = await attachQrPaymentMethod(payment, customer);
            activeQrPayment = { ...payment, imageUrl };
            setPaymentMessage('Scan the QR code in the popup. Your booking will confirm automatically after payment.', 'loading');
            openQrPaymentPopup(activeQrPayment);
            startQrPaymentPolling(activeQrPayment.token);
        } catch (error) {
            setPaymentMessage(error.message || 'Unable to prepare the secure QR payment. Please try again.', 'error');
        } finally {
            payNowButton.disabled = false;
            payNowButton.innerHTML = legacyOriginalLabel;
        }
        /* The former hosted-checkout implementation is intentionally kept
           disabled: QR Ph now renders inside the modal above. */
        /*
        let payload;
        try {
            payload = checkoutPayload();
        } catch (error) {
            setPaymentMessage(error.message, 'error');
            return;
        }

        const originalLabel = payNowButton.innerHTML;
        payNowButton.disabled = true;
        payNowButton.innerHTML = '<i class="fas fa-spinner fa-spin" style="margin-right:8px;"></i> Opening secure QR checkout...';
        setPaymentMessage('Connecting to PayMongo securely…', 'loading');

        try {
            const response = await fetch('../api/create_paymongo_checkout.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-Token': csrfToken
                },
                body: JSON.stringify(payload)
            });
            const data = await readJson(response);
            throw new Error('This legacy block is disabled.');
        } catch (error) {
            setPaymentMessage(error.message || 'Unable to open secure QR payment. Please try again.', 'error');
            payNowButton.disabled = false;
            payNowButton.innerHTML = originalLabel;
        }
        */
    });

    function formattedBookingDate(date) {
        if (!date) return '-';
        const localDate = new Date(`${date}T12:00:00`);
        return Number.isNaN(localDate.getTime())
            ? date
            : localDate.toLocaleDateString('en-PH', { month: 'long', day: 'numeric', year: 'numeric' });
    }

    function formattedBookingTime(time) {
        const match = String(time || '').match(/^(\d{1,2}):(\d{2})/);
        if (!match) return time || '-';
        const hour = Number(match[1]);
        const suffix = hour >= 12 ? 'PM' : 'AM';
        const displayHour = hour % 12 || 12;
        return `${displayHour}:${match[2]} ${suffix}`;
    }

    function showConfirmedBooking(booking) {
        const total = Number(booking.total_amount || 0);
        const paid = Math.round(total * 50) / 100;
        const bookingReference = booking.booking_id ? `CAT-${String(booking.booking_id).padStart(6, '0')}` : '-';

        setText('revService', booking.service_name || bookingData.service?.name || '-');
        setText('revDate', formattedBookingDate(booking.booking_date));
        setText('revTime', formattedBookingTime(booking.booking_time));
        setText('revTherapist', booking.therapist_name || 'To be assigned');
        setText('revName', booking.customer_name || document.getElementById('fullName')?.value || '-');
        setText('revEmail', booking.customer_email || document.getElementById('email')?.value || '-');
        setText('revPhone', booking.customer_phone || document.getElementById('phone')?.value || '-');
        setText('revRequests', booking.notes || 'None');
        setText('revServicePrice', formatCurrency(total));
        setText('revDownpayment', formatCurrency(paid));
        setText('revBalance', formatCurrency(total - paid));

        const title = step3?.querySelector('.review-header h2');
        const subtitle = step3?.querySelector('.review-subtitle');
        if (title) title.textContent = `Payment Confirmed · ${bookingReference}`;
        if (subtitle) subtitle.textContent = 'Your downpayment was received and your appointment is confirmed.';

        sessionStorage.removeItem('bookingData');
        showStep(3, false);
        step3?.scrollIntoView({ behavior: 'smooth', block: 'start' });
    }

    async function verifyReturnedPayment(token, attempt = 0) {
        const response = await fetch('../api/verify_paymongo_checkout.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-Token': csrfToken
            },
            body: JSON.stringify({ token })
        });
        const data = await response.json().catch(() => ({}));

        if (response.ok && data.success && data.booking) {
            showConfirmedBooking(data.booking);
            window.history.replaceState({}, document.title, window.location.pathname);
            return;
        }

        if (data.state === 'pending' && attempt < 4) {
            setPaymentMessage('Payment received. Confirming your booking…', 'loading');
            window.setTimeout(() => verifyReturnedPayment(token, attempt + 1), 1500);
            return;
        }

        setPaymentMessage(data.error || 'We could not confirm the payment yet. Please refresh shortly or contact the spa.', 'error');
        if (payNowButton) payNowButton.disabled = false;
    }

    const returnParameters = new URLSearchParams(window.location.search);
    const returnState = returnParameters.get('payment_return');
    const returnToken = returnParameters.get('token') || '';
    if (returnState === 'success' && /^[a-f0-9]{64}$/.test(returnToken)) {
        showStep(2, false);
        if (payNowButton) payNowButton.disabled = true;
        setPaymentMessage('Checking your PayMongo payment…', 'loading');
        verifyReturnedPayment(returnToken);
    } else if (returnState === 'cancel') {
        showStep(2, false);
        setPaymentMessage('Payment was not completed. No booking was created.', 'error');
        window.history.replaceState({}, document.title, window.location.pathname);
    }

    form?.addEventListener('keydown', (event) => {
        if (event.key === 'Enter' && event.target.tagName !== 'TEXTAREA') {
            event.preventDefault();
            nextButton?.click();
        }
    });
});
