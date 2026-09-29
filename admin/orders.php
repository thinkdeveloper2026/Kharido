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
   STEP 10
   SEARCH + FILTER + PAGINATION
======================================== */

$search = isset($_GET["search"])
    ? trim($_GET["search"])
    : "";

$status_filter = isset($_GET["status"])
    ? trim($_GET["status"])
    : "All";


/* ========================================
   VALID STATUS VALUES
======================================== */

$allowed_statuses = [
    "Pending",
    "Processing",
    "Shipped",
    "Delivered",
    "Cancelled"
];

if (
    $status_filter !== "All" &&
    !in_array(
        $status_filter,
        $allowed_statuses,
        true
    )
) {
    $status_filter = "All";
}


/* ========================================
   PAGINATION
======================================== */

$orders_per_page = 10;

$current_page = isset($_GET["page"])
    ? (int) $_GET["page"]
    : 1;

if ($current_page < 1) {
    $current_page = 1;
}


/* ========================================
   BUILD WHERE CONDITION
======================================== */

$where_conditions = [];

$params = [];

$param_types = "";


/* Search */

if ($search !== "") {

    $where_conditions[] = "
        (
            orders.id LIKE ?
            OR users.name LIKE ?
            OR users.email LIKE ?
            OR orders.phone LIKE ?
        )
    ";

    $search_value = "%" . $search . "%";

    $params[] = $search_value;
    $params[] = $search_value;
    $params[] = $search_value;
    $params[] = $search_value;

    $param_types .= "ssss";
}


/* Status */

if ($status_filter !== "All") {

    $where_conditions[] =
        "orders.status = ?";

    $params[] =
        $status_filter;

    $param_types .= "s";
}


$where_sql = "";

if (count($where_conditions) > 0) {

    $where_sql =
        "WHERE " .
        implode(
            " AND ",
            $where_conditions
        );
}


/* ========================================
   COUNT TOTAL ORDERS
======================================== */

$count_sql = "
    SELECT COUNT(*) AS total
    FROM orders
    INNER JOIN users
        ON orders.user_id = users.id
    $where_sql
";


$count_stmt = mysqli_prepare(
    $conn,
    $count_sql
);

if (
    $count_stmt &&
    count($params) > 0
) {

    mysqli_stmt_bind_param(
        $count_stmt,
        $param_types,
        ...$params
    );
}

if ($count_stmt) {

    mysqli_stmt_execute(
        $count_stmt
    );

    $count_result =
        mysqli_stmt_get_result(
            $count_stmt
        );

    $count_row =
        mysqli_fetch_assoc(
            $count_result
        );

    $total_orders_found =
        (int) $count_row["total"];

    mysqli_stmt_close(
        $count_stmt
    );

} else {

    $total_orders_found = 0;
}


/* ========================================
   TOTAL PAGES
======================================== */

$total_pages = max(
    1,
    (int) ceil(
        $total_orders_found /
        $orders_per_page
    )
);


if (
    $current_page >
    $total_pages
) {

    $current_page =
        $total_pages;
}


$offset =
    (
        $current_page - 1
    ) * $orders_per_page;


/* ========================================
   GET ORDERS
======================================== */

$order_sql = "
    SELECT
        orders.id,
        orders.total_amount,
        orders.address,
        orders.phone,
        orders.status,
        orders.created_at,
        users.name AS user_name,
        users.email AS user_email
    FROM orders
    INNER JOIN users
        ON orders.user_id = users.id
    $where_sql
    ORDER BY orders.created_at DESC
    LIMIT ? OFFSET ?
";


$order_stmt = mysqli_prepare(
    $conn,
    $order_sql
);


/* Add pagination parameters */

if ($order_stmt) {

    $order_params =
        $params;

    $order_types =
        $param_types . "ii";

    $order_params[] =
        $orders_per_page;

    $order_params[] =
        $offset;


    mysqli_stmt_bind_param(
        $order_stmt,
        $order_types,
        ...$order_params
    );


    mysqli_stmt_execute(
        $order_stmt
    );


    $result =
        mysqli_stmt_get_result(
            $order_stmt
        );

} else {

    $result = false;
}


/* ========================================
   PAGE TITLE
======================================== */

$page_title =
    "Manage Orders - Kharido";

include "admin_header.php";

?>


<style>

/* ========================================
   EXISTING ORDER STATUS BADGES
   DO NOT CHANGE
======================================== */

