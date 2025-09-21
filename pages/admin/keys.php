<?php
// License key management page
$user = getCurrentUser();

if (!$user) {
    header('Location: index.php?page=login');
    exit();
}

// Check if user is admin or reseller
if (!isAdmin() && !isReseller()) {
    header('Location: index.php?page=dashboard');
    exit();
}

$error = '';
$success = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'add_keys') {
        $productId = (int)($_POST['product_id'] ?? 0);
        $duration = $_POST['duration'] ?? '';
        $keyType = $_POST['key_type'] ?? 'generate';
        $quantity = (int)($_POST['quantity'] ?? 1);
        
        if ($productId <= 0 || empty($duration) || $quantity <= 0) {
            $error = 'Please fill in all required fields.';
        } else {
            $keys = [];
            
            if ($keyType === 'generate') {
                // Generate random keys
                for ($i = 0; $i < $quantity; $i++) {
                    $keys[] = generateLicenseKey();
                }
            } else {
                // Manual keys
                $manualKeys = trim($_POST['manual_keys'] ?? '');
                if (empty($manualKeys)) {
                    $error = 'Please enter license keys.';
                } else {
                    $keys = array_filter(array_map('trim', explode("\n", $manualKeys)));
                }
            }
            
            if (empty($error) && !empty($keys)) {
                $result = addLicenseKeys($productId, $duration, $keys, $user['id']);
                if ($result['success']) {
                    $success = $result['message'];
                } else {
                    $error = $result['message'];
                }
            }
        }
    } elseif ($action === 'delete_keys') {
        $keyIds = $_POST['key_ids'] ?? [];
        if (!empty($keyIds)) {
            $result = deleteLicenseKeys($keyIds);
            if ($result['success']) {
                $success = $result['message'];
            } else {
                $error = $result['message'];
            }
        } else {
            $error = 'Please select keys to delete.';
        }
    }
}

// Get filters
$filters = [
    'product_id' => $_GET['product_id'] ?? '',
    'duration' => $_GET['duration'] ?? ''
];

// Get data
$products = getAllProducts();
$availableKeys = getAllAvailableKeys($filters);
$usedKeys = getUsedKeys($filters);
$keyStats = getKeyStatistics();
?>

<div class="admin-header">
    <div class="header-content">
        <div class="header-title">
            <h1><i class="fas fa-key"></i> License Key Management</h1>
            <p>Add, view, and manage license keys for all products with advanced security features</p>
        </div>
        <div class="header-actions">
            <div class="quick-stats">
                <div class="stat-item">
                    <i class="fas fa-plus"></i>
                    <span>Add Keys</span>
                </div>
                <div class="stat-item">
                    <i class="fas fa-download"></i>
                    <span>Export Data</span>
                </div>
                <div class="stat-item">
                    <i class="fas fa-shield-alt"></i>
                    <span>Security</span>
                </div>
            </div>
            <a href="#" onclick="showAddKeysModal()" class="btn btn-primary">
                <i class="fas fa-plus"></i> Add Keys
            </a>
        </div>
    </div>
