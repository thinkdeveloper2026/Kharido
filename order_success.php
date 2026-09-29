<?php

session_start();

if (!isset($_GET["order_id"])) {
    header("Location: index.php");
    exit;
}

$order_id = (int) $_GET["order_id"];

$page_title = "Order Successful - Kharido";

include "includes/header.php";
?>

<main class="order-success-page">

    <div class="order-success-box">

        <div class="success-icon">
            ✓
        </div>

        <h1>Order Placed Successfully!</h1>

        <p>
            Thank you for shopping with Kharido.
        </p>

        <p>
            Your Order ID is:
            <strong>#<?php echo $order_id; ?></strong>
        </p>

        <a href="index.php" class="continue-shopping-button">
            Continue Shopping
        </a>

    </div>

</main>

<?php
include "includes/footer.php";
?>