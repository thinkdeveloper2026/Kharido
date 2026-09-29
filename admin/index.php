<?php 
 
/* ======================================== 
   ADMIN DASHBOARD - KHARIDO 
   STEP 9.10 
======================================== */ 
 
if (session_status() === PHP_SESSION_NONE) { 
    session_start(); 
} 
 
require_once "../config/database.php"; 
 
 
/* ======================================== 
   ADMIN ACCESS CHECK 
======================================== */ 
 
if (!isset($_SESSION["user_id"])) { 
    header("Location: ../login.php"); 
    exit; 
} 
 
if ( 
    !isset($_SESSION["user_role"]) || 
    $_SESSION["user_role"] !== "admin" 
) { 
    header("Location: ../index.php"); 
    exit; 
} 
 
 
/* ======================================== 
   1. DASHBOARD STATISTICS 
======================================== */ 
 
/* Total Products */ 
$product_query = mysqli_query( 
    $conn, 
    "SELECT COUNT(*) AS total 
     FROM products" 
); 
 
$total_products = mysqli_fetch_assoc( 
    $product_query 
)["total"]; 
 
 
/* Total Orders */ 
$order_query = mysqli_query( 
    $conn, 
    "SELECT COUNT(*) AS total 
     FROM orders" 
); 
 
$total_orders = mysqli_fetch_assoc( 
    $order_query 
)["total"]; 
 
 
/* Total Users */ 
$user_query = mysqli_query( 
    $conn, 
    "SELECT COUNT(*) AS total 
     FROM users 
     WHERE role = 'user'" 
); 
 
$total_users = mysqli_fetch_assoc( 
    $user_query 
)["total"]; 
 
 
/* Total Revenue */ 
$revenue_query = mysqli_query( 
    $conn, 
    "SELECT COALESCE(SUM(total_amount), 0) AS total 
     FROM orders 
     WHERE status != 'Cancelled'" 
); 
 
$total_revenue = mysqli_fetch_assoc( 
    $revenue_query 
)["total"]; 
 
 
/* Pending Orders */ 
$pending_query = mysqli_query( 
    $conn, 
    "SELECT COUNT(*) AS total 
     FROM orders 
     WHERE status = 'Pending'" 
); 
 
$pending_orders = mysqli_fetch_assoc( 
    $pending_query 
)["total"]; 
 
 
/* Low Stock */ 
$low_stock_query = mysqli_query( 
    $conn, 
    "SELECT COUNT(*) AS total 
     FROM products 
     WHERE stock <= 5" 
); 
 
$low_stock = mysqli_fetch_assoc( 
    $low_stock_query 
)["total"]; 
 
 
/* ======================================== 
   2. ORDER STATUS SUMMARY 
======================================== */ 
 
$pending_status_query = mysqli_query( 
    $conn, 
    "SELECT COUNT(*) AS total 
     FROM orders 
     WHERE status = 'Pending'" 
); 
 
$pending_status = mysqli_fetch_assoc( 
    $pending_status_query 
)["total"]; 
 
/* Processing Orders */ 
 
$processing_status_query = mysqli_query( 
    $conn, 
    "SELECT COUNT(*) AS total 
     FROM orders 
     WHERE status = 'Processing'" 
); 
 
$processing_status = 0; 
 
if ($processing_status_query) { 
 
    $processing_status = 
        mysqli_fetch_assoc( 
            $processing_status_query 
        )["total"]; 
} 
 
$shipped_status_query = mysqli_query( 
    $conn, 
    "SELECT COUNT(*) AS total 
     FROM orders 
     WHERE status = 'Shipped'" 
); 
 
$shipped_status = mysqli_fetch_assoc( 
    $shipped_status_query 
)["total"]; 
 
 
$delivered_status_query = mysqli_query( 
    $conn, 
    "SELECT COUNT(*) AS total 
     FROM orders 
     WHERE status = 'Delivered'" 
); 
 
$delivered_status = mysqli_fetch_assoc( 
    $delivered_status_query 
)["total"]; 
 
 
$cancelled_status_query = mysqli_query( 
    $conn, 
    "SELECT COUNT(*) AS total 
     FROM orders 
     WHERE status = 'Cancelled'" 
); 
 
$cancelled_status = mysqli_fetch_assoc( 
    $cancelled_status_query 
)["total"]; 
 
 
/* ======================================== 
   3. RECENT 5 ORDERS 
======================================== */ 
 
$recent_orders_query = mysqli_query( 
    $conn, 
    "SELECT 
        orders.id, 
        orders.total_amount, 
        orders.status, 
        orders.created_at, 
        users.name AS customer_name 
     FROM orders 
     INNER JOIN users 
        ON orders.user_id = users.id 
     ORDER BY orders.id DESC 
     LIMIT 5" 
); 
 
 
/* ======================================== 
   4. LOW STOCK PRODUCTS 
======================================== */ 
 
$low_stock_products_query = mysqli_query( 
    $conn, 
    "SELECT 
        id, 
        name, 
        stock 
     FROM products 
     WHERE stock <= 5 
     ORDER BY stock ASC 
     LIMIT 10" 
); 
 
 
/* ======================================== 
   5. LAST 6 MONTHS REVENUE 
======================================== */ 
 
$monthly_revenue_query = mysqli_query( 
    $conn, 
    "SELECT 
        DATE_FORMAT(created_at, '%Y-%m') AS month_key, 
        DATE_FORMAT(created_at, '%b %Y') AS month_name, 
        COALESCE(SUM(total_amount), 0) AS revenue, 
        COUNT(*) AS orders 
     FROM orders 
     WHERE status != 'Cancelled' 
     AND created_at >= DATE_SUB( 
         CURDATE(), 
         INTERVAL 5 MONTH 
     ) 
     GROUP BY 
        DATE_FORMAT(created_at, '%Y-%m'), 
        DATE_FORMAT(created_at, '%b %Y') 
     ORDER BY month_key ASC" 
); 
 
 
$monthly_data = []; 
 
if ($monthly_revenue_query) { 
 
    while ( 
        $row = mysqli_fetch_assoc( 
            $monthly_revenue_query 
        ) 
    ) { 
 
        $monthly_data[$row["month_key"]] = [ 
            "month_name" => $row["month_name"], 
            "revenue" => (float) $row["revenue"], 
            "orders" => (int) $row["orders"] 
        ]; 
    } 
} 
 
 
$six_months = []; 
 
for ($i = 5; $i >= 0; $i--) { 
 
    $timestamp = strtotime( 
        date("Y-m-01") . " -$i months" 
    ); 
 
    $month_key = date( 
        "Y-m", 
        $timestamp 
    ); 
 
    $month_name = date( 
        "M Y", 
        $timestamp 
    ); 
 
    if (isset($monthly_data[$month_key])) { 
 
        $six_months[] = [ 
            "month_key" => $month_key, 
            "month_name" => 
                $monthly_data[$month_key]["month_name"], 
            "revenue" => 
                $monthly_data[$month_key]["revenue"], 
            "orders" => 
                $monthly_data[$month_key]["orders"] 
        ]; 
 
    } else { 
 
        $six_months[] = [ 
            "month_key" => $month_key, 
            "month_name" => $month_name, 
            "revenue" => 0, 
            "orders" => 0 
        ]; 
    } 
} 
 
 
$max_revenue = 0; 
 
foreach ($six_months as $month) { 
 
    if ($month["revenue"] > $max_revenue) { 
        $max_revenue = $month["revenue"]; 
    } 
} 
 
 
/* ======================================== 
   6. TOP SELLING PRODUCTS 
======================================== */ 
 
