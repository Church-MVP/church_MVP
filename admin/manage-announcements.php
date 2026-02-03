<?php
/**
 * Manage Announcements
 * 
 * View, edit, delete, and toggle active/inactive status
 */

// Set page title
$page_title = 'Manage Announcements';

// Include admin header
include 'includes/admin-header.php';

// Initialize messages
$success_message = '';
$error_message = '';

// Handle delete action
if (isset($_GET['delete'])) {
    $announcement_id = (int)$_GET['delete'];
    
    try {
        $stmt = $pdo->prepare("DELETE FROM announcements WHERE id = ?");
        $stmt->execute([$announcement_id]);
        $success_message = 'Announcement deleted successfully!';
    } catch (PDOException $e) {
        $error_message = 'Error deleting announcement. Please try again.';
    }
}

// Handle toggle active/inactive
if (isset($_GET['toggle'])) {
    $announcement_id = (int)$_GET['toggle'];
    
    try {
        // Get current status
        $stmt = $pdo->prepare("SELECT is_active FROM announcements WHERE id = ?");
        $stmt->execute([$announcement_id]);
        $current_status = $stmt->fetchColumn();
        
        // Toggle status
        $new_status = $current_status ? 0 : 1;
        $stmt = $pdo->prepare("UPDATE announcements SET is_active = ? WHERE id = ?");
        $stmt->execute([$new_status, $announcement_id]);
        
        $success_message = 'Announcement status updated successfully!';
    } catch (PDOException $e) {
        $error_message = 'Error updating announcement status.';
    }
}

// Fetch all announcements
$stmt = $pdo->query("SELECT * FROM announcements ORDER BY announcement_date DESC");
$announcements = $stmt->fetchAll();
?>

<!-- Page Header -->
<div class="page-header">
    <h2><i class="fas fa-bullhorn"></i> Manage Announcements</h2>
    <p>View, edit, and manage church announcements</p>
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

<!-- Add New Button -->
<div style="margin-bottom: 1.5rem;">
    <a href="add-announcement.php" class="btn btn-warning">
        <i class="fas fa-plus"></i> Add New Announcement
    </a>
</div>

<!-- Announcements Table -->
<div class="card">
    <div class="card-header">
        <h3><i class="fas fa-list"></i> All Announcements (<?php echo count($announcements); ?>)</h3>
    </div>
    <div class="card-body">
        <?php if (!empty($announcements)): ?>
        <div class="table-responsive">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Title</th>
                        <th>Content</th>
                        <th>Date</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($announcements as $announcement): ?>
                    <tr style="<?php echo !$announcement['is_active'] ? 'opacity: 0.5;' : ''; ?>">
                        <td><?php echo $announcement['id']; ?></td>
                        <td>
                            <strong><?php echo htmlspecialchars($announcement['title']); ?></strong>
                        </td>
                        <td>
                            <small style="color: #666;">
                                <?php echo htmlspecialchars(substr($announcement['content'], 0, 80)); ?>...
                            </small>
                        </td>
                        <td><?php echo date('M d, Y', strtotime($announcement['announcement_date'])); ?></td>
                        <td>
                            <?php if ($announcement['is_active']): ?>
                                <span class="badge badge-success">Active</span>
                            <?php else: ?>
                                <span class="badge badge-secondary">Inactive</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <a href="?toggle=<?php echo $announcement['id']; ?>" 
                               class="btn btn-sm <?php echo $announcement['is_active'] ? 'btn-secondary' : 'btn-success'; ?>" 
                               title="<?php echo $announcement['is_active'] ? 'Deactivate' : 'Activate'; ?>">
                                <i class="fas fa-<?php echo $announcement['is_active'] ? 'eye-slash' : 'eye'; ?>"></i>
                            </a>
                            <a href="edit-announcement.php?id=<?php echo $announcement['id']; ?>" 
                               class="btn btn-sm btn-info" 
                               title="Edit">
                                <i class="fas fa-edit"></i>
                            </a>
                            <a href="?delete=<?php echo $announcement['id']; ?>" 
                               class="btn btn-sm btn-danger btn-delete" 
                               data-item="<?php echo htmlspecialchars($announcement['title']); ?>"
                               title="Delete">
                                <i class="fas fa-trash"></i>
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php else: ?>
        <div style="text-align: center; padding: 3rem;">
            <i class="fas fa-bullhorn" style="font-size: 4rem; color: #ccc; margin-bottom: 1rem;"></i>
            <p style="color: #666; font-size: 1.1rem;">No announcements found.</p>
            <a href="add-announcement.php" class="btn btn-warning" style="margin-top: 1rem;">
                <i class="fas fa-plus"></i> Add Your First Announcement
            </a>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- Info Box -->
<div class="alert alert-info" style="margin-top: 1.5rem;">
    <i class="fas fa-info-circle"></i> 
    <strong>Note:</strong> Only active announcements will be displayed on the public website. Use the eye icon to toggle visibility.
</div>

<?php
// Include admin footer
include 'includes/admin-footer.php';
?>