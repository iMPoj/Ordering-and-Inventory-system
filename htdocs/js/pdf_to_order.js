import { showLoader, hideLoader, showMessage, showConfirmation } from './ui.js';
import { postData, fetchData } from './api.js';

let parsedOrderData = {};
let localCustomers = [];

function updateRowPrice(row) {
    const qtyInput = row.querySelector('.item-quantity');
    const skuSelect = row.querySelector('.item-sku-select');
    const priceDisplay = row.querySelector('.item-price-display');
    const selectedOption = skuSelect.options[skuSelect.selectedIndex];
    const discountPercent = parseFloat(document.getElementById('discountPercentage').value) || 0;

    const quantity = parseInt(qtyInput.value) || 0;
    const unitPrice = parseFloat(selectedOption?.dataset.price || 0);
    const grossLineTotal = quantity * unitPrice;
    const discountedLineTotal = grossLineTotal * (1 - discountPercent / 100);

    priceDisplay.textContent = discountedLineTotal > 0 ? discountedLineTotal.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) : '0.00';
}

/**
 * Updates the grand total price and item counts in the "Final Actions" box.
 * ✅ CORRECTED: Now sums the already discounted prices from each row.
 */
function updateSummary() {
    const list = document.getElementById('orderItemsList');
    const rows = list.querySelectorAll('tr.item-row');
    let totalDiscountedValue = 0; // Sum of discounted prices
    let itemCount = 0;

    rows.forEach((row, index) => {
        const rowNumberCell = row.querySelector('.row-number');
        if (rowNumberCell) rowNumberCell.textContent = index + 1;

        const qtyInput = row.querySelector('.item-quantity');
        const quantity = parseInt(qtyInput.value) || 0;
        const priceDisplay = row.querySelector('.item-price-display');

        if (quantity > 0) {
            // Read the displayed discounted price text, remove formatting, and parse as float
            const priceText = priceDisplay.textContent.replace(/[₱,]/g, '');
            totalDiscountedValue += parseFloat(priceText) || 0;
            itemCount++;
        }
    });

    // Display the sum of the discounted prices
    document.getElementById('orderTotalDisplay').textContent = totalDiscountedValue.toLocaleString('en-US', { style: 'currency', currency: 'PHP' });
    document.getElementById('summaryItemCount').textContent = `(${itemCount} items)`;
    document.getElementById('poItemCount').textContent = itemCount;
}

async function populateOrderItems(items, location) {
    const list = document.getElementById('orderItemsList');
    list.innerHTML = `<tr><td colspan="8" class="text-center py-4">Finding best SKUs for ${items.length} items...</td></tr>`;

    const itemPromises = items.map(item => 
        postData('find_product_with_best_sku', { term: item.vendorCode, location })
    );
    const results = await Promise.all(itemPromises);

    const rowsHtml = results.map((result, index) => {
        return createItemRow(items[index], result.success ? result.data : null, index);
    }).join('');
    
    list.innerHTML = rowsHtml || `<tr><td colspan="8" class="text-center py-4">No items were parsed.</td></tr>`;
    
    list.querySelectorAll('tr.item-row').forEach(updateRowPrice);
    updateSummary(); // Calculate total AFTER individual rows are calculated
}

