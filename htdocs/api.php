<?php

date_default_timezone_set('Asia/Manila');
// --- DEBUG MODE ---
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
ini_set('max_execution_time', 300);

// --- ROBUST ERROR HANDLER ---
set_error_handler(function($severity, $message, $file, $line) {
    if (!(error_reporting() & $severity)) { return; }
    throw new ErrorException($message, 0, $severity, $file, $line);
});

session_start();
header('Content-Type: application/json');

// --- WRAP ENTIRE APPLICATION IN A TRY...CATCH BLOCK ---
try {
    require __DIR__ . '/db_connect.php';
    $action = $_POST['action'] ?? $_GET['action'] ?? '';

    $viewer_actions = [
        'get_products', 'get_customers', 'get_orders', 'get_unserved_orders', 
        'get_dashboard_data', 'get_customer_dashboard_data', 'get_fulfillable_items',
        'get_rojon_dashboard_data', 
        'get_rojon_orders',
        'get_stock_for_product',
        'find_product_with_best_sku',
        'search_pos_by_product',
        'get_address_by_code',
        'get_address_suggestions'
    ];

    $public_actions = ['login', 'logout'];
    $user_role = $_SESSION['role'] ?? 'viewer';

    if (!in_array($action, $public_actions) && $user_role === 'viewer') {
        if (!in_array($action, $viewer_actions)) {
            http_response_code(401);
            echo json_encode(['success' => false, 'message' => 'Login required for this action.']);
            exit;
        }
    } else if (!in_array($action, $public_actions) && (!isset($_SESSION['user_logged_in']) || $_SESSION['user_logged_in'] !== true)) {
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => 'Authentication required.']);
        exit;
    }

    $admin_only_actions = [
        'get_orders_for_export', 'add_customer', 'delete_customer', 'add_product', 
        'bulk_add_products', 'bulk_update_stock', 'delete_code', 'bulk_add_aliases', 
        'toggle_customer_priority', 'get_unlinked_skus',
        'delete_order',
        'get_address_codes', 'add_address_code', 'update_address_code', 'delete_address_code',
        'get_monthly_targets', 'set_monthly_targets',
        'toggle_pristine_status',
        'set_display_month'
    ];
    
    if (in_array($action, $admin_only_actions) && (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin')) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Admin permission required.']);
        exit;
    }

    // --- Main Router ---
    switch ($action) {
        case 'login': login($pdo); break;
        case 'logout': logout(); break;
        case 'get_products': getProducts($pdo); break;
        case 'get_customers': getCustomers($pdo); break;
        case 'get_orders': getOrders($pdo); break;
        case 'get_unserved_orders': getUnservedOrders($pdo); break;
        case 'get_order_details': getOrderDetails($pdo); break;
        case 'update_order_items': updateOrderItems($pdo); break;
        case 'add_order': addOrder($pdo); break;
        case 'get_dashboard_data': getDashboardData($pdo); break;
        case 'get_customer_dashboard_data': getCustomerDashboardData($pdo); break;
        case 'get_fulfillable_items': getFulfillableItems($pdo); break;
        case 'get_orders_for_export': getOrdersForExport($pdo); break;
        case 'add_customer': addCustomer($pdo); break;
        case 'delete_customer': deleteCustomer($pdo); break;
        case 'toggle_customer_priority': toggleCustomerPriority($pdo); break;
        case 'add_product': addProduct($pdo); break;
        case 'bulk_add_products': bulkAddProducts($pdo); break;
        case 'bulk_update_stock': bulkUpdateStock($pdo); break;
        case 'delete_code': deleteCode($pdo); break;
        case 'bulk_add_aliases': bulkAddAliases($pdo); break;
        case 'get_unlinked_skus': getUnlinkedSkus($pdo); break;
        case 'delete_order': deleteOrder($pdo); break;
        case 'get_rojon_dashboard_data': getRojonDashboardData($pdo); break;
        case 'get_rojon_orders': getRojonOrders($pdo); break;
        case 'get_address_codes': getAddressCodes($pdo); break;
        case 'add_address_code': addAddressCode($pdo); break;
        case 'update_address_code': updateAddressCode($pdo); break;
        case 'delete_address_code': deleteAddressCode($pdo); break;
        case 'get_monthly_targets': getMonthlyTargets($pdo); break;
        case 'set_monthly_targets': setMonthlyTargets($pdo); break;
        case 'get_stock_for_product': get_stock_for_product($pdo); break;
        case 'toggle_pristine_status': toggle_pristine_status($pdo); break;
        case 'find_product_with_best_sku': find_product_with_best_sku($pdo); break;
        case 'search_pos_by_product': search_pos_by_product($pdo); break;
        case 'get_address_by_code': get_address_by_code($pdo); break;
        case 'get_product_suggestions': get_product_suggestions($pdo); break;
        case 'set_display_month': set_display_month($pdo); break;
        case 'get_address_suggestions': getAddressSuggestions($pdo); break;
        default: echo json_encode(['success' => false, 'message' => 'Invalid action specified']);
    }

} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Server Error: ' . $e->getMessage() . ' in ' . $e->getFile() . ' on line ' . $e->getLine()]);
}

// --- FUNCTIONS ---

function login($pdo) {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    if (empty($username) || empty($password)) { throw new Exception('Username and password are required.'); }
    $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
    $stmt->execute([$username]);
    $user = $stmt->fetch();
    if ($user && password_verify($password, $user['password_hash'])) {
        session_regenerate_id(true); $_SESSION['user_logged_in'] = true; $_SESSION['user_id'] = $user['id']; $_SESSION['username'] = $user['username']; $_SESSION['role'] = $user['role'];
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Invalid username or password.']);
    }
    exit;
}

function logout() {
    $_SESSION = array();
    session_destroy();
    echo json_encode(['success' => true, 'message' => 'Logged out successfully.']);
    exit;
}

function getCustomers($pdo) {
    $stmt = $pdo->query("SELECT id, name, is_priority, default_discount FROM customers ORDER BY name");
    $customers = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode(['success' => true, 'data' => $customers]);
    exit;
}

function getProducts($pdo) {
    $sql = "SELECT p.id, p.description, p.bu, p.is_promo, pc.code, pc.type, pc.pieces_per_case, pc.sales_price, il.location, il.stock FROM products p JOIN product_codes pc ON p.id = pc.product_id LEFT JOIN inventory_levels il ON pc.code = il.product_code ORDER BY p.description, pc.code";
    $stmt = $pdo->query($sql);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $productsById = [];
    foreach ($rows as $row) {
        $productId = $row['id'];
        if (!isset($productsById[$productId])) { $productsById[$productId] = ['id' => (int)$row['id'], 'description' => $row['description'], 'bu' => $row['bu'], 'is_promo' => (bool)$row['is_promo'], 'codes' => []]; }
        $code = $row['code'];
        $codeIndex = -1;
        foreach ($productsById[$productId]['codes'] as $idx => $existingCode) { if ($existingCode['code'] === $code) { $codeIndex = $idx; break; } }
        if ($codeIndex === -1) {
            $productsById[$productId]['codes'][] = ['code' => $code, 'type' => $row['type'], 'pieces_per_case' => (int)$row['pieces_per_case'], 'sales_price' => (float)$row['sales_price'], 'inventory' => []];
            $codeIndex = count($productsById[$productId]['codes']) - 1;
        }
        if ($row['location'] !== null) { $productsById[$productId]['codes'][$codeIndex]['inventory'][] = ['location' => $row['location'], 'stock' => (int)$row['stock']]; }
    }
    echo json_encode(['success' => true, 'data' => array_values($productsById)]);
    exit;
}

