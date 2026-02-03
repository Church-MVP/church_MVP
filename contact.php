<?php
/**
 * Contact Page - contact.php
 * 
 * Allows users to contact the church
 * Features:
 * - Contact form
 * - Church contact information
 * - Location map
 * - Office hours
 */

// Include database connection
require_once 'includes/db.php';

// Set page title
$page_title = 'Contact Us';

// Fetch site settings
$stmt = $pdo->query("SELECT * FROM site_settings");
$settings = [];
while ($row = $stmt->fetch()) {
    $settings[$row['setting_key']] = $row['setting_value'];
}

// Initialize variables
$success_message = '';
$error_message = '';

// Handle form submission (MVP: just display success message, not actually sending email)
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Sanitize input
    $name = sanitize_input($_POST['name']);
    $email = sanitize_input($_POST['email']);
    $phone = sanitize_input($_POST['phone']);
    $subject = sanitize_input($_POST['subject']);
    $message = sanitize_input($_POST['message']);
    
    // Validation
    if (empty($name) || empty($email) || empty($subject) || empty($message)) {
        $error_message = 'Please fill in all required fields.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error_message = 'Please enter a valid email address.';
    } else {
        // In a full version, you would send an email here
        // For MVP, we just show success message
        $success_message = 'Thank you for contacting us! We have received your message and will respond within 24-48 hours.';
        
        // Clear form
        $_POST = [];
    }
}

// Include header
include 'includes/header.php';
?>

<!-- Page Header -->
<section class="hero" style="min-height: 300px; padding: 4rem 1rem;">
    <div class="hero-content">
        <h1><i class="fas fa-envelope"></i> Contact Us</h1>
        <p>We'd love to hear from you!</p>
    </div>
</section>

<!-- Contact Information Section -->
<section class="section">
    <div class="container">
        <h2 class="section-title">Get in Touch</h2>
        <p class="section-subtitle">Have questions? Need prayer? Want to get involved? Reach out to us!</p>
        
        <div class="card-grid">
            <div class="card">
                <div class="card-content" style="text-align: center;">
                    <div style="font-size: 3rem; color: var(--primary-light); margin-bottom: 1rem;">
                        <i class="fas fa-map-marker-alt"></i>
                    </div>
                    <h3 class="card-title">Visit Us</h3>
                    <p class="card-text">
                        <strong><?php echo htmlspecialchars($settings['church_address'] ?? '1234 Divi Street, Your City, ST 12345'); ?></strong>
                    </p>
                    <a href="https://maps.google.com" target="_blank" class="btn btn-primary" style="margin-top: 1rem;">
                        <i class="fas fa-directions"></i> Get Directions
                    </a>
                </div>
            </div>
            
            <div class="card">
                <div class="card-content" style="text-align: center;">
                    <div style="font-size: 3rem; color: var(--primary-light); margin-bottom: 1rem;">
                        <i class="fas fa-phone"></i>
                    </div>
                    <h3 class="card-title">Call Us</h3>
                    <p class="card-text">
                        <strong><?php echo htmlspecialchars($settings['church_phone'] ?? '(555) 123-4567'); ?></strong>
                    </p>
                    <p style="color: #666; margin-top: 1rem;">
                        Office Hours:<br>
                        Mon-Fri: 9:00 AM - 5:00 PM<br>
                        Sat-Sun: Closed (See you at service!)
                    </p>
                </div>
            </div>
            
            <div class="card">
                <div class="card-content" style="text-align: center;">
                    <div style="font-size: 3rem; color: var(--primary-light); margin-bottom: 1rem;">
                        <i class="fas fa-envelope"></i>
                    </div>
                    <h3 class="card-title">Email Us</h3>
                    <p class="card-text">
                        <strong><?php echo htmlspecialchars($settings['church_email'] ?? 'info@gracechurch.com'); ?></strong>
                    </p>
                    <p style="color: #666; margin-top: 1rem;">
                        We typically respond within 24-48 hours during business days.
                    </p>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Contact Form Section -->
