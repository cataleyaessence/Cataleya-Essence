<?php
require_once __DIR__ . '/../config/database.php';

if (empty($_SESSION['admin_logged_in']) || empty($_SESSION['admin_id'])) {
    header('Location: signin.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Cataleya Essence of Beauty – Customer's Rating</title>
  <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;600&family=Inter:wght@300;400;500;600&display=swap" rel="stylesheet" />
  <link rel="stylesheet" href="../css/Admin-CustomerRating.css" />
  <link rel="stylesheet" href="../css/admin-sidebar.css" />
</head>

<body>

  <header class="navbar">
    <div class="navbar-brand">
      <div class="navbar-logo">
        <img src="../img/Rectangle 38 (1).png" class="logo-img" alt="Cataleya Essence of Beauty" />
      </div>
      <div class="navbar-brand-text">
        <span class="navbar-brand-name">Cataleya Essence</span>
        <span class="navbar-brand-sub">Admin Panel</span>
      </div>
    </div>
    <div class="navbar-user">
      <div class="user-info">
        <span class="user-name">Admin</span>
        <span class="user-role">Manager</span>
      </div>
      <div class="user-avatar">A</div>
    </div>
  </header>

  <div class="layout">

    <aside class="sidebar">
      <nav class="sidebar-nav">
                <a href="Admin-Sched.php" class="nav-link"><i class="fas fa-calendar-check"></i> Schedule</a>
                <a href="Admin-BookingStatus.php" class="nav-link"><i class="fas fa-check-circle"></i> Booking Status</a>
                <a href="Admin-Service.php" class="nav-link"><i class="fas fa-hand-sparkles"></i> Services</a>
                <a href="Admin-Staff.php" class="nav-link"><i class="fas fa-user-tie"></i> Staff</a>
                <a href="Admin-Analytics.php" class="nav-link active"><i class="fas fa-chart-pie"></i> Analytics Reports</a>
                <a href="Admin-ActivityLog.php" class="nav-link"><i class="fas fa-clipboard-list"></i> Activity Log</a>
                <a href="Admin-Settings.php" class="nav-link"><i class="fas fa-cog"></i> Settings</a>
            </nav>
      <div class="sidebar-footer">
        <a href="../auth/logout.php" class="nav-link logout" id="logoutBtn">
          <i class="fas fa-sign-out-alt"></i> Logout
        </a>
      </div>
    </aside>

    <!-- ── MAIN CONTENT ── -->
    <main class="main">

      <!-- Page Header -->
      <div class="page-header">
        <div class="page-header-left">
          <h1 class="page-title">Customer's Rating</h1>
          <p class="page-sub">Monitor customer feedback and service quality</p>
        </div>
        <button class="export-btn" id="exportBtn">Export Reviews</button>
      </div>

      <div class="ratings-card-grid">

        <div class="score-card">
          <div class="score-big">4.8</div>
          <div class="score-stars" id="summaryStars"></div>
          <div class="score-count">Based on <strong>247</strong> Reviews</div>
          <div class="bar-list" id="barList"></div>
        </div>

        <div class="reviews-panel">
          <div class="reviews-header">
            <h2 class="reviews-title">Recent Reviews</h2>
            <div class="filter-wrap">
              <select class="filter-select" id="filterSelect">
                <option value="all">All ratings ↓</option>
                <option value="5">5 Stars</option>
                <option value="4">4 Stars</option>
                <option value="3">3 Stars</option>
                <option value="2">2 Stars</option>
                <option value="1">1 Star</option>
              </select>
            </div>
          </div>
          <div class="review-list" id="reviewList"></div>
        </div>

      </div>
    </main>
  </div>

  <script>
    /* ── Data ── */
    const ratingBreakdown = [{
        stars: 5,
        count: 198
      },
      {
        stars: 4,
        count: 38
      },
      {
        stars: 3,
        count: 8
      },
      {
        stars: 2,
        count: 2
      },
      {
        stars: 1,
        count: 1
      },
    ];

    const reviews = [{
        name: "Nell Sta. Maria",
        service: "Deep Tissue Massage",
        staff: "Emma Wilson",
        date: "January 10, 2026",
        rating: 5,
        text: "Absolutely amazing experience! Emma was professional and the massage was exactly what I needed. The ambiance was perfect and I left feeling completely relaxed.",
        initials: "NS"
      },
      {
        name: "Michael Chen",
        service: "Facial Glow Treatment",
        staff: "Olivia Santos",
        date: "January 10, 2026",
        rating: 4,
        text: "Outstanding facial treatment! Olivia explained every step and my skin has never looked better. Highly recommend this clinic for anyone looking for quality skincare.",
        initials: "MC"
      },
      {
        name: "Princess Reyes",
        service: "Deep Tissue Massage",
        staff: "Emma Wilson",
        date: "January 10, 2026",
        rating: 5,
        text: "Absolutely amazing experience! Emma was professional and the massage was exactly what I needed. The ambiance was perfect and I left feeling completely relaxed.",
        initials: "PR"
      },
      {
        name: "Sofia Dela Cruz",
        service: "Facial Glow Treatment",
        staff: "Olivia Santos",
        date: "January 9, 2026",
        rating: 5,
        text: "Best facial I've ever had! The products used were top quality and the results were visible immediately. Will definitely come back for more sessions.",
        initials: "SD"
      },
      {
        name: "James Tan",
        service: "Hot Stone Massage",
        staff: "Lily Cruz",
        date: "January 8, 2026",
        rating: 4,
        text: "Very relaxing session. The hot stones were therapeutic and Lily was attentive throughout. The only minor thing was the wait time at reception.",
        initials: "JT"
      },
      {
        name: "Angela Lim",
        service: "Aromatherapy",
        staff: "Emma Wilson",
        date: "January 7, 2026",
        rating: 5,
        text: "Pure bliss from start to finish. The scents were calming and Emma customized everything to my preferences. A truly luxurious experience.",
        initials: "AL"
      },
      {
        name: "Camille Bautista",
        service: "Swedish Massage",
        staff: "Lily Cruz",
        date: "January 6, 2026",
        rating: 5,
        text: "I came in feeling stressed and left feeling like a new person. Lily's technique is impeccable and the salon atmosphere is so calming and beautiful.",
        initials: "CB"
      },
      {
        name: "Rodrigo Mendoza",
        service: "Hot Stone Massage",
        staff: "Olivia Santos",
        date: "January 5, 2026",
        rating: 3,
        text: "The massage itself was good but I felt the session was a bit rushed. The staff was polite and the place is clean. Would try again to give it a fair chance.",
        initials: "RM"
      },
      {
        name: "Patricia Santos",
        service: "Aromatherapy",
        staff: "Emma Wilson",
        date: "January 4, 2026",
        rating: 5,
        text: "Emma has magic hands! The lavender aromatherapy session completely melted away my tension headache. I booked my next appointment before I even left the salon.",
        initials: "PS"
      },
      {
        name: "Kevin Garcia",
        service: "Deep Tissue Massage",
        staff: "Lily Cruz",
        date: "January 3, 2026",
        rating: 4,
        text: "Great deep tissue work — Lily really knew exactly where the problem areas were. I felt sore the next day which is normal but then felt incredible. Highly recommend.",
        initials: "KG"
      },
      {
        name: "Marie Villanueva",
        service: "Facial Glow Treatment",
        staff: "Olivia Santos",
        date: "January 2, 2026",
        rating: 5,
        text: "My skin literally glowed after the treatment! Olivia was thorough and gentle, and she explained every product she used. I've never felt more pampered in my life.",
        initials: "MV"
      },
      {
        name: "Danielle Flores",
        service: "Swedish Massage",
        staff: "Emma Wilson",
        date: "January 1, 2026",
        rating: 5,
        text: "What a perfect way to start the new year! The salon was beautifully decorated and Emma gave the most relaxing Swedish massage. I am already a loyal customer.",
        initials: "DF"
      },
      {
        name: "Roberto Aquino",
        service: "Hot Stone Massage",
        staff: "Lily Cruz",
        date: "December 30, 2025",
        rating: 4,
        text: "Came here with my wife as a year-end treat and we both loved it. The hot stone massage was deeply relaxing and the ambiance felt very premium and calming.",
        initials: "RA"
      },
      {
        name: "Isabelle Torres",
        service: "Aromatherapy",
        staff: "Olivia Santos",
        date: "December 28, 2025",
        rating: 5,
        text: "I've been to many spas but Cataleya Essence is truly on another level. The attention to detail, the scents, the music — every element was perfectly curated for relaxation.",
        initials: "IT"
      },
      {
        name: "Marco Espinosa",
        service: "Deep Tissue Massage",
        staff: "Emma Wilson",
        date: "December 26, 2025",
        rating: 2,
        text: "Unfortunately my experience was not what I expected. The room temperature was too cold and the pressure was inconsistent. I hope this improves in my next visit.",
        initials: "ME"
      },
      {
        name: "Lyra Castillo",
        service: "Facial Glow Treatment",
        staff: "Lily Cruz",
        date: "December 24, 2025",
        rating: 5,
        text: "Best Christmas gift I gave myself! Lily was so attentive and professional. My skin looked radiant for the holiday parties. Will be back every month without question.",
        initials: "LC"
      },
    ];

    /* ── Render Summary Stars ── */
    const summaryStars = document.getElementById('summaryStars');
    for (let i = 1; i <= 5; i++) {
      const s = document.createElement('span');
      s.className = 'star';
      s.textContent = '★';
      summaryStars.appendChild(s);
    }

    /* ── Render Rating Bars ── */
    const total = ratingBreakdown.reduce((a, b) => a + b.count, 0);
    const barList = document.getElementById('barList');
    ratingBreakdown.forEach(({
      stars,
      count
    }) => {
      const pct = Math.round((count / total) * 100);
      barList.innerHTML += `
        <div class="bar-row">
          <span class="bar-label">${stars}</span>
          <div class="bar-track">
            <div class="bar-fill" style="width:${pct}%"></div>
          </div>
          <span class="bar-count">${count}</span>
        </div>`;
    });

    /* ── Render Reviews ── */
    function renderReviews(filter) {
      const list = document.getElementById('reviewList');
      const filtered = filter === 'all' ? reviews : reviews.filter(r => r.rating === parseInt(filter));
      list.innerHTML = filtered.map(r => {
        const stars = Array.from({
            length: 5
          }, (_, i) =>
          `<span class="star${i < r.rating ? '' : ' empty'}">★</span>`
        ).join('');
        return `
          <div class="review-card">
            <div class="review-top">
              <div class="review-user">
                <div class="review-avatar">${r.initials}</div>
                <div>
                  <div class="review-name">${r.name}</div>
                  <div class="review-service">${r.service} • ${r.staff}</div>
                </div>
              </div>
              <span class="review-date">${r.date}</span>
            </div>
            <div class="review-stars">${stars}</div>
            <p class="review-text">${r.text}</p>
          </div>`;
      }).join('');
    }

    renderReviews('all');

    document.getElementById('filterSelect').addEventListener('change', function() {
      renderReviews(this.value);
    });
  </script>
</body>

</html>
