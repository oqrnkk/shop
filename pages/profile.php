<?php
// Profile page handling
$error = '';
$success = '';

// Get current user data
$user = getCurrentUser();

if (!$user) {
    header('Location: index.php?page=login');
    exit();
}

// Handle profile updates
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'update_profile') {
        $username = trim($_POST['username'] ?? '');
        $email = trim($_POST['email'] ?? '');
        
        // Basic validation
        if (empty($username) || empty($email)) {
            $error = 'Username and email are required.';
        } elseif (strlen($username) < 3) {
            $error = 'Username must be at least 3 characters long.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = 'Please enter a valid email address.';
        } else {
            // Check if username/email already exists (excluding current user)
            $conn = getDatabaseConnection();
            if ($conn) {
                $stmt = $conn->prepare("SELECT id FROM users WHERE username = ? AND id != ?");
                $stmt->execute([$username, $user['id']]);
                if ($stmt->fetch()) {
                    $error = 'Username already exists.';
                } else {
                    $stmt = $conn->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
                    $stmt->execute([$email, $user['id']]);
                    if ($stmt->fetch()) {
                        $error = 'Email already exists.';
                    } else {
                        // Update profile
                        $updateData = ['username' => $username, 'email' => $email];
                        if (updateUserProfile($user['id'], $updateData)) {
                            $success = 'Profile updated successfully!';
                            // Refresh user data
                            $user = getCurrentUser();
                        } else {
                            $error = 'Failed to update profile.';
                        }
                    }
                }
            } else {
                $error = 'Database connection unavailable.';
            }
        }
    } elseif ($action === 'change_password') {
        $currentPassword = $_POST['current_password'] ?? '';
        $newPassword = $_POST['new_password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';
        
        // Basic validation
        if (empty($currentPassword) || empty($newPassword) || empty($confirmPassword)) {
            $error = 'All password fields are required.';
        } elseif (strlen($newPassword) < 6) {
            $error = 'New password must be at least 6 characters long.';
        } elseif ($newPassword !== $confirmPassword) {
            $error = 'New passwords do not match.';
        } else {
            $result = changePassword($user['id'], $currentPassword, $newPassword);
            if ($result['success']) {
                $success = 'Password changed successfully!';
            } else {
                $error = $result['message'];
            }
        }
    }
}
?>