function getOrders($pdo) {
    $page = isset($_POST['page']) ? (int)$_POST['page'] : 1;
    $limit = isset($_POST['limit']) ? (int)$_POST['limit'] : 20;
    $offset = ($page - 1) * $limit;
    $whereClauses = [];
    $params = [];
    $month = $_POST['month'] ?? date('m'); 
    $year = $_POST['year'] ?? date('Y');
    if ($month !== 'all') {
        if (!empty($year)) {
            $whereClauses[] = "YEAR(o.order_date) = ?";
            $params[] = $year;
        }
        if (!empty($month)) {
            $whereClauses[] = "MONTH(o.order_date) = ?";
            $params[] = $month;
        }
    }
    if (!empty($_POST['po_number'])) { $whereClauses[] = "o.po_number LIKE ?"; $params[] = '%' . $_POST['po_number'] . '%'; }
    if (!empty($_POST['address'])) { $whereClauses[] = "o.customer_address LIKE ?"; $params[] = '%' . $_POST['address'] . '%'; }
    if (!empty($_POST['location']) && $_POST['location'] !== 'all') { $whereClauses[] = "o.location = ?"; $params[] = $_POST['location']; }
    if (!empty($_POST['customer']) && $_POST['customer'] !== 'all') { $whereClauses[] = "c.name = ?"; $params[] = $_POST['customer']; }
    if (!empty($_POST['bu']) && $_POST['bu'] !== 'all') { $whereClauses[] = "o.bu = ?"; $params[] = $_POST['bu']; }
    if (!empty($_POST['so_number'])) { $whereClauses[] = "o.so_number LIKE ?"; $params[] = '%' . $_POST['so_number'] . '%'; }
    $whereSql = empty($whereClauses) ? '' : 'WHERE ' . implode(' AND ', $whereClauses);
    $countSql = "SELECT COUNT(DISTINCT o.id) FROM orders o LEFT JOIN customers c ON o.customer_id = c.id $whereSql";
    $countStmt = $pdo->prepare($countSql);
    $countStmt->execute($params);
    $totalOrders = $countStmt->fetchColumn();
    $orderIdSql = "SELECT o.id FROM orders o LEFT JOIN customers c ON o.customer_id = c.id $whereSql ORDER BY o.order_date DESC, o.id ASC LIMIT ? OFFSET ?";
    $orderIdStmt = $pdo->prepare($orderIdSql);
    $paramIndex = 1;
    foreach ($params as $value) {
        $orderIdStmt->bindValue($paramIndex++, $value);
    }
    $orderIdStmt->bindValue($paramIndex++, $limit, PDO::PARAM_INT);
    $orderIdStmt->bindValue($paramIndex++, $offset, PDO::PARAM_INT);
    $orderIdStmt->execute();
    $orderIds = $orderIdStmt->fetchAll(PDO::FETCH_COLUMN);
    if (empty($orderIds)) {
        echo json_encode(['success' => true, 'data' => [], 'total_orders' => 0, 'pagination' => ['totalPages' => 0]]);
        exit;
    }
    $placeholders = implode(',', array_fill(0, count($orderIds), '?'));
    $sql = "SELECT 
                o.id, o.po_number, o.order_date, o.location, o.bu, o.customer_address, o.is_pristine_checked,
                c.name as customer_name,
                (SELECT SUM(oi.price) FROM order_items oi WHERE oi.order_id = o.id) as total_value
            FROM orders o 
            LEFT JOIN customers c ON o.customer_id = c.id 
            WHERE o.id IN ($placeholders) 
            ORDER BY FIELD(o.id, " . implode(',', $orderIds) . ")";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($orderIds);
    $finalOrders = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode([
        'success' => true, 
        'data' => $finalOrders, 
        'total_orders' => (int)$totalOrders,
        'pagination' => [
            'total' => (int)$totalOrders,
            'page' => $page,
            'limit' => $limit,
            'totalPages' => ceil($totalOrders / $limit)
        ]
    ]);
    exit;
}

function getUnservedOrders($pdo) {
    $page = isset($_POST['page']) ? (int)$_POST['page'] : 1;
    $limit = isset($_POST['limit']) ? (int)$_POST['limit'] : 50;
    $offset = ($page - 1) * $limit;
    $whereClauses = ["oi.status = 'unserved'"];
    $params = [];
    if (!empty($_POST['location']) && $_POST['location'] !== 'all') { $whereClauses[] = "o.location = ?"; $params[] = $_POST['location']; }
    if (!empty($_POST['bu']) && $_POST['bu'] !== 'all') { $whereClauses[] = "o.bu = ?"; $params[] = $_POST['bu']; }
    if (!empty($_POST['customer']) && $_POST['customer'] !== 'all') { $whereClauses[] = "c.name = ?"; $params[] = $_POST['customer']; }
    if (!empty($_POST['sku'])) {
        $whereClauses[] = "oi.sku LIKE ?";
        $params[] = '%' . $_POST['sku'] . '%';
    }
    $whereSql = 'WHERE ' . implode(' AND ', $whereClauses);
    $countSql = "SELECT COUNT(DISTINCT o.id) FROM orders o JOIN order_items oi ON o.id = oi.order_id LEFT JOIN customers c ON o.customer_id = c.id $whereSql";
    $countStmt = $pdo->prepare($countSql);
    $countStmt->execute($params);
    $totalOrders = $countStmt->fetchColumn();
    $orderIdSql = "SELECT DISTINCT o.id FROM orders o JOIN order_items oi ON o.id = oi.order_id LEFT JOIN customers c ON o.customer_id = c.id $whereSql ORDER BY o.order_date DESC LIMIT ? OFFSET ?";
    $orderIdStmt = $pdo->prepare($orderIdSql);
    $orderIdStmt->execute(array_merge($params, [$limit, $offset]));
    $orderIds = $orderIdStmt->fetchAll(PDO::FETCH_COLUMN);
    if (empty($orderIds)) {
        echo json_encode(['success' => true, 'data' => [], 'total_orders' => 0]);
        exit;
    }
    $placeholders = implode(',', array_fill(0, count($orderIds), '?'));
    $sql = "SELECT o.id, o.po_number, o.order_date, o.location, o.bu, c.name as customer_name, oi.id as item_id, oi.sku, oi.description, oi.quantity, oi.price, oi.status FROM orders o JOIN customers c ON o.customer_id = c.id JOIN order_items oi ON o.id = oi.order_id WHERE o.id IN ($placeholders) ORDER BY o.order_date DESC, o.id ASC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($orderIds);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $ordersById = [];
    foreach ($rows as $row) {
        $orderId = $row['id'];
        if (!isset($ordersById[$orderId])) {
            $ordersById[$orderId] = [ 'id' => $orderId, 'location' => $row['location'], 'bu' => $row['bu'], 'customer' => ['name' => $row['customer_name'], 'poNumber' => $row['po_number']], 'date' => $row['order_date'], 'items' => [] ];
        }
        $ordersById[$orderId]['items'][] = $row;
    }
    $sortedOrders = [];
    foreach($orderIds as $id){ if(isset($ordersById[$id])){ $sortedOrders[] = $ordersById[$id]; } }
    echo json_encode(['success' => true, 'data' => $sortedOrders, 'total_orders' => $totalOrders]);
    exit;
}

function getOrderDetails($pdo) {
    $orderId = $_POST['id'] ?? 0;
    if (empty($orderId)) { throw new Exception('Order ID is required.'); }
    $orderStmt = $pdo->prepare("SELECT o.*, c.name as customer_name FROM orders o LEFT JOIN customers c ON o.customer_id = c.id WHERE o.id = ?");
    $orderStmt->execute([$orderId]); $order = $orderStmt->fetch(PDO::FETCH_ASSOC);
    if (!$order) { throw new Exception('Order not found.'); }
    $itemStmt = $pdo->prepare("SELECT * FROM order_items WHERE order_id = ?");
    $itemStmt->execute([$orderId]); $items = $itemStmt->fetchAll(PDO::FETCH_ASSOC);
    $result = ['id' => $order['id'], 'location' => $order['location'], 'bu' => $order['bu'], 'discount' => (float)$order['discount_percentage'], 'customer' => ['name' => $order['customer_name'] ?? 'N/A', 'address' => $order['customer_address'], 'poNumber' => $order['po_number']], 'date' => $order['order_date'], 'items' => $items];
    echo json_encode(['success' => true, 'data' => $result]);
    exit;
}
function getFulfillableItems($pdo) {
    // 1. Get the selected dashboard month and year from settings
    $settings_stmt = $pdo->query("SELECT setting_key, setting_value FROM settings WHERE setting_key IN ('display_month', 'display_year')");
    $settings = $settings_stmt->fetchAll(PDO::FETCH_KEY_PAIR);
    $year = $settings['display_year'] ?? date('Y');
    $month = $settings['display_month'] ?? date('m');

    // 2. Prepare WHERE conditions and parameters, now including the date
    $whereClauses = [
        "oi.status = 'unserved'",
        "c.is_priority = 1",
        "YEAR(o.order_date) = ?",
        "MONTH(o.order_date) = ?"
    ];
    $params = [$year, $month];
    $whereSql = "WHERE " . implode(" AND ", $whereClauses);

    // 3. The main SQL query, now using the dynamic WHERE clause
    $sql = "SELECT
                o.id AS order_id,
                o.po_number,
                o.customer_address,
                c.name AS customer_name,
                oi.id AS item_id,
                oi.sku,
                oi.description,
                oi.quantity,
                (
                    SELECT COALESCE(SUM(il.stock), 0)
                    FROM inventory_levels il
                    JOIN product_codes pc2 ON il.product_code = pc2.code
                    WHERE pc2.product_id = pc1.product_id AND il.location = o.location AND pc2.type = 'sku'
                ) AS total_available_stock,
                (
                    SELECT MAX(il.last_updated)
                    FROM inventory_levels il
                    JOIN product_codes pc2 ON il.product_code = pc2.code
                    WHERE pc2.product_id = pc1.product_id AND il.location = o.location AND pc2.type = 'sku'
                ) AS stock_update_date
            FROM
                order_items oi
            JOIN orders o ON oi.order_id = o.id
            JOIN customers c ON o.customer_id = c.id
            JOIN product_codes pc1 ON oi.sku = pc1.code
            {$whereSql}
            HAVING
                total_available_stock >= oi.quantity
            ORDER BY
                o.order_date ASC";

    // 4. Execute the query with the date parameters
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode(['success' => true, 'data' => $items]);
    exit;
}