.order-status {
    display: inline-block;
    padding: 6px 10px;
    border-radius: 20px;
    font-size: 12px;
    font-weight: 600;
    white-space: nowrap;
}


/* Pending */

.order-status.pending {
    background: #fff3cd;
    color: #856404;
}


/* Processing */

.order-status.processing {
    background: #ede9fe;
    color: #6d28d9;
}


/* Shipped */

.order-status.shipped {
    background: #dbeafe;
    color: #1d4ed8;
}


/* Delivered */

.order-status.delivered {
    background: #dcfce7;
    color: #166534;
}


/* Cancelled */

.order-status.cancelled {
    background: #fee2e2;
    color: #b91c1c;
}


/* ========================================
   EXISTING ORDER STATUS UPDATE AREA
   DO NOT CHANGE
======================================== */

.order-status-form {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 5px 0;
}

.order-status-form select {
    padding: 8px 12px;
    border: 1px solid #d1d5db;
    border-radius: 6px;
    background: #fff;
    color: #374151;
    font-size: 13px;
    cursor: pointer;
}

.order-status-form select:focus {
    outline: none;
    border-color: #0066ff;
    box-shadow:
        0 0 0 2px rgba(0,102,255,0.1);
}

.small-button {
    padding: 8px 13px;
    font-size: 13px;
}


/* ========================================
   EXISTING CUSTOMER EMAIL
   DO NOT CHANGE
======================================== */

.admin-email {
    display: block;
    margin-top: 4px;
    color: #6b7280;
    font-size: 12px;
}


/* ========================================
   EXISTING RESPONSIVE
   DO NOT CHANGE
======================================== */

@media (max-width: 700px) {

    .order-status-form {
        flex-direction: column;
        align-items: stretch;
    }

    .order-status-form select,
    .order-status-form button {
        width: 100%;
    }
}


/* ========================================
   STEP 10 NEW CSS
   EXISTING CSS ABOVE IS UNCHANGED
======================================== */


/* SEARCH + FILTER */

.orders-tools {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 15px;
    margin-bottom: 20px;
    flex-wrap: wrap;
}

.orders-search-form {
    display: flex;
    align-items: center;
    gap: 8px;
    flex: 1;
    min-width: 280px;
}

.orders-search-input {
    width: 100%;
    max-width: 420px;
    padding: 10px 13px;
    border: 1px solid #d1d5db;
    border-radius: 7px;
    font-size: 13px;
    outline: none;
}

.orders-search-input:focus {
    border-color: #0066ff;
    box-shadow:
        0 0 0 2px rgba(0,102,255,0.1);
}

.orders-search-button {
    padding: 10px 15px;
    border: none;
    border-radius: 7px;
    background: #0066ff;
    color: #fff;
    cursor: pointer;
    font-size: 13px;
}

.orders-search-button:hover {
    background: #0052cc;
}


.orders-filter-form {
    display: flex;
    align-items: center;
    gap: 8px;
}

.orders-filter-label {
    font-size: 13px;
    color: #4b5563;
    font-weight: 600;
}

.orders-filter-select {
    padding: 10px 12px;
    border: 1px solid #d1d5db;
    border-radius: 7px;
    background: #fff;
    color: #374151;
    font-size: 13px;
    cursor: pointer;
}


/* RESULTS INFO */

.orders-results-info {
    margin-bottom: 15px;
    color: #6b7280;
    font-size: 13px;
}


/* CLEAR FILTER */

.orders-clear-button {
    padding: 10px 13px;
    border-radius: 7px;
    border: 1px solid #d1d5db;
    background: #fff;
    color: #374151;
    text-decoration: none;
    font-size: 13px;
}

.orders-clear-button:hover {
    background: #f3f4f6;
}


/* PAGINATION */

.orders-pagination {
    display: flex;
    justify-content: center;
    align-items: center;
    gap: 6px;
    margin-top: 22px;
    flex-wrap: wrap;
}

.orders-page-link {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-width: 36px;
    height: 36px;
    padding: 0 10px;
    border: 1px solid #d1d5db;
    border-radius: 6px;
    background: #fff;
    color: #374151;
    text-decoration: none;
    font-size: 13px;
}

.orders-page-link:hover {
    background: #f3f4f6;
}

.orders-page-link.active {
    background: #0066ff;
    color: #fff;
    border-color: #0066ff;
}

.orders-page-link.disabled {
    color: #9ca3af;
    background: #f9fafb;
    pointer-events: none;
}


/* NO RESULT */

.orders-no-result {
    text-align: center;
    padding: 35px 20px;
    color: #6b7280;
}

