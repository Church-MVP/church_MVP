<?php
/**
 * Live Stream Page - live.php
 * 
 * Displays live streaming video
 * Features:
 * - Embedded live stream (YouTube/Facebook/Custom)
 * - Service schedule
 * - Chat or interaction features
 */

// Include database connection
require_once 'includes/db.php';

// Set page title
$page_title = 'Live Stream';

// Fetch live stream URL from settings
$stmt = $pdo->prepare("SELECT setting_value FROM site_settings WHERE setting_key = 'live_stream_url'");
$stmt->execute();
$live_stream_url = $stmt->fetchColumn();

// Default to YouTube embed if no URL is set
if (empty($live_stream_url)) {
    $live_stream_url = 'https://www.youtube.com/embed/VIDEO_ID';
}

// Fetch upcoming service times
$stmt = $pdo->prepare("SELECT * FROM events WHERE event_date >= CURDATE() AND title LIKE '%service%' ORDER BY event_date ASC LIMIT 3");
$stmt->execute();
$upcoming_services = $stmt->fetchAll();

// Include header
include 'includes/header.php';
?>

<!-- Page Header -->
<section class="hero" style="min-height: 250px; padding: 3rem 1rem;">
    <div class="hero-content">
        <h1><i class="fas fa-play-circle"></i> Live Stream</h1>
        <p>Join us online from anywhere in the world</p>
    </div>
</section>

<!-- Live Stream Section -->
<section class="section">
    <div class="container">
        <!-- Stream Status Alert -->
        <div class="alert alert-info" style="text-align: center; font-size: 1.1rem;">
            <i class="fas fa-info-circle"></i> 
            <strong>Live services stream on Sundays at 9:00 AM and 11:30 AM</strong>
        </div>
        
        <!-- Video Player -->
        <div style="max-width: 1000px; margin: 0 auto;">
            <div style="position: relative; padding-bottom: 56.25%; height: 0; overflow: hidden; background-color: #000; border-radius: 8px; box-shadow: 0 4px 16px rgba(0,0,0,0.2);">
                <iframe 
                    src="<?php echo htmlspecialchars($live_stream_url); ?>" 
                    style="position: absolute; top: 0; left: 0; width: 100%; height: 100%; border: none;"
                    allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" 
                    allowfullscreen>
                </iframe>
            </div>
            
            <!-- Video Controls Info -->
            <div style="text-align: center; margin-top: 1rem; color: #666;">
                <p><i class="fas fa-info-circle"></i> If the stream hasn't started yet, you'll see a message or countdown. Check back at service time!</p>
            </div>
        </div>
    </div>
</section>

<!-- Service Times -->
<section class="section" style="background-color: #fff;">
    <div class="container">
        <h2 class="section-title">Service Schedule</h2>
        <p class="section-subtitle">Join us online or in person at these times</p>
        
        <div class="card-grid">
            <div class="card">
                <div class="card-content" style="text-align: center;">
                    <div style="font-size: 3rem; color: var(--primary-light); margin-bottom: 1rem;">
                        <i class="fas fa-sun"></i>
                    </div>
                    <h3 class="card-title">Sunday Morning</h3>
                    <p style="font-size: 1.5rem; font-weight: 600; color: var(--primary-dark); margin: 1rem 0;">9:00 AM</p>
                    <p class="card-text">Traditional worship service with contemporary elements. Perfect for families and those who prefer a morning service.</p>
                    <div style="margin-top: 1rem; padding-top: 1rem; border-top: 1px solid #e0e0e0;">
                        <p style="color: #666;"><i class="fas fa-clock"></i> Duration: ~75 minutes</p>
                    </div>
                </div>
            </div>
            
            <div class="card" style="border: 2px solid var(--primary-light);">
                <div class="card-content" style="text-align: center;">
                    <div style="font-size: 3rem; color: var(--primary-light); margin-bottom: 1rem;">
                        <i class="fas fa-church"></i>
                    </div>
                    <h3 class="card-title">Sunday Evening</h3>
                    <p style="font-size: 1.5rem; font-weight: 600; color: var(--primary-dark); margin: 1rem 0;">11:30 AM</p>
                    <p class="card-text">Contemporary worship with upbeat music and engaging messages. Great for young adults and families.</p>
                    <div style="margin-top: 1rem; padding-top: 1rem; border-top: 1px solid #e0e0e0;">
                        <p style="color: #666;"><i class="fas fa-clock"></i> Duration: ~75 minutes</p>
                    </div>
                </div>
            </div>
            
            <div class="card">
                <div class="card-content" style="text-align: center;">
                    <div style="font-size: 3rem; color: var(--primary-light); margin-bottom: 1rem;">
                        <i class="fas fa-praying-hands"></i>
                    </div>
                    <h3 class="card-title">Wednesday Prayer</h3>
                    <p style="font-size: 1.5rem; font-weight: 600; color: var(--primary-dark); margin: 1rem 0;">7:00 PM</p>
                    <p class="card-text">Midweek prayer meeting and Bible study. Join us as we grow together in God's Word and prayer.</p>
                    <div style="margin-top: 1rem; padding-top: 1rem; border-top: 1px solid #e0e0e0;">
                        <p style="color: #666;"><i class="fas fa-clock"></i> Duration: ~60 minutes</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- How to Watch Section -->
