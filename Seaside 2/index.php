<?php
session_start();
require_once 'db_config.php'; 


$error_login = $_SESSION['login_error'] ?? '';
$error_register = $_SESSION['register_error'] ?? '';
$register_success_message = $_SESSION['register_success'] ?? '';
$page_error_message = $_SESSION['page_error_message'] ?? ''; // For general page errors
$page_success_message = $_SESSION['page_success_message'] ?? ''; // For success messages

$activeForm = $_SESSION['active_form'] ?? 'login';


unset(
    $_SESSION['login_error'], $_SESSION['register_error'], $_SESSION['register_success'],
    $_SESSION['active_form'], $_SESSION['page_error_message'], $_SESSION['page_success_message']
);

function showError($message_content){
    return !empty($message_content) ? "<p class='error-message form-message'>" . htmlspecialchars($message_content, ENT_QUOTES) . "</p>" : '';
}
function showSuccess($message_content){
    return !empty($message_content) ? "<p class='success-message form-message'>" . htmlspecialchars($message_content, ENT_QUOTES) . "</p>" : '';
}
function isActiveForm($formName, $currentActiveForm){
    return $formName === $currentActiveForm ? 'active' : '';
}

// --- HANDLE RESERVATION CANCELLATION ---
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['cancel_reservation_id']) && isset($_SESSION['user_id'])) {
    $reservation_id_to_cancel = filter_input(INPUT_POST, 'cancel_reservation_id', FILTER_VALIDATE_INT);
    $current_user_id = $_SESSION['user_id'];

    
    $stmt_user_verify = $pdo->prepare("SELECT name, phone_number FROM users WHERE id = ?");
    $stmt_user_verify->execute([$current_user_id]);
    $user_for_verification = $stmt_user_verify->fetch();

    if (!$reservation_id_to_cancel || !$user_for_verification) {
        $_SESSION['page_error_message'] = "Invalid request or user details not found for cancellation.";
        header("Location: " . $_SERVER['PHP_SELF']);
        exit();
    }

    try {
        $pdo->beginTransaction();

        $stmt_get_res = $pdo->prepare("SELECT name, phone_number, status, reservation_date FROM reservations WHERE id = ?");
        $stmt_get_res->execute([$reservation_id_to_cancel]);
        $reservation_to_cancel = $stmt_get_res->fetch();

        if ($reservation_to_cancel &&
            $reservation_to_cancel['name'] === $user_for_verification['name'] &&
            (string)$reservation_to_cancel['phone_number'] === (string)$user_for_verification['phone_number']) {

            
            $cancelable_statuses = ['Pending', 'Confirmed']; 
            $is_past_reservation = strtotime($reservation_to_cancel['reservation_date']) < strtotime(date('Y-m-d'));

            if (in_array($reservation_to_cancel['status'], $cancelable_statuses) && !$is_past_reservation) {
                
                $stmt_cancel_res = $pdo->prepare("UPDATE reservations SET status = 'Cancelled' WHERE id = ?");
                $stmt_cancel_res->execute([$reservation_id_to_cancel]);

                
                $stmt_delete_orders = $pdo->prepare("DELETE FROM cart_items WHERE reservation_id = ? AND user_id = ?");
                $stmt_delete_orders->execute([$reservation_id_to_cancel, $current_user_id]);
                
                

                $pdo->commit();
                $_SESSION['page_success_message'] = "Reservation #{$reservation_id_to_cancel} and any associated pre-orders have been successfully cancelled.";
            } else {
                $reason = $is_past_reservation ? "it is in the past" : "its status is '" . htmlspecialchars($reservation_to_cancel['status'], ENT_QUOTES) . "'";
                $_SESSION['page_error_message'] = "This reservation cannot be cancelled because " . $reason . ".";
                if ($pdo->inTransaction()) $pdo->rollBack(); 
            }
        } else {
            $_SESSION['page_error_message'] = "You are not authorized to cancel this reservation, or it was not found.";
            if ($pdo->inTransaction()) $pdo->rollBack();
        }
    } catch (PDOException $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        error_log("Error cancelling reservation #{$reservation_id_to_cancel}: " . $e->getMessage());
        $_SESSION['page_error_message'] = "Could not cancel reservation due to a database error. Please try again.";
    }
    header("Location: " . $_SERVER['PHP_SELF']); 
    exit();
}