function getCustomerDashboardData($pdo) {
    $customerId = $_POST['customer_id'] ?? 0; if (!$customerId) throw new Exception("Customer ID is required.");
    $whereClauses = ["o.customer_id = ?"]; $params = [$customerId];
    if (!empty($_POST['location']) && $_POST['location'] !== 'all') { $whereClauses[] = "o.location = ?"; $params[] = $_POST['location']; }
    if (!empty($_POST['bu']) && $_POST['bu'] !== 'all') { $whereClauses[] = "o.bu = ?"; $params[] = $_POST['bu']; }
    $whereSql = 'WHERE ' . implode(' AND ', $whereClauses);
    $sql = "SELECT SUM(CASE WHEN oi.status = 'served' THEN oi.price ELSE 0 END) as totalServedValue, SUM(CASE WHEN oi.status = 'unserved' THEN oi.price ELSE 0 END) as totalUnservedValue FROM orders o JOIN order_items oi ON o.id = oi.order_id $whereSql";
    $stmt = $pdo->prepare($sql); $stmt->execute($params); $data = $stmt->fetch(PDO::FETCH_ASSOC);
    echo json_encode(['success' => true, 'data' => $data]);
    exit;
}

function updateOrderItems($pdo) {
    $orderId = $_POST['order_id'] ?? 0;
    $itemsData = $_POST['items'] ?? null;
    $soNumbersJSON = $_POST['so_numbers'] ?? '[]';
    if (empty($orderId) || empty($itemsData)) {
        throw new Exception('Missing order ID or items data.');
    }
    $newItems = json_decode($itemsData, true);
    $soNumbers = json_decode($soNumbersJSON, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception('Invalid items or SO numbers JSON format.');
    }
    $pdo->beginTransaction();
    try {
        $orderInfoStmt = $pdo->prepare("SELECT location, customer_address, customer_code FROM orders WHERE id = ?");
        $orderInfoStmt->execute([$orderId]);
        $orderInfo = $orderInfoStmt->fetch(PDO::FETCH_ASSOC);
        $location = $orderInfo['location'];
        if (empty($orderInfo['customer_code']) && !empty($orderInfo['customer_address'])) {
            $codeStmt = $pdo->prepare("SELECT customer_code FROM customer_address_codes WHERE address = ?");
            $codeStmt->execute([$orderInfo['customer_address']]);
            $customer_code = $codeStmt->fetchColumn();
            if ($customer_code) {
                $updateCodeStmt = $pdo->prepare("UPDATE orders SET customer_code = ? WHERE id = ?");
                $updateCodeStmt->execute([$customer_code, $orderId]);
            }
        }
        $orderUpdateStmt = $pdo->prepare("UPDATE orders SET so_number = ? WHERE id = ?");
        $orderUpdateStmt->execute([json_encode($soNumbers), $orderId]);
        $originalItemsStmt = $pdo->prepare("SELECT sku, quantity FROM order_items WHERE order_id = ? AND status = 'served'");
        $originalItemsStmt->execute([$orderId]);
        $originalServedItems = $originalItemsStmt->fetchAll(PDO::FETCH_KEY_PAIR);
        $itemUpdateStmt = $pdo->prepare("UPDATE order_items SET sku = ?, description = ?, quantity = ?, price = ?, status = ? WHERE id = ?");
        $newServedItems = [];
        foreach ($newItems as $item) {
            if (!empty($item['id'])) {
                $itemUpdateStmt->execute([$item['sku'], $item['description'], $item['quantity'], $item['price'], $item['status'], $item['id']]);
                if ($item['status'] === 'served') {
                    $newServedItems[$item['sku']] = ($newServedItems[$item['sku']] ?? 0) + (int)$item['quantity'];
                }
            }
        }
        if ($location) {
            $stockUpdateStmt = $pdo->prepare("UPDATE inventory_levels SET stock = stock + ? WHERE product_code = ? AND location = ?");
            $allSkusInvolved = array_unique(array_merge(array_keys($originalServedItems), array_keys($newServedItems)));
            foreach ($allSkusInvolved as $sku) {
                $originalQty = $originalServedItems[$sku] ?? 0;
                $newQty = $newServedItems[$sku] ?? 0;
                $adjustment = $originalQty - $newQty;
                if ($adjustment != 0) {
                    $stockUpdateStmt->execute([$adjustment, $sku, $location]);
                }
            }
        }
        $pdo->commit();
        echo json_encode(['success' => true, 'message' => "Order #{$orderId} updated successfully."]);
    } catch (Exception $e) {
        $pdo->rollBack();
        throw $e;
    }
    exit;
}

