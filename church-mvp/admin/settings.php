<?php
/**
 * Site Settings - Enhanced with Tabs
 * 
 * Each section manages its own content and images independently
 */

// Set page title
$page_title = 'Site Settings';

// Include admin header
include 'includes/admin-header.php';

// Check permission
require_permission('view_settings');

// Initialize messages
$success_message = '';
$error_message = '';

// Get active tab
$active_tab = isset($_GET['tab']) ? $_GET['tab'] : 'general';

// Fetch current settings
$stmt = $pdo->query("SELECT * FROM site_settings");
$settings = [];
while ($row = $stmt->fetch()) {
    $settings[$row['setting_key']] = $row['setting_value'];
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST' && has_permission('edit_settings')) {
    try {
        // Handle image uploads
        $upload_dir = '../assets/images/';
        
        if (!empty($_FILES)) {
            foreach ($_FILES as $field_name => $file) {
                if ($file['error'] == UPLOAD_ERR_OK) {
                    $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
                    
                    if (in_array($file['type'], $allowed_types)) {
                        $file_extension = pathinfo($file['name'], PATHINFO_EXTENSION);
                        $new_filename = $field_name . '_' . time() . '.' . $file_extension;
                        $upload_path = $upload_dir . $new_filename;
                        
                        if (move_uploaded_file($file['tmp_name'], $upload_path)) {
                            $image_path = 'assets/images/' . $new_filename;
                            
                            // Save image path to database
                            $stmt = $pdo->prepare("SELECT COUNT(*) FROM site_settings WHERE setting_key = ?");
                            $stmt->execute([$field_name]);
                            $exists = $stmt->fetchColumn();
                            
                            if ($exists) {
                                $stmt = $pdo->prepare("UPDATE site_settings SET setting_value = ? WHERE setting_key = ?");
                                $stmt->execute([$image_path, $field_name]);
                            } else {
                                $stmt = $pdo->prepare("INSERT INTO site_settings (setting_key, setting_value) VALUES (?, ?)");
                                $stmt->execute([$field_name, $image_path]);
                            }
                        }
                    }
                }
            }
        }
        
        // Update text fields
        foreach ($_POST as $key => $value) {
            if ($key !== 'submit') {
                $sanitized_value = sanitize_input($value);
                
                $stmt = $pdo->prepare("SELECT COUNT(*) FROM site_settings WHERE setting_key = ?");
                $stmt->execute([$key]);
                $exists = $stmt->fetchColumn();
                
                if ($exists) {
                    $stmt = $pdo->prepare("UPDATE site_settings SET setting_value = ? WHERE setting_key = ?");
                    $stmt->execute([$sanitized_value, $key]);
                } else {
                    $stmt = $pdo->prepare("INSERT INTO site_settings (setting_key, setting_value) VALUES (?, ?)");
                    $stmt->execute([$key, $sanitized_value]);
                }
            }
        }
        
        $success_message = 'Settings updated successfully!';
        
        // Refresh settings
        $stmt = $pdo->query("SELECT * FROM site_settings");
        $settings = [];
        while ($row = $stmt->fetch()) {
            $settings[$row['setting_key']] = $row['setting_value'];
        }
    } catch (Exception $e) {
        $error_message = 'Error updating settings. Please try again.';
    }
}
?>

<!-- Page Header -->
<div class="page-header">
    <h2><i class="fas fa-cog"></i> Site Settings</h2>
    <p>Manage site-wide settings and content</p>
</div>

<!-- Success/Error Messages -->
<?php if (!empty($success_message)): ?>
<div class="alert alert-success">
    <i class="fas fa-check-circle"></i> <?php echo $success_message; ?>
    <a href="../index.php" target="_blank" style="margin-left: 1rem;">View website</a>
</div>
<?php endif; ?>

<?php if (!empty($error_message)): ?>
<div class="alert alert-danger">
    <i class="fas fa-exclamation-circle"></i> <?php echo $error_message; ?>
</div>
<?php endif; ?>

<!-- Settings Tabs -->
<div class="card">
    <div class="card-header" style="padding: 0;">
        <ul class="settings-tabs" style="display: flex; list-style: none; margin: 0; padding: 0; border-bottom: 2px solid #e0e5eb;">
            <li style="flex: 1;">
                <a href="?tab=general" class="tab-link <?php echo $active_tab == 'general' ? 'active' : ''; ?>">
                    <i class="fas fa-info-circle"></i> General
                </a>
            </li>
            <li style="flex: 1;">
                <a href="?tab=homepage" class="tab-link <?php echo $active_tab == 'homepage' ? 'active' : ''; ?>">
                    <i class="fas fa-home"></i> Homepage
                </a>
            </li>
            <li style="flex: 1;">
                <a href="?tab=about" class="tab-link <?php echo $active_tab == 'about' ? 'active' : ''; ?>">
                    <i class="fas fa-book-open"></i> About Page
                </a>
            </li>
            <li style="flex: 1;">
                <a href="?tab=contact" class="tab-link <?php echo $active_tab == 'contact' ? 'active' : ''; ?>">
                    <i class="fas fa-envelope"></i> Contact Us
                </a>
            </li>
            <li style="flex: 1;">
                <a href="?tab=livestream" class="tab-link <?php echo $active_tab == 'livestream' ? 'active' : ''; ?>">
                    <i class="fas fa-video"></i> Live Stream
                </a>
            </li>
        </ul>
    </div>
</div>

