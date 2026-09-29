<?php

session_start();

include "../config/database.php";

if (
    !isset($_SESSION["user_id"]) ||
    !isset($_SESSION["user_role"]) ||
    $_SESSION["user_role"] !== "admin"
) {
    header("Location: ../login.php");
    exit;
}

$result = mysqli_query(
    $conn,
    "SELECT id, name, email, role, created_at
     FROM users
     ORDER BY id DESC"
);

if (!$result) {
    die("User Query Error: " . mysqli_error($conn));
}

$page_title = "Manage Users - Kharido";

include "admin_header.php";

?>

<main class="admin-page">

    <?php if (isset($_SESSION["user_message"])) { ?>

        <div class="user-alert <?php echo $_SESSION["user_message_type"]; ?>">

            <?php
            echo htmlspecialchars($_SESSION["user_message"]);
            ?>

        </div>

            <?php
                unset($_SESSION["user_message"]);
                unset($_SESSION["user_message_type"]);
            ?>

    <?php } ?>

    <div class="admin-container">

        <div class="admin-page-header">

            <div>
                <h1>Manage Users</h1>
                <p>View and manage all registered Kharido users.</p>
            </div>

        </div>

        <div class="admin-table-container">

            <table class="admin-table">

                <thead>

                    <tr>
                        <th>ID</th>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Role</th>
                        <th>Registered</th>
                        <th>Action</th>
                    </tr>

                </thead>

                <tbody>

                    <?php if (mysqli_num_rows($result) > 0) { ?>

                        <?php while ($user = mysqli_fetch_assoc($result)) { ?>

                            <tr>

                                <td>
                                    <?php echo $user["id"]; ?>
                                </td>

                                <td>
                                    <?php
                                    echo htmlspecialchars($user["name"]);
                                    ?>
                                </td>

                                <td>
                                    <?php
                                    echo htmlspecialchars($user["email"]);
                                    ?>
                                </td>

                                <td>

                                    <form
                                        method="POST"
                                        action="update_user_role.php"
                                        class="user-role-form"
                                    >

                                        <input
                                            type="hidden"
                                            name="user_id"
                                            value="<?php echo $user["id"]; ?>"
                                        >

                                        <select
                                            name="role"
                                            onchange="this.form.submit()"
                                        >

                                            <option
                                                value="user"
                                                <?php
                                                if ($user["role"] === "user") {
                                                    echo "selected";
                                                }
                                                ?>
                                            >
                                                User
                                            </option>

                                            <option
                                                value="admin"
                                                <?php
                                                if ($user["role"] === "admin") {
                                                    echo "selected";
                                                }
                                                ?>
                                            >
                                                Admin
                                            </option>

                                        </select>

                                    </form>

                                </td>

                                <td>
                                    <?php
                                    echo date(
                                        "d M Y, h:i A",
                                        strtotime($user["created_at"])
                                    );
                                    ?>
                                </td>

                                <td>

                                    <?php if ($user["id"] != $_SESSION["user_id"]) { ?>

                                        <form
                                            method="POST"
                                            action="delete_user.php"
                                            onsubmit="return confirm('Are you sure you want to delete this user?');"
                                        >

                                            <input
                                                type="hidden"
                                                name="user_id"
                                                value="<?php echo $user["id"]; ?>"
                                            >

                                            <button
                                                type="submit"
                                                class="delete-user-button"
                                            >
                                                🗑️ Delete
                                            </button>

                                        </form>

                                    <?php } else { ?>

                                        <span class="admin-user-status">
                                            Current Admin
                                        </span>

                                        <?php } ?>

                                </td>

                            </tr>

                        <?php } ?>

                    <?php } else { ?>

                        <tr>

                            <td
                                colspan="6"
                                style="text-align:center;"
                            >
                                No users found.
                            </td>

                        </tr>

                    <?php } ?>

                </tbody>

            </table>

        </div>

    </div>

</main>

<?php include "admin_footer.php"; ?>