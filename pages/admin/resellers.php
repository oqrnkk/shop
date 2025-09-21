<?php
// Get database connection
$conn = getDatabaseConnection();

// Handle reseller approval/rejection
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    error_log("Admin resellers POST request received");
    error_log("POST data: " . print_r($_POST, true));
    
    if (isset($_POST['action']) && isset($_POST['reseller_id'])) {
        $resellerId = (int)$_POST['reseller_id'];
        $action = $_POST['action'];
        
        error_log("Processing reseller action: $action for reseller ID: $resellerId");
        
        if ($action === 'approve' || $action === 'reject') {
            $isApproved = ($action === 'approve') ? 1 : 0;
            $result = updateResellerStatus($resellerId, $isApproved);
            
            error_log("updateResellerStatus result: " . print_r($result, true));
            
            if ($result['success']) {
                $success_message = $result['message'];
            } else {
                $error_message = $result['message'];
            }
        } else {
            error_log("Invalid action: $action");
            $error_message = "Invalid action";
        }
    } else {
        error_log("Missing action or reseller_id in POST data");
        $error_message = "Missing required data";
    }
}

// Get reseller stats
$stmt = $conn->prepare("SELECT COUNT(*) as total_resellers FROM resellers");
$stmt->execute();
$total_resellers = $stmt->fetchColumn();

$stmt = $conn->prepare("SELECT COUNT(*) as pending_resellers FROM resellers WHERE is_approved = 0");
$stmt->execute();
$pending_resellers = $stmt->fetchColumn();

$stmt = $conn->prepare("SELECT COUNT(*) as active_resellers FROM resellers WHERE is_approved = 1 AND is_active = 1");
$stmt->execute();
$active_resellers = $stmt->fetchColumn();

$stmt = $conn->prepare("SELECT SUM(total_earnings) as total_commission FROM resellers");
$stmt->execute();
$total_commission = $stmt->fetchColumn() ?: 0;

// Get resellers with pagination
$page = isset($_GET['p']) ? max(1, (int)$_GET['p']) : 1;
$per_page = 20;
$offset = ($page - 1) * $per_page;

$resellers = getAllResellers($per_page, $offset);

// Get total count for pagination
$stmt = $conn->prepare("SELECT COUNT(*) FROM resellers");
$stmt->execute();
$total_count = $stmt->fetchColumn();
$total_pages = ceil($total_count / $per_page);
?>

<div class="admin-header">
    <div class="header-content">
        <div class="header-title">
            <h1><i class="fas fa-store"></i> Reseller Management</h1>
            <p>Manage reseller accounts, approve applications, monitor performance, and commission tracking</p>
        </div>
        <div class="header-actions">
            <div class="quick-stats">
                <div class="stat-item">
                    <i class="fas fa-chart-line"></i>
                    <span>Analytics</span>
                </div>
                <div class="stat-item">
                    <i class="fas fa-download"></i>
                    <span>Export Data</span>
                </div>
                <div class="stat-item">
                    <i class="fas fa-dollar-sign"></i>
                    <span>Commission</span>
                </div>
            </div>
            <a href="#" onclick="showResellerAnalytics()" class="btn btn-primary">
                <i class="fas fa-chart-line"></i> Analytics
            </a>
        </div>
    </div>