<style>
.tab-link {
    display: block;
    padding: 1rem;
    text-align: center;
    text-decoration: none;
    color: #666;
    border-bottom: 3px solid transparent;
    transition: all 0.3s;
}
.tab-link.active {
    color: var(--admin-accent) !important;
    border-bottom-color: var(--admin-accent) !important;
    font-weight: 600;
}
.tab-link:hover {
    color: var(--admin-accent);
    background: rgba(122, 156, 198, 0.1);
}
.office-hours-row {
    display: grid;
    grid-template-columns: 1fr 1fr 1fr auto;
    gap: 1rem;
    align-items: end;
    padding: 1rem;
    background: #f8f9fa;
    border-radius: 6px;
    margin-bottom: 0.75rem;
}
.office-hours-row .form-group {
    margin-bottom: 0;
}
.social-link-row {
    display: grid;
    grid-template-columns: 150px 1fr auto;
    gap: 1rem;
    align-items: end;
    padding: 1rem;
    background: #f8f9fa;
    border-radius: 6px;
    margin-bottom: 0.75rem;
}
.social-link-row .form-group {
    margin-bottom: 0;
}
.btn-remove {
    background: #dc3545;
    color: white;
    border: none;
    padding: 0.5rem 0.75rem;
    border-radius: 4px;
    cursor: pointer;
    transition: background 0.3s;
}
.btn-remove:hover {
    background: #c82333;
}
.btn-add {
    background: #28a745;
    color: white;
    border: none;
    padding: 0.75rem 1.5rem;
    border-radius: 4px;
    cursor: pointer;
    transition: background 0.3s;
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
}
.btn-add:hover {
    background: #218838;
}
</style>

<?php if ($active_tab == 'general'): ?>
<!-- ============================================
     GENERAL SETTINGS TAB
     ============================================ -->
<form method="POST" action="?tab=general" enctype="multipart/form-data">
    <div class="card">
        <div class="card-header">
            <h3><i class="fas fa-info-circle"></i> General Information</h3>
        </div>
        <div class="card-body">
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem;">
                <div class="form-group">
                    <label for="site_title">Site Title</label>
                    <input type="text" id="site_title" name="site_title" class="form-control" 
                           value="<?php echo htmlspecialchars($settings['site_title'] ?? 'Christ Mission Ministries Inc'); ?>">
                </div>
                
                <div class="form-group">
                    <label for="church_address">Church Address</label>
                    <input type="text" id="church_address" name="church_address" class="form-control" 
                           value="<?php echo htmlspecialchars($settings['church_address'] ?? ''); ?>">
                </div>
                
                <div class="form-group">
                    <label for="church_phone">Church Phone</label>
                    <input type="tel" id="church_phone" name="church_phone" class="form-control" 
                           value="<?php echo htmlspecialchars($settings['church_phone'] ?? ''); ?>">
                </div>
                
                <div class="form-group">
                    <label for="church_email">Church Email</label>
                    <input type="email" id="church_email" name="church_email" class="form-control" 
                           value="<?php echo htmlspecialchars($settings['church_email'] ?? ''); ?>">
                </div>
            </div>
        </div>
    </div>
    
    <!-- Form Actions -->
    <div style="display: flex; gap: 1rem; padding: 1.5rem; background: white; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.08); margin-top: 1.5rem;">
        <button type="submit" name="submit" class="btn btn-primary">
            <i class="fas fa-save"></i> Save General Settings
        </button>
        <a href="dashboard.php" class="btn btn-secondary">
            <i class="fas fa-times"></i> Cancel
        </a>
        <a href="../index.php" target="_blank" class="btn btn-info" style="margin-left: auto;">
            <i class="fas fa-external-link-alt"></i> Preview Website
        </a>
    </div>
</form>
<?php endif; ?>

<?php if ($active_tab == 'homepage'): ?>
<!-- ============================================
     HOMEPAGE SETTINGS TAB
     ============================================ -->
<form method="POST" action="?tab=homepage" enctype="multipart/form-data">
    <div class="card">
        <div class="card-header">
            <h3><i class="fas fa-image"></i> Hero Section</h3>
        </div>
        <div class="card-body">
            <!-- Hero Background Image -->
            <div class="form-group">
                <label for="hero_background_image">Hero Background Image</label>
                <?php if (!empty($settings['hero_background_image'])): ?>
                <div style="margin-bottom: 1rem;">
                    <img src="../<?php echo htmlspecialchars($settings['hero_background_image']); ?>" 
                         style="max-width: 100%; max-height: 200px; object-fit: cover; border-radius: 4px; border: 1px solid #e0e5eb;">
                    <p style="margin-top: 0.5rem; font-size: 0.85rem; color: #666;">Current background image</p>
                </div>
                <?php endif; ?>
                <input type="file" id="hero_background_image" name="hero_background_image" class="form-control" accept="image/*">
                <small style="color: #666;">Recommended: 1920x1080px. Upload new to replace current.</small>
            </div>
            
            <div class="form-group">
                <label for="hero_title">Hero Title</label>
                <input type="text" id="hero_title" name="hero_title" class="form-control" 
                       value="<?php echo htmlspecialchars($settings['hero_title'] ?? 'Helping You Grow Your Faith'); ?>">
            </div>
            
            <div class="form-group">
                <label for="hero_subtitle">Hero Subtitle</label>
                <input type="text" id="hero_subtitle" name="hero_subtitle" class="form-control" 
                       value="<?php echo htmlspecialchars($settings['hero_subtitle'] ?? '1234 Divi St. | Sundays @ 9 & 11:30am'); ?>">
            </div>
        </div>
    </div>
    
    <div class="card">
        <div class="card-header">
            <h3><i class="fas fa-clock"></i> Service Times</h3>
        </div>
        <div class="card-body">
            <div class="form-group">
                <label for="service_time_1">Service Time 1</label>
                <input type="text" id="service_time_1" name="service_time_1" class="form-control" 
                       value="<?php echo htmlspecialchars($settings['service_time_1'] ?? 'Sunday Morning: 9:00 AM'); ?>">
            </div>
            
            <div class="form-group">
                <label for="service_time_2">Service Time 2</label>
                <input type="text" id="service_time_2" name="service_time_2" class="form-control" 
                       value="<?php echo htmlspecialchars($settings['service_time_2'] ?? 'Sunday Evening: 11:30 AM'); ?>">
            </div>
            
            <div class="form-group">
                <label for="service_time_3">Service Time 3</label>
                <input type="text" id="service_time_3" name="service_time_3" class="form-control" 
                       value="<?php echo htmlspecialchars($settings['service_time_3'] ?? 'Wednesday Prayer: 7:00 PM'); ?>">
            </div>
        </div>
    </div>
    
    <!-- Form Actions -->
    <div style="display: flex; gap: 1rem; padding: 1.5rem; background: white; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.08); margin-top: 1.5rem;">
        <button type="submit" name="submit" class="btn btn-primary">
            <i class="fas fa-save"></i> Save Homepage Settings
        </button>
        <a href="dashboard.php" class="btn btn-secondary">
            <i class="fas fa-times"></i> Cancel
        </a>
        <a href="../index.php" target="_blank" class="btn btn-info" style="margin-left: auto;">
            <i class="fas fa-external-link-alt"></i> Preview Website
        </a>
    </div>
