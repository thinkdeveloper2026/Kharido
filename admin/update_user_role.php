<?php

session_start();

include "../config/database.php";


// Check admin login

if (
    !isset($_SESSION["user_id"]) ||
    !isset($_SESSION["user_role"]) ||
    $_SESSION["user_role"] !== "admin"
) {
    header("Location: ../login.php");
    exit;
}


// Only POST request allowed

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    die("Invalid request.");
}


// Get data

$user_id = isset($_POST["user_id"])
    ? (int) $_POST["user_id"]
    : 0;

$role = $_POST["role"] ?? "";


// Allowed roles

$allowed_roles = [
    "user",
    "admin"
];


// Validate

if (
    $user_id <= 0 ||
    !in_array($role, $allowed_roles, true)
) {
    die("Invalid user ID or role.");
}


// Prevent admin from changing their own role

if ($user_id === (int) $_SESSION["user_id"]) {
    die("You cannot change your own admin role.");
}


// Update role

$stmt = mysqli_prepare(
    $conn,
    "UPDATE users
     SET role = ?
     WHERE id = ?"
);

if (!$stmt) {
    die("Prepare Error: " . mysqli_error($conn));
}


mysqli_stmt_bind_param(
    $stmt,
    "si",
    $role,
    $user_id
);


if (!mysqli_stmt_execute($stmt)) {
    die(
        "Update Error: " .
        mysqli_stmt_error($stmt)
    );
}


mysqli_stmt_close($stmt);


// Back to Users page

header("Location: users.php");
exit;

?>