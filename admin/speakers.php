<?php
/**
 * Admin Speakers Management
 */

define('BOGF_ACCESS', true);
require_once '../includes/config.php';

// Require admin login
requireAdminLogin();

$pageTitle = 'Speakers Management';
$admin = getCurrentAdmin();
$adminId = getCurrentAdminId();

// Handle form submissions
$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    try {
        $db = Database::getInstance()->getConnection();
        
        switch ($action) {
            case 'add':
            case 'edit':
                $result = handleSpeakerForm($db, $action, $_POST, $_FILES, $adminId);
                $message = $result['message'];
                $messageType = $result['type'];
                break;
                
            case 'delete':
                $speakerId = (int)$_POST['speaker_id'];
                if ($speakerId > 0) {
                    $stmt = $db->prepare("UPDATE speakers SET is_active = 0 WHERE id = ?");
                    $stmt->execute([$speakerId]);
                    
                    logAdminActivity($adminId, 'delete', 'speakers', $speakerId);
                    
                    $message = 'Speaker deactivated successfully';
                    $messageType = 'success';
                }
                break;
                
            case 'toggle_featured':
                $speakerId = (int)$_POST['speaker_id'];
                $currentStatus = (int)$_POST['current_status'];
                $newStatus = $currentStatus ? 0 : 1;
                
                $stmt = $db->prepare("UPDATE speakers SET is_featured = ? WHERE id = ?");
                $stmt->execute([$newStatus, $speakerId]);
                
                logAdminActivity($adminId, 'update', 'speakers', $speakerId);
                
                $message = $newStatus ? 'Speaker featured successfully' : 'Speaker unfeatured successfully';
                $messageType = 'success';
                break;
        }
    } catch (Exception $e) {
        $message = 'Error: ' . $e->getMessage();
        $messageType = 'error';
        error_log("Admin speakers error: " . $e->getMessage());
    }
}

