<?php
// Contact page handling
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $subject = trim($_POST['subject'] ?? '');
    $message = trim($_POST['message'] ?? '');
    
    // Basic validation
    if (empty($name) || empty($email) || empty($subject) || empty($message)) {
        $error = 'All fields are required.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } else {
        // Here you would typically send the email
        // For now, we'll just show a success message
        $success = 'Thank you for your message! We will get back to you soon.';
        
        // Clear form data
        $name = $email = $subject = $message = '';
    }
}
?>

<div class="container">
    <div class="contact-header">
        <h1>Contact Us</h1>
        <p>Get in touch with our support team for any questions or assistance.</p>
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

    <div class="contact-content">
        <!-- Contact Information -->
        <div class="contact-info-section">
            <h2>Get in Touch</h2>
            <p>We're here to help! Choose the best way to reach us.</p>
            
            <div class="contact-methods">
                <div class="contact-method">
                    <div class="method-icon">
                        <i class="fas fa-envelope"></i>
                    </div>
                    <div class="method-content">
                        <h3>Email Support</h3>
                        <p>support@cheatstore.net</p>
                        <span class="response-time">Response within 24 hours</span>
                    </div>
                </div>
                
                <div class="contact-method">
                    <div class="method-icon">
                        <i class="fab fa-discord"></i>
                    </div>
                    <div class="method-content">
                        <h3>Discord</h3>
                        <p>CheatStore#1234</p>
                        <span class="response-time">Instant response</span>
                    </div>
                </div>
                
                <div class="contact-method">
                    <div class="method-icon">
                        <i class="fab fa-telegram"></i>
                    </div>
                    <div class="method-content">
                        <h3>Telegram</h3>
                        <p>@CheatStore</p>
                        <span class="response-time">Response within 1 hour</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Contact Form -->
        <div class="contact-form-section">
            <h2>Send us a Message</h2>
            <form method="POST" class="contact-form">
                <div class="form-group">
                    <label for="name">Full Name</label>
                    <input
                        type="text"
                        id="name"
                        name="name"
                        value="<?php echo htmlspecialchars($name ?? ''); ?>"
                        required
                    >
                </div>

                <div class="form-group">
                    <label for="email">Email Address</label>
                    <input
                        type="email"
                        id="email"
                        name="email"
                        value="<?php echo htmlspecialchars($email ?? ''); ?>"
                        required
                    >
                </div>

                <div class="form-group">
                    <label for="subject">Subject</label>
                    <input
                        type="text"
                        id="subject"
                        name="subject"
                        value="<?php echo htmlspecialchars($subject ?? ''); ?>"
                        required
                    >
                </div>

                <div class="form-group">
                    <label for="message">Message</label>
                    <textarea
                        id="message"
                        name="message"
                        rows="6"
                        required
                    ><?php echo htmlspecialchars($message ?? ''); ?></textarea>
                </div>

                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-paper-plane"></i>
                    Send Message
                </button>
            </form>
        </div>
    </div>

    <!-- FAQ Section -->
    <div class="faq-section">
        <h2>Frequently Asked Questions</h2>
        <div class="faq-grid">
            <div class="faq-item">
                <h3>How do I download my cheat after purchase?</h3>
                <p>After successful payment, you'll receive an email with download instructions and your license key. You can also find your keys in your dashboard.</p>
            </div>
            
            <div class="faq-item">
                <h3>Are your cheats undetected?</h3>
                <p>Yes, we use advanced anti-detection technology and regularly update our cheats to stay ahead of game updates and anti-cheat systems.</p>
            </div>
            
            <div class="faq-item">
                <h3>What payment methods do you accept?</h3>
                <p>We accept all major credit cards, PayPal, and various cryptocurrency payments through our secure payment system.</p>
            </div>
            
            <div class="faq-item">
                <h3>Do you offer refunds?</h3>
                <p>We offer refunds within 24 hours of purchase if the cheat doesn't work as advertised. Please contact support for assistance.</p>
            </div>
        </div>
    </div>
</div>

<style>
.contact-header {
    text-align: center;
    margin-bottom: 3rem;
    padding-top: 1rem;
}

.contact-header h1 {
    color: #ffffff;
    margin-bottom: 0.5rem;
    font-size: 2.5rem;
    font-weight: 700;
}

.contact-header p {
    color: rgba(255, 255, 255, 0.7);
    font-size: 1.125rem;
}

.contact-content {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 3rem;
    margin-bottom: 3rem;
    max-width: 1000px;
    margin: 0 auto 3rem auto;
}

.contact-info-section,
.contact-form-section {
    background: rgba(255, 255, 255, 0.05);
    border: 1px solid rgba(255, 255, 255, 0.1);
    border-radius: 16px;
    padding: 2rem;
    backdrop-filter: blur(10px);
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
    position: relative;
    overflow: hidden;
}

.contact-info-section::before,
.contact-form-section::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 1px;
    background: linear-gradient(90deg, transparent, #ff69b4, transparent);
}

.contact-info-section h2,
.contact-form-section h2 {
    color: #ffffff;
    margin-bottom: 1rem;
    font-weight: 600;
}

.contact-info-section p {
    color: rgba(255, 255, 255, 0.7);
    margin-bottom: 2rem;
}

.contact-methods {
    display: flex;
    flex-direction: column;
    gap: 1.5rem;
}

.contact-method {
    display: flex;
    align-items: center;
    gap: 1rem;
    padding: 1rem;
    background: rgba(255, 255, 255, 0.03);
    border: 1px solid rgba(255, 255, 255, 0.1);
    border-radius: 12px;
    transition: all 0.3s ease;
    backdrop-filter: blur(10px);
}

