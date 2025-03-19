/**
 * Assistant Chat Component
 * Vanilla JavaScript implementation for OctoberCMS 3
 */
'use strict';

class NerdAIAssistantChat {
    constructor(element, options) {
        this.element = element;
        this.options = options || {};
        this.pusher = null;
        this.channel = null;
        this.isTyping = false;

        // Initialize the chat component
        this.init();
    }

    init() {
        // Cache DOM elements
        this.messagesContainer = this.element.querySelector('.chat-messages');
        this.messageInput = this.element.querySelector('.message-input');
        this.sendButton = this.element.querySelector('.send-button');
        this.typingIndicator = this.element.querySelector('.typing-indicator');

        console.log('Messages container found:', !!this.messagesContainer);
        if (this.messagesContainer) {
            console.log('Container exists with', this.messagesContainer.children.length, 'children');
        }

        if (this.typingIndicator) {
            this.typingIndicator.style.display = 'none';
        }

        // Setup event listeners
        this.setupEventListeners();

        // Setup Pusher if available
        this.setupPusher();

        // Scroll to bottom
        this.scrollToBottom();

        // Initialize markdown renderer if marked.js is available
        if (typeof marked !== 'undefined') {
            marked.setOptions({
                breaks: true,
                gfm: true,
                tables: true,
                smartLists: true
            });
        }
    }

    setupEventListeners() {
        // Send message on button click
        if (this.sendButton) {
            this.sendButton.addEventListener('click', () => {
                this.sendMessage();
            });
        }

        // Send message on Enter key (with shift+enter for new line)
        if (this.messageInput) {
            this.messageInput.addEventListener('keydown', (e) => {
                if (e.key === 'Enter' && !e.shiftKey) {
                    e.preventDefault();
                    this.sendMessage();
                }

                // Auto-resize textarea
                setTimeout(() => {
                    this.resizeInput();
                }, 0);
            });

            // Auto-resize on input
            this.messageInput.addEventListener('input', () => {
                this.resizeInput();
            });
        }

        // Clear chat button
        const clearChatBtn = this.element.querySelector('.clear-chat-btn');
        if (clearChatBtn) {
            clearChatBtn.addEventListener('click', () => {
                this.clearChat();
            });
        }
    }

    setupPusher() {
        const channelName = this.element.dataset.channel;
        const key = this.element.dataset.key;
        const cluster = this.element.dataset.cluster;

        if (!channelName || !key) {
            console.error('Pusher configuration is missing');
            return;
        }

        console.log('Setting up Pusher with: ', {
            channel: channelName,
            key: key,
            cluster: cluster
        });

        // Initialize Pusher
        this.pusher = new Pusher(key, {
            cluster: cluster,
            forceTLS: true
        });

        // Debug connection states
        this.pusher.connection.bind('state_change', states => {
            console.log('Pusher connection state changed from', states.previous, 'to', states.current);
        });

        // Subscribe to channel
        this.channel = this.pusher.subscribe(channelName);

        // Debug channel events
        this.channel.bind('subscription_succeeded', () => {
            console.log('Successfully subscribed to channel:', channelName);
        });

        this.channel.bind('subscription_error', error => {
            console.error('Error subscribing to Pusher channel:', error);
        });

        // Listen for messages with enhanced debugging
        this.channel.bind('chat-message', data => {
            console.log('Received message from Pusher channel', channelName, ':', data);
            this.handlePusherMessage(data);
        });
    }

    handlePusherMessage(data) {
        console.log('Processing Pusher message of type:', data.type || 'message', 'role:', data.role);

        if (data.type === 'status') {
            console.log('Handling status message:', data.status);
            this.handleStatusMessage(data);
        } else if (data.type === 'error') {
            console.log('Handling error message:', data.message);
            this.handleErrorMessage(data);
        } else {
            console.log('Handling chat message from role:', data.role);
            this.addMessage(data);
        }
    }

    handleStatusMessage(data) {
        if (data.status === 'typing') {
            this.showTypingIndicator();
        } else if (data.status === 'complete') {
            this.hideTypingIndicator();
        }
    }

    handleErrorMessage(data) {
        this.hideTypingIndicator();

        // Show error message in chat
        const errorMessage = {
            role: 'system',
            content: data.message || 'An error occurred',
            timestamp: data.timestamp
        };

        this.addMessage(errorMessage);
    }

    showTypingIndicator() {
        if (!this.isTyping && this.typingIndicator) {
            this.isTyping = true;
            this.typingIndicator.style.display = 'flex';
            this.scrollToBottom();
        }
    }

    hideTypingIndicator() {
        if (this.isTyping && this.typingIndicator) {
            this.isTyping = false;
            this.typingIndicator.style.display = 'none';
        }
    }