function addOrder($pdo) {
    $itemsJson = $_POST['items'] ?? '[]';
    $location = $_POST['location'] ?? '';
    $bu = $_POST['bu'] ?? '';
    $discount = (float)($_POST['discount'] ?? 0);
    $customerId = $_POST['customer_id'] ?? null;
    $customerName = trim($_POST['customer_name'] ?? '');
    $customerAddress = trim($_POST['customer_address'] ?? '');
    $poNumber = $_POST['po_number'] ?? '';
    if (empty($poNumber) || empty($itemsJson) || empty($location)) {
        throw new Exception("Missing required order data (PO, Items, or Location).");
    }
    $isValidCustomer = false;
    if (!empty($customerId)) {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM customers WHERE id = ?");
        $stmt->execute([$customerId]);
        if ($stmt->fetchColumn() > 0) {
            $isValidCustomer = true;
        }
    }
    if (!$isValidCustomer && !empty($customerName)) {
        $stmt = $pdo->prepare("SELECT id FROM customers WHERE UPPER(name) = UPPER(?)");
        $stmt->execute([$customerName]);
        $existingId = $stmt->fetchColumn();
        if ($existingId) {
            $customerId = $existingId;
        } else {
            $stmt = $pdo->prepare("INSERT INTO customers (name) VALUES (?)");
            $stmt->execute([$customerName]);
            $customerId = $pdo->lastInsertId();
        }
    }
    if (empty($customerId)) {
        throw new Exception("Customer could not be found or created.");
    }
    $codeStmt = $pdo->prepare("SELECT customer_code FROM customer_address_codes WHERE address = ?");
    $codeStmt->execute([$customerAddress]);
    $customer_code = $codeStmt->fetchColumn();
    $items = json_decode($itemsJson, true);
    if (json_last_error() !== JSON_ERROR_NONE || !is_array($items) || empty($items)) {
        throw new Exception("Invalid or empty items data provided.");
    }
    if (empty($bu) && !empty($items)) {
        $firstSku = $items[0]['sku'] ?? '';
        if($firstSku) {
            $buStmt = $pdo->prepare("SELECT p.bu FROM products p JOIN product_codes pc ON p.id = pc.product_id WHERE pc.code = ?");
            $buStmt->execute([$firstSku]);
            $bu = $buStmt->fetchColumn() ?: 'Health';
        }
    }
    $pdo->beginTransaction();
    try {
        $stmt = $pdo->prepare("INSERT INTO orders (customer_id, customer_address, customer_code, po_number, location, bu, discount_percentage, order_date) VALUES (?, ?, ?, ?, ?, ?, ?, NOW())");
        $stmt->execute([$customerId, $customerAddress, $customer_code, $poNumber, $location, $bu, $discount]);
        $orderId = $pdo->lastInsertId();
        $itemInsertStmt = $pdo->prepare("INSERT INTO order_items (order_id, sku, description, quantity, price, status, stock_snapshot) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stockUpdateStmt = $pdo->prepare("UPDATE inventory_levels SET stock = stock - ? WHERE product_code = ? AND location = ?");
        $stockCheckStmt = $pdo->prepare("SELECT stock FROM inventory_levels WHERE product_code = ? AND location = ?");
        foreach ($items as $item) {
            if (empty($item['sku'])) { continue; }
            $priceStmt = $pdo->prepare("SELECT sales_price FROM product_codes WHERE code = ?");
            $priceStmt->execute([$item['sku']]);
            $sales_price = $priceStmt->fetchColumn();
            $price = ($sales_price ?: 0) * $item['quantity'];
            if ($discount > 0) {
                $price -= $price * ($discount / 100);
            }
            $stockCheckStmt->execute([$item['sku'], $location]);
            $currentStock = $stockCheckStmt->fetchColumn();
            if ($currentStock === false) $currentStock = 0;
            $status = ($currentStock >= $item['quantity']) ? 'served' : 'unserved';
            $itemInsertStmt->execute([ $orderId, $item['sku'], $item['description'], $item['quantity'], $price, $status, $currentStock ]);
            if ($status === 'served') {
                $stockUpdateStmt->execute([$item['quantity'], $item['sku'], $location]);
            }
        }
        $pdo->commit();
        echo json_encode(['success' => true, 'message' => 'Order processed.', 'order_id' => $orderId]);
    } catch (Exception $e) {
        $pdo->rollBack();
        throw $e;
    }
    exit;
}

function addCustomer($pdo) {
    $name = $_POST['name'] ?? ''; if (empty($name)) { throw new Exception('Customer name is required.'); }
    $stmt = $pdo->prepare("INSERT INTO customers (name) VALUES (?)");
    $stmt->execute([$name]);
    echo json_encode(['success' => true, 'id' => $pdo->lastInsertId()]);
    exit;
}

function deleteCustomer($pdo) {
    $id = $_POST['id'] ?? 0; if (empty($id)) { throw new Exception('Customer ID is required.'); }
    $stmt = $pdo->prepare("DELETE FROM customers WHERE id = ?");
    $stmt->execute([$id]);
    echo json_encode(['success' => true]);
    exit;
}

function toggleCustomerPriority($pdo) {
    $id = $_POST['id'] ?? 0; $is_priority = $_POST['is_priority'] ?? 0;
    if (empty($id)) { throw new Exception('Customer ID is required.'); }
    $stmt = $pdo->prepare("UPDATE customers SET is_priority = ? WHERE id = ?");
    $stmt->execute([(int)$is_priority, $id]);
    echo json_encode(['success' => true]);
    exit;
}

function addProduct($pdo) {
    $sku = $_POST['sku'] ?? ''; $description = $_POST['description'] ?? ''; $bu = $_POST['bu'] ?? 'Health'; $stockQty = $_POST['stock'] ?? 0; $location = $_POST['location'] ?? '';
    if (empty($sku) || empty($description) || empty($location)) { throw new Exception('SKU, Description, and Location are required.'); }
    $pdo->beginTransaction();
    $stmt = $pdo->prepare("SELECT id FROM products WHERE description = ? AND bu = ?");
    $stmt->execute([$description, $bu]); $productId = $stmt->fetchColumn();
    if (!$productId) {
        $stmt = $pdo->prepare("INSERT INTO products (description, bu) VALUES (?, ?)");
        $stmt->execute([$description, $bu]); $productId = $pdo->lastInsertId();
    }
    $stmt = $pdo->prepare("INSERT INTO product_codes (product_id, code, type) VALUES (?, ?, 'sku') ON DUPLICATE KEY UPDATE product_id = VALUES(product_id)");
    $stmt->execute([$productId, $sku]);
    $stockStmt = $pdo->prepare("INSERT INTO inventory_levels (product_code, location, stock) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE stock = VALUES(stock)");
    $stockStmt->execute([$sku, $location, (int)$stockQty]);
    $pdo->commit();
    echo json_encode(['success' => true]);
    exit;
}

function bulkAddProducts($pdo) {
    $data = $_POST['data'] ?? ''; if(empty($data)) { throw new Exception('No data provided.'); }
    $lines = explode("\n", trim($data)); $productsAdded = 0; $codesAdded = 0; $validBUs = ['Health', 'Hygiene', 'Nutri'];
    $pdo->beginTransaction();
    $productStmt = $pdo->prepare("INSERT INTO products (description, bu, is_promo) VALUES (?, ?, ?)");
    $findProductStmt = $pdo->prepare("SELECT id FROM products WHERE description = ? AND bu = ?");
    $updatePromoStmt = $pdo->prepare("UPDATE products SET is_promo = ? WHERE id = ?");
    $codeStmt = $pdo->prepare("INSERT INTO product_codes (product_id, code, type, pieces_per_case) VALUES (?, ?, ?, ?) ON DUPLICATE KEY UPDATE pieces_per_case = VALUES(pieces_per_case)");
    foreach ($lines as $line) {
        if (empty(trim($line))) continue;
        $parts = preg_split('/\s+/', trim($line), -1, PREG_SPLIT_NO_EMPTY); if (count($parts) < 3) continue;
        $isPromo = false; $piecesPerCase = 1; $lastPart = $parts[count($parts) - 1];
        if (is_numeric($lastPart)) { $piecesPerCase = (int)array_pop($parts); }
        if (count($parts) >= 2) {
            $promoCheck = strtolower($parts[count($parts) - 2] . ' ' . $parts[count($parts) - 1]);
            if ($promoCheck === 'promo sku') { $isPromo = true; array_pop($parts); array_pop($parts); } 
            elseif ($promoCheck === 'regular sku') { $isPromo = false; array_pop($parts); array_pop($parts); }
        }
        $bu = 'Health'; $startIndex = 0; $firstPart = ucfirst(strtolower($parts[0]));
        if (in_array($firstPart, $validBUs)) { $bu = $firstPart; $startIndex = 1; }
        $numericCodes = []; $descriptionParts = []; $foundDescription = false;
        for ($i = $startIndex; $i < count($parts); $i++) { if (is_numeric($parts[$i]) && !$foundDescription) { $numericCodes[] = $parts[$i]; } else { $foundDescription = true; $descriptionParts[] = $parts[$i]; } }
        $description = implode(' ', $descriptionParts);
        if (empty($description) || empty($numericCodes)) continue;
        $findProductStmt->execute([$description, $bu]); $productId = $findProductStmt->fetchColumn();
        if (!$productId) { $productStmt->execute([$description, $bu, $isPromo]); $productId = $pdo->lastInsertId(); $productsAdded++; } else { $updatePromoStmt->execute([$isPromo, $productId]); }
        foreach (array_unique($numericCodes) as $code) {
            $type = strlen((string)$code) > 8 ? 'barcode' : 'sku';
            $codeStmt->execute([$productId, $code, $type, $piecesPerCase]);
            if ($codeStmt->rowCount() > 0) $codesAdded++;
        }
    }
    $pdo->commit();
    echo json_encode(['success' => true, 'message' => "$productsAdded products and $codesAdded codes processed."]);
    exit;
}

function bulkUpdateStock($pdo) {
    $data = $_POST['data'] ?? ''; $location = $_POST['location'] ?? '';
    if(empty($data) || empty($location)) { throw new Exception('No stock data or location provided.'); }
    $lines = explode("\n", trim($data));
    $notFoundCodes = [];
    $processedCount = 0;
    $pdo->beginTransaction();
    try {
        $checkCodeStmt = $pdo->prepare("SELECT COUNT(*) FROM product_codes WHERE code = ?");
        $stockUpdateStmt = $pdo->prepare("INSERT INTO inventory_levels (product_code, location, stock) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE stock = VALUES(stock)");
        $pdo->prepare("UPDATE inventory_levels SET stock = 0 WHERE location = ?")->execute([$location]);
        foreach ($lines as $line) {
            if (empty(trim($line))) continue;
            $parts = preg_split('/\s+/', trim($line), -1, PREG_SPLIT_NO_EMPTY);
            if (count($parts) < 2) continue;
            $code = array_shift($parts);
            $quantity = str_replace(',', '', $parts[count($parts) - 2]);
            if (!is_numeric($code) || !is_numeric($quantity)) continue;
            $checkCodeStmt->execute([$code]);
            if ($checkCodeStmt->fetchColumn() > 0) {
                $stockUpdateStmt->execute([$code, $location, (int)$quantity]);
                $processedCount++;
            } else {
                $notFoundCodes[] = $code;
            }
        }
        $pdo->commit();
        $message = "$processedCount stock records for '{$location}' updated.";
        if (!empty($notFoundCodes)) {
            $message .= " SKIPPED non-existent SKUs: " . implode(', ', array_unique($notFoundCodes));
        }
        echo json_encode(['success' => true, 'message' => $message]);
    } catch (Exception $e) {
        $pdo->rollBack();
        throw $e;
    }
    exit;
}

function deleteCode($pdo) {
    $code = $_POST['code'] ?? ''; if (empty($code)) { throw new Exception('Code (SKU/Barcode) is required.'); }
    $pdo->beginTransaction();
    $stmt = $pdo->prepare("SELECT product_id FROM product_codes WHERE code = ?");
    $stmt->execute([$code]); $productId = $stmt->fetchColumn();
    $pdo->prepare("DELETE FROM product_codes WHERE code = ?")->execute([$code]);
    $pdo->prepare("DELETE FROM inventory_levels WHERE product_code = ?")->execute([$code]);
    if ($productId) {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM product_codes WHERE product_id = ?");
        $stmt->execute([$productId]);
        if ($stmt->fetchColumn() == 0) { $pdo->prepare("DELETE FROM products WHERE id = ?")->execute([$productId]); }
    }
    $pdo->commit();
    echo json_encode(['success' => true]);
    exit;
}

function bulkAddAliases($pdo) {
    $data = $_POST['data'] ?? ''; if (empty($data)) { throw new Exception('No alias data provided.'); }
    $lines = explode("\n", trim($data)); $linkedCount = 0; $notFound = [];
    $pdo->beginTransaction();
    $findStmt = $pdo->prepare("SELECT product_id FROM product_codes WHERE code = ? AND type = 'barcode'");
    $insertStmt = $pdo->prepare("INSERT INTO product_codes (product_id, code, type) VALUES (?, ?, 'sku') ON DUPLICATE KEY UPDATE product_id = VALUES(product_id)");
    foreach ($lines as $line) {
        if (empty(trim($line))) continue;
        $parts = preg_split('/\s+/', trim($line), -1, PREG_SPLIT_NO_EMPTY); if (count($parts) < 2) continue;
        $customerSku = array_shift($parts); $barcode = array_pop($parts);
        if (!is_numeric($customerSku) || !is_numeric($barcode)) { continue; }
        $findStmt->execute([$barcode]); $productId = $findStmt->fetchColumn();
        if ($productId) {
            $insertStmt->execute([$productId, $customerSku]);
            if ($insertStmt->rowCount() > 0) $linkedCount++;
        } else { $notFound[] = $barcode; }
    }
    $pdo->commit();
    $message = "$linkedCount customer SKUs were successfully linked.";
    if (!empty($notFound)) { $message .= " Barcodes not found: " . implode(', ', array_unique($notFound)); }
    echo json_encode(['success' => true, 'message' => $message]);
    exit;
}

function getUnlinkedSkus($pdo) {
    $location = $_POST['location'] ?? 'Davao';
    $sql = "SELECT p.description, pc.code AS sku, (SELECT il.stock FROM inventory_levels il WHERE il.product_code = pc.code AND il.location = ?) as current_stock FROM products p JOIN product_codes pc ON p.id = pc.product_id WHERE p.id IN (SELECT product_id FROM product_codes GROUP BY product_id HAVING SUM(CASE WHEN type = 'barcode' THEN 1 ELSE 0 END) = 0) AND pc.type = 'sku' ORDER BY p.description;";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$location]);
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode(['success' => true, 'data' => $results]);
    exit;
}

