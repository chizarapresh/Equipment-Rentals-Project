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
    $image_path       = null;


    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $max_size      = 2 * 1024 * 1024; 
        $file_size     = $_FILES['image']['size'];
        $ext           = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
        $allowed_exts  = ['jpg', 'jpeg', 'png', 'webp', 'gif'];
        $img_info      = @getimagesize($_FILES['image']['tmp_name']);
        
        if (!in_array($ext, $allowed_exts) || $img_info === false) {
            $error = "Invalid image type. Please upload a JPG, PNG, WEBP or GIF image.";

        } elseif ($file_size > $max_size) {
            $error = "Image too large. Maximum size is 2MB.";
        } else {

            $upload_dir = 'images/equipment/';
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0755, true);
            }


        
            $filename   = strtolower(preg_replace('/[^a-zA-Z0-9]/', '-', $name))
                          . '-' . time() . '.' . $ext;
            $dest       = $upload_dir . $filename;

            if (move_uploaded_file($_FILES['image']['tmp_name'], $dest)) {
                $image_path = $dest;
            } else {
                $error = "Failed to save image. Check folder permissions on images/equipment/.";
            }
        }
    }

    if (empty($error)) {
        try {
            $stmt = $pdo->prepare("
                INSERT INTO equipment (name, categoryID, description, daily_rate, total_quantity, available_quantity, condition_status, status, image)
                VALUES (?, ?, ?, ?, ?, ?, ?, 'available', ?)
            ");
            $stmt->execute([$name, $category_id, $description, $daily_rate, $total_quantity, $total_quantity, $condition_status, $image_path]);
            $message = "Equipment added successfully!";
        } catch (PDOException $e) {
            $error = "Failed to add equipment: " . $e->getMessage();
        }
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

    $status = !empty($_POST['status_override']) ? $_POST['status_override'] : $_POST['status'];

    try {
        $stmt = $pdo->prepare("
            UPDATE equipment
            SET name=?, categoryID=?, description=?, daily_rate=?, total_quantity=?, condition_status=?, status=?
            WHERE equipment_id=?
        ");
        $stmt->execute([$name, $category_id, $description, $daily_rate, $total_quantity, $condition_status, $status, $equipment_id]);

        $stmt2 = $pdo->prepare("
            UPDATE equipment
            SET available_quantity = GREATEST(
                0,
                total_quantity - (
                    SELECT COUNT(*) FROM rentals
                    WHERE equipment_id = ? AND status IN ('rented','overdue')
                )
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

            $stmt = $pdo->prepare("UPDATE equipment SET status = 'maintenance' WHERE equipment_id = ?");
            $stmt->execute([$equipment_id]);
            $message = "Equipment has active rentals and cannot be deleted. It has been automatically set to Maintenance and hidden from users.";
        } else {
            $stmt = $pdo->prepare("DELETE FROM equipment WHERE equipment_id = ?");
            $stmt->execute([$equipment_id]);
            $message = "Equipment deleted successfully!";
        }
    } catch (PDOException $e) {
        $error = "Failed to delete equipment: " . $e->getMessage();
    }
}

if (isset($_POST['update_equipment_image'])) {
    $equipment_id = $_POST['equipment_id'];
    
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $max_size = 2 * 1024 * 1024;
        $ext = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
        $allowed_exts = ['jpg', 'jpeg', 'png', 'webp', 'gif'];
        $img_info = @getimagesize($_FILES['image']['tmp_name']);
        
        if (!in_array($ext, $allowed_exts) || $img_info === false) {
            $error = "Invalid image type. Please upload a JPG, PNG, WEBP or GIF image.";
        } elseif ($_FILES['image']['size'] > $max_size) {
            $error = "Image too large. Maximum size is 2MB.";
        } else {
            $stmt = $pdo->prepare("SELECT image FROM equipment WHERE equipment_id = ?");
            $stmt->execute([$equipment_id]);
            $current = $stmt->fetch();
            if ($current && !empty($current['image']) && file_exists($current['image'])) {
                unlink($current['image']);
            }
            
            $upload_dir = 'images/equipment/';
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0755, true);
            }
            
            $stmt = $pdo->prepare("SELECT name FROM equipment WHERE equipment_id = ?");
            $stmt->execute([$equipment_id]);
            $equip_name = $stmt->fetchColumn();
            
            $filename = strtolower(preg_replace('/[^a-zA-Z0-9]/', '-', $equip_name)) 
                        . '-' . time() . '.' . $ext;
            $dest = $upload_dir . $filename;
            
            if (move_uploaded_file($_FILES['image']['tmp_name'], $dest)) {
                $stmt = $pdo->prepare("UPDATE equipment SET image = ? WHERE equipment_id = ?");
                $stmt->execute([$dest, $equipment_id]);
                $message = "Equipment image updated successfully!";
            } else {
                $error = "Failed to save image.";
            }
        }
    } else {
        $error = "Please select an image to upload.";
    }
}

