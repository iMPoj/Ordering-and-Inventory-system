import { showLoader, hideLoader, showMessage, showConfirmation } from './ui.js';
import { appState } from './state.js';
import { postData } from './api.js';

let orderData = {};

function updateSummary() {
    const list = document.getElementById('orderItemsList');
    const rows = list.querySelectorAll('tr');
    let total = 0;
    let itemCount = 0;

    rows.forEach(row => {
        const qtyInput = row.querySelector('.item-quantity');
        const priceInput = row.querySelector('.item-price');
        const quantity = parseInt(qtyInput.value) || 0;
        
        if (quantity > 0) {
            total += parseFloat(priceInput.value) || 0;
            itemCount++;
        }
    });

    const discount = parseFloat(document.getElementById('discountPercentage').value) || 0;
    const discountedTotal = total * (1 - discount / 100);

    document.getElementById('orderTotalDisplay').textContent = discountedTotal.toLocaleString('en-US', { style: 'currency', currency: 'PHP' });
    document.getElementById('summaryItemCount').textContent = `(${itemCount} items)`;
}

async function populateOrderItems(items) {
    const list = document.getElementById('orderItemsList');
    if (!list) return;

    const location = document.getElementById('orderLocation').value;
    if (!location) {
        showMessage("Please select a location before processing items.", true);
        return;
    }
    
    showLoader();
    list.innerHTML = `<tr><td colspan="7" class="text-center py-4">Finding best SKUs for ${items.length} items...</td></tr>`;

    const itemPromises = items.map(async (item, index) => {
        const result = await postData('find_product_with_best_sku', { term: item.vendorCode, location });
        // Return the original index along with the data to preserve order
        return { originalIndex: index, originalItem: item, apiResult: result.success ? result.data : null };
    });

    const processedItems = await Promise.all(itemPromises);
    
    // Sort the results back into their original order, just in case.
    processedItems.sort((a, b) => a.originalIndex - b.originalIndex);

    const rowsHtml = processedItems.map(data => {
        const { originalItem, apiResult, originalIndex } = data;
        let optionsHtml = '<option value="">Not Found</option>';
        let bestSku = '';
        let description = originalItem.description;

        if (apiResult) {
            bestSku = apiResult.bestSku;
            description = apiResult.description;
            optionsHtml = apiResult.allSkus
                .filter(s => s.type === 'sku' && s.sales_price > 0)
                .map(s => `<option value="${s.code}" ${s.code === bestSku ? 'selected' : ''}>${s.code}</option>`)
                .join('');
        }
        
        let price = 0;
        if (bestSku && appState.products[bestSku]) {
            price = (appState.products[bestSku].sales_price || 0) * originalItem.quantity;
        }

        return `
            <tr class="item-row" data-original-index="${originalIndex}">
                <td class="px-2 py-2 w-12 text-center text-slate-500">${originalIndex + 1}</td>
                <td class="px-4 py-2">${description}</td>
                <td class="px-4 py-2">
                    <select class="item-sku-select mt-1 block w-full rounded-md border-slate-300 shadow-sm text-xs">
                        ${optionsHtml}
                    </select>
                </td>
                <td class="px-4 py-2"><input type="number" class="item-quantity w-20 rounded-md border-slate-300 shadow-sm text-sm" value="${originalItem.quantity}"></td>
                <td class="px-4 py-2 w-16">N/A</td>
                <td class="px-4 py-2"><input type="number" step="0.01" class="item-price w-32 rounded-md border-slate-300 shadow-sm text-sm" value="${price.toFixed(2)}"></td>
                <td class="px-4 py-2 text-right"><button class="delete-item-btn text-red-500 hover:text-red-700">✖</button></td>
            </tr>`;
    }).join('');
    
    list.innerHTML = rowsHtml.length > 0 ? rowsHtml : `<tr><td colspan="7" class="text-center py-4">No items were parsed.</td></tr>`;
    hideLoader();
    updateSummary();
}


function setupForm(data) {
    document.getElementById('orderLocation').value = data.location || 'Davao';
    document.getElementById('customerName').value = data.customerName || '';
    document.getElementById('customerAddress').value = data.shipTo || '';
    document.getElementById('poNumber').value = data.poNumber || '';
    
    const customer = appState.customers.find(c => c.id == data.customerId);
    if (customer && customer.default_discount) {
         document.getElementById('discountPercentage').value = customer.default_discount;
    }

    if (!data.bu && data.items.length > 0 && appState.products[data.items[0].vendorCode]) {
        document.getElementById('orderBu').value = appState.products[data.items[0].vendorCode].bu;
    } else {
        document.getElementById('orderBu').value = data.bu || '';
    }

    populateOrderItems(data.items);
}

function loadDataFromStorage() {
    const dataJSON = sessionStorage.getItem('pdfOrderData');
    if (!dataJSON) {
        showMessage("No parsed PDF data found. Please go back and parse a PDF first.", true);
        document.querySelector('#main-content').innerHTML = '<p class="text-center text-red-500">No data to display.</p>';
        return false;
    }
    orderData = JSON.parse(dataJSON);
    return true;
}

function init() {
    if (!loadDataFromStorage()) return;

    setupForm(orderData);

    document.getElementById('orderItemsList').addEventListener('change', (e) => {
        if (e.target.classList.contains('item-sku-select') || e.target.classList.contains('item-quantity')) {
            const row = e.target.closest('tr');
            const sku = row.querySelector('.item-sku-select').value;
            const quantity = parseInt(row.querySelector('.item-quantity').value) || 0;
            const priceInput = row.querySelector('.item-price');
            
            const productInfo = appState.products[sku];
            if (productInfo) {
                priceInput.value = (productInfo.sales_price * quantity).toFixed(2);
            }
        }
        updateSummary();
    });

    document.getElementById('orderItemsList').addEventListener('input', updateSummary);
    document.getElementById('discountPercentage').addEventListener('input', updateSummary);

    document.getElementById('orderItemsList').addEventListener('click', (e) => {
        if (e.target.classList.contains('delete-item-btn')) {
            e.target.closest('tr').remove();
            updateSummary();
        }
    });

    document.getElementById('processOrderBtn').addEventListener('click', () => {
        const items = [];
        document.querySelectorAll('#orderItemsList tr').forEach(row => {
            items.push({
                sku: row.querySelector('.item-sku-select').value,
                description: row.cells[1].textContent.trim(),
                quantity: row.querySelector('.item-quantity').value,
                // Price is now recalculated on the server, but we can send this for reference
                price: row.querySelector('.item-price').value 
            });
        });

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
                showMessage(`Order #${result.order_id} created successfully!`, false);
                setTimeout(() => { window.location.href = `view_order.php?id=${result.order_id}`; }, 1500);
            } else {
                showMessage(result.message || "Failed to process order.", true);
            }
        });
    });
}

document.addEventListener('DOMContentLoaded', () => {
    // A small delay to ensure appState is populated from the main script
    setTimeout(init, 100);
});