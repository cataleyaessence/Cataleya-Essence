document.addEventListener('DOMContentLoaded', function() {

    // ─── HAMBURGER MENU ──────────────────────────────────────
    // ─── CHARACTER COUNTER ──────────────────────────────────
    const messageField = document.getElementById('message');
    const charCountSpan = document.getElementById('charCount');

    if (messageField && charCountSpan) {
        const maxLength = parseInt(messageField.getAttribute('maxlength')) || 500;

        messageField.addEventListener('input', function() {
            const currentLength = this.value.length;
            charCountSpan.textContent = currentLength;

            if (currentLength > maxLength * 0.9) {
                charCountSpan.style.color = '#E23F5B';
            } else {
                charCountSpan.style.color = 'var(--text-muted)';
            }
        });

        charCountSpan.textContent = '0';
    }

    // ─── FORM SUBMIT ────────────────────────────────────────
    const form = document.getElementById('contactForm');

    if (form) {
        form.addEventListener('submit', function(e) {
            e.preventDefault();

            const name = document.getElementById('fullName').value.trim();
            const email = document.getElementById('email').value.trim();
            const phone = document.getElementById('phone').value.trim();
            const message = document.getElementById('message').value.trim();

            if (!name || !email || !phone || !message) {
                alert('Please fill in all required fields.');
                return;
            }

            if (!email.includes('@') || !email.includes('.')) {
                alert('Please enter a valid email address.');
                return;
            }

            alert(
                '✅ Message Sent!\n\n' +
                `Thank you, ${name}! We have received your message and will get back to you within 24 hours.\n\n` +
                `Reference: We'll contact you at ${email} or ${phone}.`
            );

            form.reset();
            charCountSpan.textContent = '0';
        });
    }

    // ─── GOOGLE MAPS ────────────────────────────────────────
    const LAT = 15.3125288;
    const LNG = 120.9473276;
    const ADDRESS = 'Building J, Malgapo St., San Vicente, Gapan City, Nueva Ecija, Philippines, 3105';

    const mapContainer = document.getElementById('mapContainer');
    const fallback = document.getElementById('mapFallback');

    function loadMap() {
        // Google Maps embed URL — no API key required, fully interactive
        // (pan, zoom, click through to Google Maps).
        const iframe = document.createElement('iframe');
        iframe.src =
            `https://www.google.com/maps?q=${LAT},${LNG}&z=16&output=embed`;
        iframe.allowFullscreen = true;
        iframe.loading = 'lazy';
        iframe.referrerPolicy = 'no-referrer-when-downgrade';
        iframe.title = 'Cataleya Essence Location Map';

        iframe.addEventListener('load', function() {
            if (fallback) {
                fallback.style.display = 'none';
            }
        });

        iframe.addEventListener('error', function() {
            showFallback();
        });

        mapContainer.appendChild(iframe);
    }

    function showFallback() {
        if (!fallback) return;
        fallback.innerHTML = `
            <i class="fas fa-map-location-dot"></i>
            <p>Map unavailable right now.</p>
            <p style="margin-top:4px;">
                <a href="https://www.google.com/maps/search/?api=1&query=${encodeURIComponent(ADDRESS)}" target="_blank" rel="noopener">
                    Open in Google Maps
                </a>
            </p>
        `;
        fallback.style.display = 'flex';
    }

    loadMap();

    // ─── LOCATION PIN INTERACTIVITY ──────────────────────
    const pins = document.querySelectorAll('.map-location-pin');
    pins.forEach(pin => {
        pin.addEventListener('click', function() {
            const label = this.querySelector('.pin-label');
            if (label) {
                const query = encodeURIComponent(label.textContent.trim() + ', Gapan City, Philippines');
                window.open(`https://www.google.com/maps/search/?api=1&query=${query}`, '_blank');
            }
        });
    });

});
