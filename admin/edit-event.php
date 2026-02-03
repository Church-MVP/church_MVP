<?php
/**
 * Edit Event
 * 
 * Form to edit an existing event
 */

// Set page title
$page_title = 'Edit Event';

// Include admin header
include 'includes/admin-header.php';

// Initialize messages
$success_message = '';
$error_message = '';

// Get event ID from URL
if (!isset($_GET['id'])) {
    header("Location: manage-events.php");
    exit();
}

$event_id = (int)$_GET['id'];

// Fetch event data
try {
    $stmt = $pdo->prepare("SELECT * FROM events WHERE id = ?");
    $stmt->execute([$event_id]);
    $event = $stmt->fetch();
    
    if (!$event) {
        header("Location: manage-events.php");
        exit();
    }
} catch (PDOException $e) {
    $error_message = 'Error fetching event data.';
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Sanitize inputs
    $title = sanitize_input($_POST['title']);
    $event_date = sanitize_input($_POST['event_date']);
    $event_time = sanitize_input($_POST['event_time']);
    $end_time = sanitize_input($_POST['end_time'] ?? '');
    $location = sanitize_input($_POST['location']);
    $description = sanitize_input($_POST['description']);
    $registration_url = sanitize_input($_POST['registration_url'] ?? '');
    $contact_email = sanitize_input($_POST['contact_email'] ?? '');
    $contact_phone = sanitize_input($_POST['contact_phone'] ?? '');
    $event_image = $event['event_image'] ?? ''; // Keep existing image by default
    
    // Check if user wants to remove the current image
    $remove_image = isset($_POST['remove_event_image']) && $_POST['remove_event_image'] == '1';
    
    // Validation
    if (empty($title) || empty($event_date) || empty($event_time) || empty($location)) {
        $error_message = 'Please fill in all required fields.';
    } else {
        try {
            // Handle cover image upload
            if (!empty($_FILES['event_image']) && $_FILES['event_image']['error'] == UPLOAD_ERR_OK) {
                $upload_dir = '../assets/images/events/';
                
                // Create directory if it doesn't exist
                if (!is_dir($upload_dir)) {
                    mkdir($upload_dir, 0755, true);
                }
                
                $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
                $file = $_FILES['event_image'];
                
                if (in_array($file['type'], $allowed_types)) {
                    // Check file size (max 5MB)
                    if ($file['size'] <= 5 * 1024 * 1024) {
                        $file_extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
                        
                        // Create unique filename
                        $new_filename = 'event_' . time() . '_' . uniqid() . '.' . $file_extension;
                        $upload_path = $upload_dir . $new_filename;
                        
                        if (move_uploaded_file($file['tmp_name'], $upload_path)) {
                            // Delete old image if exists
                            if (!empty($event['event_image']) && file_exists('../' . $event['event_image'])) {
                                unlink('../' . $event['event_image']);
                            }
                            $event_image = 'assets/images/events/' . $new_filename;
                        } else {
                            $error_message = 'Error uploading event image. Please try again.';
                        }
                    } else {
                        $error_message = 'Event image must be less than 5MB.';
                    }
                } else {
                    $error_message = 'Invalid image type. Please use JPG, PNG, GIF, or WebP.';
                }
            } elseif ($remove_image) {
                // Remove current image
                if (!empty($event['event_image']) && file_exists('../' . $event['event_image'])) {
                    unlink('../' . $event['event_image']);
                }
                $event_image = '';
            }
            
            // Only proceed if no upload errors
            if (empty($error_message)) {
                // Update event in database
                $stmt = $pdo->prepare("UPDATE events SET title = ?, event_date = ?, event_time = ?, end_time = ?, location = ?, description = ?, event_image = ?, registration_url = ?, contact_email = ?, contact_phone = ? WHERE id = ?");
                $stmt->execute([
                    $title,
                    $event_date,
                    $event_time,
                    $end_time,
                    $location,
                    $description,
                    $event_image,
                    $registration_url,
                    $contact_email,
                    $contact_phone,
                    $event_id
                ]);
                
                $success_message = 'Event updated successfully!';
                
                // Refresh event data
                $stmt = $pdo->prepare("SELECT * FROM events WHERE id = ?");
                $stmt->execute([$event_id]);
                $event = $stmt->fetch();
            }
        } catch (PDOException $e) {
            $error_message = 'Error updating event. Please try again.';
        }
    }
}
?>

