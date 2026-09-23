document.addEventListener('DOMContentLoaded', function () {
    var logoutBtn = document.getElementById('logoutBtn');
    if (logoutBtn) {
        logoutBtn.addEventListener('click', function (event) {
            event.preventDefault();
            window.location.href = '../auth/logout.php';
        });
    }

    var filterButtons = document.querySelectorAll('.filter-button');
    var bookingItems = document.querySelectorAll('.previous-booking-item');
    var noBookingsMessage = document.querySelector('.no-bookings-message');
    var filterStorageKey = 'cataleya-admin-booking-status-filter';

    if (!filterButtons.length || !noBookingsMessage) {
        startLiveBookingUpdates();
        return;
    }

    function applyStatusFilter(status) {
        var visibleCount = 0;

        bookingItems.forEach(function (item) {
            var itemStatus = item.getAttribute('data-status');
            var shouldShow = status === 'all' || itemStatus === status;

            item.hidden = !shouldShow;
            item.classList.toggle('is-filtered-out', !shouldShow);

            if (shouldShow) {
                visibleCount += 1;
            }
        });

        noBookingsMessage.hidden = visibleCount > 0;
    }

    filterButtons.forEach(function (button) {
        button.addEventListener('click', function () {
            filterButtons.forEach(function (btn) {
                btn.classList.toggle('active', btn === button);
            });

            var status = button.getAttribute('data-status');
            sessionStorage.setItem(filterStorageKey, status);
            applyStatusFilter(status);
        });
    });

    var summaryCards = document.querySelectorAll('.status-summary-card[data-status]');
    summaryCards.forEach(function (card) {
        card.addEventListener('click', function () {
            var status = card.getAttribute('data-status');
            filterButtons.forEach(function (btn) {
                btn.classList.toggle('active', btn.getAttribute('data-status') === status);
            });
            summaryCards.forEach(function (summaryCard) {
                summaryCard.classList.toggle('active', summaryCard === card);
            });
            sessionStorage.setItem(filterStorageKey, status);
            applyStatusFilter(status);
        });
    });

    var savedStatus = sessionStorage.getItem(filterStorageKey);
    var defaultStatus = Array.from(filterButtons).some(function (button) {
        return button.getAttribute('data-status') === savedStatus;
    }) ? savedStatus : 'all';
    var defaultButton = Array.from(filterButtons).find(function (btn) {
        return btn.getAttribute('data-status') === defaultStatus;
    }) || filterButtons[0];

    if (defaultButton) {
        defaultButton.classList.add('active');
    }
    summaryCards.forEach(function (card) {
        card.classList.remove('active');
    });
    applyStatusFilter(defaultStatus);

    startLiveBookingUpdates();

    function startLiveBookingUpdates() {
        var snapshotSignature = document.body.getAttribute('data-booking-snapshot') || '';
        var liveStatus = document.getElementById('bookingLiveStatus');
        var isPolling = false;

        window.setInterval(async function () {
            if (document.hidden || isPolling) {
                return;
            }

            isPolling = true;
            if (liveStatus) {
                liveStatus.classList.add('is-connecting');
            }

            try {
                var response = await fetch('Admin-BookingStatus.php?action=snapshot', {
                    cache: 'no-store',
                    credentials: 'same-origin',
                    headers: { 'Accept': 'application/json' }
                });
                var data = await response.json();

                if (data.success && data.signature && snapshotSignature && data.signature !== snapshotSignature) {
                    window.location.reload();
                    return;
                }
                if (data.success && data.signature) {
                    snapshotSignature = data.signature;
                }
            } catch (error) {
                // Keep the existing admin page usable if a temporary network
                // problem prevents a background update check.
                console.warn('Live booking update check failed.', error);
            } finally {
                isPolling = false;
                if (liveStatus) {
                    liveStatus.classList.remove('is-connecting');
                }
            }
        }, 5000);
    }
});
