<?php
/** Shared customer footer. Its markup intentionally mirrors Php/home.php. */
?>
<footer class="footer" role="contentinfo">
    <div class="footer__main">
        <section class="footer__col footer__col--brand" aria-label="Cataleya Essence">
            <div class="footer__logo">
                <img src="../img/Rectangle 38 (1).png" class="footer__logo-img" alt="Cataleya Essence of Beauty" />
                <div class="footer__logo-text">
                    <span class="footer__logo-name">Cataleya Essence</span>
                    <span class="footer__logo-sub">of Beauty</span>
                </div>
            </div>
            <nav class="footer__nav" aria-label="Support links">
                <a href="contact.php">Contact</a>
                <a href="faq.php">FAQs</a>
                <a href="terms.php">Terms &amp; Conditions</a>
                <a href="PrivacyPolicy.php">Privacy Policy</a>
            </nav>
        </section>

        <section class="footer__col footer__col--hours" aria-labelledby="footerHoursTitle">
            <h2 class="footer__col-title" id="footerHoursTitle">Opening Hours</h2>
            <ul class="footer__hours">
                <li><span class="day">Monday</span><span class="time">9:00 AM - 7:00 PM</span></li>
                <li><span class="day">Tuesday</span><span class="time">9:00 AM - 7:00 PM</span></li>
                <li><span class="day">Wednesday</span><span class="time">9:00 AM - 7:00 PM</span></li>
                <li><span class="day">Thursday</span><span class="time">9:00 AM - 7:00 PM</span></li>
                <li><span class="day">Friday</span><span class="time">9:00 AM - 7:00 PM</span></li>
                <li><span class="day">Saturday</span><span class="time">9:00 AM - 7:00 PM</span></li>
            </ul>
        </section>

        <section class="footer__col footer__col--contact" aria-labelledby="footerContactTitle">
            <h2 class="footer__col-title" id="footerContactTitle">Contact</h2>
            <ul class="footer__contact">
                <li><i class="fab fa-facebook-f contact-icon" aria-hidden="true"></i><a href="https://www.facebook.com/CatelyaEssence" target="_blank" rel="noopener">https://www.facebook.com/CatelyaEssence</a></li>
                <li><i class="fab fa-instagram contact-icon" aria-hidden="true"></i><a href="https://www.instagram.com/CatelyaEssence" target="_blank" rel="noopener">@CatelyaEssence</a></li>
                <li><i class="fas fa-phone contact-icon" aria-hidden="true"></i><a href="tel:+639922353293">+63 992 235 3293</a></li>
                <li><i class="fas fa-location-dot contact-icon" aria-hidden="true"></i><span>Building J Maigapo St. San Vicente, Gapan City, Philippines, 3105</span></li>
            </ul>
        </section>
    </div>
    <div class="footer__bottom">
        <p>&copy; <?php echo date('Y'); ?> Cataleya Essence of Beauty. All rights reserved.</p>
    </div>
</footer>
