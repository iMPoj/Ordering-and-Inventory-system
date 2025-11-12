document.addEventListener('DOMContentLoaded', () => {
    // --- Mobile Menu Toggle ---
    const mobileMenuButton = document.getElementById('mobile-menu-button');
    const mobileMenu = document.getElementById('mobile-menu');

    if (mobileMenuButton && mobileMenu) {
        mobileMenuButton.addEventListener('click', () => {
            mobileMenu.classList.toggle('hidden');
        });
    }

    // --- Navigation from Standalone Pages (like product_search.php) ---
    // This part ensures header links work correctly when you are NOT on index.php
    const currentPage = window.location.pathname.split('/').pop();
    if (currentPage !== 'index.php') {
        document.querySelectorAll('a[data-tab]').forEach(link => {
            link.addEventListener('click', (e) => {
                // No need to prevent default, the href will navigate
            });
        });
    }
});