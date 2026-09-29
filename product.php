<?php

session_start();

include "config/database.php";

// ===============================
// PRODUCT ID CHECK
// ===============================

if (!isset($_GET["id"]) || !is_numeric($_GET["id"])) {

    header("Location: index.php");
    exit;
}

$product_id = (int) $_GET["id"];


// ===============================
// FETCH PRODUCT
// ===============================

$stmt = mysqli_prepare(
    $conn,
    "SELECT
        products.*,
        categories.name AS category_name
     FROM products
     LEFT JOIN categories
        ON products.category_id = categories.id
     WHERE products.id = ?"
);

mysqli_stmt_bind_param(
    $stmt,
    "i",
    $product_id
);

mysqli_stmt_execute($stmt);

$result = mysqli_stmt_get_result($stmt);


// ===============================
// PRODUCT NOT FOUND
// ===============================

if (mysqli_num_rows($result) == 0) {

    header("Location: index.php");
    exit;
}

$product = mysqli_fetch_assoc($result);

mysqli_stmt_close($stmt);


// ========================================
// FETCH REVIEW SUMMARY
// ========================================

$review_summary_query = mysqli_query(
    $conn,
    "SELECT
        COUNT(*) AS total_reviews,
        AVG(rating) AS average_rating
     FROM reviews
     WHERE product_id = $product_id"
);

$review_summary = mysqli_fetch_assoc($review_summary_query);

$total_reviews = (int) ($review_summary["total_reviews"] ?? 0);

$average_rating = $review_summary["average_rating"] !== null
    ? (float) $review_summary["average_rating"]
    : 0;


// ========================================
// FETCH ALL REVIEWS
// ========================================

$reviews_query = mysqli_query(
    $conn,
    "SELECT
        reviews.id,
        reviews.rating,
        reviews.review,
        reviews.created_at,
        users.name AS user_name
     FROM reviews
     INNER JOIN users
        ON reviews.user_id = users.id
     WHERE reviews.product_id = $product_id
     ORDER BY reviews.created_at DESC"
);


// ========================================
// REVIEW ELIGIBILITY
// ========================================

$can_review = false;
$already_reviewed = false;

if (isset($_SESSION["user_id"])) {

    $logged_in_user_id = (int) $_SESSION["user_id"];


    // ========================================
    // CHECK DELIVERED ORDER
    // ========================================

    $eligibility_stmt = mysqli_prepare(
        $conn,
        "SELECT oi.id
         FROM order_items oi
         INNER JOIN orders o
            ON oi.order_id = o.id
         WHERE o.user_id = ?
         AND oi.product_id = ?
         AND o.status = 'Delivered'
         LIMIT 1"
    );

    mysqli_stmt_bind_param(
        $eligibility_stmt,
        "ii",
        $logged_in_user_id,
        $product_id
    );

    mysqli_stmt_execute($eligibility_stmt);

    $eligibility_result = mysqli_stmt_get_result(
        $eligibility_stmt
    );

    if (mysqli_num_rows($eligibility_result) > 0) {

        $can_review = true;
    }

    mysqli_stmt_close($eligibility_stmt);


    // ========================================
    // CHECK ALREADY REVIEWED
    // ========================================

    $existing_review_stmt = mysqli_prepare(
        $conn,
        "SELECT id
         FROM reviews
         WHERE user_id = ?
         AND product_id = ?
         LIMIT 1"
    );

    mysqli_stmt_bind_param(
        $existing_review_stmt,
        "ii",
        $logged_in_user_id,
        $product_id
    );

    mysqli_stmt_execute($existing_review_stmt);

    $existing_review_result = mysqli_stmt_get_result(
        $existing_review_stmt
    );

    if (mysqli_num_rows($existing_review_result) > 0) {

        $already_reviewed = true;
    }

    mysqli_stmt_close($existing_review_stmt);
}


// ===============================
// PAGE TITLE
// ===============================

$page_title = $product["name"] . " - Kharido";


// ========================================
// REVIEW MESSAGE
// ========================================

$review_message = "";
$review_message_type = "";

if (isset($_GET["review"])) {

    if ($_GET["review"] === "success") {

        $review_message =
            "⭐ Your review has been submitted successfully!";

        $review_message_type = "success";

    } elseif ($_GET["review"] === "already") {

        $review_message =
            "⚠️ You have already reviewed this product.";

        $review_message_type = "warning";

    } elseif ($_GET["review"] === "not_eligible") {

        $review_message =
            "📦 You can review this product after it is delivered.";

        $review_message_type = "warning";
    }
}


