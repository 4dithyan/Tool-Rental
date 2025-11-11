<?php
require_once 'includes/db_connect.php';
require_once 'includes/settings_helper.php';
include 'includes/header.php';

// Initialize variables with safe defaults
$featured_result = new stdClass();
$featured_result->num_rows = 0;
$common_tools_result = new stdClass();
$common_tools_result->num_rows = 0;
$faq_result = new stdClass();
$faq_result->num_rows = 0;

$tools_count = 0;
$users_count = 0;
$rentals_count = 0;

// Try to get data from database, but don't let errors break the page
try {
    // Get featured tools (using the is_featured flag)
    $featured_query = "SELECT t.*, c.category_name FROM tools t 
                       LEFT JOIN categories c ON t.category_id = c.category_id 
                       WHERE t.status = 'active' AND t.is_featured = 1
                       ORDER BY t.created_at DESC 
                       LIMIT 6";
    $featured_result = $conn->query($featured_query);

    // If query failed or no featured tools are marked, fall back to newest tools
    if (!$featured_result || $featured_result->num_rows == 0) {
        $featured_query = "SELECT t.*, c.category_name FROM tools t 
                           LEFT JOIN categories c ON t.category_id = c.category_id 
                           WHERE t.status = 'active' 
                           ORDER BY t.created_at DESC 
                           LIMIT 6";
        $featured_result = $conn->query($featured_query);
    }

    // Get common tools (using the is_common flag)
    $common_tools_query = "SELECT t.*, c.category_name FROM tools t 
                           LEFT JOIN categories c ON t.category_id = c.category_id 
                           WHERE t.status = 'active' AND t.is_common = 1
                           ORDER BY t.created_at DESC 
                           LIMIT 6";
    $common_tools_result = $conn->query($common_tools_query);

    // If query failed or no common tools are marked, fall back to most rented tools
    if (!$common_tools_result || $common_tools_result->num_rows == 0) {
        $common_tools_query = "SELECT t.*, c.category_name, COUNT(r.tool_id) as rental_count 
                               FROM tools t 
                               LEFT JOIN categories c ON t.category_id = c.category_id 
                               LEFT JOIN rentals r ON t.tool_id = r.tool_id 
                               WHERE t.status = 'active' 
                               GROUP BY t.tool_id 
                               ORDER BY rental_count DESC, t.created_at DESC 
                               LIMIT 6";
        $common_tools_result = $conn->query($common_tools_query);
    }

    // Get FAQ items
    $faq_query = "SELECT * FROM faq WHERE is_active = 1 ORDER BY sort_order ASC, created_at DESC";
    $faq_result = $conn->query($faq_query);

    // Get total counts for statistics
    $tools_count_query = "SELECT COUNT(*) as count FROM tools WHERE status = 'active'";
    $tools_count_result = $conn->query($tools_count_query);
    $tools_count = $tools_count_result ? $tools_count_result->fetch_assoc()['count'] : 0;

    $users_count_query = "SELECT COUNT(*) as count FROM users WHERE role = 'customer'";
    $users_count_result = $conn->query($users_count_query);
    $users_count = $users_count_result ? $users_count_result->fetch_assoc()['count'] : 0;

    $rentals_count_query = "SELECT COUNT(*) as count FROM rentals";
    $rentals_count_result = $conn->query($rentals_count_query);
    $rentals_count = $rentals_count_result ? $rentals_count_result->fetch_assoc()['count'] : 0;
} catch (Exception $e) {
    // If there are any database errors, continue with empty results
    error_log("Database error in index.php: " . $e->getMessage());
}
?>

