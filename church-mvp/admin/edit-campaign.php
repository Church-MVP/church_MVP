<?php
/**
 * Edit Donation Campaign
 * 
 * Admin page to edit an existing donation campaign
 * Shows current progress and recent donations
 */

// Set page title
$page_title = 'Edit Campaign';

// Include admin header (handles session, db connection, auth)
include 'includes/admin-header.php';

/**
 * ===============================
 * Get Campaign ID
 * ===============================
 */
$campaign_id = isset($_GET['id']) ? (int) $_GET['id'] : 0;

if ($campaign_id <= 0) {
    header('Location: manage-donations.php');
    exit;
}

/**
 * ===============================
 * Fetch Campaign Data
 * ===============================
 */
$stmt = $pdo->prepare("SELECT * FROM donation_campaigns WHERE id = ?");
$stmt->execute([$campaign_id]);
$campaign = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$campaign) {
    header('Location: manage-donations.php');
    exit;
}

/**
 * ===============================
 * Fetch Campaign Statistics
 * ===============================
 */
$stmt = $pdo->prepare("
    SELECT 
        COUNT(*) as donation_count,
        COALESCE(SUM(amount), 0) as total_raised
    FROM donations 
    WHERE campaign_id = ?
");
$stmt->execute([$campaign_id]);
$stats = $stmt->fetch(PDO::FETCH_ASSOC);

/**
 * ===============================
 * Fetch Recent Donations
 * ===============================
 */
$stmt = $pdo->prepare("
    SELECT * FROM donations 
    WHERE campaign_id = ? 
    ORDER BY donation_date DESC 
    LIMIT 10
");
$stmt->execute([$campaign_id]);
$recent_donations = $stmt->fetchAll(PDO::FETCH_ASSOC);

/**
 * ===============================
 * Initialize Variables
 * ===============================
 */
$success_message = '';
$error_message = '';

// Form field values (pre-populated from database)
$form_data = [
    'title' => $campaign['title'],
    'slug' => $campaign['slug'],
    'short_description' => $campaign['short_description'],
    'description' => $campaign['description'],
    'goal_amount' => $campaign['goal_amount'],
    'donation_type' => $campaign['donation_type'],
    'start_date' => $campaign['start_date'],
    'end_date' => $campaign['end_date'],
    'is_active' => $campaign['is_active'],
    'is_featured' => $campaign['is_featured'],
    'show_progress_bar' => $campaign['show_progress_bar'],
    'show_donor_count' => $campaign['show_donor_count'],
    'minimum_amount' => $campaign['minimum_amount'],
    'suggested_amounts' => $campaign['suggested_amounts'],
    'featured_image' => $campaign['featured_image']
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
    
    // Validation
    $errors = [];
    
    if (empty($form_data['title'])) {
        $errors[] = 'Campaign title is required.';
    }
    
    if (empty($form_data['slug'])) {
        $errors[] = 'Campaign slug is required.';
    } else {
        // Check for unique slug (excluding current campaign)
        $stmt = $pdo->prepare("SELECT id FROM donation_campaigns WHERE slug = ? AND id != ?");
        $stmt->execute([$form_data['slug'], $campaign_id]);
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
    $featured_image = $form_data['featured_image']; // Keep existing by default
    
    // Check if user wants to remove image
    if (isset($_POST['remove_image']) && $_POST['remove_image'] == '1') {
        // Delete old image file
        if (!empty($featured_image)) {
            $old_image_path = '../uploads/campaigns/' . $featured_image;
            if (file_exists($old_image_path)) {
                unlink($old_image_path);
            }
        }
        $featured_image = '';
    }
    // Check for new image upload
    elseif (isset($_FILES['featured_image']) && $_FILES['featured_image']['error'] === UPLOAD_ERR_OK) {
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
            
            // Delete old image if exists
            if (!empty($form_data['featured_image'])) {
                $old_image_path = $upload_dir . $form_data['featured_image'];
                if (file_exists($old_image_path)) {
                    unlink($old_image_path);
                }
            }
            
            // Generate unique filename
            $extension = pathinfo($_FILES['featured_image']['name'], PATHINFO_EXTENSION);
            $featured_image = $form_data['slug'] . '-' . time() . '.' . $extension;
            $upload_path = $upload_dir . $featured_image;
            
            if (!move_uploaded_file($_FILES['featured_image']['tmp_name'], $upload_path)) {
                $errors[] = 'Failed to upload image. Please try again.';
                $featured_image = $form_data['featured_image']; // Revert to old
            }
        }
    }
    
    // If no errors, update database
    if (empty($errors)) {
        try {
            $stmt = $pdo->prepare("
                UPDATE donation_campaigns SET
                    title = ?,
                    slug = ?,
                    short_description = ?,
                    description = ?,
                    featured_image = ?,
                    goal_amount = ?,
                    donation_type = ?,
                    start_date = ?,
                    end_date = ?,
                    is_active = ?,
                    is_featured = ?,
                    show_progress_bar = ?,
                    show_donor_count = ?,
                    minimum_amount = ?,
                    suggested_amounts = ?,
                    updated_at = NOW()
                WHERE id = ?
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
                $campaign_id
            ]);
            
            $success_message = 'Campaign updated successfully!';
            
            // Update local form data with new image
            $form_data['featured_image'] = $featured_image;
            
        } catch (PDOException $e) {
            $error_message = 'Database error: Failed to update campaign. Please try again.';
        }
    } else {
        $error_message = implode('<br>', $errors);
    }
}

/**
 * ===============================
 * Handle Manual Donation Addition
 * ===============================
 */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_offline_donation'])) {
    $offline_name = sanitize_input($_POST['offline_donor_name'] ?? '');
    $offline_email = sanitize_input($_POST['offline_donor_email'] ?? '');
    $offline_amount = floatval($_POST['offline_amount'] ?? 0);
    $offline_method = sanitize_input($_POST['offline_method'] ?? 'Cash');
    $offline_note = sanitize_input($_POST['offline_note'] ?? '');
    
    if (!empty($offline_name) && $offline_amount > 0) {
        try {
            // Insert offline donation
            $stmt = $pdo->prepare("
                INSERT INTO donations (donor_name, donor_email, amount, donation_type, payment_method, message, campaign_id)
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $offline_name,
                $offline_email,
                $offline_amount,
                $campaign['donation_type'],
                $offline_method,
                $offline_note,
                $campaign_id
            ]);
            
            // Update campaign current_amount
            $stmt = $pdo->prepare("
                UPDATE donation_campaigns 
                SET current_amount = current_amount + ? 
                WHERE id = ?
            ");
            $stmt->execute([$offline_amount, $campaign_id]);
            
            $success_message = 'Offline donation recorded successfully!';
            
            // Refresh stats
            $stmt = $pdo->prepare("
                SELECT COUNT(*) as donation_count, COALESCE(SUM(amount), 0) as total_raised
                FROM donations WHERE campaign_id = ?
            ");
            $stmt->execute([$campaign_id]);
            $stats = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // Refresh recent donations
            $stmt = $pdo->prepare("
                SELECT * FROM donations WHERE campaign_id = ? ORDER BY donation_date DESC LIMIT 10
            ");
            $stmt->execute([$campaign_id]);
            $recent_donations = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
        } catch (PDOException $e) {
            $error_message = 'Failed to record offline donation.';
        }
    } else {
        $error_message = 'Please provide donor name and amount for offline donation.';
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

/**
 * ===============================
 * Calculate Progress
 * ===============================
 */
$progress = 0;
if ($campaign['goal_amount'] > 0) {
    $progress = min(100, ($campaign['current_amount'] / $campaign['goal_amount']) * 100);
}
?>

<!-- Page Header -->
<div class="page-header">
    <h2><i class="fas fa-edit"></i> Edit Campaign</h2>
    <p>Editing: <?php echo htmlspecialchars($campaign['title']); ?></p>
</div>

<!-- Breadcrumb -->
<div style="margin-bottom: 1.5rem; display: flex; gap: 0.5rem;">
    <a href="manage-donations.php" class="btn btn-secondary">
        <i class="fas fa-arrow-left"></i> Back to Campaigns
    </a>
    <a href="../campaign.php?slug=<?php echo htmlspecialchars($campaign['slug']); ?>" class="btn btn-secondary" target="_blank">
        <i class="fas fa-external-link-alt"></i> View Live Page
    </a>
</div>

<!-- Messages -->
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

<!-- Campaign Progress Stats -->
<div class="dashboard-cards">
    <div class="stat-card">
        <div class="stat-card-header">
            <h3>Total Raised</h3>
            <div class="stat-icon green">
                <i class="fas fa-dollar-sign"></i>
            </div>
        </div>
        <div class="stat-number">$<?php echo number_format($campaign['current_amount'], 2); ?></div>
        <?php if ($campaign['goal_amount'] > 0): ?>
        <div class="stat-label">of $<?php echo number_format($campaign['goal_amount'], 2); ?> goal</div>
        <?php else: ?>
        <div class="stat-label">No goal set</div>
        <?php endif; ?>
    </div>
    
    <div class="stat-card">
        <div class="stat-card-header">
            <h3>Progress</h3>
            <div class="stat-icon blue">
                <i class="fas fa-chart-pie"></i>
            </div>
        </div>
        <div class="stat-number"><?php echo number_format($progress, 1); ?>%</div>
        <div style="background: #e0e5eb; height: 8px; border-radius: 4px; margin-top: 0.5rem;">
            <div style="width: <?php echo $progress; ?>%; background: <?php echo $progress >= 100 ? '#28a745' : 'var(--primary-light)'; ?>; height: 100%; border-radius: 4px;"></div>
        </div>
    </div>
    
    <div class="stat-card">
        <div class="stat-card-header">
            <h3>Donations</h3>
            <div class="stat-icon teal">
                <i class="fas fa-hand-holding-heart"></i>
            </div>
        </div>
        <div class="stat-number"><?php echo number_format($stats['donation_count']); ?></div>
        <div class="stat-label">Total donations received</div>
    </div>
    
    <div class="stat-card">
        <div class="stat-card-header">
            <h3>Average</h3>
            <div class="stat-icon orange">
                <i class="fas fa-calculator"></i>
            </div>
        </div>
        <div class="stat-number">
            $<?php echo $stats['donation_count'] > 0 ? number_format($stats['total_raised'] / $stats['donation_count'], 2) : '0.00'; ?>
        </div>
        <div class="stat-label">Per donation</div>
    </div>
</div>

<!-- Main Content Grid -->
<div style="display: grid; grid-template-columns: 2fr 1fr; gap: 1.5rem;">
    
    <!-- Left Column - Edit Form -->
    <div>
        <form method="POST" enctype="multipart/form-data">
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
                               required>
                    </div>
                    
                    <!-- Slug -->
                    <div class="form-group">
                        <label for="slug">URL Slug <span style="color: red;">*</span></label>
                        <div style="display: flex; align-items: center; gap: 0.5rem;">
                            <span style="color: #666;">/campaign/</span>
                            <input type="text" 
                                   id="slug" 
                                   name="slug" 
                                   class="form-control" 
                                   value="<?php echo htmlspecialchars($form_data['slug']); ?>"
                                   style="flex: 1;"
                                   required>
                        </div>
                        <small style="color: #666;">Changing the slug will change the campaign URL.</small>
                    </div>
                    
                    <!-- Short Description -->
                    <div class="form-group">
                        <label for="short_description">Short Description <span style="color: red;">*</span></label>
                        <textarea id="short_description" 
                                  name="short_description" 
                                  class="form-control" 
                                  rows="2"
                                  maxlength="500"
                                  required><?php echo htmlspecialchars($form_data['short_description']); ?></textarea>
                        <small style="color: #666;"><span id="short_desc_count">0</span>/500 characters</small>
                    </div>
                    
                    <!-- Full Description -->
                    <div class="form-group">
                        <label for="description">Full Description</label>
                        <textarea id="description" 
                                  name="description" 
                                  class="form-control" 
                                  rows="8"><?php echo htmlspecialchars($form_data['description']); ?></textarea>
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
                                   value="<?php echo htmlspecialchars($form_data['goal_amount']); ?>">
                            <small style="color: #666;">Leave empty or 0 for open-ended.</small>
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
                               value="<?php echo htmlspecialchars($form_data['suggested_amounts']); ?>">
                        <small style="color: #666;">Comma-separated values.</small>
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
            
            <!-- Submit Button (in form) -->
            <button type="submit" class="btn btn-primary" style="width: 100%; padding: 1rem; margin-bottom: 1.5rem;">
                <i class="fas fa-save"></i> Save Changes
            </button>
        </form>
    </div>
    
    <!-- Right Column - Settings & Info -->
    <div>
        <!-- Publish Settings -->
        <form method="POST" enctype="multipart/form-data">
            <!-- Hidden fields to preserve other data -->
            <input type="hidden" name="title" value="<?php echo htmlspecialchars($form_data['title']); ?>">
            <input type="hidden" name="slug" value="<?php echo htmlspecialchars($form_data['slug']); ?>">
            <input type="hidden" name="short_description" value="<?php echo htmlspecialchars($form_data['short_description']); ?>">
            <input type="hidden" name="description" value="<?php echo htmlspecialchars($form_data['description']); ?>">
            <input type="hidden" name="goal_amount" value="<?php echo htmlspecialchars($form_data['goal_amount']); ?>">
            <input type="hidden" name="minimum_amount" value="<?php echo htmlspecialchars($form_data['minimum_amount']); ?>">
            <input type="hidden" name="suggested_amounts" value="<?php echo htmlspecialchars($form_data['suggested_amounts']); ?>">
            <input type="hidden" name="donation_type" value="<?php echo htmlspecialchars($form_data['donation_type']); ?>">
            
            <div class="card">
                <div class="card-header">
                    <h3><i class="fas fa-cog"></i> Publish Settings</h3>
                </div>
                <div class="card-body">
                    <div class="form-group">
                        <label style="display: flex; align-items: center; gap: 0.5rem; cursor: pointer;">
                            <input type="checkbox" name="is_active" value="1"
                                   <?php echo $form_data['is_active'] ? 'checked' : ''; ?>>
                            <span>Active (Accepting Donations)</span>
                        </label>
                    </div>
                    
                    <div class="form-group">
                        <label style="display: flex; align-items: center; gap: 0.5rem; cursor: pointer;">
                            <input type="checkbox" name="is_featured" value="1"
                                   <?php echo $form_data['is_featured'] ? 'checked' : ''; ?>>
                            <span>Featured Campaign</span>
                        </label>
                    </div>
                    
                    <hr style="margin: 1rem 0;">
                    
                    <div class="form-group">
                        <label style="display: flex; align-items: center; gap: 0.5rem; cursor: pointer;">
                            <input type="checkbox" name="show_progress_bar" value="1"
                                   <?php echo $form_data['show_progress_bar'] ? 'checked' : ''; ?>>
                            <span>Show Progress Bar</span>
                        </label>
                    </div>
                    
                    <div class="form-group">
                        <label style="display: flex; align-items: center; gap: 0.5rem; cursor: pointer;">
                            <input type="checkbox" name="show_donor_count" value="1"
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
                        <input type="date" id="start_date" name="start_date" class="form-control"
                               value="<?php echo htmlspecialchars($form_data['start_date']); ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="end_date">End Date</label>
                        <input type="date" id="end_date" name="end_date" class="form-control"
                               value="<?php echo htmlspecialchars($form_data['end_date']); ?>">
                    </div>
                </div>
            </div>
            
            <!-- Featured Image -->
            <div class="card">
                <div class="card-header">
                    <h3><i class="fas fa-image"></i> Featured Image</h3>
                </div>
                <div class="card-body">
                    <?php if (!empty($form_data['featured_image'])): ?>
                    <div style="margin-bottom: 1rem;">
                        <img src="../uploads/campaigns/<?php echo htmlspecialchars($form_data['featured_image']); ?>" 
                             alt="Current Image" 
                             style="max-width: 100%; border-radius: 4px;">
                        <div style="margin-top: 0.5rem;">
                            <label style="display: flex; align-items: center; gap: 0.5rem; cursor: pointer; color: #dc3545;">
                                <input type="checkbox" name="remove_image" value="1">
                                <span>Remove current image</span>
                            </label>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <div class="form-group">
                        <label for="featured_image"><?php echo !empty($form_data['featured_image']) ? 'Replace Image' : 'Upload Image'; ?></label>
                        <input type="file" id="featured_image" name="featured_image" class="form-control"
                               accept="image/jpeg,image/png,image/gif,image/webp">
                        <small style="color: #666;">Max 5MB. JPG, PNG, GIF, or WEBP.</small>
                    </div>
                    
                    <div id="image_preview" style="display: none; margin-top: 1rem;">
                        <img id="preview_img" src="" alt="Preview" style="max-width: 100%; border-radius: 4px;">
                    </div>
                </div>
            </div>
            
            <button type="submit" class="btn btn-primary" style="width: 100%; padding: 1rem;">
                <i class="fas fa-save"></i> Save Changes
            </button>
        </form>
        
        <!-- Add Offline Donation -->
        <div class="card" style="margin-top: 1.5rem;">
            <div class="card-header">
                <h3><i class="fas fa-plus-circle"></i> Record Offline Donation</h3>
            </div>
            <div class="card-body">
                <form method="POST">
                    <input type="hidden" name="add_offline_donation" value="1">
                    
                    <div class="form-group">
                        <label for="offline_donor_name">Donor Name <span style="color: red;">*</span></label>
                        <input type="text" id="offline_donor_name" name="offline_donor_name" class="form-control" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="offline_donor_email">Donor Email</label>
                        <input type="email" id="offline_donor_email" name="offline_donor_email" class="form-control">
                    </div>
                    
                    <div class="form-group">
                        <label for="offline_amount">Amount ($) <span style="color: red;">*</span></label>
                        <input type="number" id="offline_amount" name="offline_amount" class="form-control" min="0.01" step="0.01" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="offline_method">Payment Method</label>
                        <select id="offline_method" name="offline_method" class="form-control">
                            <option value="Cash">Cash</option>
                            <option value="Check">Check</option>
                            <option value="Bank Transfer">Bank Transfer</option>
                            <option value="Other">Other</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="offline_note">Note</label>
                        <textarea id="offline_note" name="offline_note" class="form-control" rows="2"></textarea>
                    </div>
                    
                    <button type="submit" class="btn btn-success" style="width: 100%;">
                        <i class="fas fa-plus"></i> Add Donation
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Recent Donations -->
<div class="card" style="margin-top: 1.5rem;">
    <div class="card-header" style="display: flex; justify-content: space-between; align-items: center;">
        <h3><i class="fas fa-list"></i> Recent Donations</h3>
        <a href="donations.php?campaign=<?php echo $campaign_id; ?>" class="btn btn-sm btn-secondary">
            View All <i class="fas fa-arrow-right"></i>
        </a>
    </div>
    <div class="card-body">
        <?php if (!empty($recent_donations)): ?>
        <div class="table-responsive">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>Donor</th>
                        <th>Amount</th>
                        <th>Method</th>
                        <th>Date</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($recent_donations as $donation): ?>
                    <tr>
                        <td>
                            <strong><?php echo htmlspecialchars($donation['donor_name']); ?></strong>
                            <?php if (!empty($donation['donor_email'])): ?>
                            <br><small style="color: #666;"><?php echo htmlspecialchars($donation['donor_email']); ?></small>
                            <?php endif; ?>
                        </td>
                        <td><strong style="color: #28a745;">$<?php echo number_format($donation['amount'], 2); ?></strong></td>
                        <td><span class="badge badge-secondary"><?php echo htmlspecialchars($donation['payment_method']); ?></span></td>
                        <td><?php echo date('M d, Y', strtotime($donation['donation_date'])); ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php else: ?>
        <p style="text-align: center; color: #666; padding: 2rem;">No donations yet for this campaign.</p>
        <?php endif; ?>
    </div>
</div>

<!-- JavaScript -->
<script>
// Character counter for short description
document.getElementById('short_description').addEventListener('input', function() {
    document.getElementById('short_desc_count').textContent = this.value.length;
});
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