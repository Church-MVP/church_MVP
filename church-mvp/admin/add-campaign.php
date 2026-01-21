<?php
/**
 * Add Donation Campaign
 * 
 * Admin page to create a new donation campaign
 * Includes all campaign settings and options
 */

// Set page title
$page_title = 'Add Campaign';

// Include admin header (handles session, db connection, auth)
include 'includes/admin-header.php';

/**
 * ===============================
 * Initialize Variables
 * ===============================
 */
$success_message = '';
$error_message = '';

// Form field defaults
$form_data = [
    'title' => '',
    'slug' => '',
    'short_description' => '',
    'description' => '',
    'goal_amount' => '',
    'donation_type' => 'General',
    'start_date' => '',
    'end_date' => '',
    'is_active' => 1,
    'is_featured' => 0,
    'show_progress_bar' => 1,
    'show_donor_count' => 1,
    'minimum_amount' => '1.00',
    'suggested_amounts' => '25,50,100,250,500'
];

/**
 * ===============================
 * Handle Form Submission
 * ===============================
 */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Collect and sanitize form data
    $form_data['title'] = sanitize_input($_POST['title'] ?? '');
    $form_data['slug'] = sanitize_input($_POST['slug'] ?? '');
    $form_data['short_description'] = sanitize_input($_POST['short_description'] ?? '');
    $form_data['description'] = $_POST['description'] ?? ''; // Allow HTML for rich text
    $form_data['goal_amount'] = floatval($_POST['goal_amount'] ?? 0);
    $form_data['donation_type'] = sanitize_input($_POST['donation_type'] ?? 'General');
    $form_data['start_date'] = sanitize_input($_POST['start_date'] ?? '');
    $form_data['end_date'] = sanitize_input($_POST['end_date'] ?? '');
    $form_data['is_active'] = isset($_POST['is_active']) ? 1 : 0;
    $form_data['is_featured'] = isset($_POST['is_featured']) ? 1 : 0;
    $form_data['show_progress_bar'] = isset($_POST['show_progress_bar']) ? 1 : 0;
    $form_data['show_donor_count'] = isset($_POST['show_donor_count']) ? 1 : 0;
    $form_data['minimum_amount'] = floatval($_POST['minimum_amount'] ?? 1);
    $form_data['suggested_amounts'] = sanitize_input($_POST['suggested_amounts'] ?? '25,50,100,250,500');
    
    // Generate slug if empty
    if (empty($form_data['slug'])) {
        $form_data['slug'] = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $form_data['title']), '-'));
    }
    
    // Validation
    $errors = [];
    
    if (empty($form_data['title'])) {
        $errors[] = 'Campaign title is required.';
    }
    
    if (empty($form_data['slug'])) {
        $errors[] = 'Campaign slug is required.';
    } else {
        // Check for unique slug
        $stmt = $pdo->prepare("SELECT id FROM donation_campaigns WHERE slug = ?");
        $stmt->execute([$form_data['slug']]);
        if ($stmt->fetch()) {
            $errors[] = 'A campaign with this slug already exists. Please choose a different one.';
        }
    }
    
    if (empty($form_data['short_description'])) {
        $errors[] = 'Short description is required.';
    }
    
    if ($form_data['minimum_amount'] < 0) {
        $errors[] = 'Minimum amount cannot be negative.';
    }
    
    if (!empty($form_data['start_date']) && !empty($form_data['end_date'])) {
        if (strtotime($form_data['end_date']) < strtotime($form_data['start_date'])) {
            $errors[] = 'End date cannot be before start date.';
        }
    }
    
    // Handle image upload
    $featured_image = '';
    if (isset($_FILES['featured_image']) && $_FILES['featured_image']['error'] === UPLOAD_ERR_OK) {
        $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        $max_size = 5 * 1024 * 1024; // 5MB
        
        $file_type = $_FILES['featured_image']['type'];
        $file_size = $_FILES['featured_image']['size'];
        
        if (!in_array($file_type, $allowed_types)) {
            $errors[] = 'Invalid image type. Allowed: JPG, PNG, GIF, WEBP.';
        } elseif ($file_size > $max_size) {
            $errors[] = 'Image size must be less than 5MB.';
        } else {
            // Create uploads directory if it doesn't exist
            $upload_dir = '../uploads/campaigns/';
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0755, true);
            }
            
            // Generate unique filename
            $extension = pathinfo($_FILES['featured_image']['name'], PATHINFO_EXTENSION);
            $featured_image = $form_data['slug'] . '-' . time() . '.' . $extension;
            $upload_path = $upload_dir . $featured_image;
            
            if (!move_uploaded_file($_FILES['featured_image']['tmp_name'], $upload_path)) {
                $errors[] = 'Failed to upload image. Please try again.';
                $featured_image = '';
            }
        }
    }
    
    // If no errors, insert into database
    if (empty($errors)) {
        try {
            $stmt = $pdo->prepare("
                INSERT INTO donation_campaigns (
                    title, slug, short_description, description, featured_image,
                    goal_amount, donation_type, start_date, end_date,
                    is_active, is_featured, show_progress_bar, show_donor_count,
                    minimum_amount, suggested_amounts, created_by, created_at
                ) VALUES (
                    ?, ?, ?, ?, ?,
                    ?, ?, ?, ?,
                    ?, ?, ?, ?,
                    ?, ?, ?, NOW()
                )
            ");
            
            $stmt->execute([
                $form_data['title'],
                $form_data['slug'],
                $form_data['short_description'],
                $form_data['description'],
                $featured_image,
                $form_data['goal_amount'] > 0 ? $form_data['goal_amount'] : null,
                $form_data['donation_type'],
                !empty($form_data['start_date']) ? $form_data['start_date'] : null,
                !empty($form_data['end_date']) ? $form_data['end_date'] : null,
                $form_data['is_active'],
                $form_data['is_featured'],
                $form_data['show_progress_bar'],
                $form_data['show_donor_count'],
                $form_data['minimum_amount'],
                $form_data['suggested_amounts'],
                $_SESSION['admin_id'] ?? null
            ]);
            
            $success_message = 'Campaign created successfully!';
            
            // Redirect to manage page after short delay
            header("Refresh: 2; url=manage-donations.php");
            
            // Reset form
            $form_data = [
                'title' => '',
                'slug' => '',
                'short_description' => '',
                'description' => '',
                'goal_amount' => '',
                'donation_type' => 'General',
                'start_date' => '',
                'end_date' => '',
                'is_active' => 1,
                'is_featured' => 0,
                'show_progress_bar' => 1,
                'show_donor_count' => 1,
                'minimum_amount' => '1.00',
                'suggested_amounts' => '25,50,100,250,500'
            ];
            
        } catch (PDOException $e) {
            $error_message = 'Database error: Failed to create campaign. Please try again.';
        }
    } else {
        $error_message = implode('<br>', $errors);
    }
}