function deleteOrder($pdo) {
    $orderId = $_POST['order_id'] ?? 0;
    if (empty($orderId)) {
        throw new Exception('Order ID is required.');
    }
    $pdo->beginTransaction();
    try {
        $itemsStmt = $pdo->prepare("SELECT sku, quantity, o.location FROM order_items oi JOIN orders o ON oi.order_id = o.id WHERE oi.order_id = ? AND oi.status = 'served'");
        $itemsStmt->execute([$orderId]);
        $servedItems = $itemsStmt->fetchAll(PDO::FETCH_ASSOC);
        if (!empty($servedItems)) {
            $stockUpdateStmt = $pdo->prepare("UPDATE inventory_levels SET stock = stock + ? WHERE product_code = ? AND location = ?");
            $location = $servedItems[0]['location'];
            foreach ($servedItems as $item) {
                $stockUpdateStmt->execute([$item['quantity'], $item['sku'], $location]);
            }
        }
        $pdo->prepare("DELETE FROM order_items WHERE order_id = ?")->execute([$orderId]);
        $pdo->prepare("DELETE FROM orders WHERE id = ?")->execute([$orderId]);
        $pdo->commit();
        echo json_encode(['success' => true, 'message' => "Order #{$orderId} has been successfully deleted."]);
    } catch (Exception $e) {
        $pdo->rollBack();
        throw $e;
    }
    exit;
}

function set_display_month($pdo) {
    if ($_SESSION['role'] !== 'admin') {
        throw new Exception('Admin permission required.');
    }
    $month = $_POST['month'] ?? null;
    $year = $_POST['year'] ?? null;
    if (!$month || !$year) {
        throw new Exception('Month and Year are required.');
    }
    $pdo->beginTransaction();
    try {
        $stmt = $pdo->prepare("INSERT INTO settings (setting_key, setting_value) VALUES ('display_month', ?) ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)");
        $stmt->execute([$month]);
        $stmt = $pdo->prepare("INSERT INTO settings (setting_key, setting_value) VALUES ('display_year', ?) ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)");
        $stmt->execute([$year]);
        $pdo->commit();
        echo json_encode(['success' => true, 'message' => 'Display month updated successfully.']);
    } catch (Exception $e) {
        $pdo->rollBack();
        throw $e;
    }
    exit;
}

function getDashboardData($pdo) {
    $stmt = $pdo->query("SELECT setting_key, setting_value FROM settings WHERE setting_key IN ('display_month', 'display_year')");
    $settings = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
    $year = $settings['display_year'] ?? date('Y');
    $month = $settings['display_month'] ?? date('m');
    $fromSql = "FROM orders o 
                JOIN order_items oi ON o.id = oi.order_id 
                LEFT JOIN customers c ON o.customer_id = c.id 
                LEFT JOIN product_codes pc ON oi.sku = pc.code
                LEFT JOIN products p ON pc.product_id = p.id";
    $whereClauses = ["YEAR(o.order_date) = ?", "MONTH(o.order_date) = ?"];
    $params = [$year, $month];
    if (!empty($_POST['location']) && $_POST['location'] !== 'all') { $whereClauses[] = "o.location = ?"; $params[] = $_POST['location']; }
    if (!empty($_POST['bu']) && $_POST['bu'] !== 'all') { $whereClauses[] = "p.bu = ?"; $params[] = $_POST['bu']; } 
    if (!empty($_POST['customer']) && $_POST['customer'] !== 'all') { $whereClauses[] = "c.name = ?"; $params[] = $_POST['customer']; }
    $whereSql = 'WHERE ' . implode(' AND ', $whereClauses);
    $statsSql = "SELECT 
                    SUM(CASE WHEN oi.status = 'served' THEN oi.price ELSE 0 END) as totalServedValue, 
                    SUM(CASE WHEN oi.status = 'unserved' THEN oi.price ELSE 0 END) as totalUnservedValue, 
                    SUM(CASE WHEN oi.status = 'served' THEN oi.quantity ELSE 0 END) as totalServedQty, 
                    SUM(CASE WHEN oi.status = 'unserved' THEN oi.quantity ELSE 0 END) as totalUnservedQty, 
                    COUNT(DISTINCT CASE WHEN oi.status = 'unserved' THEN oi.sku ELSE NULL END) as unservedSkuCount 
                 $fromSql $whereSql";
    $stmt = $pdo->prepare($statsSql);
    $stmt->execute($params);
    $stats = $stmt->fetch(PDO::FETCH_ASSOC);
    $fillRateByPoSql = "SELECT (SUM(CASE WHEN oi.status = 'served' THEN oi.quantity ELSE 0 END) * 100.0 / SUM(oi.quantity)) AS po_fill_rate 
                        $fromSql $whereSql GROUP BY o.id HAVING SUM(oi.quantity) > 0";
    $fillRateStmt = $pdo->prepare($fillRateByPoSql);
    $fillRateStmt->execute($params);
    $all_fill_rates = $fillRateStmt->fetchAll(PDO::FETCH_COLUMN, 0);
    $average_fill_rate = count($all_fill_rates) > 0 ? array_sum($all_fill_rates) / count($all_fill_rates) : 0;
    $stats['quantityFillRateByPo'] = $average_fill_rate;
    $unservedSqlWhere = "$whereSql AND oi.status = 'unserved'";
    $unservedSql = "SELECT oi.sku, oi.description, SUM(oi.quantity) as total_quantity, SUM(oi.price) as total_value 
                    $fromSql $unservedSqlWhere 
                    GROUP BY oi.sku, oi.description 
                    ORDER BY total_value DESC";
    $stmt = $pdo->prepare($unservedSql);
    $stmt->execute($params);
    $topUnserved = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $customerSqlWhere = "$whereSql AND oi.status = 'served'";
    $customerSql = "SELECT c.name, SUM(oi.price) as value 
                    $fromSql $customerSqlWhere 
                    GROUP BY c.name ORDER BY value DESC LIMIT 5";
    $stmt = $pdo->prepare($customerSql);
    $stmt->execute($params);
    $topCustomers = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $responseData = ['stats' => $stats, 'topUnserved' => $topUnserved, 'topCustomers' => $topCustomers];
    echo json_encode(['success' => true, 'data' => $responseData]);
    exit;
}

