<?php
/**
 * Donations View (Updated)
 * 
 * View all donation records and statistics
 * Now includes campaign filtering and assignment
 */

// Set page title
$page_title = 'Donations';

// Include admin header
include 'includes/admin-header.php';

/**
 * ===============================
 * Handle Actions
 * ===============================
 */
$action_message = '';
$action_error = '';

// Assign donation to campaign
if (isset($_POST['assign_campaign'])) {
    $donation_id = (int) $_POST['donation_id'];
    $new_campaign_id = !empty($_POST['new_campaign_id']) ? (int) $_POST['new_campaign_id'] : null;
    $old_campaign_id = !empty($_POST['old_campaign_id']) ? (int) $_POST['old_campaign_id'] : null;
    
    try {
        // Get donation amount
        $stmt = $pdo->prepare("SELECT amount FROM donations WHERE id = ?");
        $stmt->execute([$donation_id]);
        $donation = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($donation) {
            $amount = $donation['amount'];
            
            // Update donation's campaign_id
            $stmt = $pdo->prepare("UPDATE donations SET campaign_id = ? WHERE id = ?");
            $stmt->execute([$new_campaign_id, $donation_id]);
            
            // Subtract from old campaign if exists
            if ($old_campaign_id) {
                $stmt = $pdo->prepare("UPDATE donation_campaigns SET current_amount = current_amount - ? WHERE id = ?");
                $stmt->execute([$amount, $old_campaign_id]);
            }
            
            // Add to new campaign if exists
            if ($new_campaign_id) {
                $stmt = $pdo->prepare("UPDATE donation_campaigns SET current_amount = current_amount + ? WHERE id = ?");
                $stmt->execute([$amount, $new_campaign_id]);
            }
            
            $action_message = 'Donation campaign assignment updated successfully.';
        }
    } catch (PDOException $e) {
        $action_error = 'Failed to update donation assignment.';
    }
}

// Delete donation
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $donation_id = (int) $_GET['delete'];
    
    try {
        // Get donation details first
        $stmt = $pdo->prepare("SELECT amount, campaign_id FROM donations WHERE id = ?");
        $stmt->execute([$donation_id]);
        $donation = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($donation) {
            // If assigned to campaign, subtract from campaign total
            if ($donation['campaign_id']) {
                $stmt = $pdo->prepare("UPDATE donation_campaigns SET current_amount = current_amount - ? WHERE id = ?");
                $stmt->execute([$donation['amount'], $donation['campaign_id']]);
            }
            
            // Delete the donation
            $stmt = $pdo->prepare("DELETE FROM donations WHERE id = ?");
            $stmt->execute([$donation_id]);
            
            $action_message = 'Donation deleted successfully.';
        }
    } catch (PDOException $e) {
        $action_error = 'Failed to delete donation.';
    }
}

/**
 * ===============================
 * Filter Logic
 * ===============================
 */
$filter_campaign = isset($_GET['campaign']) ? $_GET['campaign'] : '';
$filter_type = isset($_GET['type']) ? sanitize_input($_GET['type']) : '';
$filter_date_from = isset($_GET['date_from']) ? sanitize_input($_GET['date_from']) : '';
$filter_date_to = isset($_GET['date_to']) ? sanitize_input($_GET['date_to']) : '';
$filter_search = isset($_GET['search']) ? sanitize_input($_GET['search']) : '';

// Build query
$where_clauses = [];
$params = [];

if ($filter_campaign === 'unassigned') {
    $where_clauses[] = "d.campaign_id IS NULL";
} elseif (!empty($filter_campaign) && is_numeric($filter_campaign)) {
    $where_clauses[] = "d.campaign_id = ?";
    $params[] = (int) $filter_campaign;
}

if (!empty($filter_type)) {
    $where_clauses[] = "d.donation_type = ?";
    $params[] = $filter_type;
}

if (!empty($filter_date_from)) {
    $where_clauses[] = "DATE(d.donation_date) >= ?";
    $params[] = $filter_date_from;
}

if (!empty($filter_date_to)) {
    $where_clauses[] = "DATE(d.donation_date) <= ?";
    $params[] = $filter_date_to;
}

if (!empty($filter_search)) {
    $where_clauses[] = "(d.donor_name LIKE ? OR d.donor_email LIKE ?)";
    $search_param = '%' . $filter_search . '%';
    $params[] = $search_param;
    $params[] = $search_param;
}

$where_sql = !empty($where_clauses) ? 'WHERE ' . implode(' AND ', $where_clauses) : '';

