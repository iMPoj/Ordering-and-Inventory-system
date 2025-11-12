<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$currentPage = basename($_SERVER['PHP_SELF']);
$user_role = $_SESSION['role'] ?? 'viewer';
?>
<header id="app-header" class="bg-white shadow-md sticky top-0 z-40">
    <div class="container mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex items-center justify-between h-16">
            <div class="flex items-center gap-3">
                <img src="reckitt-logo.png" alt="Reckitt Logo" class="h-8 w-auto">
                <a href="index.php" class="font-bold text-xl text-slate-700 tracking-wider">Inventory</a>
            </div>

            <nav class="hidden lg:flex lg:space-x-8">
                <a href="index.php#dashboard" data-tab="dashboard" class="nav-link">Dashboard</a>
                <a href="index.php#stocksDashboard" data-tab="stocksDashboard" class="nav-link">Stocks</a>
                <a href="product_search.php" class="nav-link <?php echo ($currentPage == 'product_search.php') ? 'active' : ''; ?>">Product Search</a>
                <a href="index.php#orderBook" data-tab="orderBook" class="nav-link">Order Book</a>
                <div class="relative">
                    <button id="more-links-btn" class="nav-link flex items-center">
                        More <svg class="w-4 h-4 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                    </button>
                    <div id="more-links-dropdown" class="absolute right-0 mt-2 w-48 bg-white rounded-md shadow-lg py-1 hidden">
                        <a href="index.php#unserved" data-tab="unserved" class="dropdown-link">Unserved Orders</a>
                        <a href="index.php#fulfillable" data-tab="fulfillable" class="dropdown-link">Fulfillable</a>
                    </div>
                </div>
            </nav>

            <div class="flex items-center space-x-2">
                <div class="hidden lg:flex items-center space-x-2">
                    <?php if (in_array($user_role, ['admin', 'encoder'])): ?>
                        <a href="create_order.php" class="btn btn-secondary">Create Order</a>
                    <?php endif; ?>
                    <?php if ($user_role === 'admin'): ?>
                        <a href="index.php#admin" class="btn btn-secondary">Admin</a>
                    <?php endif; ?>
                </div>
                
                <?php if (isset($_SESSION['user_logged_in']) && $_SESSION['user_logged_in'] === true): ?>
                     <a href="logout.php" class="btn btn-primary">Logout</a>
                <?php else: ?>
                    <a href="login.php" class="btn btn-primary">Login</a>
                <?php endif; ?>
                
                <div class="lg:hidden">
                    <button id="mobile-menu-button" class="inline-flex items-center justify-center p-2 rounded-md text-slate-400 hover:text-white hover:bg-slate-700">
                        <svg class="h-6 w-6" stroke="currentColor" fill="none" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path></svg>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <div id="mobile-menu" class="lg:hidden hidden">
        <div class="px-2 pt-2 pb-3 space-y-1 sm:px-3">
            <a href="index.php#dashboard" data-tab="dashboard" class="nav-link-mobile">Dashboard</a>
            <a href="index.php#stocksDashboard" data-tab="stocksDashboard" class="nav-link-mobile">Stocks</a>
            <a href="product_search.php" class="nav-link-mobile <?php echo ($currentPage == 'product_search.php') ? 'active' : ''; ?>">Product Search</a>
            <a href="index.php#orderBook" data-tab="orderBook" class="nav-link-mobile">Order Book</a>
            <a href="index.php#unserved" data-tab="unserved" class="nav-link-mobile">Unserved Orders</a>
            <a href="index.php#fulfillable" data-tab="fulfillable" class="nav-link-mobile">Fulfillable</a>
            
            <div class="border-t border-slate-200 pt-4 mt-4 space-y-2">
                <?php if (in_array($user_role, ['admin', 'encoder'])): ?>
                    <a href="create_order.php" class="nav-link-mobile">Create Order</a>
                <?php endif; ?>
                <?php if ($user_role === 'admin'): ?>
                    <a href="index.php#admin" class="nav-link-mobile">Admin</a>
                <?php endif; ?>

                <?php if (isset($_SESSION['user_logged_in']) && $_SESSION['user_logged_in'] === true): ?>
                    <a href="logout.php" class="nav-link-mobile w-full text-left bg-red-50 text-red-700">Logout</a>
                <?php else: ?>
                    <a href="login.php" class="nav-link-mobile w-full text-left bg-emerald-50 text-emerald-700">Login</a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</header>