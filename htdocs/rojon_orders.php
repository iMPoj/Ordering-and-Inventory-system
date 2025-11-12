<?php
session_start();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rojon Pharmacy - All Orders</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;900&display=swap" rel="stylesheet">
    
    <link rel="stylesheet" type="text/css" href="https://cdn.jsdelivr.net/npm/toastify-js/src/toastify.min.css">
    <script type="text/javascript" src="https://cdn.jsdelivr.net/npm/toastify-js"></script>
    
    <link rel="stylesheet" href="style.css">
    <style>
        body { font-family: 'Inter', sans-serif; }
        .filter-btn.active { background-color: #dc2626; color: white; font-weight: 600; }
        .pagination-btn { padding: 0.5rem 1rem; border: 1px solid #cbd5e1; border-radius: 0.375rem; background-color: white; font-weight: 500; cursor: pointer; transition: background-color 0.2s; }
        .pagination-btn:hover:not(:disabled) { background-color: #f1f5f9; }
        .pagination-btn:disabled { opacity: 0.5; cursor: not-allowed; }
        @media (max-width: 768px) {
            .data-table thead { display: none; }
            .data-table tr { display: block; border-bottom: 2px solid #e2e8f0; margin-bottom: 1rem; }
            .data-table td { display: block; text-align: right; padding-left: 50%; position: relative; }
            .data-table td::before { content: attr(data-label); position: absolute; left: 0.75rem; width: calc(50% - 1.5rem); padding-right: 0.75rem; font-weight: 600; text-align: left; white-space: nowrap; color: #475569; }
        }
    </style>
</head>
<body class="bg-slate-100">
    <div id="loading-overlay" class="modal-backdrop" style="display: flex; z-index: 9999;">
        <div class="animate-spin rounded-full h-16 w-16 border-t-2 border-b-2 border-white"></div>
    </div>

    <div class="container mx-auto p-4 md:p-8">
        <header class="text-center my-8">
            <h1 class="text-4xl md:text-5xl font-black uppercase tracking-wider">
                <span class="text-red-600">Rojon</span>
                <span class="text-slate-800">Pharmacy Corporation</span>
            </h1>
            <p class="text-slate-500 mt-2">All Orders</p>
        </header>

        <div class="mb-4 text-center">
             <a href="rojon_dashboard.php" class="text-indigo-600 hover:text-indigo-800 font-medium">← Back to Dashboard</a>
        </div>

        <main class="space-y-8">
            <div class="content-card flex flex-col lg:flex-row items-center justify-between gap-4">
                <div class="flex flex-col sm:flex-row items-center gap-x-6 gap-y-4">
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
                <div class="relative w-full lg:w-auto">
                    <input type="text" id="search-input" placeholder="Search PO or SO..." class="filter-input w-full sm:w-64">
                    <svg class="absolute top-1/2 right-3 -translate-y-1/2 w-5 h-5 text-slate-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z" /></svg>
                </div>
            </div>

            <div class="bg-white p-6 rounded-lg shadow-md">
                 <h3 class="text-lg font-bold text-slate-800 mb-4">Order List <span id="order-count" class="text-base font-normal text-slate-500">(0 orders)</span></h3>
                <div class="overflow-x-auto max-h-[60rem]">
                    <table class="data-table text-sm w-full">
                        <thead class="sticky top-0">
                            <tr>
                                <th>Date</th>
                                <th>PO Number / Address</th>
                                <th>SO Number(s)</th>
                                <th class="text-center">Action</th>
                            </tr>
                        </thead>
                        <tbody id="orders-table-body">
                        </tbody>
                    </table>
                </div>
                <div id="pagination-controls" class="flex items-center justify-between mt-4">
                </div>
            </div>
        </main>
    </div>

    <script type="module" src="js/rojon_orders.js" defer></script>
</body>
</html>