 <div id="stocksDashboardPage" class="hidden">
    <div class="content-card">
        <h2 class="text-2xl font-bold text-slate-800 mb-2">Inventory Stocks</h2>
        <p class="text-slate-500 mb-6">A detailed view of product stock levels, grouped by barcode.</p>
        
        <details class="group border rounded-lg overflow-hidden mb-6">
            <summary class="p-4 font-semibold cursor-pointer flex justify-between items-center text-slate-700 hover:bg-slate-50">
                <span>Show Filters</span>
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5 transition-transform duration-200 group-open:rotate-180"><path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5" /></svg>
            </summary>
            <div class="p-4 border-t border-slate-200 bg-slate-50">
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 items-end">
                    <div class="lg:col-span-2">
                        <label for="stockSearchInput" class="block text-sm font-medium text-slate-700">Search SKU or Description</label>
                        <input type="text" id="stockSearchInput" placeholder="Search..." class="mt-1 block w-full rounded-md border-slate-300 shadow-sm">
                    </div>
                    <div>
                        <label for="locFilterStocks" class="block text-sm font-medium text-slate-700">Location</label>
                        <select id="locFilterStocks" class="mt-1 block w-full rounded-md border-slate-300 shadow-sm"><option value="Davao">Davao</option><option value="Gensan">Gensan</option></select>
                    </div>
                    <div>
                        <label for="buFilter" class="block text-sm font-medium text-slate-700">Business Unit</label>
                        <select id="buFilter" class="mt-1 block w-full rounded-md border-slate-300 shadow-sm"><option value="all">All BUs</option><option value="Health">Health</option><option value="Hygiene">Hygiene</option><option value="Nutri">Nutri</option></select>
                    </div>
                    <div>
                        <label for="stockStatusFilter" class="block text-sm font-medium text-slate-700">Stock Status</label>
                        <select id="stockStatusFilter" class="mt-1 block w-full rounded-md border-slate-300 shadow-sm"><option value="all">All Stocks</option><option value="in_stock">In Stock</option><option value="low_stock">Low Stock</option><option value="no_stock">No Stock</option></select>
                    </div>
                </div>
            </div>
        </details>
        
        <table class="data-table">
            <thead>
                <tr>
                    <th>Barcode / SKU</th>
                    <th>Description</th>
                    <th>Stock on Hand</th>
                </tr>
            </thead>
            <tbody id="stocksDashboardList"></tbody>
        </table>
    </div>
</div>