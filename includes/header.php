<?php
if (!isset($page_title)) {
    $page_title = "Kharido - Online Shopping";
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title><?php echo htmlspecialchars($page_title); ?></title>

    <link rel="stylesheet" href="/Kharido/css/style.css">
</head>

<body>

<?php include "includes/navbar.php"; ?>