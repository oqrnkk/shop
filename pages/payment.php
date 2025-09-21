<?php
require_once 'config/config.php';

// Get database connection
$conn = getDatabaseConnection();

if (!$isLoggedIn) {
    header('Location: index.php?page=login');
    exit();
}

$order_id = $_GET['order_id'] ?? '';
$order = null;

if (!$order_id) {
    header('Location: index.php?page=products');
    exit();
}

if ($conn) {
    try {
        // Get order details
        $stmt = $conn->prepare("
            SELECT o.*, p.name as product_name, p.slug as product_slug
            FROM orders o
            JOIN products p ON o.product_id = p.id
            WHERE o.id = ? AND o.user_id = ? AND o.status = 'pending'
        ");
        $stmt->execute([$order_id, $_SESSION['user_id']]);
        $order = $stmt->fetch();

        if (!$order) {
            header('Location: index.php?page=products');
            exit();
        }
    } catch (Exception $e) {
        // Log error or handle gracefully
        error_log("Database error in payment.php: " . $e->getMessage());
        header('Location: index.php?page=products');
        exit();
    }
} else {
    header('Location: index.php?page=products');
    exit();
}

// Handle non-AJAX payment submission (fallback)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Check if this is an AJAX request
    $isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';
    
    // Only handle non-AJAX requests here (AJAX is handled in index.php)
    if (!$isAjax) {
        $payment_method_id = $_POST['payment_method_id'] ?? '';
        
        if (!$payment_method_id) {
            $error = 'Please select a payment method.';
        } else {
            // For non-AJAX requests, redirect to the same page with an error
            // This is a fallback in case JavaScript is disabled
            $error = 'Please enable JavaScript to process payments securely.';
        }
    }
}
?>

<div class="container">
    <div class="payment-container">
        <div class="payment-header">
            <h1>Complete Payment</h1>
            <p>Secure payment powered by Stripe</p>
        </div>
        
        <div class="payment-content">
            <div class="order-summary">
                <h2>Order Summary</h2>
                <div class="order-details">
                    <div class="order-item">
                        <div class="item-info">
                            <h3><?php echo htmlspecialchars($order['product_name']); ?></h3>
                            <p>Duration: <?php echo ucfirst(str_replace('_', ' ', $order['duration'])); ?></p>
                            <p>Quantity: <?php echo $order['quantity']; ?></p>
                        </div>
                        <div class="item-price">
                            $<?php echo number_format($order['total_price'], 2); ?>
                        </div>
                    </div>
                </div>
                
                <div class="order-total">
                    <div class="total-row">
                        <span>Total:</span>
                        <span class="total-amount">$<?php echo number_format($order['total_price'], 2); ?></span>
                    </div>
                </div>
            </div>
            
            <div class="payment-form">
                <h2>Payment Information</h2>
                
                <?php if (isset($error)): ?>
                    <div class="alert alert-error">
                        <i class="fas fa-exclamation-circle"></i>
                        <?php echo htmlspecialchars($error); ?>
                    </div>
                <?php endif; ?>
                
                <form method="POST" id="payment-form">
                                         <!-- Email Field -->
                     <div class="form-group">
                         <label for="email" class="form-label">Email</label>
                         <input type="email" id="email" name="email" class="form-control" 
                                value="<?php echo htmlspecialchars($_SESSION['email'] ?? ''); ?>" 
                                placeholder="email@example.com" required>
                     </div>
                     
                     <!-- Card Information -->
                     <div class="form-group">
                         <label for="card-element" class="form-label">Card Information</label>
                         <div id="card-element" class="card-element"></div>
                         <div id="card-errors" class="card-errors" role="alert"></div>
                     </div>
                     
                     <!-- Cardholder Name -->
                     <div class="form-group">
                         <label for="cardholder-name" class="form-label">Name on Card</label>
                         <input type="text" id="cardholder-name" name="cardholder_name" class="form-control" 
                                placeholder="Full Name" required>
                     </div>
                     
                     <!-- Country and Postal Code -->
                     <div class="form-row">
                         <div class="form-group">
                             <label for="country" class="form-label">Country or Region</label>
                             <select id="country" name="country" class="form-control" required>
                                 <option value="">Select Country</option>
                                 <option value="US" selected>United States</option>
                                 <option value="DE">Germany</option>
                                 <option value="GB">United Kingdom</option>
                                 <option value="FR">France</option>
                                 <option value="IT">Italy</option>
                                 <option value="ES">Spain</option>
                                 <option value="CA">Canada</option>
                                 <option value="AU">Australia</option>
                                 <option value="NL">Netherlands</option>
                                 <option value="BE">Belgium</option>
                                 <option value="AT">Austria</option>
                                 <option value="CH">Switzerland</option>
                                 <option value="SE">Sweden</option>
                                 <option value="NO">Norway</option>
                                 <option value="DK">Denmark</option>
                                 <option value="FI">Finland</option>
                                 <option value="PL">Poland</option>
                                 <option value="CZ">Czech Republic</option>
                                 <option value="HU">Hungary</option>
                                 <option value="RO">Romania</option>
                                 <option value="BG">Bulgaria</option>
                                 <option value="HR">Croatia</option>
                                 <option value="SI">Slovenia</option>
                                 <option value="SK">Slovakia</option>
                                 <option value="LT">Lithuania</option>
                                 <option value="LV">Latvia</option>
                                 <option value="EE">Estonia</option>
                                 <option value="IE">Ireland</option>
                                 <option value="PT">Portugal</option>
                                 <option value="GR">Greece</option>
                                 <option value="CY">Cyprus</option>
                                 <option value="MT">Malta</option>
                                 <option value="LU">Luxembourg</option>
                             </select>
                         </div>
                         <div class="form-group">
                             <label for="postal-code" class="form-label">Postal Code</label>
                             <input type="text" id="postal-code" name="postal_code" class="form-control" 
                                    placeholder="ZIP Code" required>
                         </div>
                     </div>
                     
                     <button type="submit" class="btn btn-primary payment-btn" id="submit-button">
                         <i class="fas fa-lock"></i>
                         Pay $<?php echo number_format($order['total_price'], 2); ?>
                     </button>
                </form>
                
                <div class="payment-security">
                    <div class="security-info">
                        <i class="fas fa-shield-alt"></i>
                        <span>Your payment is secure and encrypted</span>
                    </div>
                    <div class="security-info">
                        <i class="fas fa-lock"></i>
                        <span>Powered by Stripe</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.payment-container {
    max-width: 800px;
    margin: 0 auto;
    padding: 2rem 0;
}