// Get speakers data
try {
    $db = Database::getInstance()->getConnection();
    
    // Get filter parameters
    $filterCategory = $_GET['category'] ?? 'all';
    $filterStatus = $_GET['status'] ?? 'active';
    $search = $_GET['search'] ?? '';
    $page = max(1, (int)($_GET['page'] ?? 1));
    $limit = SPEAKERS_PER_PAGE;
    $offset = ($page - 1) * $limit;
    
    // Build WHERE clause
    $whereConditions = [];
    $params = [];
    
    if ($filterStatus === 'active') {
        $whereConditions[] = 'is_active = 1';
    } elseif ($filterStatus === 'inactive') {
        $whereConditions[] = 'is_active = 0';
    }
    
    if ($filterCategory !== 'all') {
        $whereConditions[] = 'category = ?';
        $params[] = $filterCategory;
    }
    
    if ($search) {
        $whereConditions[] = '(name LIKE ? OR organization LIKE ? OR title LIKE ?)';
        $searchTerm = "%$search%";
        $params[] = $searchTerm;
        $params[] = $searchTerm;
        $params[] = $searchTerm;
    }
    
    $whereClause = empty($whereConditions) ? '' : 'WHERE ' . implode(' AND ', $whereConditions);
    
    // Get total count
    $countQuery = "SELECT COUNT(*) as total FROM speakers $whereClause";
    $countStmt = $db->prepare($countQuery);
    $countStmt->execute($params);
    $totalSpeakers = $countStmt->fetch()['total'];
    
    // Get speakers
    $query = "
        SELECT s.*, CONCAT(au.first_name, ' ', au.last_name) as created_by_name
        FROM speakers s
        LEFT JOIN admin_users au ON s.created_by = au.id
        $whereClause
        ORDER BY s.display_order ASC, s.created_at DESC
        LIMIT $limit OFFSET $offset
    ";
    
    $stmt = $db->prepare($query);
    $stmt->execute($params);
    $speakers = $stmt->fetchAll();
    
    // Calculate pagination
    $totalPages = ceil($totalSpeakers / $limit);
    
    // Get category counts
    $categoryCounts = $db->query("
        SELECT category, COUNT(*) as count 
        FROM speakers 
        WHERE is_active = 1 
        GROUP BY category
    ")->fetchAll(PDO::FETCH_KEY_PAIR);
    
} catch (Exception $e) {
    error_log("Error fetching speakers: " . $e->getMessage());
    $speakers = [];
    $totalSpeakers = 0;
    $totalPages = 0;
    $categoryCounts = [];
}

include 'includes/header.php';
?>

<div class="admin-content">
    <!-- Page Header -->
    <div class="content-header">
        <div class="header-left">
            <h1>Speakers Management</h1>
            <p class="header-subtitle">Manage summit speakers and their information</p>
        </div>
        <div class="header-actions">
            <button class="btn btn-primary" onclick="openAddSpeakerModal()">
                <i data-lucide="user-plus"></i>
                Add New Speaker
            </button>
        </div>
    </div>
    
    <?php if ($message): ?>
        <div class="alert alert-<?php echo $messageType; ?>">
            <i data-lucide="<?php echo $messageType === 'success' ? 'check-circle' : 'x-circle'; ?>"></i>
            <span><?php echo htmlspecialchars($message); ?></span>
        </div>
    <?php endif; ?>
    
    <!-- Filters and Search -->
    <div class="filters-section">
        <div class="filters-row">
            <div class="filter-group">
                <label for="categoryFilter">Category:</label>
                <select id="categoryFilter" onchange="applyFilters()">
                    <option value="all" <?php echo $filterCategory === 'all' ? 'selected' : ''; ?>>All Categories</option>
                    <option value="keynote" <?php echo $filterCategory === 'keynote' ? 'selected' : ''; ?>>Keynote (<?php echo $categoryCounts['keynote'] ?? 0; ?>)</option>
                    <option value="panelist" <?php echo $filterCategory === 'panelist' ? 'selected' : ''; ?>>Panelist (<?php echo $categoryCounts['panelist'] ?? 0; ?>)</option>
                    <option value="moderator" <?php echo $filterCategory === 'moderator' ? 'selected' : ''; ?>>Moderator (<?php echo $categoryCounts['moderator'] ?? 0; ?>)</option>
                    <option value="roundtable" <?php echo $filterCategory === 'roundtable' ? 'selected' : ''; ?>>Roundtable (<?php echo $categoryCounts['roundtable'] ?? 0; ?>)</option>
                </select>
            </div>
            
            <div class="filter-group">
                <label for="statusFilter">Status:</label>
                <select id="statusFilter" onchange="applyFilters()">
                    <option value="active" <?php echo $filterStatus === 'active' ? 'selected' : ''; ?>>Active</option>
                    <option value="inactive" <?php echo $filterStatus === 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                    <option value="all" <?php echo $filterStatus === 'all' ? 'selected' : ''; ?>>All</option>
                </select>
            </div>
            
            <div class="search-group">
                <div class="search-input">
                    <i data-lucide="search"></i>
                    <input type="text" id="searchInput" placeholder="Search speakers..." value="<?php echo htmlspecialchars($search); ?>" onkeyup="handleSearch(event)">
                    <?php if ($search): ?>
                        <button type="button" class="search-clear" onclick="clearSearch()">
                            <i data-lucide="x"></i>
                        </button>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <div class="results-info">
            <span><?php echo $totalSpeakers; ?> speaker<?php echo $totalSpeakers !== 1 ? 's' : ''; ?> found</span>
            <?php if ($search): ?>
                <span class="search-term">for "<?php echo htmlspecialchars($search); ?>"</span>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Speakers Table -->
    <div class="data-table">
        <div class="table-container">
            <?php if (empty($speakers)): ?>
                <div class="empty-state">
                    <div class="empty-icon">
                        <i data-lucide="users"></i>
                    </div>
                    <h3>No Speakers Found</h3>
                    <p>
                        <?php if ($search || $filterCategory !== 'all' || $filterStatus !== 'active'): ?>
                            No speakers match your current filters. Try adjusting your search criteria.
                        <?php else: ?>
                            Get started by adding your first speaker to the summit.
                        <?php endif; ?>
                    </p>
                    <?php if (!$search && $filterCategory === 'all' && $filterStatus === 'active'): ?>
                        <button class="btn btn-primary" onclick="openAddSpeakerModal()">
                            <i data-lucide="user-plus"></i>
                            Add First Speaker
                        </button>
                    <?php endif; ?>
                </div>
            <?php else: ?>
                <table class="table">
                    <thead>
                        <tr>
                            <th>Speaker</th>
                            <th>Category</th>
                            <th>Organization</th>
                            <th>Status</th>
                            <th>Featured</th>
                            <th>Created</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($speakers as $speaker): ?>
                            <tr>
                                <td class="speaker-cell">
                                    <div class="speaker-info">
                                        <?php if ($speaker['image_url']): ?>
                                            <img src="<?php echo htmlspecialchars($speaker['image_url']); ?>" 
                                                 alt="<?php echo htmlspecialchars($speaker['name']); ?>"
                                                 class="speaker-avatar"
                                                 onerror="this.src='../assets/images/default-avatar.jpg'">
                                        <?php else: ?>
                                            <div class="speaker-avatar-placeholder">
                                                <?php echo strtoupper(substr($speaker['name'], 0, 2)); ?>
                                            </div>
                                        <?php endif; ?>
                                        <div class="speaker-details">
                                            <strong><?php echo htmlspecialchars($speaker['name']); ?></strong>
                                            <?php if ($speaker['title']): ?>
                                                <div class="speaker-title"><?php echo htmlspecialchars($speaker['title']); ?></div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <span class="category-badge <?php echo $speaker['category']; ?>">
                                        <?php echo ucfirst($speaker['category']); ?>
                                    </span>
                                </td>
                                <td><?php echo htmlspecialchars($speaker['organization'] ?: '-'); ?></td>
                                <td>
                                    <span class="status-badge <?php echo $speaker['is_active'] ? 'active' : 'inactive'; ?>">
                                        <?php echo $speaker['is_active'] ? 'Active' : 'Inactive'; ?>
                                    </span>
                                </td>
                                <td>
                                    <button class="featured-toggle <?php echo $speaker['is_featured'] ? 'featured' : 'not-featured'; ?>"
                                            onclick="toggleFeatured(<?php echo $speaker['id']; ?>, <?php echo $speaker['is_featured']; ?>)"
                                            title="<?php echo $speaker['is_featured'] ? 'Remove from featured' : 'Add to featured'; ?>">
                                        <i data-lucide="<?php echo $speaker['is_featured'] ? 'star' : 'star'; ?>"></i>
                                    </button>
                                </td>
                                <td>
                                    <time title="<?php echo date('F j, Y \a\t g:i A', strtotime($speaker['created_at'])); ?>">
                                        <?php echo date('M j, Y', strtotime($speaker['created_at'])); ?>
                                    </time>
                                    <?php if ($speaker['created_by_name']): ?>
                                        <small class="text-muted">by <?php echo htmlspecialchars($speaker['created_by_name']); ?></small>
                                    <?php endif; ?>
                                </td>
                                <td class="table-actions-cell">
                                    <button class="table-action-btn view" onclick="viewSpeaker(<?php echo $speaker['id']; ?>)" title="View Details">
                                        <i data-lucide="eye"></i>
                                    </button>
                                    <button class="table-action-btn edit" onclick="editSpeaker(<?php echo $speaker['id']; ?>)" title="Edit Speaker">
                                        <i data-lucide="edit"></i>
                                    </button>
                                    <?php if ($speaker['is_active']): ?>
                                        <button class="table-action-btn delete" 
                                                onclick="deleteSpeaker(<?php echo $speaker['id']; ?>, '<?php echo htmlspecialchars($speaker['name']); ?>')"
                                                title="Deactivate Speaker">
                                            <i data-lucide="trash-2"></i>
                                        </button>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Pagination -->
    <?php if ($totalPages > 1): ?>
        <div class="pagination">
            <?php
            $currentUrl = strtok($_SERVER['REQUEST_URI'], '?');
            $queryParams = $_GET;
            ?>
            
            <?php if ($page > 1): ?>
                <?php
                $queryParams['page'] = $page - 1;
                $prevUrl = $currentUrl . '?' . http_build_query($queryParams);
                ?>
                <a href="<?php echo $prevUrl; ?>" class="pagination-btn">
                    <i data-lucide="chevron-left"></i>
                    Previous
                </a>
            <?php endif; ?>
            
            <div class="pagination-info">
                Page <?php echo $page; ?> of <?php echo $totalPages; ?>
            </div>
            
            <?php if ($page < $totalPages): ?>
                <?php
                $queryParams['page'] = $page + 1;
                $nextUrl = $currentUrl . '?' . http_build_query($queryParams);
                ?>
                <a href="<?php echo $nextUrl; ?>" class="pagination-btn">
                    Next
                    <i data-lucide="chevron-right"></i>
                </a>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</div>

<!-- Add/Edit Speaker Modal -->
<div class="modal" id="speakerModal">
    <div class="modal-content">
        <div class="modal-header">
            <h3 id="modalTitle">Add New Speaker</h3>
            <button class="modal-close" onclick="closeSpeakerModal()">&times;</button>
        </div>
        <div class="modal-body">
            <form id="speakerForm" method="POST" enctype="multipart/form-data">
                <input type="hidden" name="action" id="formAction" value="add">
                <input type="hidden" name="speaker_id" id="speakerId">
                
                <!-- Speaker Image -->
                <div class="form-section">
                    <div class="image-upload-section">
                        <div class="current-image" id="currentImage" style="display: none;">
                            <img id="currentImagePreview" src="" alt="Current speaker image">
                        </div>
                        <div class="image-upload">
                            <label for="speakerImage">Speaker Photo</label>
                            <input type="file" id="speakerImage" name="speaker_image" accept="image/*" onchange="previewImage(this)">
                            <div class="upload-help">
                                <p>Upload a professional headshot (JPG, PNG, or WebP)</p>
                                <p>Recommended size: 400x400px, Max size: 5MB</p>
                            </div>
                        </div>
                        <div class="image-preview" id="imagePreview" style="display: none;">
                            <img id="previewImg" src="" alt="Image preview">
                        </div>
                    </div>
                </div>
                
                <!-- Basic Information -->
                <div class="form-section">
                    <h4>Basic Information</h4>
                    <div class="form-grid">
                        <div class="form-group">
                            <label for="speakerName">Full Name *</label>
                            <input type="text" id="speakerName" name="name" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="speakerCategory">Category *</label>
                            <select id="speakerCategory" name="category" required>
                                <option value="">Select Category</option>
                                <option value="keynote">Keynote Speaker</option>
                                <option value="panelist">Panelist</option>
                                <option value="moderator">Moderator</option>
                                <option value="roundtable">Roundtable Lead</option>
                                <option value="host">Session Host</option>
                                <option value="guest">Special Guest</option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label for="speakerTitle">Job Title</label>
                            <input type="text" id="speakerTitle" name="title">
                        </div>
                        
                        <div class="form-group">
                            <label for="speakerOrganization">Organization</label>
                            <input type="text" id="speakerOrganization" name="organization">
                        </div>
                    </div>
                </div>
                
                <!-- Biography -->
                <div class="form-section">
                    <h4>Biography</h4>
                    <div class="form-group">
                        <label for="speakerBio">Biography</label>
                        <textarea id="speakerBio" name="bio" rows="6" placeholder="Write a comprehensive biography of the speaker..."></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label for="speakerExpertise">Areas of Expertise</label>
                        <input type="text" id="speakerExpertise" name="expertise" 
                               placeholder="Comma-separated expertise areas (e.g., Fashion Design, Business Strategy, Marketing)">
                        <small class="help-text">Separate multiple areas with commas</small>
                    </div>
                </div>
                
                <!-- Social Links -->
                <div class="form-section">
                    <h4>Social Media & Links</h4>
                    <div class="form-grid">
                        <div class="form-group">
                            <label for="speakerLinkedIn">LinkedIn URL</label>
                            <input type="url" id="speakerLinkedIn" name="linkedin_url" placeholder="https://linkedin.com/in/username">
                        </div>
                        
                        <div class="form-group">
                            <label for="speakerTwitter">Twitter URL</label>
                            <input type="url" id="speakerTwitter" name="twitter_url" placeholder="https://twitter.com/username">
                        </div>
                        
                        <div class="form-group full-width">
                            <label for="speakerWebsite">Website URL</label>
                            <input type="url" id="speakerWebsite" name="website_url" placeholder="https://website.com">
                        </div>
                    </div>
                </div>
                
                <!-- Session Information -->
                <div class="form-section">
                    <h4>Session Information (Optional)</h4>
                    <div class="form-group">
                        <label for="sessionTitle">Session Title</label>
                        <input type="text" id="sessionTitle" name="session_title">
                    </div>
                    
                    <div class="form-group">
                        <label for="sessionDescription">Session Description</label>
                        <textarea id="sessionDescription" name="session_description" rows="3"></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label for="sessionTime">Session Time</label>
                        <input type="text" id="sessionTime" name="session_time" placeholder="e.g., 10:00 AM - 11:00 AM">
                    </div>
                </div>
                
                <!-- Settings -->
                <div class="form-section">
                    <h4>Settings</h4>
                    <div class="form-grid">
                        <div class="form-group">
                            <label for="displayOrder">Display Order</label>
                            <input type="number" id="displayOrder" name="display_order" min="0" value="0">
                            <small class="help-text">Lower numbers appear first</small>
                        </div>
                        
                        <div class="form-group">
                            <div class="checkbox-group">
                                <label class="checkbox-label">
                                    <input type="checkbox" id="isFeatured" name="is_featured" value="1">
                                    <span class="checkmark"></span>
                                    Featured Speaker
                                </label>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="form-actions">
                    <button type="button" class="btn btn-secondary" onclick="closeSpeakerModal()">Cancel</button>
                    <button type="submit" class="btn btn-primary">
                        <i data-lucide="save"></i>
                        <span id="submitButtonText">Add Speaker</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<style>
.content-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: var(--spacing-xl);
}

