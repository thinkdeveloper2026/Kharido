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


/* =========================================================
   ADMIN CHECK
========================================================= */

$is_admin = (
    isset($_SESSION["user_role"]) &&
    $_SESSION["user_role"] === "admin"
);


/* =========================================================
   ORDER ID CHECK
========================================================= */

if (
    !isset($_GET["id"]) ||
    !is_numeric($_GET["id"])
) {

    if ($is_admin) {
        header("Location: admin/orders.php");
    } else {
        header("Location: my_orders.php");
    }

    exit;
}

$order_id = (int) $_GET["id"];
$user_id = (int) $_SESSION["user_id"];


/* =========================================================
   ADMIN STATUS UPDATE + HISTORY
========================================================= */

if (
    $_SERVER["REQUEST_METHOD"] === "POST" &&
    $is_admin
) {

    $new_status = isset($_POST["status"])
        ? trim($_POST["status"])
        : "";

    $allowed_statuses = [
        "Pending",
        "Processing",
        "Shipped",
        "Delivered",
        "Cancelled"
    ];


    /* Validate status */

    if (
        !in_array(
            $new_status,
            $allowed_statuses,
            true
        )
    ) {

        header(
            "Location: order_details.php?id=" . $order_id
        );

        exit;
    }


    /* =====================================================
       GET CURRENT ORDER STATUS
    ===================================================== */

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
            "Location: admin/orders.php"
        );

        exit;
    }


    $current_order = mysqli_fetch_assoc(
        $check_result
    );

    $current_status =
        $current_order["status"];


    mysqli_stmt_close(
        $check_stmt
    );


    /* =====================================================
       START TRANSACTION
    ===================================================== */

    mysqli_begin_transaction($conn);


    /* =====================================================
       UPDATE ORDERS STATUS
    ===================================================== */

    $update_stmt = mysqli_prepare(
        $conn,
        "UPDATE orders
         SET status = ?
         WHERE id = ?"
    );

    mysqli_stmt_bind_param(
        $update_stmt,
        "si",
        $new_status,
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


    /* =====================================================
       SAVE STATUS HISTORY ONLY IF STATUS CHANGED
    ===================================================== */

    if ($current_status !== $new_status) {

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
            $new_status
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
}


/* =========================================================
   GET ORDER DETAILS
========================================================= */

if ($is_admin) {

    $stmt = mysqli_prepare(
        $conn,
        "SELECT
            orders.id,
            orders.user_id,
            orders.total_amount,
            orders.address,
            orders.phone,
            orders.status,
            orders.created_at,
            users.name AS customer_name,
            users.email AS customer_email
         FROM orders
         INNER JOIN users
            ON orders.user_id = users.id
         WHERE orders.id = ?"
    );

    mysqli_stmt_bind_param(
        $stmt,
        "i",
        $order_id
    );

} else {

    $stmt = mysqli_prepare(
        $conn,
        "SELECT
            orders.id,
            orders.user_id,
            orders.total_amount,
            orders.address,
            orders.phone,
            orders.status,
            orders.created_at,
            users.name AS customer_name,
            users.email AS customer_email
         FROM orders
         INNER JOIN users
            ON orders.user_id = users.id
         WHERE orders.id = ?
         AND orders.user_id = ?"
    );

    mysqli_stmt_bind_param(
        $stmt,
        "ii",
        $order_id,
        $user_id
    );
}


mysqli_stmt_execute(
    $stmt
);

$result = mysqli_stmt_get_result(
    $stmt
);


/* =========================================================
   ORDER NOT FOUND
========================================================= */

if (mysqli_num_rows($result) === 0) {

    mysqli_stmt_close($stmt);

    if ($is_admin) {

        header("Location: admin/orders.php");
        exit;

    } else {

        echo "<div style='
            max-width:600px;
            margin:80px auto;
            padding:30px;
            text-align:center;
            background:#ffffff;
            border:1px solid #e5e7eb;
            border-radius:12px;
            font-family:Arial,sans-serif;
        '>

            <h2 style='color:#dc2626;'>
                🚫 Access Denied
            </h2>

            <p style='color:#475569;'>
                You are not authorized to view this order.
            </p>

            <a href='my_orders.php'
               style='
                   display:inline-block;
                   margin-top:15px;
                   padding:10px 18px;
                   background:#2563eb;
                   color:#ffffff;
                   text-decoration:none;
                   border-radius:8px;
               '>
                ← Back to My Orders
            </a>

        </div>";

        exit;
    }
}

$order = mysqli_fetch_assoc(
    $result
);

mysqli_stmt_close(
    $stmt
);


/* =========================================================
   GET ORDER PRODUCTS
========================================================= */

$item_stmt = mysqli_prepare(
    $conn,
    "SELECT
        order_items.product_id,
        order_items.quantity,
        order_items.price,
        order_items.subtotal,
        products.name,
        products.image
     FROM order_items
     INNER JOIN products
        ON order_items.product_id = products.id
     WHERE order_items.order_id = ?
     ORDER BY order_items.id ASC"
);

mysqli_stmt_bind_param(
    $item_stmt,
    "i",
    $order_id
);

mysqli_stmt_execute(
    $item_stmt
);

$items_result = mysqli_stmt_get_result(
    $item_stmt
);


/* =========================================================
   GET ORDER STATUS HISTORY
========================================================= */

$history_stmt = mysqli_prepare(
    $conn,
    "SELECT
        status,
        changed_at
     FROM order_status_history
     WHERE order_id = ?
     ORDER BY changed_at ASC, id ASC"
);

mysqli_stmt_bind_param(
    $history_stmt,
    "i",
    $order_id
);

mysqli_stmt_execute(
    $history_stmt
);

$history_result = mysqli_stmt_get_result(
    $history_stmt
);


/* =========================================================
   STORE HISTORY IN ARRAY
========================================================= */

$status_history = [];

while (
    $history_row =
    mysqli_fetch_assoc($history_result)
) {

    $status_history[] = $history_row;
}

mysqli_stmt_close(
    $history_stmt
);


/* =========================================================
   PAGE TITLE
========================================================= */

$page_title =
    "Order #" . $order_id . " - Kharido";


include "includes/header.php";

?>


<!-- =========================================================
     ORDER DETAILS PAGE
========================================================= -->

<main class="order-details-page">

    <div class="order-details-container">


        <!-- =================================================
             TOP SECTION
        ================================================== -->

        <div class="order-details-top">

            <div>

                <h1>
                    Order #<?php
                    echo (int) $order["id"];
                    ?>
                </h1>

                <p>
                    Placed on
                    <?php
                    echo date(
                        "d M Y, h:i A",
                        strtotime(
                            $order["created_at"]
                        )
                    );
                    ?>
                </p>

            </div>


            <span
                class="order-status <?php
                    echo strtolower(
                        $order["status"]
                    );
                ?>"
            >
                <?php
                echo htmlspecialchars(
                    $order["status"]
                );
                ?>
            </span>

        </div>



        <!-- =================================================
             ORDER STATUS TIMELINE
        ================================================== -->

        <div class="order-timeline-box">

            <h2>
                🚚 Order Status
            </h2>


            <?php

            $timeline_statuses = [
                "Pending",
                "Processing",
                "Shipped",
                "Delivered"
            ];


            $current_status =
                $order["status"];


            /*
               Find current status position.
            */

            $current_position = -1;

            foreach (
                $timeline_statuses
                as $index => $timeline_status
            ) {

                if (
                    $timeline_status ===
                    $current_status
                ) {

                    $current_position =
                        $index;

                    break;
                }
            }


            /* =============================================
               CANCELLED ORDER
            ============================================== */

            if (
                $current_status ===
                "Cancelled"
            ) {

            ?>

                <div class="timeline-cancelled">

                    <div class="timeline-icon cancelled-icon">
                        ✕
                    </div>

                    <div class="timeline-content">

                        <strong>
                            Order Cancelled
                        </strong>

                        <?php

                        foreach (
                            $status_history
                            as $history
                        ) {

                            if (
                                $history["status"] ===
                                "Cancelled"
                            ) {

                        ?>

                            <span>
                                <?php
                                echo date(
                                    "d M Y, h:i A",
                                    strtotime(
                                        $history[
                                            "changed_at"
                                        ]
                                    )
                                );
                                ?>
                            </span>

                        <?php

                                break;
                            }
                        }

                        ?>

                    </div>

                </div>

            <?php

            } else {

            ?>


                <div class="order-timeline">


                    <?php

                    foreach (
                        $timeline_statuses
                        as $index => $timeline_status
                    ) {


                        /*
                           Check whether this status
                           exists in history.
                        */

                        $history_date = "";

                        foreach (
                            $status_history
                            as $history
                        ) {

                            if (
                                $history["status"] ===
                                $timeline_status
                            ) {

                                $history_date =
                                    $history[
                                        "changed_at"
                                    ];

                                break;
                            }
                        }


                        /*
                           Status is completed if:
                           - history exists
                           OR
                           - it is before current status
                        */

                        $is_completed =
                            (
                                $history_date !== "" ||
                                (
                                    $current_position >= 0 &&
                                    $index <=
                                    $current_position
                                )
                            );


                        $is_current =
                            (
                                $timeline_status ===
                                $current_status
                            );


                        $status_class = "";

                        if ($is_completed) {
                            $status_class .=
                                " timeline-completed";
                        }

                        if ($is_current) {
                            $status_class .=
                                " timeline-current";
                        }

                    ?>


                        <div
                            class="timeline-step<?php
                            echo $status_class;
                            ?>"
                        >


                            <!-- Timeline Icon -->

                            <div class="timeline-icon">

                                <?php

                                if ($is_completed) {
                                    echo "✓";
                                } else {
                                    echo "○";
                                }

                                ?>

                            </div>


                            <!-- Timeline Content -->

                            <div class="timeline-content">

                                <strong>

                                    <?php
                                    echo htmlspecialchars(
                                        $timeline_status
                                    );
                                    ?>

                                </strong>


                                <?php

                                if (
                                    $history_date !== ""
                                ) {

                                ?>

                                    <span>

                                        <?php
                                        echo date(
                                            "d M Y, h:i A",
                                            strtotime(
                                                $history_date
                                            )
                                        );
                                        ?>

                                    </span>

                                <?php

                                } elseif (
                                    $is_current
                                ) {

                                ?>

                                    <span>
                                        Current Status
                                    </span>

                                <?php

                                } else {

                                ?>

                                    <span>
                                        Pending
                                    </span>

                                <?php

                                ?>

                                <?php
                                }
                                ?>

                            </div>


                        </div>


                    <?php

                    }

                    ?>


                </div>


            <?php

            }

            ?>

        </div>



        <!-- =================================================
             CUSTOMER DETAILS
        ================================================== -->

        <div class="order-customer-box">

            <h2>
                👤 Customer Details
            </h2>


            <div class="customer-details-grid">


                <div class="detail-card">

                    <strong>
                        Name
                    </strong>

                    <p>
                        <?php
                        echo htmlspecialchars(
                            $order["customer_name"]
                        );
                        ?>
                    </p>

                </div>


                <div class="detail-card">

                    <strong>
                        Email
                    </strong>

                    <p>
                        <?php
                        echo htmlspecialchars(
                            $order["customer_email"]
                        );
                        ?>
                    </p>

                </div>


                <div class="detail-card">

                    <strong>
                        Phone
                    </strong>

                    <p>
                        <?php
                        echo htmlspecialchars(
                            $order["phone"]
                        );
                        ?>
                    </p>

                </div>


                <div class="detail-card">

                    <strong>
                        Order Date
                    </strong>

                    <p>
                        <?php
                        echo date(
                            "d M Y, h:i A",
                            strtotime(
                                $order["created_at"]
                            )
                        );
                        ?>
                    </p>

                </div>


            </div>

        </div>



        <!-- =================================================
             ORDER PRODUCTS
        ================================================== -->

        <div class="order-products-box">

            <h2>
                📦 Ordered Products
            </h2>


            <?php

            $item_count = 0;

            while (
                $item =
                mysqli_fetch_assoc(
                    $items_result
                )
            ) {

                $item_count++;

            ?>

                <div class="order-product">


                    <div class="order-product-image">

                        <?php
                        if (
                            !empty(
                                $item["image"]
                            )
                        ) {
                        ?>

                            <img
                                src="images/products/<?php
                                echo htmlspecialchars(
                                    $item["image"]
                                );
                                ?>"
                                alt="<?php
                                echo htmlspecialchars(
                                    $item["name"]
                                );
                                ?>"
                            >

                        <?php
                        } else {
                        ?>

                            <span>
                                🛍️
                            </span>

                        <?php
                        }
                        ?>

                    </div>


                    <div class="order-product-info">

                        <h3>
                            <?php
                            echo htmlspecialchars(
                                $item["name"]
                            );
                            ?>
                        </h3>

                        <p>
                            💰 Price:
                            ₹<?php
                            echo number_format(
                                $item["price"],
                                2
                            );
                            ?>
                        </p>

                        <p>
                            🔢 Quantity:
                            <?php
                            echo (int)
                                $item["quantity"];
                            ?>
                        </p>

                    </div>


                    <div class="order-product-subtotal">

                        ₹<?php
                        echo number_format(
                            $item["subtotal"],
                            2
                        );
                        ?>

                    </div>


                </div>

            <?php
            }


            if ($item_count === 0) {
            ?>

                <div class="no-order-items">
                    No products found for this order.
                </div>

            <?php
            }

            ?>

        </div>



        <!-- =================================================
             DELIVERY DETAILS
        ================================================== -->

        <div class="order-delivery-box">

            <h2>
                📍 Delivery Details
            </h2>


            <div class="delivery-info">


                <div>

                    <strong>
                        📞 Phone
                    </strong>

                    <p>
                        <?php
                        echo htmlspecialchars(
                            $order["phone"]
                        );
                        ?>
                    </p>

                </div>


                <div>

                    <strong>
                        📍 Delivery Address
                    </strong>

                    <p>
                        <?php
                        echo nl2br(
                            htmlspecialchars(
                                $order["address"]
                            )
                        );
                        ?>
                    </p>

                </div>


            </div>

        </div>



        <!-- =================================================
             TOTAL AMOUNT
        ================================================== -->

        <div class="order-total-box">

            <span>
                💵 Total Amount
            </span>

            <strong>
                ₹<?php
                echo number_format(
                    $order["total_amount"],
                    2
                );
                ?>
            </strong>

        </div>

            <?php
/* =========================================================
   CUSTOMER CANCEL ORDER
========================================================= */

if (
    !$is_admin &&
    $order["status"] === "Pending"
) {
?>
    <div class="customer-cancel-order-box">

        <h2>
            ⚠️ Cancel Order
        </h2>

        <p>
            You can cancel this order while it is still pending.
        </p>

        <form
            method="POST"
            action="cancel_order.php"
            onsubmit="return confirm('Are you sure you want to cancel this order?');"
        >

            <input
                type="hidden"
                name="order_id"
                value="<?php echo (int) $order_id; ?>"
            >

            <button
                type="submit"
                class="cancel-order-button"
            >
                ✕ Cancel Order
            </button>

        </form>

    </div>
<?php
}
?>

        <!-- =================================================
             ADMIN STATUS UPDATE
        ================================================== -->

        <?php

        if ($is_admin) {

        ?>

            <div class="admin-status-update-box">

                <h2>
                    🚚 Update Order Status
                </h2>


                <form
                    method="POST"
                    action="order_details.php?id=<?php
                    echo (int) $order_id;
                    ?>"
                    class="admin-status-update-form"
                >

                    <div>

                        <label for="status">
                            Current Status
                        </label>


                        <select
                            name="status"
                            id="status"
                        >

                            <option
                                value="Pending"
                                <?php
                                if (
                                    $order["status"] ===
                                    "Pending"
                                ) {
                                    echo "selected";
                                }
                                ?>
                            >
                                Pending
                            </option>


                            <option
                                value="Processing"
                                <?php
                                if (
                                    $order["status"] ===
                                    "Processing"
                                ) {
                                    echo "selected";
                                }
                                ?>
                            >
                                Processing
                            </option>


                            <option
                                value="Shipped"
                                <?php
                                if (
                                    $order["status"] ===
                                    "Shipped"
                                ) {
                                    echo "selected";
                                }
                                ?>
                            >
                                Shipped
                            </option>


                            <option
                                value="Delivered"
                                <?php
                                if (
                                    $order["status"] ===
                                    "Delivered"
                                ) {
                                    echo "selected";
                                }
                                ?>
                            >
                                Delivered
                            </option>


                            <option
                                value="Cancelled"
                                <?php
                                if (
                                    $order["status"] ===
                                    "Cancelled"
                                ) {
                                    echo "selected";
                                }
                                ?>
                            >
                                Cancelled
                            </option>

                        </select>

                    </div>


                    <button
                        type="submit"
                        class="update-order-status-button"
                    >
                        🔄 Update Status
                    </button>

                </form>

            </div>

        <?php

        }

        ?>



        <!-- =================================================
             BACK BUTTON
        ================================================== -->

        <?php

        if ($is_admin) {

        ?>

            <a
                href="admin/orders.php"
                class="back-orders-button"
            >
                ← Back to Manage Orders
            </a>

        <?php

        } else {

        ?>

            <a
                href="my_orders.php"
                class="back-orders-button"
            >
                ← Back to My Orders
            </a>

        <?php

        }

        ?>


    </div>

</main>



<!-- =========================================================
     ADDITIONAL CSS
     Existing CSS is NOT changed.
========================================================= -->

<style>

/* =========================================================
   CUSTOMER DETAILS
========================================================= */

.order-customer-box,
.admin-status-update-box {
    margin-top: 20px;
    padding: 24px;
    border-radius: 12px;
    background: #ffffff;
    border: 1px solid #e5e7eb;
}

.order-customer-box h2,
.admin-status-update-box h2,
.order-timeline-box h2 {
    margin-top: 0;
    margin-bottom: 20px;
}

.customer-details-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 16px;
}

