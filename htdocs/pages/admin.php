<div id="adminPage" class="hidden">
    <div class="mb-6 flex flex-wrap gap-2 border-b border-slate-300 bg-white p-2 rounded-t-lg">
        <button id="inventoryAdminBtn" class="admin-tab-btn active py-2 px-4 border-b-2 border-indigo-500 font-semibold text-indigo-600">Inventory</button>
        <button id="bulkOrderAdminBtn" class="admin-tab-btn py-2 px-4 text-slate-500 hover:border-slate-300 hover:text-slate-700 border-b-2 border-transparent">PDF Order Entry</button>
        <button id="customerAdminBtn" class="admin-tab-btn py-2 px-4 text-slate-500 hover:border-slate-300 hover:text-slate-700 border-b-2 border-transparent">Customers</button>
        <button id="exportAdminBtn" class="admin-tab-btn py-2 px-4 text-slate-500 hover:border-slate-300 hover:text-slate-700 border-b-2 border-transparent">Export</button>
    </div>

    <div class="content-card max-w-xl mx-auto mb-8">
        <h2 class="text-xl font-bold text-slate-800 mb-4">Dashboard Date Control</h2>
        <p class="text-sm text-slate-600 mb-4">Set the month and year that will be displayed on the Main Dashboard and the Rojon Dashboard. This will be the default view for all users until it's changed again.</p>
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 items-end">
            <div>
                <label for="displayMonth" class="block text-sm font-medium text-slate-700">Month</label>
                <select id="displayMonth" class="mt-1 block w-full rounded-md border-slate-300 shadow-sm">
                    <?php for ($m = 1; $m <= 12; $m++): ?>
                        <option value="<?php echo $m; ?>"><?php echo date('F', mktime(0, 0, 0, $m, 10)); ?></option>
                    <?php endfor; ?>
                </select>
            </div>
            <div>
                <label for="displayYear" class="block text-sm font-medium text-slate-700">Year</label>
                <select id="displayYear" class="mt-1 block w-full rounded-md border-slate-300 shadow-sm">
                    <?php 
                    $currentYear = date('Y');
                    for ($y = $currentYear + 1; $y >= $currentYear - 3; $y--): ?>
                        <option value="<?php echo $y; ?>"><?php echo $y; ?></option>
                    <?php endfor; ?>
                </select>
            </div>
            <div>
                <button id="setDisplayMonthBtn" class="w-full btn btn-primary">Set Display Month</button>
            </div>
        </div>
    </div>
    <div id="adminInventorySection" class="space-y-6">
        <div class="mb-4 flex items-center gap-4 border-b border-slate-200 bg-white p-4 rounded-lg">
            <button data-tab="manageStock" class="inventory-sub-tab-btn py-2 px-1 text-sm font-semibold border-b-2 border-indigo-500 text-indigo-600">Current Inventory</button>
            <button data-tab="bulkOps" class="inventory-sub-tab-btn py-2 px-1 text-sm font-medium border-b-2 border-transparent text-slate-500 hover:text-slate-700">Bulk Operations</button>
            <button data-tab="reports" class="inventory-sub-tab-btn py-2 px-1 text-sm font-medium border-b-2 border-transparent text-slate-500 hover:text-slate-700">Reports & Audits</button>
        </div>
        <div id="manageStockTab" class="inventory-sub-tab-content">
            <div class="content-card">
                <h2 class="text-2xl font-bold text-slate-800 mb-2">Current Inventory</h2>
                <p class="text-slate-500 mb-6">Live stock levels for the location selected in 'Bulk Operations'.</p>
                <table class="data-table">
                    <thead><tr><th>SKU</th><th>Description</th><th>Stock</th><th class="text-right">Actions</th></tr></thead>
                    <tbody id="adminProductList"></tbody>
                </table>
                <div class="flex justify-between items-center mt-6">
                    <button id="invPrevBtn" class="bg-slate-200 text-slate-700 py-1 px-3 rounded-md hover:bg-slate-300 disabled:opacity-50" disabled>&lt; Prev</button>
                    <span id="invPageInfo" class="text-sm font-medium text-slate-700">Page 1 of 1</span>
                    <button id="invNextBtn" class="bg-slate-200 text-slate-700 py-1 px-3 rounded-md hover:bg-slate-300 disabled:opacity-50" disabled>Next &gt;</button>
                </div>
            </div>
        </div>
        <div id="bulkOpsTab" class="inventory-sub-tab-content hidden">
            <div class="flex flex-col gap-8 max-w-2xl">
                <div class="content-card">
                    <label for="adminLocFilter" class="block text-sm font-medium text-slate-700">Manage Inventory For Location:</label>
                    <select id="adminLocFilter" class="mt-1 block w-full rounded-md border-slate-300 shadow-sm"><option value="Davao">Davao</option><option value="Gensan">Gensan</option></select>
                </div>
                <div class="content-card">
                    <h2 class="text-xl font-semibold mb-4">Bulk Add New Products & Codes</h2>
                    <div class="space-y-4">
                        <div>
                            <label for="bulkAddProductsInput" class="block text-sm font-medium text-slate-700">Paste Data Below</label>
                            <p class="text-xs text-slate-500">Format: BU Barcode SKU(s) Description [PROMO SKU] [Pcs/Case]</p>
                            <textarea id="bulkAddProductsInput" rows="6" class="mt-1 block w-full rounded-md border-slate-300 shadow-sm" placeholder="Nutri 8712045039953 3286526..."></textarea>
                        </div>
                        <button id="processBulkAddProductsBtn" class="w-full bg-indigo-600 text-white py-2 px-4 rounded-md hover:bg-indigo-700">Process New Products</button>
                    </div>
                </div>
                <div class="content-card">
                    <h2 class="text-xl font-semibold mb-4">Bulk Update Stock Levels</h2>
                    <div class="space-y-4">
                        <div>
                            <label for="bulkUpdateStockInput" class="block text-sm font-medium text-slate-700">Paste Stock Data Below</label>
                            <p class="text-xs text-slate-500"><strong>Format: SKU Description... Quantity Price</strong></p>
                            <textarea id="bulkUpdateStockInput" rows="6" class="mt-1 block w-full rounded-md border-slate-300 shadow-sm" placeholder="1558051 LYSOL DISINF SPRY 1,075..."></textarea>
                        </div>
                        <button id="processBulkUpdateStockBtn" class="w-full bg-indigo-600 text-white py-2 px-4 rounded-md hover:bg-indigo-700">Process Stock Updates</button>
                    </div>
                </div>
                <div class="content-card">
                    <h2 class="text-xl font-semibold mb-4">Bulk Add Customer SKU Aliases</h2>
                    <div class="space-y-4">
                        <div>
                            <label for="bulkAddAliasInput" class="block text-sm font-medium text-slate-700">Paste Customer Data Below</label>
                            <p class="text-xs text-slate-500"><strong>Format: CustomerSKU Description... MasterBarcode</strong></p>
                            <textarea id="bulkAddAliasInput" rows="6" class="mt-1 block w-full rounded-md border-slate-300 shadow-sm" placeholder="69276 DUREX CONDOMS EXTRA SAFE 3S..."></textarea>
                        </div>
                        <button id="processBulkAddAliasBtn" class="w-full bg-indigo-600 text-white py-2 px-4 rounded-md hover:bg-indigo-700">Process SKU Aliases</button>
                    </div>
                </div>
            </div>
        </div>
        <div id="reportsTab" class="inventory-sub-tab-content hidden">
            <div class="content-card max-w-2xl">
                <h2 class="text-xl font-semibold mb-4">Data Quality Report</h2>
                <p class="text-sm text-slate-600 mb-4">Find SKUs that are not linked to a barcode.</p>
                <button id="runUnlinkedSkuReportBtn" class="w-full bg-amber-600 text-white py-2 px-4 rounded-md hover:bg-amber-700">Find Unlinked SKUs</button>
                <div id="unlinkedSkuResults" class="hidden mt-4">
                    <h3 class="text-lg font-semibold mb-2">Report Results:</h3>
                    <table class="data-table"><thead><tr><th>SKU</th><th>Description</th><th>Current Stock</th></tr></thead><tbody id="unlinkedSkuList"></tbody></table>
                </div>
            </div>
        </div>
    </div>

    <div id="adminBulkOrderSection" class="hidden">
    <div class="content-card max-w-4xl mx-auto">
        <h2 class="text-2xl font-bold text-slate-800 mb-2">Parse Order from PDF Text</h2>
        <p class="text-sm text-slate-500 mb-6">Select the order's destination, copy the entire text from a supplier PDF, paste it below, and click parse to create a new order for review.</p>
        
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-4">
            <div>
                <label for="pdfParseLocation" class="block text-sm font-medium text-slate-700">Order Location (Warehouse)</label>
                <select id="pdfParseLocation" class="mt-1 block w-full rounded-md border-slate-300 shadow-sm font-medium">
                    <option value="">-- Select Location --</option>
                    <option value="Davao">Davao</option>
                    <option value="Gensan">Gensan</option>
                </select>
            </div>
            <div>
                <label for="pdfParseBu" class="block text-sm font-medium text-slate-700">Business Unit</label>
                <select id="pdfParseBu" class="mt-1 block w-full rounded-md border-slate-300 shadow-sm font-medium">
                    <option value="">-- Select BU (Optional) --</option>
                    <option value="Health">Health</option>
                    <option value="Hygiene">Hygiene</option>
                    <option value="Nutri">Nutri</option>
                </select>
            </div>
        </div>
        <div class="space-y-4">
            <div>
                <label for="rawPdfInput" class="block text-sm font-medium text-slate-700">Raw Pasted Text from PDF</label>
                <textarea id="rawPdfInput" rows="15" class="mt-1 block w-full font-mono text-xs rounded-md border-slate-300 shadow-sm" placeholder="Paste full text from PDF here..."></textarea>
            </div>
            <div class="flex justify-end">
                <button id="parsePdfTextBtn" class="btn btn-primary">Parse Text & Review Order</button>
            </div>
        </div>
    </div>
