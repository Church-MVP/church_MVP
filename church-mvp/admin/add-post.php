<?php
/**
 * Add New Post
 * 
 * Form to create a new blog post
 */

// Include database connection
require_once '../includes/db.php';

// Include authentication check
require_once '../includes/auth.php';

// Include permissions system
require_once 'includes/permissions.php';

// Check permission BEFORE including admin-header.php (which outputs HTML)
$allowed_roles = ['admin', 'super_admin', 'content_manager'];
if (!in_array($_SESSION['admin_role'] ?? '', $allowed_roles)) {
    header("Location: content.php");
    exit();
}

// Set page title
$page_title = 'Add New Post';

// Include admin header (this outputs HTML, so must come after any redirects)
include 'includes/admin-header.php';

// Initialize messages
$success_message = '';
$error_message = '';

// Available pages for targeting
$available_pages = [
    'home' => 'Homepage',
    'about' => 'About Page',
    'services' => 'Services Page',
    'contact' => 'Contact Page',
    'live' => 'Live Stream Page',
    'donate' => 'Donate Page'
];

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Sanitize inputs
    $title = sanitize_input($_POST['title']);
    $content = $_POST['content']; // Allow HTML for rich text
    $excerpt = sanitize_input($_POST['excerpt']);
    $status = sanitize_input($_POST['status']);
    $show_on_homepage = isset($_POST['show_on_homepage']) ? 1 : 0;
    $target_pages = isset($_POST['target_pages']) ? $_POST['target_pages'] : [];
    $featured_image = '';
    
    // Generate slug
    $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $title), '-'));
    
    // Check for unique slug
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM posts WHERE slug = ?");
    $stmt->execute([$slug]);
    if ($stmt->fetchColumn() > 0) {
        $slug .= '-' . time();
    }
    
    // Validation
    if (empty($title)) {
        $error_message = 'Please enter a post title.';
    } elseif (empty($content)) {
        $error_message = 'Please enter post content.';
    } else {
        try {
            // Handle featured image upload
            if (!empty($_FILES['featured_image']) && $_FILES['featured_image']['error'] == UPLOAD_ERR_OK) {
                $upload_dir = '../assets/images/posts/';
                
                // Create directory if it doesn't exist
                if (!is_dir($upload_dir)) {
                    mkdir($upload_dir, 0755, true);
                }
                
                $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
                $file = $_FILES['featured_image'];
                
                if (in_array($file['type'], $allowed_types)) {
                    if ($file['size'] <= 5 * 1024 * 1024) {
                        $file_extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
                        $new_filename = 'post_' . time() . '_' . uniqid() . '.' . $file_extension;
                        $upload_path = $upload_dir . $new_filename;
                        
                        if (move_uploaded_file($file['tmp_name'], $upload_path)) {
                            $featured_image = 'assets/images/posts/' . $new_filename;
                        } else {
                            $error_message = 'Error uploading image. Please try again.';
                        }
                    } else {
                        $error_message = 'Image must be less than 5MB.';
                    }
                } else {
                    $error_message = 'Invalid image type. Please use JPG, PNG, GIF, or WebP.';
                }
            }
            
            // Only proceed if no upload errors
            if (empty($error_message)) {
                // Auto-generate excerpt if empty
                if (empty($excerpt)) {
                    $excerpt = substr(strip_tags($content), 0, 300);
                }
                
                // Set published_at if publishing
                $published_at = ($status == 'published') ? date('Y-m-d H:i:s') : null;
                
                // Insert post (using admin_id from session)
                $stmt = $pdo->prepare("INSERT INTO posts (title, slug, content, excerpt, featured_image, author_id, status, show_on_homepage, target_pages, published_at, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())");
                $stmt->execute([
                    $title,
                    $slug,
                    $content,
                    $excerpt,
                    $featured_image,
                    $_SESSION['admin_id'],
                    $status,
                    $show_on_homepage,
                    json_encode($target_pages),
                    $published_at
                ]);
                
                $success_message = 'Post created successfully!';
                
                // Clear form on success
                $_POST = [];
            }
        } catch (PDOException $e) {
            $error_message = 'Error creating post. Please try again.';
        }
    }
}
?>

