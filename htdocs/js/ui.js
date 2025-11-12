 export function showLoader() {
    const loadingOverlay = document.getElementById('loading-overlay');
    if (loadingOverlay) loadingOverlay.style.display = 'flex';
}

export function hideLoader() {
    const loadingOverlay = document.getElementById('loading-overlay');
    if (loadingOverlay) loadingOverlay.style.display = 'none';
}

export function showMessage(text, isError = false) {
    if (typeof window.Toastify === 'undefined') {
        console.error("Toastify is not loaded! Falling back to alert.");
        alert(text);
        return;
    }

    window.Toastify({
        text: text,
        duration: 3000,
        close: true,
        gravity: "top", 
        position: "right", 
        stopOnFocus: true, 
        style: {
            background: isError ? "linear-gradient(to right, #ef4444, #b91c1c)" : "linear-gradient(to right, #10b981, #059669)",
        }
    }).showToast();
}

export function showConfirmation(text, onConfirm) {
    const confirmModal = document.getElementById('confirmModal');
    const confirmText = document.getElementById('confirmModalText');
    const yesBtn = document.getElementById('confirmModalYesBtn');
    const noBtn = document.getElementById('confirmModalNoBtn');

    if (!confirmModal || !confirmText || !yesBtn || !noBtn) {
         if (confirm(text)) {
             if (typeof onConfirm === 'function') onConfirm();
         }
         return;
    }

    confirmText.textContent = text;
    
    const newYesBtn = yesBtn.cloneNode(true);
    const newNoBtn = noBtn.cloneNode(true);
    yesBtn.parentNode.replaceChild(newYesBtn, yesBtn);
    noBtn.parentNode.replaceChild(newNoBtn, noBtn);

    const close = () => confirmModal.classList.add('hidden');

    newYesBtn.addEventListener('click', () => {
        if (typeof onConfirm === 'function') {
            onConfirm();
        }
        close();
    });

    newNoBtn.addEventListener('click', close);

    confirmModal.classList.remove('hidden');
}