.header-subtitle {
    color: var(--medium-gray);
    margin-top: var(--spacing-xs);
}

.filters-section {
    background: white;
    padding: var(--spacing-lg);
    border-radius: var(--radius-md);
    box-shadow: var(--shadow-sm);
    margin-bottom: var(--spacing-xl);
}

.filters-row {
    display: flex;
    gap: var(--spacing-lg);
    margin-bottom: var(--spacing-md);
}

.filter-group {
    display: flex;
    flex-direction: column;
    min-width: 150px;
}

.filter-group label {
    font-weight: 500;
    margin-bottom: var(--spacing-xs);
    font-size: 0.9rem;
}

.filter-group select {
    padding: var(--spacing-sm);
    border: 1px solid #D1D5DB;
    border-radius: var(--radius-sm);
    font-size: 0.9rem;
}

.search-group {
    flex: 1;
}

.search-input {
    position: relative;
}

.search-input i {
    position: absolute;
    left: 12px;
    top: 50%;
    transform: translateY(-50%);
    color: var(--medium-gray);
    width: 18px;
    height: 18px;
}

.search-input input {
    width: 100%;
    padding: var(--spacing-sm) var(--spacing-sm) var(--spacing-sm) 40px;
    border: 1px solid #D1D5DB;
    border-radius: var(--radius-sm);
    font-size: 0.9rem;
}