.payment-header {
    text-align: center;
    margin-bottom: 3rem;
    color: white;
}

.payment-header h1 {
    font-size: 2.5rem;
    font-weight: 700;
    margin-bottom: 1rem;
    text-shadow: 0 2px 10px rgba(0, 0, 0, 0.3);
}

.payment-header p {
    font-size: 1.1rem;
    opacity: 0.9;
}

.payment-content {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 3rem;
    align-items: start;
}

.order-summary {
    background: white;
    border-radius: 12px;
    padding: 2rem;
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
}

.order-summary h2 {
    margin-bottom: 1.5rem;
    color: #333;
    border-bottom: 1px solid #eee;
    padding-bottom: 1rem;
}

.order-item {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 1.5rem;
    padding-bottom: 1.5rem;
    border-bottom: 1px solid #eee;
}

.item-info h3 {
    margin-bottom: 0.5rem;
    color: #333;
}

.item-info p {
    color: #666;
    font-size: 0.9rem;
    margin-bottom: 0.25rem;
}

.item-price {
    font-size: 1.2rem;
    font-weight: 700;
    color: #667eea;
}

.order-total {
    border-top: 2px solid #eee;
    padding-top: 1rem;
}

.total-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    font-size: 1.2rem;
    font-weight: 700;
    color: #333;
}

.total-amount {
    color: #667eea;
    font-size: 1.5rem;
}

.payment-form {
    background: white;
    border-radius: 12px;
    padding: 2rem;
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
}

.payment-form h2 {
    margin-bottom: 1.5rem;
    color: #333;
}

.card-element {
    border: 2px solid #eee;
    border-radius: 8px;
    padding: 1rem;
    background: #f8f9fa;
    transition: border-color 0.3s ease;
}

.card-element.StripeElement--focus {
    border-color: #667eea;
}

.card-element.StripeElement--invalid {
    border-color: #dc3545;
}

 .card-errors {
     color: #dc3545;
     font-size: 0.9rem;
     margin-top: 0.5rem;
     min-height: 20px;
 }
 
 .form-row {
     display: grid;
     grid-template-columns: 1fr 1fr;
     gap: 1rem;
 }
 
 .form-control {
     width: 100%;
     padding: 0.75rem;
     border: 2px solid #eee;
     border-radius: 8px;
     font-size: 1rem;
     transition: border-color 0.3s ease;
     background: #f8f9fa;
 }
 
 .form-control:focus {
     outline: none;
     border-color: #667eea;
     background: white;
 }
 
 .form-control::placeholder {
     color: #aab7c4;
 }
 
 .form-label {
     display: block;
     margin-bottom: 0.5rem;
     font-weight: 600;
     color: #333;
     font-size: 0.9rem;
 }