<!-- Page Header -->
<div class="page-header">
    <h2><i class="fas fa-plus-circle"></i> Add New Post</h2>
    <p>Create a new blog post for your website</p>
</div>

<!-- Success/Error Messages -->
<?php if (!empty($success_message)): ?>
<div class="alert alert-success">
    <i class="fas fa-check-circle"></i> <?php echo $success_message; ?>
    <a href="content.php" style="margin-left: 1rem;">View all posts</a>
</div>
<?php endif; ?>

<?php if (!empty($error_message)): ?>
<div class="alert alert-danger">
    <i class="fas fa-exclamation-circle"></i> <?php echo $error_message; ?>
</div>
<?php endif; ?>

<!-- Post Form -->
<form method="POST" action="" enctype="multipart/form-data">
    <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 1.5rem;">
        <!-- Main Content Column -->
        <div>
            <!-- Post Title -->
            <div class="card">
                <div class="card-body">
                    <div class="form-group">
                        <label for="title">Post Title <span style="color: red;">*</span></label>
                        <input type="text" 
                               id="title" 
                               name="title" 
                               class="form-control" 
                               value="<?php echo isset($_POST['title']) ? htmlspecialchars($_POST['title']) : ''; ?>"
                               placeholder="Enter your post title..."
                               style="font-size: 1.25rem; padding: 0.75rem;"
                               required>
                    </div>
                </div>
            </div>
            
            <!-- Post Content -->
            <div class="card">
                <div class="card-header">
                    <h3><i class="fas fa-align-left"></i> Post Content <span style="color: red;">*</span></h3>
                </div>
                <div class="card-body">
                    <div class="form-group">
                        <textarea id="content" 
                                  name="content" 
                                  class="form-control rich-editor"
                                  rows="15"
                                  placeholder="Write your post content here..."
                                  required><?php echo isset($_POST['content']) ? htmlspecialchars($_POST['content']) : ''; ?></textarea>
                    </div>
                    <small style="color: #666;">You can use HTML for formatting. Basic tags like &lt;p&gt;, &lt;strong&gt;, &lt;em&gt;, &lt;ul&gt;, &lt;ol&gt;, &lt;a&gt; are supported.</small>
                </div>
            </div>
            
            <!-- Excerpt -->
            <div class="card">
                <div class="card-header">
                    <h3><i class="fas fa-file-alt"></i> Excerpt</h3>
                </div>
                <div class="card-body">
                    <div class="form-group">
                        <textarea id="excerpt" 
                                  name="excerpt" 
                                  class="form-control"
                                  rows="3"
                                  maxlength="500"
                                  placeholder="Brief summary of your post (auto-generated if left empty)..."><?php echo isset($_POST['excerpt']) ? htmlspecialchars($_POST['excerpt']) : ''; ?></textarea>
                    </div>
                    <small style="color: #666;">A short summary that appears in post listings. Max 500 characters.</small>
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
                    <div class="form-group">
                        <label for="status">Status</label>
                        <select id="status" name="status" class="form-control">
                            <option value="draft" <?php echo (isset($_POST['status']) && $_POST['status'] == 'draft') ? 'selected' : ''; ?>>Draft</option>
                            <option value="published" <?php echo (isset($_POST['status']) && $_POST['status'] == 'published') ? 'selected' : ''; ?>>Published</option>
                        </select>
                    </div>
                    
                    <div style="display: flex; gap: 0.75rem; margin-top: 1rem;">
                        <button type="submit" class="btn btn-primary" style="flex: 1;">
                            <i class="fas fa-save"></i> Save Post
                        </button>
                    </div>
                    
                    <div style="margin-top: 1rem; padding-top: 1rem; border-top: 1px solid #e0e5eb;">
                        <a href="content.php" class="btn btn-secondary" style="width: 100%;">
                            <i class="fas fa-times"></i> Cancel
                        </a>
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
                        <div class="cover-image-upload" id="coverImageUpload">
                            <div class="upload-preview" id="uploadPreview">
                                <i class="fas fa-image"></i>
                                <span>Click to upload</span>
                                <small>JPG, PNG, GIF, WebP (Max 5MB)</small>
                            </div>
                            <img id="imagePreview" src="" alt="Preview" style="display: none;">
                            <input type="file" 
                                   id="featured_image" 
                                   name="featured_image" 
                                   accept="image/jpeg,image/png,image/gif,image/webp"
                                   onchange="previewImage(this)">
                            <button type="button" class="btn-remove-image" id="removeImageBtn" onclick="removeImage()" style="display: none;">
                                <i class="fas fa-times"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Display Settings -->
            <div class="card">
                <div class="card-header">
                    <h3><i class="fas fa-desktop"></i> Display Settings</h3>
                </div>
                <div class="card-body">
                    <div class="form-group">
                        <label style="display: flex; align-items: center; gap: 0.75rem; cursor: pointer; padding: 0.75rem; background: #f8f9fa; border-radius: 6px;">
                            <input type="checkbox" 
                                   name="show_on_homepage" 
                                   value="1" 
                                   checked
                                   style="width: 18px; height: 18px;">
                            <span>
                                <strong>Show on Homepage</strong>
                                <small style="display: block; color: #666;">Required for all posts</small>
                            </span>
                        </label>
                    </div>
                    
                    <div class="form-group" style="margin-top: 1rem;">
                        <label>Also Display On:</label>
                        <div style="display: flex; flex-direction: column; gap: 0.5rem; margin-top: 0.5rem;">
                            <?php foreach ($available_pages as $key => $label): ?>
                            <?php if ($key !== 'home'): ?>
                            <label style="display: flex; align-items: center; gap: 0.5rem; cursor: pointer;">
                                <input type="checkbox" 
                                       name="target_pages[]" 
                                       value="<?php echo $key; ?>"
                                       <?php echo (isset($_POST['target_pages']) && in_array($key, $_POST['target_pages'])) ? 'checked' : ''; ?>
                                       style="width: 16px; height: 16px;">
                                <?php echo $label; ?>
                            </label>
                            <?php endif; ?>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</form>

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
    padding: 2rem 1rem;
    text-align: center;
    color: #666;
}