$user_reservations_with_orders = [];
$current_user_name_display = ''; 

if (isset($_SESSION['user_id'])) {
    $current_user_id = $_SESSION['user_id'];
    $current_user_name_for_query = null;
    $current_user_phone_for_query = null;
    $current_user_name_display = htmlspecialchars($_SESSION['name'] ?? 'User', ENT_QUOTES);
    
    try {
        $stmt_user_details = $pdo->prepare("SELECT name, phone_number, email FROM users WHERE id = ?");
        $stmt_user_details->execute([$current_user_id]);
        $user_data = $stmt_user_details->fetch();

        if ($user_data) {
            $current_user_name_for_query = $user_data['name'];
            $current_user_phone_for_query = $user_data['phone_number'];
            if (($_SESSION['name'] ?? '') !== $current_user_name_for_query) {
                 $_SESSION['name'] = $current_user_name_for_query;
            }
            $current_user_name_display = htmlspecialchars($current_user_name_for_query, ENT_QUOTES);

            $stmt_reservations = $pdo->prepare(
                "SELECT * FROM reservations 
                 WHERE name = :name AND phone_number = :phone_number
                 ORDER BY reservation_date DESC, reservation_time DESC" 
            );
            $stmt_reservations->execute([
                ':name' => $current_user_name_for_query,
                ':phone_number' => (string)$current_user_phone_for_query 
            ]);
            $reservations = $stmt_reservations->fetchAll();

            foreach ($reservations as $res) {
                $reservation_id = $res['id'];
                $orders_for_this_reservation = [];
                $total_order_price_for_reservation = 0;

                $stmt_orders = $pdo->prepare(
                    "SELECT ci.quantity, mi.name as item_name, mi.price as item_price 
                     FROM cart_items ci
                     JOIN menu_items mi ON ci.item_id = mi.id
                     WHERE ci.reservation_id = :reservation_id AND ci.user_id = :user_id"
                );
                $stmt_orders->execute([':reservation_id' => $reservation_id, ':user_id' => $current_user_id]);
                $orders_for_this_reservation = $stmt_orders->fetchAll();
                
                foreach($orders_for_this_reservation as $order_item) {
                    $total_order_price_for_reservation += $order_item['item_price'] * $order_item['quantity'];
                }
                $user_reservations_with_orders[] = [
                    'details' => $res,
                    'orders' => $orders_for_this_reservation,
                    'total_order_price' => $total_order_price_for_reservation
                ];
            }
        } else {
            $page_error_message = "Could not verify your user details. Please try logging out and back in.";
        }
    } catch (PDOException $e) {
        error_log("Error on user dashboard (reservations/orders fetch): " . $e->getMessage());
        $page_error_message = "Sorry, we couldn't load your details at the moment due to a database error.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Seaside Restaurant | My Dashboard</title>
    <style>
        
        :root {
            --primary-font: "Segoe UI", sans-serif;
            --bg-gradient-start: #2e6193;
            --bg-gradient-end: #2e8e93;
            --text-color-dark: #333;
            --container-bg: #ffffff;
            --input-bg: #f0f0f0;
            --button-bg: #553434;
            --button-text-color: white;
            --button-hover-bg: #c80606;
            --error-text-color: #c80606;
            --success-text-color: #2e7d32;
            --link-color: #007bff;
            --base-font-size: 16px;
            --border-radius-sm: 0.375rem;
            --border-radius-md: 0.625rem;
            --cancel-button-bg: #dc3545; 
            --cancel-button-hover-bg: #c82333;
        }
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: var(--primary-font); }
        html { font-size: var(--base-font-size); }
        body {
            display: flex; justify-content: center; align-items: flex-start;
            min-height: 100vh; background: linear-gradient(135deg, var(--bg-gradient-start), var(--bg-gradient-end));
            color: var(--text-color-dark); padding: 1rem; overflow-y: auto;
        }
        .container { width: 100%; margin-top: 1rem; margin-bottom: 1rem; }
        
        .form-box {
            width: 100%; max-width: 28.125rem; margin-left: auto; margin-right: auto;
            padding: 1.875rem; background: var(--container-bg); border-radius: var(--border-radius-md);
            box-shadow: 0 0.25rem 0.625rem rgba(0,0,0,0.15); display: none; animation: fadeIn 0.5s ease-in-out;
        }
        .form-box.active { display: block; }
        .form-box h2 {
            font-size: 2rem; text-align: center; margin-bottom: 1.25rem; color: var(--bg-gradient-start);
        }
        .form-box input, .form-box select {
            width: 100%; padding: 0.75rem; background: var(--input-bg);
            border-radius: var(--border-radius-sm); border: 1px solid #ddd; outline: none;
            font-size: 1rem; color: var(--text-color-dark); margin-bottom: 1.25rem;
        }
        .form-box input:focus {
            border-color: var(--bg-gradient-start); box-shadow: 0 0 0 0.125rem rgba(46, 97, 147, 0.25);
        }
        .form-box button {
            width: 100%; padding: 0.75rem; background: var(--button-bg);
            border-radius: var(--border-radius-sm); border: none; cursor: pointer;
            font-size: 1rem; color: var(--button-text-color); font-weight: 500;
            margin-bottom: 1.25rem; transition: background-color 0.3s ease;
        }
        .form-box button:hover { background: var(--button-hover-bg); }
        .form-box p { font-size: 0.9rem; text-align: center; margin-bottom: 0.625rem; }
        .form-box p a { color: var(--link-color); text-decoration: none; font-weight: 500; }
        .form-box p a:hover { text-decoration: underline; }
        .form-message {
            padding: 0.75rem; border-radius: var(--border-radius-sm); font-size: 0.95rem;
            text-align: center; margin-bottom: 1.25rem;
        }
        .error-message.form-message {
            background: #ffebee; color: var(--error-text-color); border: 1px solid var(--error-text-color);
        }
        .success-message.form-message {
            background: #e8f5e9; color: var(--success-text-color); border: 1px solid var(--success-text-color);
        }

        .user-dashboard {
            background-color: var(--container-bg); padding: 1.5rem; border-radius: var(--border-radius-md);
            box-shadow: 0 0.25rem 1.5rem rgba(0,0,0,0.1); max-width: 50rem; width: 100%;
            margin-left: auto; margin-right: auto; text-align: center; animation: fadeIn 0.8s ease-in-out;
        }
        .user-dashboard h2 { color: var(--bg-gradient-start); font-size: 1.75rem; margin-bottom: 0.75rem; }
        .user-dashboard .user-info { font-size: 1rem; margin-bottom: 1.5rem; color: var(--secondary-text-color, #555); }
        .user-dashboard .user-info strong { color: var(--text-color-dark); }
        .user-actions {
            margin-bottom: 1.5rem; display: flex; flex-wrap: wrap; justify-content: center; gap: 0.75rem;
        }
        .user-actions .action-button, .user-dashboard form button[name="logout"] {
            width: auto; padding: 0.625rem 1.25rem; background: var(--button-bg); margin-top: 0.5rem;
            color: var(--button-text-color); text-decoration: none; border-radius: var(--border-radius-sm);
            border: none; cursor: pointer; font-size: 0.9rem; font-weight: 500; transition: background-color 0.3s ease;
        }
        .user-actions .action-button:hover, .user-dashboard form button[name="logout"]:hover{ background: var(--button-hover-bg); }
        
        .page-message { 
            padding: 1rem; border-radius: var(--border-radius-md); margin-bottom: 1.5rem;
            text-align: center; max-width: 50rem; margin-left: auto; margin-right: auto;
            font-size: 1rem;
        }
        .page-message.error { background-color: #ffebee; color: var(--error-text-color); border: 1px solid var(--error-text-color); }
        .page-message.success { background-color: #e8f5e9; color: var(--success-text-color); border: 1px solid var(--success-text-color); }


        .reservations-section { margin-top: 1.5rem; text-align: left; }
        .reservations-section h3 {
            font-size: 1.5rem; color: var(--text-color-dark); border-bottom: 2px solid var(--bg-gradient-end);
            padding-bottom: 0.5rem; margin-bottom: 1rem; text-align: center;
        }
        .reservation-card {
            background: #f8f9fa; border: 1px solid #e0e0e0; border-radius: var(--border-radius-md);
            padding: 1rem; margin-bottom: 1.5rem; animation: fadeInUp 0.7s ease-in-out forwards; opacity:0;
        }
        .reservation-card h4 { font-size: 1.1rem; color: var(--bg-gradient-start); margin-bottom: 0.75rem; }
        .reservation-details-grid {
            display: grid; grid-template-columns: repeat(auto-fit, minmax(12rem, 1fr));
            gap: 0.75rem; margin-bottom: 1rem;
        }
        .reservation-details-grid p {
            font-size: 0.95rem; margin-bottom: 0.25rem; background-color: #e9ecef;
            padding: 0.5rem; border-radius: var(--border-radius-sm); text-align: left;
        }
        .reservation-details-grid p strong { color: var(--bg-gradient-start); display: block; margin-bottom: 0.125rem; }

        .orders-list { margin-top: 1rem; padding-top: 1rem; border-top: 1px dashed #ccc; }
        .orders-list h4 { font-size: 1.1rem; color: var(--text-color-dark); margin-bottom: 0.5rem; }
        .orders-list ul { list-style: none; padding-left: 0; }
        .orders-list li {
            font-size: 0.9rem; padding: 0.35rem 0.25rem; display: flex; flex-wrap: wrap;
            justify-content: space-between; border-bottom: 1px solid #eee;
        }
        .orders-list li:last-child { border-bottom: none; }
        .orders-list .item-name { flex-basis: 50%; margin-right: 0.5rem; }
        .orders-list .item-qty, .orders-list .item-price, .orders-list .item-total-price {
            flex-basis: auto; margin-left: 0.5rem; white-space: nowrap; text-align: right;
        }
        .orders-list .item-price { color: #555; font-size: 0.85rem; }
        .orders-list .reservation-order-total {
            font-weight: bold; margin-top: 0.75rem; text-align: right;
            color: var(--primary-text-color, var(--text-color-dark)); font-size: 1.05rem; padding-top: 0.5rem; border-top: 1px solid #ccc;
        }
        .no-data-message {
            font-style: italic; color: #6c757d; text-align: center; padding: 1rem;
            background-color: #f8f9fa; border-radius: var(--border-radius-sm); margin-top: 1rem;
        }
        .reservation-actions {
            margin-top: 1rem;
            text-align: right; 
        }
        .cancel-button {
            padding: 0.5rem 1rem;
            font-size: 0.85rem;
            font-weight: 500;
            color: white;
            background-color: var(--cancel-button-bg);
            border: none;
            border-radius: var(--border-radius-sm);
            cursor: pointer;
            transition: background-color 0.3s ease;
        }
        .cancel-button:hover {
            background-color: var(--cancel-button-hover-bg);
        }

        @keyframes fadeIn { from { opacity: 0; transform: scale(0.95); } to { opacity: 1; transform: scale(1); } }
        @keyframes fadeInUp { 0% { opacity: 0; transform: translateY(20px); } 100% { opacity: 1; transform: translateY(0); } }

        @media (max-width: 40em) {
            body { padding: 0.5rem; align-items: flex-start; }
            .container { margin: 0.5rem auto; }
            .form-box { padding: 1.25rem; }
            .form-box h2 { font-size: 1.75rem; }
            .form-box input, .form-box select, .form-box button { padding: 0.625rem; font-size: 0.95rem; }
            .user-dashboard { padding: 1rem; }
            .user-dashboard h2 { font-size: 1.5rem; }
            .user-actions { flex-direction: column; }
            .user-actions .action-button, .user-dashboard form[action="logout.php"] button { width: 100%; margin-right:0; }
            .reservations-section h3 { font-size: 1.3rem; }
            .reservation-card { padding: 0.75rem; }
            .reservation-details-grid { grid-template-columns: 1fr; }
            .orders-list li { flex-direction: column; align-items: flex-start; }
            .orders-list .item-qty, .orders-list .item-price, .orders-list .item-total-price {
                margin-left: 0; text-align: left; width: 100%;
            }
            .reservation-actions { text-align: center; } 
            .cancel-button { width: 100%; }
        }
    </style>
</head>
<body>
<div class="container">

    <?php if (!empty($page_error_message)): ?>
        <p class="page-message error"><?= htmlspecialchars($page_error_message, ENT_QUOTES) ?></p>
    <?php endif; ?>
    <?php if (!empty($page_success_message)): ?>
        <p class="page-message success"><?= htmlspecialchars($page_success_message, ENT_QUOTES) ?></p>
    <?php endif; ?>


    <?php if (isset($_SESSION['user_id'])): ?>
        <div class="user-dashboard">
            <h2>Welcome, <?= $current_user_name_display ?>!</h2>
            <p class="user-info">Logged in as <strong><?= htmlspecialchars($_SESSION['email'] ?? '', ENT_QUOTES) ?></strong></p>
            
            <div class="user-actions">
                <a href="menu.php" class="action-button" style="background-color: #007bff;">View Menu &amp; Pre-order</a>
                <a href="reservation.php" class="action-button" style="background-color: #28a745;">Make New Reservation</a>
                <form action="logout.php" method="post" style="display: inline-block; margin:0;">
                    <button type="submit" name="logout" class="action-button">Log Out</button>
                </form>
            </div>

            <div class="reservations-section">
                <h3>Your Reservations & Pre-Orders</h3>
                <?php if (!empty($user_reservations_with_orders)): ?>
                    <?php foreach ($user_reservations_with_orders as $reservation_item):
                        $r = $reservation_item['details'];
                        $orders = $reservation_item['orders'];
                        $total_order_price = $reservation_item['total_order_price'];
                        $is_past_reservation = strtotime($r['reservation_date']) < strtotime(date('Y-m-d'));
                        $is_cancelable = in_array($r['status'], ['Pending', 'Confirmed']) && !$is_past_reservation;
                    ?>
                        <div class="reservation-card">
                            <h4>Reservation #<?= htmlspecialchars((string)$r['id'], ENT_QUOTES) ?>
                                <span style="font-size: 0.8em; color: <?= ($r['status'] == 'Cancelled' ? 'var(--error-text-color)' : ($r['status'] == 'Confirmed' ? 'var(--success-text-color)' : 'inherit')); ?>;">
                                    (<?= htmlspecialchars($r['status'], ENT_QUOTES) ?>)
                                </span>
                            </h4>
                            <div class="reservation-details-grid">
                                <p><strong>Date:</strong> <?= htmlspecialchars($r['reservation_date'], ENT_QUOTES) ?></p>
                                <p><strong>Time:</strong> <?= htmlspecialchars(date("g:i A", strtotime($r['reservation_time'])), ENT_QUOTES) ?></p>
                                <p><strong>Guests:</strong> <?= htmlspecialchars((string)$r['guests'], ENT_QUOTES) ?></p>
                                <p><strong>Reserved By:</strong> <?= htmlspecialchars($r['name'], ENT_QUOTES) ?></p>
                                <?php if (!empty($r['notes'])): ?>
                                <p style="grid-column: 1 / -1;"><strong>Notes:</strong> <?= nl2br(htmlspecialchars($r['notes'], ENT_QUOTES)) ?></p>
                                <?php endif; ?>
                            </div>

                            <?php if (!empty($orders)): ?>
                                <div class="orders-list">
                                    <h4>Pre-Ordered Items:</h4>
                                    <ul>
                                        <?php foreach ($orders as $order): ?>
                                            <li>
                                                <span class="item-name"><?= htmlspecialchars($order['item_name'], ENT_QUOTES) ?></span>
                                                <span class="item-qty">x <?= htmlspecialchars((string)$order['quantity'], ENT_QUOTES) ?></span>
                                                <span class="item-price">(PHP <?= number_format($order['item_price'], 2) ?> ea.)</span>
                                                <span class="item-total-price">PHP <?= number_format($order['item_price'] * $order['quantity'], 2) ?></span>
                                            </li>
                                        <?php endforeach; ?>
                                    </ul>
                                    <p class="reservation-order-total">Total for Pre-Order: PHP <?= number_format($total_order_price, 2) ?></p>
                                </div>
                            <?php elseif ($r['status'] !== 'Cancelled'): ?>
                                <p class="no-data-message" style="text-align:left; font-size:0.9rem; margin-top:0.5rem;">No items pre-ordered for this reservation.</p>
                            <?php endif; ?>

                            <?php if ($is_cancelable): ?>
                            <div class="reservation-actions">
                                <form method="POST" action="<?= htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES) ?>" onsubmit="return confirm('Are you sure you want to cancel this reservation and its pre-orders? This action cannot be undone.');">
                                    <input type="hidden" name="cancel_reservation_id" value="<?= htmlspecialchars((string)$r['id'], ENT_QUOTES) ?>">
                                    <button type="submit" class="cancel-button">Cancel Reservation</button>
                                </form>
                            </div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p class="no-data-message">You have no reservations matching the criteria. <a href="reservation.php">Make a new reservation?</a></p>
                <?php endif; ?>
            </div>
        </div>
    <?php else: ?>
        <div class="form-box <?= isActiveForm('login', $activeForm); ?>" id="login-form">
            <form action="login_register.php" method="post">
                <h2>Log in</h2>
                <?= showError($error_login); ?>
                <?= showSuccess($register_success_message); ?>
                <input type="email" name="email" placeholder="Email" required value="<?= htmlspecialchars($_SESSION['form_data']['login']['email'] ?? '', ENT_QUOTES) ?>">
                <input type="password" name="password" placeholder="Password" required>
                <button type="submit" name="login">Log in</button>
                <p>Don't have an account? <a href="#" onclick="showForm('register'); return false;">Register</a></p>
            </form>
        </div>
        <div class="form-box <?= isActiveForm('register', $activeForm); ?>" id="register-form">
            <form action="login_register.php" method="post">
                <h2>Register</h2>
                <?= showError($error_register); ?>
                <input type="text" name="name" placeholder="Full Name" required value="<?= htmlspecialchars($_SESSION['form_data']['register']['name'] ?? '', ENT_QUOTES) ?>">
                <input type="email" name="email" placeholder="Email" required value="<?= htmlspecialchars($_SESSION['form_data']['register']['email'] ?? '', ENT_QUOTES) ?>">
                <input type="tel" name="phone_number" placeholder="Phone Number (e.g., 09xxxxxxxxx)" required pattern="09[0-9]{9}" title="Please enter a valid 11-digit phone number starting with 09." value="<?= htmlspecialchars($_SESSION['form_data']['register']['phone_number'] ?? '', ENT_QUOTES) ?>">
                <input type="password" name="password" placeholder="Password" required>
                <button type="submit" name="register">Register</button>
                <p>Already have an account? <a href="#" onclick="showForm('login'); return false;">Log in</a></p>
            </form>
        </div>
        <?php unset($_SESSION['form_data']); ?>
    <?php endif; ?>
</div>

<script>
function showForm(formType) {
    const loginForm = document.getElementById('login-form');
    const registerForm = document.getElementById('register-form');
    if (formType === 'register') {
        if(loginForm) loginForm.classList.remove('active');
        if(registerForm) registerForm.classList.add('active');
    } else { 
        if(registerForm) registerForm.classList.remove('active');
        if(loginForm) loginForm.classList.add('active');
    }
}
</script>
</body>
</html>