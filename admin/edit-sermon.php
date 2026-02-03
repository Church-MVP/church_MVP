<?php
/**
 * Edit Sermon
 * 
 * Form to edit an existing sermon
 */

// Set page title
$page_title = 'Edit Sermon';

// Include admin header
include 'includes/admin-header.php';

// Initialize messages
$success_message = '';
$error_message = '';

// Get sermon ID from URL
if (!isset($_GET['id'])) {
    header("Location: manage-sermons.php");
    exit();
}

$sermon_id = (int)$_GET['id'];

// Fetch sermon data
try {
    $stmt = $pdo->prepare("SELECT * FROM sermons WHERE id = ?");
    $stmt->execute([$sermon_id]);
    $sermon = $stmt->fetch();
    
    if (!$sermon) {
        header("Location: manage-sermons.php");
        exit();
    }
} catch (PDOException $e) {
    $error_message = 'Error fetching sermon data.';
}

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
    $cover_image = $sermon['cover_image'] ?? ''; // Keep existing image by default
    
    // Check if user wants to remove the current image
    $remove_image = isset($_POST['remove_cover_image']) && $_POST['remove_cover_image'] == '1';
    
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
                            // Delete old image if exists
                            if (!empty($sermon['cover_image']) && file_exists('../' . $sermon['cover_image'])) {
                                unlink('../' . $sermon['cover_image']);
                            }
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
            } elseif ($remove_image) {
                // Remove current image
                if (!empty($sermon['cover_image']) && file_exists('../' . $sermon['cover_image'])) {
                    unlink('../' . $sermon['cover_image']);
                }
                $cover_image = '';
            }
            
            // Only proceed if no upload errors
            if (empty($error_message)) {
                // Update sermon in database
                $stmt = $pdo->prepare("UPDATE sermons SET title = ?, preacher = ?, sermon_date = ?, description = ?, video_url = ?, audio_url = ?, scripture_reference = ?, cover_image = ? WHERE id = ?");
                $stmt->execute([
                    $title,
                    $preacher,
                    $sermon_date,
                    $description,
                    $video_url,
                    $audio_url,
                    $scripture_reference,
                    $cover_image,
                    $sermon_id
                ]);
                
                $success_message = 'Sermon updated successfully!';
                
                // Refresh sermon data
                $stmt = $pdo->prepare("SELECT * FROM sermons WHERE id = ?");
                $stmt->execute([$sermon_id]);
                $sermon = $stmt->fetch();
            }
        } catch (PDOException $e) {
            $error_message = 'Error updating sermon. Please try again.';
        }
    }
}
?>

<!-- Page Header -->
<div class="page-header">
    <h2><i class="fas fa-edit"></i> Edit Sermon</h2>
    <p>Update sermon information</p>
</div>

