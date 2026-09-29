<?php

session_start();

mysqli_report(MYSQLI_REPORT_OFF);

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
    header("Location: users.php");
    exit;
}


// Get user ID

$user_id = isset($_POST["user_id"])
    ? (int) $_POST["user_id"]
    : 0;


// Validate

if ($user_id <= 0) {

    $_SESSION["user_message"] = "Invalid user ID.";
    $_SESSION["user_message_type"] = "error";

    header("Location: users.php");
    exit;
}


// Prevent deleting yourself

if ($user_id === (int) $_SESSION["user_id"]) {

    $_SESSION["user_message"] =
        "You cannot delete your own admin account.";

    $_SESSION["user_message_type"] = "error";

    header("Location: users.php");
    exit;
}


// Check user exists

$stmt = mysqli_prepare(
    $conn,
    "SELECT id, name
     FROM users
     WHERE id = ?"
);

mysqli_stmt_bind_param(
    $stmt,
    "i",
    $user_id
);

mysqli_stmt_execute($stmt);

$result = mysqli_stmt_get_result($stmt);

if (mysqli_num_rows($result) === 0) {

    mysqli_stmt_close($stmt);

    $_SESSION["user_message"] = "User not found.";
    $_SESSION["user_message_type"] = "error";

    header("Location: users.php");
    exit;
}

$user = mysqli_fetch_assoc($result);

mysqli_stmt_close($stmt);


// Delete user

$stmt = mysqli_prepare(
    $conn,
    "DELETE FROM users
     WHERE id = ?"
);

if (!$stmt) {

    $_SESSION["user_message"] =
        "Unable to prepare delete request.";

    $_SESSION["user_message_type"] = "error";

    header("Location: users.php");
    exit;
}


mysqli_stmt_bind_param(
    $stmt,
    "i",
    $user_id
);


if (!mysqli_stmt_execute($stmt)) {

    // User has existing orders

    if (mysqli_errno($conn) == 1451) {

        mysqli_stmt_close($stmt);

        $_SESSION["user_message"] =
            "This user cannot be deleted because they have existing orders.";

        $_SESSION["user_message_type"] = "error";

        header("Location: users.php");
        exit;
    }


    $_SESSION["user_message"] =
        "User could not be deleted.";

    $_SESSION["user_message_type"] = "error";

    mysqli_stmt_close($stmt);

    header("Location: users.php");
    exit;
}


mysqli_stmt_close($stmt);


// Success message

$_SESSION["user_message"] =
    "User deleted successfully.";

$_SESSION["user_message_type"] = "success";


// Back to users page

header("Location: users.php");
exit;

?>