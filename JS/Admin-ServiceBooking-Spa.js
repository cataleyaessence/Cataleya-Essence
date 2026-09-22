// ─── SEARCH FILTER ───
const searchInput = document.getElementById('searchService');

if (searchInput) {
    searchInput.addEventListener('input', function(e) {
        const query = e.target.value.toLowerCase().trim();
        const cards = document.querySelectorAll('.booking-card');

        cards.forEach(card => {
            const name = card.querySelector('.service-name')?.textContent?.toLowerCase() || '';
            const desc = card.querySelector('.service-desc')?.textContent?.toLowerCase() || '';
            const match = name.includes(query) || desc.includes(query);
            card.style.display = match ? '' : 'none';
        });
    });
}

// ─── OPTIONAL: Log card clicks ───
document.querySelectorAll('.booking-card').forEach(card => {
    card.addEventListener('click', function(e) {
        // Don't trigger if clicking the button (button has its own handler)
        if (e.target.closest('.btn-action')) return;

        const name = this.querySelector('.service-name')?.textContent || '';
        console.log(`Selected: ${name}`);
        // You can add navigation or modal logic here
    });
});