<?php
/**
 * Homepage - index.php
 * 
 * Main landing page for the church website
 * Displays:
 * - Hero section with call-to-action buttons
 * - Service times section
 * - About section
 * - Latest sermons/events
 * - Latest blog posts/news
 */

// Include database connection
require_once 'includes/db.php';

// Set page title
$page_title = 'Home';

// Fetch site settings from database
$stmt = $pdo->query("SELECT * FROM site_settings");
$settings = [];
while ($row = $stmt->fetch()) {
    $settings[$row['setting_key']] = $row['setting_value'];
}

// Fetch latest 3 sermons
$stmt = $pdo->prepare("SELECT * FROM sermons ORDER BY sermon_date DESC");
$stmt->execute();
$latest_sermons = $stmt->fetchAll();

// Fetch upcoming events
$stmt = $pdo->prepare("SELECT * FROM events WHERE event_date >= CURDATE() ORDER BY event_date ASC LIMIT 3");
$stmt->execute();
$upcoming_events = $stmt->fetchAll();

// Fetch active announcements
$stmt = $pdo->prepare("SELECT * FROM announcements WHERE is_active = 1 ORDER BY announcement_date DESC LIMIT 3");
$stmt->execute();
$announcements = $stmt->fetchAll();

// Include header
include 'includes/header.php';
?>

<!-- Hero Section -->
<section class="hero">
    <div class="hero-content">
        <h1><?php echo htmlspecialchars($settings['hero_title'] ?? 'Helping You Grow Your Faith'); ?></h1>
        <p><?php echo htmlspecialchars($settings['hero_subtitle'] ?? '1234 Divi St. | Sundays @ 9 & 11:30am'); ?></p>
        <div class="hero-buttons">
            <a href="about.php" class="btn btn-primary">New Here?</a>
            <a href="live.php" class="btn btn-outline">
                <i class="fas fa-play-circle"></i> Live Stream
            </a>
        </div>
    </div>
</section>

<!-- Service Times Section -->
<section class="section">
    <div class="container">
        <div class="two-column">
            <!-- Sunday Services -->
            <div class="column column-light">
                <h2>Sunday Services</h2>
                <p>Join us every Sunday as we worship together and grow in faith. Whether you're new to church or have been attending for years, you're welcome here!</p>
                <p><strong><?php echo htmlspecialchars($settings['service_time_1'] ?? 'Sunday Morning: 9:00 AM'); ?></strong></p>
                <p><strong><?php echo htmlspecialchars($settings['service_time_2'] ?? 'Sunday Evening: 11:30 AM'); ?></strong></p>
                <a href="about.php" class="btn btn-secondary">Plan Your Visit</a>
            </div>
            
            <!-- About Us -->
            <div class="column column-blue">
                <h2>About Us</h2>
                <p>Grace Community Church is a vibrant community of believers dedicated to helping you grow your faith. We believe in creating a welcoming environment where everyone can encounter God's love.</p>
                <p>Our mission is to help people discover Jesus, grow in their relationship with Him, and make a difference in the world around them.</p>
                <a href="about.php" class="btn btn-outline">Learn More</a>
            </div>
        </div>
    </div>
</section>

<!-- Latest News & Updates Section -->
<?php
$posts_page = 'home';
$posts_limit = 3;
$posts_title = 'Latest News & Updates';
$posts_subtitle = 'Stay connected with what\'s happening at our church';
$show_view_all = true;
include 'includes/post-section.php';
?>

<!-- Latest Sermons Section -->
<?php if (!empty($latest_sermons)): ?>
<section class="section" style="background-color: #fff;">
    <div class="container">
        <h2 class="section-title">Latest Sermons</h2>
        <p class="section-subtitle">Catch up on recent messages or watch them for the first time</p>
        
        <div class="card-grid">
            <?php foreach ($latest_sermons as $sermon): ?>
            <?php
            // Determine the image to display
            if (!empty($sermon['cover_image']) && file_exists($sermon['cover_image'])) {
                $sermon_image = htmlspecialchars($sermon['cover_image']);
            } else {
                $sermon_image = 'https://images.unsplash.com/photo-1507003211169-0a1dd7228f2d?w=400&h=250&fit=crop';
            }
            ?>
            <div class="card">
                <img src="<?php echo $sermon_image; ?>" 
                     alt="<?php echo htmlspecialchars($sermon['title']); ?>" 
                     class="card-image"
                     onerror="this.src='https://images.unsplash.com/photo-1507003211169-0a1dd7228f2d?w=400&h=250&fit=crop';">
                <div class="card-content">
                    <h3 class="card-title"><?php echo htmlspecialchars($sermon['title']); ?></h3>
                    <p class="card-meta">
                        <i class="fas fa-user"></i> <?php echo htmlspecialchars($sermon['preacher']); ?>
                        <br>
                        <i class="fas fa-calendar"></i> <?php echo format_date($sermon['sermon_date']); ?>
                        <?php if (!empty($sermon['scripture_reference'])): ?>
                        <br>
                        <i class="fas fa-book"></i> <?php echo htmlspecialchars($sermon['scripture_reference']); ?>
                        <?php endif; ?>
                    </p>
                    <?php if (!empty($sermon['description'])): ?>
                    <p class="card-text"><?php echo htmlspecialchars(substr($sermon['description'], 0, 100)); ?>...</p>
                    <?php endif; ?>
                    <a href="services.php#sermons" class="btn btn-primary">Watch Sermon</a>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        
        <div style="text-align: center; margin-top: 2rem;">
            <a href="services.php#sermons" class="btn btn-secondary">View All Sermons</a>
        </div>
    </div>