$top_products_query = mysqli_query( 
    $conn, 
    "SELECT 
        products.id, 
        products.name, 
        products.price, 
        COALESCE( 
            SUM( 
                CASE 
                    WHEN orders.id IS NOT NULL 
                    THEN order_items.quantity 
                    ELSE 0 
                END 
            ), 
            0 
        ) AS total_sold, 
        COALESCE( 
            SUM( 
                CASE 
                    WHEN orders.id IS NOT NULL 
                    THEN order_items.subtotal 
                    ELSE 0 
                END 
            ), 
            0 
        ) AS total_sales 
     FROM order_items 
     INNER JOIN orders 
        ON order_items.order_id = orders.id 
     INNER JOIN products 
        ON order_items.product_id = products.id 
     WHERE orders.status != 'Cancelled' 
     GROUP BY 
        products.id, 
        products.name, 
        products.price 
     ORDER BY total_sold DESC, total_sales DESC 
     LIMIT 5" 
); 
 
 
$top_products = []; 
 
if ($top_products_query) { 
 
    while ( 
        $row = mysqli_fetch_assoc( 
            $top_products_query 
        ) 
    ) { 
 
        $top_products[] = $row; 
    } 
} 
 
 
$max_sold = 0; 
 
foreach ($top_products as $product) { 
 
    if ( 
        (int) $product["total_sold"] > 
        $max_sold 
    ) { 
 
        $max_sold = 
            (int) $product["total_sold"]; 
    } 
} 
 
 
/* ======================================== 
   7. STEP 9.7 
      SALES BY CATEGORY 
======================================== */ 
 
$category_sales_query = mysqli_query( 
    $conn, 
    "SELECT 
        c.id, 
        c.name, 
 
        COALESCE( 
            SUM( 
                CASE 
                    WHEN o.id IS NOT NULL 
                    THEN oi.quantity 
                    ELSE 0 
                END 
            ), 
            0 
        ) AS total_sold, 
 
        COALESCE( 
            SUM( 
                CASE 
                    WHEN o.id IS NOT NULL 
                    THEN oi.subtotal 
                    ELSE 0 
                END 
            ), 
            0 
        ) AS total_sales, 
 
        COUNT( 
            DISTINCT o.id 
        ) AS total_orders 
 
     FROM categories c 
 
     LEFT JOIN products p 
        ON c.id = p.category_id 
 
     LEFT JOIN order_items oi 
        ON p.id = oi.product_id 
 
     LEFT JOIN orders o 
        ON oi.order_id = o.id 
        AND o.status != 'Cancelled' 
 
     GROUP BY 
        c.id, 
        c.name 
 
     ORDER BY total_sales DESC" 
); 
 
 
$category_sales = []; 
 
if ($category_sales_query) { 
 
    while ( 
        $row = mysqli_fetch_assoc( 
            $category_sales_query 
        ) 
    ) { 
 
        $category_sales[] = $row; 
    } 
} 
 
 
$max_category_sales = 0; 
 
foreach ($category_sales as $category) { 
 
    if ( 
        (float) $category["total_sales"] 
        > $max_category_sales 
    ) { 
 
        $max_category_sales = 
            (float) $category["total_sales"]; 
    } 
} 
 
 
/* ======================================== 
   8. STEP 9.8 
      CUSTOMER / USER STATISTICS 
======================================== */ 
 
 
/* New Users - Last 30 Days */ 
 
$new_users_query = mysqli_query( 
    $conn, 
    "SELECT COUNT(*) AS total 
     FROM users 
     WHERE role = 'user' 
     AND created_at >= DATE_SUB( 
         NOW(), 
         INTERVAL 30 DAY 
     )" 
); 
 
$new_users = 0; 
 
if ($new_users_query) { 
 
    $new_users = 
        mysqli_fetch_assoc( 
            $new_users_query 
        )["total"]; 
} 
 
 
/* Active Customers */ 
 
$active_customers_query = mysqli_query( 
    $conn, 
    "SELECT COUNT(DISTINCT orders.user_id) AS total 
     FROM orders 
     INNER JOIN users 
        ON orders.user_id = users.id 
     WHERE users.role = 'user' 
     AND orders.status != 'Cancelled'" 
); 
 
$active_customers = 0; 
 
if ($active_customers_query) { 
 
    $active_customers = 
        mysqli_fetch_assoc( 
            $active_customers_query 
        )["total"]; 
} 
 
 
/* Repeat Customers */ 
 
$repeat_customers_query = mysqli_query( 
    $conn, 
    "SELECT COUNT(*) AS total 
     FROM ( 
         SELECT orders.user_id 
         FROM orders 
         INNER JOIN users 
            ON orders.user_id = users.id 
         WHERE users.role = 'user' 
         AND orders.status != 'Cancelled' 
         GROUP BY orders.user_id 
         HAVING COUNT(orders.id) > 1 
     ) AS repeat_users" 
); 
 
$repeat_customers = 0; 
 
if ($repeat_customers_query) { 
 
    $repeat_customers = 
        mysqli_fetch_assoc( 
            $repeat_customers_query 
        )["total"]; 
} 
 
 
/* Users Without Orders */ 
 
$inactive_users_query = mysqli_query( 
    $conn, 
    "SELECT COUNT(*) AS total 
     FROM users 
     WHERE role = 'user' 
     AND id NOT IN ( 
         SELECT DISTINCT user_id 
         FROM orders 
         WHERE status != 'Cancelled' 
     )" 
); 
 
$inactive_users = 0; 
 
if ($inactive_users_query) { 
 
    $inactive_users = 
        mysqli_fetch_assoc( 
            $inactive_users_query 
        )["total"]; 
} 
 
 
/* Customer Order Percentage */ 
 
$customer_order_percentage = 0; 
 
if ($total_users > 0) { 
 
    $customer_order_percentage = 
        ( 
            $active_customers / 
            $total_users 
        ) * 100; 
} 
 
 
/* ======================================== 
   9. STEP 9.9 
      DASHBOARD NOTIFICATIONS & ALERTS 
======================================== */ 
 
 
/* Out of Stock */ 
 
$out_of_stock_query = mysqli_query( 
    $conn, 
    "SELECT COUNT(*) AS total 
     FROM products 
     WHERE stock <= 0" 
); 
 
$out_of_stock = 0; 
 
if ($out_of_stock_query) { 
 
    $out_of_stock = 
        mysqli_fetch_assoc( 
            $out_of_stock_query 
        )["total"]; 
} 
 
 
/* Low Stock */ 
 
$low_stock_alert_query = mysqli_query( 
    $conn, 
    "SELECT COUNT(*) AS total 
     FROM products 
     WHERE stock > 0 
     AND stock <= 5" 
); 
 
$low_stock_alert = 0; 
 
if ($low_stock_alert_query) { 
 
    $low_stock_alert = 
        mysqli_fetch_assoc( 
            $low_stock_alert_query 
        )["total"]; 
} 
 
 
/* Pending Orders */ 
 
$pending_alert_query = mysqli_query( 
    $conn, 
    "SELECT COUNT(*) AS total 
     FROM orders 
     WHERE status = 'Pending'" 
); 
 
$pending_alert = 0; 
 
if ($pending_alert_query) { 
 
    $pending_alert = 
        mysqli_fetch_assoc( 
            $pending_alert_query 
        )["total"]; 
} 
 
 
/* Orders in Last 24 Hours */ 
 
$recent_orders_alert_query = mysqli_query( 
    $conn, 
    "SELECT COUNT(*) AS total 
     FROM orders 
     WHERE created_at >= DATE_SUB( 
         NOW(), 
         INTERVAL 1 DAY 
     )" 
); 
 