.detail-card {
    padding: 16px;
    border-radius: 10px;
    background: #f8fafc;
    border: 1px solid #e5e7eb;
}

.detail-card strong {
    display: block;
    margin-bottom: 6px;
    color: #475569;
}

.detail-card p {
    margin: 0;
    color: #111827;
    word-break: break-word;
}


/* =========================================================
   ORDER TIMELINE
========================================================= */

.order-timeline-box {
    margin-top: 20px;
    padding: 24px;
    border-radius: 12px;
    background: #ffffff;
    border: 1px solid #e5e7eb;
}

.order-timeline {
    position: relative;
    margin-top: 10px;
    padding-left: 10px;
}

.timeline-step {
    position: relative;
    display: flex;
    align-items: flex-start;
    gap: 15px;
    min-height: 75px;
    padding-bottom: 20px;
}

.timeline-step:not(:last-child)::after {
    content: "";
    position: absolute;
    left: 15px;
    top: 32px;
    width: 2px;
    height: 55px;
    background: #d1d5db;
}

.timeline-icon {
    position: relative;
    z-index: 2;
    width: 32px;
    height: 32px;
    min-width: 32px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 50%;
    background: #e5e7eb;
    color: #64748b;
    font-weight: 700;
}

.timeline-completed .timeline-icon {
    background: #16a34a;
    color: #ffffff;
}