</form>
<?php endif; ?>

<?php if ($active_tab == 'about'): ?>
<!-- ============================================
     ABOUT PAGE SETTINGS TAB
     ============================================ -->
<form method="POST" action="?tab=about" enctype="multipart/form-data">
    <div class="card">
        <div class="card-header">
            <h3><i class="fas fa-image"></i> About Page Banner</h3>
        </div>
        <div class="card-body">
            <div class="form-group">
                <label for="about_banner_image">Banner Image</label>
                <?php if (!empty($settings['about_banner_image'])): ?>
                <div style="margin-bottom: 1rem;">
                    <img src="../<?php echo htmlspecialchars($settings['about_banner_image']); ?>" 
                         style="max-width: 100%; max-height: 200px; object-fit: cover; border-radius: 4px; border: 1px solid #e0e5eb;">
                    <p style="margin-top: 0.5rem; font-size: 0.85rem; color: #666;">Current banner</p>
                </div>
                <?php endif; ?>
                <input type="file" id="about_banner_image" name="about_banner_image" class="form-control" accept="image/*">
                <small style="color: #666;">Recommended: 1920x500px</small>
            </div>
        </div>
    </div>
    
    <div class="card">
        <div class="card-header">
            <h3><i class="fas fa-bullseye"></i> Mission & Vision</h3>
        </div>
        <div class="card-body">
            <div class="form-group">
                <label for="about_mission">Mission Statement</label>
                <textarea id="about_mission" name="about_mission" class="form-control" rows="4" maxlength="500"><?php echo htmlspecialchars($settings['about_mission'] ?? ''); ?></textarea>
                <small style="color: #666;">Your church's mission (max 500 characters)</small>
            </div>
            
            <div class="form-group">
                <label for="about_vision">Vision Statement</label>
                <textarea id="about_vision" name="about_vision" class="form-control" rows="4" maxlength="500"><?php echo htmlspecialchars($settings['about_vision'] ?? ''); ?></textarea>
                <small style="color: #666;">Your church's vision (max 500 characters)</small>
            </div>
            
            <div class="form-group">
                <label for="about_values">Core Values</label>
                <textarea id="about_values" name="about_values" class="form-control" rows="6" maxlength="1000"><?php echo htmlspecialchars($settings['about_values'] ?? ''); ?></textarea>
                <small style="color: #666;">List your values (one per line, max 1000 characters)</small>
            </div>
        </div>
    </div>
    
    <div class="card">
        <div class="card-header">
            <h3><i class="fas fa-user-tie"></i> Leadership Team</h3>
        </div>
        <div class="card-body">
            <h4 style="margin-bottom: 1.5rem; color: var(--admin-primary);">Senior Pastor</h4>
            
            <!-- Pastor Photo -->
            <div class="form-group">
                <label for="pastor_photo">Pastor Photo</label>
                <?php if (!empty($settings['pastor_photo'])): ?>
                <div style="margin-bottom: 1rem;">
                    <img src="../<?php echo htmlspecialchars($settings['pastor_photo']); ?>" 
                         style="width: 150px; height: 150px; object-fit: cover; border-radius: 50%; border: 3px solid #e0e5eb;">
                    <p style="margin-top: 0.5rem; font-size: 0.85rem; color: #666;">Current photo</p>
                </div>
                <?php endif; ?>
                <input type="file" id="pastor_photo" name="pastor_photo" class="form-control" accept="image/*">
                <small style="color: #666;">Recommended: 400x400px square image</small>
            </div>
            
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem;">
                <div class="form-group">
                    <label for="pastor_name">Pastor Name</label>
                    <input type="text" id="pastor_name" name="pastor_name" class="form-control" 
                           value="<?php echo htmlspecialchars($settings['pastor_name'] ?? ''); ?>">
                </div>
                
                <div class="form-group">
                    <label for="pastor_title">Pastor Title</label>
                    <input type="text" id="pastor_title" name="pastor_title" class="form-control" 
                           value="<?php echo htmlspecialchars($settings['pastor_title'] ?? 'Senior Pastor'); ?>">
                </div>
            </div>
            
            <div class="form-group">
                <label for="pastor_bio">Pastor Biography</label>
                <textarea id="pastor_bio" name="pastor_bio" class="form-control" rows="6" maxlength="1000"><?php echo htmlspecialchars($settings['pastor_bio'] ?? ''); ?></textarea>
                <small style="color: #666;">Brief biography (max 1000 characters)</small>
            </div>
            
            <hr style="margin: 2rem 0; border: none; border-top: 2px solid #e0e5eb;">
            
            <h4 style="margin-bottom: 1.5rem; color: var(--admin-primary);">Associate Pastor</h4>
            
            <!-- Associate Pastor Photo -->
            <div class="form-group">
                <label for="associate_pastor_photo">Associate Pastor Photo</label>
                <?php if (!empty($settings['associate_pastor_photo'])): ?>
                <div style="margin-bottom: 1rem;">
                    <img src="../<?php echo htmlspecialchars($settings['associate_pastor_photo']); ?>" 
                         style="width: 150px; height: 150px; object-fit: cover; border-radius: 50%; border: 3px solid #e0e5eb;">
                    <p style="margin-top: 0.5rem; font-size: 0.85rem; color: #666;">Current photo</p>
                </div>
                <?php endif; ?>
                <input type="file" id="associate_pastor_photo" name="associate_pastor_photo" class="form-control" accept="image/*">
                <small style="color: #666;">Recommended: 400x400px square image</small>
            </div>
            
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem;">
                <div class="form-group">
                    <label for="associate_pastor_name">Name</label>
                    <input type="text" id="associate_pastor_name" name="associate_pastor_name" class="form-control" 
                           value="<?php echo htmlspecialchars($settings['associate_pastor_name'] ?? ''); ?>">
                </div>
                
                <div class="form-group">
                    <label for="associate_pastor_title">Title</label>
                    <input type="text" id="associate_pastor_title" name="associate_pastor_title" class="form-control" 
                           value="<?php echo htmlspecialchars($settings['associate_pastor_title'] ?? 'Associate Pastor'); ?>">
                </div>
            </div>
            
            <div class="form-group">
                <label for="associate_pastor_bio">Biography</label>
                <textarea id="associate_pastor_bio" name="associate_pastor_bio" class="form-control" rows="4" maxlength="500"><?php echo htmlspecialchars($settings['associate_pastor_bio'] ?? ''); ?></textarea>
                <small style="color: #666;">Brief biography (max 500 characters)</small>
            </div>
        </div>
    </div>
    
    <div class="card">
        <div class="card-header">
            <h3><i class="fas fa-history"></i> Church History</h3>
        </div>
        <div class="card-body">
            <!-- Church Building Photo -->
            <div class="form-group">
                <label for="church_building_photo">Church Building Photo</label>
                <?php if (!empty($settings['church_building_photo'])): ?>
                <div style="margin-bottom: 1rem;">
                    <img src="../<?php echo htmlspecialchars($settings['church_building_photo']); ?>" 
                         style="max-width: 100%; max-height: 200px; object-fit: cover; border-radius: 4px; border: 1px solid #e0e5eb;">
                    <p style="margin-top: 0.5rem; font-size: 0.85rem; color: #666;">Current photo</p>
                </div>
                <?php endif; ?>
                <input type="file" id="church_building_photo" name="church_building_photo" class="form-control" accept="image/*">
                <small style="color: #666;">Photo of your church building</small>
            </div>
            
            <div class="form-group">
                <label for="church_history">Church History/Story</label>
                <textarea id="church_history" name="church_history" class="form-control" rows="8" maxlength="2000"><?php echo htmlspecialchars($settings['church_history'] ?? ''); ?></textarea>
                <small style="color: #666;">Tell your church's story (max 2000 characters)</small>
            </div>
        </div>
    </div>
    
    <!-- Form Actions -->
    <div style="display: flex; gap: 1rem; padding: 1.5rem; background: white; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.08); margin-top: 1.5rem;">
        <button type="submit" name="submit" class="btn btn-primary">
            <i class="fas fa-save"></i> Save About Page Settings
        </button>
        <a href="dashboard.php" class="btn btn-secondary">
            <i class="fas fa-times"></i> Cancel
        </a>
        <a href="../about.php" target="_blank" class="btn btn-info" style="margin-left: auto;">
            <i class="fas fa-external-link-alt"></i> Preview About Page
        </a>
    </div>
