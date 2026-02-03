<?php
/**
 * News/Blog Archive Page
 * 
 * Lists all published blog posts with pagination
 */

// Include database connection
require_once 'includes/db.php';

// Set page title
$page_title = 'News & Updates';

// Pagination
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$per_page = 9;
$offset = ($page - 1) * $per_page;

// Get total count
$stmt = $pdo->query("SELECT COUNT(*) FROM posts WHERE status = 'published'");
$total_posts = $stmt->fetchColumn();
$total_pages = ceil($total_posts / $per_page);

// Fetch posts
$stmt = $pdo->prepare("SELECT * FROM posts WHERE status = 'published' ORDER BY published_at DESC LIMIT ? OFFSET ?");
$stmt->execute([$per_page, $offset]);
$posts = $stmt->fetchAll();

// Include header
include 'includes/header.php';
?>

<!-- Page Hero -->
<section class="hero" style="min-height: 300px; padding: 4rem 1rem;">
    <div class="hero-content">
        <h1>News & Updates</h1>
        <p>Stay connected with what's happening at our church</p>
    </div>
</section>

<!-- Posts Grid -->
<section class="section" style="background: #f8f9fa;">
    <div class="container">
        <?php if (!empty($posts)): ?>
        
        <?php 
        // Check if first post should be featured
        $first_post = $posts[0];
        $remaining_posts = array_slice($posts, 1);
        
        // Featured post image
        if (!empty($first_post['featured_image']) && file_exists($first_post['featured_image'])) {
            $featured_image = $first_post['featured_image'];
        } else {
            $featured_image = 'https://images.unsplash.com/photo-1504052434569-70ad5836ab65?w=800&h=400&fit=crop';
        }
        ?>
        
        <?php if ($page == 1): ?>
        <!-- Featured Post -->
        <article class="featured-post">
            <div class="featured-post-image">
                <img src="<?php echo htmlspecialchars($featured_image); ?>" 
                     alt="<?php echo htmlspecialchars($first_post['title']); ?>"
                     onerror="this.src='https://images.unsplash.com/photo-1504052434569-70ad5836ab65?w=800&h=400&fit=crop';">
            </div>
            <div class="featured-post-content">
                <span class="featured-badge">
                    <i class="fas fa-star"></i> Latest Post
                </span>
                <h2>
                    <a href="post.php?slug=<?php echo htmlspecialchars($first_post['slug']); ?>">
                        <?php echo htmlspecialchars($first_post['title']); ?>
                    </a>
                </h2>
                <div class="featured-meta">
                    <span><i class="fas fa-calendar-alt"></i> <?php echo date('F j, Y', strtotime($first_post['published_at'])); ?></span>
                </div>
                <?php if (!empty($first_post['excerpt'])): ?>
                <p><?php echo htmlspecialchars($first_post['excerpt']); ?></p>
                <?php endif; ?>
                <a href="post.php?slug=<?php echo htmlspecialchars($first_post['slug']); ?>" class="btn btn-primary">
                    Read Full Article <i class="fas fa-arrow-right"></i>
                </a>
            </div>
        </article>
        <?php else: 
            // On other pages, show first post in grid
            $remaining_posts = $posts;
        endif; ?>
        
        <!-- Posts Grid -->
        <?php if (!empty($remaining_posts)): ?>
        <div class="news-grid">
            <?php foreach ($remaining_posts as $post): ?>
            <?php
            if (!empty($post['featured_image']) && file_exists($post['featured_image'])) {
                $post_image = $post['featured_image'];
            } else {
                $post_image = 'https://images.unsplash.com/photo-1504052434569-70ad5836ab65?w=400&h=250&fit=crop';
            }
            ?>
            <article class="news-card">
                <div class="news-card-image">
                    <a href="post.php?slug=<?php echo htmlspecialchars($post['slug']); ?>">
                        <img src="<?php echo htmlspecialchars($post_image); ?>" 
                             alt="<?php echo htmlspecialchars($post['title']); ?>"
                             loading="lazy"
                             onerror="this.src='https://images.unsplash.com/photo-1504052434569-70ad5836ab65?w=400&h=250&fit=crop';">
                    </a>
                </div>
                <div class="news-card-content">
                    <div class="news-card-meta">
                        <span><i class="fas fa-calendar-alt"></i> <?php echo date('M j, Y', strtotime($post['published_at'])); ?></span>
                    </div>
                    <h3>
                        <a href="post.php?slug=<?php echo htmlspecialchars($post['slug']); ?>">
                            <?php echo htmlspecialchars($post['title']); ?>
                        </a>
                    </h3>
                    <?php if (!empty($post['excerpt'])): ?>
                    <p><?php echo htmlspecialchars(substr($post['excerpt'], 0, 120)); ?>...</p>
                    <?php endif; ?>
                    <a href="post.php?slug=<?php echo htmlspecialchars($post['slug']); ?>" class="news-card-link">
                        Read More <i class="fas fa-arrow-right"></i>
                    </a>
                </div>
            </article>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
        
        <!-- Pagination -->
        <?php if ($total_pages > 1): ?>
        <div class="pagination">
            <?php if ($page > 1): ?>
            <a href="?page=<?php echo $page - 1; ?>" class="pagination-btn">
                <i class="fas fa-chevron-left"></i> Previous
            </a>
            <?php endif; ?>
            
            <div class="pagination-numbers">
                <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                    <?php if ($i == 1 || $i == $total_pages || ($i >= $page - 2 && $i <= $page + 2)): ?>
                        <a href="?page=<?php echo $i; ?>" 
                           class="pagination-number <?php echo $i == $page ? 'active' : ''; ?>">
                            <?php echo $i; ?>
                        </a>
                    <?php elseif ($i == $page - 3 || $i == $page + 3): ?>
                        <span class="pagination-dots">...</span>
                    <?php endif; ?>
                <?php endfor; ?>
            </div>
            
            <?php if ($page < $total_pages): ?>
            <a href="?page=<?php echo $page + 1; ?>" class="pagination-btn">
                Next <i class="fas fa-chevron-right"></i>
            </a>
            <?php endif; ?>
        </div>
        <?php endif; ?>
        
        <?php else: ?>
        <!-- No Posts -->
        <div style="text-align: center; padding: 4rem 2rem; background: white; border-radius: 12px;">
            <i class="fas fa-newspaper" style="font-size: 4rem; color: #ccc; margin-bottom: 1rem;"></i>
            <h2 style="color: #666; margin-bottom: 0.5rem;">No Posts Yet</h2>
            <p style="color: #888;">Check back soon for news and updates!</p>
        </div>
        <?php endif; ?>
    </div>