</div>

    <?php if ($error): ?>
        <div class="alert alert-error">
            <i class="fas fa-exclamation-circle"></i>
            <?php echo htmlspecialchars($error); ?>
        </div>
    <?php endif; ?>

    <?php if ($success): ?>
        <div class="alert alert-success">
            <i class="fas fa-check-circle"></i>
            <?php echo htmlspecialchars($success); ?>
        </div>
    <?php endif; ?>

    <!-- Key Statistics -->
    <div class="stats-section">
        <h3><i class="fas fa-chart-bar"></i> Key Statistics</h3>
        <div class="stats-grid">
            <?php foreach ($keyStats as $stat): ?>
                <div class="stat-card">
                    <div class="stat-header">
                        <h4><?php echo htmlspecialchars($stat['product_name']); ?></h4>
                        <span class="duration-badge"><?php echo str_replace('_', ' ', $stat['duration']); ?></span>
                    </div>
                    <div class="stat-numbers">
                        <div class="stat-item">
                            <span class="stat-label">Available:</span>
                            <span class="stat-value available"><?php echo $stat['available']; ?></span>
                        </div>
                        <div class="stat-item">
                            <span class="stat-label">Used:</span>
                            <span class="stat-value used"><?php echo $stat['used']; ?></span>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Add Keys Section -->
    <div class="add-keys-section">
        <h3><i class="fas fa-plus"></i> Add License Keys</h3>
        <form method="POST" class="add-keys-form">
            <input type="hidden" name="action" value="add_keys">
            
            <div class="form-row">
                <div class="form-group">
                    <label for="product_id">Product</label>
                    <select id="product_id" name="product_id" required>
                        <option value="">Select Product</option>
                        <?php foreach ($products as $product): ?>
                            <option value="<?php echo $product['id']; ?>">
                                <?php echo htmlspecialchars($product['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label for="duration">Duration</label>
                    <select id="duration" name="duration" required>
                        <option value="">Select Duration</option>
                        <option value="1_day">1 Day</option>
                        <option value="1_week">1 Week</option>
                        <option value="1_month">1 Month</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="key_type">Key Type</label>
                    <select id="key_type" name="key_type" required>
                        <option value="generate">Generate Random Keys</option>
                        <option value="manual">Manual Keys</option>
                    </select>
                </div>
                
                <div class="form-group" id="quantity-group">
                    <label for="quantity">Quantity</label>
                    <input type="number" id="quantity" name="quantity" min="1" max="100" value="1" required>
                </div>
            </div>

            <div class="form-group" id="manual-keys-group" style="display: none;">
                <label for="manual_keys">License Keys (one per line)</label>
                <textarea id="manual_keys" name="manual_keys" rows="5" placeholder="Enter license keys, one per line"></textarea>
            </div>

            <button type="submit" class="btn btn-primary">
                <i class="fas fa-plus"></i>
                Add Keys
            </button>
        </form>
    </div>

    <!-- Available Keys Section -->
    <div class="keys-section">
        <div class="section-header">
            <h3><i class="fas fa-key"></i> Available Keys</h3>
            <div class="filters">
                <select id="filter-product" onchange="applyFilters()">
                    <option value="">All Products</option>
                    <?php foreach ($products as $product): ?>
                        <option value="<?php echo $product['id']; ?>" <?php echo $filters['product_id'] == $product['id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($product['name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <select id="filter-duration" onchange="applyFilters()">
                    <option value="">All Durations</option>
                    <option value="1_day" <?php echo $filters['duration'] === '1_day' ? 'selected' : ''; ?>>1 Day</option>
                    <option value="1_week" <?php echo $filters['duration'] === '1_week' ? 'selected' : ''; ?>>1 Week</option>
                    <option value="1_month" <?php echo $filters['duration'] === '1_month' ? 'selected' : ''; ?>>1 Month</option>
                </select>
            </div>
        </div>

        <?php if (empty($availableKeys)): ?>
            <div class="empty-state">
                <i class="fas fa-key"></i>
                <h4>No available keys found</h4>
                <p>Add some license keys to get started</p>
            </div>
        <?php else: ?>
            <form method="POST" id="delete-keys-form">
                <input type="hidden" name="action" value="delete_keys">
                <div class="keys-table-container">
                    <table class="keys-table">
                        <thead>
                            <tr>
                                <th><input type="checkbox" id="select-all" onchange="toggleSelectAll()"></th>
                                <th>Product</th>
                                <th>License Key</th>
                                <th>Duration</th>
                                <th>Added By</th>
                                <th>Date Added</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($availableKeys as $key): ?>
                                <tr>
                                    <td>
                                        <input type="checkbox" name="key_ids[]" value="<?php echo $key['id']; ?>" class="key-checkbox">
                                    </td>
                                    <td>
                                        <div class="product-info">
                                            <div class="product-icon">
                                                <i class="fas fa-gamepad"></i>
                                            </div>
                                            <span><?php echo htmlspecialchars($key['product_name']); ?></span>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="key-info">
                                            <code class="license-key"><?php echo htmlspecialchars($key['license_key']); ?></code>
                                            <button type="button" class="copy-btn" onclick="copyToClipboard('<?php echo $key['license_key']; ?>')" title="Copy Key">
                                                <i class="fas fa-copy"></i>
                                            </button>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="duration-badge"><?php echo str_replace('_', ' ', $key['duration']); ?></span>
                                    </td>
                                    <td><?php echo htmlspecialchars($key['added_by_username']); ?></td>
                                    <td>
                                        <div class="date-info">
                                            <i class="fas fa-calendar"></i>
                                            <?php echo date('M j, Y', strtotime($key['created_at'])); ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <button type="submit" class="btn btn-danger" onclick="return confirm('Are you sure you want to delete the selected keys?')">
                    <i class="fas fa-trash"></i>
                    Delete Selected
                </button>
            </form>
        <?php endif; ?>
    </div>

    <!-- Used Keys Section -->
    <div class="keys-section">
        <h3><i class="fas fa-history"></i> Used Keys</h3>
        <?php if (empty($usedKeys)): ?>
            <div class="empty-state">
                <i class="fas fa-history"></i>
                <h4>No used keys found</h4>
                <p>Used keys will appear here once customers purchase products</p>
            </div>
        <?php else: ?>
            <div class="keys-table-container">
                <table class="keys-table">
                    <thead>
                        <tr>
                            <th>Product</th>
                            <th>License Key</th>
                            <th>Duration</th>
                            <th>Added By</th>
                            <th>Used By</th>
                            <th>Date Used</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($usedKeys as $key): ?>
                            <tr>
                                <td>
                                    <div class="product-info">
                                        <div class="product-icon">
                                            <i class="fas fa-gamepad"></i>
                                        </div>
                                        <span><?php echo htmlspecialchars($key['product_name']); ?></span>
                                    </div>
                                </td>
                                <td>
                                    <code class="license-key"><?php echo htmlspecialchars($key['license_key']); ?></code>
                                </td>
                                <td>
                                    <span class="duration-badge"><?php echo str_replace('_', ' ', $key['duration']); ?></span>
                                </td>
                                <td><?php echo htmlspecialchars($key['added_by_username']); ?></td>
                                <td><?php echo htmlspecialchars($key['buyer_username'] ?? 'N/A'); ?></td>
                                <td>
                                    <div class="date-info">
                                        <i class="fas fa-calendar"></i>
                                        <?php echo date('M j, Y', strtotime($key['used_at'])); ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<style>


.stats-section {
    background: rgba(30, 41, 59, 0.4);
    border-radius: 12px;
    padding: 2rem;
    margin-bottom: 2rem;
    border: 1px solid rgba(255, 255, 255, 0.1);
}

.stats-section h3 {
    color: #f8fafc;
    margin-bottom: 1.5rem;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 1.5rem;
}

.stat-card {
    background: rgba(51, 65, 85, 0.3);
    border: 1px solid rgba(255, 255, 255, 0.1);
    border-radius: 12px;
    padding: 1.5rem;
    transition: transform 0.3s ease, box-shadow 0.3s ease;
}

.stat-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 25px rgba(0, 0, 0, 0.2);
}

.stat-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1rem;
}

.stat-header h4 {
    color: #f8fafc;
    margin: 0;
    font-size: 1.125rem;
}

.duration-badge {
    background: linear-gradient(135deg, #ec4899, #8b5cf6);
    color: white;
    padding: 0.25rem 0.75rem;
    border-radius: 20px;
    font-size: 0.875rem;
    font-weight: 600;
}

.stat-numbers {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 1rem;
}

.stat-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.stat-label {
    color: #cbd5e1;
    font-size: 0.875rem;
}

.stat-value {
    font-weight: 700;
    font-size: 1.25rem;
}

.stat-value.available {
    color: #4ade80;
}

.stat-value.used {
    color: #f87171;
}

.add-keys-section {
    background: rgba(30, 41, 59, 0.4);
    border-radius: 12px;
    padding: 2rem;
    margin-bottom: 2rem;
    border: 1px solid rgba(255, 255, 255, 0.1);
}

.add-keys-section h3 {
    color: #f8fafc;
    margin-bottom: 1.5rem;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.add-keys-form {
    display: grid;
    gap: 1.5rem;
}

.form-row {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 1rem;
}

.form-group {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
}

.form-group label {
    font-weight: 600;
    color: #f8fafc;
}

.form-group select,
.form-group input,
.form-group textarea {
    padding: 0.75rem;
    background: rgba(51, 65, 85, 0.5);
    border: 1px solid rgba(255, 255, 255, 0.2);
    border-radius: 8px;
    color: #f8fafc;
    font-size: 1rem;
    transition: all 0.3s ease;
}

.form-group select:focus,
.form-group input:focus,
.form-group textarea:focus {
    outline: none;
    border-color: #ec4899;
    box-shadow: 0 0 0 3px rgba(236, 72, 153, 0.1);
}

.form-group select::placeholder,
.form-group input::placeholder,
.form-group textarea::placeholder {
    color: #94a3b8;
}

.keys-section {
    background: rgba(30, 41, 59, 0.4);
    border-radius: 12px;
    padding: 2rem;
    margin-bottom: 2rem;
    border: 1px solid rgba(255, 255, 255, 0.1);
}

.keys-section h3 {
    color: #f8fafc;
    margin-bottom: 1.5rem;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.keys-section .section-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1.5rem;
    flex-wrap: wrap;
    gap: 1rem;
}

.filters {
    display: flex;
    gap: 1rem;
}

.filters select {
    padding: 0.5rem;
    background: rgba(51, 65, 85, 0.5);
    border: 1px solid rgba(255, 255, 255, 0.2);
    border-radius: 8px;
    color: #f8fafc;
    font-size: 0.875rem;
}

.keys-table-container {
    overflow-x: auto;
    margin-bottom: 1rem;
}

.keys-table {
    width: 100%;
    border-collapse: collapse;
}

.keys-table th,
.keys-table td {
    padding: 1rem;
    text-align: left;
    border-bottom: 1px solid rgba(255, 255, 255, 0.1);
}

.keys-table th {
    background: rgba(51, 65, 85, 0.5);
    font-weight: 600;
    color: #f8fafc;
}

.keys-table td {
    color: #e2e8f0;
}

.keys-table tbody tr:hover {
    background: rgba(255, 255, 255, 0.05);
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

.key-info {
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.license-key {
    background: rgba(51, 65, 85, 0.5);
    padding: 0.25rem 0.5rem;
    border-radius: 4px;
    font-family: monospace;
    font-size: 0.875rem;
    color: #f8fafc;
    border: 1px solid rgba(255, 255, 255, 0.1);
}

.copy-btn {
    background: rgba(255, 255, 255, 0.1);
    border: 1px solid rgba(255, 255, 255, 0.2);
    color: #f8fafc;
    cursor: pointer;
    padding: 0.25rem;
    border-radius: 4px;
    transition: all 0.3s ease;
}

.copy-btn:hover {
    background: rgba(255, 255, 255, 0.2);
    color: #ec4899;
}

.date-info {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    color: #94a3b8;
    font-size: 0.9rem;
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

.empty-state h4 {
    color: #f8fafc;
    margin-bottom: 0.5rem;
}

.btn {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.75rem 1.5rem;
    border: none;
    border-radius: 8px;
    font-size: 1rem;
    font-weight: 600;
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

.btn-primary {
    background: linear-gradient(135deg, #ec4899, #8b5cf6);
    color: white;
    border: none;
}

.btn-primary:hover {
    background: linear-gradient(135deg, #db2777, #7c3aed);
}

.btn-danger {
    background: rgba(239, 68, 68, 0.2);
    color: #f87171;
    border: 1px solid rgba(239, 68, 68, 0.3);
}

.btn-danger:hover {
    background: rgba(239, 68, 68, 0.3);
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

.alert-error {
    background: rgba(239, 68, 68, 0.1);
    color: #f87171;
    border-color: rgba(239, 68, 68, 0.3);
}

.alert-success {
    background: rgba(34, 197, 94, 0.1);
    color: #4ade80;
    border-color: rgba(34, 197, 94, 0.3);
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
    
    .form-row {
        grid-template-columns: 1fr;
    }
    
    .keys-section .section-header {
        flex-direction: column;
        align-items: stretch;
    }
    
    .filters {
        flex-direction: column;
    }
    
    .keys-table th,
    .keys-table td {
        padding: 0.75rem 0.5rem;
        font-size: 0.875rem;
    }
}
</style>

<script>
function toggleKeyType() {
    const keyType = document.getElementById('key_type').value;
    const quantityGroup = document.getElementById('quantity-group');
    const manualKeysGroup = document.getElementById('manual-keys-group');
    
    if (keyType === 'generate') {
        quantityGroup.style.display = 'block';
        manualKeysGroup.style.display = 'none';
    } else {
        quantityGroup.style.display = 'none';
        manualKeysGroup.style.display = 'block';
    }
}

function toggleSelectAll() {
    const selectAll = document.getElementById('select-all');
    const checkboxes = document.querySelectorAll('.key-checkbox');
    
    checkboxes.forEach(checkbox => {
        checkbox.checked = selectAll.checked;
    });
}

function copyToClipboard(text) {
    navigator.clipboard.writeText(text).then(() => {
        // Show feedback
        const event = new CustomEvent('showToast', {
            detail: { message: 'License key copied to clipboard!', type: 'success' }
        });
        document.dispatchEvent(event);
    });
}

function applyFilters() {
    const productId = document.getElementById('filter-product').value;
    const duration = document.getElementById('filter-duration').value;
    
    const params = new URLSearchParams();
    if (productId) params.append('product_id', productId);
    if (duration) params.append('duration', duration);
    
    window.location.href = 'index.php?page=admin&section=keys&' + params.toString();
}

// Initialize
document.addEventListener('DOMContentLoaded', function() {
    // Prevent automatic loading states from modern.js for this form
    const addKeysFormElement = document.querySelector('.add-keys-form');
    if (addKeysFormElement) {
        const submitBtn = addKeysFormElement.querySelector('button[type="submit"]');
        if (submitBtn) {
            // Remove any existing click event listeners that might add loading state
            const newBtn = submitBtn.cloneNode(true);
            submitBtn.parentNode.replaceChild(newBtn, submitBtn);
        }
    }
    
    document.getElementById('key_type').addEventListener('change', toggleKeyType);
    
    // Handle add keys form submission
    const addKeysForm = document.querySelector('.add-keys-form');
    if (addKeysForm) {
        addKeysForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const submitBtn = this.querySelector('button[type="submit"]');
            const originalText = submitBtn.innerHTML;
            
            // Show loading state
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Loading...';
            submitBtn.disabled = true;
            
            // Get form data
            const formData = new FormData(this);
            
            // Send AJAX request
            fetch('index.php?page=admin&section=keys', {
                method: 'POST',
                body: formData
            })
            .then(response => response.text())
            .then(data => {
                // Reset button
                submitBtn.innerHTML = originalText;
                submitBtn.disabled = false;
                
                // Check if the response contains success message
                if (data.includes('alert-success') || data.includes('Keys added successfully')) {
                    // Show success message
                    const event = new CustomEvent('showToast', {
                        detail: { message: 'License keys added successfully!', type: 'success' }
                    });
                    document.dispatchEvent(event);
                    
                    // Reload the page to show new keys
                    setTimeout(() => {
                        window.location.reload();
                    }, 1000);
                } else {
                    // Show error message
                    const event = new CustomEvent('showToast', {
                        detail: { message: 'Failed to add license keys. Please try again.', type: 'error' }
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
    
    // Handle delete keys form submission
    const deleteKeysForm = document.getElementById('delete-keys-form');
    if (deleteKeysForm) {
        // Prevent automatic loading states from modern.js for this form
        const submitBtn = deleteKeysForm.querySelector('button[type="submit"]');
        if (submitBtn) {
            // Remove any existing click event listeners that might add loading state
            const newBtn = submitBtn.cloneNode(true);
            submitBtn.parentNode.replaceChild(newBtn, submitBtn);
        }
        
        deleteKeysForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const selectedKeys = document.querySelectorAll('.key-checkbox:checked');
            if (selectedKeys.length === 0) {
                const event = new CustomEvent('showToast', {
                    detail: { message: 'Please select keys to delete.', type: 'error' }
                });
                document.dispatchEvent(event);
                return;
            }
            
            if (!confirm('Are you sure you want to delete the selected keys?')) {
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
            fetch('index.php?page=admin&section=keys', {
                method: 'POST',
                body: formData
            })
            .then(response => response.text())
            .then(data => {
                // Reset button
                submitBtn.innerHTML = originalText;
                submitBtn.disabled = false;
                
                // Check if the response contains success message
                if (data.includes('alert-success') || data.includes('Keys deleted successfully')) {
                    // Show success message
                    const event = new CustomEvent('showToast', {
                        detail: { message: 'License keys deleted successfully!', type: 'success' }
                    });
                    document.dispatchEvent(event);
                    
                    // Reload the page to show updated keys
                    setTimeout(() => {
                        window.location.reload();
                    }, 1000);
                } else {
                    // Show error message
                    const event = new CustomEvent('showToast', {
                        detail: { message: 'Failed to delete license keys. Please try again.', type: 'error' }
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
</script>
