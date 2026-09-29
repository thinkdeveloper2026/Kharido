<?php

session_start();

include "config/database.php";


// User login check
if (!isset($_SESSION["user_id"])) {

    header("Location: login.php");
    exit;
}


// Cart empty check
if (!isset($_SESSION["cart"]) || empty($_SESSION["cart"])) {

    header("Location: cart.php");
    exit;
}


// Calculate cart total
$subtotal = 0;
$total_items = 0;

foreach ($_SESSION["cart"] as $item) {

    $subtotal +=
        $item["price"] * $item["quantity"];

    $total_items += $item["quantity"];
}


$page_title = "Checkout - Kharido";

include "includes/header.php";

?>


<main class="checkout-page">

    <div class="checkout-container">

        <h1>
            Checkout
        </h1>


        <div class="checkout-content">


            <!-- Customer Details -->

            <div class="checkout-form">

                <h2>
                    Delivery Details
                </h2>


                <form
                    method="POST"
                    action="place_order.php"
                >

                    <div class="form-group">

                        <label>
                            Full Name
                        </label>

                        <input
                            type="text"
                            name="name"
                            value="<?php
                                echo htmlspecialchars(
                                    $_SESSION["user_name"]
                                );
                            ?>"
                            required
                        >

                    </div>


                    <div class="form-group">

                        <label>
                            Email
                        </label>

                        <input
                            type="email"
                            name="email"
                            value="<?php
                                echo htmlspecialchars(
                                    $_SESSION["user_email"]
                                );
                            ?>"
                            readonly
                        >

                    </div>


                    <div class="form-group">

                        <label>
                            Phone Number
                        </label>

                        <input
                            type="tel"
                            name="phone"
                            placeholder="Enter phone number"
                            maxlength="15"
                            required
                        >

                    </div>


                    <div class="form-group">

                        <label>
                            Delivery Address
                        </label>

                        <textarea
                            name="address"
                            rows="5"
                            placeholder="Enter complete delivery address"
                            required
                        ></textarea>

                    </div>


                    <button
                        type="submit"
                        class="place-order-button"
                    >
                        🛍️ Place Order
                    </button>

                </form>

            </div>


            <!-- Order Summary -->

            <div class="checkout-summary">

                <h2>
                    Order Summary
                </h2>


                <?php foreach ($_SESSION["cart"] as $item) { ?>

                    <div class="checkout-item">

                        <div>

                            <strong>
                                <?php
                                echo htmlspecialchars(
                                    $item["name"]
                                );
                                ?>
                            </strong>

                            <p>
                                Quantity:
                                <?php
                                echo $item["quantity"];
                                ?>
                            </p>

                        </div>


                        <span>

                            ₹<?php
                            echo number_format(
                                $item["price"] *
                                $item["quantity"],
                                2
                            );
                            ?>

                        </span>

                    </div>

                <?php } ?>


                <hr>


                <div class="summary-row">

                    <span>
                        Items
                    </span>

                    <span>
                        <?php echo $total_items; ?>
                    </span>

                </div>


                <div class="summary-row">

                    <span>
                        Subtotal
                    </span>

                    <span>
                        ₹<?php
                        echo number_format(
                            $subtotal,
                            2
                        );
                        ?>
                    </span>

                </div>


                <div class="summary-row">

                    <span>
                        Delivery
                    </span>

                    <span class="free-delivery">
                        FREE
                    </span>

                </div>


                <div class="checkout-total">

                    <span>
                        Total
                    </span>

                    <span>
                        ₹<?php
                        echo number_format(
                            $subtotal,
                            2
                        );
                        ?>
                    </span>

                </div>

            </div>

        </div>

    </div>

</main>


<?php

include "includes/footer.php";

?>