<div class="container">
    <div class="profile-container">
        <!-- Profile Header -->
        <div class="profile-header">
            <div class="profile-avatar">
                <div class="avatar-circle">
                    <i class="fas fa-user"></i>
                </div>
            </div>
            <div class="profile-info">
                <h1><?php echo htmlspecialchars($user['username']); ?></h1>
                <p class="user-email"><?php echo htmlspecialchars($user['email']); ?></p>
                <div class="user-status">
                    <span class="status-badge <?php echo $user['is_active'] ? 'active' : 'inactive'; ?>">
                        <i class="fas fa-circle"></i>
                        <?php echo $user['is_active'] ? 'Active' : 'Inactive'; ?>
                    </span>
                    <?php if (isReseller()): ?>
                        <span class="status-badge reseller">
                            <i class="fas fa-store"></i>
                            Reseller
                        </span>
                    <?php endif; ?>
                    <?php if (isAdmin()): ?>
                        <span class="status-badge admin">
                            <i class="fas fa-crown"></i>
                            Admin
                        </span>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-danger">
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

        <div class="profile-content">
            <div class="profile-grid">
                <!-- Profile Information -->
                <div class="profile-section">
                    <div class="section-header">
                        <h2><i class="fas fa-user-edit"></i> Profile Information</h2>
                        <p>Update your account details</p>
                    </div>
                    
                    <form method="POST" class="profile-form" id="profileForm">
                        <input type="hidden" name="action" value="update_profile">
                        
                        <div class="form-group">
                            <label for="username">Username</label>
                            <div class="input-wrapper">
                                <i class="fas fa-user input-icon"></i>
                                <input
                                    type="text"
                                    id="username"
                                    name="username"
                                    value="<?php echo htmlspecialchars($user['username']); ?>"
                                    required
                                >
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="email">Email Address</label>
                            <div class="input-wrapper">
                                <i class="fas fa-envelope input-icon"></i>
                                <input
                                    type="email"
                                    id="email"
                                    name="email"
                                    value="<?php echo htmlspecialchars($user['email']); ?>"
                                    required
                                >
                            </div>
                        </div>

                        <div class="info-grid">
                            <div class="info-item">
                                <label>Member Since</label>
                                <div class="info-value">
                                    <i class="fas fa-calendar-alt"></i>
                                    <?php echo date('F j, Y', strtotime($user['created_at'])); ?>
                                </div>
                            </div>

                            <div class="info-item">
                                <label>Last Login</label>
                                <div class="info-value">
                                    <i class="fas fa-clock"></i>
                                    <?php echo $user['last_login'] ? date('F j, Y g:i A', strtotime($user['last_login'])) : 'Never'; ?>
                                </div>
                            </div>
                        </div>

                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i>
                            Update Profile
                        </button>
                    </form>
                </div>

                <!-- Change Password -->
                <div class="profile-section">
                    <div class="section-header">
                        <h2><i class="fas fa-lock"></i> Security Settings</h2>
                        <p>Change your password</p>
                    </div>
                    
                    <form method="POST" class="profile-form" id="passwordForm">
                        <input type="hidden" name="action" value="change_password">
                        
                        <div class="form-group">
                            <label for="current_password">Current Password</label>
                            <div class="input-wrapper">
                                <i class="fas fa-key input-icon"></i>
                                <input
                                    type="password"
                                    id="current_password"
                                    name="current_password"
                                    required
                                >
                                <button type="button" class="password-toggle" onclick="togglePassword('current_password')">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="new_password">New Password</label>
                            <div class="input-wrapper">
                                <i class="fas fa-lock input-icon"></i>
                                <input
                                    type="password"
                                    id="new_password"
                                    name="new_password"
                                    required
                                >
                                <button type="button" class="password-toggle" onclick="togglePassword('new_password')">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="confirm_password">Confirm New Password</label>
                            <div class="input-wrapper">
                                <i class="fas fa-lock input-icon"></i>
                                <input
                                    type="password"
                                    id="confirm_password"
                                    name="confirm_password"
                                    required
                                >
                                <button type="button" class="password-toggle" onclick="togglePassword('confirm_password')">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </div>
                        </div>

                        <button type="submit" class="btn btn-secondary">
                            <i class="fas fa-key"></i>
                            Change Password
                        </button>
                    </form>
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="profile-section">
                <div class="section-header">
                    <h2><i class="fas fa-bolt"></i> Quick Actions</h2>
                    <p>Access your account features</p>
                </div>
                
                <div class="quick-actions">
                    <a href="index.php?page=orders" class="action-card">
                        <div class="action-icon">
                            <i class="fas fa-shopping-cart"></i>
                        </div>
                        <div class="action-content">
                            <h3>My Orders</h3>
                            <p>View your purchase history</p>
                        </div>
                        <i class="fas fa-chevron-right action-arrow"></i>
                    </a>
                    
                    <?php if (isReseller()): ?>
                        <a href="index.php?page=reseller-dashboard" class="action-card">
                            <div class="action-icon reseller">
                                <i class="fas fa-store"></i>
                            </div>
                            <div class="action-content">
                                <h3>Reseller Dashboard</h3>
                                <p>Manage your products and sales</p>
                            </div>
                            <i class="fas fa-chevron-right action-arrow"></i>
                        </a>
                    <?php else: ?>
                        <a href="index.php?page=reseller-apply" class="action-card">
                            <div class="action-icon">
                                <i class="fas fa-store"></i>
                            </div>
                            <div class="action-content">
                                <h3>Become a Reseller</h3>
                                <p>Start selling your own products</p>
                            </div>
                            <i class="fas fa-chevron-right action-arrow"></i>
                        </a>
                    <?php endif; ?>
                    
                    <?php if (isAdmin()): ?>
                        <a href="index.php?page=admin" class="action-card">
                            <div class="action-icon admin">
                                <i class="fas fa-crown"></i>
                            </div>
                            <div class="action-content">
                                <h3>Admin Panel</h3>
                                <p>Manage the entire platform</p>
                            </div>
                            <i class="fas fa-chevron-right action-arrow"></i>
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.profile-container {
    max-width: 1200px;
    margin: 0 auto;
    padding: 2rem 0;
}