.search-clear {
    position: absolute;
    right: 8px;
    top: 50%;
    transform: translateY(-50%);
    background: none;
    border: none;
    cursor: pointer;
    color: var(--medium-gray);
    padding: 4px;
    border-radius: 4px;
}

.search-clear:hover {
    color: var(--dark-gray);
    background: var(--light-gray);
}

.results-info {
    font-size: 0.9rem;
    color: var(--medium-gray);
}

.search-term {
    font-weight: 500;
    color: var(--primary-color);
}

.speaker-cell {
    min-width: 200px;
}

.speaker-info {
    display: flex;
    align-items: center;
    gap: var(--spacing-md);
}

.speaker-avatar {
    width: 48px;
    height: 48px;
    border-radius: 50%;
    object-fit: cover;
}

.speaker-avatar-placeholder {
    width: 48px;
    height: 48px;
    border-radius: 50%;
    background: var(--primary-color);
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 600;
    font-size: 0.9rem;
}

.speaker-details strong {
    display: block;
    color: var(--dark-gray);
}

.speaker-title {
    font-size: 0.85rem;
    color: var(--medium-gray);
    margin-top: 2px;
}

.category-badge {
    padding: 4px 8px;
    border-radius: 12px;
    font-size: 0.75rem;
    font-weight: 500;
    text-transform: uppercase;
}