<!-- Page Header -->
<div class="page-header">
    <h2><i class="fas fa-edit"></i> Edit Event</h2>
    <p>Update event information</p>
</div>

<!-- Success/Error Messages -->
<?php if (!empty($success_message)): ?>
<div class="alert alert-success">
    <i class="fas fa-check-circle"></i> <?php echo $success_message; ?>
    <a href="manage-events.php" style="margin-left: 1rem;">Back to all events</a>
</div>
<?php endif; ?>

<?php if (!empty($error_message)): ?>
<div class="alert alert-danger">
    <i class="fas fa-exclamation-circle"></i> <?php echo $error_message; ?>
</div>
<?php endif; ?>

<!-- Event Form -->
<div class="card">
    <div class="card-header">
        <h3><i class="fas fa-calendar-alt"></i> Edit Event Details</h3>
    </div>
    <div class="card-body">
        <form method="POST" action="" enctype="multipart/form-data">
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem;">
                <!-- Left Column -->
                <div>
                    <!-- Event Image Upload -->
                    <div class="form-group">
                        <label for="event_image">Event Cover Image</label>
                        <div class="cover-image-upload <?php echo !empty($event['event_image']) ? 'has-image' : ''; ?>" id="coverImageUpload">
                            <div class="upload-preview" id="uploadPreview" <?php echo !empty($event['event_image']) ? 'style="display: none;"' : ''; ?>>
                                <i class="fas fa-image"></i>
                                <span>Click to upload event image</span>
                                <small>Recommended: 800x600px (JPG, PNG, GIF, WebP - Max 5MB)</small>
                            </div>
                            <img id="imagePreview" 
                                 src="<?php echo !empty($event['event_image']) ? '../' . htmlspecialchars($event['event_image']) : ''; ?>" 
                                 alt="Event preview" 
                                 <?php echo empty($event['event_image']) ? 'style="display: none;"' : ''; ?>>
                            <input type="file" 
                                   id="event_image" 
                                   name="event_image" 
                                   class="form-control" 
                                   accept="image/jpeg,image/png,image/gif,image/webp"
                                   onchange="previewImage(this)">
                            <button type="button" 
                                    class="btn-remove-image" 
                                    id="removeImageBtn" 
                                    onclick="removeImage()" 
                                    <?php echo empty($event['event_image']) ? 'style="display: none;"' : ''; ?>>
                                <i class="fas fa-times"></i> Remove
                            </button>
                        </div>
                        <!-- Hidden field to track image removal -->
                        <input type="hidden" name="remove_event_image" id="remove_event_image" value="0">
                        <?php if (!empty($event['event_image'])): ?>
                        <small style="color: #666; display: block; margin-top: 0.5rem;">
                            <i class="fas fa-info-circle"></i> Current image will be kept unless you upload a new one or remove it.
                        </small>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Event Title -->
                    <div class="form-group">
                        <label for="title">Event Title <span style="color: red;">*</span></label>
                        <input type="text" 
                               id="title" 
                               name="title" 
                               class="form-control" 
                               value="<?php echo htmlspecialchars($event['title']); ?>"
                               required>
                    </div>
                    
                    <!-- Event Date -->
                    <div class="form-group">
                        <label for="event_date">Event Date <span style="color: red;">*</span></label>
                        <input type="date" 
                               id="event_date" 
                               name="event_date" 
                               class="form-control" 
                               value="<?php echo htmlspecialchars($event['event_date']); ?>"
                               required>
                    </div>
                    
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                        <!-- Event Start Time -->
                        <div class="form-group">
                            <label for="event_time">Start Time <span style="color: red;">*</span></label>
                            <input type="time" 
                                   id="event_time" 
                                   name="event_time" 
                                   class="form-control" 
                                   value="<?php echo htmlspecialchars($event['event_time']); ?>"
                                   required>
                        </div>
                        
                        <!-- Event End Time -->
                        <div class="form-group">
                            <label for="end_time">End Time</label>
                            <input type="time" 
                                   id="end_time" 
                                   name="end_time" 
                                   class="form-control" 
                                   value="<?php echo htmlspecialchars($event['end_time'] ?? ''); ?>">
                        </div>
                    </div>
                    
                    <!-- Location -->
                    <div class="form-group">
                        <label for="location">Location <span style="color: red;">*</span></label>
                        <input type="text" 
                               id="location" 
                               name="location" 
                               class="form-control" 
                               value="<?php echo htmlspecialchars($event['location']); ?>"
                               required>
                    </div>
                </div>
                
                <!-- Right Column -->
                <div>
                    <!-- Description -->
                    <div class="form-group">
                        <label for="description">Description</label>
                        <textarea id="description" 
                                  name="description" 
                                  class="form-control" 
                                  rows="6"
                                  maxlength="2000"><?php echo htmlspecialchars($event['description']); ?></textarea>
                        <small style="color: #666; display: block; margin-top: 0.25rem;">
                            Maximum 2000 characters
                        </small>
                    </div>
                    
                    <!-- Registration URL -->
                    <div class="form-group">
                        <label for="registration_url">Registration Link (Optional)</label>
                        <input type="url" 
                               id="registration_url" 
                               name="registration_url" 
                               class="form-control" 
                               value="<?php echo htmlspecialchars($event['registration_url'] ?? ''); ?>"
                               placeholder="https://example.com/register">
                        <small style="color: #666; display: block; margin-top: 0.25rem;">
                            Link for online registration or sign-up
                        </small>
                    </div>
                    
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                        <!-- Contact Email -->
                        <div class="form-group">
                            <label for="contact_email">Contact Email</label>
                            <input type="email" 
                                   id="contact_email" 
                                   name="contact_email" 
                                   class="form-control" 
                                   value="<?php echo htmlspecialchars($event['contact_email'] ?? ''); ?>"
                                   placeholder="events@church.com">
                        </div>
                        
                        <!-- Contact Phone -->
                        <div class="form-group">
                            <label for="contact_phone">Contact Phone</label>
                            <input type="tel" 
                                   id="contact_phone" 
                                   name="contact_phone" 
                                   class="form-control" 
                                   value="<?php echo htmlspecialchars($event['contact_phone'] ?? ''); ?>"
                                   placeholder="(555) 123-4567">
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Form Actions -->
            <div style="display: flex; gap: 1rem; margin-top: 2rem; padding-top: 1.5rem; border-top: 1px solid #e0e5eb;">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> Update Event
                </button>
                <a href="manage-events.php" class="btn btn-secondary">
                    <i class="fas fa-times"></i> Cancel
                </a>
                <a href="delete-event.php?id=<?php echo $event_id; ?>" 
                   class="btn btn-danger btn-delete" 
                   data-item="<?php echo htmlspecialchars($event['title']); ?>"
                   style="margin-left: auto;"
                   onclick="return confirm('Are you sure you want to delete this event?');">
                    <i class="fas fa-trash"></i> Delete Event
                </a>
            </div>
        </form>
    </div>
