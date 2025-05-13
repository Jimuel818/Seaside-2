<?php

session_start();
require_once 'db_config.php'; // Include the database connection

$item_count = 0;

if (isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];
    try {
        $stmt = $pdo->prepare("SELECT SUM(quantity) as total_items FROM cart_items WHERE user_id = ?");
        $stmt->execute([$user_id]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($result && $result['total_items'] !== null) {
            $item_count = (int)$result['total_items'];
        }
    } catch (PDOException $e) {
        
        error_log("Cart count fetching error: " . $e->getMessage());
        $item_count = 0;
    }
}

echo $item_count;
?>