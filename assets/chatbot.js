/**
 * chatbot.js
 * نظام الدردشة الذكي للعملاء - بوت عزم
 */

class ChatBot {
    constructor(options = {}) {
        try {
            this.base = options.base || (window.AZM_BASE || (function(){
                const scripts = document.getElementsByTagName('script');
                for (let i = scripts.length - 1; i >= 0; i--) {
                    const src = scripts[i].src || '';
                    if (src.indexOf('assets/chatbot.js') !== -1) {
                        const u = new URL(src, window.location.href);
                        return u.pathname.replace(/\/assets\/chatbot\.js.*$/, '');
                    }
                }
                return '';
            })());
        } catch (e) { this.base = ''; }
        this.apiUrl = options.apiUrl || (this.base + '/api_chat.php');
        this.conversationId = null;
        this.sessionId = null;
        this.customerId = null;
        this.isOpen = false;
        this.botName = 'عزم';
        
        this.init();
    }
    
    init() {
        this.createWidget();
        this.attachEventListeners();
        this.loadConversation();
    }
    
    createWidget() {
        const html = `
            <div id="chatbot-widget" class="chatbot-widget">
                <div class="chatbot-label">إسأل عزم</div>
                <button id="chatbot-toggle" class="chatbot-toggle" title="فتح الدردشة">
                    <img src="${window.APP_BASE_URL}/assets/img/favicon-32x32.png" alt="عزم" class="chatbot-icon-img">
                </button>
                <div id="chatbot-window" class="chatbot-window" style="display:none;">
                    <div class="chatbot-header">
                        <div class="chatbot-title">
                            <h3>🤖 عزم - مساعدك الذكي</h3>
                            <p class="chatbot-subtitle">متاح 24/7 لمساعدتك</p>
                        </div>
                        <button id="chatbot-close" class="chatbot-close">✕</button>
                    </div>
                    <div id="chatbot-messages" class="chatbot-messages">
                        <div class="chatbot-welcome"><div class="welcome-icon">👋</div><h4>مرحباً بك!</h4><p>كيف يمكننا مساعدتك؟</p></div>
                    </div>
                    <div class="chatbot-input-area">
                        <form id="chatbot-form" class="chatbot-form">
                            <input type="text" id="chatbot-input" class="chatbot-input" placeholder="اكتب رسالتك..." autocomplete="off">
                            <button type="submit" class="chatbot-send"><span>إرسال</span><span class="send-icon">➤</span></button>
                        </form>
                    </div>
                </div>
            </div>
        `;
        document.body.insertAdjacentHTML('beforeend', html);
        try{
            const icon = document.querySelector('#chatbot-widget .chatbot-icon-img');
            if (icon) { icon.src = `${window.APP_BASE_URL}/assets/img/favicon-32x32.png`; }
        }catch(e){}
        this.injectStyles();
    }
    
    injectStyles() {
        const styles = `<style>
.chatbot-widget{position:fixed;bottom:20px;left:20px;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,'Helvetica Neue',Arial,sans-serif;z-index:9999;display:flex;flex-direction:column;align-items:center;gap:8px}.chatbot-label{font-size:14px;font-weight:600;color:#667eea;background:white;padding:6px 12px;border-radius:20px;box-shadow:0 2px 8px rgba(0,0,0,.1);white-space:nowrap}.chatbot-toggle{width:60px;height:60px;border-radius:50%;background:0 0;border:none;color:white;font-size:28px;cursor:pointer;box-shadow:none;transition:all .3s ease;position:relative;padding:0;display:flex;align-items:center;justify-content:center;animation:float 3s ease-in-out infinite}@keyframes float{0%,to{transform:translateY(0)}50%{transform:translateY(-10px)}}.chatbot-toggle:hover{animation:none;transform:scale(1.1);box-shadow:0 6px 20px rgba(102,126,234,.6)}.chatbot-icon-img{width:48px;height:48px;object-fit:contain;display:block}.chatbot-window{position:absolute;bottom:80px;left:0;width:380px;height:600px;background:white;border-radius:12px;box-shadow:0 5px 40px rgba(0,0,0,.16);display:flex;flex-direction:column;animation:slideUp .3s ease}@keyframes slideUp{from{opacity:0;transform:translateY(20px)}to{opacity:1;transform:translateY(0)}}.chatbot-header{background:linear-gradient(135deg,#667eea 0%,#764ba2 100%);color:white;padding:20px;border-radius:12px 12px 0 0;display:flex;justify-content:space-between;align-items:flex-start}.chatbot-title h3{margin:0;font-size:18px;font-weight:600}.chatbot-subtitle{margin:4px 0 0;font-size:12px;opacity:.9}.chatbot-close{background:0 0;border:none;color:white;font-size:24px;cursor:pointer;padding:0;width:30px;height:30px;display:flex;align-items:center;justify-content:center}.chatbot-messages{flex:1;overflow-y:auto;padding:20px;background:#f7f7f7}.chatbot-welcome{text-align:center;padding:40px 20px;color:#666}.welcome-icon{font-size:48px;margin-bottom:10px}.chatbot-welcome h4{margin:10px 0 5px;color:#333}.chatbot-welcome p{margin:0;font-size:14px}.message{margin-bottom:12px;display:flex;animation:fadeIn .3s ease}@keyframes fadeIn{from{opacity:0;transform:translateY(10px)}to{opacity:1;transform:translateY(0)}}.message.customer{justify-content:flex-end}.message-content{max-width:70%;padding:10px 14px;border-radius:12px;font-size:14px;line-height:1.4;word-wrap:break-word}.message.customer .message-content{background:#667eea;color:white;border-bottom-right-radius:4px}.message.bot .message-content{background:white;color:#333;border:1px solid #e0e0e0;border-bottom-left-radius:4px}.chatbot-input-area{padding:12px;background:white;border-top:1px solid #e0e0e0;border-radius:0 0 12px 12px}.chatbot-form{display:flex;gap:8px}.chatbot-input{flex:1;border:1px solid #ddd;border-radius:20px;padding:10px 16px;font-size:14px;font-family:inherit;outline:none;transition:border-color .2s}.chatbot-input:focus{border-color:#667eea}.chatbot-send{background:#667eea;color:white;border:none;border-radius:50%;width:38px;height:38px;cursor:pointer;display:flex;align-items:center;justify-content:center;transition:background .2s;font-size:12px}.chatbot-send:hover{background:#764ba2}.send-icon{display:inline-block;margin-left:4px}@media (max-width:480px){.chatbot-window{width:calc(100vw - 40px);height:70vh;max-height:600px}}</style>`;
        document.head.insertAdjacentHTML('beforeend', styles);
    }
    
