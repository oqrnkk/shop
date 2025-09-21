// Product page JavaScript for CheatStore

document.addEventListener('DOMContentLoaded', function() {
    // Product image gallery
    const productImages = document.querySelectorAll('.product-image');
    const mainImage = document.querySelector('.product-main-image');
    
    if (productImages.length > 0 && mainImage) {
        productImages.forEach(image => {
            image.addEventListener('click', function() {
                // Remove active class from all images
                productImages.forEach(img => img.classList.remove('active'));
                
                // Add active class to clicked image
                this.classList.add('active');
                
                // Update main image
                mainImage.src = this.src;
                mainImage.alt = this.alt;
            });
        });
    }
    
    // Quantity selector
    const quantityInput = document.querySelector('.quantity-input');
    const quantityMinus = document.querySelector('.quantity-minus');
    const quantityPlus = document.querySelector('.quantity-plus');
    
    if (quantityInput && quantityMinus && quantityPlus) {
        quantityMinus.addEventListener('click', function() {
            const currentValue = parseInt(quantityInput.value);
            if (currentValue > 1) {
                quantityInput.value = currentValue - 1;
                updatePrice();
            }
        });
        
        quantityPlus.addEventListener('click', function() {
            const currentValue = parseInt(quantityInput.value);
            const maxValue = parseInt(quantityInput.getAttribute('max')) || 10;
            if (currentValue < maxValue) {
                quantityInput.value = currentValue + 1;
                updatePrice();
            }
        });
        
        quantityInput.addEventListener('change', function() {
            updatePrice();
        });
    }
    
    // Duration selector
    const durationButtons = document.querySelectorAll('.duration-option');
    const priceDisplay = document.querySelector('.product-price');
    
    if (durationButtons.length > 0 && priceDisplay) {
        durationButtons.forEach(button => {
            button.addEventListener('click', function() {
                // Remove active class from all buttons
                durationButtons.forEach(btn => btn.classList.remove('active'));
                
                // Add active class to clicked button
                this.classList.add('active');
                
                // Update price
                updatePrice();
            });
        });
    }
    
    // Price update function
    function updatePrice() {
        const activeDuration = document.querySelector('.duration-option.active');
        const quantity = parseInt(quantityInput?.value) || 1;
        
        if (activeDuration) {
            const basePrice = parseFloat(activeDuration.getAttribute('data-price'));
            const totalPrice = basePrice * quantity;
            
            if (priceDisplay) {
                priceDisplay.textContent = `$${totalPrice.toFixed(2)}`;
            }
            
            // Update purchase button
            const purchaseButton = document.querySelector('.btn-purchase');
            if (purchaseButton) {
                purchaseButton.textContent = `Purchase Now - $${totalPrice.toFixed(2)}`;
            }
        }
    }
    
    // Feature accordion
    const featureItems = document.querySelectorAll('.feature-item');
    
    featureItems.forEach(item => {
        const header = item.querySelector('.feature-header');
        const content = item.querySelector('.feature-content');
        
        if (header && content) {
            header.addEventListener('click', function() {
                const isActive = item.classList.contains('active');
                
                // Close all other items
                featureItems.forEach(feature => {
                    feature.classList.remove('active');
                    const featureContent = feature.querySelector('.feature-content');
                    if (featureContent) {
                        featureContent.style.maxHeight = null;
                    }
                });
                
                // Toggle current item
                if (!isActive) {
                    item.classList.add('active');
                    content.style.maxHeight = content.scrollHeight + 'px';
                }
            });
        }
    });
    
    // Copy license key
    const copyButtons = document.querySelectorAll('.copy-key');
    
    copyButtons.forEach(button => {
        button.addEventListener('click', function() {
            const keyElement = this.closest('.license-key-item').querySelector('.key-value');
            const key = keyElement.textContent;
            
            navigator.clipboard.writeText(key).then(() => {
                // Show success message
                const originalText = this.textContent;
                this.textContent = 'Copied!';
                this.classList.add('copied');
                
                setTimeout(() => {
                    this.textContent = originalText;
                    this.classList.remove('copied');
                }, 2000);
            }).catch(() => {
                // Fallback for older browsers
                const textArea = document.createElement('textarea');
                textArea.value = key;
                document.body.appendChild(textArea);
                textArea.select();
                document.execCommand('copy');
                document.body.removeChild(textArea);
                
                // Show success message
                const originalText = this.textContent;
                this.textContent = 'Copied!';
                this.classList.add('copied');
                
                setTimeout(() => {
                    this.textContent = originalText;
                    this.classList.remove('copied');
                }, 2000);
            });
        });
    });
    
    // Product reviews
    const reviewForm = document.querySelector('.review-form');
    const ratingStars = document.querySelectorAll('.rating-star');
    
    if (reviewForm && ratingStars.length > 0) {
        let selectedRating = 0;
        
        ratingStars.forEach((star, index) => {
            star.addEventListener('click', function() {
                selectedRating = index + 1;
                
                // Update star display
                ratingStars.forEach((s, i) => {
                    if (i < selectedRating) {
                        s.classList.add('active');
                    } else {
                        s.classList.remove('active');
                    }
                });
                
                // Update hidden input
                const ratingInput = document.querySelector('input[name="rating"]');
                if (ratingInput) {
                    ratingInput.value = selectedRating;
                }
            });
            
            star.addEventListener('mouseenter', function() {
                const hoverRating = index + 1;
                ratingStars.forEach((s, i) => {
                    if (i < hoverRating) {
                        s.classList.add('hover');
                    } else {
                        s.classList.remove('hover');
                    }
                });
            });
            
            star.addEventListener('mouseleave', function() {
                ratingStars.forEach(s => s.classList.remove('hover'));
            });
        });
    }
    
    // Smooth scroll to sections
    const navLinks = document.querySelectorAll('.product-nav a');
    
    navLinks.forEach(link => {
        link.addEventListener('click', function(e) {
            e.preventDefault();
            const targetId = this.getAttribute('href').substring(1);
            const targetSection = document.getElementById(targetId);
            
            if (targetSection) {
                targetSection.scrollIntoView({
                    behavior: 'smooth',
                    block: 'start'
                });
                
                // Update active nav link
                navLinks.forEach(l => l.classList.remove('active'));
                this.classList.add('active');
            }
        });
    });
    
    // Lazy load product images
    const productImageObserver = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                const img = entry.target;
                img.src = img.dataset.src;
                img.classList.remove('lazy');
                productImageObserver.unobserve(img);
            }
        });
    });
    
    const lazyImages = document.querySelectorAll('img[data-src]');
    lazyImages.forEach(img => productImageObserver.observe(img));
});

