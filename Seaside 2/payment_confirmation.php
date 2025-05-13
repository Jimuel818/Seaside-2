<?php
session_start();
require_once 'db_config.php';

if (!isset($_SESSION['payment_status_message'])) {
    if (!isset($_SESSION['reservation_id'])) {
        header("Location: reservation.php");
        exit();
    }
    $_SESSION['payment_status_message'] = "Your reservation (ID: " . htmlspecialchars((string)$_SESSION['reservation_id'], ENT_QUOTES) . ") has been recorded.";
}

$payment_status_msg = $_SESSION['payment_status_message'];
$reservation_id_confirmed = $_SESSION['reservation_id'] ?? null;

$confirmed_reservation_details = null;
if ($reservation_id_confirmed) {
    try {
        
        $stmt = $pdo->prepare("SELECT * FROM reservations WHERE id = ?");
        $stmt->execute([$reservation_id_confirmed]);
        $confirmed_reservation_details = $stmt->fetch();
    } catch (PDOException $e) {
        error_log("Error fetching confirmed reservation: " . $e->getMessage());
        
    }
}

unset($_SESSION['payment_status_message']);

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Seaside Floating Restaurant - Payment Confirmation</title>
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

        .message {
            font-size: 1.25rem; 
            margin-bottom: 1.25rem;
            line-height: 1.6;
        }
        .message p {
            margin: 0; 
        }

        .details-section {
            margin-top: 1.5rem;
            margin-bottom: 1.5rem;
            font-size: 1rem;
            text-align: left;
            padding: 1rem;
            background-color: rgba(0,0,0,0.1);
            border-radius: 0.3125rem;
        }
        .details-section p {
            margin: 0.5rem 0;
        }
        .details-section strong {
            color: #e0f7fa;
        }

        .actions a.button-link {
            padding: 0.75rem 1.25rem;
            font-size: 1rem;
            font-weight: bold;
            color: var(--text-color);
            background: #0f6383; 
            border: 2px solid #0f6383;
            border-radius: 0.3125rem;
            cursor: pointer;
            transition: background-color 0.3s, color 0.3s;
            text-decoration: none;
            display: inline-block;
            margin-top: 1rem;
        }

        .actions a.button-link:hover {
            background: var(--button-hover-bg);
            color: var(--button-hover-text);
            border-color: var(--button-hover-bg);
        }

        
        @media (min-width: 48em) {
            .container {
                width: 70%;
                padding: 2rem;
            }
            .title {
                font-size: 2.5rem;
            }
            .message {
                font-size: 1.5rem;
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
    <h1 class="title">Payment Confirmation</h1>

    <div class="message">
        <p><?= htmlspecialchars($payment_status_msg, ENT_QUOTES) ?></p>
    </div>

    <?php if ($confirmed_reservation_details): ?>
    <div class="details-section">
        <h3>Your Reservation Details:</h3>
        <p><strong>Reservation ID:</strong> <?= htmlspecialchars((string)$confirmed_reservation_details['id'], ENT_QUOTES) ?></p>
        <p><strong>Name:</strong> <?= htmlspecialchars($confirmed_reservation_details['name'], ENT_QUOTES) ?></p>
        <p><strong>Date:</strong> <?= htmlspecialchars($confirmed_reservation_details['reservation_date'], ENT_QUOTES) ?></p>
        <p><strong>Time:</strong> <?= htmlspecialchars(date("h:i A", strtotime($confirmed_reservation_details['reservation_time'])), ENT_QUOTES) ?></p>
        <p><strong>Guests:</strong> <?= htmlspecialchars((string)$confirmed_reservation_details['guests'], ENT_QUOTES) ?></p>
        <p><strong>Status:</strong> <?= htmlspecialchars($confirmed_reservation_details['status'], ENT_QUOTES) ?></p>
        <?php if(!empty($confirmed_reservation_details['notes'])): ?>
             <p><strong>Notes:</strong> <?= htmlspecialchars($confirmed_reservation_details['notes'], ENT_QUOTES) ?></p>
        <?php endif; ?>
    </div>
    <?php else: ?>
        <?php if($reservation_id_confirmed): ?>
            <p>Could not retrieve full details for reservation ID: <?= htmlspecialchars((string)$reservation_id_confirmed, ENT_QUOTES) ?>.</p>
        <?php endif; ?>
    <?php endif; ?>

    <p>Thank you! We look forward to serving you.</p>

    <div class="actions">
        <a href="user_page.php" class="button-link">Back to My Page</a>
    </div>
</div>

</body>
</html>