<?php
// Check if user is already a reseller
$existingReseller = getResellerByUserId($_SESSION['user_id'] ?? 0);

// Get error/success messages from index.php processing
$error = $reseller_error ?? '';
$success = $reseller_success ?? '';

// Debug information
if (isset($_POST['business_name'])) {
    error_log("Reseller apply form submitted with business_name: " . $_POST['business_name']);
    error_log("Full POST data: " . print_r($_POST, true));
    error_log("Session user_id: " . ($_SESSION['user_id'] ?? 'not set'));
}
?>

<div class="container">
    <div class="reseller-apply">
        <div class="apply-header">
            <h1><i class="fas fa-store"></i> Become a Reseller</h1>
            <p>Join our reseller program and start earning money by selling your cheats!</p>
        </div>

        <?php if ($existingReseller): ?>
            <div class="reseller-status">
                <div class="status-card">
                    <div class="status-icon">
                        <i class="fas fa-store"></i>
                    </div>
                    <div class="status-content">
                        <h3><?php echo htmlspecialchars($existingReseller['business_name']); ?></h3>
                        <p class="status-text">
                            <?php if ($existingReseller['is_approved']): ?>
                                <span class="status-badge status-active">Approved</span>
                                Your reseller account is active and ready to use!
                            <?php else: ?>
                                <span class="status-badge status-pending">Pending Approval</span>
                                Your application is being reviewed by our team.
                            <?php endif; ?>
                        </p>
                        <p class="status-description"><?php echo htmlspecialchars($existingReseller['description']); ?></p>
                    </div>
                </div>
                
                <?php if ($existingReseller['is_approved']): ?>
                    <div class="status-actions">
                        <a href="index.php?page=reseller-dashboard" class="btn btn-primary">
                            <i class="fas fa-tachometer-alt"></i>
                            Go to Dashboard
                        </a>
                        <a href="index.php?page=reseller-products" class="btn btn-outline">
                            <i class="fas fa-plus"></i>
                            Add Products
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <div class="apply-content">
                <div class="benefits-section">
                    <h2>Why Become a Reseller?</h2>
                    <div class="benefits-grid">
                        <div class="benefit-card">
                            <div class="benefit-icon">
                                <i class="fas fa-dollar-sign"></i>
                            </div>
                            <h3>Earn 80% Commission</h3>
                            <p>Keep 80% of every sale you make. We only take 20% as platform fee.</p>
                        </div>
                        
                        <div class="benefit-card">
                            <div class="benefit-icon">
                                <i class="fas fa-users"></i>
                            </div>
                            <h3>Large Customer Base</h3>
                            <p>Access our existing customer base and grow your business faster.</p>
                        </div>
                        
                        <div class="benefit-card">
                            <div class="benefit-icon">
                                <i class="fas fa-chart-line"></i>
                            </div>
                            <h3>Analytics & Insights</h3>
                            <p>Track your sales, earnings, and performance with detailed analytics.</p>
                        </div>
                        
                        <div class="benefit-card">
                            <div class="benefit-icon">
                                <i class="fas fa-shield-alt"></i>
                            </div>
                            <h3>Secure Payments</h3>
                            <p>We handle all payments securely. Get paid automatically to your account.</p>
                        </div>
                    </div>
                </div>

                <div class="apply-form-section">
                    <h2>Apply Now</h2>

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

                    <form method="POST" action="index.php?page=reseller-apply" class="apply-form" id="resellerApplyForm">
                        <div class="form-group">
                            <label for="business_name" class="form-label">Business Name *</label>
                            <input type="text" id="business_name" name="business_name" class="form-control" 
                                   value="<?php echo htmlspecialchars($_POST['business_name'] ?? ''); ?>" 
                                   placeholder="Your business or brand name" required>
                            <small class="form-help">This will be displayed to customers</small>
                        </div>
                        
                        <div class="form-group">
                            <label for="description" class="form-label">Business Description</label>
                            <textarea id="description" name="description" class="form-control" rows="4" 
                                      placeholder="Tell us about your business, experience, and what you plan to sell..."><?php echo htmlspecialchars($_POST['description'] ?? ''); ?></textarea>
                            <small class="form-help">Optional: Help us understand your business better</small>
                        </div>
                        
                                                 <div class="form-actions">
                             <button type="submit" class="btn btn-primary btn-large">
                                 <i class="fas fa-paper-plane"></i>
                                 Submit Application
                             </button>
                             <button type="button" class="btn btn-outline" onclick="resetForm()" style="margin-left: 10px;">
                                 <i class="fas fa-refresh"></i>
                                 Reset
                             </button>
                             <button type="button" class="btn btn-secondary" onclick="testForm()" style="margin-left: 10px;">
                                 <i class="fas fa-bug"></i>
                                 Test Form
                             </button>
                         </div>
                    </form>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<style>
.reseller-apply {
    max-width: 1200px;
    margin: 0 auto;
    padding: 2rem 0;
}

.apply-header {
    text-align: center;
    margin-bottom: 3rem;
}

.apply-header h1 {
    font-size: 2.5rem;
    font-weight: 700;
    color: #333;
    margin-bottom: 1rem;
}

.apply-header p {
    font-size: 1.2rem;
    color: #666;
    max-width: 600px;
    margin: 0 auto;
}

