/**
 * Utility for managing guest user data.
 */
export default {
    /**
     * Store data for the guest user.
     *
     * @param {string} key - The key to store the data under.
     * @param {any} value - The data to store.
     * @returns {Promise} - A promise that resolves when the data is stored.
     */
    async storeData(key, value) {
        try {
            const response = await fetch('/guest/data', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                },
                body: JSON.stringify({ key, value }),
            });
            
            return await response.json();
        } catch (error) {
            console.error('Error storing guest data:', error);
            throw error;
        }
    },
    
    /**
     * Get data for the guest user.
     *
     * @param {string} key - The key to retrieve the data for.
     * @returns {Promise} - A promise that resolves with the data.
     */
    async getData(key) {
        try {
            const response = await fetch(`/guest/data/${key}`, {
                method: 'GET',
                headers: {
                    'Content-Type': 'application/json',
                },
            });
            
            const result = await response.json();
            return result.data;
        } catch (error) {
            console.error('Error retrieving guest data:', error);
            throw error;
        }
    },
    
    /**
     * Clear all data for the guest user.
     *
     * @returns {Promise} - A promise that resolves when the data is cleared.
     */
    async clearData() {
        try {
            const response = await fetch('/guest/data', {
                method: 'DELETE',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                },
            });
            
            return await response.json();
        } catch (error) {
            console.error('Error clearing guest data:', error);
            throw error;
        }
    },
};