 <div id="lowStockModal" class="modal-backdrop hidden"><div class="modal-content text-center">
    <h3 class="text-lg font-medium leading-6 text-slate-900">Low Stock Alert</h3>
    <div class="mt-2"><p class="text-sm text-slate-500">Stocks for <strong id="modalItemName"></strong> are not enough. Would you like to mark this item as unserved?</p></div>
    <div class="mt-4 flex justify-center space-x-4">
        <button id="markUnservedBtn" type="button" class="inline-flex justify-center rounded-md border border-transparent bg-red-600 px-4 py-2 text-base font-medium text-white shadow-sm hover:bg-red-700">Mark as Unserved</button>
        <button id="addAnywayBtn" type="button" class="inline-flex justify-center rounded-md border border-slate-300 bg-white px-4 py-2 text-base font-medium text-slate-700 shadow-sm hover:bg-slate-50">Add Anyway (Served)</button>
    </div>
</div></div>
<div id="messageModal" class="modal-backdrop hidden"><div class="modal-content text-center">
    <h3 id="messageModalTitle" class="text-lg font-medium leading-6 text-slate-900">Message</h3>
    <div class="mt-2"><p id="messageModalText" class="text-sm text-slate-500"></p></div>
    <div class="mt-4"><button id="messageModalCloseBtn" type="button" class="w-full inline-flex justify-center rounded-md border border-transparent bg-indigo-600 px-4 py-2 text-base font-medium text-white shadow-sm hover:bg-indigo-700">OK</button></div>
</div></div>
<div id="confirmModal" class="modal-backdrop hidden"><div class="modal-content text-center">
    <h3 id="confirmModalTitle" class="text-lg font-medium leading-6 text-slate-900">Confirmation</h3>
    <div class="mt-2"><p id="confirmModalText" class="text-sm text-slate-500"></p></div>
    <div class="mt-4 flex justify-center space-x-4">
         <button id="confirmModalYesBtn" type="button" class="inline-flex justify-center rounded-md border border-transparent bg-red-600 px-4 py-2 text-base font-medium text-white shadow-sm hover:bg-red-700">Yes</button>
        <button id="confirmModalNoBtn" type="button" class="inline-flex justify-center rounded-md border border-slate-300 bg-white px-4 py-2 text-base font-medium text-slate-700 shadow-sm hover:bg-slate-50">No</button>
    </div>
</div></div>

<div id="pdfUploadModal" class="modal-backdrop hidden">
    <div class="modal-content !max-w-xl text-left">
        <div class="flex justify-between items-center mb-4">
            <h3 class="text-xl font-semibold leading-6 text-slate-900">PDF to Order Uploader</h3>
            <button id="closePdfModalBtn" class="text-slate-400 hover:text-slate-600">&times;</button>
        </div>
        <div class="mt-2 space-y-4">
            <p class="text-sm text-slate-600">
                Select one or more Purchase Order PDF files. The app will process them one by one.
            </p>
            <div id="pdfDropZone" class="border-2 border-dashed border-slate-300 rounded-lg p-8 text-center cursor-pointer hover:border-indigo-500 bg-slate-50 transition-colors">
                <p class="text-slate-500">Drag & drop files here, or click to select</p>
                <input type="file" id="pdfFileInput" multiple accept=".pdf" class="hidden">
            </div>
            <div id="pdfFileList" class="space-y-2 max-h-48 overflow-y-auto"></div>
        </div>
        <div class="mt-6 flex justify-end">
            <button id="startPdfProcessingBtn" type="button" class="inline-flex justify-center rounded-md border border-transparent bg-indigo-600 px-4 py-2 text-base font-medium text-white shadow-sm hover:bg-indigo-700 disabled:opacity-50" disabled>
                Process Files
            </button>
        </div>
    </div>
</div>
<div id="addressCodeModal" class="modal-backdrop hidden">
    <div class="modal-content !max-w-md text-left">
        <h3 id="addressCodeModalTitle" class="text-xl font-semibold leading-6 text-slate-900 mb-4">Add/Edit Address Code</h3>
        <form id="addressCodeForm" class="space-y-4">
            <input type="hidden" id="addressCodeId" name="id">
            <div>
                <label for="addressInput" class="block text-sm font-medium text-slate-700">Address</label>
                <input type="text" id="addressInput" name="address" required class="mt-1 block w-full rounded-md border-slate-300 shadow-sm">
            </div>
            <div>
                <label for="customerCodeInput" class="block text-sm font-medium text-slate-700">Customer Code</label>
                <input type="text" id="customerCodeInput" name="customer_code" required class="mt-1 block w-full rounded-md border-slate-300 shadow-sm">
            </div>
            <div class="mt-6 flex justify-end gap-3">
                <button id="cancelAddressCodeBtn" type="button" class="btn btn-secondary">Cancel</button>
                <button type="submit" class="btn btn-primary">Save</button>
            </div>
        </form>
    </div>
</div>