<section class="section">
    <div class="container">
        <h2 class="section-title">How to Watch</h2>
        
        <div class="two-column">
            <div class="column" style="background-color: #fff; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.1);">
                <h3><i class="fas fa-laptop" style="color: var(--primary-light);"></i> On This Page</h3>
                <p>Simply return to this page at service time, and the live stream will automatically appear in the video player above. No downloads or accounts required!</p>
                <ul style="margin-top: 1rem; line-height: 2;">
                    <li>Works on desktop and mobile</li>
                    <li>No special software needed</li>
                    <li>HD quality streaming</li>
                    <li>Rewind and pause available</li>
                </ul>
            </div>
            
            <div class="column" style="background-color: #fff; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.1);">
                <h3><i class="fab fa-youtube" style="color: var(--primary-light);"></i> On YouTube</h3>
                <p>Prefer to watch on YouTube? Subscribe to our channel and get notified when we go live. You can also watch past services anytime!</p>
                <ul style="margin-top: 1rem; line-height: 2;">
                    <li>Subscribe for notifications</li>
                    <li>Watch on your Smart TV</li>
                    <li>Access full sermon archive</li>
                    <li>Like and share messages</li>
                </ul>
                <a href="#" class="btn btn-danger" style="margin-top: 1rem; background-color: #FF0000;">
                    <i class="fab fa-youtube"></i> Visit Our Channel
                </a>
            </div>
        </div>
    </div>
</section>

<!-- Connection Card -->
<section class="section" style="background-color: #fff;">
    <div class="container">
        <div style="background: linear-gradient(135deg, var(--primary-light), var(--accent-blue)); color: white; padding: 3rem; border-radius: 8px; text-align: center;">
            <h2 style="color: white; margin-bottom: 1rem;">Watching Online?</h2>
            <p style="font-size: 1.2rem; margin-bottom: 2rem;">We'd love to connect with you! Let us know you're watching and if you have any prayer requests.</p>
            <a href="contact.php" class="btn btn-outline">Send Us a Message</a>
        </div>
    </div>
</section>

<!-- FAQ Section -->
<section class="section">
    <div class="container">
        <h2 class="section-title">Frequently Asked Questions</h2>
        
        <div style="max-width: 800px; margin: 0 auto;">
            <div style="background-color: #fff; padding: 1.5rem; margin-bottom: 1rem; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.1);">
                <h4 style="color: var(--primary-dark); margin-bottom: 0.5rem;">
                    <i class="fas fa-question-circle" style="color: var(--primary-light);"></i> 
                    What if I miss the live stream?
                </h4>
                <p>Don't worry! All services are recorded and available on our Services page within a few hours after the service ends.</p>
            </div>
            
            <div style="background-color: #fff; padding: 1.5rem; margin-bottom: 1rem; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.1);">
                <h4 style="color: var(--primary-dark); margin-bottom: 0.5rem;">
                    <i class="fas fa-question-circle" style="color: var(--primary-light);"></i> 
                    Can I watch on my mobile device?
                </h4>
                <p>Yes! Our live stream works on smartphones, tablets, and computers. Just visit this page on any device with an internet connection.</p>
            </div>
            
            <div style="background-color: #fff; padding: 1.5rem; margin-bottom: 1rem; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.1);">
                <h4 style="color: var(--primary-dark); margin-bottom: 0.5rem;">
                    <i class="fas fa-question-circle" style="color: var(--primary-light);"></i> 
                    Is there a chat feature during live services?
                </h4>
                <p>If you watch through YouTube, you can participate in the live chat. Otherwise, feel free to connect with us through our contact page.</p>
            </div>
            
            <div style="background-color: #fff; padding: 1.5rem; margin-bottom: 1rem; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.1);">
                <h4 style="color: var(--primary-dark); margin-bottom: 0.5rem;">
                    <i class="fas fa-question-circle" style="color: var(--primary-light);"></i> 
                    Can I give online while watching?
                </h4>
                <p>Absolutely! Visit our <a href="donate.php">donation page</a> to give securely online at any time.</p>
            </div>
        </div>
    </div>
</section>

<!-- Call to Action -->
<section class="section" style="background: linear-gradient(135deg, var(--primary-dark), var(--accent-blue)); color: white; text-align: center;">
    <div class="container">
        <h2 style="color: white;">We'd Love to See You in Person</h2>
        <p style="font-size: 1.2rem; margin-bottom: 2rem;">While we love connecting online, there's nothing quite like worshiping together in person!</p>
        <div class="hero-buttons">
            <a href="about.php" class="btn btn-outline">Plan Your Visit</a>
            <a href="services.php" class="btn btn-primary">View Past Sermons</a>
        </div>
    </div>
</section>

<?php
// Include footer
include 'includes/footer.php';
?>