/**
 * ===============================
 * Fetch donation statistics
 * ===============================
 */
$stmt = $pdo->query("
    SELECT 
        COUNT(*) AS total_donations, 
        COALESCE(SUM(amount), 0) AS total_amount 
    FROM donations
");
$stats = $stmt->fetch(PDO::FETCH_ASSOC);

$total_donations = (int) ($stats['total_donations'] ?? 0);
$total_amount = (float) ($stats['total_amount'] ?? 0);
$average_donation = $total_donations > 0 ? $total_amount / $total_donations : 0;

// Unassigned donations count
$stmt = $pdo->query("SELECT COUNT(*) FROM donations WHERE campaign_id IS NULL");
$unassigned_count = $stmt->fetchColumn();

/**
 * ===============================
 * Donations by type
 * ===============================
 */
$stmt = $pdo->query("
    SELECT 
        donation_type, 
        COUNT(*) AS count, 
        COALESCE(SUM(amount), 0) AS total 
    FROM donations 
    GROUP BY donation_type 
    ORDER BY total DESC
");
$donations_by_type = $stmt->fetchAll(PDO::FETCH_ASSOC);

/**
 * ===============================
 * Fetch all donations with campaign info
 * ===============================
 */
$sql = "
    SELECT d.*, dc.title as campaign_title, dc.slug as campaign_slug
    FROM donations d
    LEFT JOIN donation_campaigns dc ON d.campaign_id = dc.id
    $where_sql
    ORDER BY d.donation_date DESC
";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$donations = $stmt->fetchAll(PDO::FETCH_ASSOC);

/**
 * ===============================
 * Fetch campaigns for filter dropdown
 * ===============================
 */
$stmt = $pdo->query("SELECT id, title FROM donation_campaigns ORDER BY title");
$campaigns = $stmt->fetchAll(PDO::FETCH_ASSOC);

/**
 * ===============================
 * Get unique donation types for filter
 * ===============================
 */
$stmt = $pdo->query("SELECT DISTINCT donation_type FROM donations ORDER BY donation_type");
$donation_types = $stmt->fetchAll(PDO::FETCH_COLUMN);
?>

<!-- Page Header -->
<div class="page-header">
    <h2><i class="fas fa-hand-holding-usd"></i> Donations</h2>
    <p>View and manage donation records</p>
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
            <h3>Total Donations</h3>
            <div class="stat-icon teal">
                <i class="fas fa-donate"></i>
            </div>
        </div>
        <div class="stat-number"><?php echo number_format($total_donations); ?></div>
        <div class="stat-label">Total donation records</div>
    </div>
    
    <div class="stat-card">
        <div class="stat-card-header">
            <h3>Total Amount</h3>
            <div class="stat-icon green">
                <i class="fas fa-dollar-sign"></i>
            </div>
        </div>
        <div class="stat-number">$<?php echo number_format($total_amount, 2); ?></div>
        <div class="stat-label">Total donations received</div>
    </div>
    
    <div class="stat-card">
        <div class="stat-card-header">
            <h3>Average Donation</h3>
            <div class="stat-icon blue">
                <i class="fas fa-chart-line"></i>
            </div>
        </div>
        <div class="stat-number">$<?php echo number_format($average_donation, 2); ?></div>
        <div class="stat-label">Per donation</div>
    </div>
    
    <div class="stat-card">
        <div class="stat-card-header">
            <h3>Unassigned</h3>
            <div class="stat-icon <?php echo $unassigned_count > 0 ? 'orange' : 'green'; ?>">
                <i class="fas fa-<?php echo $unassigned_count > 0 ? 'exclamation-triangle' : 'check-circle'; ?>"></i>
            </div>
        </div>
        <div class="stat-number"><?php echo number_format($unassigned_count); ?></div>
        <div class="stat-label">
            <?php if ($unassigned_count > 0): ?>
            <a href="?campaign=unassigned" style="color: var(--primary-light);">View unassigned →</a>
            <?php else: ?>
            All donations assigned
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Donations by Type -->
<?php if (!empty($donations_by_type)): ?>
<div class="card">
    <div class="card-header">
        <h3><i class="fas fa-chart-pie"></i> Donations by Type</h3>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>Donation Type</th>
                        <th>Count</th>
                        <th>Total Amount</th>
                        <th>Percentage</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($donations_by_type as $type): ?>
                    <?php
                        $percentage = $total_amount > 0 ? ($type['total'] / $total_amount) * 100 : 0;
                    ?>
                    <tr>
                        <td><strong><?php echo htmlspecialchars($type['donation_type']); ?></strong></td>
                        <td><?php echo number_format($type['count']); ?></td>
                        <td><strong>$<?php echo number_format($type['total'], 2); ?></strong></td>
                        <td>
                            <div style="display: flex; align-items: center; gap: 0.5rem;">
                                <div style="flex: 1; background: #e0e5eb; height: 8px; border-radius: 4px; max-width: 200px;">
                                    <div style="width: <?php echo $percentage; ?>%; background: #28a745; height: 100%; border-radius: 4px;"></div>
                                </div>
                                <span><?php echo number_format($percentage, 1); ?>%</span>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Filters -->
<div class="card">
    <div class="card-header">
        <h3><i class="fas fa-filter"></i> Filter Donations</h3>
    </div>
    <div class="card-body">
        <form method="GET" style="display: flex; flex-wrap: wrap; gap: 1rem; align-items: flex-end;">
            <!-- Search -->
            <div class="form-group" style="margin-bottom: 0; flex: 1; min-width: 200px;">
                <label for="search">Search</label>
                <input type="text" 
                       id="search" 
                       name="search" 
                       class="form-control" 
                       placeholder="Donor name or email..."
                       value="<?php echo htmlspecialchars($filter_search); ?>">
            </div>
            
            <!-- Campaign Filter -->
            <div class="form-group" style="margin-bottom: 0; min-width: 180px;">
                <label for="campaign">Campaign</label>
                <select id="campaign" name="campaign" class="form-control">
                    <option value="">All Campaigns</option>
                    <option value="unassigned" <?php echo $filter_campaign === 'unassigned' ? 'selected' : ''; ?>>
                        ⚠️ Unassigned Only
                    </option>
                    <?php foreach ($campaigns as $camp): ?>
                    <option value="<?php echo $camp['id']; ?>" <?php echo $filter_campaign == $camp['id'] ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($camp['title']); ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <!-- Type Filter -->
            <div class="form-group" style="margin-bottom: 0; min-width: 150px;">
                <label for="type">Type</label>
                <select id="type" name="type" class="form-control">
                    <option value="">All Types</option>
                    <?php foreach ($donation_types as $type): ?>
                    <option value="<?php echo htmlspecialchars($type); ?>" <?php echo $filter_type === $type ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($type); ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <!-- Date From -->
            <div class="form-group" style="margin-bottom: 0; min-width: 150px;">
                <label for="date_from">From Date</label>
                <input type="date" id="date_from" name="date_from" class="form-control"
                       value="<?php echo htmlspecialchars($filter_date_from); ?>">
            </div>
            
            <!-- Date To -->
            <div class="form-group" style="margin-bottom: 0; min-width: 150px;">
                <label for="date_to">To Date</label>
                <input type="date" id="date_to" name="date_to" class="form-control"
                       value="<?php echo htmlspecialchars($filter_date_to); ?>">
            </div>
            
            <!-- Buttons -->
            <div style="display: flex; gap: 0.5rem;">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-search"></i> Search
                </button>
                <?php if (!empty($filter_campaign) || !empty($filter_type) || !empty($filter_date_from) || !empty($filter_date_to) || !empty($filter_search)): ?>
                <a href="donations.php" class="btn btn-secondary">
                    <i class="fas fa-times"></i> Clear
                </a>
                <?php endif; ?>
            </div>
        </form>
    </div>
</div>

<!-- All Donations Table -->
<div class="card">
    <div class="card-header">
        <h3><i class="fas fa-list"></i> Donation Records (<?php echo count($donations); ?>)</h3>
    </div>
    <div class="card-body">
        <?php if (!empty($donations)): ?>
        <div class="table-responsive">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Donor</th>
                        <th>Amount</th>
                        <th>Type</th>
                        <th>Campaign</th>
                        <th>Payment Method</th>
                        <th>Date</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($donations as $donation): ?>
                    <tr>
                        <td><?php echo $donation['id']; ?></td>
                        <td>
                            <strong><?php echo htmlspecialchars($donation['donor_name']); ?></strong>
                            <br><small style="color: #666;"><?php echo htmlspecialchars($donation['donor_email']); ?></small>
                        </td>
                        <td><strong style="color: #28a745;">$<?php echo number_format($donation['amount'], 2); ?></strong></td>
                        <td><span class="badge badge-info"><?php echo htmlspecialchars($donation['donation_type']); ?></span></td>
                        <td>
                            <?php if ($donation['campaign_id']): ?>
                            <a href="edit-campaign.php?id=<?php echo $donation['campaign_id']; ?>" 
                               style="color: var(--primary-light);">
                                <?php echo htmlspecialchars($donation['campaign_title']); ?>
                            </a>
                            <?php else: ?>
                            <span class="badge badge-warning">
                                <i class="fas fa-exclamation-triangle"></i> Unassigned
                            </span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <small><?php echo htmlspecialchars($donation['payment_method']); ?></small>
                        </td>
                        <td>
                            <?php echo date('M d, Y', strtotime($donation['donation_date'])); ?>
                            <br><small style="color: #666;"><?php echo date('g:i A', strtotime($donation['donation_date'])); ?></small>
                        </td>
                        <td>
                            <div style="display: flex; gap: 0.25rem; flex-wrap: wrap;">
                                <!-- View Message -->
                                <?php if (!empty($donation['message'])): ?>
                                <button type="button" 
                                        class="btn btn-sm btn-secondary"
                                        title="View Message"
                                        onclick="alert('<?php echo htmlspecialchars(addslashes($donation['message'])); ?>')">
                                    <i class="fas fa-envelope"></i>
                                </button>
                                <?php endif; ?>
                                
                                <!-- Assign to Campaign -->
                                <button type="button" 
                                        class="btn btn-sm btn-info"
                                        title="Assign to Campaign"
                                        onclick="openAssignModal(<?php echo $donation['id']; ?>, <?php echo $donation['campaign_id'] ?? 'null'; ?>)">
                                    <i class="fas fa-link"></i>
                                </button>
                                
                                <!-- Delete -->
                                <a href="?delete=<?php echo $donation['id']; ?><?php echo !empty($_SERVER['QUERY_STRING']) ? '&' . $_SERVER['QUERY_STRING'] : ''; ?>" 
                                   class="btn btn-sm btn-danger"
                                   title="Delete Donation"
                                   onclick="return confirm('Are you sure you want to delete this donation? This will also update the campaign total.');">
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
            <i class="fas fa-inbox" style="font-size: 3rem; margin-bottom: 1rem; opacity: 0.5;"></i>
            <p>No donations found matching your criteria.</p>
            <?php if (!empty($filter_campaign) || !empty($filter_type) || !empty($filter_date_from) || !empty($filter_date_to) || !empty($filter_search)): ?>
            <a href="donations.php" class="btn btn-secondary">
                <i class="fas fa-times"></i> Clear Filters
            </a>
            <?php endif; ?>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- Assign Campaign Modal -->
<div id="assignModal" style="display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.5); z-index: 1000; align-items: center; justify-content: center;">
    <div style="background: white; padding: 2rem; border-radius: 8px; max-width: 400px; width: 90%; margin: 2rem;">
        <h3 style="margin-top: 0;"><i class="fas fa-link"></i> Assign to Campaign</h3>
        <form method="POST">
            <input type="hidden" name="assign_campaign" value="1">
            <input type="hidden" name="donation_id" id="modal_donation_id">
            <input type="hidden" name="old_campaign_id" id="modal_old_campaign_id">
            
            <div class="form-group">
                <label for="new_campaign_id">Select Campaign</label>
                <select name="new_campaign_id" id="new_campaign_id" class="form-control">
                    <option value="">-- No Campaign (Unassigned) --</option>
                    <?php foreach ($campaigns as $camp): ?>
                    <option value="<?php echo $camp['id']; ?>">
                        <?php echo htmlspecialchars($camp['title']); ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div style="display: flex; gap: 0.5rem; justify-content: flex-end; margin-top: 1.5rem;">
                <button type="button" class="btn btn-secondary" onclick="closeAssignModal()">Cancel</button>
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> Save
                </button>
            </div>
        </form>
    </div>
</div>

<!-- JavaScript -->
<script>
function openAssignModal(donationId, currentCampaignId) {
    document.getElementById('modal_donation_id').value = donationId;
    document.getElementById('modal_old_campaign_id').value = currentCampaignId || '';
    document.getElementById('new_campaign_id').value = currentCampaignId || '';
    document.getElementById('assignModal').style.display = 'flex';
}

function closeAssignModal() {
    document.getElementById('assignModal').style.display = 'none';
}

// Close modal on outside click
document.getElementById('assignModal').addEventListener('click', function(e) {
    if (e.target === this) {
        closeAssignModal();
    }
});

// Close modal on Escape key
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        closeAssignModal();
    }
});
</script>

<?php include 'includes/admin-footer.php'; ?>