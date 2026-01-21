<?php
/**
 * Add New Sermon
 * 
 * Form to add a new sermon to the database
 */

// Set page title
$page_title = 'Add New Sermon';

// Include admin header
include 'includes/admin-header.php';

// Initialize messages
$success_message = '';
$error_message = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Sanitize inputs
    $title = sanitize_input($_POST['title']);
    $preacher = sanitize_input($_POST['preacher']);
    $sermon_date = sanitize_input($_POST['sermon_date']);
    $description = sanitize_input($_POST['description']);
    $video_url = sanitize_input($_POST['video_url']);
    $audio_url = sanitize_input($_POST['audio_url']);
    $scripture_reference = sanitize_input($_POST['scripture_reference']);
    $cover_image = '';
    
    // Validation
    if (empty($title) || empty($preacher) || empty($sermon_date)) {
        $error_message = 'Please fill in all required fields.';
    } else {
        try {
            // Handle cover image upload
            if (!empty($_FILES['cover_image']) && $_FILES['cover_image']['error'] == UPLOAD_ERR_OK) {
                $upload_dir = '../assets/images/sermons/';
                
                // Create directory if it doesn't exist
                if (!is_dir($upload_dir)) {
                    mkdir($upload_dir, 0755, true);
                }
                
                $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
                $file = $_FILES['cover_image'];
                
                if (in_array($file['type'], $allowed_types)) {
                    // Check file size (max 5MB)
                    if ($file['size'] <= 5 * 1024 * 1024) {
                        $file_extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
                        
                        // Create unique filename
                        $new_filename = 'sermon_' . time() . '_' . uniqid() . '.' . $file_extension;
                        $upload_path = $upload_dir . $new_filename;
                        
                        if (move_uploaded_file($file['tmp_name'], $upload_path)) {
                            $cover_image = 'assets/images/sermons/' . $new_filename;
                        } else {
                            $error_message = 'Error uploading cover image. Please try again.';
                        }
                    } else {
                        $error_message = 'Cover image must be less than 5MB.';
                    }
                } else {
                    $error_message = 'Invalid image type. Please use JPG, PNG, GIF, or WebP.';
                }
            }
            
            // Only proceed if no upload errors
            if (empty($error_message)) {
                // Insert sermon into database
                $stmt = $pdo->prepare("INSERT INTO sermons (title, preacher, sermon_date, description, video_url, audio_url, scripture_reference, cover_image) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->execute([
                    $title,
                    $preacher,
                    $sermon_date,
                    $description,
                    $video_url,
                    $audio_url,
                    $scripture_reference,
                    $cover_image
                ]);
                
                $success_message = 'Sermon added successfully!';
                
                // Clear form
                $_POST = [];
            }
        } catch (PDOException $e) {
            $error_message = 'Error adding sermon. Please try again.';
        }
    }
}
?>

<!-- Page Header -->
<div class="page-header">
    <h2><i class="fas fa-plus-circle"></i> Add New Sermon</h2>
    <p>Create a new sermon entry</p>
</div>

<!-- Success/Error Messages -->
<?php if (!empty($success_message)): ?>
<div class="alert alert-success">
    <i class="fas fa-check-circle"></i> <?php echo $success_message; ?>
    <a href="manage-sermons.php" style="margin-left: 1rem;">View all sermons</a>
</div>
<?php endif; ?>

<?php if (!empty($error_message)): ?>
<div class="alert alert-danger">
    <i class="fas fa-exclamation-circle"></i> <?php echo $error_message; ?>
</div>
<?php endif; ?>

