<?php
session_start();
require_once 'config.php';
$firstname = $lastname = $email = $phone = $dob = $role = '';
$errors = [];

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $firstname = trim($_POST['firstname'] ?? '');
    $lastname = trim($_POST['lastname'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $dob = $_POST['dob'] ?? '';
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    $role = $_POST['role'] ?? '';
    if (empty($firstname)) {
        $errors[] = "First name is required";
    }    
    if (empty($lastname)) {
        $errors[] = "Last name is required";
    }    
    if (empty($email)) {
        $errors[] = "Email is required";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Invalid email format";
    } else {
        try {
            $stmt = $pdo->prepare("SELECT user_id FROM users WHERE email = ?");
            $stmt->execute([$email]);
            if ($stmt->fetch()) 
                {
                    $errors[] = "Email already registered. Please use a different email or <a href='login.php'>login here</a>";
                }
            } 
            catch(PDOException $e) 
            {
            error_log("Email check failed: " . $e->getMessage());
            $errors[] = "System error. Please try again later.";
            }
        }    
    if (empty($phone)) {
        $errors[] = "Phone number is required";
    }    
    if (empty($dob)) {
        $errors[] = "Date of birth is required";
    }    
    if (empty($password)) {
        $errors[] = "Password is required";
    } elseif (strlen($password) < 8) {
        $errors[] = "Password must be at least 8 characters long";
    }    
    if ($password !== $confirm_password) {
        $errors[] = "Passwords do not match";
    }    
    if (empty($role)) {
        $errors[] = "Please select your role";
    } 
    if (empty($errors)) {
        try {
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);

        $sql = "INSERT INTO users 
                (firstname, lastname, email, password_hash, phone, DOB, role) 
                VALUES (?, ?, ?, ?, ?, ?, ?)";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            $firstname,
            $lastname,
            $email,
            $hashed_password,
            $phone,
            $dob,
            $role
        ]);

        $_SESSION['success'] = "Registration successful! Please login.";
        header("Location: login.php");
        exit();
        } 
        catch(PDOException $e) {
        error_log("Registration failed: " . $e->getMessage());
        $errors[] = "Registration failed: " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - ZaramOUTFITTERS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="css/style.css" rel="stylesheet">
</head>

<body>
    <?php include 'nav.html'; ?>

    <div class="container register-container">
        <div class="card">
            <div class="card-header text-center">
                <h2 class="mb-0"><i class="fas fa-user-plus me-2"></i>Create an Account</h2>
                <p class="mb-0 mt-2">Join ZaramOUTFITTERS today!</p>
            </div>
            
            <div class="card-body p-4">
                <?php if (!empty($errors)): ?>
                    <div class="alert alert-danger">
                        <strong><i class="fas fa-exclamation-triangle me-2"></i>Please fix the following errors:</strong>
                        <ul class="mb-0 mt-2">
                            <?php foreach ($errors as $error): ?>
                                <li><?php echo $error; ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>
                <form method="POST" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>">                
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="Firstname" class="form-label"></i>First Name *</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-user me-1"></i></span>
                                <input type="text" class="form-control" id="Firstname" name="firstname" placeholder="Enter first name"
                                value="<?php echo htmlspecialchars($firstname); ?>" required>
                            </div>
                        </div>                        
                        <div class="col-md-6 mb-3">
                            <label for="lastname" class="form-label">Last Name *</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-user me-1"></i></span>
                                <input type="text" class="form-control" id="lastname" name="lastname" placeholder="Enter last name"
                                value="<?php echo htmlspecialchars($lastname); ?>" required>
                            </div>
                        </div>

                    </div>                 
                    <div class="mb-3">
                        <label for="email" class="form-label">Email Address *</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-envelope"></i></span>
                            <input type="email" class="form-control" id="email" name="email" placeholder="you@example.com"
                                   value="<?php echo htmlspecialchars($email); ?>" required>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="phone" class="form-label">Phone Number *</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-phone"></i></span>
                            <input type="tel" class="form-control" id="phone" name="phone" placeholder="+44(0)7000000000"
                                   value="<?php echo htmlspecialchars($phone); ?>" required>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="dob" class="form-label"></i>Date of Birth * </label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-calendar me-1"></i></span>
                            <input type="date" class="form-control" id="dob" name="dob" 
                            value="<?php echo htmlspecialchars($dob); ?>" required>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="password" class="form-label">Password *</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-lock"></i></span>
                                <input type="password" class="form-control" id="password" name="password" placeholder="Create password" required>
                            </div>
                            <small class="text-muted">Minimum 8 characters</small>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="confirm_password" class="form-label">Confirm Password *</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-check"></i></span>
                                <input type="password" class="form-control" id="confirm_password" name="confirm_password" placeholder="Re-enter password"  required>
                            </div>
                        </div>
                    </div>
                    <div class="mb-4">
                        <label class="form-label">Register as *</label>
                        <select class="form-select" name="role" required>
                            <option value="" disabled <?php echo $role == '' ? 'selected' : ''; ?>>Select your role</option>
                            <option value="user" <?php echo $role == 'user' ? 'selected' : ''; ?>>👤 User - I want to rent equipment</option>
                            <option value="admin" <?php echo $role == 'admin' ? 'selected' : ''; ?>>⚙️ Admin - I manage the system</option>
                        </select>
                    </div>
                    <button type="submit" class="btn-register">
                        <i class="fas fa-user-plus me-2"></i>Create Account
                    </button>
                    <div class="text-center mt-3">
                        <p class="mb-0">Already have an account? 
                            <a href="login.php" class="text-primary">Login here</a>
                        </p>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <script>
        function validateForm() {
            var password = document.getElementById("password").value;
            var confirmPassword = document.getElementById("confirmPassword").value;
            var message = document.getElementById("message");
            
            if (password.length < 8) {
                message.innerHTML = "Password must be at least 8 characters long!";
                message.classList.remove("d-none");
                return false;
            }
            
            if (password !== confirmPassword) {
                message.innerHTML = "Passwords do not match!";
                message.classList.remove("d-none");
                return false;
            }
            
            return true; 
        }
    </script>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
