<?php
session_start();

// If a user isn't logged in, we explicitly define them as a 'viewer'.
if (!isset($_SESSION['user_logged_in']) || $_SESSION['user_logged_in'] !== true) {
    $_SESSION['role'] = 'viewer';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <script>
        window.userRole = <?php echo json_encode($_SESSION['role'] ?? 'viewer'); ?>;
    </script>
    <title>Inventory Management</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.1/chart.umd.min.js"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" type="text/css" href="https://cdn.jsdelivr.net/npm/toastify-js/src/toastify.min.css">
    <link rel="stylesheet" href="style.css">
    <link href="https://fonts.googleapis.com/css2?family=Lato:wght@400;700;900&display=swap" rel="stylesheet">
</head>
<body id="app-body" class="bg-slate-100">

    <div id="loading-overlay" class="modal-backdrop" style="display: none; z-index: 9999;">
        <div class="animate-spin rounded-full h-16 w-16 border-t-2 border-b-2 border-white"></div>
    </div>

    <?php include 'header.php'; ?>

    <main id="main-content" class="flex-1 p-4 sm:p-6 lg:p-8">
        <div id="page-content-wrapper" class="max-w-7xl mx-auto">
            <?php
                // Main Navigation Pages
                include 'pages/dashboard.php';
                include 'pages/stocks_dashboard.php';
                include 'pages/order_book.php';
                include 'pages/unserved.php';
                include 'pages/fulfillable.php';

                // Role-Specific Pages
                include 'pages/encoder.php';
                include 'pages/admin.php';
            ?>
        </div>
    </main>

    <?php include 'components/modals.php'; ?>
    
    <script type="text/javascript" src="https://cdn.jsdelivr.net/npm/toastify-js"></script>
    <script type="module" src="js/main.js"></script>
    <script src="js/global.js" defer></script>
</body>
</html>