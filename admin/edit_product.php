<?php

session_start();

include "../config/database.php";

if (!isset($_SESSION["user_id"]) || $_SESSION["user_role"] !== "admin") {
    header("Location: ../login.php");
    exit;
}


if (!isset($_GET["id"]) || !is_numeric($_GET["id"])) {
    header("Location: products.php");
    exit;
}

$product_id = (int) $_GET["id"];

$error = "";


/* Get product */

$stmt = mysqli_prepare(
    $conn,
    "SELECT *
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

if (mysqli_num_rows($result) === 0) {

    mysqli_stmt_close($stmt);

    header("Location: products.php");
    exit;
}

$product = mysqli_fetch_assoc($result);

mysqli_stmt_close($stmt);


/* Categories */

$category_result = mysqli_query(
    $conn,
    "SELECT id, name FROM categories ORDER BY name"
);


/* Update */

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $name = trim($_POST["name"] ?? "");
    $description = trim($_POST["description"] ?? "");
    $price = (float) ($_POST["price"] ?? 0);

    $old_price = !empty($_POST["old_price"])
        ? (float) $_POST["old_price"]
        : null;

    $category_id = (int) ($_POST["category_id"] ?? 0);
    $stock = (int) ($_POST["stock"] ?? 0);

    $image_name = $product["image"];


    if ($name === "" || $price <= 0 || $category_id <= 0) {

        $error = "Please fill all required fields.";

    } else {

        /* New image */

        if (
            isset($_FILES["image"]) &&
            $_FILES["image"]["error"] === UPLOAD_ERR_OK
        ) {

            $allowed_types = [
                "image/jpeg",
                "image/png",
                "image/webp"
            ];

            if (
                !in_array(
                    $_FILES["image"]["type"],
                    $allowed_types
                )
            ) {

                $error =
                    "Only JPG, PNG and WEBP images are allowed.";

            } else {

                $extension = pathinfo(
                    $_FILES["image"]["name"],
                    PATHINFO_EXTENSION
                );

                $image_name =
                    time() . "_" .
                    uniqid() . "." .
                    strtolower($extension);

                $upload_path =
                    "../images/products/" .
                    $image_name;

                if (
                    move_uploaded_file(
                        $_FILES["image"]["tmp_name"],
                        $upload_path
                    )
                ) {

                    if (
                        !empty($product["image"]) &&
                        file_exists(
                            "../images/products/" .
                            $product["image"]
                        )
                    ) {

                        unlink(
                            "../images/products/" .
                            $product["image"]
                        );
                    }

                } else {

                    $error = "Image upload failed.";
                }
            }
        }


        if ($error === "") {

            $update_stmt = mysqli_prepare(
                $conn,
                "UPDATE products
                 SET
                    name = ?,
                    description = ?,
                    price = ?,
                    old_price = ?,
                    image = ?,
                    category_id = ?,
                    stock = ?
                 WHERE id = ?"
            );

            mysqli_stmt_bind_param(
                $update_stmt,
                "ssddssii",
                $name,
                $description,
                $price,
                $old_price,
                $image_name,
                $category_id,
                $stock,
                $product_id
            );

            if (mysqli_stmt_execute($update_stmt)) {

                mysqli_stmt_close($update_stmt);

                header("Location: products.php");
                exit;

            } else {

                $error = "Product could not be updated.";
            }

            mysqli_stmt_close($update_stmt);
        }
    }
}


$page_title = "Edit Product - Kharido";

include "admin_header.php";

?>

<main class="admin-page">

    <div class="admin-container">

        <div class="admin-page-header">

            <div>

                <h1>Edit Product</h1>

                <p>
                    Update product information.
                </p>

            </div>

        </div>


        <?php if ($error !== "") { ?>

            <div class="admin-error">
                <?php echo htmlspecialchars($error); ?>
            </div>

        <?php } ?>


        <div class="admin-form-card">

            <form
                method="POST"
                enctype="multipart/form-data"
            >

                <div class="admin-form-group">

                    <label>
                        Product Name *
                    </label>

                    <input
                        type="text"
                        name="name"
                        value="<?php echo htmlspecialchars($product["name"]); ?>"
                        required
                    >

                </div>


                <div class="admin-form-group">

                    <label>
                        Description
                    </label>

                    <textarea
                        name="description"
                        rows="5"
                    ><?php echo htmlspecialchars($product["description"]); ?></textarea>

                </div>


                <div class="admin-form-grid">

                    <div class="admin-form-group">

                        <label>
                            Price *
                        </label>

                        <input
                            type="number"
                            name="price"
                            step="0.01"
                            value="<?php echo $product["price"]; ?>"
                            required
                        >

                    </div>


                    <div class="admin-form-group">

                        <label>
                            Old Price
                        </label>

                        <input
                            type="number"
                            name="old_price"
                            step="0.01"
                            value="<?php echo $product["old_price"]; ?>"
                        >

                    </div>

                </div>


                <div class="admin-form-grid">

                    <div class="admin-form-group">

                        <label>
                            Category *
                        </label>

                        <select
                            name="category_id"
                            required
                        >

                            <?php while ($category = mysqli_fetch_assoc($category_result)) { ?>

                                <option
                                    value="<?php echo $category["id"]; ?>"
                                    <?php
                                    echo (
                                        $category["id"]
                                        == $product["category_id"]
                                    )
                                        ? "selected"
                                        : "";
                                    ?>
                                >
                                    <?php
                                    echo htmlspecialchars(
                                        $category["name"]
                                    );
                                    ?>
                                </option>

                            <?php } ?>

                        </select>

                    </div>


                    <div class="admin-form-group">

                        <label>
                            Stock *
                        </label>

                        <input
                            type="number"
                            name="stock"
                            min="0"
                            value="<?php echo $product["stock"]; ?>"
                            required
                        >

                    </div>

                </div>


                <div class="admin-form-group">

                    <label>
                        Current Image
                    </label>

                    <?php if (!empty($product["image"])) { ?>

                        <div class="current-product-image">

                            <img
                                src="../images/products/<?php echo htmlspecialchars($product["image"]); ?>"
                                alt="Current Product"
                            >

                        </div>

                    <?php } ?>

                </div>


                <div class="admin-form-group">

                    <label>
                        Change Image
                    </label>

                    <input
                        type="file"
                        name="image"
                        accept=".jpg,.jpeg,.png,.webp"
                    >

                </div>


                <div class="admin-form-buttons">

                    <button
                        type="submit"
                        class="admin-primary-button"
                    >
                        Update Product
                    </button>

                    <a
                        href="products.php"
                        class="admin-secondary-button"
                    >
                        Cancel
                    </a>

                </div>

            </form>

        </div>

    </div>

</main>

<?php include "admin_footer.php"; ?>