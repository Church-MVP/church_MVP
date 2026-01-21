<?php
/**
 * Unified Services, Sermons & Events Page
 * 
 * All ministry content in one place with smooth navigation
 */

// Include database connection
require_once 'includes/db.php';

// Set page title
$page_title = 'Services, Sermons & Events';

// Fetch service times from settings
$stmt = $pdo->query("SELECT * FROM site_settings WHERE setting_key LIKE 'service_time_%'");
$service_times = [];
while ($row = $stmt->fetch()) {
    $service_times[$row['setting_key']] = $row['setting_value'];
}

// Fetch sermons (latest 6)
$stmt = $pdo->query("SELECT * FROM sermons ORDER BY sermon_date DESC LIMIT 6");
$sermons = $stmt->fetchAll();

// Fetch upcoming events
$stmt = $pdo->query("SELECT * FROM events WHERE event_date >= CURDATE() ORDER BY event_date ASC LIMIT 6");
$upcoming_events = $stmt->fetchAll();

// Include header
include 'includes/header.php';

// Default images if none uploaded
$default_sermon_image = 'assets/images/default-sermon.jpg';
$default_event_image = 'assets/images/default-event.jpg';
?>

<!-- Page Hero -->
<section class="hero" style="min-height: 350px; padding: 5rem 1rem;">
    <div class="hero-content">
        <h1>Ministries & Events</h1>
        <p>Join us for worship, learning, and fellowship</p>
    </div>
</section>

<!-- Quick Navigation Bar -->
<div style="position: sticky; top: 60px; z-index: 100; background: white; box-shadow: 0 2px 8px rgba(0,0,0,0.1);">
    <div class="container">
        <nav style="display: flex; justify-content: center; gap: 0; overflow-x: auto; padding: 0;">
            <a href="#services" class="quick-nav-link">
                <i class="fas fa-clock"></i>
                <span>Service Times</span>
            </a>
            <a href="#sermons" class="quick-nav-link">
                <i class="fas fa-bible"></i>
                <span>Sermons</span>
            </a>
            <a href="#events" class="quick-nav-link">
                <i class="fas fa-calendar-alt"></i>
                <span>Events</span>
            </a>
            <a href="#live" class="quick-nav-link">
                <i class="fas fa-video"></i>
                <span>Watch Live</span>
            </a>
        </nav>
    </div>
</div>

<style>
.quick-nav-link {
    flex: 1;
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 0.5rem;
    padding: 1.25rem 1.5rem;
    text-decoration: none;
    color: #666;
    font-weight: 600;
    font-size: 0.95rem;
    border-bottom: 3px solid transparent;
    transition: all 0.3s ease;
    min-width: 140px;
    text-align: center;
}

.quick-nav-link i {
    font-size: 1.5rem;
    color: var(--primary-light);
}

.quick-nav-link:hover {
    color: var(--primary-dark);
    background: rgba(122, 156, 198, 0.1);
    border-bottom-color: var(--primary-light);
}

.quick-nav-link.active {
    color: var(--primary-dark);
    border-bottom-color: var(--primary-light);
    background: rgba(122, 156, 198, 0.05);
}

/* Sermon Card Image Styles */
.sermon-card-image {
    width: 100%;
    height: 200px;
    object-fit: cover;
    background-color: #f0f0f0;
    transition: transform 0.3s ease;
}

.card:hover .sermon-card-image {
    transform: scale(1.05);
}

.card .card-image-wrapper {
    overflow: hidden;
    position: relative;
}

.card .card-image-wrapper::after {
    content: '';
    position: absolute;
    bottom: 0;
    left: 0;
    right: 0;
    height: 60px;
    background: linear-gradient(to top, rgba(0,0,0,0.3), transparent);
    pointer-events: none;
}

/* Event Card Styles */
.event-card {
    cursor: pointer;
    transition: all 0.3s ease;
}

.event-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 12px 30px rgba(0,0,0,0.15);
}

.event-card .card-image-wrapper {
    overflow: hidden;
    position: relative;
}