</div>

<style>
.cover-image-upload {
    position: relative;
    border: 2px dashed #ccd5e0;
    border-radius: 8px;
    background: #f8f9fa;
    cursor: pointer;
    overflow: hidden;
    transition: all 0.3s ease;
}

.cover-image-upload:hover {
    border-color: var(--admin-accent);
    background: rgba(122, 156, 198, 0.05);
}

.cover-image-upload input[type="file"] {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    opacity: 0;
    cursor: pointer;
    z-index: 2;
}

.upload-preview {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 2.5rem 1.5rem;
    text-align: center;
    color: #666;
}

.upload-preview i {
    font-size: 3rem;
    color: #adb5bd;
    margin-bottom: 1rem;
}

.upload-preview span {
    font-size: 1rem;
    font-weight: 500;
    color: #495057;
    margin-bottom: 0.5rem;
}

.upload-preview small {
    font-size: 0.8rem;
    color: #868e96;
}

.cover-image-upload img {
    width: 100%;
    height: 250px;
    object-fit: cover;
    display: block;
}

.btn-remove-image {
    position: absolute;
    top: 10px;
    right: 10px;
    background: rgba(220, 53, 69, 0.9);
    color: white;
    border: none;
    padding: 0.5rem 1rem;
    border-radius: 4px;
    cursor: pointer;
    font-size: 0.85rem;
    z-index: 3;
    transition: background 0.3s;
}

