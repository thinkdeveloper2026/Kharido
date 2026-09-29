<?php

include "config/database.php";

$page_title = "Kharido - Online Shopping";

include "includes/header.php";


// ===============================
// FETCH CATEGORIES
// ===============================

$category_query = "SELECT * FROM categories ORDER BY name ASC";
$category_result = mysqli_query($conn, $category_query);


// ===============================
// FETCH PRODUCTS
// ===============================

$product_query = "
    SELECT
        products.*,
        categories.name AS category_name
    FROM products
    LEFT JOIN categories
        ON products.category_id = categories.id
    ORDER BY products.id DESC
";

$product_result = mysqli_query($conn, $product_query);


// ===============================
// GET USER WISHLIST
// ===============================

$wishlist_products = [];

if (isset($_SESSION["user_id"])) {

    $wishlist_user_id = (int) $_SESSION["user_id"];

    $wishlist_query = "
        SELECT product_id
        FROM wishlist
        WHERE user_id = $wishlist_user_id
    ";

    $wishlist_result = mysqli_query($conn, $wishlist_query);

    if ($wishlist_result) {

        while ($wishlist = mysqli_fetch_assoc($wishlist_result)) {

            $wishlist_products[] = (int) $wishlist["product_id"];

        }
    }
}

?>

<!-- ===============================
     HERO SECTION
================================ -->

<section class="hero">

    <div class="hero-content">

        <h1>Welcome to Kharido</h1>

        <p>
            Shop electronics, fashion, gaming products
            and more at amazing prices.
        </p>

        <a href="#products" class="hero-btn">
            Shop Now
        </a>

    </div>


    <div class="hero-image">

        <div class="hero-circle">
            🛍️
        </div>

    </div>

</section>


<!-- ===============================
     CATEGORIES SECTION
================================ -->

<section class="section" id="categories">

    <div class="section-title">

        <h2>Shop by Category</h2>

        <p>
            Choose your favorite category
        </p>

    </div>


    <div class="categories-grid">


        <!-- ALL PRODUCTS -->

        <a
            href="#products"
            class="category-card"
            onclick="showAllProducts(); return false;"
        >

            <div class="category-icon">
                🛍️
            </div>

            <h3>
                All Products
            </h3>

            <span>
                View All
            </span>

        </a>


        <!-- DATABASE CATEGORIES -->

        <?php while ($category = mysqli_fetch_assoc($category_result)) { ?>

            <a
                href="#products"
                class="category-card"
                data-category="<?php echo (int)$category["id"]; ?>"
                onclick="filterCategory(this.getAttribute('data-category')); return false;"
            >

                <div class="category-icon">

                    <?php

                    switch (strtolower($category["name"])) {

                        case "mobiles":
                            echo "📱";
                            break;

                        case "electronics":
                            echo "💻";
                            break;

                        case "fashion":
                            echo "👕";
                            break;

                        case "home":
                            echo "🏠";
                            break;

                        case "gaming":
                            echo "🎮";
                            break;

                        case "beauty":
                            echo "💄";
                            break;

                        default:
                            echo "🛒";
                    }

                    ?>

                </div>


                <h3>

                    <?php
                    echo htmlspecialchars($category["name"]);
                    ?>

                </h3>


                <span>
                    Shop Now
                </span>

            </a>

        <?php } ?>

    </div>

</section>


<!-- ===============================
     PRODUCTS SECTION
================================ -->

