<?php

session_start();

include "config/database.php";

/* =========================================================
   LOGIN CHECK
========================================================= */

if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit;
}

$user_id = (int) $_SESSION["user_id"];


/* =========================================================
   ORDER ID CHECK
========================================================= */

if (
    !isset($_POST["order_id"]) ||
    !is_numeric($_POST["order_id"])
) {
    header("Location: my_orders.php");
    exit;
}

$order_id = (int) $_POST["order_id"];


/* =========================================================
   GET ORDER + OWNERSHIP CHECK
========================================================= */

$check_stmt = mysqli_prepare(
    $conn,
    "SELECT id, user_id, status
     FROM orders
     WHERE id = ?
     AND user_id = ?"
);

mysqli_stmt_bind_param(
    $check_stmt,
    "ii",
    $order_id,
    $user_id
);

mysqli_stmt_execute($check_stmt);

$check_result = mysqli_stmt_get_result(
    $check_stmt
);


/* =========================================================
   ORDER NOT FOUND / NOT OWNER
========================================================= */

if (mysqli_num_rows($check_result) === 0) {

    mysqli_stmt_close($check_stmt);

    header(
        "Location: order_details.php?id=" . $order_id
    );

    exit;
}

$order_data = mysqli_fetch_assoc(
    $check_result
);

mysqli_stmt_close($check_stmt);

$current_status = $order_data["status"];


/* =========================================================
   ONLY PENDING ORDER CAN BE CANCELLED
========================================================= */

if ($current_status !== "Pending") {

    header(
        "Location: order_details.php?id=" . $order_id
    );

    exit;
}


/* =========================================================
   START TRANSACTION
========================================================= */

mysqli_begin_transaction($conn);

try {

    /* =====================================================
       UPDATE ORDER STATUS
    ===================================================== */

    $update_stmt = mysqli_prepare(
        $conn,
        "UPDATE orders
         SET status = 'Cancelled'
         WHERE id = ?
         AND user_id = ?
         AND status = 'Pending'"
    );

    mysqli_stmt_bind_param(
        $update_stmt,
        "ii",
        $order_id,
        $user_id
    );

    if (!mysqli_stmt_execute($update_stmt)) {

        mysqli_stmt_close($update_stmt);

        throw new Exception(
            "Failed to cancel order."
        );
    }

    /*
     * Make sure exactly an eligible order was updated.
     */
    if (mysqli_stmt_affected_rows($update_stmt) !== 1) {

        mysqli_stmt_close($update_stmt);

        throw new Exception(
            "Order could not be cancelled."
        );
    }

    mysqli_stmt_close($update_stmt);


    /* =====================================================
       SAVE STATUS HISTORY
    ===================================================== */

    $history_stmt = mysqli_prepare(
        $conn,
        "INSERT INTO order_status_history
            (order_id, status)
         VALUES
            (?, 'Cancelled')"
    );

    mysqli_stmt_bind_param(
        $history_stmt,
        "i",
        $order_id
    );

    if (!mysqli_stmt_execute($history_stmt)) {

        mysqli_stmt_close($history_stmt);

        throw new Exception(
            "Failed to save cancellation history."
        );
    }

    mysqli_stmt_close($history_stmt);


    /* =====================================================
       COMMIT
    ===================================================== */

    mysqli_commit($conn);


    /* =====================================================
       REDIRECT
    ===================================================== */

    header(
        "Location: order_details.php?id=" . $order_id
    );

    exit;

} catch (Exception $e) {

    mysqli_rollback($conn);

    header(
        "Location: order_details.php?id=" . $order_id
    );

    exit;
}