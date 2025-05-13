<?php
session_start();
require_once 'db_config.php';


if (!isset($_SESSION['user_id'])) {
    $_SESSION['error_message'] = "Please log in to make a payment.";
    header("Location: login.php"); 
    exit();
}

if (!isset($_SESSION['reservation_date']) || !isset($_SESSION['reservation_time']) || !isset($_SESSION['reservation_guests'])) {
    $_SESSION['error_message'] = "Reservation details are missing. Please make a reservation first.";
    header("Location: reservation.php"); 
    exit();
}

// Retrieve reservation details from the session
$reservation_date_str = $_SESSION['reservation_date'];
$reservation_time_str = $_SESSION['reservation_time'];
$reservation_guests = (int)$_SESSION['reservation_guests'];
$logged_in_user_id = $_SESSION['user_id']; 


$user_name = 'N/A';
$user_phone = 'N/A';
$user_email = 'N/A';

try {
    $stmt_user = $pdo->prepare("SELECT name, phone_number, email FROM users WHERE id = ?");
    $stmt_user->execute([$logged_in_user_id]);
    $user = $stmt_user->fetch();
    if ($user) {
        $user_name = $user['name'];
        $user_phone = $user['phone_number'];
        $user_email = $user['email'];
    } else {
        
        $_SESSION['error_message'] = "Could not retrieve your user details. Please try logging in again.";
        header("Location: login.php");
        exit();
    }
} catch (PDOException $e) {
    error_log("Error fetching user details: " . $e->getMessage());
    $_SESSION['error_message'] = "Error retrieving user data.";
    
}


