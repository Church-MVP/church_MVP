<?php
/**
 * Add New Announcement
 * 
 * Form to add a new announcement to the database
 */

// Set page title
$page_title = 'Add New Announcement';

// Include admin header
include 'includes/admin-header.php';

// Initialize messages
$success_message = '';
$error_message = '';

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
            // Insert announcement into database
            $stmt = $pdo->prepare("INSERT INTO announcements (title, content, announcement_date, is_active) VALUES (?, ?, ?, ?)");
            $stmt->execute([
                $title,
                $content,
                $announcement_date,
                $is_active
            ]);
            
            $success_message = 'Announcement added successfully!';
            
            // Clear form
            $_POST = [];
        } catch (PDOException $e) {
            $error_message = 'Error adding announcement. Please try again.';
        }
    }
}
?>

<!-- Page Header -->
<div class="page-header">
    <h2><i class="fas fa-plus-circle"></i> Add New Announcement</h2>
    <p>Create a new church announcement</p>
</div>

<!-- Success/Error Messages -->
<?php if (!empty($success_message)): ?>
<div class="alert alert-success">
    <i class="fas fa-check-circle"></i> <?php echo $success_message; ?>
    <a href="manage-announcements.php" style="margin-left: 1rem;">View all announcements</a>
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
        <h3><i class="fas fa-bullhorn"></i> Announcement Details</h3>
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
                       value="<?php echo isset($_POST['title']) ? htmlspecialchars($_POST['title']) : ''; ?>"
                       placeholder="e.g., New Service Time"
                       required>
            </div>
            
            <!-- Announcement Date -->
            <div class="form-group">
                <label for="announcement_date">Announcement Date <span style="color: red;">*</span></label>
                <input type="date" 
                       id="announcement_date" 
                       name="announcement_date" 
                       class="form-control" 
                       value="<?php echo isset($_POST['announcement_date']) ? htmlspecialchars($_POST['announcement_date']) : date('Y-m-d'); ?>"
                       required>
                <small style="color: #666; display: block; margin-top: 0.25rem;">
                    This date will be displayed on the website
                </small>
            </div>
            
            <!-- Content -->
            <div class="form-group">
                <label for="content">Content <span style="color: red;">*</span></label>
                <textarea id="content" 
                          name="content" 
                          class="form-control" 
                          rows="6"
                          maxlength="1000"
                          placeholder="Enter the announcement details..."
                          required><?php echo isset($_POST['content']) ? htmlspecialchars($_POST['content']) : ''; ?></textarea>
                <small style="color: #666; display: block; margin-top: 0.25rem;">
                    Maximum 1000 characters
                </small>
            </div>
            
            <!-- Active Status -->
            <div class="form-group">
                <label style="display: flex; align-items: center; gap: 0.5rem; cursor: pointer;">
                    <input type="checkbox" 
                           id="is_active" 
                           name="is_active" 
                           value="1"
                           <?php echo (!isset($_POST['is_active']) || isset($_POST['is_active'])) ? 'checked' : ''; ?>
                           style="width: 20px; height: 20px;">
                    <span>Make this announcement active (visible on website)</span>
                </label>
            </div>
            
            <!-- Form Actions -->
            <div style="display: flex; gap: 1rem; margin-top: 2rem; padding-top: 1.5rem; border-top: 1px solid #e0e5eb;">
                <button type="submit" class="btn btn-warning">
                    <i class="fas fa-save"></i> Save Announcement
                </button>
                <a href="manage-announcements.php" class="btn btn-secondary">
                    <i class="fas fa-times"></i> Cancel
                </a>
            </div>
        </form>
    </div>
</div>

<?php
// Include admin footer
include 'includes/admin-footer.php';
?>