.event-card .event-card-image {
    width: 100%;
    height: 200px;
    object-fit: cover;
    background-color: #f0f0f0;
    transition: transform 0.3s ease;
}

.event-card:hover .event-card-image {
    transform: scale(1.05);
}

.event-card .view-details-hint {
    position: absolute;
    bottom: 10px;
    right: 10px;
    background: rgba(255,255,255,0.9);
    color: var(--primary-dark);
    padding: 0.35rem 0.75rem;
    border-radius: 20px;
    font-size: 0.8rem;
    font-weight: 600;
    opacity: 0;
    transition: opacity 0.3s ease;
    z-index: 2;
}

.event-card:hover .view-details-hint {
    opacity: 1;
}

/* Event Modal Styles */
.event-modal-overlay {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0,0,0,0.7);
    z-index: 9999;
    opacity: 0;
    visibility: hidden;
    transition: opacity 0.3s ease, visibility 0.3s ease;
    overflow-y: auto;
    padding: 2rem 1rem;
    display: flex;
    align-items: flex-start;
    justify-content: center;
}

.event-modal-overlay.active {
    opacity: 1;
    visibility: visible;
}

.event-modal {
    background: white;
    border-radius: 16px;
    max-width: 700px;
    width: 100%;
    max-height: calc(100vh - 4rem);
    overflow: hidden;
    transform: scale(0.9) translateY(20px);
    transition: transform 0.3s ease;
    box-shadow: 0 25px 50px rgba(0,0,0,0.3);
    margin: auto;
}

.event-modal-overlay.active .event-modal {
    transform: scale(1) translateY(0);
}

.event-modal-header {
    position: relative;
}

.event-modal-image {
    width: 100%;
    height: 280px;
    object-fit: cover;
}

.event-modal-close {
    position: absolute;
    top: 15px;
    right: 15px;
    width: 40px;
    height: 40px;
    border-radius: 50%;
    background: rgba(255,255,255,0.95);
    border: none;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.25rem;
    color: #333;
    transition: all 0.3s ease;
    box-shadow: 0 2px 10px rgba(0,0,0,0.2);
}

.event-modal-close:hover {
    background: white;
    transform: scale(1.1);
}

.event-modal-badge {
    position: absolute;
    top: 15px;
    left: 15px;
    padding: 0.5rem 1rem;
    border-radius: 20px;
    font-size: 0.85rem;
    font-weight: 700;
    color: white;
}

.event-modal-badge.today {
    background: var(--warning-color);
}

.event-modal-badge.this-week {
    background: var(--primary-light);
}

.event-modal-body {
    padding: 2rem;
    overflow-y: auto;
    max-height: calc(100vh - 4rem - 280px);
}

.event-modal-title {
    font-size: 1.75rem;
    color: var(--primary-dark);
    margin-bottom: 1.5rem;
    line-height: 1.3;
}

.event-modal-meta {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 1rem;
    margin-bottom: 1.5rem;
    padding: 1.25rem;
    background: #f8f9fa;
    border-radius: 12px;
}

.event-modal-meta-item {
    display: flex;
    align-items: center;
    gap: 0.75rem;
}

.event-modal-meta-item i {
    width: 40px;
    height: 40px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: var(--primary-light);
    color: white;
    border-radius: 10px;
    font-size: 1rem;
}

.event-modal-meta-item .meta-content {
    display: flex;
    flex-direction: column;
}

.event-modal-meta-item .meta-label {
    font-size: 0.75rem;
    color: #888;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.event-modal-meta-item .meta-value {
    font-size: 1rem;
    color: #333;
    font-weight: 600;
}

.event-modal-description {
    color: #555;
    line-height: 1.8;
    margin-bottom: 1.5rem;
    font-size: 1rem;
}

.event-modal-contact {
    padding: 1.25rem;
    background: linear-gradient(135deg, rgba(122, 156, 198, 0.1), rgba(122, 156, 198, 0.05));
    border-radius: 12px;
    margin-bottom: 1.5rem;
}

