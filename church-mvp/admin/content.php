<?php
/**
 * Content Management - Blog Posts
 * 
 * Allows admin, super-admin, viewer, and content-manager roles to manage blog posts
 */

// Include database connection
require_once '../includes/db.php';

// Include authentication check
require_once '../includes/auth.php';

// Include permissions system
require_once 'includes/permissions.php';

// Check permission BEFORE including admin-header.php (which outputs HTML)
$allowed_roles = ['admin', 'super_admin', 'content_manager', 'viewer'];
if (!in_array($_SESSION['admin_role'] ?? '', $allowed_roles)) {
    header("Location: dashboard.php");
    exit();
}

// Set page title
$page_title = 'Content Management';

// Include admin header (this outputs HTML, so must come after any redirects)
include 'includes/admin-header.php';

// Initialize messages
$success_message = '';
$error_message = '';

// Handle delete action
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    // Only admins and content managers can delete
    $can_delete = in_array($_SESSION['admin_role'] ?? '', ['admin', 'super_admin', 'content_manager']);
    
    if ($can_delete) {
        try {
            $post_id = (int)$_GET['delete'];
            
            // Get post image before deleting
            $stmt = $pdo->prepare("SELECT featured_image FROM posts WHERE id = ?");
            $stmt->execute([$post_id]);
            $post = $stmt->fetch();
            
            // Delete post
            $stmt = $pdo->prepare("DELETE FROM posts WHERE id = ?");
            $stmt->execute([$post_id]);
            
            // Delete associated image
            if (!empty($post['featured_image']) && file_exists('../' . $post['featured_image'])) {
                unlink('../' . $post['featured_image']);
            }
            
            $success_message = 'Post deleted successfully!';
        } catch (PDOException $e) {
            $error_message = 'Error deleting post. Please try again.';
        }
    } else {
        $error_message = 'You do not have permission to delete posts.';
    }
}

// Handle status toggle (publish/unpublish)
if (isset($_GET['toggle_status']) && is_numeric($_GET['toggle_status'])) {
    $can_edit = in_array($_SESSION['admin_role'] ?? '', ['admin', 'super_admin', 'content_manager']);
    
    if ($can_edit) {
        try {
            $post_id = (int)$_GET['toggle_status'];
            
            // Get current status
            $stmt = $pdo->prepare("SELECT status FROM posts WHERE id = ?");
            $stmt->execute([$post_id]);
            $post = $stmt->fetch();
            
            if ($post) {
                $new_status = ($post['status'] == 'published') ? 'draft' : 'published';
                $published_at = ($new_status == 'published') ? date('Y-m-d H:i:s') : null;
                
                $stmt = $pdo->prepare("UPDATE posts SET status = ?, published_at = COALESCE(published_at, ?) WHERE id = ?");
                $stmt->execute([$new_status, $published_at, $post_id]);
                
                $success_message = 'Post status updated successfully!';
            }
        } catch (PDOException $e) {
            $error_message = 'Error updating post status.';
        }
    } else {
        $error_message = 'You do not have permission to change post status.';
    }
}

// Pagination
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$per_page = 10;
$offset = ($page - 1) * $per_page;

// Filter by status
$status_filter = isset($_GET['status']) ? $_GET['status'] : '';

// Build query
$where_clause = "";
$params = [];

if ($status_filter && in_array($status_filter, ['draft', 'published'])) {
    $where_clause = "WHERE p.status = ?";
    $params[] = $status_filter;
}

// Get total count
$count_sql = "SELECT COUNT(*) FROM posts p $where_clause";
$stmt = $pdo->prepare($count_sql);
$stmt->execute($params);
$total_posts = $stmt->fetchColumn();
$total_pages = ceil($total_posts / $per_page);

// Fetch posts with author info (using 'admins' table instead of 'users')
$sql = "SELECT p.*, a.username as author_name 
        FROM posts p 
        LEFT JOIN admins a ON p.author_id = a.id 
        $where_clause 
        ORDER BY p.created_at DESC 
        LIMIT $per_page OFFSET $offset";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$posts = $stmt->fetchAll();

// Check user permissions
$is_viewer = ($_SESSION['admin_role'] ?? '') === 'viewer';
$can_create = in_array($_SESSION['admin_role'] ?? '', ['admin', 'super_admin', 'content_manager']);
$can_edit = in_array($_SESSION['admin_role'] ?? '', ['admin', 'super_admin', 'content_manager']);
$can_delete = in_array($_SESSION['admin_role'] ?? '', ['admin', 'super_admin', 'content_manager']);

