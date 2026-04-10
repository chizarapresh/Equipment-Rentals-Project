<?php
session_start();
require_once 'config.php';


if (!isset($_SESSION['user_id'])) {
    header("Location: login.php?redirect=checkout.php");
    exit();
}

if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

$cart  = $_SESSION['cart'];
$error = '';


$duration_options = [4 => '4 days', 7 => '7 days', 14 => '14 days'];
$duration = isset($_POST['duration']) ? (int)$_POST['duration'] : 4;
if (!array_key_exists($duration, $duration_options)) $duration = 4;

$subtotal = 0;
foreach ($cart as $item) {
    $subtotal += $item['daily_rate'] * $duration;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['pay'])) {

    $duration     = (int)($_POST['duration']    ?? 4);
    $card_name    = trim($_POST['card_name']    ?? '');
    $card_number  = preg_replace('/\D/', '', $_POST['card_number'] ?? '');
    $card_expiry  = trim($_POST['card_expiry']  ?? '');
    $card_cvv     = trim($_POST['card_cvv']     ?? '');

    if (empty($cart)) {
        $error = "Your cart is empty.";
    } elseif (strlen($card_name) < 3) {
        $error = "Please enter the name on your card.";
    } elseif (strlen($card_number) < 13 || strlen($card_number) > 19) {
        $error = "Please enter a valid card number (13–19 digits).";
    } elseif (!preg_match('/^\d{2}\/\d{2}$/', $card_expiry)) {
        $error = "Please enter expiry as MM/YY.";
    } elseif (!preg_match('/^\d{3,4}$/', $card_cvv)) {
        $error = "Please enter a valid CVV (3–4 digits).";
    } else {
        $_SESSION['checkout_data'] = [
            'duration'    => $duration,
            'card_last4'  => substr($card_number, -4),
            'card_name'   => $card_name,
        ];
        header("Location: payment-success.php");
        exit();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checkout – ZaramOUTFITTERS</title>
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
            <div class="collapse navbar-collapse">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item"><a class="nav-link" href="browse.php"><i class="fas fa-arrow-left me-1"></i>Continue Shopping</a></li>
                    <li class="nav-item"><a class="nav-link" href="user-dashboard.php"><i class="fas fa-tachometer-alt me-1"></i>Dashboard</a></li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="browse-hero" style="padding: 40px 0 30px;">
        <div class="container">
            <h1 class="display-6 fw-bold"><i class="fas fa-shopping-cart me-3"></i>Checkout</h1>
            <p class="lead mb-0">Review your rental and complete payment</p>
        </div>
    </div>

    <div class="container py-5">

        <?php if (!empty($error)): ?>
            <div class="alert alert-danger"><i class="fas fa-exclamation-triangle me-2"></i><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <?php if (empty($cart)): ?>
            <div class="text-center py-5">
                <i class="fas fa-shopping-cart fa-4x text-muted mb-4"></i>
                <h3 class="text-muted">Your cart is empty</h3>
                <a href="browse.php" class="btn btn-primary mt-3"><i class="fas fa-search me-2"></i>Browse Equipment</a>
            </div>
        <?php else: ?>

        <form method="POST">
        <div class="row g-4">

            <div class="col-lg-7">

                <div class="card mb-4">
                    <div class="card-header" style="background: linear-gradient(135deg,#667eea,#764ba2); color:white; border-radius:15px 15px 0 0;">
                        <h5 class="mb-0"><i class="fas fa-list me-2"></i>Your Rental Cart
                            <span class="badge bg-light text-dark ms-2"><?php echo count($cart); ?>/3</span>
                        </h5>
                    </div>
                    <div class="card-body p-0">
                        <?php foreach ($cart as $item): ?>
                        <div class="cart-item d-flex align-items-center p-3 border-bottom">
                            <div class="cart-item-img me-3">
                                <?php if (!empty($item['image'])): ?>
                                    <img src="<?php echo htmlspecialchars($item['image']); ?>"
                                         alt="<?php echo htmlspecialchars($item['name']); ?>"
                                         onerror="this.style.display='none';this.nextElementSibling.style.display='flex'">
                                    <div class="img-placeholder" style="display:none;width:70px;height:70px;font-size:0.7rem;border-radius:10px;">
                                        <i class="fas fa-image"></i>
                                    </div>
                                <?php else: ?>
                                    <div class="img-placeholder" style="width:70px;height:70px;font-size:0.7rem;border-radius:10px;">
                                        <i class="fas fa-image"></i>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <div class="flex-grow-1">
                                <h6 class="mb-1 fw-bold"><?php echo htmlspecialchars($item['name']); ?></h6>
                                <small class="text-muted"><i class="fas fa-tag me-1"></i><?php echo htmlspecialchars($item['category_name']); ?></small>
                                <div class="mt-1">
                                    <span class="fw-bold text-primary">£<?php echo number_format($item['daily_rate'], 2); ?>/day</span>
                                </div>
                            </div>
                            <div class="text-end">
                                <div class="fw-bold mb-2">
                                    £<?php echo number_format($item['daily_rate'] * $duration, 2); ?>
                                </div>
                                <form method="POST" action="cart.php" class="d-inline">
                                    <input type="hidden" name="action" value="remove">
                                    <input type="hidden" name="equipment_id" value="<?php echo $item['equipment_id']; ?>">
                                    <button type="submit" class="btn btn-sm btn-outline-danger" title="Remove">
                                        <i class="fas fa-trash-alt"></i>
                                    </button>
                                </form>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div class="card mb-4">
                    <div class="card-body">
                        <h6 class="fw-bold mb-3"><i class="fas fa-calendar-alt me-2 text-primary"></i>Rental Duration</h6>
                        <div class="d-flex gap-3 flex-wrap">
                            <?php foreach ($duration_options as $days => $label): ?>
                            <div class="form-check duration-option">
                                <input class="form-check-input" type="radio" name="duration"
                                       id="dur<?php echo $days; ?>" value="<?php echo $days; ?>"
                                       <?php echo $duration === $days ? 'checked' : ''; ?>>
                                <label class="form-check-label duration-label" for="dur<?php echo $days; ?>">
                                    <i class="fas fa-clock me-1"></i><?php echo $label; ?>
                                </label>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        <div class="mt-3 text-muted small">
                            <i class="fas fa-info-circle me-1"></i>
                            Due date: <strong id="dueDatePreview"></strong>
                        </div>
                    </div>
                </div>

                <?php if (count($cart) < 3): ?>
                <div class="text-center">
                    <a href="browse.php" class="btn btn-outline-primary btn-sm">
                        <i class="fas fa-plus me-2"></i>Add More Items (<?php echo 3 - count($cart); ?> slot<?php echo (3 - count($cart)) !== 1 ? 's' : ''; ?> remaining)
                    </a>
                </div>
                <?php endif; ?>

            </div>

            <div class="col-lg-5">

                <div class="card mb-4 order-summary-card">
                    <div class="card-body">
                        <h6 class="fw-bold mb-3"><i class="fas fa-receipt me-2 text-primary"></i>Order Summary</h6>
                        <?php foreach ($cart as $item): ?>
                        <div class="d-flex justify-content-between mb-2 small">
                            <span class="text-muted"><?php echo htmlspecialchars($item['name']); ?> × <?php echo $duration; ?> days</span>
                            <span>£<?php echo number_format($item['daily_rate'] * $duration, 2); ?></span>
                        </div>
                        <?php endforeach; ?>
                        <hr>
                        <div class="d-flex justify-content-between fw-bold fs-5">
                            <span>Total</span>
                            <span class="text-primary" id="totalDisplay">£<?php echo number_format($subtotal, 2); ?></span>
                        </div>
                        <small class="text-muted d-block mt-1">Payable on collection</small>
                    </div>
                </div>

                <div class="card">
                    <div class="card-body">
                        <h6 class="fw-bold mb-1"><i class="fas fa-credit-card me-2 text-primary"></i>Payment Details</h6>
                        <p class="text-muted small mb-3"><i class="fas fa-lock me-1"></i>Demo only – no real payment is taken</p>

                        <div class="mb-3">
                            <label class="form-label small fw-semibold">Name on Card</label>
                            <input type="text" class="form-control" name="card_name"
                                   placeholder="e.g. John Smith"
                                   value="<?php echo htmlspecialchars($_POST['card_name'] ?? ''); ?>"
                                   maxlength="60">
                        </div>

                        <div class="mb-3">
                            <label class="form-label small fw-semibold">Card Number</label>
                            <div class="input-group">
                                <input type="text" class="form-control" name="card_number"
                                       id="cardNumber" placeholder="1234 5678 9012 3456"
                                       maxlength="19" inputmode="numeric">
                                <span class="input-group-text" id="cardBrandIcon">
                                    <i class="fas fa-credit-card text-muted"></i>
                                </span>
                            </div>
                        </div>

                        <div class="row g-2 mb-3">
                            <div class="col-6">
                                <label class="form-label small fw-semibold">Expiry (MM/YY)</label>
                                <input type="text" class="form-control" name="card_expiry"
                                       placeholder="MM/YY" maxlength="5" inputmode="numeric"
                                       value="<?php echo htmlspecialchars($_POST['card_expiry'] ?? ''); ?>">
                            </div>
                            <div class="col-6">
                                <label class="form-label small fw-semibold">CVV</label>
                                <input type="text" class="form-control" name="card_cvv"
                                       placeholder="123" maxlength="4" inputmode="numeric"
                                       value="<?php echo htmlspecialchars($_POST['card_cvv'] ?? ''); ?>">
                            </div>
                        </div>

                        <div class="alert alert-info py-2 px-3 small mb-3">
                            <i class="fas fa-lightbulb me-1"></i>
                            Use any dummy details, e.g. <strong>4242 4242 4242 4242</strong> · <strong>12/28</strong> · <strong>123</strong>
                        </div>

                        <button type="submit" name="pay" class="btn btn-pay w-100">
                            <i class="fas fa-lock me-2"></i>Confirm Rental · <span id="payBtnTotal">£<?php echo number_format($subtotal, 2); ?></span>
                        </button>
                    </div>
                </div>

            </div>
        </div>
        </form>

        <?php endif; ?>
    </div>

    <footer class="bg-dark text-white py-4 mt-5">
        <div class="container text-center">
            <p>&copy; 2026 Zaram<span style="color:#ffc400;font-style:italic;font-weight:bold;">O</span>UTFITTERS. All rights reserved.</p>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
    <script>

    const cartItems = <?php echo json_encode(array_map(fn($i) => [
        'daily_rate' => (float)$i['daily_rate']
    ], $cart)); ?>;

    function updateCheckout() {
        const selected = document.querySelector('input[name="duration"]:checked');
        const days = selected ? parseInt(selected.value) : 4;

        const due = new Date();
        due.setDate(due.getDate() + days);
        const formatted = due.toLocaleDateString('en-GB', { day:'numeric', month:'long', year:'numeric' });
        const preview = document.getElementById('dueDatePreview');
        if (preview) preview.textContent = formatted;

        let total = cartItems.reduce((sum, item) => sum + item.daily_rate * days, 0);
        const fmt = '£' + total.toFixed(2);
        const td = document.getElementById('totalDisplay');
        const pb = document.getElementById('payBtnTotal');
        if (td) td.textContent = fmt;
        if (pb) pb.textContent = fmt;
    }

    document.querySelectorAll('input[name="duration"]').forEach(r => r.addEventListener('change', updateCheckout));
    updateCheckout();

    const cardInput = document.getElementById('cardNumber');
    if (cardInput) {
        cardInput.addEventListener('input', function () {
            let v = this.value.replace(/\D/g, '').substring(0, 16);
            this.value = v.replace(/(.{4})/g, '$1 ').trim();

            const icon = document.querySelector('#cardBrandIcon i');
            if (v.startsWith('4'))      icon.className = 'fab fa-cc-visa text-primary';
            else if (v.startsWith('5')) icon.className = 'fab fa-cc-mastercard text-danger';
            else if (v.startsWith('3')) icon.className = 'fab fa-cc-amex text-info';
            else                         icon.className = 'fas fa-credit-card text-muted';
        });
    }

    document.querySelector('input[name="card_expiry"]')?.addEventListener('input', function () {
        let v = this.value.replace(/\D/g, '');
        if (v.length >= 2) v = v.substring(0,2) + '/' + v.substring(2,4);
        this.value = v;
    });
    </script>
</body>
</html>