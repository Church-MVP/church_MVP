<?php
/**
 * Manage Sermons
 * 
 * View, edit, and delete sermons
 */

// Set page title
$page_title = 'Manage Sermons';

// Include admin header
include 'includes/admin-header.php';

// Initialize messages
$success_message = '';
$error_message = '';

// Handle delete action
if (isset($_GET['delete'])) {
    $sermon_id = (int)$_GET['delete'];
    
    try {
        $stmt = $pdo->prepare("DELETE FROM sermons WHERE id = ?");
        $stmt->execute([$sermon_id]);
        $success_message = 'Sermon deleted successfully!';
    } catch (PDOException $e) {
        $error_message = 'Error deleting sermon. Please try again.';
    }
}

// Fetch all sermons
$stmt = $pdo->query("SELECT * FROM sermons ORDER BY sermon_date DESC");
$sermons = $stmt->fetchAll();
?>

<!-- Page Header -->
<div class="page-header">
    <h2><i class="fas fa-bible"></i> Manage Sermons</h2>
    <p>View, edit, and delete sermons</p>
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
    <a href="add-sermon.php" class="btn btn-primary">
        <i class="fas fa-plus"></i> Add New Sermon
    </a>
</div>

<!-- Sermons Table -->
<div class="card">
    <div class="card-header">
        <h3><i class="fas fa-list"></i> All Sermons (<?php echo count($sermons); ?>)</h3>
    </div>
    <div class="card-body">
        <?php if (!empty($sermons)): ?>
        <div class="table-responsive">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th data-sortable>ID</th>
                        <th data-sortable>Title</th>
                        <th data-sortable>Preacher</th>
                        <th data-sortable>Date</th>
                        <th>Scripture</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($sermons as $sermon): ?>
                    <tr>
                        <td><?php echo $sermon['id']; ?></td>
                        <td>
                            <strong><?php echo htmlspecialchars($sermon['title']); ?></strong>
                            <br>
                            <small style="color: #666;">
                                <?php echo htmlspecialchars(substr($sermon['description'], 0, 60)); ?>...
                            </small>
                        </td>
                        <td><?php echo htmlspecialchars($sermon['preacher']); ?></td>
                        <td><?php echo date('M d, Y', strtotime($sermon['sermon_date'])); ?></td>
                        <td><?php echo htmlspecialchars($sermon['scripture_reference']); ?></td>
                        <td>
                            <a href="edit-sermon.php?id=<?php echo $sermon['id']; ?>" 
                               class="btn btn-sm btn-info" 
                               title="Edit">
                                <i class="fas fa-edit"></i>
                            </a>
                            <a href="?delete=<?php echo $sermon['id']; ?>" 
                               class="btn btn-sm btn-danger btn-delete" 
                               data-item="<?php echo htmlspecialchars($sermon['title']); ?>"
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
            <i class="fas fa-bible" style="font-size: 4rem; color: #ccc; margin-bottom: 1rem;"></i>
            <p style="color: #666; font-size: 1.1rem;">No sermons found.</p>
            <a href="add-sermon.php" class="btn btn-primary" style="margin-top: 1rem;">
                <i class="fas fa-plus"></i> Add Your First Sermon
            </a>
        </div>
        <?php endif; ?>
    </div>
</div>

<?php
// Include admin footer
include 'includes/admin-footer.php';
?>