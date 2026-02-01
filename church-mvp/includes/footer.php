<?php
/**
 * Footer Template
 * 
 * Dynamically displays footer content from site_settings
 * Include this file at the bottom of all frontend pages
 */

// Fetch site settings if not already loaded
if (!isset($settings) || empty($settings)) {
    // Include database connection if not already included
    if (!isset($pdo)) {
        require_once __DIR__ . '/includes/db.php';
    }
    
    try {
        $stmt = $pdo->query("SELECT setting_key, setting_value FROM site_settings");
        $settings = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $settings[$row['setting_key']] = $row['setting_value'];
        }
    } catch (PDOException $e) {
        $settings = [];
    }
}

// Helper function to get setting with fallback
function get_setting($key, $default = '') {
    global $settings;
    return isset($settings[$key]) && !empty($settings[$key]) ? $settings[$key] : $default;
}

// Get social links (stored as JSON)
$social_links = [];
if (!empty($settings['contact_social_links'])) {
    $social_links = json_decode($settings['contact_social_links'], true) ?? [];
}

// Social media icon mapping
$social_icons = [
    'facebook' => 'fab fa-facebook-f',
    'instagram' => 'fab fa-instagram',
    'twitter' => 'fab fa-twitter',
    'youtube' => 'fab fa-youtube',
    'tiktok' => 'fab fa-tiktok',
    'linkedin' => 'fab fa-linkedin-in',
    'pinterest' => 'fab fa-pinterest-p',
    'whatsapp' => 'fab fa-whatsapp',
    'telegram' => 'fab fa-telegram-plane'
];
?>

<!-- Footer -->
<footer class="footer">
    <div class="container">
        <div class="footer-content">
            <!-- Footer Column 1: About -->
            <div class="footer-column">
                <h3><?php echo htmlspecialchars(get_setting('site_title', 'Christ Mission Ministries Inc')); ?></h3>
                <p><?php echo htmlspecialchars(get_setting('footer_description', 'Helping you grow your faith and connect with God\'s love. Join us every Sunday as we worship together.')); ?></p>
                
                <?php if (!empty($social_links)): ?>
                <div class="social-links">
                    <?php foreach ($social_links as $link): ?>
                        <?php if (!empty($link['url'])): ?>
                        <a href="<?php echo htmlspecialchars($link['url']); ?>" 
                           aria-label="<?php echo ucfirst($link['platform']); ?>" 
                           target="_blank" 
                           rel="noopener noreferrer">
                            <i class="<?php echo $social_icons[$link['platform']] ?? 'fas fa-link'; ?>"></i>
                        </a>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </div>
                <?php else: ?>
                <!-- Default social links if none configured -->
                <div class="social-links">
                    <a href="#" aria-label="Facebook"><i class="fab fa-facebook-f"></i></a>
                    <a href="#" aria-label="Twitter"><i class="fab fa-twitter"></i></a>
                    <a href="#" aria-label="Instagram"><i class="fab fa-instagram"></i></a>
                    <a href="#" aria-label="YouTube"><i class="fab fa-youtube"></i></a>
                </div>
                <?php endif; ?>
            </div>
            
            <!-- Footer Column 2: Quick Links -->
            <div class="footer-column">
                <h3>Quick Links</h3>
                <ul>
                    <li><a href="about.php">About Us</a></li>
                    <li><a href="sermons.php">Services & Sermons</a></li>
                    <li><a href="live.php">Watch Live</a></li>
                    <li><a href="events.php">Events</a></li>
                    <li><a href="contact.php">Contact Us</a></li>
                </ul>
            </div>
            
            <!-- Footer Column 3: Service Times -->
            <div class="footer-column">
                <h3>Service Times</h3>
                <ul>
                    <?php 
                    $service_time_1 = get_setting('service_time_1', 'Sunday Morning: 9:00 AM');
                    $service_time_2 = get_setting('service_time_2', 'Sunday Evening: 11:30 AM');
                    $service_time_3 = get_setting('service_time_3', 'Wednesday Prayer: 7:00 PM');
                    ?>
                    <?php if (!empty($service_time_1)): ?>
                    <li><i class="far fa-clock"></i> <?php echo htmlspecialchars($service_time_1); ?></li>
                    <?php endif; ?>
                    <?php if (!empty($service_time_2)): ?>
                    <li><i class="far fa-clock"></i> <?php echo htmlspecialchars($service_time_2); ?></li>
                    <?php endif; ?>
                    <?php if (!empty($service_time_3)): ?>
                    <li><i class="far fa-clock"></i> <?php echo htmlspecialchars($service_time_3); ?></li>
                    <?php endif; ?>
                </ul>
            </div>
            
            <!-- Footer Column 4: Contact -->
            <div class="footer-column">
                <h3>Contact Us</h3>
                <ul>
                    <?php 
                    // Build address from contact settings or fall back to general settings
                    $address_line1 = get_setting('contact_address_line1', '');
                    $address_line2 = get_setting('contact_address_line2', '');
                    $city = get_setting('contact_city', '');
                    $state = get_setting('contact_state', '');
                    $zip = get_setting('contact_zip', '');
                    
                    // Build full address
                    $full_address = '';
                    if (!empty($address_line1)) {
                        $full_address = $address_line1;
                        if (!empty($address_line2)) {
                            $full_address .= ', ' . $address_line2;
                        }
                        if (!empty($city) || !empty($state) || !empty($zip)) {
                            $full_address .= '<br>';
                            $city_state_zip = [];
                            if (!empty($city)) $city_state_zip[] = $city;
                            if (!empty($state)) $city_state_zip[] = $state;
                            if (!empty($zip)) $city_state_zip[] = $zip;
                            $full_address .= implode(', ', $city_state_zip);
                        }
                    } else {
                        // Fall back to general church_address
                        $full_address = get_setting('church_address', '1234 Church Street<br>Your City, ST 12345');
                    }
                    
                    // Get phone and email
                    $phone = get_setting('contact_phone_main', get_setting('church_phone', '(555) 123-4567'));
                    $email = get_setting('contact_email_main', get_setting('church_email', 'info@church.com'));
                    ?>
                    
                    <?php if (!empty($full_address)): ?>
                    <li><i class="fas fa-map-marker-alt"></i> <?php echo $full_address; ?></li>
                    <?php endif; ?>
                    
                    <?php if (!empty($phone)): ?>
                    <li>
                        <i class="fas fa-phone"></i> 
                        <a href="tel:<?php echo preg_replace('/[^0-9+]/', '', $phone); ?>">
                            <?php echo htmlspecialchars($phone); ?>
                        </a>
                    </li>
                    <?php endif; ?>
                    
                    <?php if (!empty($email)): ?>
                    <li>
                        <i class="fas fa-envelope"></i> 
                        <a href="mailto:<?php echo htmlspecialchars($email); ?>">
                            <?php echo htmlspecialchars($email); ?>
                        </a>
                    </li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
        
        <!-- Footer Bottom -->
        <div class="footer-bottom">
            <p>&copy; <?php echo date('Y'); ?> <?php echo htmlspecialchars(get_setting('site_title', 'Christ Mission Ministries Inc')); ?>. All rights reserved.</p>
            <p><a href="admin/login.php">Admin Login</a></p>
        </div>
    </div>
</footer>

<!-- Main JavaScript -->
<script src="assets/js/main.js"></script>
</body>
</html>