.orders-no-result-icon {
    font-size: 35px;
    margin-bottom: 10px;
}

.orders-no-result-title {
    font-size: 16px;
    font-weight: 600;
    color: #374151;
    margin-bottom: 5px;
}


/* ORDER UPDATE ROW */

.order-update-row {
    background: #fafafa;
}

.order-update-row td {
    border-bottom: 1px solid #eee;
}


/* RESPONSIVE NEW CSS */

@media (max-width: 800px) {

    .orders-tools {
        align-items: stretch;
        flex-direction: column;
    }

    .orders-search-form {
        width: 100%;
        min-width: 0;
    }

    .orders-search-input {
        max-width: none;
    }

    .orders-filter-form {
        width: 100%;
    }

    .orders-filter-select {
        flex: 1;
    }
}


@media (max-width: 500px) {

    .orders-search-form {
        flex-wrap: wrap;
    }

    .orders-search-input {
        width: 100%;
    }

    .orders-search-button {
        width: 100%;
    }

    .orders-clear-button {
        width: 100%;
        text-align: center;
    }

}

</style>


<main class="admin-page">

    <div class="admin-container">


        <!-- ========================================
             PAGE HEADER
        ========================================= -->

        <div class="admin-page-header">

            <div>

                <h1>
                    Manage Orders
                </h1>

                <p>
                    View and update customer orders.
                </p>

            </div>

        </div>


        <!-- ========================================
             STEP 10
             SEARCH + FILTER
        ========================================= -->

        <div class="orders-tools">


            <!-- SEARCH -->

            <form
                method="GET"
                class="orders-search-form"
            >

                <input
                    type="text"
                    name="search"
                    class="orders-search-input"
                    placeholder="Search by Order ID, customer, email or phone..."
                    value="<?php
                    echo htmlspecialchars(
                        $search
                    );
                    ?>"
                >

                <?php if (
                    $status_filter !== "All"
                ): ?>

                    <input
                        type="hidden"
                        name="status"
                        value="<?php
                        echo htmlspecialchars(
                            $status_filter
                        );
                        ?>"
                    >

                <?php endif; ?>


                <button
                    type="submit"
                    class="orders-search-button"
                >
                    🔍 Search
                </button>

            </form>


            <!-- STATUS FILTER -->

            <form
                method="GET"
                class="orders-filter-form"
            >

                <?php if (
                    $search !== ""
                ): ?>

                    <input
                        type="hidden"
                        name="search"
                        value="<?php
                        echo htmlspecialchars(
                            $search
                        );
                        ?>"
                    >

                <?php endif; ?>


                <label
                    class="orders-filter-label"
                >
                    Status:
                </label>


                <select
                    name="status"
                    class="orders-filter-select"
                    onchange="this.form.submit()"
                >

                    <option
                        value="All"
                        <?php
                        echo
                            $status_filter === "All"
                            ? "selected"
                            : "";
                        ?>
                    >
                        All Orders
                    </option>

                    <option
                        value="Pending"
                        <?php
                        echo
                            $status_filter === "Pending"
                            ? "selected"
                            : "";
                        ?>
                    >
                        Pending
                    </option>

                    <option
                        value="Processing"
                        <?php
                        echo
                            $status_filter === "Processing"
                            ? "selected"
                            : "";
                        ?>
                    >
                        Processing
                    </option>

                    <option
                        value="Shipped"
                        <?php
                        echo
                            $status_filter === "Shipped"
                            ? "selected"
                            : "";
                        ?>
                    >
                        Shipped
                    </option>

                    <option
                        value="Delivered"
                        <?php
                        echo
                            $status_filter === "Delivered"
                            ? "selected"
                            : "";
                        ?>
                    >
                        Delivered
                    </option>

                    <option
                        value="Cancelled"
                        <?php
                        echo
                            $status_filter === "Cancelled"
                            ? "selected"
                            : "";
                        ?>
                    >
                        Cancelled
                    </option>

                </select>

            </form>


            <!-- CLEAR -->

            <?php if (
                $search !== "" ||
                $status_filter !== "All"
            ): ?>

                <a
                    href="orders.php"
                    class="orders-clear-button"
                >
                    ✕ Clear
                </a>

            <?php endif; ?>

        </div>


        <!-- ========================================
             RESULTS INFO
        ========================================= -->

        <div class="orders-results-info">

            Showing

            <?php
            if ($total_orders_found > 0) {

                $start_number =
                    $offset + 1;

                $end_number =
                    min(
                        $offset +
                        $orders_per_page,
                        $total_orders_found
                    );

                echo $start_number .
                    " - " .
                    $end_number;

            } else {

                echo "0";
            }
            ?>

            of

            <?php
            echo $total_orders_found;
            ?>

            order(s)

        </div>


        <!-- ========================================
             ORDERS TABLE
        ========================================= -->

        <div class="admin-table-container">

            <table class="admin-table">

                <thead>

                    <tr>

                        <th>
                            Order ID
                        </th>

                        <th>
                            Customer
                        </th>

                        <th>
                            Phone
                        </th>

                        <th>
                            Total
                        </th>

                        <th>
                            Status
                        </th>

                        <th>
                            Date
                        </th>

                        <th>
                            Action
                        </th>

                    </tr>

                </thead>


                <tbody>


                <?php if (
                    $result &&
                    mysqli_num_rows($result) > 0
                ) { ?>


                    <?php while (
                        $order =
                        mysqli_fetch_assoc($result)
                    ) { ?>


                        <!-- ========================================
                             ORDER INFORMATION
                        ========================================= -->

                        <tr>


                            <!-- ORDER ID -->

                            <td>

                                #<?php
                                echo (int)
                                    $order["id"];
                                ?>

                            </td>


                            <!-- CUSTOMER -->

                            <td>

                                <strong>

                                    <?php
                                    echo htmlspecialchars(
                                        $order["user_name"]
                                    );
                                    ?>

                                </strong>

                                <small
                                    class="admin-email"
                                >

                                    <?php
                                    echo htmlspecialchars(
                                        $order["user_email"]
                                    );
                                    ?>

                                </small>

                            </td>


                            <!-- PHONE -->

                            <td>

                                <?php
                                echo htmlspecialchars(
                                    $order["phone"]
                                );
                                ?>

                            </td>


                            <!-- TOTAL -->

                            <td>

                                ₹<?php
                                echo number_format(
                                    $order[
                                        "total_amount"
                                    ],
                                    2
                                );
                                ?>

                            </td>


                            <!-- STATUS -->

                            <td>

                                <?php

                                $status =
                                    $order["status"];

                                $status_class =
                                    strtolower(
                                        $status
                                    );

                                ?>

                                <span
                                    class="
                                        order-status
                                        <?php
                                        echo htmlspecialchars(
                                            $status_class
                                        );
                                        ?>
                                    "
                                >

                                    <?php
                                    echo htmlspecialchars(
                                        $status
                                    );
                                    ?>

                                </span>

                            </td>


                            <!-- DATE -->

                            <td>

                                <?php

                                if (
                                    !empty(
                                        $order[
                                            "created_at"
                                        ]
                                    )
                                ) {

                                    echo date(
                                        "d M Y",
                                        strtotime(
                                            $order[
                                                "created_at"
                                            ]
                                        )
                                    );

                                } else {

                                    echo "N/A";
                                }

                                ?>

                            </td>


                            <!-- ACTION -->

                            <td>

                                <a
                                    href="../order_details.php?id=<?php
                                    echo (int)
                                        $order["id"];
                                    ?>"
                                    class="edit-button"
                                >
                                    View
                                </a>

                            </td>

                        </tr>


                        <!-- ========================================
                             UPDATE STATUS
                        ========================================= -->

                        <tr class="order-update-row">

                            <td colspan="7">

                                <form
                                    method="POST"
                                    action="update_order.php"
                                    class="order-status-form"
                                >


                                    <input
                                        type="hidden"
                                        name="order_id"
                                        value="<?php
                                        echo (int)
                                            $order["id"];
                                        ?>"
                                    >


                                    <select
                                        name="status"
                                        required
                                    >


                                        <!-- PENDING -->

                                        <option
                                            value="Pending"
                                            <?php
                                            echo
                                                $order["status"]
                                                === "Pending"
                                                ? "selected"
                                                : "";
                                            ?>
                                        >
                                            Pending
                                        </option>


                                        <!-- PROCESSING -->

                                        <option
                                            value="Processing"
                                            <?php
                                            echo
                                                $order["status"]
                                                === "Processing"
                                                ? "selected"
                                                : "";
                                            ?>
                                        >
                                            Processing
                                        </option>


                                        <!-- SHIPPED -->

                                        <option
                                            value="Shipped"
                                            <?php
                                            echo
                                                $order["status"]
                                                === "Shipped"
                                                ? "selected"
                                                : "";
                                            ?>
                                        >
                                            Shipped
                                        </option>


                                        <!-- DELIVERED -->

                                        <option
                                            value="Delivered"
                                            <?php
                                            echo
                                                $order["status"]
                                                === "Delivered"
                                                ? "selected"
                                                : "";
                                            ?>
                                        >
                                            Delivered
                                        </option>


                                        <!-- CANCELLED -->

                                        <option
                                            value="Cancelled"
                                            <?php
                                            echo
                                                $order["status"]
                                                === "Cancelled"
                                                ? "selected"
                                                : "";
                                            ?>
                                        >
                                            Cancelled
                                        </option>


                                    </select>


                                    <button
                                        type="submit"
                                        class="
                                            admin-primary-button
                                            small-button
                                        "
                                    >
                                        Update Status
                                    </button>


                                </form>

                            </td>

                        </tr>


                    <?php } ?>


                <?php } else { ?>


                    <!-- ========================================
                         NO ORDERS
                    ========================================= -->

                    <tr>

                        <td
                            colspan="7"
                            class="orders-no-result"
                        >

                            <div
                                class="orders-no-result-icon"
                            >
                                📦
                            </div>

                            <div
                                class="orders-no-result-title"
                            >
                                No orders found
                            </div>

                            <div>
                                Try changing your search
                                or status filter.
                            </div>

                        </td>

                    </tr>


                <?php } ?>


                </tbody>

            </table>

        </div>


        <!-- ========================================
             PAGINATION
        ========================================= -->

        <?php if (
            $total_pages > 1
        ): ?>

            <div class="orders-pagination">


                <!-- PREVIOUS -->

                <?php if (
                    $current_page > 1
                ): ?>

                    <a
                        href="?<?php

                        $query_params = [
                            "page" =>
                                $current_page - 1
                        ];

                        if ($search !== "") {
                            $query_params["search"] =
                                $search;
                        }

                        if (
                            $status_filter !==
                            "All"
                        ) {
                            $query_params["status"] =
                                $status_filter;
                        }

                        echo htmlspecialchars(
                            http_build_query(
                                $query_params
                            )
                        );

                        ?>"
                        class="orders-page-link"
                    >
                        ←
                    </a>

                <?php else: ?>

                    <span
                        class="
                            orders-page-link
                            disabled
                        "
                    >
                        ←
                    </span>

                <?php endif; ?>


                <!-- PAGE NUMBERS -->

                <?php

                $start_page =
                    max(
                        1,
                        $current_page - 2
                    );

                $end_page =
                    min(
                        $total_pages,
                        $current_page + 2
                    );

                for (
                    $page = $start_page;
                    $page <= $end_page;
                    $page++
                ):

                ?>

                    <?php

                    $query_params = [
                        "page" => $page
                    ];

                    if ($search !== "") {
                        $query_params["search"] =
                            $search;
                    }

                    if (
                        $status_filter !==
                        "All"
                    ) {
                        $query_params["status"] =
                            $status_filter;
                    }

                    $page_url =
                        "?" .
                        http_build_query(
                            $query_params
                        );

                    ?>


                    <a
                        href="<?php
                        echo htmlspecialchars(
                            $page_url
                        );
                        ?>"
                        class="
                            orders-page-link
                            <?php
                            echo
                                $page ===
                                $current_page
                                ? "active"
                                : "";
                            ?>
                        "
                    >

                        <?php
                        echo $page;
                        ?>

                    </a>


                <?php endfor; ?>


                <!-- NEXT -->

                <?php if (
                    $current_page <
                    $total_pages
                ): ?>

                    <a
                        href="?<?php

                        $query_params = [
                            "page" =>
                                $current_page + 1
                        ];

                        if ($search !== "") {
                            $query_params["search"] =
                                $search;
                        }

                        if (
                            $status_filter !==
                            "All"
                        ) {
                            $query_params["status"] =
                                $status_filter;
                        }

                        echo htmlspecialchars(
                            http_build_query(
                                $query_params
                            )
                        );

                        ?>"
                        class="orders-page-link"
                    >
                        →
                    </a>

                <?php else: ?>

                    <span
                        class="
                            orders-page-link
                            disabled
                        "
                    >
                        →
                    </span>

                <?php endif; ?>


            </div>

        <?php endif; ?>


    </div>

</main>


<?php

if (isset($order_stmt) && $order_stmt) {
    mysqli_stmt_close($order_stmt);
}

include "admin_footer.php";

?>