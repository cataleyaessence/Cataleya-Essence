// ════════════════════════════════════════
//  Cataleya Essence – Customer Ratings JS
// ════════════════════════════════════════

// ── Rating distribution data ──
const distribution = { 5: 198, 4: 38, 3: 8, 2: 2, 1: 1 };
const totalReviews  = Object.values(distribution).reduce((a, b) => a + b, 0);

// ── Reviews data ──
const reviews = [
  {
    name:    'Nell Sta Maria',
    service: 'Deep Tissue Massage',
    staff:   'Emma Wilson',
    date:    'January 10, 2026',
    rating:  5,
    text:    'Absolutely amazing experience! Emma was professional and the massage was exactly what I needed. The ambiance was perfect and I left feeling completely relaxed.',
  },
  {
    name:    'Michael Chen',
    service: 'Facial Treatment',
    staff:   'Emma Wilson',
    date:    'January 10, 2026',
    rating:  4,
    text:    'Outstanding facial treatment! Olivia explained every step and my skin has never looked better. Highly recommend this clinic for anyone looking for quality skincare.',
  },
  {
    name:    'Nell Sta Maria',
    service: 'Deep Tissue Massage',
    staff:   'Emma Wilson',
    date:    'January 10, 2026',
    rating:  5,
    text:    'Absolutely amazing experience! Emma was professional and the massage was exactly what I needed. The ambiance was perfect and I left feeling completely relaxed.',
  },
  {
    name:    'Sarah Reyes',
    service: 'Manicure & Pedicure',
    staff:   'Lily Cruz',
    date:    'January 8, 2026',
    rating:  5,
    text:    'Lily did an incredible job! My nails look absolutely stunning. Will definitely be coming back every month.',
  },
  {
    name:    'Jose Mendoza',
    service: 'Hot Stone Massage',
    staff:   'Emma Wilson',
    date:    'January 7, 2026',
    rating:  3,
    text:    'The massage was okay. I expected a bit more pressure but the ambiance was relaxing.',
  },
  {
    name:    'Ana Torres',
    service: 'Hair Coloring',
    staff:   'Grace Lim',
    date:    'January 5, 2026',
    rating:  5,
    text:    'Grace is a genius with color! I came in wanting a balayage and left looking like a model. Exceeded every expectation.',
  },
];

// ── Helpers ──

// Get initials from full name
function getInitials(name) {
  return name.split(' ').slice(0, 2).map(n => n[0]).join('').toUpperCase();
}

// Render star icons (filled / empty)
function renderStars(rating, container) {
  container.innerHTML = '';
  for (let i = 1; i <= 5; i++) {
    const s = document.createElement('span');
    s.className = i <= rating ? 'star' : 'star empty';
    s.textContent = '★';
    container.appendChild(s);
  }
}

// ── Build rating bars ──
function buildBars() {
  const list = document.getElementById('barList');
  list.innerHTML = '';

  [5, 4, 3, 2, 1].forEach(star => {
    const count   = distribution[star] || 0;
    const pct     = totalReviews > 0 ? (count / totalReviews) * 100 : 0;
    const isLow   = star <= 2;

    const row = document.createElement('div');
    row.className = 'bar-row';

    row.innerHTML = `
      <span class="bar-num">${star}</span>
      <div class="bar-track">
        <div class="bar-fill${isLow ? ' low' : ''}" style="width: 0%" data-width="${pct.toFixed(1)}%"></div>
      </div>
      <span class="bar-count">${count}</span>
    `;
    list.appendChild(row);
  });

  // Animate bars in
  requestAnimationFrame(() => {
    document.querySelectorAll('.bar-fill').forEach(fill => {
      fill.style.width = fill.dataset.width;
    });
  });
}

// ── Build summary stars ──
function buildSummaryStars() {
  const container = document.getElementById('summaryStars');
  const rating    = 4.8;
  container.innerHTML = '';
  for (let i = 1; i <= 5; i++) {
    const s = document.createElement('span');
    s.className  = i <= Math.round(rating) ? 'star' : 'star empty';
    s.textContent = '★';
    container.appendChild(s);
  }
}

// ── Build review cards ──
function buildReviews(filterRating = 'all') {
  const list   = document.getElementById('reviewList');
  list.innerHTML = '';

  const filtered = filterRating === 'all'
    ? reviews
    : reviews.filter(r => r.rating === parseInt(filterRating));

  if (filtered.length === 0) {
    const empty = document.createElement('div');
    empty.className  = 'reviews-empty';
    empty.textContent = 'No reviews found for this rating.';
    list.appendChild(empty);
    return;
  }

  filtered.forEach(review => {
    const card = document.createElement('div');
    card.className = 'review-card';

    // Avatar
    const avatarDiv = document.createElement('div');
    avatarDiv.className   = 'reviewer-avatar';
    avatarDiv.textContent = getInitials(review.name);

    // Stars container
    const starsDiv = document.createElement('div');
    starsDiv.className = 'review-stars';
    renderStars(review.rating, starsDiv);

    card.innerHTML = `
      <div class="review-top">
        <div class="review-left">
          <div class="reviewer-avatar">${getInitials(review.name)}</div>
          <div class="reviewer-info">
            <span class="reviewer-name">${review.name}</span>
            <span class="reviewer-service">${review.service} • ${review.staff}</span>
          </div>
        </div>
        <span class="review-date">${review.date}</span>
      </div>
      <div class="review-stars-wrap"></div>
      <p class="review-body">${review.text}</p>
    `;

    // Insert stars into the wrap
    const starsWrap = card.querySelector('.review-stars-wrap');
    const starsEl   = document.createElement('div');
    starsEl.className = 'review-stars';
    renderStars(review.rating, starsEl);
    starsWrap.appendChild(starsEl);

    list.appendChild(card);
  });
}

// ── Filter handler ──
document.getElementById('filterSelect').addEventListener('change', (e) => {
  buildReviews(e.target.value);
});

// ── Export handler (demo) ──
document.getElementById('exportBtn').addEventListener('click', () => {
  // Build CSV content
  const headers = ['Name', 'Service', 'Staff', 'Date', 'Rating', 'Review'];
  const rows    = reviews.map(r => [
    `"${r.name}"`,
    `"${r.service}"`,
    `"${r.staff}"`,
    `"${r.date}"`,
    r.rating,
    `"${r.text.replace(/"/g, '""')}"`,
  ].join(','));

  const csv  = [headers.join(','), ...rows].join('\n');
  const blob = new Blob([csv], { type: 'text/csv' });
  const url  = URL.createObjectURL(blob);
  const a    = document.createElement('a');
  a.href     = url;
  a.download = 'cataleya-reviews.csv';
  a.click();
  URL.revokeObjectURL(url);
});

// ── Init ──
buildSummaryStars();
buildBars();
buildReviews();