    sendMessage() {
        const message = this.messageInput.value.trim();
        if (!message) return;

        const threadId = this.element.dataset.threadId;

        // Clear input and resize
        this.messageInput.value = '';
        this.resizeInput();

        // Disable input while sending
        this.messageInput.disabled = true;
        this.sendButton.disabled = true;

        // Send the message to the server using oc.ajax
        oc.ajax('onSendMessage', {
            data: {
                message: message,
                thread_id: threadId
            },
            success: (data) => {
                // Re-enable input
                this.messageInput.disabled = false;
                this.sendButton.disabled = false;
                this.messageInput.focus();

                if (data.error) {
                    // Handle error
                    alert(data.error);
                }
            },
            error: () => {
                // Re-enable input
                this.messageInput.disabled = false;
                this.sendButton.disabled = false;

                // Show error
                alert('Failed to send message. Please try again.');
            }
        });
    }

    addMessage(message) {
        console.log('Adding message to chat:', message);

        // Don't add message if it's already in the DOM (check by timestamp)
        const timestamp = message.timestamp;
        if (this.messagesContainer.querySelector(`[data-timestamp="${timestamp}"]`)) {
            console.log('Message already exists, skipping');
            return;
        }

        // Create message element
        const messageEl = document.createElement('div');
        messageEl.className = `message ${message.role}-message`;
        messageEl.setAttribute('data-timestamp', timestamp);

        // Avatar
        const avatarSrc = message.role === 'user'
            ? this.element.dataset.userAvatar
            : this.element.dataset.assistantAvatar;

        const avatarEl = document.createElement('div');
        avatarEl.className = 'message-avatar';
        avatarEl.innerHTML = `<img src="${avatarSrc}" alt="Avatar">`;

        // Message content
        const contentEl = document.createElement('div');
        contentEl.className = 'message-content';

        // Message text with markdown support
        let messageText = message.content;
        const textEl = document.createElement('div');
        textEl.className = 'message-text';

        if (typeof marked !== 'undefined') {
            textEl.className += ' message-markdown';
            textEl.innerHTML = marked.parse(messageText);
        } else {
            textEl.textContent = messageText;
        }

        // Message timestamp
        const formattedTime = this.formatTimestamp(timestamp);
        const timeEl = document.createElement('div');
        timeEl.className = 'message-time';
        timeEl.textContent = formattedTime;

        // Assemble message
        contentEl.appendChild(textEl);
        contentEl.appendChild(timeEl);
        messageEl.appendChild(avatarEl);
        messageEl.appendChild(contentEl);

        // Add to container
        this.messagesContainer.appendChild(messageEl);
        console.log('Message added to DOM');

        // Scroll to the new message
        this.scrollToBottom();
    }

    formatTimestamp(timestamp) {
        if (!timestamp) return '';

        const date = new Date(timestamp);
        const hours = date.getHours();
        const minutes = date.getMinutes();
        const ampm = hours >= 12 ? 'PM' : 'AM';

        const formattedHours = hours % 12 || 12; // Convert 0 to 12
        const formattedMinutes = minutes < 10 ? '0' + minutes : minutes;

        return `${formattedHours}:${formattedMinutes} ${ampm}`;
    }

    scrollToBottom() {
        if (this.messagesContainer) {
            this.messagesContainer.scrollTop = this.messagesContainer.scrollHeight;
        }
    }

    resizeInput() {
        const input = this.messageInput;
        if (!input) return;

        // Reset height
        input.style.height = 'auto';

        // Set new height based on content
        const newHeight = Math.min(input.scrollHeight, 120);
        input.style.height = newHeight + 'px';
    }

    clearChat() {
        if (!confirm('Are you sure you want to clear the chat history and start a new conversation?')) {
            return;
        }

        oc.ajax('onClearChat', {
            success: (data) => {
                if (data.error) {
                    alert(data.error);
                    return;
                }

                // Update Pusher channel if needed
                if (this.channel && data.pusherChannel) {
                    this.pusher.unsubscribe(this.channel.name);
                    this.channel = this.pusher.subscribe(data.pusherChannel);

                    // Rebind event
                    this.channel.bind('chat-message', (messageData) => {
                        this.handlePusherMessage(messageData);
                    });
                }
            }
        });
    }
}

// Component registration function
function initAssistantChat() {
    const chatComponents = document.querySelectorAll('[data-control="assistant-chat"]');

    chatComponents.forEach(element => {
        // Only initialize if not already initialized
        if (!element.dataset.initialized) {
            const chat = new NerdAIAssistantChat(element);
            element.dataset.initialized = 'true';

            // Store instance in element for future reference if needed
            element.assistantChat = chat;
        }
    });
}

// Initialize on document ready using OctoberCMS 3's approach
document.addEventListener('DOMContentLoaded', initAssistantChat);

// Also initialize when AJAX updates complete
document.addEventListener('render', initAssistantChat);