// CSS for product page
const productStyles = `
<style>
/* Product image gallery */
.product-image {
    cursor: pointer;
    transition: opacity 0.3s;
}

.product-image:hover {
    opacity: 0.8;
}

.product-image.active {
    border: 2px solid #007bff;
}

/* Quantity selector */
.quantity-selector {
    display: flex;
    align-items: center;
    gap: 10px;
    margin: 1rem 0;
}

.quantity-minus,
.quantity-plus {
    width: 40px;
    height: 40px;
    border: 1px solid #ddd;
    background: white;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 18px;
    border-radius: 4px;
}

.quantity-minus:hover,
.quantity-plus:hover {
    background: #f8f9fa;
}

.quantity-input {
    width: 60px;
    height: 40px;
    text-align: center;
    border: 1px solid #ddd;
    border-radius: 4px;
}

/* Duration selector */
.duration-option {
    padding: 15px;
    border: 2px solid #ddd;
    border-radius: 8px;
    cursor: pointer;
    transition: all 0.3s;
    text-align: center;
}

.duration-option:hover {
    border-color: #007bff;
}

.duration-option.active {
    border-color: #007bff;
    background: #f8f9ff;
}

/* Feature accordion */
.feature-header {
    padding: 15px;
    background: #f8f9fa;
    border: 1px solid #ddd;
    cursor: pointer;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.feature-header:hover {
    background: #e9ecef;
}

.feature-content {
    max-height: 0;
    overflow: hidden;
    transition: max-height 0.3s ease;
    border: 1px solid #ddd;
    border-top: none;
}

.feature-item.active .feature-content {
    max-height: 200px;
}

.feature-content > div {
    padding: 15px;
}

/* Copy button */
.copy-key {
    padding: 5px 10px;
    background: #007bff;
    color: white;
    border: none;
    border-radius: 4px;
    cursor: pointer;
    font-size: 12px;
    transition: background 0.3s;
}

.copy-key:hover {
    background: #0056b3;
}

.copy-key.copied {
    background: #28a745;
}

/* Rating stars */
.rating-star {
    cursor: pointer;
    color: #ddd;
    font-size: 20px;
    transition: color 0.3s;
}

.rating-star.active,
.rating-star.hover {
    color: #ffc107;
}

/* Product navigation */
.product-nav {
    position: sticky;
    top: 0;
    background: white;
    border-bottom: 1px solid #ddd;
    z-index: 100;
}

.product-nav a {
    padding: 15px 20px;
    text-decoration: none;
    color: #666;
    border-bottom: 2px solid transparent;
    transition: all 0.3s;
}

.product-nav a:hover,
.product-nav a.active {
    color: #007bff;
    border-bottom-color: #007bff;
}

/* Lazy loading */
img.lazy {
    opacity: 0;
    transition: opacity 0.3s;
}

img.lazy.loaded {
    opacity: 1;
}
</style>
`;

// Inject product styles
document.head.insertAdjacentHTML('beforeend', productStyles);
