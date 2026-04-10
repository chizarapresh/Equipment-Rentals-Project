<?php
session_start();
require_once 'config.php';

if (!isset($_SESSION['user_id']) || empty($_SESSION['cart']) || !isset($_SESSION['checkout_data'])) {
    header("Location: browse.php");
    exit();
}

$user_id       = $_SESSION['user_id'];
$cart          = $_SESSION['cart'];
$checkout_data = $_SESSION['checkout_data'];
$duration      = (int)($checkout_data['duration'] ?? 4);
$card_last4    = $checkout_data['card_last4'] ?? '****';
$card_name     = $checkout_data['card_name']  ?? '';
$due_date      = date('Y-m-d', strtotime("+{$duration} days"));

$stmt = $pdo->prepare("SELECT firstname, lastname, email FROM users WHERE user_id = ?");
$stmt->execute([$user_id]);
$user          = $stmt->fetch();
$user_fullname = htmlspecialchars(($user['firstname'] ?? '') . ' ' . ($user['lastname'] ?? ''));
$user_email    = htmlspecialchars($user['email'] ?? '');

$inserted   = [];
$errors     = [];
$total_cost = 0;

try {
    $pdo->beginTransaction();

    $stmt = $pdo->prepare("SELECT COUNT(*) FROM rentals WHERE user_id = ? AND status IN ('rented','overdue')");
    $stmt->execute([$user_id]);
    $existing = (int)$stmt->fetchColumn();

    if ($existing + count($cart) > 3) {
        $pdo->rollBack();
        $_SESSION['cart_error'] = "You already have active rentals. You can have a maximum of 3 active rentals at once.";
        header("Location: checkout.php");
        exit();
    }

    foreach ($cart as $item) {
        $equipment_id = (int)$item['equipment_id'];

        $stmt = $pdo->prepare("SELECT * FROM equipment WHERE equipment_id = ? AND status = 'available' AND available_quantity > 0 FOR UPDATE");
        $stmt->execute([$equipment_id]);
        $equip = $stmt->fetch();

        if (!$equip) {
            $errors[] = "\"" . htmlspecialchars($item['name']) . "\" is no longer available and was skipped.";
            continue;
        }

        $insert = $pdo->prepare("INSERT INTO rentals (user_id, equipment_id, rental_date, due_date, status) VALUES (?, ?, NOW(), ?, 'rented')");
        $insert->execute([$user_id, $equipment_id, $due_date]);
        $rental_id = $pdo->lastInsertId();

        $new_qty    = (int)$equip['available_quantity'] - 1;
        $new_status = $new_qty <= 0 ? 'rented' : 'available';
        $upd = $pdo->prepare("UPDATE equipment SET available_quantity = ?, status = ? WHERE equipment_id = ?");
        $upd->execute([$new_qty, $new_status, $equipment_id]);

        $inserted[] = [
            'rental_id'  => $rental_id,
            'name'       => $item['name'],
            'category'   => $item['category_name'],
            'daily_rate' => $item['daily_rate'],
            'cost'       => $item['daily_rate'] * $duration,
        ];
        $total_cost += $item['daily_rate'] * $duration;
    }

    $pdo->commit();

} catch (PDOException $e) {
    $pdo->rollBack();
    $_SESSION['cart_error'] = "Payment failed: " . $e->getMessage();
    header("Location: checkout.php");
    exit();
}

unset($_SESSION['cart'], $_SESSION['checkout_data']);

$booking_ref = 'ZO-' . str_pad($inserted[0]['rental_id'] ?? rand(10000,99999), 5, '0', STR_PAD_LEFT);
$issued_at   = date('d M Y, H:i');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Booking Confirmed – ZaramOUTFITTERS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Libre+Barcode+39&display=swap" rel="stylesheet">
    <link href="css/style.css" rel="stylesheet">