.event-modal-contact h4 {
    font-size: 1rem;
    color: var(--primary-dark);
    margin-bottom: 0.75rem;
}

.event-modal-contact-info {
    display: flex;
    flex-wrap: wrap;
    gap: 1rem;
}

.event-modal-contact-info a {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    color: var(--primary-light);
    text-decoration: none;
    font-weight: 500;
    transition: color 0.3s ease;
}

.event-modal-contact-info a:hover {
    color: var(--primary-dark);
}

.event-modal-actions {
    display: flex;
    gap: 1rem;
    flex-wrap: wrap;
}

.event-modal-actions .btn {
    flex: 1;
    min-width: 150px;
    text-align: center;
    padding: 0.875rem 1.5rem;
}

@media (max-width: 768px) {
    .quick-nav-link {
        min-width: 100px;
        padding: 1rem;
        font-size: 0.85rem;
    }
    .quick-nav-link i {
        font-size: 1.25rem;
    }
    .quick-nav-link span {
        font-size: 0.8rem;
    }
    .sermon-card-image,
    .event-card-image {
        height: 180px;
    }
    
    .event-modal {
        margin: 0;
        border-radius: 16px 16px 0 0;
        max-height: 90vh;
    }
    
    .event-modal-image {
        height: 200px;
    }
    
    .event-modal-body {
        padding: 1.5rem;
        max-height: calc(90vh - 200px);
    }
    
    .event-modal-title {
        font-size: 1.5rem;
    }
    
    .event-modal-meta {
        grid-template-columns: 1fr;
    }
    
    .event-modal-actions {
        flex-direction: column;
    }
    
    .event-modal-actions .btn {
        width: 100%;
    }
}
</style>

<!-- ============================================
     SECTION 1: SERVICE TIMES
     ============================================ -->
<section id="services" class="section" style="scroll-margin-top: 120px;">
    <div class="container">
        <h2 class="section-title">
            <i class="fas fa-clock"></i> Service Times
        </h2>
        <p class="section-subtitle">Join us in person for worship and fellowship</p>
        
        <div style="background: linear-gradient(135deg, var(--primary-light), var(--accent-blue)); color: white; padding: 3rem 2rem; border-radius: 12px; text-align: center; margin-bottom: 2rem;">
            <h3 style="color: white; margin-bottom: 2rem; font-size: 1.75rem;">Weekly Services</h3>
            
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 2rem; max-width: 900px; margin: 0 auto;">
                <div style="background: rgba(255,255,255,0.15); padding: 2rem; border-radius: 8px; backdrop-filter: blur(10px);">
                    <i class="fas fa-sun" style="font-size: 3rem; margin-bottom: 1rem; opacity: 0.9;"></i>
                    <h4 style="color: white; font-size: 1.25rem; margin-bottom: 0.5rem;">Sunday Morning</h4>
                    <p style="font-size: 1.5rem; font-weight: 700; margin: 0.5rem 0;">9:00 AM</p>
                    <p style="opacity: 0.9; margin: 0;">Traditional Service</p>
                </div>
                
                <div style="background: rgba(255,255,255,0.15); padding: 2rem; border-radius: 8px; backdrop-filter: blur(10px);">
                    <i class="fas fa-church" style="font-size: 3rem; margin-bottom: 1rem; opacity: 0.9;"></i>
                    <h4 style="color: white; font-size: 1.25rem; margin-bottom: 0.5rem;">Sunday Evening</h4>
                    <p style="font-size: 1.5rem; font-weight: 700; margin: 0.5rem 0;">11:30 AM</p>
                    <p style="opacity: 0.9; margin: 0;">Contemporary Service</p>
                </div>
                
                <div style="background: rgba(255,255,255,0.15); padding: 2rem; border-radius: 8px; backdrop-filter: blur(10px);">
                    <i class="fas fa-praying-hands" style="font-size: 3rem; margin-bottom: 1rem; opacity: 0.9;"></i>
                    <h4 style="color: white; font-size: 1.25rem; margin-bottom: 0.5rem;">Wednesday</h4>
                    <p style="font-size: 1.5rem; font-weight: 700; margin: 0.5rem 0;">7:00 PM</p>
                    <p style="opacity: 0.9; margin: 0;">Prayer & Bible Study</p>
                </div>
            </div>
            
            <div style="margin-top: 2rem;">
                <a href="about.php" class="btn btn-outline">
                    <i class="fas fa-info-circle"></i> Plan Your Visit
                </a>
            </div>
        </div>
    </div>
