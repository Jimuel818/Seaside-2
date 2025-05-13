<?php
session_start();
require_once 'db_config.php'; 


if (!isset($_SESSION['user_id'])) {
    $_SESSION['error_message'] = "Please log in to proceed with checkout.";
    header("Location: index.php"); 
    exit();
}
$user_id = $_SESSION['user_id'];

$reservation_id = null;
$reservation_details = null;
$user_details = null;
$cart_items_checkout = [];
$total_checkout_price = 0;
$can_proceed = false;


if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['reservation_id_for_checkout'])) {
    $reservation_id = filter_input(INPUT_POST, 'reservation_id_for_checkout', FILTER_VALIDATE_INT);
    $_SESSION['checkout_reservation_id'] = $reservation_id; 
} elseif (isset($_SESSION['checkout_reservation_id'])) {
    $reservation_id = $_SESSION['checkout_reservation_id'];
}

if (!$reservation_id) {
    $_SESSION['error_message'] = "No reservation selected for checkout. Please go through your cart first.";
    header("Location: cart.php");
    exit();
}


if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action']) && $_POST['action'] == 'confirm_pre_order') {
    if (!isset($_SESSION['checkout_reservation_id']) || !isset($_SESSION['final_checkout_total']) || !isset($_SESSION['user_email_for_payment'])) {
        $_SESSION['error_message'] = "Checkout session expired or data missing. Please try again from your cart.";
        header("Location: cart.php");
        exit();
    }

    $reservation_id_to_confirm = $_SESSION['checkout_reservation_id'];
    $final_amount_to_pay = $_SESSION['final_checkout_total'];
    $user_email_for_payment_record = $_SESSION['user_email_for_payment'];
    $new_reservation_status = 'Confirmed'; 
    $new_payment_status = 'Complete'; 

    try {
        $pdo->beginTransaction();

        
        $stmt_update_res = $pdo->prepare("UPDATE reservations SET status = ? WHERE id = ?");
        $stmt_update_res->execute([$new_reservation_status, $reservation_id_to_confirm]);


    
        $stmt_update_payment = $pdo->prepare(
            "UPDATE payments SET amount_paid = ?, payment_status = ?, payment_date = CURDATE()
             WHERE order_id = ?"
        );
        
        $stmt_update_payment->execute([$final_amount_to_pay, $new_payment_status, $reservation_id_to_confirm]);

        $pdo->commit();

        $_SESSION['success_message'] = "Your pre-order (Reservation ID: $reservation_id_to_confirm) has been confirmed and payment processed (simulated). Thank you!";
        
        
        unset($_SESSION['checkout_reservation_id']);
        unset($_SESSION['final_checkout_total']);
        unset($_SESSION['user_email_for_payment']);
        

        header("Location: order_thank_you.php"); 
        exit();

    } catch (PDOException $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        error_log("Checkout confirmation error: " . $e->getMessage());
        $_SESSION['error_message'] = "There was an error confirming your pre-order. Please try again. DB Error: " . $e->getMessage();
        
    }
}