<!-- Success/Error Messages -->
<?php if (!empty($success_message)): ?>
<div class="alert alert-success">
    <i class="fas fa-check-circle"></i> <?php echo $success_message; ?>
    <a href="manage-sermons.php" style="margin-left: 1rem;">Back to all sermons</a>
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
        <h3><i class="fas fa-bible"></i> Edit Sermon Details</h3>
    </div>
    <div class="card-body">
        <form method="POST" action="" enctype="multipart/form-data">
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem;">
                <!-- Left Column -->
                <div>
                    <!-- Cover Image Upload -->
                    <div class="form-group">
                        <label for="cover_image">Cover Photo</label>
                        <div class="cover-image-upload <?php echo !empty($sermon['cover_image']) ? 'has-image' : ''; ?>" id="coverImageUpload">
                            <div class="upload-preview" id="uploadPreview" <?php echo !empty($sermon['cover_image']) ? 'style="display: none;"' : ''; ?>>
                                <i class="fas fa-image"></i>
                                <span>Click to upload cover photo</span>
                                <small>Recommended: 800x600px (JPG, PNG, GIF, WebP - Max 5MB)</small>
                            </div>
                            <img id="imagePreview" 
                                 src="<?php echo !empty($sermon['cover_image']) ? '../' . htmlspecialchars($sermon['cover_image']) : ''; ?>" 
                                 alt="Cover preview" 
                                 <?php echo empty($sermon['cover_image']) ? 'style="display: none;"' : ''; ?>>
                            <input type="file" 
                                   id="cover_image" 
                                   name="cover_image" 
                                   class="form-control" 
                                   accept="image/jpeg,image/png,image/gif,image/webp"
                                   onchange="previewImage(this)">
                            <button type="button" 
                                    class="btn-remove-image" 
                                    id="removeImageBtn" 
                                    onclick="removeImage()" 
                                    <?php echo empty($sermon['cover_image']) ? 'style="display: none;"' : ''; ?>>
                                <i class="fas fa-times"></i> Remove
                            </button>
                        </div>
                        <!-- Hidden field to track image removal -->
                        <input type="hidden" name="remove_cover_image" id="remove_cover_image" value="0">
                        <?php if (!empty($sermon['cover_image'])): ?>
                        <small style="color: #666; display: block; margin-top: 0.5rem;">
                            <i class="fas fa-info-circle"></i> Current image will be kept unless you upload a new one or remove it.
                        </small>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Sermon Title -->
                    <div class="form-group">
                        <label for="title">Sermon Title <span style="color: red;">*</span></label>
                        <input type="text" 
                               id="title" 
                               name="title" 
                               class="form-control" 
                               value="<?php echo htmlspecialchars($sermon['title']); ?>"
                               required>
                    </div>
                    
                    <!-- Preacher -->
                    <div class="form-group">
                        <label for="preacher">Preacher <span style="color: red;">*</span></label>
                        <input type="text" 
                               id="preacher" 
                               name="preacher" 
                               class="form-control" 
                               value="<?php echo htmlspecialchars($sermon['preacher']); ?>"
                               required>
                    </div>
                    
                    <!-- Sermon Date -->
                    <div class="form-group">
                        <label for="sermon_date">Sermon Date <span style="color: red;">*</span></label>
                        <input type="date" 
                               id="sermon_date" 
                               name="sermon_date" 
                               class="form-control" 
                               value="<?php echo htmlspecialchars($sermon['sermon_date']); ?>"
                               required>
                    </div>
                    
                    <!-- Scripture Reference -->
                    <div class="form-group">
                        <label for="scripture_reference">Scripture Reference</label>
                        <input type="text" 
                               id="scripture_reference" 
                               name="scripture_reference" 
                               class="form-control" 
                               value="<?php echo htmlspecialchars($sermon['scripture_reference']); ?>">
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
                               value="<?php echo htmlspecialchars($sermon['video_url']); ?>">
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
                               value="<?php echo htmlspecialchars($sermon['audio_url']); ?>">
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
                                  maxlength="1000"><?php echo htmlspecialchars($sermon['description']); ?></textarea>
                        <small style="color: #666; display: block; margin-top: 0.25rem;">
                            Maximum 1000 characters
                        </small>
                    </div>
                </div>
            </div>
            
            <!-- Form Actions -->
            <div style="display: flex; gap: 1rem; margin-top: 2rem; padding-top: 1.5rem; border-top: 1px solid #e0e5eb;">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> Update Sermon
                </button>
                <a href="manage-sermons.php" class="btn btn-secondary">
                    <i class="fas fa-times"></i> Cancel
                </a>
                <a href="?delete=<?php echo $sermon_id; ?>" 
                   class="btn btn-danger btn-delete" 
                   data-item="<?php echo htmlspecialchars($sermon['title']); ?>"
                   style="margin-left: auto;">
                    <i class="fas fa-trash"></i> Delete Sermon
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
let hasExistingImage = <?php echo !empty($sermon['cover_image']) ? 'true' : 'false'; ?>;
let existingImagePath = '<?php echo !empty($sermon['cover_image']) ? '../' . htmlspecialchars($sermon['cover_image']) : ''; ?>';

function previewImage(input) {
    const container = document.getElementById('coverImageUpload');
    const preview = document.getElementById('imagePreview');
    const uploadPreview = document.getElementById('uploadPreview');
    const removeBtn = document.getElementById('removeImageBtn');
    const removeHidden = document.getElementById('remove_cover_image');
    
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
    const input = document.getElementById('cover_image');
    const removeHidden = document.getElementById('remove_cover_image');
    
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