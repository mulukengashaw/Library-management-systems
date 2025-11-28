<?php
include 'config.php';
$page_title = "eLibrary - Authentication";

// Redirect if already logged in
if (isset($_SESSION['user_id'])) {
    if ($_SESSION['user_role'] === 'admin') {
        header('Location: admin-dashboard.php');
    } else {
        header('Location: user-dashboard.php');
    }
    exit;
}

// Handle form submission
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    
    if ($action === 'login') {
        // Login
        $stmt = $conn->prepare("SELECT id, name, password, role FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($user = $result->fetch_assoc()) {
            // For demo purposes, using simple password check
            if ($password === 'password' || password_verify($password, $user['password'])) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_name'] = $user['name'];
                $_SESSION['user_role'] = $user['role'];
                
                if ($user['role'] === 'admin') {
                    header('Location: admin-dashboard.php');
                } else {
                    header('Location: user-dashboard.php');
                }
                exit;
            } else {
                $error = "Invalid password";
            }
        } else {
            $error = "User not found";
        }
        $stmt->close();
    } elseif ($action === 'register') {
        // Register
        $name = trim($_POST['name'] ?? '');
        $confirm_password = $_POST['confirm_password'] ?? '';
        
        if (empty($name) || empty($email) || empty($password)) {
            $error = "Please fill in all required fields";
        } elseif ($password !== $confirm_password) {
            $error = "Passwords do not match";
        } else {
            // Check if email already exists
            $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
            $stmt->bind_param("s", $email);
            $stmt->execute();
            if ($stmt->get_result()->num_rows > 0) {
                $error = "Email already registered";
            } else {
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $conn->prepare("INSERT INTO users (name, email, password) VALUES (?, ?, ?)");
                $stmt->bind_param("sss", $name, $email, $hashed_password);
                
                if ($stmt->execute()) {
                    $_SESSION['user_id'] = $stmt->insert_id;
                    $_SESSION['user_name'] = $name;
                    $_SESSION['user_role'] = 'member';
                    header('Location: user-dashboard.php');
                    exit;
                } else {
                    $error = "Registration failed";
                }
            }
            $stmt->close();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?></title>
    <link rel="stylesheet" href="css/style.css">
    <style>
        /* Auth Page Specific Styles */
        .auth-layout {
            display: flex;
            min-height: 100vh;
            background: linear-gradient(135deg, var(--slate-50) 0%, var(--blue-50) 100%);
        }

        .auth-main-content {
            flex: 1;
            margin-left: 256px;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem;
            min-height: 100vh;
        }

        .auth-container-wrapper {
            width: 100%;
            max-width: 440px;
            margin: 0 auto;
        }

        .auth-container {
            width: 100%;
        }

        .auth-card {
            background: white;
            padding: 2.5rem;
            border-radius: 1.5rem;
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
            border: 1px solid var(--slate-200);
            transition: all 0.3s ease;
        }

        .auth-card:hover {
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.15);
            transform: translateY(-2px);
        }

        .auth-header {
            text-align: center;
            margin-bottom: 2rem;
        }

        .auth-logo {
            width: 4rem;
            height: 4rem;
            background: linear-gradient(135deg, var(--blue-500), var(--purple-600));
            border-radius: 1rem;
            margin: 0 auto 1.5rem;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
        }

        .auth-header h1 {
            font-size: 1.875rem;
            font-weight: bold;
            color: var(--slate-900);
            margin-bottom: 0.5rem;
        }

        .auth-header p {
            color: var(--slate-500);
            font-size: 1rem;
        }

        .auth-error {
            margin-bottom: 1.5rem;
            padding: 1rem;
            background-color: var(--rose-50);
            border: 1px solid var(--rose-100);
            border-radius: 0.75rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
            font-size: 0.875rem;
            color: var(--rose-600);
        }

        .auth-form {
            space-y: 1.25rem;
            margin-bottom: 1.5rem;
        }

        .form-group {
            space-y: 0.5rem;
        }

        .input-wrapper {
            position: relative;
        }

        .input-icon {
            position: absolute;
            left: 1rem;
            top: 50%;
            transform: translateY(-50%);
            color: var(--slate-400);
        }

        .input-wrapper input {
            width: 100%;
            padding: 1rem 1rem 1rem 3rem;
            border-radius: 0.75rem;
            background-color: var(--slate-50);
            border: 1px solid var(--slate-200);
            outline: none;
            font-size: 0.875rem;
            transition: all 0.2s;
        }

        .input-wrapper input:focus {
            border-color: var(--blue-400);
            box-shadow: 0 0 0 3px var(--blue-100);
            background-color: white;
        }

        .auth-submit-btn {
            width: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            margin-top: 0.5rem;
            padding: 1rem 1.5rem;
            font-weight: 600;
        }

        .auth-switch {
            text-align: center;
            padding-top: 1.5rem;
            border-top: 1px solid var(--slate-200);
        }

        .auth-switch p {
            font-size: 0.875rem;
            color: var(--slate-600);
        }

        .switch-link {
            margin-left: 0.25rem;
            color: var(--blue-600);
            font-weight: 600;
            background: none;
            border: none;
            cursor: pointer;
            text-decoration: underline;
        }

        .switch-link:hover {
            color: var(--blue-700);
        }

        .auth-note {
            margin-top: 2rem;
            font-size: 0.75rem;
            color: var(--slate-400);
            text-align: center;
            max-width: 28rem;
            margin-left: auto;
            margin-right: auto;
        }

        /* Mobile Responsive */
        @media (max-width: 768px) {
            .auth-main-content {
                margin-left: 0;
                padding: 1rem;
            }
            
            .auth-card {
                padding: 2rem 1.5rem;
            }
            
            .auth-logo {
                width: 3.5rem;
                height: 3.5rem;
            }
            
            .auth-header h1 {
                font-size: 1.5rem;
            }
        }

        /* Animation for form switching */
        .auth-card {
            animation: fadeInUp 0.5s ease-out;
        }

        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
    </style>
</head>
<body class="auth-layout">
    <!-- Left Sidebar -->
    <?php include 'includes/sidebar.php'; ?>
    
    <!-- Main Content Area -->
    <div class="auth-main-content">
        <div class="auth-container-wrapper">
            <div class="auth-container">
                <!-- Login Form -->
                <div class="auth-card" id="login-card">
                    <div class="auth-header">
                        <div class="auth-logo">
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="m4 6 8-4 8 4"></path>
                                <path d="m18 10 4 2v8a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2v-8l4-2"></path>
                                <path d="M14 22v-4a2 2 0 0 0-2-2v0a2 2 0 0 0-2 2v4"></path>
                                <path d="M18 5v17"></path>
                                <path d="M6 5v17"></path>
                                <circle cx="12" cy="9" r="2"></circle>
                            </svg>
                        </div>
                        <h1>Welcome Back</h1>
                        <p>Sign in to access your library account</p>
                    </div>

                    <?php if (!empty($error) && (!isset($_POST['action']) || $_POST['action'] === 'login')): ?>
                    <div class="auth-error">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <circle cx="12" cy="12" r="10"></circle>
                            <line x1="12" y1="8" x2="12" y2="12"></line>
                            <line x1="12" y1="16" x2="12.01" y2="16"></line>
                        </svg>
                        <?php echo htmlspecialchars($error); ?>
                    </div>
                    <?php endif; ?>

                    <form class="auth-form" method="POST">
                        <input type="hidden" name="action" value="login">
                        
                        <div class="form-group">
                            <div class="input-wrapper">
                                <svg class="input-icon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"></path>
                                    <polyline points="22,6 12,13 2,6"></polyline>
                                </svg>
                                <input type="email" name="email" placeholder="Email Address" value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>" required>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <div class="input-wrapper">
                                <svg class="input-icon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect>
                                    <path d="M7 11V7a5 5 0 0 1 10 0v4"></path>
                                </svg>
                                <input type="password" name="password" placeholder="Password" required>
                            </div>
                        </div>
                        
                        <button type="submit" class="btn btn-primary auth-submit-btn">
                            Sign In
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="m9 18 6-6-6-6"></path>
                            </svg>
                        </button>
                    </form>

                    <div class="auth-switch">
                        <p>Don't have an account?
                            <button type="button" class="switch-link" onclick="showRegister()">Sign up</button>
                        </p>
                    </div>
                </div>
                
                <!-- Register Form -->
                <div class="auth-card register-card" id="register-card" style="display: none;">
                    <div class="auth-header">
                        <div class="auth-logo">
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="m4 6 8-4 8 4"></path>
                                <path d="m18 10 4 2v8a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2v-8l4-2"></path>
                                <path d="M14 22v-4a2 2 0 0 0-2-2v0a2 2 0 0 0-2 2v4"></path>
                                <path d="M18 5v17"></path>
                                <path d="M6 5v17"></path>
                                <circle cx="12" cy="9" r="2"></circle>
                            </svg>
                        </div>
                        <h1>Join LibraFlow</h1>
                        <p>Create an account to start borrowing</p>
                    </div>

                    <?php if (!empty($error) && isset($_POST['action']) && $_POST['action'] === 'register'): ?>
                    <div class="auth-error">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <circle cx="12" cy="12" r="10"></circle>
                            <line x1="12" y1="8" x2="12" y2="12"></line>
                            <line x1="12" y1="16" x2="12.01" y2="16"></line>
                        </svg>
                        <?php echo htmlspecialchars($error); ?>
                    </div>
                    <?php endif; ?>

                    <form class="auth-form" method="POST">
                        <input type="hidden" name="action" value="register">
                        
                        <div class="form-group">
                            <div class="input-wrapper">
                                <svg class="input-icon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                                    <circle cx="12" cy="7" r="4"></circle>
                                </svg>
                                <input type="text" name="name" placeholder="Full Name" value="<?php echo htmlspecialchars($_POST['name'] ?? ''); ?>" required>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <div class="input-wrapper">
                                <svg class="input-icon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"></path>
                                    <polyline points="22,6 12,13 2,6"></polyline>
                                </svg>
                                <input type="email" name="email" placeholder="Email Address" value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>" required>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <div class="input-wrapper">
                                <svg class="input-icon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect>
                                    <path d="M7 11V7a5 5 0 0 1 10 0v4"></path>
                                </svg>
                                <input type="password" name="password" placeholder="Password" required>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <div class="input-wrapper">
                                <svg class="input-icon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect>
                                    <path d="M7 11V7a5 5 0 0 1 10 0v4"></path>
                                </svg>
                                <input type="password" name="confirm_password" placeholder="Confirm Password" required>
                            </div>
                        </div>
                        
                        <button type="submit" class="btn btn-primary auth-submit-btn">
                            Create Account
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="m9 18 6-6-6-6"></path>
                            </svg>
                        </button>
                    </form>

                    <div class="auth-switch">
                        <p>Already a member?
                            <button type="button" class="switch-link" onclick="showLogin()">Log in</button>
                        </p>
                    </div>
                </div>
            </div>
            
            <p class="auth-note">Demo: Use 'password' for all accounts or register new account</p>
        </div>
    </div>

    <script>
        function showRegister() {
            document.getElementById('login-card').style.display = 'none';
            document.getElementById('register-card').style.display = 'block';
        }
        
        function showLogin() {
            document.getElementById('register-card').style.display = 'none';
            document.getElementById('login-card').style.display = 'block';
        }
        
        // Show register form if there was an error and we were on register
        <?php if (!empty($error) && isset($_POST['action']) && $_POST['action'] === 'register'): ?>
        showRegister();
        <?php endif; ?>

        // Add smooth animations
        document.addEventListener('DOMContentLoaded', function() {
            const authCards = document.querySelectorAll('.auth-card');
            authCards.forEach(card => {
                card.style.opacity = '0';
                card.style.transform = 'translateY(20px)';
                
                setTimeout(() => {
                    card.style.transition = 'all 0.5s ease-out';
                    card.style.opacity = '1';
                    card.style.transform = 'translateY(0)';
                }, 100);
            });
        });
    </script>
</body>
</html>