<?php
session_start();
require 'db_connect.php';

$user_role = $_SESSION['role'] ?? 'viewer';
$orderId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$orderId) {
    header('Location: index.php');
    exit;
}

// Fetch the main order details
$orderStmt = $pdo->prepare("SELECT o.*, c.name as customer_name FROM orders o LEFT JOIN customers c ON o.customer_id = c.id WHERE o.id = ?");
$orderStmt->execute([$orderId]);
$order = $orderStmt->fetch();

if (!$order) {
    echo "Order not found.";
    exit;
}

// Fetch all items for this order
$itemStmt = $pdo->prepare("SELECT * FROM order_items WHERE order_id = ? ORDER BY id ASC");
$itemStmt->execute([$orderId]);
$items = $itemStmt->fetchAll();

// Get SO Numbers and decode them from JSON. Default to an empty array.
$so_numbers = json_decode($order['so_number'] ?? '[]', true);
if (!is_array($so_numbers)) {
    $so_numbers = [];
}

// Define the BU to BU Code mapping
$bu_map = [
    'Nutri' => 'ifcn',
    'Health' => 'rw',
    'Hygiene' => 'hygiene'
];
$bu_code = $bu_map[$order['bu']] ?? 'N/A';


// --- This is the NEW block for your file ---
$products_by_sku = [];
if (!empty($items)) {
    // Get all unique SKUs from the current order items
    $itemSkus = array_unique(array_column($items, 'sku'));
    if (!empty($itemSkus)) {
        // Find all product families associated with these SKUs
        $placeholders = implode(',', array_fill(0, count($itemSkus), '?'));
        $sql = "SELECT DISTINCT product_id FROM product_codes WHERE code IN ($placeholders)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($itemSkus);
        $productIds = $stmt->fetchAll(PDO::FETCH_COLUMN);

        if (!empty($productIds)) {
            // Fetch all related codes for those product families, INCLUDING the sales_price
            $placeholders = implode(',', array_fill(0, count($productIds), '?'));
            // --- KEY CHANGE: Added pc.sales_price to the query ---
            $sql = "SELECT p.id, p.description, pc.code, pc.type, pc.sales_price 
                    FROM products p JOIN product_codes pc ON p.id = pc.product_id 
                    WHERE p.id IN ($placeholders)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute($productIds);
            $related_products_data = $stmt->fetchAll(PDO::FETCH_GROUP | PDO::FETCH_ASSOC);

            // Structure the data for the frontend
            foreach ($related_products_data as $productId => $codes) {
                $product_info = ['description' => $codes[0]['description'], 'codes' => $codes];
                foreach ($product_info['codes'] as $code) {
                    // --- KEY CHANGE: Added sales_price to the JS object ---
                    $products_by_sku[$code['code']] = [
                        'productId' => $productId, 
                        'description' => $product_info['description'], 
                        'sales_price' => (float)$code['sales_price'], // Make sure price is a number
                        'allSkus' => $product_info['codes']
                    ];
                }
            }
        }
    }
}

// --- CALCULATE TOTALS ---
$totalServedValue = 0;
$totalUnservedValue = 0;
$totalUnservedCount = 0;
// NEW: Add variables for quantity calculation
$totalServedQty = 0;
$totalItemQty = 0;

foreach ($items as $item) {
    // Add the quantity of every item to the total quantity
    $totalItemQty += $item['quantity'];

    if ($item['status'] === 'served') {
        $totalServedValue += $item['price'];
        // Add the quantity of served items to the served quantity total
        $totalServedQty += $item['quantity'];
    } else {
        $totalUnservedValue += $item['price'];
        $totalUnservedCount++;
    }
}
$totalPoValue = $totalServedValue + $totalUnservedValue;
// We'll keep the old value-based rate in case it's needed elsewhere, but we won't display it.
$fillRate = ($totalPoValue > 0) ? ($totalServedValue / $totalPoValue) * 100 : 0;
// NEW: Calculate the quantity-based fill rate
$qtyFillRate = ($totalItemQty > 0) ? ($totalServedQty / $totalItemQty) * 100 : 0;

// Split items into pages of 12
$item_pages = array_chunk($items, 12);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Order - PO #<?php echo htmlspecialchars($order['po_number']); ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" type="text/css" href="https://cdn.jsdelivr.net/npm/toastify-js/src/toastify.min.css">
    <link rel="stylesheet" href="style.css">
