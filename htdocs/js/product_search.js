import { postData } from './api.js';

let debounceTimer;

async function handleSearch() {
    const input = document.getElementById('product-search-input');
    const resultsBody = document.getElementById('search-results-body');
    const searchTerm = input.value.trim();

    if (searchTerm.length < 3) {
        resultsBody.innerHTML = `<tr><td colspan="6" class="text-center py-8 text-slate-500">Please enter at least 3 characters to search.</td></tr>`;
        return;
    }

    resultsBody.innerHTML = `<tr><td colspan="6" class="text-center py-8 text-slate-500">Searching...</td></tr>`;

    try {
        const result = await postData('search_pos_by_product', { term: searchTerm });
        if (result.success) {
            renderResults(result.data || []);
        } else {
            resultsBody.innerHTML = `<tr><td colspan="6" class="text-center py-8 text-red-500">Error: ${result.message}</td></tr>`;
        }
    } catch (error) {
        console.error("Search failed:", error);
        resultsBody.innerHTML = `<tr><td colspan="6" class="text-center py-8 text-red-500">An error occurred during the search.</td></tr>`;
    }
}

function renderResults(items) {
    const resultsBody = document.getElementById('search-results-body');
    if (items.length === 0) {
        resultsBody.innerHTML = `<tr><td colspan="6" class="text-center py-8 text-slate-500">No purchase orders found for this item.</td></tr>`;
        return;
    }

    resultsBody.innerHTML = items.map(item => {
        const statusClass = item.status === 'served' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800';
        return `
            <tr>
                <td data-label="Order Date">${new Date(item.order_date).toLocaleDateString()}</td>
                <td data-label="Customer / PO">
                    <div class="font-bold text-slate-800">${item.customer_name}</div>
                    <div class="text-xs text-slate-500">PO: ${item.po_number}</div>
                </td>
                <td data-label="Matched Item">
                    <div>${item.description}</div>
                    <div class="font-mono text-xs text-slate-500">${item.sku}</div>
                </td>
                <td data-label="Qty" class="text-center">${item.quantity}</td>
                <td data-label="Status" class="text-center">
                    <span class="px-2 py-1 text-xs leading-5 font-semibold rounded-full ${statusClass}">
                        ${item.status}
                    </span>
                </td>
                <td data-label="Action" class="text-right">
                    <a href="view_order.php?id=${item.order_id}" target="_blank" class="text-indigo-600 hover:text-indigo-900 font-medium text-sm">View Order</a>
                </td>
            </tr>
        `;
    }).join('');
}

function init() {
    const searchInput = document.getElementById('product-search-input');
    searchInput.addEventListener('input', () => {
        clearTimeout(debounceTimer);
        debounceTimer = setTimeout(handleSearch, 500); // Wait 500ms after user stops typing
    });
}

document.addEventListener('DOMContentLoaded', init);