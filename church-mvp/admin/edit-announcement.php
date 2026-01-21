<?php
/**
 * Edit Announcement
 * 
 * Form to edit an existing announcement
 */

// Set page title
$page_title = 'Edit Announcement';

// Include admin header
include 'includes/admin-header.php';

// Initialize messages
$success_message = '';
$error_message = '';

// Get announcement ID from URL
if (!isset($_GET['id'])) {
    header("Location: manage-announcements.php");
    exit();
}

$announcement_id = (int)$_GET['id'];

// Fetch announcement data
try {
    $stmt = $pdo->prepare("SELECT * FROM announcements WHERE id = ?");
    $stmt->execute([$announcement_id]);
    $announcement = $stmt->fetch();
    
    if (!$announcement) {
        header("Location: manage-announcements.php");
        exit();
    }
} catch (PDOException $e) {
    $error_message = 'Error fetching announcement data.';
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Sanitize inputs
    $title = sanitize_input($_POST['title']);
    $content = sanitize_input($_POST['content']);
    $announcement_date = sanitize_input($_POST['announcement_date']);
    $is_active = isset($_POST['is_active']) ? 1 : 0;
    
    // Validation
    if (empty($title) || empty($content) || empty($announcement_date)) {
        $error_message = 'Please fill in all required fields.';
    } else {
        try {
            // Update announcement in database
            $stmt = $pdo->prepare("UPDATE announcements SET title = ?, content = ?, announcement_date = ?, is_active = ? WHERE id = ?");
            $stmt->execute([
                $title,
                $content,
                $announcement_date,
                $is_active,
                $announcement_id
            ]);
            
            $success_message = 'Announcement updated successfully!';
            
            // Refresh announcement data
            $stmt = $pdo->prepare("SELECT * FROM announcements WHERE id = ?");
            $stmt->execute([$announcement_id]);
            $announcement = $stmt->fetch();
        } catch (PDOException $e) {
            $error_message = 'Error updating announcement. Please try again.';
        }
    }
}
?>

<!-- Page Header -->
<div class="page-header">
    <h2><i class="fas fa-edit"></i> Edit Announcement</h2>
    <p>Update announcement information</p>
</div>

<!-- Success/Error Messages -->
<?php if (!empty($success_message)): ?>
<div class="alert alert-success">
    <i class="fas fa-check-circle"></i> <?php echo $success_message; ?>
    <a href="manage-announcements.php" style="margin-left: 1rem;">Back to all announcements</a>
</div>
<?php endif; ?>

<?php if (!empty($error_message)): ?>
<div class="alert alert-danger">
    <i class="fas fa-exclamation-circle"></i> <?php echo $error_message; ?>
</div>
<?php endif; ?>

<!-- Announcement Form -->
<div class="card">
    <div class="card-header">
        <h3><i class="fas fa-bullhorn"></i> Edit Announcement Details</h3>
    </div>
    <div class="card-body">
        <form method="POST" action="">
            <!-- Announcement Title -->
            <div class="form-group">
                <label for="title">Announcement Title <span style="color: red;">*</span></label>
                <input type="text" 
                       id="title" 
                       name="title" 
                       class="form-control" 
                       value="<?php echo htmlspecialchars($announcement['title']); ?>"
                       required>
            </div>
            
            <!-- Announcement Date -->
            <div class="form-group">
                <label for="announcement_date">Announcement Date <span style="color: red;">*</span></label>
                <input type="date" 
                       id="announcement_date" 
                       name="announcement_date" 
                       class="form-control" 
                       value="<?php echo htmlspecialchars($announcement['announcement_date']); ?>"
                       required>
            </div>
            
            <!-- Content -->
            <div class="form-group">
                <label for="content">Content <span style="color: red;">*</span></label>
                <textarea id="content" 
                          name="content" 
                          class="form-control" 
                          rows="6"
                          maxlength="1000"
                          required><?php echo htmlspecialchars($announcement['content']); ?></textarea>
            </div>
            
            <!-- Active Status -->
            <div class="form-group">
                <label style="display: flex; align-items: center; gap: 0.5rem; cursor: pointer;">
                    <input type="checkbox" 
                           id="is_active" 
                           name="is_active" 
                           value="1"
                           <?php echo $announcement['is_active'] ? 'checked' : ''; ?>
                           style="width: 20px; height: 20px;">
                    <span>Make this announcement active (visible on website)</span>
                </label>
            </div>
            
            <!-- Form Actions -->
            <div style="display: flex; gap: 1rem; margin-top: 2rem; padding-top: 1.5rem; border-top: 1px solid #e0e5eb;">
                <button type="submit" class="btn btn-warning">
                    <i class="fas fa-save"></i> Update Announcement
                </button>
                <a href="manage-announcements.php" class="btn btn-secondary">
                    <i class="fas fa-times"></i> Cancel
                </a>
                <a href="manage-announcements.php?delete=<?php echo $announcement_id; ?>" 
                   class="btn btn-danger btn-delete" 
                   data-item="<?php echo htmlspecialchars($announcement['title']); ?>"
                   style="margin-left: auto;">
                    <i class="fas fa-trash"></i> Delete Announcement
                </a>
            </div>
        </form>
    </div>
</div>

<?php
// Include admin footer
include 'includes/admin-footer.php';
?>