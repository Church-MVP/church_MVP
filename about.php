<?php
/**
 * About Page - about.php
 * 
 * Provides information about the church:
 * - Mission and vision
 * - Leadership team
 * - What to expect when visiting
 * - Service times and location
 */

// Include database connection
require_once 'includes/db.php';

// Set page title
$page_title = 'About Us';

// Fetch site settings
$stmt = $pdo->query("SELECT * FROM site_settings");
$settings = [];
while ($row = $stmt->fetch()) {
    $settings[$row['setting_key']] = $row['setting_value'];
}

// Include header
include 'includes/header.php';
?>

<!-- Page Header -->
<section class="hero" style="min-height: 300px; padding: 4rem 1rem;">
    <div class="hero-content">
        <h1>About Us</h1>
        <p>Get to know Grace Community Church</p>
    </div>
</section>

<!-- Our Mission Section -->
<section class="section">
    <div class="container">
        <h2 class="section-title">Our Mission</h2>
        <p class="section-subtitle">Helping people discover Jesus, grow in their relationship with Him, and make a difference in the world</p>
        
        <div class="two-column" style="margin-top: 3rem;">
            <div class="column" style="background-color: #fff; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.1);">
                <h3><i class="fas fa-heart" style="color: var(--primary-light);"></i> Our Vision</h3>
                <p>We envision a community where everyone can experience the transforming love of Jesus Christ. Through authentic worship, biblical teaching, and genuine fellowship, we strive to create an environment where people of all backgrounds can grow in their faith and discover their God-given purpose.</p>
            </div>
            
            <div class="column" style="background-color: #fff; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.1);">
                <h3><i class="fas fa-compass" style="color: var(--primary-light);"></i> Our Values</h3>
                <ul style="line-height: 2;">
                    <li><strong>Faith:</strong> We trust in God's promises and His plan</li>
                    <li><strong>Community:</strong> We believe in the power of togetherness</li>
                    <li><strong>Service:</strong> We serve others with compassion</li>
                    <li><strong>Growth:</strong> We encourage spiritual development</li>
                    <li><strong>Authenticity:</strong> We value genuine relationships</li>
                </ul>
            </div>
        </div>
    </div>
</section>

<!-- What to Expect Section -->
<section class="section" style="background-color: #fff;">
    <div class="container">
        <h2 class="section-title">What to Expect</h2>
        <p class="section-subtitle">First time visiting? Here's what you can expect when you join us</p>
        
        <div class="card-grid">
            <div class="card">
                <div class="card-content">
                    <div style="font-size: 3rem; color: var(--primary-light); margin-bottom: 1rem;">
                        <i class="fas fa-door-open"></i>
                    </div>
                    <h3 class="card-title">Welcoming Environment</h3>
                    <p class="card-text">From the moment you arrive, you'll be greeted by friendly faces. Our greeters will help you find your way and answer any questions you might have.</p>
                </div>
            </div>
            
            <div class="card">
                <div class="card-content">
                    <div style="font-size: 3rem; color: var(--primary-light); margin-bottom: 1rem;">
                        <i class="fas fa-music"></i>
                    </div>
                    <h3 class="card-title">Contemporary Worship</h3>
                    <p class="card-text">Our worship services feature modern music led by a talented worship team. We create an atmosphere where you can genuinely connect with God.</p>
                </div>
            </div>
            
            <div class="card">
                <div class="card-content">
                    <div style="font-size: 3rem; color: var(--primary-light); margin-bottom: 1rem;">
                        <i class="fas fa-book-open"></i>
                    </div>
                    <h3 class="card-title">Biblical Teaching</h3>
                    <p class="card-text">Our messages are relevant, practical, and rooted in Scripture. You'll leave with insights you can apply to your everyday life.</p>
                </div>
            </div>
            
            <div class="card">
                <div class="card-content">
                    <div style="font-size: 3rem; color: var(--primary-light); margin-bottom: 1rem;">
                        <i class="fas fa-users"></i>
                    </div>
                    <h3 class="card-title">Kids Programs</h3>
                    <p class="card-text">We offer age-appropriate programs for children during all services, so parents can worship while kids have fun learning about God's love.</p>
                </div>
            </div>
            
            <div class="card">
                <div class="card-content">
                    <div style="font-size: 3rem; color: var(--primary-light); margin-bottom: 1rem;">
                        <i class="fas fa-coffee"></i>
                    </div>
                    <h3 class="card-title">Coffee & Fellowship</h3>
                    <p class="card-text">Before and after services, enjoy complimentary coffee and refreshments while connecting with other members of our church family.</p>
                </div>
            </div>
            
            <div class="card">
                <div class="card-content">
                    <div style="font-size: 3rem; color: var(--primary-light); margin-bottom: 1rem;">
                        <i class="fas fa-clock"></i>
                    </div>
                    <h3 class="card-title">Service Duration</h3>
                    <p class="card-text">Services typically last about 75 minutes, including worship, announcements, and the message. Come a few minutes early to get settled!</p>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Service Times & Location -->
