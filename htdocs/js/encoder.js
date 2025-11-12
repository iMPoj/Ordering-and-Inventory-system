import { appState } from './state.js';
import { postData, fetchData } from './api.js';
import { showLoader, hideLoader, showMessage, showConfirmation } from './ui.js';

let editingItemIndex = null;
let selectedLocation = '';
let itemEntryFieldset, summaryFieldset, itemEntryOverlay, summaryOverlay;
let debounceTimer;
let currentItemData = null;

// --- INITIALIZATION AND STATE MANAGEMENT ---

async function init() {
    // This page now loads its own data
    showLoader();
    await Promise.all([
        fetchData('get_products').then(res => {
            if (res.success && Array.isArray(res.data)) {
                appState.products = {};
                res.data.forEach(p => {
                    if (Array.isArray(p.codes)) {
                        p.codes.forEach(s => {
                            appState.products[s.code] = {
                                productId: p.id, description: p.description, bu: p.bu,
                                inventory: s.inventory || [], sales_price: s.sales_price,
                                pieces_per_case: s.pieces_per_case, type: s.type
                            };
                        });
                    }
                });
            }
        }),
        fetchData('get_customers').then(res => {
            if (res.success && Array.isArray(res.data)) {
                appState.customers = res.data;
            }
        })
    ]);
    hideLoader();

    // Cache DOM elements after data is loaded
    itemEntryFieldset = document.getElementById('itemEntryFieldset');
    summaryFieldset = document.getElementById('summaryFieldset');
    itemEntryOverlay = document.getElementById('itemEntryOverlay');
    summaryOverlay = document.getElementById('summaryOverlay');
    
    setupEventListeners();
    loadOrderFromStorage();
}

function updateFormState() {
    const locationSelected = document.getElementById('orderLocation').value;
    const buSelected = document.getElementById('orderBu').value;
    const isDisabled = !locationSelected || !buSelected;

    if (itemEntryFieldset) itemEntryFieldset.disabled = isDisabled;
    if (summaryFieldset) summaryFieldset.disabled = isDisabled;
    if (itemEntryOverlay) itemEntryOverlay.style.display = isDisabled ? 'flex' : 'none';
    if (summaryOverlay) summaryOverlay.style.display = isDisabled ? 'flex' : 'none';

    if (!isDisabled) {
        localStorage.setItem('defaultLocation', locationSelected);
    }
    selectedLocation = locationSelected;

}

function saveOrderToStorage() {
    const orderDraft = {
        orderLocation: selectedLocation,
        orderBu: document.getElementById('orderBu').value,
        customerName: document.getElementById('customerName').value,
        poNumber: document.getElementById('poNumber').value,
        address: document.getElementById('customerAddress').value,
        discount: document.getElementById('discountPercentage').value,
        items: appState.orderItems,
        selectedCustomer: appState.selectedCustomer
    };
    sessionStorage.setItem('currentOrderDraft', JSON.stringify(orderDraft));
}

function loadOrderFromStorage() {
    const draftJSON = sessionStorage.getItem('currentOrderDraft');
    if (!draftJSON) {
        document.getElementById('orderLocation').value = localStorage.getItem('defaultLocation') || '';
        updateFormState();
        return;
    }
    try {
        const draft = JSON.parse(draftJSON);
        if (!draft) return;
        document.getElementById('customerName').value = draft.customerName || '';
        document.getElementById('poNumber').value = draft.poNumber || '';
        document.getElementById('customerAddress').value = draft.address || '';
        document.getElementById('discountPercentage').value = draft.discount || '';
        document.getElementById('orderBu').value = draft.orderBu || '';
        document.getElementById('orderLocation').value = draft.orderLocation || '';
        appState.orderItems = Array.isArray(draft.items) ? draft.items : [];
        appState.selectedCustomer = draft.selectedCustomer || null;
        updateOrderSummary();
        updateFormState();
    } catch (e) {
        console.error("Failed to load draft", e);
        sessionStorage.removeItem('currentOrderDraft');
    }
}

function resetEncoderState() {
    appState.selectedCustomer = null;
    appState.orderItems = [];
    
    document.getElementById('orderBu').value = '';
    document.getElementById('customerName').value = '';
    document.getElementById('discountPercentage').value = '';
    document.getElementById('customerAddress').value = '';
    document.getElementById('poNumber').value = '';
    document.getElementById('customerCode').value = '';
    
    stopEditingItem();
    updateOrderSummary();
    sessionStorage.removeItem('currentOrderDraft');
    document.getElementById('orderLocation').value = localStorage.getItem('defaultLocation') || '';
    updateFormState();
}

