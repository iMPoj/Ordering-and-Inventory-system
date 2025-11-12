// FILE: js/dashboard.js
import { appState } from './state.js';
import { postData } from './api.js';
import { showLoader, hideLoader, showMessage } from './ui.js';

let charts = {}; // Object to hold all chart instances

// --- CUSTOM CHART.JS PLUGIN to display text in the center of doughnut charts ---
const centerTextPlugin = {
  id: 'centerText',
  afterDraw: (chart) => {
    if (chart.config.type !== 'doughnut' || !chart.config.options.plugins.centerText) return;
    const { text } = chart.config.options.plugins.centerText;
    const ctx = chart.ctx;
    const {top, left, width, height} = chart.chartArea;
    const fontSize = Math.min(Math.max(height / 5, 16), 40); 
    ctx.save();
    ctx.font = `bold ${fontSize}px Inter, sans-serif`;
    ctx.textAlign = 'center';
    ctx.textBaseline = 'middle';
    ctx.fillStyle = '#1e293b'; // slate-800
    ctx.fillText(text, left + width / 2, top + height / 2);
    ctx.restore();
  }
};

async function fetchDashboardData() {
    showLoader();
    const filterData = {
        location: document.getElementById('locFilterDashboard').value,
        bu: document.getElementById('buFilterDashboard').value,
        customer: document.getElementById('customerFilter').value,
    };
    try {
        const result = await postData('get_dashboard_data', filterData);
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
    const stats = data.stats || {};
    const formatCompact = (numStr) => {
        const num = parseFloat(numStr) || 0;
        return new Intl.NumberFormat('en-US', { notation: 'compact', maximumFractionDigits: 1 }).format(num);
    };

    // Update Stat Cards
    document.getElementById('stat-total-served').textContent = `₱${formatCompact(stats.totalServedValue)}`;
    document.getElementById('stat-total-qty').textContent = formatCompact(stats.totalServedQty);
    const totalValue = (parseFloat(stats.totalServedValue) || 0) + (parseFloat(stats.totalUnservedValue) || 0);
    document.getElementById('stat-qty-fill-rate-by-po').textContent = `${parseFloat(stats.quantityFillRateByPo || 0).toFixed(1)}%`;
    document.getElementById('stat-unserved-skus').textContent = (stats.unservedSkuCount || 0).toLocaleString();
    document.getElementById('stat-total-unserved-value').textContent = `₱${formatCompact(stats.totalUnservedValue)}`;

    // Render All Main Charts
    const servedPrice = parseFloat(stats.totalServedValue) || 0;
    const unservedPrice = parseFloat(stats.totalUnservedValue) || 0;
    const servedQty = parseInt(stats.totalServedQty) || 0;
    const unservedQty = parseInt(stats.totalUnservedQty) || 0;
    const totalQty = servedQty + unservedQty;

    renderChart('fulfillmentChartPrice', { type: 'doughnut', data: { labels: ['Served', 'Unserved'], datasets: [{ data: [servedPrice, unservedPrice], backgroundColor: ['#4f46e5', '#e2e8f0'] }] }, options: { cutout: '80%', plugins: { legend: { display: true, position: 'bottom' }, centerText: { text: totalValue > 0 ? `${(servedPrice / totalValue * 100).toFixed(0)}%` : 'N/A' } } } });
    renderChart('fulfillmentChartQty', { type: 'doughnut', data: { labels: ['Served', 'Unserved'], datasets: [{ data: [servedQty, unservedQty], backgroundColor: ['#4f46e5', '#e2e8f0'] }] }, options: { cutout: '80%', plugins: { legend: { display: true, position: 'bottom' }, centerText: { text: totalQty > 0 ? `${(servedQty / totalQty * 100).toFixed(0)}%` : 'N/A' } } } });
    
    // The bar chart is now gone, but we still need the data for the new table
    renderUnservedDetailsTable(data.topUnserved || []);
    renderTopCustomersTable(data.topCustomers || []);
    renderCustomerDashboards(data.topCustomers || []);
}

function renderChart(canvasId, config) {
    const ctx = document.getElementById(canvasId)?.getContext('2d');
    if (charts[canvasId]) charts[canvasId].destroy();
    if (ctx) {
        charts[canvasId] = new window.Chart(ctx, { ...config, options: { responsive: true, maintainAspectRatio: false, ...config.options } });
    }
}

function renderUnservedDetailsTable(items) {
    const tableBody = document.getElementById('unservedItemsListBody');
    if (!tableBody) return;

    if (items.length === 0) {
        tableBody.innerHTML = `<tr><td colspan="4" class="text-center py-4 text-slate-500">No unserved items found.</td></tr>`;
        return;
    }

    const formatCurrency = (num) => (parseFloat(num) || 0).toLocaleString('en-US', { style: 'currency', currency: 'PHP' });

    tableBody.innerHTML = items.map(item => `
        <tr>
            <td data-label="Description" class="font-medium text-slate-700">${item.description}</td>
            <td data-label="SKU" class="font-mono text-xs">${item.sku}</td>
            <td data-label="Total Qty" class="text-center">${parseInt(item.total_quantity).toLocaleString()}</td>
            <td data-label="Total Value" class="text-right font-semibold">${formatCurrency(item.total_value)}</td>
        </tr>
    `).join('');
}

function renderTopCustomersTable(topCustomers) {
    const list = document.getElementById('topCustomerList');
    if (!list) return;
    list.innerHTML = topCustomers.map(cust => `
        <tr class="text-sm">
            <td class="px-3 py-3 text-slate-700 font-medium">${cust.name}</td>
            <td class="px-3 py-3 text-slate-900 font-semibold text-right">₱${parseFloat(cust.value).toLocaleString('en-US', { minimumFractionDigits: 2 })}</td>
        </tr>
    `).join('');
}

function renderCustomerDashboards(topCustomers) {
    const container = document.getElementById('customerDashboards');
    if (!container) return;
    const customers = appState.customers.filter(c => topCustomers.some(tc => tc.name === c.name));
    
    container.innerHTML = customers.map(customer => `
        <div class="content-card">
            <h3 class="text-xl font-bold text-slate-800 mb-4">${customer.name}</h3>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 items-center">
                <div class="md:col-span-1 h-48">
                    <canvas id="customerChart-${customer.id}"></canvas>
                </div>
                <div class="md:col-span-2 space-y-4">
                    <div>
                        <label for="loc-filter-${customer.id}" class="block text-sm font-medium text-slate-700">Location</label>
                        <select id="loc-filter-${customer.id}" data-customer-id="${customer.id}" class="customer-filter mt-1 block w-full rounded-md border-slate-300 shadow-sm">
                            <option value="all">All Locations</option><option value="Davao">Davao</option><option value="Gensan">Gensan</option>
                        </select>
                    </div>
                    <div>
                        <label for="bu-filter-${customer.id}" class="block text-sm font-medium text-slate-700">Business Unit</label>
                        <select id="bu-filter-${customer.id}" data-customer-id="${customer.id}" class="customer-filter mt-1 block w-full rounded-md border-slate-300 shadow-sm">
                            <option value="all">All BUs</option><option value="Health">Health</option><option value="Hygiene">Hygiene</option><option value="Nutri">Nutri</option>
                        </select>
                    </div>
                </div>
            </div>
        </div>
    `).join('');

    customers.forEach(customer => updateCustomerChart(customer.id));
    
    document.querySelectorAll('.customer-filter').forEach(filter => {
        filter.addEventListener('change', (e) => updateCustomerChart(e.target.dataset.customerId));
    });
}

async function updateCustomerChart(customerId) {
    const location = document.getElementById(`loc-filter-${customerId}`).value;
    const bu = document.getElementById(`bu-filter-${customerId}`).value;
    const result = await postData('get_customer_dashboard_data', { customer_id: customerId, location, bu });
    if (result.success && result.data) {
        const { totalServedValue, totalUnservedValue } = result.data;
        const total = (parseFloat(totalServedValue) || 0) + (parseFloat(totalUnservedValue) || 0);
        renderChart(`customerChart-${customerId}`, {
            type: 'doughnut',
            data: {
                labels: ['Served', 'Unserved'],
                datasets: [{ data: [totalServedValue || 0, totalUnservedValue || 0], backgroundColor: ['#10b981', '#ef4444'] }]
            },
            options: { cutout: '80%', plugins: { legend: { position: 'bottom' }, centerText: { text: total > 0 ? `${(totalServedValue / total * 100).toFixed(0)}%` : 'N/A' } } }
        });
    }
}

export function populateDashboardFilters() {
    const customerFilter = document.getElementById('customerFilter');
    if (customerFilter) {
        customerFilter.innerHTML = '<option value="all">All Customers</option>' + 
            appState.customers.map(c => `<option value="${c.name}">${c.name}</option>`).join('');
    }
}

export function initDashboard() {
    if (window.Chart) {
        window.Chart.register(centerTextPlugin);
    }
    ['locFilterDashboard', 'buFilterDashboard', 'customerFilter'].forEach(id => {
        document.getElementById(id)?.addEventListener('input', fetchDashboardData);
    });

    document.getElementById('copyUnservedBtn')?.addEventListener('click', () => {
        const table = document.getElementById('unservedItemsListBody');
        if (table) {
            let text = 'Description\tSKU\tTotal Qty\tTotal Value\n';
            table.querySelectorAll('tr').forEach(row => {
                const cells = row.querySelectorAll('td');
                if (cells.length === 4) {
                    text += `${cells[0].textContent}\t${cells[1].textContent}\t${cells[2].textContent}\t${cells[3].textContent}\n`;
                }
            });
            navigator.clipboard.writeText(text)
                .then(() => showMessage('Unserved items table copied to clipboard!'))
                .catch(() => showMessage('Failed to copy text.', true));
        }
    });
}

export function renderDashboard() {
    fetchDashboardData();
}