if ($reservation_id) {
    try {
        
        $stmt_res = $pdo->prepare("SELECT * FROM reservations WHERE id = ?");
        $stmt_res->execute([$reservation_id]);
        $reservation_details = $stmt_res->fetch();

        if (!$reservation_details) {
            $_SESSION['error_message'] = "Invalid reservation ID for checkout.";
            unset($_SESSION['checkout_reservation_id']); 
            header("Location: cart.php");
            exit();
        }

        
        $stmt_user = $pdo->prepare("SELECT name, email, phone_number FROM users WHERE id = ?");
        $stmt_user->execute([$user_id]);
        $user_details = $stmt_user->fetch();
        if ($user_details) {
             $_SESSION['user_email_for_payment'] = $user_details['email']; 
        }


        
        $stmt_cart = $pdo->prepare(
            "SELECT ci.item_id, ci.quantity, mi.name, mi.price
             FROM cart_items ci
             JOIN menu_items mi ON ci.item_id = mi.id
             WHERE ci.user_id = ? AND ci.reservation_id = ?"
        );
        $stmt_cart->execute([$user_id, $reservation_id]);
        $cart_items_checkout = $stmt_cart->fetchAll(PDO::FETCH_ASSOC);

        if (empty($cart_items_checkout) && !isset($_SESSION['success_message'])) { 
            $_SESSION['error_message'] = "Your pre-order for this reservation is empty. Please add items from the menu.";
            
        } else {
            foreach ($cart_items_checkout as $item) {
                $total_checkout_price += $item['price'] * $item['quantity'];
            }
            $_SESSION['final_checkout_total'] = $total_checkout_price; 
            $can_proceed = true; 
        }

    } catch (PDOException $e) {
        error_log("Checkout data fetching error: " . $e->getMessage());
        $_SESSION['error_message'] = "Could not load checkout details. Database error.";
        $can_proceed = false;
    }
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checkout | Seaside Restaurant</title>
    <style>
        :root {
            --primary-font: 'Segoe UI', sans-serif;
            --bg-gradient-start: #e0f7fa; 
            --bg-gradient-end: #b2dfdb;   
            --container-bg: white;
            --primary-text-color: #004d40; 
            --secondary-text-color: #37474f;
            --table-header-bg: #b2dfdb; 
            --table-header-text: #004d40;
            --border-color: #a7ffeb; 
            --button-confirm-bg: #26a69a; 
            --button-confirm-hover-bg: #00897b;
            --button-back-bg: #78909c;
            --button-back-hover-bg: #546e7a;
            --base-font-size: 16px;
        }
        html { font-size: var(--base-font-size); scroll-behavior: smooth; }
        body {
            font-family: var(--primary-font);
            background: linear-gradient(to bottom, var(--bg-gradient-start), var(--bg-gradient-end));
            margin: 0;
            padding: 1.25rem;
            color: var(--secondary-text-color);
            line-height: 1.6;
            min-height: 100vh;
        }
        .container {
            max-width: 50rem; 
            margin: 2rem auto;
            background: var(--container-bg);
            padding: 1.5rem 2rem; 
            border-radius: 0.75rem;
            box-shadow: 0 0.5rem 1.5rem rgba(0,0,0,0.15);
        }
        h1, h2, h3 { color: var(--primary-text-color); text-align: center; }
        h1 { font-size: 2rem; margin-bottom: 1.5rem; }
        h2 { font-size: 1.5rem; margin-top: 2rem; margin-bottom: 1rem; border-bottom: 1px solid var(--border-color); padding-bottom: 0.5rem;}
        h3 { font-size: 1.25rem; margin-bottom: 0.75rem; text-align: left; }

        .summary-section { margin-bottom: 2rem; }
        .summary-section p { margin: 0.5rem 0; font-size: 1rem; }
        .summary-section strong { font-weight: 600; color: var(--primary-text-color); }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 1.5rem;
        }
        table th, table td {
            padding: 0.75rem;
            border-bottom: 1px solid var(--border-color);
            text-align: left;
            vertical-align: middle;
        }
        table th {
            background: var(--table-header-bg);
            color: var(--table-header-text);
            font-size: 0.9rem;
            text-transform: uppercase;
        }
        table td.item-name { font-weight: 500; }
        table td.price, table td.quantity, table td.subtotal { text-align: right; }
        
        .grand-total {
            text-align: right;
            margin-top: 1rem;
            font-size: 1.25rem;
            font-weight: bold;
            color: var(--primary-text-color);
            padding-top: 1rem;
            border-top: 2px solid var(--primary-text-color);
        }
        .actions {
            display: flex;
            flex-wrap: wrap;
            justify-content: space-between;
            align-items: center;
            margin-top: 2rem;
            padding-top: 1.5rem;
            border-top: 1px solid var(--border-color);
        }
        .action-button {
            color: white;
            padding: 0.75rem 1.5rem;
            border: none;
            border-radius: 0.375rem;
            cursor: pointer;
            transition: background-color 0.3s ease, transform 0.2s ease;
            text-decoration: none;
            font-size: 1rem;
            font-weight: 500;
            display: inline-block;
        }
        .action-button:hover { transform: translateY(-2px); }

        .confirm-btn { background: var(--button-confirm-bg); }
        .confirm-btn:hover { background: var(--button-confirm-hover-bg); }
        .back-btn { background: var(--button-back-bg); }
        .back-btn:hover { background: var(--button-back-hover-bg); }
        .disabled-btn, .disabled-btn:hover {
            background: #bdbdbd;
            cursor: not-allowed;
            transform: none;
            opacity: 0.7;
        }
        .message {
            text-align: center;
            padding: 1rem;
            margin-bottom: 1.5rem;
            border-radius: 0.375rem;
        }
        .message.error { color: #c62828; background-color: #ffcdd2; border: 1px solid #ef9a9a;}
        .message.success { color: #2e7d32; background-color: #c8e6c9; border: 1px solid #a5d6a7;}

        @media screen and (max-width: 48em) { 
            .container { padding: 1rem 1.5rem; margin: 1rem auto; }
            h1 { font-size: 1.75rem; }
            h2 { font-size: 1.3rem; }
            table { font-size: 0.9rem; }
            table th, table td { padding: 0.5rem; }
            .grand-total { font-size: 1.1rem; }
            .actions { flex-direction: column; align-items: stretch; }
            .actions .action-button, .actions form { width: 100%; margin-bottom: 0.75rem; }
            .actions .action-button { text-align: center; }
            .actions form:last-child .action-button, .actions a:last-child .action-button { margin-bottom: 0; }
        }
        @media screen and (max-width: 30em) { 
             table thead { display: none; }
             table, table tbody, table tr, table td { display: block; width: 100%; }
             table tr { margin-bottom: 1rem; border: 1px solid var(--border-color); border-radius: 0.25rem; }
             table td {
                 text-align: right; 
                 padding-left: 50%; 
                 position: relative;
                 border-bottom: 0;
             }
             table td::before {
                 content: attr(data-label); 
                 position: absolute;
                 left: 0.5rem; 
                 width: calc(50% - 1rem); 
                 padding-right: 0.625rem;
                 font-weight: bold;
                 text-align: left;
                 white-space: nowrap;
             }
             table td.item-name, table td.price, table td.quantity, table td.subtotal { text-align: right; } 
        }

    </style>
</head>
<body>

<div class="container">
    <h1>Review Your Pre-Order</h1>

    <?php if (isset($_SESSION['error_message'])): ?>
        <p class="message error"><?= htmlspecialchars($_SESSION['error_message'], ENT_QUOTES) ?></p>
        <?php unset($_SESSION['error_message']); ?>
    <?php endif; ?>
    <?php if (isset($_SESSION['success_message'])):  ?>
        <p class="message success"><?= htmlspecialchars($_SESSION['success_message'], ENT_QUOTES) ?></p>
        <?php unset($_SESSION['success_message']); ?>
    <?php endif; ?>

    <?php if ($reservation_details && $user_details): ?>
        <div class="summary-section reservation-info">
            <h2>Reservation Details</h2>
            <p><strong>Reservation ID:</strong> <?= htmlspecialchars((string)$reservation_details['id'], ENT_QUOTES) ?></p>
            <p><strong>Name:</strong> <?= htmlspecialchars($reservation_details['name'], ENT_QUOTES) // This is name from reservation record ?></p>
            <p><strong>Contact:</strong> <?= htmlspecialchars($user_details['phone_number'] ?? $reservation_details['phone_number'], ENT_QUOTES) ?></p>
            <p><strong>Email:</strong> <?= htmlspecialchars($user_details['email'], ENT_QUOTES) ?></p>
            <p><strong>Date:</strong> <?= htmlspecialchars($reservation_details['reservation_date'], ENT_QUOTES) ?></p>
            <p><strong>Time:</strong> <?= htmlspecialchars(date("h:i A", strtotime($reservation_details['reservation_time'])), ENT_QUOTES) ?></p>
            <p><strong>Guests:</strong> <?= htmlspecialchars((string)$reservation_details['guests'], ENT_QUOTES) ?></p>
            <p><strong>Status:</strong> <?= htmlspecialchars($reservation_details['status'], ENT_QUOTES) ?></p>
        </div>

        <?php if (!empty($cart_items_checkout)): ?>
            <div class="summary-section order-items">
                <h2>Pre-Order Items</h2>
                <table>
                    <thead>
                        <tr>
                            <th>Item</th>
                            <th style="text-align:right;">Price</th>
                            <th style="text-align:right;">Quantity</th>
                            <th style="text-align:right;">Subtotal</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($cart_items_checkout as $item): ?>
                            <tr>
                                <td data-label="Item" class="item-name"><?= htmlspecialchars($item['name'], ENT_QUOTES) ?></td>
                                <td data-label="Price" class="price">PHP <?= number_format($item['price'], 2) ?></td>
                                <td data-label="Quantity" class="quantity"><?= htmlspecialchars((string)$item['quantity'], ENT_QUOTES) ?></td>
                                <td data-label="Subtotal" class="subtotal">PHP <?= number_format($item['price'] * $item['quantity'], 2) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <div class="grand-total">
                    Grand Total for Pre-Order: PHP <?= number_format($total_checkout_price, 2) ?>
                </div>
            </div>

            <div class="actions">
                <a href="cart.php" class="action-button back-btn">← Back to Cart</a>
                <form method="POST" action="checkout.php">
                    <input type="hidden" name="action" value="confirm_pre_order">
                    <button type="submit" class="action-button confirm-btn">Confirm Pre-Order &amp; Proceed</button>
                </form>
            </div>
        <?php elseif(!isset($_SESSION['success_message'])):  ?>
            <p class="message error">Your pre-order for this reservation is empty. Please <a href="menu.php" class="link-button">add items from the menu</a>.</p>
            <div class="actions">
                 <a href="menu.php" class="action-button back-btn">← Back to Menu</a>
            </div>
        <?php endif; ?>

    <?php elseif (!isset($_SESSION['success_message'])):  ?>
        <p class="message error">Could not load reservation details. Please try returning to your <a href="cart.php" class="link-button">cart</a> or <a href="user_page.php" class="link-button">your page</a>.</p>
    <?php endif; ?>

</div>
<script>
    
    const confirmForm = document.querySelector('form[action="checkout.php"] button[value="confirm_pre_order"]');
    if (confirmForm) {
        confirmForm.closest('form').addEventListener('submit', function() {
            confirmForm.setAttribute('disabled', 'disabled');
            confirmForm.textContent = 'Processing...';
        });
    }
</script>
</body>
</html>