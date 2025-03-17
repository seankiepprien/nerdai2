<div id="chat-container">
    <div id="chat-messages"></div>
    <input type="text" id="chat-input" placeholder="Type a message..." />
    <button id="send-btn">Send</button>
</div>

<script src="https://js.pusher.com/7.0/pusher.min.js"></script>
<script>
    var pusher = new Pusher("<?= config('services.pusher.key') ?>", {
        cluster: "<?= config('services.pusher.cluster') ?>"
    });

    var channel = pusher.subscribe("chat-channel");
    channel.bind("new-message", function(data) {
        var chatMessages = document.getElementById("chat-messages");
        var newMessage = document.createElement("div");
        newMessage.textContent = (data.role === 'assistant' ? 'AI: ' : 'You: ') + data.message;
        chatMessages.appendChild(newMessage);
        chatMessages.scrollTop = chatMessages.scrollHeight; // Auto-scroll
    });

    function sendMessage(event) {
        event.preventDefault(); // ✅ Stop default form submission
        event.stopPropagation(); // ✅ Ensure it doesn't bubble up

        var input = document.getElementById("chat-input");
        var message = input.value.trim();
        if (message === "") return;

        // ✅ Ensure AJAX request without page reload
        $.request("onSendMessage", {
            data: { message: message },
            success: function(response) {
                input.value = ""; // ✅ Clear input after sending
            },
            error: function(error) {
                console.error("Error sending message:", error);
            }
        });
    }

    // ✅ Attach event listener
    document.getElementById("send-btn").addEventListener("click", sendMessage);

    // ✅ Also allow "Enter" key to send messages
    document.getElementById("chat-input").addEventListener("keypress", function(event) {
        if (event.key === "Enter") {
            sendMessage(event);
        }
    });
</script>

<style>
    #chat-container { padding: 10px; border: 1px solid #ccc; width: 300px; }
    #chat-messages { height: 200px; overflow-y: auto; border-bottom: 1px solid #ddd; padding: 5px; }
    #chat-input { width: 80%; }
</style>