.contact-method:hover {
    border-color: rgba(255, 105, 180, 0.3);
    transform: translateY(-2px);
    box-shadow: 0 10px 25px rgba(0, 0, 0, 0.2);
}

.method-icon {
    font-size: 1.5rem;
    color: #ff69b4;
    width: 40px;
    text-align: center;
    filter: drop-shadow(0 0 8px rgba(255, 105, 180, 0.6));
}

.method-content h3 {
    color: #ffffff;
    margin-bottom: 0.25rem;
    font-size: 1rem;
    font-weight: 600;
}

.method-content p {
    color: rgba(255, 255, 255, 0.9);
    margin-bottom: 0.25rem;
    font-weight: 600;
}

.response-time {
    color: rgba(255, 255, 255, 0.6);
    font-size: 0.875rem;
}

.contact-form {
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
    color: rgba(255, 255, 255, 0.8);
}

.form-group input,
.form-group textarea {
    padding: 0.75rem;
    background: rgba(255, 255, 255, 0.05);
    border: 1px solid rgba(255, 255, 255, 0.2);
    border-radius: 8px;
    font-size: 1rem;
    font-family: inherit;
    color: #ffffff;
    backdrop-filter: blur(10px);
    transition: all 0.3s ease;
}

.form-group input:focus,
.form-group textarea:focus {
    outline: none;
    border-color: #ff69b4;
    box-shadow: 0 0 0 3px rgba(255, 105, 180, 0.1);
    background: rgba(255, 255, 255, 0.08);
}

.form-group input::placeholder,
.form-group textarea::placeholder {
    color: rgba(255, 255, 255, 0.5);
}

.form-group textarea {
    resize: vertical;
    min-height: 120px;
}

.btn {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.75rem 1.5rem;
    border: none;
    border-radius: 5px;
    font-size: 1rem;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s ease;
}

.btn-primary {
    background: linear-gradient(135deg, #ff69b4, #ff1493);
    color: white;
    box-shadow: 0 4px 15px rgba(255, 105, 180, 0.3);
}

.btn-primary:hover {
    background: linear-gradient(135deg, #ff1493, #ff69b4);
    transform: translateY(-2px);
    box-shadow: 0 8px 25px rgba(255, 105, 180, 0.4);
}

.alert {
    padding: 1rem;
    border-radius: 8px;
    margin-bottom: 1.5rem;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.alert-error {
    background: rgba(239, 68, 68, 0.1);
    border: 1px solid rgba(239, 68, 68, 0.3);
    color: #fca5a5;
}

.alert-success {
    background: rgba(16, 185, 129, 0.1);
    border: 1px solid rgba(16, 185, 129, 0.3);
    color: #6ee7b7;
}

.faq-section {
    background: rgba(255, 255, 255, 0.05);
    border: 1px solid rgba(255, 255, 255, 0.1);
    border-radius: 16px;
    padding: 2rem;
    backdrop-filter: blur(10px);
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
    margin-top: 3rem;
}

.faq-section h2 {
    color: #ffffff;
    margin-bottom: 2rem;
    text-align: center;
    font-weight: 600;
}

.faq-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 1.5rem;
}

.faq-item {
    padding: 1.5rem;
    background: rgba(255, 255, 255, 0.03);
    border: 1px solid rgba(255, 255, 255, 0.1);
    border-radius: 12px;
    transition: all 0.3s ease;
    backdrop-filter: blur(10px);
}

.faq-item:hover {
    border-color: rgba(255, 105, 180, 0.3);
    transform: translateY(-2px);
    box-shadow: 0 10px 25px rgba(0, 0, 0, 0.2);
}

.faq-item h3 {
    color: #ffffff;
    margin-bottom: 0.75rem;
    font-size: 1.125rem;
    font-weight: 600;
}

.faq-item p {
    color: rgba(255, 255, 255, 0.7);
    line-height: 1.6;
    margin: 0;
}

@media (max-width: 768px) {
    .contact-header h1 {
        font-size: 2rem;
    }
    
    .contact-content {
        grid-template-columns: 1fr;
        gap: 2rem;
    }
    
    .contact-info-section,
    .contact-form-section {
        padding: 1.5rem;
    }
    
    .faq-grid {
        grid-template-columns: 1fr;
    }
}
</style>

<script>
// Handle form submissions with AJAX
document.addEventListener('DOMContentLoaded', function() {
    // Handle contact form
    const contactForm = document.querySelector('.contact-form');
    if (contactForm) {
        contactForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const submitBtn = this.querySelector('button[type="submit"]');
            const originalText = submitBtn.innerHTML;
            
            // Show loading state
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Sending...';
            submitBtn.disabled = true;
            
            // Get form data
            const formData = new FormData(this);
            
            // Send AJAX request
            fetch('index.php?page=contact', {
                method: 'POST',
                body: formData
            })
            .then(response => response.text())
            .then(data => {
                // Reset button
                submitBtn.innerHTML = originalText;
                submitBtn.disabled = false;
                
                // Check if the response contains success message
                if (data.includes('alert-success') || data.includes('Message sent successfully')) {
                    // Show success message
                    const event = new CustomEvent('showToast', {
                        detail: { message: 'Message sent successfully! We will get back to you soon.', type: 'success' }
                    });
                    document.dispatchEvent(event);
                    
                    // Clear form
                    this.reset();
                } else {
                    // Show error message
                    const event = new CustomEvent('showToast', {
                        detail: { message: 'Failed to send message. Please try again.', type: 'error' }
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