.payment-btn {
    width: 100%;
    justify-content: center;
    margin: 1.5rem 0;
    font-size: 1.1rem;
    padding: 1rem;
}

.payment-btn:disabled {
    opacity: 0.6;
    cursor: not-allowed;
}

.payment-security {
    margin-top: 2rem;
    padding-top: 1.5rem;
    border-top: 1px solid #eee;
}

.security-info {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    margin-bottom: 0.75rem;
    color: #666;
    font-size: 0.9rem;
}

.security-info i {
    color: #28a745;
    width: 16px;
}

 @media (max-width: 768px) {
     .payment-content {
         grid-template-columns: 1fr;
         gap: 2rem;
     }
     
     .payment-header h1 {
         font-size: 2rem;
     }
     
     .form-row {
         grid-template-columns: 1fr;
         gap: 0;
     }
     
     .form-group {
         margin-bottom: 1rem;
     }
 }
</style>

<script>
// Initialize Stripe
const stripe = Stripe('<?php echo STRIPE_PUBLISHABLE_KEY; ?>');
const elements = stripe.elements();

// Create card element
const card = elements.create('card', {
    style: {
        base: {
            fontSize: '16px',
            color: '#424770',
            '::placeholder': {
                color: '#aab7c4',
            },
        },
        invalid: {
            color: '#9e2146',
        },
    },
});

// Mount card element
card.mount('#card-element');

// Handle form submission
const form = document.getElementById('payment-form');
const submitButton = document.getElementById('submit-button');

 form.addEventListener('submit', async (event) => {
     event.preventDefault();
     
     submitButton.disabled = true;
     submitButton.innerHTML = '<div class="loading"></div>Processing...';
     
     // Get billing details
     const email = document.getElementById('email').value;
     const cardholderName = document.getElementById('cardholder-name').value;
     const country = document.getElementById('country').value;
     const postalCode = document.getElementById('postal-code').value;
     
     // Validate required fields
     if (!email || !cardholderName || !country || !postalCode) {
         const errorElement = document.getElementById('card-errors');
         errorElement.textContent = 'Please fill in all required fields.';
         submitButton.disabled = false;
         submitButton.innerHTML = '<i class="fas fa-lock"></i>Pay $<?php echo number_format($order['total_price'], 2); ?>';
         return;
     }
     
     const {paymentMethod, error} = await stripe.createPaymentMethod({
         type: 'card',
         card: card,
         billing_details: {
             name: cardholderName,
             email: email,
             address: {
                 country: country,
                 postal_code: postalCode
             }
         }
     });
     
     if (error) {
         const errorElement = document.getElementById('card-errors');
         errorElement.textContent = error.message;
         submitButton.disabled = false;
         submitButton.innerHTML = '<i class="fas fa-lock"></i>Pay $<?php echo number_format($order['total_price'], 2); ?>';
     } else {
         // Add payment method ID to form
         const hiddenInput = document.createElement('input');
         hiddenInput.setAttribute('type', 'hidden');
         hiddenInput.setAttribute('name', 'payment_method_id');
         hiddenInput.setAttribute('value', paymentMethod.id);
         form.appendChild(hiddenInput);
         
         // Submit form via AJAX
         console.log('Submitting payment form via AJAX...');
         
         const formData = new FormData(form);
         
         fetch(window.location.href, {
             method: 'POST',
             headers: {
                 'X-Requested-With': 'XMLHttpRequest'
             },
             body: formData
         })
         .then(response => response.text())
         .then(text => {
             console.log('Response:', text);
             try {
                 const data = JSON.parse(text);
                 if (data.success) {
                     // Redirect to success page
                     window.location.href = data.redirect;
                 } else {
                     // Show error message
                     const errorElement = document.getElementById('card-errors');
                     errorElement.textContent = data.message;
                     submitButton.disabled = false;
                     submitButton.innerHTML = '<i class="fas fa-lock"></i>Pay $<?php echo number_format($order['total_price'], 2); ?>';
                 }
             } catch (e) {
                 console.error('Failed to parse JSON:', e);
                 // If it's not JSON, it might be a redirect or error page
                 window.location.reload();
             }
         })
         .catch(error => {
             console.error('Error:', error);
             const errorElement = document.getElementById('card-errors');
             errorElement.textContent = 'An error occurred. Please try again.';
             submitButton.disabled = false;
             submitButton.innerHTML = '<i class="fas fa-lock"></i>Pay $<?php echo number_format($order['total_price'], 2); ?>';
         });
     }
 });

// Handle card element changes
card.addEventListener('change', ({error}) => {
    const displayError = document.getElementById('card-errors');
    if (error) {
        displayError.textContent = error.message;
    } else {
        displayError.textContent = '';
    }
});
</script>
