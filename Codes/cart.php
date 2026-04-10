<?php
session_start();
require_once 'config.php';


if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

$action = $_POST['action'] ?? $_GET['action'] ?? '';

if ($action === 'add' && isset($_POST['equipment_id'])) {

    $equipment_id = (int)$_POST['equipment_id'];


    if (!isset($_SESSION['user_id'])) {
        $_SESSION['cart_pending'] = $equipment_id;
        header("Location: login.php?redirect=browse.php");
        exit();
    }

    if (count($_SESSION['cart']) >= 3) {
        $_SESSION['cart_error'] = "You can only add up to 3 items at once.";
        header("Location: " . ($_SERVER['HTTP_REFERER'] ?? 'browse.php'));
        exit();
    }

    foreach ($_SESSION['cart'] as $item) {
        if ($item['equipment_id'] === $equipment_id) {
            $_SESSION['cart_error'] = "This item is already in your cart.";
            header("Location: " . ($_SERVER['HTTP_REFERER'] ?? 'browse.php'));
            exit();
        }
    }

    $stmt = $pdo->prepare("
        SELECT e.*, ec.category_name
        FROM equipment e
        JOIN equipment_categories ec ON e.categoryID = ec.category_id
        WHERE e.equipment_id = ? AND e.status = 'available' AND e.available_quantity > 0
    ");
    $stmt->execute([$equipment_id]);
    $item = $stmt->fetch();

    if ($item) {
        $_SESSION['cart'][] = [
            'equipment_id'  => $item['equipment_id'],
            'name'          => $item['name'],
            'category_name' => $item['category_name'],
            'daily_rate'    => $item['daily_rate'],
            'image'         => $item['image'] ?? '',
            'condition'     => $item['condition_status'] ?? 'good',
        ];
        $_SESSION['cart_message'] = "\"" . htmlspecialchars($item['name']) . "\" added to your cart!";
    } else {
        $_SESSION['cart_error'] = "Sorry, that item is no longer available.";
    }

    header("Location: " . ($_SERVER['HTTP_REFERER'] ?? 'browse.php'));
    exit();
}

if ($action === 'remove' && isset($_POST['equipment_id'])) {
    $remove_id = (int)$_POST['equipment_id'];
    $_SESSION['cart'] = array_values(
        array_filter($_SESSION['cart'], fn($i) => $i['equipment_id'] !== $remove_id)
    );
    header("Location: checkout.php");
    exit();
}

if ($action === 'clear') {
    $_SESSION['cart'] = [];
    header("Location: browse.php");
    exit();
}

header("Location: browse.php");
exit();