</div>

    <?php if (isset($success_message)): ?>
        <div class="alert alert-success">
            <i class="fas fa-check-circle"></i>
            <?php echo htmlspecialchars($success_message); ?>
        </div>
    <?php endif; ?>

    <?php if (isset($error_message)): ?>
        <div class="alert alert-danger">
            <i class="fas fa-exclamation-circle"></i>
            <?php echo htmlspecialchars($error_message); ?>
        </div>
    <?php endif; ?>

    <!-- Reseller Stats -->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-icon">
                <i class="fas fa-users"></i>
            </div>
            <div class="stat-content">
                <div class="stat-number"><?php echo $total_resellers; ?></div>
                <div class="stat-label">Total Resellers</div>
            </div>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon">
                <i class="fas fa-clock"></i>
            </div>
            <div class="stat-content">
                <div class="stat-number"><?php echo $pending_resellers; ?></div>
                <div class="stat-label">Pending Approval</div>
            </div>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon">
                <i class="fas fa-check-circle"></i>
            </div>
            <div class="stat-content">
                <div class="stat-number"><?php echo $active_resellers; ?></div>
                <div class="stat-label">Active Resellers</div>
            </div>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon">
                <i class="fas fa-dollar-sign"></i>
            </div>
            <div class="stat-content">
                <div class="stat-number">$<?php echo number_format($total_commission, 2); ?></div>
                <div class="stat-label">Total Commission</div>
            </div>
        </div>
    </div>

    <!-- Resellers Table -->
    <div class="content-card">
        <div class="card-header">
            <h3>Reseller Accounts</h3>
            <div class="header-actions">
                <a href="index.php?page=admin&section=resellers&filter=pending" class="btn btn-outline btn-sm">
                    <i class="fas fa-clock"></i> Pending
                </a>
                <a href="index.php?page=admin&section=resellers&filter=active" class="btn btn-outline btn-sm">
                    <i class="fas fa-check"></i> Active
                </a>
                <a href="index.php?page=admin&section=resellers" class="btn btn-outline btn-sm">
                    <i class="fas fa-list"></i> All
                </a>
            </div>
        </div>
        
        <div class="card-content">
            <?php if (empty($resellers)): ?>
                <div class="empty-state">
                    <i class="fas fa-store"></i>
                    <p>No reseller accounts found</p>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Business Name</th>
                                <th>User</th>
                                <th>Email</th>
                                <th>Status</th>
                                <th>Products</th>
                                <th>Earnings</th>
                                <th>Joined</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($resellers as $reseller): ?>
                                <tr>
                                    <td>
                                        <div class="reseller-info">
                                            <strong><?php echo htmlspecialchars($reseller['business_name']); ?></strong>
                                            <?php if ($reseller['description']): ?>
                                                <small><?php echo htmlspecialchars(substr($reseller['description'], 0, 50)) . (strlen($reseller['description']) > 50 ? '...' : ''); ?></small>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                    <td><?php echo htmlspecialchars($reseller['username']); ?></td>
                                    <td><?php echo htmlspecialchars($reseller['email']); ?></td>
                                    <td>
                                        <?php if ($reseller['is_approved']): ?>
                                            <span class="status-badge status-active">Approved</span>
                                        <?php else: ?>
                                            <span class="status-badge status-pending">Pending</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php
                                        $stmt = $conn->prepare("SELECT COUNT(*) FROM reseller_products WHERE reseller_id = ?");
                                        $stmt->execute([$reseller['id']]);
                                        $product_count = $stmt->fetchColumn();
                                        echo $product_count;
                                        ?>
                                    </td>
                                    <td>$<?php echo number_format($reseller['total_earnings'] ?? 0, 2); ?></td>
                                    <td><?php echo date('M j, Y', strtotime($reseller['created_at'])); ?></td>
                                    <td>
                                        <div class="action-buttons">
                                            <?php if (!$reseller['is_approved']): ?>
                                                <button type="button" class="btn btn-success btn-sm" onclick="approveReseller(<?php echo $reseller['id']; ?>)">
                                                    <i class="fas fa-check"></i> Approve
                                                </button>
                                                <button type="button" class="btn btn-danger btn-sm" onclick="rejectReseller(<?php echo $reseller['id']; ?>)">
                                                    <i class="fas fa-times"></i> Reject
                                                </button>
                                            <?php else: ?>
                                                <a href="index.php?page=admin&section=reseller-details&id=<?php echo $reseller['id']; ?>" class="btn btn-primary btn-sm">
                                                    <i class="fas fa-eye"></i> View
                                                </a>
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
                    <div class="pagination">
                        <?php if ($page > 1): ?>
                            <a href="?page=admin&section=resellers&p=<?php echo $page - 1; ?>" class="page-link">
                                <i class="fas fa-chevron-left"></i> Previous
                            </a>
                        <?php endif; ?>
                        
                        <?php for ($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++): ?>
                            <a href="?page=admin&section=resellers&p=<?php echo $i; ?>" 
                               class="page-link <?php echo $i === $page ? 'active' : ''; ?>">
                                <?php echo $i; ?>
                            </a>
                        <?php endfor; ?>
                        
                        <?php if ($page < $total_pages): ?>
                            <a href="?page=admin&section=resellers&p=<?php echo $page + 1; ?>" class="page-link">
                                Next <i class="fas fa-chevron-right"></i>
                            </a>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<style>


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

