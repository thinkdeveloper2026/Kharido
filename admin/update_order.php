<?php

session_start();

require_once "../config/database.php";


/* ========================================
   ADMIN ACCESS CHECK
======================================== */

if (
    !isset($_SESSION["user_id"]) ||
    !isset($_SESSION["user_role"]) ||
    $_SESSION["user_role"] !== "admin"
) {
    header("Location: ../login.php");
    exit;
}


/* ========================================
   ONLY POST REQUEST
======================================== */

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: orders.php");
    exit;
}


/* ========================================
   GET DATA
======================================== */

$order_id = isset($_POST["order_id"])
    ? (int) $_POST["order_id"]
    : 0;

$status = isset($_POST["status"])
    ? trim($_POST["status"])
    : "";


/* ========================================
   ALLOWED STATUSES
======================================== */

$allowed_statuses = [
    "Pending",
    "Processing",
    "Shipped",
    "Delivered",
    "Cancelled"
];


/* ========================================
   VALIDATE ORDER ID
======================================== */

if ($order_id <= 0) {
    header("Location: orders.php");
    exit;
}


/* ========================================
   VALIDATE STATUS
======================================== */

if (
    !in_array(
        $status,
        $allowed_statuses,
        true
    )
) {
    header("Location: orders.php");
    exit;
}


/* ========================================
   CHECK ORDER EXISTS
======================================== */

$check_stmt = mysqli_prepare(
    $conn,
    "SELECT id, status
     FROM orders
     WHERE id = ?"
);

mysqli_stmt_bind_param(
    $check_stmt,
    "i",
    $order_id
);

mysqli_stmt_execute(
    $check_stmt
);

$check_result = mysqli_stmt_get_result(
    $check_stmt
);


if (
    mysqli_num_rows(
        $check_result
    ) === 0
) {

    mysqli_stmt_close(
        $check_stmt
    );

    header(
        "Location: orders.php"
    );

    exit;
}


/* Get current status */

$current_order = mysqli_fetch_assoc(
    $check_result
);

$current_status = $current_order["status"];


mysqli_stmt_close(
    $check_stmt
);


/* ========================================
   START TRANSACTION
======================================== */

mysqli_begin_transaction($conn);


/* ========================================
   UPDATE ORDER STATUS
======================================== */

$update_stmt = mysqli_prepare(
    $conn,
    "UPDATE orders
     SET status = ?
     WHERE id = ?"
);

mysqli_stmt_bind_param(
    $update_stmt,
    "si",
    $status,
    $order_id
);


if (
    !mysqli_stmt_execute(
        $update_stmt
    )
) {

    mysqli_stmt_close(
        $update_stmt
    );

    mysqli_rollback($conn);

    die(
        "Failed to update order status."
    );
}


mysqli_stmt_close(
    $update_stmt
);


/* ========================================
   SAVE STATUS HISTORY
======================================== */

/*
   History tabhi save hogi jab status
   actually change hua ho.
*/

if ($current_status !== $status) {

    $history_stmt = mysqli_prepare(
        $conn,
        "INSERT INTO order_status_history
            (order_id, status)
         VALUES
            (?, ?)"
    );

    mysqli_stmt_bind_param(
        $history_stmt,
        "is",
        $order_id,
        $status
    );


    if (
        !mysqli_stmt_execute(
            $history_stmt
        )
    ) {

        mysqli_stmt_close(
            $history_stmt
        );

        mysqli_rollback($conn);

        die(
            "Failed to save order status history."
        );
    }


    mysqli_stmt_close(
        $history_stmt
    );
}


/* ========================================
   COMMIT
======================================== */

mysqli_commit($conn);


/* ========================================
   REDIRECT
======================================== */

header(
    "Location: orders.php"
);

exit;

?>