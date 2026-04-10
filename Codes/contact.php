<?php
session_start();
require_once 'config.php';

$success = false;
$error   = '';

$prefill_name  = '';
$prefill_email = '';
if (isset($_SESSION['user_name'])) {
    $prefill_name  = $_SESSION['user_name'];
    $prefill_email = $_SESSION['email'] ?? '';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['send_message'])) {
    $name    = trim($_POST['name']    ?? '');
    $email   = trim($_POST['email']   ?? '');
    $subject = trim($_POST['subject'] ?? '');
    $message = trim($_POST['message'] ?? '');

    if (empty($name) || empty($email) || empty($subject) || empty($message)) {
        $error = "Please fill in all fields.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Please enter a valid email address.";
    } elseif (strlen($message) < 10) {
        $error = "Message is too short — please provide more detail.";
    } else {
        try {
            $stmt = $pdo->prepare("
                INSERT INTO contact_messages (name, email, subject, message)
                VALUES (?, ?, ?, ?)
            ");
            $stmt->execute([$name, $email, $subject, $message]);
            $success = true;
        } catch (PDOException $e) {
            $error = "Message could not be sent. Please try again later.";
            error_log("Contact form error: " . $e->getMessage());
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contact Us - ZaramOUTFITTERS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="css/style.css" rel="stylesheet">

</head>
<body class="contact-page">

    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container">
            <a class="navbar-brand fw-bold" href="index.php">
                Zaram<span style="color:#ffc400;font-style:italic;">O</span>UTFITTERS
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarMain">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarMain">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item"><a class="nav-link" href="index.php">HOME</a></li>
                    <li class="nav-item"><a class="nav-link" href="browse.php">BROWSE</a></li>
                    <li class="nav-item"><a class="nav-link" href="about.php">ABOUT</a></li>
                    <li class="nav-item"><a class="nav-link active" href="contact.php">CONTACT US</a></li>
                </ul>
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" data-bs-toggle="dropdown">
                            <i class="fas fa-user-circle me-1"></i>
                            <?php echo isset($_SESSION['user_name']) ? htmlspecialchars($_SESSION['user_name']) : 'Account'; ?>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <?php if (isset($_SESSION['user_id'])): ?>
                                <li><h6 class="dropdown-header">Welcome, <?php echo htmlspecialchars($_SESSION['firstname'] ?? ''); ?>!</h6></li>
                                <li><a class="dropdown-item" href="user-dashboard.php"><i class="fas fa-tachometer-alt me-2"></i>Dashboard</a></li>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item text-danger" href="logout.php"><i class="fas fa-sign-out-alt me-2"></i>Logout</a></li>
                            <?php else: ?>
                                <li><a class="dropdown-item" href="login.php"><i class="fas fa-sign-in-alt me-2"></i>Login</a></li>
                                <li><a class="dropdown-item" href="register.php"><i class="fas fa-user-plus me-2"></i>Register</a></li>
                            <?php endif; ?>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="contact-hero">
        <div class="container">
            <h1 class="fw-bold"><i class="fas fa-paper-plane me-3"></i>Contact Us</h1>
            <p class="lead mb-0">Got a question? We'd love to hear from you.</p>
        </div>
    </div>

    <div class="container my-5">
        <div class="row g-4">

            <div class="col-lg-7">
                <div class="card">
                    <div class="card-header-custom">
                        <i class="fas fa-envelope me-2"></i>Send Us a Message
                    </div>
                    <div class="card-body p-4">

                        <?php if ($success): ?>
                            <div class="success-box">
                                <div class="success-icon mx-auto">
                                    <i class="fas fa-check"></i>
                                </div>
                                <h4 class="fw-bold mb-2">Message Sent!</h4>
                                <p class="text-muted mb-4">
                                    Thanks for getting in touch. We'll get back to you as soon as possible.
                                </p>
                                <div class="d-flex gap-2 justify-content-center flex-wrap">
                                    <a href="contact.php" class="btn btn-outline-primary">
                                        <i class="fas fa-redo me-1"></i>Send Another Message
                                    </a>
                                    <a href="browse.php" class="btn btn-primary">
                                        <i class="fas fa-search me-1"></i>Browse Equipment
                                    </a>
                                </div>
                            </div>

                        <?php else: ?>
                            <!-- Form -->
                            <?php if ($error): ?>
                                <div class="alert alert-danger alert-dismissible fade show mb-4">
                                    <i class="fas fa-exclamation-circle me-2"></i><?php echo htmlspecialchars($error); ?>
                                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                                </div>
                            <?php endif; ?>

                            <form method="POST" id="contactForm">
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label class="form-label fw-semibold">
                                            <i class="fas fa-user me-1 text-primary"></i>Your Name *
                                        </label>
                                        <input type="text" class="form-control" name="name"
                                               value="<?php echo htmlspecialchars($_POST['name'] ?? $prefill_name); ?>"
                                               placeholder="Full name" required>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label fw-semibold">
                                            <i class="fas fa-envelope me-1 text-primary"></i>Email Address *
                                        </label>
                                        <input type="email" class="form-control" name="email"
                                               value="<?php echo htmlspecialchars($_POST['email'] ?? $prefill_email); ?>"
                                               placeholder="your@email.com" required>
                                    </div>
                                    <div class="col-12">
                                        <label class="form-label fw-semibold">
                                            <i class="fas fa-tag me-1 text-primary"></i>Subject *
                                        </label>
                                        <select class="form-select" name="subject" required>
                                            <option value="" disabled <?php echo empty($_POST['subject']) ? 'selected' : ''; ?>>
                                                Select a subject...
                                            </option>
                                            <?php
                                            $subjects = [
                                                'General Enquiry',
                                                'Equipment Question',
                                                'Rental / Booking Help',
                                                'Return or Extension Request',
                                                'Complaint or Feedback',
                                                'Other',
                                            ];
                                            foreach ($subjects as $s):
                                            ?>
                                                <option value="<?php echo $s; ?>"
                                                    <?php echo (($_POST['subject'] ?? '') === $s) ? 'selected' : ''; ?>>
                                                    <?php echo $s; ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="col-12">
                                        <label class="form-label fw-semibold">
                                            <i class="fas fa-comment me-1 text-primary"></i>Message *
                                        </label>
                                        <textarea class="form-control" name="message" id="messageArea"
                                                  rows="5" maxlength="1000"
                                                  placeholder="Tell us how we can help..."
                                                  required><?php echo htmlspecialchars($_POST['message'] ?? ''); ?></textarea>
                                        <div class="char-count" id="charCount">0 / 1000</div>
                                    </div>
                                </div>

                                <div class="mt-4">
                                    <button type="submit" name="send_message" class="btn-send">
                                        <i class="fas fa-paper-plane me-2"></i>Send Message
                                    </button>
                                </div>
                            </form>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <div class="col-lg-5">
                <div class="card p-4 mb-4">
                    <h5 class="fw-bold mb-4">Get in Touch</h5>

                    <div class="info-item">
                        <div class="info-icon"><i class="fas fa-map-marker-alt"></i></div>
                        <div>
                            <div class="fw-semibold">Visit Us</div>
                            <small class="text-muted">123 Adventure Lane<br>Aberdeen, AB10 1AB, Scotland</small>
                        </div>
                    </div>

                    <div class="info-item">
                        <div class="info-icon"><i class="fas fa-phone"></i></div>
                        <div>
                            <div class="fw-semibold">Call Us</div>
                            <small class="text-muted">01224 000000</small>
                        </div>
                    </div>

                    <div class="info-item">
                        <div class="info-icon"><i class="fas fa-envelope"></i></div>
                        <div>
                            <div class="fw-semibold">Email</div>
                            <small class="text-muted">info@zaramoutfitters.co.uk</small>
                        </div>
                    </div>

                    <div class="info-item" style="margin-bottom:0;">
                        <div class="info-icon"><i class="fas fa-clock"></i></div>
                        <div>
                            <div class="fw-semibold">Opening Hours</div>
                            <small class="text-muted">
                                Mon–Sat: 8:00am – 6:00pm<br>
                                Sunday: 10:00am – 4:00pm
                            </small>
                        </div>
                    </div>
                </div>

                <div class="card p-4">
                    <h5 class="fw-bold mb-3">Quick Links</h5>
                    <div class="d-grid gap-2">
                        <a href="browse.php" class="btn btn-outline-primary btn-sm text-start">
                            <i class="fas fa-search me-2"></i>Browse Equipment
                        </a>
                        <?php if (isset($_SESSION['user_id'])): ?>
                            <a href="user-dashboard.php" class="btn btn-outline-primary btn-sm text-start">
                                <i class="fas fa-tachometer-alt me-2"></i>My Dashboard
                            </a>
                        <?php else: ?>
                            <a href="register.php" class="btn btn-outline-primary btn-sm text-start">
                                <i class="fas fa-user-plus me-2"></i>Create Account
                            </a>
                            <a href="login.php" class="btn btn-outline-primary btn-sm text-start">
                                <i class="fas fa-sign-in-alt me-2"></i>Login
                            </a>
                        <?php endif; ?>
                        <a href="about.php" class="btn btn-outline-secondary btn-sm text-start">
                            <i class="fas fa-info-circle me-2"></i>About Us
                        </a>
                    </div>
                </div>
            </div>

        </div>
    </div>

    <footer class="bg-dark text-white py-4 mt-2">
        <div class="container text-center">
            <p class="mb-1">&copy; 2026 Zaram<span style="color:#ffc400;font-style:italic;font-weight:bold;">O</span>UTFITTERS. All rights reserved.</p>
            <p class="mb-0">
                <a href="privacy.php" class="text-white me-3">Privacy Policy</a>
                <a href="terms.php" class="text-white">Terms of Use</a>
            </p>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        const area      = document.getElementById('messageArea');
        const counter   = document.getElementById('charCount');

        function updateCount() {
            const len = area.value.length;
            counter.textContent = len + ' / 1000';
            counter.className   = 'char-count';
            if (len > 800) counter.classList.add('warning');
            if (len > 950) counter.classList.add('danger');
        }

        if (area) {
            area.addEventListener('input', updateCount);
            updateCount(); 
        }
    </script>
</body>
</html>