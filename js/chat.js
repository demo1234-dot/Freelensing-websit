document.addEventListener('DOMContentLoaded', function() {
    const chatBox = document.getElementById('chat-box');
    const messageForm = document.getElementById('message-form');
    const messageInput = document.getElementById('message-input');
    const projectId = document.getElementById('project_id') ? document.getElementById('project_id').value : null;

    function displayError(message) {
        if (chatBox) {
            chatBox.innerHTML = `<div class="text-center h-100 d-flex align-items-center justify-content-center">
                                    <p class="text-danger">${message}</p>
                                 </div>`;
        }
    }

    function fetchMessages() {
        if (!projectId || !chatBox) return;

        fetch(`actions/get_messages.php?project_id=${projectId}`)
            .then(response => {
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                return response.json();
            })
            .then(data => {
                if (!chatBox) return;
                chatBox.innerHTML = '';
                if (data.error) {
                    displayError(data.error);
                    return;
                }
                if (data.length === 0) {
                    chatBox.innerHTML = `<div class="text-center h-100 d-flex align-items-center justify-content-center">
                                            <p class="text-muted">No messages yet. Start the conversation!</p>
                                         </div>`;
                } else {
                    data.forEach(message => {
                        const messageElement = document.createElement('div');
                        messageElement.classList.add('message');
                        
                        let bubble = `<div class="bubble">${message.message}</div>`;
                        let timestamp = `<small class="text-muted d-block mt-1">${new Date(message.created_at).toLocaleTimeString()}</small>`;

                        if (message.sender_id == myUserId) {
                            messageElement.classList.add('sent');
                            messageElement.innerHTML = bubble + timestamp;
                        } else {
                            messageElement.classList.add('received');
                            messageElement.innerHTML = bubble + timestamp;
                        }
                        chatBox.appendChild(messageElement);
                    });
                }
                // Scroll to the bottom
                chatBox.scrollTop = chatBox.scrollHeight;
            })
            .catch(error => {
                console.error('Error fetching messages:', error);
                displayError('Could not load messages. Please check your connection or try again later.');
            });
    }

    if (messageForm) {
        messageForm.addEventListener('submit', function(e) {
            e.preventDefault();
            if (messageInput.value.trim() === '') return;

            const formData = new FormData(this);

            fetch('actions/send_message.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    messageInput.value = '';
                    fetchMessages();
                } else {
                    alert(data.error || 'Error sending message.');
                }
            })
            .catch(error => {
                console.error('Error sending message:', error);
                alert('Could not send message. Please check your connection.');
            });
        });
        
        messageInput.addEventListener('keypress', function (e) {
            if (e.key === 'Enter' && !e.shiftKey) {
                e.preventDefault();
                messageForm.dispatchEvent(new Event('submit', {cancelable: true}));
            }
        });
    }

    if (projectId) {
        fetchMessages();
        setInterval(fetchMessages, 3000);
    }
});