.category-badge.keynote {
    background: #FEE2E2;
    color: #991B1B;
}

.category-badge.panelist {
    background: #DBEAFE;
    color: #1E40AF;
}

.category-badge.moderator {
    background: #D1FAE5;
    color: #065F46;
}

.category-badge.roundtable {
    background: #FEF3C7;
    color: #92400E;
}

.featured-toggle {
    background: none;
    border: none;
    cursor: pointer;
    padding: 8px;
    border-radius: 50%;
    transition: var(--transition);
}

.featured-toggle.featured {
    color: #F59E0B;
}

.featured-toggle.not-featured {
    color: #D1D5DB;
}

.featured-toggle:hover {
    background: var(--light-gray);
}

.pagination {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-top: var(--spacing-xl);
    padding: var(--spacing-lg);
    background: white;
    border-radius: var(--radius-md);
    box-shadow: var(--shadow-sm);
}

.pagination-btn {
    display: flex;
    align-items: center;
    gap: var(--spacing-xs);
    padding: var(--spacing-sm) var(--spacing-md);
    background: var(--light-gray);
    color: var(--dark-gray);
    text-decoration: none;
    border-radius: var(--radius-sm);
    transition: var(--transition);
}

.pagination-btn:hover {
    background: var(--primary-color);
    color: white;
}

