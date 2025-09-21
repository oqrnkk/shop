<?php
// Get database connection
$conn = getDatabaseConnection();

// Handle settings update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'update_settings') {
        $settings = [
            'site_name' => sanitizeInput($_POST['site_name']),
            'site_url' => sanitizeInput($_POST['site_url']),
            'support_email' => sanitizeInput($_POST['support_email']),
            'discord_invite' => sanitizeInput($_POST['discord_invite']),
            'maintenance_mode' => isset($_POST['maintenance_mode']) ? 'true' : 'false',
            'registration_enabled' => isset($_POST['registration_enabled']) ? 'true' : 'false'
        ];
        
        $success = true;
        foreach ($settings as $key => $value) {
            $stmt = $conn->prepare("UPDATE settings SET setting_value = ? WHERE setting_key = ?");
            if (!$stmt->execute([$value, $key])) {
                $success = false;
                break;
            }
        }
        
        if ($success) {
            $success_message = "Settings updated successfully!";
        } else {
            $error = "Failed to update some settings.";
        }
    }
}

// Get current settings
$stmt = $conn->prepare("SELECT setting_key, setting_value FROM settings");
$stmt->execute();
$settings_data = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

// Get system stats
$stmt = $conn->prepare("SELECT COUNT(*) as total_users FROM users WHERE is_admin = 0");
$stmt->execute();
$total_users = $stmt->fetchColumn();

$stmt = $conn->prepare("SELECT COUNT(*) as total_products FROM products");
$stmt->execute();
$total_products = $stmt->fetchColumn();

$stmt = $conn->prepare("SELECT COUNT(*) as total_orders FROM orders");
$stmt->execute();
$total_orders = $stmt->fetchColumn();

$stmt = $conn->prepare("SELECT SUM(total_price) as total_revenue FROM orders WHERE status = 'paid'");
$stmt->execute();
$total_revenue = $stmt->fetchColumn() ?: 0;

