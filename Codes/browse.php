<?php
session_start();
require_once 'config.php';


$cart_message = $_SESSION['cart_message'] ?? '';
$cart_error   = $_SESSION['cart_error']   ?? '';
unset($_SESSION['cart_message'], $_SESSION['cart_error']);

if (!isset($_SESSION['cart'])) $_SESSION['cart'] = [];
$cart_count = count($_SESSION['cart']);


$cart_ids = array_column($_SESSION['cart'], 'equipment_id');

$search    = trim($_GET['search']    ?? '');
$category  = $_GET['category']       ?? '';
$condition = $_GET['condition']      ?? '';
$sort      = $_GET['sort']           ?? 'name';

$sql    = "SELECT e.*, ec.category_name
           FROM equipment e
           JOIN equipment_categories ec ON e.categoryID = ec.category_id
           WHERE e.status = 'available' AND e.available_quantity > 0";
$params = [];

if (!empty($search)) {
    $sql     .= " AND (e.name LIKE ? OR e.description LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}
if (!empty($category)) {
    $sql     .= " AND e.categoryID = ?";
    $params[] = $category;
}
if (!empty($condition)) {
    $sql     .= " AND e.condition_status = ?";
    $params[] = $condition;
}

$allowed_sorts = ['name', 'daily_rate ASC', 'daily_rate DESC', 'available_quantity'];
$sort = in_array($sort, ['name','price_asc','price_desc','quantity']) ? $sort : 'name';
switch ($sort) {
    case 'price_asc':  $sql .= " ORDER BY e.daily_rate ASC";          break;
    case 'price_desc': $sql .= " ORDER BY e.daily_rate DESC";         break;
    case 'quantity':   $sql .= " ORDER BY e.available_quantity DESC"; break;
    default:           $sql .= " ORDER BY ec.category_name, e.name";  break;
}

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$equipment = $stmt->fetchAll();


$by_category = [];
foreach ($equipment as $item) {
    $by_category[$item['category_name']][] = $item;
}


$categories = $pdo->query("SELECT * FROM equipment_categories ORDER BY category_name")->fetchAll();


$total = count($equipment);


function equipmentImage(string $path, string $name): string {

    $keywords = [
        'kayak'        => 'kayak',
        'paddleboard'  => 'paddleboard',
        'surfboard'    => 'surfboard',
        'jet ski'      => 'jetski',
        'life jacket'  => 'lifejacket',
        'wetsuit'      => 'wetsuit',
        'snorkel'      => 'snorkeling',
        'tent'         => 'camping+tent',
        'sleeping bag' => 'sleeping+bag',
        'sleeping pad' => 'camping',
        'trekking'     => 'hiking',
        'headlamp'     => 'headlamp',
        'compass'      => 'compass',
        'map'          => 'hiking+map',
        'camp chair'   => 'camping+chair',
        'camp table'   => 'camping',
        'campervan'    => 'campervan',
        'trailer'      => 'camping+trailer',
        'roof rack'    => 'car+roof+rack',
        'watersports'  => 'kayak+carrier',
        'bike rack'    => 'bike+rack',
        'water shoes'  => 'water+shoes',
    ];
    $keyword = 'outdoor+adventure';
    $lower   = strtolower($name);
    foreach ($keywords as $k => $v) {
        if (str_contains($lower, $k)) { $keyword = $v; break; }
    }

    return htmlspecialchars($path);
}


$cat_icons = [
    'Water Sports'    => 'fa-water',
    'Camping/Hiking'  => 'fa-campground',
    'Transportation'  => 'fa-car-side',
];
$cat_colours = [
    'Water Sports'    => '#0d6efd',
    'Camping/Hiking'  => '#198754',
    'Transportation'  => '#fd7e14',
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Browse Equipment - ZaramOUTFITTERS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="css/style.css" rel="stylesheet">

</head>
<body class="page-browse">


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
                    <li class="nav-item">
                        <a class="nav-link active" href="browse.php">BROWSE</a>
                    </li>
                    <li class="nav-item"><a class="nav-link" href="about.php">ABOUT</a></li>
                    <li class="nav-item"><a class="nav-link" href="contact.php">CONTACT US</a></li>
                </ul>
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" data-bs-toggle="dropdown">
                            <i class="fas fa-user-circle me-1"></i>
                            <?php echo isset($_SESSION['user_name']) ? htmlspecialchars($_SESSION['user_name']) : 'Account'; ?>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <?php if (isset($_SESSION['user_name'])): ?>
                                <li><h6 class="dropdown-header">Welcome, <?php echo htmlspecialchars($_SESSION['firstname']); ?>!</h6></li>
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


    <?php if ($cart_message): ?>
    <div class="alert alert-success alert-dismissible fade show mb-0 rounded-0" role="alert">
        <div class="container">
            <i class="fas fa-check-circle me-2"></i><?php echo htmlspecialchars($cart_message); ?>
            <a href="checkout.php" class="btn btn-sm btn-success ms-3"><i class="fas fa-shopping-cart me-1"></i>View Cart</a>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    </div>
    <?php endif; ?>
    <?php if ($cart_error): ?>
    <div class="alert alert-danger alert-dismissible fade show mb-0 rounded-0" role="alert">
        <div class="container">
            <i class="fas fa-exclamation-triangle me-2"></i><?php echo htmlspecialchars($cart_error); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    </div>
    <?php endif; ?>

    <?php if ($cart_count > 0): ?>
    <div class="floating-cart" id="floatingCart">
        <a href="checkout.php" class="floating-cart-btn">
            <i class="fas fa-shopping-cart"></i>
            <span class="cart-badge"><?php echo $cart_count; ?></span>
        </a>
        <div class="floating-cart-popup">
            <div class="fw-bold mb-2"><i class="fas fa-shopping-cart me-2"></i>Your Cart (<?php echo $cart_count; ?>/3)</div>
            <?php foreach ($_SESSION['cart'] as $ci): ?>
            <div class="cart-popup-item">
                <span><?php echo htmlspecialchars($ci['name']); ?></span>
                <span class="text-primary">£<?php echo number_format($ci['daily_rate'], 2); ?>/day</span>
            </div>
            <?php endforeach; ?>
            <a href="checkout.php" class="btn btn-primary btn-sm w-100 mt-2">
                <i class="fas fa-lock me-1"></i>Checkout
            </a>
            <a href="cart.php?action=clear" class="btn btn-outline-secondary btn-sm w-100 mt-1">
                <i class="fas fa-trash me-1"></i>Clear Cart
            </a>
        </div>
    </div>
    <?php endif; ?>

    <div class="browse-hero">
        <div class="container">
            <h1 class="display-5 fw-bold">
                <i class="fas fa-hiking me-3"></i>Browse Equipment
            </h1>
            <p class="lead mb-0">
                <?php echo $total; ?> item<?php echo $total !== 1 ? 's' : ''; ?> available for rental across
                <?php echo count($by_category); ?> categor<?php echo count($by_category) !== 1 ? 'ies' : 'y'; ?>
            </p>
        </div>
    </div>


    <div class="filter-bar">
        <div class="container">
            <form method="GET" class="row g-2 align-items-center">
                <div class="col-md-4 col-sm-6">
                    <div class="input-group input-group-sm">
                        <span class="input-group-text"><i class="fas fa-search"></i></span>
                        <input type="text" class="form-control" name="search"
                               placeholder="Search equipment..."
                               value="<?php echo htmlspecialchars($search); ?>">
                    </div>
                </div>
                <div class="col-md-2 col-sm-6">
                    <select class="form-select form-select-sm" name="category">
                        <option value="">All Categories</option>
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?php echo $cat['category_id']; ?>"
                                <?php echo $category == $cat['category_id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($cat['category_name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2 col-sm-4">
                    <select class="form-select form-select-sm" name="condition">
                        <option value="">Any Condition</option>
                        <option value="new"  <?php echo $condition == 'new'  ? 'selected' : ''; ?>>New</option>
                        <option value="good" <?php echo $condition == 'good' ? 'selected' : ''; ?>>Good</option>
                        <option value="fair" <?php echo $condition == 'fair' ? 'selected' : ''; ?>>Fair</option>
                    </select>
                </div>
                <div class="col-md-2 col-sm-4">
                    <select class="form-select form-select-sm" name="sort">
                        <option value="name"       <?php echo $sort == 'name'       ? 'selected' : ''; ?>>Name (A–Z)</option>
                        <option value="price_asc"  <?php echo $sort == 'price_asc'  ? 'selected' : ''; ?>>Price (Low–High)</option>
                        <option value="price_desc" <?php echo $sort == 'price_desc' ? 'selected' : ''; ?>>Price (High–Low)</option>
                        <option value="quantity"   <?php echo $sort == 'quantity'   ? 'selected' : ''; ?>>Availability</option>
                    </select>
                </div>
                <div class="col-md-1 col-sm-2">
                    <button type="submit" class="btn btn-primary btn-sm w-100">
                        <i class="fas fa-filter"></i>
                    </button>
                </div>
                <?php if ($search || $category || $condition || $sort !== 'name'): ?>
                <div class="col-md-1 col-sm-2">
                    <a href="browse.php" class="btn btn-outline-secondary btn-sm w-100" title="Clear filters">
                        <i class="fas fa-times"></i>
                    </a>
                </div>
                <?php endif; ?>
            </form>
        </div>
    </div>


    <div class="container py-4">


        <?php if ($search || $category || $condition): ?>
        <div class="results-summary">
            <i class="fas fa-info-circle text-primary me-2"></i>
            Showing <strong><?php echo $total; ?></strong> result<?php echo $total !== 1 ? 's' : ''; ?>
            <?php if ($search): ?>
                for <strong>"<?php echo htmlspecialchars($search); ?>"</strong>
            <?php endif; ?>
            <?php if ($category): ?>
                in <strong><?php
                    foreach ($categories as $c) {
                        if ($c['category_id'] == $category) echo htmlspecialchars($c['category_name']);
                    }
                ?></strong>
            <?php endif; ?>
            &nbsp;—&nbsp;<a href="browse.php">Clear all filters</a>
        </div>
        <?php endif; ?>

        <?php if (empty($equipment)): ?>

            <div class="no-results">
                <i class="fas fa-search fa-4x mb-3 text-muted"></i>
                <h3>No equipment found</h3>
                <p>Try adjusting your search or filters.</p>
                <a href="browse.php" class="btn btn-primary">View All Equipment</a>
            </div>

        <?php elseif (!empty($search) || !empty($category) || !empty($condition)): ?>

            <div class="row g-4">
                <?php foreach ($equipment as $item): ?>
                <?php
                $cond      = $item['condition_status'] ?? 'good';
                $condLabel = ['new' => 'New', 'good' => 'Good', 'fair' => 'Fair'];
                $condClass = ['new' => 'bg-success', 'good' => 'bg-primary', 'fair' => 'bg-warning text-dark'];
                $qty       = (int)$item['available_quantity'];
                $qtyClass  = $qty <= 2 ? 'qty-low' : 'qty-ok';
                $imgSrc    = htmlspecialchars($item['image'] ?? '');
                ?>
                <div class="col-xl-3 col-lg-4 col-md-6">
                    <div class="equipment-card card">
                        <div class="equipment-img-wrapper">
                            <?php if (!empty($imgSrc)): ?>
                                <img src="<?php echo $imgSrc; ?>"
                                alt="<?php echo htmlspecialchars($item['name']); ?>"
                                onerror="this.parentElement.innerHTML='<div class=\'img-placeholder\'><i class=\'fas fa-image fa-2x mb-2\'></i><span><?php echo htmlspecialchars(addslashes($item['name'])); ?></span></div>'">
                                <?php else: ?>
                                    <div class="img-placeholder">
                                        <i class="fas fa-image fa-2x mb-2"></i>
                                        <span><?php echo htmlspecialchars($item['name']); ?></span>
                                    </div>
                            <?php endif; ?>
                            <span class="condition-badge <?php echo $condClass[$cond] ?? 'bg-secondary'; ?>">
                                <?php echo $condLabel[$cond] ?? ucfirst($cond); ?>
                            </span>
                            <span class="availability-badge">
                                <i class="fas fa-boxes me-1"></i><?php echo $qty; ?> left
                            </span>
                        </div>
                        <div class="card-body-custom">
                            <div class="card-title-custom"><?php echo htmlspecialchars($item['name']); ?></div>
                            <div class="card-desc"><?php echo htmlspecialchars($item['description'] ?? ''); ?></div>
                            <div class="card-footer-custom">
                                <div>
                                    <div class="price-tag">£
                                        <?php echo number_format($item['daily_rate'], 2); ?><small>/day</small>
                                    </div>
                                    <div class="<?php echo $qtyClass; ?>" style="font-size:0.78rem;">
                                        <?php if ($qty <= 2): ?>
                                            <i class="fas fa-exclamation-triangle me-1"></i>Only <?php echo $qty; ?> left!
                                            <?php else: ?>
                                                <i class="fas fa-check-circle me-1"></i><?php echo $qty; ?> available
                                            <?php endif; ?>
                                    </div>
                                </div>
                                <?php
                                $in_cart = in_array($item['equipment_id'], $cart_ids);
                                if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
                                    <span class="badge bg-secondary">Admin View</span>
                                <?php elseif ($in_cart): ?>
                                    <span class="btn-in-cart"><i class="fas fa-check me-1"></i>In Cart</span>
                                <?php elseif ($cart_count >= 3): ?>
                                    <span class="btn-cart-full" title="Cart full"><i class="fas fa-ban me-1"></i>Cart Full</span>
                                <?php else: ?>
                                    <form method="POST" action="cart.php" class="d-inline">
                                        <input type="hidden" name="action" value="add">
                                        <input type="hidden" name="equipment_id" value="<?php echo $item['equipment_id']; ?>">
                                        <button type="submit" class="btn-rent-now">
                                            <i class="fas fa-cart-plus me-1"></i><?php echo isset($_SESSION['user_id']) ? 'Add to Cart' : 'Login to Rent'; ?>
                                        </button>
                                    </form>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        
        <?php endforeach; ?>
    </div>

        <?php else: ?>

            <?php foreach ($by_category as $cat_name => $items): ?>
                <div class="category-section">
                    <div class="category-header"
                         style="background: <?php echo $cat_colours[$cat_name] ?? '#667eea'; ?>;">
                        <i class="fas <?php echo $cat_icons[$cat_name] ?? 'fa-box'; ?> fa-2x"></i>
                        <div>
                            <h2><?php echo htmlspecialchars($cat_name); ?></h2>
                            <small><?php echo count($items); ?> item<?php echo count($items) !== 1 ? 's' : ''; ?> available</small>
                        </div>
                    </div>
                    <div class="row g-4">
                        <?php foreach ($items as $item): ?>
                            <div class="col-xl-3 col-lg-4 col-md-6">
                                <?php

                                $cond        = $item['condition_status'] ?? 'good';
                                $condLabel   = ['new' => 'New', 'good' => 'Good', 'fair' => 'Fair'];
                                $condClass   = ['new' => 'bg-success', 'good' => 'bg-primary', 'fair' => 'bg-warning text-dark'];
                                $qty         = (int)$item['available_quantity'];
                                $qtyClass    = $qty <= 2 ? 'qty-low' : 'qty-ok';
                                $imgSrc      = htmlspecialchars($item['image'] ?? '');
                                ?>
                                <div class="equipment-card card">
                                    <div class="equipment-img-wrapper">
                                        <?php if (!empty($imgSrc)): ?>
                                            <img src="<?php echo $imgSrc; ?>"
                                                 alt="<?php echo htmlspecialchars($item['name']); ?>"
                                                 onerror="this.parentElement.innerHTML='<div class=\'img-placeholder\'><i class=\'fas fa-image fa-2x mb-2\'></i><span><?php echo htmlspecialchars(addslashes($item['name'])); ?></span></div>'">
                                        <?php else: ?>
                                            <div class="img-placeholder">
                                                <i class="fas fa-image fa-2x mb-2"></i>
                                                <span><?php echo htmlspecialchars($item['name']); ?></span>
                                            </div>
                                        <?php endif; ?>
                                        <span class="condition-badge <?php echo $condClass[$cond] ?? 'bg-secondary'; ?>">
                                            <?php echo $condLabel[$cond] ?? ucfirst($cond); ?>
                                        </span>
                                        <span class="availability-badge">
                                            <i class="fas fa-boxes me-1"></i><?php echo $qty; ?> left
                                        </span>
                                    </div>
                                    <div class="card-body-custom">
                                        <div class="card-title-custom">
                                            <?php echo htmlspecialchars($item['name']); ?>
                                        </div>
                                        <div class="card-desc">
                                            <?php echo htmlspecialchars($item['description'] ?? ''); ?>
                                        </div>
                                        <div class="card-footer-custom">
                                            <div>
                                                <div class="price-tag">
                                                    £<?php echo number_format($item['daily_rate'], 2); ?>
                                                    <small>/day</small>
                                                </div>
                                                <div class="<?php echo $qtyClass; ?>" style="font-size:0.78rem;">
                                                    <?php if ($qty <= 2): ?>
                                                        <i class="fas fa-exclamation-triangle me-1"></i>Only <?php echo $qty; ?> left!
                                                    <?php else: ?>
                                                        <i class="fas fa-check-circle me-1"></i><?php echo $qty; ?> available
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                            <?php
                                            $in_cart = in_array($item['equipment_id'], $cart_ids);
                                            if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
                                                <span class="badge bg-secondary">Admin View</span>
                                            <?php elseif ($in_cart): ?>
                                                <span class="btn-in-cart"><i class="fas fa-check me-1"></i>In Cart</span>
                                            <?php elseif ($cart_count >= 3): ?>
                                                <span class="btn-cart-full" title="Cart full"><i class="fas fa-ban me-1"></i>Cart Full</span>
                                            <?php else: ?>
                                                <form method="POST" action="cart.php" class="d-inline">
                                                    <input type="hidden" name="action" value="add">
                                                    <input type="hidden" name="equipment_id" value="<?php echo $item['equipment_id']; ?>">
                                                    <button type="submit" class="btn-rent-now">
                                                        <i class="fas fa-cart-plus me-1"></i><?php echo isset($_SESSION['user_id']) ? 'Add to Cart' : 'Login to Rent'; ?>
                                                    </button>
                                                </form>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>

    </div>

    <footer class="bg-dark text-white py-4 mt-5">
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

        const searchInput = document.querySelector('input[name="search"]');
        if (searchInput) {
            searchInput.addEventListener('input', function () {
                const query = this.value.toLowerCase().trim();

                if (window.location.search.includes('search=')) return;

                document.querySelectorAll('.equipment-card').forEach(function (card) {
                    const title = card.querySelector('.card-title-custom')?.textContent.toLowerCase() ?? '';
                    const desc  = card.querySelector('.card-desc')?.textContent.toLowerCase() ?? '';
                    const col   = card.closest('[class*="col-"]');
                    if (col) col.style.display = (title.includes(query) || desc.includes(query)) ? '' : 'none';
                });
            });
        }

        document.querySelectorAll('.qty-low').forEach(function (el) {
            el.closest('.equipment-card').style.border = '2px solid #dc354540';
        });
    </script>
</body>
</html>