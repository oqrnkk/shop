<?php
// Get database connection
$conn = getDatabaseConnection();

// Handle order actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'update_status') {
        $order_id = intval($_POST['order_id']);
        $status = $_POST['status'];
        
        $stmt = $conn->prepare("UPDATE orders SET status = ?, updated_at = NOW() WHERE id = ?");
        if ($stmt->execute([$status, $order_id])) {
            $success = "Order status updated successfully!";
        } else {
            $error = "Failed to update order status.";
        }
    } elseif ($_POST['action'] === 'delete_order') {
        $order_id = intval($_POST['order_id']);
        
        $stmt = $conn->prepare("DELETE FROM orders WHERE id = ?");
        if ($stmt->execute([$order_id])) {
            $success = "Order deleted successfully!";
        } else {
            $error = "Failed to delete order.";
        }
    }
}

// Get all orders with pagination
$page = max(1, intval($_GET['page'] ?? 1));
$per_page = 20;
$offset = ($page - 1) * $per_page;

$search = $_GET['search'] ?? '';
$status_filter = $_GET['status'] ?? '';

// Build query
$where_conditions = ['1=1'];
$params = [];

if ($search) {
    $where_conditions[] = '(o.id LIKE ? OR p.name LIKE ? OR u.username LIKE ? OR u.email LIKE ?)';
    $search_term = '%' . $search . '%';
    $params[] = $search_term;
    $params[] = $search_term;
    $params[] = $search_term;
    $params[] = $search_term;
}

if ($status_filter) {
    $where_conditions[] = 'o.status = ?';
    $params[] = $status_filter;
}

$where_clause = implode(' AND ', $where_conditions);

// Get total count
$count_sql = "
    SELECT COUNT(*) 
    FROM orders o
    JOIN products p ON o.product_id = p.id
    JOIN users u ON o.user_id = u.id
    WHERE $where_clause
";
$stmt = $conn->prepare($count_sql);
$stmt->execute($params);
$total_orders = $stmt->fetchColumn();

$total_pages = ceil($total_orders / $per_page);

// Get orders
$sql = "
    SELECT o.*, p.name as product_name, p.slug as product_slug, u.username as user_name, u.email as user_email
    FROM orders o
    JOIN products p ON o.product_id = p.id
    JOIN users u ON o.user_id = u.id
    WHERE $where_clause
    ORDER BY o.created_at DESC
    LIMIT $per_page OFFSET $offset
";

$stmt = $conn->prepare($sql);
$stmt->execute($params);
$orders = $stmt->fetchAll();
?>