function getRojonDashboardData($pdo) {
    $VAT_RATE = 1.12;

    $stmt = $pdo->query("SELECT setting_key, setting_value FROM settings WHERE setting_key IN ('display_month', 'display_year')");
    $settings = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
    $year = $settings['display_year'] ?? date('Y');
    $month = $settings['display_month'] ?? date('m');

    $location = $_POST['location'] ?? 'all';
    $bu = $_POST['bu'] ?? 'all';
    $customer_name = 'ROJON PHARMACY CORPORATION';

    $whereClauses = ["c.name = ?", "YEAR(o.order_date) = ?", "MONTH(o.order_date) = ?"];
    $params = [$customer_name, $year, $month];

    if ($location !== 'all') { $whereClauses[] = "o.location = ?"; $params[] = $location; }
    if ($bu !== 'all') { $whereClauses[] = "p.bu = ?"; $params[] = $bu; }
    $whereSql = 'WHERE ' . implode(' AND ', $whereClauses);

    $baseSql = "SELECT
                    oi.status, oi.price, oi.quantity, oi.sku, oi.description,
                    p.bu, o.id as order_id, o.location, o.customer_address, o.po_number,
                    o.discount_percentage,
                    pc.sales_price, pc.product_id
                FROM orders o
                JOIN order_items oi ON o.id = oi.order_id
                JOIN customers c ON o.customer_id = c.id
                LEFT JOIN product_codes pc ON oi.sku = pc.code
                LEFT JOIN products p ON pc.product_id = p.id $whereSql";

    $stmt = $pdo->prepare($baseSql);
    $stmt->execute(!empty($params) ? $params : []);
    $allItems = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $productIds = array_unique(array_column($allItems, 'product_id'));
    $barcodeMap = [];
    if (!empty($productIds)) {
        $placeholders = implode(',', array_fill(0, count($productIds), '?'));
        $codeStmt = $pdo->prepare("SELECT product_id, code FROM product_codes WHERE product_id IN ($placeholders) AND type = 'barcode'");
        $codeStmt->execute(array_values($productIds));
        $allBarcodes = $codeStmt->fetchAll(PDO::FETCH_ASSOC);
        foreach($allBarcodes as $bc) {
            if ($bc['product_id']) {
                $barcodeMap[$bc['product_id']] = $bc['code'];
            }
        }
    }

    $stats = [ 'totalServedValue' => 0, 'totalUnservedValue' => 0, 'totalUnservedQty' => 0, 'unservedSkuCount' => 0 ];
    $buPerformance = []; $unservedItems = []; $unservedSkus = []; $fillRateByPo = [];

    foreach ($allItems as $item) {
        if ($item['bu']) {
            if (!isset($buPerformance[$item['bu']])) {
                $buPerformance[$item['bu']] = [
                    'bu' => $item['bu'], 'served_net_vat_in' => 0,
                    'served_gross' => 0, 'unserved' => 0
                ];
            }
            if ($item['status'] === 'served') {
                $stats['totalServedValue'] += $item['price'];
                $gross_value = (float)($item['sales_price'] ?? 0) * (int)$item['quantity'];
                $buPerformance[$item['bu']]['served_gross'] += $gross_value;
                $buPerformance[$item['bu']]['served_net_vat_in'] += (float)$item['price'];
            } else {
                
                
                
                
                $stats['totalUnservedValue'] += $item['price'];
                $buPerformance[$item['bu']]['unserved'] += (float)$item['price'];
                $stats['totalUnservedQty'] += $item['quantity'];
                if (!in_array($item['sku'], $unservedSkus)) { $unservedSkus[] = $item['sku']; }
                if (!isset($unservedItems[$item['sku']])) {
                    $unservedItems[$item['sku']] = [
                        'description' => $item['description'],
                        'sku' => $item['sku'],
                        'barcode' => $item['product_id'] ? ($barcodeMap[$item['product_id']] ?? 'N/A') : 'N/A',
                        'total_qty' => 0, 'total_value' => 0
                    ];
                }
                $unservedItems[$item['sku']]['total_qty'] += $item['quantity'];
                $unservedItems[$item['sku']]['total_value'] += $item['price'];
            }
        }
        if (!isset($fillRateByPo[$item['order_id']])) { $fillRateByPo[$item['order_id']] = ['served_qty' => 0, 'total_qty' => 0]; }
        if ($item['status'] === 'served') { $fillRateByPo[$item['order_id']]['served_qty'] += $item['quantity']; }
        $fillRateByPo[$item['order_id']]['total_qty'] += $item['quantity'];
    }

    // --- Start of Fix ---
    // This loop now calculates the total PO amount in addition to the VAT values.
    foreach ($buPerformance as &$buData) {
        // Calculate the total PO amount by summing the gross served value and the unserved value.
        $buData['po_amount_total'] = ($buData['served_gross'] ?? 0) + ($buData['unserved'] ?? 0);

        // Existing VAT calculations
        $net_vat_in = $buData['served_net_vat_in'];
        $buData['served_net_vat_ex'] = $net_vat_in / $VAT_RATE;
        $buData['vat_amount'] = $net_vat_in - $buData['served_net_vat_ex'];
    }
    unset($buData); // Unset reference to last element
    // --- End of Fix ---

    $stats['totalPoValue'] = $stats['totalServedValue'] + $stats['totalUnservedValue']; $stats['unservedSkuCount'] = count($unservedSkus);
    $all_fill_rates = []; foreach($fillRateByPo as $po) { if ($po['total_qty'] > 0) { $all_fill_rates[] = ($po['served_qty'] * 100.0) / $po['total_qty']; } } $stats['quantityFillRateByPo'] = count($all_fill_rates) > 0 ? array_sum($all_fill_rates) / count($all_fill_rates) : 0;
    $finalUnservedItems = array_values($unservedItems);
    usort($finalUnservedItems, function($a, $b) { return $b['total_value'] <=> $a['total_value']; });

    $recentPoSql = "SELECT
                        o.id, o.po_number, o.customer_address, o.so_number, o.order_date,
                        (SELECT SUM(price) FROM order_items WHERE order_id = o.id AND status = 'served') as total_amount
                    FROM orders o
                    JOIN customers c ON o.customer_id = c.id
                    WHERE c.name = ?
                    ORDER BY o.order_date DESC
                    LIMIT 5";
    $stmt = $pdo->prepare($recentPoSql);
    $stmt->execute([$customer_name]);
    $recentPOs = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $fulfillableWhereClauses = $whereClauses;
    $fulfillableWhereClauses[] = "oi.status = 'unserved'";
    $fulfillableWhereClauses[] = "il.stock >= oi.quantity";

    $fulfillableWhereSql = 'WHERE ' . implode(' AND ', $fulfillableWhereClauses);

    $fulfillableSkuSql = "SELECT p.bu, o.customer_address, oi.sku, oi.description, pc.sales_price, o.id as order_id, o.po_number, oi.quantity, il.stock as inventory_left FROM order_items oi JOIN orders o ON oi.order_id = o.id JOIN customers c ON o.customer_id = c.id LEFT JOIN product_codes pc ON oi.sku = pc.code LEFT JOIN products p ON pc.product_id = p.id LEFT JOIN inventory_levels il ON oi.sku = il.product_code AND o.location = il.location {$fulfillableWhereSql} ORDER BY p.bu, o.customer_address, oi.description";

    $stmt = $pdo->prepare($fulfillableSkuSql);
    $stmt->execute($params);
    $fulfillableSkus = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $targetParams = [$year, $month]; $targetWhere = '';
    if ($bu !== 'all') { $targetWhere = ' AND bu = ?'; $targetParams[] = $bu; }
    $targetsStmt = $pdo->prepare("SELECT location, bu, target_amount FROM monthly_targets WHERE year = ? AND month = ?{$targetWhere}"); $targetsStmt->execute($targetParams);
    $targets = $targetsStmt->fetchAll(PDO::FETCH_GROUP | PDO::FETCH_UNIQUE | PDO::FETCH_ASSOC);

    $progressParams = [$customer_name, $year, $month]; $progressWhere = '';
    if ($bu !== 'all') { $progressWhere = ' AND p.bu = ?'; $progressParams[] = $bu; }
    $progressSql = "SELECT o.location, p.bu, SUM(oi.price) as current_total FROM orders o JOIN order_items oi ON o.id = oi.order_id JOIN customers c ON o.customer_id = c.id LEFT JOIN product_codes pc ON oi.sku = pc.code LEFT JOIN products p ON pc.product_id = p.id WHERE c.name = ? AND YEAR(o.order_date) = ? AND MONTH(o.order_date) = ?{$progressWhere} GROUP BY o.location, p.bu";
    $progressStmt = $pdo->prepare($progressSql); $progressStmt->execute($progressParams); $progressData = $progressStmt->fetchAll(PDO::FETCH_ASSOC);
    $progress = []; foreach($progressData as $row) { if (!isset($progress[$row['location']])) { $progress[$row['location']] = []; } $progress[$row['location']][$row['bu']] = $row['current_total']; }

    $responseData = [ 'stats' => $stats, 'buPerformance' => array_values($buPerformance), 'unservedItems' => $finalUnservedItems, 'fulfillableSkus' => $fulfillableSkus, 'targets' => $targets, 'progress' => $progress, 'recent_pos' => $recentPOs ];
    echo json_encode(['success' => true, 'data' => $responseData]);
    exit;
}

