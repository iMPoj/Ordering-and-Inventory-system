<?php
session_start();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rojon Pharmacy Corporation - Dashboard</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
    <style>
        .filter-btn.active {
            background-color: #dc2626; /* red-600 */
            color: white;
            font-weight: 600;
        }
    </style>
</head>
<body class="bg-slate-100">
    <div id="loading-overlay" class="modal-backdrop" style="display: none; z-index: 9999;">
        <div class="animate-spin rounded-full h-16 w-16 border-t-2 border-b-2 border-white"></div>
    </div>

    <div class="container mx-auto p-4 md:p-8">
        <header class="text-center my-8">
            <h1 class="text-4xl md:text-5xl font-black uppercase tracking-wider">
                <span class="text-red-600">Rojon</span>
                <span class="text-slate-800">Pharmacy Corporation</span>
            </h1>
            <p class="text-slate-500 mt-2">Performance Dashboard</p>
        </header>

        <div class="sticky top-0 z-30 bg-slate-100/80 backdrop-blur-sm py-4 mb-6">
            <div class="content-card flex flex-col sm:flex-row items-center justify-center sm:justify-between gap-4">
                <div class="flex items-center gap-2">
                    <span class="text-sm font-medium text-slate-700">Location:</span>
                    <div id="location-filter-group" class="flex rounded-md shadow-sm bg-slate-100 p-1 text-sm">
                        <button data-location="all" class="filter-btn active px-4 py-1 rounded-md">All</button>
                        <button data-location="Davao" class="filter-btn px-4 py-1 rounded-md">Davao</button>
                        <button data-location="Gensan" class="filter-btn px-4 py-1 rounded-md">Gensan</button>
                    </div>
                </div>
                 <div class="flex items-center gap-2">
                    <span class="text-sm font-medium text-slate-700">Business Unit:</span>
                    <div id="bu-filter-group" class="flex rounded-md shadow-sm bg-slate-100 p-1 text-sm">
                        <button data-bu="all" class="filter-btn active px-4 py-1 rounded-md">All</button>
                        <button data-bu="Health" class="filter-btn px-4 py-1 rounded-md">Health</button>
                        <button data-bu="Hygiene" class="filter-btn px-4 py-1 rounded-md">Hygiene</button>
                        <button data-bu="Nutri" class="filter-btn px-4 py-1 rounded-md">Nutri</button>
                    </div>
                </div>
            </div>
        </div>

        <main class="space-y-6">
            <div id="bu-performance-container" class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                </div>

            <div class="content-card">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="text-xl font-bold text-slate-800">Recent Sales</h3>
                    <a href="rojon_orders.php" class="text-sm font-semibold text-indigo-600 hover:text-indigo-800">View All Orders →</a>
                </div>
                <div class="overflow-x-auto">
                    <table class="data-table text-sm w-full">
                        <thead>
                            <tr>
                                <th>PO Number / Address</th>
                                <th>SO Number(s)</th>
                                <th class="text-right">Total Amount</th>
                                <th class="text-right">Date</th>
                            </tr>
                        </thead>
                        <tbody id="recent-sales-body" class="divide-y divide-slate-200"></tbody>
                    </table>
                </div>
            </div>

            <div class="content-card">
                <h3 class="text-xl font-bold text-slate-800 mb-4">Unserved Items Details</h3>
                <div class="overflow-x-auto max-h-[40rem]">
                    <table class="data-table text-sm w-full">
                        <thead class="sticky top-0">
                            <tr>
                                <th>Description</th>
                                <th>SKU / Barcode</th>
                                <th class="text-center">Total Qty</th>
                                <th class="text-right">Total Value</th>
                            </tr>
                        </thead>
                        <tbody id="unservedItemsTableBody" class="divide-y divide-slate-200"></tbody>
                    </table>
                </div>
            </div>
        </main>
    </div>
    
    <script type="text/javascript" src="https://cdn.jsdelivr.net/npm/toastify-js"></script>
    <script type="module" src="js/rojon_dashboard.js" defer></script>
</body>
</html>