<?php
// 404 Error Page
http_response_code(404);
?>

<div class="container">
    <div class="error-page">
        <div class="error-content">
            <div class="error-icon">
                <i class="fas fa-exclamation-triangle"></i>
            </div>
            <h1>404 - Page Not Found</h1>
            <p>The page you're looking for doesn't exist or has been moved.</p>
            <div class="error-actions">
                <a href="index.php" class="btn btn-primary">
                    <i class="fas fa-home"></i>
                    Go Home
                </a>
                <a href="index.php?page=products" class="btn btn-outline">
                    <i class="fas fa-shopping-cart"></i>
                    Browse Products
                </a>
            </div>
        </div>
    </div>
</div>

<style>
.error-page {
    min-height: 60vh;
    display: flex;
    align-items: center;
    justify-content: center;
    text-align: center;
    padding: 2rem 0;
}

.error-content {
    max-width: 500px;
}

.error-icon {
    font-size: 4rem;
    color: #e74c3c;
    margin-bottom: 1rem;
}

.error-page h1 {
    font-size: 2.5rem;
    color: #2c3e50;
    margin-bottom: 1rem;
}

.error-page p {
    font-size: 1.1rem;
    color: #7f8c8d;
    margin-bottom: 2rem;
}

.error-actions {
    display: flex;
    gap: 1rem;
    justify-content: center;
    flex-wrap: wrap;
}

.error-actions .btn {
    min-width: 150px;
}
</style>
