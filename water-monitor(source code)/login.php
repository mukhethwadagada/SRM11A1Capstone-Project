<?php
require_once 'includes/config.php';
require_once 'includes/auth.php';

// Redirect if already logged in
if (is_logged_in()) {
    $redirect = is_admin() ? 'admin/dashboard.php' : 'dashboard.php';
    header("Location: $redirect");
    exit();
}

// Default admin credentials
$default_admin_email = "admin@joburgwatersaver.co.za";
$default_admin_password = "Admin@1234";

// Process login form
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login'])) {
    $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
    $password = $_POST['password'] ?? '';
    $remember = isset($_POST['remember']);

    if (empty($email) || empty($password)) {
        $login_error = "Email and password are required";
    } else {
        try {
            // Check for default admin login
            if ($email === $default_admin_email && $password === $default_admin_password) {
                $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
                $stmt->execute([$email]);
                $user = $stmt->fetch();
                
                
                if (!$user) {
                    // Create default admin if doesn't exist
                    $hashed_password = password_hash($default_admin_password, PASSWORD_DEFAULT);
                    $pdo->prepare("INSERT INTO users (email, password_hash, name, is_admin) VALUES (?, ?, 'System Admin', 1)")
                       ->execute([$default_admin_email, $hashed_password]);
                    $user = ['id' => $pdo->lastInsertId(), 'email' => $default_admin_email, 'is_admin' => 1];
                }
            } else {
                $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
                $stmt->execute([$email]);
                $user = $stmt->fetch();
            }

            if ($user && ($email === $default_admin_email || password_verify($password, $user['password_hash']))) {
                // Regenerate session ID
                session_regenerate_id(true);
                
                // Set session variables
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['email'] = $user['email'];
                $_SESSION['is_admin'] = (bool)$user['is_admin'];
                $_SESSION['ip_address'] = $_SERVER['REMOTE_ADDR'];
                $_SESSION['user_agent'] = $_SERVER['HTTP_USER_AGENT'];
                
                // Redirect to appropriate dashboard
                $redirect = $_SESSION['is_admin'] ? 'admin/dashboard.php' : 'dashboard.php';
                header("Location: $redirect");
                exit();
            } else {
                $login_error = "Invalid email or password";
            }
        } catch (PDOException $e) {
            error_log("Login error: " . $e->getMessage());
            $login_error = "System error. Please try again later.";
        }
    }
}

include 'includes/header.php';
?>

<!-- Hero Section with Water Wave Animation -->
<div class="hero-section" style="background: linear-gradient(135deg, #0077b6 0%, #00b4d8 100%); padding: 140px 0; margin-bottom: 30px; position: relative; overflow: hidden;">
    <div class="water-wave" style="position: absolute; bottom: 0; left: 0; right: 0; height: 100px; background: url('data:image/svg+xml;utf8,<svg viewBox=\"0 0 1200 120\" xmlns=\"http://www.w3.org/2000/svg\" preserveAspectRatio=\"none\"><path d=\"M0,0V46.29c47.79,22.2,103.59,32.17,158,28,70.36-5.37,136.33-33.31,206.8-37.5C438.64,32.43,512.34,53.67,583,72.05c69.27,18,138.3,24.88,209.4,13.08,36.15-6,69.85-17.84,104.45-29.34C989.49,25,1113-14.29,1200,52.47V0Z\" fill=\"%23ffffff\" opacity=\".25\"/><path d=\"M0,0V15.81C13,36.92,27.64,56.86,47.69,72.05,99.41,111.27,165,111,224.58,91.58c31.15-10.15,60.09-26.07,89.67-39.8,40.92-19,84.73-46,130.83-49.67,36.26-2.85,70.9,9.42,98.6,31.56,31.77,25.39,62.32,62,103.63,73,40.44,10.79,81.35-6.69,119.13-24.28s75.16-39,116.92-43.05c59.73-5.85,113.28,22.88,168.9,38.84,30.2,8.66,59,6.17,87.09-7.5,22.43-10.89,48-26.93,60.65-49.24V0Z\" fill=\"%23ffffff\" opacity=\".5\"/><path d=\"M0,0V5.63C149.93,59,314.09,71.32,475.83,42.57c43-7.64,84.23-20.12,127.61-26.46,59-8.63,112.48,12.24,165.56,35.4C827.93,77.22,886,95.24,951.2,90c86.53-7,172.46-45.71,248.8-84.81V0Z\" fill=\"%23ffffff\"/></svg>') repeat-x; animation: wave 10s linear infinite;"></div>
    <div class="container text-center text-white position-relative" style="z-index: 1;">
        <h1 class="display-4 font-weight-bold mb-3"><i class="fas fa-water" style="text-shadow: 0 0 10px rgba(255,255,255,0.5);"></i> JOBURG WATER SAVER</h1>
        <p class="lead" style="font-size: 1.5rem;">Smart Water Conservation & Rainwater Harvesting Solutions</p>
        <a href="register.php" class="btn btn-light btn-lg mt-3 px-4 py-2" style="background: rgba(255,255,255,0.2); border: 2px solid white; border-radius: 30px; font-weight: 600; letter-spacing: 1px;"><i class="fas fa-user-plus mr-2"></i> JOIN OUR MISSION</a>
    </div>
