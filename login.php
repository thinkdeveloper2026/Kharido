<?php

session_start();

include "config/database.php";

$message = "";
$message_type = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $email = trim($_POST["email"]);
    $password = $_POST["password"];

    if (empty($email) || empty($password)) {

        $message = "Please fill all fields.";
        $message_type = "error";

    } else {

        $stmt = mysqli_prepare(
            $conn,
            "SELECT id, name, email, password, role  FROM users WHERE email = ?"
        );

        mysqli_stmt_bind_param(
            $stmt,
            "s",
            $email
        );

        mysqli_stmt_execute($stmt);

        $result = mysqli_stmt_get_result($stmt);

        if (mysqli_num_rows($result) == 1) {

            $user = mysqli_fetch_assoc($result);

            if (password_verify($password, $user["password"])) {

                $_SESSION["user_id"] = $user["id"];
                $_SESSION["user_name"] = $user["name"];
                $_SESSION["user_email"] = $user["email"];
                $_SESSION["user_role"] = $user["role"];

                header("Location: index.php");
                exit;

            } else {

                $message = "Incorrect password.";
                $message_type = "error";
            }

        } else {

            $message = "Email not registered.";
            $message_type = "error";
        }

        mysqli_stmt_close($stmt);
    }
}

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Login - Kharido</title>

    <link
        rel="stylesheet"
        href="css/style.css"
    >

</head>

<body class="auth-page">

    <div class="auth-container">

        <div class="auth-logo">
            Kharido
        </div>

        <h1>Login</h1>

        <p class="auth-subtitle">
            Login to your Kharido account.
        </p>


        <?php if (!empty($message)) { ?>

            <div class="auth-message <?php echo $message_type; ?>">

                <?php echo htmlspecialchars($message); ?>

            </div>

        <?php } ?>


        <form method="POST" action="">

            <div class="form-group">

                <label>Email</label>

                <input
                    type="email"
                    name="email"
                    placeholder="Enter your email"
                    required
                >

            </div>


            <div class="form-group">

                <label>Password</label>

                <input
                    type="password"
                    name="password"
                    placeholder="Enter your password"
                    required
                >

            </div>


            <button
                type="submit"
                class="auth-button"
            >
                Login
            </button>

        </form>


        <p class="auth-bottom">

            Don't have an account?

            <a href="register.php">
                Create Account
            </a>

        </p>


        <a
            href="index.php"
            class="back-home"
        >
            ← Back to Kharido
        </a>

    </div>

</body>

</html>