<!-- Hero Section with Carousel -->
<section class="hero">
    <div class="carousel-container">
        <!-- Carousel Slide 1: Workshop -->
        <div class="carousel-slide active">
            <img src="https://images.unsplash.com/photo-1508873535684-277a3cbcc4e8?q=80&w=1170&auto=format&fit=crop&ixlib=rb-4.1.0&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D" alt="Professional Workshop">
            <div class="carousel-overlay">
                <div class="hero-content">
                    <h1 style="color:rgb(255, 255, 255); text-shadow: none;">Professional Tool Rental</h1>
                    <p style="color:rgb(255, 255, 255);">Access high-quality tools for every project without the hefty investment</p>
                    <a href="browse_tools.php" class="btn btn-primary">Browse Our Tools</a>
                </div>
            </div>
        </div>
        
        <!-- Carousel Slide 2: Tool Collection -->
        <div class="carousel-slide">
            <img src="https://images.unsplash.com/photo-1645651964715-d200ce0939cc?q=80&w=1170&auto=format&fit=crop&ixlib=rb-4.1.0&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D" alt="Tool Collection">
            <div class="carousel-overlay">
                <div class="hero-content">
                    <h1 style="color:rgb(255, 255, 255); text-shadow: none;">Quality Tools, Affordable Rates</h1>
                    <p style="color:rgb(255, 255, 255);">From carpentry to gardening - we have the perfect tool for your needs</p>
                    <a href="register.php" class="btn btn-primary">Get Started Today</a>
                </div>
            </div>
        </div>
        
        <!-- Carousel Slide 3: Storefront -->
        <div class="carousel-slide">
            <img src="https://images.unsplash.com/photo-1688516940599-674116306c5c?q=80&w=1170&auto=format&fit=crop&ixlib=rb-4.1.0&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D" alt="Tool Store">
            <div class="carousel-overlay">
                <div class="hero-content">
                    <h1 style="color:var(--accent-yellow); text-shadow: none;">Trusted by Professionals</h1>
                    <p style="color:white;">Join thousands of satisfied customers who choose Tool-Kart for their projects</p>
                    <a href="browse_tools.php" class="btn btn-primary">Explore Categories</a>
                </div>
            </div>
        </div>
    </div>
</section>

<div class="container">
    <!-- Common/Most Rented Tools Section -->
    <section class="mt-3 animate-on-scroll common-tools-section">
        <div class="section-card">
            <div class="section-header">
                <h2>Common Tools</h2>
                <p>Tools that our customers rent frequently</p>
            </div>
            
            <!-- Horizontal Scroll Navigation -->
            <div class="horizontal-scroll-nav">
                <button class="scroll-btn scroll-btn-left" onclick="scrollHorizontal('left')">
                    <i class="fas fa-chevron-left"></i>
                </button>
                <button class="scroll-btn scroll-btn-right" onclick="scrollHorizontal('right')">
                    <i class="fas fa-chevron-right"></i>
                </button>
            </div>
            
            <!-- Horizontal Scroll Container -->
            <div class="horizontal-scroll-container" id="commonToolsScroll">
                <div class="horizontal-scroll-wrapper">
                    <?php 
                    // Check if we have results before trying to loop
                    if (isset($common_tools_result) && $common_tools_result instanceof mysqli_result && $common_tools_result->num_rows > 0):
                        // Reset the result pointer to the beginning
                        $common_tools_result->data_seek(0);
                        while ($tool = $common_tools_result->fetch_assoc()): ?>
                            <div class="tool-card-horizontal animate-on-scroll" data-category="<?php echo $tool['category_id']; ?>">
                                <!-- FIXED: Use actual tool image or fallback to placeholder -->
                                <img src="<?php echo !empty($tool['image_url']) ? (filter_var($tool['image_url'], FILTER_VALIDATE_URL) ? $tool['image_url'] : htmlspecialchars($tool['image_url'])) : 'https://images.unsplash.com/photo-1572981779307-38b8cabb2407?ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D&auto=format&fit=crop&w=400&q=80'; ?>" 
                                     alt="<?php echo htmlspecialchars($tool['name']); ?>" class="tool-image"
                                     onerror="this.src='https://images.unsplash.com/photo-1572981779307-38b8cabb2407?ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D&auto=format&fit=crop&w=400&q=80'">
                                <div class="tool-info">
                                    <div class="tool-name"><?php echo htmlspecialchars($tool['name']); ?></div>
                                    <div class="tool-description"><?php echo htmlspecialchars(substr($tool['description'], 0, 80)); ?>...</div>
                                    <div class="tool-price">₹<?php echo number_format($tool['daily_rate'], 2); ?>/day</div>
                                    <div class="d-flex justify-center">
                                        <a href="tool_details.php?id=<?php echo $tool['tool_id']; ?>" class="btn btn-secondary">View Details</a>
                                    </div>
                                </div>
                            </div>
                        <?php endwhile; ?>
                        
                        <!-- View More Card -->
                        <div class="tool-card-horizontal view-more-card">
                            <div class="view-more-content">
                                <i class="fas fa-plus-circle" style="font-size: 3rem; color: var(--accent-yellow); margin-bottom: 15px;"></i>
                                <h3>View All Tools</h3>
                                <p>Explore our complete collection</p>
                                <a href="browse_tools.php" class="btn btn-primary">View More</a>
                            </div>
                        </div>
                    <?php else: ?>
                        <!-- Fallback content if no tools are available -->
                        <div class="tool-card-horizontal">
                            <div class="tool-info text-center">
                                <p>No common tools available at the moment.</p>
                                <a href="browse_tools.php" class="btn btn-primary">Browse All Tools</a>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </section>