$recent_orders_alert = 0; 
 
if ($recent_orders_alert_query) { 
 
    $recent_orders_alert = 
        mysqli_fetch_assoc( 
            $recent_orders_alert_query 
        )["total"]; 
} 
 
 
$total_alerts = 
    $out_of_stock 
    + $low_stock_alert 
    + $pending_alert; 
 
 
/* ======================================== 
   PAGE TITLE 
======================================== */ 
 
$page_title = "Admin Dashboard - Kharido"; 
 
include "admin_header.php"; 
 
?> 
 
 
<style> 
 
/* ======================================== 
   DASHBOARD 
======================================== */ 
 
.dashboard-container { 
    padding: 25px; 
} 
 
.dashboard-title { 
    margin-bottom: 25px; 
} 
 
.dashboard-title h1 { 
    margin: 0; 
    font-size: 28px; 
    color: #111827; 
} 
 
.dashboard-title p { 
    margin-top: 6px; 
    color: #6b7280; 
} 
 
 
/* ======================================== 
   STAT CARDS 
======================================== */ 
 
.dashboard-stats { 
    display: grid; 
    grid-template-columns: 
        repeat(auto-fit, minmax(190px, 1fr)); 
    gap: 20px; 
    margin-bottom: 30px; 
} 
 
.dashboard-stat-card { 
    background: #fff; 
    border-radius: 12px; 
    padding: 22px; 
    text-decoration: none; 
    color: inherit; 
    box-shadow: 
        0 2px 8px rgba(0,0,0,0.08); 
    transition: 0.2s; 
    text-align: center; 
    border: 1px solid #eee; 
} 
 
.dashboard-stat-card:hover { 
    transform: translateY(-4px); 
    box-shadow: 
        0 6px 18px rgba(0,0,0,0.12); 
} 
 
.stat-icon { 
    font-size: 30px; 
    margin-bottom: 8px; 
} 
 
.stat-title { 
    font-size: 14px; 
    color: #4b5563; 
    font-weight: 600; 
} 
 
.stat-number { 
    font-size: 28px; 
    font-weight: bold; 
    margin: 7px 0; 
    color: #111827; 
} 
 
.stat-link { 
    color: #0066ff; 
    font-size: 13px; 
} 
 
 
/* ======================================== 
   COMMON SECTION 
======================================== */ 
 
.dashboard-section { 
    background: #fff; 
    border-radius: 12px; 
    padding: 20px; 
    margin-bottom: 30px; 
    box-shadow: 
        0 2px 8px rgba(0,0,0,0.08); 
} 
 
.dashboard-section h2 { 
    margin: 0 0 18px; 
    font-size: 19px; 
    color: #111827; 
} 
 
 
/* ======================================== 
   ORDER STATUS 
======================================== */ 
 
.status-grid { 
    display: grid; 
    grid-template-columns: 
        repeat(auto-fit, minmax(180px, 1fr)); 
    gap: 15px; 
} 
 
.status-card { 
    padding: 18px; 
    border-radius: 9px; 
    text-decoration: none; 
    display: block; 
    transition: 0.2s; 
} 
 
.status-card:hover { 
    transform: translateY(-3px); 
} 
 
.status-icon { 
    font-size: 28px; 
    margin-bottom: 8px; 
} 
 
.status-name { 
    font-size: 13px; 
    font-weight: 600; 
} 
 
.status-number { 
    font-size: 26px; 
    font-weight: bold; 
    margin-top: 5px; 
} 
 
.status-link { 
    font-size: 12px; 
    margin-top: 5px; 
} 
 
 
/* ======================================== 
   REVENUE OVERVIEW 
======================================== */ 
 
.revenue-header { 
    display: flex; 
    justify-content: space-between; 
    align-items: center; 
    gap: 15px; 
    margin-bottom: 20px; 
} 
 
.revenue-header h2 { 
    margin: 0; 
} 
 
.revenue-info { 
    font-size: 13px; 
    color: #6b7280; 
} 
 
.revenue-summary { 
    display: grid; 
    grid-template-columns: 
        repeat(auto-fit, minmax(180px, 1fr)); 
    gap: 15px; 
    margin-bottom: 25px; 
} 
 
.revenue-summary-card { 
    background: #f8fafc; 
    border: 1px solid #e5e7eb; 
    border-radius: 9px; 
    padding: 16px; 
} 
 
.revenue-summary-title { 
    font-size: 13px; 
    color: #6b7280; 
} 
 
.revenue-summary-value { 
    font-size: 22px; 
    font-weight: bold; 
    margin-top: 6px; 
    color: #15803d; 
} 
 
.revenue-row { 
    margin-bottom: 18px; 
} 
 
.revenue-row-top { 
    display: flex; 
    justify-content: space-between; 
    align-items: center; 
    margin-bottom: 7px; 
} 
 
.revenue-month { 
    font-size: 13px; 
    font-weight: 600; 
    color: #374151; 
} 
 
.revenue-amount { 
    font-size: 13px; 
    font-weight: bold; 
    color: #15803d; 
} 
 
.revenue-bar-background { 
    width: 100%; 
    height: 22px; 
    background: #eef2f7; 
    border-radius: 20px; 
    overflow: hidden; 
} 
 
.revenue-bar { 
    height: 100%; 
    background: #22c55e; 
    border-radius: 20px; 
    transition: width 0.4s ease; 
} 
 
.revenue-orders { 
    font-size: 11px; 
    color: #6b7280; 
    margin-top: 4px; 
} 
 
 
/* ======================================== 
   TOP SELLING PRODUCTS 
======================================== */ 
 
.top-products-grid { 
    display: grid; 
    grid-template-columns: 
        repeat(auto-fit, minmax(250px, 1fr)); 
    gap: 15px; 
} 
 
.top-product-card { 
    background: #f8fafc; 
    border: 1px solid #e5e7eb; 
    border-radius: 10px; 
    padding: 18px; 
    transition: 0.2s; 
} 
 
.top-product-card:hover { 
    transform: translateY(-3px); 
    box-shadow: 
        0 5px 15px rgba(0,0,0,0.08); 
} 
 
.top-product-header { 
    display: flex; 
    align-items: center; 
    gap: 12px; 
    margin-bottom: 15px; 
} 
 
.product-rank { 
    width: 38px; 
    height: 38px; 
    border-radius: 50%; 
    display: flex; 
    align-items: center; 
    justify-content: center; 
    background: #eef2ff; 
    color: #4338ca; 
    font-weight: bold; 
    font-size: 14px; 
    flex-shrink: 0; 
} 
 
.top-product-name { 
    font-size: 15px; 
    font-weight: 600; 
    color: #111827; 
    word-break: break-word; 
} 
 
.top-product-stats { 
    display: flex; 
    justify-content: space-between; 
    gap: 10px; 
    margin-bottom: 10px; 
} 
 
.sold-count { 
    font-size: 13px; 
    color: #2563eb; 
    font-weight: 600; 
} 
 
.sales-amount { 
    font-size: 13px; 
    color: #15803d; 
    font-weight: 600; 
} 
 
.sales-bar-background { 
    width: 100%; 
    height: 9px; 
    background: #e5e7eb; 
    border-radius: 20px; 
    overflow: hidden; 
} 
 
.sales-bar { 
    height: 100%; 
    background: #6366f1; 
    border-radius: 20px; 
} 
 
.top-product-price { 
    margin-top: 9px; 
    font-size: 11px; 
    color: #6b7280; 
} 
 
