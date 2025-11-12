import { showLoader, hideLoader, showMessage, showConfirmation } from './ui.js';
import { postData, fetchData } from './api.js';

let isEditMode = false;

/**
 * Toggles the entire page between view and edit mode.
 * @param {boolean} edit - True to enter edit mode, false to exit.
 */
function setEditMode(edit) {
    isEditMode = edit;
    
    // Enable/disable the discount input
    document.getElementById('orderDiscountInput').disabled = !edit;

    document.querySelectorAll('.item-row').forEach(row => {
        row.querySelector('.sku-text-display').classList.toggle('hidden', edit);
        row.querySelector('.sku-select').classList.toggle('hidden', !edit);
        
        row.querySelectorAll('.sku-select, .quantity-input, .status-toggle-btn').forEach(el => {
            el.disabled = !edit;
        });
        row.querySelector('.price-input').readOnly = true; 
    });

    document.querySelectorAll('.soNumberText').forEach(el => el.classList.toggle('hidden', edit));
    document.querySelectorAll('.soNumberInput').forEach(el => el.classList.toggle('hidden', !edit));

    document.getElementById('editOrderBtn').classList.toggle('hidden', edit);
    document.getElementById('saveChangesBtn').classList.toggle('hidden', !edit);
    document.getElementById('cancelChangesBtn').classList.toggle('hidden', !edit);
    document.getElementById('deleteOrderBtn')?.classList.toggle('hidden', edit);

    if (edit) {
        fetchAllProductStocks();
    }
}

/**
 * Recalculates the total price for a single item row.
 * @param {HTMLElement} row - The table row element for the item.
 */
/**
 * Recalculates the total price for a single item row.
 * @param {HTMLElement} row - The table row element for the item.
 */
function recalculateRowPrice(row) {
    const quantityInput = row.querySelector('.quantity-input');
    const skuSelect = row.querySelector('.sku-select');
    const priceInput = row.querySelector('.price-input');
    const discountInput = document.getElementById('orderDiscountInput');

    const quantity = parseInt(quantityInput.value, 10) || 0;
    const selectedSku = skuSelect.value;
    const discount = parseFloat(discountInput.value) || 0;
    
    const productInfo = window.productsBySku[selectedSku];
    const unitPrice = productInfo ? productInfo.sales_price : 0;

    const preDiscountTotal = quantity * unitPrice;
    const finalTotal = preDiscountTotal * (1 - (discount / 100)); // Apply discount

    priceInput.value = finalTotal.toFixed(2);
}
/**
 * A helper function to loop through and recalculate all item rows.
 */
function recalculateAllPrices() {
    document.querySelectorAll('.item-row').forEach(row => {
        recalculateRowPrice(row);
    });
}

// (The functions fetchAllProductStocks, updateAllSkuDropdownsForProduct, and initializeStatusButton remain unchanged)
async function fetchAllProductStocks() {
    const productIds = new Set();
    document.querySelectorAll('.item-row').forEach(row => {
        const sku = row.querySelector('.sku-select').value || row.dataset.originalSku;
        const productInfo = window.productsBySku[sku];
        if (productInfo) {
            productIds.add(productInfo.productId);
        }
    });

    for (const productId of productIds) {
        try {
            // This API endpoint still needs to be created in api.php
            const result = await fetchData(`get_stock_for_product&product_id=${productId}`);
            if (result.success) {
                updateAllSkuDropdownsForProduct(productId, result.data);
            } else {
                console.error(`API error for product ${productId}:`, result.message);
            }
        } catch (e) {
            console.error(`Failed to fetch stock for product ${productId}`, e);
        }
    }
}
function updateAllSkuDropdownsForProduct(productId, stockData) {
    document.querySelectorAll('.item-row').forEach(row => {
        const originalSku = row.dataset.originalSku;
        const productInfo = window.productsBySku[originalSku];
        if (productInfo && productInfo.productId == productId) {
            const skuSelect = row.querySelector('.sku-select');
            const currentSku = skuSelect.value;
            
            skuSelect.innerHTML = productInfo.allSkus.map(s => {
                const stockInfo = stockData[s.code] || { Davao: 0, Gensan: 0 };
                return `<option value="${s.code}">
                            ${s.code} (${s.type}) (DVO: ${stockInfo.Davao} | GEN: ${stockInfo.Gensan})
                        </option>`;
            }).join('');
            skuSelect.value = currentSku;
        }
    });
}
function initializeStatusButton(btn) {
    const row = btn.closest('.item-row');
    let currentStatus = row.dataset.originalStatus;
    
    const updateButtonState = () => {
        btn.dataset.status = currentStatus;
        if (currentStatus === 'served') {
            btn.textContent = 'Served';
            btn.classList.add('bg-green-100', 'text-green-800');
            btn.classList.remove('bg-white', 'text-red-800', 'border', 'border-red-300');
        } else {
            btn.textContent = 'Unserved';
            btn.classList.add('bg-white', 'text-red-800', 'border', 'border-red-300');
            btn.classList.remove('bg-green-100', 'text-green-800');
        }
    };

    btn.addEventListener('click', () => {
        if (!isEditMode) return;
        currentStatus = (currentStatus === 'served') ? 'unserved' : 'served';
        updateButtonState();
    });
    updateButtonState();
}


