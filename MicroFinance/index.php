<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MicroFinance Banking System - Home</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
</head>

<body>
    <header>
        <div class="container" style="display: flex; justify-content: space-between; align-items: center; max-width: 1400px;">
            <div class="logo">
                MicroFinance <span>Banking</span>
            </div>
            <nav>
                <a href="index.php">Home</a>
                <a href="about.php">About</a>
                <a href="login.php">Login</a>
                <a href="register.php" class="btn btn-primary btn-small">Get Started</a>
            </nav>
        </div>
    </header>

    <section class="hero-section">
        <div class="container">
            <h2 class="animate-fade">Welcome to <span class="highlight">MicroCredit</span> Bank</h2>
            <p class="animate-fade" style="animation-delay: 0.1s;">Empowering your financial future with accessible micro-loans and modern banking services designed for you.</p>

            <div style="margin-top: 40px; display: flex; gap: 16px; justify-content: center; flex-wrap: wrap;" class="animate-fade" style="animation-delay: 0.2s;">
                <a href="register.php" class="btn btn-primary btn-lg" style="border-radius: 50px;">
                    <i class="fas fa-rocket"></i> Get Started Today
                </a>
                <a href="about.php" class="btn btn-outline btn-lg" style="border-radius: 50px; border-color: rgba(255,255,255,0.3); color: white;">
                    <i class="fas fa-play-circle"></i> Learn More
                </a>
            </div>

            <!-- Trust Indicators -->
            <div style="margin-top: 60px; display: flex; justify-content: center; gap: 48px; flex-wrap: wrap; opacity: 0.8;" class="animate-fade" style="animation-delay: 0.3s;">
                <div style="text-align: center;">
                    <div style="font-size: 2rem; font-weight: 800;">50K+</div>
                    <div style="font-size: 0.9rem; opacity: 0.8;">Happy Clients</div>
                </div>
                <div style="text-align: center;">
                    <div style="font-size: 2rem; font-weight: 800;">PKR 120M</div>
                    <div style="font-size: 0.9rem; opacity: 0.8;">Loans Disbursed</div>
                </div>
                <div style="text-align: center;">
                    <div style="font-size: 2rem; font-weight: 800;">98%</div>
                    <div style="font-size: 0.9rem; opacity: 0.8;">Success Rate</div>
                </div>
            </div>
        </div>
    </section>

    <section class="features-container container">
        <div style="text-align: center; margin-bottom: 48px;">
            <h2 style="font-size: 2.25rem;">Why Choose Us?</h2>
            <p style="color: var(--text-muted); max-width: 600px; margin: 0 auto;">Discover the features that make MicroCredit Bank the trusted choice for thousands of customers.</p>
        </div>
        
        <div class="dashboard-grid">
            <div class="card animate-fade">
                <div class="feature-icon">💼</div>
                <h3>Easy Loans</h3>
                <p>Apply for small business or personal loans with minimal documentation and get approval within 24 hours.</p>
            </div>
            <div class="card animate-fade">
                <div class="feature-icon">🛡️</div>
                <h3>Secure Banking</h3>
                <p>Your data and transactions are protected with bank-grade encryption and security protocols.</p>
            </div>
            <div class="card animate-fade">
                <div class="feature-icon">🌐</div>
                <h3>24/7 Access</h3>
                <p>Manage your account from anywhere, anytime using our modern online dashboard and mobile app.</p>
            </div>
        </div>
    </section>

    <!-- How It Works Section -->
    <section style="padding: 80px 24px; background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%); margin-top: 60px;">
        <div class="container" style="text-align: center;">
            <h2 style="color: white; font-size: 2.25rem; margin-bottom: 16px;">How It Works</h2>
            <p style="color: rgba(255,255,255,0.7); max-width: 500px; margin: 0 auto 48px;">Get your loan approved in three simple steps</p>
            
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 32px; max-width: 1000px; margin: 0 auto;">
                <div style="text-align: center; padding: 32px;">
                    <div style="width: 80px; height: 80px; background: var(--primary-gradient); border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 24px; font-size: 2rem; color: white; box-shadow: 0 10px 30px rgba(102, 126, 234, 0.4);">
                        <i class="fas fa-user-plus"></i>
                    </div>
                    <h4 style="color: white; margin-bottom: 12px;">1. Create Account</h4>
                    <p style="color: rgba(255,255,255,0.7); font-size: 0.95rem;">Sign up in minutes with just your basic details and CNIC.</p>
                </div>
                <div style="text-align: center; padding: 32px;">
                    <div style="width: 80px; height: 80px; background: var(--secondary-gradient); border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 24px; font-size: 2rem; color: white; box-shadow: 0 10px 30px rgba(17, 153, 142, 0.4);">
                        <i class="fas fa-file-invoice"></i>
                    </div>
                    <h4 style="color: white; margin-bottom: 12px;">2. Apply for Loan</h4>
                    <p style="color: rgba(255,255,255,0.7); font-size: 0.95rem;">Fill out a simple application form and submit your request.</p>
                </div>
                <div style="text-align: center; padding: 32px;">
                    <div style="width: 80px; height: 80px; background: linear-gradient(135deg, #f59e0b 0%, #fbbf24 100%); border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 24px; font-size: 2rem; color: white; box-shadow: 0 10px 30px rgba(245, 158, 11, 0.4);">
                        <i class="fas fa-money-bill-wave"></i>
                    </div>
                    <h4 style="color: white; margin-bottom: 12px;">3. Get Funded</h4>
                    <p style="color: rgba(255,255,255,0.7); font-size: 0.95rem;">Receive your funds directly after quick approval process.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- CTA Section -->
    <section style="padding: 100px 24px; text-align: center;">
        <div class="container">
            <h2 style="font-size: 2.5rem; margin-bottom: 16px;">Ready to Get Started?</h2>
            <p style="color: var(--text-muted); max-width: 500px; margin: 0 auto 32px; font-size: 1.1rem;">Join thousands of satisfied customers who trust MicroCredit Bank with their financial needs.</p>
            <a href="register.php" class="btn btn-primary btn-lg" style="border-radius: 50px; padding: 18px 48px;">
                <i class="fas fa-arrow-right"></i> Create Free Account
            </a>
        </div>
    </section>

    <?php include('includes/footer.php'); ?>
</body>

</html>
