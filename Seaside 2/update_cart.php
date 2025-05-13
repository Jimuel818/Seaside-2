<?php
session_start();

if (isset($_POST['item_id']) && isset($_POST['quantity'])) {
    $item_id = $_POST['item_id'];
    $quantity = intval($_POST['quantity']);

    if (isset($_SESSION['cart'])) {
        foreach ($_SESSION['cart'] as $index => &$item) {
            if ($item['id'] == $item_id) {
                if ($quantity > 0) {
                    $item['quantity'] = $quantity;
                    echo json_encode(['status' => 'success', 'item_id' => $item_id, 'quantity' => $quantity, 'total' => number_format($item['price'] * $quantity, 2)]);
                } else {
                    unset($_SESSION['cart'][$index]);
                    $_SESSION['cart'] = array_values($_SESSION['cart']);
                    echo json_encode(['status' => 'removed', 'item_id' => $item_id]);
                }
                exit();
            }
        }
    }
    echo json_encode(['status' => 'error', 'message' => 'Item not found in cart']);
    exit();
} else {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request']);
    exit();
}
?>