</div>

<!-- Parallax Section -->
<section class="parallax-section">
    <div class="parallax-content">
        <h2>Premium Tool Rental Experience</h2>
        <p>Experience the difference with Tool-Kart's professional service</p>
    </div>
</section>

<div class="container">
    <!-- Featured Tools Section -->
    <section class="mt-3 animate-on-scroll">
        <div class="section-card">
            <div class="section-header">
                <h2>Featured Tools</h2>
                <p>Popular tools that our customers love</p>
            </div>
            
            <div class="grid grid-3">
                <?php 
                // Check if we have results before trying to loop
                if (isset($featured_result) && $featured_result instanceof mysqli_result && $featured_result->num_rows > 0):
                    // Reset the result pointer to the beginning
                    $featured_result->data_seek(0);
                    while ($tool = $featured_result->fetch_assoc()): ?>
                        <div class="tool-card animate-on-scroll" data-category="<?php echo $tool['category_id']; ?>">
                            <!-- FIXED: Use actual tool image or fallback to placeholder -->
                            <img src="<?php echo !empty($tool['image_url']) ? (filter_var($tool['image_url'], FILTER_VALIDATE_URL) ? $tool['image_url'] : htmlspecialchars($tool['image_url'])) : 'https://images.unsplash.com/photo-1572981779307-38b8cabb2407?ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D&auto=format&fit=crop&w=400&q=80'; ?>" 
                                 alt="<?php echo htmlspecialchars($tool['name']); ?>" class="tool-image"
                                 onerror="this.src='https://images.unsplash.com/photo-1572981779307-38b8cabb2407?ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D&auto=format&fit=crop&w=400&q=80'">
                            <div class="tool-info">
                                <div class="tool-name"><?php echo htmlspecialchars($tool['name']); ?></div>
                                <div class="tool-description"><?php echo htmlspecialchars(substr($tool['description'], 0, 80)); ?>...</div>
                                <div class="tool-price">₹<?php echo number_format($tool['daily_rate'], 2); ?>/day</div>
                                <div class="d-flex justify-center">
                                    <a href="tool_details.php?id=<?php echo $tool['tool_id']; ?>" class="btn btn-secondary">View Details</a>
                                </div>
                            </div>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <!-- Fallback content if no tools are available -->
                    <div class="col-12 text-center">
                        <p>No featured tools available at the moment.</p>
                        <a href="browse_tools.php" class="btn btn-primary">Browse All Tools</a>
                    </div>
                <?php endif; ?>
            </div>
            
            <?php if (isset($featured_result) && $featured_result instanceof mysqli_result && $featured_result->num_rows > 0): ?>
                <div class="view-all-tools">
                    <a href="browse_tools.php" class="btn btn-primary">View All Tools</a>
                </div>
            <?php endif; ?>
        </div>
    </section>
    
    <!-- Features Section -->
    <section class="features-section mt-3 animate-on-scroll">
        <div class="container">
            <div class="features-card">
                <div class="features-header">
                    <h2>Why Choose Tool-Kart?</h2>
                    <p>We make tool rental simple, affordable, and reliable</p>
                </div>
                
                <div class="features-grid">
                    <div class="feature-item animate-on-scroll">
                        <div class="card-body text-center">
                            <i class="fas fa-shield-alt"></i>
                            <h4>Quality Assured</h4>
                            <p>All our tools are professionally maintained and regularly inspected for optimal performance.</p>
                        </div>
                    </div>
                    <div class="feature-item animate-on-scroll">
                        <div class="card-body text-center">
                            <i class="fas fa-clock"></i>
                            <h4>Flexible Rental</h4>
                            <p>Rent tools for a day, week, or longer. Our flexible terms adapt to your project needs.</p>
                        </div>
                    </div>
                    <div class="feature-item animate-on-scroll">
                        <div class="card-body text-center">
                            <i class="fas fa-rupee-sign"></i>
                            <h4>Affordable Rates</h4>
                            <p>Competitive daily rates that save you money compared to purchasing expensive tools.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
