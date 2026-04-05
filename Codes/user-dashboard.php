<?php
session_start();
require_once 'config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'user') {
    header("Location: login.php");
    exit();
}

$search    = $_GET['search']    ?? '';
$category  = $_GET['category']  ?? '';
$condition = $_GET['condition'] ?? '';
$message   = '';
$error     = '';

if (isset($_GET['rent_id'])) {
    try {
        $pdo->beginTransaction();
        $equipment_id = $_GET['rent_id'];
        $user_id      = $_SESSION['user_id'];

        $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM rentals WHERE user_id = ? AND status IN ('rented', 'overdue')");
        $stmt->execute([$user_id]);
        $rental_count = $stmt->fetch()['count'];

        if ($rental_count >= 3) {
            $pdo->rollBack();
            $error = "You have reached the maximum limit of 3 active rentals.";
        } else {
            $stmt = $pdo->prepare("SELECT * FROM equipment WHERE equipment_id = ? AND status = 'available'");
            $stmt->execute([$equipment_id]);
            $equipment = $stmt->fetch();

            if ($equipment) {
                $due_date = date('Y-m-d', strtotime('+7 days'));

                $insert = $pdo->prepare("INSERT INTO rentals (user_id, equipment_id, rental_date, due_date, status) VALUES (?, ?, NOW(), ?, 'rented')");
                $insert->execute([$user_id, $equipment_id, $due_date]);

                $update = $pdo->prepare("UPDATE equipment SET status = 'rented' WHERE equipment_id = ?");
                $update->execute([$equipment_id]);

                $pdo->commit();
                $message = "Equipment rented successfully! Due date: " . date('M d, Y', strtotime($due_date));
            } else {
                $pdo->rollBack();
                $error = "Equipment is not available.";
            }
        }
    } catch (PDOException $e) {
        $pdo->rollBack();
        $error = "Rental failed: " . $e->getMessage();
    }
}


if (isset($_GET['return_id'])) {
    try {
        $pdo->beginTransaction();
        $rental_id = $_GET['return_id'];

        $stmt = $pdo->prepare("SELECT * FROM rentals WHERE rental_id = ? AND user_id = ? AND status IN ('rented', 'overdue')");
        $stmt->execute([$rental_id, $_SESSION['user_id']]);
        $rental = $stmt->fetch();

        if ($rental) {
            $update = $pdo->prepare("UPDATE rentals SET status = 'returned', return_date = NOW() WHERE rental_id = ?");
            $update->execute([$rental_id]);

            $update_equip = $pdo->prepare("UPDATE equipment SET status = 'available' WHERE equipment_id = ?");
            $update_equip->execute([$rental['equipment_id']]);

            $pdo->commit();
            $message = "Equipment returned successfully!";
        } else {
            $pdo->rollBack();
            $error = "Rental not found or already returned.";
        }
    } catch (PDOException $e) {
        $pdo->rollBack();
        $error = "Return failed: " . $e->getMessage();
    }
}





try {
    $stmt = $pdo->prepare("UPDATE rentals SET status = 'overdue' WHERE status = 'rented' AND due_date < CURDATE()");
    $stmt->execute();
} catch (PDOException $e) {
    error_log("Overdue update failed: " . $e->getMessage());
}