</div>

    <div id="adminCustomerSection" class="hidden">
        <div class="content-card max-w-2xl mx-auto">
            <h2 class="text-2xl font-bold text-slate-800 mb-6">Manage Customers</h2>
            <form id="addCustomerForm" class="flex gap-2 mb-6">
            <input type="text" id="newCustomerName" placeholder="Enter new customer name" class="flex-grow block w-full rounded-md border-slate-300 shadow-sm">
            <button type="submit" class="bg-indigo-600 text-white py-2 px-4 rounded-md hover:bg-indigo-700">Add</button>
            </form>
            <div id="customerManagementList" class="space-y-2"></div>
        </div>
        <div class="content-card max-w-4xl mx-auto mt-8">
            <h2 class="text-2xl font-bold text-slate-800 mb-2">Manage Customer Address Codes</h2>
            <p class="text-sm text-slate-500 mb-6">This table maps a customer's address (from the PO) to their specific Customer Code.</p>
            <div class="mb-4">
                <button id="addAddressCodeBtn" class="btn btn-secondary text-sm">Add New Mapping</button>
            </div>
            <div class="overflow-x-auto">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Address</th>
                            <th>Customer Code</th>
                            <th class="text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody id="addressCodeList"></tbody>
                </table>
            </div>
        </div>
    </div>

    <div id="adminExportSection" class="hidden">
    <div class="content-card max-w-2xl mx-auto">
        <h2 class="text-2xl font-bold text-slate-800 mb-4">Export Order Data</h2>
        <p class="text-sm text-slate-600 mb-6">Select a month, year, and location to download order data.</p>
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
            <div>
                    <label for="exportLoc" class="block text-sm font-medium text-slate-700">Location</label>
                    <select id="exportLoc" class="mt-1 block w-full rounded-md border-slate-300 shadow-sm"><option value="all">All Locations</option><option value="Davao">Davao</option><option value="Gensan">Gensan</option></select>
            </div>
            <div>
                    <label for="exportMonth" class="block text-sm font-medium text-slate-700">Month</label>
                    <select id="exportMonth" class="mt-1 block w-full rounded-md border-slate-300 shadow-sm"><option value="1">January</option><option value="2">February</option><option value="3">March</option><option value="4">April</option><option value="5">May</option><option value="6">June</option><option value="7">July</option><option value="8">August</option><option value="9">September</option><option value="10">October</option><option value="11">November</option><option value="12">December</option></select>
            </div>
            <div>
                    <label for="exportYear" class="block text-sm font-medium text-slate-700">Year</label>
                    <input type="number" id="exportYear" placeholder="e.g., 2025" class="mt-1 block w-full rounded-md border-slate-300 shadow-sm">
            </div>
        </div>
        <div class="mt-6 grid grid-cols-1 sm:grid-cols-2 gap-4">
                <button id="exportCsvBtn" class="w-full bg-emerald-600 text-white py-2 px-4 rounded-md hover:bg-emerald-700">Download as CSV</button>
                <button id="exportTsvBtn" class="w-full bg-indigo-600 text-white py-2 px-4 rounded-md hover:bg-indigo-700">Download for Excel (TSV)</button>
        </div>
    </div>
    </div>
</div>