</form>
<?php endif; ?>

<?php if ($active_tab == 'contact'): ?>
<!-- ============================================
     CONTACT US SETTINGS TAB
     ============================================ -->
<form method="POST" action="?tab=contact" enctype="multipart/form-data">
    
    <!-- Contact Page Banner -->
    <div class="card">
        <div class="card-header">
            <h3><i class="fas fa-image"></i> Contact Page Banner</h3>
        </div>
        <div class="card-body">
            <div class="form-group">
                <label for="contact_banner_image">Banner Image</label>
                <?php if (!empty($settings['contact_banner_image'])): ?>
                <div style="margin-bottom: 1rem;">
                    <img src="../<?php echo htmlspecialchars($settings['contact_banner_image']); ?>" 
                         style="max-width: 100%; max-height: 200px; object-fit: cover; border-radius: 4px; border: 1px solid #e0e5eb;">
                    <p style="margin-top: 0.5rem; font-size: 0.85rem; color: #666;">Current banner</p>
                </div>
                <?php endif; ?>
                <input type="file" id="contact_banner_image" name="contact_banner_image" class="form-control" accept="image/*">
                <small style="color: #666;">Recommended: 1920x500px</small>
            </div>
            
            <div class="form-group">
                <label for="contact_page_title">Page Title</label>
                <input type="text" id="contact_page_title" name="contact_page_title" class="form-control" 
                       value="<?php echo htmlspecialchars($settings['contact_page_title'] ?? 'Contact Us'); ?>">
            </div>
            
            <div class="form-group">
                <label for="contact_page_subtitle">Page Subtitle</label>
                <input type="text" id="contact_page_subtitle" name="contact_page_subtitle" class="form-control" 
                       value="<?php echo htmlspecialchars($settings['contact_page_subtitle'] ?? 'We\'d love to hear from you'); ?>">
            </div>
        </div>
    </div>
    
    <!-- Primary Contact Information -->
    <div class="card">
        <div class="card-header">
            <h3><i class="fas fa-address-card"></i> Primary Contact Information</h3>
        </div>
        <div class="card-body">
            <div class="alert alert-info">
                <i class="fas fa-info-circle"></i> 
                This information will be displayed prominently on your Contact Us page.
            </div>
            
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem;">
                <div class="form-group">
                    <label for="contact_address_line1">Address Line 1</label>
                    <input type="text" id="contact_address_line1" name="contact_address_line1" class="form-control" 
                           value="<?php echo htmlspecialchars($settings['contact_address_line1'] ?? ''); ?>"
                           placeholder="123 Church Street">
                </div>
                
                <div class="form-group">
                    <label for="contact_address_line2">Address Line 2</label>
                    <input type="text" id="contact_address_line2" name="contact_address_line2" class="form-control" 
                           value="<?php echo htmlspecialchars($settings['contact_address_line2'] ?? ''); ?>"
                           placeholder="Suite 100 (optional)">
                </div>
                
                <div class="form-group">
                    <label for="contact_city">City</label>
                    <input type="text" id="contact_city" name="contact_city" class="form-control" 
                           value="<?php echo htmlspecialchars($settings['contact_city'] ?? ''); ?>"
                           placeholder="City">
                </div>
                
                <div class="form-group">
                    <label for="contact_state">State/Province</label>
                    <input type="text" id="contact_state" name="contact_state" class="form-control" 
                           value="<?php echo htmlspecialchars($settings['contact_state'] ?? ''); ?>"
                           placeholder="State">
                </div>
                
                <div class="form-group">
                    <label for="contact_zip">ZIP/Postal Code</label>
                    <input type="text" id="contact_zip" name="contact_zip" class="form-control" 
                           value="<?php echo htmlspecialchars($settings['contact_zip'] ?? ''); ?>"
                           placeholder="12345">
                </div>
                
                <div class="form-group">
                    <label for="contact_country">Country</label>
                    <input type="text" id="contact_country" name="contact_country" class="form-control" 
                           value="<?php echo htmlspecialchars($settings['contact_country'] ?? 'United States'); ?>"
                           placeholder="Country">
                </div>
            </div>
            
            <hr style="margin: 2rem 0; border: none; border-top: 2px solid #e0e5eb;">
            
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem;">
                <div class="form-group">
                    <label for="contact_phone_main">Main Phone Number</label>
                    <input type="tel" id="contact_phone_main" name="contact_phone_main" class="form-control" 
                           value="<?php echo htmlspecialchars($settings['contact_phone_main'] ?? ''); ?>"
                           placeholder="(555) 123-4567">
                </div>
                
                <div class="form-group">
                    <label for="contact_phone_secondary">Secondary Phone (Optional)</label>
                    <input type="tel" id="contact_phone_secondary" name="contact_phone_secondary" class="form-control" 
                           value="<?php echo htmlspecialchars($settings['contact_phone_secondary'] ?? ''); ?>"
                           placeholder="(555) 987-6543">
                </div>
                
                <div class="form-group">
                    <label for="contact_email_main">Main Email Address</label>
                    <input type="email" id="contact_email_main" name="contact_email_main" class="form-control" 
                           value="<?php echo htmlspecialchars($settings['contact_email_main'] ?? ''); ?>"
                           placeholder="info@church.com">
                </div>
                
                <div class="form-group">
                    <label for="contact_email_pastor">Pastor's Email (Optional)</label>
                    <input type="email" id="contact_email_pastor" name="contact_email_pastor" class="form-control" 
                           value="<?php echo htmlspecialchars($settings['contact_email_pastor'] ?? ''); ?>"
                           placeholder="pastor@church.com">
                </div>
                
                <div class="form-group">
                    <label for="contact_email_prayer">Prayer Requests Email (Optional)</label>
                    <input type="email" id="contact_email_prayer" name="contact_email_prayer" class="form-control" 
                           value="<?php echo htmlspecialchars($settings['contact_email_prayer'] ?? ''); ?>"
                           placeholder="prayer@church.com">
                </div>
                
                <div class="form-group">
                    <label for="contact_fax">Fax Number (Optional)</label>
                    <input type="tel" id="contact_fax" name="contact_fax" class="form-control" 
                           value="<?php echo htmlspecialchars($settings['contact_fax'] ?? ''); ?>"
                           placeholder="(555) 123-4568">
                </div>
            </div>
        </div>
    </div>
    
    <!-- Office Hours -->
    <div class="card">
        <div class="card-header">
            <h3><i class="fas fa-clock"></i> Office Hours</h3>
        </div>
        <div class="card-body">
            <div class="alert alert-info">
                <i class="fas fa-info-circle"></i> 
                Specify when your church office is open for visitors and calls.
            </div>
            
            <div id="office-hours-container">
                <?php 
                // Parse existing office hours from JSON or set defaults
                $office_hours = [];
                if (!empty($settings['contact_office_hours'])) {
                    $office_hours = json_decode($settings['contact_office_hours'], true) ?? [];
                }
                if (empty($office_hours)) {
                    $office_hours = [
                        ['day' => 'Monday - Friday', 'open' => '9:00 AM', 'close' => '5:00 PM'],
                        ['day' => 'Saturday', 'open' => '10:00 AM', 'close' => '2:00 PM'],
                        ['day' => 'Sunday', 'open' => 'Closed', 'close' => '']
                    ];
                }
                foreach ($office_hours as $index => $hours):
                ?>
                <div class="office-hours-row" data-index="<?php echo $index; ?>">
                    <div class="form-group">
                        <label>Day(s)</label>
                        <input type="text" name="office_hours_day[]" class="form-control" 
                               value="<?php echo htmlspecialchars($hours['day'] ?? ''); ?>"
                               placeholder="e.g., Monday - Friday">
                    </div>
                    <div class="form-group">
                        <label>Opening Time</label>
                        <input type="text" name="office_hours_open[]" class="form-control" 
                               value="<?php echo htmlspecialchars($hours['open'] ?? ''); ?>"
                               placeholder="e.g., 9:00 AM or Closed">
                    </div>
                    <div class="form-group">
                        <label>Closing Time</label>
                        <input type="text" name="office_hours_close[]" class="form-control" 
                               value="<?php echo htmlspecialchars($hours['close'] ?? ''); ?>"
                               placeholder="e.g., 5:00 PM">
                    </div>
                    <button type="button" class="btn-remove" onclick="removeOfficeHoursRow(this)" title="Remove">
                        <i class="fas fa-trash"></i>
                    </button>
                </div>
                <?php endforeach; ?>
            </div>
            
            <button type="button" class="btn-add" onclick="addOfficeHoursRow()">
                <i class="fas fa-plus"></i> Add Office Hours
            </button>
        </div>
    </div>
    
    <!-- Social Media Links -->
    <div class="card">
        <div class="card-header">
            <h3><i class="fas fa-share-alt"></i> Social Media Links</h3>
        </div>
        <div class="card-body">
            <div class="alert alert-info">
                <i class="fas fa-info-circle"></i> 
                Add your church's social media profiles to display on the Contact Us page.
            </div>
            
            <div id="social-links-container">
                <?php 
                // Parse existing social links from JSON or set defaults
                $social_links = [];
                if (!empty($settings['contact_social_links'])) {
                    $social_links = json_decode($settings['contact_social_links'], true) ?? [];
                }
                if (empty($social_links)) {
                    $social_links = [
                        ['platform' => 'facebook', 'url' => ''],
                        ['platform' => 'instagram', 'url' => ''],
                        ['platform' => 'youtube', 'url' => '']
                    ];
                }
                foreach ($social_links as $index => $link):
                ?>
                <div class="social-link-row" data-index="<?php echo $index; ?>">
                    <div class="form-group">
                        <label>Platform</label>
                        <select name="social_platform[]" class="form-control">
                            <option value="facebook" <?php echo ($link['platform'] ?? '') == 'facebook' ? 'selected' : ''; ?>>Facebook</option>
                            <option value="instagram" <?php echo ($link['platform'] ?? '') == 'instagram' ? 'selected' : ''; ?>>Instagram</option>
                            <option value="twitter" <?php echo ($link['platform'] ?? '') == 'twitter' ? 'selected' : ''; ?>>Twitter/X</option>
                            <option value="youtube" <?php echo ($link['platform'] ?? '') == 'youtube' ? 'selected' : ''; ?>>YouTube</option>
                            <option value="tiktok" <?php echo ($link['platform'] ?? '') == 'tiktok' ? 'selected' : ''; ?>>TikTok</option>
                            <option value="linkedin" <?php echo ($link['platform'] ?? '') == 'linkedin' ? 'selected' : ''; ?>>LinkedIn</option>
                            <option value="pinterest" <?php echo ($link['platform'] ?? '') == 'pinterest' ? 'selected' : ''; ?>>Pinterest</option>
                            <option value="whatsapp" <?php echo ($link['platform'] ?? '') == 'whatsapp' ? 'selected' : ''; ?>>WhatsApp</option>
                            <option value="telegram" <?php echo ($link['platform'] ?? '') == 'telegram' ? 'selected' : ''; ?>>Telegram</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Profile URL</label>
                        <input type="url" name="social_url[]" class="form-control" 
                               value="<?php echo htmlspecialchars($link['url'] ?? ''); ?>"
                               placeholder="https://facebook.com/yourchurch">
                    </div>
                    <button type="button" class="btn-remove" onclick="removeSocialLinkRow(this)" title="Remove">
                        <i class="fas fa-trash"></i>
                    </button>
                </div>
                <?php endforeach; ?>
            </div>
            
            <button type="button" class="btn-add" onclick="addSocialLinkRow()">
                <i class="fas fa-plus"></i> Add Social Link
            </button>
        </div>
    </div>
    
    <!-- Google Maps Integration -->
    <div class="card">
        <div class="card-header">
            <h3><i class="fas fa-map-marker-alt"></i> Google Maps Integration</h3>
        </div>
        <div class="card-body">
            <div class="form-group">
                <label for="contact_map_embed">Google Maps Embed Code</label>
                <textarea id="contact_map_embed" name="contact_map_embed" class="form-control" rows="4" 
                          placeholder='<iframe src="https://www.google.com/maps/embed?..." ...></iframe>'><?php echo htmlspecialchars($settings['contact_map_embed'] ?? ''); ?></textarea>
                <small style="color: #666; display: block; margin-top: 0.5rem;">
                    Paste the full embed code from Google Maps. Go to Google Maps → Search your location → Share → Embed a map → Copy HTML
                </small>
            </div>
            
            <div style="background: #f8f9fa; padding: 1.5rem; border-radius: 6px; margin-top: 1.5rem; border-left: 4px solid var(--admin-info);">
                <h4 style="font-size: 1rem; margin-bottom: 0.75rem; color: var(--admin-primary);">
                    <i class="fas fa-lightbulb"></i> How to get Google Maps Embed Code:
                </h4>
                <ol style="margin: 0; padding-left: 1.5rem; color: #666; line-height: 1.8;">
                    <li>Go to <a href="https://maps.google.com" target="_blank">Google Maps</a></li>
                    <li>Search for your church location</li>
                    <li>Click "Share" button</li>
                    <li>Select "Embed a map" tab</li>
                    <li>Copy the entire HTML code and paste it above</li>
                </ol>
            </div>
            
            <?php if (!empty($settings['contact_map_embed'])): ?>
            <div style="margin-top: 1.5rem;">
                <label>Current Map Preview:</label>
                <div style="margin-top: 0.5rem; border: 1px solid #e0e5eb; border-radius: 4px; overflow: hidden;">
                    <?php echo $settings['contact_map_embed']; ?>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Contact Form Settings -->
    <div class="card">
        <div class="card-header">
            <h3><i class="fas fa-envelope-open-text"></i> Contact Form Settings</h3>
        </div>
        <div class="card-body">
            <div class="form-group">
                <label for="contact_form_recipient">Form Submissions Email</label>
                <input type="email" id="contact_form_recipient" name="contact_form_recipient" class="form-control" 
                       value="<?php echo htmlspecialchars($settings['contact_form_recipient'] ?? ''); ?>"
                       placeholder="submissions@church.com">
                <small style="color: #666;">Email address where contact form submissions will be sent</small>
            </div>
            
            <div class="form-group">
                <label for="contact_form_success_message">Success Message</label>
                <textarea id="contact_form_success_message" name="contact_form_success_message" class="form-control" rows="3" maxlength="500"><?php echo htmlspecialchars($settings['contact_form_success_message'] ?? 'Thank you for your message! We will get back to you as soon as possible.'); ?></textarea>
                <small style="color: #666;">Message displayed after successful form submission</small>
            </div>
            
            <div class="form-group">
                <label for="contact_form_intro">Form Introduction Text</label>
                <textarea id="contact_form_intro" name="contact_form_intro" class="form-control" rows="3" maxlength="500"><?php echo htmlspecialchars($settings['contact_form_intro'] ?? 'Have a question or want to connect with us? Fill out the form below and we\'ll be in touch soon.'); ?></textarea>
                <small style="color: #666;">Text displayed above the contact form</small>
            </div>
            
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem; margin-top: 1rem;">
                <div class="form-group">
                    <label for="contact_form_enabled">Contact Form Status</label>
                    <select id="contact_form_enabled" name="contact_form_enabled" class="form-control">
                        <option value="1" <?php echo ($settings['contact_form_enabled'] ?? '1') == '1' ? 'selected' : ''; ?>>Enabled</option>
                        <option value="0" <?php echo ($settings['contact_form_enabled'] ?? '1') == '0' ? 'selected' : ''; ?>>Disabled</option>
                    </select>
                    <small style="color: #666;">Enable or disable the contact form on your website</small>
                </div>
                
                <div class="form-group">
                    <label for="contact_form_require_phone">Require Phone Number</label>
                    <select id="contact_form_require_phone" name="contact_form_require_phone" class="form-control">
                        <option value="0" <?php echo ($settings['contact_form_require_phone'] ?? '0') == '0' ? 'selected' : ''; ?>>Optional</option>
                        <option value="1" <?php echo ($settings['contact_form_require_phone'] ?? '0') == '1' ? 'selected' : ''; ?>>Required</option>
                    </select>
                    <small style="color: #666;">Whether phone number is required in the contact form</small>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Additional Contact Information -->
    <div class="card">
        <div class="card-header">
            <h3><i class="fas fa-info"></i> Additional Information</h3>
        </div>
        <div class="card-body">
            <div class="form-group">
                <label for="contact_additional_info">Additional Contact Information</label>
                <textarea id="contact_additional_info" name="contact_additional_info" class="form-control" rows="5" maxlength="1000"><?php echo htmlspecialchars($settings['contact_additional_info'] ?? ''); ?></textarea>
                <small style="color: #666;">Any additional information you want to display (parking info, accessibility notes, etc.)</small>
            </div>
            
            <div class="form-group">
                <label for="contact_directions">Directions to Church</label>
                <textarea id="contact_directions" name="contact_directions" class="form-control" rows="5" maxlength="1000"><?php echo htmlspecialchars($settings['contact_directions'] ?? ''); ?></textarea>
                <small style="color: #666;">Special directions or landmarks to help visitors find your church</small>
            </div>
        </div>
    </div>
    
    <!-- Hidden fields to store JSON data -->
    <input type="hidden" id="contact_office_hours" name="contact_office_hours" value="">
    <input type="hidden" id="contact_social_links" name="contact_social_links" value="">
    
    <!-- Form Actions -->
    <div style="display: flex; gap: 1rem; padding: 1.5rem; background: white; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.08); margin-top: 1.5rem;">
        <button type="submit" name="submit" class="btn btn-primary" onclick="prepareContactFormSubmission()">
            <i class="fas fa-save"></i> Save Contact Us Settings
        </button>
        <a href="dashboard.php" class="btn btn-secondary">
            <i class="fas fa-times"></i> Cancel
        </a>
        <a href="../contact.php" target="_blank" class="btn btn-info" style="margin-left: auto;">
            <i class="fas fa-external-link-alt"></i> Preview Contact Page
        </a>
    </div>
