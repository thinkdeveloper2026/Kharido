<?php

session_start();

include "config/database.php";

/* User login check */
if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit;
}

/* Cart check */
if (!isset($_SESSION["cart"]) || empty($_SESSION["cart"])) {
    header("Location: cart.php");
    exit;
}

/* Form data */
$name = trim($_POST["name"] ?? "");
$phone = trim($_POST["phone"] ?? "");
$address = trim($_POST["address"] ?? "");

/* Basic validation */
if ($name === "" || $phone === "" || $address === "") {
    die("Please fill all delivery details.");
}

/* Calculate total */
$total_amount = 0;

foreach ($_SESSION["cart"] as $item) {
    $total_amount += $item["price"] * $item["quantity"];
}

/* Start database transaction */
mysqli_begin_transaction($conn);

try {

    /* Insert order */
    $stmt = mysqli_prepare(
        $conn,
        "INSERT INTO orders
        (user_id, total_amount, address, phone, status)
        VALUES (?, ?, ?, ?, 'Pending')"
    );

    mysqli_stmt_bind_param(
        $stmt,
        "idss",
        $_SESSION["user_id"],
        $total_amount,
        $address,
        $phone
    );

    if (!mysqli_stmt_execute($stmt)) {
        throw new Exception("Order could not be created.");
    }

    /* Get new order ID */
    $order_id = mysqli_insert_id($conn);

    mysqli_stmt_close($stmt);


    /* Insert order items */

    $item_stmt = mysqli_prepare(
        $conn,
        "INSERT INTO order_items
        (order_id, product_id, quantity, price, subtotal)
        VALUES (?, ?, ?, ?, ?)"
    );

    foreach ($_SESSION["cart"] as $item) {

        $product_id = $item["id"];
        $quantity = $item["quantity"];
        $price = $item["price"];
        $subtotal = $price * $quantity;

        mysqli_stmt_bind_param(
            $item_stmt,
            "iiidd",
            $order_id,
            $product_id,
            $quantity,
            $price,
            $subtotal
        );

        if (!mysqli_stmt_execute($item_stmt)) {
            throw new Exception("Order item could not be saved.");
        }
    }

    mysqli_stmt_close($item_stmt);


    /* Commit transaction */
    mysqli_commit($conn);


    /* Empty cart */
    $_SESSION["cart"] = [];


    /* Redirect to success page */
    header("Location: order_success.php?order_id=" . $order_id);
    exit;


} catch (Exception $e) {

    /* Rollback if something goes wrong */
    mysqli_rollback($conn);

    die("Order failed: " . $e->getMessage());
}

?>