.timeline-current .timeline-icon {
    background: #2563eb;
    color: #ffffff;
    box-shadow: 0 0 0 5px #dbeafe;
}

.timeline-content {
    display: flex;
    flex-direction: column;
    gap: 4px;
    padding-top: 3px;
}

.timeline-content strong {
    color: #1f2937;
    font-size: 16px;
}

.timeline-current .timeline-content strong {
    color: #2563eb;
}

.timeline-content span {
    color: #64748b;
    font-size: 13px;
}


/* =========================================================
   CANCELLED TIMELINE
========================================================= */

.timeline-cancelled {
    display: flex;
    align-items: center;
    gap: 15px;
    padding: 10px 0;
}

.cancelled-icon {
    background: #dc2626;
    color: #ffffff;
}

.timeline-cancelled .timeline-content {
    display: flex;
    flex-direction: column;
    gap: 4px;
}

.timeline-cancelled .timeline-content strong {
    color: #dc2626;
}


/* =========================================================
   EMPTY ITEMS
========================================================= */

.no-order-items {
    padding: 25px;
    text-align: center;
    color: #64748b;
}


/* =========================================================
   ADMIN STATUS UPDATE
========================================================= */

.admin-status-update-box {
    background: #f8fafc;
}

.admin-status-update-form {
    display: flex;
    align-items: end;
    gap: 15px;
    flex-wrap: wrap;
}