function getRojonOrders($pdo) {
    $stmt = $pdo->query("SELECT setting_key, setting_value FROM settings WHERE setting_key IN ('display_month', 'display_year')");
    $settings = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
    $year = $settings['display_year'] ?? date('Y');
    $month = $settings['display_month'] ?? date('m');
    $location = $_POST['location'] ?? 'all';
    $bu = $_POST['bu'] ?? 'all';
    $search = $_POST['search'] ?? '';
    $limit = 50;
    $page = isset($_POST['page']) ? (int)$_POST['page'] : 1;
    $offset = ($page - 1) * $limit;
    $rojon_customer_id = 34;
    $whereClauses = ["o.customer_id = ?", "YEAR(o.order_date) = ?", "MONTH(o.order_date) = ?"];
    $params = [$rojon_customer_id, $year, $month];
    if ($location !== 'all') { $whereClauses[] = "o.location = ?"; $params[] = $location; }
    if ($bu !== 'all') { $whereClauses[] = "o.bu = ?"; $params[] = $bu; }
    if (!empty($search)) {
        $whereClauses[] = "(o.po_number LIKE ? OR o.so_number LIKE ?)";
        $searchTerm = '%' . $search . '%';
        array_push($params, $searchTerm, $searchTerm);
    }
    $whereSql = 'WHERE ' . implode(' AND ', $whereClauses);
    $countSql = "SELECT COUNT(id) FROM orders o $whereSql";
    $countStmt = $pdo->prepare($countSql);
    $countStmt->execute($params);
    $totalOrders = $countStmt->fetchColumn();
    $ordersSql = "SELECT id, po_number, order_date, customer_address, so_number FROM orders o $whereSql ORDER BY o.order_date DESC LIMIT ? OFFSET ?";
    $ordersStmt = $pdo->prepare($ordersSql);
    $ordersStmt->execute(array_merge($params, [$limit, $offset]));
    $finalOrders = $ordersStmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode([
        'success' => true, 
        'data' => $finalOrders,
        'pagination' => [
            'total' => (int)$totalOrders,
            'page' => $page,
            'limit' => $limit,
            'totalPages' => ceil($totalOrders / $limit)
        ]
    ]);
    exit;
}

function get_stock_for_product($pdo) {
    $productId = $_GET['product_id'] ?? 0;
    if (!$productId) {
        echo json_encode(['success' => false, 'message' => 'Product ID required.']);
        exit;
    }
    $sql = "SELECT il.product_code, il.location, il.stock
            FROM inventory_levels il
            JOIN product_codes pc ON il.product_code = pc.code
            WHERE pc.product_id = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$productId]);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $stockBySku = [];
    foreach ($rows as $row) {
        if (!isset($stockBySku[$row['product_code']])) {
            $stockBySku[$row['product_code']] = [];
        }
        $stockBySku[$row['product_code']][$row['location']] = (int)$row['stock'];
    }
    echo json_encode(['success' => true, 'data' => $stockBySku]);
    exit;
}