function createItemRow(originalItem, apiData, index) {
    let optionsHtml = '<option value="">Not Found</option>';
    let bestSku = '';
    let description = `(Not Found) ${originalItem.description || ''}`;
    let barcode = 'No Barcode';

    if (apiData && Array.isArray(apiData.allSkus)) {
        description = apiData.description;
        bestSku = apiData.bestSku;
        const barcodeObj = apiData.allSkus.find(c => c.type === 'barcode');
        if (barcodeObj) barcode = barcodeObj.code;

        optionsHtml = apiData.allSkus
            .filter(s => s.type === 'sku' && parseFloat(s.sales_price) > 0)
            .map(s => {
                const stock = s.stock ? parseInt(s.stock) : 0;
                const stockColor = stock <= 0 ? 'text-red-500' : 'text-slate-500';
                // Note: The span inside the option might not render consistently across browsers,
                // but the text content itself will work.
                return `<option value="${s.code}" data-price="${s.sales_price || 0}" ${s.code === bestSku ? 'selected' : ''}>
                            ${s.code} (Stock: ${stock.toLocaleString()})
                        </option>`;
            })
            .join('');
    }
    
    return `
        <tr class="item-row" data-product-id="${apiData?.productId || ''}">
            <td class="px-2 py-2 w-10 text-center cursor-move drag-handle text-slate-400">☰</td>
            <td class="px-2 py-2 text-center row-number">${index + 1}</td>
            <td class="px-4 py-2">
                <p class="font-medium text-slate-800 item-description">${description}</p>
                <p class="font-mono text-xs text-slate-500 mt-1">${barcode}</p>
            </td>
            <td class="px-4 py-2">
                <select class="item-sku-select mt-1 block w-full rounded-md border-slate-300 shadow-sm text-xs">${optionsHtml}</select>
            </td>
            <td class="px-4 py-2"><input type="number" class="item-quantity w-20 rounded-md border-slate-300 shadow-sm text-sm" value="${originalItem.quantity || 1}"></td>
            <td class="px-4 py-2 w-16 item-unit">pc</td>
            <td class="px-4 py-2 item-price-display font-medium text-slate-800 text-right">0.00</td>
            <td class="px-4 py-2 text-right"><button class="delete-item-btn text-red-500 hover:text-red-700">✖</button></td>
        </tr>`;
}

