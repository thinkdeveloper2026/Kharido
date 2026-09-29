<?php

include "config/database.php";

$message = "";
$message_type = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $name = trim($_POST["name"]);
    $email = trim($_POST["email"]);
    $password = $_POST["password"];

    if (empty($name) || empty($email) || empty($password)) {

        $message = "Please fill all fields.";
        $message_type = "error";

    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {

        $message = "Please enter a valid email.";
        $message_type = "error";

    } elseif (strlen($password) < 6) {

        $message = "Password must be at least 6 characters.";
        $message_type = "error";

    } else {

        // Check if email already exists
        $check = mysqli_prepare(
            $conn,
            "SELECT id FROM users WHERE email = ?"
        );

        mysqli_stmt_bind_param(
            $check,
            "s",
            $email
        );

        mysqli_stmt_execute($check);

        mysqli_stmt_store_result($check);

        if (mysqli_stmt_num_rows($check) > 0) {

            $message = "Email is already registered.";
            $message_type = "error";

        } else {

            // Secure password hashing
            $hashed_password = password_hash(
                $password,
                PASSWORD_DEFAULT
            );

            $insert = mysqli_prepare(
                $conn,
                "INSERT INTO users (name, email, password)
                 VALUES (?, ?, ?)"
            );

            mysqli_stmt_bind_param(
                $insert,
                "sss",
                $name,
                $email,
                $hashed_password
            );

            if (mysqli_stmt_execute($insert)) {

                $message = "Registration successful! You can now login.";
                $message_type = "success";

            } else {

                $message = "Registration failed. Please try again.";
                $message_type = "error";
            }

            mysqli_stmt_close($insert);
        }

        mysqli_stmt_close($check);
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

    <title>Create Account - Kharido</title>

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

        <h1>Create Account</h1>

        <p class="auth-subtitle">
            Join Kharido and start shopping.
        </p>


        <?php if (!empty($message)) { ?>

            <div class="auth-message <?php echo $message_type; ?>">

                <?php echo htmlspecialchars($message); ?>

            </div>

        <?php } ?>


        <form method="POST" action="">

            <div class="form-group">

                <label>
                    Full Name
                </label>

                <input
                    type="text"
                    name="name"
                    placeholder="Enter your name"
                    required
                >

            </div>


            <div class="form-group">

                <label>
                    Email
                </label>

                <input
                    type="email"
                    name="email"
                    placeholder="Enter your email"
                    required
                >

            </div>


            <div class="form-group">

                <label>
                    Password
                </label>

                <input
                    type="password"
                    name="password"
                    placeholder="Minimum 6 characters"
                    required
                >

            </div>


            <button
                type="submit"
                class="auth-button"
            >
                Create Account
            </button>

        </form>


        <p class="auth-bottom">

            Already have an account?

            <a href="login.php">
                Login
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