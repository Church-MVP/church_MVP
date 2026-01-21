<?php
/**
 * Admin Dashboard
 * 
 * Main admin panel showing statistics and overview
 */

// Set page title
$page_title = 'Dashboard';

// Include admin header (must start session & provide $pdo)
include 'includes/admin-header.php';

/* ===============================
   FETCH DASHBOARD STATISTICS
   =============================== */

// Total Sermons
$stmt = $pdo->query("SELECT COUNT(*) FROM sermons");
$total_sermons = (int) $stmt->fetchColumn();

// Total Events
$stmt = $pdo->query("SELECT COUNT(*) FROM events");
$total_events = (int) $stmt->fetchColumn();

// Upcoming Events
$stmt = $pdo->query("SELECT COUNT(*) FROM events WHERE event_date >= CURDATE()");
$upcoming_events = (int) $stmt->fetchColumn();

// Active Announcements
$stmt = $pdo->query("SELECT COUNT(*) FROM announcements WHERE is_active = 1");
$active_announcements = (int) $stmt->fetchColumn();

// Total Donations (FIXED)
$stmt = $pdo->query("
    SELECT 
        COUNT(*) AS total_donations,
        COALESCE(SUM(amount), 0) AS total_amount
    FROM donations
");

$donation_data = $stmt->fetch(PDO::FETCH_ASSOC);

$total_donations = (int) ($donation_data['total_donations'] ?? 0);
$total_donation_amount = (float) ($donation_data['total_amount'] ?? 0);

// Posts/Content Statistics
$total_posts = 0;
$published_posts = 0;
$draft_posts = 0;

try {
    $stmt = $pdo->query("SELECT COUNT(*) FROM posts");
    $total_posts = (int) $stmt->fetchColumn();
    
    $stmt = $pdo->query("SELECT COUNT(*) FROM posts WHERE status = 'published'");
    $published_posts = (int) $stmt->fetchColumn();
    
    $stmt = $pdo->query("SELECT COUNT(*) FROM posts WHERE status = 'draft'");
    $draft_posts = (int) $stmt->fetchColumn();
} catch (PDOException $e) {
    // Posts table may not exist yet
    $total_posts = 0;
    $published_posts = 0;
    $draft_posts = 0;
}

// Recent Sermons
$stmt = $pdo->query("SELECT * FROM sermons ORDER BY sermon_date DESC LIMIT 5");
$recent_sermons = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Upcoming Events List
$stmt = $pdo->query("
    SELECT * 
    FROM events 
    WHERE event_date >= CURDATE() 
    ORDER BY event_date ASC 
    LIMIT 5
");
$upcoming_events_list = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Recent Donations
$stmt = $pdo->query("SELECT * FROM donations ORDER BY donation_date DESC LIMIT 5");
$recent_donations = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Recent Posts
$recent_posts = [];
try {
    $stmt = $pdo->query("SELECT p.*, u.username as author_name FROM posts p LEFT JOIN users u ON p.author_id = u.id ORDER BY p.created_at DESC LIMIT 5");
    $recent_posts = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $recent_posts = [];
}

// Check if user can access content management
$content_roles = ['admin', 'super_admin', 'viewer', 'content_creator'];
$can_view_content = in_array($_SESSION['user_role'] ?? '', $content_roles);
?>

<!-- Page Header -->
<div class="page-header">
    <h2><i class="fas fa-tachometer-alt"></i> Dashboard Overview</h2>
    <p>Welcome to your admin panel. Here's what's happening with your church website.</p>
</div>

<!-- Dashboard Statistics Cards -->
<div class="dashboard-cards">

    <!-- Total Sermons -->
    <div class="stat-card">
        <div class="stat-card-header">
            <h3>Total Sermons</h3>
            <div class="stat-icon blue"><i class="fas fa-bible"></i></div>
        </div>
        <div class="stat-number"><?php echo number_format($total_sermons); ?></div>
        <div class="stat-label">Published sermons</div>
        <a href="manage-sermons.php">View All <i class="fas fa-arrow-right"></i></a>
    </div>

    <!-- Upcoming Events -->
    <div class="stat-card">
        <div class="stat-card-header">
            <h3>Upcoming Events</h3>
            <div class="stat-icon green"><i class="fas fa-calendar-alt"></i></div>
        </div>
        <div class="stat-number"><?php echo number_format($upcoming_events); ?></div>
        <div class="stat-label">Scheduled events</div>
        <a href="manage-events.php">View All <i class="fas fa-arrow-right"></i></a>
    </div>

    <!-- Blog Posts / Content -->
    <?php if ($can_view_content): ?>
    <div class="stat-card">
        <div class="stat-card-header">
            <h3>Blog Posts</h3>
            <div class="stat-icon purple"><i class="fas fa-newspaper"></i></div>
        </div>
        <div class="stat-number"><?php echo number_format($published_posts); ?></div>
        <div class="stat-label"><?php echo number_format($draft_posts); ?> drafts</div>
        <a href="content.php">Manage Content <i class="fas fa-arrow-right"></i></a>
    </div>
    <?php endif; ?>

    <!-- Announcements -->
    <div class="stat-card">
        <div class="stat-card-header">
            <h3>Announcements</h3>
            <div class="stat-icon orange"><i class="fas fa-bullhorn"></i></div>
        </div>
        <div class="stat-number"><?php echo number_format($active_announcements); ?></div>
        <div class="stat-label">Active announcements</div>
        <a href="manage-announcements.php">View All <i class="fas fa-arrow-right"></i></a>
    </div>

    <!-- Donations -->
    <div class="stat-card">
        <div class="stat-card-header">
            <h3>Donations</h3>
            <div class="stat-icon teal"><i class="fas fa-hand-holding-usd"></i></div>
        </div>
        <div class="stat-number">$<?php echo number_format($total_donation_amount, 2); ?></div>
        <div class="stat-label"><?php echo number_format($total_donations); ?> total donations</div>
        <a href="donations.php">View All <i class="fas fa-arrow-right"></i></a>
    </div>

</div>

<style>
.stat-icon.purple {
    background: linear-gradient(135deg, #9b59b6, #8e44ad);
}
</style>

<!-- Two Column Layout for Recent Items -->
<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(400px, 1fr)); gap: 1.5rem; margin-bottom: 1.5rem;">

    <!-- Recent Sermons -->
    <div class="card">
        <div class="card-header">
            <h3><i class="fas fa-bible"></i> Recent Sermons</h3>
        </div>
        <div class="card-body">
            <?php if ($recent_sermons): ?>
                <table class="admin-table">
                    <thead>
                        <tr><th>Title</th><th>Date</th><th>Preacher</th></tr>
                    </thead>
                    <tbody>
                    <?php foreach ($recent_sermons as $sermon): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($sermon['title']); ?></td>
                            <td><?php echo date('M d, Y', strtotime($sermon['sermon_date'])); ?></td>
                            <td><?php echo htmlspecialchars($sermon['preacher']); ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p>No sermons available.</p>
            <?php endif; ?>
        </div>
    </div>

    <!-- Recent Posts -->
    <?php if ($can_view_content): ?>
    <div class="card">
        <div class="card-header" style="display: flex; justify-content: space-between; align-items: center;">
            <h3><i class="fas fa-newspaper"></i> Recent Posts</h3>
            <a href="add-post.php" class="btn btn-primary" style="padding: 0.4rem 0.75rem; font-size: 0.85rem;">
                <i class="fas fa-plus"></i> New Post
            </a>
        </div>
        <div class="card-body">
            <?php if ($recent_posts): ?>
                <table class="admin-table">
                    <thead>
                        <tr><th>Title</th><th>Author</th><th>Status</th><th>Date</th></tr>
                    </thead>
                    <tbody>
                    <?php foreach ($recent_posts as $post): ?>
                        <tr>
                            <td>
                                <a href="edit-post.php?id=<?php echo $post['id']; ?>" style="color: var(--admin-accent); text-decoration: none;">
                                    <?php echo htmlspecialchars(substr($post['title'], 0, 30)); ?><?php echo strlen($post['title']) > 30 ? '...' : ''; ?>
                                </a>
                            </td>
                            <td><?php echo htmlspecialchars($post['author_name'] ?? 'Unknown'); ?></td>
                            <td>
                                <span class="status-badge <?php echo $post['status']; ?>">
                                    <?php echo ucfirst($post['status']); ?>
                                </span>
                            </td>
                            <td><?php echo date('M d, Y', strtotime($post['created_at'])); ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
                <div style="text-align: center; margin-top: 1rem;">
                    <a href="content.php" class="btn btn-secondary" style="padding: 0.4rem 1rem; font-size: 0.85rem;">
                        View All Posts <i class="fas fa-arrow-right"></i>
                    </a>
                </div>
            <?php else: ?>
                <div style="text-align: center; padding: 2rem;">
                    <i class="fas fa-newspaper" style="font-size: 2.5rem; color: #ccc; margin-bottom: 1rem;"></i>
                    <p style="color: #666; margin-bottom: 1rem;">No posts yet. Start creating content!</p>
                    <a href="add-post.php" class="btn btn-primary">
                        <i class="fas fa-plus"></i> Create First Post
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </div>
    <?php endif; ?>

</div>

<!-- Recent Donations -->
<div class="card">
    <div class="card-header">
        <h3><i class="fas fa-hand-holding-usd"></i> Recent Donations</h3>
    </div>
    <div class="card-body">
        <?php if ($recent_donations): ?>
            <table class="admin-table">
                <thead>
                    <tr><th>Donor</th><th>Amount</th><th>Type</th><th>Date</th></tr>
                </thead>
                <tbody>
                <?php foreach ($recent_donations as $donation): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($donation['donor_name']); ?></td>
                        <td>$<?php echo number_format($donation['amount'], 2); ?></td>
                        <td><?php echo htmlspecialchars($donation['donation_type']); ?></td>
                        <td><?php echo date('M d, Y', strtotime($donation['donation_date'])); ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p>No donations recorded yet.</p>
        <?php endif; ?>
    </div>
</div>

<style>
.status-badge {
    display: inline-block;
    padding: 0.25rem 0.6rem;
    border-radius: 12px;
    font-size: 0.75rem;
    font-weight: 600;
    text-transform: uppercase;
}

.status-badge.published {
    background: #d4edda;
    color: #155724;
}

.status-badge.draft {
    background: #fff3cd;
    color: #856404;
}
</style>

<?php include 'includes/admin-footer.php'; ?>