document.addEventListener('DOMContentLoaded', () => {

    // --- Toast Notification Function ---
    // Creates a temporary notification message on the screen.
    const showToast = (message, type = 'success') => {
        // Create toast element
        const toast = document.createElement('div');
        toast.className = `toast toast-${type}`;
        toast.textContent = message;

        // Add to DOM
        document.body.appendChild(toast);

        // Animate in
        setTimeout(() => {
            toast.classList.add('show');
        }, 100);

        // Animate out and remove after 3 seconds
        setTimeout(() => {
            toast.classList.remove('show');
            toast.addEventListener('transitionend', () => toast.remove());
        }, 3000);
    };
    
    // --- Add Toast Styles to the Page Dynamically ---
    // This ensures the notifications work without needing to modify the CSS file.
    const addToastStyles = () => {
        const style = document.createElement('style');
        style.textContent = `
            .toast {
                position: fixed;
                bottom: 20px;
                left: 50%;
                transform: translateX(-50%);
                padding: 1rem 2rem;
                border-radius: 8px;
                color: #fff;
                font-family: 'Inter', 'Kanit', sans-serif;
                font-size: 1rem;
                z-index: 9999;
                box-shadow: 0 4px 12px rgba(0,0,0,0.15);
                opacity: 0;
                visibility: hidden;
                transition: opacity 0.3s ease, visibility 0.3s ease, transform 0.3s ease;
            }
            .toast.show {
                opacity: 1;
                visibility: visible;
                transform: translate(-50%, -10px);
            }
            .toast-success {
                background-color: #28a745; /* var(--success-color) */
            }
            .toast-error {
                background-color: #dc3545; /* var(--danger-color) */
            }
        `;
        document.head.appendChild(style);
    };

    addToastStyles();


    // --- AJAX for User Role/Status Changes in manage_users.php ---
    // This hijacks the form submission to prevent a page reload.
    const userActionForms = document.querySelectorAll('.action-form');
    
    userActionForms.forEach(form => {
        // We use 'change' on the select element itself for a better UX
        const select = form.querySelector('select');
        if (select) {
            select.addEventListener('change', function(event) {
                // The form is automatically submitted by the 'onchange' HTML attribute.
                // We listen for the 'submit' event on the form.
            });
        }

        form.addEventListener('submit', function(event) {
            event.preventDefault(); // Stop the default page reload

            const formData = new FormData(form);
            const selectElement = form.querySelector('select');
            
            // Provide visual feedback that something is happening
            selectElement.disabled = true;

            fetch(form.action, {
                method: 'POST',
                body: formData
            })
            .then(response => {
                 // Check if the response is ok, if not, parse as text for errors
                 if (!response.ok) {
                    return response.text().then(text => { throw new Error(text) });
                 }
                 // Try to parse as JSON, but handle non-json responses
                 return response.json().catch(() => ({ status: 'error', message: 'Received an invalid response from server.' }));
            })
            .then(data => {
                if (data && data.status === 'success') {
                    showToast(data.message || 'Update successful!');
                    // Update the select element's class to reflect the new status/role color
                    if(selectElement.name === 'status'){
                        selectElement.className = `status-select status-${selectElement.value}`;
                    }
                } else {
                    showToast(data.message || 'An unknown error occurred.', 'error');
                    // If there was an error, revert the change in the UI
                    // This requires storing the original value, which adds complexity.
                    // For now, a page reload on error might be a simpler way to reset state.
                    // location.reload(); 
                }
            })
            .catch(error => {
                console.error('Fetch Error:', error);
                showToast('A network or server error occurred.', 'error');
            })
            .finally(() => {
                // Re-enable the select element after the operation is complete
                selectElement.disabled = false;
            });
        });
    });

});