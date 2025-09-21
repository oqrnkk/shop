<?php
// Get featured products
$conn = getDatabaseConnection();
$featuredProducts = [];

if ($conn) {
    try {
        $stmt = $conn->prepare("
            SELECT p.* 
            FROM products p 
            WHERE p.is_featured = 1 AND p.is_active = 1 
            ORDER BY p.sort_order ASC, p.created_at DESC 
            LIMIT 6
        ");
        $stmt->execute();
        $featuredProducts = $stmt->fetchAll();
    } catch (Exception $e) {
        // Log error or handle gracefully
        error_log("Database error in home.php: " . $e->getMessage());
        $featuredProducts = [];
    }
}


?>



<!-- Featured Games Section -->
<section class="featured-games">
    <div class="container">
        <div class="section-header">
            <h2>Featured Games</h2>
            <p>Popular games with premium cheats available</p>
        </div>
        
        <div class="games-grid">
            <div class="game-card">
                <div class="game-image">
                    <img src="https://images.unsplash.com/photo-1542751371-adc38448a05e?w=300&h=200&fit=crop&crop=center" alt="Counter-Strike 2">
                </div>
                <div class="game-info">
                    <h3>Counter-Strike 2</h3>
                    <p>Premium aimbot and wallhacks</p>
                    <a href="index.php?page=products" class="btn btn-outline">
                        <i class="fas fa-gamepad"></i>
                        View Cheats
                    </a>
                </div>
            </div>
            
            <div class="game-card">
                <div class="game-image">
                    <img src="https://images.unsplash.com/photo-1614728894747-a83421e2b9c9?w=300&h=200&fit=crop&crop=center" alt="Valorant">
                </div>
                <div class="game-info">
                    <h3>Valorant</h3>
                    <p>Undetected ESP and triggerbot</p>
                    <a href="index.php?page=products" class="btn btn-outline">
                        <i class="fas fa-gamepad"></i>
                        View Cheats
                    </a>
                </div>
            </div>
            
            <div class="game-card">
                <div class="game-image">
                    <img src="https://images.unsplash.com/photo-1578662996442-48f60103fc96?w=300&h=200&fit=crop&crop=center" alt="Fortnite">
                </div>
                <div class="game-info">
                    <h3>Fortnite</h3>
                    <p>Advanced building and aim assistance</p>
                    <a href="index.php?page=products" class="btn btn-outline">
                        <i class="fas fa-gamepad"></i>
                        View Cheats
                    </a>
                </div>
            </div>
            
            <div class="game-card">
                <div class="game-image">
                    <img src="https://images.unsplash.com/photo-1542751371-adc38448a05e?w=300&h=200&fit=crop&crop=center" alt="Apex Legends">
                </div>
                <div class="game-info">
                    <h3>Apex Legends</h3>
                    <p>Legendary aimbot and radar</p>
                    <a href="index.php?page=products" class="btn btn-outline">
                        <i class="fas fa-gamepad"></i>
                        View Cheats
                    </a>
                </div>
            </div>
            
            <div class="game-card">
                <div class="game-image">
                    <img src="https://images.unsplash.com/photo-1614728894747-a83421e2b9c9?w=300&h=200&fit=crop&crop=center" alt="Call of Duty">
                </div>
                <div class="game-info">
                    <h3>Call of Duty</h3>
                    <p>Professional-grade cheats</p>
                    <a href="index.php?page=products" class="btn btn-outline">
                        <i class="fas fa-gamepad"></i>
                        View Cheats
                    </a>
                </div>
            </div>
            
            <div class="game-card">
                <div class="game-image">
                    <img src="https://images.unsplash.com/photo-1578662996442-48f60103fc96?w=300&h=200&fit=crop&crop=center" alt="Rainbow Six Siege">
                </div>
                <div class="game-info">
                    <h3>Rainbow Six Siege</h3>
                    <p>Tactical advantage tools</p>
                    <a href="index.php?page=products" class="btn btn-outline">
                        <i class="fas fa-gamepad"></i>
                        View Cheats
                    </a>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Testimonials Section -->
<section class="testimonials">
    <div class="container">
        <div class="section-header">
            <h2>What Our Customers Say</h2>
            <p>Join thousands of satisfied customers</p>
        </div>
        
        <div class="testimonials-grid">
            <div class="testimonial-card">
                <div class="testimonial-content">
                    <p>"Amazing cheat! Been using it for months without any issues. The support team is incredibly helpful."</p>
                </div>
                <div class="testimonial-author">
                    <div class="author-avatar">
                        <i class="fas fa-user"></i>
                    </div>
                    <div class="author-info">
                        <h4>John D.</h4>
                        <span>CS2 Player</span>
                    </div>
                </div>
            </div>
            
            <div class="testimonial-card">
                <div class="testimonial-content">
                    <p>"Best Valorant cheat I've ever used. Undetected and performs flawlessly. Highly recommended!"</p>
                </div>
                <div class="testimonial-author">
                    <div class="author-avatar">
                        <i class="fas fa-user"></i>
                    </div>
                    <div class="author-info">
                        <h4>Sarah M.</h4>
                        <span>Valorant Player</span>
                    </div>
                </div>
            </div>
            
            <div class="testimonial-card">
                <div class="testimonial-content">
                    <p>"The Fortnite cheat is incredible. Building assistance and aimbot work perfectly together."</p>
                </div>
                <div class="testimonial-author">
                    <div class="author-avatar">
                        <i class="fas fa-user"></i>
                    </div>
                    <div class="author-info">
                        <h4>Mike R.</h4>
                        <span>Fortnite Player</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- CTA Section -->
<section class="cta">
    <div class="container">
        <div class="cta-content">
            <h2>Ready to Dominate?</h2>
            <p>Join thousands of players who have already gained the competitive edge</p>
            <div class="cta-buttons">
                <a href="index.php?page=register" class="btn btn-primary">
                    <i class="fas fa-user-plus"></i>
                    Get Started
                </a>
                <a href="index.php?page=products" class="btn btn-outline">
                    <i class="fas fa-gamepad"></i>
                    Browse Cheats
                </a>
            </div>
        </div>
    </div>
</section>

<style>
/* Featured Games Section */
.featured-games {
    padding: 4rem 0;
    background: rgba(0, 0, 0, 0.9);
}

.games-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 2rem;
}

