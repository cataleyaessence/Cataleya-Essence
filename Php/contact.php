<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Get In Touch · Cataleya Essence</title>
    <link href="https://fonts.googleapis.com/css2?family=DM+Serif+Display:ital@0;1&family=DM+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" />
    <link rel="stylesheet" href="../css/contact.css" />
    <link rel="icon" href="../img/Rectangle 38 (1).png" />
</head>
<body>

    <!-- ========== NAVBAR ========== -->
    <header class="navbar">
        <div class="navbar__logo">
            <img src="../img/Rectangle 38 (1).png" class="logo-img" alt="Cataleya Essence of Beauty" />
            <div class="logo-text">
                <span class="logo-name">Cataleya Essence</span>
                <span class="logo-sub">of Beauty</span>
            </div>
        </div>
        <nav class="navbar__links" id="nav-links">
            <a href="home.php" class="nav-link">Home</a>
            <a href="about.php" class="nav-link">About Us</a>
            <a href="serv.php" class="nav-link">Services</a>
        </nav>
    </header>

    <!-- ========== MAIN CONTENT ========== -->
    <main class="contact-page">
        <div class="contact-card">

            <!-- Hero Header -->
            <div class="contact-header">
                <h1>Get In Touch</h1>
                <p>Have questions or ready to book your appointment? We're here to help you begin your wellness journey.</p>
            </div>

            <!-- Grid Layout -->
            <div class="contact-grid">

                <!-- LEFT COLUMN: Contact Details + Map -->
                <div class="contact-details">

                    <!-- Visit Us -->
                    <div class="detail-item">
                        <div class="icon-circle">
                            <i class="fas fa-location-dot"></i>
                        </div>
                        <div class="detail-content">
                            <h3>Visit Us</h3>
                            <p>Building J Maigapo St. San Vicente,<br />Gapan City, Gapan, Philippines, 3105</p>
                        </div>
                    </div>

                    <!-- Call Us -->
                    <div class="detail-item">
                        <div class="icon-circle">
                            <i class="fas fa-phone"></i>
                        </div>
                        <div class="detail-content">
                            <h3>Call Us</h3>
                            <p><a href="tel:+639922353293">+63 992 235 3293</a></p>
                            <p class="meta">Mon-Sat: 9AM – 8PM</p>
                        </div>
                    </div>

                    <!-- Email Us -->
                    <div class="detail-item">
                        <div class="icon-circle">
                            <i class="fas fa-envelope"></i>
                        </div>
                        <div class="detail-content">
                            <h3>Email Us</h3>
                            <p><a href="mailto:info@CatelyaEssence.com">info@CatelyaEssence.com</a></p>
                            <p class="meta">
                                <a href="https://www.facebook.com/CatelyaEssence" target="_blank">
                                    <i class="fab fa-facebook" style="margin-right:6px;"></i> /CatelyaEssence
                                </a>
                            </p>
                        </div>
                    </div>

                    <!-- Our Location / Map -->
                    <div class="detail-item map-box">
                        <div class="icon-circle">
                            <i class="fas fa-map-pin"></i>
                        </div>
                        <div class="detail-content map-content">
                            <h3>Our Location</h3>
                            <div class="map-container" id="mapContainer">
                                <div class="map-fallback" id="mapFallback">
                                    <i class="fas fa-map"></i>
                                    <p>Loading map…</p>
                                </div>
                            </div>
                        </div>
                    </div>

                </div>

                <!-- RIGHT COLUMN: Contact Form -->
                <div class="contact-form">
                    <h2>Send Us a Message</h2>
                    <form id="contactForm" novalidate>
                        <div class="form-group">
                            <label for="fullName">Full Name</label>
                            <input type="text" id="fullName" placeholder="Jose David" required />
                        </div>

                        <div class="form-group">
                            <label for="email">Email Address</label>
                            <input type="email" id="email" placeholder="jose@example.com" required />
                        </div>

                        <div class="form-group">
                            <label for="phone">Phone Number</label>
                            <input type="tel" id="phone" placeholder="+63 9123 456 8910" required />
                        </div>

                        <div class="form-group">
                            <label for="service">Service Interest</label>
                            <select id="service">
                                <option value="">Select a Service</option>
                                <option value="Facial Services">Facial Services</option>
                                <option value="RF + Lipo Cav">RF + Lipo Cav</option>
                                <option value="EyeLash Enhancement">EyeLash Enhancement</option>
                                <option value="Hair Laser Removal">Hair Laser Removal</option>
                                <option value="Laser Whitening">Laser Whitening</option>
                                <option value="Mesolipo">Mesolipo</option>
                                <option value="Gluta Whitening">Gluta Whitening</option>
                                <option value="Semi-Permanent Make Up">Semi-Permanent Make Up</option>
                                <option value="Waxing">Waxing</option>
                                <option value="HIFU Ultheraphy">HIFU Ultheraphy</option>
                                <option value="Nail Services">Nail Services</option>
                                <option value="Body Care">Body Care</option>
                                <option value="Traditional Body Care">Traditional Body Care</option>
                                <option value="Body Skin Treatment">Body Skin Treatment</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="message">Message</label>
                            <textarea id="message" placeholder="Tell us about your needs..." maxlength="500"></textarea>
                            <div class="char-counter">
                                <span id="charCount">0</span>/500 characters
                            </div>
                        </div>

                        <button type="submit" class="btn-primary">
                            <i class="fas fa-paper-plane" style="margin-right:8px;"></i> Send Message
                        </button>
                    </form>
                </div>

            </div>

        </div>
    </main>

    <!-- ========== FOOTER ========== -->
    <footer class="footer">
        <div class="footer__main">
            <div class="footer__col footer__col--brand">
                <div class="footer__logo">
                    <img src="../img/Rectangle 38 (1).png" class="footer__logo-img" alt="Cataleya Essence of Beauty" />
                    <div class="footer__logo-text">
                        <span class="footer__logo-name">Cataleya Essence</span>
                        <span class="footer__logo-sub">of Beauty</span>
                    </div>
                </div>
                <nav class="footer__nav">
                    <a href="contact.php">Contact</a>
                    <a href="terms-conditions.php">Terms and Condition</a>
                    <a href="PrivacyPolicy.php">Privacy Policy</a>
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
                        <svg class="contact-icon" viewBox="0 0 24 24" fill="currentColor"><path d="M18 2h-3a5 5 0 0 0-5 5v3H7v4h3v8h4v-8h3l1-4h-4V7a1 1 0 0 1 1-1h3z" /></svg>
                        <a href="https://www.facebook.com/CatelyaEssence" target="_blank">https://www.facebook.com/CatelyaEssence</a>
                    </li>
                    <li>
                        <svg class="contact-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="2" width="20" height="20" rx="5" ry="5" /><circle cx="12" cy="12" r="4" /><circle cx="17.5" cy="6.5" r="1" fill="currentColor" stroke="none" /></svg>
                        <a href="#">@CatelyaEssence</a>
                    </li>
                    <li>
                        <svg class="contact-icon" viewBox="0 0 24 24" fill="currentColor"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347z" /><path d="M12 0C5.373 0 0 5.373 0 12c0 2.123.554 4.116 1.528 5.845L.057 23.497a.5.5 0 0 0 .609.61l5.714-1.497A11.955 11.955 0 0 0 12 24c6.627 0 12-5.373 12-12S18.627 0 12 0zm0 22a9.956 9.956 0 0 1-5.073-1.38l-.361-.214-3.742.981.998-3.648-.235-.374A9.956 9.956 0 0 1 2 12C2 6.477 6.477 2 12 2s10 4.477 10 10-4.477 10-10 10z" /></svg>
                        <a href="tel:+639922353293">+63 992 235 3293</a>
                    </li>
                    <li>
                        <svg class="contact-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="10" r="3" /><path d="M12 2a8 8 0 0 0-8 8c0 5.25 8 13 8 13s8-7.75 8-13a8 8 0 0 0-8-8z" /></svg>
                        <span>Building J Maigapo St. San Vicente, Gapan City, Gapan, Philippines, 3105</span>
                    </li>
                </ul>
            </div>
        </div>
        <div class="footer__bottom">
            <p>&copy; 2026 Cataleya Essence of Beauty. All rights reserved.</p>
        </div>
    </footer>

    <script src="../JS/contact.js"></script>
</body>
</html>