// Available pages for targeting
$available_pages = [
    'home' => 'Homepage',
    'about' => 'About Page',
    'services' => 'Services Page',
    'contact' => 'Contact Page',
    'live' => 'Live Stream Page',
    'donate' => 'Donate Page'
];
?>

<!-- Page Header -->
<div class="page-header" style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem;">
    <div>
        <h2><i class="fas fa-newspaper"></i> Content Management</h2>
        <p>Create and manage blog posts for your website</p>
    </div>
    <?php if ($can_create): ?>
    <a href="add-post.php" class="btn btn-primary">
        <i class="fas fa-plus"></i> Create New Post
    </a>
    <?php endif; ?>
</div>

<!-- Success/Error Messages -->
<?php if (!empty($success_message)): ?>
<div class="alert alert-success">
    <i class="fas fa-check-circle"></i> <?php echo $success_message; ?>
</div>
<?php endif; ?>

<?php if (!empty($error_message)): ?>
<div class="alert alert-danger">
    <i class="fas fa-exclamation-circle"></i> <?php echo $error_message; ?>
</div>
<?php endif; ?>

<!-- Stats Cards -->
<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1.5rem; margin-bottom: 2rem;">
    <div class="card" style="padding: 1.5rem; text-align: center;">
        <i class="fas fa-file-alt" style="font-size: 2rem; color: var(--admin-primary); margin-bottom: 0.5rem;"></i>
        <h3 style="font-size: 2rem; margin: 0.5rem 0;"><?php echo $total_posts; ?></h3>
        <p style="color: #666; margin: 0;">Total Posts</p>
    </div>
    
    <?php
    $stmt = $pdo->query("SELECT COUNT(*) FROM posts WHERE status = 'published'");
    $published_count = $stmt->fetchColumn();
    ?>
    <div class="card" style="padding: 1.5rem; text-align: center;">
        <i class="fas fa-check-circle" style="font-size: 2rem; color: #28a745; margin-bottom: 0.5rem;"></i>
        <h3 style="font-size: 2rem; margin: 0.5rem 0;"><?php echo $published_count; ?></h3>
        <p style="color: #666; margin: 0;">Published</p>
    </div>
    
    <?php
    $stmt = $pdo->query("SELECT COUNT(*) FROM posts WHERE status = 'draft'");
    $draft_count = $stmt->fetchColumn();
    ?>
    <div class="card" style="padding: 1.5rem; text-align: center;">
        <i class="fas fa-edit" style="font-size: 2rem; color: #ffc107; margin-bottom: 0.5rem;"></i>
        <h3 style="font-size: 2rem; margin: 0.5rem 0;"><?php echo $draft_count; ?></h3>
        <p style="color: #666; margin: 0;">Drafts</p>
    </div>
    
    <?php
    $stmt = $pdo->query("SELECT COUNT(*) FROM posts WHERE show_on_homepage = 1 AND status = 'published'");
    $homepage_count = $stmt->fetchColumn();
    ?>
    <div class="card" style="padding: 1.5rem; text-align: center;">
        <i class="fas fa-home" style="font-size: 2rem; color: var(--admin-accent); margin-bottom: 0.5rem;"></i>
        <h3 style="font-size: 2rem; margin: 0.5rem 0;"><?php echo $homepage_count; ?></h3>
        <p style="color: #666; margin: 0;">On Homepage</p>
    </div>
</div>

<!-- Filter Tabs -->
<div class="card" style="margin-bottom: 1.5rem;">
    <div style="display: flex; gap: 0; border-bottom: 2px solid #e0e5eb;">
        <a href="content.php" class="filter-tab <?php echo empty($status_filter) ? 'active' : ''; ?>">
            <i class="fas fa-list"></i> All Posts
        </a>
        <a href="content.php?status=published" class="filter-tab <?php echo $status_filter === 'published' ? 'active' : ''; ?>">
            <i class="fas fa-check-circle"></i> Published
        </a>
        <a href="content.php?status=draft" class="filter-tab <?php echo $status_filter === 'draft' ? 'active' : ''; ?>">
            <i class="fas fa-edit"></i> Drafts
        </a>
    </div>
</div>