</section>
<?php endif; ?>

<!-- Upcoming Events Section -->
<?php if (!empty($upcoming_events)): ?>
<section class="section">
    <div class="container">
        <h2 class="section-title">Upcoming Events</h2>
        <p class="section-subtitle">Join us for these exciting opportunities to connect and grow</p>
        
        <div class="card-grid">
            <?php foreach ($upcoming_events as $event): ?>
            <?php
            // Determine the image to display
            if (!empty($event['event_image']) && file_exists($event['event_image'])) {
                $event_image = htmlspecialchars($event['event_image']);
            } elseif (!empty($event['image_url'])) {
                $event_image = htmlspecialchars($event['image_url']);
            } else {
                $event_image = 'https://images.unsplash.com/photo-1511632765486-a01980e01a18?w=400';
            }
            ?>
            <div class="card">
                <img src="<?php echo $event_image; ?>" 
                     alt="<?php echo htmlspecialchars($event['title']); ?>" 
                     class="card-image"
                     onerror="this.src='https://images.unsplash.com/photo-1511632765486-a01980e01a18?w=400';">
                <div class="card-content">
                    <h3 class="card-title"><?php echo htmlspecialchars($event['title']); ?></h3>
                    <p class="card-meta">
                        <i class="fas fa-calendar"></i> <?php echo format_date($event['event_date']); ?>
                        <br>
                        <i class="fas fa-clock"></i> <?php echo date('g:i A', strtotime($event['event_time'])); ?>
                        <br>
                        <i class="fas fa-map-marker-alt"></i> <?php echo htmlspecialchars($event['location']); ?>
                    </p>
                    <?php if (!empty($event['description'])): ?>
                    <p class="card-text"><?php echo htmlspecialchars(substr($event['description'], 0, 100)); ?>...</p>
                    <?php endif; ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        
        <div style="text-align: center; margin-top: 2rem;">
            <a href="services.php#events" class="btn btn-secondary">View All Events</a>
        </div>
    </div>
</section>
<?php endif; ?>

<!-- Announcements Section -->
<?php if (!empty($announcements)): ?>
<section class="section" style="background-color: #fff;">
    <div class="container">
        <h2 class="section-title">Announcements</h2>
        
        <?php foreach ($announcements as $announcement): ?>
        <div class="alert alert-info">
            <h4><?php echo htmlspecialchars($announcement['title']); ?></h4>
            <p><?php echo nl2br(htmlspecialchars($announcement['content'])); ?></p>
            <small><em><?php echo format_date($announcement['announcement_date']); ?></em></small>
        </div>
        <?php endforeach; ?>
    </div>
</section>
<?php endif; ?>

<!-- Call to Action Section -->
<section class="section" style="background: linear-gradient(135deg, var(--primary-dark), var(--accent-blue)); color: white; text-align: center;">
    <div class="container">
        <h2 style="color: white;">Ready to Take the Next Step?</h2>
        <p style="font-size: 1.2rem; margin-bottom: 2rem;">Join us this Sunday or support our mission</p>
        <div class="hero-buttons">
            <a href="about.php" class="btn btn-outline">Plan Your Visit</a>
            <a href="donate.php" class="btn btn-primary">Give Online</a>
        </div>
    </div>
</section>

<?php
// Include footer
include 'includes/footer.php';
?>

<?php
// ... your existing connection code ...

try {
    // Line 28 - add proper error handling
    $stmt = $pdo->prepare("SELECT * FROM sermons ORDER BY sermon_date DESC");
    $stmt->execute();
    $sermons = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    echo "<div class='alert alert-warning'>Sermons table not found or empty. Please run database setup.</div>";
    $sermons = []; // Empty array to prevent further errors
    // Uncomment below to see the actual error during development:
    // echo "<div class='alert alert-danger'>Error: " . $e->getMessage() . "</div>";
}
?>