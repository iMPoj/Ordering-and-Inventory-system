// FILE: js/unserved.js
import { appState } from './state.js';
import { postData } from './api.js';
import { showLoader, hideLoader, showMessage, showConfirmation } from './ui.js';

let currentPage = 1;
const ROWS_PER_PAGE = 50;

// ✅ ADD: Helper function for the delete action
function handleDeleteOrder(orderId) {
    showConfirmation(`Are you sure you want to permanently delete Order #${orderId}? Stock for served items will be returned.`, async () => {
        showLoader();
        try {
            const result = await postData('delete_order', { order_id: orderId });
            if (result.success) {
                showMessage(result.message);
                fetchUnservedPage(currentPage); // Refresh the unserved list
            } else {
                showMessage(result.message || 'Failed to delete order.', true);
            }
        } catch (e) {
            showMessage('An error occurred while deleting the order.', true);
        } finally {
            hideLoader();
        }
    });
}


export async function fetchUnservedPage(page) {
    currentPage = page;
    showLoader();

    const filterData = {
        page: currentPage,
        limit: ROWS_PER_PAGE,
        location: document.getElementById('unLocFilter').value,
        bu: document.getElementById('unBuFilter').value,
        customer: document.getElementById('unCustomerFilter').value,
        sku: document.getElementById('unSkuFilter').value,
    };

    try {
        const result = await postData('get_unserved_orders', filterData);
        if (result.success) {
            // Note: Using a different state variable to avoid conflicts with order book
            appState.unservedOrders = result.data || [];
            appState.unservedTotal = result.total_orders || 0;
            renderUnservedPage();
        } else {
            showMessage(result.message || 'Failed to fetch unserved orders.', true);
        }
    } catch(e) {
        showMessage('An error occurred while fetching unserved orders.', true);
    } finally {
        hideLoader();
    }
}

export function renderUnservedPage() {
    const list = document.getElementById('unservedList');
    if (!list) return;

    const pageItems = appState.unservedOrders || [];

    const grandTotalUnserved = pageItems.reduce((sum, order) => {
        const orderUnservedValue = order.items.reduce((itemSum, item) => {
            return item.status === 'unserved' ? itemSum + parseFloat(item.price) : itemSum;
        }, 0);
        return sum + orderUnservedValue;
    }, 0);
    document.getElementById('unservedGrandTotal').textContent = grandTotalUnserved.toLocaleString('en-US', { style: 'currency', currency: 'PHP' });
    
    const totalPages = Math.ceil(appState.unservedTotal / ROWS_PER_PAGE);

    if (pageItems.length === 0) {
        list.innerHTML = `<tr><td colspan="4" class="!text-center py-8 text-slate-500">No unserved orders match filters.</td></tr>`;
    } else {
         list.innerHTML = pageItems.map(order => {
            const unservedValue = order.items.reduce((sum, item) => item.status === 'unserved' ? sum + parseFloat(item.price) : sum, 0);
            if (unservedValue === 0) return ''; 

            // ✅ ADD: Conditionally create the delete button for admins
            const deleteButton = window.userRole === 'admin' 
                ? `<button data-id="${order.id}" class="delete-order-btn text-red-600 hover:text-red-900 font-medium text-sm ml-4">Delete</button>` 
                : '';

            return `
                <tr>
                    <td data-label="Customer / PO">
                        <div class="font-bold text-slate-800">${order.customer.name}</div>
                        <div class="text-xs text-slate-500">PO: ${order.customer.poNumber}</div>
                    </td>
                    <td data-label="Location / BU">
                        <div>${order.location}</div>
                        <div class="text-xs">${order.bu}</div>
                    </td>
                    <td data-label="Unserved Value" class="font-semibold text-red-600">${unservedValue.toLocaleString('en-US', { style: 'currency', currency: 'PHP' })}</td>
                    <td data-label="Action" class="text-right">
                        <a href="view_order.php?id=${order.id}&context=unserved" class="text-indigo-600 hover:text-indigo-900 font-medium text-sm">View</a>
                        ${deleteButton}
                    </td>
                </tr>
            `;
        }).join('');
    }

    document.getElementById('unPageInfo').textContent = `Page ${currentPage} of ${totalPages || 1}`;
    document.getElementById('unPrevBtn').disabled = currentPage <= 1;
    document.getElementById('unNextBtn').disabled = currentPage >= totalPages;
}

export function populateUnservedFilters() {
    const customerFilter = document.getElementById('unCustomerFilter');
    if (customerFilter) {
        customerFilter.innerHTML = '<option value="all">All Customers</option>' + 
            appState.customers.map(c => `<option value="${c.name}">${c.name}</option>`).join('');
    }
}

export function initUnservedPage() {
    ['unLocFilter', 'unBuFilter', 'unCustomerFilter', 'unSkuFilter'].forEach(id => {
        document.getElementById(id)?.addEventListener('input', () => fetchUnservedPage(1));
    });
    
    document.getElementById('unPrevBtn')?.addEventListener('click', () => {
        if (currentPage > 1) fetchUnservedPage(currentPage - 1);
    });

    document.getElementById('unNextBtn')?.addEventListener('click', () => {
        const totalPages = Math.ceil(appState.unservedTotal / ROWS_PER_PAGE);
        if (currentPage < totalPages) fetchUnservedPage(currentPage + 1);
    });

    // ✅ ADD: Event delegation for the new delete buttons
    document.getElementById('unservedList')?.addEventListener('click', (e) => {
        if (e.target.classList.contains('delete-order-btn')) {
            const orderId = e.target.dataset.id;
            handleDeleteOrder(orderId);
        }
    });
}