// Get recent activity
$stmt = $conn->prepare("
    SELECT al.*, u.username 
    FROM activity_logs al
    JOIN users u ON al.user_id = u.id
    ORDER BY al.created_at DESC
    LIMIT 10
");
$stmt->execute();
$recent_activity = $stmt->fetchAll();
?>

<div class="admin-header">
    <div class="header-content">
        <div class="header-title">
            <h1><i class="fas fa-cog"></i> System Settings</h1>
            <p>Configure system settings, view statistics, manage preferences, and system maintenance</p>
        </div>
        <div class="header-actions">
            <div class="quick-stats">
                <div class="stat-item">
                    <i class="fas fa-save"></i>
                    <span>Save Settings</span>
                </div>
                <div class="stat-item">
                    <i class="fas fa-database"></i>
                    <span>Backup</span>
                </div>
                <div class="stat-item">
                    <i class="fas fa-shield-alt"></i>
                    <span>Security</span>
                </div>
            </div>
            <a href="#" onclick="backupDatabase()" class="btn btn-primary">
                <i class="fas fa-database"></i> Backup
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

    <?php if (isset($error)): ?>
        <div class="alert alert-error">
            <i class="fas fa-exclamation-circle"></i>
            <?php echo htmlspecialchars($error); ?>
        </div>
    <?php endif; ?>

    <div class="settings-grid">
        <!-- General Settings -->
        <div class="settings-card">
            <div class="card-header">
                <h3><i class="fas fa-sliders-h"></i> General Settings</h3>
            </div>
            <form method="POST" class="settings-form">
                <input type="hidden" name="action" value="update_general">
                
                <div class="form-group">
                    <label for="site_name">Site Name</label>
                    <input type="text" id="site_name" name="site_name" class="form-input" 
                           value="<?php echo htmlspecialchars($settings_data['site_name'] ?? 'CheatStore'); ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="site_description">Site Description</label>
                    <textarea id="site_description" name="site_description" class="form-input" rows="3"
                              placeholder="Brief description of your store"><?php echo htmlspecialchars($settings_data['site_description'] ?? ''); ?></textarea>
                </div>
                
                <div class="form-group">
                    <label for="contact_email">Contact Email</label>
                    <input type="email" id="contact_email" name="contact_email" class="form-input" 
                           value="<?php echo htmlspecialchars($settings_data['support_email'] ?? ''); ?>">
                </div>
                
                <div class="form-group">
                    <label for="currency">Currency</label>
                    <select id="currency" name="currency" class="form-input">
                        <option value="USD" <?php echo ($settings_data['currency'] ?? 'USD') === 'USD' ? 'selected' : ''; ?>>USD ($)</option>
                        <option value="EUR" <?php echo ($settings_data['currency'] ?? 'USD') === 'EUR' ? 'selected' : ''; ?>>EUR (€)</option>
                        <option value="GBP" <?php echo ($settings_data['currency'] ?? 'USD') === 'GBP' ? 'selected' : ''; ?>>GBP (£)</option>
                    </select>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label class="checkbox-label">
                            <input type="checkbox" name="maintenance_mode" <?php echo ($settings_data['maintenance_mode'] ?? 'false') === 'true' ? 'checked' : ''; ?>>
                            <span class="checkmark"></span>
                            Maintenance Mode
                            <small>Enable to show maintenance page to visitors</small>
                        </label>
                    </div>
                    
                    <div class="form-group">
                        <label class="checkbox-label">
                            <input type="checkbox" name="registration_enabled" <?php echo ($settings_data['registration_enabled'] ?? 'true') === 'true' ? 'checked' : ''; ?>>
                            <span class="checkmark"></span>
                            Allow Registration
                            <small>Enable new user registrations</small>
                        </label>
                    </div>
                </div>
                
                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i>
                        Save General Settings
                    </button>
                </div>
            </form>
        </div>

        <!-- System Information -->
        <div class="settings-card">
            <div class="card-header">
                <h3><i class="fas fa-info-circle"></i> System Information</h3>
            </div>
            <div class="info-grid">
                <div class="info-item">
                    <span class="info-label">PHP Version:</span>
                    <span class="info-value"><?php echo PHP_VERSION; ?></span>
                </div>
                <div class="info-item">
                    <span class="info-label">Database:</span>
                    <span class="info-value">MySQL</span>
                </div>
                <div class="info-item">
                    <span class="info-label">Server:</span>
                    <span class="info-value"><?php echo $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown'; ?></span>
                </div>
                <div class="info-item">
                    <span class="info-label">Upload Max:</span>
                    <span class="info-value"><?php echo ini_get('upload_max_filesize'); ?></span>
                </div>
            </div>
        </div>

        <!-- Statistics -->
        <div class="settings-card">
            <div class="card-header">
                <h3><i class="fas fa-chart-bar"></i> Statistics</h3>
            </div>
            <div class="stats-grid">
                <div class="stat-item">
                    <div class="stat-icon">
                        <i class="fas fa-users"></i>
                    </div>
                    <div class="stat-content">
                        <div class="stat-number"><?php echo $total_users; ?></div>
                        <div class="stat-label">Total Users</div>
                    </div>
                </div>
                <div class="stat-item">
                    <div class="stat-icon">
                        <i class="fas fa-shopping-cart"></i>
                    </div>
                    <div class="stat-content">
                        <div class="stat-number"><?php echo $total_orders; ?></div>
                        <div class="stat-label">Total Orders</div>
                    </div>
                </div>
                <div class="stat-item">
                    <div class="stat-icon">
                        <i class="fas fa-dollar-sign"></i>
                    </div>
                    <div class="stat-content">
                        <div class="stat-number">$<?php echo number_format($total_revenue, 2); ?></div>
                        <div class="stat-label">Total Revenue</div>
                    </div>
                </div>
                <div class="stat-item">
                    <div class="stat-icon">
                        <i class="fas fa-key"></i>
                    </div>
                    <div class="stat-content">
                        <div class="stat-number"><?php echo $total_products; ?></div>
                        <div class="stat-label">License Keys</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Recent Activity -->
        <div class="settings-card">
            <div class="card-header">
                <h3><i class="fas fa-clock"></i> Recent Activity</h3>
            </div>
            <?php if (empty($recent_activity)): ?>
                <div class="empty-state">
                    <i class="fas fa-clock"></i>
                    <h4>No recent activity</h4>
                    <p>Activity will appear here as users interact with the system</p>
                </div>
            <?php else: ?>
                <div class="activity-list">
                    <?php foreach ($recent_activity as $activity): ?>
                        <div class="activity-item">
                            <div class="activity-icon">
                                <i class="fas fa-<?php echo $activity['icon']; ?>"></i>
                            </div>
                            <div class="activity-content">
                                <div class="activity-text"><?php echo htmlspecialchars($activity['description']); ?></div>
                                <div class="activity-time"><?php echo date('M j, Y H:i', strtotime($activity['created_at'])); ?></div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<style>


.settings-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
    gap: 2rem;
}

.settings-card {
    background: rgba(30, 41, 59, 0.4);
    border-radius: 12px;
    padding: 2rem;
    border: 1px solid rgba(255, 255, 255, 0.1);
    backdrop-filter: blur(20px);
    transition: transform 0.3s ease, box-shadow 0.3s ease;
}

.settings-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 25px rgba(0, 0, 0, 0.2);
}

.card-header {
    margin-bottom: 1.5rem;
}

