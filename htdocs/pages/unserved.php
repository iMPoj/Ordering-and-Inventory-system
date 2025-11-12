
<div id="unservedPage" class="hidden space-y-6">
    <div class="content-card">
        <div class="md:flex justify-between items-start border-b pb-4 mb-6">
            <div>
                <h2 class="text-2xl font-bold text-slate-800 mb-2">Unserved Orders</h2>
                <p class="text-slate-500">Showing all orders that contain at least one unserved item.</p>
            </div>
            <div class="text-left md:text-right mt-4 md:mt-0">
                <h3 class="text-sm font-medium text-slate-500 uppercase tracking-wide">Total Unserved Value (Visible Page)</h3>
                <p id="unservedGrandTotal" class="text-2xl font-bold text-red-600">₱0.00</p>
            </div>
        </div>
        
        <details class="group border rounded-lg overflow-hidden mb-6">
            <summary class="p-4 font-semibold cursor-pointer flex justify-between items-center text-slate-700 hover:bg-slate-50">
                <span>Show Filters</span>
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5 transition-transform duration-200 group-open:rotate-180"><path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5" /></svg>
            </summary>
            <div class="p-4 border-t border-slate-200 bg-slate-50">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label for="unLocFilter" class="block text-sm font-medium text-slate-700">Location</label>
                        <select id="unLocFilter" class="mt-1 block w-full rounded-md border-slate-300 shadow-sm"><option value="all">All Locations</option> <option value="Davao">Davao</option> <option value="Gensan">Gensan</option></select>
                    </div>
                    <div>
                        <label for="unBuFilter" class="block text-sm font-medium text-slate-700">Business Unit</label>
                        <select id="unBuFilter" class="mt-1 block w-full rounded-md border-slate-300 shadow-sm"><option value="all">All BUs</option> <option value="Health">Health</option> <option value="Hygiene">Hygiene</option> <option value="Nutri">Nutri</option></select>
                    </div>
                    <div class="md:col-span-2">
                        <label for="unSkuFilter" class="block text-sm font-medium text-slate-700">Filter by SKU</label>
                        <input type="text" id="unSkuFilter" placeholder="Enter SKU from dashboard..." class="mt-1 block w-full rounded-md border-slate-300 shadow-sm">
                    </div>
                    <div class="md:col-span-2">
                        <label for="unCustomerFilter" class="block text-sm font-medium text-slate-700">Customer</label>
                        <select id="unCustomerFilter" class="mt-1 block w-full rounded-md border-slate-300 shadow-sm"><option value="all">All Customers</option></select>
                    </div>
                </div>
            </div>
        </details>
    </div>
    
    <div class="content-card">
        <table class="data-table">
            <thead>
                 <tr>
                    <th>Customer / PO</th>
                    <th>Location / BU</th>
                    <th>Unserved Value</th>
                    <th class="text-right">Actions</th>
                </tr>
            </thead>
            <tbody id="unservedList"></tbody>
        </table>

        <div class="flex justify-between items-center mt-6">
            <button id="unPrevBtn" class="bg-slate-200 text-slate-700 py-1 px-3 rounded-md hover:bg-slate-300 disabled:opacity-50">&lt; Prev</button>
            <span id="unPageInfo" class="text-sm font-medium text-slate-700">Page 1 of 1</span>
            <button id="unNextBtn" class="bg-slate-200 text-slate-700 py-1 px-3 rounded-md hover:bg-slate-300 disabled:opacity-50">Next &gt;</button>
        </div>
    </div>
</div>