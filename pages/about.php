<?php
// About page content
?>

<div class="container">
    <div class="about-header">
        <h1>About CheatStore</h1>
        <p>Premium gaming cheats and hacks for competitive advantage</p>
    </div>

    <div class="about-content">
        <!-- Mission Section -->
        <div class="about-section">
            <div class="section-icon">
                <i class="fas fa-bullseye"></i>
            </div>
            <h2>Our Mission</h2>
            <p>At CheatStore, we provide high-quality, undetected gaming cheats and hacks that give you the competitive edge you need. Our products are designed with the latest anti-detection technology to ensure your gaming experience remains uninterrupted.</p>
        </div>

        <!-- Features Section -->
        <div class="about-section">
            <div class="section-icon">
                <i class="fas fa-star"></i>
            </div>
            <h2>Why Choose CheatStore?</h2>
            <div class="features-grid">
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-shield-alt"></i>
                    </div>
                    <h3>Undetected</h3>
                    <p>Our cheats use advanced anti-detection technology to keep you safe from bans.</p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-sync-alt"></i>
                    </div>
                    <h3>Regular Updates</h3>
                    <p>We constantly update our cheats to stay ahead of game updates and anti-cheat systems.</p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-headset"></i>
                    </div>
                    <h3>24/7 Support</h3>
                    <p>Our dedicated support team is available around the clock to help you with any issues.</p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-rocket"></i>
                    </div>
                    <h3>Instant Delivery</h3>
                    <p>Get your license keys instantly after payment confirmation.</p>
                </div>
            </div>
        </div>

        <!-- Games Section -->
        <div class="about-section">
            <div class="section-icon">
                <i class="fas fa-gamepad"></i>
            </div>
            <h2>Supported Games</h2>
            <p>We offer premium cheats for the most popular competitive games:</p>
            <div class="games-grid">
                <div class="game-card">
                    <div class="game-icon">
                        <i class="fas fa-crosshairs"></i>
                    </div>
                    <h3>Counter-Strike 2</h3>
                    <p>Advanced aimbot, wallhack, and more features for CS2.</p>
                </div>
                <div class="game-card">
                    <div class="game-icon">
                        <i class="fas fa-bullseye"></i>
                    </div>
                    <h3>Valorant</h3>
                    <p>Undetected Valorant cheats with premium features.</p>
                </div>
                <div class="game-card">
                    <div class="game-icon">
                        <i class="fas fa-fort-awesome"></i>
                    </div>
                    <h3>Fortnite</h3>
                    <p>Building assistance and aimbot for Fortnite.</p>
                </div>
                <div class="game-card">
                    <div class="game-icon">
                        <i class="fas fa-car"></i>
                    </div>
                    <h3>PUBG</h3>
                    <p>Complete PUBG hack suite with ESP and vehicle hacks.</p>
                </div>
            </div>
        </div>

        <!-- Team Section -->
        <div class="about-section">
            <div class="section-icon">
                <i class="fas fa-users"></i>
            </div>
            <h2>Our Team</h2>
            <p>We are a team of experienced developers and gaming enthusiasts dedicated to providing the best cheating experience. Our expertise in reverse engineering and game development allows us to create high-quality, undetected cheats.</p>
        </div>

        <!-- Contact Section -->
        <div class="about-section">
            <div class="section-icon">
                <i class="fas fa-envelope"></i>
            </div>
            <h2>Get in Touch</h2>
            <p>Have questions or need support? We're here to help!</p>
            <div class="contact-info">
                <div class="contact-item">
                    <i class="fas fa-envelope"></i>
                    <span>support@cheatstore.net</span>
                </div>
                <div class="contact-item">
                    <i class="fab fa-discord"></i>
                    <span>Discord: CheatStore#1234</span>
                </div>
                <div class="contact-item">
                    <i class="fab fa-telegram"></i>
                    <span>Telegram: @CheatStore</span>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.about-header {
    text-align: center;
    margin-bottom: 3rem;
    padding-top: 1rem;
}

.about-header h1 {
    color: #ffffff;
    margin-bottom: 0.5rem;
    font-size: 2.5rem;
    font-weight: 700;
}

