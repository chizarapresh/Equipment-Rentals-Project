<?php
ob_start();
session_start();
require_once 'config.php';
if (isset($_SESSION['role'])) {

    if ($_SESSION['role'] == "user") {
        header("Location: user-dashboard.php");
        exit();
    } elseif ($_SESSION['role'] == "admin") {
        header("Location: admin-dashboard.php");
        exit();
    }
}

$error = '';
$email = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $role = $_POST['role'] ?? '';
    
    if (empty($email) || empty($password)) {
        $error = "Please enter both email and password";
    } else {
        try {
            $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
            $stmt->execute([$email]);
            $user = $stmt->fetch();
            
            if ($user && password_verify($password, $user['password_hash'])) {
                if ($user['account_status'] != 'active') {
                    $error = "Your account is not active. Please contact admin.";
                } elseif ($user['role'] !== $role) {
                    $error = "Invalid role selected. Please try again.";
                } else {
                    $_SESSION['user_id'] = $user['user_id'];
                    $_SESSION['firstname'] = $user['firstname'];
                    $_SESSION['lastname'] = $user['lastname'];
                    $_SESSION['user_name'] = $user['firstname'] . ' ' . $user['lastname'];
                    $_SESSION['email'] = $user['email'];
                    $_SESSION['role'] = $user['role'];
                    
                    
                    // $update = $pdo->prepare("UPDATE users SET last_login = NOW() WHERE user_id = ?");
                    // $update->execute([$user['user_id']]);
                    
                    if ($user['role'] == 'admin') {
                        header("Location: admin-dashboard.php");
                    } else {
                        header("Location: user-dashboard.php");
                    }
                    exit();
                }
            } else {
                $error = "Invalid email or password";
            }
        } catch (PDOException $e) {
            error_log("Login error: " . $e->getMessage());
            $error = "System error. Please try again later.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - ZaramOUTFITTERS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2  100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .login-container {
            max-width: 450px;
            width: 90%;
            margin: 20px auto;
        }
        .card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.2);
            overflow: hidden;
        }
        .card-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px 20px;
            text-align: center;
            border-bottom: none;
        }
        .card-header h2 {
            margin: 0;
            font-size: 2rem;
            font-weight: 700;
        }
        .card-header p {
            margin: 10px 0 0;
            opacity: 0.9;
        }
        .card-body {
            padding: 40px 30px;
            background: white;
        }
        .form-label {
            font-weight: 500;
            color: #495057;
            margin-bottom: 8px;
        }
        .input-group-text {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            padding: 12px 15px;
        }
        .form-control {
            border: 2px solid #e1e5e9;
            border-left: none;
            padding: 12px 15px;
            font-size: 1rem;
            transition: all 0.3s;
        }
        .form-control:focus {
            border-color: #667eea;
            box-shadow: none;
        }
        .btn-login {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            padding: 14px;
            font-weight: 600;
            font-size: 1.1rem;
            color: white;
            width: 100%;
            border-radius: 8px;
            margin-top: 20px;
            transition: transform 0.3s, box-shadow 0.3s;
        }
        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 20px rgba(102, 126, 234, 0.4);
            color: white;
        }
        .role-select {
            margin: 20px 0;
        }
        .role-select .form-check {
            display: inline-block;
            margin-right: 20px;
        }
        .alert {
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 25px;
        }
        .brand-icon {
            font-size: 3rem;
            margin-bottom: 15px;
        }
        .register-link {
            text-align: center;
            margin-top: 25px;
            padding-top: 20px;
            border-top: 1px solid #e1e5e9;
        }
        .register-link a {
            color: #667eea;
            font-weight: 600;
            text-decoration: none;
        }
        .register-link a:hover {
            color: #764ba2;
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="card">
            <div class="card-header">
                <div class="brand-icon">
                    <i class="fas fa-mountain"></i>
                </div>
                <h2>Welcome Back!</h2>
                <p>Sign in to continue your adventure</p>
            </div>
            
            <div class="card-body">

                <?php if (!empty($error)): ?>
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-circle me-2"></i>
                        <?php echo $error; ?>
                    </div>
                <?php endif; ?>


                <?php if (isset($_SESSION['success'])): ?>
                    <div class="alert alert-success">
                        <i class="fas fa-check-circle me-2"></i>
                        <?php 
                        echo $_SESSION['success']; 
                        unset($_SESSION['success']);
                        ?>
                    </div>
                <?php endif; ?>


                <form method="POST" action="">

                    <div class="mb-3">
                        <label class="form-label">
                            <i class="fas fa-envelope text-primary me-1"></i>Email Address
                        </label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-envelope"></i></span>
                            <input type="email" class="form-control" name="email" 
                                   value="<?php echo htmlspecialchars($email); ?>" 
                                   placeholder="Enter your email" required>
                        </div>
                    </div>


                    <div class="mb-3">
                        <label class="form-label">
                            <i class="fas fa-lock text-primary me-1"></i>Password
                        </label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-lock"></i></span>
                            <input type="password" class="form-control" name="password" 
                                   placeholder="Enter your password" required>
                        </div>
                    </div>

                    <div class="role-select">
                        <label class="form-label">
                            <i class="fas fa-user-tag text-primary me-1"></i>Login as
                        </label>
                        <div>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="radio" name="role" 
                                       id="roleUser" value="user" 
                                       <?php echo (!isset($_POST['role']) || $_POST['role'] == 'user') ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="roleUser">
                                    <i class="fas fa-user me-1"></i>User
                                </label>
                            </div>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="radio" name="role" 
                                       id="roleAdmin" value="admin"
                                       <?php echo (isset($_POST['role']) && $_POST['role'] == 'admin') ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="roleAdmin">
                                    <i class="fas fa-user-tie me-1"></i>Admin
                                </label>
                            </div>
                        </div>
                    </div>


                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="remember">
                            <label class="form-check-label" for="remember">
                                Remember me
                            </label>
                        </div>
                        <a href="forgot-password.php" class="text-decoration-none" style="color: #667eea;">
                            Forgot Password?
                        </a>
                    </div>

                    <button type="submit" class="btn-login">
                        <i class="fas fa-sign-in-alt me-2"></i>Sign In
                    </button>


                    <div class="register-link">
                        <p class="mb-0">Don't have an account?</p>
                        <a href="register.php">
                            <i class="fas fa-user-plus me-1"></i>Create Account
                        </a>
                    </div>
                </form>
            </div>
        </div>
        

        <div class="text-center mt-3">
            <a href="index.php" class="text-white text-decoration-none">
                <i class="fas fa-home me-1"></i>Back to Home
            </a>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>