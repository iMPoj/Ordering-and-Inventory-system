import { postData } from './api.js';
import { showLoader, hideLoader, showMessage } from './ui.js';

let currentLocation = 'all';
let currentBu = 'all';

const formatFullCurrency = (val) => (parseFloat(val) || 0).toLocaleString('en-US', { style: 'currency', currency: 'PHP' });

function renderBuPerformanceCards(buData = []) {
    const container = document.getElementById('bu-performance-container');
    if (!container) return;

    const bus = ['Nutri', 'Health', 'Hygiene'];
    
    const cardsHtml = bus.map(buName => {
        const data = buData.find(b => b.bu === buName) || {
            bu: buName, po_amount_total: 0, served_gross: 0, served_net_vat_in: 0,
            served_net_vat_ex: 0, vat_amount: 0, unserved: 0
        };

        return `
            <div class="content-card">
                <h3 class="text-xl font-bold text-slate-800 mb-4">${buName} Sales</h3>
                <table class="w-full text-sm">
                    <tbody class="divide-y divide-slate-200">
                        <tr><td class="py-2 text-slate-600 font-bold">PO Amount Total</td><td class="py-2 text-right font-bold text-slate-900">${formatFullCurrency(data.po_amount_total)}</td></tr>
                        <tr><td class="py-2 text-slate-600">Gross Sales</td><td class="py-2 text-right font-semibold text-slate-800">${formatFullCurrency(data.served_gross)}</td></tr>
                        <tr><td class="py-2 text-slate-600">Net Sales (VAT In)</td><td class="py-2 text-right font-semibold text-green-600">${formatFullCurrency(data.served_net_vat_in)}</td></tr>
                        <tr><td class="py-2 text-slate-600">Net Sales (VAT Ex)</td><td class="py-2 text-right font-semibold text-slate-800">${formatFullCurrency(data.served_net_vat_ex)}</td></tr>
                        <tr><td class="py-2 text-slate-600">VAT Amount</td><td class="py-2 text-right font-semibold text-slate-800">${formatFullCurrency(data.vat_amount)}</td></tr>
                        <tr class="bg-red-50"><td class="py-2 text-red-700">Unserved Value</td><td class="py-2 text-right font-semibold text-red-700">${formatFullCurrency(data.unserved)}</td></tr>
                    </tbody>
                </table>
            </div>
        `;
    }).join('');

    container.innerHTML = cardsHtml;
}

function renderRecentSales(recentPOs = []) {
    const tableBody = document.getElementById('recent-sales-body');
    if (!tableBody) return;
    if (recentPOs.length === 0) {
        tableBody.innerHTML = `<tr><td colspan="4" class="text-center py-4 text-slate-500">No recent sales found.</td></tr>`;
        return;
    }
    tableBody.innerHTML = recentPOs.map(po => {
        let soDisplay = 'N/A';
        if (po.so_number) {
            try {
                const soArray = JSON.parse(po.so_number);
                soDisplay = Array.isArray(soArray) ? (soArray.filter(s => s).join(', ') || 'N/A') : po.so_number;
            } catch (e) { soDisplay = po.so_number; }
        }

        const timeAgo = getTimeAgo(new Date(po.order_date));

        return `
            <tr>
                <td data-label="PO Number / Address">
                    <div class="font-semibold text-slate-800">${po.po_number}</div>
                    <div class="text-xs text-slate-500 truncate">${po.customer_address}</div>
                </td>
                <td data-label="SO Number(s)" class="text-xs font-mono whitespace-pre-wrap">${soDisplay}</td>
                <td data-label="Total Amount" class="text-right font-semibold">${formatFullCurrency(po.total_amount)}</td>
                <td data-label="Date" class="text-right text-xs text-slate-500">${timeAgo}</td>
            </tr>
        `;
    }).join('');
}

function renderUnservedItemsTable(items = []) {
    const tableBody = document.getElementById('unservedItemsTableBody');
    if (!tableBody) return;
    if (items.length === 0) {
        tableBody.innerHTML = `<tr><td colspan="4" class="text-center py-4 text-slate-500">No unserved items found.</td></tr>`;
        return;
    }
    tableBody.innerHTML = items.map(item => `
        <tr>
            <td data-label="Description">${item.description}</td>
            <td data-label="SKU / Barcode">
                <div class="font-mono text-xs text-slate-800">${item.sku}</div>
                <div class="font-mono text-xs text-slate-500">${item.barcode}</div>
            </td>
            <td data-label="Total Qty" class="text-center">${parseInt(item.total_qty).toLocaleString()}</td>
            <td data-label="Total Value" class="text-right font-semibold">${formatFullCurrency(item.total_value)}</td>
        </tr>
    `).join('');
}

function getTimeAgo(date) {
    const seconds = Math.floor((new Date() - date) / 1000);
    let interval = seconds / 31536000;
    if (interval > 1) return Math.floor(interval) + " years ago";
    interval = seconds / 2592000;
    if (interval > 1) return Math.floor(interval) + " months ago";
    interval = seconds / 86400;
    if (interval > 1) return Math.floor(interval) + " days ago";
    interval = seconds / 3600;
    if (interval > 1) return Math.floor(interval) + " hours ago";
    interval = seconds / 60;
    if (interval > 1) return Math.floor(interval) + " minutes ago";
    return "a few seconds ago";
}

async function fetchDashboardData() {
    showLoader();
    try {
        const result = await postData('get_rojon_dashboard_data', {
            location: currentLocation,
            bu: currentBu
        });
        if (result.success && result.data) {
            updateDashboardUI(result.data);
        } else {
            showMessage(result.message || 'Failed to load dashboard data.', true);
        }
    } catch (e) {
        console.error("Dashboard fetch error:", e);
        showMessage('An error occurred while fetching dashboard data.', true);
    } finally {
        hideLoader();
    }
}

function updateDashboardUI(data) {
    renderBuPerformanceCards(data.buPerformance);
    renderRecentSales(data.recent_pos);
    renderUnservedItemsTable(data.unservedItems);
}

function init() {
    document.querySelectorAll('#location-filter-group .filter-btn').forEach(btn => {
        btn.addEventListener('click', () => {
            document.querySelector('#location-filter-group .filter-btn.active').classList.remove('active');
            btn.classList.add('active');
            currentLocation = btn.dataset.location;
            fetchDashboardData();
        });
    });

    document.querySelectorAll('#bu-filter-group .filter-btn').forEach(btn => {
        btn.addEventListener('click', () => {
            document.querySelector('#bu-filter-group .filter-btn.active').classList.remove('active');
            btn.classList.add('active');
            currentBu = btn.dataset.bu;
            fetchDashboardData();
        });
    });

    fetchDashboardData();
}

document.addEventListener('DOMContentLoaded', init);