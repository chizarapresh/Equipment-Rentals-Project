<?php
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
    <link href="css/style.css" rel="stylesheet">
    
</head>
<body>
    <div class="login-container">
        <div class="login-card">
            <div class="login-card-header">
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