</form>

<script>
// Office Hours Management
function addOfficeHoursRow() {
    const container = document.getElementById('office-hours-container');
    const newIndex = container.children.length;
    
    const newRow = document.createElement('div');
    newRow.className = 'office-hours-row';
    newRow.dataset.index = newIndex;
    newRow.innerHTML = `
        <div class="form-group">
            <label>Day(s)</label>
            <input type="text" name="office_hours_day[]" class="form-control" placeholder="e.g., Monday - Friday">
        </div>
        <div class="form-group">
            <label>Opening Time</label>
            <input type="text" name="office_hours_open[]" class="form-control" placeholder="e.g., 9:00 AM or Closed">
        </div>
        <div class="form-group">
            <label>Closing Time</label>
            <input type="text" name="office_hours_close[]" class="form-control" placeholder="e.g., 5:00 PM">
        </div>
        <button type="button" class="btn-remove" onclick="removeOfficeHoursRow(this)" title="Remove">
            <i class="fas fa-trash"></i>
        </button>
    `;
    container.appendChild(newRow);
}

function removeOfficeHoursRow(button) {
    const row = button.closest('.office-hours-row');
    const container = document.getElementById('office-hours-container');
    
    if (container.children.length > 1) {
        row.remove();
    } else {
        alert('You must have at least one office hours entry.');
    }
}