/* Profile Header */
.profile-header {
    display: flex;
    align-items: center;
    gap: 2rem;
    background: rgba(255, 255, 255, 0.08);
    backdrop-filter: blur(10px);
    border: 1px solid rgba(255, 255, 255, 0.15);
    padding: 2rem;
    border-radius: 16px;
    margin-bottom: 2rem;
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
}

.profile-avatar {
    flex-shrink: 0;
}

.avatar-circle {
    width: 80px;
    height: 80px;
    background: linear-gradient(135deg, #ff69b4 0%, #a855f7 100%);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 2rem;
    box-shadow: 0 8px 25px rgba(255, 105, 180, 0.3);
}

.profile-info {
    flex: 1;
}

.profile-info h1 {
    color: #ffffff;
    font-size: 2rem;
    font-weight: 700;
    margin-bottom: 0.5rem;
}

.user-email {
    color: rgba(255, 255, 255, 0.8);
    font-size: 1.1rem;
    margin-bottom: 1rem;
}

.user-status {
    display: flex;
    gap: 0.75rem;
    flex-wrap: wrap;
}

/* Profile Content */
.profile-content {
    display: flex;
    flex-direction: column;
    gap: 2rem;
}

.profile-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
    gap: 2rem;
}

.profile-section {
    background: rgba(255, 255, 255, 0.08);
    backdrop-filter: blur(10px);
    border: 1px solid rgba(255, 255, 255, 0.15);
    padding: 2rem;
    border-radius: 16px;
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
}

.section-header {
    margin-bottom: 2rem;
}

.section-header h2 {
    color: #ffffff;
    font-size: 1.5rem;
    font-weight: 600;
    margin-bottom: 0.5rem;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.section-header p {
    color: rgba(255, 255, 255, 0.7);
    font-size: 0.95rem;
}

/* Forms */
.profile-form {
    display: flex;
    flex-direction: column;
    gap: 1.5rem;
}

.form-group {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
}

.form-group label {
    font-weight: 600;
    color: #ffffff;
    font-size: 0.95rem;
}

.input-wrapper {
    position: relative;
    display: flex;
    align-items: center;
}

.input-icon {
    position: absolute;
    left: 1rem;
    color: rgba(255, 255, 255, 0.6);
    z-index: 1;
}

.form-group input {
    width: 100%;
    padding: 0.75rem 1rem 0.75rem 2.5rem;
    background: rgba(255, 255, 255, 0.1);
    border: 1px solid rgba(255, 255, 255, 0.2);
    border-radius: 8px;
    color: #ffffff;
    font-size: 1rem;
    transition: all 0.3s ease;
}

.form-group input:focus {
    outline: none;
    border-color: #ff69b4;
    background: rgba(255, 255, 255, 0.15);
    box-shadow: 0 0 0 3px rgba(255, 105, 180, 0.1);
}

.form-group input::placeholder {
    color: rgba(255, 255, 255, 0.5);
}

.password-toggle {
    position: absolute;
    right: 1rem;
    background: none;
    border: none;
    color: rgba(255, 255, 255, 0.6);
    cursor: pointer;
    padding: 0.25rem;
    transition: color 0.3s ease;
}

.password-toggle:hover {
    color: #ff69b4;
}

/* Info Grid */
.info-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 1rem;
    margin-top: 1rem;
}

.info-item {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
}

.info-item label {
    font-weight: 600;
    color: rgba(255, 255, 255, 0.8);
    font-size: 0.9rem;
}

.info-value {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    color: #ffffff;
    font-size: 0.95rem;
}

