<?php
session_start();
// Redirect if not logged in or not an admin/encoder
if (!isset($_SESSION['user_logged_in']) || $_SESSION['user_logged_in'] !== true || !in_array($_SESSION['role'], ['admin', 'encoder'])) {
    header('Location: index.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PDF to Order Review</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" type="text/css" href="https://cdn.jsdelivr.net/npm/toastify-js/src/toastify.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sortablejs@latest/Sortable.min.js"></script>
    <link rel="stylesheet" href="style.css">
</head>
<body class="bg-slate-100">
    <div id="loading-overlay" class="modal-backdrop" style="display: flex; z-index: 9999;">
        <div class="animate-spin rounded-full h-16 w-16 border-t-2 border-b-2 border-white"></div>
    </div>

    <main id="main-content" class="p-4 sm:p-6 lg:p-8">
        <div id="pdfToOrderPage" class="max-w-7xl mx-auto">
            <header class="mb-6 flex justify-between items-center">
                <h1 class="text-3xl font-bold text-slate-800">Review Parsed Order</h1>
                <a href="index.php#admin" class="btn btn-secondary">Back to Admin</a>
            </header>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                <div class="md:col-span-2 space-y-6">
                    <div class="bg-white p-6 rounded-lg shadow-md">
                        <h2 class="text-xl font-semibold mb-4 border-b pb-2">Customer & Order Details</h2>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label for="orderLocation" class="block text-sm font-medium text-slate-700">Order Location (Warehouse)</label>
                                <select id="orderLocation" class="mt-1 block w-full rounded-md border-slate-300 shadow-sm bg-slate-50 font-medium">
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
                            <div>
                                <label for="customerName" class="block text-sm font-medium text-slate-700">Customer Name</label>
                                <input type="text" id="customerName" class="mt-1 block w-full rounded-md border-slate-300 shadow-sm bg-slate-50" readonly>
                            </div>
                            <div>
                                <label for="discountPercentage" class="block text-sm font-medium text-slate-700">Discount (%)</label>
                                <input type="number" id="discountPercentage" class="mt-1 block w-full rounded-md border-slate-300 shadow-sm">
                            </div>
                            <div class="sm:col-span-2">
                                <label for="customerAddress" class="block text-sm font-medium text-slate-700">Customer Address</label>
                                <input type="text" id="customerAddress" class="mt-1 block w-full rounded-md border-slate-300 shadow-sm bg-slate-50" readonly>
                            </div>
                             <div>
                                <label for="poNumber" class="block text-sm font-medium text-slate-700">PO Number</label>
                                <input type="text" id="poNumber" class="mt-1 block w-full rounded-md border-slate-300 shadow-sm bg-slate-50" readonly>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white p-6 rounded-lg shadow-md relative">
                        <h2 class="text-xl font-semibold mb-4 border-b pb-2">Add New Item</h2>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-x-4 gap-y-4 items-end">
                            <div class="sm:col-span-2 relative">
                                <label for="itemDescription" class="block text-sm font-medium text-slate-700">Search Description or SKU</label>
                                <input type="text" id="itemDescription" placeholder="Type to search..." class="mt-1 block w-full rounded-md border-slate-300 shadow-sm">
                                <div id="descriptionSuggestions" class="absolute z-20 w-full bg-white border border-slate-300 rounded-md mt-1 max-h-60 overflow-y-auto hidden"></div>
                            </div>
                             <div class="sm:col-span-1">
                                <label for="itemQuantity" class="block text-sm font-medium text-slate-700">Quantity</label>
                                <input type="number" id="itemQuantity" value="1" class="mt-1 block w-full rounded-md border-slate-300 shadow-sm">
                            </div>
                            <div class="sm:col-span-1">
                                <button id="addItemBtn" class="w-full btn btn-secondary">Add Item to End of List</button>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="md:col-span-1 space-y-6">
                    <div class="bg-white p-6 rounded-lg shadow-md sticky top-8">
                        <h2 class="text-xl font-semibold mb-4">Final Actions</h2>
                        <div class="mt-4 border-t pt-4">
                            <div class="flex justify-between font-bold text-lg">
                                <span class="text-slate-800">Total Price:</span>
                                <span id="orderTotalDisplay" class="text-slate-900">₱0.00</span>
                                </div>
                            <div class="flex justify-between text-sm mt-2">
                                <span class="text-slate-600">PO Items:</span>
                                <span id="poItemCount" class="font-medium text-slate-700">0</span>
                            </div>
                        </div>
                        <div class="mt-6 grid grid-cols-1 gap-2">
                            <button id="processOrderBtn" class="w-full btn btn-primary">Process This Order</button>
                            <a href="index.php#admin" class="w-full btn btn-secondary text-center">Cancel</a>
                        </div>
                    </div>
                </div>

                <div class="md:col-span-3 bg-white p-6 rounded-lg shadow-md">
                     <h2 class="text-xl font-semibold mb-4 border-b pb-2">Order Summary & Item Review <span id="summaryItemCount" class="text-base font-normal text-slate-500">(0 items)</span></h2>
                     <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-stone-200">
                            <thead class="bg-stone-50">
                                <tr>
                                    <th class="px-2 py-2 w-10"></th>
                                    <th class="px-2 py-2 text-left text-xs font-medium text-stone-500 uppercase w-12">#</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-stone-500 uppercase">Description / Code</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-stone-500 uppercase">Select SKU</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-stone-500 uppercase">Quantity</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-stone-500 uppercase">Unit</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-stone-500 uppercase">Price</th>
                                    <th class="px-4 py-2 text-right text-xs font-medium text-stone-500 uppercase">Actions</th>
                                </tr>
                            </thead>
                            <tbody id="orderItemsList" class="bg-white divide-y divide-stone-200">
                                </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <?php include 'components/modals.php'; ?>

    <script type="text/javascript" src="https://cdn.jsdelivr.net/npm/toastify-js"></script>
    <script type="module" src="js/pdf_to_order.js" defer></script>
</body>
</html>