.no-top-products { 
    text-align: center; 
    padding: 30px; 
    color: #6b7280; 
    background: #f9fafb; 
    border-radius: 8px; 
} 
 
 
/* ======================================== 
   STEP 9.7 
   SALES BY CATEGORY 
======================================== */ 
 
.category-sales-grid { 
    display: grid; 
    grid-template-columns: 
        repeat(auto-fit, minmax(230px, 1fr)); 
    gap: 15px; 
} 
 
.category-sales-card { 
    background: #f8fafc; 
    border: 1px solid #e5e7eb; 
    border-radius: 10px; 
    padding: 18px; 
    transition: 0.2s; 
} 
 
.category-sales-card:hover { 
    transform: translateY(-3px); 
    box-shadow: 
        0 5px 15px rgba(0,0,0,0.08); 
} 
 
.category-sales-header { 
    display: flex; 
    justify-content: space-between; 
    gap: 10px; 
    margin-bottom: 12px; 
} 
 
.category-sales-name { 
    font-size: 15px; 
    font-weight: 600; 
    color: #111827; 
} 
 
.category-sales-value { 
    font-size: 14px; 
    font-weight: bold; 
    color: #15803d; 
} 
 
.category-sales-bar-bg { 
    width: 100%; 
    height: 9px; 
    background: #e5e7eb; 
    border-radius: 20px; 
    overflow: hidden; 
} 
 
.category-sales-bar { 
    height: 100%; 
    background: #6366f1; 
    border-radius: 20px; 
} 
 
.category-sales-meta { 
    display: flex; 
    justify-content: space-between; 
    gap: 10px; 
    margin-top: 9px; 
    font-size: 11px; 
    color: #6b7280; 
} 
 
 
/* ======================================== 
   STEP 9.8 
   CUSTOMER STATISTICS 
======================================== */ 
 
.customer-stat-grid { 
    display: grid; 
    grid-template-columns: 
        repeat(auto-fit, minmax(190px, 1fr)); 
    gap: 15px; 
} 
 
.customer-stat-card { 
    background: #f8fafc; 
    border: 1px solid #e5e7eb; 
    border-radius: 10px; 
    padding: 18px; 
    transition: 0.2s; 
} 
 
.customer-stat-card:hover { 
    transform: translateY(-3px); 
    box-shadow: 
        0 5px 15px rgba(0,0,0,0.08); 
} 
 
.customer-stat-icon { 
    font-size: 27px; 
    margin-bottom: 9px; 
} 
 
.customer-stat-title { 
    font-size: 13px; 
    color: #6b7280; 
} 
 
.customer-stat-number { 
    font-size: 25px; 
    font-weight: bold; 
    color: #111827; 
    margin-top: 5px; 
} 
 
.customer-stat-info { 
    font-size: 11px; 
    color: #6b7280; 
    margin-top: 5px; 
} 
 
 
/* ======================================== 
   STEP 9.9 
   NOTIFICATIONS 
======================================== */ 
 
.dashboard-alerts { 
    display: grid; 
    grid-template-columns: 
        repeat(auto-fit, minmax(190px, 1fr)); 
    gap: 15px; 
} 
 
.dashboard-alert { 
    display: block; 
    text-decoration: none; 
    color: inherit; 
    padding: 18px; 
    border-radius: 10px; 
    transition: 0.2s; 
} 
 
.dashboard-alert:hover { 
    transform: translateY(-3px); 
} 
 
.alert-icon { 
    font-size: 27px; 
    margin-bottom: 8px; 
} 
 
.alert-title { 
    font-size: 13px; 
    font-weight: 600; 
} 
 
.alert-number { 
    font-size: 25px; 
    font-weight: bold; 
    margin-top: 5px; 
} 
 
.alert-info { 
    font-size: 11px; 
    margin-top: 5px; 
} 
 
 
/* ======================================== 
   SECTION TITLE 
======================================== */ 
 
.dashboard-section-title-row { 
    display: flex; 
    justify-content: space-between; 
    align-items: center; 
    gap: 10px; 
    margin-bottom: 18px; 
} 
 
.dashboard-section-title-row h2 { 
    margin: 0; 
} 
 
.dashboard-mini-summary { 
    font-size: 12px; 
    color: #6b7280; 
} 
 
.dashboard-empty { 
    text-align: center; 
    padding: 25px; 
    color: #6b7280; 
    background: #f9fafb; 
    border-radius: 8px; 
} 
 
 
/* ======================================== 
   QUICK ACTIONS 
======================================== */ 
 
.quick-actions { 
    display: flex; 
    flex-wrap: wrap; 
    gap: 10px; 
} 
 
.quick-action { 
    text-decoration: none; 
    color: #111827; 
    padding: 10px 14px; 
    border-radius: 7px; 
    background: #f8fafc; 
    border: 1px solid #e5e7eb; 
    transition: 0.2s; 
} 
 
.quick-action:hover { 
    background: #eef2ff; 
    transform: translateY(-2px); 
} 
 
 
/* ======================================== 
   TWO COLUMNS 
======================================== */ 
 
.dashboard-columns { 
    display: grid; 
    grid-template-columns: 
        repeat(2, minmax(0, 1fr)); 
    gap: 20px; 
    margin-bottom: 30px; 
} 
 
 
/* ======================================== 
   TABLE 
======================================== */ 
 
.admin-table-container { 
    background: white; 
    border: 1px solid #eee; 
    border-radius: 12px; 
    overflow-x: auto; 
} 
 
.admin-table { 
    width: 100%; 
    border-collapse: collapse; 
    min-width: 0; 
} 
 
.admin-table th { 
    background: #f9fafb; 
    color: #374151; 
    font-size: 13px; 
    text-align: left; 
    padding: 15px; 
    border-bottom: 1px solid #eee; 
} 
 
.admin-table td { 
    padding: 15px; 
    border-bottom: 1px solid #eee; 
    color: #374151; 
    vertical-align: middle; 
    font-size: 13px; 
} 
 
 
/* ======================================== 
   STATUS BADGES 
======================================== */ 
 
.status-badge { 
    display: inline-block; 
    padding: 5px 9px; 
    border-radius: 20px; 
    font-size: 11px; 
    font-weight: 600; 
} 
 
.status-pending { 
    background: #fff3cd; 
    color: #856404; 
} 
 
/* PROCESSING STATUS - ADDED */ 
.status-processing { 
    background: #ede9fe; 
    color: #6d28d9; 
} 
 
.status-shipped { 
    background: #dbeafe; 
    color: #1d4ed8; 
} 
 
.status-delivered { 
    background: #dcfce7; 
    color: #166534; 
} 
 
.status-cancelled { 
    background: #fee2e2; 
    color: #b91c1c; 
} 
 
 
/* ======================================== 
   LINKS 
======================================== */ 
 
.section-link { 
    float: right; 
    text-decoration: none; 
    color: #0066ff; 
    font-size: 13px; 
    font-weight: 600; 
} 
 
.section-link:hover { 
    text-decoration: underline; 
} 
 
 
/* ======================================== 
   STOCK 
======================================== */ 
 
.stock-warning { 
    color: #dc2626; 
    font-weight: bold; 
} 
 
.stock-ok { 
    color: #16a34a; 
} 
 
 
/* ======================================== 
   RESPONSIVE 
======================================== */ 
 
