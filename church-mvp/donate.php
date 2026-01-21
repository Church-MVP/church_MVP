<?php
/**
 * Donation Page - donate.php (Updated)
 * 
 * Displays donation campaigns as cards with progress tracking
 * Allows users to browse and select campaigns to donate to
 */

// Include database connection
require_once 'includes/db.php';

// Set page title
$page_title = 'Donate';

/**
 * ===============================
 * Filter Logic
 * ===============================
 */
$filter_type = isset($_GET['type']) ? sanitize_input($_GET['type']) : '';

// Build query for active campaigns
$where_clauses = ["is_active = 1"];
$params = [];

// Check if campaign has started (if start_date is set)
$where_clauses[] = "(start_date IS NULL OR start_date <= CURDATE())";

// Check if campaign hasn't ended (if end_date is set)
$where_clauses[] = "(end_date IS NULL OR end_date >= CURDATE())";

if (!empty($filter_type)) {
    $where_clauses[] = "donation_type = ?";
    $params[] = $filter_type;
}

$where_sql = 'WHERE ' . implode(' AND ', $where_clauses);

/**
 * ===============================
 * Fetch Active Campaigns
 * ===============================
 */
$sql = "
    SELECT dc.*, 
           (SELECT COUNT(*) FROM donations WHERE campaign_id = dc.id) as donor_count
    FROM donation_campaigns dc
    $where_sql
    ORDER BY is_featured DESC, created_at DESC
";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$campaigns = $stmt->fetchAll(PDO::FETCH_ASSOC);

/**
 * ===============================
 * Get unique donation types for filter
 * ===============================
 */