.game-card {
    background: rgba(255, 255, 255, 0.05);
    border: 1px solid rgba(255, 255, 255, 0.1);
    border-radius: 16px;
    overflow: hidden;
    backdrop-filter: blur(10px);
    transition: all 0.3s ease;
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
}

.game-card:hover {
    transform: translateY(-5px);
    border-color: rgba(255, 105, 180, 0.3);
    box-shadow: 0 15px 40px rgba(255, 105, 180, 0.2);
}

.game-image {
    width: 100%;
    height: 200px;
    overflow: hidden;
    position: relative;
}

.game-image img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    transition: transform 0.3s ease;
}

.game-card:hover .game-image img {
    transform: scale(1.05);
}

.game-info {
    padding: 1.5rem;
    text-align: center;
}

.game-info h3 {
    margin-bottom: 0.5rem;
    color: #ffffff;
    font-size: 1.3rem;
    font-weight: 600;
}

.game-info p {
    color: rgba(255, 255, 255, 0.7);
    margin-bottom: 1.5rem;
    line-height: 1.5;
}

.btn-outline {
    background: transparent;
    color: rgba(255, 255, 255, 0.9);
    border: 2px solid rgba(255, 255, 255, 0.6);
    backdrop-filter: blur(10px);
}

.btn-outline:hover {
    background: rgba(255, 105, 180, 0.1);
    border-color: #ff69b4;
    color: #ffffff;
    transform: translateY(-2px);
}

/* Featured Products */
.featured-products {
    padding: 4rem 0;
}

.section-header {
    text-align: center;
    margin-bottom: 3rem;
}

.section-header h2 {
    font-size: 2.5rem;
    font-weight: 700;
    margin-bottom: 1rem;
    color: white;
}

.section-header p {
    font-size: 1.1rem;
    color: rgba(255, 255, 255, 0.8);
}



.section-footer {
    text-align: center;
    margin-top: 3rem;
}



/* Testimonials */
.testimonials {
    padding: 4rem 0;
}

.testimonials-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 2rem;
}

.testimonial-card {
    background: white;
    border-radius: 12px;
    padding: 2rem;
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
}

.testimonial-content {
    margin-bottom: 1.5rem;
}

.testimonial-content p {
    font-style: italic;
    color: #666;
    line-height: 1.6;
}

.testimonial-author {
    display: flex;
    align-items: center;
    gap: 1rem;
}

.author-avatar {
    width: 50px;
    height: 50px;
    background: #667eea;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
}

.author-info h4 {
    margin-bottom: 0.25rem;
    color: #333;
}

.author-info span {
    color: #666;
    font-size: 0.9rem;
}

/* CTA Section */
.cta {
    padding: 4rem 0;
    background: rgba(0, 0, 0, 0.8);
    text-align: center;
}

.cta-content h2 {
    font-size: 2.5rem;
    font-weight: 700;
    margin-bottom: 1rem;
    color: white;
}

.cta-content p {
    font-size: 1.1rem;
    color: rgba(255, 255, 255, 0.8);
    margin-bottom: 2rem;
}

.cta-buttons {
    display: flex;
    gap: 1rem;
    justify-content: center;
    flex-wrap: wrap;
}

@media (max-width: 768px) {
    .games-grid {
        grid-template-columns: 1fr;
    }
    
    .testimonials-grid {
        grid-template-columns: 1fr;
    }
    
    .cta-buttons {
        flex-direction: column;
        align-items: center;
    }
}
</style>
