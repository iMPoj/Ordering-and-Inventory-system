import { appState } from './state.js';
import { postData } from './api.js';
import { showLoader, hideLoader, showMessage } from './ui.js';

/**
 * Renders the table with the provided data.
 * @param {Array} items - The array of fulfillable items from the API.
 */
function renderFulfillablePage(items = []) {
    const list = document.getElementById('fulfillableList');
    if (!list) return;

    // Calculate the grand total based on the items currently displayed
    const grandTotal = items.reduce((sum, item) => {
        const product = appState.products[item.sku];
        const itemPrice = (product?.sales_price || 0) * item.quantity;
        return sum + itemPrice;
    }, 0);
    document.getElementById('fulfillableGrandTotal').textContent = grandTotal.toLocaleString('en-US', { style: 'currency', currency: 'PHP' });

    if (items.length === 0) {
        list.innerHTML = `<tr><td colspan="6" class="!text-center py-8 text-slate-500">No fulfillable items found for the selected filters.</td></tr>`;
    } else {
        list.innerHTML = items.map(item => `
            <tr>
                <td data-label="Customer / PO">
                    <div class="font-bold text-slate-800">${item.customer_name}</div>
                    <div class="text-xs text-slate-500">PO: ${item.po_number}</div>
                </td>
                <td data-label="Unserved Item">
                    <div>${item.description}</div>
                    <div class="font-mono text-xs text-slate-500">${item.sku}</div>
                </td>
                <td data-label="Qty Needed">${item.quantity}</td>
                <td data-label="Available Stock" class="font-semibold text-green-600">${item.total_available_stock}</td>
                <td data-label="Last Stock Update">${item.stock_update_date ? new Date(item.stock_update_date).toLocaleDateString() : 'N/A'}</td>
                <td data-label="Action" class="text-right">
                    <a href="view_order.php?id=${item.order_id}&context=fulfillable" target="_blank" class="text-indigo-600 hover:text-indigo-900 font-medium text-sm">View Order</a>
                </td>
            </tr>
        `).join('');
    }
}

/**
 * Fetches data from the API based on current filters and then renders the page.
 */
export async function fetchFulfillableData() {
    showLoader();
    try {
        // NOTE: The API for get_fulfillable_items doesn't use filters yet,
        // but this structure allows for it to be easily added in the future.
        const result = await postData('get_fulfillable_items', {});

        if (result.success) {
            // Client-side filtering is still needed as the API sends all locations/customers
            const locationFilter = document.getElementById('ffLocFilter').value;
            const customerFilter = document.getElementById('ffCustomerFilter').value;

            const filteredItems = (result.data || []).filter(item => {
                const locMatch = locationFilter === 'all' || item.location === locationFilter;
                const custMatch = customerFilter === 'all' || item.customer_name === customerFilter;
                return locMatch && custMatch;
            });
            
            renderFulfillablePage(filteredItems);
        } else {
            showMessage(result.message || 'Failed to fetch fulfillable items.', true);
            renderFulfillablePage([]); // Render an empty table on error
        }
    } catch (e) {
        showMessage('An error occurred while fetching fulfillable items.', true);
        renderFulfillablePage([]); // Render an empty table on error
    } finally {
        hideLoader();
    }
}

/**
 * Populates filter dropdowns with dynamic data.
 */
export function populateFulfillableFilters() {
    const customerFilter = document.getElementById('ffCustomerFilter');
    if (customerFilter) {
        const priorityCustomers = appState.customers.filter(c => c.is_priority);
        customerFilter.innerHTML = '<option value="all">All Priority Customers</option>' + 
            priorityCustomers.map(c => `<option value="${c.name}">${c.name}</option>`).join('');
    }
}

/**
 * Initializes the event listeners for the page.
 */
export function initFulfillable() {
    // When any filter changes, re-fetch the data from the server.
    ['ffLocFilter', 'ffCustomerFilter'].forEach(id => {
        document.getElementById(id)?.addEventListener('input', fetchFulfillableData);
    });
}