.about-header p {
    color: rgba(255, 255, 255, 0.7);
    font-size: 1.125rem;
}

.about-content {
    max-width: 1000px;
    margin: 0 auto;
}

.about-section {
    background: rgba(255, 255, 255, 0.05);
    border: 1px solid rgba(255, 255, 255, 0.1);
    border-radius: 16px;
    padding: 2.5rem;
    margin-bottom: 2rem;
    backdrop-filter: blur(10px);
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
    text-align: center;
    position: relative;
    overflow: hidden;
}

.about-section::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 1px;
    background: linear-gradient(90deg, transparent, #ff69b4, transparent);
}

.section-icon {
    font-size: 3rem;
    color: #ff69b4;
    margin-bottom: 1rem;
    filter: drop-shadow(0 0 8px rgba(255, 105, 180, 0.6));
}

.about-section h2 {
    color: #ffffff;
    margin-bottom: 1rem;
    font-size: 1.75rem;
    font-weight: 600;
}

.about-section p {
    color: rgba(255, 255, 255, 0.7);
    line-height: 1.6;
    margin-bottom: 1.5rem;
}

.features-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 1.5rem;
    margin-top: 2rem;
}

.feature-card {
    padding: 1.5rem;
    background: rgba(255, 255, 255, 0.03);
    border: 1px solid rgba(255, 255, 255, 0.1);
    border-radius: 12px;
    transition: all 0.3s ease;
    backdrop-filter: blur(10px);
}

.feature-card:hover {
    transform: translateY(-4px);
    border-color: rgba(255, 105, 180, 0.3);
    box-shadow: 0 20px 40px rgba(0, 0, 0, 0.3);
}

.feature-icon {
    font-size: 2rem;
    color: #ff69b4;
    margin-bottom: 1rem;
    filter: drop-shadow(0 0 8px rgba(255, 105, 180, 0.6));
}

.feature-card h3 {
    color: #ffffff;
    margin-bottom: 0.5rem;
    font-weight: 600;
}

.feature-card p {
    color: rgba(255, 255, 255, 0.7);
    font-size: 0.875rem;
    margin: 0;
}

.games-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 1.5rem;
    margin-top: 2rem;
}

.game-card {
    padding: 1.5rem;
    background: rgba(255, 255, 255, 0.03);
    border: 1px solid rgba(255, 255, 255, 0.1);
    border-radius: 12px;
    transition: all 0.3s ease;
    backdrop-filter: blur(10px);
}

.game-card:hover {
    transform: translateY(-4px);
    border-color: rgba(255, 105, 180, 0.3);
    box-shadow: 0 20px 40px rgba(0, 0, 0, 0.3);
}

.game-icon {
    font-size: 2rem;
    color: #ff69b4;
    margin-bottom: 1rem;
    filter: drop-shadow(0 0 8px rgba(255, 105, 180, 0.6));
}

.game-card h3 {
    color: #ffffff;
    margin-bottom: 0.5rem;
    font-weight: 600;
}

.game-card p {
    color: rgba(255, 255, 255, 0.7);
    font-size: 0.875rem;
    margin: 0;
}

.contact-info {
    display: flex;
    flex-direction: column;
    gap: 1rem;
    margin-top: 1.5rem;
}

.contact-item {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    padding: 1rem;
    background: rgba(255, 255, 255, 0.05);
    border: 1px solid rgba(255, 255, 255, 0.1);
    border-radius: 12px;
    backdrop-filter: blur(10px);
    transition: all 0.3s ease;
}

.contact-item:hover {
    border-color: rgba(255, 105, 180, 0.3);
    transform: translateY(-2px);
}

.contact-item i {
    color: #ff69b4;
    font-size: 1.25rem;
    width: 20px;
    filter: drop-shadow(0 0 8px rgba(255, 105, 180, 0.6));
}

.contact-item span {
    color: #ffffff;
    font-weight: 500;
}

@media (max-width: 768px) {
    .about-header h1 {
        font-size: 2rem;
    }
    
    .about-section {
        padding: 1.5rem;
    }
    
    .features-grid,
    .games-grid {
        grid-template-columns: 1fr;
    }
    
    .contact-info {
        align-items: center;
    }
}
</style>
