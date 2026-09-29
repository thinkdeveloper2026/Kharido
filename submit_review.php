<?php

session_start();

include "config/database.php";

// ========================================
// LOGIN CHECK
// ========================================

if (!isset($_SESSION["user_id"])) {

    header("Location: login.php");
    exit;
}

$user_id = (int) $_SESSION["user_id"];


// ========================================
// ONLY POST REQUEST
// ========================================

if ($_SERVER["REQUEST_METHOD"] !== "POST") {

    header("Location: index.php");
    exit;
}


// ========================================
// GET FORM DATA
// ========================================

$product_id = isset($_POST["product_id"])
    ? (int) $_POST["product_id"]
    : 0;

$rating = isset($_POST["rating"])
    ? (int) $_POST["rating"]
    : 0;

$review = isset($_POST["review"])
    ? trim($_POST["review"])
    : "";


// ========================================
// VALIDATE PRODUCT ID
// ========================================

if ($product_id <= 0) {

    die("Invalid product.");
}


// ========================================
// CHECK PRODUCT EXISTS
// ========================================

$product_stmt = mysqli_prepare(
    $conn,
    "SELECT id FROM products WHERE id = ?"
);

mysqli_stmt_bind_param(
    $product_stmt,
    "i",
    $product_id
);

mysqli_stmt_execute($product_stmt);

$product_result = mysqli_stmt_get_result($product_stmt);

if (mysqli_num_rows($product_result) === 0) {

    mysqli_stmt_close($product_stmt);

    die("Product not found.");
}

mysqli_stmt_close($product_stmt);


// ========================================
// VALIDATE RATING
// ========================================

if ($rating < 1 || $rating > 5) {

    die("Rating must be between 1 and 5.");
}


// ========================================
// VALIDATE REVIEW
// ========================================

if ($review === "") {

    die("Please write a review.");
}


// ========================================
// CHECK DELIVERED ORDER ELIGIBILITY
// ========================================

$purchase_stmt = mysqli_prepare(
    $conn,
    "SELECT oi.id
     FROM order_items oi
     INNER JOIN orders o
        ON oi.order_id = o.id
     WHERE o.user_id = ?
     AND oi.product_id = ?
     AND o.status = 'Delivered'
     LIMIT 1"
);

mysqli_stmt_bind_param(
    $purchase_stmt,
    "ii",
    $user_id,
    $product_id
);

mysqli_stmt_execute($purchase_stmt);

$purchase_result = mysqli_stmt_get_result($purchase_stmt);

if (mysqli_num_rows($purchase_result) === 0) {

    mysqli_stmt_close($purchase_stmt);

    header(
        "Location: product.php?id=" .
        $product_id .
        "&review=not_eligible"
    );

    exit;
}

mysqli_stmt_close($purchase_stmt);


// ========================================
// CHECK EXISTING REVIEW
// ========================================

$check_stmt = mysqli_prepare(
    $conn,
    "SELECT id
     FROM reviews
     WHERE user_id = ?
     AND product_id = ?"
);

mysqli_stmt_bind_param(
    $check_stmt,
    "ii",
    $user_id,
    $product_id
);

mysqli_stmt_execute($check_stmt);

$check_result = mysqli_stmt_get_result($check_stmt);

if (mysqli_num_rows($check_result) > 0) {

    mysqli_stmt_close($check_stmt);

    header(
        "Location: product.php?id=" .
        $product_id .
        "&review=already"
    );

    exit;
}

mysqli_stmt_close($check_stmt);


// ========================================
// INSERT REVIEW
// ========================================

$insert_stmt = mysqli_prepare(
    $conn,
    "INSERT INTO reviews
    (user_id, product_id, rating, review)
    VALUES (?, ?, ?, ?)"
);

mysqli_stmt_bind_param(
    $insert_stmt,
    "iiis",
    $user_id,
    $product_id,
    $rating,
    $review
);


// ========================================
// SUCCESS / ERROR
// ========================================

if (mysqli_stmt_execute($insert_stmt)) {

    mysqli_stmt_close($insert_stmt);

    header(
        "Location: product.php?id=" .
        $product_id .
        "&review=success"
    );

    exit;

} else {

    $error_message = mysqli_error($conn);

    mysqli_stmt_close($insert_stmt);

    die(
        "Failed to submit review: " .
        htmlspecialchars($error_message)
    );
}

?>