</head>
<body style="background:#f8f9fa;">

    <div class="confetti-bar"></div>

    <nav class="navbar navbar-dark bg-dark">
        <div class="container">
            <a class="navbar-brand fw-bold" href="index.php">
                Zaram<span style="color:#ffc400;font-style:italic;">O</span>UTFITTERS
            </a>
        </div>
    </nav>


    <div class="success-hero">
        <div class="container">
            <div class="success-icon"><i class="fas fa-check"></i></div>
            <h1 class="display-5 fw-bold mb-2">Booking Confirmed!</h1>
            <p class="lead mb-3">Your adventure gear is reserved and ready for collection.</p>
            <div class="booking-ref-pill"><?php echo $booking_ref; ?></div>
        </div>
    </div>

    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-lg-8">

                <?php if (!empty($errors)): ?>
                <div class="alert alert-warning mb-4">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    <strong>Some items could not be booked:</strong>
                    <ul class="mb-0 mt-1">
                        <?php foreach ($errors as $e): ?><li><?php echo $e; ?></li><?php endforeach; ?>
                    </ul>
                </div>
                <?php endif; ?>

                <?php if (!empty($inserted)): ?>


                <div class="card mb-4" style="border-left:4px solid #28a745;">
                    <div class="card-body">
                        <h6 class="fw-bold mb-3">
                            <i class="fas fa-clipboard-list me-2 text-success"></i>What Happens Next
                        </h6>
                        <ol class="mb-0" style="padding-left:1.2rem;">
                            <li class="mb-2"><strong>Print or save your receipt</strong> below — you'll need it to collect your gear.</li>
                            <li class="mb-2">Come to our store with your receipt and a valid <strong>photo ID</strong>.</li>
                            <li class="mb-2">Return everything by <strong><?php echo date('D, d M Y', strtotime($due_date)); ?></strong> to avoid late fees.</li>
                            <li>Track your rentals anytime in your <a href="user-dashboard.php">Dashboard</a>.</li>
                        </ol>
                    </div>
                </div>


                <div class="d-flex gap-3 flex-wrap align-items-center mb-4">
                    <button onclick="window.print()" class="btn-print">
                        <i class="fas fa-print me-2"></i>Print Receipt
                    </button>
                    <a href="user-dashboard.php" class="btn btn-primary">
                        <i class="fas fa-tachometer-alt me-2"></i>My Dashboard
                    </a>
                    <a href="browse.php" class="btn btn-outline-primary">
                        <i class="fas fa-search me-2"></i>Browse More
                    </a>
                </div>

                <div id="receiptSection" class="receipt-wrapper mb-5">

                    <div class="receipt-header">
                        <h2>Zaram<span style="color:#ffd700;font-style:italic;">O</span>UTFITTERS</h2>
                        <p class="tagline">Adventure Answered!!!</p>
                        <div class="receipt-label">Equipment Rental Receipt</div>
                        <div class="ref-number"><?php echo $booking_ref; ?></div>
                    </div>

                    <div class="receipt-body">

                        <div class="receipt-meta">
                            <div class="receipt-meta-item">
                                <div class="label">Customer</div>
                                <div class="value"><?php echo $user_fullname; ?></div>
                            </div>
                            <div class="receipt-meta-item">
                                <div class="label">Email</div>
                                <div class="value" style="font-size:0.8rem;word-break:break-all;"><?php echo $user_email; ?></div>
                            </div>
                            <div class="receipt-meta-item">
                                <div class="label">Issued</div>
                                <div class="value"><?php echo $issued_at; ?></div>
                            </div>
                            <div class="receipt-meta-item">
                                <div class="label">Payment</div>
                                <div class="value">
                                    <i class="fas fa-credit-card me-1" style="color:#667eea;"></i>
                                    Card •••• <?php echo htmlspecialchars($card_last4); ?>
                                </div>
                            </div>
                            <div class="receipt-meta-item">
                                <div class="label">Collection From</div>
                                <div class="value"><?php echo date('d M Y'); ?></div>
                            </div>
                            <div class="receipt-meta-item">
                                <div class="label">Rental Duration</div>
                                <div class="value"><?php echo $duration; ?> days</div>
                            </div>
                        </div>

                        <hr class="receipt-divider">

                        <div class="receipt-items-header">
                            <span>Item &amp; Category</span>
                            <span>Subtotal</span>
                        </div>

                        <?php foreach ($inserted as $r): ?>
                        <div class="receipt-item-row">
                            <div>
                                <div class="receipt-item-name"><?php echo htmlspecialchars($r['name']); ?></div>
                                <div class="receipt-item-sub">
                                    <?php echo htmlspecialchars($r['category']); ?>
                                    &nbsp;·&nbsp;
                                    £<?php echo number_format($r['daily_rate'], 2); ?>/day
                                    &times; <?php echo $duration; ?> days
                                </div>
                            </div>
                            <div class="receipt-item-cost">£<?php echo number_format($r['cost'], 2); ?></div>
                        </div>
                        <?php endforeach; ?>

                        <div class="receipt-total-row">
                            <span>TOTAL PAID</span>
                            <span class="amount">£<?php echo number_format($total_cost, 2); ?></span>
                        </div>

                        <div class="receipt-due-box">
                            <i class="fas fa-calendar-alt"></i>
                            <div>
                                <strong>Return by:</strong>
                                <?php echo date('l, d M Y', strtotime($due_date)); ?>
                                &nbsp;—&nbsp;Late returns may incur additional charges.
                            </div>
                        </div>

                        <div class="receipt-instructions">
                            <strong><i class="fas fa-store me-1" style="color:#667eea;"></i>Collection Instructions</strong>
                            <ol>
                                <li>Present this receipt (printed or on screen) at the store counter.</li>
                                <li>Show a valid <strong>photo ID</strong> matching the name: <strong><?php echo $user_fullname; ?></strong>.</li>
                                <li>Quote booking reference <strong><?php echo $booking_ref; ?></strong> if asked.</li>
                                <li>Inspect all equipment before leaving and report any pre-existing damage to staff.</li>
                            </ol>
                        </div>

                    </div>

                    <div class="receipt-footer-strip">
                        <div class="receipt-barcode">*<?php echo str_replace('-', '', $booking_ref); ?>*</div>
                        <div style="font-size:0.75rem; letter-spacing:1px;"><?php echo $booking_ref; ?></div>
                        <div class="mt-2">
                            ZaramOUTFITTERS &nbsp;·&nbsp; adventure@zaramoutfitters.com &nbsp;·&nbsp; zaramoutfitters.com
                        </div>
                        <div class="mt-1">Thank you for your booking. Adventure Answered!!!</div>
                    </div>

                </div>

                <?php endif; ?>

            </div>
        </div>
    </div>

    <footer class="bg-dark text-white py-4 mt-2">
        <div class="container text-center">
            <p>&copy; 2026 Zaram<span style="color:#ffc400;font-style:italic;font-weight:bold;">O</span>UTFITTERS. All rights reserved.</p>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>