</section>

<!-- ============================================
     SECTION 2: SERMONS
     ============================================ -->
<section id="sermons" class="section" style="background-color: #fff; scroll-margin-top: 120px;">
    <div class="container">
        <h2 class="section-title">
            <i class="fas fa-bible"></i> Recent Sermons
        </h2>
        <p class="section-subtitle">Watch or listen to inspiring messages from our pastors</p>
        
        <?php if (!empty($sermons)): ?>
        <div class="card-grid">
            <?php foreach ($sermons as $sermon): ?>
            <?php
            // Determine the image to display
            // Priority: 1. Uploaded cover image, 2. Default sermon image, 3. Placeholder
            if (!empty($sermon['cover_image']) && file_exists($sermon['cover_image'])) {
                $sermon_image = htmlspecialchars($sermon['cover_image']);
            } elseif (file_exists($default_sermon_image)) {
                $sermon_image = $default_sermon_image;
            } else {
                // Fallback placeholder if no default image exists
                $sermon_image = 'https://images.unsplash.com/photo-1507003211169-0a1dd7228f2d?w=400&h=250&fit=crop';
            }
            ?>
            <div class="card">
                <div class="card-image-wrapper">
                    <img src="<?php echo $sermon_image; ?>" 
                         alt="<?php echo htmlspecialchars($sermon['title']); ?>" 
                         class="card-image sermon-card-image"
                         loading="lazy"
                         onerror="this.src='https://images.unsplash.com/photo-1507003211169-0a1dd7228f2d?w=400&h=250&fit=crop';">
                </div>
                
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
                    <p class="card-text">
                        <?php 
                        $description = htmlspecialchars($sermon['description']);
                        echo strlen($description) > 100 ? substr($description, 0, 100) . '...' : $description;
                        ?>
                    </p>
                    <?php endif; ?>
                    
                    <div style="display: flex; gap: 0.5rem; margin-top: 1rem;">
                        <?php if (!empty($sermon['video_url'])): ?>
                        <a href="<?php echo htmlspecialchars($sermon['video_url']); ?>" 
                           target="_blank" 
                           class="btn btn-primary" 
                           style="flex: 1; font-size: 0.9rem; padding: 0.5rem;">
                            <i class="fas fa-play"></i> Watch
                        </a>
                        <?php endif; ?>
                        
                        <?php if (!empty($sermon['audio_url'])): ?>
                        <a href="<?php echo htmlspecialchars($sermon['audio_url']); ?>" 
                           target="_blank" 
                           class="btn btn-secondary" 
                           style="flex: 1; font-size: 0.9rem; padding: 0.5rem;">
                            <i class="fas fa-headphones"></i> Listen
                        </a>
                        <?php endif; ?>
                        
                        <?php if (empty($sermon['video_url']) && empty($sermon['audio_url'])): ?>
                        <span class="btn btn-secondary" style="flex: 1; font-size: 0.9rem; padding: 0.5rem; opacity: 0.6; cursor: default;">
                            <i class="fas fa-clock"></i> Coming Soon
                        </span>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        
        <div style="text-align: center; margin-top: 2rem;">
            <p style="color: #666; margin-bottom: 1rem;">Never miss a message!</p>
            <div style="display: flex; gap: 1rem; justify-content: center; flex-wrap: wrap;">
                <a href="#" class="btn btn-secondary" style="background: #FF0000;">
                    <i class="fab fa-youtube"></i> YouTube
                </a>
                <a href="#" class="btn btn-secondary" style="background: #1DB954;">
                    <i class="fab fa-spotify"></i> Spotify
                </a>
                <a href="#" class="btn btn-secondary" style="background: #9933CC;">
                    <i class="fas fa-podcast"></i> Podcast
                </a>
            </div>
        </div>
        
        <?php else: ?>
        <div style="text-align: center; padding: 3rem;">
            <i class="fas fa-bible" style="font-size: 4rem; color: #ccc; margin-bottom: 1rem;"></i>
            <p style="color: #666; font-size: 1.1rem;">No sermons available yet. Check back soon!</p>
        </div>
        <?php endif; ?>
    </div>
