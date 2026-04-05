<?php
session_start();
require_once 'config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: login.php");
    exit();
}

$message    = '';
$error      = '';
$active_tab = $_GET['tab'] ?? 'equipment';


if (isset($_POST['add_equipment'])) {
    $name             = trim($_POST['name']);
    $category_id      = $_POST['category_id'];
    $description      = trim($_POST['description']);
    $daily_rate       = $_POST['daily_rate'];
    $total_quantity   = $_POST['total_quantity'];
    $condition_status = $_POST['condition_status'];

    try {
        $stmt = $pdo->prepare("
            INSERT INTO equipment (name, categoryID, description, daily_rate, total_quantity, available_quantity, condition_status, status)
            VALUES (?, ?, ?, ?, ?, ?, ?, 'available')
        ");
        $stmt->execute([$name, $category_id, $description, $daily_rate, $total_quantity, $total_quantity, $condition_status]);
        $message = "Equipment added successfully!";
    } catch (PDOException $e) {
        $error = "Failed to add equipment: " . $e->getMessage();
    }
}


if (isset($_POST['update_equipment'])) {
    $equipment_id     = $_POST['equipment_id'];
    $name             = trim($_POST['name']);
    $category_id      = $_POST['category_id'];
    $description      = trim($_POST['description']);
    $daily_rate       = $_POST['daily_rate'];
    $total_quantity   = $_POST['total_quantity'];
    $condition_status = $_POST['condition_status'];
    $status           = $_POST['status'];

    try {
        $stmt = $pdo->prepare("
            UPDATE equipment
            SET name=?, categoryID=?, description=?, daily_rate=?, total_quantity=?, condition_status=?, status=?
            WHERE equipment_id=?
        ");
        $stmt->execute([$name, $category_id, $description, $daily_rate, $total_quantity, $condition_status, $status, $equipment_id]);


        $stmt2 = $pdo->prepare("
            UPDATE equipment
            SET available_quantity = total_quantity - (
                SELECT COUNT(*) FROM rentals WHERE equipment_id = ? AND status IN ('rented','overdue')
            )
            WHERE equipment_id = ?
        ");
        $stmt2->execute([$equipment_id, $equipment_id]);

        $message = "Equipment updated successfully!";
    } catch (PDOException $e) {
        $error = "Failed to update equipment: " . $e->getMessage();
    }
}


if (isset($_GET['delete_equipment'])) {
    $equipment_id = $_GET['delete_equipment'];
    try {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM rentals WHERE equipment_id = ? AND status IN ('rented','overdue')");
        $stmt->execute([$equipment_id]);
        $active_rentals = $stmt->fetchColumn();

        if ($active_rentals > 0) {
            $error = "Cannot delete equipment with active rentals. Set status to Maintenance instead.";
        } else {
            $stmt = $pdo->prepare("DELETE FROM equipment WHERE equipment_id = ?");
            $stmt->execute([$equipment_id]);
            $message = "Equipment deleted successfully!";
        }
    } catch (PDOException $e) {
        $error = "Failed to delete equipment: " . $e->getMessage();
    }
}


if (isset($_POST['update_user'])) {
    $user_id        = $_POST['user_id'];
    $firstname      = trim($_POST['firstname']);
    $lastname       = trim($_POST['lastname']);
    $email          = trim($_POST['email']);
    $phone          = $_POST['phone'];
    $role           = $_POST['role'];
    $account_status = $_POST['account_status'];

    try {
        $stmt = $pdo->prepare("
            UPDATE users SET firstname=?, lastname=?, email=?, phone=?, role=?, account_status=?
            WHERE user_id=?
        ");
        $stmt->execute([$firstname, $lastname, $email, $phone, $role, $account_status, $user_id]);
        $message = "User updated successfully!";
    } catch (PDOException $e) {
        $error = "Failed to update user: " . $e->getMessage();
    }
}


if (isset($_GET['delete_user'])) {
    $user_id = $_GET['delete_user'];
    try {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM rentals WHERE user_id = ? AND status IN ('rented','overdue')");
        $stmt->execute([$user_id]);
        $active_rentals = $stmt->fetchColumn();

        if ($active_rentals > 0) {
            $error = "Cannot delete user with active rentals. Suspend the account instead.";
        } else {
            $stmt = $pdo->prepare("DELETE FROM users WHERE user_id = ?");
            $stmt->execute([$user_id]);
            $message = "User deleted successfully!";
        }
    } catch (PDOException $e) {
        $error = "Failed to delete user: " . $e->getMessage();
    }
}

$equipment  = $pdo->query("
    SELECT e.*, ec.category_name
    FROM equipment e
    JOIN equipment_categories ec ON e.categoryID = ec.category_id
    ORDER BY e.equipment_id DESC
")->fetchAll();

$categories = $pdo->query("SELECT * FROM equipment_categories ORDER BY category_name")->fetchAll();
$users      = $pdo->query("SELECT * FROM users ORDER BY user_id DESC")->fetchAll();

$stats = $pdo->query("
    SELECT
        (SELECT COUNT(*) FROM equipment WHERE status = 'available')  AS available_equipment,
        (SELECT COUNT(*) FROM equipment WHERE status = 'rented')     AS rented_equipment,
        (SELECT COUNT(*) FROM equipment WHERE status = 'maintenance') AS maintenance_equipment,
        (SELECT COUNT(*) FROM users    WHERE role = 'user')          AS total_users,
        (SELECT COUNT(*) FROM rentals  WHERE status = 'rented')      AS active_rentals,
        (SELECT COUNT(*) FROM rentals  WHERE status = 'overdue')     AS overdue_rentals
")->fetch();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - ZaramOUTFITTERS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="css/style.css" rel="stylesheet">
</head>

<body class="admin-page">

    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container">
            <a class="navbar-brand" href="index.php">ZaramOUTFITTERS – Admin Panel</a>
            <div class="ms-auto">
                <span class="text-white me-3">Welcome, <?php echo htmlspecialchars($_SESSION['firstname']); ?>!</span>
                <a href="logout.php" class="btn btn-outline-light btn-sm">
                    <i class="fas fa-sign-out-alt"></i> Logout
                </a>
            </div>
        </div>
    </nav>

    <div class="dashboard-header">
        <div class="container">
            <h1><i class="fas fa-tachometer-alt me-2"></i>Admin Dashboard</h1>
            <p class="mb-0">Manage equipment, users, and monitor rentals</p>
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
            <div class="col-md-2">
                <div class="stat-card text-center">
                    <i class="fas fa-box-open fa-2x text-success mb-2"></i>
                    <div class="stat-number"><?php echo $stats['available_equipment']; ?></div>
                    <small class="text-muted">Available</small>
                </div>
            </div>
            <div class="col-md-2">
                <div class="stat-card text-center">
                    <i class="fas fa-hand-holding-heart fa-2x text-warning mb-2"></i>
                    <div class="stat-number"><?php echo $stats['rented_equipment']; ?></div>
                    <small class="text-muted">Rented</small>
                </div>
            </div>
            <div class="col-md-2">
                <div class="stat-card text-center">
                    <i class="fas fa-tools fa-2x text-secondary mb-2"></i>
                    <div class="stat-number"><?php echo $stats['maintenance_equipment']; ?></div>
                    <small class="text-muted">Maintenance</small>
                </div>
            </div>
            <div class="col-md-2">
                <div class="stat-card text-center">
                    <i class="fas fa-users fa-2x text-primary mb-2"></i>
                    <div class="stat-number"><?php echo $stats['total_users']; ?></div>
                    <small class="text-muted">Users</small>
                </div>
            </div>
            <div class="col-md-2">
                <div class="stat-card text-center">
                    <i class="fas fa-clock fa-2x text-info mb-2"></i>
                    <div class="stat-number"><?php echo $stats['active_rentals']; ?></div>
                    <small class="text-muted">Active Rentals</small>
                </div>
            </div>
            <div class="col-md-2">
                <div class="stat-card text-center">
                    <i class="fas fa-exclamation-triangle fa-2x text-danger mb-2"></i>
                    <div class="stat-number"><?php echo $stats['overdue_rentals']; ?></div>
                    <small class="text-muted">Overdue</small>
                </div>
            </div>
        </div>

        <ul class="nav nav-tabs mb-4">
            <li class="nav-item">
                <a class="nav-link <?php echo $active_tab == 'equipment' ? 'active' : ''; ?>" href="?tab=equipment">
                    <i class="fas fa-tools me-1"></i>Equipment Management
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo $active_tab == 'users' ? 'active' : ''; ?>" href="?tab=users">
                    <i class="fas fa-users me-1"></i>User Management
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo $active_tab == 'rentals' ? 'active' : ''; ?>" href="?tab=rentals">
                    <i class="fas fa-list me-1"></i>All Rentals
                </a>
            </li>
        </ul>


        <?php if ($active_tab == 'equipment'): ?>

            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-plus-circle me-2"></i>Add New Equipment</h5>
                </div>
                <div class="card-body">
                    <form method="POST" class="row g-3" id="addEquipmentForm">
                        <div class="col-md-3">
                            <input type="text" class="form-control" name="name" placeholder="Equipment Name" required>
                            <small id="name_error" class="text-danger" style="display:none;"></small>
                        </div>
                        <div class="col-md-2">
                            <select class="form-control" name="category_id" required>
                                <option value="">Category</option>
                                <?php foreach ($categories as $cat): ?>
                                    <option value="<?php echo $cat['category_id']; ?>">
                                        <?php echo htmlspecialchars($cat['category_name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <small id="category_id_error" class="text-danger" style="display:none;"></small>
                        </div>
                        <div class="col-md-3">
                            <input type="text" class="form-control" name="description" placeholder="Description">
                        </div>
                        <div class="col-md-1">
                            <input type="number" class="form-control" name="daily_rate" placeholder="£/day" step="0.01" min="0.01" required>
                            <small id="daily_rate_error" class="text-danger" style="display:none;"></small>
                        </div>
                        <div class="col-md-1">
                            <input type="number" class="form-control" name="total_quantity" placeholder="Qty" min="1" required>
                            <small id="total_quantity_error" class="text-danger" style="display:none;"></small>
                        </div>
                        <div class="col-md-2">
                            <select class="form-control" name="condition_status">
                                <option value="new">New</option>
                                <option value="good" selected>Good</option>
                                <option value="fair">Fair</option>
                            </select>
                        </div>
                        <div class="col-md-12">
                            <button type="submit" name="add_equipment" class="btn btn-primary">
                                <i class="fas fa-plus me-1"></i>Add Equipment
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <h5 class="mb-0"><i class="fas fa-database me-2"></i>Equipment Inventory (<?php echo count($equipment); ?> items)</h5>
                    <div class="d-flex align-items-center gap-2">
                        <input type="text" id="equipmentSearch"
                               class="form-control form-control-sm"
                               placeholder="&#128269; Search inventory..."
                               style="min-width:220px;">
                        <small id="searchResultCount" class="text-muted" style="white-space:nowrap;"></small>
                    </div>
                </div>
                <div class="card-body table-responsive">
                    <?php if (count($equipment) > 0): ?>
                    <table class="table table-hover align-middle">
                        <thead class="table-dark">
                            <tr>
                                <th>ID</th>
                                <th>Name</th>
                                <th>Category</th>
                                <th>Description</th>
                                <th>£/Day</th>
                                <th>Total</th>
                                <th>Available</th>
                                <th>Condition</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody id="equipmentTableBody">
                            <tr id="noSearchResults" style="display:none;">
                                <td colspan="10" class="text-center text-muted py-3">
                                    <i class="fas fa-search me-2"></i>No equipment matches your search.
                                </td>
                            </tr>
                            <?php foreach ($equipment as $item): ?>
                            <tr>
                                <form method="POST">
                                    <input type="hidden" name="equipment_id" value="<?php echo $item['equipment_id']; ?>">
                                    <td><?php echo $item['equipment_id']; ?></td>
                                    <td><input type="text" name="name" value="<?php echo htmlspecialchars($item['name']); ?>" class="form-control form-control-sm" required></td>
                                    <td>
                                        <select name="category_id" class="form-select form-select-sm">
                                            <?php foreach ($categories as $cat): ?>
                                                <option value="<?php echo $cat['category_id']; ?>"
                                                    <?php echo $item['categoryID'] == $cat['category_id'] ? 'selected' : ''; ?>>
                                                    <?php echo htmlspecialchars($cat['category_name']); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </td>
                                    <td><input type="text" name="description" value="<?php echo htmlspecialchars($item['description'] ?? ''); ?>" class="form-control form-control-sm"></td>
                                    <td><input type="number" name="daily_rate" value="<?php echo $item['daily_rate']; ?>" class="form-control form-control-sm" step="0.01" min="0"></td>
                                    <td><input type="number" name="total_quantity" value="<?php echo $item['total_quantity']; ?>" class="form-control form-control-sm" min="1"></td>
                                    <td class="text-center"><?php echo $item['available_quantity']; ?></td>
                                    <td>
                                        <select name="condition_status" class="form-select form-select-sm">
                                            <option value="new"  <?php echo $item['condition_status'] == 'new'  ? 'selected' : ''; ?>>New</option>
                                            <option value="good" <?php echo $item['condition_status'] == 'good' ? 'selected' : ''; ?>>Good</option>
                                            <option value="fair" <?php echo $item['condition_status'] == 'fair' ? 'selected' : ''; ?>>Fair</option>
                                        </select>
                                    </td>
                                    <td>
                                        <select name="status" class="form-select form-select-sm">
                                            <option value="available"   <?php echo $item['status'] == 'available'   ? 'selected' : ''; ?>>Available</option>
                                            <option value="rented"      <?php echo $item['status'] == 'rented'      ? 'selected' : ''; ?>>Rented</option>
                                            <option value="maintenance" <?php echo $item['status'] == 'maintenance' ? 'selected' : ''; ?>>Maintenance</option>
                                        </select>
                                    </td>
                                    <td class="table-actions">
                                        <button type="submit" name="update_equipment" class="btn btn-sm btn-primary">
                                            <i class="fas fa-save"></i>
                                        </button>
                                        <a href="?tab=equipment&delete_equipment=<?php echo $item['equipment_id']; ?>"
                                           class="btn btn-sm btn-danger"
                                           onclick="return confirm('Delete this equipment permanently?')">
                                            <i class="fas fa-trash"></i>
                                        </a>
                                    </td>
                                </form>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    <?php else: ?>
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle me-2"></i>No equipment in the inventory yet. Add some above!
                        </div>
                    <?php endif; ?>
                </div>
            </div>

        <?php endif; ?>

       
        <?php if ($active_tab == 'users'): ?>
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-users me-2"></i>User Management (<?php echo count($users); ?> users)</h5>
                </div>
                <div class="card-body table-responsive">
                    <table class="table table-hover align-middle">
                        <thead class="table-dark">
                            <tr>
                                <th>ID</th>
                                <th>First Name</th>
                                <th>Last Name</th>
                                <th>Email</th>
                                <th>Phone</th>
                                <th>Role</th>
                                <th>Status</th>
                                <th>Joined</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($users as $user): ?>
                            <tr>
                                <form method="POST">
                                    <input type="hidden" name="user_id" value="<?php echo $user['user_id']; ?>">
                                    <td><?php echo $user['user_id']; ?></td>
                                    <td><input type="text" name="firstname" value="<?php echo htmlspecialchars($user['firstname']); ?>" class="form-control form-control-sm"></td>
                                    <td><input type="text" name="lastname"  value="<?php echo htmlspecialchars($user['lastname']);  ?>" class="form-control form-control-sm"></td>
                                    <td><input type="email" name="email"    value="<?php echo htmlspecialchars($user['email']);    ?>" class="form-control form-control-sm"></td>
                                    <td><input type="text" name="phone"     value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>" class="form-control form-control-sm"></td>
                                    <td>
                                        <select name="role" class="form-select form-select-sm">
                                            <option value="user"  <?php echo $user['role'] == 'user'  ? 'selected' : ''; ?>>User</option>
                                            <option value="admin" <?php echo $user['role'] == 'admin' ? 'selected' : ''; ?>>Admin</option>
                                        </select>
                                    </td>
                                    <td>
                                        <select name="account_status" class="form-select form-select-sm">
                                            <option value="active"    <?php echo $user['account_status'] == 'active'    ? 'selected' : ''; ?>>Active</option>
                                            <option value="suspended" <?php echo $user['account_status'] == 'suspended' ? 'selected' : ''; ?>>Suspended</option>
                                            <option value="inactive"  <?php echo $user['account_status'] == 'inactive'  ? 'selected' : ''; ?>>Inactive</option>
                                        </select>
                                    </td>
                                    <td><small class="text-muted"><?php echo date('M d, Y', strtotime($user['date_created'])); ?></small></td>
                                    <td class="table-actions">
                                        <button type="submit" name="update_user" class="btn btn-sm btn-primary">
                                            <i class="fas fa-save"></i>
                                        </button>
                                        <a href="?tab=users&delete_user=<?php echo $user['user_id']; ?>"
                                           class="btn btn-sm btn-danger"
                                           onclick="return confirm('Delete this user permanently?')">
                                            <i class="fas fa-trash"></i>
                                        </a>
                                    </td>
                                </form>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        <?php endif; ?>

        <?php if ($active_tab == 'rentals'): ?>
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-list me-2"></i>All Rentals</h5>
                </div>
                <div class="card-body table-responsive">
                    <?php
                    $rentals = $pdo->query("
                        SELECT r.*, u.firstname, u.lastname, u.email, e.name AS equipment_name
                        FROM rentals r
                        JOIN users u     ON r.user_id      = u.user_id
                        JOIN equipment e ON r.equipment_id = e.equipment_id
                        ORDER BY r.rental_date DESC
                    ")->fetchAll();
                    ?>
                    <?php if (count($rentals) > 0): ?>
                    <table class="table table-hover align-middle">
                        <thead class="table-dark">
                            <tr>
                                <th>ID</th>
                                <th>User</th>
                                <th>Equipment</th>
                                <th>Rented</th>
                                <th>Due</th>
                                <th>Returned</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($rentals as $rental): ?>
                            <tr>
                                <td><?php echo $rental['rental_id']; ?></td>
                                <td>
                                    <?php echo htmlspecialchars($rental['firstname'] . ' ' . $rental['lastname']); ?>
                                    <br><small class="text-muted"><?php echo htmlspecialchars($rental['email']); ?></small>
                                </td>
                                <td><?php echo htmlspecialchars($rental['equipment_name']); ?></td>
                                <td><?php echo date('M d, Y', strtotime($rental['rental_date'])); ?></td>
                                <td><?php echo date('M d, Y', strtotime($rental['due_date'])); ?></td>
                                <td><?php echo $rental['return_date'] ? date('M d, Y', strtotime($rental['return_date'])) : '—'; ?></td>
                                <td>
                                    <?php
                                    $badge = match($rental['status']) {
                                        'rented'   => ['status-rented',      'Rented'],
                                        'overdue'  => ['status-overdue',     'Overdue'],
                                        'returned' => ['status-available',   'Returned'],
                                        default    => ['status-inactive',    ucfirst($rental['status'])],
                                    };
                                    ?>
                                    <span class="status-badge <?php echo $badge[0]; ?>"><?php echo $badge[1]; ?></span>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    <?php else: ?>
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle me-2"></i>No rentals recorded yet.
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>

    </div>

    <footer class="bg-dark text-white py-4 mt-5">
        <div class="container text-center">
            <p>&copy; 2026 Zaram<span style="color:#ffc400;font-style:italic;font-weight:bold;">O</span>UTFITTERS. All rights reserved.</p>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        const equipmentSearch = document.getElementById('equipmentSearch');
        if (equipmentSearch) {
            equipmentSearch.addEventListener('input', function () {
                const query = this.value.toLowerCase().trim();
                const rows  = document.querySelectorAll('#equipmentTableBody tr');
                let visibleCount = 0;

                rows.forEach(function (row) {

                    const inputs   = row.querySelectorAll('input[type="text"], input[type="number"]');
                    const selects  = row.querySelectorAll('select');
                    let   rowText  = '';

                    inputs.forEach(function (input) {
                        rowText += ' ' + input.value.toLowerCase();
                    });
                    selects.forEach(function (select) {
                        rowText += ' ' + select.options[select.selectedIndex].text.toLowerCase();
                    });

                    if (rowText.includes(query)) {
                        row.style.display = '';
                        visibleCount++;
                    } else {
                        row.style.display = 'none';
                    }
                });

               
                const noResults = document.getElementById('noSearchResults');
                if (noResults) {
                    noResults.style.display = visibleCount === 0 ? '' : 'none';
                }
                
                const counter = document.getElementById('searchResultCount');
                if (counter) {
                    counter.textContent = query === ''
                        ? ''
                        : visibleCount + ' result' + (visibleCount !== 1 ? 's' : '');
                }
            });
        }


        const addEquipmentForm = document.getElementById('addEquipmentForm');
        if (addEquipmentForm) {            
            function validateField(field) {
                const errorEl = document.getElementById(field.name + '_error');
                let   message = '';

                if (field.hasAttribute('required') && field.value.trim() === '') {
                    message = 'This field is required.';
                } else if (field.type === 'number') {
                    const val = parseFloat(field.value);
                    if (isNaN(val) || val <= 0) {
                        message = 'Please enter a value greater than 0.';
                    }
                }

                if (errorEl) {
                    errorEl.textContent = message;
                    errorEl.style.display = message ? 'block' : 'none';
                }
                field.classList.toggle('is-invalid', message !== '');
                field.classList.toggle('is-valid',   message === '' && field.value.trim() !== '');
                return message === '';
            }

            
            addEquipmentForm.querySelectorAll('input, select').forEach(function (field) {
                field.addEventListener('blur',  function () { validateField(this); });
                field.addEventListener('input', function () { validateField(this); });
            });

            
            addEquipmentForm.addEventListener('submit', function (e) {
                let valid = true;
                this.querySelectorAll('input, select').forEach(function (field) {
                    if (!validateField(field)) valid = false;
                });
                if (!valid) {
                    e.preventDefault();                    
                    const firstInvalid = addEquipmentForm.querySelector('.is-invalid');
                    if (firstInvalid) firstInvalid.scrollIntoView({ behavior: 'smooth', block: 'center' });
                }
            });
        }
    </script>
</body>
</html>