<section class="section" id="products">

    <div class="section-title">

        <h2>
            Latest Products
        </h2>

        <p>
            Discover our latest products
        </p>

    </div>


    <!-- ===============================
         PRICE FILTER
    ================================ -->

    <div class="product-controls">

        <select
            id="priceFilter"
            onchange="applyFilters()"
        >

            <option value="all">
                All Prices
            </option>

            <option value="0-500">
                ₹0 - ₹500
            </option>

            <option value="500-1000">
                ₹500 - ₹1000
            </option>

            <option value="1000-2000">
                ₹1000 - ₹2000
            </option>

            <option value="2000+">
                ₹2000+
            </option>

        </select>


        <!-- ===============================
             SORTING
        ================================ -->

        <select
            id="sortProducts"
            onchange="sortProducts()"
        >

            <option value="default">
                Sort By
            </option>

            <option value="low-high">
                Price: Low to High
            </option>

            <option value="high-low">
                Price: High to Low
            </option>

            <option value="name-az">
                Name: A to Z
            </option>

            <option value="name-za">
                Name: Z to A
            </option>

        </select>

    </div>


    <!-- ===============================
         PRODUCTS GRID
    ================================ -->

    <div class="products-grid">

        <?php if (mysqli_num_rows($product_result) > 0) { ?>


            <?php while ($product = mysqli_fetch_assoc($product_result)) { ?>


                <!-- PRODUCT CARD -->

                <div
                    class="product-card"

                    data-name="<?php echo htmlspecialchars(
                        $product["name"],
                        ENT_QUOTES
                    ); ?>"

                    data-category="<?php echo (int)$product["category_id"]; ?>"

                    data-price="<?php echo (float)$product["price"]; ?>"
                >


                    <!-- PRODUCT IMAGE -->

                    <div class="product-image">

                        <?php if (!empty($product["image"])) { ?>

                            <a
                                href="product.php?id=<?php echo (int)$product["id"]; ?>"
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


                        <!-- PRODUCT CATEGORY -->

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


                        <!-- PRODUCT DESCRIPTION -->

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


                        <!-- PRODUCT BOTTOM -->

                        <div class="product-bottom">


                            <!-- STOCK -->

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


                            <!-- ADD TO CART -->

                            <?php if ($product["stock"] > 0) { ?>

                                <button
                                    type="button"
                                    class="add-cart"

                                    onclick="addProductToCart(
                                        <?php echo (int)$product["id"]; ?>,
                                        '<?php echo htmlspecialchars(
                                            $product["name"],
                                            ENT_QUOTES
                                        ); ?>'
                                    )"
                                >

                                    🛒 Add to Cart

                                </button>

                            <?php } else { ?>

                                <button
                                    type="button"
                                    class="add-cart"
                                    disabled
                                >

                                    Out of Stock

                                </button>

                            <?php } ?>


                        </div>


                        <!-- ===============================
                             WISHLIST
                        ================================ -->

                        <?php if (isset($_SESSION["user_id"])) { ?>

                            <?php

                            $is_wishlisted =
                                in_array(
                                    (int)$product["id"],
                                    $wishlist_products
                                );

                            ?>


                            <?php if ($is_wishlisted) { ?>

                                <a
                                    href="wishlist_action.php?product_id=<?php echo (int)$product["id"]; ?>"
                                    class="wishlist-button wishlisted"
                                    title="Remove from Wishlist"
                                >
                                    ❤️ Saved
                                </a>

                            <?php } else { ?>

                                <a
                                    href="wishlist_action.php?product_id=<?php echo (int)$product["id"]; ?>"
                                    class="wishlist-button"
                                    title="Add to Wishlist"
                                >
                                    ♡ Wishlist
                                </a>

                            <?php } ?>


                        <?php } else { ?>

                            <a
                                href="login.php"
                                class="wishlist-button"
                                title="Login to add Wishlist"
                            >
                                ♡ Wishlist
                            </a>

                        <?php } ?>


                        <!-- VIEW DETAILS -->

                        <a
                            href="product.php?id=<?php echo (int)$product["id"]; ?>"
                            class="view-details-button"
                        >

                            View Details →

                        </a>


                    </div>

                </div>


            <?php } ?>


        <?php } else { ?>


            <!-- NO PRODUCTS -->

            <div class="no-products">

                <h3>
                    No Products Found
                </h3>

                <p>
                    Products will appear here soon.
                </p>

            </div>


        <?php } ?>

    </div>


    <!-- ===============================
         PAGINATION
    ================================ -->

    <div id="pagination" class="pagination"></div>

</section>


<!-- ===============================
     ADD TO CART SCRIPT
================================ -->

<script>

function addProductToCart(productId, productName) {

    window.location.href =
        "cart.php?add=" + productId + "&quantity=1";

}

</script>


<?php

include "includes/footer.php";

?>