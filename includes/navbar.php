<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

?>

<nav class="navbar">

    <div class="nav-container">

        <a href="index.php" class="logo">
            Kharido
        </a>


        <div class="search-box">

            <input
                type="text"
                id="searchInput"
                placeholder="Search products..."
                autocomplete="off"
                oninput="applyFilters()"
            >

            <button
                type="button"
                onclick="applyFilters()"
            >
        🔍
            </button>

        </div>


        <div class="nav-links">

            <a href="index.php">
                Home
            </a>

            <a href="index.php#categories">
                Categories
            </a>

            <a href="index.php#products">
                Products
            </a>

            <?php if (isset($_SESSION["user_id"])) { ?>
                <a href="wishlist.php" class="wishlist-nav">
                    ❤️ Wishlist
                </a>
            <?php } ?>


            <?php if (isset($_SESSION["user_id"])) { ?>

                <span class="user-name">
                    👤 Hi, <?php echo htmlspecialchars($_SESSION["user_name"]); ?>
                </span>

                <a href="my_orders.php">
                    📦 My Orders
                </a>

                <?php if (isset($_SESSION["user_role"]) && $_SESSION["user_role"] === "admin") { ?>

                    <a href="admin/index.php">
                        Admin Panel
                    </a>

                <?php } ?>

                <a href="logout.php">
                    Logout
                </a>

            <?php } else { ?>

                <a href="login.php">
                    Login
                </a>

                <a href="register.php">
                    Register
                </a>

            <?php } ?>


           <a href="cart.php" class="cart">
                🛒 Cart
                <span id="cartCount">
                    <?php
                    echo isset($_SESSION["cart"])
                        ? array_sum(array_column($_SESSION["cart"], "quantity"))
                        : 0;
                    ?>
                </span>
            </a>

        </div>

    </div>

</nav>