<?php
// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if this is a POST request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'save_cart') {
    // Get cart data from POST
    $cartItems = isset($_POST['cart_items']) ? json_decode($_POST['cart_items'], true) : [];
    $cartTotal = isset($_POST['cart_total']) ? floatval($_POST['cart_total']) : 0;

    // Save to session
    $_SESSION['cart_items'] = $cartItems;
    $_SESSION['cart_total'] = $cartTotal;

    // Return success response
    header('Content-Type: application/json');
    echo json_encode(['success' => true]);
    exit;
}

// If we get here, it's an invalid request
header('HTTP/1.1 400 Bad Request');
echo 'Invalid request';
?>