</div>

<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-6 col-lg-5">
            <div class="card shadow-lg border-0" style="border-radius: 15px; overflow: hidden; border-top: 5px solid #00b4d8;">
                <div class="card-header text-white" style="background: linear-gradient(to right, #0077b6, #00b4d8);">
                    <h4 class="mb-0"><i class="fas fa-sign-in-alt mr-2"></i> CONSERVATION PORTAL</h4>
                </div>
                <div class="card-body px-4 py-4">
                <?php if (isset($_SESSION['redirect_message'])): ?>
    <div class="alert alert-info"><?= htmlspecialchars($_SESSION['redirect_message']) ?></div>
    <?php unset($_SESSION['redirect_message']); ?>
<?php endif; ?>

<?php if (isset($_GET['message']) && $_GET['message'] === 'logged_out'): ?>
    <div class="alert alert-success">You have been successfully logged out.</div>
<?php endif; ?>

<?php if (!empty($login_error)): ?>
    <div class="alert alert-danger"><?= htmlspecialchars($login_error) ?></div>
<?php endif; ?>
                    
                    <form method="POST" action="login.php">
                        <div class="mb-4">
                            <label for="email" class="form-label text-muted"><i class="fas fa-envelope mr-2"></i> Email Address</label>
                            <input type="email" class="form-control py-2" id="email" name="email" required value="<?= isset($_POST['email']) ? htmlspecialchars($_POST['email']) : '' ?>" style="border-radius: 8px; border: 1px solid #ced4da;">
                        </div>
                        <div class="mb-4">
                            <label for="password" class="form-label text-muted"><i class="fas fa-lock mr-2"></i> Password</label>
                            <div class="input-group">
                                <input type="password" class="form-control py-2" id="password" name="password" required style="border-radius: 8px 0 0 8px; border: 1px solid #ced4da;">
                                <button class="btn btn-outline-secondary" type="button" id="togglePassword" style="border-radius: 0 8px 8px 0;">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </div>
                        </div>
                        <div class="mb-4 form-check">
                            <input type="checkbox" class="form-check-input" id="remember" name="remember" <?= isset($_POST['remember']) ? 'checked' : '' ?>>
                            <label class="form-check-label text-muted" for="remember">Remember me</label>
                        </div>
                        <button type="submit" name="login" class="btn btn-primary w-100 py-2" style="background: linear-gradient(to right, #0077b6, #00b4d8); border: none; border-radius: 8px; font-weight: 600; letter-spacing: 1px;"><i class="fas fa-sign-in-alt mr-2"></i> LOGIN</button>
                    </form>
                    
                    <div class="mt-4 text-center">
                        <p class="text-muted">New to water conservation? <a href="register.php" style="color: #0077b6; font-weight: 500;">Create account</a></p>
                        <p class="mb-0"><a href="forgot_password.php" style="color: #0077b6; font-weight: 500;"><i class="fas fa-question-circle mr-1"></i> Forgot password?</a></p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Previous code remains the same until the end of the login form section -->
</div>
        </div>
    </div>
</div>