.admin-status-update-form > div {
    display: flex;
    flex-direction: column;
    gap: 7px;
}

.admin-status-update-form label {
    font-weight: 600;
    color: #334155;
}

.admin-status-update-form select {
    min-width: 220px;
    padding: 11px 14px;
    border: 1px solid #cbd5e1;
    border-radius: 8px;
    background: #ffffff;
    font-size: 15px;
    cursor: pointer;
}

.admin-status-update-form select:focus {
    outline: none;
    border-color: #2563eb;
}

.update-order-status-button {
    padding: 11px 18px;
    border: none;
    border-radius: 8px;
    background: #2563eb;
    color: #ffffff;
    font-size: 15px;
    font-weight: 600;
    cursor: pointer;
}

.update-order-status-button:hover {
    opacity: 0.9;
}


/* =========================================================
   MOBILE
========================================================= */

@media (max-width: 700px) {

    .customer-details-grid {
        grid-template-columns: 1fr;
    }

    .admin-status-update-form {
        align-items: stretch;
        flex-direction: column;
    }

    .admin-status-update-form select {
        width: 100%;
        min-width: 0;
    }

    .update-order-status-button {
        width: 100%;
    }

    .order-timeline-box {
        padding: 18px;
    }

}

</style>


<?php

mysqli_stmt_close(
    $item_stmt
);

include "includes/footer.php";

?>