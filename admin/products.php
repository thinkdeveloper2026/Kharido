<?php

session_start();

include "../config/database.php";

if (!isset($_SESSION["user_id"]) || $_SESSION["user_role"] !== "admin") {
    header("Location: ../login.php");
    exit;
}

$result = mysqli_query(
    $conn,
    "SELECT 
        products.*, 
        categories.name AS category_name
     FROM products 
     LEFT JOIN categories 
        ON products.category_id = categories.id
     ORDER BY products.id DESC"
);

if (!$result) {
    die("Product Query Error: " . mysqli_error($conn));
}

$page_title = "Manage Products - Kharido";

include "admin_header.php";

?>

<main class="admin-page">

    <div class="admin-container">

        <div class="admin-page-header">

            <div>
                <h1>Manage Products</h1>

                <p>
                    Add, edit and delete products.
                </p>
            </div>

            <a
                href="add_product.php"
                class="admin-primary-button"
            >
                + Add Product
            </a>

        </div>


        <div class="admin-table-container">

            <table class="admin-table">

                <thead>

                    <tr>

                        <th>ID</th>

                        <th>Image</th>

                        <th>Name</th>

                        <th>Category</th>

                        <th>Price</th>

                        <th>Old Price</th>

                        <th>Stock</th>

                        <th>Actions</th>

                    </tr>

                </thead>

                <tbody>

                    <?php while ($product = mysqli_fetch_assoc($result)) { ?>

                        <tr>

                            <td>
                                <?php echo $product["id"]; ?>
                            </td>


                            <td>

                                <?php if (!empty($product["image"])) { ?>

                                    <img
                                        src="../images/products/<?php echo htmlspecialchars($product["image"]); ?>"
                                        class="admin-product-image"
                                        alt="Product"
                                    >

                                <?php } else { ?>

                                    🛍️

                                <?php } ?>

                            </td>


                            <td>
                                <strong>
                                    <?php
                                    echo htmlspecialchars(
                                        $product["name"]
                                    );
                                    ?>
                                </strong>
                            </td>


                            <td>
                                <?php
                                echo htmlspecialchars(
                                    $product["category_name"] ?? "N/A"
                                );
                                ?>
                            </td>


                            <td>
                                ₹<?php
                                echo number_format(
                                    $product["price"],
                                    2
                                );
                                ?>
                            </td>


                            <td>

                                <?php if (!empty($product["old_price"])) { ?>

                                    ₹<?php
                                    echo number_format(
                                        $product["old_price"],
                                        2
                                    );
                                    ?>

                                <?php } else { ?>

                                    -

                                <?php } ?>

                            </td>


                            <td>

                                <?php if ($product["stock"] > 0) { ?>

                                    <span class="stock-good">
                                        <?php echo $product["stock"]; ?>
                                    </span>

                                <?php } else { ?>

                                    <span class="stock-bad">
                                        Out of Stock
                                    </span>

                                <?php } ?>

                            </td>


                            <td>

                                <div class="admin-table-actions">

                                    <a
                                        href="edit_product.php?id=<?php echo $product["id"]; ?>"
                                        class="edit-button"
                                    >
                                        Edit
                                    </a>


                                    <a
                                        href="delete_product.php?id=<?php echo $product["id"]; ?>"
                                        class="delete-button"
                                        onclick="return confirm('Are you sure you want to delete this product?');"
                                    >
                                        Delete
                                    </a>

                                </div>

                            </td>

                        </tr>

                    <?php } ?>

                </tbody>

            </table>

        </div>

    </div>

</main>

<?php include "admin_footer.php"; ?>