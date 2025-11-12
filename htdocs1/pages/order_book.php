<div id="orderBookPage" class="hidden space-y-6">
    <div class="content-card">
        <div class="md:flex justify-between items-start border-b pb-4 mb-6">
            <div>
                <h2 class="text-2xl font-bold text-slate-800 mb-2">Order Book</h2>
                <p class="text-slate-500">A complete history of all processed orders.</p>
            </div>
            <div class="text-left md:text-right mt-4 md:mt-0">
                <h3 class="text-sm font-medium text-slate-500 uppercase tracking-wide">Total Value (Visible Page)</h3>
                <p id="orderBookGrandTotal" class="text-2xl font-bold text-indigo-600">₱0.00</p>
            </div>
        </div>
        
        <details class="group border rounded-lg overflow-hidden">
            <summary class="p-4 font-semibold cursor-pointer flex justify-between items-center text-slate-700 hover:bg-slate-50">
                <span>Show Filters</span>
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5 transition-transform duration-200 group-open:rotate-180">
                  <path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5" />
                </svg>
            </summary>
            <div class="p-4 border-t border-slate-200 bg-slate-50">
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 items-end">
                
                <div>
                    <label for="obMonthFilter" class="block text-sm font-medium text-slate-700">Month</label>
                    <select id="obMonthFilter" class="mt-1 block w-full rounded-md border-slate-300 shadow-sm">
                        <option value="all">All Months</option>
                        <?php for ($m = 1; $m <= 12; $m++): ?>
                            <option value="<?php echo $m; ?>" <?php echo ($m == date('m')) ? 'selected' : ''; ?>>
                                <?php echo date('F', mktime(0, 0, 0, $m, 10)); ?>
                            </option>
                        <?php endfor; ?>
                    </select>
                </div>
                <div>
                    <label for="obYearFilter" class="block text-sm font-medium text-slate-700">Year</label>
                    <select id="obYearFilter" class="mt-1 block w-full rounded-md border-slate-300 shadow-sm">
                        <?php 
                        $currentYear = date('Y');
                        for ($y = $currentYear + 1; $y >= $currentYear - 2; $y--): ?>
                            <option value="<?php echo $y; ?>" <?php echo ($y == $currentYear) ? 'selected' : ''; ?>>
                                <?php echo $y; ?>
                            </option>
                        <?php endfor; ?>
                    </select>
                </div>
                <div class="lg:col-span-2 grid grid-cols-1 sm:grid-cols-3 gap-2">
                    <div class="sm:col-span-1">
                       <label for="obPoFilter" class="block text-sm font-medium text-slate-700">PO Number</label>
                       <input type="text" id="obPoFilter" placeholder="Search PO..." class="mt-1 block w-full rounded-md border-slate-300 shadow-sm">
                    </div>
                    <div class="sm:col-span-1">
                       <label for="obSoFilter" class="block text-sm font-medium text-slate-700">SO Number</label>
                       <input type="text" id="obSoFilter" placeholder="Search SO..." class="mt-1 block w-full rounded-md border-slate-300 shadow-sm">
                    </div>
                    <div class="sm:col-span-1">
                       <label for="obAddressFilter" class="block text-sm font-medium text-slate-700">Address</label>
                       <input type="text" id="obAddressFilter" placeholder="Search Address..." class="mt-1 block w-full rounded-md border-slate-300 shadow-sm">
                    </div>
                </div>
                <div>
                        <label for="obLocFilter" class="block text-sm font-medium text-slate-700">Location</label>
                        <select id="obLocFilter" class="mt-1 block w-full rounded-md border-slate-300 shadow-sm">
                            <option value="all">All Locations</option>
                            <option value="Davao">Davao</option>
                            <option value="Gensan">Gensan</option>
                        </select>
                    </div>
                    <div>
                        <label for="obBuFilter" class="block text-sm font-medium text-slate-700">Business Unit</label>
                        <select id="obBuFilter" class="mt-1 block w-full rounded-md border-slate-300 shadow-sm">
                            <option value="all">All BUs</option>
                            <option value="Health">Health</option>
                            <option value="Hygiene">Hygiene</option>
                            <option value="Nutri">Nutri</option>
                        </select>
                    </div>
                    <div class="lg:col-span-4">
                        <label for="obCustomerFilter" class="block text-sm font-medium text-slate-700">Customer</label>
                        <select id="obCustomerFilter" class="mt-1 block w-full rounded-md border-slate-300 shadow-sm">
                            <option value="all">All Customers</option>
                        </select>
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
                    <th>Date</th>
                    <th>Location / BU</th>
                    <th>Total Value</th>
                    <th class="text-right">Actions</th>
                </tr>
            </thead>
            <tbody id="orderBookList"></tbody>
        </table>

        <div class="flex justify-between items-center mt-6">
            <button id="obPrevBtn" class="bg-slate-200 text-slate-700 py-1 px-3 rounded-md hover:bg-slate-300 disabled:opacity-50">&lt; Prev</button>
            <span id="obPageInfo" class="text-sm font-medium text-slate-700">Page 1 of 1</span>
            <button id="obNextBtn" class="bg-slate-200 text-slate-700 py-1 px-3 rounded-md hover:bg-slate-300 disabled:opacity-50">Next &gt;</button>
        </div>
    </div>
</div>