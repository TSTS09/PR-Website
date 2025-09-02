<?php
/**
 * Admin Dashboard
 */

define('BOGF_ACCESS', true);
require_once '../includes/config.php';

// Require admin login
requireAdminLogin();

$admin = getCurrentAdmin();
$adminId = getCurrentAdminId();

// Get dashboard statistics
try {
    $db = Database::getInstance()->getConnection();
    
    // Count speakers
    $speakersStmt = $db->query("SELECT COUNT(*) as total, COUNT(CASE WHEN is_active = 1 THEN 1 END) as active FROM speakers");
    $speakersStats = $speakersStmt->fetch();
    
    // Count applications
    $applicationsStmt = $db->query("
        SELECT 
            COUNT(*) as total,
            COUNT(CASE WHEN status = 'pending' THEN 1 END) as pending,
            COUNT(CASE WHEN status = 'approved' THEN 1 END) as approved,
            COUNT(CASE WHEN status = 'rejected' THEN 1 END) as rejected
        FROM summit_applications
    ");
    $applicationStats = $applicationsStmt->fetch();
    
    // Count newsletter subscribers
    $newsletterStmt = $db->query("SELECT COUNT(*) as total FROM newsletter_subscribers WHERE is_active = 1");
    $newsletterStats = $newsletterStmt->fetch();
    
    // Count contact messages
    $messagesStmt = $db->query("
        SELECT 
            COUNT(*) as total,
            COUNT(CASE WHEN status = 'new' THEN 1 END) as unread
        FROM contact_messages
    ");
    $messageStats = $messagesStmt->fetch();
    
    // Count partnership inquiries
    $partnershipsStmt = $db->query("
        SELECT 
            COUNT(*) as total,
            COUNT(CASE WHEN status = 'new' THEN 1 END) as new
        FROM partnership_inquiries
    ");
    $partnershipStats = $partnershipsStmt->fetch();
    
    // Recent applications
    $recentApplicationsStmt = $db->prepare("
        SELECT 
            id, first_name, last_name, email, organization, 
            application_type, status, applied_at
        FROM summit_applications 
        ORDER BY applied_at DESC 
        LIMIT 10
    ");
    $recentApplicationsStmt->execute();
    $recentApplications = $recentApplicationsStmt->fetchAll();
    
    // Recent activity
    $recentActivityStmt = $db->prepare("
        SELECT 
            aal.*,
            CONCAT(au.first_name, ' ', au.last_name) as admin_name
        FROM admin_activity_log aal
        JOIN admin_users au ON aal.admin_id = au.id
        ORDER BY aal.created_at DESC
        LIMIT 10
    ");
    $recentActivityStmt->execute();
    $recentActivity = $recentActivityStmt->fetchAll();
    
} catch (Exception $e) {
    error_log("Dashboard error: " . $e->getMessage());
    $speakersStats = ['total' => 0, 'active' => 0];
    $applicationStats = ['total' => 0, 'pending' => 0, 'approved' => 0, 'rejected' => 0];
    $newsletterStats = ['total' => 0];
    $messageStats = ['total' => 0, 'unread' => 0];
    $partnershipStats = ['total' => 0, 'new' => 0];
    $recentApplications = [];
    $recentActivity = [];
}

include 'includes/header.php';
?>

<div class="admin-content">
    <div class="dashboard-header">
        <h1>Dashboard</h1>
        <p class="text-muted">Welcome back, <?php echo htmlspecialchars($admin['first_name']); ?>! Here's what's happening with BoGF.</p>
    </div>
    
    <!-- Dashboard Statistics -->
    <div class="dashboard-stats">
        <div class="stat-card">
            <div class="stat-card-header">
                <div class="stat-card-icon primary">
                    <i data-lucide="users"></i>
                </div>
            </div>
            <h3 class="stat-value"><?php echo $speakersStats['active']; ?></h3>
            <p class="stat-label">Active Speakers</p>
            <div class="stat-change">
                <small><?php echo $speakersStats['total']; ?> total speakers</small>
            </div>
        </div>
        
        <div class="stat-card">
            <div class="stat-card-header">
                <div class="stat-card-icon warning">
                    <i data-lucide="file-text"></i>
                </div>
            </div>
            <h3 class="stat-value"><?php echo $applicationStats['pending']; ?></h3>
            <p class="stat-label">Pending Applications</p>
            <div class="stat-change">
                <small><?php echo $applicationStats['total']; ?> total applications</small>
            </div>
        </div>
        
        <div class="stat-card">
            <div class="stat-card-header">
                <div class="stat-card-icon success">
                    <i data-lucide="check-circle"></i>
                </div>
            </div>
            <h3 class="stat-value"><?php echo $applicationStats['approved']; ?></h3>
            <p class="stat-label">Approved Attendees</p>
            <div class="stat-change">
                <small><?php echo $applicationStats['rejected']; ?> rejected</small>
            </div>
        </div>
        
        <div class="stat-card">
            <div class="stat-card-header">
                <div class="stat-card-icon info">
                    <i data-lucide="mail"></i>
                </div>
            </div>
            <h3 class="stat-value"><?php echo $newsletterStats['total']; ?></h3>
            <p class="stat-label">Newsletter Subscribers</p>
            <div class="stat-change">
                <small><?php echo $messageStats['unread']; ?> unread messages</small>
            </div>
        </div>
    </div>
    
    <!-- Quick Actions -->
    <div class="dashboard-section">
        <div class="section-header">
            <h2>Quick Actions</h2>
        </div>
        <div class="quick-actions">
            <a href="speakers.php" class="action-card">
                <div class="action-icon">
                    <i data-lucide="user-plus"></i>
                </div>
                <h3>Add Speaker</h3>
                <p>Add a new speaker to the summit</p>
            </a>
            
            <a href="applications.php" class="action-card">
                <div class="action-icon">
                    <i data-lucide="eye"></i>
                </div>
                <h3>Review Applications</h3>
                <p>Review pending summit applications</p>
            </a>
            
            <a href="messages.php" class="action-card">
                <div class="action-icon">
                    <i data-lucide="message-circle"></i>
                </div>
                <h3>Messages</h3>
                <p>View contact messages</p>
            </a>
            
            <a href="../index.html" target="_blank" class="action-card">
                <div class="action-icon">
                    <i data-lucide="external-link"></i>
                </div>
                <h3>View Website</h3>
                <p>Visit the public website</p>
            </a>
        </div>
    </div>
    
    <div class="dashboard-grid">
        <!-- Recent Applications -->
        <div class="data-table">
            <div class="table-header">
                <h3>Recent Applications</h3>
                <a href="applications.php" class="btn btn-sm btn-outline">View All</a>
            </div>
            <div class="table-container">
                <?php if (empty($recentApplications)): ?>
                    <div class="empty-state">
                        <div class="empty-icon">
                            <i data-lucide="inbox"></i>
                        </div>
                        <h4>No Applications Yet</h4>
                        <p>Summit applications will appear here when submitted.</p>
                    </div>
                <?php else: ?>
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Applicant</th>
                                <th>Organization</th>
                                <th>Type</th>
                                <th>Status</th>
                                <th>Applied</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recentApplications as $application): ?>
                                <tr>
                                    <td>
                                        <strong><?php echo htmlspecialchars($application['first_name'] . ' ' . $application['last_name']); ?></strong>
                                        <br>
                                        <small class="text-muted"><?php echo htmlspecialchars($application['email']); ?></small>
                                    </td>
                                    <td><?php echo htmlspecialchars($application['organization'] ?: '-'); ?></td>
                                    <td><span class="type-badge"><?php echo ucfirst($application['application_type']); ?></span></td>
                                    <td>
                                        <span class="status-badge <?php echo $application['status']; ?>">
                                            <?php echo ucfirst($application['status']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <time title="<?php echo date('F j, Y \a\t g:i A', strtotime($application['applied_at'])); ?>">
                                            <?php echo date('M j', strtotime($application['applied_at'])); ?>
                                        </time>
                                    </td>
                                    <td class="table-actions-cell">
                                        <button class="table-action-btn view" onclick="viewApplication(<?php echo $application['id']; ?>)">
                                            <i data-lucide="eye"></i>
                                            View
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Recent Activity -->
        <div class="data-table">
            <div class="table-header">
                <h3>Recent Activity</h3>
            </div>
            <div class="activity-list">
                <?php if (empty($recentActivity)): ?>
                    <div class="empty-state">
                        <div class="empty-icon">
                            <i data-lucide="activity"></i>
                        </div>
                        <h4>No Recent Activity</h4>
                        <p>Admin activities will appear here.</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($recentActivity as $activity): ?>
                        <div class="activity-item">
                            <div class="activity-icon">
                                <i data-lucide="<?php echo getActivityIcon($activity['action']); ?>"></i>
                            </div>
                            <div class="activity-content">
                                <p>
                                    <strong><?php echo htmlspecialchars($activity['admin_name']); ?></strong>
                                    <?php echo formatActivityAction($activity['action']); ?>
                                    <?php if ($activity['table_name']): ?>
                                        in <em><?php echo ucfirst(str_replace('_', ' ', $activity['table_name'])); ?></em>
                                    <?php endif; ?>
                                </p>
                                <time class="text-muted">
                                    <?php echo date('M j, Y \a\t g:i A', strtotime($activity['created_at'])); ?>
                                </time>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<style>
.dashboard-header {
    margin-bottom: var(--spacing-xl);
}

.text-muted {
    color: var(--medium-gray);
    font-size: 0.95rem;
    margin-top: var(--spacing-xs);
}

.quick-actions {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: var(--spacing-lg);
    margin-top: var(--spacing-lg);
}

.action-card {
    background: white;
    padding: var(--spacing-lg);
    border-radius: var(--radius-lg);
    box-shadow: var(--shadow-sm);
    border: 1px solid #E5E5E5;
    text-decoration: none;
    color: var(--dark-gray);
    transition: var(--transition);
    display: block;
}

.action-card:hover {
    box-shadow: var(--shadow-md);
    transform: translateY(-2px);
    color: var(--dark-gray);
}

.action-icon {
    width: 48px;
    height: 48px;
    border-radius: var(--radius-md);
    background: rgba(200, 16, 46, 0.1);
    color: var(--primary-color);
    display: flex;
    align-items: center;
    justify-content: center;
    margin-bottom: var(--spacing-md);
}

.action-card h3 {
    margin: 0 0 var(--spacing-xs) 0;
    font-size: 1.1rem;
}

.action-card p {
    margin: 0;
    color: var(--medium-gray);
    font-size: 0.9rem;
}

.dashboard-grid {
    display: grid;
    grid-template-columns: 2fr 1fr;
    gap: var(--spacing-xl);
    margin-top: var(--spacing-xl);
}

.section-header {
    margin-bottom: var(--spacing-lg);
}

.section-header h2 {
    margin: 0;
    color: var(--dark-gray);
}

.empty-state {
    text-align: center;
    padding: var(--spacing-xxl);
    color: var(--medium-gray);
}

.empty-icon {
    width: 64px;
    height: 64px;
    border-radius: 50%;
    background: var(--light-gray);
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto var(--spacing-lg) auto;
}

.empty-icon i {
    width: 32px;
    height: 32px;
    color: var(--medium-gray);
}

.empty-state h4 {
    margin: 0 0 var(--spacing-sm) 0;
    color: var(--dark-gray);
}

.empty-state p {
    margin: 0;
    font-size: 0.9rem;
}

.activity-list {
    padding: var(--spacing-lg);
    max-height: 400px;
    overflow-y: auto;
}

.activity-item {
    display: flex;
    gap: var(--spacing-md);
    padding: var(--spacing-md) 0;
    border-bottom: 1px solid #F3F4F6;
}

.activity-item:last-child {
    border-bottom: none;
}

.activity-icon {
    width: 32px;
    height: 32px;
    border-radius: 50%;
    background: var(--light-gray);
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}

.activity-icon i {
    width: 16px;
    height: 16px;
    color: var(--medium-gray);
}

.activity-content {
    flex: 1;
}

.activity-content p {
    margin: 0 0 var(--spacing-xs) 0;
    font-size: 0.9rem;
}

.activity-content time {
    font-size: 0.8rem;
}

.type-badge {
    background: rgba(59, 130, 246, 0.1);
    color: #3B82F6;
    padding: 4px 8px;
    border-radius: 12px;
    font-size: 0.75rem;
    font-weight: 500;
}

@media (max-width: 768px) {
    .dashboard-grid {
        grid-template-columns: 1fr;
    }
    
    .quick-actions {
        grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
    }
}
</style>

<script>
function viewApplication(id) {
    window.location.href = 'applications.php?view=' + id;
}
</script>

<?php
include 'includes/footer.php';

// Helper functions
function getActivityIcon($action) {
    $icons = [
        'login_success' => 'log-in',
        'create' => 'plus',
        'update' => 'edit',
        'delete' => 'trash-2',
        'approve' => 'check',
        'reject' => 'x',
        'upload' => 'upload',
        'export' => 'download'
    ];
    
    return $icons[$action] ?? 'activity';
}

function formatActivityAction($action) {
    $actions = [
        'login_success' => 'logged in',
        'create' => 'created a record',
        'update' => 'updated a record',
        'delete' => 'deleted a record',
        'approve' => 'approved a record',
        'reject' => 'rejected a record',
        'upload' => 'uploaded a file',
        'export' => 'exported data'
    ];
    
    return $actions[$action] ?? $action;
}
?>