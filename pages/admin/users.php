<?php
// Get database connection
$conn = getDatabaseConnection();

// Handle user actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'toggle_admin') {
        $user_id = intval($_POST['user_id']);
        $is_admin = intval($_POST['is_admin']);
        
        $stmt = $conn->prepare("UPDATE users SET is_admin = ? WHERE id = ?");
        if ($stmt->execute([$is_admin, $user_id])) {
            $success = "User admin status updated successfully!";
        } else {
            $error = "Failed to update user admin status.";
        }
    } elseif ($_POST['action'] === 'toggle_active') {
        $user_id = intval($_POST['user_id']);
        $is_active = intval($_POST['is_active']);
        
        $stmt = $conn->prepare("UPDATE users SET is_active = ? WHERE id = ?");
        if ($stmt->execute([$is_active, $user_id])) {
            $success = "User status updated successfully!";
        } else {
            $error = "Failed to update user status.";
        }
    } elseif ($_POST['action'] === 'delete_user') {
        $user_id = intval($_POST['user_id']);
        
        // Don't allow deleting the current admin user
        if ($user_id == $_SESSION['user_id']) {
            $error = "You cannot delete your own account.";
        } else {
            $stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
            if ($stmt->execute([$user_id])) {
                $success = "User deleted successfully!";
            } else {
                $error = "Failed to delete user.";
            }
        }
    }
}

// Get all users with pagination
$page = max(1, intval($_GET['page'] ?? 1));
$per_page = 20;
$offset = ($page - 1) * $per_page;

$search = $_GET['search'] ?? '';
$status_filter = $_GET['status'] ?? '';

// Build query
$where_conditions = ['1=1'];
$params = [];

if ($search) {
    $where_conditions[] = '(u.username LIKE ? OR u.email LIKE ?)';
    $search_term = '%' . $search . '%';
    $params[] = $search_term;
    $params[] = $search_term;
}

if ($status_filter === 'admin') {
    $where_conditions[] = 'u.is_admin = 1';
} elseif ($status_filter === 'user') {
    $where_conditions[] = 'u.is_admin = 0';
} elseif ($status_filter === 'active') {
    $where_conditions[] = 'u.is_active = 1';
} elseif ($status_filter === 'inactive') {
    $where_conditions[] = 'u.is_active = 0';
}

$where_clause = implode(' AND ', $where_conditions);

// Get total count
$count_sql = "SELECT COUNT(*) FROM users u WHERE $where_clause";
$stmt = $conn->prepare($count_sql);
$stmt->execute($params);
$total_users = $stmt->fetchColumn();

$total_pages = ceil($total_users / $per_page);

// Get users
$sql = "
    SELECT u.*, 
           COUNT(DISTINCT o.id) as total_orders,
           COUNT(DISTINCT lk.id) as total_keys,
           SUM(CASE WHEN o.status = 'paid' THEN o.total_price ELSE 0 END) as total_spent
    FROM users u
    LEFT JOIN orders o ON u.id = o.user_id
    LEFT JOIN license_keys lk ON u.id = lk.user_id
    WHERE $where_clause
    GROUP BY u.id
    ORDER BY u.created_at DESC
    LIMIT $per_page OFFSET $offset
";

$stmt = $conn->prepare($sql);
$stmt->execute($params);
$users = $stmt->fetchAll();
?>

<div class="admin-header">
    <div class="header-content">
        <div class="header-title">
            <h1><i class="fas fa-users"></i> Users Management</h1>
            <p>View and manage user accounts, roles, permissions, and security settings</p>
        </div>
        <div class="header-actions">
            <div class="quick-stats">
                <div class="stat-item">
                    <i class="fas fa-user-plus"></i>
                    <span>Add User</span>
                </div>
                <div class="stat-item">
                    <i class="fas fa-shield-alt"></i>
                    <span>Security</span>
                </div>
                <div class="stat-item">
                    <i class="fas fa-chart-pie"></i>
                    <span>Analytics</span>
                </div>
            </div>
            <a href="#" onclick="showAddUserModal()" class="btn btn-primary">
                <i class="fas fa-user-plus"></i> Add User
            </a>
        </div>
    </div>
