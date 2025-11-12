<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PRISTINE Reader Dashboard</title>
    <link rel="stylesheet" type="text/css" href="style.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
    <div class="container">
        <header>
            <div class="logo-area">
                <img src="Surespot1.png" alt="Sure-Spot Logo" class="header-logo">
                <span class="header-title">PRISTINE Reader</span>
            </div>
            <div class="filters">
                <form action="viewer.php" method="get">
                    <label for="location">Location:</label>
                    <select id="location" name="location" onchange="this.form.submit()">
                        <option value="davao" <?php echo (!isset($_GET['location']) || $_GET['location'] == 'davao') ? 'selected' : ''; ?>>Davao</option>
                        <option value="gensan" <?php echo (isset($_GET['location']) && $_GET['location'] == 'gensan') ? 'selected' : ''; ?>>Gensan</option>
                    </select>
                    <input type="hidden" name="tab" value="<?php echo htmlspecialchars($_GET['tab'] ?? 'dashboard'); ?>">
                </form>
            </div>
        </header>

        <nav class="tabs">
            <a href="?location=<?php echo $_GET['location'] ?? 'davao'; ?>&tab=dashboard" class="<?php echo ($_GET['tab'] ?? 'dashboard') == 'dashboard' ? 'active' : ''; ?>">Dashboard</a>
            <a href="?location=<?php echo $_GET['location'] ?? 'davao'; ?>&tab=unserved" class="<?php echo ($_GET['tab'] ?? '') == 'unserved' ? 'active' : ''; ?>">Unserved Orders</a> <a href="?location=<?php echo $_GET['location'] ?? 'davao'; ?>&tab=groups" class="<?php echo ($_GET['tab'] ?? '') == 'groups' ? 'active' : ''; ?>">Customer Groups</a>
            <a href="?location=<?php echo $_GET['location'] ?? 'davao'; ?>&tab=customers" class="<?php echo ($_GET['tab'] ?? '') == 'customers' ? 'active' : ''; ?>">All Customers</a>
            <a href="?location=<?php echo $_GET['location'] ?? 'davao'; ?>&tab=products" class="<?php echo ($_GET['tab'] ?? '') == 'products' ? 'active' : ''; ?>">Products</a>
            <a href="?location=<?php echo $_GET['location'] ?? 'davao'; ?>&tab=routes" class="<?php echo ($_GET['tab'] ?? '') == 'routes' ? 'active' : ''; ?>">Routes</a>
        </nav>

        <main class="content">
            <?php
            include 'db.php';
            $location = $_GET['location'] ?? 'davao';
            $current_tab = $_GET['tab'] ?? 'dashboard';
            $selected_cust_code = $_GET['cust_code'] ?? null;
            $selected_group = $_GET['group'] ?? null;
            $selected_route = $_GET['route'] ?? null;
            $selected_uninvoiced_item = $_GET['uninvoiced_item'] ?? null;

            $byroute_table = "`" . $location . '_byroute`';
            $bysku_table = "`" . $location . '_bysku`';

            // --- CUSTOMER GROUPING LOGIC (UNCHANGED) ---
            $customer_groups = [
                'ROSE PHARMACY' => 'ROSE PHARMACY', 'ROSE' => 'ROSE PHARMACY', 'ROJON' => 'Rojon Pharmacy',
                'MERCURY DRUG' => 'MERCURY DRUG', '7-ELEVEN' => '7-ELEVEN', 'HB1' => 'HB1',
                'NCCC' => 'NCCC', 'PUREGOLD' => 'PUREGOLD', 'GAISANO' => 'GAISANO',
                'FELCRIS' => 'FELCRIS', 'FARMACIA' => 'FARMACIA', 'FAR EAST' => 'FAR EAST',
                'DCCS' => 'DCCS / DAVAO CENTRAL', 'DAVAO CENTRAL WHSE' => 'DCCS / DAVAO CENTRAL', 'DAVAO CENTRAL' => 'DCCS / DAVAO CENTRAL',
                'TAGUM COMMERCIAL' => 'TAGUM COMMERCIAL'
            ];
            $excluded_words = ['DAVAO', 'THE', 'INC', 'STORE'];
            $explicit_group_case_sql = "CASE ";
            foreach ($customer_groups as $keyword => $group_name) { $explicit_group_case_sql .= "WHEN `customer_name` LIKE '" . $conn->real_escape_string($keyword) . "%' THEN '" . $conn->real_escape_string($group_name) . "' "; }
            $explicit_group_case_sql .= "ELSE 'Others' END";
            $auto_groups_query = $conn->query("SELECT `first_word` FROM (SELECT SUBSTRING_INDEX(`customer_name`, ' ', 1) AS `first_word`, `cust_code` FROM $byroute_table WHERE `customer_name` IS NOT NULL AND `customer_name` != '' AND ($explicit_group_case_sql) = 'Others') AS `sub` WHERE `first_word` NOT IN ('" . implode("','", $excluded_words) . "') AND `first_word` NOT LIKE '%-%' GROUP BY `first_word` HAVING COUNT(DISTINCT `cust_code`) > 1");
            $dynamic_groups = [];
            if ($auto_groups_query) { while ($row = $auto_groups_query->fetch_assoc()) { $dynamic_groups[] = $row['first_word']; } }
            $group_case_sql = "CASE ";
            foreach ($customer_groups as $keyword => $group_name) { $group_case_sql .= "WHEN `customer_name` LIKE '" . $conn->real_escape_string($keyword) . "%' THEN '" . $conn->real_escape_string($group_name) . "' "; }
            foreach ($dynamic_groups as $group) { $group_case_sql .= "WHEN `customer_name` LIKE '" . $conn->real_escape_string($group) . " %' THEN '" . $conn->real_escape_string($group) . "' "; }
            $group_case_sql .= "ELSE `customer_name` END";
            
            // --- QUERIES ARE NOW CLEANER (NO MORE REPLACE) ---
            if ($current_tab == 'dashboard') {
                if ($selected_uninvoiced_item) {
                    $stmt = $conn->prepare("SELECT `customer_name`, (`order_qty_ea` - `invoice_quantity`) AS unfulfilled_qty FROM $bysku_table WHERE `product_description` = ? AND `order_qty_ea` > `invoice_quantity` ORDER BY unfulfilled_qty DESC");
                    $stmt->bind_param("s", $selected_uninvoiced_item);
                    $stmt->execute();
                    $stores_with_item = $stmt->get_result();
            ?>
                    <div class="data-table card">
                        <div class="table-header"><h2>Stores with Uninvoiced: <?php echo htmlspecialchars($selected_uninvoiced_item); ?></h2><a href="?location=<?php echo $location; ?>&tab=dashboard" class="back-button">← Back to Dashboard</a></div>
                        <table>
                            <thead><tr><th>Customer Name</th><th>Uninvoiced Quantity</th></tr></thead>
                            <tbody>
                                <?php if($stores_with_item) { while($row = $stores_with_item->fetch_assoc()): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($row['customer_name']); ?></td>
                                        <td><?php echo number_format($row['unfulfilled_qty']); ?></td>
                                    </tr>
                                <?php endwhile; } ?>
                            </tbody>
                        </table>
                    </div>
            <?php
                } else {
                    $total_sales_query = $conn->query("SELECT SUM(invoice_amt) as total FROM $byroute_table WHERE invoice_status = 'Invoiced'");
                    $total_sales = $total_sales_query ? $total_sales_query->fetch_assoc()['total'] : 0;
                    $total_invoiced_qty_query = $conn->query("SELECT SUM(invoice_quantity) as total FROM $bysku_table WHERE invoice_status = 'Invoiced'");
                    $total_invoiced_qty = $total_invoiced_qty_query ? $total_invoiced_qty_query->fetch_assoc()['total'] : 0;
                    $open_orders_query = $conn->query("SELECT SUM(order_qty_ea) as total_qty, SUM(order_amt) as total_amt FROM $bysku_table WHERE order_status = 'Open'");
                    $open_orders_data = $open_orders_query ? $open_orders_query->fetch_assoc() : ['total_qty' => 0, 'total_amt' => 0];
                    $total_open_qty = $open_orders_data['total_qty'] ?? 0;
                    $total_open_amount = $open_orders_data['total_amt'] ?? 0;
                    $unserved_query = $conn->query("SELECT SUM(order_qty_ea - invoice_quantity) as total_unserved_qty, SUM((order_amt / order_qty_ea) * (order_qty_ea - invoice_quantity)) as total_unserved_amt FROM $bysku_table WHERE order_status != 'Open' AND order_qty_ea > invoice_quantity AND order_qty_ea > 0");
                    $unserved_data = $unserved_query ? $unserved_query->fetch_assoc() : ['total_unserved_qty' => 0, 'total_unserved_amt' => 0];
                    $total_unserved_qty = $unserved_data['total_unserved_qty'] ?? 0;
                    $total_unserved_amount = $unserved_data['total_unserved_amt'] ?? 0;
                    $total_orders_query = $conn->query("SELECT COUNT(DISTINCT order_number) as total FROM $byroute_table");
                    $total_orders = $total_orders_query ? $total_orders_query->fetch_assoc()['total'] : 0;
                    $avg_fill_rate_query = $conn->query("SELECT AVG(qty_fillrate) as avg_rate FROM $byroute_table WHERE invoice_status = 'Invoiced'");
                    $avg_fill_rate = $avg_fill_rate_query ? $avg_fill_rate_query->fetch_assoc()['avg_rate'] : 0;
                    $top_groups_query = $conn->query("SELECT ($group_case_sql) as customer_group, SUM(`invoice_amt`) as total_sales FROM $byroute_table WHERE invoice_status = 'Invoiced' GROUP BY customer_group HAVING customer_group != 'Others' ORDER BY total_sales DESC LIMIT 7");
                    $top_groups = [];
                    if($top_groups_query) { while($row = $top_groups_query->fetch_assoc()) { $top_groups[] = $row; } }
                    $uninvoiced_items_query = $conn->query("SELECT `product_description`, SUM(order_qty_ea - invoice_quantity) as total_unfulfilled_qty, SUM((order_amt / order_qty_ea) * (order_qty_ea - invoice_quantity)) as estimated_unfulfilled_amount FROM $bysku_table WHERE order_qty_ea > invoice_quantity AND order_qty_ea > 0 GROUP BY `product_description` HAVING total_unfulfilled_qty > 0 ORDER BY estimated_unfulfilled_amount DESC");
            ?>
                    <div class="dashboard-grid">
                        <div class="card"><h2>Total Invoiced Sales</h2><p>₱<?php echo number_format($total_sales, 2); ?></p></div>
                        <div class="card"><h2>Quantity Invoiced</h2><p><?php echo number_format($total_invoiced_qty); ?></p></div>
                        <div class="card"><h2>Unique Orders</h2><p><?php echo number_format($total_orders); ?></p></div>
                        <div class="card"><h2>Average Fill Rate</h2><p><?php echo number_format($avg_fill_rate, 2); ?>%</p></div>
                    </div>
                    <div class="charts-grid">
                         <div class="chart-container card">
                            <div class="canvas-wrapper"><canvas id="orderVsInvoiceChart"></canvas></div>
                            <div class="custom-legend-data">
                                <div class="legend-data-section"><strong>Outer Ring: Quantity</strong>
                                    <div class="legend-data-item"><span class="legend-color-box" style="background-color: #004225;"></span><span class="legend-label">Invoiced:</span><span class="legend-value"><?php echo number_format($total_invoiced_qty); ?></span></div>
                                    <div class="legend-data-item"><span class="legend-color-box" style="background-color: #D4AF37;"></span><span class="legend-label">Unserved:</span><span class="legend-value"><?php echo number_format($total_unserved_qty); ?></span></div>
                                    <div class="legend-data-item"><span class="legend-color-box" style="background-color: #8A784E;"></span><span class="legend-label">Open:</span><span class="legend-value"><?php echo number_format($total_open_qty); ?></span></div>
                                </div>
                                <div class="legend-data-section"><strong>Inner Ring: Amount</strong>
                                    <div class="legend-data-item"><span class="legend-color-box" style="background-color: #006A4E;"></span><span class="legend-label">Invoiced:</span><span class="legend-value">₱<?php echo number_format($total_sales, 2); ?></span></div>
                                    <div class="legend-data-item"><span class="legend-color-box" style="background-color: #EAC435;"></span><span class="legend-label">Unserved:</span><span class="legend-value">₱<?php echo number_format($total_unserved_amount, 2); ?></span></div>
                                    <div class="legend-data-item"><span class="legend-color-box" style="background-color: #B0A17D;"></span><span class="legend-label">Open:</span><span class="legend-value">₱<?php echo number_format($total_open_amount, 2); ?></span></div>
                                </div>
                            </div>
                         </div>
                         <div class="chart-container card"><div class="canvas-wrapper"><canvas id="topGroupsChart"></canvas></div></div>
                    </div>
                    <div class="data-table card uninvoiced-section">
                        <div class="table-header"><h2>Uninvoiced Items Summary</h2><div class="grand-total">Grand Total Uninvoiced: <span>₱<?php echo number_format($total_unserved_amount + $total_open_amount, 2); ?></span></div></div>
                        <table>
                            <thead><tr><th>Product Description (Click to see stores)</th><th>Total Uninvoiced Qty</th><th>Est. Uninvoiced Amount</th></tr></thead>
                            <tbody>
                                <?php if($uninvoiced_items_query) { while($row = $uninvoiced_items_query->fetch_assoc()): ?>
                                    <tr>
                                        <td><a href="?location=<?php echo $location; ?>&tab=dashboard&uninvoiced_item=<?php echo urlencode($row['product_description']); ?>"><?php echo htmlspecialchars($row['product_description']); ?></a></td>
                                        <td><?php echo number_format($row['total_unfulfilled_qty']); ?></td>
                                        <td><?php echo number_format($row['estimated_unfulfilled_amount'], 2); ?></td>
                                    </tr>
                                <?php endwhile; } ?>
                            </tbody>
                        </table>
                    </div>
            <?php
                }
            } elseif ($current_tab == 'unserved') { // --- ADDED: NEW UNSERVED TAB LOGIC ---
                $unserved_query = $conn->query("SELECT cust_code, customer_name, product_description, order_qty_ea as ordered, invoice_quantity as invoiced, (order_qty_ea - invoice_quantity) as unserved_qty FROM $bysku_table WHERE order_qty_ea > invoice_quantity ORDER BY customer_name, product_description");
            ?>
                <div class="data-table card">
                    <div class="table-header">
                        <h2>All Unserved Order Lines</h2>
                        <input type="text" id="tableSearch" class="table-search" onkeyup="filterTable()" placeholder="Search customers or products..">
                    </div>
                    <table id="filterableTable">
                        <thead><tr><th>Customer Name</th><th>Product Description</th><th>Qty Ordered</th><th>Qty Invoiced</th><th>Qty Unserved</th></tr></thead>
                        <tbody>
                            <?php if($unserved_query) { while($row = $unserved_query->fetch_assoc()): ?>
                                <tr>
                                    <td><a href="?location=<?php echo $location; ?>&tab=customers&cust_code=<?php echo urlencode($row['cust_code']); ?>"><?php echo htmlspecialchars($row['customer_name']); ?></a></td>
                                    <td><?php echo htmlspecialchars($row['product_description']); ?></td>
                                    <td><?php echo number_format($row['ordered']); ?></td>
                                    <td><?php echo number_format($row['invoiced']); ?></td>
                                    <td><?php echo number_format($row['unserved_qty']); ?></td>
                                </tr>
                            <?php endwhile; } ?>
                        </tbody>
                    </table>
                </div>
            <?php
            } elseif ($current_tab == 'groups') {
                 if ($selected_group) {
                    $where_sql = "";
                    $keywords_for_group = array_keys($customer_groups, $selected_group);
                    if (!empty($keywords_for_group)) { $like_clauses = []; foreach ($keywords_for_group as $keyword) { $like_clauses[] = "`customer_name` LIKE '" . $conn->real_escape_string($keyword) . "%'"; } $where_sql = "(" . implode(' OR ', $like_clauses) . ")";
                    } else { $where_sql = "`customer_name` LIKE '" . $conn->real_escape_string($selected_group) . " %'"; }
                    $group_members_query = $conn->query("SELECT `cust_code`, `customer_name`, COUNT(DISTINCT order_number) as order_count, SUM(`invoice_amt`) as total_spent FROM $byroute_table WHERE $where_sql GROUP BY `cust_code`, `customer_name` ORDER BY total_spent DESC");
            ?>
                    <div class="data-table card">
                         <div class="table-header"><h2>Branches in Group: <?php echo htmlspecialchars($selected_group); ?></h2><a href="?location=<?php echo $location; ?>&tab=groups" class="back-button">← Back to Group List</a></div>
                        <table>
                            <thead><tr><th>Branch Name</th><th>Total Orders</th><th>Total Invoiced Sales (PHP)</th></tr></thead>
                            <tbody>
                                <?php if($group_members_query) { while($row = $group_members_query->fetch_assoc()): ?>
                                    <tr>
                                        <td><a href="?location=<?php echo $location; ?>&tab=customers&cust_code=<?php echo urlencode($row['cust_code']); ?>"><?php echo htmlspecialchars($row['customer_name']); ?></a></td>
                                        <td><?php echo $row['order_count']; ?></td>
                                        <td><?php echo number_format($row['total_spent'], 2); ?></td>
                                    </tr>
                                <?php endwhile; } ?>
                            </tbody>
                        </table>
                    </div>
            <?php
                 } else {
                    $groups_query = $conn->query("SELECT ($group_case_sql) as customer_group, COUNT(DISTINCT cust_code) as branch_count, MAX(cust_code) as cust_code, SUM(`invoice_amt`) as total_sales FROM $byroute_table GROUP BY customer_group ORDER BY total_sales DESC");
            ?>
                    <div class="data-table card">
                        <h2>Customer Ranking by Sales</h2>
                        <table>
                            <thead><tr><th>Group / Customer Name</th><th>Branches</th><th>Total Invoiced Sales (PHP)</th></tr></thead>
                            <tbody>
                                <?php if($groups_query) { while($row = $groups_query->fetch_assoc()): ?>
                                    <tr>
                                        <td>
                                            <?php if ($row['branch_count'] > 1): ?>
                                                <a href="?location=<?php echo $location; ?>&tab=groups&group=<?php echo urlencode($row['customer_group']); ?>"><?php echo htmlspecialchars($row['customer_group']); ?></a>
                                            <?php else: ?>
                                                <a href="?location=<?php echo $location; ?>&tab=customers&cust_code=<?php echo urlencode($row['cust_code']); ?>"><?php echo htmlspecialchars($row['customer_group']); ?></a>
                                            <?php endif; ?>
                                        </td>
                                        <td><?php echo $row['branch_count']; ?></td>
                                        <td><?php echo number_format($row['total_sales'], 2); ?></td>
                                    </tr>
                                <?php endwhile; } ?>
                            </tbody>
                        </table>
                    </div>
            <?php
                 }
            } elseif ($current_tab == 'customers') {
                if ($selected_cust_code) {
                    // --- MODIFIED: Using prepared statements for security ---
                    $stmt = $conn->prepare("SELECT `customer_name` FROM $byroute_table WHERE `cust_code` = ? LIMIT 1"); $stmt->bind_param("s", $selected_cust_code); $stmt->execute();
                    $customer_name = $stmt->get_result()->fetch_assoc()['customer_name'] ?? 'Unknown Customer'; $stmt->close();
                    $details_stmt = $conn->prepare("SELECT `order_number`, `order_date`, `product_description`, `order_qty_ea` as ordered, `invoice_quantity` as invoiced, `invoice_amt` FROM $bysku_table WHERE `cust_code` = ? ORDER BY `order_date` DESC, `order_number`");
                    $details_stmt->bind_param("s", $selected_cust_code); $details_stmt->execute(); $customer_orders = $details_stmt->get_result();
            ?>
                    <div class="data-table card">
                        <div class="table-header"><h2>Order Details for: <?php echo htmlspecialchars($customer_name); ?></h2><a href="javascript:history.back()" class="back-button">← Go Back</a></div>
                        <table>
                            <thead><tr><th>Order Number</th><th>Order Date</th><th>Product</th><th>Qty Ordered</th><th>Qty Invoiced</th><th>Invoiced Amount</th></tr></thead>
                            <tbody>
                                <?php if($customer_orders) { while($row = $customer_orders->fetch_assoc()): ?>
                                    <tr class="<?php echo ($row['ordered'] > $row['invoiced']) ? 'unfulfilled' : ''; ?>">
                                        <td><?php echo htmlspecialchars($row['order_number']); ?></td><td><?php echo htmlspecialchars($row['order_date']); ?></td>
                                        <td><?php echo htmlspecialchars($row['product_description']); ?></td>
                                        <td><?php echo number_format($row['ordered']); ?></td>
                                        <td><?php echo number_format($row['invoiced']); ?></td>
                                        <td><?php echo number_format($row['invoice_amt'], 2); ?></td>
                                    </tr>
                                <?php endwhile; } ?>
                            </tbody>
                        </table>
                    </div>
            <?php
                } else {
                    $customers_query = $conn->query("SELECT `cust_code`, `customer_name`, COUNT(DISTINCT order_number) as order_count, SUM(`invoice_amt`) as total_spent FROM $byroute_table WHERE `customer_name` IS NOT NULL AND `customer_name` != '' GROUP BY `cust_code`, `customer_name` ORDER BY total_spent DESC LIMIT 200");
            ?>
                    <div class="data-table card">
                        <div class="table-header">
                            <h2>All Customers Summary (Top 200 by Sales)</h2>
                             <input type="text" id="tableSearch" class="table-search" onkeyup="filterTable()" placeholder="Search for customers..">
                        </div>
                        <table id="filterableTable">
                            <thead><tr><th>Customer Name</th><th>Total Orders</th><th>Total Invoiced Sales (PHP)</th></tr></thead>
                            <tbody>
                                <?php if($customers_query) { while($row = $customers_query->fetch_assoc()): ?>
                                    <tr>
                                        <td><a href="?location=<?php echo $location; ?>&tab=customers&cust_code=<?php echo urlencode($row['cust_code']); ?>"><?php echo htmlspecialchars($row['customer_name']); ?></a></td>
                                        <td><?php echo $row['order_count']; ?></td>
                                        <td><?php echo number_format($row['total_spent'], 2); ?></td>
                                    </tr>
                                <?php endwhile; } ?>
                            </tbody>
                        </table>
                    </div>
            <?php
                }
            } elseif ($current_tab == 'products') {
                 $products_query = $conn->query("SELECT `product_description`, SUM(`order_qty_ea`) as total_ordered, SUM(`invoice_quantity`) as total_invoiced, SUM(`invoice_amt`) as total_sales FROM $bysku_table WHERE `product_description` IS NOT NULL AND `product_description` != '' GROUP BY `product_description` ORDER BY total_sales DESC");
            ?>
                 <div class="data-table card">
                    <div class="table-header">
                        <h2>Product Summary</h2>
                        <input type="text" id="tableSearch" class="table-search" onkeyup="filterTable()" placeholder="Search for products..">
                    </div>
                    <table id="filterableTable">
                        <thead><tr><th>Product Description</th><th>Total Ordered</th><th>Total Invoiced</th><th>Total Invoiced Sales (PHP)</th></tr></thead>
                        <tbody>
                             <?php if($products_query) { while($row = $products_query->fetch_assoc()): ?>
                                <tr class="<?php echo ($row['total_ordered'] > $row['total_invoiced']) ? 'unfulfilled' : ''; ?>">
                                    <td><?php echo htmlspecialchars($row['product_description']); ?></td>
                                    <td><?php echo number_format($row['total_ordered']); ?></td>
                                    <td><?php echo number_format($row['total_invoiced']); ?></td>
                                    <td><?php echo number_format($row['total_sales'], 2); ?></td>
                                </tr>
                            <?php endwhile; } ?>
                        </tbody>
                    </table>
                </div>
            <?php } elseif ($current_tab == 'routes') {
                if ($selected_route) {
                    $stmt = $conn->prepare("SELECT `cust_code`, `customer_name`, SUM(`invoice_amt`) as total_spent FROM $byroute_table WHERE `route_name` = ? GROUP BY `cust_code`, `customer_name` ORDER BY total_spent DESC");
                    $stmt->bind_param("s", $selected_route); $stmt->execute();
                    $route_customers = $stmt->get_result();
            ?>
                    <div class="data-table card">
                        <div class="table-header"><h2>Customers in Route: <?php echo htmlspecialchars($selected_route); ?></h2><a href="?location=<?php echo $location; ?>&tab=routes" class="back-button">← Back to Route List</a></div>
                        <table>
                            <thead><tr><th>Customer Name</th><th>Total Invoiced Sales (PHP)</th></tr></thead>
                            <tbody>
                                <?php if($route_customers) { while($row = $route_customers->fetch_assoc()): ?>
                                    <tr>
                                        <td><a href="?location=<?php echo $location; ?>&tab=customers&cust_code=<?php echo urlencode($row['cust_code']); ?>"><?php echo htmlspecialchars($row['customer_name']); ?></a></td>
                                        <td><?php echo number_format($row['total_spent'], 2); ?></td>
                                    </tr>
                                <?php endwhile; } ?>
                            </tbody>
                        </table>
                    </div>
            <?php
                } else {
                    $routes_query = $conn->query("SELECT `route_name`, COUNT(DISTINCT `cust_code`) as customer_count, SUM(`invoice_amt`) as total_sales FROM $byroute_table WHERE `route_name` IS NOT NULL AND `route_name` != '' GROUP BY `route_name` ORDER BY total_sales DESC");
            ?>
                    <div class="data-table card">
                        <h2>Route Summary (Click name for details)</h2>
                        <table>
                            <thead><tr><th>Route Name</th><th>Unique Customers</th><th>Total Invoiced Sales (PHP)</th></tr></thead>
                            <tbody>
                                <?php if($routes_query) { while($row = $routes_query->fetch_assoc()): ?>
                                    <tr>
                                        <td><a href="?location=<?php echo $location; ?>&tab=routes&route=<?php echo urlencode($row['route_name']); ?>"><?php echo htmlspecialchars($row['route_name']); ?></a></td>
                                        <td><?php echo $row['customer_count']; ?></td>
                                        <td><?php echo number_format($row['total_sales'], 2); ?></td>
                                    </tr>
                                <?php endwhile; } ?>
                            </tbody>
                        </table>
                    </div>
            <?php
                }
            } ?>
        </main>
    </div>

    <script>
        // Data for Charts
        const totalInvoicedQty = <?php echo $total_invoiced_qty ?? 0; ?>;
        const totalUnservedQty = <?php echo $total_unserved_qty ?? 0; ?>;
        const totalOpenQty = <?php echo $total_open_qty ?? 0; ?>;
        const totalInvoicedSales = <?php echo $total_sales ?? 0; ?>;
        const totalUnservedSales = <?php echo $total_unserved_amount ?? 0; ?>;
        const totalOpenSales = <?php echo $total_open_amount ?? 0; ?>;
        const topGroupsData = <?php echo json_encode($top_groups ?? []); ?>;

        document.addEventListener('DOMContentLoaded', function() {
            const fontColor = '#8A784E'; const titleColor = '#004225';
            if (document.getElementById('orderVsInvoiceChart')) {
                new Chart(document.getElementById('orderVsInvoiceChart').getContext('2d'), { 
                    type: 'doughnut',
                    data: { labels: ['Invoiced', 'Unserved', 'Open'], datasets: [{ label: 'Quantity', data: [totalInvoicedQty, totalUnservedQty, totalOpenQty], backgroundColor: ['#004225', '#D4AF37', '#8A784E'], cutout: '70%'},{ label: 'Amount', data: [totalInvoicedSales, totalUnservedSales, totalOpenSales], backgroundColor: ['#006A4E', '#EAC435', '#B0A17D'], cutout: '0%'}]}, 
                    options: { responsive: true, maintainAspectRatio: false, plugins: { title: { display: true, text: 'Order Status Breakdown', color: titleColor, font: { size: 16 } }, legend: { display: false }, tooltip: { callbacks: { label: function(context) { let label = context.dataset.label || ''; if (label) { label += ': '; } let value = context.raw; if (context.datasetIndex === 1) { label = 'Amount: ₱'; value = value.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 }); } else { label = 'Qty: '; value = value.toLocaleString('en-US'); } return context.label + ': ' + label + value;}}}}} 
                });
            }
            if (document.getElementById('topGroupsChart')) {
                new Chart(document.getElementById('topGroupsChart').getContext('2d'), { type: 'bar', data: { labels: topGroupsData.map(g => g.customer_group), datasets: [{ label: 'Total Invoiced Sales (PHP)', data: topGroupsData.map(g => g.total_sales), backgroundColor: '#004225', borderColor: '#D4AF37', borderWidth: 2 }] }, options: { responsive: true, maintainAspectRatio: false, plugins: { title: { display: true, text: 'Top Customer Groups by Invoiced Sales', color: titleColor, font: { size: 16 } }, legend: { display: false } }, scales: { x: { ticks: { color: fontColor } }, y: { ticks: { color: fontColor } } } } });
            }
        });

        // --- ADDED: Live search filter function ---
        function filterTable() {
            const input = document.getElementById("tableSearch");
            const filter = input.value.toUpperCase();
            const table = document.getElementById("filterableTable");
            const tr = table.getElementsByTagName("tr");

            for (let i = 1; i < tr.length; i++) { // Start from 1 to skip header row
                let tdArray = tr[i].getElementsByTagName("td");
                let textValue = "";
                for (let j = 0; j < tdArray.length; j++) {
                    textValue += tdArray[j].textContent || tdArray[j].innerText;
                }
                if (textValue.toUpperCase().indexOf(filter) > -1) {
                    tr[i].style.display = "";
                } else {
                    tr[i].style.display = "none";
                }
            }
        }
    </script>
</body>
</html>