.upload-preview i {
    font-size: 2.5rem;
    color: #adb5bd;
    margin-bottom: 0.75rem;
}

.upload-preview span {
    font-size: 0.95rem;
    font-weight: 500;
    color: #495057;
    margin-bottom: 0.25rem;
}

.upload-preview small {
    font-size: 0.75rem;
    color: #868e96;
}

.cover-image-upload img {
    width: 100%;
    height: 200px;
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
    padding: 0.4rem 0.6rem;
    border-radius: 4px;
    cursor: pointer;
    font-size: 0.8rem;
    z-index: 3;
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

.rich-editor {
    font-family: inherit;
    line-height: 1.6;
    min-height: 300px;
}

@media (max-width: 992px) {
    form > div {
        grid-template-columns: 1fr !important;
    }
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
        
        if (file.size > 5 * 1024 * 1024) {
            alert('File size must be less than 5MB');
            input.value = '';
            return;
        }
        
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
    const input = document.getElementById('featured_image');
    
    input.value = '';
    preview.src = '';
    preview.style.display = 'none';
    uploadPreview.style.display = 'flex';
    removeBtn.style.display = 'none';
    container.classList.remove('has-image');
}

// Auto-resize textarea
document.getElementById('content').addEventListener('input', function() {
    this.style.height = 'auto';
    this.style.height = Math.max(300, this.scrollHeight) + 'px';
});
</script>

<?php
// Include admin footer
include 'includes/admin-footer.php';
?>