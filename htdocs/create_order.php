<?php
session_start();
// Redirect if not logged in or not an admin/encoder
if (!isset($_SESSION['user_logged_in']) || $_SESSION['user_logged_in'] !== true || !in_array($_SESSION['role'], ['admin', 'encoder'])) {
    header('Location: login.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create New Order</title>
    <script>
        // Pass PHP session role to JavaScript
        window.userRole = <?php echo json_encode($_SESSION['role'] ?? 'viewer'); ?>;
    </script>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Lato:wght@400;700&family=Inter:wght@400;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" type="text/css" href="https://cdn.jsdelivr.net/npm/toastify-js/src/toastify.min.css">
    <link rel="stylesheet" href="style.css">
</head>
    <body id="app-body" class="bg-slate-100">

    <div id="loading-overlay" class="modal-backdrop" style="display: none; z-index: 9999;">
        <div class="animate-spin rounded-full h-16 w-16 border-t-2 border-b-2 border-white"></div>
    </div>

    <?php include __DIR__ . '/header.php'; ?>

    <main id="main-content" class="flex-1 p-4 sm:p-6 lg:p-8">
        <div id="page-content-wrapper" class="max-w-7xl mx-auto">
            <div id="encoderPage">
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                    
                    <div id="entry-forms-column" class="lg:col-span-2 space-y-6">
                        <div class="bg-white p-4 sm:p-6 rounded-lg shadow-md">
                            <h2 class="text-lg sm:text-xl font-semibold mb-4 border-b pb-2">Customer & Order Details</h2>
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div>
                                    <label for="orderLocation" class="block text-sm font-medium text-slate-700">Order Location (Warehouse)</label>
                                    <select id="orderLocation" class="mt-1 block w-full rounded-md border-slate-300 shadow-sm bg-slate-50 font-medium">
                                        <option value="">-- Select Location --</option>
                                        <option value="Davao">Davao</option>
                                        <option value="Gensan">Gensan</option>
                                    </select>
                                </div>
                                <div>
                                    <label for="orderBu" class="block text-sm font-medium text-slate-700">Business Unit</label>
                                    <select id="orderBu" class="mt-1 block w-full rounded-md border-slate-300 shadow-sm bg-slate-50 font-medium">
                                        <option value="">-- Select BU --</option>
                                        <option value="Health">Health</option>
                                        <option value="Hygiene">Hygiene</option>
                                        <option value="Nutri">Nutri</option>
                                    </select>
                                </div>
                                <div class="sm:col-span-2">
                                    <label for="customerName" class="block text-sm font-medium text-slate-700">Customer Name</label>
                                    <div class="relative">
                                        <input type="text" id="customerName" placeholder="Type to search..." class="mt-1 block w-full rounded-md border-slate-300 shadow-sm">
                                        <div id="customerSuggestions" class="absolute z-30 w-full bg-white border border-slate-300 rounded-md mt-1 max-h-60 overflow-y-auto hidden"></div>
                                    </div>
                                </div>
                                <div class="relative"> <label for="customerAddress" class="block text-sm font-medium text-slate-700">Customer Address</label>
                                    <input type="text" id="customerAddress" placeholder="Type to search for an address..." class="mt-1 block w-full rounded-md border-slate-300 shadow-sm">
                                    <div id="addressSuggestions" class="absolute z-30 w-full bg-white border border-slate-300 rounded-md mt-1 max-h-60 overflow-y-auto hidden"></div>
                                </div>
                                <div>
                                    <label for="poNumber" class="block text-sm font-medium text-slate-700">PO Number</label>
                                    <input type="text" id="poNumber" placeholder="Enter PO Number" class="mt-1 block w-full rounded-md border-slate-300 shadow-sm">
                                </div>
                                <div>
                                    <label for="discountPercentage" class="block text-sm font-medium text-slate-700">Discount (%)</label>
                                    <input type="number" id="discountPercentage" placeholder="e.g., 5" class="mt-1 block w-full rounded-md border-slate-300 shadow-sm">
                                </div>
                                <div>
                                    <label for="customerCode" class="block text-sm font-medium text-slate-700">Customer Code</label>
                                    <input type="text" id="customerCode" class="mt-1 block w-full rounded-md border-slate-300 shadow-sm bg-slate-50" readonly>
                                </div>
                            </div>
                        </div>

                        <fieldset id="itemEntryFieldset" disabled class="bg-white p-4 sm:p-6 rounded-lg shadow-md relative">
                            <div id="itemEntryOverlay" class="absolute inset-0 bg-slate-50 bg-opacity-70 z-10 flex items-center justify-center rounded-lg"><span class="font-semibold text-slate-500">Select Location & BU first</span></div>
                            <h2 class="text-lg sm:text-xl font-semibold mb-4 border-b pb-2">Add Order Items</h2>
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-x-4 gap-y-4 items-end">
                                <div class="sm:col-span-2 relative">
                                    <label for="itemBarcode" class="block text-sm font-medium text-slate-700">Barcode / SKU</label>
                                    <input type="text" id="itemBarcode" placeholder="Scan or type Barcode/SKU..." class="mt-1 block w-full rounded-md border-slate-300 shadow-sm">
                                    <div id="barcodeSuggestions" class="absolute z-20 w-full bg-white border border-slate-300 rounded-md mt-1 max-h-60 overflow-y-auto hidden"></div>
                                </div>
                                <div class="sm:col-span-2 relative">
                                    <label for="itemDescription" class="block text-sm font-medium text-slate-700">Description</label>
                                    <input type="text" id="itemDescription" placeholder="Search by description..." class="mt-1 block w-full rounded-md border-slate-300 shadow-sm">
                                    <div id="descriptionSuggestions" class="absolute z-20 w-full bg-white border border-slate-300 rounded-md mt-1 max-h-60 overflow-y-auto hidden"></div>
                                </div>
                                <div id="skuSelectionContainer" class="hidden sm:col-span-2">
                                    <label for="itemSkuSelect" class="block text-sm font-medium text-slate-700">Select SKU</label>
                                    <div class="flex items-center space-x-2">
                                        <select id="itemSkuSelect" class="mt-1 block w-full rounded-md border-slate-300 shadow-sm"></select>
                                        <span id="skuStockDisplay" class="mt-1 text-sm text-slate-600 font-medium whitespace-nowrap"></span>
                                    </div>
                                </div>
                                <div class="sm:col-span-2 grid grid-cols-1 sm:grid-cols-3 gap-2 items-end">
                                    <div><label for="itemQuantity" class="block text-sm font-medium text-slate-700">Quantity</label><input type="number" id="itemQuantity" value="1" class="mt-1 block w-full rounded-md border-slate-300 shadow-sm"></div>
                                    <div><label for="itemUnit" class="block text-sm font-medium text-slate-700">Unit</label><select id="itemUnit" class="mt-1 block w-full rounded-md border-slate-300 shadow-sm"><option value="pcs">Pcs</option><option value="case">Case</option></select></div>
                                    <span id="caseInfoDisplay" class="text-xs text-slate-500 whitespace-nowrap self-center pb-2"></span>
                                </div>
                                <div class="sm:col-span-2"><label for="itemPrice" class="block text-sm font-medium text-slate-700">Total Price</label><input type="number" id="itemPrice" placeholder="0.00" class="mt-1 block w-full rounded-md border-slate-300 shadow-sm bg-slate-50" readonly></div>
                                <div id="formActions" class="sm:col-span-2"><button id="addItemBtn" class="w-full btn btn-primary">Add Item</button></div>
                            </div>
                        </fieldset> 
                    </div>

                    <div id="summary-column" class="lg:col-span-1 space-y-6">
                        <fieldset id="summaryFieldset" disabled class="bg-white p-4 sm:p-6 rounded-lg shadow-md sticky top-8 relative">
                             <div id="summaryOverlay" class="absolute inset-0 bg-slate-50 bg-opacity-70 z-10 flex items-center justify-center rounded-lg"><span class="font-semibold text-slate-500">Select Location & BU first</span></div>
                            <h2 class="text-lg sm:text-xl font-semibold mb-4">Order Summary</h2>
                            <div class="mt-2 border rounded-lg overflow-x-auto max-h-[30rem] min-h-[10rem]">
                                <table class="data-table min-w-full divide-y divide-slate-200">
                                    <thead>
                                        <tr>
                                            <th class="px-4 py-2 text-left text-xs font-medium text-slate-500 uppercase">Description / SKU</th>
                                            <th class="px-4 py-2 text-center text-xs font-medium text-slate-500 uppercase">Qty</th>
                                            <th class="px-4 py-2 text-right text-xs font-medium text-slate-500 uppercase">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody id="orderItemsList" class="bg-white divide-y divide-slate-200"></tbody>
                                </table>
                            </div>
                            <div class="mt-4 border-t pt-4">
                                <div class="flex justify-between font-bold text-lg"><span class="text-slate-800">Total Price:</span><span id="orderTotalDisplay" class="text-slate-900">₱0.00</span></div>
                            </div>
                            <div id="main-actions" class="mt-6 grid grid-cols-1 gap-2">
                                <button id="submitOrderBtn" class="w-full btn btn-primary">Submit Order</button>
                                <button id="cancelOrderBtn" class="w-full btn btn-secondary text-center">Clear & Reset</button>
                            </div>
                        </fieldset>
                    </div>

                </div>
            </div>
        </div>
    </main>

    <?php include __DIR__ . '/components/modals.php'; ?>
    
    <script type="text/javascript" src="https://cdn.jsdelivr.net/npm/toastify-js"></script>
    <script type="module" src="js/encoder.js"></script>
    <script src="js/global.js" defer></script>
</body>
</html>