.btn-remove-image:hover {
    background: #c82333;
}

.cover-image-upload.has-image {
    border-style: solid;
    border-color: #28a745;
}

.cover-image-upload.has-image .upload-preview {
    display: none;
}

.cover-image-upload.dragover {
    border-color: var(--admin-accent);
    background: rgba(122, 156, 198, 0.1);
}
</style>

<script>
// Track if we have an existing image from the database
let hasExistingImage = <?php echo !empty($event['event_image']) ? 'true' : 'false'; ?>;
let existingImagePath = '<?php echo !empty($event['event_image']) ? '../' . htmlspecialchars($event['event_image']) : ''; ?>';

function previewImage(input) {
    const container = document.getElementById('coverImageUpload');
    const preview = document.getElementById('imagePreview');
    const uploadPreview = document.getElementById('uploadPreview');
    const removeBtn = document.getElementById('removeImageBtn');
    const removeHidden = document.getElementById('remove_event_image');
    
    if (input.files && input.files[0]) {
        const file = input.files[0];
        
        // Validate file size (5MB max)
        if (file.size > 5 * 1024 * 1024) {
            alert('File size must be less than 5MB');
            input.value = '';
            return;
        }
        
        // Validate file type
        const allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        if (!allowedTypes.includes(file.type)) {
            alert('Please select a valid image file (JPG, PNG, GIF, or WebP)');
            input.value = '';
            return;
        }
        
        const reader = new FileReader();
        
        reader.onload = function(e) {
            preview.src = e.target.result;
            preview.style.display = 'block';
            uploadPreview.style.display = 'none';
            removeBtn.style.display = 'block';
            container.classList.add('has-image');
            removeHidden.value = '0'; // New image uploaded, don't remove
        };
        
        reader.readAsDataURL(file);
    }
}

function removeImage() {
    const container = document.getElementById('coverImageUpload');
    const preview = document.getElementById('imagePreview');
    const uploadPreview = document.getElementById('uploadPreview');
    const removeBtn = document.getElementById('removeImageBtn');
    const input = document.getElementById('event_image');
    const removeHidden = document.getElementById('remove_event_image');
    
    // Clear the file input
    input.value = '';
    
    // Reset preview
    preview.src = '';
    preview.style.display = 'none';
    uploadPreview.style.display = 'flex';
    removeBtn.style.display = 'none';
    container.classList.remove('has-image');
    
    // Mark for removal if there was an existing image
    if (hasExistingImage) {
        removeHidden.value = '1';
    }
}

// Drag and drop functionality
const uploadContainer = document.getElementById('coverImageUpload');

uploadContainer.addEventListener('dragover', function(e) {
    e.preventDefault();
    this.classList.add('dragover');
});

uploadContainer.addEventListener('dragleave', function(e) {
    e.preventDefault();
    this.classList.remove('dragover');
});

uploadContainer.addEventListener('drop', function(e) {
    e.preventDefault();
    this.classList.remove('dragover');
    
    const files = e.dataTransfer.files;
    if (files.length > 0) {
        const input = document.getElementById('event_image');
        input.files = files;
        previewImage(input);
    }
});
</script>

<?php
// Include admin footer
include 'includes/admin-footer.php';
?>