.pagination-info {
    font-weight: 500;
    color: var(--dark-gray);
}

/* Modal Specific Styles */
.modal-content {
    width: 90vw;
    max-width: 800px;
}

.form-section {
    margin-bottom: var(--spacing-xl);
    padding-bottom: var(--spacing-lg);
    border-bottom: 1px solid #F3F4F6;
}

.form-section:last-child {
    border-bottom: none;
    margin-bottom: 0;
}

.form-section h4 {
    color: var(--primary-color);
    margin-bottom: var(--spacing-md);
    font-size: 1.1rem;
}

.image-upload-section {
    display: flex;
    gap: var(--spacing-lg);
    align-items: flex-start;
}

.current-image,
.image-preview {
    flex-shrink: 0;
}

.current-image img,
.image-preview img {
    width: 120px;
    height: 120px;
    border-radius: var(--radius-md);
    object-fit: cover;
    border: 2px solid #E5E5E5;
}

.image-upload {
    flex: 1;
}

.upload-help {
    margin-top: var(--spacing-sm);
}

.upload-help p {
    font-size: 0.85rem;
    color: var(--medium-gray);
    margin: 2px 0;
}

.help-text {
    font-size: 0.85rem;
    color: var(--medium-gray);
    margin-top: var(--spacing-xs);
}

