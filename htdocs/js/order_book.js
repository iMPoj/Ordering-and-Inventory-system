// FILE: js/order_book.js
import { appState } from './state.js';
import { postData } from './api.js';
import { showLoader, hideLoader, showMessage, showConfirmation } from './ui.js';

let currentPage = 1;
const ROWS_PER_PAGE = 20;

function handleDeleteOrder(orderId) {
    showConfirmation(`Are you sure you want to permanently delete Order #${orderId}? Stock for served items will be returned.`, async () => {
        showLoader();
        try {
            const result = await postData('delete_order', { order_id: orderId });
            if (result.success) {
                showMessage(result.message);
                fetchOrderBookPage(currentPage); // Refresh the list
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

export async function fetchOrderBookPage(page) {
    currentPage = page;
    showLoader();

    const filterData = {
        page: currentPage,
        limit: ROWS_PER_PAGE,
        month: document.getElementById('obMonthFilter').value,
        year: document.getElementById('obYearFilter').value,
        po_number: document.getElementById('obPoFilter').value,
        address: document.getElementById('obAddressFilter').value,
        location: document.getElementById('obLocFilter').value,
        bu: document.getElementById('obBuFilter').value,
        customer: document.getElementById('obCustomerFilter').value,
        so_number: document.getElementById('obSoFilter').value,
    };

    try {
        const result = await postData('get_orders', filterData);
        if (result.success) {
            appState.processedOrders = result.data || [];
            appState.orderBookTotal = result.pagination.total || 0;
            renderOrderBookPage();
        } else {
            showMessage(result.message || 'Failed to fetch orders.', true);
        }
    } catch(e) {
        showMessage('An error occurred while fetching the order book.', true);
    } finally {
        hideLoader();
    }
}

export function renderOrderBookPage() {
    const list = document.getElementById('orderBookList');
    if (!list) return;

    const pageItems = appState.processedOrders;
    
    const grandTotal = pageItems.reduce((sum, order) => sum + parseFloat(order.total_value || 0), 0);
    document.getElementById('orderBookGrandTotal').textContent = grandTotal.toLocaleString('en-US', { style: 'currency', currency: 'PHP' });

    const totalPages = Math.ceil(appState.orderBookTotal / ROWS_PER_PAGE);

    if (pageItems.length === 0) {
        list.innerHTML = `<tr><td colspan="5" class="!text-center py-8 text-slate-500">No orders match filters.</td></tr>`;
    } else {
        list.innerHTML = pageItems.map(order => {
            const deleteButton = window.userRole === 'admin' 
                ? `<button data-id="${order.id}" class="delete-order-btn text-red-600 hover:text-red-900 font-medium text-sm ml-4">Delete</button>` 
                : '';

            return `
                <tr>
                    <td data-label="Customer / PO">
                        <div class="font-bold text-slate-800">${order.customer_name}</div>
                        <div class="text-xs text-slate-500 mt-1 truncate" title="${order.customer_address || ''}">${order.customer_address || 'No address'}</div>
                        <div class="text-xs text-slate-500">PO: ${order.po_number}</div>
                    </td>
                    <td data-label="Date">${new Date(order.order_date).toLocaleDateString()}</td>
                    <td data-label="Location / BU">
                        <div>${order.location}</div>
                        <div class="text-xs">${order.bu}</div>
                    </td>
                    <td data-label="Total Value" class="font-semibold">${parseFloat(order.total_value || 0).toLocaleString('en-US', { style: 'currency', currency: 'PHP' })}</td>
                    <td data-label="Action" class="text-right">
                        <a href="view_order.php?id=${order.id}&context=orderBook" target="_blank" class="text-indigo-600 hover:text-indigo-900 font-medium text-sm">View</a>
                        ${deleteButton}
                    </td>
                </tr>
            `;
        }).join('');
    }

    document.getElementById('obPageInfo').textContent = `Page ${currentPage} of ${totalPages || 1}`;
    document.getElementById('obPrevBtn').disabled = currentPage <= 1;
    document.getElementById('obNextBtn').disabled = currentPage >= totalPages;
}

export function populateOrderBookFilters() {
    const customerFilter = document.getElementById('obCustomerFilter');
    if (customerFilter) {
        customerFilter.innerHTML = '<option value="all">All Customers</option>' + 
            appState.customers.map(c => `<option value="${c.name}">${c.name}</option>`).join('');
    }
}

export function initOrderBook() {
    const filterIds = [
        'obMonthFilter', 'obYearFilter', 'obPoFilter', 'obSoFilter', 
        'obAddressFilter', 'obLocFilter', 'obBuFilter', 'obCustomerFilter'
    ];
    filterIds.forEach(id => {
        document.getElementById(id)?.addEventListener('input', () => fetchOrderBookPage(1));
    });
    
    document.getElementById('obPrevBtn')?.addEventListener('click', () => {
        if (currentPage > 1) fetchOrderBookPage(currentPage - 1);
    });
    document.getElementById('obNextBtn')?.addEventListener('click', () => {
        const totalPages = Math.ceil(appState.orderBookTotal / ROWS_PER_PAGE);
        if (currentPage < totalPages) fetchOrderBookPage(currentPage + 1);
    });

    document.getElementById('orderBookList')?.addEventListener('click', (e) => {
        if (e.target.classList.contains('delete-order-btn')) {
            const orderId = e.target.dataset.id;
            handleDeleteOrder(orderId);
        }
    });
}