if (isset($_POST['update_available_quantity'])) {
    $equipment_id   = $_POST['equipment_id'];
    $new_available  = (int)$_POST['available_quantity'];
    $total_quantity = (int)$_POST['total_quantity'];

    $chk = $pdo->prepare("SELECT COUNT(*) FROM rentals WHERE equipment_id = ? AND status IN ('rented','overdue')");
    $chk->execute([$equipment_id]);
    $rented_now    = (int)$chk->fetchColumn();
    $max_available = $total_quantity - $rented_now;

    if ($new_available < 0) {
        $error = "Available quantity cannot be negative.";
    } elseif ($new_available > $max_available) {
        $error = "Cannot set available to {$new_available} — {$rented_now} unit(s) are currently out on rent, so the maximum is {$max_available}.";
    } else {
        try {
            $stmt = $pdo->prepare("UPDATE equipment SET available_quantity = ? WHERE equipment_id = ?");
            $stmt->execute([$new_available, $equipment_id]);
            $message = "Available quantity updated successfully!";
        } catch (PDOException $e) {
            $error = "Failed to update available quantity: " . $e->getMessage();
        }
    }
}


if (isset($_POST['add_user'])) {
    $firstname      = trim($_POST['new_firstname']);
    $lastname       = trim($_POST['new_lastname']);
    $email          = trim($_POST['new_email']);
    $phone          = trim($_POST['new_phone']);
    $dob            = $_POST['new_dob'];
    $role           = $_POST['new_role'];
    $account_status = $_POST['new_account_status'];
    $password       = $_POST['new_password'];

    if (empty($firstname) || empty($lastname) || empty($email) || empty($password)) {
        $error = "First name, last name, email and password are required.";
    } elseif (strlen($password) < 8) {
        $error = "Password must be at least 8 characters.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Invalid email address.";
    } else {
        try {

            $check = $pdo->prepare("SELECT user_id FROM users WHERE email = ?");
            $check->execute([$email]);
            if ($check->fetch()) {
                $error = "That email address is already registered.";
            } else {
                $hashed = password_hash($password, PASSWORD_DEFAULT);
                $stmt   = $pdo->prepare("
                    INSERT INTO users (firstname, lastname, email, password_hash, phone, DOB, role, account_status)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?)
                ");
                $stmt->execute([$firstname, $lastname, $email, $hashed, $phone, $dob ?: null, $role, $account_status]);
                $message = "User '{$firstname} {$lastname}' created successfully!";
            }
        } catch (PDOException $e) {
            $error = "Failed to create user: " . $e->getMessage();
        }
    }
}


