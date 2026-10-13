// =======================================================
// SkillSwap - AI Chatbot & Campus Advisor Client
// =======================================================

document.addEventListener('DOMContentLoaded', () => {
    const toggler = document.getElementById('chatbotToggler');
    const panel = document.getElementById('chatbotPanel');
    const closeBtn = document.getElementById('chatbotClose');
    const form = document.getElementById('chatbotForm');
    const input = document.getElementById('chatbotInput');
    const messages = document.getElementById('chatbotMessages');
    const quickPrompts = document.querySelectorAll('.quick-prompt-btn');

    if (!toggler || !panel) return;

    // Toggle chatbot panel
    toggler.addEventListener('click', () => {
        panel.classList.toggle('open');
        if (panel.classList.contains('open')) {
            input.focus();
        }
    });

    closeBtn.addEventListener('click', () => {
        panel.classList.remove('open');
    });

    // Quick prompt clicks
    quickPrompts.forEach(btn => {
        btn.addEventListener('click', () => {
            const promptText = btn.getAttribute('data-prompt');
            if (promptText) {
                sendMessage(promptText);
            }
        });
    });

    // Send on form submit
    form.addEventListener('submit', (e) => {
        e.preventDefault();
        const text = input.value.trim();
        if (!text) return;
        sendMessage(text);
        input.value = '';
    });

    async function sendMessage(userText) {
        // Append user bubble
        appendBubble('user', userText);

        // Show typing indicator
        const typingId = showTypingIndicator();

        try {
            // Find base API URL
            const apiEndpoint = window.SKILLSWAP_BASE_URL 
                ? `${window.SKILLSWAP_BASE_URL}/api/ai_chat.php` 
                : '../api/ai_chat.php';

            const response = await fetch(apiEndpoint, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ message: userText })
            });

            const data = await response.json();
            removeTypingIndicator(typingId);

            if (data.status === 'success') {
                appendBubble('bot', data.reply);
            } else {
                appendBubble('bot', data.reply || 'Sorry, I ran into a minor issue. Please try again!');
            }
        } catch (err) {
            removeTypingIndicator(typingId);
            appendBubble('bot', "I'm having trouble reaching the campus AI server. Feel free to explore the Search and Match pages in the meantime!");
        }
    }

    function appendBubble(sender, text) {
        const bubble = document.createElement('div');
        bubble.className = `chat-bubble ${sender}`;
        // Basic formatting for linebreaks & bold
        const formatted = text
            .replace(/\n/g, '<br>')
            .replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>');
        bubble.innerHTML = formatted;
        messages.appendChild(bubble);
        messages.scrollTop = messages.scrollHeight;
    }

    function showTypingIndicator() {
        const id = 'typing-' + Date.now();
        const bubble = document.createElement('div');
        bubble.className = 'chat-bubble bot text-muted';
        bubble.id = id;
        bubble.innerHTML = '<small><i class="bi bi-three-dots"></i> AI is thinking...</small>';
        messages.appendChild(bubble);
        messages.scrollTop = messages.scrollHeight;
        return id;
    }

    function removeTypingIndicator(id) {
        const el = document.getElementById(id);
        if (el) el.remove();
    }
});