$stmt = $pdo->prepare("
    SELECT r.*, e.name AS equipment_name, e.daily_rate, e.status AS equip_status
    FROM rentals r
    JOIN equipment e ON r.equipment_id = e.equipment_id
    WHERE r.user_id = ? AND r.status IN ('rented', 'overdue')
    ORDER BY r.due_date ASC
");
$stmt->execute([$_SESSION['user_id']]);
$active_rentals = $stmt->fetchAll();


$stmt = $pdo->prepare("
    SELECT r.*, e.name AS equipment_name
    FROM rentals r
    JOIN equipment e ON r.equipment_id = e.equipment_id
    WHERE r.user_id = ? AND r.status = 'returned'
    ORDER BY r.return_date DESC
    LIMIT 10
");
$stmt->execute([$_SESSION['user_id']]);
$rental_history = $stmt->fetchAll();

// Build equipment query - only available equipment
$sql    = "SELECT e.*, ec.category_name
           FROM equipment e
           JOIN equipment_categories ec ON e.categoryID = ec.category_id
           WHERE e.status = 'available'";
$params = [];

if (!empty($search)) {
    $sql     .= " AND e.name LIKE ?";
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
$sql .= " ORDER BY e.name";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$available_equipment = $stmt->fetchAll();


$categories = $pdo->query("SELECT * FROM equipment_categories ORDER BY category_name")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Dashboard - ZaramOUTFITTERS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="css/style.css" rel="stylesheet">


</head>
<body class="page-user">

    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container">
            <a class="navbar-brand" href="index.php">ZaramOUTFITTERS</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" data-bs-toggle="dropdown">
                            <i class="fas fa-user-circle text-white"></i><?php echo htmlspecialchars($_SESSION['firstname']); ?>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><a class="dropdown-item" href="profile.php"><i class="fas fa-user me-2"></i>Profile</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item text-danger" href="logout.php"><i class="fas fa-sign-out-alt me-2"></i>Logout</a></li>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="dashboard-header">
        <div class="container">
            <h1 class="display-4">Welcome back, <?php echo htmlspecialchars($_SESSION['firstname']); ?>!</h1>
            <p class="lead">Manage your rentals and find new adventures.</p>
        </div>
    </div>

    <div class="container mb-5">

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


        <div class="row mb-4">
            <div class="col-md-4">
                <div class="card text-center p-4">
                    <i class="fas fa-clock fa-3x text-primary mb-3"></i>
                    <h3><?php echo count($active_rentals); ?></h3>
                    <p class="text-muted">Active Rentals</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card text-center p-4">
                    <i class="fas fa-check-circle fa-3x text-success mb-3"></i>
                    <h3><?php echo count($rental_history); ?></h3>
                    <p class="text-muted">Past Rentals</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card text-center p-4">
                    <i class="fas fa-tag fa-3x text-info mb-3"></i>
                    <h3><?php echo max(0, 3 - count($active_rentals)); ?></h3>
                    <p class="text-muted">Rentals Remaining (Max 3)</p>
                </div>
            </div>
        </div>


        <ul class="nav nav-tabs mb-4" id="dashboardTabs" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active" id="rentals-tab" data-bs-toggle="tab" data-bs-target="#rentals" type="button" role="tab">
                    <i class="fas fa-list me-2"></i>My Active Rentals
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="search-tab" data-bs-toggle="tab" data-bs-target="#search" type="button" role="tab">
                    <i class="fas fa-search me-2"></i>Browse & Rent Equipment
                </button> 
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="history-tab" data-bs-toggle="tab" data-bs-target="#history" type="button" role="tab">
                    <i class="fas fa-history me-2"></i>Rental History
                </button>
            </li>
        </ul>

        <div class="tab-content" id="dashboardTabsContent">


            <div class="tab-pane fade show active" id="rentals" role="tabpanel">
                <h3 class="mb-4">Your Active Rentals</h3>
                <?php if (count($active_rentals) > 0): ?>
                    <div class="row">
                        <?php foreach ($active_rentals as $rental): ?>
                            <div class="col-md-6">
                                <div class="rental-item">
                                    <div class="d-flex justify-content-between align-items-start">
                                        <div>
                                            <h5><?php echo htmlspecialchars($rental['equipment_name']); ?></h5>
                                            <p class="mb-2">
                                                <small class="text-muted">
                                                    <i class="fas fa-calendar-alt me-1"></i>
                                                    Rented: <?php echo date('M d, Y', strtotime($rental['rental_date'])); ?>
                                                </small>
                                            </p>
                                            <p class="due-date mb-2">
                                                <i class="fas fa-exclamation-circle me-1"></i>
                                                Due: <?php echo date('M d, Y', strtotime($rental['due_date'])); ?>
                                            </p>
                                            <?php if ($rental['status'] == 'overdue'): ?>
                                                <span class="status-badge status-overdue">
                                                    <i class="fas fa-exclamation-triangle me-1"></i>Overdue
                                                </span>
                                            <?php else: ?>
                                                <span class="status-badge status-rented">
                                                    <i class="fas fa-check-circle me-1"></i>Rented
                                                </span>
                                            <?php endif; ?>
                                        </div>
                                        <a href="?return_id=<?php echo $rental['rental_id']; ?>"
                                           class="btn btn-return"
                                           onclick="return confirm('Return this equipment?')">
                                            <i class="fas fa-undo-alt me-1"></i>Return
                                        </a>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-2"></i>You have no active rentals.
                        <a href="#" onclick="document.getElementById('search-tab').click()" class="alert-link">Browse equipment</a> to get started!
                    </div>
                <?php endif; ?>
            </div>


            <div class="tab-pane fade" id="search" role="tabpanel">
                <h3 class="mb-4">Find Equipment to Rent</h3>

                <div class="filter-section">
                    <form method="GET" class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label">Search</label>
                            <input type="text" class="form-control" name="search"
                                   value="<?php echo htmlspecialchars($search); ?>"
                                   placeholder="Equipment name...">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Category</label>
                            <select class="form-select" name="category">
                                <option value="">All Categories</option>
                                <?php foreach ($categories as $cat): ?>
                                    <option value="<?php echo $cat['category_id']; ?>"
                                        <?php echo $category == $cat['category_id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($cat['category_name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Condition</label>
                            <select class="form-select" name="condition">
                                <option value="">Any Condition</option>
                                <option value="new"  <?php echo $condition == 'new'  ? 'selected' : ''; ?>>New</option>
                                <option value="good" <?php echo $condition == 'good' ? 'selected' : ''; ?>>Good</option>
                                <option value="fair" <?php echo $condition == 'fair' ? 'selected' : ''; ?>>Fair</option>
                            </select>
                        </div>
                        <div class="col-md-2 d-flex align-items-end">
                            <button type="submit" class="btn btn-primary w-100">
                                <i class="fas fa-filter me-2"></i>Filter
                            </button>
                        </div>
                    </form>
                </div>

                <div class="row">
                    <?php if (count($available_equipment) > 0): ?>
                        <?php foreach ($available_equipment as $item): ?>
                            <div class="col-md-6 col-lg-4 mb-4">
                                <div class="card equipment-card">
                                    <div class="card-body">
                                        <?php
                                            $cond      = $item['condition_status'] ?? 'good';
                                            $condColor = $cond == 'new' ? 'success' : ($cond == 'good' ? 'primary' : 'warning');
                                        ?>
                                        <span class="badge bg-<?php echo $condColor; ?> badge-condition">
                                            <?php echo ucfirst($cond); ?>
                                        </span>
                                        <h5 class="card-title"><?php echo htmlspecialchars($item['name']); ?></h5>
                                        <p class="card-text">
                                            <small class="text-muted">
                                                <i class="fas fa-tag me-1"></i><?php echo htmlspecialchars($item['category_name']); ?>
                                            </small>
                                        </p>
                                        <?php if (!empty($item['description'])): ?>
                                            <p class="card-text text-muted small"><?php echo htmlspecialchars($item['description']); ?></p>
                                        <?php endif; ?>
                                        <div class="d-flex justify-content-between align-items-center mt-3">
                                            <div>
                                                <span class="h5 mb-0">£<?php echo number_format($item['daily_rate'], 2); ?>/day</span>
                                                <br>
                                                <small class="text-muted">Available: <?php echo $item['available_quantity']; ?></small>
                                            </div>
                                            <a href="?rent_id=<?php echo $item['equipment_id']; ?>"
                                               class="btn btn-rent"
                                               onclick="return confirm('Rent this equipment for 7 days?')">
                                                <i class="fas fa-hand-holding-heart me-1"></i>Rent
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="col-12">
                            <div class="alert alert-info">
                                <i class="fas fa-info-circle me-2"></i>No equipment matches your search criteria.
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>


            <div class="tab-pane fade" id="history" role="tabpanel">
                <h3 class="mb-4">Your Rental History</h3>
                <?php if (count($rental_history) > 0): ?>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead class="table-dark">
                                <tr>
                                    <th>Equipment</th>
                                    <th>Rented Date</th>
                                    <th>Returned Date</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($rental_history as $rental): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($rental['equipment_name']); ?></td>
                                        <td><?php echo date('M d, Y', strtotime($rental['rental_date'])); ?></td>
                                        <td><?php echo $rental['return_date'] ? date('M d, Y', strtotime($rental['return_date'])) : '—'; ?></td>
                                        <td><span class="status-badge status-returned">Returned</span></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-2"></i>No rental history yet.
                    </div>
                <?php endif; ?>
            </div>

        </div>
    </div>

    <footer class="bg-dark text-white py-4 mt-5">
        <div class="container text-center">
            <p>&copy; 2026 Zaram<span style="color:#ffc400;font-style:italic;font-weight:bold;">O</span>UTFITTERS. All rights reserved.</p>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>