</section>

<!-- ============================================
     SECTION 3: UPCOMING EVENTS
     ============================================ -->
<section id="events" class="section" style="scroll-margin-top: 120px;">
    <div class="container">
        <h2 class="section-title">
            <i class="fas fa-calendar-alt"></i> Upcoming Events
        </h2>
        <p class="section-subtitle">Join us for these exciting opportunities to connect and grow</p>
        
        <?php if (!empty($upcoming_events)): ?>
        <div class="card-grid">
            <?php foreach ($upcoming_events as $index => $event): ?>
            <?php
            $event_date = strtotime($event['event_date']);
            $is_today = date('Y-m-d', $event_date) == date('Y-m-d');
            $is_this_week = $event_date <= strtotime('+7 days') && $event_date > strtotime('today');
            
            // Determine the image to display
            // Check for new 'event_image' field first, then fall back to old 'image_url'
            if (!empty($event['event_image']) && file_exists($event['event_image'])) {
                $event_image = htmlspecialchars($event['event_image']);
            } elseif (!empty($event['image_url'])) {
                $event_image = htmlspecialchars($event['image_url']);
            } elseif (file_exists($default_event_image)) {
                $event_image = $default_event_image;
            } else {
                $event_image = 'https://images.unsplash.com/photo-1511632765486-a01980e01a18?w=400';
            }
            
            // Format time display
            $start_time = date('g:i A', strtotime($event['event_time']));
            $end_time = !empty($event['end_time']) ? date('g:i A', strtotime($event['end_time'])) : '';
            $time_display = $end_time ? $start_time . ' - ' . $end_time : $start_time;
            ?>
            <div class="card event-card" 
                 onclick="openEventModal(<?php echo $index; ?>)"
                 data-event-id="<?php echo $event['id']; ?>"
                 style="<?php echo $is_today ? 'border: 3px solid var(--warning-color);' : ''; ?>">
                
                <?php if ($is_today): ?>
                <div style="background: var(--warning-color); color: white; padding: 0.5rem; text-align: center; font-weight: 700;">
                    <i class="fas fa-star"></i> TODAY
                </div>
                <?php elseif ($is_this_week): ?>
                <div style="background: var(--primary-light); color: white; padding: 0.5rem; text-align: center; font-weight: 600;">
                    <i class="fas fa-clock"></i> This Week
                </div>
                <?php endif; ?>
                
                <div class="card-image-wrapper">
                    <img src="<?php echo $event_image; ?>" 
                         alt="<?php echo htmlspecialchars($event['title']); ?>" 
                         class="card-image event-card-image"
                         loading="lazy"
                         onerror="this.src='https://images.unsplash.com/photo-1511632765486-a01980e01a18?w=400';">
                    <span class="view-details-hint">
                        <i class="fas fa-expand"></i> View Details
                    </span>
                </div>
                
                <div class="card-content">
                    <h3 class="card-title"><?php echo htmlspecialchars($event['title']); ?></h3>
                    
                    <p class="card-meta">
                        <i class="fas fa-calendar"></i> <?php echo format_date($event['event_date']); ?>
                        <br>
                        <i class="fas fa-clock"></i> <?php echo $time_display; ?>
                        <br>
                        <i class="fas fa-map-marker-alt"></i> <?php echo htmlspecialchars($event['location']); ?>
                    </p>
                    
                    <?php if (!empty($event['description'])): ?>
                    <p class="card-text">
                        <?php 
                        $description = htmlspecialchars($event['description']);
                        echo strlen($description) > 100 ? substr($description, 0, 100) . '...' : $description;
                        ?>
                    </p>
                    <?php endif; ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        
        <?php else: ?>
        <div style="text-align: center; padding: 3rem; background: white; border-radius: 8px;">
            <i class="fas fa-calendar-alt" style="font-size: 4rem; color: #ccc; margin-bottom: 1rem;"></i>
            <p style="color: #666; font-size: 1.1rem;">No upcoming events at the moment.</p>
            <p style="color: #999;">Check back soon for new events!</p>
        </div>
        <?php endif; ?>
    </div>
