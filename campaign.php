<?php
/**
 * Single Campaign Page - campaign.php
 * 
 * Displays full campaign details and donation form
 * Accessible via /campaign.php?slug=campaign-slug
 */

// Include database connection
require_once 'includes/db.php';

/**
 * ===============================
 * Get Campaign by Slug
 * ===============================
 */
$slug = isset($_GET['slug']) ? sanitize_input($_GET['slug']) : '';

if (empty($slug)) {
    header('Location: donate.php');
    exit;
}

// Fetch campaign
$stmt = $pdo->prepare("
    SELECT dc.*, 
           (SELECT COUNT(*) FROM donations WHERE campaign_id = dc.id) as donor_count
    FROM donation_campaigns dc
    WHERE dc.slug = ? AND dc.is_active = 1
");
$stmt->execute([$slug]);
$campaign = $stmt->fetch(PDO::FETCH_ASSOC);

// If campaign not found or inactive, redirect
if (!$campaign) {
    header('Location: donate.php');
    exit;
}

// Check if campaign has started
if (!empty($campaign['start_date']) && strtotime($campaign['start_date']) > time()) {
    header('Location: donate.php');
    exit;
}

// Check if campaign has ended
$campaign_ended = false;
if (!empty($campaign['end_date']) && strtotime($campaign['end_date']) < time()) {
    $campaign_ended = true;
}

// Set page title
$page_title = $campaign['title'] . ' - Donate';

/**
 * ===============================
 * Calculate Progress
 * ===============================
 */
$progress = 0;
if ($campaign['goal_amount'] > 0) {
    $progress = min(100, ($campaign['current_amount'] / $campaign['goal_amount']) * 100);
}

/**
 * ===============================
 * Parse Suggested Amounts
 * ===============================
 */
$suggested_amounts = [];
if (!empty($campaign['suggested_amounts'])) {
    $amounts = explode(',', $campaign['suggested_amounts']);
    foreach ($amounts as $amt) {
        $amt = trim($amt);
        if (is_numeric($amt) && $amt > 0) {
            $suggested_amounts[] = floatval($amt);
        }
    }
}

/**
 * ===============================
 * Handle Form Submission
 * ===============================
 */
$success_message = '';
$error_message = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && !$campaign_ended) {
    // Sanitize and validate input
    $donor_name = sanitize_input($_POST['donor_name']);
    $donor_email = sanitize_input($_POST['donor_email']);
    $amount = floatval($_POST['amount']);
    $message = sanitize_input($_POST['message']);
    
    // Validation
    $errors = [];
    
    if (empty($donor_name)) {
        $errors[] = 'Please enter your name.';
    }
    
    if (empty($donor_email)) {
        $errors[] = 'Please enter your email address.';
    } elseif (!filter_var($donor_email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Please enter a valid email address.';
    }
    
    if ($amount <= 0) {
        $errors[] = 'Please enter a valid donation amount.';
    } elseif ($amount < $campaign['minimum_amount']) {
        $errors[] = 'Minimum donation amount is $' . number_format($campaign['minimum_amount'], 2) . '.';
    }
    
    if (empty($errors)) {
        try {
            // Insert donation record
            $stmt = $pdo->prepare("
                INSERT INTO donations (donor_name, donor_email, amount, donation_type, payment_method, message, campaign_id) 
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $donor_name,
                $donor_email,
                $amount,
                $campaign['donation_type'],
                'Pending - Payment Integration Coming Soon',
                $message,
                $campaign['id']
            ]);
            
            // Update campaign current_amount
            $stmt = $pdo->prepare("
                UPDATE donation_campaigns 
                SET current_amount = current_amount + ? 
                WHERE id = ?
            ");
            $stmt->execute([$amount, $campaign['id']]);
            
            // Update local campaign data for display
            $campaign['current_amount'] += $amount;
            $campaign['donor_count']++;
            
            // Recalculate progress
            if ($campaign['goal_amount'] > 0) {
                $progress = min(100, ($campaign['current_amount'] / $campaign['goal_amount']) * 100);
            }
            
            $success_message = 'Thank you for your generous donation of $' . number_format($amount, 2) . '! Payment integration is coming soon. Our team will contact you at ' . htmlspecialchars($donor_email) . ' with payment instructions.';
            
            // Clear form
            $_POST = [];
            
        } catch (PDOException $e) {
            $error_message = 'An error occurred while processing your donation. Please try again.';
        }
    } else {
        $error_message = implode('<br>', $errors);
    }
}

/**
 * ===============================
 * Fetch Related Campaigns
 * ===============================
 */
$stmt = $pdo->prepare("
    SELECT id, title, slug, short_description, featured_image, current_amount, goal_amount
    FROM donation_campaigns 
    WHERE is_active = 1 
      AND id != ? 
      AND (start_date IS NULL OR start_date <= CURDATE())
      AND (end_date IS NULL OR end_date >= CURDATE())
    ORDER BY is_featured DESC, RAND()
    LIMIT 3
");
$stmt->execute([$campaign['id']]);
$related_campaigns = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Include header
include 'includes/header.php';
?>

<!-- Campaign Hero -->
<section class="hero" style="min-height: 350px; padding: 4rem 1rem; position: relative; <?php echo !empty($campaign['featured_image']) ? 'background-image: linear-gradient(rgba(0,0,0,0.6), rgba(0,0,0,0.6)), url(\'uploads/campaigns/' . htmlspecialchars($campaign['featured_image']) . '\'); background-size: cover; background-position: center;' : ''; ?>">
    <div class="hero-content">
        <!-- Breadcrumb -->
        <div style="margin-bottom: 1rem;">
            <a href="donate.php" style="color: rgba(255,255,255,0.8); text-decoration: none;">
                <i class="fas fa-arrow-left"></i> Back to All Campaigns
            </a>
        </div>
        
        <!-- Category Badge -->
        <span style="display: inline-block; background: var(--primary-light); color: #fff; padding: 0.3rem 1rem; border-radius: 20px; font-size: 0.875rem; margin-bottom: 1rem;">
            <?php echo htmlspecialchars($campaign['donation_type']); ?>
        </span>
        
        <h1 style="margin-bottom: 0.5rem;"><?php echo htmlspecialchars($campaign['title']); ?></h1>
        <p style="font-size: 1.1rem; opacity: 0.9;"><?php echo htmlspecialchars($campaign['short_description']); ?></p>
        
        <?php if ($campaign['is_featured']): ?>
        <span style="display: inline-block; background: #f0ad4e; color: #fff; padding: 0.25rem 0.75rem; border-radius: 4px; font-size: 0.75rem; margin-top: 1rem;">
            <i class="fas fa-star"></i> Featured Campaign
        </span>
        <?php endif; ?>
    </div>
</section>

<!-- Main Content -->
<section class="section">
    <div class="container">
        <div style="display: grid; grid-template-columns: 1fr 400px; gap: 2rem; align-items: start;">
            
            <!-- Left Column - Campaign Details -->
            <div>
                <!-- Progress Card -->
                <?php if ($campaign['goal_amount'] > 0 && $campaign['show_progress_bar']): ?>
                <div style="background: #fff; padding: 1.5rem; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); margin-bottom: 2rem;">
                    <div style="display: flex; justify-content: space-between; align-items: flex-end; margin-bottom: 1rem;">
                        <div>
                            <span style="font-size: 2.5rem; font-weight: bold; color: #28a745;">
                                $<?php echo number_format($campaign['current_amount'], 0); ?>
                            </span>
                            <span style="color: #666; font-size: 1.1rem;">
                                raised of $<?php echo number_format($campaign['goal_amount'], 0); ?> goal
                            </span>
                        </div>
                        <div style="text-align: right;">
                            <span style="font-size: 1.5rem; font-weight: bold; color: var(--primary-dark);">
                                <?php echo number_format($progress, 0); ?>%
                            </span>
                        </div>
                    </div>
                    
                    <!-- Progress Bar -->
                    <div style="background: #e0e5eb; height: 20px; border-radius: 10px; overflow: hidden; margin-bottom: 1rem;">
                        <div style="width: <?php echo $progress; ?>%; background: <?php echo $progress >= 100 ? '#28a745' : 'linear-gradient(90deg, var(--primary-light), var(--accent-blue))'; ?>; height: 100%; transition: width 0.5s; border-radius: 10px;"></div>
                    </div>
                    
                    <!-- Stats -->
                    <div style="display: flex; gap: 2rem;">
                        <?php if ($campaign['show_donor_count']): ?>
                        <div>
                            <i class="fas fa-users" style="color: var(--primary-light);"></i>
                            <strong><?php echo number_format($campaign['donor_count']); ?></strong>
                            <span style="color: #666;">donors</span>
                        </div>
                        <?php endif; ?>
                        
                        <?php if (!empty($campaign['end_date'])): ?>
                        <?php
                            $days_left = max(0, floor((strtotime($campaign['end_date']) - time()) / 86400));
                        ?>
                        <div>
                            <i class="fas fa-clock" style="color: <?php echo $days_left <= 7 ? '#dc3545' : 'var(--primary-light)'; ?>;"></i>
                            <strong><?php echo $days_left; ?></strong>
                            <span style="color: #666;">days left</span>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
                <?php elseif ($campaign['show_donor_count'] || $campaign['current_amount'] > 0): ?>
                <div style="background: #fff; padding: 1.5rem; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); margin-bottom: 2rem;">
                    <div style="display: flex; gap: 2rem; align-items: center;">
                        <div>
                            <span style="font-size: 2rem; font-weight: bold; color: #28a745;">
                                $<?php echo number_format($campaign['current_amount'], 0); ?>
                            </span>
                            <span style="color: #666;">raised</span>
                        </div>
                        <?php if ($campaign['show_donor_count']): ?>
                        <div>
                            <i class="fas fa-users" style="color: var(--primary-light);"></i>
                            <strong><?php echo number_format($campaign['donor_count']); ?></strong>
                            <span style="color: #666;">donors</span>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endif; ?>
                
                <!-- Campaign Description -->
                <div style="background: #fff; padding: 2rem; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.1);">
                    <h2 style="color: var(--primary-dark); margin-bottom: 1.5rem;">
                        <i class="fas fa-info-circle" style="color: var(--primary-light);"></i> About This Campaign
                    </h2>
                    
                    <?php if (!empty($campaign['description'])): ?>
                    <div style="line-height: 1.8; color: #444;">
                        <?php echo nl2br(htmlspecialchars($campaign['description'])); ?>
                    </div>
                    <?php else: ?>
                    <p style="color: #666;"><?php echo htmlspecialchars($campaign['short_description']); ?></p>
                    <?php endif; ?>
                    
                    <!-- Campaign Dates -->
                    <?php if (!empty($campaign['start_date']) || !empty($campaign['end_date'])): ?>
                    <div style="margin-top: 2rem; padding-top: 1.5rem; border-top: 1px solid #e0e5eb;">
                        <h4 style="color: var(--primary-dark); margin-bottom: 0.75rem;">
                            <i class="fas fa-calendar-alt" style="color: var(--primary-light);"></i> Campaign Period
                        </h4>
                        <p style="color: #666; margin: 0;">
                            <?php if (!empty($campaign['start_date'])): ?>
                            <strong>Started:</strong> <?php echo date('F j, Y', strtotime($campaign['start_date'])); ?>
                            <?php endif; ?>
                            <?php if (!empty($campaign['start_date']) && !empty($campaign['end_date'])): ?> &bull; <?php endif; ?>
                            <?php if (!empty($campaign['end_date'])): ?>
                            <strong>Ends:</strong> <?php echo date('F j, Y', strtotime($campaign['end_date'])); ?>
                            <?php endif; ?>
                        </p>
                    </div>
                    <?php endif; ?>
                </div>
                
                <!-- Social Sharing -->
                <div style="background: #fff; padding: 1.5rem; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); margin-top: 2rem;">
                    <h4 style="color: var(--primary-dark); margin-bottom: 1rem;">
                        <i class="fas fa-share-alt" style="color: var(--primary-light);"></i> Share This Campaign
                    </h4>
                    <div style="display: flex; gap: 0.5rem;">
                        <?php
                            $share_url = urlencode((isset($_SERVER['HTTPS']) ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']);
                            $share_title = urlencode($campaign['title']);
                            $share_text = urlencode($campaign['short_description']);
                        ?>
                        <a href="https://www.facebook.com/sharer/sharer.php?u=<?php echo $share_url; ?>" 
                           target="_blank"
                           class="btn btn-secondary" 
                           style="background: #1877f2; border-color: #1877f2; color: #fff;">
                            <i class="fab fa-facebook-f"></i>
                        </a>
                        <a href="https://twitter.com/intent/tweet?url=<?php echo $share_url; ?>&text=<?php echo $share_title; ?>" 
                           target="_blank"
                           class="btn btn-secondary" 
                           style="background: #1da1f2; border-color: #1da1f2; color: #fff;">
                            <i class="fab fa-twitter"></i>
                        </a>
                        <a href="https://www.linkedin.com/shareArticle?mini=true&url=<?php echo $share_url; ?>&title=<?php echo $share_title; ?>" 
                           target="_blank"
                           class="btn btn-secondary" 
                           style="background: #0077b5; border-color: #0077b5; color: #fff;">
                            <i class="fab fa-linkedin-in"></i>
                        </a>
                        <a href="mailto:?subject=<?php echo $share_title; ?>&body=<?php echo $share_text; ?>%0A%0A<?php echo $share_url; ?>" 
                           class="btn btn-secondary">
                            <i class="fas fa-envelope"></i>
                        </a>
                        <button type="button" 
                                class="btn btn-secondary"
                                onclick="navigator.clipboard.writeText(window.location.href); alert('Link copied to clipboard!');">
                            <i class="fas fa-link"></i>
                        </button>
                    </div>
                </div>
            </div>
            
            <!-- Right Column - Donation Form -->
            <div style="position: sticky; top: 2rem;">
                <div style="background: #fff; padding: 2rem; border-radius: 8px; box-shadow: 0 4px 15px rgba(0,0,0,0.1);">
                    
                    <?php if ($campaign_ended): ?>
                    <!-- Campaign Ended Notice -->
                    <div style="text-align: center; padding: 2rem;">
                        <i class="fas fa-calendar-times" style="font-size: 3rem; color: #999; margin-bottom: 1rem;"></i>
                        <h3 style="color: #666;">Campaign Has Ended</h3>
                        <p style="color: #999;">This campaign ended on <?php echo date('F j, Y', strtotime($campaign['end_date'])); ?>. Thank you to all who contributed!</p>
                        <a href="donate.php" class="btn btn-primary" style="margin-top: 1rem;">
                            <i class="fas fa-heart"></i> View Other Campaigns
                        </a>
                    </div>
                    
                    <?php else: ?>
                    
                    <h3 style="color: var(--primary-dark); margin-bottom: 1.5rem; text-align: center;">
                        <i class="fas fa-heart" style="color: var(--primary-light);"></i> Make a Donation
                    </h3>
                    
                    <!-- Payment Integration Notice -->
                    <div class="alert alert-info" style="font-size: 0.875rem;">
                        <i class="fas fa-info-circle"></i> 
                        <strong>Coming Soon:</strong> Online payment processing. Submit your intent and we'll contact you.
                    </div>
                    
                    <!-- Success Message -->
                    <?php if (!empty($success_message)): ?>
                    <div class="alert alert-success">
                        <i class="fas fa-check-circle"></i> <?php echo $success_message; ?>
                    </div>
                    <?php endif; ?>
                    
                    <!-- Error Message -->
                    <?php if (!empty($error_message)): ?>
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-circle"></i> <?php echo $error_message; ?>
                    </div>
                    <?php endif; ?>
                    
                    <!-- Donation Form -->
                    <form method="POST" action="">
                        <!-- Suggested Amount Buttons -->
                        <?php if (!empty($suggested_amounts)): ?>
                        <div class="form-group">
                            <label>Select Amount</label>
                            <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 0.5rem;">
                                <?php foreach ($suggested_amounts as $amt): ?>
                                <button type="button" 
                                        class="btn btn-secondary amount-btn"
                                        onclick="selectAmount(<?php echo $amt; ?>)"
                                        data-amount="<?php echo $amt; ?>">
                                    $<?php echo number_format($amt, 0); ?>
                                </button>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        <?php endif; ?>
                        
                        <!-- Custom Amount -->
                        <div class="form-group">
                            <label for="amount">Donation Amount ($) <span style="color: red;">*</span></label>
                            <input type="number" 
                                   id="amount" 
                                   name="amount" 
                                   class="form-control" 
                                   min="<?php echo $campaign['minimum_amount']; ?>" 
                                   step="0.01"
                                   value="<?php echo isset($_POST['amount']) ? htmlspecialchars($_POST['amount']) : ''; ?>"
                                   placeholder="Enter amount"
                                   required
                                   style="font-size: 1.25rem; padding: 0.75rem;">
                            <?php if ($campaign['minimum_amount'] > 1): ?>
                            <small style="color: #666;">Minimum: $<?php echo number_format($campaign['minimum_amount'], 2); ?></small>
                            <?php endif; ?>
                        </div>
                        
                        <!-- Donor Name -->
                        <div class="form-group">
                            <label for="donor_name">Full Name <span style="color: red;">*</span></label>
                            <input type="text" 
                                   id="donor_name" 
                                   name="donor_name" 
                                   class="form-control" 
                                   value="<?php echo isset($_POST['donor_name']) ? htmlspecialchars($_POST['donor_name']) : ''; ?>"
                                   placeholder="Your full name"
                                   required>
                        </div>
                        
                        <!-- Donor Email -->
                        <div class="form-group">
                            <label for="donor_email">Email Address <span style="color: red;">*</span></label>
                            <input type="email" 
                                   id="donor_email" 
                                   name="donor_email" 
                                   class="form-control" 
                                   value="<?php echo isset($_POST['donor_email']) ? htmlspecialchars($_POST['donor_email']) : ''; ?>"
                                   placeholder="your@email.com"
                                   required>
                            <small style="color: #666;">For receipt and payment instructions</small>
                        </div>
                        
                        <!-- Message (Optional) -->
                        <div class="form-group">
                            <label for="message">Message (Optional)</label>
                            <textarea id="message" 
                                      name="message" 
                                      class="form-control" 
                                      rows="3" 
                                      placeholder="Share why you're giving or any prayer requests..."><?php echo isset($_POST['message']) ? htmlspecialchars($_POST['message']) : ''; ?></textarea>
                        </div>
                        
                        <!-- Submit Button -->
                        <button type="submit" class="btn btn-primary" style="width: 100%; padding: 1rem; font-size: 1.1rem;">
                            <i class="fas fa-heart"></i> Donate Now
                        </button>
                        
                        <p style="text-align: center; margin-top: 1rem; color: #666; font-size: 0.8rem;">
                            <i class="fas fa-lock"></i> Your information is secure
                        </p>
                    </form>
                    
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Related Campaigns -->
<?php if (!empty($related_campaigns)): ?>
<section class="section" style="background-color: #f8f9fa;">
    <div class="container">
        <h2 class="section-title">Other Ways to Give</h2>
        <p class="section-subtitle">Explore more campaigns you can support</p>
        
        <div class="card-grid">
            <?php foreach ($related_campaigns as $related): ?>
            <?php
                $rel_progress = 0;
                if ($related['goal_amount'] > 0) {
                    $rel_progress = min(100, ($related['current_amount'] / $related['goal_amount']) * 100);
                }
            ?>
            <div class="card" style="overflow: hidden;">
                <?php if (!empty($related['featured_image'])): ?>
                <div style="height: 150px; overflow: hidden;">
                    <img src="uploads/campaigns/<?php echo htmlspecialchars($related['featured_image']); ?>" 
                         alt="<?php echo htmlspecialchars($related['title']); ?>"
                         style="width: 100%; height: 100%; object-fit: cover;">
                </div>
                <?php else: ?>
                <div style="height: 150px; background: linear-gradient(135deg, var(--primary-dark), var(--accent-blue)); display: flex; align-items: center; justify-content: center;">
                    <i class="fas fa-hand-holding-heart" style="font-size: 2.5rem; color: rgba(255,255,255,0.3);"></i>
                </div>
                <?php endif; ?>
                
                <div class="card-content">
                    <h3 class="card-title"><?php echo htmlspecialchars($related['title']); ?></h3>
                    <p class="card-text" style="font-size: 0.875rem; color: #666;">
                        <?php echo htmlspecialchars(substr($related['short_description'], 0, 100)); ?>...
                    </p>
                    
                    <?php if ($related['goal_amount'] > 0): ?>
                    <div style="margin-bottom: 1rem;">
                        <div style="background: #e0e5eb; height: 6px; border-radius: 3px; overflow: hidden;">
                            <div style="width: <?php echo $rel_progress; ?>%; background: var(--primary-light); height: 100%;"></div>
                        </div>
                        <small style="color: #666;">
                            $<?php echo number_format($related['current_amount'], 0); ?> of $<?php echo number_format($related['goal_amount'], 0); ?>
                        </small>
                    </div>
                    <?php endif; ?>
                    
                    <a href="campaign.php?slug=<?php echo htmlspecialchars($related['slug']); ?>" class="btn btn-secondary" style="width: 100%;">
                        Learn More
                    </a>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        
        <div style="text-align: center; margin-top: 2rem;">
            <a href="donate.php" class="btn btn-primary">
                <i class="fas fa-th-large"></i> View All Campaigns
            </a>
        </div>
    </div>
</section>
<?php endif; ?>

<!-- JavaScript for Amount Selection -->
<script>
function selectAmount(amount) {
    // Set the amount field
    document.getElementById('amount').value = amount;
    
    // Update button states
    document.querySelectorAll('.amount-btn').forEach(function(btn) {
        btn.classList.remove('btn-primary');
        btn.classList.add('btn-secondary');
    });
    
    // Highlight selected button
    document.querySelector('.amount-btn[data-amount="' + amount + '"]').classList.remove('btn-secondary');
    document.querySelector('.amount-btn[data-amount="' + amount + '"]').classList.add('btn-primary');
}

// Update button state when amount is manually changed
document.getElementById('amount').addEventListener('input', function() {
    var value = parseFloat(this.value);
    
    document.querySelectorAll('.amount-btn').forEach(function(btn) {
        if (parseFloat(btn.dataset.amount) === value) {
            btn.classList.remove('btn-secondary');
            btn.classList.add('btn-primary');
        } else {
            btn.classList.remove('btn-primary');
            btn.classList.add('btn-secondary');
        }
    });
});
</script>

<!-- Card Hover CSS -->
<style>
.card:hover {
    transform: translateY(-5px);
    box-shadow: 0 8px 25px rgba(0,0,0,0.15);
    transition: all 0.3s;
}

@media (max-width: 900px) {
    section.section > .container > div[style*="grid-template-columns: 1fr 400px"] {
        grid-template-columns: 1fr !important;
    }
    
    section.section > .container > div > div[style*="position: sticky"] {
        position: static !important;
    }
}
</style>

<?php
// Include footer
include 'includes/footer.php';
?>