@media (max-width: 800px) { 
 
    .dashboard-columns { 
        grid-template-columns: 1fr; 
    } 
 
    .dashboard-container { 
        padding: 15px; 
    } 
 
    .dashboard-title h1 { 
        font-size: 23px; 
    } 
 
    .revenue-header { 
        display: block; 
    } 
 
    .revenue-info { 
        margin-top: 5px; 
    } 
 
    .top-products-grid { 
        grid-template-columns: 1fr; 
    } 
 
    .category-sales-grid { 
        grid-template-columns: 1fr; 
    } 
 
    .customer-stat-grid { 
        grid-template-columns: 
            repeat(2, minmax(0, 1fr)); 
    } 
 
    .dashboard-alerts { 
        grid-template-columns: 
            repeat(2, minmax(0, 1fr)); 
    } 
 
    .dashboard-section-title-row { 
        display: block; 
    } 
 
    .dashboard-mini-summary { 
        margin-top: 5px; 
    } 
} 
 
 
@media (max-width: 500px) { 
 
    .customer-stat-grid { 
        grid-template-columns: 1fr; 
    } 
 
    .dashboard-alerts { 
        grid-template-columns: 1fr; 
    } 
 
    .category-sales-header { 
        display: block; 
    } 
 
    .category-sales-value { 
        margin-top: 5px; 
    } 
} 
 
