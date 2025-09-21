<?php
// Session Configuration (must be before session_start())
ini_set('session.cookie_httponly', 1);
ini_set('session.cookie_secure', 0); // Set to 1 for HTTPS
ini_set('session.use_strict_mode', 1);
ini_set('session.cookie_samesite', 'Strict');

session_start();
require_once 'config/database.php';
require_once 'includes/functions.php';
require_once 'includes/auth.php';
require_once 'includes/reseller_functions.php';

// Initialize database connection
$db = new Database();
$conn = $db->getConnection();

// Get current page
$page = isset($_GET['page']) ? $_GET['page'] : 'home';

// Check if user is logged in
$isLoggedIn = isset($_SESSION['user_id']);
$user = null;

if ($isLoggedIn) {
    $user = getUserById($_SESSION['user_id']);
}

// Handle logout
if (isset($_GET['action']) && $_GET['action'] === 'logout') {
    logout();
    header('Location: index.php');
    exit();
}

// Handle AJAX requests for product purchase
if ($page === 'product' && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['purchase'])) {
    // Check if this is an AJAX request
    $isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';
    
    if (!$isAjax) {
        // Not an AJAX request, continue with normal page load
    } else {
        // This is an AJAX request, handle it here
        $slug = $_GET['slug'] ?? '';
        
        if (!$slug) {
            echo json_encode(['success' => false, 'message' => 'Product not found.']);
            exit();
        }
        
        // Get product details
        $conn = getDatabaseConnection();
        if (!$conn) {
            echo json_encode(['success' => false, 'message' => 'Database connection failed.']);
            exit();
        }
        
        $stmt = $conn->prepare("
            SELECT p.*
            FROM products p 
            WHERE p.slug = ? AND p.is_active = 1
        ");
        $stmt->execute([$slug]);
        $product = $stmt->fetch();
        
        if (!$product) {
            echo json_encode(['success' => false, 'message' => 'Product not found.']);
            exit();
        }
        
        // Check if user is logged in
        if (!$isLoggedIn) {
            echo json_encode(['success' => false, 'message' => 'Please log in to purchase products.', 'redirect' => 'index.php?page=login']);
            exit();
        }
        
        $duration = $_POST['duration'] ?? '';
        $quantity = max(1, intval($_POST['quantity'] ?? 1));
        
        if (!in_array($duration, ['1_day', '1_week', '1_month'])) {
            echo json_encode(['success' => false, 'message' => 'Invalid duration selected.']);
            exit();
        }
        
        // Calculate price
        $price_field = 'price_' . $duration;
        $unit_price = $product[$price_field];
        $total_price = $unit_price * $quantity;
        
        // Create order
        $stmt = $conn->prepare("
            INSERT INTO orders (user_id, product_id, duration, quantity, unit_price, total_price, status)
            VALUES (?, ?, ?, ?, ?, ?, 'pending')
        ");
        
        if ($stmt->execute([$_SESSION['user_id'], $product['id'], $duration, $quantity, $unit_price, $total_price])) {
            $order_id = $conn->lastInsertId();
            echo json_encode(['success' => true, 'redirect' => "index.php?page=payment&order_id=$order_id"]);
            exit();
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to create order. Please try again.']);
            exit();
        }
    }
}

// Handle AJAX requests for payment processing
if ($page === 'payment' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    // Check if this is an AJAX request
    $isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';
    
    if ($isAjax) {
        // This is an AJAX request, handle it here
        if (!$isLoggedIn) {
            echo json_encode(['success' => false, 'message' => 'Please log in to process payments.', 'redirect' => 'index.php?page=login']);
            exit();
        }
        
        $order_id = $_GET['order_id'] ?? '';
        $payment_method_id = $_POST['payment_method_id'] ?? '';
        
        if (!$order_id) {
            echo json_encode(['success' => false, 'message' => 'Order not found.']);
            exit();
        }
        
        if (!$payment_method_id) {
            echo json_encode(['success' => false, 'message' => 'Please select a payment method.']);
            exit();
        }
        
        // Get database connection
        $conn = getDatabaseConnection();
        if (!$conn) {
            echo json_encode(['success' => false, 'message' => 'Database connection failed.']);
            exit();
        }
        
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
            echo json_encode(['success' => false, 'message' => 'Order not found or already processed.']);
            exit();
        }
        
        try {
            // Check if Stripe library is available
            if (!class_exists('\Stripe\Stripe')) {
                // For testing purposes, simulate a successful payment
                $payment_intent_id = 'pi_test_' . uniqid();
                
                // Update order status to paid
                $stmt = $conn->prepare("
                    UPDATE orders 
                    SET status = 'paid', payment_id = ?, stripe_payment_intent_id = ?, updated_at = NOW()
                    WHERE id = ?
                ");
                $stmt->execute([$payment_method_id, $payment_intent_id, $order_id]);
                
                // Assign license keys from available keys
                for ($i = 0; $i < $order['quantity']; $i++) {
                    $result = assignLicenseKeyToOrder($order_id, $order['product_id'], $order['duration']);
                    if (!$result['success']) {
                        // If no keys available, create a fallback key
                        $license_key = generateLicenseKey();
                        $expiration_date = calculateExpirationDate($order['duration']);
                        
                        $stmt = $conn->prepare("
                            INSERT INTO license_keys (order_id, user_id, product_id, license_key, duration, expires_at)
                            VALUES (?, ?, ?, ?, ?, ?)
                        ");
                        $stmt->execute([
                            $order_id,
                            $_SESSION['user_id'],
                            $order['product_id'],
                            $license_key,
                            $order['duration'],
                            $expiration_date->format('Y-m-d H:i:s')
                        ]);
                    }
                }
                
                // Log activity
                logActivity($_SESSION['user_id'], 'purchase_completed', "Purchased {$order['quantity']}x {$order['product_name']} for \${$order['total_price']}");
                
                echo json_encode(['success' => true, 'redirect' => "index.php?page=payment-success&order_id=$order_id"]);
                exit();
            } else {
                // Stripe library is available - use real payment processing
                \Stripe\Stripe::setApiKey(STRIPE_SECRET_KEY);
                
                // Create payment intent
                $payment_intent = \Stripe\PaymentIntent::create([
                    'amount' => $order['total_price'] * 100, // Convert to cents
                    'currency' => 'usd',
                    'payment_method' => $payment_method_id,
                    'confirmation_method' => 'manual',
                    'confirm' => true,
                    'metadata' => [
                        'order_id' => $order_id,
                        'user_id' => $_SESSION['user_id'],
                        'product_id' => $order['product_id']
                    ]
                ]);
                
                if ($payment_intent->status === 'succeeded') {
                    // Payment successful - update order and generate license keys
                    $stmt = $conn->prepare("
                        UPDATE orders 
                        SET status = 'paid', payment_id = ?, stripe_payment_intent_id = ?, updated_at = NOW()
                        WHERE id = ?
                    ");
                    $stmt->execute([$payment_method_id, $payment_intent->id, $order_id]);
                    
                    // Assign license keys from available keys
                    for ($i = 0; $i < $order['quantity']; $i++) {
                        $result = assignLicenseKeyToOrder($order_id, $order['product_id'], $order['duration']);
                        if (!$result['success']) {
                            // If no keys available, create a fallback key
                            $license_key = generateLicenseKey();
                            $expiration_date = calculateExpirationDate($order['duration']);
                            
                            $stmt = $conn->prepare("
                                INSERT INTO license_keys (order_id, user_id, product_id, license_key, duration, expires_at)
                                VALUES (?, ?, ?, ?, ?, ?)
                            ");
                            $stmt->execute([
                                $order_id,
                                $_SESSION['user_id'],
                                $order['product_id'],
                                $license_key,
                                $order['duration'],
                                $expiration_date->format('Y-m-d H:i:s')
                            ]);
                        }
                    }
                    
                    // Log activity
                    logActivity($_SESSION['user_id'], 'purchase_completed', "Purchased {$order['quantity']}x {$order['product_name']} for \${$order['total_price']}");
                    
                    echo json_encode(['success' => true, 'redirect' => "index.php?page=payment-success&order_id=$order_id"]);
                    exit();
                } else {
                    echo json_encode(['success' => false, 'message' => 'Payment failed. Please try again.']);
                    exit();
                }
            }
        } catch (\Stripe\Exception\CardException $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
            exit();
        } catch (Exception $e) {
            $errorMessage = 'An error occurred. Please try again. Error: ' . $e->getMessage();
            error_log("Payment Error: " . $e->getMessage());
            error_log("Payment Error Trace: " . $e->getTraceAsString());
            echo json_encode(['success' => false, 'message' => $errorMessage]);
            exit();
        }
    }
}

    // Handle reseller application form submission
    if ($page === 'reseller-apply' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        // Debug logging
        error_log("Reseller apply request received");
        error_log("POST data: " . print_r($_POST, true));
        
        // Check if user is logged in
        if (!$isLoggedIn) {
            error_log("User not logged in for reseller application");
            header('Location: index.php?page=login');
            exit();
        }
        
        $businessName = sanitizeInput($_POST['business_name'] ?? '');
        $description = sanitizeInput($_POST['description'] ?? '');
        
        error_log("Business name: $businessName");
        error_log("Description: $description");
        
        if (empty($businessName)) {
            error_log("Business name is empty");
            $reseller_error = 'Business name is required.';
        } else {
            // Check if user is already a reseller
            $existingReseller = getResellerByUserId($_SESSION['user_id']);
            if ($existingReseller) {
                error_log("User already a reseller");
                $reseller_error = 'You have already applied as a reseller.';
            } else {
                // Register reseller
                error_log("Calling registerReseller function");
                $result = registerReseller($_SESSION['user_id'], $businessName, $description);
                error_log("registerReseller result: " . print_r($result, true));
                
                if ($result['success']) {
                    // Log activity
                    logActivity($_SESSION['user_id'], 'reseller_application', "Applied as reseller: $businessName");
                    
                    error_log("Reseller application successful");
                    $reseller_success = 'Application submitted successfully! We will review it soon.';
                    header('Location: index.php?page=dashboard');
                    exit();
                } else {
                    error_log("Reseller application failed: " . $result['message']);
                    $reseller_error = $result['message'];
                }
            }
        }
    }

// Handle login form submission
if ($page === 'login' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    
    // Basic validation
    if (empty($email) || empty($password)) {
        $login_error = 'Email and password are required.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $login_error = 'Please enter a valid email address.';
    } else {
        // Try to login
        try {
            $result = loginUser($email, $password);
            
            if ($result['success']) {
                // Redirect to dashboard
                header('Location: index.php?page=dashboard');
                exit();
            } else {
                $login_error = $result['message'];
            }
        } catch (Exception $e) {
            $login_error = 'Login failed. Please try again.';
        }
    }
}

// Handle registration form submission
if ($page === 'register' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    
    // Basic validation
    if (empty($username) || empty($email) || empty($password) || empty($confirm_password)) {
        $register_error = 'All fields are required.';
    } elseif (strlen($username) < 3) {
        $register_error = 'Username must be at least 3 characters long.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $register_error = 'Please enter a valid email address.';
    } elseif (strlen($password) < 6) {
        $register_error = 'Password must be at least 6 characters long.';
    } elseif ($password !== $confirm_password) {
        $register_error = 'Passwords do not match.';
    } else {
        // Try to register
        try {
            $result = registerUser($username, $email, $password);
            
            if ($result['success']) {
                // Try to auto-login
                $login_result = loginUser($email, $password);
                if ($login_result['success']) {
                    header('Location: index.php?page=dashboard');
                    exit();
                } else {
                    $register_success = 'Registration successful! Please log in.';
                }
            } else {
                $register_error = $result['message'];
            }
        } catch (Exception $e) {
            $register_error = 'Registration failed. Please try again.';
        }
    }
}

// Handle authentication requirements before any output
if ($page === 'reseller-dashboard' || $page === 'reseller-products' || $page === 'reseller-orders') {
    require_once 'includes/reseller_functions.php';
    requireReseller();
} elseif ($page === 'dashboard' || $page === 'orders' || $page === 'profile' || $page === 'payment' || $page === 'payment-success' || $page === 'reseller-apply') {
    require_once 'includes/auth.php';
    requireAuth();
} elseif ($page === 'admin') {
    require_once 'includes/auth.php';
    requireAdmin();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CheatStore - Premium Gaming Cheats</title>
    <meta name="description" content="Premium gaming cheats and hacks for popular games. 1 day, 1 week, and 1 month keys available.">
    
    <!-- CSS -->
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/components.css">
    <?php if ($page === 'admin'): ?>
    <link rel="stylesheet" href="assets/css/admin.css">
    <?php endif; ?>
    
    <!-- Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Teko:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
    <!-- Stripe -->
    <script src="https://js.stripe.com/v3/"></script>
    
    <!-- P5.js for animated background -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/p5.js/1.7.0/p5.min.js"></script>
    
    <style>
        /* Reset and base styles */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Inter', sans-serif;
            background: #000000;
            color: #ffffff;
            overflow-x: hidden;
        }
        
        /* Header styles - Enhanced */
        .header {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            z-index: 1000;
            background: rgba(0, 0, 0, 0.85);
            backdrop-filter: blur(15px);
            border-bottom: 1px solid rgba(255, 255, 255, 0.08);
            padding: 1.2rem 0;
            transition: all 0.3s ease;
        }
        
        .header.scrolled {
            background: rgba(0, 0, 0, 0.95);
            border-bottom: 1px solid rgba(255, 105, 180, 0.2);
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 2rem;
        }
        
        .header-content {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .logo a {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            text-decoration: none;
            color: #ffffff;
            font-family: 'Teko', sans-serif;
            font-weight: 600;
            font-size: 1.8rem;
            letter-spacing: -0.02em;
            transition: all 0.3s ease;
        }
        
        .logo a:hover {
            color: #ff69b4;
            transform: scale(1.02);
        }
        
        .logo i {
            color: #ff69b4;
            font-size: 2rem;
            filter: drop-shadow(0 0 8px rgba(255, 105, 180, 0.6));
        }
        
        .nav {
            display: flex;
            gap: 2.5rem;
        }
        
        .nav-link {
            color: rgba(255, 255, 255, 0.9);
            text-decoration: none;
            font-weight: 500;
            font-size: 0.95rem;
            letter-spacing: 0.02em;
            transition: all 0.3s ease;
            position: relative;
            padding: 0.5rem 0;
        }
        
        .nav-link::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            width: 0;
            height: 2px;
            background: linear-gradient(90deg, #ff69b4, #ff1493);
            transition: width 0.3s ease;
        }
        
        .nav-link:hover {
            color: #ffffff;
        }
        
        .nav-link:hover::after {
            width: 100%;
        }
        
        .header-actions {
            display: flex;
            gap: 1.2rem;
            align-items: center;
        }
        
        .btn {
            display: inline-flex;
            align-items: center;
            gap: 0.6rem;
            padding: 0.8rem 1.8rem;
            border-radius: 10px;
            text-decoration: none;
            font-weight: 600;
            font-size: 0.95rem;
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            border: 2px solid transparent;
            position: relative;
            overflow: hidden;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #ff69b4, #ff1493);
            color: #ffffff;
            box-shadow: 0 4px 15px rgba(255, 105, 180, 0.3);
        }
        
        .btn-primary:hover {
            background: linear-gradient(135deg, #ff1493, #ff69b4);
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(255, 105, 180, 0.4);
        }
        
        .btn-outline {
            background: transparent;
            color: rgba(255, 255, 255, 0.9);
            border-color: rgba(255, 255, 255, 0.6);
            backdrop-filter: blur(10px);
        }
        
        .btn-outline:hover {
            background: rgba(255, 255, 255, 0.1);
            border-color: rgba(255, 255, 255, 0.9);
            color: #ffffff;
            transform: translateY(-2px);
        }
        
        /* Hero Section - Redesigned */
        .hero-section {
            position: relative;
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            text-align: center;
            overflow: hidden;
        }
        
        #animated-background {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: 1;
        }
        
        .hero-content {
            position: relative;
            z-index: 2;
            max-width: 900px;
            padding: 2rem;
            opacity: 0;
            animation: fadeIn 1.2s ease-in-out forwards;
            margin-top: -5vh; /* Slight upward adjustment for better centering */
        }
        
        @keyframes fadeIn {
            to { opacity: 1; }
        }
        
        .hero-heading {
            font-family: 'Teko', sans-serif;
            font-size: 5.5rem;
            font-weight: 600;
            font-stretch: condensed;
            line-height: 0.9;
            margin-bottom: 1.5rem;
            color: #ffffff;
            text-shadow: 0 0 30px rgba(255, 105, 180, 0.6);
            letter-spacing: -0.02em;
        }
        
        .hero-subheading {
            font-size: 1.4rem;
            font-weight: 400;
            margin-bottom: 4rem; /* Increased margin for better spacing */
            color: #cccccc;
            opacity: 0.9;
            line-height: 1.6;
            max-width: 600px;
            margin-left: auto;
            margin-right: auto;
        }
        
        .hero-buttons {
            display: flex;
            gap: 2rem;
            justify-content: center;
            flex-wrap: wrap;
            margin-top: 2rem; /* Additional spacing for visual hierarchy */
        }
        
        .hero-btn {
            display: inline-flex;
            align-items: center;
            gap: 0.75rem;
            padding: 1.2rem 2.5rem;
            border-radius: 12px;
            text-decoration: none;
            font-weight: 600;
            font-size: 1.1rem;
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            border: 2px solid transparent;
            min-width: 180px;
            justify-content: center;
            position: relative;
            overflow: hidden;
        }
        
        .hero-btn-primary {
            background: linear-gradient(135deg, #ff69b4, #ff1493);
            color: #ffffff;
            box-shadow: 0 8px 25px rgba(255, 105, 180, 0.3);
        }
        
        .hero-btn-primary:hover {
            transform: translateY(-4px);
            box-shadow: 0 15px 40px rgba(255, 105, 180, 0.5);
        }
        
        .hero-btn-outline {
            background: transparent;
            color: #ffffff;
            border-color: rgba(255, 255, 255, 0.8);
            backdrop-filter: blur(10px);
        }
        
        .hero-btn-outline:hover {
            background: rgba(255, 255, 255, 0.1);
            border-color: #ffffff;
            transform: translateY(-4px);
        }
        
        /* Typing animation */
        .typing-cursor {
            display: inline-block;
            width: 4px;
            height: 5.5rem;
            background: #ff69b4;
            margin-left: 0.5rem;
            animation: blink 1.2s infinite;
            box-shadow: 0 0 10px rgba(255, 105, 180, 0.8);
        }
        
        @keyframes blink {
            0%, 50% { opacity: 1; }
            51%, 100% { opacity: 0; }
        }
        
        /* Scroll Indicator */
        .scroll-indicator {
            position: absolute;
            bottom: 2rem;
            left: 50%;
            transform: translateX(-50%);
            z-index: 3;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 0.5rem;
            opacity: 0;
            animation: fadeInScroll 2s ease-in-out 1s forwards;
        }
        
        .scroll-circle {
            width: 50px;
            height: 50px;
            border: 2px solid rgba(255, 255, 255, 0.6);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            animation: pulse 2s ease-in-out infinite;
            transition: all 0.3s ease;
            cursor: pointer;
        }
        
        .scroll-circle:hover {
            border-color: rgba(255, 255, 255, 0.9);
            transform: scale(1.1);
        }
        
        .scroll-arrow {
            color: rgba(255, 255, 255, 0.8);
            font-size: 1.2rem;
            animation: bounce 2s ease-in-out infinite;
        }
        
        .scroll-text {
            color: rgba(255, 255, 255, 0.6);
            font-size: 0.8rem;
            font-weight: 500;
            letter-spacing: 0.1em;
            text-transform: uppercase;
        }
        
        @keyframes fadeInScroll {
            to { opacity: 1; }
        }
        
        @keyframes pulse {
            0%, 100% { 
                transform: scale(1);
                box-shadow: 0 0 0 0 rgba(255, 255, 255, 0.3);
            }
            50% { 
                transform: scale(1.05);
                box-shadow: 0 0 0 10px rgba(255, 255, 255, 0);
            }
        }
        
        @keyframes bounce {
            0%, 20%, 50%, 80%, 100% { transform: translateY(0); }
            40% { transform: translateY(-5px); }
            60% { transform: translateY(-3px); }
        }
        
        /* User menu styles - Enhanced */
        .user-menu {
            position: relative;
        }
        
        .user-menu-toggle {
            display: flex;
            align-items: center;
            gap: 0.6rem;
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(255, 255, 255, 0.1);
            color: rgba(255, 255, 255, 0.9);
            cursor: pointer;
            padding: 0.7rem 1.2rem;
            border-radius: 10px;
            transition: all 0.3s ease;
            backdrop-filter: blur(10px);
            font-weight: 500;
        }
        
        .user-menu-toggle:hover {
            background: rgba(255, 255, 255, 0.1);
            border-color: rgba(255, 105, 180, 0.3);
            color: #ffffff;
            transform: translateY(-1px);
        }
        
        .user-menu-toggle.active {
            background: rgba(255, 105, 180, 0.15);
            border-color: rgba(255, 105, 180, 0.5);
            color: #ffffff;
        }
        
        .user-dropdown {
            position: absolute;
            top: calc(100% + 0.5rem);
            right: 0;
            background: rgba(0, 0, 0, 0.95);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.08);
            border-radius: 12px;
            padding: 0.8rem;
            min-width: 220px;
            z-index: 1001;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.5);
            animation: dropdownFadeIn 0.3s ease;
        }
        
        .dropdown-item {
            display: flex;
            align-items: center;
            gap: 0.8rem;
            padding: 0.8rem 1rem;
            color: rgba(255, 255, 255, 0.9);
            text-decoration: none;
            border-radius: 8px;
            transition: all 0.3s ease;
            font-weight: 500;
            font-size: 0.9rem;
        }
        
        .dropdown-item:hover {
            background: rgba(255, 105, 180, 0.15);
            color: #ffffff;
            transform: translateX(5px);
        }
        
        .dropdown-item i {
            color: #ff69b4;
            width: 16px;
            text-align: center;
        }
        
        @keyframes dropdownFadeIn {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        /* Responsive design - Enhanced */
        @media (max-width: 768px) {
            .hero-heading {
                font-size: 3.5rem;
                line-height: 0.95;
            }
            
            .hero-subheading {
                font-size: 1.2rem;
                margin-bottom: 3rem;
            }
            
            .hero-buttons {
                flex-direction: column;
                align-items: center;
                gap: 1.5rem;
            }
            
            .hero-btn {
                min-width: 200px;
                padding: 1rem 2rem;
            }
            
            .scroll-indicator {
                bottom: 1.5rem;
            }
            
            .scroll-circle {
                width: 45px;
                height: 45px;
            }
            
            .nav {
                display: none;
            }
            
            .header-content {
                justify-content: space-between;
            }
            
            .logo a {
                font-size: 1.5rem;
            }
            
            .logo i {
                font-size: 1.7rem;
            }
            
            .btn {
                padding: 0.7rem 1.5rem;
                font-size: 0.9rem;
            }
            
            .user-menu-toggle {
                padding: 0.6rem 1rem;
                font-size: 0.9rem;
            }
            
            .footer-content {
                grid-template-columns: 1fr;
                gap: 2rem;
            }
            
            .footer-section h3,
            .footer-section h4 {
                font-size: 1.2rem;
            }
        }
        
        @media (max-width: 480px) {
            .hero-heading {
                font-size: 2.8rem;
            }
            
            .hero-subheading {
                font-size: 1.1rem;
            }
            
            .hero-content {
                padding: 1rem;
                margin-top: -3vh;
            }
            
            .container {
                padding: 0 1rem;
            }
            
            .header {
                padding: 1rem 0;
            }
            
            .logo a {
                font-size: 1.3rem;
                gap: 0.5rem;
            }
            
            .logo i {
                font-size: 1.5rem;
            }
            
            .btn {
                padding: 0.6rem 1.2rem;
                font-size: 0.85rem;
            }
            
            .user-menu-toggle {
                padding: 0.5rem 0.8rem;
                font-size: 0.85rem;
            }
            
        .footer {
                padding: 3rem 0 1.5rem;
            }
            
            .social-links {
                gap: 1rem;
            }
            
            .social-links a {
                width: 40px;
                height: 40px;
            }
        }
        
        /* Main content area - Enhanced */
        .main {
            position: relative;
            z-index: 3;
            background: linear-gradient(180deg, #000000 0%, #0a0a0a 100%);
            min-height: 100vh;
            padding-top: 80px; /* Add padding to account for fixed header */
        }
        
        /* Footer styles - Enhanced */
        .footer {
            background: linear-gradient(180deg, rgba(0, 0, 0, 0.95) 0%, rgba(0, 0, 0, 0.98) 100%);
            border-top: 1px solid rgba(255, 105, 180, 0.1);
            padding: 4rem 0 2rem;
            margin-top: 4rem;
            position: relative;
        }
        
        .footer::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 1px;
            background: linear-gradient(90deg, transparent, rgba(255, 105, 180, 0.3), transparent);
        }
        
        .footer-content {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 3rem;
            margin-bottom: 3rem;
        }
        
        .footer-section h3,
        .footer-section h4 {
            color: #ff69b4;
            margin-bottom: 1.5rem;
            font-family: 'Teko', sans-serif;
            font-weight: 600;
            font-size: 1.4rem;
            letter-spacing: -0.02em;
        }
        
        .footer-section p {
            color: rgba(255, 255, 255, 0.8);
            line-height: 1.6;
            margin-bottom: 1.5rem;
        }
        
        .footer-section ul {
            list-style: none;
        }
        
        .footer-section ul li {
            margin-bottom: 0.8rem;
        }
        
        .footer-section a {
            color: rgba(255, 255, 255, 0.7);
            text-decoration: none;
            transition: all 0.3s ease;
            font-weight: 500;
            position: relative;
            padding-left: 0;
        }
        
        .footer-section a::before {
            content: '';
            position: absolute;
            left: 0;
            top: 50%;
            width: 0;
            height: 1px;
            background: #ff69b4;
            transition: width 0.3s ease;
            transform: translateY(-50%);
        }
        
        .footer-section a:hover {
            color: #ff69b4;
            padding-left: 15px;
        }
        
        .footer-section a:hover::before {
            width: 10px;
        }
        
        .social-links {
            display: flex;
            gap: 1.2rem;
            margin-top: 1.5rem;
        }
        
        .social-links a {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 45px;
            height: 45px;
            background: rgba(255, 105, 180, 0.1);
            border: 1px solid rgba(255, 105, 180, 0.2);
            border-radius: 12px;
            transition: all 0.3s ease;
            color: rgba(255, 255, 255, 0.8);
        }
        
        .social-links a:hover {
            background: rgba(255, 105, 180, 0.2);
            border-color: #ff69b4;
            color: #ffffff;
            transform: translateY(-3px);
            box-shadow: 0 8px 20px rgba(255, 105, 180, 0.3);
        }
        
        .footer-bottom {
            text-align: center;
            padding-top: 2.5rem;
            border-top: 1px solid rgba(255, 255, 255, 0.08);
            color: rgba(255, 255, 255, 0.6);
            font-size: 0.9rem;
        }
    </style>
</head>
<body>
    <!-- Header -->
    <header class="header">
        <div class="container">
            <div class="header-content">
                <div class="logo">
                    <a href="index.php">
                        <i class="fas fa-shield-alt"></i>
                        <span>CheatStore</span>
                    </a>
                </div>
                
                <nav class="nav">
                    <a href="index.php" class="nav-link">Home</a>
                    <a href="index.php?page=products" class="nav-link">Products</a>
                    <a href="index.php?page=about" class="nav-link">About</a>
                    <a href="index.php?page=contact" class="nav-link">Contact</a>
                </nav>
                
                <div class="header-actions">
                    <?php if ($isLoggedIn): ?>
                        <div class="user-menu">
                            <button class="user-menu-toggle">
                                <i class="fas fa-user"></i>
                                <span><?php echo htmlspecialchars($user['username']); ?></span>
                                <i class="fas fa-chevron-down"></i>
                            </button>
                            <div class="user-dropdown">
                                <a href="index.php?page=dashboard" class="dropdown-item">
                                    <i class="fas fa-tachometer-alt"></i>
                                    Dashboard
                                </a>
                                <a href="index.php?page=orders" class="dropdown-item">
                                    <i class="fas fa-shopping-cart"></i>
                                    My Orders
                                </a>
                                <a href="index.php?page=profile" class="dropdown-item">
                                    <i class="fas fa-user-edit"></i>
                                    Profile
                                </a>
                                <?php if (isReseller()): ?>
                                    <a href="index.php?page=reseller-dashboard" class="dropdown-item">
                                        <i class="fas fa-store"></i>
                                        Reseller Dashboard
                                    </a>
                                <?php else: ?>
                                    <a href="index.php?page=reseller-apply" class="dropdown-item">
                                        <i class="fas fa-store"></i>
                                        Become a Reseller
                                    </a>
                                <?php endif; ?>
                                <?php if (isAdmin()): ?>
                                    <a href="index.php?page=admin" class="dropdown-item">
                                        <i class="fas fa-crown"></i>
                                        Admin Panel
                                    </a>
                                <?php endif; ?>
                                <a href="index.php?action=logout" class="dropdown-item">
                                    <i class="fas fa-sign-out-alt"></i>
                                    Logout
                                </a>
                            </div>
                        </div>
                    <?php else: ?>
                        <a href="index.php?page=login" class="btn btn-outline">Login</a>
                        <a href="index.php?page=register" class="btn btn-primary">Register</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </header>

    <!-- Hero Section (only on home page) -->
    <?php if ($page === 'home' || $page === ''): ?>
    <section class="hero-section">
        <div id="animated-background"></div>
        <div class="hero-content">
            <h1 class="hero-heading">
                <span id="typing-text"></span>
                <span class="typing-cursor"></span>
            </h1>
            <p class="hero-subheading">Get the winning edge with powerful cheat software.</p>
            <div class="hero-buttons">
                <a href="index.php?page=products" class="hero-btn hero-btn-primary">
                    <i class="fas fa-shopping-cart"></i>
                    Purchase
                </a>
                <a href="#" class="hero-btn hero-btn-outline">
                    <i class="fab fa-discord"></i>
                    Discord
                </a>
            </div>
        </div>
        
        <!-- Scroll Indicator -->
        <div class="scroll-indicator">
            <div class="scroll-circle" onclick="scrollToContent()">
                <i class="fas fa-chevron-down scroll-arrow"></i>
            </div>
            <span class="scroll-text">Scroll</span>
        </div>
    </section>
    <?php endif; ?>

    <!-- Main Content -->
    <main class="main">
        <?php
        // Route to appropriate page
        switch ($page) {
            case 'home':
                include 'pages/home.php';
                break;
            case 'products':
                include 'pages/products.php';
                break;
            case 'product':
                include 'pages/product.php';
                break;
            case 'login':
                include 'pages/login.php';
                break;
            case 'register':
                include 'pages/register.php';
                break;
            case 'dashboard':
                include 'pages/dashboard.php';
                break;
            case 'orders':
                include 'pages/orders.php';
                break;
            case 'profile':
                include 'pages/profile.php';
                break;
            case 'admin':
                include 'pages/admin.php';
                break;
            case 'about':
                include 'pages/about.php';
                break;
            case 'contact':
                include 'pages/contact.php';
                break;
            case 'payment':
                include 'pages/payment.php';
                break;
            case 'payment-success':
                include 'pages/payment-success.php';
                break;
            case 'reseller-apply':
                include 'pages/reseller-apply.php';
                break;
            case 'reseller-dashboard':
                include 'pages/reseller-dashboard.php';
                break;
            case 'reseller-products':
                include 'pages/reseller-products.php';
                break;
            case 'reseller-orders':
                include 'pages/reseller-orders.php';
                break;
            case 'reseller-product':
                include 'pages/reseller-product.php';
                break;
            default:
                include 'pages/404.php';
                break;
        }
        ?>
    </main>

    <!-- Footer -->
    <footer class="footer">
        <div class="container">
            <div class="footer-content">
                <div class="footer-section">
                    <h3>CheatStore</h3>
                    <p>Premium gaming cheats and hacks for competitive advantage.</p>
                    <div class="social-links">
                        <a href="#"><i class="fab fa-discord"></i></a>
                        <a href="#"><i class="fab fa-telegram"></i></a>
                        <a href="#"><i class="fab fa-twitter"></i></a>
                    </div>
                </div>
                
                <div class="footer-section">
                    <h4>Quick Links</h4>
                    <ul>
                        <li><a href="index.php">Home</a></li>
                        <li><a href="index.php?page=products">Products</a></li>
                        <li><a href="index.php?page=about">About</a></li>
                        <li><a href="index.php?page=contact">Contact</a></li>
                    </ul>
                </div>
                
                <div class="footer-section">
                    <h4>Support</h4>
                    <ul>
                        <li><a href="#">FAQ</a></li>
                        <li><a href="#">Terms of Service</a></li>
                        <li><a href="#">Privacy Policy</a></li>
                        <li><a href="#">Refund Policy</a></li>
                    </ul>
                </div>
                
                <div class="footer-section">
                    <h4>Contact Info</h4>
                    <p><i class="fas fa-envelope"></i> support@cheatstore.net</p>
                    <p><i class="fab fa-discord"></i> Discord: CheatStore#1234</p>
                </div>
            </div>
            
            <div class="footer-bottom">
                <p>&copy; 2024 CheatStore. All rights reserved.</p>
            </div>
        </div>
    </footer>

    <!-- JavaScript -->
    <script src="assets/js/main.js"></script>
    <script src="assets/js/components.js"></script>
    <script src="assets/js/modern.js"></script>
    
    <!-- Enhanced Header Scroll Effect -->
    <script>
        // Header scroll effect
        window.addEventListener('scroll', function() {
            const header = document.querySelector('.header');
            if (window.scrollY > 50) {
                header.classList.add('scrolled');
            } else {
                header.classList.remove('scrolled');
            }
        });
        
        // Smooth scrolling for all internal links
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                e.preventDefault();
                const target = document.querySelector(this.getAttribute('href'));
                if (target) {
                    target.scrollIntoView({
                        behavior: 'smooth',
                        block: 'start'
                    });
                }
            });
        });
        
        // Enhanced button hover effects
        document.querySelectorAll('.btn, .hero-btn').forEach(button => {
            button.addEventListener('mouseenter', function() {
                this.style.transform = 'translateY(-2px) scale(1.02)';
            });
            
            button.addEventListener('mouseleave', function() {
                this.style.transform = 'translateY(0) scale(1)';
            });
        });
        
        // Add loading animation to page elements
        document.addEventListener('DOMContentLoaded', function() {
            const elements = document.querySelectorAll('.hero-content, .footer-content');
            elements.forEach((element, index) => {
                element.style.opacity = '0';
                element.style.transform = 'translateY(20px)';
                
                setTimeout(() => {
                    element.style.transition = 'all 0.8s cubic-bezier(0.4, 0, 0.2, 1)';
                    element.style.opacity = '1';
                    element.style.transform = 'translateY(0)';
                }, index * 200);
            });
        });
    </script>
    
    <?php if ($page === 'product'): ?>
    <script src="assets/js/product.js"></script>
    <?php endif; ?>
    
    <?php if ($page === 'dashboard'): ?>
    <script src="assets/js/dashboard.js"></script>
    <?php endif; ?>
    
    <!-- Hero Section JavaScript -->
    <?php if ($page === 'home' || $page === ''): ?>
    <script>
        // Game titles array - easily editable
        const gameTitles = [
            'Rust',
            'Rainbow Six Siege',
            'Valorant',
            'CS2',
            'Apex Legends',
            'Fortnite',
            'PUBG',
            'Warzone',
            'Escape from Tarkov',
            'Overwatch 2'
        ];

        // Typing animation class
        class TypeWriter {
            constructor(element, words, speed = 100, deleteSpeed = 50, pauseTime = 2000) {
                this.element = element;
                this.words = words;
                this.speed = speed;
                this.deleteSpeed = deleteSpeed;
                this.pauseTime = pauseTime;
                this.currentWordIndex = 0;
                this.currentCharIndex = 0;
                this.isDeleting = false;
                this.isPaused = false;
                this.type();
            }

            type() {
                const currentWord = this.words[this.currentWordIndex];
                
                if (this.isDeleting) {
                    this.currentCharIndex--;
                } else {
                    this.currentCharIndex++;
                }

                this.element.textContent = currentWord.substring(0, this.currentCharIndex);

                let typeSpeed = this.isDeleting ? this.deleteSpeed : this.speed;

                if (!this.isDeleting && this.currentCharIndex === currentWord.length) {
                    typeSpeed = this.pauseTime;
                    this.isDeleting = true;
                } else if (this.isDeleting && this.currentCharIndex === 0) {
                    this.isDeleting = false;
                    this.currentWordIndex = (this.currentWordIndex + 1) % this.words.length;
                    typeSpeed = 500;
                }

                setTimeout(() => this.type(), typeSpeed);
            }
        }

        // Initialize typing animation
        const typingElement = document.getElementById('typing-text');
        new TypeWriter(typingElement, gameTitles, 100, 50, 2000);

        // Animated background using p5.js - Digital Landscape
        let nodes = [];
        let connections = [];
        let time = 0;

        function setup() {
            const canvas = createCanvas(windowWidth, windowHeight);
            canvas.parent('animated-background');
            
            // Create nodes for digital landscape effect
            // Concentrate more nodes in the bottom 40% of the screen
            for (let i = 0; i < 25; i++) {
                nodes.push(new Node('bottom')); // Bottom area nodes
            }
            for (let i = 0; i < 15; i++) {
                nodes.push(new Node('top')); // Top area nodes (sparse)
            }
        }

        function draw() {
            background(0, 25);
            time += 0.002; // Very slow, fluid animation speed
            
            // Update nodes
            for (let node of nodes) {
                node.update();
            }
            
            // Create dynamic connections between nearby nodes
            connections = [];
            for (let i = 0; i < nodes.length; i++) {
                for (let j = i + 1; j < nodes.length; j++) {
                    const d = dist(nodes[i].x, nodes[i].y, nodes[j].x, nodes[j].y);
                    if (d < 160) {
                        connections.push(new Connection(nodes[i], nodes[j], d));
                    }
                }
            }
            
            // Display connections first (background)
            for (let connection of connections) {
                connection.display();
            }
            
            // Display nodes as tiny points only
            for (let node of nodes) {
                node.display();
            }
        }

        function windowResized() {
            resizeCanvas(windowWidth, windowHeight);
        }

        class Node {
            constructor(zone) {
                this.zone = zone;
                
                if (zone === 'bottom') {
                    // Concentrate nodes in bottom 40% of screen
                this.x = random(width);
                    this.y = random(height * 0.6, height + 50); // Bottom 40% + buffer
                    this.alpha = random(80, 150); // More visible in bottom
                } else {
                    // Sparse nodes in top 60% of screen
                    this.x = random(width);
                    this.y = random(-50, height * 0.6); // Top 60% + buffer
                    this.alpha = random(40, 80); // Less visible in top
                }
                
                this.vx = random(-0.12, 0.12); // Very slow, drifting movement
                this.vy = random(-0.12, 0.12); // Very slow, drifting movement
                this.pulseSpeed = random(0.008, 0.02);
                this.pulseOffset = random(TWO_PI);
            }

            update() {
                // Ultra-smooth, fluid movement like landscape drifting
                this.x += this.vx;
                this.y += this.vy;
                
                // Zone-specific boundary handling
                if (this.zone === 'bottom') {
                    // Bottom zone nodes wrap horizontally, stay in bottom area
                    if (this.x < -50) this.x = width + 50;
                    if (this.x > width + 50) this.x = -50;
                    if (this.y < height * 0.5) this.y = height * 0.5; // Don't go too high
                    if (this.y > height + 50) this.y = height + 50;
                } else {
                    // Top zone nodes wrap horizontally, stay in top area
                    if (this.x < -50) this.x = width + 50;
                    if (this.x > width + 50) this.x = -50;
                    if (this.y < -50) this.y = -50;
                    if (this.y > height * 0.7) this.y = height * 0.7; // Don't go too low
                }
                
                // Very subtle random drift
                this.vx += random(-0.001, 0.001);
                this.vy += random(-0.001, 0.001);
                
                // Keep movement very slow and smooth
                this.vx = constrain(this.vx, -0.15, 0.15);
                this.vy = constrain(this.vy, -0.15, 0.15);
            }

            display() {
                // Only display as a tiny glowing point, no filled shapes
                const pulse = sin(time * this.pulseSpeed + this.pulseOffset) * 0.3 + 0.7;
                const pointAlpha = this.alpha * pulse;
                
                // Single tiny point with glow
                stroke(255, 105, 180, pointAlpha);
                strokeWeight(1);
                point(this.x, this.y);
                
                // Subtle glow effect with additional point
                stroke(255, 105, 180, pointAlpha * 0.4);
                strokeWeight(0.5);
                point(this.x, this.y);
            }
        }

        class Connection {
            constructor(node1, node2, distance) {
                this.node1 = node1;
                this.node2 = node2;
                this.distance = distance;
                this.alpha = map(distance, 0, 160, 60, 0);
                this.pulseSpeed = random(0.015, 0.035);
                this.pulseOffset = random(TWO_PI);
            }

            display() {
                if (this.alpha > 3) {
                    // Pulsing line effect
                    const pulse = sin(time * this.pulseSpeed + this.pulseOffset) * 0.3 + 0.7;
                    const lineAlpha = this.alpha * pulse;
                    
                    // Single thin glowing line - 1px thick
                    stroke(255, 105, 180, lineAlpha);
                    strokeWeight(1);
                    line(this.node1.x, this.node1.y, this.node2.x, this.node2.y);
                    
                    // Subtle glow effect with a second line
                    stroke(255, 105, 180, lineAlpha * 0.3);
                    strokeWeight(0.5);
                    line(this.node1.x, this.node1.y, this.node2.x, this.node2.y);
                }
            }
        }

        // Add loading animation delay
        setTimeout(() => {
            const heroContent = document.querySelector('.hero-content');
            if (heroContent) {
                heroContent.classList.remove('loading');
            }
        }, 500);

        // Scroll to content function
        function scrollToContent() {
            const mainContent = document.querySelector('.main');
            if (mainContent) {
                mainContent.scrollIntoView({ 
                    behavior: 'smooth',
                    block: 'start'
                });
            }
        }

        // Add keyboard support for scroll indicator
        document.addEventListener('keydown', function(event) {
            if (event.key === 'ArrowDown' || event.key === ' ') {
                event.preventDefault();
                scrollToContent();
            }
        });
    </script>
    <?php endif; ?>
</body>
</html>