$stmt = $pdo->query("
    SELECT DISTINCT donation_type 
    FROM donation_campaigns 
    WHERE is_active = 1 
    ORDER BY donation_type
");
$donation_types = $stmt->fetchAll(PDO::FETCH_COLUMN);

/**
 * ===============================
 * Separate featured and regular campaigns
 * ===============================
 */
$featured_campaigns = array_filter($campaigns, function($c) { return $c['is_featured']; });
$regular_campaigns = array_filter($campaigns, function($c) { return !$c['is_featured']; });

// Include header
include 'includes/header.php';
?>

<!-- Page Header -->
<section class="hero" style="min-height: 300px; padding: 4rem 1rem;">
    <div class="hero-content">
        <h1><i class="fas fa-heart"></i> Give</h1>
        <p>Support our mission and ministry through generous giving</p>
    </div>
</section>

<!-- Why Give Section -->
<section class="section">
    <div class="container">
        <h2 class="section-title">Why Give?</h2>
        <p class="section-subtitle">Your generosity helps us spread God's love and serve our community</p>
        
        <div class="card-grid">
            <div class="card">
                <div class="card-content" style="text-align: center;">
                    <div style="font-size: 3rem; color: var(--primary-light); margin-bottom: 1rem;">
                        <i class="fas fa-hands-helping"></i>
                    </div>
                    <h3 class="card-title">Community Outreach</h3>
                    <p class="card-text">Your donations help us serve our local community through food banks, clothing drives, and support programs for those in need.</p>
                </div>
            </div>
            
            <div class="card">
                <div class="card-content" style="text-align: center;">
                    <div style="font-size: 3rem; color: var(--primary-light); margin-bottom: 1rem;">
                        <i class="fas fa-globe"></i>
                    </div>
                    <h3 class="card-title">Global Missions</h3>
                    <p class="card-text">Support missionaries around the world who are sharing the Gospel and making a difference in underserved communities.</p>
                </div>
            </div>
            
            <div class="card">
                <div class="card-content" style="text-align: center;">
                    <div style="font-size: 3rem; color: var(--primary-light); margin-bottom: 1rem;">
                        <i class="fas fa-users"></i>
                    </div>
                    <h3 class="card-title">Ministry Programs</h3>
                    <p class="card-text">Fund youth programs, worship services, Bible studies, and various ministries that help people grow in their faith journey.</p>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Active Campaigns Section -->
<section class="section" style="background-color: #f8f9fa;">
    <div class="container">
        <h2 class="section-title">Current Campaigns</h2>
        <p class="section-subtitle">Choose a campaign to support or make a general donation</p>
        
        <?php if (count($donation_types) > 1): ?>
        <!-- Filter Buttons -->
        <div style="display: flex; justify-content: center; gap: 0.5rem; flex-wrap: wrap; margin-bottom: 2rem;">
            <a href="donate.php" 
               class="btn <?php echo empty($filter_type) ? 'btn-primary' : 'btn-secondary'; ?>"
               style="border-radius: 20px;">
                All Campaigns
            </a>
            <?php foreach ($donation_types as $type): ?>
            <a href="donate.php?type=<?php echo urlencode($type); ?>" 
               class="btn <?php echo $filter_type === $type ? 'btn-primary' : 'btn-secondary'; ?>"
               style="border-radius: 20px;">
                <?php echo htmlspecialchars($type); ?>
            </a>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
        
        <?php if (!empty($campaigns)): ?>
        
        <?php if (!empty($featured_campaigns)): ?>
        <!-- Featured Campaigns -->
        <div style="margin-bottom: 3rem;">
            <h3 style="color: var(--primary-dark); margin-bottom: 1.5rem;">
                <i class="fas fa-star" style="color: #f0ad4e;"></i> Featured Campaigns
            </h3>
            <div class="card-grid">
                <?php foreach ($featured_campaigns as $campaign): ?>
                <?php
                    // Calculate progress
                    $progress = 0;
                    if (($campaign['goal_amount'] ?? 0) > 0) {
                        $progress = min(100, (($campaign['current_amount'] ?? 0) / ($campaign['goal_amount'] ?? 1)) * 100);
                    }
                ?>
                <div class="card" style="overflow: hidden; transition: transform 0.3s, box-shadow 0.3s;">
                    <!-- Campaign Image -->
                    <?php if (!empty($campaign['featured_image'])): ?>
                    <div style="height: 200px; overflow: hidden;">
                        <img src="uploads/campaigns/<?php echo htmlspecialchars($campaign['featured_image']); ?>" 
                             alt="<?php echo htmlspecialchars($campaign['title']); ?>"
                             style="width: 100%; height: 100%; object-fit: cover;">
                    </div>
                    <?php else: ?>
                    <div style="height: 200px; background: linear-gradient(135deg, var(--primary-dark), var(--primary-light)); display: flex; align-items: center; justify-content: center;">
                        <i class="fas fa-hand-holding-heart" style="font-size: 4rem; color: rgba(255,255,255,0.3);"></i>
                    </div>
                    <?php endif; ?>
                    
                    <div class="card-content">
                        <!-- Featured Badge -->
                        <span style="display: inline-block; background: #f0ad4e; color: #fff; padding: 0.25rem 0.75rem; border-radius: 20px; font-size: 0.75rem; margin-bottom: 0.5rem;">
                            <i class="fas fa-star"></i> Featured
                        </span>
                        
                        <!-- Title -->
                        <h3 class="card-title" style="margin-bottom: 0.5rem;">
                            <?php echo htmlspecialchars($campaign['title']); ?>
                        </h3>
                        
                        <!-- Category Badge -->
                        <span style="display: inline-block; background: var(--primary-light); color: #fff; padding: 0.2rem 0.6rem; border-radius: 4px; font-size: 0.75rem; margin-bottom: 0.75rem;">
                            <?php echo htmlspecialchars($campaign['donation_type'] ?? 'general'); ?>
                        </span>
                        
                        <!-- Short Description -->
                        <p class="card-text" style="color: #666; margin-bottom: 1rem;">
                            <?php echo htmlspecialchars($campaign['short_description'] ?? $campaign['description'] ?? 'No description available'); ?>
                        </p>
                        
                        <!-- Progress Section -->
                        <?php if (($campaign['goal_amount'] ?? 0) > 0 && ($campaign['show_progress_bar'] ?? 1)): ?>
                        <div style="margin-bottom: 1rem;">
                            <div style="display: flex; justify-content: space-between; margin-bottom: 0.5rem;">
                                <span style="font-weight: bold; color: #28a745;">
                                    $<?php echo number_format($campaign['current_amount'] ?? 0, 0); ?>
                                </span>
                                <span style="color: #666;">
                                    of $<?php echo number_format($campaign['goal_amount'] ?? 0, 0); ?> goal
                                </span>
                            </div>
                            <div style="background: #e0e5eb; height: 10px; border-radius: 5px; overflow: hidden;">
                                <div style="width: <?php echo $progress; ?>%; background: <?php echo $progress >= 100 ? '#28a745' : 'var(--primary-light)'; ?>; height: 100%; transition: width 0.5s;"></div>
                            </div>
                            <div style="display: flex; justify-content: space-between; margin-top: 0.25rem;">
                                <small style="color: #666;"><?php echo number_format($progress, 0); ?>% funded</small>
                                <?php if ($campaign['show_donor_count'] ?? 1): ?>
                                <small style="color: #666;">
                                    <i class="fas fa-users"></i> <?php echo number_format($campaign['donor_count'] ?? 0); ?> donors
                                </small>
                                <?php endif; ?>
                            </div>
                        </div>
                        <?php endif; ?>
                        
                        <!-- End Date Notice -->
                        <?php if (!empty($campaign['end_date'])): ?>
                        <?php
                            $days_left = floor((strtotime($campaign['end_date']) - time()) / 86400);
                        ?>
                        <?php if ($days_left <= 7 && $days_left >= 0): ?>
                        <p style="color: #dc3545; font-size: 0.875rem; margin-bottom: 1rem;">
                            <i class="fas fa-clock"></i> 
                            <?php echo $days_left == 0 ? 'Last day!' : $days_left . ' days left'; ?>
                        </p>
                        <?php endif; ?>
                        <?php endif; ?>
                        
                        <!-- Donate Button -->
                        <a href="campaign.php?slug=<?php echo htmlspecialchars($campaign['slug'] ?? $campaign['id'] ?? ''); ?>" 
                           class="btn btn-primary" 
                           style="width: 100%; text-align: center;">
                            <i class="fas fa-heart"></i> Donate Now
                        </a>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>
        
        <?php if (!empty($regular_campaigns)): ?>
        <!-- Regular Campaigns -->
        <div>
            <?php if (!empty($featured_campaigns)): ?>
            <h3 style="color: var(--primary-dark); margin-bottom: 1.5rem;">
                <i class="fas fa-hand-holding-heart"></i> More Campaigns
            </h3>
            <?php endif; ?>
            <div class="card-grid">
                <?php foreach ($regular_campaigns as $campaign): ?>
                <?php
                    // Calculate progress
                    $progress = 0;
                    if (($campaign['goal_amount'] ?? 0) > 0) {
                        $progress = min(100, (($campaign['current_amount'] ?? 0) / ($campaign['goal_amount'] ?? 1)) * 100);
                    }
                ?>
                <div class="card" style="overflow: hidden; transition: transform 0.3s, box-shadow 0.3s;">
                    <!-- Campaign Image -->
                    <?php if (!empty($campaign['featured_image'])): ?>
                    <div style="height: 180px; overflow: hidden;">
                        <img src="uploads/campaigns/<?php echo htmlspecialchars($campaign['featured_image']); ?>" 
                             alt="<?php echo htmlspecialchars($campaign['title']); ?>"
                             style="width: 100%; height: 100%; object-fit: cover;">
                    </div>
                    <?php else: ?>
                    <div style="height: 180px; background: linear-gradient(135deg, var(--primary-dark), var(--accent-blue)); display: flex; align-items: center; justify-content: center;">
                        <i class="fas fa-hand-holding-heart" style="font-size: 3rem; color: rgba(255,255,255,0.3);"></i>
                    </div>
                    <?php endif; ?>
                    
                    <div class="card-content">
                        <!-- Title -->
                        <h3 class="card-title" style="margin-bottom: 0.5rem;">
                            <?php echo htmlspecialchars($campaign['title']); ?>
                        </h3>
                        
                        <!-- Category Badge -->
                        <span style="display: inline-block; background: var(--primary-light); color: #fff; padding: 0.2rem 0.6rem; border-radius: 4px; font-size: 0.75rem; margin-bottom: 0.75rem;">
                            <?php echo htmlspecialchars($campaign['donation_type'] ?? 'general'); ?>
                        </span>
                        
                        <!-- Short Description -->
                        <p class="card-text" style="color: #666; margin-bottom: 1rem; font-size: 0.9rem;">
                            <?php echo htmlspecialchars($campaign['short_description'] ?? $campaign['description'] ?? ''); ?>
                        </p>
                        
                        <!-- Progress Section -->
                        <?php if (($campaign['goal_amount'] ?? 0) > 0 && ($campaign['show_progress_bar'] ?? 1)): ?>
                        <div style="margin-bottom: 1rem;">
                            <div style="display: flex; justify-content: space-between; margin-bottom: 0.5rem; font-size: 0.875rem;">
                                <span style="font-weight: bold; color: #28a745;">
                                    $<?php echo number_format($campaign['current_amount'] ?? 0, 0); ?>
                                </span>
                                <span style="color: #666;">
                                    of $<?php echo number_format($campaign['goal_amount'] ?? 0, 0); ?>
                                </span>
                            </div>
                            <div style="background: #e0e5eb; height: 8px; border-radius: 4px; overflow: hidden;">
                                <div style="width: <?php echo $progress; ?>%; background: <?php echo $progress >= 100 ? '#28a745' : 'var(--primary-light)'; ?>; height: 100%;"></div>
                            </div>
                            <?php if ($campaign['show_donor_count'] ?? 1): ?>
                            <small style="color: #666; display: block; margin-top: 0.25rem;">
                                <i class="fas fa-users"></i> <?php echo number_format($campaign['donor_count'] ?? 0); ?> donors
                            </small>
                            <?php endif; ?>
                        </div>
                        <?php endif; ?>
                        
                        <!-- Donate Button -->
                        <a href="campaign.php?slug=<?php echo htmlspecialchars($campaign['slug'] ?? $campaign['id'] ?? ''); ?>" 
                           class="btn btn-primary" 
                           style="width: 100%; text-align: center;">
                            <i class="fas fa-heart"></i> Donate Now
                        </a>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>
        
        <?php else: ?>
        <!-- No Campaigns Message -->
        <div style="text-align: center; padding: 3rem; background: #fff; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.1);">
            <i class="fas fa-hand-holding-heart" style="font-size: 4rem; color: #ccc; margin-bottom: 1rem;"></i>
            <h3 style="color: #666;">No Active Campaigns</h3>
            <p style="color: #999;">Check back soon for new giving opportunities, or use the general donation option below.</p>
        </div>
        <?php endif; ?>
    </div>
</section>

<!-- Other Ways to Give -->
<section class="section">
    <div class="container">
        <h2 class="section-title">Other Ways to Give</h2>
        
        <div class="two-column">
            <div class="column" style="background-color: #fff; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.1);">
                <h3><i class="fas fa-money-check" style="color: var(--primary-light);"></i> Mail a Check</h3>
                <p>You can mail your donation to:</p>
                <p style="background-color: #f8f9fa; padding: 1rem; border-radius: 4px; margin: 1rem 0;">
                    <strong>Grace Community Church</strong><br>
                    1234 Divi Street<br>
                    Your City, ST 12345
                </p>
                <p>Make checks payable to "Grace Community Church" and include your envelope number or name for proper recording.</p>
            </div>
            
            <div class="column" style="background-color: #fff; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.1);">
                <h3><i class="fas fa-hand-holding-usd" style="color: var(--primary-light);"></i> Give in Person</h3>
                <p>During any of our services, you can place your donation in the offering baskets or boxes located:</p>
                <ul style="line-height: 2; margin: 1rem 0;">
                    <li>At the sanctuary entrances</li>
                    <li>In the church lobby</li>
                    <li>Given during the offering time</li>
                </ul>
                <p>Cash and checks are accepted. Giving envelopes are available at all locations.</p>
            </div>
        </div>
    </div>
</section>

<!-- Tax Deductibility Notice -->
<section class="section" style="background-color: #fff;">
    <div class="container">
        <div style="max-width: 800px; margin: 0 auto; text-align: center; padding: 2rem; background-color: #f8f9fa; border-radius: 8px;">
            <h3 style="color: var(--primary-dark); margin-bottom: 1rem;">
                <i class="fas fa-file-invoice-dollar" style="color: var(--primary-light);"></i> 
                Tax Deductibility
            </h3>
            <p>Grace Community Church is a 501(c)(3) non-profit organization. Your donations are tax-deductible to the extent allowed by law. You will receive a receipt for your records and tax purposes.</p>
            <p style="margin-top: 1rem; font-size: 0.9rem; color: #666;">Tax ID: XX-XXXXXXX</p>
        </div>
    </div>
</section>

<!-- Scripture Reference -->
<section class="section" style="background: linear-gradient(135deg, var(--primary-dark), var(--accent-blue)); color: white; text-align: center;">
    <div class="container">
        <i class="fas fa-bible" style="font-size: 3rem; margin-bottom: 1rem; opacity: 0.8;"></i>
        <h2 style="color: white; font-style: italic; font-weight: 300;">"Give, and it will be given to you..."</h2>
        <p style="font-size: 1.2rem; margin-top: 1rem;">Luke 6:38</p>
    </div>
</section>

<!-- Card Hover Effect CSS -->
<style>
.card:hover {
    transform: translateY(-5px);
    box-shadow: 0 8px 25px rgba(0,0,0,0.15);
}
</style>

<?php
// Include footer
include 'includes/footer.php';
?>