include "includes/header.php";

?>


<main class="product-details-page">

    <?php if ($review_message !== "") { ?>

        <div class="review-alert <?php echo $review_message_type; ?>">

            <?php echo $review_message; ?>

        </div>

    <?php } ?>


    <div class="product-details-container">

        <!-- ===============================
             PRODUCT IMAGE
        ================================ -->

        <div class="product-details-image">

            <?php if (!empty($product["image"])) { ?>

                <img
                    src="images/products/<?php echo htmlspecialchars(
                        $product["image"]
                    ); ?>"
                    alt="<?php echo htmlspecialchars(
                        $product["name"]
                    ); ?>"
                >

            <?php } else { ?>

                <div class="product-image-placeholder">
                    🛍️
                </div>

            <?php } ?>

        </div>


        <!-- ===============================
             PRODUCT INFORMATION
        ================================ -->

        <div class="product-details-info">

            <!-- CATEGORY -->

            <div class="product-category">

                <?php

                echo htmlspecialchars(
                    $product["category_name"] ?? "Product"
                );

                ?>

            </div>


            <!-- PRODUCT NAME -->

            <h1>

                <?php

                echo htmlspecialchars(
                    $product["name"]
                );

                ?>

            </h1>


            <!-- DESCRIPTION -->

            <p class="product-details-description">

                <?php

                echo htmlspecialchars(
                    $product["description"] ?? ""
                );

                ?>

            </p>


            <!-- PRICE -->

            <div class="product-details-price">

                ₹<?php

                echo number_format(
                    $product["price"],
                    2
                );

                ?>

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

            <?php if ($product["stock"] > 0) { ?>

                <p class="stock available">

                    ✓ In Stock

                    (<?php echo $product["stock"]; ?> available)

                </p>

            <?php } else { ?>

                <p class="stock unavailable">

                    ✕ Out of Stock

                </p>

            <?php } ?>


            <!-- QUANTITY -->

            <?php if ($product["stock"] > 0) { ?>

                <div class="quantity-box">

                    <label>
                        Quantity
                    </label>

                    <input
                        type="number"
                        id="productQuantity"
                        value="1"
                        min="1"
                        max="<?php echo $product["stock"]; ?>"
                    >

                </div>


                <!-- ADD TO CART -->

                <button
                    class="details-cart-button"
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
                    class="details-cart-button disabled"
                    disabled
                >

                    Out of Stock

                </button>

            <?php } ?>


            <!-- BACK -->

            <a
                href="index.php"
                class="back-products"
            >

                ← Continue Shopping

            </a>

        </div>

    </div>


    <!-- ========================================
         REVIEWS & RATINGS
    ========================================= -->

    <section class="reviews-section">

        <div class="reviews-container">


            <!-- ===============================
                 REVIEW TITLE
            ================================ -->

            <div class="reviews-title">

                <h2>
                    ⭐ Customer Reviews
                </h2>

                <p>
                    Share your experience with this product.
                </p>

            </div>


            <!-- ========================================
                 RATING SUMMARY
            ========================================= -->

            <div class="review-summary-box">

                <div class="average-rating">

                    <div class="average-rating-number">

                        <?php echo number_format(
                            $average_rating,
                            1
                        ); ?>

                        ⭐

                    </div>


                    <div class="average-rating-stars">

                        <?php

                        $rounded_rating = round(
                            $average_rating
                        );

                        for ($i = 1; $i <= 5; $i++) {

                            if ($i <= $rounded_rating) {

                                echo "⭐";

                            } else {

                                echo "☆";
                            }
                        }

                        ?>

                    </div>


                    <p>

                        Based on

                        <strong>
                            <?php echo $total_reviews; ?>
                        </strong>

                        review<?php echo $total_reviews == 1 ? "" : "s"; ?>

                    </p>

                </div>

            </div>


            <!-- ========================================
                 EXISTING REVIEWS
            ========================================= -->

            <div class="reviews-list">

                <h3>
                    Customer Feedback
                </h3>


                <?php if ($total_reviews > 0) { ?>


                    <?php while ($review = mysqli_fetch_assoc($reviews_query)) { ?>

                        <div class="review-item">

                            <div class="review-header">

                                <div class="review-user">

                                    👤

                                    <?php

                                    echo htmlspecialchars(
                                        $review["user_name"]
                                    );

                                    ?>

                                </div>


                                <div class="review-date">

                                    <?php

                                    echo date(
                                        "d M Y",
                                        strtotime(
                                            $review["created_at"]
                                        )
                                    );

                                    ?>

                                </div>

                            </div>


                            <!-- RATING -->

                            <div class="review-rating">

                                <?php

                                $user_rating =
                                    (int) $review["rating"];

                                for ($i = 1; $i <= 5; $i++) {

                                    if ($i <= $user_rating) {

                                        echo "⭐";

                                    } else {

                                        echo "☆";
                                    }
                                }

                                ?>

                            </div>


                            <!-- REVIEW TEXT -->

                            <p class="review-content">

                                <?php

                                echo nl2br(
                                    htmlspecialchars(
                                        $review["review"]
                                    )
                                );

                                ?>

                            </p>

                        </div>

                    <?php } ?>


                <?php } else { ?>


                    <div class="no-reviews">

                        <div class="no-reviews-icon">
                            ⭐
                        </div>

                        <h3>
                            No Reviews Yet
                        </h3>

                        <p>
                            Be the first person to review this product.
                        </p>

                    </div>


                <?php } ?>

            </div>


            <!-- ========================================
                 REVIEW FORM / REVIEW ELIGIBILITY
            ========================================= -->

            <?php if (
                isset($_SESSION["user_id"]) &&
                $can_review &&
                !$already_reviewed
            ) { ?>


                <!-- USER CAN REVIEW -->

                <div class="review-form-box">

                    <h3>
                        Write a Review
                    </h3>


                    <form
                        action="submit_review.php"
                        method="POST"
                    >

                        <!-- PRODUCT ID -->

                        <input
                            type="hidden"
                            name="product_id"
                            value="<?php echo $product_id; ?>"
                        >


                        <!-- RATING -->

                        <div class="rating-input">

                            <label>
                                Your Rating
                            </label>


                            <div class="star-options">

                                <label>

                                    <input
                                        type="radio"
                                        name="rating"
                                        value="1"
                                        required
                                    >

                                    ⭐

                                </label>


                                <label>

                                    <input
                                        type="radio"
                                        name="rating"
                                        value="2"
                                    >

                                    ⭐⭐

                                </label>


                                <label>

                                    <input
                                        type="radio"
                                        name="rating"
                                        value="3"
                                    >

                                    ⭐⭐⭐

                                </label>


                                <label>

                                    <input
                                        type="radio"
                                        name="rating"
                                        value="4"
                                    >

                                    ⭐⭐⭐⭐

                                </label>


                                <label>

                                    <input
                                        type="radio"
                                        name="rating"
                                        value="5"
                                    >

                                    ⭐⭐⭐⭐⭐

                                </label>

                            </div>

                        </div>


                        <!-- REVIEW TEXT -->

                        <div class="review-text-box">

                            <label for="review">
                                Your Review
                            </label>


                            <textarea
                                id="review"
                                name="review"
                                rows="5"
                                placeholder="Write your review here..."
                                required
                            ></textarea>

                        </div>


                        <!-- SUBMIT -->

                        <button
                            type="submit"
                            class="submit-review-button"
                        >

                            ⭐ Submit Review

                        </button>

                    </form>

                </div>


            <?php } elseif (!isset($_SESSION["user_id"])) { ?>


                <!-- NOT LOGGED IN -->

                <div class="review-login-box">

                    <p>
                        Please login to write a review.
                    </p>


                    <a
                        href="login.php"
                        class="hero-btn"
                    >

                        Login to Review

                    </a>

                </div>


            <?php } elseif ($already_reviewed) { ?>


                <!-- ALREADY REVIEWED -->

                <div class="review-login-box">

                    <p>
                        ⭐ You have already reviewed this product.
                    </p>

                </div>


            <?php } elseif (!$can_review) { ?>


                <!-- NOT ELIGIBLE -->

                <div class="review-login-box">

                    <p>
                        📦 You can review this product after your order is delivered.
                    </p>

                </div>


            <?php } ?>


        </div>

    </section>

</main>


<!-- ===============================
     ADD TO CART SCRIPT
=============================== -->

<script>

function addProductToCart(productId, productName) {

    const quantityInput =
        document.getElementById("productQuantity");

    const quantity =
        quantityInput
            ? quantityInput.value
            : 1;

    window.location.href =
        "cart.php?add=" +
        productId +
        "&quantity=" +
        quantity;
}

</script>


<?php

include "includes/footer.php";

?>