/**
 * ===============================
 * Get existing donation types for dropdown
 * ===============================
 */
$default_types = ['General', 'Tithe', 'Building Fund', 'Missions', 'Youth Ministry', 'Community Outreach', 'Other'];
$stmt = $pdo->query("SELECT DISTINCT donation_type FROM donation_campaigns ORDER BY donation_type");
$existing_types = $stmt->fetchAll(PDO::FETCH_COLUMN);
$donation_types = array_unique(array_merge($default_types, $existing_types));
sort($donation_types);
?>

<!-- Page Header -->
<div class="page-header">
    <h2><i class="fas fa-plus-circle"></i> Add New Campaign</h2>
    <p>Create a new donation campaign</p>
</div>

<!-- Breadcrumb -->
<div style="margin-bottom: 1.5rem;">
    <a href="manage-donations.php" class="btn btn-secondary">
        <i class="fas fa-arrow-left"></i> Back to Campaigns
    </a>
</div>

<!-- Messages -->
<?php if (!empty($success_message)): ?>
<div class="alert alert-success">
    <i class="fas fa-check-circle"></i> <?php echo $success_message; ?>
    <br><small>Redirecting to campaigns list...</small>
</div>
<?php endif; ?>

<?php if (!empty($error_message)): ?>
<div class="alert alert-danger">
    <i class="fas fa-exclamation-circle"></i> <?php echo $error_message; ?>
</div>
<?php endif; ?>

