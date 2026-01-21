<?php
/**
 * Manage Events
 * 
 * View, edit, and delete events
 */

// Set page title
$page_title = 'Manage Events';

// Include admin header
include 'includes/admin-header.php';

// Initialize messages
$success_message = '';
$error_message = '';

// Handle delete action
if (isset($_GET['delete'])) {
    $event_id = (int)$_GET['delete'];
    
    try {
        $stmt = $pdo->prepare("DELETE FROM events WHERE id = ?");
        $stmt->execute([$event_id]);
        $success_message = 'Event deleted successfully!';
    } catch (PDOException $e) {
        $error_message = 'Error deleting event. Please try again.';
    }
}

// Fetch all events
$stmt = $pdo->query("SELECT * FROM events ORDER BY event_date DESC");
$events = $stmt->fetchAll();
?>

<!-- Page Header -->
<div class="page-header">
    <h2><i class="fas fa-calendar-alt"></i> Manage Events</h2>
    <p>View, edit, and delete church events</p>
</div>

<!-- Success/Error Messages -->
<?php if (!empty($success_message)): ?>
<div class="alert alert-success">
    <i class="fas fa-check-circle"></i> <?php echo $success_message; ?>
</div>
<?php endif; ?>

<?php if (!empty($error_message)): ?>
<div class="alert alert-danger">
    <i class="fas fa-exclamation-circle"></i> <?php echo $error_message; ?>
</div>
<?php endif; ?>

<!-- Add New Button -->
<div style="margin-bottom: 1.5rem;">
    <a href="add-event.php" class="btn btn-success">
        <i class="fas fa-plus"></i> Add New Event
    </a>
</div>

<!-- Events Table -->
<div class="card">
    <div class="card-header">
        <h3><i class="fas fa-list"></i> All Events (<?php echo count($events); ?>)</h3>
    </div>
    <div class="card-body">
        <?php if (!empty($events)): ?>
        <div class="table-responsive">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Event Title</th>
                        <th>Date</th>
                        <th>Time</th>
                        <th>Location</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($events as $event): ?>
                    <?php
                    $event_date = strtotime($event['event_date']);
                    $is_past = $event_date < strtotime('today');
                    $is_today = $event_date == strtotime('today');
                    ?>
                    <tr style="<?php echo $is_past ? 'opacity: 0.6;' : ''; ?>">
                        <td><?php echo $event['id']; ?></td>
                        <td>
                            <strong><?php echo htmlspecialchars($event['title']); ?></strong>
                            <br>
                            <small style="color: #666;">
                                <?php echo htmlspecialchars(substr($event['description'], 0, 50)); ?>...
                            </small>
                        </td>
                        <td><?php echo date('M d, Y', $event_date); ?></td>
                        <td><?php echo date('g:i A', strtotime($event['event_time'])); ?></td>
                        <td><?php echo htmlspecialchars($event['location']); ?></td>
                        <td>
                            <?php if ($is_today): ?>
                                <span class="badge badge-warning">Today</span>
                            <?php elseif ($is_past): ?>
                                <span class="badge badge-secondary">Past</span>
                            <?php else: ?>
                                <span class="badge badge-success">Upcoming</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <a href="edit-event.php?id=<?php echo $event['id']; ?>" 
                               class="btn btn-sm btn-info" 
                               title="Edit">
                                <i class="fas fa-edit"></i>
                            </a>
                            <a href="?delete=<?php echo $event['id']; ?>" 
                               class="btn btn-sm btn-danger btn-delete" 
                               data-item="<?php echo htmlspecialchars($event['title']); ?>"
                               title="Delete">
                                <i class="fas fa-trash"></i>
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php else: ?>
        <div style="text-align: center; padding: 3rem;">
            <i class="fas fa-calendar-alt" style="font-size: 4rem; color: #ccc; margin-bottom: 1rem;"></i>
            <p style="color: #666; font-size: 1.1rem;">No events found.</p>
            <a href="add-event.php" class="btn btn-success" style="margin-top: 1rem;">
                <i class="fas fa-plus"></i> Add Your First Event
            </a>
        </div>
        <?php endif; ?>
    </div>
</div>

<?php
// Include admin footer
include 'includes/admin-footer.php';
?>