<!-- JoJo Tank Testimonials -->
<div class="container mt-5">
    <h2 class="text-center mb-5" style="color: #0077b6; position: relative;">
        <span style="background: white; padding: 0 20px; position: relative; z-index: 1;">JOJO TANK SUCCESS STORIES</span>
        <div style="position: absolute; top: 50%; left: 0; right: 0; height: 2px; background: linear-gradient(to right, transparent, #00b4d8, transparent); z-index: 0;"></div>
    </h2>
    
    <div class="row">
        <!-- Testimonial 1 -->
        <div class="col-md-4 mb-4">
            <div class="card h-100 border-0 shadow-sm" style="border-radius: 12px; overflow: hidden;">
                <div class="position-relative" style="height: 200px; overflow: hidden;">
                    <img src="https://media.cnn.com/api/v1/images/stellar/prod/201005123244-cape-town-berg-river-dam-before.jpg?c=16x9&q=h_720,w_1280,c_fill" class="card-img-top" alt="JoJo Tank installation" style="height: 100%; object-fit: cover;">
                    <div class="position-absolute top-0 start-0 bg-primary text-white px-3 py-1" style="border-radius: 0 0 12px 0;">Cape Town</div>
                </div>
                <div class="card-body">
                    <div class="d-flex align-items-center mb-3">
                        <div class="rounded-circle overflow-hidden" style="width: 50px; height: 50px;">
                            <img src="https://randomuser.me/api/portraits/women/45.jpg" alt="User" style="width: 100%; height: 100%; object-fit: cover;">
                        </div>
                        <div class="ms-3">
                            <h5 class="mb-0">Sarah K.</h5>
                            <small class="text-muted">Homeowner</small>
                        </div>
                    </div>
                    <p class="card-text">"Our JoJo tank saved us during the drought. We reduced municipal water use by 70% and kept our garden alive with rainwater harvesting!"</p>
                    <div class="text-warning">
                        <i class="fas fa-star"></i>
                        <i class="fas fa-star"></i>
                        <i class="fas fa-star"></i>
                        <i class="fas fa-star"></i>
                        <i class="fas fa-star"></i>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Testimonial 2 -->
        <div class="col-md-4 mb-4">
            <div class="card h-100 border-0 shadow-sm" style="border-radius: 12px; overflow: hidden;">
                <div class="position-relative" style="height: 200px; overflow: hidden;">
                    <img src="https://groundup.org.za/media/uploads/images/photographers/Ashraf%20Hendricks/limpopowater/limpopowater-20230510-6v2a1040hr.jpg" class="card-img-top" alt="JoJo Tank farm" style="height: 100%; object-fit: cover;">
                    <div class="position-absolute top-0 start-0 bg-primary text-white px-3 py-1" style="border-radius: 0 0 12px 0;">Limpopo</div>
                </div>
                <div class="card-body">
                    <div class="d-flex align-items-center mb-3">
                        <div class="rounded-circle overflow-hidden" style="width: 50px; height: 50px;">
                            <img src="https://randomuser.me/api/portraits/men/32.jpg" alt="User" style="width: 100%; height: 100%; object-fit: cover;">
                        </div>
                        <div class="ms-3">
                            <h5 class="mb-0">Thomas B.</h5>
                            <small class="text-muted">Farmer</small>
                        </div>
                    </div>
                    <p class="card-text">"Installed 8 JoJo tanks for irrigation. Now we're water-independent during dry seasons. The quality is unmatched after 5 years of use."</p>
                    <div class="text-warning">
                        <i class="fas fa-star"></i>
                        <i class="fas fa-star"></i>
                        <i class="fas fa-star"></i>
                        <i class="fas fa-star"></i>
                        <i class="fas fa-star-half-alt"></i>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Testimonial 3 -->
        <div class="col-md-4 mb-4">
            <div class="card h-100 border-0 shadow-sm" style="border-radius: 12px; overflow: hidden;">
                <div class="position-relative" style="height: 200px; overflow: hidden;">
                    <img src="https://mg.co.za/wp-content/uploads/2022/01/c541a977-gettyimages-1231211872-1024x683.jpg" class="card-img-top" alt="JoJo Tank school" style="height: 100%; object-fit: cover;">
                    <div class="position-absolute top-0 start-0 bg-primary text-white px-3 py-1" style="border-radius: 0 0 12px 0;">Gauteng</div>
                </div>
                <div class="card-body">
                    <div class="d-flex align-items-center mb-3">
                        <div class="rounded-circle overflow-hidden" style="width: 50px; height: 50px;">
                            <img src="https://randomuser.me/api/portraits/women/68.jpg" alt="User" style="width: 100%; height: 100%; object-fit: cover;">
                        </div>
                        <div class="ms-3">
                            <h5 class="mb-0">Nomsa D.</h5>
                            <small class="text-muted">School Principal</small>
                        </div>
                    </div>
                    <p class="card-text">"Our school's water bill dropped by 60% after installing JoJo tanks. Now we teach students practical water conservation with real examples."</p>
                    <div class="text-warning">
                        <i class="fas fa-star"></i>
                        <i class="fas fa-star"></i>
                        <i class="fas fa-star"></i>
                        <i class="fas fa-star"></i>
                        <i class="fas fa-star"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Water Saving Tips with Blue Theme -->