</section>

<!-- ============================================
     EVENT MODAL
     ============================================ -->
<div class="event-modal-overlay" id="eventModalOverlay" onclick="closeEventModal(event)">
    <div class="event-modal" onclick="event.stopPropagation()">
        <div class="event-modal-header">
            <img src="" alt="" class="event-modal-image" id="modalEventImage">
            <button class="event-modal-close" onclick="closeEventModal(event)">
                <i class="fas fa-times"></i>
            </button>
            <span class="event-modal-badge" id="modalEventBadge" style="display: none;"></span>
        </div>
        <div class="event-modal-body">
            <h2 class="event-modal-title" id="modalEventTitle"></h2>
            
            <div class="event-modal-meta">
                <div class="event-modal-meta-item">
                    <i class="fas fa-calendar-alt"></i>
                    <div class="meta-content">
                        <span class="meta-label">Date</span>
                        <span class="meta-value" id="modalEventDate"></span>
                    </div>
                </div>
                <div class="event-modal-meta-item">
                    <i class="fas fa-clock"></i>
                    <div class="meta-content">
                        <span class="meta-label">Time</span>
                        <span class="meta-value" id="modalEventTime"></span>
                    </div>
                </div>
                <div class="event-modal-meta-item">
                    <i class="fas fa-map-marker-alt"></i>
                    <div class="meta-content">
                        <span class="meta-label">Location</span>
                        <span class="meta-value" id="modalEventLocation"></span>
                    </div>
                </div>
            </div>
            
            <div class="event-modal-description" id="modalEventDescription"></div>
            
            <div class="event-modal-contact" id="modalEventContact" style="display: none;">
                <h4><i class="fas fa-address-book"></i> Contact Information</h4>
                <div class="event-modal-contact-info" id="modalEventContactInfo"></div>
            </div>
            
            <div class="event-modal-actions" id="modalEventActions">
                <!-- Dynamic buttons will be added here -->
            </div>
        </div>
    </div>
</div>

<!-- ============================================
     SECTION 4: WATCH LIVE
     ============================================ -->
<section id="live" class="section" style="background-color: #fff; scroll-margin-top: 120px;">
    <div class="container">
        <h2 class="section-title">
            <i class="fas fa-video"></i> Watch Live Online
        </h2>
        <p class="section-subtitle">Can't make it in person? Join us online!</p>
        
        <div style="background: linear-gradient(135deg, var(--primary-dark), var(--accent-blue)); padding: 3rem 2rem; border-radius: 12px; text-align: center; color: white;">
            <i class="fas fa-play-circle" style="font-size: 5rem; margin-bottom: 1.5rem; opacity: 0.9;"></i>
            <h3 style="color: white; font-size: 2rem; margin-bottom: 1rem;">Join Us Live</h3>
            <p style="font-size: 1.2rem; margin-bottom: 2rem; opacity: 0.9;">
                Services stream live every Sunday at 9:00 AM and 11:30 AM
            </p>
            <a href="live.php" class="btn btn-outline" style="font-size: 1.1rem; padding: 1rem 2.5rem;">
                <i class="fas fa-tv"></i> Go to Live Stream
            </a>
        </div>
    </div>
</section>