</head>
<body class="bg-stone-100">
    <div id="loading-overlay" class="modal-backdrop" style="display: none; z-index: 9999;">
        <div class="animate-spin rounded-full h-16 w-16 border-t-2 border-b-2 border-white"></div>
    </div>
    
    <div class="container mx-auto p-4 md:p-8 max-w-6xl">
        <header class="sticky top-0 z-40 bg-stone-100 py-4 mb-6 flex justify-end items-center print:hidden">
    <div class="flex gap-4 items-center"> <div class="flex items-center gap-2">
            <label for="orderDiscountInput" class="text-sm font-medium text-slate-700">Discount:</label>
            <input type="number" id="orderDiscountInput" step="0.1" value="<?php echo htmlspecialchars($order['discount_percentage']); ?>" class="w-24 rounded-md border-slate-300 shadow-sm text-sm" disabled>
            <span class="text-sm font-medium text-slate-700">%</span>
        </div>
        <?php if ($user_role === 'admin' || $user_role === 'encoder'):
            // This PHP block determines the button's initial appearance
            $pristine_class = $order['is_pristine_checked'] ? 'bg-emerald-600 hover:bg-emerald-700' : 'bg-slate-400 hover:bg-slate-500';
            $pristine_text = $order['is_pristine_checked'] ? '✓ Pristine Checked' : 'Mark as Pristine';
        ?>
            <button id="pristineCheckBtn" data-status="<?php echo $order['is_pristine_checked']; ?>" class="btn <?php echo $pristine_class; ?> text-white"><?php echo $pristine_text; ?></button>
            
            <button id="editOrderBtn" class="btn btn-secondary">Edit</button>
            <button id="saveChangesBtn" class="btn btn-primary hidden">Save Changes</button>
            <button id="cancelChangesBtn" class="bg-slate-500 hover:bg-slate-600 text-white font-bold py-2 px-4 rounded-md hidden">Cancel</button>
        <?php endif; ?>
        <?php if ($user_role === 'admin'): ?>
            <button id="deleteOrderBtn" class="btn bg-red-600 hover:bg-red-700 text-white">Delete Order</button>
        <?php endif; ?>
    </div>
