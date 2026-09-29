<?php

session_start();

include "../config/database.php";

if (!isset($_SESSION["user_id"]) || $_SESSION["user_role"] !== "admin") {
    header("Location: ../login.php");
    exit;
}


$error = "";


/* Categories */

$category_result = mysqli_query(
    $conn,
    "SELECT id, name FROM categories ORDER BY name"
);


/* Form submit */

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $name = trim($_POST["name"] ?? "");
    $description = trim($_POST["description"] ?? "");
    $price = (float) ($_POST["price"] ?? 0);
    $old_price = !empty($_POST["old_price"])
        ? (float) $_POST["old_price"]
        : null;

    $category_id = (int) ($_POST["category_id"] ?? 0);
    $stock = (int) ($_POST["stock"] ?? 0);

    $image_name = "";


    if ($name === "" || $price <= 0 || $category_id <= 0) {

        $error = "Please fill all required fields.";

    } else {

        /* Image upload */

        if (
            isset($_FILES["image"]) &&
            $_FILES["image"]["error"] === UPLOAD_ERR_OK
        ) {

            $allowed_types = [
                "image/jpeg",
                "image/png",
                "image/webp"
            ];

            $file_type = $_FILES["image"]["type"];

            if (!in_array($file_type, $allowed_types)) {

                $error = "Only JPG, PNG and WEBP images are allowed.";

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

                if (!move_uploaded_file(
                    $_FILES["image"]["tmp_name"],
                    $upload_path
                )) {

                    $error = "Image upload failed.";
                }
            }
        }


        if ($error === "") {

            $stmt = mysqli_prepare(
                $conn,
                "INSERT INTO products
                (name, description, price, old_price, image, category_id, stock)
                VALUES (?, ?, ?, ?, ?, ?, ?)"
            );

            mysqli_stmt_bind_param(
                $stmt,
                "ssddssi",
                $name,
                $description,
                $price,
                $old_price,
                $image_name,
                $category_id,
                $stock
            );

            if (mysqli_stmt_execute($stmt)) {

                mysqli_stmt_close($stmt);

                header("Location: products.php");
                exit;

            } else {

                $error = "Product could not be added.";

            }

            mysqli_stmt_close($stmt);
        }
    }
}


$page_title = "Add Product - Kharido";

include "admin_header.php";

?>

<main class="admin-page">

    <div class="admin-container">

        <div class="admin-page-header">

            <div>

                <h1>Add Product</h1>

                <p>
                    Add a new product to Kharido.
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
                    ></textarea>

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
                            min="0"
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
                            min="0"
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

                            <option value="">
                                Select Category
                            </option>

                            <?php while ($category = mysqli_fetch_assoc($category_result)) { ?>

                                <option
                                    value="<?php echo $category["id"]; ?>"
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
                            value="0"
                            required
                        >

                    </div>

                </div>


                <div class="admin-form-group">

                    <label>
                        Product Image
                    </label>

                    <input
                        type="file"
                        name="image"
                        accept=".jpg,.jpeg,.png,.webp"
                    >

                    <small>
                        JPG, PNG or WEBP
                    </small>

                </div>


                <div class="admin-form-buttons">

                    <button
                        type="submit"
                        class="admin-primary-button"
                    >
                        Add Product
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