document.addEventListener('DOMContentLoaded', () => {

    // --- 1. Reusable Toast Notification Function ---
    const showToast = (message, type = 'success') => {
        const toast = document.createElement('div');
        toast.className = `toast toast-${type}`;
        toast.textContent = message;
        document.body.appendChild(toast);
        setTimeout(() => {
            toast.classList.add('show');
        }, 100);
        setTimeout(() => {
            toast.classList.remove('show');
            toast.addEventListener('transitionend', () => toast.remove());
        }, 3500);
    };

    // --- Add Toast Styles (ensures it works without CSS changes) ---
    const addToastStyles = () => {
        // Prevent adding styles multiple times if another script already did
        if (document.getElementById('toast-styles')) return;
        const style = document.createElement('style');
        style.id = 'toast-styles';
        style.textContent = `
            .toast{position:fixed;bottom:20px;left:50%;transform:translateX(-50%);padding:1rem 2rem;border-radius:8px;color:#fff;font-family:'Inter','Kanit',sans-serif;font-size:1rem;z-index:9999;box-shadow:0 4px 12px rgba(0,0,0,0.15);opacity:0;visibility:hidden;transition:all 0.3s ease-in-out;}.toast.show{opacity:1;visibility:visible;transform:translate(-50%, -10px);}.toast-success{background-color:#28a745;}.toast-error{background-color:#dc3545;}.toast-info{background-color:#17a2b8;}
        `;
        document.head.appendChild(style);
    };

    addToastStyles();


    // --- 2. AJAX Chat System ---
    const chatBox = document.getElementById('chat-box');
    const chatForm = document.getElementById('chat-form');
    let lastMessageId = 0;

    if (chatBox && chatForm) {
        const groupId = chatBox.dataset.groupId || null;
        const currentUserId = chatBox.dataset.userId;

        const scrollToBottom = () => {
            chatBox.scrollTop = chatBox.scrollHeight;
        };

        const displayMessages = (messages) => {
            messages.forEach(msg => {
                const messageElement = document.createElement('div');
                messageElement.classList.add('chat-message');
                if (parseInt(msg.user_id, 10) === parseInt(currentUserId, 10)) {
                    messageElement.classList.add('current-user');
                }
                messageElement.innerHTML = `
                    <div class="message-sender role-${msg.role}">${msg.username}</div>
                    <div class="message-content">${msg.message_text.replace(/\n/g, '<br>')}</div>
                    <div class="message-time">${new Date(msg.sent_at).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' })}</div>
                `;
                chatBox.appendChild(messageElement);
                lastMessageId = Math.max(lastMessageId, msg.id);
            });
            scrollToBottom();
        };

        const fetchMessages = async () => {
            const formData = new FormData();
            formData.append('action', 'fetch');
            formData.append('last_message_id', lastMessageId);
            if (groupId) {
                formData.append('group_id', groupId);
            }

            try {
                const response = await fetch('api/chat.php', { method: 'POST', body: formData });
                const data = await response.json();
                if (data.status === 'success' && data.messages.length > 0) {
                    displayMessages(data.messages);
                }
            } catch (error) {
                console.error('Chat fetch error:', error);
            }
        };

        chatForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            const messageInput = document.getElementById('chat-message-input');
            const message = messageInput.value.trim();
            if (!message) return;

            const formData = new FormData();
            formData.append('action', 'send');
            formData.append('message', message);
            if (groupId) {
                formData.append('group_id', groupId);
            }

            try {
                const response = await fetch('api/chat.php', { method: 'POST', body: formData });
                const data = await response.json();
                if (data.status === 'success') {
                    messageInput.value = '';
                    fetchMessages(); // Fetch immediately to show own message
                } else {
                    showToast(data.message || 'Could not send message.', 'error');
                }
            } catch (error) {
                showToast('Network error.', 'error');
            }
        });

        // Initial fetch and start polling
        fetchMessages();
        setInterval(fetchMessages, 5000); // Poll every 5 seconds
    }


    // --- 3. Generic API Form Handler (for Profile, Payments, etc.) ---
    const handleApiForm = (formId, apiEndpoint) => {
        const form = document.getElementById(formId);
        if (form) {
            form.addEventListener('submit', async function(e) {
                e.preventDefault();
                const submitButton = form.querySelector('button[type="submit"]');
                const originalButtonText = submitButton.innerHTML;
                submitButton.disabled = true;
                submitButton.innerHTML = 'Processing...';

                try {
                    const formData = new FormData(form);
                    const response = await fetch(apiEndpoint, { method: 'POST', body: formData });
                    const data = await response.json();

                    if (data.status === 'success') {
                        showToast(data.message, 'success');
                        setTimeout(() => location.reload(), 1500); // Reload to see changes
                    } else {
                        showToast(data.message || 'An error occurred.', 'error');
                    }
                } catch (error) {
                    showToast('A network error occurred. Please try again.', 'error');
                } finally {
                    submitButton.disabled = false;
                    submitButton.innerHTML = originalButtonText;
                }
            });
        }
    };
    
    handleApiForm('profile-update-form', 'api/user_actions.php');
    handleApiForm('payment-slip-form', 'api/payment.php');
    handleApiForm('create-group-form', 'api/group_actions.php');


    // --- 4. Simple API Action Button Handler (for Join/Leave) ---
    document.body.addEventListener('click', async function(e) {
        const target = e.target.closest('[data-action]');
        if (!target) return;
        
        e.preventDefault();
        const { action, groupId } = target.dataset;

        if (!action || !groupId) return;

        target.disabled = true;

        try {
            const formData = new FormData();
            formData.append('action', action);
            formData.append('group_id', groupId);

            const response = await fetch('api/group_actions.php', { method: 'POST', body: formData });
            const data = await response.json();

            if (data.status === 'success') {
                showToast(data.message, 'success');
                setTimeout(() => location.reload(), 1500);
            } else {
                 showToast(data.message || 'An error occurred.', 'error');
                 target.disabled = false;
            }
        } catch(error) {
            showToast('A network error occurred.', 'error');
            target.disabled = false;
        }
    });

});