</section>

<style>
/* Featured Post */
.featured-post {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 2rem;
    background: white;
    border-radius: 16px;
    overflow: hidden;
    box-shadow: 0 8px 30px rgba(0,0,0,0.1);
    margin-bottom: 3rem;
}

.featured-post-image {
    height: 100%;
    min-height: 350px;
}

.featured-post-image img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.featured-post-content {
    padding: 2.5rem;
    display: flex;
    flex-direction: column;
    justify-content: center;
}

.featured-badge {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    background: var(--primary-light);
    color: white;
    padding: 0.5rem 1rem;
    border-radius: 20px;
    font-size: 0.85rem;
    font-weight: 600;
    width: fit-content;
    margin-bottom: 1rem;
}

.featured-post-content h2 {
    font-size: 1.75rem;
    line-height: 1.3;
    margin-bottom: 1rem;
}

.featured-post-content h2 a {
    color: var(--primary-dark);
    text-decoration: none;
    transition: color 0.3s ease;
}

.featured-post-content h2 a:hover {
    color: var(--primary-light);
}

.featured-meta {
    display: flex;
    gap: 1.5rem;
    color: #888;
    font-size: 0.9rem;
    margin-bottom: 1rem;
}

.featured-meta i {
    color: var(--primary-light);
}

.featured-post-content p {
    color: #666;
    line-height: 1.7;
    margin-bottom: 1.5rem;
}

/* News Grid */
.news-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
    gap: 2rem;
}

.news-card {
    background: white;
    border-radius: 12px;
    overflow: hidden;
    box-shadow: 0 4px 15px rgba(0,0,0,0.08);
    transition: all 0.3s ease;
}

.news-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 12px 30px rgba(0,0,0,0.12);
}

.news-card-image {
    height: 200px;
    overflow: hidden;
}

.news-card-image img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    transition: transform 0.3s ease;
}

.news-card:hover .news-card-image img {
    transform: scale(1.05);
}

.news-card-content {
    padding: 1.5rem;
}

.news-card-meta {
    font-size: 0.85rem;
    color: #888;
    margin-bottom: 0.75rem;
}

.news-card-meta i {
    color: var(--primary-light);
    margin-right: 0.35rem;
}

.news-card-content h3 {
    font-size: 1.15rem;
    line-height: 1.4;
    margin-bottom: 0.75rem;
}

.news-card-content h3 a {
    color: var(--primary-dark);
    text-decoration: none;
    transition: color 0.3s ease;
}

.news-card-content h3 a:hover {
    color: var(--primary-light);
}

.news-card-content p {
    color: #666;
    font-size: 0.95rem;
    line-height: 1.6;
    margin-bottom: 1rem;
}

.news-card-link {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    color: var(--primary-light);
    text-decoration: none;
    font-weight: 600;
    font-size: 0.9rem;
    transition: all 0.3s ease;
}

.news-card-link:hover {
    color: var(--primary-dark);
    gap: 0.75rem;
}

/* Pagination */
.pagination {
    display: flex;
    justify-content: center;
    align-items: center;
    gap: 1rem;
    margin-top: 3rem;
    padding-top: 2rem;
    border-top: 1px solid #e0e5eb;
}

.pagination-btn {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.75rem 1.25rem;
    background: white;
    color: var(--primary-dark);
    text-decoration: none;
    border-radius: 8px;
    font-weight: 600;
    transition: all 0.3s ease;
    box-shadow: 0 2px 8px rgba(0,0,0,0.08);
}

.pagination-btn:hover {
    background: var(--primary-light);
    color: white;
}

.pagination-numbers {
    display: flex;
    gap: 0.5rem;
}

.pagination-number {
    width: 40px;
    height: 40px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: white;
    color: var(--primary-dark);
    text-decoration: none;
    border-radius: 8px;
    font-weight: 600;
    transition: all 0.3s ease;
    box-shadow: 0 2px 8px rgba(0,0,0,0.08);
}

.pagination-number:hover,
.pagination-number.active {
    background: var(--primary-light);
    color: white;
}

.pagination-dots {
    width: 40px;
    height: 40px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #888;
}

@media (max-width: 992px) {
    .featured-post {
        grid-template-columns: 1fr;
    }
    
    .featured-post-image {
        min-height: 250px;
    }
}

@media (max-width: 768px) {
    .news-grid {
        grid-template-columns: 1fr;
    }
    
    .pagination {
        flex-direction: column;
    }
    
    .pagination-numbers {
        order: -1;
    }
}
</style>

<?php
// Include footer
include 'includes/footer.php';
?> 