<section class="section">
    <div class="container">
        <h2 class="section-title">Service Times & Location</h2>
        
        <div class="two-column">
            <div class="column" style="background-color: #fff; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.1);">
                <h3><i class="fas fa-calendar-alt" style="color: var(--primary-light);"></i> Service Times</h3>
                <div style="margin-top: 1.5rem;">
                    <p style="font-size: 1.1rem; margin-bottom: 1rem;">
                        <i class="far fa-clock"></i> 
                        <strong><?php echo htmlspecialchars($settings['service_time_1'] ?? 'Sunday Morning: 9:00 AM'); ?></strong>
                    </p>
                    <p style="font-size: 1.1rem; margin-bottom: 1rem;">
                        <i class="far fa-clock"></i> 
                        <strong><?php echo htmlspecialchars($settings['service_time_2'] ?? 'Sunday Evening: 11:30 AM'); ?></strong>
                    </p>
                    <p style="font-size: 1.1rem; margin-bottom: 1rem;">
                        <i class="far fa-clock"></i> 
                        <strong><?php echo htmlspecialchars($settings['service_time_3'] ?? 'Wednesday Prayer: 7:00 PM'); ?></strong>
                    </p>
                </div>
                <a href="live.php" class="btn btn-primary" style="margin-top: 1rem;">
                    <i class="fas fa-play-circle"></i> Watch Online
                </a>
            </div>
            
            <div class="column" style="background-color: #fff; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.1);">
                <h3><i class="fas fa-map-marker-alt" style="color: var(--primary-light);"></i> Our Location</h3>
                <div style="margin-top: 1.5rem;">
                    <p style="font-size: 1.1rem; margin-bottom: 1rem;">
                        <strong><?php echo htmlspecialchars($settings['church_address'] ?? '1234 Divi Street, Your City, ST 12345'); ?></strong>
                    </p>
                    <p style="margin-bottom: 0.5rem;">
                        <i class="fas fa-phone"></i> 
                        <?php echo htmlspecialchars($settings['church_phone'] ?? '(555) 123-4567'); ?>
                    </p>
                    <p style="margin-bottom: 1rem;">
                        <i class="fas fa-envelope"></i> 
                        <?php echo htmlspecialchars($settings['church_email'] ?? 'info@gracechurch.com'); ?>
                    </p>
                    
                    <!-- Embedded Google Map Placeholder -->
                    <div style="width: 100%; height: 200px; background-color: #e0e0e0; border-radius: 8px; display: flex; align-items: center; justify-content: center; margin-top: 1rem;">
                        <div style="text-align: center; color: #666;">
                            <i class="fas fa-map" style="font-size: 3rem; margin-bottom: 0.5rem;"></i>
                            <p>Google Maps Integration</p>
                            <small>Add your Google Maps embed code here</small>
                        </div>
                    </div>
                </div>
                <a href="contact.php" class="btn btn-secondary" style="margin-top: 1rem;">Get Directions</a>
            </div>
        </div>
    </div>
</section>

<!-- Leadership Team Section -->
<section class="section" style="background-color: #fff;">
    <div class="container">
        <h2 class="section-title">Our Leadership Team</h2>
        <p class="section-subtitle">Meet the pastoral team serving our community</p>
        
        <div class="card-grid">
            <div class="card">
                <img src="https://images.unsplash.com/photo-1560250097-0b93528c311a?w=400" 
                     alt="Pastor John Smith" 
                     class="card-image">
                <div class="card-content" style="text-align: center;">
                    <h3 class="card-title">Pastor John Smith</h3>
                    <p class="card-meta" style="color: var(--primary-light); font-weight: 600;">Senior Pastor</p>
                    <p class="card-text">Pastor John has been leading our church for over 10 years with passion and dedication to God's Word.</p>
                </div>
            </div>
            
            <div class="card">
                <img src="https://images.unsplash.com/photo-1573496359142-b8d87734a5a2?w=400" 
                     alt="Pastor Sarah Johnson" 
                     class="card-image">
                <div class="card-content" style="text-align: center;">
                    <h3 class="card-title">Pastor Sarah Johnson</h3>
                    <p class="card-meta" style="color: var(--primary-light); font-weight: 600;">Associate Pastor</p>
                    <p class="card-text">Pastor Sarah oversees our youth and young adults ministry with creativity and compassion.</p>
                </div>
            </div>
            
            <div class="card">
                <img src="https://images.unsplash.com/photo-1566492031773-4f4e44671857?w=400" 
                     alt="Michael Davis" 
                     class="card-image">
                <div class="card-content" style="text-align: center;">
                    <h3 class="card-title">Michael Davis</h3>
                    <p class="card-meta" style="color: var(--primary-light); font-weight: 600;">Worship Director</p>
                    <p class="card-text">Michael leads our worship team in creating an atmosphere of genuine praise and worship.</p>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Call to Action -->
<section class="section" style="background: linear-gradient(135deg, var(--primary-dark), var(--accent-blue)); color: white; text-align: center;">
    <div class="container">
        <h2 style="color: white;">Ready to Visit?</h2>
        <p style="font-size: 1.2rem; margin-bottom: 2rem;">We'd love to see you this Sunday!</p>
        <div class="hero-buttons">
            <a href="contact.php" class="btn btn-outline">Contact Us</a>
            <a href="live.php" class="btn btn-primary">Watch Online</a>
        </div>
    </div>
</section>

<?php
// Include footer
include 'includes/footer.php';
?>