import { postData } from './api.js';

// --- State Variables ---
let currentLocation = 'all';
let currentBu = 'all';
let currentPage = 1;
let totalPages = 1;
let currentSearch = '';
let debounceTimer;

// --- Main Data Fetching Function ---
async function fetchData() {
    document.getElementById('loading-overlay').style.display = 'flex';
    try {
        const formData = new FormData();
        formData.append('location', currentLocation);
        formData.append('bu', currentBu);
        formData.append('page', currentPage);
        formData.append('search', currentSearch);

        const result = await postData('get_rojon_orders', formData);

        if (result.success) {
            renderOrders(result.data || []);
            renderPagination(result.pagination || {});
        } else {
            console.error("Failed to fetch orders:", result.message);
            document.getElementById('orders-table-body').innerHTML = `<tr><td colspan="4" class="text-center py-8 text-slate-500">Error: ${result.message}</td></tr>`;
        }
    } catch (error) {
        console.error("Error fetching orders:", error);
        document.getElementById('orders-table-body').innerHTML = `<tr><td colspan="4" class="text-center py-8 text-slate-500">An error occurred while fetching data.</td></tr>`;
    } finally {
        document.getElementById('loading-overlay').style.display = 'none';
    }
}

// --- Rendering Functions ---
function renderOrders(orders) {
    const tableBody = document.getElementById('orders-table-body');
    if (orders.length === 0) {
        tableBody.innerHTML = `<tr><td colspan="4" class="text-center py-8 text-slate-500">No orders found matching your criteria.</td></tr>`;
        return;
    }
    tableBody.innerHTML = orders.map(order => {
        let soDisplay = 'N/A';
        if (order.so_number) {
            try {
                const soArray = JSON.parse(order.so_number);
                soDisplay = Array.isArray(soArray) ? (soArray.filter(s => s).join(', ') || 'N/A') : order.so_number;
            } catch (e) { soDisplay = order.so_number; }
        }
        return `
        <tr>
            <td data-label="Date">${new Date(order.order_date).toLocaleDateString()}</td>
            <td data-label="PO Number / Address">
                <div class="font-semibold text-slate-800">${order.po_number}</div>
                <div class="text-xs text-slate-500">${order.customer_address}</div>
            </td>
            <td data-label="SO Number(s)" class="text-xs font-mono whitespace-pre-wrap">${soDisplay}</td>
            <td class="text-center">
                 <a href="view_order.php?id=${order.id}&context=rojon_dashboard" target="_blank" class="text-indigo-600 hover:text-indigo-800 font-semibold text-sm">View Details</a>
            </td>
        </tr>
    `}).join('');
}

function renderPagination(pagination) {
    totalPages = pagination.totalPages || 1;
    currentPage = pagination.page || 1;
    document.getElementById('order-count').textContent = `(${pagination.total || 0} orders)`;
    const paginationContainer = document.getElementById('pagination-controls');
    if (!paginationContainer || totalPages <= 1) {
        if(paginationContainer) paginationContainer.innerHTML = '';
        return;
    }
    const prevDisabled = currentPage === 1 ? 'disabled' : '';
    const nextDisabled = currentPage === totalPages ? 'disabled' : '';
    paginationContainer.innerHTML = `
        <button id="prev-page" class="pagination-btn" ${prevDisabled}>&laquo; Previous</button>
        <span class="text-sm text-slate-700">Page ${currentPage} of ${totalPages}</span>
        <button id="next-page" class="pagination-btn" ${nextDisabled}>Next &raquo;</button>
    `;
    document.getElementById('prev-page')?.addEventListener('click', () => changePage(currentPage - 1));
    document.getElementById('next-page')?.addEventListener('click', () => changePage(currentPage + 1));
}

function changePage(page) {
    if (page >= 1 && page <= totalPages) {
        currentPage = page;
        fetchData();
    }
}

// --- Initialization ---
function init() {
    // Location Filter
    document.querySelectorAll('#location-filter-group .filter-btn').forEach(btn => {
        btn.addEventListener('click', () => {
            document.querySelectorAll('#location-filter-group .filter-btn').forEach(b => b.classList.remove('active'));
            btn.classList.add('active');
            currentLocation = btn.dataset.location;
            currentPage = 1;
            fetchData();
        });
    });

    // BU Filter
    document.querySelectorAll('#bu-filter-group .filter-btn').forEach(btn => {
        btn.addEventListener('click', () => {
            document.querySelectorAll('#bu-filter-group .filter-btn').forEach(b => b.classList.remove('active'));
            btn.classList.add('active');
            currentBu = btn.dataset.bu;
            currentPage = 1;
            fetchData();
        });
    });

    // Search Input
    const searchInput = document.getElementById('search-input');
    searchInput.addEventListener('input', () => {
        clearTimeout(debounceTimer);
        debounceTimer = setTimeout(() => {
            currentSearch = searchInput.value;
            currentPage = 1;
            fetchData();
        }, 400);
    });

    // Initial data load
    fetchData();
}

document.addEventListener('DOMContentLoaded', init);