</header>
        <?php foreach ($item_pages as $page_index => $page_items): ?>

            <?php // This part shows the Order Summary only on the first page ?>
            <?php if ($page_index === 0): ?>
                <div class="bg-white p-6 rounded-lg shadow-md mb-8">
                    <h2 class="text-xl font-bold text-stone-900 mb-4">Order Summary</h2>
                    <div class="grid grid-cols-2 lg:grid-cols-5 gap-4 text-center">
                        <div class="bg-slate-50 p-3 rounded-lg">
                            <p class="text-sm font-medium text-slate-500">Total PO Value</p>
                            <p class="text-xl font-bold text-slate-800"><?php echo '₱' . number_format($totalPoValue, 2); ?></p>
                        </div>
                        <div class="bg-slate-50 p-3 rounded-lg">
                            <p class="text-sm font-medium text-slate-500">Served Value</p>
                            <p class="text-xl font-bold text-green-600"><?php echo '₱' . number_format($totalServedValue, 2); ?></p>
                        </div>
                        <div class="bg-slate-50 p-3 rounded-lg">
                            <p class="text-sm font-medium text-slate-500">Unserved Value</p>
                            <p class="text-xl font-bold text-red-600"><?php echo '₱' . number_format($totalUnservedValue, 2); ?></p>
                        </div>
                        <div class="bg-slate-50 p-3 rounded-lg">
                            <p class="text-sm font-medium text-slate-500">Unserved Items</p>
                            <p class="text-xl font-bold text-red-600"><?php echo $totalUnservedCount; ?></p>
                        </div>
                        <div class="bg-slate-50 p-3 rounded-lg">
                            <p class="text-sm font-medium text-slate-500">Qty Fill Rate</p>
                            <p class="text-xl font-bold text-indigo-600"><?php echo number_format($qtyFillRate, 1); ?>%</p>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <?php // This part shows the Order Details and Items for every page ?>
            <div class="bg-white p-6 rounded-lg shadow-md mb-8 page-container" style="page-break-after: always;">
                <div class="flex flex-col sm:flex-row justify-between items-start border-b pb-4 mb-4">
                    <div>
                        <h1 class="text-2xl font-bold text-stone-900">Order Details</h1>
                        <p class="text-stone-600">Purchase Order #<?php echo htmlspecialchars($order['po_number']); ?></p>
                        <p class="text-stone-600">Customer: <?php echo htmlspecialchars($order['customer_name'] ?? 'N/A'); ?></p>
                        <p class="text-stone-600">Customer Code: <strong class="font-mono"><?php echo htmlspecialchars($order['customer_code'] ?? 'N/A'); ?></strong></p>
                        <p class="text-stone-600">BU Code: <strong class="font-mono"><?php echo htmlspecialchars($bu_code); ?></strong></p>
                        <p class="text-stone-600">Address: <?php echo htmlspecialchars($order['customer_address'] ?? 'N/A'); ?></p>
                    </div>
                    <div class="text-sm text-stone-700 mt-2 sm:mt-0 sm:text-right">
                        <p><strong>Date:</strong> <?php echo date("F j, Y", strtotime($order['order_date'])); ?></p>
                        <p><strong>Location:</strong> <?php echo htmlspecialchars($order['location']); ?></p>
                        <p><strong>Business Unit:</strong> <?php echo htmlspecialchars($order['bu']); ?></p>
                        <div>
                            <strong>SO Number:</strong>
                            <span class="soNumberText whitespace-pre-wrap"><?php echo htmlspecialchars($so_numbers[$page_index] ?? 'N/A'); ?></span>
                            <textarea class="soNumberInput hidden mt-1 block w-full rounded-md border-slate-300 shadow-sm" data-page-index="<?php echo $page_index; ?>" rows="1"><?php echo htmlspecialchars($so_numbers[$page_index] ?? ''); ?></textarea>
                        </div>
                    </div>
                </div>
                
                <div class="overflow-x-auto">
                    <table class="min-w-full">
                        <thead class="bg-stone-50">
                            <tr>
                                <th class="px-2 py-2 text-center text-xs font-medium text-stone-500 uppercase">#</th>
                                <th class="px-4 py-2 text-left text-xs font-medium text-stone-500 uppercase">Description / SKU</th>
                                <th class="px-4 py-2 text-left text-xs font-medium text-stone-500 uppercase">Quantity</th>
                                <th class="px-4 py-2 text-left text-xs font-medium text-stone-500 uppercase">Price</th>
                                <th class="px-4 py-2 text-center text-xs font-medium text-stone-500 uppercase">Status</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-stone-200">
                            <?php foreach ($page_items as $item_index => $item): 
                                $global_index = ($page_index * 12) + $item_index;
                            ?>
                                <tr class="item-row text-sm" data-item-id="<?php echo $item['id']; ?>" data-original-sku="<?php echo htmlspecialchars($item['sku']); ?>" data-original-status="<?php echo htmlspecialchars($item['status']); ?>">

                                    <td data-label="#" class="px-2 py-2 text-center font-mono text-sm text-slate-500"><?php echo $global_index + 1; ?></td>
                                    <td data-label="Description" class="px-4 py-2">
                                        <p class="font-medium text-slate-800"><?php echo htmlspecialchars($item['description']); ?></p>
                                        <p class="sku-text-display mt-1 font-mono text-sm text-slate-500"><?php echo htmlspecialchars($item['sku']); ?></p>
                                        <select class="sku-select mt-1 block w-full rounded-md border-slate-300 shadow-sm text-xs hidden" disabled></select>
                                    </td>
                                    <td data-label="Quantity" class="px-4 py-2">
                                        <input type="number" value="<?php echo htmlspecialchars($item['quantity']); ?>" class="quantity-input w-20 rounded-md border-slate-300 shadow-sm text-sm" disabled>
                                    </td>
                                    <td data-label="Price" class="px-4 py-2">
                                        <input type="number" step="0.01" value="<?php echo htmlspecialchars($item['price']); ?>" class="price-input w-32 rounded-md border-slate-300 shadow-sm text-sm" disabled>
                                    </td>
                                    <td data-label="Status" class="px-4 py-2 text-center">
                                        <button class="status-toggle-btn px-2 py-1 text-xs leading-5 font-semibold rounded-full" disabled></button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <?php
                // CORRECTED LOGIC: Calculate and display the total for SERVED items on the current page
                $page_served_total = 0;
                foreach ($page_items as $item) :
                    if ($item['status'] === 'served') :
                        $page_served_total += $item['price'];
                    endif;
                endforeach;
                ?>
                <div class="flex justify-end mt-4 pt-4 border-t">
                    <div class="w-full sm:w-auto sm:min-w-[250px]">
                        <div class="flex justify-between font-semibold">
                            <span class="text-slate-600">Page Served Total:</span>
                            <span class="text-slate-900"><?php echo '₱' . number_format($page_served_total, 2); ?></span>
                        </div>
                    </div>
                </div>
                
            </div>
        <?php endforeach; ?>
        
    </div>
    
    <script>
        window.orderId = <?php echo json_encode($orderId); ?>;
        window.orderLocation = <?php echo json_encode($order['location']); ?>;
        window.productsBySku = <?php echo json_encode($products_by_sku); ?>;
    </script>
    <script type="text/javascript" src="https://cdn.jsdelivr.net/npm/toastify-js"></script>
    <script type="module" src="js/view_order.js"></script>
</body>
</html>