<!-- Call to Action -->
<section class="section" style="background: linear-gradient(135deg, var(--primary-dark), var(--accent-blue)); color: white; text-align: center;">
    <div class="container">
        <h2 style="color: white; font-size: 2.25rem; margin-bottom: 1rem;">Ready to Get Involved?</h2>
        <p style="font-size: 1.25rem; margin-bottom: 2rem; opacity: 0.95;">
            Whether in person or online, we'd love to have you join us!
        </p>
        <div class="hero-buttons" style="justify-content: center;">
            <a href="about.php" class="btn btn-outline">
                <i class="fas fa-info-circle"></i> Plan Your Visit
            </a>
            <a href="contact.php" class="btn btn-primary" style="background: white; color: var(--primary-dark);">
                <i class="fas fa-envelope"></i> Contact Us
            </a>
            <a href="donate.php" class="btn btn-outline">
                <i class="fas fa-heart"></i> Give Online
            </a>
        </div>
    </div>
</section>

<script>
// Event data for modal
const eventsData = <?php 
$events_for_js = array_map(function($event) use ($default_event_image) {
    // Determine image
    if (!empty($event['event_image']) && file_exists($event['event_image'])) {
        $image = $event['event_image'];
    } elseif (!empty($event['image_url'])) {
        $image = $event['image_url'];
    } elseif (file_exists($default_event_image)) {
        $image = $default_event_image;
    } else {
        $image = 'https://images.unsplash.com/photo-1511632765486-a01980e01a18?w=400';
    }
    
    // Format times
    $start_time = date('g:i A', strtotime($event['event_time']));
    $end_time = !empty($event['end_time']) ? date('g:i A', strtotime($event['end_time'])) : '';
    
    return [
        'id' => $event['id'],
        'title' => $event['title'],
        'date' => date('l, F j, Y', strtotime($event['event_date'])),
        'date_raw' => $event['event_date'],
        'time' => $end_time ? $start_time . ' - ' . $end_time : $start_time,
        'location' => $event['location'] ?? '',
        'description' => $event['description'] ?? '',
        'image' => $image,
        'registration_url' => $event['registration_url'] ?? '',
        'contact_email' => $event['contact_email'] ?? '',
        'contact_phone' => $event['contact_phone'] ?? '',
        'is_today' => date('Y-m-d', strtotime($event['event_date'])) == date('Y-m-d'),
        'is_this_week' => strtotime($event['event_date']) <= strtotime('+7 days') && strtotime($event['event_date']) > strtotime('today')
    ];
}, $upcoming_events);
echo json_encode($events_for_js, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP);
?>;