</div>

    <?php if (isset($success)): ?>
        <div class="alert alert-success">
            <i class="fas fa-check-circle"></i>
            <?php echo htmlspecialchars($success); ?>
        </div>
    <?php endif; ?>

    <?php if (isset($error)): ?>
        <div class="alert alert-error">
            <i class="fas fa-exclamation-circle"></i>
            <?php echo htmlspecialchars($error); ?>
        </div>
    <?php endif; ?>

    <!-- Filters -->
    <div class="filters-section">
        <form method="GET" class="filters-form">
            <input type="hidden" name="page" value="admin">
            <input type="hidden" name="section" value="users">
            
            <div class="filters-row">
                <div class="filter-group">
                    <div class="input-wrapper">
                        <i class="fas fa-search input-icon"></i>
                        <input
                            type="text"
                            name="search"
                            class="form-input"
                            placeholder="Search users by username or email..."
                            value="<?php echo htmlspecialchars($search); ?>"
                        >
                    </div>
                </div>
                
                <div class="filter-group">
                    <select name="status" class="form-input">
                        <option value="">All Users</option>
                        <option value="admin" <?php echo $status_filter === 'admin' ? 'selected' : ''; ?>>Admins</option>
                        <option value="user" <?php echo $status_filter === 'user' ? 'selected' : ''; ?>>Regular Users</option>
                        <option value="active" <?php echo $status_filter === 'active' ? 'selected' : ''; ?>>Active</option>
                        <option value="inactive" <?php echo $status_filter === 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                    </select>
                </div>
                
                <div class="filter-group">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-search"></i>
                        Filter
                    </button>
                    <a href="index.php?page=admin&section=users" class="btn btn-outline">
                        <i class="fas fa-times"></i>
                        Clear
                    </a>
                </div>
            </div>
        </form>
    </div>

    <!-- Results Info -->
    <div class="results-info">
        <div class="results-stats">
            <div class="stat-item">
                <i class="fas fa-users"></i>
                <span>Showing <?php echo $total_users; ?> user<?php echo $total_users !== 1 ? 's' : ''; ?></span>
            </div>
            <?php if ($search || $status_filter): ?>
                <div class="stat-item">
                    <i class="fas fa-filter"></i>
                    <span>Filtered results</span>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Users Table -->
    <div class="table-container">
        <?php if (empty($users)): ?>
            <div class="empty-state">
                <i class="fas fa-users"></i>
                <h3>No users found</h3>
                <p>Users will appear here once they register</p>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>User</th>
                            <th>Email</th>
                            <th>Role</th>
                            <th>Status</th>
                            <th>Orders</th>
                            <th>Keys</th>
                            <th>Total Spent</th>
                            <th>Joined</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($users as $user): ?>
                            <tr>
                                <td>
                                    <div class="user-info">
                                        <div class="user-avatar">
                                            <i class="fas fa-user"></i>
                                        </div>
                                        <div class="user-details">
                                            <div class="username"><?php echo htmlspecialchars($user['username']); ?></div>
                                            <div class="user-id">ID: <?php echo $user['id']; ?></div>
                                        </div>
                                    </div>
                                </td>
                                <td><?php echo htmlspecialchars($user['email']); ?></td>
                                <td>
                                    <?php if ($user['is_admin']): ?>
                                        <span class="role-badge role-admin">
                                            <i class="fas fa-crown"></i>
                                            Admin
                                        </span>
                                    <?php else: ?>
                                        <span class="role-badge role-user">
                                            <i class="fas fa-user"></i>
                                            User
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($user['is_active']): ?>
                                        <span class="status-badge status-active">
                                            <i class="fas fa-circle"></i>
                                            Active
                                        </span>
                                    <?php else: ?>
                                        <span class="status-badge status-inactive">
                                            <i class="fas fa-circle"></i>
                                            Inactive
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="stat-number"><?php echo $user['total_orders']; ?></span>
                                </td>
                                <td>
                                    <span class="stat-number"><?php echo $user['total_keys']; ?></span>
                                </td>
                                <td>
                                    <strong>$<?php echo number_format($user['total_spent'] ?: 0, 2); ?></strong>
                                </td>
                                <td>
                                    <div class="date-info">
                                        <i class="fas fa-calendar"></i>
                                        <?php echo date('M j, Y', strtotime($user['created_at'])); ?>
                                    </div>
                                </td>
                                <td>
                                    <div class="action-buttons">
                                        <button class="btn btn-sm btn-outline" onclick="viewUser(<?php echo $user['id']; ?>)" title="View User">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                        
                                        <?php if ($user['id'] != $_SESSION['user_id']): ?>
                                            <?php if ($user['is_admin']): ?>
                                                <form method="POST" style="display: inline;">
                                                    <input type="hidden" name="action" value="toggle_admin">
                                                    <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                                    <input type="hidden" name="is_admin" value="0">
                                                    <button type="submit" class="btn btn-sm btn-warning" onclick="return confirm('Remove admin privileges from this user?')" title="Remove Admin">
                                                        <i class="fas fa-user-minus"></i>
                                                    </button>
                                                </form>
                                            <?php else: ?>
                                                <form method="POST" style="display: inline;">
                                                    <input type="hidden" name="action" value="toggle_admin">
                                                    <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                                    <input type="hidden" name="is_admin" value="1">
                                                    <button type="submit" class="btn btn-sm btn-success" onclick="return confirm('Grant admin privileges to this user?')" title="Make Admin">
                                                        <i class="fas fa-user-plus"></i>
                                                    </button>
                                                </form>
                                            <?php endif; ?>
                                            
                                            <?php if ($user['is_active']): ?>
                                                <form method="POST" style="display: inline;">
                                                    <input type="hidden" name="action" value="toggle_active">
                                                    <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                                    <input type="hidden" name="is_active" value="0">
                                                    <button type="submit" class="btn btn-sm btn-warning" onclick="return confirm('Deactivate this user?')" title="Deactivate User">
                                                        <i class="fas fa-ban"></i>
                                                    </button>
                                                </form>
                                            <?php else: ?>
                                                <form method="POST" style="display: inline;">
                                                    <input type="hidden" name="action" value="toggle_active">
                                                    <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                                    <input type="hidden" name="is_active" value="1">
                                                    <button type="submit" class="btn btn-sm btn-success" title="Activate User">
                                                        <i class="fas fa-check"></i>
                                                    </button>
                                                </form>
                                            <?php endif; ?>
                                            
                                            <form method="POST" style="display: inline;">
                                                <input type="hidden" name="action" value="delete_user">
                                                <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                                <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Delete this user? This action cannot be undone.')" title="Delete User">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </form>
                                        <?php else: ?>
                                            <span class="text-muted">Current user</span>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <?php if ($total_pages > 1): ?>
                <div class="pagination-container">
                    <?php
                    $current_url = 'index.php?page=admin&section=users';
                    if ($search) $current_url .= '&search=' . urlencode($search);
                    if ($status_filter) $current_url .= '&status=' . urlencode($status_filter);
                    
                    echo getPagination($total_users, $per_page, $page, $current_url);
                    ?>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</div>