// Social Links Management
function addSocialLinkRow() {
    const container = document.getElementById('social-links-container');
    const newIndex = container.children.length;
    
    const newRow = document.createElement('div');
    newRow.className = 'social-link-row';
    newRow.dataset.index = newIndex;
    newRow.innerHTML = `
        <div class="form-group">
            <label>Platform</label>
            <select name="social_platform[]" class="form-control">
                <option value="facebook">Facebook</option>
                <option value="instagram">Instagram</option>
                <option value="twitter">Twitter/X</option>
                <option value="youtube">YouTube</option>
                <option value="tiktok">TikTok</option>
                <option value="linkedin">LinkedIn</option>
                <option value="pinterest">Pinterest</option>
                <option value="whatsapp">WhatsApp</option>
                <option value="telegram">Telegram</option>
            </select>
        </div>
        <div class="form-group">
            <label>Profile URL</label>
            <input type="url" name="social_url[]" class="form-control" placeholder="https://facebook.com/yourchurch">
        </div>
        <button type="button" class="btn-remove" onclick="removeSocialLinkRow(this)" title="Remove">
            <i class="fas fa-trash"></i>
        </button>
    `;
    container.appendChild(newRow);
}

function removeSocialLinkRow(button) {
    const row = button.closest('.social-link-row');
    row.remove();
}

