<?php
require_once 'config/database.php';

// Get current date info
$current_year = date('Y');
$current_month = date('n');
$today = date('Y-m-d');

// Get first and last day of current month
$first_day = new DateTime("$current_year-$current_month-01");
$last_day = new DateTime("$current_year-$current_month-" . $first_day->format('t'));
$days_in_month = $first_day->format('t');

// Fetch daily slot availability for current month
$stmt = $pdo->prepare("
    SELECT slot_date, status
    FROM daily_slot_availability
    WHERE slot_date >= ? AND slot_date <= ?
    GROUP BY slot_date, status
");
$stmt->execute([$first_day->format('Y-m-d'), $last_day->format('Y-m-d')]);
$daily_availability = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

// Fetch time slots
$stmt = $pdo->prepare("SELECT id, slot_time, display_time, is_active FROM time_slots WHERE is_active = 1 ORDER BY sort_order");
$stmt->execute();
$time_slots = $stmt->fetchAll();

// Fetch user's bookings for the current month (if logged in)
$user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;
$booking_dates = [];
if ($user_id) {
    $stmt = $pdo->prepare("
        SELECT b.booking_date, b.booking_time, b.status, s.name as service_name
        FROM bookings b
        JOIN services s ON b.service_id = s.id
        WHERE b.user_id = ? AND b.booking_date >= ? AND b.booking_date <= ?
        ORDER BY b.booking_date, b.booking_time
    ");
    $stmt->execute([$user_id, $first_day->format('Y-m-d'), $last_day->format('Y-m-d')]);
    $user_bookings = $stmt->fetchAll();

    // Create a map of booking dates for easy lookup
    foreach ($user_bookings as $booking) {
        $date = $booking['booking_date'];
        if (!isset($booking_dates[$date])) {
            $booking_dates[$date] = [];
        }
        $booking_dates[$date][] = $booking;
    }
}

// Get availability for a specific date (today or next available)
$selected_date = date('Y-m-d');
$stmt = $pdo->prepare("
    SELECT d.slot_id, d.status, d.max_bookings, d.current_bookings, t.display_time
    FROM daily_slot_availability d
    JOIN time_slots t ON d.slot_id = t.id
    WHERE d.slot_date = ? AND t.is_active = 1
    ORDER BY t.sort_order
");
$stmt->execute([$selected_date]);
$selected_date_slots = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Cataleya Essence of Beauty</title>
  <link rel="stylesheet" href="css/home.css" />
  <link href="https://fonts.googleapis.com/css2?family=DM+Serif+Display:ital@0;1&family=DM+Sans:wght@300;400;500;600&display=swap" rel="stylesheet" />
  <link rel="icon" href="img/Rectangle 38 (1).png" />
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" />
</head>
<body>

 
  <header class="navbar">

    <div class="navbar__logo">
      <img src="img/Rectangle 38 (1).png" class="logo-img" alt="Cataleya Essence of Beauty">
      <div class="logo-text">
        <span class="logo-name">Cataleya Essence</span>
        <span class="logo-sub">of Beauty</span>
      </div>
    </div>

    <nav class="navbar__links" id="nav-links">
      <a href="index.php" class="nav-link active">Home</a>
      <a href="Php/signup.php" class="nav-link">About Us</a>
      <a href="Php/signup.php" class="nav-link">Services</a>
      <a href="Php/signup.php" class="nav-link">Login</a>
    </nav>

    <a href="Php/signup.php" class="btn-book">Book Now</a>

    <button class="hamburger" id="hamburger" aria-label="Open menu" aria-expanded="false">
      <span></span>
      <span></span>
      <span></span>
    </button>
  </header>


  <section class="hero">

    <div class="hero__left">
      <p class="hero__eyebrow">Essence of Beauty</p>
      <h1 class="hero__title">Your Sanctuary of Wellness &amp; Beauty</h1>
      <p class="hero__body">
        Experience transformative treatments in our luxurious spa. From HIFU ultherapy to signature massages, discover holistic beauty and wellness.
      </p>
      <div class="hero__btns">
        <a href="Php/signup.php" class="btn btn--solid">Book Appointment</a>
        <a href="Php/signup.php" class="btn btn--ghost">View Services</a>
      </div>
    </div>

    <div class="hero__right">
      <img
        src="img/image 1.png"
        alt="Woman receiving spa treatment"
        class="hero__photo"
        onerror="this.classList.add('hero__photo--placeholder')"
      />
    </div>

  </section>

  <!-- ========== SERVICES SECTION ========== -->
  <section class="services" id="services">

    <div class="services__header">
      <p class="services__eyebrow">Our Signature</p>
      <h2 class="services__title">Real-Time Availability</h2>
    </div>

    <div class="services__grid">

      <!-- Card 1 -->
      <article class="svc-card">
        <div class="svc-card__img-wrap">
          <img src="img/Rectangle 461.png" alt="Beauty Aesthetic"
               onerror="this.style.background='linear-gradient(135deg,#fce4ec,#f48fb1)';this.removeAttribute('src')" />
          <span class="svc-card__tag">Beauty Aesthetic</span>
        </div>
        <h3 class="svc-card__title">Cataleya Essence of Beauty And Wellness Center</h3>
        <p class="svc-card__desc">A range of professional treatments designed to enhance natural beauty while promoting skin health and overall wellness. These services focus on improving skin condition, rejuvenating the body, and helping clients feel more confident and refreshed through modern aesthetic techniques and relaxing care.</p>
      </article>

      <!-- Card 2 -->
      <article class="svc-card">
        <div class="svc-card__img-wrap">
          <img src="img/Rectangle 462.png" alt="Spa Massage"
               onerror="this.style.background='linear-gradient(135deg,#fff8e1,#ffcc80)';this.removeAttribute('src')" />
          <span class="svc-card__tag">Spa Massage</span>
        </div>
        <h3 class="svc-card__title">River Serenity Wellness Spa</h3>
        <p class="svc-card__desc">Discover our comprehensive range of beauty and wellness treatments, each designed to enhance your natural radiance and promote holistic well-being.</p>
      </article>

    </div>

    <div class="services__cta">
      <a href="Php/signup.php" class="btn-view-all">View all Services</a>
    </div>

  </section>
          </div> <!-- /services__cta -->

    </section> <!-- /services -->

    <!-- ========== HALL OF FAME — NEW SECTION ========== -->
    <section class="hall-of-fame" id="hall-of-fame">

        <div class="hall-of-fame__header">
            <h2 class="hall-of-fame__title">Hall of Fame</h2>
            <p class="hall-of-fame__subtitle">Top Members Leaderboard</p>
            <p class="hall-of-fame__desc">
                Our most dedicated wellness enthusiasts. Could you be next on this list?
            </p>
        </div>

               <!-- Top 3 Members - Column Heights -->
        <div class="hall-of-fame__top3">

            <!-- Rank 2 (Silver) -->
            <div class="top-member top-member--rank2">
                <span class="top-member__rank">#2</span>
                <span class="top-member__name">Isobel Chen</span>
                <span class="top-member__points"><strong>3.80</strong> pt</span>
            </div>

            <!-- Rank 1 (Gold - tallest) -->
            <div class="top-member top-member--rank1">
                <span class="top-member__crown"></span>
                <span class="top-member__rank">#1</span>
                <span class="top-member__name">Sneha Williamson</span>
                <span class="top-member__points"><strong>48.20</strong> pt</span>
            </div>

            <!-- Rank 3 (Bronze) -->
            <div class="top-member top-member--rank3">
                <span class="top-member__rank">#3</span>
                <span class="top-member__name">Olivia Martinez</span>
                <span class="top-member__points"><strong>1.41</strong> pt</span>
            </div>

        </div>

        <!-- Table -->
        <div class="hall-of-fame__table-wrap">
            <table class="hall-of-fame__table">
                <thead>
                    <tr>
                        <th>Rank</th>
                        <th>Members</th>
                        <th>HR</th>
                        <th>POINTS</th>
                        <th>VISITS</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td class="rank-col">1</td>
                        <td class="member-col">Saphia Williamson</td>
                        <td class="hr-col hr-platinum">Platinum</td>
                        <td class="points-col">48.20 pts</td>
                        <td class="visits-col">47</td>
                    </tr>
                    <tr>
                        <td class="rank-col">2</td>
                        <td class="member-col">Isobel Chen</td>
                        <td class="hr-col hr-platinum">Platinum</td>
                        <td class="points-col">39.50 pts</td>
                        <td class="visits-col">39</td>
                    </tr>
                    <tr>
                        <td class="rank-col">3</td>
                        <td class="member-col">Olivia Martinez</td>
                        <td class="hr-col hr-platinum">Platinum</td>
                        <td class="points-col">34.10 pts</td>
                        <td class="visits-col">33</td>
                    </tr>
                    <tr>
                        <td class="rank-col">4</td>
                        <td class="member-col">Emara Thompson</td>
                        <td class="hr-col hr-gold">Gold</td>
                        <td class="points-col">12.60</td>
                        <td class="visits-col">22</td>
                    </tr>
                    <tr>
                        <td class="rank-col">5</td>
                        <td class="member-col">Ana Patel</td>
                        <td class="hr-col hr-gold">Gold</td>
                        <td class="points-col">1.07 pts</td>
                        <td class="visits-col">19</td>
                    </tr>
                    <tr>
                        <td class="rank-col">6</td>
                        <td class="member-col">Charlotte Davis</td>
                        <td class="hr-col hr-gold">Gold</td>
                        <td class="points-col">1.02 pts</td>
                        <td class="visits-col">17</td>
                    </tr>
                    <tr>
                        <td class="rank-col">7</td>
                        <td class="member-col">Mia Anderson</td>
                        <td class="hr-col hr-silver">Silver</td>
                        <td class="points-col">6.20 pts</td>
                        <td class="visits-col">13</td>
                    </tr>
                    <tr>
                        <td class="rank-col">8</td>
                        <td class="member-col">Zoe Robinson</td>
                        <td class="hr-col hr-bronze">Bronze</td>
                        <td class="points-col">1.95 pts</td>
                        <td class="visits-col">5</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- View More button → links to a new page / design -->
        <div class="hall-of-fame__cta">
            <a href="Php/signup.php" class="btn-hall-of-fame">View More</a>
        </div>

    </section>
    
          
    <section class="availability-preview" id="availability">
        <div class="availability__header">
            <h2 class="availability__title">Check Real‑Time Availability</h2>
            <p class="availability__desc">See what dates and times are open for your next appointment.</p>
        </div>
        <div class="availability__grid">
            <!-- Calendar (dynamic) -->
            <div class="calendar-card">
                <div class="calendar-header">
                    <button class="calendar-nav-btn" id="prevMonth">&lt;</button>
                    <span class="calendar-month" id="calendarMonth"></span>
                    <button class="calendar-nav-btn" id="nextMonth">&gt;</button>
                </div>
                <div class="calendar-body">
                    <div class="calendar-weekdays">
                        <span>SUN</span><span>MON</span><span>TUE</span><span>WED</span><span>THU</span><span>FRI</span><span>SAT</span>
                    </div>
                    <div class="calendar-dates" id="calendarDates">
                        <?php
                        $first_day_offset = $first_day->format('w');
                        for ($i = 0; $i < $first_day_offset; $i++):
                        ?>
                            <div class="date-cell empty"></div>
                        <?php endfor; ?>
                        
                        <?php
                        for ($day = 1; $day <= $days_in_month; $day++):
                            $date_str = sprintf('%04d-%02d-%02d', $current_year, $current_month, $day);
                            $status = $daily_availability[$date_str] ?? 'available';
                            $is_past = $date_str < $today;
                            $cell_class = $is_past ? 'past' : $status;
                            $is_selected = $date_str === $selected_date;
                            if ($is_selected) $cell_class .= ' selected';
                            
                            // Check if user has a booking on this date
                            $has_booking = isset($booking_dates[$date_str]);
                            if ($has_booking) $cell_class .= ' has-booking';
                        ?>
                            <div class="date-cell <?php echo $cell_class; ?>" data-date="<?php echo $date_str; ?>" data-status="<?php echo $is_past ? 'past' : $status; ?>" <?php if ($has_booking): ?>data-booking="true"<?php endif; ?>>
                                <?php echo $day; ?>
                                <?php if ($has_booking): ?>
                                    <span class="booking-indicator"></span>
                                <?php endif; ?>
                            </div>
                        <?php endfor; ?>
                    </div>
                    <div class="calendar-legend">
                        <span><i class="dot available"></i> Available</span>
                        <span><i class="dot filling"></i> Filling Up</span>
                        <span><i class="dot booked"></i> Fully booked</span>
                        <span><i class="dot past"></i> Past</span>
                    </div>
                </div>
            </div>
            <!-- Timeslots (dynamic) -->
            <div class="timeslots-card">
                <span class="timeslots-eyebrow">TIME SLOTS</span>
                <span class="timeslots-date" id="timeslotsDate"><?php echo date('F j, Y', strtotime($selected_date)); ?></span>
                <div class="timeslots-grid" id="timeslotsGrid">
                    <?php
                    $available_count = 0;
                    $booked_count = 0;
                    foreach ($time_slots as $slot):
                        $slot_status = 'available';
                        foreach ($selected_date_slots as $date_slot) {
                            if ($date_slot['slot_id'] == $slot['id']) {
                                $slot_status = $date_slot['status'];
                                break;
                            }
                        }
                        if ($slot_status === 'available') $available_count++;
                        if ($slot_status === 'booked') $booked_count++;
                    ?>
                        <div class="slot-btn <?php echo $slot_status; ?>"><?php echo htmlspecialchars($slot['display_time']); ?></div>
                    <?php endforeach; ?>
                </div>
                <div class="timeslots-legend">
                    <span><i class="dot available"></i> <span id="availableCount"><?php echo $available_count; ?></span> available</span>
                    <span><i class="dot booked"></i> <span id="bookedCount"><?php echo $booked_count; ?></span> booked</span>
                </div>
                <p class="timeslots-hint">Visit our booking page to reserve your slot.</p>
            </div>
        
    </section>

    <!-- ========== HOLISTIC BEAUTY & WELLNESS SECTION ========== -->
    <section class="wellness" id="about">
        <!-- ... existing wellness ... -->
    </section>

    <!-- ========== HOLISTIC BEAUTY & WELLNESS SECTION ========== -->
    <section class="wellness" id="about"></section>


  <section class="wellness" id="about">

    <!-- Heading block -->
    <div class="wellness__header">
      <h2 class="wellness__title">Holistic Beauty &amp; Wellness</h2>
      <p class="wellness__subtitle">At Cataleya, we believe true beauty radiates from within. Our philosophy combines ancient wellness wisdom with modern aesthetic science.</p>
    </div>

    <!-- 3 feature pillars -->
    <div class="wellness__pillars">

      <div class="pillar">
        <div class="pillar__icon">
          <svg width="28" height="28" viewBox="0 0 28 28" fill="none">
            <circle cx="14" cy="14" r="14" fill="#E91E8C"/>
            <path d="M14 7 Q18 11 14 15 Q10 11 14 7Z" fill="white" opacity="0.9"/>
            <circle cx="14" cy="18" r="3" fill="white" opacity="0.7"/>
          </svg>
        </div>
        <h3 class="pillar__title">Holistic Wellness</h3>
        <p class="pillar__desc">Comprehensive treatments that nurture your body, mind, and spirit for complete rejuvenation and balance.</p>
      </div>

      <div class="pillar">
        <div class="pillar__icon">
          <svg width="28" height="28" viewBox="0 0 28 28" fill="none">
            <circle cx="14" cy="14" r="14" fill="#E91E8C"/>
            <path d="M10 10 Q14 7 18 10 Q18 16 14 19 Q10 16 10 10Z" fill="white" opacity="0.85"/>
          </svg>
        </div>
        <h3 class="pillar__title">Expert Therapists</h3>
        <p class="pillar__desc">Highly trained professionals with years of experience in advanced beauty and wellness techniques.</p>
      </div>

      <div class="pillar">
        <div class="pillar__icon">
          <svg width="28" height="28" viewBox="0 0 28 28" fill="none">
            <circle cx="14" cy="14" r="14" fill="#E91E8C"/>
            <path d="M9 14 Q14 8 19 14 Q14 20 9 14Z" fill="white" opacity="0.85"/>
          </svg>
        </div>
        <h3 class="pillar__title">Premium Quality</h3>
        <p class="pillar__desc">State-of-the-art equipment and luxury products ensuring safe, effective, and lasting results.</p>
      </div>

    </div>

    <!-- Testimonial / owner card -->
    <div class="wellness__card">
      <div class="wellness__card-avatar">
        <img src="img/46187bdbb3beca85d9a08ee2bf0e08ac.jpg" alt="Shiro Amawasa"
             onerror="this.style.background='linear-gradient(135deg,#f9d4e8,#f0a0c0)';this.removeAttribute('src')" />
      </div>
      <div class="wellness__card-body">
        <p class="wellness__card-quote">At Cataleya, we believe true beauty radiates from within. Our philosophy combines ancient wellness wisdom with modern aesthetic science.</p>
        <p class="wellness__card-name">janella benito Tolentino</p>
        <p class="wellness__card-role">Owner</p>
      </div>
    </div>

  </section>

  <!-- ========== FOOTER (IDENTICAL TO serv.html) ========== -->
  <footer class="footer">
    <div class="footer__main">
      <div class="footer__col footer__col--brand">
        <div class="footer__logo">
          <img src="img/Rectangle 38 (1).png" class="footer__logo-img" alt="Cataleya Essence of Beauty">
          <div class="footer__logo-text">
            <span class="footer__logo-name">Cataleya Essence</span>
            <span class="footer__logo-sub">of Beauty</span>
          </div>
        </div>
        <nav class="footer__nav">
          <a href="index.php">Home</a>
          <a href="Php/signup.php">Services</a>
          <a href="Php/signup.php">Contact</a>
          <a href="Php/signup.php">Terms and Condition</a>
          <a href="Php/signup.php">Privacy Policy</a>
        </nav>
      </div>
      <div class="footer__col footer__col--hours">
        <h4 class="footer__col-title">Opening Hours</h4>
        <ul class="footer__hours">
          <li><span class="day">Monday</span><span class="time">9:00 AM – 7:00 PM</span></li>
          <li><span class="day">Tuesday</span><span class="time">9:00 AM – 7:00 PM</span></li>
          <li><span class="day">Wednesday</span><span class="time">9:00 AM – 7:00 PM</span></li>
          <li><span class="day">Thursday</span><span class="time">9:00 AM – 7:00 PM</span></li>
          <li><span class="day">Friday</span><span class="time">9:00 AM – 7:00 PM</span></li>
          <li><span class="day">Saturday</span><span class="time">9:00 AM – 7:00 PM</span></li>
        </ul>
      </div>
      <div class="footer__col footer__col--contact">
        <h4 class="footer__col-title">Contact</h4>
        <ul class="footer__contact">
          <li>
            <svg class="contact-icon" viewBox="0 0 24 24" fill="currentColor"><path d="M18 2h-3a5 5 0 0 0-5 5v3H7v4h3v8h4v-8h3l1-4h-4V7a1 1 0 0 1 1-1h3z"/></svg>
            <a href="https://www.facebook.com/CatelyaEssence" target="_blank">https://www.facebook.com/CatelyaEssence</a>
          </li>
          <li>
            <svg class="contact-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="2" width="20" height="20" rx="5" ry="5"/><circle cx="12" cy="12" r="4"/><circle cx="17.5" cy="6.5" r="1" fill="currentColor" stroke="none"/></svg>
            <a href="#">@CatelyaEssence</a>
          </li>
          <li>
            <svg class="contact-icon" viewBox="0 0 24 24" fill="currentColor"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347z"/><path d="M12 0C5.373 0 0 5.373 0 12c0 2.123.554 4.116 1.528 5.845L.057 23.497a.5.5 0 0 0 .609.61l5.714-1.497A11.955 11.955 0 0 0 12 24c6.627 0 12-5.373 12-12S18.627 0 12 0zm0 22a9.956 9.956 0 0 1-5.073-1.38l-.361-.214-3.742.981.998-3.648-.235-.374A9.956 9.956 0 0 1 2 12C2 6.477 6.477 2 12 2s10 4.477 10 10-4.477 10-10 10z"/></svg>
            <a href="tel:+639922353293">+63 992 235 3293</a>
          </li>
          <li>
            <svg class="contact-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="10" r="3"/><path d="M12 2a8 8 0 0 0-8 8c0 5.25 8 13 8 13s8-7.75 8-13a8 8 0 0 0-8-8z"/></svg>
            <span>Building J Malagpo St. San Vicente, Gapan City, Philippines, 3105</span>
          </li>
        </ul>
      </div>
    </div>
    <div class="footer__bottom">
      <p>&copy; 2026 Cataleya Essence of Beauty. All rights reserved.</p>
    </div>
  </footer>

  <script src="Js/script.js"></script>
  <script>
    document.addEventListener('DOMContentLoaded', function() {
        const calendarMonth = document.getElementById('calendarMonth');
        const calendarDates = document.getElementById('calendarDates');
        const prevMonthBtn = document.getElementById('prevMonth');
        const nextMonthBtn = document.getElementById('nextMonth');
        const timeslotsDate = document.getElementById('timeslotsDate');
        const timeslotsGrid = document.getElementById('timeslotsGrid');
        const availableCount = document.getElementById('availableCount');
        const bookedCount = document.getElementById('bookedCount');

        let currentYear = new Date().getFullYear();
        let currentMonth = new Date().getMonth() + 1;
        let selectedDate = new Date().toISOString().split('T')[0];

        // Initial load
        loadCalendar(currentYear, currentMonth);

        // Month navigation
        prevMonthBtn.addEventListener('click', function() {
            if (currentMonth === 1) {
                currentMonth = 12;
                currentYear--;
            } else {
                currentMonth--;
            }
            loadCalendar(currentYear, currentMonth);
        });

        nextMonthBtn.addEventListener('click', function() {
            if (currentMonth === 12) {
                currentMonth = 1;
                currentYear++;
            } else {
                currentMonth++;
            }
            loadCalendar(currentYear, currentMonth);
        });

        function loadCalendar(year, month) {
            const monthNames = ['January', 'February', 'March', 'April', 'May', 'June',
                               'July', 'August', 'September', 'October', 'November', 'December'];
            calendarMonth.textContent = monthNames[month - 1].toUpperCase() + ' ' + year;

            // Fetch monthly availability and user bookings
            Promise.all([
                fetch(`api/get_monthly_availability.php?year=${year}&month=${month}`).then(r => r.json()),
                fetch(`api/get_user_bookings.php?year=${year}&month=${month}`).then(r => r.json())
            ])
            .then(([availabilityData, bookingsData]) => {
                const availability = availabilityData.success ? availabilityData.availability : {};
                const userBookings = bookingsData.success ? bookingsData.bookings : [];
                renderCalendar(year, month, availability, userBookings);
            })
            .catch(error => {
                console.error('Error fetching calendar data:', error);
                renderCalendar(year, month, {}, []);
            });
        }

        function renderCalendar(year, month, availability, userBookings) {
            const firstDay = new Date(year, month - 1, 1);
            const lastDay = new Date(year, month, 0);
            const daysInMonth = lastDay.getDate();
            const firstDayOffset = firstDay.getDay(); // 0 = Sunday
            const today = new Date().toISOString().split('T')[0];

            // Create a map of booking dates for quick lookup
            const bookingDates = {};
            userBookings.forEach(booking => {
                bookingDates[booking.booking_date] = true;
            });

            calendarDates.innerHTML = '';

            // Empty cells for days before first of month
            for (let i = 0; i < firstDayOffset; i++) {
                const emptyCell = document.createElement('div');
                emptyCell.className = 'date-cell empty';
                calendarDates.appendChild(emptyCell);
            }

            // Days of the month
            for (let day = 1; day <= daysInMonth; day++) {
                const dateStr = `${year}-${String(month).padStart(2, '0')}-${String(day).padStart(2, '0')}`;
                const status = availability[dateStr] || 'available';
                const isPast = dateStr < today;
                const hasBooking = bookingDates[dateStr];
                const isFullyBooked = status === 'booked';
                let cellClass = isPast ? 'past' : status;
                if (hasBooking) cellClass += ' has-booking';
                if (isFullyBooked) cellClass += ' fully-booked';
                const isSelected = dateStr === selectedDate;
                if (isSelected) cellClass += ' selected';

                const dateCell = document.createElement('div');
                dateCell.className = `date-cell ${cellClass}`;
                dateCell.textContent = day;
                dateCell.dataset.date = dateStr;
                dateCell.dataset.status = isPast ? 'past' : status;
                
                // Add booking indicator for user's bookings
                if (hasBooking) {
                    dateCell.dataset.booking = 'true';
                    const indicator = document.createElement('span');
                    indicator.className = 'booking-indicator';
                    dateCell.appendChild(indicator);
                }
                
                // Add red mark for fully booked dates
                if (isFullyBooked && !isPast) {
                    const redMark = document.createElement('span');
                    redMark.className = 'fully-booked-mark';
                    dateCell.appendChild(redMark);
                }

                calendarDates.appendChild(dateCell);
            }
        }

        if (calendarDates) {
            calendarDates.addEventListener('click', function(e) {
                const dateCell = e.target.closest('.date-cell');
                if (!dateCell || dateCell.classList.contains('empty') || dateCell.classList.contains('past')) {
                    return;
                }

                selectedDate = dateCell.dataset.date;
                if (!selectedDate) return;

                // Update selected state
                document.querySelectorAll('.date-cell').forEach(cell => {
                    cell.classList.remove('selected');
                });
                dateCell.classList.add('selected');

                // Update date display
                const dateObj = new Date(selectedDate);
                timeslotsDate.textContent = dateObj.toLocaleDateString('en-US', { month: 'long', day: 'numeric', year: 'numeric' });

                // Fetch availability from API
                fetch(`api/get_availability.php?date=${selectedDate}`)
                    .then(response => response.json())
                    .then(data => {
                        if (data.success && data.slots) {
                            updateTimeslots(data.slots);
                        }
                    })
                    .catch(error => {
                        console.error('Error fetching availability:', error);
                    });
            });
        }

        function updateTimeslots(slots) {
            timeslotsGrid.innerHTML = '';
            let available = 0;
            let booked = 0;
            let userBooked = 0;

            slots.forEach(slot => {
                const slotBtn = document.createElement('div');
                let slotClass = `slot-btn ${slot.status}`;
                if (slot.user_booked) {
                    slotClass += ' user-booked';
                    userBooked++;
                }
                slotBtn.className = slotClass;
                slotBtn.textContent = slot.display_time;
                
                // Add indicator for user's booked slots
                if (slot.user_booked) {
                    const indicator = document.createElement('span');
                    indicator.className = 'user-booking-indicator';
                    slotBtn.appendChild(indicator);
                }
                
                timeslotsGrid.appendChild(slotBtn);

                if (slot.status === 'available') available++;
                if (slot.status === 'booked') booked++;
            });

            availableCount.textContent = available;
            bookedCount.textContent = booked;
        }

        // Auto-refresh calendar every 30 seconds for real-time updates
        setInterval(() => {
            loadCalendar(currentYear, currentMonth);
            if (selectedDate) {
                fetch(`api/get_availability.php?date=${selectedDate}`)
                    .then(response => response.json())
                    .then(data => {
                        if (data.success && data.slots) {
                            updateTimeslots(data.slots);
                        }
                    })
                    .catch(error => {
                        console.error('Error refreshing availability:', error);
                    });
            }
        }, 30000); // Refresh every 30 seconds
    });
  </script>
</body>
</html>