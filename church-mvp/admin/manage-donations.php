<?php
/**
 * Manage Donation Campaigns
 * 
 * Admin page to view, manage, and control all donation campaigns
 * Includes statistics, filtering, and quick actions
 */

// Set page title
$page_title = 'Manage Campaigns';

// Include admin header (handles session, db connection, auth)
include 'includes/admin-header.php';

/**
 * ===============================
 * Handle Quick Actions
 * ===============================
 */
$action_message = '';
$action_error = '';

// Toggle campaign status (activate/deactivate)
if (isset($_GET['toggle']) && is_numeric($_GET['toggle'])) {
    $campaign_id = (int) $_GET['toggle'];
    try {
        $stmt = $pdo->prepare("UPDATE donation_campaigns SET is_active = NOT is_active WHERE id = ?");
        $stmt->execute([$campaign_id]);
        $action_message = 'Campaign status updated successfully.';
    } catch (PDOException $e) {
        $action_error = 'Failed to update campaign status.';
    }
}

// Delete campaign
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $campaign_id = (int) $_GET['delete'];
    try {
        // Check if campaign has donations
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM donations WHERE campaign_id = ?");
        $stmt->execute([$campaign_id]);
        $donation_count = $stmt->fetchColumn();
        
        if ($donation_count > 0) {
            $action_error = 'Cannot delete campaign with existing donations. Deactivate it instead.';
        } else {
            $stmt = $pdo->prepare("DELETE FROM donation_campaigns WHERE id = ?");
            $stmt->execute([$campaign_id]);
            $action_message = 'Campaign deleted successfully.';
        }
    } catch (PDOException $e) {
        $action_error = 'Failed to delete campaign.';
    }
}

// Toggle featured status
if (isset($_GET['feature']) && is_numeric($_GET['feature'])) {
    $campaign_id = (int) $_GET['feature'];
    try {
        $stmt = $pdo->prepare("UPDATE donation_campaigns SET is_featured = NOT is_featured WHERE id = ?");
        $stmt->execute([$campaign_id]);
        $action_message = 'Campaign featured status updated.';
    } catch (PDOException $e) {
        $action_error = 'Failed to update featured status.';
    }
}

/**
 * ===============================
 * Filter Logic
 * ===============================
 */
$filter_status = isset($_GET['status']) ? $_GET['status'] : 'all';
$filter_type = isset($_GET['type']) ? sanitize_input($_GET['type']) : '';

// Build query based on filters
$where_clauses = [];
$params = [];

if ($filter_status === 'active') {
    $where_clauses[] = "is_active = 1";
} elseif ($filter_status === 'inactive') {
    $where_clauses[] = "is_active = 0";
} elseif ($filter_status === 'ended') {
    $where_clauses[] = "end_date < CURDATE() AND end_date IS NOT NULL";
} elseif ($filter_status === 'featured') {
    $where_clauses[] = "is_featured = 1";
}

if (!empty($filter_type)) {
    $where_clauses[] = "donation_type = ?";
    $params[] = $filter_type;
}

$where_sql = !empty($where_clauses) ? 'WHERE ' . implode(' AND ', $where_clauses) : '';

/**
 * ===============================
 * Fetch Statistics
 * ===============================
 */
// Total campaigns
$stmt = $pdo->query("SELECT COUNT(*) FROM donation_campaigns");
$total_campaigns = $stmt->fetchColumn();

// Active campaigns
$stmt = $pdo->query("SELECT COUNT(*) FROM donation_campaigns WHERE is_active = 1");
$active_campaigns = $stmt->fetchColumn();

// Total raised across all campaigns
$stmt = $pdo->query("SELECT COALESCE(SUM(current_amount), 0) FROM donation_campaigns");
$total_raised = $stmt->fetchColumn();

// Total goal amount
$stmt = $pdo->query("SELECT COALESCE(SUM(goal_amount), 0) FROM donation_campaigns WHERE goal_amount > 0");
$total_goal = $stmt->fetchColumn();