// Prepare form data before submission
function prepareContactFormSubmission() {
    // Collect office hours data
    const officeHoursRows = document.querySelectorAll('.office-hours-row');
    const officeHours = [];
    officeHoursRows.forEach(row => {
        const day = row.querySelector('input[name="office_hours_day[]"]').value;
        const open = row.querySelector('input[name="office_hours_open[]"]').value;
        const close = row.querySelector('input[name="office_hours_close[]"]').value;
        if (day.trim()) {
            officeHours.push({ day: day, open: open, close: close });
        }
    });
    document.getElementById('contact_office_hours').value = JSON.stringify(officeHours);
    
    // Collect social links data
    const socialLinkRows = document.querySelectorAll('.social-link-row');
    const socialLinks = [];
    socialLinkRows.forEach(row => {
        const platform = row.querySelector('select[name="social_platform[]"]').value;
        const url = row.querySelector('input[name="social_url[]"]').value;
        if (url.trim()) {
            socialLinks.push({ platform: platform, url: url });
        }
    });
    document.getElementById('contact_social_links').value = JSON.stringify(socialLinks);
}

// Attach to form submission
document.querySelector('form').addEventListener('submit', function(e) {
    prepareContactFormSubmission();
});
</script>
<?php endif; ?>

<?php if ($active_tab == 'livestream'): ?>
<!-- ============================================
     LIVE STREAM SETTINGS TAB
     ============================================ -->