<style>
.filter-tab {
    padding: 1rem 1.5rem;
    text-decoration: none;
    color: #666;
    font-weight: 600;
    border-bottom: 3px solid transparent;
    transition: all 0.3s;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.filter-tab:hover {
    color: var(--admin-accent);
    background: rgba(122, 156, 198, 0.1);
}

.filter-tab.active {
    color: var(--admin-accent);
    border-bottom-color: var(--admin-accent);
}

.post-card {
    display: grid;
    grid-template-columns: 120px 1fr auto;
    gap: 1.5rem;
    padding: 1.5rem;
    border-bottom: 1px solid #e0e5eb;
    align-items: center;
    transition: background 0.3s;
}

.post-card:hover {
    background: #f8f9fa;
}

.post-card:last-child {
    border-bottom: none;
}

.post-thumbnail {
    width: 120px;
    height: 80px;
    object-fit: cover;
    border-radius: 8px;
    background: #e0e5eb;
}

.post-info h3 {
    margin: 0 0 0.5rem 0;
    font-size: 1.1rem;
    color: var(--admin-primary);
}

.post-info h3 a {
    color: inherit;
    text-decoration: none;
}

.post-info h3 a:hover {
    color: var(--admin-accent);
}

.post-meta {
    display: flex;
    flex-wrap: wrap;
    gap: 1rem;
    font-size: 0.85rem;
    color: #666;
}

.post-meta span {
    display: flex;
    align-items: center;
    gap: 0.35rem;
}

.post-excerpt {
    color: #888;
    font-size: 0.9rem;
    margin-top: 0.5rem;
    line-height: 1.5;
}

.post-status {
    display: inline-block;
    padding: 0.25rem 0.75rem;
    border-radius: 20px;
    font-size: 0.75rem;
    font-weight: 600;
    text-transform: uppercase;
}

.post-status.published {
    background: #d4edda;
    color: #155724;
}

.post-status.draft {
    background: #fff3cd;
    color: #856404;
}

.post-actions {
    display: flex;
    gap: 0.5rem;
    flex-wrap: wrap;
}

.post-pages {
    display: flex;
    flex-wrap: wrap;
    gap: 0.35rem;
    margin-top: 0.5rem;
}

.page-badge {
    display: inline-block;
    padding: 0.15rem 0.5rem;
    background: rgba(122, 156, 198, 0.15);
    color: var(--admin-accent);
    border-radius: 4px;
    font-size: 0.7rem;
    font-weight: 600;
}

@media (max-width: 768px) {
    .post-card {
        grid-template-columns: 1fr;
        gap: 1rem;
    }
    
    .post-thumbnail {
        width: 100%;
        height: 150px;
    }
    
    .post-actions {
        justify-content: flex-start;
    }
}
</style>

<!-- Posts List -->
<div class="card">
    <div class="card-header">
        <h3><i class="fas fa-list"></i> Posts (<?php echo $total_posts; ?>)</h3>
    </div>
    
    <?php if (!empty($posts)): ?>
    <div class="posts-list">
        <?php foreach ($posts as $post): ?>
        <?php
        $target_pages = json_decode($post['target_pages'] ?? '[]', true) ?: [];
        $thumbnail = !empty($post['featured_image']) ? '../' . $post['featured_image'] : 'https://via.placeholder.com/120x80?text=No+Image';
        ?>
        <div class="post-card">
            <img src="<?php echo htmlspecialchars($thumbnail); ?>" 
                 alt="<?php echo htmlspecialchars($post['title']); ?>" 
                 class="post-thumbnail"
                 onerror="this.src='https://via.placeholder.com/120x80?text=No+Image';">
            
            <div class="post-info">
                <h3>
                    <?php if ($can_edit): ?>
                    <a href="edit-post.php?id=<?php echo $post['id']; ?>"><?php echo htmlspecialchars($post['title']); ?></a>
                    <?php else: ?>
                    <?php echo htmlspecialchars($post['title']); ?>
                    <?php endif; ?>
                    <span class="post-status <?php echo $post['status']; ?>"><?php echo ucfirst($post['status']); ?></span>
                </h3>
                
                <div class="post-meta">
                    <span><i class="fas fa-user"></i> <?php echo htmlspecialchars($post['author_name'] ?? 'Unknown'); ?></span>
                    <span><i class="fas fa-calendar"></i> <?php echo date('M j, Y', strtotime($post['created_at'])); ?></span>
                    <?php if ($post['status'] == 'published' && $post['published_at']): ?>
                    <span><i class="fas fa-globe"></i> Published <?php echo date('M j, Y', strtotime($post['published_at'])); ?></span>
                    <?php endif; ?>
                </div>
                
                <?php if (!empty($post['excerpt'])): ?>
                <p class="post-excerpt"><?php echo htmlspecialchars(substr($post['excerpt'], 0, 150)); ?>...</p>
                <?php endif; ?>
                
                <div class="post-pages">
                    <?php if ($post['show_on_homepage']): ?>
                    <span class="page-badge"><i class="fas fa-home"></i> Homepage</span>
                    <?php endif; ?>
                    <?php foreach ($target_pages as $page_key): ?>
                    <?php if ($page_key !== 'home' && isset($available_pages[$page_key])): ?>
                    <span class="page-badge"><?php echo $available_pages[$page_key]; ?></span>
                    <?php endif; ?>
                    <?php endforeach; ?>
                </div>
            </div>
            
            <div class="post-actions">
                <?php if ($can_edit): ?>
                <a href="edit-post.php?id=<?php echo $post['id']; ?>" class="btn btn-secondary" style="padding: 0.5rem 0.75rem; font-size: 0.85rem;">
                    <i class="fas fa-edit"></i> Edit
                </a>
                
                <a href="content.php?toggle_status=<?php echo $post['id']; ?>" 
                   class="btn <?php echo $post['status'] == 'published' ? 'btn-warning' : 'btn-success'; ?>" 
                   style="padding: 0.5rem 0.75rem; font-size: 0.85rem;"
                   onclick="return confirm('Are you sure you want to <?php echo $post['status'] == 'published' ? 'unpublish' : 'publish'; ?> this post?');">
                    <i class="fas <?php echo $post['status'] == 'published' ? 'fa-eye-slash' : 'fa-check'; ?>"></i>
                    <?php echo $post['status'] == 'published' ? 'Unpublish' : 'Publish'; ?>
                </a>
                <?php endif; ?>
                
                <?php if ($can_delete): ?>
                <a href="content.php?delete=<?php echo $post['id']; ?>" 
                   class="btn btn-danger" 
                   style="padding: 0.5rem 0.75rem; font-size: 0.85rem;"
                   onclick="return confirm('Are you sure you want to delete this post? This action cannot be undone.');">
                    <i class="fas fa-trash"></i>
                </a>
                <?php endif; ?>
                
                <?php if ($post['status'] == 'published'): ?>
                <a href="../index.php#post-<?php echo $post['id']; ?>" target="_blank" class="btn btn-info" style="padding: 0.5rem 0.75rem; font-size: 0.85rem;">
                    <i class="fas fa-external-link-alt"></i> View
                </a>
                <?php endif; ?>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    
    <!-- Pagination -->
    <?php if ($total_pages > 1): ?>
    <div style="padding: 1.5rem; border-top: 1px solid #e0e5eb; display: flex; justify-content: center; gap: 0.5rem;">
        <?php if ($page > 1): ?>
        <a href="?page=<?php echo $page - 1; ?><?php echo $status_filter ? '&status=' . $status_filter : ''; ?>" class="btn btn-secondary" style="padding: 0.5rem 1rem;">
            <i class="fas fa-chevron-left"></i> Previous
        </a>
        <?php endif; ?>
        
        <span style="padding: 0.5rem 1rem; color: #666;">
            Page <?php echo $page; ?> of <?php echo $total_pages; ?>
        </span>
        
        <?php if ($page < $total_pages): ?>
        <a href="?page=<?php echo $page + 1; ?><?php echo $status_filter ? '&status=' . $status_filter : ''; ?>" class="btn btn-secondary" style="padding: 0.5rem 1rem;">
            Next <i class="fas fa-chevron-right"></i>
        </a>
        <?php endif; ?>
    </div>
    <?php endif; ?>
    
    <?php else: ?>
    <div style="padding: 3rem; text-align: center;">
        <i class="fas fa-newspaper" style="font-size: 4rem; color: #ccc; margin-bottom: 1rem;"></i>
        <p style="color: #666; font-size: 1.1rem;">No posts found.</p>
        <?php if ($can_create): ?>
        <a href="add-post.php" class="btn btn-primary" style="margin-top: 1rem;">
            <i class="fas fa-plus"></i> Create Your First Post
        </a>
        <?php endif; ?>
    </div>
    <?php endif; ?>
</div>

<?php if ($is_viewer): ?>
<div class="alert alert-info" style="margin-top: 1.5rem;">
    <i class="fas fa-info-circle"></i> You have view-only access. Contact an administrator if you need to create or edit posts.
</div>
<?php endif; ?>

<?php
// Include admin footer
include 'includes/admin-footer.php';
?>