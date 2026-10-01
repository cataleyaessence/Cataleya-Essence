document.addEventListener('DOMContentLoaded', function () {
    const searchInput = document.getElementById('recordSearch');
    const rows = Array.from(document.querySelectorAll('#recordsTableBody [data-record-search]'));
    const displayedCount = document.querySelector('.records-count strong');
    const noMatches = document.getElementById('clientSearchEmpty');

    if (!searchInput || rows.length === 0) {
        return;
    }

    function filterRecords() {
        const query = searchInput.value.trim().toLocaleLowerCase();
        let visibleCount = 0;

        rows.forEach(function (row) {
            const recordText = (row.dataset.recordSearch || '').toLocaleLowerCase();
            const isMatch = query === '' || recordText.includes(query);
            row.hidden = !isMatch;

            if (isMatch) {
                visibleCount += 1;
            }
        });

        if (displayedCount) {
            displayedCount.textContent = visibleCount.toLocaleString();
        }

        if (noMatches) {
            noMatches.hidden = visibleCount !== 0;
        }
    }

    searchInput.addEventListener('input', filterRecords);
    filterRecords();
});
