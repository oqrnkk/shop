<?php
// Login error from index.php processing
$error = $login_error ?? '';
?>

<div class="auth-container">
    <div class="auth-card">
        <div class="auth-header">
            <div class="auth-logo">
                <i class="fas fa-shield-alt"></i>
            </div>
            <h1 class="auth-title">Welcome Back</h1>
            <p class="auth-subtitle">Sign in to your account to continue</p>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-circle"></i>
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <form method="POST" class="auth-form" id="loginForm">
            <div class="form-group">
                <label for="email" class="form-label">
                    <i class="fas fa-envelope"></i>
                    Email Address
                </label>
                <input
                    type="email"
                    id="email"
                    name="email"
                    class="form-input"
                    placeholder="Enter your email"
                    value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>"
                    required
                >
            </div>

            <div class="form-group">
                <label for="password" class="form-label">
                    <i class="fas fa-lock"></i>
                    Password
                </label>
                <div class="password-input-container">
                    <input
                        type="password"
                        id="password"
                        name="password"
                        class="form-input"
                        placeholder="Enter your password"
                        required
                    >
                    <button
                        type="button"
                        class="password-toggle"
                        onclick="togglePassword('password')"
                    >
                        <i class="fas fa-eye"></i>
                    </button>
                </div>
            </div>

            <div class="form-group">
                <label class="checkbox-label">
                    <input type="checkbox" name="remember" value="1">
                    <span class="checkmark"></span>
                    Remember me
                </label>
            </div>

            <button type="submit" class="btn btn-primary auth-submit" id="loginBtn">
                <i class="fas fa-sign-in-alt"></i>
                Sign In
            </button>
        </form>

        <div class="auth-footer">
            <p class="auth-footer-text">
                Don't have an account? 
                <a href="index.php?page=register" class="auth-link">Sign up here</a>
            </p>
            <p class="auth-footer-text">
                <a href="index.php?page=forgot-password" class="auth-link">Forgot your password?</a>
            </p>
        </div>
    </div>
</div>

<style>
/* Auth Container */
.auth-container {
    min-height: 100vh;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: var(--space-lg);
    background: linear-gradient(135deg, rgba(255, 105, 180, 0.1) 0%, rgba(168, 85, 247, 0.1) 100%);
    position: relative;
    overflow: hidden;
}

.auth-container::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: radial-gradient(circle at 30% 20%, rgba(255, 105, 180, 0.05) 0%, transparent 50%),
                radial-gradient(circle at 70% 80%, rgba(168, 85, 247, 0.05) 0%, transparent 50%);
    pointer-events: none;
}

/* Auth Card */
.auth-card {
    background: rgba(255, 255, 255, 0.05);
    border: 1px solid rgba(255, 255, 255, 0.1);
    border-radius: var(--radius-xl);
    padding: var(--space-2xl);
    width: 100%;
    max-width: 450px;
    backdrop-filter: blur(20px);
    position: relative;
    overflow: hidden;
    box-shadow: 0 25px 50px rgba(0, 0, 0, 0.3);
}

.auth-card::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 1px;
    background: linear-gradient(90deg, transparent, var(--primary), transparent);
}

/* Auth Header */
.auth-header {
    text-align: center;
    margin-bottom: var(--space-xl);
}

.auth-logo {
    margin-bottom: var(--space-lg);
}

.auth-logo i {
    font-size: 3rem;
    color: var(--primary);
    filter: drop-shadow(0 0 20px rgba(255, 105, 180, 0.5));
}

.auth-title {
    font-family: var(--font-display);
    font-size: 2.5rem;
    font-weight: 600;
    color: #ffffff;
    margin-bottom: var(--space-sm);
    letter-spacing: -0.02em;
}

.auth-subtitle {
    color: rgba(255, 255, 255, 0.7);
    font-size: 1rem;
    font-weight: 500;
}

/* Auth Form */
.auth-form {
    margin-bottom: var(--space-xl);
}

.auth-form .form-group {
    margin-bottom: var(--space-lg);
}

.auth-form .form-label {
    display: flex;
    align-items: center;
    gap: var(--space-sm);
    margin-bottom: var(--space-sm);
    font-weight: 600;
    color: #ffffff;
    font-size: 0.9rem;
    text-transform: uppercase;
    letter-spacing: 0.05em;
}

.auth-form .form-label i {
    color: var(--primary);
    width: 16px;
    text-align: center;
}

.auth-form .form-input {
    width: 100%;
    padding: var(--space-md);
    border: 1px solid rgba(255, 255, 255, 0.2);
    border-radius: var(--radius-lg);
    background: rgba(255, 255, 255, 0.05);
    color: #ffffff;
    font-size: 1rem;
    transition: var(--transition);
    backdrop-filter: blur(10px);
}