</div>

<!-- About Us Section -->
<section id="about-section" class="about-section">
    <div class="container">
        <div class="section-card">
            <div class="section-header">
                <h2>About Tool-Kart</h2>
                <p>Your Trusted Tool Rental Partner in Kerala</p>
            </div>
            
            <div>
                <div>
                    <p><strong>Tool-Kart is Rajakumari's premier tool rental service</strong>, dedicated to providing professionals and DIY enthusiasts with high-quality tools at affordable rates. Our mission is to make professional-grade equipment accessible to everyone, from construction workers to weekend warriors.</p><br>
                    
                    <p>Founded in 2020, we've grown to become the most trusted tool rental service across Kerala, with thousands of satisfied customers who rely on us for their project needs. Our extensive inventory includes everything from basic hand tools to specialized power equipment.</p><br>
                    <p>What sets us apart is our commitment to quality and customer service. Every tool in our inventory is regularly maintained and inspected to ensure optimal performance. Our team of experts is always ready to help you choose the right tool for your project and provide guidance on usage.</p><br>
                    
                    <div class="about-stats">
                        <div class="stat-item">
                            <i class="fas fa-tools"></i>
                            <h3><?php echo $tools_count; ?>+</h3>
                            <p>Quality Tools</p>
                        </div>
                        <div class="stat-item">
                            <i class="fas fa-users"></i>
                            <h3><?php echo $users_count; ?>+</h3>
                            <p>Happy Customers</p>
                        </div>
                        <div class="stat-item">
                            <i class="fas fa-handshake"></i>
                            <h3><?php echo $rentals_count; ?>+</h3>
                            <p>Successful Rentals</p>
                        </div>
                    </div>
                    
                    <div class="about-cta">
                        <a href="browse_tools.php" class="btn btn-primary">Explore Our Tools</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Parallax CTA Section -->
<section class="parallax-cta-section">
    <div class="parallax-cta-overlay">
        <div class="container">
            <div class="cta-content">
                <h2>Ready to Start Your Project?</h2>
                <p>Join Tool-Kart today and get access to professional-grade tools at unbeatable prices</p>
                <div class="cta-buttons">
                    <?php if (isset($_SESSION['user_id'])): ?>
                        <a href="browse_tools.php" class="btn btn-primary">Browse Tools Now</a>
                    <?php else: ?>
                        <a href="register.php" class="btn btn-primary">Register Free</a>
                        <a href="login.php" class="btn btn-primary cta-login-btn">Login</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- FAQ Section -->
