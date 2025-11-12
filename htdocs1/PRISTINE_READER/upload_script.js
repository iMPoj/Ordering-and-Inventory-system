document.addEventListener('DOMContentLoaded', () => {
    const davaoForm = document.getElementById('davao-form');
    const davaoDropZone = document.getElementById('davao-drop-zone');
    const davaoFileInput = davaoDropZone.querySelector('.drop-zone-input');
    const davaoFileList = document.getElementById('davao-file-list');

    const gensanForm = document.getElementById('gensan-form');
    const gensanDropZone = document.getElementById('gensan-drop-zone');
    const gensanFileInput = gensanDropZone.querySelector('.drop-zone-input');
    const gensanFileList = document.getElementById('gensan-file-list');

    const statusMessage = document.getElementById('status-message');

    // Setup for Davao
    setupDropZone(davaoDropZone, davaoFileInput, davaoFileList);
    davaoForm.addEventListener('submit', (e) => {
        e.preventDefault();
        uploadFiles(davaoFileInput, 'davao_files[]', 'handle_upload.php', statusMessage, davaoFileList);
    });

    // Setup for Gensan
    setupDropZone(gensanDropZone, gensanFileInput, gensanFileList);
    gensanForm.addEventListener('submit', (e) => {
        e.preventDefault();
        uploadFiles(gensanFileInput, 'gensan_files[]', 'handle_upload.php', statusMessage, gensanFileList);
    });
});

function setupDropZone(dropZone, fileInput, fileList) {
    dropZone.addEventListener('click', () => fileInput.click());

    fileInput.addEventListener('change', () => {
        if (fileInput.files.length) {
            updateFileList(fileInput.files, fileList);
        }
    });

    dropZone.addEventListener('dragover', (e) => {
        e.preventDefault();
        dropZone.classList.add('drop-zone--over');
    });

    ['dragleave', 'dragend'].forEach(type => {
        dropZone.addEventListener(type, () => {
            dropZone.classList.remove('drop-zone--over');
        });
    });

    dropZone.addEventListener('drop', (e) => {
        e.preventDefault();
        if (e.dataTransfer.files.length) {
            fileInput.files = e.dataTransfer.files;
            updateFileList(fileInput.files, fileList);
        }
        dropZone.classList.remove('drop-zone--over');
    });
}

function updateFileList(files, fileList) {
    fileList.innerHTML = ''; // Clear current list
    for (const file of files) {
        const item = document.createElement('div');
        item.className = 'file-list-item';
        item.textContent = file.name;
        fileList.appendChild(item);
    }
}

async function uploadFiles(fileInput, fieldName, url, statusEl, fileListEl) {
    const formData = new FormData();
    if (!fileInput.files.length) {
        statusEl.textContent = 'Please select files to upload.';
        statusEl.className = 'error';
        return;
    }

    for (const file of fileInput.files) {
        formData.append(fieldName, file);
    }

    statusEl.textContent = 'Uploading...';
    statusEl.className = '';
    statusEl.innerHTML = 'Uploading...'; // Clear previous debug info

    try {
        const response = await fetch(url, {
            method: 'POST',
            body: formData,
        });

        if (!response.ok) {
            throw new Error(`Server responded with status: ${response.status}`);
        }
        
        const result = await response.json();

        statusEl.textContent = result.message;
        statusEl.className = result.status;

        if (result.status === 'success') {
            fileInput.value = '';
            fileListEl.innerHTML = '';
        }

        // Display debug info if present
        if (result.debug) {
            console.error('Server Debug Info:', result.debug);
            const debugInfo = document.createElement('pre');
            debugInfo.style.textAlign = 'left';
            debugInfo.style.backgroundColor = '#2c2c3e';
            debugInfo.style.padding = '10px';
            debugInfo.style.borderRadius = '5px';
            debugInfo.style.whiteSpace = 'pre-wrap';
            debugInfo.style.wordBreak = 'break-all';
            debugInfo.textContent = result.debug;

            statusEl.appendChild(document.createElement('br'));
            statusEl.appendChild(debugInfo);
        }

    } catch (error) {
        console.error('Upload Error:', error);
        statusEl.textContent = 'A critical error occurred. Check the browser console (F12) for details.';
        statusEl.className = 'error';
    }
}