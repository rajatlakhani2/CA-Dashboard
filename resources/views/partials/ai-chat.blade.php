<div id="ai-chat-widget" class="fixed bottom-6 right-6 z-[9999] font-sans">
    <!-- Chat Window -->
    <div id="ai-window" style="display: none;" class="hidden flex-col w-80 h-96 bg-white/80 backdrop-blur-xl border border-white/40 shadow-2xl rounded-2xl overflow-hidden transition-all transform origin-bottom-right duration-300">
        <!-- Header -->
        <div class="bg-gradient-to-r from-indigo-600 to-purple-600 p-4 flex justify-between items-center text-white">
            <div class="flex items-center space-x-2">
                <div class="h-2 w-2 bg-green-400 rounded-full animate-pulse"></div>
                <h3 class="font-bold text-sm">Antigravity AI</h3>
            </div>
            <button onclick="toggleAI()" class="text-white/80 hover:text-white">
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>

        <!-- Messages Area -->
        <div id="ai-messages" class="flex-1 p-4 overflow-y-auto space-y-3 text-sm">
            <!-- Welcome Message -->
            <div class="flex items-start space-x-2">
                <div class="h-6 w-6 rounded-full bg-indigo-100 flex items-center justify-center text-xs">🤖</div>
                <div class="bg-white/50 backdrop-blur-sm p-2 rounded-lg rounded-tl-none border border-white/50 shadow-sm text-gray-700">
                    Hello! I'm your dashboard assistant. Try asking: <br>
                    <span class="text-indigo-600 cursor-pointer hover:underline" onclick="sendQuick('Show overdue')">• Show overdue</span><br>
                    <span class="text-indigo-600 cursor-pointer hover:underline" onclick="sendQuick('Create invoice')">• Create invoice</span><br>
                    <span class="text-indigo-600 cursor-pointer hover:underline" onclick="sendQuick('Go to settings')">• Go to settings</span>
                </div>
            </div>
        </div>

        <!-- Input Area -->
        <div class="p-3 bg-white/50 border-t border-white/40">
            <form onsubmit="handleAI(event)" class="relative">
                <input type="text" id="ai-input" placeholder="Type a command..." class="w-full pl-4 pr-10 py-2 rounded-full border-none bg-white/60 focus:ring-2 focus:ring-purple-500 shadow-inner text-sm text-gray-700 placeholder-gray-400">
                <button type="submit" class="absolute right-2 top-1.5 text-purple-600 hover:text-purple-700">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8" />
                    </svg>
                </button>
            </form>
        </div>
    </div>

    <!-- Floating Button -->
    <button id="ai-toggle-btn" onclick="toggleAI()" class="mt-4 ml-auto h-14 w-14 bg-gradient-to-br from-indigo-500 to-purple-600 rounded-full shadow-lg shadow-purple-500/30 flex items-center justify-center text-white hover:scale-110 transition-transform duration-200 group">
        <svg class="w-7 h-7 group-hover:rotate-12 transition-transform" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z" />
        </svg>
    </button>
</div>

<script>
    function toggleAI() {
        const window = document.getElementById('ai-window');
        const btn = document.getElementById('ai-toggle-btn');

        if (window.classList.contains('hidden')) {
            window.classList.remove('hidden');
            window.classList.remove('style'); // Remove inline style
            window.style.display = 'flex'; // Enforce flex
            window.classList.add('flex');
            btn.classList.add('hidden');
            setTimeout(() => document.getElementById('ai-input').focus(), 100);
        } else {
            window.classList.add('hidden');
            window.classList.remove('flex');
            window.style.display = 'none'; // Enforce none
            btn.classList.remove('hidden');
        }
    }

    function sendQuick(text) {
        document.getElementById('ai-input').value = text;
        handleAI({
            preventDefault: () => {}
        });
    }

    function handleAI(e) {
        e.preventDefault();
        const input = document.getElementById('ai-input');
        const messages = document.getElementById('ai-messages');
        const text = input.value.trim().toLowerCase();

        if (!text) return;

        // User Message
        appendMessage(input.value, 'user');
        input.value = '';

        // Response Logic (Simulated AI)
        setTimeout(() => {
            let response = "I'm not sure how to help with that yet.";
            let action = null;

            if (text.includes('overdue') || text.includes('pending')) {
                response = "Navigating to Compliance Dashboard filtered by Overdue...";
                action = () => window.location.href = "{{ route('service-dues.index') }}?status=Overdue";
            } else if (text.includes('invoice') && text.includes('create')) {
                response = "Opening Invoice Creator...";
                action = () => window.location.href = "{{ route('invoices.create') }}";
            } else if (text.includes('client') && text.includes('new')) {
                response = "Taking you to Client Registration...";
                action = () => window.location.href = "{{ route('clients.create') }}";
            } else if (text.includes('settings')) {
                response = "Opening Settings...";
                action = () => window.location.href = "{{ route('settings.index') }}";
            } else if (text.includes('hello') || text.includes('hi')) {
                response = "Hello! I'm ready to assist. Try 'Show compliance calendar' or 'New Task'.";
            } else if (text.includes('calendar')) {
                response = "Showing the Master Calendar...";
                action = () => window.location.href = "{{ route('compliance.index') }}";
            } else if (text.includes('task')) {
                response = "Let's create a new task.";
                action = () => window.location.href = "{{ route('tasks.create') }}";
            }

            appendMessage(response, 'ai');

            if (action) {
                setTimeout(action, 1000);
            }
        }, 600);
    }

    function appendMessage(text, sender) {
        const messages = document.getElementById('ai-messages');
        const div = document.createElement('div');
        div.className = sender === 'user' ? 'flex justify-end' : 'flex items-start space-x-2';

        if (sender === 'user') {
            div.innerHTML = `
                <div class="bg-indigo-600 text-white p-2 rounded-lg rounded-tr-none text-right shadow-md">
                    ${text}
                </div>
            `;
        } else {
            div.innerHTML = `
                <div class="h-6 w-6 rounded-full bg-indigo-100 flex items-center justify-center text-xs">🤖</div>
                <div class="bg-white/50 backdrop-blur-sm p-2 rounded-lg rounded-tl-none border border-white/50 shadow-sm text-gray-700">
                    ${text}
                </div>
            `;
        }

        messages.appendChild(div);
        messages.scrollTop = messages.scrollHeight;
    }
</script>