<div class="admin-header">
    <div class="header-content">
        <div class="header-title">
            <h1><i class="fas fa-shopping-cart"></i> Orders Management</h1>
            <p>View and manage all customer orders, payments, deliveries, and transaction history</p>
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
                    <span>Revenue</span>
                </div>
            </div>
            <a href="#" onclick="showOrderAnalytics()" class="btn btn-primary">
                <i class="fas fa-chart-line"></i> Analytics
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
            <input type="hidden" name="section" value="orders">
            
            <div class="filters-row">
                <div class="filter-group">
                    <div class="input-wrapper">
                        <i class="fas fa-search input-icon"></i>
                        <input
                            type="text"
                            name="search"
                            class="form-input"
                            placeholder="Search orders, products, or users..."
                            value="<?php echo htmlspecialchars($search); ?>"
                        >
                    </div>
                </div>
                
                <div class="filter-group">
                    <select name="status" class="form-input">
                        <option value="">All Status</option>
                        <option value="pending" <?php echo $status_filter === 'pending' ? 'selected' : ''; ?>>Pending</option>
                        <option value="paid" <?php echo $status_filter === 'paid' ? 'selected' : ''; ?>>Paid</option>
                        <option value="failed" <?php echo $status_filter === 'failed' ? 'selected' : ''; ?>>Failed</option>
                        <option value="cancelled" <?php echo $status_filter === 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                        <option value="refunded" <?php echo $status_filter === 'refunded' ? 'selected' : ''; ?>>Refunded</option>
                    </select>
                </div>
                
                <div class="filter-group">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-search"></i>
                        Filter
                    </button>
                    <a href="index.php?page=admin&section=orders" class="btn btn-outline">
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
                <i class="fas fa-shopping-cart"></i>
                <span>Showing <?php echo $total_orders; ?> order<?php echo $total_orders !== 1 ? 's' : ''; ?></span>
            </div>
            <?php if ($search || $status_filter): ?>
                <div class="stat-item">
                    <i class="fas fa-filter"></i>
                    <span>Filtered results</span>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Orders Table -->
    <div class="table-container">
        <?php if (empty($orders)): ?>
            <div class="empty-state">
                <i class="fas fa-shopping-cart"></i>
                <h3>No orders found</h3>
                <p>Orders will appear here once customers make purchases</p>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Order ID</th>
                            <th>Product</th>
                            <th>Customer</th>
                            <th>Duration</th>
                            <th>Amount</th>
                            <th>Status</th>
                            <th>Payment Method</th>
                            <th>Date</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($orders as $order): ?>
                            <tr>
                                <td>
                                    <div class="order-id">
                                        <i class="fas fa-hashtag"></i>
                                        <strong>#<?php echo $order['id']; ?></strong>
                                    </div>
                                </td>
                                <td>
                                    <div class="product-info">
                                        <div class="product-icon">
                                            <i class="fas fa-gamepad"></i>
                                        </div>
                                        <div class="product-details">
                                            <a href="index.php?page=product&slug=<?php echo $order['product_slug']; ?>" class="link">
                                                <?php echo htmlspecialchars($order['product_name']); ?>
                                            </a>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <div class="customer-info">
                                        <div class="customer-avatar">
                                            <i class="fas fa-user"></i>
                                        </div>
                                        <div class="customer-details">
                                            <div class="username"><?php echo htmlspecialchars($order['user_name']); ?></div>
                                            <div class="email"><?php echo htmlspecialchars($order['user_email']); ?></div>
                                        </div>
                                    </div>
                                </td>
                                <td><?php echo htmlspecialchars($order['duration']); ?></td>
                                <td>
                                    <strong>$<?php echo number_format($order['total_price'], 2); ?></strong>
                                </td>
                                <td>
                                    <span class="status-badge status-<?php echo $order['status']; ?>">
                                        <i class="fas fa-circle"></i>
                                        <?php echo ucfirst($order['status']); ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if ($order['payment_method']): ?>
                                        <span class="payment-method">
                                            <i class="fas fa-credit-card"></i>
                                            <?php echo htmlspecialchars($order['payment_method']); ?>
                                        </span>
                                    <?php else: ?>
                                        <span class="text-muted">N/A</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="date-info">
                                        <i class="fas fa-calendar"></i>
                                        <?php echo date('M j, Y H:i', strtotime($order['created_at'])); ?>
                                    </div>
                                </td>
                                <td>
                                    <div class="action-buttons">
                                        <button class="btn btn-sm btn-outline" onclick="viewOrder(<?php echo $order['id']; ?>)" title="View Order">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                        <button class="btn btn-sm btn-outline" onclick="updateStatus(<?php echo $order['id']; ?>, '<?php echo $order['status']; ?>')" title="Update Status">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <form method="POST" style="display: inline;">
                                            <input type="hidden" name="action" value="delete_order">
                                            <input type="hidden" name="order_id" value="<?php echo $order['id']; ?>">
                                            <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Delete this order? This action cannot be undone.')" title="Delete Order">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </form>
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
                    $current_url = 'index.php?page=admin&section=orders';
                    if ($search) $current_url .= '&search=' . urlencode($search);
                    if ($status_filter) $current_url .= '&status=' . urlencode($status_filter);
                    
                    echo getPagination($total_orders, $per_page, $page, $current_url);
                    ?>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</div>

<!-- Update Status Modal -->
<div id="updateStatusModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3><i class="fas fa-edit"></i> Update Order Status</h3>
            <button class="modal-close" onclick="closeModal('updateStatusModal')">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <form method="POST" class="modal-body">
            <input type="hidden" name="action" value="update_status">
            <input type="hidden" id="update_order_id" name="order_id">
            
            <div class="form-group">
                <label for="status">Order Status</label>
                <select id="status" name="status" class="form-input" required>
                    <option value="pending">Pending</option>
                    <option value="paid">Paid</option>
                    <option value="failed">Failed</option>
                    <option value="cancelled">Cancelled</option>
                    <option value="refunded">Refunded</option>
                </select>
            </div>
            
            <div class="modal-actions">
                <button type="button" class="btn btn-outline" onclick="closeModal('updateStatusModal')">Cancel</button>
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i>
                    Update Status
                </button>
            </div>
        </form>
    </div>
</div>

<!-- View Order Modal -->
<div id="viewOrderModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3><i class="fas fa-eye"></i> Order Details</h3>
            <button class="modal-close" onclick="closeModal('viewOrderModal')">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <div class="modal-body" id="orderDetails">
            <!-- Order details will be loaded here -->
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

.order-id {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    color: #f8fafc;
}

.product-info {
    display: flex;
    align-items: center;
    gap: 0.75rem;
}

