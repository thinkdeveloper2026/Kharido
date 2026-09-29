<?php

session_start();

include "../config/database.php";

if (!isset($_SESSION["user_id"]) || $_SESSION["user_role"] !== "admin") {
    header("Location: ../login.php");
    exit;
}


if (!isset($_GET["id"]) || !is_numeric($_GET["id"])) {
    header("Location: products.php");
    exit;
}

$product_id = (int) $_GET["id"];


/* Get image */

$stmt = mysqli_prepare(
    $conn,
    "SELECT image
     FROM products
     WHERE id = ?"
);

mysqli_stmt_bind_param(
    $stmt,
    "i",
    $product_id
);

mysqli_stmt_execute($stmt);

$result = mysqli_stmt_get_result($stmt);

$image = "";

if (mysqli_num_rows($result) === 1) {

    $product = mysqli_fetch_assoc($result);

    $image = $product["image"];
}

mysqli_stmt_close($stmt);


/* Delete product */

$stmt = mysqli_prepare(
    $conn,
    "DELETE FROM products
     WHERE id = ?"
);

mysqli_stmt_bind_param(
    $stmt,
    "i",
    $product_id
);

mysqli_stmt_execute($stmt);

mysqli_stmt_close($stmt);


/* Delete image */

if (
    !empty($image) &&
    file_exists("../images/products/" . $image)
) {

    unlink("../images/products/" . $image);
}


header("Location: products.php");
exit;

?>