/**
 * ===============================
 * Fetch Campaigns
 * ===============================
 */
$sql = "SELECT dc.*, 
        (SELECT COUNT(*) FROM donations WHERE campaign_id = dc.id) as donation_count
        FROM donation_campaigns dc 
        $where_sql 
        ORDER BY is_featured DESC, created_at DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$campaigns = $stmt->fetchAll(PDO::FETCH_ASSOC);

/**
 * ===============================
 * Get unique donation types for filter
 * ===============================
 */
$stmt = $pdo->query("SELECT DISTINCT donation_type FROM donation_campaigns ORDER BY donation_type");
$donation_types = $stmt->fetchAll(PDO::FETCH_COLUMN);
?>

<!-- Page Header -->
<div class="page-header">
    <h2><i class="fas fa-hand-holding-heart"></i> Manage Donation Campaigns</h2>
    <p>Create, edit, and manage fundraising campaigns</p>
</div>

<!-- Action Messages -->
<?php if (!empty($action_message)): ?>
<div class="alert alert-success">
    <i class="fas fa-check-circle"></i> <?php echo $action_message; ?>
</div>
<?php endif; ?>

<?php if (!empty($action_error)): ?>
<div class="alert alert-danger">
    <i class="fas fa-exclamation-circle"></i> <?php echo $action_error; ?>
</div>
<?php endif; ?>

<!-- Statistics Cards -->
<div class="dashboard-cards">
    <div class="stat-card">
        <div class="stat-card-header">
            <h3>Total Campaigns</h3>
            <div class="stat-icon teal">
                <i class="fas fa-bullhorn"></i>
            </div>
        </div>
        <div class="stat-number"><?php echo number_format($total_campaigns); ?></div>
        <div class="stat-label">All campaigns created</div>
    </div>
    
    <div class="stat-card">
        <div class="stat-card-header">
            <h3>Active Campaigns</h3>
            <div class="stat-icon green">
                <i class="fas fa-check-circle"></i>
            </div>
        </div>
        <div class="stat-number"><?php echo number_format($active_campaigns); ?></div>
        <div class="stat-label">Currently accepting donations</div>
    </div>
    
    <div class="stat-card">
        <div class="stat-card-header">
            <h3>Total Raised</h3>
            <div class="stat-icon blue">
                <i class="fas fa-dollar-sign"></i>
            </div>
        </div>
        <div class="stat-number">$<?php echo number_format($total_raised, 2); ?></div>
        <div class="stat-label">Across all campaigns</div>
    </div>
    
    <div class="stat-card">
        <div class="stat-card-header">
            <h3>Goal Progress</h3>
            <div class="stat-icon orange">
                <i class="fas fa-chart-line"></i>
            </div>
        </div>
        <div class="stat-number">
            <?php echo $total_goal > 0 ? number_format(($total_raised / $total_goal) * 100, 1) : 0; ?>%
        </div>
        <div class="stat-label">Of combined goals</div>
    </div>
</div>

<!-- Action Bar -->
<div class="card">
    <div class="card-body" style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem;">
        <!-- Add New Button -->
        <a href="add-campaign.php" class="btn btn-primary">
            <i class="fas fa-plus"></i> Add New Campaign
        </a>
        
        <!-- Filters -->
        <form method="GET" style="display: flex; gap: 0.5rem; align-items: center; flex-wrap: wrap;">
            <select name="status" class="form-control" style="width: auto;">
                <option value="all" <?php echo $filter_status === 'all' ? 'selected' : ''; ?>>All Status</option>
                <option value="active" <?php echo $filter_status === 'active' ? 'selected' : ''; ?>>Active</option>
                <option value="inactive" <?php echo $filter_status === 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                <option value="ended" <?php echo $filter_status === 'ended' ? 'selected' : ''; ?>>Ended</option>
                <option value="featured" <?php echo $filter_status === 'featured' ? 'selected' : ''; ?>>Featured</option>
            </select>
            
            <select name="type" class="form-control" style="width: auto;">
                <option value="">All Types</option>
                <?php foreach ($donation_types as $type): ?>
                <option value="<?php echo htmlspecialchars($type); ?>" <?php echo $filter_type === $type ? 'selected' : ''; ?>>
                    <?php echo htmlspecialchars($type); ?>
                </option>
                <?php endforeach; ?>
            </select>
            
            <button type="submit" class="btn btn-secondary">
                <i class="fas fa-filter"></i> Filter
            </button>
            
            <?php if ($filter_status !== 'all' || !empty($filter_type)): ?>
            <a href="manage-donations.php" class="btn btn-secondary">
                <i class="fas fa-times"></i> Clear
            </a>
            <?php endif; ?>
        </form>
    </div>