if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['payment_method'])) {
    $payment_method_posted = $_POST['payment_method'];

    $reservation_notes = isset($_SESSION['reservation_notes']) ? $_SESSION['reservation_notes'] : '';
    $reservation_status = 'Pending';
    $payment_amount = 0.00; 
    $payment_status_db = 'Pending';

    try {
        $pdo->beginTransaction();

        
        $sql_reservation = "INSERT INTO reservations (name, phone_number, guests, reservation_date, reservation_time, notes, status)
                            VALUES (:name, :phone_number, :guests, :reservation_date, :reservation_time, :notes, :status)";
        $stmt_reservation = $pdo->prepare($sql_reservation);
        $stmt_reservation->execute([
            ':name' => $user_name, 
            ':phone_number' => $user_phone, 
            ':guests' => $reservation_guests,
            ':reservation_date' => $reservation_date_str,
            ':reservation_time' => $reservation_time_str,
            ':notes' => $reservation_notes,
            ':status' => $reservation_status
        ]);
        $reservation_id = $pdo->lastInsertId();
        $_SESSION['reservation_id'] = $reservation_id;

        
        $sql_payment_params = [
            ':order_id' => $reservation_id,
            ':amount_paid' => $payment_amount,
            ':payment_status' => $payment_status_db,
            ':email' => $user_email
        ];

        
        $sql_payment = "INSERT INTO payments (order_id, amount_paid, payment_date, payment_status, email)
                        VALUES (:order_id, :amount_paid, CURDATE(), :payment_status, :email)";

        

        $stmt_payment = $pdo->prepare($sql_payment);
        $stmt_payment->execute($sql_payment_params);


        $pdo->commit();

        if ($payment_method_posted == "paypal") {
            $_SESSION['payment_status_message'] = "Payment initiated via GCash! Your reservation (ID: $reservation_id) is pending confirmation.";
        } else {
            $_SESSION['payment_status_message'] = "Payment method " . htmlspecialchars($payment_method_posted) . " selected. Reservation (ID: $reservation_id) is pending.";
        }
        
        unset($_SESSION['reservation_date'], $_SESSION['reservation_time'], $_SESSION['reservation_guests'], $_SESSION['reservation_notes']);

        header("Location: payment_confirmation.php");
        exit();

    } catch (PDOException $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        error_log("Payment processing error: " . $e->getMessage());
        $_SESSION['error_message'] = "A database error occurred during payment processing. Please try again. Details: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Seaside Floating Restaurant - Reservation Payment</title>
    <style>
        
        :root {
            --primary-font: Arial, sans-serif;
            --text-color: white;
            --gradient-start: #0f3b53;
            --gradient-end: #145874;
            --container-gradient-start: #2e6193;
            --container-gradient-end: #2e938e;
            --button-border-color: white;
            --button-hover-bg: white;
            --button-hover-text: #2c3e50;
            --base-font-size: 16px; 
        }

        html {
            font-size: var(--base-font-size);
        }

        body {
            font-family: var(--primary-font);
            background: linear-gradient(to bottom, var(--gradient-start), var(--gradient-end));
            color: var(--text-color);
            text-align: center;
            margin: 0;
            padding: 1rem; 
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
        }

        .site-header { 
            width: 100%;
            padding: 0.5rem 1rem;
            position: absolute; 
            top: 0;
            left: 0;
            display: flex;
            justify-content: flex-start; 
        }

        .site-header .logo img {
            width: 3.125rem; 
            height: 3.125rem;
            display: block; 
        }

        .container {
            width: 90%; 
            max-width: 37.5rem; 
            margin: 2rem auto; 
            padding: 1.5rem; 
            background: linear-gradient(var(--container-gradient-start), var(--container-gradient-end));
            border-radius: 0.625rem; 
            box-shadow: 0px 0.25rem 0.625rem rgba(0, 0, 0, 0.2);
        }

        .title {
            font-size: 2.25rem; 
            font-weight: bold;
            text-transform: uppercase;
            margin-bottom: 1.25rem; 
        }

        .reservation-details p {
            font-size: 1rem;
            margin: 0.5rem 0; 
        }
        .reservation-details strong {
            color: #e0f7fa; 
        }

        .payment-form h3 {
            font-size: 1.25rem; 
            margin-top: 1.5rem;
            margin-bottom: 1rem;
        }

        .form-group {
            margin-bottom: 1.25rem; 
        }

        button, .button-link { 
            padding: 0.75rem 1.25rem; 
            font-size: 1rem;    
            font-weight: bold;
            color: var(--text-color);
            background: transparent;
            border: 2px solid var(--button-border-color);
            border-radius: 0.3125rem; 
            cursor: pointer;
            transition: background-color 0.3s, color 0.3s;
            text-decoration: none; 
            display: inline-block; 
            line-height: 1.5;
        }

        button:hover, .button-link:hover {
            background: var(--button-hover-bg);
            color: var(--button-hover-text);
        }

        .error-message {
            background-color: #ffdddd;
            border: 1px solid #ffaaaa;
            color: #D8000C;
            padding: 0.75rem;
            margin-bottom: 1rem;
            border-radius: 0.25rem;
            text-align: left;
        }

        
        @media (min-width: 48em) { 
            .container {
                width: 70%;
                padding: 2rem;
            }
            .title {
                font-size: 2.5rem; 
            }
        }
        @media (min-width: 64em) { 
            .container {
                width: 50%;
            }
        }
    </style>
</head>
<body>

<header class="site-header">
    <div class="logo">
        <a href="user_page.php">
            <img src="logo.png" alt="Seaside Restaurant Logo">
        </a>
    </div>
</header>

<div class="container">
    <h1 class="title">Reservation Payment</h1>

    <?php if (isset($_SESSION['error_message'])): ?>
        <div class="error-message">
            <p><?= htmlspecialchars($_SESSION['error_message'], ENT_QUOTES) ?></p>
        </div>
        <?php unset($_SESSION['error_message']); ?>
    <?php endif; ?>

    <div class="reservation-details">
        <h2>Your Reservation:</h2>
        <p><strong>Date:</strong> <?= htmlspecialchars($reservation_date_str, ENT_QUOTES) ?></p>
        <p><strong>Time:</strong> <?= htmlspecialchars(date("h:i A", strtotime($reservation_time_str)), ENT_QUOTES) ?></p>
        <p><strong>Guests:</strong> <?= htmlspecialchars((string)$reservation_guests, ENT_QUOTES) ?></p>
        <p><strong>Reserved by:</strong> <?= htmlspecialchars($user_name, ENT_QUOTES) ?> (<?= htmlspecialchars($user_phone, ENT_QUOTES) ?>)</p>
    </div>

    <form method="POST" action="payment.php" class="payment-form">
        <h3>Select a Payment Method</h3>
        <div class="form-group">
            <button type="submit" name="payment_method" value="paypal">Proceed with GCash</button>
        </div>
    </form>
    <div class="form-group">
         <a href="reservation.php" class="button-link" style="background-color: #555; border-color: #444;">Back to Reservation</a>
    </div>
</div>

</body>
</html>