.auth-form .form-input:focus {
    outline: none;
    border-color: var(--primary);
    box-shadow: 0 0 0 3px rgba(255, 105, 180, 0.1);
    background: rgba(255, 255, 255, 0.08);
}

.auth-form .form-input::placeholder {
    color: rgba(255, 255, 255, 0.5);
}

/* Password Input Container */
.password-input-container {
    position: relative;
}

.password-toggle {
    position: absolute;
    right: var(--space-md);
    top: 50%;
    transform: translateY(-50%);
    background: none;
    border: none;
    color: rgba(255, 255, 255, 0.6);
    cursor: pointer;
    padding: var(--space-xs);
    transition: var(--transition);
    border-radius: var(--radius);
}

.password-toggle:hover {
    color: var(--primary);
    background: rgba(255, 255, 255, 0.1);
}

/* Checkbox */
.checkbox-label {
    display: flex;
    align-items: center;
    cursor: pointer;
    font-size: 0.9rem;
    color: rgba(255, 255, 255, 0.8);
    font-weight: 500;
}

.checkbox-label input[type="checkbox"] {
    display: none;
}

.checkmark {
    width: 20px;
    height: 20px;
    border: 2px solid rgba(255, 255, 255, 0.3);
    border-radius: var(--radius);
    margin-right: var(--space-sm);
    position: relative;
    transition: var(--transition);
    background: rgba(255, 255, 255, 0.05);
}

.checkbox-label input[type="checkbox"]:checked + .checkmark {
    background: var(--primary);
    border-color: var(--primary);
    box-shadow: 0 0 0 3px rgba(255, 105, 180, 0.2);
}

.checkbox-label input[type="checkbox"]:checked + .checkmark::after {
    content: '\f00c';
    font-family: 'Font Awesome 6 Free';
    font-weight: 900;
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    color: white;
    font-size: 10px;
}

/* Auth Submit Button */
.auth-submit {
    width: 100%;
    justify-content: center;
    margin-top: var(--space-lg);
    padding: var(--space-lg);
    font-size: 1.1rem;
    font-weight: 600;
}

/* Auth Footer */
.auth-footer {
    text-align: center;
    padding-top: var(--space-xl);
    border-top: 1px solid rgba(255, 255, 255, 0.1);
}

.auth-footer-text {
    color: rgba(255, 255, 255, 0.7);
    font-size: 0.9rem;
    margin-bottom: var(--space-sm);
}

.auth-footer-text:last-child {
    margin-bottom: 0;
}

.auth-link {
    color: var(--primary);
    text-decoration: none;
    font-weight: 600;
    transition: var(--transition);
}

.auth-link:hover {
    color: var(--primary-light);
    text-decoration: underline;
}

/* Alert Styles */
.alert {
    padding: var(--space-md);
    border-radius: var(--radius-lg);
    margin-bottom: var(--space-lg);
    border: 1px solid transparent;
    backdrop-filter: blur(10px);
    display: flex;
    align-items: center;
    gap: var(--space-sm);
}

.alert-danger {
    background: rgba(239, 68, 68, 0.1);
    border-color: rgba(239, 68, 68, 0.3);
    color: #ef4444;
}

.alert i {
    font-size: 1.1rem;
}

/* Responsive Design */
@media (max-width: 768px) {
    .auth-container {
        padding: var(--space);
    }
    
    .auth-card {
        padding: var(--space-xl);
    }
    
    .auth-title {
        font-size: 2rem;
    }
}

@media (max-width: 480px) {
    .auth-card {
        padding: var(--space-lg);
    }
    
    .auth-title {
        font-size: 1.75rem;
    }
    
    .auth-submit {
        padding: var(--space-md);
        font-size: 1rem;
    }
}
</style>

<script>
// Prevent the automatic loading state from modern.js
document.addEventListener('DOMContentLoaded', function() {
    const loginBtn = document.getElementById('loginBtn');
    if (loginBtn) {
        // Remove any existing click event listeners that might add loading state
        const newBtn = loginBtn.cloneNode(true);
        loginBtn.parentNode.replaceChild(newBtn, loginBtn);
        
        // Add our own simple form submission
        const form = document.getElementById('loginForm');
        form.addEventListener('submit', function(e) {
            // Let the form submit normally - no loading state
            return true;
        });
    }
});

function togglePassword(inputId) {
    const input = document.getElementById(inputId);
    const toggle = input.nextElementSibling;
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
</script>