.info-value i {
    color: #ff69b4;
    width: 16px;
}

/* Status Badges */
.status-badge {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.5rem 1rem;
    border-radius: 20px;
    font-size: 0.875rem;
    font-weight: 600;
    border: 1px solid;
}

.status-badge.active {
    background: rgba(16, 185, 129, 0.2);
    color: #10b981;
    border-color: rgba(16, 185, 129, 0.3);
}

.status-badge.inactive {
    background: rgba(239, 68, 68, 0.2);
    color: #ef4444;
    border-color: rgba(239, 68, 68, 0.3);
}

.status-badge.reseller {
    background: rgba(255, 105, 180, 0.2);
    color: #ff69b4;
    border-color: rgba(255, 105, 180, 0.3);
}

.status-badge.admin {
    background: rgba(168, 85, 247, 0.2);
    color: #a855f7;
    border-color: rgba(168, 85, 247, 0.3);
}

.status-badge i {
    font-size: 0.75rem;
}

/* Buttons */
.btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 0.5rem;
    padding: 0.75rem 1.5rem;
    border: none;
    border-radius: 8px;
    font-size: 1rem;
    font-weight: 600;
    text-decoration: none;
    cursor: pointer;
    transition: all 0.3s ease;
    min-height: 44px;
}

.btn-primary {
    background: linear-gradient(135deg, #ff69b4 0%, #a855f7 100%);
    color: white;
    box-shadow: 0 4px 15px rgba(255, 105, 180, 0.3);
}

.btn-primary:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 25px rgba(255, 105, 180, 0.4);
}

.btn-secondary {
    background: rgba(255, 255, 255, 0.1);
    color: #ffffff;
    border: 1px solid rgba(255, 255, 255, 0.2);
}

.btn-secondary:hover {
    background: rgba(255, 255, 255, 0.2);
    border-color: rgba(255, 255, 255, 0.3);
}

/* Quick Actions */
.quick-actions {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 1rem;
}

.action-card {
    display: flex;
    align-items: center;
    gap: 1rem;
    padding: 1.5rem;
    background: rgba(255, 255, 255, 0.05);
    border: 1px solid rgba(255, 255, 255, 0.1);
    border-radius: 12px;
    text-decoration: none;
    color: inherit;
    transition: all 0.3s ease;
}

.action-card:hover {
    background: rgba(255, 255, 255, 0.1);
    border-color: rgba(255, 105, 180, 0.3);
    transform: translateY(-2px);
    box-shadow: 0 8px 25px rgba(255, 105, 180, 0.2);
}

