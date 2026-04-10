<?php
session_start();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>About Us - ZaramOUTFITTERS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="css/style.css" rel="stylesheet">

</head>

<body class="about-page">

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
                    <li class="nav-item"><a class="nav-link active" href="about.php">ABOUT</a></li>
                    <li class="nav-item"><a class="nav-link" href="contact.php">CONTACT US</a></li>
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

    <div class="about-hero">
        <div class="container">
            <h1><i class="fas fa-mountain me-3"></i>About ZaramOUTFITTERS</h1>
            <p class="lead mb-0">Your adventure equipment rental specialist since 2024</p>
        </div>
    </div>

    <div class="container my-5">

        <div class="row g-4 mb-4">

            <div class="col-lg-6">
                <div class="card p-4">
                    <div class="section-icon" style="background:linear-gradient(135deg,#667eea,#764ba2);color:white;">
                        <i class="fas fa-book-open"></i>
                    </div>
                    <h3 class="fw-bold mb-3">Our Story</h3>
                    <p class="text-muted">ZaramOUTFITTERS was founded with a simple mission — to make outdoor adventures accessible to everyone. We believe the best experiences in life happen outdoors, and we're here to provide the gear you need to explore Scotland's stunning landscapes.</p>
                    <p class="text-muted mb-0">Based in Aberdeen, we serve adventurers of all levels, from first-time campers to experienced kayakers. Our equipment is regularly inspected, maintained, and updated to ensure your safety and enjoyment on every trip.</p>
                </div>
            </div>

            <div class="col-lg-6">
                <div class="card p-4">
                    <div class="section-icon" style="background:#d4edda;color:#155724;">
                        <i class="fas fa-boxes"></i>
                    </div>
                    <h3 class="fw-bold mb-3">What We Offer</h3>

                    <div class="value-item">
                        <div class="value-icon"><i class="fas fa-water"></i></div>
                        <div>
                            <div class="fw-semibold">Water Sports</div>
                            <small class="text-muted">Kayaks, paddleboards, surfboards, jet skis, life jackets, wetsuits and more</small>
                        </div>
                    </div>

                    <div class="value-item">
                        <div class="value-icon"><i class="fas fa-campground"></i></div>
                        <div>
                            <div class="fw-semibold">Camping &amp; Hiking</div>
                            <small class="text-muted">Tents, sleeping bags, trekking poles, headlamps, compasses and navigation tools</small>
                        </div>
                    </div>

                    <div class="value-item" style="margin-bottom:0;">
                        <div class="value-icon"><i class="fas fa-car-side"></i></div>
                        <div>
                            <div class="fw-semibold">Transportation</div>
                            <small class="text-muted">Campervans, roof racks, camping trailers, bike racks and watersports carriers</small>
                        </div>
                    </div>
                </div>
            </div>

        </div>

        <div class="row g-4 mb-4">

            <div class="col-lg-6">
                <div class="card p-4">
                    <div class="section-icon" style="background:#cce5ff;color:#004085;">
                        <i class="fas fa-star"></i>
                    </div>
                    <h3 class="fw-bold mb-3">Why Choose Us?</h3>

                    <?php
                    $reasons = [
                        ['fas fa-check-circle', 'High-quality, well-maintained equipment inspected before every rental'],
                        ['fas fa-tag',          'Affordable daily rates with transparent VAT-inclusive pricing'],
                        ['fas fa-laptop',       'Easy online booking — browse, add to cart, and checkout in minutes'],
                        ['fas fa-truck',        'Flexible in-store pickup — just bring your invoice'],
                        ['fas fa-users',        'Expert staff available to help you choose the right gear'],
                        ['fas fa-undo',         'Simple returns process with rental history tracked in your account'],
                    ];
                    foreach ($reasons as [$icon, $text]):
                    ?>
                    <div class="value-item">
                        <div class="value-icon" style="background:#d4edda;color:#155724;">
                            <i class="<?php echo $icon; ?>"></i>
                        </div>
                        <div><small class="text-muted"><?php echo $text; ?></small></div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="col-lg-6">
                <div class="card p-4">
                    <div class="section-icon" style="background:#fff3cd;color:#856404;">
                        <i class="fas fa-map-marker-alt"></i>
                    </div>
                    <h3 class="fw-bold mb-3">Find Us</h3>

                    <div class="contact-item">
                        <div class="contact-icon"><i class="fas fa-map-marker-alt"></i></div>
                        <div>
                            <div class="fw-semibold">Address</div>
                            <small class="text-muted">123 Adventure Lane, Aberdeen, AB10 1AB, Scotland</small>
                        </div>
                    </div>

                    <div class="contact-item">
                        <div class="contact-icon"><i class="fas fa-envelope"></i></div>
                        <div>
                            <div class="fw-semibold">Email</div>
                            <small class="text-muted">info@zaramoutfitters.co.uk</small>
                        </div>
                    </div>

                    <div class="contact-item">
                        <div class="contact-icon"><i class="fas fa-phone"></i></div>
                        <div>
                            <div class="fw-semibold">Phone</div>
                            <small class="text-muted">01224 888880</small>
                        </div>
                    </div>

                    <div class="contact-item">
                        <div class="contact-icon"><i class="fas fa-clock"></i></div>
                        <div>
                            <div class="fw-semibold">Opening Hours</div>
                            <small class="text-muted">
                                Mon–Sat: 8:00am – 6:00pm<br>
                                Sunday: 10:00am – 4:00pm
                            </small>
                        </div>
                    </div>

                    <div class="mt-3">
                        <a href="contact.php" class="btn btn-sm w-100 fw-bold text-white"
                           style="background:linear-gradient(135deg,#667eea,#764ba2);border-radius:8px;padding:10px;">
                            <i class="fas fa-paper-plane me-1"></i>Send Us a Message
                        </a>
                    </div>
                </div>
            </div>

        </div>

        <div class="team-section">
            <h3 class="fw-bold mb-2">Ready for your next adventure?</h3>
            <p class="mb-4" style="opacity:.85;">Browse our full range of equipment and get started in minutes.</p>
            <div class="d-flex gap-3 justify-content-center flex-wrap">
                <a href="browse.php" class="btn btn-warning fw-bold px-4">
                    <i class="fas fa-search me-1"></i>Browse Equipment
                </a>
                <?php if (!isset($_SESSION['user_id'])): ?>
                <a href="register.php" class="btn btn-outline-light fw-bold px-4">
                    <i class="fas fa-user-plus me-1"></i>Create Free Account
                </a>
                <?php else: ?>
                <a href="user-dashboard.php" class="btn btn-outline-light fw-bold px-4">
                    <i class="fas fa-tachometer-alt me-1"></i>Go to Dashboard
                </a>
                <?php endif; ?>
            </div>
        </div>

    </div>

<footer class="bg-dark text-white py-4">
    <div class="container text-center">
        <p>&copy; 2026 Zaram<span style="color: #ffc400;
            font-style: italic; font-weight: bold; font-size:large;">O</span>UTFITTERS. All rights reserved.</p>
        <p>
            <a href="about.html" class="text-white me-3">SiteMap</a>
			<a href="privacy.php" class="text-white me-3">Privacy Policy</a>
			<a href="terms.php" class="text-white">Terms of Use</a>
            
        </p>
    </div>
</footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>