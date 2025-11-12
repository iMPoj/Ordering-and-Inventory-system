import { appState } from './state.js';
import { fetchData, postData } from './api.js';
import { showLoader, hideLoader, showMessage } from './ui.js';

import { initDashboard, populateDashboardFilters, renderDashboard } from './dashboard.js';
import { initStocksDashboard, renderStocksDashboard } from './stocks_dashboard.js';
import { initAdmin } from './admin.js';
import { initOrderBook, populateOrderBookFilters, fetchOrderBookPage } from './order_book.js';
import { initUnservedPage, populateUnservedFilters, fetchUnservedPage } from './unserved.js';
import { initFulfillable, populateFulfillableFilters, fetchFulfillableData } from './fulfillable.js';

const pages = [
    'dashboard', 'stocksDashboard', 'orderBook', 'unserved',
    'fulfillable', 'admin' // 'encoder' is removed from this list
];

// Keep track of which pages have been initialized
const initializedTabs = new Set();

function switchTab(tabId) {
    appState.activeTab = tabId;
    window.location.hash = tabId;

    pages.forEach(page => {
        const pageElement = document.getElementById(`${page}Page`);
        if (pageElement) {
            pageElement.classList.toggle('hidden', page !== tabId);
        }
    });

    document.querySelectorAll('.nav-link, .dropdown-link, .nav-link-mobile').forEach(link => {
        link.classList.remove('active');
        if (link.getAttribute('data-tab') === tabId) {
            link.classList.add('active');
        }
    });

    // --- NEW LOGIC: Initialize the page's JS only when it's first switched to ---
    if (!initializedTabs.has(tabId)) {
        switch (tabId) {
            case 'dashboard': initDashboard(); break;
            case 'stocksDashboard': initStocksDashboard(); break;
            case 'orderBook': initOrderBook(); break;
            case 'unserved': initUnservedPage(); break;
            case 'fulfillable': initFulfillable(); break;
            case 'admin': initAdmin(refreshData); break;
        }
        initializedTabs.add(tabId);
    }

    // Always refresh data for data-heavy pages
    switch (tabId) {
        case 'dashboard': renderDashboard(); break;
        case 'stocksDashboard': renderStocksDashboard(); break;
        case 'orderBook': fetchOrderBookPage(1); break;
        case 'unserved': fetchUnservedPage(1); break;
        case 'fulfillable': fetchFulfillableData(); break;
    }
}

function setupEventListeners() {
    // This function handles tab switching when a URL hash changes or a link is clicked
    function handleNavigation(e) {
        const link = e.target.closest('a[data-tab]');
        if (link) {
            e.preventDefault();
            const tabId = link.getAttribute('data-tab');
            // If the link is for the create order page, navigate normally
            if (tabId === 'encoder') {
                window.location.href = 'create_order.php';
                return;
            }
            switchTab(tabId);
        }
    }

    document.body.addEventListener('click', handleNavigation);
    window.addEventListener('hashchange', () => {
        const hash = window.location.hash.substring(1);
        if (hash && pages.includes(hash)) switchTab(hash);
    });
    
    // Keep the listeners for the "More" dropdown
    const moreBtn = document.getElementById('more-links-btn');
    const moreDropdown = document.getElementById('more-links-dropdown');
    moreBtn?.addEventListener('click', () => moreDropdown.classList.toggle('hidden'));
    document.addEventListener('click', (e) => {
        if (!moreBtn?.contains(e.target) && !moreDropdown?.contains(e.target)) {
            moreDropdown?.classList.add('hidden');
        }
    });
}

async function refreshData() {
    showLoader();
    try {
        const [productsResponse, customersData] = await Promise.all([
            fetchData('get_products'),
            fetchData('get_customers')
        ]);

        appState.products = {};
        if (productsResponse && Array.isArray(productsResponse.data)) {
            productsResponse.data.forEach(p => {
                if (Array.isArray(p.codes)) {
                    p.codes.forEach(s => {
                        appState.products[s.code] = {
                            productId: p.id,
                            description: p.description,
                            bu: p.bu,
                            inventory: s.inventory || [],
                            sales_price: s.sales_price,
                            pieces_per_case: s.pieces_per_case,
                            type: s.type
                        };
                    });
                }
            });
        }
        
        appState.customers = Array.isArray(customersData.data) ? customersData.data : [];
        
        // Populate filters that depend on this data
        populateDashboardFilters();
        populateOrderBookFilters();
        populateUnservedFilters();
        populateFulfillableFilters();

    } catch (error) {
        console.error("Failed to refresh critical data:", error);
        showMessage("Could not load essential product and customer data.", true);
    } finally {
        hideLoader();
    }
}

async function initApp() {
    showLoader();
    setupEventListeners();

    try {
        await refreshData(); 
        
        const initialTab = window.location.hash.substring(1) || 'dashboard';
        if (pages.includes(initialTab)) {
            switchTab(initialTab);
        } else {
            switchTab('dashboard');
        }

    } catch (e) {
        console.error("A critical error occurred during app initialization:", e);
        showMessage("Failed to load critical application data.", true);
    } finally {
        hideLoader();
    }
}

document.addEventListener('DOMContentLoaded', initApp);