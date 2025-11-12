 <div id="fulfillablePage" class="hidden space-y-6">
    <div class="content-card">
        <div class="md:flex justify-between items-start border-b pb-4 mb-6">
            <div>
                <h2 class="text-2xl font-bold text-slate-800 mb-2">Fulfillable Orders</h2>
                <p class="text-slate-500">Unserved items for priority customers where stock is now available.</p>
            </div>
            <div class="text-left md:text-right mt-4 md:mt-0">
                <h3 class="text-sm font-medium text-slate-500 uppercase tracking-wide">Total Value to Fulfill</h3>
                <p id="fulfillableGrandTotal" class="text-2xl font-bold text-emerald-600">₱0.00</p>
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
                        <label for="ffLocFilter" class="block text-sm font-medium text-slate-700">Location</label>
                        <select id="ffLocFilter" class="mt-1 block w-full rounded-md border-slate-300 shadow-sm"><option value="all">All Locations</option><option value="Davao">Davao</option><option value="Gensan">Gensan</option></select>
                    </div>
                    <div>
                         <label for="ffCustomerFilter" class="block text-sm font-medium text-slate-700">Customer</label>
                        <select id="ffCustomerFilter" class="mt-1 block w-full rounded-md border-slate-300 shadow-sm"><option value="all">All Priority Customers</option></select>
                    </div>
                </div>
            </div>
        </details>

        <table class="data-table">
            <thead>
                 <tr>
                    <th>Customer / PO</th>
                    <th>Unserved Item</th>
                    <th>Qty Needed</th>
                    <th>Available Stock</th>
                    <th>Last Stock Update</th>
                    <th class="text-right">Action</th>
                </tr>
            </thead>
            <tbody id="fulfillableList"></tbody>
        </table>
    </div>
</div>