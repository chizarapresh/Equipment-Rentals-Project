<?php
session_start();

require_once 'config.php';
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ZaramOUTFITTERS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="css/style.css" rel="stylesheet">
	

</head>

<index.body>
    <header class="site-header">
        <div class="container">

                    <div>
                        <h1 class="site-title">
                            Zaram<span>O</span>UTFITTERS
                        </h1>
                        <p class="tagline">
                            Adventure <span>Answered!!!</span>
                        </p>
                    </div>
        
        </div>

    </header>



    
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container">
            <div class="logo-section">
                <img src="images/logo.png" href="index.php"
                     
                     class="logo-img">

            </div>

        
            
            <a class="navbar-brand"></a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarMain">
                <span class="navbar-toggler-icon"></span>
            </button>
            
            
            <div class="collapse navbar-collapse" id="navbarMain">
                
                <ul class="navbar-nav me-auto ">
                    <li class="nav-item">
                        <a class="nav-link" href="index.php">HOME</a>
                    </li>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="browseDropdown" role="button" data-bs-toggle="dropdown">
                            BROWSE
                        </a>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="water-sports.php"><i class="fas fa-water"></i>Water Sports</a></li>
                            <li><a class="dropdown-item" href="camping.php"><i class="fas fa-campground"></i>Camping/Hiking</a></li>
                            <li><a class="dropdown-item" href="transportation.php"><i class="fas fa-bicycle"></i>Transportation</a></li>
                        </ul>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="about.php">ABOUT</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="contact.php">CONTACT US</a>
                    </li>
                </ul>
                
             
                <form class="d-flex me-3" action="search.php" method="get">
                    <input class="form-control me-2" type="search" name="search" placeholder="Search equipment...">
                    <button class="btn btn-outline-light" type="submit"><i class="fas fa-search"></i></button>
                </form>
                            
                
                <ul class="navbar-nav ms-auto mb-2 mb-lg-0">
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="userDropdown" 
                        role="button" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="fas fa-user-circle me-1"></i>
                        <?php echo isset($_SESSION['user_name']) ? $_SESSION['user_name'] : 'Account'; ?></a>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <?php if(isset($_SESSION['user_name'])): ?>
                                <li><h6 class="dropdown-header">Welcome, <?php echo htmlspecialchars($_SESSION['user_name']); ?>!</h6></li>
                                <li><a class="dropdown-item" href="user-dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
                                <li><a class="dropdown-item" href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
                                <?php else: ?>
                                    <li><a class="dropdown-item" href="login.php"><i class="fas fa-sign-in-alt"></i> Login</a></li>
                                    <li><a class="dropdown-item" href="register.php"><i class="fas fa-user-plus"></i> Register</a></li>
                                    <li><hr class="dropdown-divider"></li>
                                    <li><a class="dropdown-item" href="support.php"><i class="fas fa-headset"></i> Support</a></li>
                                <?php endif; 
                            ?>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    
    <main class="container my-5">
        <h2 class="text-white">Welcome to ZaramOUTFITTERS</h2>
        <p class="text-white">Your adventure equipment rental specialist! Browse our selection of water sports, hiking, camping gear, and transportation options.</p>
        <?php if(isset($_SESSION['user_name'])): ?>
            <div class="alert alert-success mt-3">
                Welcome back, <?php echo $_SESSION['user_name']; ?>! Ready for your next adventure?
            </div>
        <?php endif; ?>        


<section class="how-it-works py-5">
    <div class="container">

        <div class="text-center mb-5">
            <h2 class="display-4 fw-bold text-white">HOW IT <span class="text-primary">WORKS</span></h2>
            <p class="lead text-white">Four simple steps to your next outdoor experience</p>
        </div>
        
 
        <div class="position-relative mb-4 d-none d-lg-block">
            <div class="progress-line" style="height: 2px; background: #e9ecef; position: absolute; top: 40px; left: 15%; right: 15%; z-index: 0;"></div>
        </div>
        

        <div class="row g-4 position-relative" style="z-index: 1;">

            <div class="col-md-6 col-lg-3">
                <div class="step-card text-center p-4">
                    <div class="step-number mb-3">1</div>
                    <h3 class="h4 mb-3">CREATE ACCOUNT</h3>
                    <p class="text-muted">Sign up in 60 seconds and get ready to explore.</p>
                    <div class="step-icon mt-3">
                        <i class="fas fa-user-check fa-3x text-primary opacity-50"></i>
                    </div>
                    <div class="mt-3">
                        <a href="register.php" class="btn btn-sm btn-outline-primary me-1">Register</a>
                        <a href="login.php" class="btn btn-sm btn-primary">Login</a>
                    </div>
                </div>
            </div>
            

            <div class="col-md-6 col-lg-3">
                <div class="step-card text-center p-4">
                    <div class="step-number mb-3">2</div>
                    <h3 class="h4 mb-3">CHOOSE GEAR</h3>
                    <p class="text-muted">Browse our selection of kayaks, tents, bikes and more.</p>
                    <div class="step-icon mt-3">
                        <i class="fas fa-search fa-3x text-primary opacity-50"></i>
                    </div>
                    <div class="mt-3">
                        <a href="browse.php" class="btn btn-sm btn-outline-primary">Browse</a>
                    </div>
                </div>
            </div>
            

            <div class="col-md-6 col-lg-3">
                <div class="step-card text-center p-4">
                    <div class="step-number mb-3">3</div>
                    <h3 class="h4 mb-3">BOOK & PICKUP</h3>
                    <p class="text-muted">Select dates and pickup in-store or get delivery.</p>
                    <div class="step-icon mt-3">
                        <i class="fas fa-calendar-check fa-3x text-primary opacity-50"></i>
                    </div>
                    <div class="mt-3">
                        <a href="book.php" class="btn btn-sm btn-outline-primary">Bookings</a>
                    </div>
                </div>
            </div>
            

            <div class="col-md-6 col-lg-3">
                <div class="step-card text-center p-4">
                    <div class="step-number mb-3">4</div>
                    <h3 class="h4 mb-3">ADVENTURE AWAITS...</h3>
                    <p class="text-muted">Enjoy your gear and return when you're done.</p>
                    <div class="step-icon mt-3">
                        <i class="fas fa-mountain fa-3x text-primary opacity-50"></i>
                    </div>
                    <div class="mt-3">
                        <a href="return.php" class="btn btn-sm btn-outline-primary">Return Items</a>
                    </div>
                </div>
            </div>
        </div>
        

        <div class="text-center mt-5">
            <a href="register.php" class="btn btn-lg btn-primary px-5">Start Your Adventure</a>
        </div>
    </div>
</section>

    </main>

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
</index.body>

</html>