.benefits-section {
    margin-bottom: 3rem;
}

.benefits-section h2 {
    text-align: center;
    font-size: 2rem;
    font-weight: 600;
    color: #333;
    margin-bottom: 2rem;
}

.benefits-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 2rem;
    margin-bottom: 3rem;
}

.benefit-card {
    background: white;
    padding: 2rem;
    border-radius: 12px;
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
    text-align: center;
    transition: transform 0.3s ease;
}

.benefit-card:hover {
    transform: translateY(-5px);
}

.benefit-icon {
    width: 60px;
    height: 60px;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 1rem;
    color: white;
    font-size: 1.5rem;
}

.benefit-card h3 {
    font-size: 1.2rem;
    font-weight: 600;
    color: #333;
    margin-bottom: 0.5rem;
}

.benefit-card p {
    color: #666;
    line-height: 1.6;
}

.apply-form-section {
    background: white;
    padding: 2rem;
    border-radius: 12px;
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
}

.apply-form-section h2 {
    font-size: 1.5rem;
    font-weight: 600;
    color: #333;
    margin-bottom: 1.5rem;
}

.apply-form {
    max-width: 600px;
}

.form-group {
    margin-bottom: 1.5rem;
}

.form-label {
    display: block;
    font-weight: 600;
    color: #333;
    margin-bottom: 0.5rem;
}

.form-control {
    width: 100%;
    padding: 0.75rem;
    border: 2px solid #eee;
    border-radius: 8px;
    font-size: 1rem;
    transition: border-color 0.3s ease;
}

.form-control:focus {
    outline: none;
    border-color: #667eea;
}

.form-help {
    display: block;
    font-size: 0.9rem;
    color: #666;
    margin-top: 0.25rem;
}

.form-actions {
    margin-top: 2rem;
}

.btn-large {
    padding: 1rem 2rem;
    font-size: 1.1rem;
}

.alert {
    padding: 12px 16px;
    border-radius: 8px;
    margin-bottom: 20px;
    display: flex;
    align-items: center;
    gap: 8px;
}

.alert-danger {
    background: #fee;
    color: #c53030;
    border: 1px solid #feb2b2;
}

.alert-success {
    background: #f0fff4;
    color: #2f855a;
    border: 1px solid #9ae6b4;
}

.btn-secondary {
    background: #6c757d;
    color: white;
    border: 1px solid #6c757d;
}

.btn-secondary:hover {
    background: #5a6268;
    border-color: #545b62;
}

.reseller-status {
    max-width: 800px;
    margin: 0 auto;
}

.status-card {
    background: white;
    padding: 2rem;
    border-radius: 12px;
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
    display: flex;
    align-items: center;
    gap: 2rem;
    margin-bottom: 2rem;
}

.status-icon {
    width: 80px;
    height: 80px;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 2rem;
}

.status-content h3 {
    font-size: 1.5rem;
    font-weight: 600;
    color: #333;
    margin-bottom: 0.5rem;
}

.status-text {
    font-size: 1.1rem;
    color: #666;
    margin-bottom: 0.5rem;
}

.status-description {
    color: #666;
    line-height: 1.6;
}

.status-actions {
    display: flex;
    gap: 1rem;
    justify-content: center;
}

@media (max-width: 768px) {
    .apply-header h1 {
        font-size: 2rem;
    }
    
    .benefits-grid {
        grid-template-columns: 1fr;
    }
    
    .status-card {
        flex-direction: column;
        text-align: center;
    }
    
    .status-actions {
        flex-direction: column;
    }
}
</style>

<script>
// Simple form submission without AJAX to avoid loading state issues
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('resellerApplyForm');
    if (form) {
        // Remove any existing event listeners
        const newForm = form.cloneNode(true);
        form.parentNode.replaceChild(newForm, form);
        
        // Add simple form submission
        newForm.addEventListener('submit', function(e) {
            const submitBtn = this.querySelector('button[type="submit"]');
            const originalText = submitBtn.innerHTML;
            
            // Show loading state
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Submitting...';
            submitBtn.disabled = true;
            
            // Let the form submit normally
            return true;
        });
    }
});

// Function to reset the form if it gets stuck
function resetForm() {
    const submitBtn = document.querySelector('#resellerApplyForm button[type="submit"]');
    if (submitBtn) {
        submitBtn.innerHTML = '<i class="fas fa-paper-plane"></i> Submit Application';
        submitBtn.disabled = false;
    }
    console.log('Form reset manually');
}

// Function to test the form submission
function testForm() {
    const form = document.getElementById('resellerApplyForm');
    const businessNameInput = form.querySelector('#business_name');
    const descriptionInput = form.querySelector('#description');
    
    console.log('Testing form submission...');
    console.log('Business name:', businessNameInput.value);
    console.log('Description:', descriptionInput.value);
    console.log('Form action:', form.action);
    console.log('Form method:', form.method);
    
    // Test if form can be submitted
    if (businessNameInput.value.trim() === '') {
        alert('Please enter a business name first');
        return;
    }
    
    // Show what would be submitted
    const formData = new FormData(form);
    console.log('Form data that would be submitted:');
    for (let [key, value] of formData.entries()) {
        console.log(key + ': ' + value);
    }
    
    alert('Form test completed. Check console for details.');
}
</script>
