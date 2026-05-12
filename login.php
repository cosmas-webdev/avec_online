<?php
/**
 * Login Page
 * 
 * Professional login interface for AVEC System.
 * Default language: English
 */

require_once 'includes/session.php';
require_once 'includes/auth.php';

if (isLoggedIn()) {
    header('Location: dashboard.php');
    exit();
}

$page_title = 'Login';
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    
    if (empty($email) || empty($password)) {
        $error = 'Please fill in all fields.';
    } else {
        if (authenticateUser($email, $password)) {
            header('Location: dashboard.php');
            exit();
        } else {
            $error = 'Invalid email or password. Please try again.';
            error_log("Failed login attempt for email: $email");
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="AVEC System - Secure login to your account">
    <meta name="author" content="Ir. Cosmas MUSAFIRI MUGONGO">
    <title>Login - <?php echo APP_NAME; ?></title>
    
    <!-- TailwindCSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    
    <!-- Font Awesome 6 -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    
    <style>
        * { 
            margin: 0; 
            padding: 0; 
            box-sizing: border-box; 
        }
        
        body { 
            font-family: 'Inter', sans-serif; 
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            position: relative;
            overflow: hidden;
        }
        
        /* Animated Background */
        .bg-animated {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: 0;
            background: linear-gradient(135deg, #0f172a 0%, #1e293b 50%, #2e1065 100%);
            background-size: 300% 300%;
            animation: gradientShift 20s ease-in-out infinite;
        }
        
        @keyframes gradientShift {
            0% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
            100% { background-position: 0% 50%; }
        }
        
        /* Subtle Orbs */
        .orb {
            position: absolute;
            border-radius: 50%;
            filter: blur(100px);
            animation: orbFloat 25s ease-in-out infinite;
        }
        
        .orb-1 {
            width: 300px;
            height: 300px;
            background: rgba(99, 102, 241, 0.12);
            top: -150px;
            right: -150px;
            animation-delay: 0s;
        }
        
        .orb-2 {
            width: 250px;
            height: 250px;
            background: rgba(168, 85, 247, 0.10);
            bottom: -120px;
            left: -120px;
            animation-delay: -5s;
        }
        
        @keyframes orbFloat {
            0%, 100% { transform: translate(0, 0) scale(1); }
            50% { transform: translate(30px, -30px) scale(1.1); }
        }
        
        /* Login Card */
        .login-container {
            position: relative;
            z-index: 1;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        
        .login-card {
            width: 100%;
            max-width: 420px;
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border-radius: 24px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            padding: 40px;
            animation: slideUp 0.6s cubic-bezier(0.34, 1.56, 0.64, 1) forwards;
            border: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(30px) scale(0.96);
            }
            to {
                opacity: 1;
                transform: translateY(0) scale(1);
            }
        }
        
        .login-card h1 {
            font-size: 32px;
            font-weight: 800;
            color: #1a1a1a;
            text-align: center;
            margin-bottom: 30px;
            background: linear-gradient(135deg, #4f46e5, #7c3aed);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        
        .login-card h1 i {
            background: linear-gradient(135deg, #4f46e5, #7c3aed);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            margin-right: 8px;
        }
        
        .login-card p {
            text-align: center;
            color: #64748b;
            font-size: 16px;
            margin-bottom: 30px;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            font-size: 14px;
            font-weight: 600;
            color: #1e293b;
            margin-bottom: 8px;
        }
        
        .form-group input {
            width: 100%;
            padding: 14px 16px;
            border: 2px solid #e2e8f0;
            border-radius: 12px;
            font-size: 16px;
            color: #1e293b;
            outline: none;
            transition: all 0.3s ease;
            background: #f8fafc;
        }
        
        .form-group input:focus {
            border-color: #4f46e5;
            box-shadow: 0 0 0 4px rgba(79, 70, 229, 0.1);
            background: #ffffff;
        }
        
        .btn-login {
            width: 100%;
            padding: 14px;
            background: linear-gradient(135deg, #4f46e5, #7c3aed);
            color: #ffffff;
            font-size: 17px;
            font-weight: 700;
            border: none;
            border-radius: 12px;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 30px rgba(79, 70, 229, 0.3);
        }
        
        .error-box {
            background: rgba(254, 226, 226, 0.9);
            border: 1px solid #fecaca;
            color: #b91c1c;
            padding: 14px 18px;
            border-radius: 12px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            font-size: 14px;
        }
        
        .success-box {
            background: rgba(220, 252, 231, 0.9);
            border: 1px solid #bbf7d0;
            color: #166534;
            padding: 14px 18px;
            border-radius: 12px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            font-size: 14px;
        }
        
        .error-box i, .success-box i {
            margin-right: 10px;
            font-size: 18px;
        }
        
        .footer-text {
            text-align: center;
            margin-top: 30px;
            font-size: 13px;
            color: #94a3b8;
        }
    </style>
</head>
<body>
    <!-- Animated Background -->
    <div class="bg-animated"></div>
    
    <!-- Orbs -->
    <div class="orb orb-1"></div>
    <div class="orb orb-2"></div>
    
    <!-- Login Container -->
    <div class="login-container">
        <div class="login-card">
            <h1><i class="fas fa-hand-holding-heart"></i> <?php echo APP_NAME; ?></h1>
            <p>Sign in to continue to your account</p>
            
            <?php if ($error): ?>
            <div class="error-box">
                <i class="fas fa-exclamation-circle"></i>
                <span><?php echo $error; ?></span>
            </div>
            <?php endif; ?>
            
            <?php if ($success): ?>
            <div class="success-box">
                <i class="fas fa-check-circle"></i>
                <span><?php echo $success; ?></span>
            </div>
            <?php endif; ?>
            
            <form method="post">
                <div class="form-group">
                    <label>Email Address</label>
                    <input type="email" name="email" placeholder="Enter your email address" required>
                </div>
                <div class="form-group">
                    <label>Password</label>
                    <input type="password" name="password" placeholder="Enter your password" required>
                </div>
                <button type="submit" class="btn-login">
                    <i class="fas fa-sign-in-alt"></i>
                    Sign In
                </button>
            </form>
            <div class="footer-text">&copy; <?php echo date('Y'); ?> Ir. Cosmas MUSAFIRI MUGONGO. All rights reserved.</div>
        </div>
    </div>
</body>
</html>