<form method="POST" action="?tab=livestream" enctype="multipart/form-data">
    <div class="card">
        <div class="card-header">
            <h3><i class="fas fa-video"></i> Live Stream Configuration</h3>
        </div>
        <div class="card-body">
            <div class="alert alert-info">
                <i class="fas fa-info-circle"></i> 
                <strong>Important:</strong> Update this URL before each live service.
            </div>
            
            <div class="form-group">
                <label for="live_stream_url">Live Stream Embed URL</label>
                <input type="url" id="live_stream_url" name="live_stream_url" class="form-control" 
                       value="<?php echo htmlspecialchars($settings['live_stream_url'] ?? ''); ?>"
                       placeholder="https://www.youtube.com/embed/VIDEO_ID">
                <small style="color: #666; display: block; margin-top: 0.5rem;">
                    For YouTube: Use the embed URL format (youtube.com/embed/VIDEO_ID)<br>
                    For Facebook Live: Use the embed URL from Facebook
                </small>
            </div>
            
            <div style="background: #f8f9fa; padding: 1.5rem; border-radius: 6px; margin-top: 1.5rem; border-left: 4px solid var(--admin-info);">
                <h4 style="font-size: 1rem; margin-bottom: 0.75rem; color: var(--admin-primary);">
                    <i class="fas fa-lightbulb"></i> Quick YouTube Setup:
                </h4>
                <ol style="margin: 0; padding-left: 1.5rem; color: #666; line-height: 1.8;">
                    <li>Go to your YouTube video</li>
                    <li>Click "Share" → "Embed"</li>
                    <li>Copy the URL from the iframe src</li>
                    <li>Paste it in the field above</li>
                </ol>
            </div>
        </div>
    </div>
    
    <!-- Form Actions -->
    <div style="display: flex; gap: 1rem; padding: 1.5rem; background: white; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.08); margin-top: 1.5rem;">
        <button type="submit" name="submit" class="btn btn-primary">
            <i class="fas fa-save"></i> Save Live Stream Settings
        </button>
        <a href="dashboard.php" class="btn btn-secondary">
            <i class="fas fa-times"></i> Cancel
        </a>
        <a href="../live.php" target="_blank" class="btn btn-info" style="margin-left: auto;">
            <i class="fas fa-external-link-alt"></i> Preview Live Stream Page
        </a>
    </div>
</form>
<?php endif; ?>

<?php
// Include admin footer
include 'includes/admin-footer.php';
?>