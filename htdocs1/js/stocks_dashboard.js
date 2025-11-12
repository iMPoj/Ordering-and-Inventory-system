 import { appState } from './state.js';

export function renderStocksDashboard() {
    const list = document.getElementById('stocksDashboardList');
    if (!list) return;

    const selectedLoc = document.getElementById('locFilterStocks').value;
    const selectedBu = document.getElementById('buFilter').value;
    const selectedStatus = document.getElementById('stockStatusFilter').value;
    const searchTerm = document.getElementById('stockSearchInput').value.trim().toLowerCase();
    
    const productsById = {};
    for (const code in appState.products) {
        const productData = appState.products[code];
        const pid = productData.productId;
        if (!productsById[pid]) {
            productsById[pid] = { id: pid, description: productData.description, bu: productData.bu, codes: [] };
        }
        // FIX: We add the 'sku: code' property here so the sort function can find it.
        productsById[pid].codes.push({ ...productData, sku: code });
    }
    
    let filteredProducts = Object.values(productsById).filter(productGroup => {
        const buMatch = selectedBu === 'all' || productGroup.bu === selectedBu;
        const searchMatch = !searchTerm || productGroup.description.toLowerCase().includes(searchTerm) || productGroup.codes.some(code => code.sku.toLowerCase().includes(searchTerm));
        if (!buMatch || !searchMatch) return false;

        if (selectedStatus === 'all') return true;

        const totalStockForGroup = productGroup.codes
            .filter(c => c.type === 'sku')
            .reduce((sum, sku) => {
                const invEntry = sku.inventory.find(inv => inv.location === selectedLoc);
                return sum + (invEntry ? parseInt(invEntry.stock) : 0);
            }, 0);

        if (selectedStatus === 'in_stock') return totalStockForGroup > 10;
        if (selectedStatus === 'low_stock') return totalStockForGroup > 0 && totalStockForGroup <= 10;
        if (selectedStatus === 'no_stock') return totalStockForGroup === 0;
        
        return false;
    });
    
    if (filteredProducts.length === 0) {
        list.innerHTML = `<tr><td colspan="3" class="!text-center py-8 text-slate-500">No products match filters.</td></tr>`;
        return;
    }

    let finalHtml = '';
    filteredProducts
        .sort((a,b) => a.description.localeCompare(b.description))
        .forEach(productGroup => {
            const barcode = productGroup.codes.find(c => c.type === 'barcode');
            const skus = productGroup.codes.filter(c => c.type === 'sku').sort((a,b) => a.sku.localeCompare(b.sku));

            finalHtml += `
                <tr>
                    <td data-label="Barcode / SKU">
                        <div class="font-bold text-slate-800">${barcode ? barcode.sku : 'NO BARCODE'}</div>
                        ${skus.map(sku => `<div class="font-mono text-xs text-slate-500 mt-1">${sku.sku}</div>`).join('')}
                    </td>
                    <td data-label="Description">${productGroup.description}</td>
                    <td data-label="Stock on Hand">
                        ${skus.map(sku => {
                            const invEntry = sku.inventory.find(inv => inv.location === selectedLoc);
                            const stock = invEntry ? parseInt(invEntry.stock) : 0;
                            const stockColor = stock === 0 ? 'text-slate-400' : (stock <= 10 ? 'text-red-600' : 'text-slate-800');
                            return `<div class="flex justify-end md:justify-start items-center gap-2 mt-1">
                                        <span class="font-mono text-xs text-slate-500">${sku.sku}</span>
                                        <span class="font-semibold ${stockColor}">${stock.toLocaleString('en-US')}</span>
                                    </div>`;
                        }).join('')}
                    </td>
                </tr>
            `;
        });

    list.innerHTML = finalHtml;
}

export function initStocksDashboard() {
    const filterIds = ['locFilterStocks', 'stockSearchInput', 'buFilter', 'stockStatusFilter'];
    filterIds.forEach(id => {
        document.getElementById(id)?.addEventListener('input', renderStocksDashboard);
    });
}