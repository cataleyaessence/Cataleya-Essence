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

    if (!filterButtons.length || !bookingItems.length || !noBookingsMessage) {
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
            applyStatusFilter(status);
        });
    });

    var defaultStatus = 'all';
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
});