if (isset($_POST['update_user'])) {
    $user_id        = $_POST['user_id'];
    $firstname      = trim($_POST['firstname']);
    $lastname       = trim($_POST['lastname']);
    $email          = trim($_POST['email']);
    $dob            = $_POST['dob'];
    $phone          = trim($_POST['phone']) ;
    $role           = $_POST['role'];
    $account_status = $_POST['account_status'];
    $new_password = $_POST['new_password']??'';

    try {
        $stmt = $pdo->prepare("UPDATE users SET firstname=?, lastname=?, email=?,DOB=?, phone=?, role=?, account_status=?
            WHERE user_id=?
        ");
        $stmt->execute([$firstname, $lastname, $email, $dob?:null, $phone, $role, $account_status, $user_id]);
        if (!empty($new_password)) {
            if(strlen($new_password) < 8) {
                $error = "Password must be at least 8 characters for user: $firstname";
            } else {
                $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("UPDATE users SET password_hash = ? WHERE user_id = ?");
                $stmt->execute([$hashed_password, $user_id]);
                $message = "User updated successfully! Password changed for $firstname $lastname";
            }
        } else {
            $message = "User updated successfully!";
        }
        if (empty($error)) {
            $message = isset($message) ? $message : "User updated successfully!";
        }
    } catch (PDOException $e) {
        $error = "Failed to update user: " . $e->getMessage();
    }
}

if (isset($_POST['reset_user_password'])) {
    $user_id = $_POST['user_id'];
    $new_password = $_POST['new_password'];
    
    if (strlen($new_password) < 8) {
        $error = "Password must be at least 8 characters.";
    } else {
        try {
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("UPDATE users SET password_hash = ? WHERE user_id = ?");
            $stmt->execute([$hashed_password, $user_id]);
            $message = "Password reset successfully for user ID: {$user_id}";
        } catch (PDOException $e) {
            $error = "Failed to reset password: " . $e->getMessage();
        }
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

if (isset($_POST['extend_rental'])) {
    $rental_id   = (int)$_POST['rental_id'];
    $extend_days = (int)$_POST['extend_days'];

    if ($extend_days < 1 || $extend_days > 2) {
        $error = "Extension must be 1 or 2 days only.";
    } else {
        try {
            $stmt = $pdo->prepare("SELECT due_date, status FROM rentals WHERE rental_id = ?");
            $stmt->execute([$rental_id]);
            $rental = $stmt->fetch();

            if (!$rental || !in_array($rental['status'], ['rented', 'overdue'])) {
                $error = "Rental not found or already returned.";
            } else {
                $new_due = date('Y-m-d', strtotime($rental['due_date'] . " +{$extend_days} days"));
                $stmt = $pdo->prepare("UPDATE rentals SET due_date = ?, status = 'rented' WHERE rental_id = ?");
                $stmt->execute([$new_due, $rental_id]);
                $message = "Rental #{$rental_id} extended by {$extend_days} day(s). New due date: " . date('d M Y', strtotime($new_due));
            }
        } catch (PDOException $e) {
            $error = "Failed to extend rental: " . $e->getMessage();
        }
    }
}

if (isset($_GET['mark_read'])) {
    $msg_id = (int)$_GET['mark_read'];
    try { $pdo->prepare("UPDATE contact_messages SET status = 'read' WHERE message_id = ?")->execute([$msg_id]); $message = "Message marked as read."; } catch (PDOException $e) {}
}
if (isset($_GET['delete_message'])) {
    $msg_id = (int)$_GET['delete_message'];
    try { $pdo->prepare("DELETE FROM contact_messages WHERE message_id = ?")->execute([$msg_id]); $message = "Message deleted."; } catch (PDOException $e) {}
}

$equipment  = $pdo->query("
    SELECT e.*, ec.category_name,
           (e.total_quantity - e.available_quantity) AS rented_count
    FROM equipment e
    JOIN equipment_categories ec ON e.categoryID = ec.category_id
    ORDER BY ec.category_name, e.name
")->fetchAll();

$categories = $pdo->query("SELECT * FROM equipment_categories ORDER BY category_name")->fetchAll();
$users      = $pdo->query("SELECT * FROM users ORDER BY user_id DESC")->fetchAll();

$stats = $pdo->query("
    SELECT
        (SELECT COUNT(*) FROM equipment WHERE status = 'available')   AS available_equipment,
        (SELECT COUNT(*) FROM equipment WHERE status = 'rented')      AS rented_equipment,
        (SELECT COUNT(*) FROM equipment WHERE status = 'maintenance') AS maintenance_equipment,
        (SELECT COUNT(*) FROM users    WHERE role = 'user')           AS total_users,
        (SELECT COUNT(*) FROM rentals  WHERE status = 'rented')       AS active_rentals,
        (SELECT COUNT(*) FROM rentals  WHERE status = 'overdue')      AS overdue_rentals
")->fetch();

$low_stock = $pdo->query("
    SELECT e.name, e.available_quantity, e.total_quantity, ec.category_name
    FROM equipment e
    JOIN equipment_categories ec ON e.categoryID = ec.category_id
    WHERE e.available_quantity <= 2
      AND e.status != 'maintenance'
    ORDER BY e.available_quantity ASC, e.name
")->fetchAll();

try {
    $contact_messages = $pdo->query("SELECT * FROM contact_messages ORDER BY submitted_at DESC LIMIT 100")->fetchAll();
    $unread_count = count(array_filter($contact_messages, fn($m) => $m['status'] === 'unread'));
} catch (PDOException $e) { $contact_messages = []; $unread_count = 0; }

$overdue_detail = $pdo->query("
    SELECT r.rental_id, r.due_date, u.firstname, u.lastname, e.name AS equipment_name
    FROM rentals r
    JOIN users u     ON r.user_id      = u.user_id
    JOIN equipment e ON r.equipment_id = e.equipment_id
    WHERE r.status = 'overdue'
    ORDER BY r.due_date ASC
    LIMIT 10
")->fetchAll();
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
<body class="page-admin">

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

        <?php if (!empty($low_stock)): ?>
        <div class="alert alert-warning alert-dismissible fade show mb-4" role="alert">
            <div class="d-flex align-items-start gap-3">
                <i class="fas fa-exclamation-triangle fa-2x text-warning flex-shrink-0 mt-1"></i>
                <div class="flex-grow-1">
                    <h6 class="fw-bold mb-2">
                        Low Stock Warning &mdash;
                        <?php echo count($low_stock); ?> item<?php echo count($low_stock) !== 1 ? 's' : ''; ?> running low
                    </h6>
                    <div class="d-flex flex-wrap gap-2 mb-2">
                        <?php foreach ($low_stock as $ls): ?>
                        <span class="badge fs-6 px-3 py-2 <?php echo (int)$ls['available_quantity'] === 0 ? 'bg-danger' : 'bg-warning text-dark'; ?>">
                            <i class="fas fa-box me-1"></i>
                            <?php echo htmlspecialchars($ls['name']); ?>
                            &mdash; <?php echo $ls['available_quantity']; ?>/<?php echo $ls['total_quantity']; ?> left
                        </span>
                        <?php endforeach; ?>
                    </div>
                    <small>
                        <a href="?tab=equipment" class="alert-link fw-semibold">
                            <i class="fas fa-tools me-1"></i>Go to Equipment tab
                        </a> to restock or adjust quantities.
                    </small>
                </div>
            </div>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php endif; ?>

        <?php if (!empty($overdue_detail)): ?>
        <div class="alert alert-danger alert-dismissible fade show mb-4" role="alert">
            <div class="d-flex align-items-start gap-3">
                <i class="fas fa-clock fa-2x text-danger flex-shrink-0 mt-1"></i>
                <div class="flex-grow-1">
                    <h6 class="fw-bold mb-2">
                        Overdue Rentals &mdash; <?php echo count($overdue_detail); ?> rental<?php echo count($overdue_detail) !== 1 ? 's' : ''; ?> past due date
                    </h6>
                    <div class="table-responsive">
                        <table class="table table-sm table-bordered mb-1" style="background:white; font-size:0.85rem;">
                            <thead class="table-dark">
                                <tr>
                                    <th>Rental #</th>
                                    <th>Customer</th>
                                    <th>Equipment</th>
                                    <th>Due Date</th>
                                    <th>Days Overdue</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($overdue_detail as $od): ?>
                                <tr>
                                    <td><?php echo $od['rental_id']; ?></td>
                                    <td><?php echo htmlspecialchars($od['firstname'] . ' ' . $od['lastname']); ?></td>
                                    <td><?php echo htmlspecialchars($od['equipment_name']); ?></td>
                                    <td><?php echo date('d M Y', strtotime($od['due_date'])); ?></td>
                                    <td>
                                        <span class="badge bg-danger">
                                            <?php echo max(0, (int)((time() - strtotime($od['due_date'])) / 86400)); ?> day(s)
                                        </span>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <small>
                        <a href="?tab=rentals" class="alert-link fw-semibold">
                            <i class="fas fa-list me-1"></i>View all rentals
                        </a> to manage overdue items.
                    </small>
                </div>
            </div>
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
            <div class="col-md-2">
                <div class="stat-card text-center" style="cursor:pointer;" onclick="window.location='?tab=messages'">
                    <i class="fas fa-envelope fa-2x <?php echo $unread_count > 0 ? 'text-warning' : 'text-secondary'; ?> mb-2"></i>
                    <div class="stat-number"><?php echo $unread_count; ?></div>
                    <small class="text-muted">Unread Messages</small>
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
            <li class="nav-item">
                <a class="nav-link <?php echo $active_tab == 'messages' ? 'active' : ''; ?>" href="?tab=messages">
                    <i class="fas fa-envelope me-1"></i>Messages
                    <?php if ($unread_count > 0): ?><span class="badge bg-danger ms-1"><?php echo $unread_count; ?></span><?php endif; ?>
                </a>
            </li>
        </ul>


        <?php if ($active_tab == 'equipment'): ?>


            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-plus-circle me-2"></i>Add New Equipment</h5>
                </div>
                <div class="card-body">
                    <form method="POST" enctype="multipart/form-data" class="row g-3" id="addEquipmentForm">
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
                        <div class="col-md-4">
                            <label class="form-label">Equipment Image <small class="text-muted">(JPG/PNG/WEBP, max 2MB)</small></label>
                            <input type="file" class="form-control" name="image"
                                   accept="image/jpeg,image/png,image/webp,image/gif"
                                   id="imageInput">
                        </div>
                        <div class="col-md-4 d-flex align-items-center">

                            <div id="imagePreview" style="display:none;">
                                <img id="previewImg" src="" alt="Preview"
                                     style="height:80px; border-radius:8px;
                                            object-fit:cover; border:2px solid #667eea;">
                                <small class="text-muted ms-2" id="previewName"></small>
                            </div>
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
                                <th>Image</th>
                                <th>ID</th>
                                <th>Name</th>
                                <th>Category</th>
                                <th>Description</th>
                                <th>£/Day</th>
                                <th>Total Qty</th>
                                <th>Condition</th>
                                <th>Stock Status</th>
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
                                    <form method="POST" style="display: inline;">
                                        <input type="hidden" name="equipment_id" value="<?php echo $item['equipment_id']; ?>">
                                        <td>
                                            <?php if (!empty($item['image'])): ?>
                                                <img src="<?php echo htmlspecialchars($item['image']); ?>"
                                                alt="<?php echo htmlspecialchars($item['name']); ?>"
                                                style="height:50px; width:70px; object-fit:cover; border-radius:6px;"
                                                onerror="this.src=''; this.style.display='none';">
                                                <?php else: ?>
                                                <div style="height:50px; width:70px; background:#e9ecef;
                                                border-radius:6px; display:flex; align-items:center;
                                                justify-content:center; color:#adb5bd; font-size:0.7rem;">
                                                No image
                                                </div>
                                            <?php endif; ?>
                                            <div class="mt-1">
                                                <button type="button" class="btn btn-sm btn-outline-info" 
                                                data-bs-toggle="modal" 
                                                data-bs-target="#imageModal<?php echo $item['equipment_id']; ?>">
                                                <i class="fas fa-camera"></i> Change
                                                </button>
                                            </div>
                                        </td>
                                        <td><?php echo $item['equipment_id']; ?></td>
                                        <td><input type="text" name="name" value="<?php echo htmlspecialchars($item['name']); ?>" 
                                        class="form-control form-control-sm" required></td>
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
                                        <td><input type="text" name="description" value="<?php echo htmlspecialchars($item['description'] ?? ''); ?>" 
                                        class="form-control form-control-sm"></td>
                                        <td><input type="number" name="daily_rate" value="<?php echo $item['daily_rate']; ?>" 
                                        class="form-control form-control-sm" step="0.01" min="0"></td>
                                        <td>
                                            <input type="number" name="total_quantity" value="<?php echo $item['total_quantity']; ?>" 
                                            class="form-control form-control-sm" min="1" style="width:80px; display:inline-block;"><br>
                                            <small class="text-muted">Available: <?php echo $item['available_quantity']; ?></small>

                                            <div class="mt-1">
                                                <input type="number" name="available_quantity" placeholder="Set available" 
                                                class="form-control form-control-sm" style="width:80px; display:inline-block;"
                                                value="<?php echo $item['available_quantity']; ?>" min="0" max="<?php echo $item['total_quantity']; ?>">
                                                <button type="submit" name="update_available_quantity" class="btn btn-sm btn-warning mt-1">
                                                    <i class="fas fa-edit"></i> Update Qty
                                                </button>
                                            </div>
                                        </td>
                                        <td>
                                            <select name="condition_status" class="form-select form-select-sm">
                                                <option value="new"  <?php echo $item['condition_status'] == 'new'  ? 'selected' : ''; ?>>New</option>
                                                <option value="good" <?php echo $item['condition_status'] == 'good' ? 'selected' : ''; ?>>Good</option>
                                                <option value="fair" <?php echo $item['condition_status'] == 'fair' ? 'selected' : ''; ?>>Fair</option>
                                            </select>
                                        </td>
                                        <td>
                                            <?php
                                            $avail  = (int)$item['available_quantity'];
                                            $rented = (int)$item['rented_count'];
                                            $total  = (int)$item['total_quantity'];
                                            $st     = $item['status'];
                                            ?>
                                            <?php if ($st === 'maintenance'): ?>
                                                <span class="status-badge status-maintenance">
                                                    <i class="fas fa-tools me-1"></i>Maintenance
                                                </span>
                                                <?php else: ?>
                                                    <div style="font-size:0.82rem; line-height:1.7;">
                                                        <div>
                                                            <span class="badge bg-success"><?php echo $avail; ?> available</span>
                                                        </div>
                                                        <?php if ($rented > 0): ?>
                                                            <div>
                                                                <span class="badge bg-warning text-dark"><?php echo $rented; ?> out on rent</span>
                                                            </div>
                                                            <?php endif; ?>
                                                            <div class="text-muted" style="font-size:0.75rem;">
                                                                <?php echo $total; ?> total
                                                            </div>
                                                    </div>
                                                    <input type="hidden" name="status" value="<?php echo htmlspecialchars($st); ?>">
                                            <?php endif; ?>
                                            <select name="status_override" class="form-select form-select-sm mt-1"
                                            onchange="if(this.value) { this.previousElementSibling.value=this.value; this.closest('form').submit(); }">
                                            <option value="">Change status...</option>
                                            <option value="available">Set Available</option>
                                            <option value="maintenance">Set Maintenance</option>
                                            </select>
                                        </td>
                                        
                                        <td class="table-actions">
                                            <button type="submit" name="update_equipment" class="btn btn-sm btn-primary" title="Update Equipment">
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
                                
                                <div class="modal fade" id="imageModal<?php echo $item['equipment_id']; ?>" tabindex="-1">
                                    <div class="modal-dialog modal-sm">
                                        <div class="modal-content">
                                            <div class="modal-header">
                                                <h5 class="modal-title">Update Image for <?php echo htmlspecialchars($item['name']); ?></h5>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                            </div>
                                            <form method="POST" enctype="multipart/form-data">
                                                <div class="modal-body">
                                                    <input type="hidden" name="equipment_id" value="<?php echo $item['equipment_id']; ?>">
                                                    <?php if (!empty($item['image']) && file_exists($item['image'])): ?>
                                                        <img src="<?php echo $item['image']; ?>" class="img-fluid mb-3" style="border-radius:8px;" alt="Current Image">
                                                        <p class="text-muted small">Current Image</p>
                                                    <?php endif; ?>
                                                    <label class="form-label">Select New Image</label>
                                                    <input type="file" class="form-control" name="image" accept="image/jpeg,image/png,image/webp,image/gif" required>
                                                    <small class="text-muted">Max 2MB (JPG, PNG, WEBP, GIF)</small>
                                                </div>
                                                <div class="modal-footer">
                                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                    <button type="submit" name="update_equipment_image" class="btn btn-primary">Upload Image</button>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                </div>
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


            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-user-plus me-2"></i>Add New User</h5>
                </div>
                <div class="card-body">
                    <form method="POST" class="row g-3" id="addUserForm">
                        <div class="col-md-2">
                            <label class="form-label">First Name *</label>
                            <input type="text" class="form-control" name="new_firstname"
                                   placeholder="First name" required>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Last Name *</label>
                            <input type="text" class="form-control" name="new_lastname"
                                   placeholder="Last name" required>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Email Address *</label>
                            <input type="email" class="form-control" name="new_email"
                                   placeholder="email@example.com" required>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Phone</label>
                            <input type="text" class="form-control" name="new_phone"
                                   placeholder="07700 000000">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Date of Birth</label>
                            <input type="date" class="form-control" name="new_dob">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Password *</label>
                            <input type="password" class="form-control" name="new_password"
                                   placeholder="Min 8 characters" required minlength="8">
                            <small class="text-muted">Min 8 characters</small>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Role *</label>
                            <select class="form-select" name="new_role" required>
                                <option value="user">User</option>
                                <option value="admin">Admin</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Account Status *</label>
                            <select class="form-select" name="new_account_status" required>
                                <option value="active">Active</option>
                                <option value="suspended">Suspended</option>
                                <option value="inactive">Inactive</option>
                            </select>
                        </div>
                        <div class="col-md-12">
                            <button type="submit" name="add_user" class="btn btn-primary">
                                <i class="fas fa-user-plus me-1"></i>Create User
                            </button>
                        </div>
                    </form>
                </div>
            </div>


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
                                <th>Reset Password</th>
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
                                    <td>
                                        <input type="password" name="new_password" class="form-control form-control-sm" 
                                        placeholder="Enter new password" style="min-width: 130px;">
                                        <small class="text-muted">Leave blank to keep current</small>
                                    </td>
                                    <td class="table-actions">
                                        <button type="submit" name="update_user" class="btn btn-sm btn-primary">
                                            <i class="fas fa-save"></i> Update
                                        </button>
                                        <a href="?tab=users&delete_user=<?php echo $user['user_id']; ?>"
                                           class="btn btn-sm btn-danger"
                                           onclick="return confirm('Delete this user permanently?')">
                                            <i class="fas fa-trash"></i> Delete
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
            <?php
            $rental_filter = $_GET['rental_status'] ?? 'all';
            $allowed_filters = ['all', 'rented', 'overdue', 'returned'];
            if (!in_array($rental_filter, $allowed_filters)) $rental_filter = 'all';

            $rental_sql = "
                SELECT r.*, u.firstname, u.lastname, u.email, e.name AS equipment_name
                FROM rentals r
                JOIN users u     ON r.user_id      = u.user_id
                JOIN equipment e ON r.equipment_id = e.equipment_id
            ";
            if ($rental_filter !== 'all') {
                $rental_sql .= " WHERE r.status = " . $pdo->quote($rental_filter);
            }
            $rental_sql .= " ORDER BY r.rental_date DESC LIMIT 200";
            $rentals = $pdo->query($rental_sql)->fetchAll();

            $rental_counts = $pdo->query("
                SELECT status, COUNT(*) AS cnt FROM rentals GROUP BY status
            ")->fetchAll(PDO::FETCH_KEY_PAIR);
            $total_rentals = array_sum($rental_counts);
            ?>

            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <h5 class="mb-0"><i class="fas fa-list me-2"></i>All Rentals</h5>

                    <div class="d-flex flex-wrap gap-1">
                        <?php
                        $filter_opts = [
                            'all'      => ['All',      'secondary', $total_rentals],
                            'rented'   => ['Active',   'primary',   $rental_counts['rented']   ?? 0],
                            'overdue'  => ['Overdue',  'danger',    $rental_counts['overdue']  ?? 0],
                            'returned' => ['Returned', 'success',   $rental_counts['returned'] ?? 0],
                        ];
                        foreach ($filter_opts as $val => [$label, $colour, $count]):
                            $active_cls = $rental_filter === $val ? "btn-{$colour}" : "btn-outline-{$colour}";
                        ?>
                        <a href="?tab=rentals&rental_status=<?php echo $val; ?>"
                           class="btn btn-sm <?php echo $active_cls; ?>">
                            <?php echo $label; ?>
                            <span class="badge bg-white text-dark ms-1"><?php echo $count; ?></span>
                        </a>
                        <?php endforeach; ?>
                    </div>
                </div>
                <div class="card-body table-responsive">
                    <?php if (count($rentals) > 0): ?>
                    <p class="text-muted small mb-2">
                        Showing <?php echo count($rentals); ?> rental<?php echo count($rentals) !== 1 ? 's' : ''; ?>
                        <?php echo $rental_filter !== 'all' ? '(filtered by: <strong>' . htmlspecialchars(ucfirst($rental_filter)) . '</strong>)' : ''; ?>
                        <?php echo $total_rentals > 200 ? ' — limited to 200 most recent' : ''; ?>
                    </p>
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
                                <th>Extend</th>
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
                                <td>
                                    <?php if (in_array($rental['status'], ['rented','overdue'])): ?>
                                    <form method="POST" class="d-flex align-items-center gap-1" style="min-width:140px;">
                                        <input type="hidden" name="rental_id" value="<?php echo $rental['rental_id']; ?>">
                                        <select name="extend_days" class="form-select form-select-sm" style="width:70px;">
                                            <option value="1">+1 day</option>
                                            <option value="2">+2 days</option>
                                        </select>
                                        <button type="submit" name="extend_rental"
                                                class="btn btn-sm btn-outline-primary"
                                                onclick="return confirm('Extend this rental?')"
                                                title="Extend due date">
                                            <i class="fas fa-clock"></i>
                                        </button>
                                    </form>
                                    <?php else: ?>
                                        <span class="text-muted" style="font-size:.8rem;">—</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    <?php else: ?>
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle me-2"></i>
                            No <?php echo $rental_filter !== 'all' ? htmlspecialchars($rental_filter) . ' ' : ''; ?>rentals recorded yet.
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>

        <?php if ($active_tab == 'messages'): ?>
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0"><i class="fas fa-envelope me-2"></i>Contact Messages <?php if ($unread_count > 0): ?><span class="badge bg-danger ms-2"><?php echo $unread_count; ?> unread</span><?php endif; ?></h5>
                    <small class="text-muted"><?php echo count($contact_messages); ?> total</small>
                </div>
                <div class="card-body">
                    <?php if (empty($contact_messages)): ?>
                        <div class="alert alert-info"><i class="fas fa-info-circle me-2"></i>No messages yet. Messages from the <a href="contact.php">Contact page</a> appear here.</div>
                    <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead class="table-dark">
                                <tr><th>#</th><th>From</th><th>Subject</th><th>Message</th><th>Received</th><th>Status</th><th>Actions</th></tr>
                            </thead>
                            <tbody>
                                <?php foreach ($contact_messages as $msg): ?>
                                <tr class="<?php echo $msg['status'] === 'unread' ? 'table-warning fw-semibold' : ''; ?>">
                                    <td><?php echo $msg['message_id']; ?></td>
                                    <td>
                                        <div><?php echo htmlspecialchars($msg['name']); ?></div>
                                        <small class="text-muted"><?php echo htmlspecialchars($msg['email']); ?></small>
                                    </td>
                                    <td><span class="badge bg-secondary"><?php echo htmlspecialchars($msg['subject']); ?></span></td>
                                    <td style="max-width:300px;">
                                        <div style="font-size:.85rem;" title="<?php echo htmlspecialchars($msg['message']); ?>" data-bs-toggle="tooltip" data-bs-placement="top">
                                            <?php echo htmlspecialchars(substr($msg['message'], 0, 100)); ?><?php echo strlen($msg['message']) > 100 ? '...' : ''; ?>
                                        </div>
                                    </td>
                                    <td><small class="text-muted"><?php echo date('d M Y H:i', strtotime($msg['submitted_at'])); ?></small></td>
                                    <td>
                                        <?php if ($msg['status'] === 'unread'): ?>
                                            <span class="badge bg-warning text-dark"><i class="fas fa-envelope me-1"></i>Unread</span>
                                        <?php else: ?>
                                            <span class="badge bg-success"><i class="fas fa-envelope-open me-1"></i>Read</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="table-actions">
                                        <?php if ($msg['status'] === 'unread'): ?>
                                        <a href="?tab=messages&mark_read=<?php echo $msg['message_id']; ?>" class="btn btn-sm btn-outline-success" title="Mark as read"><i class="fas fa-check"></i></a>
                                        <?php endif; ?>
                                        <a href="?tab=messages&delete_message=<?php echo $msg['message_id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Delete this message?')" title="Delete"><i class="fas fa-trash"></i></a>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
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

        const imageInput = document.getElementById('imageInput');
        if (imageInput) {
            imageInput.addEventListener('change', function () {
                const file    = this.files[0];
                const preview = document.getElementById('imagePreview');
                const img     = document.getElementById('previewImg');
                const name    = document.getElementById('previewName');

                if (file) {
                    const reader = new FileReader();
                    reader.onload = function (e) {
                        img.src          = e.target.result;
                        name.textContent = file.name;
                        preview.style.display = 'flex';
                        preview.style.alignItems = 'center';
                    };
                    reader.readAsDataURL(file);
                } else {
                    preview.style.display = 'none';
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
        document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach(function(el){new bootstrap.Tooltip(el);});
    </script>
</body>
</html>