.checkbox-group {
    margin-top: var(--spacing-md);
}

.form-actions {
    display: flex;
    justify-content: flex-end;
    gap: var(--spacing-md);
    margin-top: var(--spacing-xl);
    padding-top: var(--spacing-lg);
    border-top: 1px solid #F3F4F6;
}

@media (max-width: 768px) {
    .content-header {
        flex-direction: column;
        gap: var(--spacing-md);
    }
    
    .filters-row {
        flex-direction: column;
        gap: var(--spacing-md);
    }
    
    .image-upload-section {
        flex-direction: column;
    }
    
    .form-actions {
        flex-direction: column;
    }
    
    .pagination {
        flex-direction: column;
        gap: var(--spacing-md);
    }
}
</style>

<script>
// Initialize page
document.addEventListener('DOMContentLoaded', function() {
    if (typeof lucide !== 'undefined') {
        lucide.createIcons();
    }
});

// Filter and search functions
function applyFilters() {
    const category = document.getElementById('categoryFilter').value;
    const status = document.getElementById('statusFilter').value;
    const search = document.getElementById('searchInput').value;
    
    updateURL({ category, status, search, page: 1 });
}

function handleSearch(event) {
    if (event.key === 'Enter') {
        applyFilters();
    }
}

function clearSearch() {
    document.getElementById('searchInput').value = '';
    applyFilters();
}

function updateURL(params) {
    const url = new URL(window.location);
    Object.keys(params).forEach(key => {
        if (params[key] && params[key] !== 'all' && params[key] !== '') {
            url.searchParams.set(key, params[key]);
        } else {
            url.searchParams.delete(key);
        }
    });
    window.location.href = url.toString();
}

// Speaker modal functions
function openAddSpeakerModal() {
    document.getElementById('modalTitle').textContent = 'Add New Speaker';
    document.getElementById('formAction').value = 'add';
    document.getElementById('submitButtonText').textContent = 'Add Speaker';
    document.getElementById('speakerForm').reset();
    document.getElementById('speakerId').value = '';
    hideImagePreview();
    document.getElementById('speakerModal').classList.add('show');
    document.body.style.overflow = 'hidden';
}

function editSpeaker(id) {
    // This would typically fetch speaker data via AJAX
    // For now, we'll redirect to a separate edit page or implement AJAX
    window.location.href = `speakers.php?edit=${id}`;
}

function viewSpeaker(id) {
    // Open speaker in a view modal or redirect
    window.open(`../speakers.html#speaker-${id}`, '_blank');
}

function closeSpeakerModal() {
    document.getElementById('speakerModal').classList.remove('show');
    document.body.style.overflow = '';
}

function previewImage(input) {
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        
        reader.onload = function(e) {
            document.getElementById('previewImg').src = e.target.result;
            document.getElementById('imagePreview').style.display = 'block';
            document.getElementById('currentImage').style.display = 'none';
        };
        
        reader.readAsDataURL(input.files[0]);
    }
}

function hideImagePreview() {
    document.getElementById('imagePreview').style.display = 'none';
    document.getElementById('currentImage').style.display = 'none';
}

// Speaker actions
function toggleFeatured(id, currentStatus) {
    const form = document.createElement('form');
    form.method = 'POST';
    form.style.display = 'none';
    
    form.innerHTML = `
        <input name="action" value="toggle_featured">
        <input name="speaker_id" value="${id}">
        <input name="current_status" value="${currentStatus}">
    `;
    
    document.body.appendChild(form);
    form.submit();
}