function setupAutocomplete(inputId, suggestionsId, onSelect, sourceFunction) {
    const input = document.getElementById(inputId);
    const suggestionsBox = document.getElementById(suggestionsId);

    input.addEventListener('input', () => {
        const term = input.value.trim().toLowerCase();
        
        if (term.length === 0) {
            suggestionsBox.classList.add('hidden');
            return;
        }
        
        clearTimeout(debounceTimer);
        debounceTimer = setTimeout(async () => {
            const suggestions = await sourceFunction(term);
            if(suggestions.length > 0) {
                suggestionsBox.innerHTML = suggestions.map(s => {
                    const displayText = s.name || s.description || s.address;
                    const subText = s.sku || s.barcode || s.customer_code || '';
                    const safeSuggestionJSON = JSON.stringify(s).replace(/"/g, '&quot;');
                    return `<div class="p-2 hover:bg-slate-100 cursor-pointer suggestion-item" data-suggestion="${safeSuggestionJSON}">
                                ${displayText} <span class="text-xs text-slate-400">${subText}</span>
                            </div>`;
                }).join('');
                suggestionsBox.classList.remove('hidden');
            } else {
                suggestionsBox.classList.add('hidden');
            }
        }, 300);
    });
    
    const selectItem = (item) => {
        if (item) {
            onSelect(JSON.parse(item.dataset.suggestion.replace(/&quot;/g, '"')));
            suggestionsBox.classList.add('hidden');
        }
    };

    suggestionsBox.addEventListener('click', (e) => {
        selectItem(e.target.closest('.suggestion-item'));
    });

    // --- START: THIS IS THE CORRECTED PART FOR FASTER INPUT ---
    input.addEventListener('keydown', async (e) => {
        if (e.key === 'Tab') {
            // Prevent the default Tab behavior (moving to the next field)
            e.preventDefault();

            // Clear any pending debounced search
            clearTimeout(debounceTimer);

            const term = input.value.trim();
            if (term.length === 0) return;

            // Immediately run the search without waiting for the debounce
            const suggestions = await sourceFunction(term);

            // If we have results, select the very first one
            if (suggestions && suggestions.length > 0) {
                onSelect(suggestions[0]);
                suggestionsBox.classList.add('hidden');
            }
            // If no suggestions, do nothing, keeping focus in the current input.
        }
    });
    // --- END: THIS IS THE CORRECTED PART ---

    input.addEventListener('blur', () => setTimeout(() => suggestionsBox.classList.add('hidden'), 200));
}
async function getProductSuggestions(term) {
    const bu = document.getElementById('orderBu').value;
    const result = await postData('get_product_suggestions', { term, bu });
    return result.success ? result.data : [];
}

async function handleProductSelect(productObject) {
    if (!productObject) return;

    // --- This is the new, improved logic ---

    // 1. Immediately fill the main description field for instant user feedback.
    document.getElementById('itemDescription').value = productObject.description;
    
    // 2. Use the product's unique ID to reliably fetch all of its details (all SKUs, prices, stock).
    const location = document.getElementById('orderLocation').value;
    const result = await postData('find_product_with_best_sku', { term: productObject.id, location });
    
    // 3. If the details are found, populate the rest of the item form.
    if (result.success && result.data) {
        populateItemDetails(result.data);
    } else {
        showMessage('Could not retrieve full product details.', true);
    }
}

// KEEP THIS VERSION
async function searchCustomers(term) {
    // This function filters the existing customer list based on the search term.
    return appState.customers.filter(c => c.name.toLowerCase().includes(term));
}

function onCustomerSelect(customer) {
    // This function handles what happens when a customer is selected.
    if (customer) {
        appState.selectedCustomer = customer;
        document.getElementById('customerName').value = customer.name;
        document.getElementById('discountPercentage').value = customer.default_discount || '';
        updateOrderSummary();
        document.getElementById('customerAddress').focus();
    }
}

function populateItemDetails(productData) {
    currentItemData = productData; // <-- Store the complete, fresh data

    const skuSelect = document.getElementById('itemSkuSelect');
    const descriptionInput = document.getElementById('itemDescription');
    const barcodeInput = document.getElementById('itemBarcode');
    
    if (!productData) {
        skuSelect.innerHTML = '';
        document.getElementById('skuSelectionContainer').classList.add('hidden');
        return;
    }
    
    descriptionInput.value = productData.description;
    
    const barcode = productData.allSkus.find(s => s.type === 'barcode');
    if (barcode) {
        barcodeInput.value = barcode.code;
    }

    const skusWithOptions = productData.allSkus
        .filter(s => s.type === 'sku' && parseFloat(s.sales_price) > 0);
        
    if(skusWithOptions.length === 0){
        showMessage(`No valid SKUs with prices found for ${productData.description}. Cannot add item.`, true);
        clearItemInputs();
        return;
    }

    skuSelect.innerHTML = skusWithOptions.map(s => `<option value="${s.code}">${s.code}</option>`).join('');
    
    document.getElementById('skuSelectionContainer').classList.remove('hidden');
    
    if (productData.bestSku) {
        skuSelect.value = productData.bestSku;
    } else if (skuSelect.options.length > 0) {
        skuSelect.value = skuSelect.options[0].value;
    }
    
    updateStockDisplay();
    calculateTotalPrice(); // We no longer need to pass data here
    
    document.getElementById('itemQuantity').focus();
    document.getElementById('itemQuantity').select();
}

function calculateTotalPrice() {
    const selectedSku = document.getElementById('itemSkuSelect').value;
    let quantity = parseInt(document.getElementById('itemQuantity').value);
    const unit = document.getElementById('itemUnit').value;
    const discount = parseFloat(document.getElementById('discountPercentage').value) || 0;

    if (!selectedSku || !quantity || quantity <= 0 || !currentItemData) {
        document.getElementById('itemPrice').value = '';
        return;
    }
    
    const skuInfo = currentItemData.allSkus.find(s => s.code === selectedSku);
    
    if (skuInfo && skuInfo.sales_price > 0) {
        if (unit === 'case') {
            quantity *= (parseInt(skuInfo.pieces_per_case) || 1);
        }
        
        let totalPrice = parseFloat(skuInfo.sales_price) * quantity;
        let discountedPrice = totalPrice * (1 - discount / 100);
        document.getElementById('itemPrice').value = discountedPrice.toFixed(2);
    } else {
        document.getElementById('itemPrice').value = '0.00';
    }
}

function updateStockDisplay() {
    const productCode = document.getElementById('itemSkuSelect').value;
    if (!productCode) return;
    const productInfo = appState.products[productCode];
    if (productInfo) {
        const invEntry = productInfo.inventory.find(i => i.location === selectedLocation);
        const stock = invEntry ? parseInt(invEntry.stock) : 0;
        document.getElementById('skuStockDisplay').textContent = `Stock: ${stock.toLocaleString('en-US')}`;
        document.getElementById('caseInfoDisplay').textContent = `(1 case = ${productInfo.pieces_per_case || 1} pcs)`;
    }
}

function handleItemSubmit() {
    const selectedSku = document.getElementById('itemSkuSelect').value;
    if (!currentItemData || !selectedSku) {
        return showMessage('Please search and select a product first.', true);
    }
    
    let quantity = parseInt(document.getElementById('itemQuantity').value);
    
    if (!quantity || quantity <= 0) {
        return showMessage('Please enter a valid quantity.', true);
    }

    if (currentItemData.bu !== document.getElementById('orderBu').value) {
        return showMessage(`Item BU (${currentItemData.bu}) does not match order BU.`, true);
    }
    
    const unit = document.getElementById('itemUnit').value;
    const skuInfo = currentItemData.allSkus.find(s => s.code === selectedSku);
    const finalQuantity = unit === 'case' ? quantity * (parseInt(skuInfo.pieces_per_case) || 1) : quantity;
    const price = parseFloat(document.getElementById('itemPrice').value);

    const stock = skuInfo.stock ? parseInt(skuInfo.stock) : 0;
    
    const newItem = { 
        sku: selectedSku, 
        description: currentItemData.description,
        quantity: finalQuantity, 
        price,
        status: stock >= finalQuantity ? 'served' : 'unserved' 
    };

    if (stock < finalQuantity) {
        showMessage(`Warning: Stock for ${newItem.description} is insufficient. Marked as unserved.`, true);
    }
    
    if (editingItemIndex !== null) {
        // This is for when you are already in edit mode and click "Update Item"
        appState.orderItems[editingItemIndex] = newItem;
    } else {
        // This is the new logic for adding an item
        const existingItemIndex = appState.orderItems.findIndex(item => item.sku === newItem.sku);
        
        if (existingItemIndex > -1) {
            // If the item already exists, switch to editing it instead of adding
            showMessage(`Item already in list. Switched to edit mode.`);
            startEditingItem(existingItemIndex);
            // We stop here and don't add a new item
            return; // Exit the function
        } else {
            // If it's a brand new item, add it to the list
            appState.orderItems.push(newItem);
        }
    }
    
    updateOrderSummary();
    stopEditingItem();
}

function startEditingItem(index) {
    editingItemIndex = index;
    const item = appState.orderItems[index];
    
    // --- START OF FIX ---
    // We need to simulate a product object to pass to handleProductSelect
    const productObject = {
        id: appState.products[item.sku]?.productId,
        description: item.description,
        sku: item.sku
    };

    if (!productObject.id) {
        showMessage('Could not find product details to edit.', true);
        return;
    }
    
    handleProductSelect(productObject);
    // --- END OF FIX ---
    
    setTimeout(() => {
        const productInfo = appState.products[item.sku];
        if (productInfo && productInfo.pieces_per_case > 1 && item.quantity % productInfo.pieces_per_case === 0) {
            document.getElementById('itemUnit').value = 'case';
            document.getElementById('itemQuantity').value = item.quantity / productInfo.pieces_per_case;
        } else {
            document.getElementById('itemUnit').value = 'pcs';
            document.getElementById('itemQuantity').value = item.quantity;
        }
        calculateTotalPrice();
        document.getElementById('addItemBtn').textContent = 'Update Item';
    }, 500); // Increased timeout slightly for reliability
}

function stopEditingItem() {
    editingItemIndex = null;
    // --- START OF FIX ---
    // The clearItemInputs() function handles clearing the form, so we just reset the button text here.
    document.getElementById('addItemBtn').textContent = 'Add Item';
    clearItemInputs(); // Make sure the form is fully cleared for the next item.
    // --- END OF FIX ---
}

function clearItemInputs() {
    currentItemData = null; // <-- Reset the state
    document.getElementById('itemBarcode').value = '';
    document.getElementById('itemDescription').value = '';
    document.getElementById('itemQuantity').value = '1';
    document.getElementById('itemUnit').value = 'pcs';
    document.getElementById('itemPrice').value = '';
    
    const skuContainer = document.getElementById('skuSelectionContainer');
    skuContainer.classList.add('hidden');
    document.getElementById('itemSkuSelect').innerHTML = '';
    document.getElementById('skuStockDisplay').textContent = '';
    document.getElementById('caseInfoDisplay').textContent = '';
    
    document.getElementById('itemBarcode').focus();
}

// --- ORDER SUMMARY LOGIC ---

function updateOrderSummary() {
    const list = document.getElementById('orderItemsList');
    list.innerHTML = appState.orderItems.map((item, index) => {
        const productInfo = appState.products[item.sku];
        const buMismatch = productInfo && productInfo.bu !== document.getElementById('orderBu').value;
        const buWarning = buMismatch ? `<span class="text-red-500 font-bold ml-2" title="BU Mismatch! Order is ${document.getElementById('orderBu').value}, item is ${productInfo.bu}">!</span>` : '';

        return `
        <tr class="text-sm item-summary-row" data-index="${index}">
            <td data-label="Description" class="px-4 py-2">
                ${item.description} ${buWarning}
                <div class="summary-sku-display font-mono text-xs text-slate-500">${item.sku}</div>
            </td>
            <td data-label="Qty" class="px-4 py-2 text-center w-24">
                <span class="summary-qty-display">${item.quantity.toLocaleString('en-US')}</span>
            </td>
            <td data-label="Actions" class="px-4 py-2 text-right">
                <button class="edit-btn text-blue-600 hover:text-blue-900 mr-2 text-sm font-medium">Edit</button>
                <button class="delete-btn text-red-600 hover:text-red-900 text-sm font-medium">Delete</button>
            </td>
        </tr>`;
    }).join('');

    const total = appState.orderItems.reduce((sum, item) => sum + (parseFloat(item.price) || 0), 0);
    document.getElementById('orderTotalDisplay').textContent = total.toLocaleString('en-US', { style: 'currency', currency: 'PHP' });
    
    saveOrderToStorage();
}



// --- FINAL SUBMISSION ---

async function submitFinalOrder() {
    showLoader();
    const orderData = {
        location: selectedLocation,
        bu: document.getElementById('orderBu').value,
        customer_id: appState.selectedCustomer ? appState.selectedCustomer.id : null,
        customer_name: document.getElementById('customerName').value,
        customer_address: document.getElementById('customerAddress').value,
        po_number: document.getElementById('poNumber').value,
        discount: document.getElementById('discountPercentage').value,
        items: JSON.stringify(appState.orderItems)
    };
    try {
        const result = await postData('add_order', orderData);
        if (result.success) {
            showMessage(`Order #${result.order_id} created successfully.`);

            // --- START: MODIFIED BEHAVIOR ---

            // 1. Open the newly created order in a new browser tab
            window.open(`view_order.php?id=${result.order_id}`, '_blank');

            // 2. Perform the partial reset for the next order
            resetForNextOrder();

            // --- END: MODIFIED BEHAVIOR ---

        } else {
            showMessage(result.message || 'Failed to create order.', true);
        }
    } catch (e) {
        showMessage('An error occurred while submitting the order.', true);
    } finally {
        hideLoader();
    }
}

/**
 * Resets the form for the next order for the same customer.
 * Keeps Location, BU, Customer Name, and Discount.
 * Clears items, PO, address, etc.
 */
function resetForNextOrder() {
    // 1. Clear order items from the application state
    appState.orderItems = [];

    // 2. Clear specific form fields that change with each order
    document.getElementById('poNumber').value = '';
    document.getElementById('customerAddress').value = '';
    document.getElementById('customerCode').value = '';
    
    // 3. Completely reset the item entry section
    stopEditingItem(); // This also calls clearItemInputs()

    // 4. Update the visual summary table and save the new partial draft
    updateOrderSummary();
    
    // Optional: Focus the PO Number field for the next order
    document.getElementById('poNumber').focus();
}

// --- EVENT LISTENERS ---

function setupEventListeners() {
    // Customer and Order Details
    document.querySelectorAll('#orderLocation, #orderBu').forEach(el => el.addEventListener('change', updateFormState));
    ['customerName', 'customerAddress', 'poNumber', 'discountPercentage'].forEach(id => document.getElementById(id)?.addEventListener('input', saveOrderToStorage));

    // Autocomplete for Customer Name (using your requested function names)
    setupAutocomplete('customerName', 'customerSuggestions', onCustomerSelect, searchCustomers);

    // Autocomplete for Customer Address
    setupAutocomplete('customerAddress', 'addressSuggestions',
        (suggestion) => {
            document.getElementById('customerAddress').value = suggestion.address;
            document.getElementById('customerCode').value = suggestion.customer_code;
            saveOrderToStorage();
            document.getElementById('poNumber').focus();
        },
        async (term) => {
            const result = await postData('get_address_suggestions', { term });
            return result.success ? result.data : [];
        }
    );
    
    // Item Entry Autocomplete
    setupAutocomplete('itemBarcode', 'barcodeSuggestions', handleProductSelect, getProductSuggestions);
    setupAutocomplete('itemDescription', 'descriptionSuggestions', handleProductSelect, getProductSuggestions);
    
    document.getElementById('addItemBtn').addEventListener('click', handleItemSubmit);
    ['itemQuantity', 'itemUnit', 'itemSkuSelect'].forEach(id => {
        const el = document.getElementById(id);
        el?.addEventListener('input', calculateTotalPrice);
        el?.addEventListener('change', updateStockDisplay);
    });

    // Order Summary Actions
    document.getElementById('orderItemsList').addEventListener('click', (e) => {
        const target = e.target;
        if (target.classList.contains('edit-btn')) {
            startEditingItem(parseInt(target.closest('tr').dataset.index));
        }
        if (target.classList.contains('delete-btn')) {
            const index = parseInt(target.closest('tr').dataset.index);
            showConfirmation(`Delete item "${appState.orderItems[index].description}"?`, () => {
                appState.orderItems.splice(index, 1);
                updateOrderSummary();
                if (editingItemIndex === index) stopEditingItem();
            });
        }
    });

    // Main Actions
    document.getElementById('submitOrderBtn')?.addEventListener('click', () => {
        if(appState.orderItems.length === 0) return showMessage("Cannot submit an empty order.", true);
        showConfirmation("Are you sure you want to submit this order?", submitFinalOrder);
    });
    
    document.getElementById('cancelOrderBtn')?.addEventListener('click', () => {
        showConfirmation("Are you sure you want to clear this entire order draft?", resetEncoderState);
    });
}

document.addEventListener('DOMContentLoaded', init);