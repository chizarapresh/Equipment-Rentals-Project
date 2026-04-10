<?php
session_start();
require_once 'config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'user') {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$message = '';
$error   = '';

$stmt = $pdo->prepare("SELECT * FROM users WHERE user_id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

$stmt = $pdo->prepare("
    SELECT
        COUNT(CASE WHEN status IN ('rented','overdue') THEN 1 END) AS active,
        COUNT(CASE WHEN status = 'returned'            THEN 1 END) AS returned,
        COUNT(CASE WHEN status = 'overdue'             THEN 1 END) AS overdue,
        COUNT(*)                                                    AS total
    FROM rentals WHERE user_id = ?
");
$stmt->execute([$user_id]);
$stats = $stmt->fetch();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $phone    = trim($_POST['phone']    ?? '');
    $dob      = $_POST['dob']           ?? '';

    try {
        $stmt = $pdo->prepare("UPDATE users SET phone = ?, DOB = ? WHERE user_id = ?");
        $stmt->execute([$phone, $dob ?: null, $user_id]);

        $stmt = $pdo->prepare("SELECT * FROM users WHERE user_id = ?");
        $stmt->execute([$user_id]);
        $user    = $stmt->fetch();
        $message = "Profile updated successfully!";
    } catch (PDOException $e) {
        $error = "Update failed: " . $e->getMessage();
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_password'])) {
    $current  = $_POST['current_password']  ?? '';
    $new      = $_POST['new_password']       ?? '';
    $confirm  = $_POST['confirm_password']   ?? '';

    if (!password_verify($current, $user['password_hash'])) {
        $error = "Current password is incorrect.";
    } elseif (strlen($new) < 8) {
        $error = "New password must be at least 8 characters.";
    } elseif ($new !== $confirm) {
        $error = "New passwords do not match.";
    } else {
        try {
            $hashed = password_hash($new, PASSWORD_DEFAULT);
            $stmt   = $pdo->prepare("UPDATE users SET password_hash = ? WHERE user_id = ?");
            $stmt->execute([$hashed, $user_id]);
            $message = "Password changed successfully!";
        } catch (PDOException $e) {
            $error = "Password change failed: " . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile - ZaramOUTFITTERS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="css/style.css" rel="stylesheet">

</head>
<body class="profile-user">

    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container">
            <a class="navbar-brand fw-bold" href="index.php">
                Zaram<span style="color:#ffc400;font-style:italic;">O</span>UTFITTERS
            </a>
            <div class="ms-auto d-flex gap-2">
                <a href="user-dashboard.php" class="btn btn-outline-light btn-sm">
                    <i class="fas fa-arrow-left me-1"></i>Dashboard
                </a>
                <a href="logout.php" class="btn btn-outline-danger btn-sm">
                    <i class="fas fa-sign-out-alt me-1"></i>Logout
                </a>
            </div>
        </div>
    </nav>

    <div class="profile-header text-center">
        <div class="avatar-circle mx-auto">
            <?php echo strtoupper(substr($user['firstname'], 0, 1) . substr($user['lastname'], 0, 1)); ?>
        </div>
        <h2 class="fw-bold mb-1">
            <?php echo htmlspecialchars($user['firstname'] . ' ' . $user['lastname']); ?>
        </h2>
        <p class="mb-0" style="opacity:.85;">
            <i class="fas fa-envelope me-1"></i><?php echo htmlspecialchars($user['email']); ?>
        </p>
        <p class="mt-1" style="opacity:.7; font-size:.85rem;">
            Member since <?php echo date('F Y', strtotime($user['date_created'])); ?>
        </p>
    </div>

    <div class="container mb-5 profile-cards">

        <?php if ($message): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="fas fa-check-circle me-2"></i><?php echo htmlspecialchars($message); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php endif; ?>
        <?php if ($error): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="fas fa-exclamation-circle me-2"></i><?php echo htmlspecialchars($error); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php endif; ?>

        <div class="row g-4">

            <div class="col-lg-4">

                <div class="card">
                    <div class="card-header-custom">
                        <i class="fas fa-chart-bar me-2"></i>My Rental Stats
                    </div>
                    <div class="card-body p-3">
                        <div class="row g-2">
                            <div class="col-6">
                                <div class="stat-box">
                                    <div class="stat-num"><?php echo $stats['total']; ?></div>
                                    <p class="stat-label">Total Rentals</p>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="stat-box">
                                    <div class="stat-num" style="color:#28a745;"><?php echo $stats['active']; ?></div>
                                    <p class="stat-label">Active Now</p>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="stat-box">
                                    <div class="stat-num" style="color:#6c757d;"><?php echo $stats['returned']; ?></div>
                                    <p class="stat-label">Returned</p>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="stat-box">
                                    <div class="stat-num" style="color:#dc3545;"><?php echo $stats['overdue']; ?></div>
                                    <p class="stat-label">Overdue</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header-custom">
                        <i class="fas fa-id-card me-2"></i>Account Info
                    </div>
                    <div class="card-body p-3">
                        <div class="info-row">
                            <span class="info-label">Account ID</span>
                            <span class="info-value">#<?php echo $user['user_id']; ?></span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">Role</span>
                            <span class="info-value">
                                <span class="badge bg-primary">
                                    <?php echo ucfirst($user['role']); ?>
                                </span>
                            </span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">Status</span>
                            <span class="info-value">
                                <span class="badge bg-<?php echo $user['account_status'] === 'active' ? 'success' : 'danger'; ?>">
                                    <?php echo ucfirst($user['account_status']); ?>
                                </span>
                            </span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">Rentals allowed</span>
                            <span class="info-value"><?php echo 3 - $stats['active']; ?> of 3 remaining</span>
                        </div>
                    </div>
                </div>

            </div>

            <div class="col-lg-8">

                <div class="card">
                    <div class="card-header-custom">
                        <i class="fas fa-user-edit me-2"></i>Edit Profile
                    </div>
                    <div class="card-body p-4">
                        <form method="POST">

                            <div class="row g-3 mb-3">
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold">First Name</label>
                                    <input type="text" class="form-control"
                                           value="<?php echo htmlspecialchars($user['firstname']); ?>"
                                           disabled>
                                    <p class="readonly-note">Contact admin to change your name</p>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold">Last Name</label>
                                    <input type="text" class="form-control"
                                           value="<?php echo htmlspecialchars($user['lastname']); ?>"
                                           disabled>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label fw-semibold">Email Address</label>
                                <input type="email" class="form-control"
                                       value="<?php echo htmlspecialchars($user['email']); ?>"
                                       disabled>
                                <p class="readonly-note">Contact admin to change your email</p>
                            </div>

                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold">
                                        <i class="fas fa-phone me-1 text-primary"></i>Phone Number
                                    </label>
                                    <input type="tel" class="form-control" name="phone"
                                           value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>"
                                           placeholder="e.g. 07700 000000">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold">
                                        <i class="fas fa-birthday-cake me-1 text-primary"></i>Date of Birth
                                    </label>
                                    <input type="date" class="form-control" name="dob"
                                           value="<?php echo htmlspecialchars($user['DOB'] ?? ''); ?>">
                                </div>
                            </div>

                            <div class="mt-4">
                                <button type="submit" name="update_profile" class="btn-save">
                                    <i class="fas fa-save me-1"></i>Save Changes
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header-custom">
                        <i class="fas fa-lock me-2"></i>Change Password
                    </div>
                    <div class="card-body p-4">
                        <form method="POST">
                            <div class="mb-3">
                                <label class="form-label fw-semibold">Current Password</label>
                                <input type="password" class="form-control"
                                       name="current_password"
                                       placeholder="Enter your current password"
                                       required>
                            </div>
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold">New Password</label>
                                    <input type="password" class="form-control"
                                           name="new_password" id="newPwd"
                                           placeholder="Min 8 characters"
                                           minlength="8" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold">Confirm New Password</label>
                                    <input type="password" class="form-control"
                                           name="confirm_password" id="confirmPwd"
                                           placeholder="Repeat new password"
                                           required>
                                    <small id="pwdMatch" style="display:none; font-size:.78rem;"></small>
                                </div>
                            </div>
                            <div class="mt-4">
                                <button type="submit" name="change_password" class="btn-save">
                                    <i class="fas fa-key me-1"></i>Change Password
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

            </div>
        </div>
    </div>

    <footer class="bg-dark text-white py-4">
        <div class="container text-center">
            <p>&copy; 2026 Zaram<span style="color:#ffc400;font-style:italic;font-weight:bold;">O</span>UTFITTERS. All rights reserved.</p>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        const newPwd     = document.getElementById('newPwd');
        const confirmPwd = document.getElementById('confirmPwd');
        const pwdMatch   = document.getElementById('pwdMatch');

        function checkMatch() {
            if (confirmPwd.value === '') {
                pwdMatch.style.display = 'none';
                confirmPwd.classList.remove('is-valid', 'is-invalid');
                return;
            }
            if (newPwd.value === confirmPwd.value) {
                pwdMatch.textContent      = '✓ Passwords match';
                pwdMatch.style.color      = '#28a745';
                pwdMatch.style.display    = 'block';
                confirmPwd.classList.add('is-valid');
                confirmPwd.classList.remove('is-invalid');
            } else {
                pwdMatch.textContent      = '✗ Passwords do not match';
                pwdMatch.style.color      = '#dc3545';
                pwdMatch.style.display    = 'block';
                confirmPwd.classList.add('is-invalid');
                confirmPwd.classList.remove('is-valid');
            }
        }

        confirmPwd.addEventListener('input', checkMatch);
        newPwd.addEventListener('input', checkMatch);
    </script>
</body>
</html>