.product-icon {
    width: 32px;
    height: 32px;
    background: linear-gradient(135deg, #ec4899, #8b5cf6);
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 0.8rem;
}

.product-details .link {
    color: #f8fafc;
    text-decoration: none;
    transition: color 0.3s ease;
}

.product-details .link:hover {
    color: #ec4899;
}

.customer-info {
    display: flex;
    align-items: center;
    gap: 0.75rem;
}

.customer-avatar {
    width: 32px;
    height: 32px;
    background: linear-gradient(135deg, #10b981, #3b82f6);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 0.8rem;
}

.customer-details .username {
    font-weight: 500;
    color: #f8fafc;
}

.customer-details .email {
    font-size: 0.8rem;
    color: #94a3b8;
}

.payment-method {
    background: rgba(51, 65, 85, 0.5);
    color: #f8fafc;
    padding: 0.25rem 0.5rem;
    border-radius: 4px;
    font-size: 0.8rem;
    display: flex;
    align-items: center;
    gap: 0.25rem;
    border: 1px solid rgba(255, 255, 255, 0.1);
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

.btn-danger {
    background: rgba(239, 68, 68, 0.2);
    color: #f87171;
    border: 1px solid rgba(239, 68, 68, 0.3);
}

.btn-danger:hover {
    background: rgba(239, 68, 68, 0.3);
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

.status-pending {
    background: rgba(251, 191, 36, 0.2);
    color: #fbbf24;
    border: 1px solid rgba(251, 191, 36, 0.3);
}

.status-paid {
    background: rgba(34, 197, 94, 0.2);
    color: #4ade80;
    border: 1px solid rgba(34, 197, 94, 0.3);
}

.status-failed {
    background: rgba(239, 68, 68, 0.2);
    color: #f87171;
    border: 1px solid rgba(239, 68, 68, 0.3);
}

.status-cancelled {
    background: rgba(239, 68, 68, 0.2);
    color: #f87171;
    border: 1px solid rgba(239, 68, 68, 0.3);
}

.status-refunded {
    background: rgba(59, 130, 246, 0.2);
    color: #60a5fa;
    border: 1px solid rgba(59, 130, 246, 0.3);
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
function updateStatus(orderId, currentStatus) {
    document.getElementById('update_order_id').value = orderId;
    document.getElementById('status').value = currentStatus;
    openModal('updateStatusModal');
}

function updateStatus(orderId, currentStatus) {
    document.getElementById('update_order_id').value = orderId;
    document.getElementById('status').value = currentStatus;
    openModal('updateStatusModal');
}

function viewOrder(orderId) {
    // Load order details via AJAX
    fetch(`index.php?page=admin&section=orders&action=view&order_id=${orderId}`)
        .then(response => response.text())
        .then(html => {
            document.getElementById('orderDetails').innerHTML = html;
            openModal('viewOrderModal');
        })
        .catch(error => {
            console.error('Error loading order details:', error);
            document.getElementById('orderDetails').innerHTML = '<p>Error loading order details.</p>';
            openModal('viewOrderModal');
        });
}

// Handle form submissions with AJAX
document.addEventListener('DOMContentLoaded', function() {
    // Handle update status form
    const updateStatusForm = document.querySelector('#updateStatusModal form');
    if (updateStatusForm) {
        updateStatusForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const submitBtn = this.querySelector('button[type="submit"]');
            const originalText = submitBtn.innerHTML;
            
            // Show loading state
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Updating...';
            submitBtn.disabled = true;
            
            // Get form data
            const formData = new FormData(this);
            
            // Send AJAX request
            fetch('index.php?page=admin/orders', {
                method: 'POST',
                body: formData
            })
            .then(response => response.text())
            .then(data => {
                // Reset button
                submitBtn.innerHTML = originalText;
                submitBtn.disabled = false;
                
                // Check if the response contains success message
                if (data.includes('alert-success') || data.includes('Status updated successfully')) {
                    // Show success message
                    const event = new CustomEvent('showToast', {
                        detail: { message: 'Order status updated successfully!', type: 'success' }
                    });
                    document.dispatchEvent(event);
                    
                    // Close modal and reload page
                    closeModal('updateStatusModal');
                    setTimeout(() => {
                        window.location.reload();
                    }, 1000);
                } else {
                    // Show error message
                    const event = new CustomEvent('showToast', {
                        detail: { message: 'Failed to update order status. Please try again.', type: 'error' }
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
    
    // Handle delete order forms
    const deleteForms = document.querySelectorAll('form[method="POST"]');
    deleteForms.forEach(form => {
        if (form.querySelector('input[name="action"][value="delete_order"]')) {
            form.addEventListener('submit', function(e) {
                e.preventDefault();
                
                if (!confirm('Are you sure you want to delete this order?')) {
                    return;
                }
                
                const submitBtn = this.querySelector('button[type="submit"]');
                const originalText = submitBtn.innerHTML;
                
                // Show loading state
                submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Deleting...';
                submitBtn.disabled = true;
                
                // Get form data
                const formData = new FormData(this);
                
                // Send AJAX request
                fetch('index.php?page=admin/orders', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.text())
                .then(data => {
                    // Reset button
                    submitBtn.innerHTML = originalText;
                    submitBtn.disabled = false;
                    
                    // Check if the response contains success message
                    if (data.includes('alert-success') || data.includes('Order deleted successfully')) {
                        // Show success message
                        const event = new CustomEvent('showToast', {
                            detail: { message: 'Order deleted successfully!', type: 'success' }
                        });
                        document.dispatchEvent(event);
                        
                        // Reload page
                        setTimeout(() => {
                            window.location.reload();
                        }, 1000);
                    } else {
                        // Show error message
                        const event = new CustomEvent('showToast', {
                            detail: { message: 'Failed to delete order. Please try again.', type: 'error' }
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
