// FILE: js/dashboard.js
import { appState } from './state.js';
import { postData } from './api.js';
import { showLoader, hideLoader, showMessage } from './ui.js';

let charts = {}; // Object to hold all chart instances

// ### THIS IS WHERE THE NEW LINE GOES ###
const formatFullCurrency = (val) => (parseFloat(val) || 0).toLocaleString('en-US', { style: 'currency', currency: 'PHP' });

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
        // Fetch both sets of data at the same time for efficiency.
        const [dashboardResult, salesSummaryResult] = await Promise.all([
             postData('get_dashboard_data', filterData),
             postData('get_sales_summary_data', filterData)
        ]);
        
        if (dashboardResult.success && dashboardResult.data) {
            updateDashboardUI(dashboardResult.data);
        } else {
            showMessage(dashboardResult.message || 'Failed to load dashboard data.', true);
        }
        
        // Check and render the new sales summary data.
        if (salesSummaryResult.success && salesSummaryResult.data) {
             renderSalesSummaryCards(salesSummaryResult.data);
        } else {
             document.getElementById('sales-summary-container').innerHTML = `<p class="lg:col-span-3 text-center text-slate-500 py-8">Could not load sales summary.</p>`;
             showMessage(salesSummaryResult.message || 'Failed to load sales summary.', true);
        }

    } catch (e) {
        console.error("Dashboard fetch error:", e);
        showMessage('An error occurred while fetching dashboard data.', true);
    } finally {
        hideLoader();
    }
}

function renderSalesSummaryCards(buData = []) {
    const container = document.getElementById('sales-summary-container');
    if (!container) return;

    const bus = ['Nutri', 'Health', 'Hygiene'];
    
    if (buData.length === 0) {
        container.innerHTML = `<p class="lg:col-span-3 text-center text-slate-500 py-8">No sales data found for the selected filters.</p>`;
        return;
    }

    const cardsHtml = bus.map(buName => {
        const data = buData.find(b => b.bu === buName) || {
            bu: buName, po_amount_total: 0, served_gross: 0, served_net_vat_in: 0,
            served_net_vat_ex: 0, vat_amount: 0, unserved_gross: 0
        };

        return `
            <div class="bg-slate-50 p-4 rounded-lg border">
                <h3 class="text-xl font-bold text-slate-800 mb-4">${buName} Sales</h3>
                <table class="w-full text-sm">
                    <tbody class="divide-y divide-slate-200">
                        <tr><td class="py-2 text-slate-600 font-bold">PO Amount Total</td><td class="py-2 text-right font-bold text-slate-900">${formatFullCurrency(data.po_amount_total)}</td></tr>
                        <tr><td class="py-2 text-slate-600">Gross Sales</td><td class="py-2 text-right font-semibold text-slate-800">${formatFullCurrency(data.served_gross)}</td></tr>
                        <tr><td class="py-2 text-slate-600">Net Sales (VAT In)</td><td class="py-2 text-right font-semibold text-green-600">${formatFullCurrency(data.served_net_vat_in)}</td></tr>
                        <tr><td class="py-2 text-slate-600">Net Sales (VAT Ex)</td><td class="py-2 text-right font-semibold text-slate-800">${formatFullCurrency(data.served_net_vat_ex)}</td></tr>
                        <tr><td class="py-2 text-slate-600">VAT Amount</td><td class="py-2 text-right font-semibold text-slate-800">${formatFullCurrency(data.vat_amount)}</td></tr>
                        <tr class="bg-red-50"><td class="py-2 text-red-700">Unserved Value</td><td class="py-2 text-right font-semibold text-red-700">${formatFullCurrency(data.unserved_gross)}</td></tr>
                    </tbody>
                </table>
            </div>
        `;
    }).join('');

    container.innerHTML = cardsHtml;
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

    tableBody.innerHTML = items.map(item => `
        <tr>
            <td data-label="Description" class="font-medium text-slate-700">${item.description}</td>
            <td data-label="SKU" class="font-mono text-xs">${item.sku}</td>
            <td data-label="Total Qty" class="text-center">${parseInt(item.total_quantity).toLocaleString()}</td>
            <td data-label="Total Value" class="text-right font-semibold">${formatFullCurrency(item.total_value)}</td>
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