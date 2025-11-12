 document.addEventListener('DOMContentLoaded', () => {
    const loginForm = document.getElementById('login-form');
    const errorMessage = document.getElementById('error-message');

    if (!loginForm) {
        console.error('Login form not found!');
        return;
    }

    loginForm.addEventListener('submit', async (event) => {
        // Prevent the form from submitting the default way
        event.preventDefault();
        errorMessage.classList.add('hidden'); // Hide old errors

        // Create a FormData object from the form
        const formData = new FormData(loginForm);
        
        // Add the 'login' action for the API router
        formData.append('action', 'login');

        try {
            const response = await fetch('api.php', {
                method: 'POST',
                body: formData
            });

            if (!response.ok) {
                throw new Error(`Server error: ${response.statusText}`);
            }

            const result = await response.json();

            if (result.success) {
                // Login was successful, redirect to the main app
                window.location.href = 'index.php';
            } else {
                // Login failed, show the error message from the server
                errorMessage.textContent = result.message || 'An unknown error occurred.';
                errorMessage.classList.remove('hidden');
            }

        } catch (error) {
            console.error('Login request failed:', error);
            errorMessage.textContent = 'Could not connect to the server. Please try again later.';
            errorMessage.classList.remove('hidden');
        }
    });
})