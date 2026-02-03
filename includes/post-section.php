<?php
/**
 * Posts Section Component
 * 
 * Include this file on any page to display blog posts
 * 
 * Usage:
 *   $posts_page = 'home';  // or 'about', 'services', etc.
 *   $posts_limit = 6;      // optional, defaults to 6
 *   $posts_title = 'Latest News';  // optional
 *   include 'includes/posts-section.php';
 */

// Default settings
$posts_page = $posts_page ?? 'home';
$posts_limit = $posts_limit ?? 6;
$posts_title = $posts_title ?? 'Latest News & Updates';
$posts_subtitle = $posts_subtitle ?? 'Stay connected with what\'s happening at our church';
$show_view_all = $show_view_all ?? true;

// Fetch posts for this page
try {
    if ($posts_page === 'home') {
        // Homepage shows all posts with show_on_homepage = 1
        $sql = "SELECT * FROM posts 
                WHERE status = 'published' AND show_on_homepage = 1 
                ORDER BY published_at DESC 
                LIMIT ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$posts_limit]);
    } else {
        // Other pages show posts targeted to them
        $sql = "SELECT * FROM posts 
                WHERE status = 'published' 
                AND (JSON_CONTAINS(target_pages, ?) OR show_on_homepage = 1)
                ORDER BY published_at DESC 
                LIMIT ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([json_encode($posts_page), $posts_limit]);
    }
    $posts = $stmt->fetchAll();
} catch (PDOException $e) {
    $posts = [];
}

// Only display section if there are posts
if (!empty($posts)):
?>

<!-- Posts Section -->
<section id="posts" class="section posts-section" style="scroll-margin-top: 80px;">
    <div class="container">
        <h2 class="section-title">
            <i class="fas fa-newspaper"></i> <?php echo htmlspecialchars($posts_title); ?>
        </h2>
        <p class="section-subtitle"><?php echo htmlspecialchars($posts_subtitle); ?></p>
        
        <div class="posts-grid">
            <?php foreach ($posts as $post): ?>
            <?php
            // Determine featured image
            if (!empty($post['featured_image']) && file_exists($post['featured_image'])) {
                $post_image = $post['featured_image'];
            } else {
                $post_image = 'https://images.unsplash.com/photo-1504052434569-70ad5836ab65?w=400&h=250&fit=crop';
            }
            
            // Format date
            $post_date = date('F j, Y', strtotime($post['published_at']));
            $time_ago = time_ago($post['published_at']);
            ?>
            <article class="post-card" id="post-<?php echo $post['id']; ?>">
                <div class="post-card-image">
                    <img src="<?php echo htmlspecialchars($post_image); ?>" 
                         alt="<?php echo htmlspecialchars($post['title']); ?>"
                         loading="lazy"
                         onerror="this.src='https://images.unsplash.com/photo-1504052434569-70ad5836ab65?w=400&h=250&fit=crop';">
                </div>
                
                <div class="post-card-content">
                    <div class="post-card-meta">
                        <span class="post-date">
                            <i class="fas fa-calendar-alt"></i> <?php echo $post_date; ?>
                        </span>
                    </div>
                    
                    <h3 class="post-card-title">
                        <a href="post.php?slug=<?php echo htmlspecialchars($post['slug']); ?>">
                            <?php echo htmlspecialchars($post['title']); ?>
                        </a>
                    </h3>
                    
                    <?php if (!empty($post['excerpt'])): ?>
                    <p class="post-card-excerpt">
                        <?php echo htmlspecialchars(substr($post['excerpt'], 0, 150)); ?>...
                    </p>
                    <?php endif; ?>
                    
                    <a href="post.php?slug=<?php echo htmlspecialchars($post['slug']); ?>" class="post-card-link">
                        Read More <i class="fas fa-arrow-right"></i>
                    </a>
                </div>
            </article>
            <?php endforeach; ?>
        </div>
        
        <?php if ($show_view_all): ?>
        <div style="text-align: center; margin-top: 2rem;">
            <a href="news.php" class="btn btn-secondary">
                <i class="fas fa-th-list"></i> View All Posts
            </a>
        </div>
        <?php endif; ?>
    </div>
</section>

<style>
.posts-section {
    background: #f8f9fa;
}

.posts-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
    gap: 2rem;
}

.post-card {
    background: white;
    border-radius: 12px;
    overflow: hidden;
    box-shadow: 0 4px 15px rgba(0,0,0,0.08);
    transition: all 0.3s ease;
    display: flex;
    flex-direction: column;
}

.post-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 12px 30px rgba(0,0,0,0.12);
}

.post-card-image {
    position: relative;
    width: 100%;
    height: 200px;
    overflow: hidden;
}

.post-card-image img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    transition: transform 0.3s ease;
}

.post-card:hover .post-card-image img {
    transform: scale(1.05);
}

.post-card-content {
    padding: 1.5rem;
    display: flex;
    flex-direction: column;
    flex-grow: 1;
}

.post-card-meta {
    display: flex;
    align-items: center;
    gap: 1rem;
    margin-bottom: 0.75rem;
    font-size: 0.85rem;
    color: #888;
}

.post-card-meta i {
    color: var(--primary-light);
}

.post-card-title {
    font-size: 1.25rem;
    line-height: 1.4;
    margin-bottom: 0.75rem;
    color: var(--primary-dark);
}

.post-card-title a {
    color: inherit;
    text-decoration: none;
    transition: color 0.3s ease;
}

.post-card-title a:hover {
    color: var(--primary-light);
}

.post-card-excerpt {
    color: #666;
    font-size: 0.95rem;
    line-height: 1.6;
    margin-bottom: 1rem;
    flex-grow: 1;
}

.post-card-link {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    color: var(--primary-light);
    text-decoration: none;
    font-weight: 600;
    font-size: 0.95rem;
    transition: all 0.3s ease;
    margin-top: auto;
}

.post-card-link:hover {
    color: var(--primary-dark);
    gap: 0.75rem;
}

.post-card-link i {
    transition: transform 0.3s ease;
}

.post-card-link:hover i {
    transform: translateX(3px);
}

@media (max-width: 768px) {
    .posts-grid {
        grid-template-columns: 1fr;
    }
    
    .post-card-image {
        height: 180px;
    }
}
</style>

<?php
endif; // End if (!empty($posts))

// Helper function for time ago (if not already defined)
if (!function_exists('time_ago')) {
    function time_ago($datetime) {
        $time = strtotime($datetime);
        $diff = time() - $time;
        
        if ($diff < 60) {
            return 'just now';
        } elseif ($diff < 3600) {
            $mins = floor($diff / 60);
            return $mins . ' minute' . ($mins > 1 ? 's' : '') . ' ago';
        } elseif ($diff < 86400) {
            $hours = floor($diff / 3600);
            return $hours . ' hour' . ($hours > 1 ? 's' : '') . ' ago';
        } elseif ($diff < 604800) {
            $days = floor($diff / 86400);
            return $days . ' day' . ($days > 1 ? 's' : '') . ' ago';
        } elseif ($diff < 2592000) {
            $weeks = floor($diff / 604800);
            return $weeks . ' week' . ($weeks > 1 ? 's' : '') . ' ago';
        } else {
            return date('M j, Y', $time);
        }
    }
}
?>