<section class="section" style="background-color: #fff;">
    <div class="container">
        <div style="max-width: 700px; margin: 0 auto;">
            <h2 class="section-title">Send Us a Message</h2>
            
            <!-- Success Message -->
            <?php if (!empty($success_message)): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i> <?php echo $success_message; ?>
            </div>
            <?php endif; ?>
            
            <!-- Error Message -->
            <?php if (!empty($error_message)): ?>
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-circle"></i> <?php echo $error_message; ?>
            </div>
            <?php endif; ?>
            
            <!-- Contact Form -->
            <form method="POST" action="" style="background-color: #f8f9fa; padding: 2rem; border-radius: 8px;">
                <!-- Name -->
                <div class="form-group">
                    <label for="name">Full Name <span style="color: red;">*</span></label>
                    <input type="text" 
                           id="name" 
                           name="name" 
                           class="form-control" 
                           value="<?php echo isset($_POST['name']) ? htmlspecialchars($_POST['name']) : ''; ?>"
                           required>
                </div>
                
                <!-- Email -->
                <div class="form-group">
                    <label for="email">Email Address <span style="color: red;">*</span></label>
                    <input type="email" 
                           id="email" 
                           name="email" 
                           class="form-control" 
                           value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>"
                           required>
                </div>
                
                <!-- Phone -->
                <div class="form-group">
                    <label for="phone">Phone Number (Optional)</label>
                    <input type="tel" 
                           id="phone" 
                           name="phone" 
                           class="form-control" 
                           value="<?php echo isset($_POST['phone']) ? htmlspecialchars($_POST['phone']) : ''; ?>"
                           placeholder="(555) 123-4567">
                </div>
                
                <!-- Subject -->
                <div class="form-group">
                    <label for="subject">Subject <span style="color: red;">*</span></label>
                    <select id="subject" name="subject" class="form-control" required>
                        <option value="">-- Select a subject --</option>
                        <option value="General Inquiry">General Inquiry</option>
                        <option value="Prayer Request">Prayer Request</option>
                        <option value="Visit Information">Visit Information</option>
                        <option value="Volunteer Opportunities">Volunteer Opportunities</option>
                        <option value="Youth Ministry">Youth Ministry</option>
                        <option value="Counseling">Counseling</option>
                        <option value="Wedding/Baptism">Wedding/Baptism</option>
                        <option value="Other">Other</option>
                    </select>
                </div>
                
                <!-- Message -->
                <div class="form-group">
                    <label for="message">Message <span style="color: red;">*</span></label>
                    <textarea id="message" 
                              name="message" 
                              class="form-control" 
                              rows="6" 
                              maxlength="1000"
                              required><?php echo isset($_POST['message']) ? htmlspecialchars($_POST['message']) : ''; ?></textarea>
                </div>
                
                <!-- Submit Button -->
                <button type="submit" class="btn btn-primary" style="width: 100%; padding: 1rem; font-size: 1.1rem;">
                    <i class="fas fa-paper-plane"></i> Send Message
                </button>
                
                <p style="text-align: center; margin-top: 1rem; color: #666; font-size: 0.9rem;">
                    We respect your privacy and will not share your information with third parties.
                </p>
            </form>
        </div>
    </div>
</section>

<!-- Map Section -->
<section class="section">
    <div class="container">
        <h2 class="section-title">Find Us</h2>
        
        <!-- Google Map Placeholder -->
        <div style="width: 100%; height: 400px; background-color: #e0e0e0; border-radius: 8px; overflow: hidden; box-shadow: 0 4px 16px rgba(0,0,0,0.1);">
            <div style="display: flex; align-items: center; justify-content: center; height: 100%; text-align: center; color: #666;">
                <div>
                    <i class="fas fa-map-marked-alt" style="font-size: 5rem; margin-bottom: 1rem; color: var(--primary-light);"></i>
                    <h3>Google Maps Integration</h3>
                    <p>Replace this placeholder with your Google Maps embed code</p>
                    <small style="display: block; margin-top: 1rem; max-width: 500px; padding: 0 1rem;">
                        Example: &lt;iframe src="https://www.google.com/maps/embed?..." width="100%" height="400"&gt;&lt;/iframe&gt;
                    </small>
                </div>
            </div>
        </div>
        
        <!-- Directions -->
        <div style="margin-top: 2rem; text-align: center;">
            <p style="font-size: 1.1rem; margin-bottom: 1rem;">
                <strong>Address:</strong> <?php echo htmlspecialchars($settings['church_address'] ?? '1234 Divi Street, Your City, ST 12345'); ?>
            </p>
            <a href="https://maps.google.com" target="_blank" class="btn btn-primary">
                <i class="fas fa-directions"></i> Get Directions on Google Maps
            </a>
        </div>
    </div>