<!-- Campaign Form -->
<form method="POST" enctype="multipart/form-data">
    <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 1.5rem;">
        
        <!-- Main Content Column -->
        <div>
            <!-- Basic Information -->
            <div class="card">
                <div class="card-header">
                    <h3><i class="fas fa-info-circle"></i> Basic Information</h3>
                </div>
                <div class="card-body">
                    <!-- Title -->
                    <div class="form-group">
                        <label for="title">Campaign Title <span style="color: red;">*</span></label>
                        <input type="text" 
                               id="title" 
                               name="title" 
                               class="form-control" 
                               value="<?php echo htmlspecialchars($form_data['title']); ?>"
                               placeholder="e.g., Building Fund 2024"
                               required>
                    </div>
                    
                    <!-- Slug -->
                    <div class="form-group">
                        <label for="slug">URL Slug</label>
                        <div style="display: flex; align-items: center; gap: 0.5rem;">
                            <span style="color: #666;">/campaign/</span>
                            <input type="text" 
                                   id="slug" 
                                   name="slug" 
                                   class="form-control" 
                                   value="<?php echo htmlspecialchars($form_data['slug']); ?>"
                                   placeholder="building-fund-2024"
                                   style="flex: 1;">
                        </div>
                        <small style="color: #666;">Leave empty to auto-generate from title. Use lowercase letters, numbers, and hyphens only.</small>
                    </div>
                    
                    <!-- Short Description -->
                    <div class="form-group">
                        <label for="short_description">Short Description <span style="color: red;">*</span></label>
                        <textarea id="short_description" 
                                  name="short_description" 
                                  class="form-control" 
                                  rows="2"
                                  maxlength="500"
                                  placeholder="Brief description shown on campaign cards (max 500 characters)"
                                  required><?php echo htmlspecialchars($form_data['short_description']); ?></textarea>
                        <small style="color: #666;"><span id="short_desc_count">0</span>/500 characters</small>
                    </div>
                    
                    <!-- Full Description -->
                    <div class="form-group">
                        <label for="description">Full Description</label>
                        <textarea id="description" 
                                  name="description" 
                                  class="form-control" 
                                  rows="8"
                                  placeholder="Detailed description shown on the campaign page. You can include the story behind the campaign, how funds will be used, etc."><?php echo htmlspecialchars($form_data['description']); ?></textarea>
                        <small style="color: #666;">This will be displayed on the campaign detail page.</small>
                    </div>
                </div>
            </div>
            
            <!-- Donation Settings -->
            <div class="card">
                <div class="card-header">
                    <h3><i class="fas fa-dollar-sign"></i> Donation Settings</h3>
                </div>
                <div class="card-body">
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                        <!-- Goal Amount -->
                        <div class="form-group">
                            <label for="goal_amount">Goal Amount ($)</label>
                            <input type="number" 
                                   id="goal_amount" 
                                   name="goal_amount" 
                                   class="form-control" 
                                   min="0" 
                                   step="0.01"
                                   value="<?php echo htmlspecialchars($form_data['goal_amount']); ?>"
                                   placeholder="0.00">
                            <small style="color: #666;">Leave empty or 0 for open-ended campaigns.</small>
                        </div>
                        
                        <!-- Minimum Amount -->
                        <div class="form-group">
                            <label for="minimum_amount">Minimum Donation ($)</label>
                            <input type="number" 
                                   id="minimum_amount" 
                                   name="minimum_amount" 
                                   class="form-control" 
                                   min="0" 
                                   step="0.01"
                                   value="<?php echo htmlspecialchars($form_data['minimum_amount']); ?>">
                        </div>
                    </div>
                    
                    <!-- Suggested Amounts -->
                    <div class="form-group">
                        <label for="suggested_amounts">Suggested Amounts</label>
                        <input type="text" 
                               id="suggested_amounts" 
                               name="suggested_amounts" 
                               class="form-control" 
                               value="<?php echo htmlspecialchars($form_data['suggested_amounts']); ?>"
                               placeholder="25,50,100,250,500">
                        <small style="color: #666;">Comma-separated values. These will appear as quick-select buttons.</small>
                    </div>
                    
                    <!-- Donation Type -->
                    <div class="form-group">
                        <label for="donation_type">Donation Category</label>
                        <select id="donation_type" name="donation_type" class="form-control">
                            <?php foreach ($donation_types as $type): ?>
                            <option value="<?php echo htmlspecialchars($type); ?>" 
                                    <?php echo $form_data['donation_type'] === $type ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($type); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Sidebar Column -->
        <div>
            <!-- Publish Settings -->
            <div class="card">
                <div class="card-header">
                    <h3><i class="fas fa-cog"></i> Publish Settings</h3>
                </div>
                <div class="card-body">
                    <!-- Status Toggles -->
                    <div class="form-group">
                        <label style="display: flex; align-items: center; gap: 0.5rem; cursor: pointer;">
                            <input type="checkbox" 
                                   name="is_active" 
                                   value="1"
                                   <?php echo $form_data['is_active'] ? 'checked' : ''; ?>>
                            <span>Active (Accepting Donations)</span>
                        </label>
                    </div>
                    
                    <div class="form-group">
                        <label style="display: flex; align-items: center; gap: 0.5rem; cursor: pointer;">
                            <input type="checkbox" 
                                   name="is_featured" 
                                   value="1"
                                   <?php echo $form_data['is_featured'] ? 'checked' : ''; ?>>
                            <span>Featured Campaign</span>
                        </label>
                        <small style="color: #666; display: block; margin-left: 1.5rem;">Featured campaigns appear first and may be highlighted.</small>
                    </div>
                    
                    <hr style="margin: 1rem 0;">
                    
                    <!-- Display Options -->
                    <div class="form-group">
                        <label style="display: flex; align-items: center; gap: 0.5rem; cursor: pointer;">
                            <input type="checkbox" 
                                   name="show_progress_bar" 
                                   value="1"
                                   <?php echo $form_data['show_progress_bar'] ? 'checked' : ''; ?>>
                            <span>Show Progress Bar</span>
                        </label>
                    </div>
                    
                    <div class="form-group">
                        <label style="display: flex; align-items: center; gap: 0.5rem; cursor: pointer;">
                            <input type="checkbox" 
                                   name="show_donor_count" 
                                   value="1"
                                   <?php echo $form_data['show_donor_count'] ? 'checked' : ''; ?>>
                            <span>Show Donor Count</span>
                        </label>
                    </div>
                </div>
            </div>
            
            <!-- Schedule -->
            <div class="card">
                <div class="card-header">
                    <h3><i class="fas fa-calendar-alt"></i> Schedule</h3>
                </div>
                <div class="card-body">
                    <div class="form-group">
                        <label for="start_date">Start Date</label>
                        <input type="date" 
                               id="start_date" 
                               name="start_date" 
                               class="form-control"
                               value="<?php echo htmlspecialchars($form_data['start_date']); ?>">
                        <small style="color: #666;">Leave empty to start immediately.</small>
                    </div>
                    
                    <div class="form-group">
                        <label for="end_date">End Date</label>
                        <input type="date" 
                               id="end_date" 
                               name="end_date" 
                               class="form-control"
                               value="<?php echo htmlspecialchars($form_data['end_date']); ?>">
                        <small style="color: #666;">Leave empty for ongoing campaigns.</small>
                    </div>
                </div>
            </div>
            
            <!-- Featured Image -->
            <div class="card">
                <div class="card-header">
                    <h3><i class="fas fa-image"></i> Featured Image</h3>
                </div>
                <div class="card-body">
                    <div class="form-group">
                        <input type="file" 
                               id="featured_image" 
                               name="featured_image" 
                               class="form-control"
                               accept="image/jpeg,image/png,image/gif,image/webp">
                        <small style="color: #666;">Recommended: 800x400px. Max 5MB. JPG, PNG, GIF, or WEBP.</small>
                    </div>
                    
                    <!-- Image Preview -->
                    <div id="image_preview" style="display: none; margin-top: 1rem;">
                        <img id="preview_img" src="" alt="Preview" style="max-width: 100%; border-radius: 4px;">
                    </div>
                </div>
            </div>
            
            <!-- Submit Button -->
            <div class="card">
                <div class="card-body">
                    <button type="submit" class="btn btn-primary" style="width: 100%; padding: 1rem;">
                        <i class="fas fa-save"></i> Create Campaign
                    </button>
                </div>
            </div>
        </div>
    </div>
