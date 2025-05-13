<?php
session_start();
require_once 'db_config.php'; 

if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

$success_message = "Your pre-order has been successfully confirmed!";
if (isset($_SESSION['success_message'])) {
    $success_message = $_SESSION['success_message'];
    unset($_SESSION['success_message']); 
} else {
    
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pre-Order Confirmed | Seaside Restaurant</title>
    <style>
        
        :root {
            --primary-font: 'Segoe UI', sans-serif;
            --text-color: white; 
            --bg-gradient-start: #c8e6c9; 
            --bg-gradient-end: #a5d6a7;
            --container-bg: white;
            --primary-text-color: #2e7d32; 
            --base-font-size: 16px;
        }
        html { font-size: var(--base-font-size); }
        body {
            font-family: var(--primary-font);
            background: linear-gradient(to bottom, var(--bg-gradient-start), var(--bg-gradient-end));
            color: var(--primary-text-color);
            text-align: center;
            margin: 0;
            padding: 1.25rem;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .container {
            max-width: 37.5rem; 
            margin: 1rem auto;
            background: var(--container-bg);
            padding: 2rem;
            border-radius: 0.75rem;
            box-shadow: 0 0.25rem 0.75rem rgba(0,0,0,0.1);
        }
        h1 { font-size: 2rem; margin-bottom: 1rem; }
        p { font-size: 1.1rem; line-height: 1.6; margin-bottom: 1.5rem; }
        a.button-link {
            display: inline-block;
            padding: 0.75rem 1.5rem;
            background-color: var(--primary-text-color);
            color: white;
            text-decoration: none;
            border-radius: 0.375rem;
            font-weight: 500;
            transition: background-color 0.3s ease;
        }
        a.button-link:hover { background-color:rgb(44, 141, 93);  }
    </style>
</head>
<body>
    <div class="container">
        <h1>Thank You!</h1>
        <p><?= htmlspecialchars($success_message, ENT_QUOTES) ?></p>
        <p>We look forward to serving you at Seaside Restaurant.</p>
        <a href="user_page.php" class="button-link">View My Dashboard</a>
        </div>
</body>
</html>