    attachEventListeners() {
        const toggle = document.getElementById('chatbot-toggle');
        const closeBtn = document.getElementById('chatbot-close');
        const form = document.getElementById('chatbot-form');
        toggle.addEventListener('click', () => this.toggleWindow());
        closeBtn.addEventListener('click', () => this.toggleWindow());
        form.addEventListener('submit', (e) => this.handleSubmit(e));
    }
    
    toggleWindow() {
        this.isOpen = !this.isOpen;
        const window = document.getElementById('chatbot-window');
        const toggle = document.getElementById('chatbot-toggle');
        if (this.isOpen) {
            window.style.display = 'flex';
            toggle.style.display = 'none';
            document.getElementById('chatbot-input').focus();
        } else {
            window.style.display = 'none';
            toggle.style.display = 'flex';
        }
    }
    
    async loadConversation() {
        // Always start a fresh conversation to avoid reusing other customers' chats
        await this.startConversation();
    }
    
    async startConversation() {
        try {
            const response = await fetch(this.apiUrl, {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: new URLSearchParams({ action: 'start_conversation' })
            });
            if (!response.ok) throw new Error(`HTTP ${response.status}`);
            const data = await response.json();
            if (data.success) {
                this.conversationId = data.data.conversation_id;
                this.addWelcomeMessage();
            } else {
                throw new Error(data.message || 'Failed to start conversation');
            }
        } catch (error) {
            console.error('Error starting conversation:', error);
            this.displayMessage('❌ خطأ في الاتصال. تأكد من اتصالك بالإنترنت.', 'bot');
        }
    }
    
    async handleSubmit(e) {
        e.preventDefault();
        const input = document.getElementById('chatbot-input');
        const message = input.value.trim();
        if (!message) return;

        this.displayMessage(message, 'customer');
        input.value = '';
        input.focus();
        
        this.displayMessage('⏳ جاري معالجة رسالتك...', 'bot', null, 'loading-indicator');

        try {
            const response = await fetch(this.apiUrl, {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: new URLSearchParams({
                    action: 'send_message',
                    conversation_id: this.conversationId,
                    message: message,
                    sender_type: 'customer'
                })
            });

            if (!response.ok) {
                try {
                    const errorData = await response.json();
                    throw new Error(errorData.message || `خطأ في الخادم: ${response.status}`);
                } catch (e) {
                    throw new Error(`خطأ في الشبكة: ${response.statusText}`);
                }
            }

            const data = await response.json();
            if (data.success) {
                await new Promise(r => setTimeout(r, 500));
                await this.loadMessages();
            } else {
                throw new Error(data.message || 'An unexpected server error occurred.');
            }

        } catch (error) {
            console.error('Error sending message:', error);
            this.displayMessage(`❌ خطأ: ${error.message}`, 'bot');
        } finally {
            const loadingIndicator = document.querySelector('[data-msg-id="loading-indicator"]');
            if (loadingIndicator) loadingIndicator.remove();
        }
    }
    
    async loadMessages() {
        try {
            const response = await fetch(`${this.apiUrl}?action=get_messages&conversation_id=${this.conversationId}`);
            if (!response.ok) throw new Error('Failed to fetch messages');
            const data = await response.json();
            if (data.success) {
                this.displayMessages(data.data);
            }
        } catch (error) {
            console.error('Error fetching messages:', error);
        }
    }
    
    displayMessages(messages) {
        const container = document.getElementById('chatbot-messages');
        const welcome = container.querySelector('.chatbot-welcome');
        if (welcome && messages.length > 0) welcome.remove();
        
        container.innerHTML = ''; 
        messages.forEach(msg => {
            this.displayMessage(msg.message, msg.sender_type, msg.created_at, msg.id, false);
        });
        container.scrollTop = container.scrollHeight;
    }
    
    displayMessage(text, type, time = null, msgId = null, scroll = true) {
        const container = document.getElementById('chatbot-messages');
        const messageEl = document.createElement('div');
        messageEl.className = `message ${type}`;
        if (msgId) messageEl.setAttribute('data-msg-id', msgId);

        const content = document.createElement('div');
        content.className = 'message-content';
        content.textContent = text;
        
        messageEl.appendChild(content);
        container.appendChild(messageEl);
        if (scroll) {
            container.scrollTop = container.scrollHeight;
        }
    }

    addWelcomeMessage() {
        const container = document.getElementById('chatbot-messages');
        container.innerHTML = `<div class="chatbot-welcome"><div class="welcome-icon">🤖</div><h4>أنا عزم!</h4><p>مساعدك الذكي - كيف يمكنني مساعدتك؟</p></div>`;
    }
}

document.addEventListener('DOMContentLoaded', () => {
    window.chatBot = new ChatBot();
});
