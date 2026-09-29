<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (
    !isset($_SESSION["user_id"]) ||
    !isset($_SESSION["user_role"]) ||
    $_SESSION["user_role"] !== "admin"
) {
    header("Location: ../login.php");
    exit;
}

$page_title = $page_title ?? "Kharido Admin";

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>
        <?php echo htmlspecialchars($page_title); ?>
    </title>

    <link
        rel="stylesheet"
        href="../css/style.css"
    >

</head>

<body>

<nav class="admin-navbar">

    <div class="admin-nav-container">

        <!-- Logo -->

        <a
            href="index.php"
            class="admin-logo"
        >
            Kharido Admin
        </a>


        <!-- Admin Navigation -->

        <div class="admin-nav-links">

            <a href="index.php">
                Dashboard
            </a>

            <a href="products.php">
                Products
            </a>

            <a href="orders.php">
                Orders
            </a>

            <a href="users.php">
                Users
            </a>

            <a href="../index.php">
                View Website
            </a>

            <a href="../logout.php">
                Logout
            </a>

        </div>

    </div>

</nav>