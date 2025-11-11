    <!-- Added closing main tag here to properly structure the page -->
    </main>
    
    <footer class="footer">
        <div class="container">
            <!-- Location Map -->
            <div class="map-responsive mb-3">
                <iframe
                    src="https://www.google.com/maps?q=9.9716198,77.1808363&z=17&output=embed"
                    width="100%"
                    height="300"
                    style="border:0; border-radius:12px;"
                    allowfullscreen=""
                    loading="lazy"
                    referrerpolicy="no-referrer-when-downgrade"
                    title="Shop Location Map"
                ></iframe>
                <!--<p class="text-center mt-2"><i class="fas fa-map-marker-alt"></i> Place Rajakumari</p>--->
            </div>
            
            <p>&copy; <?php echo date('Y'); ?> Tool-Kart. All rights reserved.</p>
            <p>Professional Tool Rental Service | Quality Tools for Every Work</p>
            <p>
                <a href="index.php#about-section">About Us</a> |
                <a href="mailto:info@toolkart.com"><i class="fas fa-envelope"></i> info@toolkart.com</a> |
                <a href="tel:+919876543210"><i class="fas fa-phone"></i> +91 97782 38064</a>
            </p>
        </div>
    </footer>

    <!-- JavaScript for interactive features -->
    <?php
    $base_path = (strpos($_SERVER['REQUEST_URI'], '/admin/') !== false) ? '../' : '';
    ?>
    <script src="<?php echo $base_path; ?>assets/js/main.js"></script>
    
    <!-- Carousel JavaScript for landing page -->
    <?php if ($current_page == 'index.php'): ?>
    <script>
        // Carousel functionality
        let currentSlide = 0;
        const slides = document.querySelectorAll('.carousel-slide');
        const totalSlides = slides.length;

        function showSlide(index) {
            slides.forEach(slide => slide.classList.remove('active'));
            if (slides[index]) {
                slides[index].classList.add('active');
            }
        }

        function nextSlide() {
            currentSlide = (currentSlide + 1) % totalSlides;
            showSlide(currentSlide);
        }

        // Auto-advance carousel every 5 seconds
        if (totalSlides > 0) {
            setInterval(nextSlide, 5000);
            showSlide(0); // Show first slide initially
        }
    </script>
    <?php endif; ?>
    
    <!-- FAQ accordion functionality -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Use event delegation to handle FAQ clicks
            document.addEventListener('click', function(e) {
                // Check if clicked element is a FAQ question
                if (e.target.closest('.faq-question-modern')) {
                    const question = e.target.closest('.faq-question-modern');
                    question.classList.toggle('active');
                    const answer = question.nextElementSibling;
                    answer.classList.toggle('show');
                }
            });
        });
    </script>
    
</body>
</html>