.alert-danger {
    background: rgba(239, 68, 68, 0.1);
    color: #f87171;
    border-color: rgba(239, 68, 68, 0.3);
}

.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 1.5rem;
    margin-bottom: 2rem;
}

.stat-card {
    background: rgba(30, 41, 59, 0.4);
    padding: 1.5rem;
    border-radius: 12px;
    border: 1px solid rgba(255, 255, 255, 0.1);
    backdrop-filter: blur(20px);
    display: flex;
    align-items: center;
    gap: 1rem;
    transition: transform 0.3s ease, box-shadow 0.3s ease;
}

.stat-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 8px 25px rgba(0, 0, 0, 0.2);
}

.stat-icon {
    width: 50px;
    height: 50px;
    background: linear-gradient(135deg, #ec4899, #8b5cf6);
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 1.5rem;
}

.stat-number {
    font-size: 1.75rem;
    font-weight: 700;
    color: #f8fafc;
    margin-bottom: 0.25rem;
}

.stat-label {
    color: #cbd5e1;
    font-size: 0.9rem;
}

.content-card {
    background: rgba(30, 41, 59, 0.4);
    border-radius: 12px;
    border: 1px solid rgba(255, 255, 255, 0.1);
    backdrop-filter: blur(20px);
    overflow: hidden;
}

.card-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 1.5rem;
    border-bottom: 1px solid rgba(255, 255, 255, 0.1);
    background: rgba(51, 65, 85, 0.3);
}

.card-header h3 {
    font-size: 1.1rem;
    font-weight: 600;
    color: #f8fafc;
}

.header-actions {
    display: flex;
    gap: 0.5rem;
}

.card-content {
    padding: 1.5rem;
}

.empty-state {
    text-align: center;
    padding: 3rem 1rem;
    color: #94a3b8;
}

