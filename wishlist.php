<?php

session_start();

include "config/database.php";

$page_title = "My Wishlist - Kharido";

include "includes/header.php";


// ===============================
// LOGIN CHECK
// ===============================

if (!isset($_SESSION["user_id"])) {

    header("Location: login.php");
    exit;

}


$user_id = (int) $_SESSION["user_id"];


// ===============================
// FETCH WISHLIST PRODUCTS
// ===============================

$wishlist_query = "
    SELECT
        wishlist.id AS wishlist_id,
        products.id AS product_id,
        products.name,
        products.description,
        products.price,
        products.old_price,
        products.image,
        products.stock,
        categories.name AS category_name

    FROM wishlist

    INNER JOIN products
        ON wishlist.product_id = products.id

    LEFT JOIN categories
        ON products.category_id = categories.id

    WHERE wishlist.user_id = $user_id

    ORDER BY wishlist.created_at DESC
";


$wishlist_result = mysqli_query(
    $conn,
    $wishlist_query
);

?>

<!-- ===============================
     WISHLIST SECTION
================================ -->

<section class="section wishlist-section">

    <div class="section-title">

        <h2>❤️ My Wishlist</h2>

        <p>
            Products you saved for later
        </p>

    </div>


    <?php if ($wishlist_result && mysqli_num_rows($wishlist_result) > 0) { ?>


        <!-- ===============================
             WISHLIST GRID
        ================================ -->

        <div class="products-grid">


            <?php while ($product = mysqli_fetch_assoc($wishlist_result)) { ?>


                <!-- PRODUCT CARD -->

                <div class="product-card">


                    <!-- PRODUCT IMAGE -->

                    <div class="product-image">

                        <?php if (!empty($product["image"])) { ?>

                            <a
                                href="product.php?id=<?php echo (int)$product["product_id"]; ?>"
                            >

                                <img
                                    src="images/products/<?php echo htmlspecialchars(
                                        $product["image"],
                                        ENT_QUOTES
                                    ); ?>"

                                    alt="<?php echo htmlspecialchars(
                                        $product["name"]
                                    ); ?>"
                                >

                            </a>

                        <?php } else { ?>

                            <div class="product-placeholder">
                                🛍️
                            </div>

                        <?php } ?>

                    </div>


                    <!-- PRODUCT INFO -->

                    <div class="product-info">


                        <!-- CATEGORY -->

                        <div class="product-category">

                            <?php

                            echo htmlspecialchars(
                                $product["category_name"] ?? "Product"
                            );

                            ?>

                        </div>


                        <!-- PRODUCT NAME -->

                        <h3>

                            <?php

                            echo htmlspecialchars(
                                $product["name"]
                            );

                            ?>

                        </h3>


                        <!-- DESCRIPTION -->

                        <p class="product-description">

                            <?php

                            $description =
                                $product["description"] ?? "";

                            if (strlen($description) > 80) {

                                echo htmlspecialchars(
                                    substr($description, 0, 80)
                                ) . "...";

                            } else {

                                echo htmlspecialchars(
                                    $description
                                );

                            }

                            ?>

                        </p>


                        <!-- PRICE -->

                        <div class="price-row">

                            <span class="price">

                                ₹<?php

                                echo number_format(
                                    $product["price"],
                                    2
                                );

                                ?>

                            </span>


                            <?php if (!empty($product["old_price"])) { ?>

                                <span class="old-price">

                                    ₹<?php

                                    echo number_format(
                                        $product["old_price"],
                                        2
                                    );

                                    ?>

                                </span>

                            <?php } ?>

                        </div>


                        <!-- STOCK -->

                        <div class="wishlist-stock">

                            <?php if ($product["stock"] > 0) { ?>

                                <span class="stock">
                                    ✓ In Stock
                                </span>

                            <?php } else { ?>

                                <span
                                    class="stock"
                                    style="color: #dc3545;"
                                >
                                    ✕ Out of Stock
                                </span>

                            <?php } ?>

                        </div>


                        <!-- ===============================
                             ACTIONS
                        ================================ -->

                        <div class="wishlist-actions">


                            <!-- ADD TO CART -->

                            <?php if ($product["stock"] > 0) { ?>

                                <a
                                    href="cart.php?add=<?php echo (int)$product["product_id"]; ?>&quantity=1"
                                    class="add-cart"
                                >
                                    🛒 Add to Cart
                                </a>

                            <?php } else { ?>

                                <button
                                    type="button"
                                    class="add-cart"
                                    disabled
                                >
                                    Out of Stock
                                </button>

                            <?php } ?>


                            <!-- REMOVE -->

                            <a
                                href="wishlist_action.php?product_id=<?php echo (int)$product["product_id"]; ?>"
                                class="wishlist-button wishlisted"
                                onclick="return confirm('Remove this product from your wishlist?');"
                            >
                                ❤️ Remove
                            </a>


                        </div>


                        <!-- VIEW DETAILS -->

                        <a
                            href="product.php?id=<?php echo (int)$product["product_id"]; ?>"
                            class="view-details-button"
                        >
                            View Details →
                        </a>


                    </div>

                </div>


            <?php } ?>


        </div>


    <?php } else { ?>


        <!-- ===============================
             EMPTY WISHLIST
        ================================ -->

        <div class="no-products wishlist-empty">

            <div class="wishlist-empty-icon">
                ❤️
            </div>

            <h3>
                Your Wishlist is Empty
            </h3>

            <p>
                You haven't saved any products yet.
            </p>

            <a
                href="index.php#products"
                class="hero-btn"
            >
                Continue Shopping
            </a>

        </div>


    <?php } ?>


</section>


<?php

include "includes/footer.php";

?>