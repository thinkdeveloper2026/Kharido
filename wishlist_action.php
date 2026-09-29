<?php

session_start();

require_once "config/database.php";

// User login check
if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit;
}

$user_id = (int) $_SESSION["user_id"];

// Product ID check
if (!isset($_GET["product_id"])) {
    header("Location: index.php");
    exit;
}

$product_id = (int) $_GET["product_id"];

// Product exists check
$product_query = mysqli_query(
    $conn,
    "SELECT id FROM products WHERE id = $product_id"
);

if (!$product_query || mysqli_num_rows($product_query) === 0) {
    header("Location: index.php");
    exit;
}

// Check whether product is already in wishlist
$check_query = mysqli_query(
    $conn,
    "SELECT id FROM wishlist
     WHERE user_id = $user_id
     AND product_id = $product_id"
);

if (mysqli_num_rows($check_query) > 0) {

    // Remove from wishlist
    mysqli_query(
        $conn,
        "DELETE FROM wishlist
         WHERE user_id = $user_id
         AND product_id = $product_id"
    );

} else {

    // Add to wishlist
    mysqli_query(
        $conn,
        "INSERT INTO wishlist (user_id, product_id)
         VALUES ($user_id, $product_id)"
    );
}

// Go back to previous page
$redirect = $_SERVER["HTTP_REFERER"] ?? "index.php";

header("Location: " . $redirect);
exit;

?>