<div class="container mt-5 mb-5">
    <h2 class="text-center mb-5" style="color: #0077b6; position: relative;">
        <span style="background: white; padding: 0 20px; position: relative; z-index: 1;">WATER SAVING SOLUTIONS</span>
        <div style="position: absolute; top: 50%; left: 0; right: 0; height: 2px; background: linear-gradient(to right, transparent, #00b4d8, transparent); z-index: 0;"></div>
    </h2>
    
    <div class="row">
        <div class="col-md-4 mb-4">
            <div class="card h-100 border-0 shadow-sm hover-effect" style="border-radius: 12px; overflow: hidden;">
                <div class="position-relative" style="height: 180px; overflow: hidden;">
                    <img src="https://alwadibm.com/wp-content/uploads/2023/12/Water-saver-2.jpg" class="card-img-top" alt="Shorter showers" style="height: 100%; object-fit: cover;">
                    <div class="card-img-overlay d-flex align-items-end p-0">
                        <div class="bg-primary text-white p-3 w-100" style="background: rgba(0, 119, 182, 0.85);">
                            <h5 class="mb-0"><i class="fas fa-shower mr-2"></i> Shower Smart</h5>
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    <p class="card-text">Install low-flow showerheads and reduce shower time by 2 minutes to save up to 10 gallons of water per shower.</p>
                </div>
            </div>
        </div>
        
        <div class="col-md-4 mb-4">
            <div class="card h-100 border-0 shadow-sm hover-effect" style="border-radius: 12px; overflow: hidden;">
                <div class="position-relative" style="height: 180px; overflow: hidden;">
                    <img src="https://www.freshwatersystems.com/cdn/shop/articles/Rainwater_Harvesting_2_1024x1024.jpg?v=1624377712" class="card-img-top" alt="Rainwater harvesting" style="height: 100%; object-fit: cover;">
                    <div class="card-img-overlay d-flex align-items-end p-0">
                        <div class="bg-primary text-white p-3 w-100" style="background: rgba(0, 119, 182, 0.85);">
                            <h5 class="mb-0"><i class="fas fa-umbrella mr-2"></i> Harvest Rain</h5>
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    <p class="card-text">A single JoJo tank can collect 2,500 liters from just 25mm of rain on a 100m² roof - enough for a family's needs for weeks.</p>
                </div>
            </div>
        </div>
        
        <div class="col-md-4 mb-4">
            <div class="card h-100 border-0 shadow-sm hover-effect" style="border-radius: 12px; overflow: hidden;">
                <div class="position-relative" style="height: 180px; overflow: hidden;">
                    <img src="https://images.unsplash.com/photo-1581578731548-c64695cc6952?ixlib=rb-1.2.1&auto=format&fit=crop&w=500&q=60" class="card-img-top" alt="Fix leaks" style="height: 100%; object-fit: cover;">
                    <div class="card-img-overlay d-flex align-items-end p-0">
                        <div class="bg-primary text-white p-3 w-100" style="background: rgba(0, 119, 182, 0.85);">
                            <h5 class="mb-0"><i class="fas fa-tools mr-2"></i> Fix Leaks</h5>
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    <p class="card-text">A dripping faucet can waste 20 gallons of water daily. Our smart sensors detect hidden leaks before they become costly problems.</p>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Water Facts Counter with Blue Gradient -->
<div class="py-5 mt-4" style="background: linear-gradient(135deg, #0077b6 0%, #00b4d8 100%);">
    <div class="container">
        <div class="row text-center text-white">
            <div class="col-md-3 mb-4 mb-md-0">
                <h3 class="display-4 font-weight-bold mb-2"><span class="counter" data-target="780">0</span>M</h3>
                <p class="mb-0">PEOPLE LACK CLEAN WATER</p>
            </div>
            <div class="col-md-3 mb-4 mb-md-0">
                <h3 class="display-4 font-weight-bold mb-2"><span class="counter" data-target="42">0</span>%</h3>
                <p class="mb-0">HOUSEHOLD WATER WASTED</p>
            </div>
            <div class="col-md-3 mb-4 mb-md-0">
                <h3 class="display-4 font-weight-bold mb-2"><span class="counter" data-target="200">0</span>L</h3>
                <p class="mb-0">DAILY USE PER PERSON</p>
            </div>
            <div class="col-md-3">
                <h3 class="display-4 font-weight-bold mb-2"><span class="counter" data-target="95">0</span>%</h3>
                <p class="mb-0">SAVED WITH MONITORING</p>
            </div>
        </div>
    </div>