.card-header h3 {
    color: #f8fafc;
    font-size: 1.25rem;
    font-weight: 600;
    display: flex;
    align-items: center;
    gap: 0.5rem;
    margin: 0;
}

.settings-form {
    display: grid;
    gap: 1.5rem;
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

.form-input {
    padding: 0.75rem;
    background: rgba(51, 65, 85, 0.5);
    border: 1px solid rgba(255, 255, 255, 0.2);
    border-radius: 8px;
    color: #f8fafc;
    font-size: 1rem;
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

.form-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 1rem;
}

.checkbox-label {
    display: flex;
    align-items: flex-start;
    gap: 0.75rem;
    cursor: pointer;
    font-weight: 500;
    color: #f8fafc;
    position: relative;
}

.checkbox-label input[type="checkbox"] {
    width: 18px;
    height: 18px;
    accent-color: #ec4899;
    margin-top: 0.125rem;
}

.checkbox-label small {
    display: block;
    color: #94a3b8;
    font-size: 0.875rem;
    margin-top: 0.25rem;
}

.form-actions {
    display: flex;
    justify-content: flex-end;
}

.info-grid {
    display: grid;
    gap: 1rem;
}

.info-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 0.75rem;
    background: rgba(51, 65, 85, 0.3);
    border-radius: 8px;
    border: 1px solid rgba(255, 255, 255, 0.1);
}

.info-label {
    color: #cbd5e1;
    font-weight: 500;
}

.info-value {
    color: #f8fafc;
    font-weight: 600;
}

.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
    gap: 1rem;
}

.stat-item {
    display: flex;
    align-items: center;
    gap: 1rem;
    padding: 1rem;
    background: rgba(51, 65, 85, 0.3);
    border-radius: 8px;
    border: 1px solid rgba(255, 255, 255, 0.1);
    transition: transform 0.3s ease;
}

.stat-item:hover {
    transform: translateY(-2px);
}

.stat-icon {
    width: 40px;
    height: 40px;
    background: linear-gradient(135deg, #ec4899, #8b5cf6);
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 1.125rem;
}

.stat-content {
    flex: 1;
}

.stat-number {
    font-size: 1.5rem;
    font-weight: 700;
    color: #f8fafc;
    line-height: 1;
}

.stat-label {
    font-size: 0.875rem;
    color: #cbd5e1;
    margin-top: 0.25rem;
}

.activity-list {
    display: grid;
    gap: 1rem;
}

.activity-item {
    display: flex;
    align-items: center;
    gap: 1rem;
    padding: 1rem;
    background: rgba(51, 65, 85, 0.3);
    border-radius: 8px;
    border: 1px solid rgba(255, 255, 255, 0.1);
    transition: transform 0.3s ease;
}

.activity-item:hover {
    transform: translateX(4px);
}

.activity-icon {
    width: 32px;
    height: 32px;
    background: linear-gradient(135deg, #10b981, #3b82f6);
    border-radius: 6px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 0.875rem;
}

.activity-content {
    flex: 1;
}

.activity-text {
    color: #f8fafc;
    font-weight: 500;
    margin-bottom: 0.25rem;
}

.activity-time {
    font-size: 0.875rem;
    color: #94a3b8;
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
    
    .settings-grid {
        grid-template-columns: 1fr;
    }
    
    .form-row {
        grid-template-columns: 1fr;
    }
    
    .stats-grid {
        grid-template-columns: repeat(2, 1fr);
    }
}
</style>

<script>
// Handle form submissions with AJAX
document.addEventListener('DOMContentLoaded', function() {
    // Handle settings form
    const settingsForm = document.querySelector('form[method="POST"]');
    if (settingsForm) {
        settingsForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const submitBtn = this.querySelector('button[type="submit"]');
            const originalText = submitBtn.innerHTML;
            
            // Show loading state
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Saving...';
            submitBtn.disabled = true;
            
            // Get form data
            const formData = new FormData(this);
            
            // Send AJAX request
            fetch('index.php?page=admin/settings', {
                method: 'POST',
                body: formData
            })
            .then(response => response.text())
            .then(data => {
                // Reset button
                submitBtn.innerHTML = originalText;
                submitBtn.disabled = false;
                
                // Check if the response contains success message
                if (data.includes('alert-success') || data.includes('Settings updated successfully')) {
                    // Show success message
                    const event = new CustomEvent('showToast', {
                        detail: { message: 'Settings saved successfully!', type: 'success' }
                    });
                    document.dispatchEvent(event);
                    
                    // Reload page to show updated settings
                    setTimeout(() => {
                        window.location.reload();
                    }, 1000);
                } else {
                    // Show error message
                    const event = new CustomEvent('showToast', {
                        detail: { message: 'Failed to save settings. Please try again.', type: 'error' }
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