</style> 
 
 
<div class="dashboard-container"> 
 
 
    <!-- ======================================== 
         DASHBOARD TITLE 
    ========================================= --> 
 
    <div class="dashboard-title"> 
 
        <h1> 
            Admin Dashboard 
        </h1> 
 
        <p> 
            Welcome to Kharido Admin Panel. 
            Manage your store from here. 
        </p> 
 
    </div> 
 
 
    <!-- ======================================== 
         1. STATISTICS CARDS 
    ========================================= --> 
 
    <div class="dashboard-stats"> 
 
 
        <a 
            href="products.php" 
            class="dashboard-stat-card" 
        > 
 
            <div class="stat-icon"> 
                📦 
            </div> 
 
            <div class="stat-title"> 
                Total Products 
            </div> 
 
            <div class="stat-number"> 
                <?php echo $total_products; ?> 
            </div> 
 
            <div class="stat-link"> 
                View Products → 
            </div> 
 
        </a> 
 
 
        <a 
            href="orders.php" 
            class="dashboard-stat-card" 
        > 
 
            <div class="stat-icon"> 
                🛒 
            </div> 
 
            <div class="stat-title"> 
                Total Orders 
            </div> 
 
            <div class="stat-number"> 
                <?php echo $total_orders; ?> 
            </div> 
 
            <div class="stat-link"> 
                View Orders → 
            </div> 
 
        </a> 
 
 
        <a 
            href="users.php" 
            class="dashboard-stat-card" 
        > 
 
            <div class="stat-icon"> 
                👤 
            </div> 
 
            <div class="stat-title"> 
                Registered Users 
            </div> 
 
            <div class="stat-number"> 
                <?php echo $total_users; ?> 
            </div> 
 
            <div class="stat-link"> 
                View Users → 
            </div> 
 
        </a> 
 
 
        <a 
            href="orders.php" 
            class="dashboard-stat-card" 
        > 
 
            <div class="stat-icon"> 
                💰 
            </div> 
 
            <div 
                class="stat-number" 
                style="color:#15803d;" 
            > 
                ₹<?php 
                echo number_format( 
                    $total_revenue, 
                    2 
                ); 
                ?> 
            </div> 
 
            <div class="stat-title"> 
                Total Revenue 
            </div> 
 
            <div class="stat-link"> 
                View Orders → 
            </div> 
 
        </a> 
 
 
        <a 
            href="orders.php" 
            class="dashboard-stat-card" 
        > 
 
            <div class="stat-icon"> 
                ⏳ 
            </div> 
 
            <div class="stat-title"> 
                Pending Orders 
            </div> 
 
            <div 
                class="stat-number" 
                style="color:#f97316;" 
            > 
                <?php echo $pending_orders; ?> 
            </div> 
 
            <div class="stat-link"> 
                Manage Orders → 
            </div> 
 
        </a> 
 
 
        <a 
            href="products.php" 
            class="dashboard-stat-card" 
        > 
 
            <div class="stat-icon"> 
                ⚠️ 
            </div> 
 
            <div class="stat-title"> 
                Low Stock 
            </div> 
 
            <div 
                class="stat-number" 
                style="color:#dc2626;" 
            > 
                <?php echo $low_stock; ?> 
            </div> 
 
            <div class="stat-link"> 
                Manage Stock → 
            </div> 
 
        </a> 
 
    </div> 
 
 
    <!-- ======================================== 
         2. ORDER STATUS SUMMARY 
    ========================================= --> 
 
    <div class="dashboard-section"> 
 
        <h2> 
            Order Status Summary 
        </h2> 
 
        <div class="status-grid"> 
 
 
            <a 
                href="orders.php" 
                class="status-card" 
                style=" 
                    background:#fff8e1; 
                    border:1px solid #ffe082; 
                    color:#856404; 
                " 
            > 
 
                <div class="status-icon"> 
                    ⏳ 
                </div> 
 
                <div class="status-name"> 
                    Pending 
                </div> 
 
                <div class="status-number"> 
                    <?php echo $pending_status; ?> 
                </div> 
 
                <div class="status-link"> 
                    View Orders → 
                </div> 
 
            </a> 
 
 
            <!-- PROCESSING ORDER CARD - ADDED --> 
 
            <a 
                href="orders.php" 
                class="status-card" 
                style=" 
                    background:#ede9fe; 
                    border:1px solid #c4b5fd; 
                    color:#6d28d9; 
                " 
            > 
 
                <div class="status-icon"> 
                    ⚙️ 
                </div> 
 
                <div class="status-name"> 
                    Processing 
                </div> 
 
                <div class="status-number"> 
                    <?php echo $processing_status; ?> 
                </div> 
 
                <div class="status-link"> 
                    View Orders → 
                </div> 
 
            </a> 
 
 
            <a 
                href="orders.php" 
                class="status-card" 
                style=" 
                    background:#e3f2fd; 
                    border:1px solid #90caf9; 
                    color:#1565c0; 
                " 
            > 
 
                <div class="status-icon"> 
                    🚚 
                </div> 
 
                <div class="status-name"> 
                    Shipped 
                </div> 
 
                <div class="status-number"> 
                    <?php echo $shipped_status; ?> 
                </div> 
 
                <div class="status-link"> 
                    View Orders → 
                </div> 
 
            </a> 
 
 
            <a 
                href="orders.php" 
                class="status-card" 
                style=" 
                    background:#e8f5e9; 
                    border:1px solid #a5d6a7; 
                    color:#2e7d32; 
                " 
            > 
 
                <div class="status-icon"> 
                    ✅ 
                </div> 
 
                <div class="status-name"> 
                    Delivered 
                </div> 
 
                <div class="status-number"> 
                    <?php echo $delivered_status; ?> 
                </div> 
 
                <div class="status-link"> 
                    View Orders → 
                </div> 
 
            </a> 
 
 
            <a 
                href="orders.php" 
                class="status-card" 
                style=" 
                    background:#ffebee; 
                    border:1px solid #ef9a9a; 
                    color:#c62828; 
                " 
            > 
 
                <div class="status-icon"> 
                    ❌ 
                </div> 
 
                <div class="status-name"> 
                    Cancelled 
                </div> 
 
                <div class="status-number"> 
                    <?php echo $cancelled_status; ?> 
                </div> 
 
                <div class="status-link"> 
                    View Orders → 
                </div> 
 
            </a> 
 
        </div> 
 
    </div> 
 
 
    <!-- ======================================== 
         3. SALES & REVENUE 
    ========================================= --> 
 
    <div class="dashboard-section"> 
 
        <div class="revenue-header"> 
 
            <h2> 
                Sales & Revenue Overview 
            </h2> 
 
            <div class="revenue-info"> 
                Last 6 Months • Cancelled orders excluded 
            </div> 
 
        </div> 
 
 
        <?php 
 
        $six_month_revenue = 0; 
        $six_month_orders = 0; 
 
        foreach ($six_months as $month) { 
 
            $six_month_revenue += 
                $month["revenue"]; 
 
            $six_month_orders += 
                $month["orders"]; 
        } 
 
        ?> 
 
 
        <div class="revenue-summary"> 
 
            <div class="revenue-summary-card"> 
 
                <div class="revenue-summary-title"> 
                    Last 6 Months Revenue 
                </div> 
 
                <div class="revenue-summary-value"> 
 
                    ₹<?php 
                    echo number_format( 
                        $six_month_revenue, 
                        2 
                    ); 
                    ?> 
 
                </div> 
 
            </div> 
 
 
            <div class="revenue-summary-card"> 
 
                <div class="revenue-summary-title"> 
                    Orders in Last 6 Months 
                </div> 
 
                <div 
                    class="revenue-summary-value" 
                    style="color:#2563eb;" 
                > 
                    <?php 
                    echo $six_month_orders; 
                    ?> 
                </div> 
 
            </div> 
 
 
            <div class="revenue-summary-card"> 
 
                <div class="revenue-summary-title"> 
                    All-Time Revenue 
                </div> 
 
                <div class="revenue-summary-value"> 
 
                    ₹<?php 
                    echo number_format( 
                        $total_revenue, 
                        2 
                    ); 
                    ?> 
 
                </div> 
 
            </div> 
 
        </div> 
 
 
        <div class="revenue-chart"> 
 
            <?php foreach ( 
                $six_months as $month 
            ): ?> 
 
                <?php 
 
                if ($max_revenue > 0) { 
 
                    $bar_width = 
                        ( 
                            $month["revenue"] / 
                            $max_revenue 
                        ) * 100; 
 
                } else { 
 
                    $bar_width = 0; 
                } 
 
                ?> 
 
                <div class="revenue-row"> 
 
                    <div class="revenue-row-top"> 
 
                        <span class="revenue-month"> 
 
                            <?php 
                            echo htmlspecialchars( 
                                $month["month_name"] 
                            ); 
                            ?> 
 
                        </span> 
 
                        <span class="revenue-amount"> 
 
                            ₹<?php 
                            echo number_format( 
                                $month["revenue"], 
                                2 
                            ); 
                            ?> 
 
                        </span> 
 
                    </div> 
 
 
                    <div class="revenue-bar-background"> 
 
                        <div 
                            class="revenue-bar" 
                            style=" 
                                width: 
                                <?php 
                                echo $bar_width; 
                                ?>%; 
                            " 
                        ></div> 
 
                    </div> 
 
 
                    <div class="revenue-orders"> 
 
                        <?php 
                        echo $month["orders"]; 
                        ?> 
 
                        order(s) 
 
                    </div> 
 
                </div> 
 
            <?php endforeach; ?> 
 
        </div> 
 
    </div> 
 
 
    <!-- ======================================== 
         4. TOP SELLING PRODUCTS 
    ========================================= --> 
 
    <div class="dashboard-section"> 
 
        <h2> 
 
            🏆 Top Selling Products 
 
            <a 
                href="products.php" 
                class="section-link" 
            > 
                Manage Products → 
            </a> 
 
        </h2> 
 
 
        <p 
            style=" 
                color:#6b7280; 
                font-size:13px; 
                margin-top:-8px; 
                margin-bottom:18px; 
            " 
        > 
            Top 5 products based on quantity sold. 
            Cancelled orders are excluded. 
        </p> 
 
 
        <?php if ( 
            count($top_products) > 0 
        ): ?> 
 
 
            <div class="top-products-grid"> 
 
                <?php 
 
                $rank = 1; 
 
                foreach ( 
                    $top_products as $product 
                ): 
 
                    if ($max_sold > 0) { 
 
                        $sales_width = 
                            ( 
                                $product["total_sold"] / 
                                $max_sold 
                            ) * 100; 
 
                    } else { 
 
                        $sales_width = 0; 
                    } 
 
                ?> 
 
                    <div class="top-product-card"> 
 
 
                        <div class="top-product-header"> 
 
                            <div class="product-rank"> 
 
                                <?php 
 
                                if ($rank === 1) { 
 
                                    echo "🥇"; 
 
                                } elseif ($rank === 2) { 
 
                                    echo "🥈"; 
 
                                } elseif ($rank === 3) { 
 
                                    echo "🥉"; 
 
                                } else { 
 
                                    echo "#" . $rank; 
                                } 
 
                                ?> 
 
                            </div> 
 
 
                            <div class="top-product-name"> 
 
                                <?php 
                                echo htmlspecialchars( 
                                    $product["name"] 
                                ); 
                                ?> 
 
                            </div> 
 
                        </div> 
 
 
                        <div class="top-product-stats"> 
 
                            <span class="sold-count"> 
 
                                📦 
                                <?php 
                                echo (int) 
                                    $product["total_sold"]; 
                                ?> 
                                sold 
 
                            </span> 
 
 
                            <span class="sales-amount"> 
 
                                ₹<?php 
                                echo number_format( 
                                    $product["total_sales"], 
                                    2 
                                ); 
                                ?> 
 
                            </span> 
 
                        </div> 
 
 
                        <div class="sales-bar-background"> 
 
                            <div 
                                class="sales-bar" 
                                style=" 
                                    width: 
                                    <?php 
                                    echo $sales_width; 
                                    ?>%; 
                                " 
                            ></div> 
 
                        </div> 
 
 
                        <div class="top-product-price"> 
 
                            Product Price: 
                            ₹<?php 
                            echo number_format( 
                                $product["price"], 
                                2 
                            ); 
                            ?> 
 
                        </div> 
 
                    </div> 
 
 
                <?php 
 
                    $rank++; 
 
                endforeach; 
 
                ?> 
 
            </div> 
 
 
        <?php else: ?> 
 
 
            <div class="no-top-products"> 
 
                📊 No sales data available yet. 
 
                <br> 
 
                Top selling products will appear 
                here after customers place orders. 
 
            </div> 
 
 
        <?php endif; ?> 
 
    </div> 
 
 
    <!-- ======================================== 
         5. STEP 9.7 
            SALES BY CATEGORY 
    ========================================= --> 
 
    <div class="dashboard-section"> 
 
        <div class="dashboard-section-title-row"> 
 
            <h2> 
                📊 Sales by Category 
            </h2> 
 
            <div class="dashboard-mini-summary"> 
                Cancelled orders excluded 
            </div> 
 
        </div> 
 
 
        <?php if ( 
            count($category_sales) > 0 
        ): ?> 
 
            <div class="category-sales-grid"> 
 
                <?php foreach ( 
                    $category_sales as $category 
                ): ?> 
 
                    <?php 
 
                    if ($max_category_sales > 0) { 
 
                        $category_width = 
                            ( 
                                $category["total_sales"] / 
                                $max_category_sales 
                            ) * 100; 
 
                    } else { 
 
                        $category_width = 0; 
                    } 
 
                    ?> 
 
                    <div class="category-sales-card"> 
 
                        <div class="category-sales-header"> 
 
                            <div class="category-sales-name"> 
 
                                <?php 
                                echo htmlspecialchars( 
                                    $category["name"] 
                                ); 
                                ?> 
 
                            </div> 
 
                            <div class="category-sales-value"> 
 
                                ₹<?php 
                                echo number_format( 
                                    $category["total_sales"], 
                                    2 
                                ); 
                                ?> 
 
                            </div> 
 
                        </div> 
 
 
                        <div class="category-sales-bar-bg"> 
 
                            <div 
                                class="category-sales-bar" 
                                style=" 
                                    width: 
                                    <?php 
                                    echo $category_width; 
                                    ?>%; 
                                " 
                            ></div> 
 
                        </div> 
 
 
                        <div class="category-sales-meta"> 
 
                            <span> 
 
                                📦 
                                <?php 
                                echo (int) 
                                    $category["total_sold"]; 
                                ?> 
                                items sold 
 
                            </span> 
 
                            <span> 
 
                                🛒 
                                <?php 
                                echo (int) 
                                    $category["total_orders"]; 
                                ?> 
                                orders 
 
                            </span> 
 
                        </div> 
 
                    </div> 
 
                <?php endforeach; ?> 
 
            </div> 
 
        <?php else: ?> 
 
            <div class="dashboard-empty"> 
 
                📊 No category sales data available yet. 
 
            </div> 
 
        <?php endif; ?> 
 
    </div> 
 
 
    <!-- ======================================== 
         6. STEP 9.8 
            CUSTOMER STATISTICS 
    ========================================= --> 
 
    <div class="dashboard-section"> 
 
        <div class="dashboard-section-title-row"> 
 
            <h2> 
                👥 Customer Statistics 
            </h2> 
 
            <a 
                href="users.php" 
                class="section-link" 
            > 
                Manage Users → 
            </a> 
 
        </div> 
 
 
        <div class="customer-stat-grid"> 
 
 
            <div class="customer-stat-card"> 
 
                <div class="customer-stat-icon"> 
                    👤 
                </div> 
 
                <div class="customer-stat-title"> 
                    Total Customers 
                </div> 
 
                <div class="customer-stat-number"> 
                    <?php echo $total_users; ?> 
                </div> 
 
                <div class="customer-stat-info"> 
                    Registered user accounts 
                </div> 
 
            </div> 
 
 
            <div class="customer-stat-card"> 
 
                <div class="customer-stat-icon"> 
                    🆕 
                </div> 
 
                <div class="customer-stat-title"> 
                    New Customers - 30 Days 
                </div> 
 
                <div class="customer-stat-number"> 
                    <?php echo $new_users; ?> 
                </div> 
 
                <div class="customer-stat-info"> 
                    Recently registered users 
                </div> 
 
            </div> 
 
 
            <div class="customer-stat-card"> 
 
                <div class="customer-stat-icon"> 
                    🛍️ 
                </div> 
 
                <div class="customer-stat-title"> 
                    Customers with Orders 
                </div> 
 
                <div class="customer-stat-number"> 
                    <?php echo $active_customers; ?> 
                </div> 
 
                <div class="customer-stat-info"> 
 
                    <?php 
                    echo number_format( 
                        $customer_order_percentage, 
                        1 
                    ); 
                    ?>% 
 
                    of registered customers 
 
                </div> 
 
            </div> 
 
 
            <div class="customer-stat-card"> 
 
                <div class="customer-stat-icon"> 
                    🔁 
                </div> 
 
                <div class="customer-stat-title"> 
                    Repeat Customers 
                </div> 
 
                <div class="customer-stat-number"> 
                    <?php echo $repeat_customers; ?> 
                </div> 
 
                <div class="customer-stat-info"> 
                    Customers with more than one order 
                </div> 
 
            </div> 
 
 
            <div class="customer-stat-card"> 
 
                <div class="customer-stat-icon"> 
                    💤 
                </div> 
 
                <div class="customer-stat-title"> 
                    Customers Without Orders 
                </div> 
 
                <div class="customer-stat-number"> 
                    <?php echo $inactive_users; ?> 
                </div> 
 
                <div class="customer-stat-info"> 
                    Registered but never ordered 
                </div> 
 
            </div> 
 
        </div> 
 
    </div> 
 
 
    <!-- ======================================== 
         7. STEP 9.9 
            NOTIFICATIONS & ALERTS 
    ========================================= --> 
 
    <div class="dashboard-section"> 
 
        <div class="dashboard-section-title-row"> 
 
            <h2> 
                🔔 Dashboard Notifications & Alerts 
            </h2> 
 
            <div class="dashboard-mini-summary"> 
 
                <?php echo $total_alerts; ?> 
 
                active alert(s) 
 
            </div> 
 
        </div> 
 
 
        <div class="dashboard-alerts"> 
 
 
            <!-- OUT OF STOCK --> 
 
            <a 
                href="products.php" 
                class="dashboard-alert" 
                style=" 
                    background:#fee2e2; 
                    border:1px solid #fecaca; 
                " 
            > 
 
                <div class="alert-icon"> 
                    🚨 
                </div> 
 
                <div 
                    class="alert-title" 
                    style="color:#b91c1c;" 
                > 
                    Out of Stock 
                </div> 
 
                <div 
                    class="alert-number" 
                    style="color:#991b1b;" 
                > 
                    <?php echo $out_of_stock; ?> 
                </div> 
 
                <div 
                    class="alert-info" 
                    style="color:#b91c1c;" 
                > 
                    Products with zero stock 
                </div> 
 
            </a> 
 
 
            <!-- LOW STOCK --> 
 
            <a 
                href="products.php" 
                class="dashboard-alert" 
                style=" 
                    background:#fff7ed; 
                    border:1px solid #fed7aa; 
                " 
            > 
 
                <div class="alert-icon"> 
                    ⚠️ 
                </div> 
 
                <div 
                    class="alert-title" 
                    style="color:#c2410c;" 
                > 
                    Low Stock 
                </div> 
 
                <div 
                    class="alert-number" 
                    style="color:#c2410c;" 
                > 
                    <?php echo $low_stock_alert; ?> 
                </div> 
 
                <div 
                    class="alert-info" 
                    style="color:#c2410c;" 
                > 
                    Products with 1-5 items 
                </div> 
 
            </a> 
 
 
            <!-- PENDING --> 
 
            <a 
                href="orders.php" 
                class="dashboard-alert" 
                style=" 
                    background:#fff8e1; 
                    border:1px solid #ffe082; 
                " 
            > 
 
                <div class="alert-icon"> 
                    ⏳ 
                </div> 
 
                <div 
                    class="alert-title" 
                    style="color:#856404;" 
                > 
                    Pending Orders 
                </div> 
 
                <div 
                    class="alert-number" 
                    style="color:#856404;" 
                > 
                    <?php echo $pending_alert; ?> 
                </div> 
 
                <div 
                    class="alert-info" 
                    style="color:#856404;" 
                > 
                    Orders waiting for processing 
                </div> 
 
            </a> 
 
 
            <!-- LAST 24 HOURS --> 
 
            <a 
                href="orders.php" 
                class="dashboard-alert" 
                style=" 
                    background:#e3f2fd; 
                    border:1px solid #90caf9; 
                " 
            > 
 
                <div class="alert-icon"> 
                    🛒 
                </div> 
 
                <div 
                    class="alert-title" 
                    style="color:#1565c0;" 
                > 
                    Last 24 Hours 
                </div> 
 
                <div 
                    class="alert-number" 
                    style="color:#1565c0;" 
                > 
                    <?php echo $recent_orders_alert; ?> 
                </div> 
 
                <div 
                    class="alert-info" 
                    style="color:#1565c0;" 
                > 
                    New orders received 
                </div> 
 
            </a> 
 
        </div> 
 
    </div> 
 
 
    <!-- ======================================== 
         8. QUICK ACTIONS 
    ========================================= --> 
 
    <div class="dashboard-section"> 
 
        <h2> 
            Quick Actions 
        </h2> 
 
        <div class="quick-actions"> 
 
            <a 
                href="products.php" 
                class="quick-action" 
            > 
                📦 Manage Products 
            </a> 
 
            <a 
                href="orders.php" 
                class="quick-action" 
            > 
                🛒 Manage Orders 
            </a> 
 
            <a 
                href="users.php" 
                class="quick-action" 
            > 
                👤 Manage Users 
            </a> 
 
        </div> 
 
    </div> 
 
 
    <!-- ======================================== 
         9. RECENT ORDERS + LOW STOCK 
    ========================================= --> 
 
    <div class="dashboard-columns"> 
 
 
        <!-- RECENT ORDERS --> 
 
        <div class="dashboard-section"> 
 
            <h2> 
 
                Recent Orders 
 
                <a 
                    href="orders.php" 
                    class="section-link" 
                > 
                    View All → 
                </a> 
 
            </h2> 
 
 
            <div class="admin-table-container"> 
 
                <table class="admin-table"> 
 
                    <thead> 
 
                        <tr> 
 
                            <th> 
                                ID 
                            </th> 
 
                            <th> 
                                Customer 
                            </th> 
 
                            <th> 
                                Amount 
                            </th> 
 
                            <th> 
                                Status 
                            </th> 
 
                            <th> 
                                Date 
                            </th> 
 
                        </tr> 
 
                    </thead> 
 
 
                    <tbody> 
 
                    <?php 
 
                    if ( 
                        $recent_orders_query && 
                        mysqli_num_rows( 
                            $recent_orders_query 
                        ) > 0 
                    ) { 
 
                        while ( 
                            $order = 
                            mysqli_fetch_assoc( 
                                $recent_orders_query 
                            ) 
                        ) { 
 
                    ?> 
 
                            <tr> 
 
                                <td> 
 
                                    #<?php 
                                    echo $order["id"]; 
                                    ?> 
 
                                </td> 
 
 
                                <td> 
 
                                    <?php 
                                    echo htmlspecialchars( 
                                        $order[ 
                                            "customer_name" 
                                        ] 
                                    ); 
                                    ?> 
 
                                </td> 
 
 
                                <td> 
 
                                    ₹<?php 
                                    echo number_format( 
                                        $order[ 
                                            "total_amount" 
                                        ], 
                                        2 
                                    ); 
                                    ?> 
 
                                </td> 
 
 
                                <td> 
 
                                    <?php 
 
                                    $status = 
                                        $order["status"]; 
 
                                    $status_class = ""; 
 
                                    if ( 
                                        $status === 
                                        "Pending" 
                                    ) { 
 
                                        $status_class = 
                                            "status-pending"; 
 
                                    /* PROCESSING STATUS - ADDED */ 
 
                                    } elseif ( 
                                        $status === 
                                        "Processing" 
                                    ) { 
 
                                        $status_class = 
                                            "status-processing"; 
 
                                    } elseif ( 
                                        $status === 
                                        "Shipped" 
                                    ) { 
 
                                        $status_class = 
                                            "status-shipped"; 
 
                                    } elseif ( 
                                        $status === 
                                        "Delivered" 
                                    ) { 
 
                                        $status_class = 
                                            "status-delivered"; 
 
                                    } elseif ( 
                                        $status === 
                                        "Cancelled" 
                                    ) { 
 
                                        $status_class = 
                                            "status-cancelled"; 
                                    } 
 
                                    ?> 
 
                                    <span 
                                        class=" 
                                            status-badge 
                                            <?php 
                                            echo $status_class; 
                                            ?> 
                                        " 
                                    > 
 
                                        <?php 
                                        echo htmlspecialchars( 
                                            $status 
                                        ); 
                                        ?> 
 
                                    </span> 
 
                                </td> 
 
 
                                <td> 
 
                                    <?php 
 
                                    if ( 
                                        !empty( 
                                            $order[ 
                                                "created_at" 
                                            ] 
                                        ) 
                                    ) { 
 
                                        echo date( 
                                            "d M Y", 
                                            strtotime( 
                                                $order[ 
                                                    "created_at" 
                                                ] 
                                            ) 
                                        ); 
 
                                    } else { 
 
                                        echo "N/A"; 
                                    } 
 
                                    ?> 
 
                                </td> 
 
                            </tr> 
 
                    <?php 
 
                        } 
 
                    } else { 
 
                    ?> 
 
                        <tr> 
 
                            <td 
                                colspan="5" 
                                style=" 
                                    text-align:center; 
                                    padding:25px; 
                                " 
                            > 
 
                                No orders found. 
 
                            </td> 
 
                        </tr> 
 
                    <?php 
                    } 
 
                    ?> 
 
                    </tbody> 
 
                </table> 
 
            </div> 
 
        </div> 
 
 
        <!-- LOW STOCK --> 
 
        <div class="dashboard-section"> 
 
            <h2> 
 
                ⚠️ Low Stock 
 
                <a 
                    href="products.php" 
                    class="section-link" 
                > 
                    Manage → 
                </a> 
 
            </h2> 
 
 
            <p 
                style=" 
                    color:#6b7280; 
                    font-size:13px; 
                    margin-top:-8px; 
                    margin-bottom:15px; 
                " 
            > 
                Products with 5 or fewer items remaining. 
            </p> 
 
 
            <div class="admin-table-container"> 
 
                <table class="admin-table"> 
 
                    <thead> 
 
                        <tr> 
 
                            <th> 
                                ID 
                            </th> 
 
                            <th> 
                                Product Name 
                            </th> 
 
                            <th> 
                                Stock 
                            </th> 
 
                        </tr> 
 
                    </thead> 
 
 
                    <tbody> 
 
                    <?php 
 
                    if ( 
                        $low_stock_products_query && 
                        mysqli_num_rows( 
                            $low_stock_products_query 
                        ) > 0 
                    ) { 
 
                        while ( 
                            $product = 
                            mysqli_fetch_assoc( 
                                $low_stock_products_query 
                            ) 
                        ) { 
 
                    ?> 
 
                            <tr> 
 
                                <td> 
 
                                    #<?php 
                                    echo $product["id"]; 
                                    ?> 
 
                                </td> 
 
 
                                <td> 
 
                                    <?php 
                                    echo htmlspecialchars( 
                                        $product["name"] 
                                    ); 
                                    ?> 
 
                                </td> 
 
 
                                <td> 
 
                                    <span 
                                        class=" 
                                        <?php 
 
                                        echo ( 
                                            $product["stock"] 
                                            <= 2 
                                        ) 
                                        ? "stock-warning" 
                                        : "stock-ok"; 
 
                                        ?> 
                                        " 
                                    > 
 
                                        <?php 
                                        echo $product["stock"]; 
                                        ?> 
 
                                    </span> 
 
                                </td> 
 
                            </tr> 
 
                    <?php 
 
                        } 
 
                    } else { 
 
                    ?> 
 
                        <tr> 
 
                            <td 
                                colspan="3" 
                                style=" 
                                    text-align:center; 
                                    padding:25px; 
                                " 
                            > 
 
                                <span 
                                    class="stock-ok" 
                                > 
                                    ✅ All products have sufficient stock. 
                                </span> 
 
                            </td> 
 
                        </tr> 
 
                    <?php 
                    } 
 
                    ?> 
 
                    </tbody> 
 
                </table> 
 
            </div> 
 
        </div> 
 
    </div> 
 
</div> 
 
 
<?php 
 
include "admin_footer.php"; 
 
?>