</section>

<!-- Department Contacts -->
<section class="section" style="background-color: #fff;">
    <div class="container">
        <h2 class="section-title">Department Contacts</h2>
        <p class="section-subtitle">Need to reach a specific department? Here are direct contacts</p>
        
        <div class="card-grid">
            <div class="card">
                <div class="card-content">
                    <h4 style="color: var(--primary-dark); margin-bottom: 0.5rem;">
                        <i class="fas fa-church" style="color: var(--primary-light);"></i> 
                        Pastoral Care
                    </h4>
                    <p style="color: #666; margin-bottom: 0.5rem;">For spiritual guidance and prayer</p>
                    <p><i class="fas fa-envelope"></i> pastor@gracechurch.com</p>
                </div>
            </div>
            
            <div class="card">
                <div class="card-content">
                    <h4 style="color: var(--primary-dark); margin-bottom: 0.5rem;">
                        <i class="fas fa-users" style="color: var(--primary-light);"></i> 
                        Youth Ministry
                    </h4>
                    <p style="color: #666; margin-bottom: 0.5rem;">For youth and young adult programs</p>
                    <p><i class="fas fa-envelope"></i> youth@gracechurch.com</p>
                </div>
            </div>
            
            <div class="card">
                <div class="card-content">
                    <h4 style="color: var(--primary-dark); margin-bottom: 0.5rem;">
                        <i class="fas fa-music" style="color: var(--primary-light);"></i> 
                        Worship Ministry
                    </h4>
                    <p style="color: #666; margin-bottom: 0.5rem;">For worship team and music ministry</p>
                    <p><i class="fas fa-envelope"></i> worship@gracechurch.com</p>
                </div>
            </div>
            
            <div class="card">
                <div class="card-content">
                    <h4 style="color: var(--primary-dark); margin-bottom: 0.5rem;">
                        <i class="fas fa-hands-helping" style="color: var(--primary-light);"></i> 
                        Community Outreach
                    </h4>
                    <p style="color: #666; margin-bottom: 0.5rem;">For volunteer and outreach opportunities</p>
                    <p><i class="fas fa-envelope"></i> outreach@gracechurch.com</p>
                </div>
            </div>
            
            <div class="card">
                <div class="card-content">
                    <h4 style="color: var(--primary-dark); margin-bottom: 0.5rem;">
                        <i class="fas fa-building" style="color: var(--primary-light);"></i> 
                        Facilities
                    </h4>
                    <p style="color: #666; margin-bottom: 0.5rem;">For facility rental and maintenance</p>
                    <p><i class="fas fa-envelope"></i> facilities@gracechurch.com</p>
                </div>
            </div>
            
            <div class="card">
                <div class="card-content">
                    <h4 style="color: var(--primary-dark); margin-bottom: 0.5rem;">
                        <i class="fas fa-hand-holding-usd" style="color: var(--primary-light);"></i> 
                        Finance/Giving
                    </h4>
                    <p style="color: #666; margin-bottom: 0.5rem;">For donation inquiries and receipts</p>
                    <p><i class="fas fa-envelope"></i> finance@gracechurch.com</p>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Social Media -->
<section class="section" style="background: linear-gradient(135deg, var(--primary-dark), var(--accent-blue)); color: white; text-align: center;">
    <div class="container">
        <h2 style="color: white; margin-bottom: 1rem;">Connect With Us on Social Media</h2>
        <p style="font-size: 1.2rem; margin-bottom: 2rem;">Stay updated with our latest news, events, and messages</p>
        
        <div class="social-links" style="justify-content: center; font-size: 2rem;">
            <a href="#" aria-label="Facebook"><i class="fab fa-facebook-f"></i></a>
            <a href="#" aria-label="Twitter"><i class="fab fa-twitter"></i></a>
            <a href="#" aria-label="Instagram"><i class="fab fa-instagram"></i></a>
            <a href="#" aria-label="YouTube"><i class="fab fa-youtube"></i></a>
        </div>
    </div>
</section>

<?php
// Include footer
include 'includes/footer.php';
?>