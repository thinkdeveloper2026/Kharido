<?php

session_start();

include "config/database.php";


// Cart initialize
if (!isset($_SESSION["cart"])) {
    $_SESSION["cart"] = [];
}


// Add product to cart
if (isset($_GET["add"]) && is_numeric($_GET["add"])) {

    $product_id = (int) $_GET["add"];

    $quantity = isset($_GET["quantity"])
        ? (int) $_GET["quantity"]
        : 1;

    if ($quantity < 1) {
        $quantity = 1;
    }


    $stmt = mysqli_prepare(
        $conn,
        "SELECT id, name, price, image, stock
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


    if (mysqli_num_rows($result) == 1) {

        $product = mysqli_fetch_assoc($result);


        if ($product["stock"] > 0) {

            // Stock se zyada quantity allow nahi karenge
            if ($quantity > $product["stock"]) {
                $quantity = $product["stock"];
            }


            if (isset($_SESSION["cart"][$product_id])) {

                $new_quantity =
                    $_SESSION["cart"][$product_id]["quantity"]
                    + $quantity;

                if ($new_quantity > $product["stock"]) {
                    $new_quantity = $product["stock"];
                }

                $_SESSION["cart"][$product_id]["quantity"]
                    = $new_quantity;

            } else {

                $_SESSION["cart"][$product_id] = [

                    "id" => $product["id"],

                    "name" => $product["name"],

                    "price" => $product["price"],

                    "image" => $product["image"],

                    "quantity" => $quantity
                ];
            }
        }
    }

    mysqli_stmt_close($stmt);

    header("Location: cart.php");

    exit;
}

// Increase quantity
if (isset($_GET["increase"]) && is_numeric($_GET["increase"])) {

    $product_id = (int) $_GET["increase"];

    if (isset($_SESSION["cart"][$product_id])) {

        $stmt = mysqli_prepare(
            $conn,
            "SELECT stock FROM products WHERE id = ?"
        );

        mysqli_stmt_bind_param(
            $stmt,
            "i",
            $product_id
        );

        mysqli_stmt_execute($stmt);

        $result = mysqli_stmt_get_result($stmt);

        if (mysqli_num_rows($result) == 1) {

            $product = mysqli_fetch_assoc($result);

            if (
                $_SESSION["cart"][$product_id]["quantity"]
                < $product["stock"]
            ) {
                $_SESSION["cart"][$product_id]["quantity"]++;
            }
        }

        mysqli_stmt_close($stmt);
    }

    header("Location: cart.php");
    exit;
}


// Decrease quantity
if (isset($_GET["decrease"]) && is_numeric($_GET["decrease"])) {

    $product_id = (int) $_GET["decrease"];

    if (isset($_SESSION["cart"][$product_id])) {

        $_SESSION["cart"][$product_id]["quantity"]--;

        if ($_SESSION["cart"][$product_id]["quantity"] <= 0) {

            unset($_SESSION["cart"][$product_id]);
        }
    }

    header("Location: cart.php");
    exit;
}


// Remove product
if (isset($_GET["remove"]) && is_numeric($_GET["remove"])) {

    $product_id = (int) $_GET["remove"];

    if (isset($_SESSION["cart"][$product_id])) {

        unset($_SESSION["cart"][$product_id]);
    }

    header("Location: cart.php");
    exit;
}


// Calculate total
$subtotal = 0;
$total_items = 0;

foreach ($_SESSION["cart"] as $item) {

    $subtotal +=
        $item["price"] * $item["quantity"];

    $total_items += $item["quantity"];
}


$page_title = "Shopping Cart - Kharido";

include "includes/header.php";

?>


<main class="cart-page">

    <div class="cart-container">

        <h1>
            🛒 Shopping Cart
        </h1>


        <?php if (empty($_SESSION["cart"])) { ?>

            <div class="empty-cart">

                <div class="empty-cart-icon">
                    🛒
                </div>

                <h2>
                    Your cart is empty
                </h2>

                <p>
                    Add some products to your cart.
                </p>

                <a
                    href="index.php"
                    class="continue-shopping-button"
                >
                    Continue Shopping
                </a>

            </div>


        <?php } else { ?>


            <div class="cart-content">


                <!-- Cart Products -->

                <div class="cart-products">

                    <?php foreach ($_SESSION["cart"] as $item) { ?>

                        <div class="cart-item">


                            <div class="cart-item-image">

                                <?php if (!empty($item["image"])) { ?>

                                    <img
                                        src="images/products/<?php echo htmlspecialchars($item["image"]); ?>"
                                        alt="<?php echo htmlspecialchars($item["name"]); ?>"
                                    >

                                <?php } else { ?>

                                    <span>
                                        🛍️
                                    </span>

                                <?php } ?>

                            </div>


                            <div class="cart-item-info">

                                <h3>
                                    <?php echo htmlspecialchars($item["name"]); ?>
                                </h3>

                                <p class="cart-item-price">
                                    ₹<?php echo number_format($item["price"], 2); ?>
                                </p>


                                <div class="quantity-controls">

                                    <a
                                        href="cart.php?decrease=<?php echo $item["id"]; ?>"
                                        class="quantity-button"
                                    >
                                        −
                                    </a>

                                    <span>
                                        <?php echo $item["quantity"]; ?>
                                    </span>

                                    <a
                                        href="cart.php?increase=<?php echo $item["id"]; ?>"
                                        class="quantity-button"
                                    >
                                        +
                                    </a>

                                </div>


                                <a
                                    href="cart.php?remove=<?php echo $item["id"]; ?>"
                                    class="remove-cart-item"
                                >
                                    Remove
                                </a>

                            </div>


                            <div class="cart-item-total">

                                ₹<?php
                                echo number_format(
                                    $item["price"] * $item["quantity"],
                                    2
                                );
                                ?>

                            </div>

                        </div>

                    <?php } ?>

                </div>


                <!-- Cart Summary -->

                <div class="cart-summary">

                    <h2>
                        Order Summary
                    </h2>


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
                            ₹<?php echo number_format($subtotal, 2); ?>
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


                    <hr>


                    <div class="summary-total">

                        <span>
                            Total
                        </span>

                        <span>
                            ₹<?php echo number_format($subtotal, 2); ?>
                        </span>

                    </div>


                    <a
                        href="checkout.php"
                        class="checkout-button"
                    >
                        Proceed to Checkout
                    </a>


                    <a
                        href="index.php"
                        class="continue-shopping"
                    >
                        ← Continue Shopping
                    </a>

                </div>

            </div>

        <?php } ?>

    </div>

</main>


<?php

include "includes/footer.php";

?>