function deleteSpeaker(id, name) {
    if (confirm(`Are you sure you want to deactivate "${name}"? This action can be reversed later.`)) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.style.display = 'none';
        
        form.innerHTML = `
            <input name="action" value="delete">
            <input name="speaker_id" value="${id}">
        `;
        
        document.body.appendChild(form);
        form.submit();
    }
}

// Close modal when clicking outside
document.addEventListener('click', function(e) {
    const modal = document.getElementById('speakerModal');
    if (e.target === modal) {
        closeSpeakerModal();
    }
});
</script>

<?php
include 'includes/footer.php';

/**
 * Handle speaker form submission
 */
function handleSpeakerForm($db, $action, $postData, $files, $adminId) {
    $speakerId = $action === 'edit' ? (int)$postData['speaker_id'] : null;
    
    // Validate required fields
    $requiredFields = ['name', 'category'];
    foreach ($requiredFields as $field) {
        if (empty($postData[$field])) {
            throw new Exception("$field is required");
        }
    }
    
    // Handle image upload
    $imagePath = null;
    if (isset($files['speaker_image']) && $files['speaker_image']['error'] === UPLOAD_ERR_OK) {
        try {
            $imagePath = uploadImage($files['speaker_image'], 'uploads/speakers/');
        } catch (Exception $e) {
            throw new Exception("Image upload failed: " . $e->getMessage());
        }
    }
    
    // Prepare data
    $data = [
        'name' => sanitizeInput($postData['name']),
        'title' => sanitizeInput($postData['title'] ?? ''),
        'organization' => sanitizeInput($postData['organization'] ?? ''),
        'bio' => sanitizeInput($postData['bio'] ?? ''),
        'category' => sanitizeInput($postData['category']),
        'expertise' => sanitizeInput($postData['expertise'] ?? ''),
        'linkedin_url' => !empty($postData['linkedin_url']) ? filter_var($postData['linkedin_url'], FILTER_VALIDATE_URL) : null,
        'twitter_url' => !empty($postData['twitter_url']) ? filter_var($postData['twitter_url'], FILTER_VALIDATE_URL) : null,
        'website_url' => !empty($postData['website_url']) ? filter_var($postData['website_url'], FILTER_VALIDATE_URL) : null,
        'session_title' => sanitizeInput($postData['session_title'] ?? ''),
        'session_description' => sanitizeInput($postData['session_description'] ?? ''),
        'session_time' => sanitizeInput($postData['session_time'] ?? ''),
        'is_featured' => !empty($postData['is_featured']) && $postData['is_featured'] === '1',
        'display_order' => (int)($postData['display_order'] ?? 0),
        'created_by' => $adminId
    ];
    
    if ($imagePath) {
        $data['image_url'] = $imagePath;
    }
    
    if ($action === 'add') {
        // Insert new speaker
        $fields = array_keys($data);
        $placeholders = array_fill(0, count($fields), '?');
        
        $sql = "INSERT INTO speakers (" . implode(', ', $fields) . ") VALUES (" . implode(', ', $placeholders) . ")";
        $stmt = $db->prepare($sql);
        $stmt->execute(array_values($data));
        
        $speakerId = $db->lastInsertId();
        logAdminActivity($adminId, 'create', 'speakers', $speakerId, null, $data);
        
        return ['message' => 'Speaker added successfully', 'type' => 'success'];
        
    } else {
        // Update existing speaker
        $updateFields = [];
        $updateValues = [];
        
        foreach ($data as $field => $value) {
            if ($field !== 'created_by') { // Don't update created_by
                $updateFields[] = "$field = ?";
                $updateValues[] = $value;
            }
        }
        
        $updateValues[] = $speakerId;
        
        $sql = "UPDATE speakers SET " . implode(', ', $updateFields) . " WHERE id = ?";
        $stmt = $db->prepare($sql);
        $stmt->execute($updateValues);
        
        logAdminActivity($adminId, 'update', 'speakers', $speakerId, null, $data);
        
        return ['message' => 'Speaker updated successfully', 'type' => 'success'];
    }
}
?>