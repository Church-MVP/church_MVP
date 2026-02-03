<?php
/**
 * Edit Post
 * 
 * Form to edit an existing blog post
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

// Get post ID - also must be checked before any HTML output
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: content.php");
    exit();
}

$post_id = (int)$_GET['id'];

// Fetch post data - check before HTML output so we can redirect if not found
try {
    $stmt = $pdo->prepare("SELECT * FROM posts WHERE id = ?");
    $stmt->execute([$post_id]);
    $post = $stmt->fetch();
    
    if (!$post) {
        header("Location: content.php");
        exit();
    }
} catch (PDOException $e) {
    header("Location: content.php");
    exit();
}

// Set page title
$page_title = 'Edit Post';

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

// Get current target pages
$current_target_pages = json_decode($post['target_pages'] ?? '[]', true) ?: [];

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Sanitize inputs
    $title = sanitize_input($_POST['title']);
    $content = $_POST['content']; // Allow HTML for rich text
    $excerpt = sanitize_input($_POST['excerpt']);
    $status = sanitize_input($_POST['status']);
    $show_on_homepage = isset($_POST['show_on_homepage']) ? 1 : 0;
    $target_pages = isset($_POST['target_pages']) ? $_POST['target_pages'] : [];
    $featured_image = $post['featured_image']; // Keep existing by default
    
    // Check if user wants to remove the image
    $remove_image = isset($_POST['remove_featured_image']) && $_POST['remove_featured_image'] == '1';
    
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
                            // Delete old image
                            if (!empty($post['featured_image']) && file_exists('../' . $post['featured_image'])) {
                                unlink('../' . $post['featured_image']);
                            }
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
            } elseif ($remove_image) {
                // Remove current image
                if (!empty($post['featured_image']) && file_exists('../' . $post['featured_image'])) {
                    unlink('../' . $post['featured_image']);
                }
                $featured_image = '';
            }
            
            // Only proceed if no upload errors
            if (empty($error_message)) {
                // Auto-generate excerpt if empty
                if (empty($excerpt)) {
                    $excerpt = substr(strip_tags($content), 0, 300);
                }
                
                // Set published_at if publishing for first time
                $published_at = $post['published_at'];
                if ($status == 'published' && empty($post['published_at'])) {
                    $published_at = date('Y-m-d H:i:s');
                }
                
                // Update post
                $stmt = $pdo->prepare("UPDATE posts SET title = ?, content = ?, excerpt = ?, featured_image = ?, status = ?, show_on_homepage = ?, target_pages = ?, published_at = ?, updated_at = NOW() WHERE id = ?");
                $stmt->execute([
                    $title,
                    $content,
                    $excerpt,
                    $featured_image,
                    $status,
                    $show_on_homepage,
                    json_encode($target_pages),
                    $published_at,
                    $post_id
                ]);
                
                $success_message = 'Post updated successfully!';
                
                // Refresh post data
                $stmt = $pdo->prepare("SELECT * FROM posts WHERE id = ?");
                $stmt->execute([$post_id]);
                $post = $stmt->fetch();
                $current_target_pages = json_decode($post['target_pages'] ?? '[]', true) ?: [];
            }
        } catch (PDOException $e) {
            $error_message = 'Error updating post. Please try again.';
        }
    }
}
?>

<!-- Page Header -->
<div class="page-header">
    <h2><i class="fas fa-edit"></i> Edit Post</h2>
    <p>Update your blog post</p>
</div>

<!-- Success/Error Messages -->
<?php if (!empty($success_message)): ?>
<div class="alert alert-success">
    <i class="fas fa-check-circle"></i> <?php echo $success_message; ?>
    <a href="content.php" style="margin-left: 1rem;">Back to all posts</a>
    <?php if ($post['status'] == 'published'): ?>
    <a href="../index.php#post-<?php echo $post['id']; ?>" target="_blank" style="margin-left: 1rem;">View post</a>
    <?php endif; ?>
</div>
<?php endif; ?>

<?php if (!empty($error_message)): ?>
<div class="alert alert-danger">
    <i class="fas fa-exclamation-circle"></i> <?php echo $error_message; ?>
</div>
<?php endif; ?>

<!-- Post Form -->
<form method="POST" action="" enctype="multipart/form-data">
    <input type="hidden" name="remove_featured_image" id="remove_featured_image" value="0">
    
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
                               value="<?php echo htmlspecialchars($post['title']); ?>"
                               style="font-size: 1.25rem; padding: 0.75rem;"
                               required>
                    </div>
                    
                    <div style="margin-top: 0.5rem;">
                        <small style="color: #666;">
                            <strong>Slug:</strong> <?php echo htmlspecialchars($post['slug']); ?>
                        </small>
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
                                  required><?php echo htmlspecialchars($post['content']); ?></textarea>
                    </div>
                    <small style="color: #666;">You can use HTML for formatting.</small>
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
                                  maxlength="500"><?php echo htmlspecialchars($post['excerpt']); ?></textarea>
                    </div>
                    <small style="color: #666;">A short summary. Max 500 characters.</small>
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
                            <option value="draft" <?php echo $post['status'] == 'draft' ? 'selected' : ''; ?>>Draft</option>
                            <option value="published" <?php echo $post['status'] == 'published' ? 'selected' : ''; ?>>Published</option>
                        </select>
                    </div>
                    
                    <div style="margin-top: 1rem; padding: 0.75rem; background: #f8f9fa; border-radius: 6px; font-size: 0.85rem; color: #666;">
                        <p style="margin: 0 0 0.5rem 0;"><strong>Created:</strong> <?php echo date('M j, Y \a\t g:i A', strtotime($post['created_at'])); ?></p>
                        <?php if ($post['updated_at']): ?>
                        <p style="margin: 0 0 0.5rem 0;"><strong>Updated:</strong> <?php echo date('M j, Y \a\t g:i A', strtotime($post['updated_at'])); ?></p>
                        <?php endif; ?>
                        <?php if ($post['published_at']): ?>
                        <p style="margin: 0;"><strong>Published:</strong> <?php echo date('M j, Y \a\t g:i A', strtotime($post['published_at'])); ?></p>
                        <?php endif; ?>
                    </div>
                    
                    <div style="display: flex; gap: 0.75rem; margin-top: 1rem;">
                        <button type="submit" class="btn btn-primary" style="flex: 1;">
                            <i class="fas fa-save"></i> Update Post
                        </button>
                    </div>
                    
                    <div style="margin-top: 1rem; padding-top: 1rem; border-top: 1px solid #e0e5eb; display: flex; gap: 0.5rem;">
                        <a href="content.php" class="btn btn-secondary" style="flex: 1;">
                            <i class="fas fa-arrow-left"></i> Back
                        </a>
                        <?php if ($post['status'] == 'published'): ?>
                        <a href="../index.php#post-<?php echo $post['id']; ?>" target="_blank" class="btn btn-info" style="flex: 1;">
                            <i class="fas fa-external-link-alt"></i> View
                        </a>
                        <?php endif; ?>
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
                        <div class="cover-image-upload <?php echo !empty($post['featured_image']) ? 'has-image' : ''; ?>" id="coverImageUpload">
                            <div class="upload-preview" id="uploadPreview" <?php echo !empty($post['featured_image']) ? 'style="display: none;"' : ''; ?>>
                                <i class="fas fa-image"></i>
                                <span>Click to upload</span>
                                <small>JPG, PNG, GIF, WebP (Max 5MB)</small>
                            </div>
                            <img id="imagePreview" 
                                 src="<?php echo !empty($post['featured_image']) ? '../' . htmlspecialchars($post['featured_image']) : ''; ?>" 
                                 alt="Preview" 
                                 <?php echo empty($post['featured_image']) ? 'style="display: none;"' : ''; ?>>
                            <input type="file" 
                                   id="featured_image" 
                                   name="featured_image" 
                                   accept="image/jpeg,image/png,image/gif,image/webp"
                                   onchange="previewImage(this)">
                            <button type="button" 
                                    class="btn-remove-image" 
                                    id="removeImageBtn" 
                                    onclick="removeImage()" 
                                    <?php echo empty($post['featured_image']) ? 'style="display: none;"' : ''; ?>>
                                <i class="fas fa-times"></i>
                            </button>
                        </div>
                    </div>
                    <?php if (!empty($post['featured_image'])): ?>
                    <small style="color: #666; display: block; margin-top: 0.5rem;">
                        <i class="fas fa-info-circle"></i> Upload a new image to replace, or click X to remove.
                    </small>
                    <?php endif; ?>
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
                                   <?php echo $post['show_on_homepage'] ? 'checked' : ''; ?>
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
                                       <?php echo in_array($key, $current_target_pages) ? 'checked' : ''; ?>
                                       style="width: 16px; height: 16px;">
                                <?php echo $label; ?>
                            </label>
                            <?php endif; ?>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Danger Zone -->
            <div class="card" style="border: 1px solid #f5c6cb;">
                <div class="card-header" style="background: #f8d7da;">
                    <h3 style="color: #721c24;"><i class="fas fa-exclamation-triangle"></i> Danger Zone</h3>
                </div>
                <div class="card-body">
                    <p style="font-size: 0.9rem; color: #666; margin-bottom: 1rem;">
                        Permanently delete this post. This action cannot be undone.
                    </p>
                    <a href="content.php?delete=<?php echo $post['id']; ?>" 
                       class="btn btn-danger" 
                       style="width: 100%;"
                       onclick="return confirm('Are you sure you want to delete this post? This action cannot be undone.');">
                        <i class="fas fa-trash"></i> Delete Post
                    </a>
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
let hasExistingImage = <?php echo !empty($post['featured_image']) ? 'true' : 'false'; ?>;

function previewImage(input) {
    const container = document.getElementById('coverImageUpload');
    const preview = document.getElementById('imagePreview');
    const uploadPreview = document.getElementById('uploadPreview');
    const removeBtn = document.getElementById('removeImageBtn');
    const removeHidden = document.getElementById('remove_featured_image');
    
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
            removeHidden.value = '0';
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
    const removeHidden = document.getElementById('remove_featured_image');
    
    input.value = '';
    preview.src = '';
    preview.style.display = 'none';
    uploadPreview.style.display = 'flex';
    removeBtn.style.display = 'none';
    container.classList.remove('has-image');
    
    if (hasExistingImage) {
        removeHidden.value = '1';
    }
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