function setupEventListeners() {
    const list = document.getElementById('orderItemsList');

    list.addEventListener('input', (e) => {
        if (e.target.matches('.item-quantity, .item-sku-select')) {
            updateRowPrice(e.target.closest('tr'));
            updateSummary(); // Recalculate total whenever a row changes
        }
    });

    list.addEventListener('click', (e) => {
        if (e.target.classList.contains('delete-item-btn')) {
            e.target.closest('tr').remove();
            updateSummary(); // Recalculate total after deleting a row
        }
    });

    // Recalculate everything when the main discount changes
    document.getElementById('discountPercentage').addEventListener('input', () => {
        document.querySelectorAll('#orderItemsList tr.item-row').forEach(updateRowPrice);
        updateSummary();
    });

    document.getElementById('processOrderBtn').addEventListener('click', () => {
        const items = [];
        document.querySelectorAll('#orderItemsList tr.item-row').forEach(row => {
            const sku = row.querySelector('.item-sku-select').value;
            const quantity = row.querySelector('.item-quantity').value;
            if (sku && parseInt(quantity) > 0) {
                 items.push({
                    sku: sku,
                    description: row.querySelector('.item-description').textContent.trim(),
                    quantity: quantity,
                });
            }
        });

        if (items.length === 0) return showMessage("Cannot process an empty order.", true);

        const finalOrderData = {
            customer_name: document.getElementById('customerName').value,
            customer_address: document.getElementById('customerAddress').value,
            po_number: document.getElementById('poNumber').value,
            location: document.getElementById('orderLocation').value,
            bu: document.getElementById('orderBu').value,
            discount: document.getElementById('discountPercentage').value,
            items: JSON.stringify(items)
        };
        
        showConfirmation("Are you sure you want to process this order?", async () => {
            showLoader();
            const result = await postData('add_order', finalOrderData);
            hideLoader();
            if (result.success) {
                sessionStorage.removeItem('pdfOrderData');
                showMessage(`Order #${result.order_id} created successfully! Redirecting...`);
                setTimeout(() => { window.location.href = `index.php#orderBook`; }, 1500);
            } else {
                showMessage(result.message || "Failed to process order.", true);
            }
        });
    });

    const searchInput = document.getElementById('itemDescription');
    const suggestionsBox = document.getElementById('descriptionSuggestions');
    let selectedProduct = null;

    searchInput.addEventListener('input', async () => {
        const term = searchInput.value.trim();
        if (term.length < 2) {
            suggestionsBox.classList.add('hidden');
            return;
        }
        const result = await postData('get_product_suggestions', { term, bu: document.getElementById('orderBu').value });
        if (result.success && result.data.length > 0) {
            suggestionsBox.innerHTML = result.data.map(p => 
                `<div class="p-2 hover:bg-slate-100 cursor-pointer suggestion-item" data-product='${JSON.stringify(p)}'>
                    ${p.description} <span class="text-xs text-slate-400">${p.sku}</span>
                </div>`
            ).join('');
            suggestionsBox.classList.remove('hidden');
        } else {
            suggestionsBox.classList.add('hidden');
        }
    });

    suggestionsBox.addEventListener('click', (e) => {
        const item = e.target.closest('.suggestion-item');
        if (item) {
            selectedProduct = JSON.parse(item.dataset.product);
            searchInput.value = selectedProduct.description;
            suggestionsBox.classList.add('hidden');
            document.getElementById('itemQuantity').focus();
        }
    });
    
    document.getElementById('addItemBtn').addEventListener('click', async () => {
        if (!selectedProduct) return showMessage('Please search for and select a product first.', true);
        
        showLoader();
        const result = await postData('find_product_with_best_sku', { term: selectedProduct.id, location: document.getElementById('orderLocation').value });
        hideLoader();
        
        if (result.success) {
            const list = document.getElementById('orderItemsList');
            const rowCount = list.querySelectorAll('tr').length;
            const newRowHtml = createItemRow({ quantity: document.getElementById('itemQuantity').value }, result.data, rowCount);
            list.insertAdjacentHTML('beforeend', newRowHtml);
            
            const newRowElement = list.lastElementChild;
            if (newRowElement) {
                updateRowPrice(newRowElement);
                updateSummary(); // Recalculate total after adding
            }
            
            searchInput.value = '';
            document.getElementById('itemQuantity').value = 1;
            selectedProduct = null;
            searchInput.focus();
        } else {
            showMessage(result.message || 'Could not add item.', true);
        }
    });
}

async function initializePage() {
    setupEventListeners();

    const customersResult = await fetchData('get_customers');
    if (customersResult.success) {
        localCustomers = customersResult.data;
    } else {
        showMessage('Critical Error: Could not load customer data.', true);
        hideLoader();
        return;
    }
    
    const dataJSON = sessionStorage.getItem('pdfOrderData');
    if (!dataJSON) {
        showMessage("No parsed PDF data found. Redirecting back to Admin page.", true);
        setTimeout(() => { window.location.href = 'index.php#admin'; }, 2000);
        return;
    }
    
    parsedOrderData = JSON.parse(dataJSON);

    document.getElementById('orderLocation').value = parsedOrderData.location || 'Davao';
    document.getElementById('orderBu').value = parsedOrderData.bu || '';
    document.getElementById('customerName').value = parsedOrderData.customerName || '';
    document.getElementById('customerAddress').value = parsedOrderData.shipTo || '';
    document.getElementById('poNumber').value = parsedOrderData.poNumber || '';
    
    const customer = localCustomers.find(c => c.id == parsedOrderData.customerId);
    if (customer && customer.default_discount) {
         document.getElementById('discountPercentage').value = customer.default_discount;
    }

    await populateOrderItems(parsedOrderData.items, parsedOrderData.location);
    
    new Sortable(document.getElementById('orderItemsList'), {
        animation: 150,
        handle: '.drag-handle',
        onEnd: () => updateSummary() // Recalculate numbering/total after drag
    });

    hideLoader();
}

document.addEventListener('DOMContentLoaded', initializePage);