</form>

<!-- JavaScript for form enhancements -->
<script>
// Auto-generate slug from title
document.getElementById('title').addEventListener('input', function() {
    const slugField = document.getElementById('slug');
    if (slugField.value === '' || slugField.dataset.autoGenerated === 'true') {
        slugField.value = this.value
            .toLowerCase()
            .replace(/[^a-z0-9]+/g, '-')
            .replace(/^-|-$/g, '');
        slugField.dataset.autoGenerated = 'true';
    }
});

document.getElementById('slug').addEventListener('input', function() {
    this.dataset.autoGenerated = 'false';
});

// Character counter for short description
document.getElementById('short_description').addEventListener('input', function() {
    document.getElementById('short_desc_count').textContent = this.value.length;
});

// Initialize counter
document.getElementById('short_desc_count').textContent = 
    document.getElementById('short_description').value.length;

// Image preview
document.getElementById('featured_image').addEventListener('change', function() {
    const preview = document.getElementById('image_preview');
    const previewImg = document.getElementById('preview_img');
    
    if (this.files && this.files[0]) {
        const reader = new FileReader();
        reader.onload = function(e) {
            previewImg.src = e.target.result;
            preview.style.display = 'block';
        };
        reader.readAsDataURL(this.files[0]);
    } else {
        preview.style.display = 'none';
    }
});
</script>

<?php include 'includes/admin-footer.php'; ?>