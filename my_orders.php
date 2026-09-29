<?php

session_start();

include "config/database.php";

/* Login check */
if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION["user_id"];

/* User ke orders fetch karo */
$stmt = mysqli_prepare(
    $conn,
    "SELECT id, total_amount, address, phone, status, created_at
     FROM orders
     WHERE user_id = ?
     ORDER BY created_at DESC"
);

mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);

$result = mysqli_stmt_get_result($stmt);

$page_title = "My Orders - Kharido";

include "includes/header.php";
?>

<main class="my-orders-page">

    <div class="my-orders-container">

        <h1>📦 My Orders</h1>

        <?php if (mysqli_num_rows($result) == 0) { ?>

            <div class="no-orders">

                <div class="no-orders-icon">
                    📦
                </div>

                <h2>No Orders Yet</h2>

                <p>
                    You haven't placed any orders yet.
                </p>

                <a
                    href="index.php"
                    class="continue-shopping-button"
                >
                    Start Shopping
                </a>

            </div>

        <?php } else { ?>

            <div class="orders-list">

                <?php while ($order = mysqli_fetch_assoc($result)) { ?>

                    <div class="order-card">

                        <div class="order-header">

                            <div>
                                <strong>
                                    Order #<?php echo $order["id"]; ?>
                                </strong>

                                <p>
                                    <?php
                                    echo date(
                                        "d M Y, h:i A",
                                        strtotime($order["created_at"])
                                    );
                                    ?>
                                </p>
                            </div>

                            <span
                                class="order-status
                                <?php
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

                        <div class="order-info">

                            <div>
                                <span>Order Total</span>

                                <strong>
                                    ₹<?php
                                    echo number_format(
                                        $order["total_amount"],
                                        2
                                    );
                                    ?>
                                </strong>
                            </div>

                            <div>
                                <span>Phone</span>

                                <strong>
                                    <?php
                                    echo htmlspecialchars(
                                        $order["phone"]
                                    );
                                    ?>
                                </strong>
                            </div>

                        </div>

                        <div class="order-address">

                            <strong>Delivery Address</strong>

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
                        <a
                            href="order_details.php?id=<?php echo $order["id"]; ?>"
                            class="view-order-button"
                        >
                            View Order Details →
                        </a>

                    </div>

                <?php } ?>

            </div>

        <?php } ?>

    </div>

</main>

<?php

mysqli_stmt_close($stmt);

include "includes/footer.php";

?>