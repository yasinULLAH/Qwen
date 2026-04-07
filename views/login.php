<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - <?php echo get_setting('hotel_name', 'FeastFlow Pro'); ?></title>
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <!-- SweetAlert2 -->
    <link href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css" rel="stylesheet">
    
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        .login-container {
            background: white;
            border-radius: 15px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.2);
            overflow: hidden;
            max-width: 900px;
            width: 90%;
        }
        
        .login-left {
            background: linear-gradient(135deg, #2c3e50 0%, #3498db 100%);
            color: white;
            padding: 60px 40px;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }
        
        .login-left h1 {
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 10px;
        }
        
        .login-left p {
            font-size: 1.1rem;
            opacity: 0.9;
            line-height: 1.6;
        }
        
        .login-right {
            padding: 60px 40px;
        }
        
        .brand-logo {
            text-align: center;
            margin-bottom: 30px;
        }
        
        .brand-logo i {
            font-size: 4rem;
            color: #3498db;
            margin-bottom: 15px;
        }
        
        .brand-logo h2 {
            color: #2c3e50;
            font-weight: 700;
            margin: 0;
        }
        
        .brand-logo small {
            color: #7f8c8d;
            font-size: 0.9rem;
        }
        
        .form-control {
            border-radius: 8px;
            border: 1px solid #ddd;
            padding: 12px 15px;
            font-size: 1rem;
        }
        
        .form-control:focus {
            border-color: #3498db;
            box-shadow: 0 0 0 0.2rem rgba(52, 152, 219, 0.25);
        }
        
        .input-group-text {
            background: #f8f9fa;
            border: 1px solid #ddd;
            border-radius: 8px 0 0 8px;
        }
        
        .btn-login {
            background: linear-gradient(135deg, #3498db 0%, #2980b9 100%);
            border: none;
            padding: 12px;
            font-size: 1.1rem;
            font-weight: 600;
            border-radius: 8px;
            transition: all 0.3s;
        }
        
        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(52, 152, 219, 0.4);
        }
        
        .captcha-box {
            background: #f8f9fa;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 20px;
        }
        
        .captcha-question {
            font-size: 1.5rem;
            font-weight: 700;
            color: #2c3e50;
            text-align: center;
            letter-spacing: 3px;
            background: white;
            padding: 10px;
            border-radius: 5px;
            border: 2px dashed #3498db;
        }
        
        .features-list {
            list-style: none;
            padding: 0;
            margin-top: 30px;
        }
        
        .features-list li {
            margin-bottom: 15px;
            display: flex;
            align-items: center;
        }
        
        .features-list i {
            color: #27ae60;
            margin-right: 10px;
            font-size: 1.2rem;
        }
        
        @media (max-width: 768px) {
            .login-left {
                padding: 40px 20px;
            }
            
            .login-right {
                padding: 40px 20px;
            }
            
            .login-left h1 {
                font-size: 2rem;
            }
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="row g-0">
            <div class="col-lg-5 login-left">
                <h1><i class="fas fa-utensils"></i> FeastFlow Pro</h1>
                <p class="mt-3">The Ultimate Restaurant Management Solution!</p>
                
                <ul class="features-list">
                    <li><i class="fas fa-check-circle"></i> Smart POS & Billing</li>
                    <li><i class="fas fa-check-circle"></i> Live Kitchen Display</li>
                    <li><i class="fas fa-check-circle"></i> QR Menu & Kiosk</li>
                    <li><i class="fas fa-check-circle"></i> Inventory & Recipes</li>
                    <li><i class="fas fa-check-circle"></i> Customer Khata System</li>
                    <li><i class="fas fa-check-circle"></i> Staff Payroll & Timeclock</li>
                </ul>
            </div>
            
            <div class="col-lg-7 login-right">
                <div class="brand-logo">
                    <i class="fas fa-chart-line"></i>
                    <h2>Welcome Back!</h2>
                    <small>Please login to continue</small>
                </div>
                
                <?php if (isset($_GET['error'])): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="fas fa-exclamation-triangle"></i> 
                    <?php echo htmlspecialchars($_GET['error']); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
                <?php endif; ?>
                
                <?php if (isset($_GET['logout'])): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="fas fa-check-circle"></i> You have been logged out successfully.
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
                <?php endif; ?>
                
                <form id="loginForm" method="POST" action="<?php echo APP_URL; ?>?page=login">
                    <?php echo csrf_field(); ?>
                    
                    <div class="mb-3">
                        <label for="username" class="form-label">Username</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-user"></i></span>
                            <input type="text" class="form-control" id="username" name="username" required autofocus placeholder="Enter your username">
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="password" class="form-label">Password</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-lock"></i></span>
                            <input type="password" class="form-control" id="password" name="password" required placeholder="Enter your password">
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="pincode" class="form-label">PIN Code (Optional for quick access)</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-key"></i></span>
                            <input type="password" class="form-control" id="pincode" name="pincode" maxlength="6" placeholder="Enter PIN code">
                        </div>
                        <small class="text-muted">You can use PIN code instead of password for faster login</small>
                    </div>
                    
                    <div class="captcha-box">
                        <label class="form-label d-block">Security Question</label>
                        <div class="captcha-question mb-2" id="captchaQuestion">
                            <?php echo $_SESSION['captcha_question'] ?? 'Loading...'; ?>
                        </div>
                        <input type="number" class="form-control" id="captcha_answer" name="captcha_answer" required placeholder="Enter your answer">
                        <input type="hidden" id="captcha_hash" name="captcha_hash" value="<?php echo $_SESSION['captcha_hash'] ?? ''; ?>">
                    </div>
                    
                    <div class="d-grid">
                        <button type="submit" class="btn btn-primary btn-login">
                            <i class="fas fa-sign-in-alt"></i> Login to Dashboard
                        </button>
                    </div>
                    
                    <div class="text-center mt-3">
                        <a href="<?php echo APP_URL; ?>?page=kiosk_login" class="text-muted">
                            <i class="fas fa-mobile-alt"></i> Switch to Kiosk Mode
                        </a>
                    </div>
                </form>
                
                <div class="text-center mt-4">
                    <small class="text-muted">
                        Default credentials: <strong>admin / admin123</strong>
                    </small>
                </div>
            </div>
        </div>
    </div>
    
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <!-- Bootstrap 5 JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.all.min.js"></script>
    
    <script>
        $(document).ready(function() {
            // Auto-hide alerts
            $('.alert').delay(5000).fadeOut('slow', function() {
                $(this).alert('close');
            });
            
            // Refresh CAPTCHA on click
            $('#captchaQuestion').on('click', function() {
                $.ajax({
                    url: '<?php echo APP_URL; ?>?page=login',
                    type: 'POST',
                    data: { action: 'refresh_captcha' },
                    success: function(response) {
                        try {
                            const data = JSON.parse(response);
                            if (data.success) {
                                $('#captchaQuestion').text(data.question);
                                $('#captcha_hash').val(data.hash);
                                $('#captcha_answer').val('');
                            }
                        } catch(e) {}
                    }
                });
            });
            
            // Form submission
            $('#loginForm').on('submit', function(e) {
                e.preventDefault();
                
                const formData = new FormData(this);
                formData.append('action', 'login');
                
                $.ajax({
                    url: '<?php echo APP_URL; ?>ajax/auth_actions.php',
                    type: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    success: function(response) {
                        try {
                            const data = JSON.parse(response);
                            if (data.success) {
                                Swal.fire({
                                    icon: 'success',
                                    title: 'Login Successful!',
                                    text: 'Redirecting to dashboard...',
                                    timer: 1500,
                                    showConfirmButton: false
                                }).then(() => {
                                    window.location.href = '<?php echo APP_URL; ?>?page=dashboard';
                                });
                            } else {
                                Swal.fire({
                                    icon: 'error',
                                    title: 'Login Failed!',
                                    text: data.message || 'Invalid credentials'
                                });
                                
                                // Refresh CAPTCHA on failed login
                                $.ajax({
                                    url: '<?php echo APP_URL; ?>?page=login',
                                    type: 'POST',
                                    data: { action: 'refresh_captcha' },
                                    success: function(resp) {
                                        try {
                                            const captchaData = JSON.parse(resp);
                                            if (captchaData.success) {
                                                $('#captchaQuestion').text(captchaData.question);
                                                $('#captcha_hash').val(captchaData.hash);
                                                $('#captcha_answer').val('');
                                            }
                                        } catch(e) {}
                                    }
                                });
                            }
                        } catch(e) {
                            Swal.fire({
                                icon: 'error',
                                title: 'Error!',
                                text: 'An unexpected error occurred'
                            });
                        }
                    },
                    error: function() {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error!',
                            text: 'Connection error. Please try again.'
                        });
                    }
                });
            });
        });
    </script>
</body>
</html>