.empty-state i {
    font-size: 3rem;
    margin-bottom: 1rem;
    opacity: 0.5;
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
    padding: 0.75rem;
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

.reseller-info {
    display: flex;
    flex-direction: column;
    gap: 0.25rem;
}

.reseller-info small {
    color: #94a3b8;
    font-size: 0.8rem;
}

.status-badge {
    padding: 0.25rem 0.75rem;
    border-radius: 20px;
    font-size: 0.8rem;
    font-weight: 500;
    display: flex;
    align-items: center;
    gap: 0.25rem;
}

.status-active {
    background: rgba(34, 197, 94, 0.2);
    color: #4ade80;
    border: 1px solid rgba(34, 197, 94, 0.3);
}

.status-pending {
    background: rgba(251, 191, 36, 0.2);
    color: #fbbf24;
    border: 1px solid rgba(251, 191, 36, 0.3);
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

.btn-success {
    background: rgba(34, 197, 94, 0.2);
    color: #4ade80;
    border: 1px solid rgba(34, 197, 94, 0.3);
}

.btn-success:hover {
    background: rgba(34, 197, 94, 0.3);
}

.btn-danger {
    background: rgba(239, 68, 68, 0.2);
    color: #f87171;
    border: 1px solid rgba(239, 68, 68, 0.3);
}

.btn-danger:hover {
    background: rgba(239, 68, 68, 0.3);
}

.pagination {
    display: flex;
    justify-content: center;
    align-items: center;
    gap: 0.5rem;
    margin-top: 2rem;
}

.page-link {
    padding: 0.5rem 1rem;
    border: 1px solid rgba(255, 255, 255, 0.2);
    border-radius: 6px;
    text-decoration: none;
    color: #cbd5e1;
    transition: all 0.3s ease;
    background: rgba(255, 255, 255, 0.05);
}

.page-link:hover {
    background: rgba(255, 255, 255, 0.1);
    color: #f8fafc;
}

.page-link.active {
    background: linear-gradient(135deg, #ec4899, #8b5cf6);
    color: white;
    border-color: #ec4899;
}

@media (max-width: 768px) {
    .stats-grid {
        grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
    }
    
    .card-header {
        flex-direction: column;
        gap: 1rem;
        align-items: flex-start;
    }
    
    .header-actions {
        width: 100%;
        justify-content: center;
    }
    
    .action-buttons {
        flex-direction: column;
    }
    
    .table-responsive {
        font-size: 0.8rem;
    }
}
</style>

<script>
// Handle reseller approval/rejection with AJAX
function approveReseller(resellerId) {
    if (!confirm('Approve this reseller?')) {
        return;
    }
    
    const button = event.target.closest('button');
    const originalText = button.innerHTML;
    
    // Show loading state
    button.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Approving...';
    button.disabled = true;
    
    // Send AJAX request
    fetch('index.php?page=admin&section=resellers', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: `action=approve&reseller_id=${resellerId}`
    })
    .then(response => response.text())
    .then(text => {
        console.log('Response:', text);
        
        // Reset button
        button.innerHTML = originalText;
        button.disabled = false;
        
        // Show success message and reload page
        if (text.includes('successfully')) {
            showToast('Reseller approved successfully!', 'success');
            setTimeout(() => {
                window.location.reload();
            }, 1000);
        } else {
            showToast('Failed to approve reseller', 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        
        // Reset button
        button.innerHTML = originalText;
        button.disabled = false;
        
        showToast('An error occurred', 'error');
    });
}

function rejectReseller(resellerId) {
    if (!confirm('Reject this reseller?')) {
        return;
    }
    
    const button = event.target.closest('button');
    const originalText = button.innerHTML;
    
    // Show loading state
    button.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Rejecting...';
    button.disabled = true;
    
    // Send AJAX request
    fetch('index.php?page=admin&section=resellers', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: `action=reject&reseller_id=${resellerId}`
    })
    .then(response => response.text())
    .then(text => {
        console.log('Response:', text);
        
        // Reset button
        button.innerHTML = originalText;
        button.disabled = false;
        
        // Show success message and reload page
        if (text.includes('successfully')) {
            showToast('Reseller rejected successfully!', 'success');
            setTimeout(() => {
                window.location.reload();
            }, 1000);
        } else {
            showToast('Failed to reject reseller', 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        
        // Reset button
        button.innerHTML = originalText;
        button.disabled = false;
        
        showToast('An error occurred', 'error');
    });
}

// Simple toast function if not available
function showToast(message, type = 'info') {
    if (typeof window.showToast === 'function') {
        window.showToast(message, type);
    } else {
        alert(message);
    }
}
</script>

<script>
// Handle form submissions with AJAX
document.addEventListener('DOMContentLoaded', function() {
    // Handle reseller action forms
    const resellerForms = document.querySelectorAll('form[method="POST"]');
    resellerForms.forEach(form => {
        const action = form.querySelector('input[name="action"]')?.value;
        
        if (action && ['approve', 'reject'].includes(action)) {
            form.addEventListener('submit', function(e) {
                e.preventDefault();
                
                let confirmMessage = '';
                switch(action) {
                    case 'approve':
                        confirmMessage = 'Approve this reseller?';
                        break;
                    case 'reject':
                        confirmMessage = 'Reject this reseller?';
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
                fetch('index.php?page=admin/resellers', {
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
                            case 'approve':
                                successMessage = 'Reseller approved successfully!';
                                break;
                            case 'reject':
                                successMessage = 'Reseller rejected successfully!';
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
