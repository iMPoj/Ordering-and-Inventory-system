const API_URL = 'api.php';

/**
 * Fetches data from the server using a GET request.
 * It's important to keep this function for other parts of your app.
 */
export async function fetchData(action) {
    try {
        const url = new URL(API_URL, window.location.href);
        url.searchParams.set('action', action);
        const response = await fetch(url);
        const result = await response.json();
        if (!response.ok) {
            throw new Error(result.message || `HTTP error! status: ${response.status}`);
        }
        if (result.success === false) throw new Error(result.message);
        return result;
    } catch (error) {
        console.error(`Could not fetch ${action}:`, error);
        return { success: false, message: error.message, data: [] }; 
    }
}

/**
 * Sends data to the server using a POST request.
 * This is the function your orders page is currently using.
 */
export async function postData(action, data) {
     try {
        const formData = new FormData();
        formData.append('action', action);

        if (data instanceof FormData) {
            for (const [key, value] of data.entries()) {
                if (key !== 'action') {
                    formData.append(key, value);
                }
            }
        } else {
            for (const key in data) {
                formData.append(key, data[key]);
            }
        }
        
        const url = new URL(API_URL, window.location.href);
        const response = await fetch(url, {
            method: 'POST',
            body: formData,
        });

        const result = await response.json();
        if (!response.ok) {
            throw new Error(result.message || `HTTP error! status: ${response.status}`);
        }
       
        return result;
    } catch (error) {
        console.error(`Could not post ${action}:`, error);
        return { success: false, message: error.message };
    }
}