<!-- View User Modal -->
<div id="viewUserModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3><i class="fas fa-eye"></i> User Details</h3>
            <button class="modal-close" onclick="closeModal('viewUserModal')">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <div class="modal-body" id="userDetails">
            <!-- User details will be loaded here -->
        </div>
    </div>
</div>

<style>


.filters-section {
    background: rgba(30, 41, 59, 0.4);
    border-radius: 12px;
    padding: 1.5rem;
    margin-bottom: 2rem;
    border: 1px solid rgba(255, 255, 255, 0.1);
}

.filters-row {
    display: flex;
    gap: 1rem;
    align-items: end;
    flex-wrap: wrap;
}

.filter-group {
    flex: 1;
    min-width: 200px;
}

.input-wrapper {
    position: relative;
}

.input-icon {
    position: absolute;
    left: 1rem;
    top: 50%;
    transform: translateY(-50%);
    color: #94a3b8;
    z-index: 1;
}

.form-input {
    width: 100%;
    padding: 0.75rem 1rem 0.75rem 2.5rem;
    background: rgba(51, 65, 85, 0.5);
    border: 1px solid rgba(255, 255, 255, 0.2);
    border-radius: 8px;
    color: #f8fafc;
    font-size: 0.9rem;
    transition: all 0.3s ease;
}

.form-input:focus {
    outline: none;
    border-color: #ec4899;
    box-shadow: 0 0 0 3px rgba(236, 72, 153, 0.1);
}

.form-input::placeholder {
    color: #94a3b8;
}

select.form-input {
    padding-left: 1rem;
}

.results-info {
    margin-bottom: 1.5rem;
}

.results-stats {
    display: flex;
    gap: 1rem;
    flex-wrap: wrap;
}

.stat-item {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    color: #cbd5e1;
    font-size: 0.9rem;
}

.table-container {
    background: rgba(30, 41, 59, 0.4);
    border-radius: 12px;
    overflow: hidden;
    border: 1px solid rgba(255, 255, 255, 0.1);
}

.table-responsive {
    overflow-x: auto;
}

.table {
    width: 100%;
    border-collapse: collapse;
    font-size: 0.9rem;
}

.table th,
.table td {
    padding: 1rem;
    text-align: left;
    border-bottom: 1px solid rgba(255, 255, 255, 0.1);
}

.table th {
    background: rgba(51, 65, 85, 0.5);
    font-weight: 600;
    color: #f8fafc;
}

.table td {
    color: #e2e8f0;
}