function openEventModal(index) {
    const event = eventsData[index];
    if (!event) {
        console.error('Event not found at index:', index);
        return;
    }
    
    const overlay = document.getElementById('eventModalOverlay');
    
    // Populate modal content
    document.getElementById('modalEventImage').src = event.image;
    document.getElementById('modalEventImage').alt = event.title;
    document.getElementById('modalEventTitle').textContent = event.title;
    document.getElementById('modalEventDate').textContent = event.date;
    document.getElementById('modalEventTime').textContent = event.time;
    document.getElementById('modalEventLocation').textContent = event.location;
    
    // Description with line breaks
    const descriptionEl = document.getElementById('modalEventDescription');
    if (event.description) {
        descriptionEl.innerHTML = event.description.replace(/\n/g, '<br>');
        descriptionEl.style.display = 'block';
    } else {
        descriptionEl.style.display = 'none';
    }
    
    // Badge
    const badge = document.getElementById('modalEventBadge');
    if (event.is_today) {
        badge.textContent = '★ TODAY';
        badge.className = 'event-modal-badge today';
        badge.style.display = 'block';
    } else if (event.is_this_week) {
        badge.textContent = '⏰ This Week';
        badge.className = 'event-modal-badge this-week';
        badge.style.display = 'block';
    } else {
        badge.style.display = 'none';
    }
    
    // Contact information
    const contactSection = document.getElementById('modalEventContact');
    const contactInfo = document.getElementById('modalEventContactInfo');
    if (event.contact_email || event.contact_phone) {
        contactInfo.innerHTML = '';
        if (event.contact_email) {
            contactInfo.innerHTML += '<a href="mailto:' + event.contact_email + '"><i class="fas fa-envelope"></i> ' + event.contact_email + '</a>';
        }
        if (event.contact_phone) {
            contactInfo.innerHTML += '<a href="tel:' + event.contact_phone + '"><i class="fas fa-phone"></i> ' + event.contact_phone + '</a>';
        }
        contactSection.style.display = 'block';
    } else {
        contactSection.style.display = 'none';
    }
    
    // Action buttons
    const actionsEl = document.getElementById('modalEventActions');
    actionsEl.innerHTML = '';
    
    if (event.registration_url) {
        actionsEl.innerHTML += '<a href="' + event.registration_url + '" target="_blank" class="btn btn-primary"><i class="fas fa-user-plus"></i> Register Now</a>';
    }
    
    // Add to calendar button (Google Calendar)
    const calendarUrl = generateGoogleCalendarUrl(event);
    actionsEl.innerHTML += '<a href="' + calendarUrl + '" target="_blank" class="btn btn-secondary"><i class="fas fa-calendar-plus"></i> Add to Calendar</a>';
    
    // Show modal
    overlay.classList.add('active');
    document.body.style.overflow = 'hidden';
}

function closeEventModal(e) {
    if (e) {
        e.stopPropagation();
    }
    const overlay = document.getElementById('eventModalOverlay');
    overlay.classList.remove('active');
    document.body.style.overflow = '';
}

function generateGoogleCalendarUrl(event) {
    const startDate = new Date(event.date_raw + 'T' + (event.time.split(' - ')[0] || '09:00'));
    const endDate = new Date(startDate.getTime() + 2 * 60 * 60 * 1000); // Default 2 hours
    
    const formatDate = (date) => {
        return date.toISOString().replace(/-|:|\.\d+/g, '').slice(0, 15) + 'Z';
    };
    
    const params = new URLSearchParams({
        action: 'TEMPLATE',
        text: event.title,
        dates: `${formatDate(startDate)}/${formatDate(endDate)}`,
        details: event.description || '',
        location: event.location,
        sf: 'true'
    });
    
    return `https://calendar.google.com/calendar/render?${params.toString()}`;
}

// Close modal on Escape key
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        closeEventModal();
    }
});

// Smooth scroll and active link highlighting
document.addEventListener('DOMContentLoaded', function() {
    const navLinks = document.querySelectorAll('.quick-nav-link');
    const sections = document.querySelectorAll('section[id]');
    
    // Smooth scroll
    navLinks.forEach(link => {
        link.addEventListener('click', function(e) {
            e.preventDefault();
            const targetId = this.getAttribute('href').substring(1);
            const targetSection = document.getElementById(targetId);
            
            if (targetSection) {
                const offset = 120;
                const elementPosition = targetSection.getBoundingClientRect().top;
                const offsetPosition = elementPosition + window.pageYOffset - offset;
                
                window.scrollTo({
                    top: offsetPosition,
                    behavior: 'smooth'
                });
            }
        });
    });
    
    // Highlight active section on scroll
    function highlightActiveSection() {
        let currentSection = '';
        
        sections.forEach(section => {
            const sectionTop = section.offsetTop - 150;
            const sectionHeight = section.clientHeight;
            
            if (window.pageYOffset >= sectionTop && window.pageYOffset < sectionTop + sectionHeight) {
                currentSection = section.getAttribute('id');
            }
        });
        
        navLinks.forEach(link => {
            link.classList.remove('active');
            if (link.getAttribute('href') === `#${currentSection}`) {
                link.classList.add('active');
            }
        });
    }
    
    window.addEventListener('scroll', highlightActiveSection);
    highlightActiveSection();
});
</script>

<?php
// Include footer
include 'includes/footer.php';
?>