<!-- Sermon Form -->
<div class="card">
    <div class="card-header">
        <h3><i class="fas fa-bible"></i> Sermon Details</h3>
    </div>
    <div class="card-body">
        <form method="POST" action="" enctype="multipart/form-data">
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem;">
                <!-- Left Column -->
                <div>
                    <!-- Cover Image Upload -->
                    <div class="form-group">
                        <label for="cover_image">Cover Photo</label>
                        <div class="cover-image-upload" id="coverImageUpload">
                            <div class="upload-preview" id="uploadPreview">
                                <i class="fas fa-image"></i>
                                <span>Click to upload cover photo</span>
                                <small>Recommended: 800x600px (JPG, PNG, GIF, WebP - Max 5MB)</small>
                            </div>
                            <img id="imagePreview" src="" alt="Cover preview" style="display: none;">
                            <input type="file" 
                                   id="cover_image" 
                                   name="cover_image" 
                                   class="form-control" 
                                   accept="image/jpeg,image/png,image/gif,image/webp"
                                   onchange="previewImage(this)">
                            <button type="button" class="btn-remove-image" id="removeImageBtn" onclick="removeImage()" style="display: none;">
                                <i class="fas fa-times"></i> Remove
                            </button>
                        </div>
                    </div>
                    
                    <!-- Sermon Title -->
                    <div class="form-group">
                        <label for="title">Sermon Title <span style="color: red;">*</span></label>
                        <input type="text" 
                               id="title" 
                               name="title" 
                               class="form-control" 
                               value="<?php echo isset($_POST['title']) ? htmlspecialchars($_POST['title']) : ''; ?>"
                               placeholder="e.g., Walking in Faith"
                               required>
                    </div>
                    
                    <!-- Preacher -->
                    <div class="form-group">
                        <label for="preacher">Preacher <span style="color: red;">*</span></label>
                        <input type="text" 
                               id="preacher" 
                               name="preacher" 
                               class="form-control" 
                               value="<?php echo isset($_POST['preacher']) ? htmlspecialchars($_POST['preacher']) : ''; ?>"
                               placeholder="e.g., Pastor John Smith"
                               required>
                    </div>
                    
                    <!-- Sermon Date -->
                    <div class="form-group">
                        <label for="sermon_date">Sermon Date <span style="color: red;">*</span></label>
                        <input type="date" 
                               id="sermon_date" 
                               name="sermon_date" 
                               class="form-control" 
                               value="<?php echo isset($_POST['sermon_date']) ? htmlspecialchars($_POST['sermon_date']) : date('Y-m-d'); ?>"
                               required>
                    </div>
                    
                    <!-- Scripture Reference -->
                    <div class="form-group">
                        <label for="scripture_reference">Scripture Reference</label>
                        <input type="text" 
                               id="scripture_reference" 
                               name="scripture_reference" 
                               class="form-control" 
                               value="<?php echo isset($_POST['scripture_reference']) ? htmlspecialchars($_POST['scripture_reference']) : ''; ?>"
                               placeholder="e.g., John 3:16-17">
                    </div>
                </div>
                
                <!-- Right Column -->
                <div>
                    <!-- Video URL -->
                    <div class="form-group">
                        <label for="video_url">Video URL (YouTube/Vimeo)</label>
                        <input type="url" 
                               id="video_url" 
                               name="video_url" 
                               class="form-control" 
                               value="<?php echo isset($_POST['video_url']) ? htmlspecialchars($_POST['video_url']) : ''; ?>"
                               placeholder="https://youtube.com/watch?v=...">
                        <small style="color: #666; display: block; margin-top: 0.25rem;">
                            Enter the full YouTube or Vimeo URL
                        </small>
                    </div>
                    
                    <!-- Audio URL -->
                    <div class="form-group">
                        <label for="audio_url">Audio URL (MP3/Podcast)</label>
                        <input type="url" 
                               id="audio_url" 
                               name="audio_url" 
                               class="form-control" 
                               value="<?php echo isset($_POST['audio_url']) ? htmlspecialchars($_POST['audio_url']) : ''; ?>"
                               placeholder="https://example.com/sermon.mp3">
                        <small style="color: #666; display: block; margin-top: 0.25rem;">
                            Direct link to audio file
                        </small>
                    </div>
                    
                    <!-- Description -->
                    <div class="form-group">
                        <label for="description">Description</label>
                        <textarea id="description" 
                                  name="description" 
                                  class="form-control" 
                                  rows="9"
                                  maxlength="1000"
                                  placeholder="Brief description of the sermon message..."><?php echo isset($_POST['description']) ? htmlspecialchars($_POST['description']) : ''; ?></textarea>
                        <small style="color: #666; display: block; margin-top: 0.25rem;">
                            Maximum 1000 characters
                        </small>
                    </div>
                </div>
            </div>
            
            <!-- Form Actions -->
            <div style="display: flex; gap: 1rem; margin-top: 2rem; padding-top: 1.5rem; border-top: 1px solid #e0e5eb;">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> Save Sermon
                </button>
                <a href="manage-sermons.php" class="btn btn-secondary">
                    <i class="fas fa-times"></i> Cancel
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
function previewImage(input) {
    const container = document.getElementById('coverImageUpload');
    const preview = document.getElementById('imagePreview');
    const uploadPreview = document.getElementById('uploadPreview');
    const removeBtn = document.getElementById('removeImageBtn');
    
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
        };
        
        reader.readAsDataURL(file);
    }
}

function removeImage() {
    const container = document.getElementById('coverImageUpload');
    const preview = document.getElementById('imagePreview');
    const uploadPreview = document.getElementById('uploadPreview');
    const removeBtn = document.getElementById('removeImageBtn');
    const input = document.getElementById('cover_image');
    
    // Clear the file input
    input.value = '';
    
    // Reset preview
    preview.src = '';
    preview.style.display = 'none';
    uploadPreview.style.display = 'flex';
    removeBtn.style.display = 'none';
    container.classList.remove('has-image');
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
        const input = document.getElementById('cover_image');
        input.files = files;
        previewImage(input);
    }
});
</script>

<?php
// Include admin footer
include 'includes/admin-footer.php';
?>