<section class="faq-section" id="faq-section">
    <div class="container">
        <div class="section-card">
            <div class="section-header">
                <h2>Frequently Asked Questions</h2>
                <p>Find answers to common questions about our service</p>
            </div>
            
            <div class="faq-container">
                <?php 
                // Check if we have results before trying to loop
                if (isset($faq_result) && $faq_result instanceof mysqli_result && $faq_result->num_rows > 0):
                    // Reset the result pointer to the beginning
                    $faq_result->data_seek(0);
                    while ($faq = $faq_result->fetch_assoc()): ?>
                        <div class="faq-item">
                            <div class="faq-question-modern">
                                <h3><?php echo htmlspecialchars($faq['question']); ?></h3>
                                <i class="fas fa-chevron-down"></i>
                            </div>
                            <div class="faq-answer-modern">
                                <p><?php echo htmlspecialchars($faq['answer']); ?></p>
                            </div>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <!-- Fallback content if no FAQ items are available -->
                    <div class="text-center">
                        <p>No FAQ items available at the moment.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</section>

<script>
// Carousel functionality with enhanced smoothness
let currentSlide = 0;
const slides = document.querySelectorAll('.carousel-slide');
const totalSlides = slides.length;

function showSlide(index) {
    // Remove active class from all slides
    slides.forEach(slide => {
        slide.classList.remove('active');
    });
    
    // Add active class to current slide
    if (slides[index]) {
        slides[index].classList.add('active');
    }
}

function nextSlide() {
    currentSlide = (currentSlide + 1) % totalSlides;
    showSlide(currentSlide);
}

// Auto-advance carousel every 5 seconds with smoother transition
if (totalSlides > 0) {
    setInterval(nextSlide, 5000);
    showSlide(0); // Show first slide initially
}

// Horizontal scroll enhancement for common tools
document.addEventListener('DOMContentLoaded', function() {
    const scrollContainer = document.getElementById('commonToolsScroll');
    const scrollLeftBtn = document.querySelector('.scroll-btn-left');
    const scrollRightBtn = document.querySelector('.scroll-btn-right');
    
    if (scrollContainer) {
        // Add smooth scrolling behavior
        scrollContainer.style.scrollBehavior = 'smooth';
        
        // Add mouse wheel scrolling support with increased speed
        scrollContainer.addEventListener('wheel', (evt) => {
            evt.preventDefault();
            scrollContainer.scrollLeft += evt.deltaY * 3; // Increased speed by 2x
        });
        
        // Update button states on scroll
        scrollContainer.addEventListener('scroll', function() {
            if (scrollLeftBtn && scrollRightBtn) {
                // Check if we can scroll left
                scrollLeftBtn.disabled = scrollContainer.scrollLeft <= 0;
                
                // Check if we can scroll right
                const maxScroll = scrollContainer.scrollWidth - scrollContainer.clientWidth;
                scrollRightBtn.disabled = scrollContainer.scrollLeft >= maxScroll;
            }
        });
        
        // Trigger initial check
        setTimeout(() => {
            scrollContainer.dispatchEvent(new Event('scroll'));
        }, 100);
    }
});

// Function for horizontal scrolling with buttons
function scrollHorizontal(direction) {
    const scrollContainer = document.getElementById('commonToolsScroll');
    const scrollAmount = 700; // Increased from 300 to 700 for faster scrolling
    
    if (direction === 'left') {
        scrollContainer.scrollLeft -= scrollAmount;
    } else {
        scrollContainer.scrollLeft += scrollAmount;
    }
}
</script>

<style>
/* Custom styling for CTA login button to make it more visible */
.cta-login-btn {
    background-color: rgba(255, 255, 255, 0.2);
    color: white;
    border: 2px solid white;
    backdrop-filter: blur(5px);
}

.cta-login-btn:hover {
    background-color: white;
    color: #2D2D2D;
    border-color: white;
    transform: translateY(-2px);
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
}
</style>

<?php include 'includes/footer.php'; ?>