function find_product_with_best_sku($pdo) {
    $term = $_POST['term'] ?? '';
    $location = $_POST['location'] ?? '';
    if (empty($term)) {
        echo json_encode(['success' => false, 'message' => 'Search term is required.']);
        exit;
    }

    $productId = null;
    if (is_numeric($term)) {
        $productId = $term;
    }

    if (!$productId) {
        $stmt = $pdo->prepare("SELECT product_id FROM product_codes WHERE code = ?");
        $stmt->execute([$term]);
        $productId = $stmt->fetchColumn();
    }
    
    if (!$productId) {
        $stmt = $pdo->prepare("SELECT id FROM products WHERE description LIKE ? LIMIT 1");
        $stmt->execute(['%' . $term . '%']);
        $productId = $stmt->fetchColumn();
    }

    if (!$productId) {
        echo json_encode(['success' => false, 'message' => 'Product not found.']);
        exit;
    }

    // --- START OF FIX ---
    // Fetch description AND bu in a single, more efficient query.
    $productInfoStmt = $pdo->prepare("SELECT description, bu FROM products WHERE id = ?");
    $productInfoStmt->execute([$productId]);
    $productInfo = $productInfoStmt->fetch(PDO::FETCH_ASSOC);

    if (!$productInfo) {
        echo json_encode(['success' => false, 'message' => 'Product details could not be found.']);
        exit;
    }
    // --- END OF FIX ---

    $stmt = $pdo->prepare("
		 SELECT 
            pc.code, 
            pc.type, 
            pc.sales_price,
            pc.pieces_per_case, 
            il.stock
        FROM product_codes pc
        LEFT JOIN inventory_levels il ON pc.code = il.product_code AND il.location = ?
        WHERE pc.product_id = ?
    ");
    $stmt->execute([$location, $productId]);
    $allCodes = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $bestSku = '';
    $maxStock = -1;
    $firstSku = null;

    foreach ($allCodes as $code) {
        if ($code['type'] === 'sku') {
            if ($firstSku === null) {
                $firstSku = $code['code'];
            }
            $stock = (int)($code['stock'] ?? 0);
            if ($stock > $maxStock) {
                $maxStock = $stock;
                $bestSku = $code['code'];
            }
        }
    }

    if (empty($bestSku)) {
        $bestSku = $firstSku;
    }
    
    echo json_encode([
        'success' => true,
        'data' => [
            'productId' => $productId,
            'description' => $productInfo['description'], // Use new variable
            'bu' => $productInfo['bu'],                  // <-- ADDED THIS LINE
            'bestSku' => $bestSku,
            'allSkus' => $allCodes
        ]
    ]);
    exit;
}

function get_product_suggestions($pdo) {
    $term = $_POST['term'] ?? '';
    $bu = $_POST['bu'] ?? '';
    if (strlen($term) < 2) {
        echo json_encode(['success' => true, 'data' => []]);
        exit;
    }
    $params = ['%' . $term . '%', '%' . $term . '%'];
    $sql = "SELECT p.id, p.description, pc.code as sku, (SELECT code FROM product_codes WHERE product_id = p.id AND type='barcode' LIMIT 1) as barcode
            FROM products p
            JOIN product_codes pc ON p.id = pc.product_id
            WHERE (p.description LIKE ? OR pc.code LIKE ?) AND pc.type='sku'";
    if (!empty($bu)) {
        $sql .= " AND p.bu = ?";
        $params[] = $bu;
    }
    $sql .= " GROUP BY p.id LIMIT 10";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode(['success' => true, 'data' => $results]);
    exit;
}

function toggle_pristine_status($pdo) {
    $orderId = $_POST['order_id'] ?? 0;
    $status = $_POST['status'] ?? 0;
    if (empty($orderId)) {
        throw new Exception('Order ID is required.');
    }
    $stmt = $pdo->prepare("UPDATE orders SET is_pristine_checked = ? WHERE id = ?");
    $stmt->execute([(int)$status, $orderId]);
    echo json_encode(['success' => true, 'message' => 'Pristine status updated.']);
    exit;
}

function search_pos_by_product($pdo) {
    $term = $_POST['term'] ?? '';
    if (strlen($term) < 3) {
        echo json_encode(['success' => true, 'data' => []]);
        exit;
    }
    $searchTerm = '%' . $term . '%';
    $sql = "SELECT 
                oi.order_id, oi.sku, oi.description, oi.quantity, oi.status,
                o.po_number, o.order_date,
                c.name as customer_name
            FROM order_items oi
            JOIN orders o ON oi.order_id = o.id
            LEFT JOIN customers c ON o.customer_id = c.id
            WHERE oi.sku LIKE ? OR oi.description LIKE ?
            ORDER BY o.order_date DESC
            LIMIT 100";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$searchTerm, $searchTerm]);
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode(['success' => true, 'data' => $results]);
    exit;
}

function get_address_by_code($pdo) {
    $code = $_POST['code'] ?? '';
    if (empty($code)) {
        echo json_encode(['success' => false, 'message' => 'Code required']);
        exit;
    }
    $stmt = $pdo->prepare("SELECT customer_code FROM customer_address_codes WHERE address = ?");
    $stmt->execute([$code]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($result) {
        echo json_encode(['success' => true, 'data' => $result]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Not found']);
    }
    exit;
}

function getAddressCodes($pdo) {
    $stmt = $pdo->query("SELECT * FROM customer_address_codes ORDER BY address ASC");
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode(['success' => true, 'data' => $results]);
    exit;
}

function addAddressCode($pdo) {
    $address = $_POST['address'] ?? '';
    $customer_code = $_POST['customer_code'] ?? '';
    if (empty($address) || empty($customer_code)) {
        throw new Exception('Address and Customer Code are required.');
    }
    $stmt = $pdo->prepare("INSERT INTO customer_address_codes (address, customer_code) VALUES (?, ?)");
    $stmt->execute([$address, $customer_code]);
    echo json_encode(['success' => true, 'message' => 'Address code added.']);
    exit;
}

function updateAddressCode($pdo) {
    $id = $_POST['id'] ?? 0;
    $address = $_POST['address'] ?? '';
    $customer_code = $_POST['customer_code'] ?? '';
    if (empty($id) || empty($address) || empty($customer_code)) {
        throw new Exception('ID, Address, and Customer Code are required.');
    }
    $stmt = $pdo->prepare("UPDATE customer_address_codes SET address = ?, customer_code = ? WHERE id = ?");
    $stmt->execute([$address, $customer_code, $id]);
    echo json_encode(['success' => true, 'message' => 'Address code updated.']);
    exit;
}

function deleteAddressCode($pdo) {
    $id = $_POST['id'] ?? 0;
    if (empty($id)) {
        throw new Exception('ID is required.');
    }
    $stmt = $pdo->prepare("DELETE FROM customer_address_codes WHERE id = ?");
    $stmt->execute([$id]);
    echo json_encode(['success' => true, 'message' => 'Address code deleted.']);
    exit;
}

function getMonthlyTargets($pdo) {
    $stmt = $pdo->query("SELECT * FROM monthly_targets");
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode(['success' => true, 'data' => $results]);
    exit;
}

function setMonthlyTargets($pdo) {
    $targetsJson = $_POST['targets'] ?? '[]';
    $targets = json_decode($targetsJson, true);
    if (json_last_error() !== JSON_ERROR_NONE || !is_array($targets)) {
        throw new Exception("Invalid targets data provided.");
    }
    $year = date('Y');
    $month = date('m');
    $pdo->beginTransaction();
    try {
        $stmt = $pdo->prepare(
            "INSERT INTO monthly_targets (year, month, location, bu, target_amount) 
             VALUES (?, ?, ?, ?, ?) 
             ON DUPLICATE KEY UPDATE target_amount = VALUES(target_amount)"
        );
        foreach ($targets as $target) {
            $stmt->execute([$year, $month, $target['location'], $target['bu'], $target['amount']]);
        }
        $pdo->commit();
        echo json_encode(['success' => true, 'message' => 'Monthly targets have been saved.']);
    } catch (Exception $e) {
        $pdo->rollBack();
        throw $e;
    }
    exit;
}

function getOrdersForExport($pdo) {
    $month = $_POST['month'] ?? 0;
    $year = $_POST['year'] ?? 0;
    $location = $_POST['location'] ?? 'all';
    if(empty($month) || empty($year)){
        echo json_encode(['success' => false, 'message' => 'Month and year are required for export.']);
        exit;
    }
    $whereClauses = ["MONTH(o.order_date) = ?", "YEAR(o.order_date) = ?"];
    $params = [$month, $year];
    if($location !== 'all'){
        $whereClauses[] = "o.location = ?";
        $params[] = $location;
    }
    $whereSql = "WHERE " . implode(' AND ', $whereClauses);
    $sql = "SELECT o.id, o.po_number, o.order_date, o.location, o.bu, o.customer_address, o.customer_code, o.so_number, c.name as customer_name 
            FROM orders o 
            JOIN customers c ON o.customer_id = c.id 
            $whereSql 
            ORDER BY o.order_date ASC";
    $orderStmt = $pdo->prepare($sql);
    $orderStmt->execute($params);
    $orders = $orderStmt->fetchAll(PDO::FETCH_ASSOC);
    if(empty($orders)){
        echo json_encode(['success' => true, 'data' => []]);
        exit;
    }
    $orderIds = array_column($orders, 'id');
    $placeholders = implode(',', array_fill(0, count($orderIds), '?'));
    $itemSql = "SELECT * FROM order_items WHERE order_id IN ($placeholders) ORDER BY order_id, id";
    $itemStmt = $pdo->prepare($itemSql);
    $itemStmt->execute($orderIds);
    $items = $itemStmt->fetchAll(PDO::FETCH_ASSOC);
    $itemsByOrderId = [];
    foreach($items as $item){
        $itemsByOrderId[$item['order_id']][] = $item;
    }
    $results = [];
    foreach($orders as $order){
        $results[] = [
            'id' => $order['id'],
            'location' => $order['location'],
            'bu' => $order['bu'],
            'customer_code' => $order['customer_code'],
            'so_number' => json_decode($order['so_number'] ?? '[]'),
            'customer' => [
                'name' => $order['customer_name'],
                'address' => $order['customer_address'],
                'poNumber' => $order['po_number']
            ],
            'date' => $order['order_date'],
            'items' => $itemsByOrderId[$order['id']] ?? []
        ];
    }
    echo json_encode(['success' => true, 'data' => $results]);
    exit;
}

function getAddressSuggestions($pdo) {
    $term = $_POST['term'] ?? '';
    // Only search if the user has typed at least 2 characters
    if (strlen($term) < 2) {
        echo json_encode(['success' => true, 'data' => []]);
        exit;
    }

    $searchTerm = '%' . $term . '%';
    // Query the master list of addresses
    $stmt = $pdo->prepare(
        "SELECT id, address, customer_code FROM customer_address_codes WHERE address LIKE ? ORDER BY address LIMIT 10"
    );
    $stmt->execute([$searchTerm]);
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode(['success' => true, 'data' => $results]);
    exit;
}
?>