</div>

<!-- Styles and scripts remain the same -->
<style>
    @keyframes wave {
        0% { background-position-x: 0; }
        100% { background-position-x: 1200px; }
    }
    
    .hover-effect {
        transition: transform 0.3s ease, box-shadow 0.3s ease;
    }
    
    .hover-effect:hover {
        transform: translateY(-5px);
        box-shadow: 0 10px 20px rgba(0, 119, 182, 0.15) !important;
    }
    
    .water-wave {
        animation: wave 15s linear infinite;
    }
    
    .btn-outline-primary {
        border-color: #0077b6;
        color: #0077b6;
    }
    
    .btn-outline-primary:hover {
        background-color: #0077b6;
        color: white;
    }
</style>

<script>
// Password visibility toggle
document.getElementById('togglePassword').addEventListener('click', function() {
    const password = document.getElementById('password');
    const type = password.getAttribute('type') === 'password' ? 'text' : 'password';
    password.setAttribute('type', type);
    this.innerHTML = type === 'password' ? '<i class="fas fa-eye"></i>' : '<i class="fas fa-eye-slash"></i>';
});

// Counter animation
const counters = document.querySelectorAll('.counter');
const speed = 200;

function animateCounters() {
    counters.forEach(counter => {
        const target = +counter.getAttribute('data-target');
        const count = +counter.innerText;
        const increment = target / speed;
        
        if (count < target) {
            counter.innerText = Math.ceil(count + increment);
            setTimeout(animateCounters, 1);
        } else {
            counter.innerText = target;
        }
    });
}

// Start animation when counters are in view
const observer = new IntersectionObserver((entries) => {
    entries.forEach(entry => {
        if (entry.isIntersecting) {
            animateCounters();
            observer.unobserve(entry.target);
        }
    });
}, { threshold: 0.5 });

counters.forEach(counter => {
    observer.observe(counter);
});
</script>

<script>
// Password toggle (keep only one instance)
document.getElementById('togglePassword').addEventListener('click', function() {
    const password = document.getElementById('password');
    const type = password.getAttribute('type') === 'password' ? 'text' : 'password';
    password.setAttribute('type', type);
    this.innerHTML = type === 'password' ? '<i class="fas fa-eye"></i>' : '<i class="fas fa-eye-slash"></i>';
});

// Counter animation
const counters = document.querySelectorAll('.counter');
const speed = 200;

function animateCounters() {
    counters.forEach(counter => {
        const target = +counter.getAttribute('data-target');
        const count = +counter.innerText;
        const increment = target / speed;
        
        if (count < target) {
            counter.innerText = Math.ceil(count + increment);
            setTimeout(animateCounters, 1);
        } else {
            counter.innerText = target;
        }
    });
}

// Start animation when counters are in view
const observer = new IntersectionObserver((entries) => {
    entries.forEach(entry => {
        if (entry.isIntersecting) {
            animateCounters();
            observer.unobserve(entry.target);
        }
    });
}, { threshold: 0.5 });

counters.forEach(counter => {
    observer.observe(counter);
});
</script>

<?php include 'includes/footer.php'; ?>