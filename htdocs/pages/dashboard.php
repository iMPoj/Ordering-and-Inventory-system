 <div id="dashboardPage">
    <div class="space-y-6"> 

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-6">
            <div class="bg-white p-5 rounded-lg shadow-md flex items-center justify-between gap-3">
                <div class="min-w-0 flex-1">
                    <h3 class="text-sm font-medium text-slate-500 uppercase truncate">Total Served Value</h3>
                    <p id="stat-total-served" class="text-xl md:text-2xl xl:text-3xl font-bold text-slate-900 mt-1">₱0.00</p>
                </div>
                <div class="bg-slate-100 rounded-full p-2.5 flex-shrink-0">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v.01M12 16v-1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
            </div>
            <div class="bg-white p-5 rounded-lg shadow-md flex items-center justify-between gap-3">
                <div class="min-w-0 flex-1">
                    <h3 class="text-sm font-medium text-slate-500 uppercase truncate">Total Served Qty</h3>
                    <p id="stat-total-qty" class="text-xl md:text-2xl xl:text-3xl font-bold text-slate-900 mt-1">0</p>
                </div>
                <div class="bg-slate-100 rounded-full p-2.5 flex-shrink-0">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4" />
                    </svg>
                </div>
            </div>
            <div class="bg-white p-5 rounded-lg shadow-md flex items-center justify-between gap-3">
                <div class="min-w-0 flex-1">
                    <h3 class="text-sm font-medium text-slate-500 uppercase truncate">Qty Fill Rate (by PO)</h3>
                    <p id="stat-qty-fill-rate-by-po" class="text-xl md:text-2xl xl:text-3xl font-bold text-indigo-600 mt-1">0.0%</p>
                </div>
                <div class="bg-indigo-100 rounded-full p-2.5 flex-shrink-0">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-indigo-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M11 3.055A9.001 9.001 0 1020.945 13H11V3.055z" />
                        <path stroke-linecap="round" stroke-linejoin="round" d="M20.488 9H15V3.512A9.025 9.025 0 0120.488 9z" />
                    </svg>
                </div>
            </div>
            <div class="bg-white p-5 rounded-lg shadow-md flex items-center justify-between gap-3">
                <div class="min-w-0 flex-1">
                    <h3 class="text-sm font-medium text-slate-500 uppercase truncate">Unserved SKUs</h3>
                    <p id="stat-unserved-skus" class="text-xl md:text-2xl xl:text-3xl font-bold text-red-600 mt-1">0</p>
                </div>
                <div class="bg-red-100 rounded-full p-2.5 flex-shrink-0">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-red-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                    </svg>
                </div>
            </div>
            <div class="bg-white p-5 rounded-lg shadow-md flex items-center justify-between gap-3">
                <div class="min-w-0 flex-1">
                    <h3 class="text-sm font-medium text-slate-500 uppercase truncate">Unserved Value</h3>
                    <p id="stat-total-unserved-value" class="text-xl md:text-2xl xl:text-3xl font-bold text-red-600 mt-1">₱0.00</p>
                </div>
                <div class="bg-red-100 rounded-full p-2.5 flex-shrink-0">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-red-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v.01M12 16v-1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
            </div>
        </div>
     
     <div class="content-card mt-6">
        <h2 class="text-2xl font-bold text-slate-800 mb-4">Sales Summary by Business Unit</h2>
        <div id="sales-summary-container" class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            </div>
    </div>
     
        <details class="bg-white rounded-lg shadow-md overflow-hidden">
            <summary class="p-6 font-semibold cursor-pointer flex justify-between items-center text-slate-800 hover:bg-slate-50">
                <span>Show Dashboard Filters</span>
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5 transition-transform duration-200 open-arrow">
                  <path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5" />
                </svg>
            </summary>
            <style> details[open] .open-arrow { transform: rotate(180deg); } </style>
            <div class="p-6 border-t border-slate-200">
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4 items-end">
                    <div>
                        <label for="locFilterDashboard" class="block text-sm font-medium text-slate-700">Location</label>
                        <select id="locFilterDashboard" class="mt-1 block w-full rounded-md border-slate-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                            <option value="all">All Locations</option>
                            <option value="Davao">Davao</option>
                            <option value="Gensan">Gensan</option>
                        </select>
                    </div>
                    <div>
                        <label for="buFilterDashboard" class="block text-sm font-medium text-slate-700">Business Unit</label>
                        <select id="buFilterDashboard" class="mt-1 block w-full rounded-md border-slate-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                            <option value="all">All BUs</option>
                            <option value="Health">Health</option>
                            <option value="Hygiene">Hygiene</option>
                            <option value="Nutri">Nutri</option>
                        </select>
                    </div>
                    <div>
                        <label for="customerFilter" class="block text-sm font-medium text-slate-700">Customer</label>
                        <select id="customerFilter" class="mt-1 block w-full rounded-md border-slate-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                            <option value="all">All Customers</option>
                        </select>
                    </div>
                </div>
            </div>
        </details>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div class="bg-white rounded-lg shadow-md p-6">
                 <h3 class="text-lg font-semibold mb-4 text-slate-900">Fulfillment by Price</h3>
                <div class="h-48 relative">
                    <canvas id="fulfillmentChartPrice"></canvas>
                </div>
            </div>
            <div class="bg-white rounded-lg shadow-md p-6">
                <h3 class="text-lg font-semibold mb-4 text-slate-900">Fulfillment by Quantity</h3>
                <div class="h-48 relative">
                    <canvas id="fulfillmentChartQty"></canvas>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-5 gap-6">
    <div class="lg:col-span-3 content-card">
        <div class="flex justify-between items-center mb-4">
            <h3 class="text-lg font-bold text-slate-800">Unserved Items Details</h3>
            <button id="copyUnservedBtn" class="btn btn-secondary text-sm">Copy Table</button>
        </div>
        <div class="overflow-y-auto max-h-[30rem]">
            <table class="data-table text-sm">
                <thead class="bg-slate-50 sticky top-0">
                    <tr>
                        <th>Description</th>
                        <th>SKU</th>
                        <th class="text-center">Total Qty</th>
                        <th class="text-right">Total Value</th>
                    </tr>
                </thead>
                <tbody id="unservedItemsListBody">
                    </tbody>
            </table>
        </div>
    </div>

    <div class="lg:col-span-2 content-card">
         <h3 class="text-lg font-semibold mb-4 text-slate-900">Top 5 Customers (by Served Value)</h3>
         <div class="overflow-y-auto max-h-[30rem]">
             <table class="min-w-full divide-y divide-slate-200">
                 <thead class="bg-slate-50 sticky top-0">
                     <tr>
                         <th class="px-3 py-2 text-left text-xs font-medium text-slate-500 uppercase">Customer</th>
                         <th class="px-3 py-2 text-right text-xs font-medium text-slate-500 uppercase">Value</th>
                     </tr>
                 </thead>
                 <tbody id="topCustomerList" class="bg-white divide-y divide-slate-200"></tbody>
             </table>
         </div>
    </div>
</div>
        
        <div class="mt-8 space-y-6">
            <h2 class="text-2xl font-bold text-slate-800 border-b pb-3">Top Customer Analysis</h2>
            <div id="customerDashboards" class="space-y-6">
                </div>
        </div>

    </div>
</div>