/**
 * Main function that runs when the page is loaded to set up all event listeners.
 */
function init() {
    // --- NEW: Event listener for the discount input ---
    const discountInput = document.getElementById('orderDiscountInput');
    if(discountInput) {
        discountInput.addEventListener('input', recalculateAllPrices);
    }

    document.querySelectorAll('.item-row').forEach((row) => {
        const originalSku = row.dataset.originalSku;
        const skuSelect = row.querySelector('.sku-select');
        const productInfo = window.productsBySku[originalSku];
        const quantityInput = row.querySelector('.quantity-input');

        if (productInfo && productInfo.allSkus) {
            skuSelect.innerHTML = productInfo.allSkus.map(s => 
                `<option value="${s.code}">${s.code} (${s.type})</option>`
            ).join('');
            skuSelect.value = originalSku;

            skuSelect.addEventListener('change', () => {
                const newSku = skuSelect.value;
                const newProductInfo = window.productsBySku[newSku];
                if(newProductInfo) {
                    const descriptionElement = row.querySelector('.font-medium.text-slate-800');
                    descriptionElement.textContent = newProductInfo.description;
                    recalculateRowPrice(row);
                }
            });
        }
        
        if (quantityInput) {
            quantityInput.addEventListener('input', () => {
                recalculateRowPrice(row);
            });
        }
        
        const statusBtn = row.querySelector('.status-toggle-btn');
        if(statusBtn) initializeStatusButton(statusBtn);
    });

    // --- Main button listeners ---

    document.getElementById('editOrderBtn')?.addEventListener('click', () => setEditMode(true));
    document.getElementById('cancelChangesBtn')?.addEventListener('click', () => window.location.reload());

    document.getElementById('pristineCheckBtn')?.addEventListener('click', async (e) => {
        // ... (this button's logic is unchanged)
    });
    
    document.getElementById('deleteOrderBtn')?.addEventListener('click', () => {
        // ... (this button's logic is unchanged)
    });
    
    document.getElementById('saveChangesBtn')?.addEventListener('click', async () => {
        showLoader();
        const updatedItems = [];
        document.querySelectorAll('.item-row').forEach(row => {
            updatedItems.push({
                id: row.dataset.itemId, 
                sku: row.querySelector('.sku-select').value,
                description: row.querySelector('.font-medium.text-slate-800').textContent,
                quantity: row.querySelector('.quantity-input').value,
                price: row.querySelector('.price-input').value, // The price is now auto-calculated
                status: row.querySelector('.status-toggle-btn').dataset.status
            });
        });

        const soNumberInputs = document.querySelectorAll('.soNumberInput');
        const soNumbers = Array.from(soNumberInputs).map(input => input.value);
        
        // --- MODIFIED: Add the discount to the data sent to the server ---
        const discount = document.getElementById('orderDiscountInput').value;

        const result = await postData('update_order_items', {
            order_id: window.orderId,
            items: JSON.stringify(updatedItems),
            so_numbers: JSON.stringify(soNumbers),
            discount: discount // NEW: Sending the discount value
        });

        hideLoader();
        if (result.success) {
            showMessage(result.message);
            setTimeout(() => window.location.reload(), 1500);
        } else {
            showMessage(result.message || 'Failed to save changes.', true);
        }
    });
}

document.addEventListener('DOMContentLoaded', init);