.action-icon {
    width: 50px;
    height: 50px;
    background: linear-gradient(135deg, #ff69b4 0%, #a855f7 100%);
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 1.25rem;
    flex-shrink: 0;
}

.action-icon.reseller {
    background: linear-gradient(135deg, #ff69b4 0%, #ec4899 100%);
}

.action-icon.admin {
    background: linear-gradient(135deg, #a855f7 0%, #8b5cf6 100%);
}

.action-content {
    flex: 1;
}

.action-content h3 {
    color: #ffffff;
    font-size: 1.1rem;
    font-weight: 600;
    margin-bottom: 0.25rem;
}

.action-content p {
    color: rgba(255, 255, 255, 0.7);
    font-size: 0.9rem;
    margin: 0;
}

.action-arrow {
    color: rgba(255, 255, 255, 0.5);
    font-size: 0.875rem;
    transition: all 0.3s ease;
}

.action-card:hover .action-arrow {
    color: #ff69b4;
    transform: translateX(4px);
}

/* Alerts */
.alert {
    padding: 1rem;
    border-radius: 8px;
    margin-bottom: 1.5rem;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.alert-success {
    background: rgba(16, 185, 129, 0.2);
    color: #10b981;
    border: 1px solid rgba(16, 185, 129, 0.3);
}

.alert-danger {
    background: rgba(239, 68, 68, 0.2);
    color: #ef4444;
    border: 1px solid rgba(239, 68, 68, 0.3);
}

/* Responsive Design */
@media (max-width: 768px) {
    .profile-header {
        flex-direction: column;
        text-align: center;
        gap: 1rem;
    }
    
    .profile-grid {
        grid-template-columns: 1fr;
    }
    
    .quick-actions {
        grid-template-columns: 1fr;
    }
    
    .action-card {
        flex-direction: column;
        text-align: center;
    }
    
    .user-status {
        justify-content: center;
    }
    
    .info-grid {
        grid-template-columns: 1fr;
    }
}

@media (max-width: 480px) {
    .profile-container {
        padding: 1rem 0;
    }
    
    .profile-section {
        padding: 1.5rem;
    }
    
    .btn {
        width: 100%;
    }
}
</style>

<script>
// Password toggle functionality
function togglePassword(inputId) {
    const input = document.getElementById(inputId);
    const toggle = input.parentElement.querySelector('.password-toggle');
    const icon = toggle.querySelector('i');
    
    if (input.type === 'password') {
        input.type = 'text';
        icon.classList.remove('fa-eye');
        icon.classList.add('fa-eye-slash');
    } else {
        input.type = 'password';
        icon.classList.remove('fa-eye-slash');
        icon.classList.add('fa-eye');
    }
}

// Handle form submissions with AJAX
document.addEventListener('DOMContentLoaded', function() {
    // Handle profile update form
    const profileForm = document.getElementById('profileForm');
    if (profileForm) {
        profileForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const submitBtn = this.querySelector('button[type="submit"]');
            const originalText = submitBtn.innerHTML;
            
            // Show loading state
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Updating...';
            submitBtn.disabled = true;
            
            // Get form data
            const formData = new FormData(this);
            
            // Send AJAX request
            fetch('index.php?page=profile', {
                method: 'POST',
                body: formData
            })
            .then(response => response.text())
            .then(data => {
                // Reset button
                submitBtn.innerHTML = originalText;
                submitBtn.disabled = false;
                
                // Check if the response contains success message
                if (data.includes('alert-success') || data.includes('Profile updated successfully')) {
                    // Show success message
                    const event = new CustomEvent('showToast', {
                        detail: { message: 'Profile updated successfully!', type: 'success' }
                    });
                    document.dispatchEvent(event);
                    
                    // Reload page to show updated information
                    setTimeout(() => {
                        window.location.reload();
                    }, 1000);
                } else {
                    // Show error message
                    const event = new CustomEvent('showToast', {
                        detail: { message: 'Failed to update profile. Please try again.', type: 'error' }
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
    
    // Handle password change form
    const passwordForm = document.getElementById('passwordForm');
    if (passwordForm) {
        passwordForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const submitBtn = this.querySelector('button[type="submit"]');
            const originalText = submitBtn.innerHTML;
            
            // Show loading state
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Changing...';
            submitBtn.disabled = true;
            
            // Get form data
            const formData = new FormData(this);
            
            // Send AJAX request
            fetch('index.php?page=profile', {
                method: 'POST',
                body: formData
            })
            .then(response => response.text())
            .then(data => {
                // Reset button
                submitBtn.innerHTML = originalText;
                submitBtn.disabled = false;
                
                // Check if the response contains success message
                if (data.includes('alert-success') || data.includes('Password changed successfully')) {
                    // Show success message
                    const event = new CustomEvent('showToast', {
                        detail: { message: 'Password changed successfully!', type: 'success' }
                    });
                    document.dispatchEvent(event);
                    
                    // Clear form
                    this.reset();
                } else {
                    // Show error message
                    const event = new CustomEvent('showToast', {
                        detail: { message: 'Failed to change password. Please try again.', type: 'error' }
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
    
    // Add smooth animations to action cards
    const actionCards = document.querySelectorAll('.action-card');
    actionCards.forEach(card => {
        card.addEventListener('mouseenter', function() {
            this.style.transform = 'translateY(-4px) scale(1.02)';
        });
        
        card.addEventListener('mouseleave', function() {
            this.style.transform = 'translateY(0) scale(1)';
        });
    });
});
</script>