.table tbody tr:hover {
    background: rgba(255, 255, 255, 0.05);
}

.user-info {
    display: flex;
    align-items: center;
    gap: 0.75rem;
}

.user-avatar {
    width: 32px;
    height: 32px;
    background: linear-gradient(135deg, #ec4899, #8b5cf6);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 0.8rem;
}

.user-details .username {
    font-weight: 500;
    color: #f8fafc;
}

.user-details .user-id {
    font-size: 0.8rem;
    color: #94a3b8;
}

.role-badge {
    padding: 0.25rem 0.75rem;
    border-radius: 20px;
    font-size: 0.8rem;
    font-weight: 500;
    display: flex;
    align-items: center;
    gap: 0.25rem;
    background: rgba(255, 255, 255, 0.1);
    color: #f8fafc;
}

.role-admin {
    background: rgba(236, 72, 153, 0.2);
    color: #f472b6;
    border: 1px solid rgba(236, 72, 153, 0.3);
}

.role-user {
    background: rgba(139, 92, 246, 0.2);
    color: #a78bfa;
    border: 1px solid rgba(139, 92, 246, 0.3);
}

.status-badge {
    padding: 0.25rem 0.75rem;
    border-radius: 20px;
    font-size: 0.8rem;
    font-weight: 500;
    display: flex;
    align-items: center;
    gap: 0.25rem;
    background: rgba(255, 255, 255, 0.1);
    color: #f8fafc;
}

.status-active {
    background: rgba(34, 197, 94, 0.2);
    color: #4ade80;
    border: 1px solid rgba(34, 197, 94, 0.3);
}

.status-inactive {
    background: rgba(239, 68, 68, 0.2);
    color: #f87171;
    border: 1px solid rgba(239, 68, 68, 0.3);
}

.stat-number {
    font-weight: 500;
    color: #f8fafc;
}

.date-info {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    color: #94a3b8;
    font-size: 0.9rem;
}

.action-buttons {
    display: flex;
    gap: 0.5rem;
    flex-wrap: wrap;
}

.btn {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.5rem 0.75rem;
    border: none;
    border-radius: 6px;
    font-size: 0.8rem;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.3s ease;
    text-decoration: none;
    background: rgba(255, 255, 255, 0.1);
    color: #f8fafc;
    border: 1px solid rgba(255, 255, 255, 0.2);
}

.btn:hover {
    background: rgba(255, 255, 255, 0.15);
    transform: translateY(-1px);
}

.btn-sm {
    padding: 0.4rem 0.6rem;
    font-size: 0.75rem;
}

.btn-primary {
    background: linear-gradient(135deg, #ec4899, #8b5cf6);
    color: white;
    border: none;
}

.btn-primary:hover {
    background: linear-gradient(135deg, #db2777, #7c3aed);
}

.btn-outline {
    background: transparent;
    color: #f8fafc;
    border: 1px solid rgba(255, 255, 255, 0.3);
}

.btn-outline:hover {
    background: rgba(255, 255, 255, 0.1);
}

.btn-success {
    background: rgba(34, 197, 94, 0.2);
    color: #4ade80;
    border: 1px solid rgba(34, 197, 94, 0.3);
}

.btn-success:hover {
    background: rgba(34, 197, 94, 0.3);
}

.btn-warning {
    background: rgba(251, 191, 36, 0.2);
    color: #fbbf24;
    border: 1px solid rgba(251, 191, 36, 0.3);
}

.btn-warning:hover {
    background: rgba(251, 191, 36, 0.3);
}

.btn-danger {
    background: rgba(239, 68, 68, 0.2);
    color: #f87171;
    border: 1px solid rgba(239, 68, 68, 0.3);
}

.btn-danger:hover {
    background: rgba(239, 68, 68, 0.3);
}

.empty-state {
    text-align: center;
    padding: 3rem 2rem;
    color: #94a3b8;
}

.empty-state i {
    font-size: 3rem;
    margin-bottom: 1rem;
    opacity: 0.5;
}

.empty-state h3 {
    color: #f8fafc;
    margin-bottom: 0.5rem;
}

.alert {
    padding: 1rem 1.5rem;
    border-radius: 8px;
    margin-bottom: 1.5rem;
    display: flex;
    align-items: center;
    gap: 0.5rem;
    background: rgba(255, 255, 255, 0.1);
    border: 1px solid rgba(255, 255, 255, 0.2);
}

.alert-success {
    background: rgba(34, 197, 94, 0.1);
    color: #4ade80;
    border-color: rgba(34, 197, 94, 0.3);
}

.alert-error {
    background: rgba(239, 68, 68, 0.1);
    color: #f87171;
    border-color: rgba(239, 68, 68, 0.3);
}

.text-muted {
    color: #94a3b8;
    font-size: 0.8rem;
}

@media (max-width: 768px) {
    .header-content {
        flex-direction: column;
        align-items: flex-start;
        gap: 1rem;
    }
    
    .quick-stats {
        flex-wrap: wrap;
        gap: 1rem;
    }
    
    .filters-row {
        flex-direction: column;
        align-items: stretch;
    }
    
    .filter-group {
        min-width: auto;
    }
    
    .action-buttons {
        flex-direction: column;
    }
    
    .table th,
    .table td {
        padding: 0.75rem 0.5rem;
        font-size: 0.8rem;
    }
}
</style>

<script>
function viewUser(userId) {
    // Load user details via AJAX
    fetch(`index.php?page=admin&section=users&action=view&user_id=${userId}`)
        .then(response => response.text())
        .then(html => {
            document.getElementById('userDetails').innerHTML = html;
            openModal('viewUserModal');
        })
        .catch(error => {
            console.error('Error loading user details:', error);
            document.getElementById('userDetails').innerHTML = '<p>Error loading user details.</p>';
            openModal('viewUserModal');
        });
}

// Handle form submissions with AJAX
document.addEventListener('DOMContentLoaded', function() {
    // Handle all user action forms
    const userForms = document.querySelectorAll('form[method="POST"]');
    userForms.forEach(form => {
        const action = form.querySelector('input[name="action"]')?.value;
        
        if (action && ['toggle_admin', 'toggle_active', 'delete_user'].includes(action)) {
            form.addEventListener('submit', function(e) {
                e.preventDefault();
                
                let confirmMessage = '';
                switch(action) {
                    case 'toggle_admin':
                        const isAdmin = form.querySelector('input[name="is_admin"]')?.value;
                        confirmMessage = isAdmin === '1' ? 
                            'Grant admin privileges to this user?' : 
                            'Remove admin privileges from this user?';
                        break;
                    case 'toggle_active':
                        const isActive = form.querySelector('input[name="is_active"]')?.value;
                        confirmMessage = isActive === '1' ? 
                            'Activate this user?' : 
                            'Deactivate this user?';
                        break;
                    case 'delete_user':
                        confirmMessage = 'Delete this user? This action cannot be undone.';
                        break;
                }
                
                if (!confirm(confirmMessage)) {
                    return;
                }
                
                const submitBtn = this.querySelector('button[type="submit"]');
                const originalText = submitBtn.innerHTML;
                
                // Show loading state
                submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
                submitBtn.disabled = true;
                
                // Get form data
                const formData = new FormData(this);
                
                // Send AJAX request
                fetch('index.php?page=admin/users', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.text())
                .then(data => {
                    // Reset button
                    submitBtn.innerHTML = originalText;
                    submitBtn.disabled = false;
                    
                    // Check if the response contains success message
                    if (data.includes('alert-success') || data.includes('successfully')) {
                        // Show success message
                        let successMessage = '';
                        switch(action) {
                            case 'toggle_admin':
                                const isAdmin = form.querySelector('input[name="is_admin"]')?.value;
                                successMessage = isAdmin === '1' ? 
                                    'Admin privileges granted successfully!' : 
                                    'Admin privileges removed successfully!';
                                break;
                            case 'toggle_active':
                                const isActive = form.querySelector('input[name="is_active"]')?.value;
                                successMessage = isActive === '1' ? 
                                    'User activated successfully!' : 
                                    'User deactivated successfully!';
                                break;
                            case 'delete_user':
                                successMessage = 'User deleted successfully!';
                                break;
                        }
                        
                        const event = new CustomEvent('showToast', {
                            detail: { message: successMessage, type: 'success' }
                        });
                        document.dispatchEvent(event);
                        
                        // Reload page
                        setTimeout(() => {
                            window.location.reload();
                        }, 1000);
                    } else {
                        // Show error message
                        const event = new CustomEvent('showToast', {
                            detail: { message: 'Failed to perform action. Please try again.', type: 'error' }
                        });
                        document.dispatchEvent(event);
                    }
                })
                .catch(error => {
                    // Reset button
                    submitBtn.innerHTML = originalText;
                    submitBtn.disabled = false;
                    
                    // Show error message
                    const event = new CustomEvent('showToast', {
                        detail: { message: 'An error occurred. Please try again.', type: 'error' }
                    });
                    document.dispatchEvent(event);
                    
                    console.error('Error:', error);
                });
            });
        }
    });
});
</script>