</div>

<!-- Campaigns Table -->
<div class="card">
    <div class="card-header">
        <h3><i class="fas fa-list"></i> Donation Campaigns (<?php echo count($campaigns); ?>)</h3>
    </div>
    <div class="card-body">
        <?php if (!empty($campaigns)): ?>
        <div class="table-responsive">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>Campaign</th>
                        <th>Type</th>
                        <th>Progress</th>
                        <th>Donations</th>
                        <th>Status</th>
                        <th>Dates</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($campaigns as $campaign): ?>
                    <?php
                        // Calculate progress percentage
                        $progress = 0;
                        if ($campaign['goal_amount'] > 0) {
                            $progress = min(100, ($campaign['current_amount'] / $campaign['goal_amount']) * 100);
                        }
                        
                        // Determine campaign status
                        $status_class = 'badge-secondary';
                        $status_text = 'Inactive';
                        
                        if ($campaign['is_active']) {
                            if ($campaign['end_date'] && strtotime($campaign['end_date']) < time()) {
                                $status_class = 'badge-warning';
                                $status_text = 'Ended';
                            } else {
                                $status_class = 'badge-success';
                                $status_text = 'Active';
                            }
                        }
                    ?>
                    <tr>
                        <td>
                            <div style="display: flex; align-items: center; gap: 0.75rem;">
                                <?php if ($campaign['featured_image']): ?>
                                <img src="../uploads/campaigns/<?php echo htmlspecialchars($campaign['featured_image']); ?>" 
                                     alt="" 
                                     style="width: 50px; height: 50px; object-fit: cover; border-radius: 4px;">
                                <?php else: ?>
                                <div style="width: 50px; height: 50px; background: #e0e5eb; border-radius: 4px; display: flex; align-items: center; justify-content: center;">
                                    <i class="fas fa-image" style="color: #999;"></i>
                                </div>
                                <?php endif; ?>
                                <div>
                                    <strong><?php echo htmlspecialchars($campaign['title']); ?></strong>
                                    <?php if ($campaign['is_featured']): ?>
                                    <span class="badge badge-warning" style="margin-left: 0.5rem;">
                                        <i class="fas fa-star"></i> Featured
                                    </span>
                                    <?php endif; ?>
                                    <br>
                                    <small style="color: #666;"><?php echo htmlspecialchars($campaign['slug']); ?></small>
                                </div>
                            </div>
                        </td>
                        <td>
                            <span class="badge badge-info"><?php echo htmlspecialchars($campaign['donation_type']); ?></span>
                        </td>
                        <td style="min-width: 180px;">
                            <?php if ($campaign['goal_amount'] > 0): ?>
                            <div style="margin-bottom: 0.25rem;">
                                <strong style="color: #28a745;">$<?php echo number_format($campaign['current_amount'], 2); ?></strong>
                                <span style="color: #666;">of $<?php echo number_format($campaign['goal_amount'], 2); ?></span>
                            </div>
                            <div style="background: #e0e5eb; height: 8px; border-radius: 4px; overflow: hidden;">
                                <div style="width: <?php echo $progress; ?>%; background: <?php echo $progress >= 100 ? '#28a745' : 'var(--primary-light)'; ?>; height: 100%; transition: width 0.3s;"></div>
                            </div>
                            <small style="color: #666;"><?php echo number_format($progress, 1); ?>% complete</small>
                            <?php else: ?>
                            <strong style="color: #28a745;">$<?php echo number_format($campaign['current_amount'], 2); ?></strong>
                            <br><small style="color: #666;">No goal set</small>
                            <?php endif; ?>
                        </td>
                        <td>
                            <span style="font-size: 1.1rem; font-weight: bold;"><?php echo number_format($campaign['donation_count']); ?></span>
                            <br><small style="color: #666;">donations</small>
                        </td>
                        <td>
                            <span class="badge <?php echo $status_class; ?>"><?php echo $status_text; ?></span>
                        </td>
                        <td>
                            <?php if ($campaign['start_date']): ?>
                            <small><strong>Start:</strong> <?php echo date('M d, Y', strtotime($campaign['start_date'])); ?></small><br>
                            <?php endif; ?>
                            <?php if ($campaign['end_date']): ?>
                            <small><strong>End:</strong> <?php echo date('M d, Y', strtotime($campaign['end_date'])); ?></small>
                            <?php else: ?>
                            <small style="color: #666;">Ongoing</small>
                            <?php endif; ?>
                        </td>
                        <td>
                            <div style="display: flex; gap: 0.25rem; flex-wrap: wrap;">
                                <!-- Edit -->
                                <a href="edit-campaign.php?id=<?php echo $campaign['id']; ?>" 
                                   class="btn btn-sm btn-primary" 
                                   title="Edit Campaign">
                                    <i class="fas fa-edit"></i>
                                </a>
                                
                                <!-- Toggle Active -->
                                <a href="?toggle=<?php echo $campaign['id']; ?>" 
                                   class="btn btn-sm <?php echo $campaign['is_active'] ? 'btn-warning' : 'btn-success'; ?>"
                                   title="<?php echo $campaign['is_active'] ? 'Deactivate' : 'Activate'; ?>"
                                   onclick="return confirm('Are you sure you want to <?php echo $campaign['is_active'] ? 'deactivate' : 'activate'; ?> this campaign?');">
                                    <i class="fas <?php echo $campaign['is_active'] ? 'fa-pause' : 'fa-play'; ?>"></i>
                                </a>
                                
                                <!-- Toggle Featured -->
                                <a href="?feature=<?php echo $campaign['id']; ?>" 
                                   class="btn btn-sm <?php echo $campaign['is_featured'] ? 'btn-secondary' : 'btn-info'; ?>"
                                   title="<?php echo $campaign['is_featured'] ? 'Remove Featured' : 'Make Featured'; ?>">
                                    <i class="fas fa-star"></i>
                                </a>
                                
                                <!-- View Donations -->
                                <a href="donations.php?campaign=<?php echo $campaign['id']; ?>" 
                                   class="btn btn-sm btn-secondary"
                                   title="View Donations">
                                    <i class="fas fa-list"></i>
                                </a>
                                
                                <!-- Delete -->
                                <a href="?delete=<?php echo $campaign['id']; ?>" 
                                   class="btn btn-sm btn-danger"
                                   title="Delete Campaign"
                                   onclick="return confirm('Are you sure you want to delete this campaign? This cannot be undone.');">
                                    <i class="fas fa-trash"></i>
                                </a>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php else: ?>
        <div style="text-align: center; padding: 3rem; color: #666;">
            <i class="fas fa-folder-open" style="font-size: 3rem; margin-bottom: 1rem; opacity: 0.5;"></i>
            <p>No campaigns found matching your criteria.</p>
            <a href="add-campaign.php" class="btn btn-primary">
                <i class="fas fa-plus"></i> Create Your First Campaign
            </a>
        </div>
        <?php endif; ?>
    </div>
</div>

<?php include 'includes/admin-footer.php'; ?>