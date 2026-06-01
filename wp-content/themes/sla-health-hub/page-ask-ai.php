<?php
/**
 * Template Name: Ask AI Page
 * Simplified with single clinical assistant and save functionality
 */

get_header();

// Get customizer settings
$hero_bg = vance_get_theme_mod('vance_askai_hero_bg', get_template_directory_uri() . '/assets/img/about_hero.png');
$hero_title = vance_get_theme_mod('vance_askai_hero_title', 'Ask AI');
$hero_subtitle = vance_get_theme_mod('vance_askai_hero_subtitle', 'Direct access to our Gastro Health Hub. Ask anything about IBD, clinical nutrition, and gastrointestinal health.');
$hero_badge = vance_get_theme_mod('vance_askai_hero_badge', 'Information Assistant');
$askai_overlay = max(0, min(100, absint(vance_get_theme_mod('vance_askai_hero_overlay', 85)))) / 100;
$askai_overlay_bottom = min(1, $askai_overlay + 0.05);

// Define Single AI Agent
$agent_data = array(
    'name' => 'AI Information Assistant',
    'icon' => '🤖',
    'color' => '#fd4f00',
    'description' => 'Your Gastro Health Hub assistant. Ask anything about IBD, gut health, or clinical nutrition.',
    'training' => 'Trained on peer-reviewed IBD research, clinical guidelines, and gastrointestinal science datasets.',
    'abilities' => 'Evidence-based analysis, research synthesis, and clinical protocol insights.',
    'limitations' => 'Informational only. Not medical advice.',
);
?>

<style>
.askai-page {
    background: #F8FAFC;
    min-height: 100vh;
}

.askai-hero {
    background: linear-gradient(rgba(10, 25, 41, <?php echo esc_attr($askai_overlay); ?>), rgba(10, 25, 41, <?php echo esc_attr($askai_overlay_bottom); ?>)), url('<?php echo esc_url($hero_bg); ?>') center/cover;
    padding: 80px 0;
    color: white;
    text-align: center;
    border-bottom: 3px solid var(--primary-color);
    position: relative;
}

.askai-hero h1 {
    font-family: 'Outfit', sans-serif;
    font-size: 56px;
    font-weight: 800;
    margin: 0 0 16px 0;
    letter-spacing: -1px;
    text-transform: uppercase;
}

.askai-hero p {
    font-size: 20px;
    color: #CBD5E1;
    max-width: 800px;
    margin: 0 auto;
    font-weight: 500;
}

.hero-badge {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    background: rgba(255,255,255,0.1);
    padding: 6px 16px;
    border-radius: 0;
    margin-bottom: 24px;
    border: 1px solid rgba(255,255,255,0.2);
}

.status-dot {
    width: 8px;
    height: 8px;
    background: #22C55E;
    border-radius: 0;
    box-shadow: 0 0 10px #22C55E;
    animation: pulse 2s infinite;
}

@keyframes pulse {
    0% { opacity: 1; }
    50% { opacity: 0.5; }
    100% { opacity: 1; }
}

.askai-container {
    max-width: 1000px;
    margin: -40px auto 0;
    padding: 0 20px 60px;
    position: relative;
    z-index: 10;
}

.chat-main {
    background: white;
    border-radius: 0;
    box-shadow: 0 20px 40px -10px rgba(0,0,0,0.1);
    border: 2px solid var(--primary-color);
    overflow: hidden;
}

.agent-profile {
    padding: 24px 32px;
    background: #F8FAFC;
    border-bottom: 1px solid #E2E8F0;
    display: flex;
    align-items: center;
    justify-content: space-between;
}

.profile-left {
    display: flex;
    align-items: center;
    gap: 16px;
}

.agent-avatar {
    width: 48px;
    height: 48px;
    background: var(--primary-color);
    border-radius: 0;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 24px;
    color: white;
}

.agent-info h2 {
    font-family: 'Outfit', sans-serif;
    font-size: 18px;
    font-weight: 800;
    color: #0F172A;
    margin: 0;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.agent-status {
    font-size: 12px;
    color: #22C55E;
    font-weight: 700;
    display: flex;
    align-items: center;
    gap: 6px;
}

.save-chat-btn {
    background: white;
    color: #1e293b;
    border: 1px solid #E2E8F0;
    padding: 8px 16px;
    border-radius: 0;
    font-size: 13px;
    font-weight: 700;
    cursor: pointer;
    display: flex;
    align-items: center;
    gap: 8px;
    transition: all 0.2s;
}

.save-chat-btn:hover {
    border-color: var(--primary-color);
    color: var(--primary-color);
    box-shadow: 0 4px 12px rgba(0,128,128, 0.1);
}

.chat-container {
    padding: 0;
    min-height: 550px;
}

.chat-widget-wrapper {
    height: 100%;
    background: white;
}
</style>

<div class="askai-page">
    <section class="askai-hero">
        <div class="container">
            <div class="hero-badge">
                <div class="status-dot"></div>
                <span style="color: #cbd5e1; font-size: 12px; font-weight: 800; letter-spacing: 1px; text-transform: uppercase;">
                    <?php echo esc_html($hero_badge); ?>
                </span>
            </div>
            
            <h1><?php echo esc_html($hero_title); ?></h1>
            <p><?php echo esc_html($hero_subtitle); ?></p>
        </div>
    </section>

    <div class="askai-container">
        <main class="chat-main">
            <div class="agent-profile">
                <div class="profile-left">
                    <div class="agent-avatar">
                        <?php echo $agent_data['icon']; ?>
                    </div>
                    <div class="agent-info">
                        <h2>AI INFORMATION ASSISTANT</h2>
                        <div class="agent-status"><span style="width: 6px; height: 6px; background: #22C55E; border-radius: 0;"></span> ONLINE & READY</div>
                    </div>
                </div>
                
                <button type="button" class="save-chat-btn" id="save-chat-trigger">
                    <span>💾</span> SAVE CHAT
                </button>
            </div>

            <div class="chat-container">
                <div class="chat-widget-wrapper" style="display: flex; flex-direction: column; height: 100%;">
                    
                    <div class="chat-messages" id="vance-ai-chat-messages" style="flex: 1; padding: 24px 32px; overflow-y: auto; background: white; max-height: 550px;">
                        <div class="msg bot" style="margin-bottom: 18px; display: flex; gap: 10px; flex-direction: row;">
                            <div class="msg-avatar" style="width: 32px; height: 32px; background: var(--primary-color); border-radius: 0; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                                <svg viewBox="0 0 24 24" style="width: 18px; height: 18px; fill: none; stroke: white; stroke-width: 2;"><rect x="3" y="4" width="18" height="12" rx="2" /><line x1="8" y1="20" x2="16" y2="20" /><line x1="12" y1="16" x2="12" y2="20" /></svg>
                            </div>
                            <div class="msg-bubble" style="max-width: 82%; padding: 16px 20px; border-radius: 0; font-size: 15px; line-height: 1.65; white-space: pre-wrap; background: #F8FAFC; border: 1px solid #E2E8F0; color: #1E293B;">Welcome. I can help you explore our IBD content. What would you like to know?</div>
                        </div>
                    </div>

                    <div class="chat-input-bar" style="padding: 16px 32px; border-top: 1px solid #E2E8F0; display: flex; gap: 12px; background: white;">
                        <input type="text" id="vance-ai-chat-input" class="chat-input" placeholder="Ask a question..." style="flex: 1; padding: 14px 20px; border: 2px solid #E2E8F0; border-radius: 0; font-size: 15px; outline: none; transition: 0.2s;">
                        <button class="chat-send" id="vance-ai-chat-send" style="padding: 14px 28px; background: var(--primary-color); color: white; border: none; border-radius: 0; font-weight: 700; font-size: 15px; cursor: pointer; transition: 0.2s;">Send</button>
                    </div>
                    <div style="padding: 8px 32px 14px; font-size: 12px; color: #94a3b8; background: white; line-height: 1.5;">
                        General information only, not medical advice. In an emergency call 999 or NHS 111.
                    </div>

                </div>
            </div>
        </main>
        
        <style>
        .chat-input:focus { border-color: var(--primary-color) !important; }
        .chat-send:hover { background: #E54E00 !important; }
        .typing-indicator { display: flex; gap: 4px; padding: 5px 0; }
        .typing-dot { width: 6px; height: 6px; background: #94A3B8; border-radius: 0; animation: typing 1.4s infinite ease-in-out both; }
        .typing-dot:nth-child(1) { animation-delay: -0.32s; }
        .typing-dot:nth-child(2) { animation-delay: -0.16s; }
        @keyframes typing { 0%, 80%, 100% { transform: scale(0); } 40% { transform: scale(1); } }
        /* User vs Bot styling logic inline via JS to keep it simple */
        </style>

        <div style="text-align: left; margin-top: 32px; color: #64748b; font-size: 13px; max-width: 800px; margin-left: auto; margin-right: auto; padding: 20px; background: white; border-radius: 0; border: 1px solid #E2E8F0; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);">
            <p style="margin: 0; line-height: 1.65;"><strong>How to use this tool.</strong> Vance AI gives general information about gastrointestinal health, IBD and clinical nutrition. It is an automated assistant: it can be wrong or out of date, it does not know your personal medical history, and it does not provide a diagnosis, prescription or treatment plan. It is not a substitute for advice from your own healthcare team and must not be used for urgent or emergency needs. If you feel unwell or think you may have a medical emergency, call 999 or NHS 111 now. Please do not enter information that identifies you, conversations may be processed by a third-party AI provider and stored to improve the service. By using this tool you accept that it is for general information only.</p>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    var chatInput = document.getElementById('vance-ai-chat-input');
    var chatSend = document.getElementById('vance-ai-chat-send');
    var chatMessages = document.getElementById('vance-ai-chat-messages');
    var saveBtn = document.getElementById('save-chat-trigger');
    var messages = [];

    function appendMessage(role, text) {
        var mdText = text.replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>');
        var msgDiv = document.createElement('div');
        msgDiv.className = 'msg ' + (role === 'user' ? 'user' : 'bot');
        msgDiv.style.marginBottom = '18px';
        msgDiv.style.display = 'flex';
        msgDiv.style.gap = '10px';
        msgDiv.style.flexDirection = role === 'user' ? 'row-reverse' : 'row';

        var avatarHtml = role === 'user' ? '<svg viewBox="0 0 24 24" style="width: 18px; height: 18px; fill: none; stroke: white; stroke-width: 2;"><path d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0" /></svg>' : '<svg viewBox="0 0 24 24" style="width: 18px; height: 18px; fill: none; stroke: white; stroke-width: 2;"><rect x="3" y="4" width="18" height="12" rx="2" /><line x1="8" y1="20" x2="16" y2="20" /><line x1="12" y1="16" x2="12" y2="20" /></svg>';
        
        var avatarBg = role === 'user' ? '#0A1929' : 'var(--primary-color)';
        var bubbleStyles = role === 'user' ? 'background: #0A1929; color: white;' : 'background: #F8FAFC; border: 1px solid #E2E8F0; color: #1E293B;';

        msgDiv.innerHTML = '<div class="msg-avatar" style="width: 32px; height: 32px; background: '+avatarBg+'; border-radius: 0; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">' + avatarHtml + '</div><div class="msg-bubble" style="max-width: 82%; padding: 16px 20px; border-radius: 0; font-size: 16px; line-height: 1.65; white-space: pre-wrap; ' + bubbleStyles + '">' + mdText + '</div>';
        chatMessages.appendChild(msgDiv);
        chatMessages.scrollTop = chatMessages.scrollHeight;
        if (!text.includes('typing-indicator')) messages.push({role: role, content: text});
    }

    function sendMessage() {
        var text = chatInput.value.trim();
        if(!text) return;
        appendMessage('user', text);
        chatInput.value = '';
        
        var typingDiv = document.createElement('div');
        typingDiv.className = 'msg bot typing';
        typingDiv.id = 'vance-ai-typing';
        typingDiv.style.marginBottom = '18px';
        typingDiv.style.display = 'flex';
        typingDiv.style.gap = '10px';
        typingDiv.style.flexDirection = 'row';
        typingDiv.innerHTML = '<div class="msg-avatar" style="width: 32px; height: 32px; background: var(--primary-color); border-radius: 0; display: flex; align-items: center; justify-content: center; flex-shrink: 0;"><svg viewBox="0 0 24 24" style="width: 18px; height: 18px; fill: none; stroke: white; stroke-width: 2;"><rect x="3" y="4" width="18" height="12" rx="2" /></svg></div><div class="msg-bubble" style="background:transparent; border:none; padding:16px 20px;"><div class="typing-indicator"><div class="typing-dot"></div><div class="typing-dot"></div><div class="typing-dot"></div></div></div>';
        chatMessages.appendChild(typingDiv);
        chatMessages.scrollTop = chatMessages.scrollHeight;
        
        chatSend.disabled = true;
        chatSend.style.opacity = '0.7';
        
        fetch('<?php echo home_url("/wp-json/vance-health/v1/ai-chat"); ?>', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({ messages: messages })
        })
        .then(r => r.json())
        .then(data => {
            document.getElementById('vance-ai-typing').remove();
            chatSend.disabled = false;
            chatSend.style.opacity = '1';
            if (data.success && data.reply) appendMessage('model', data.reply);
            else appendMessage('model', "I'm having trouble connecting right now.");
        })
        .catch(err => {
            document.getElementById('vance-ai-typing').remove();
            chatSend.disabled = false;
            chatSend.style.opacity = '1';
            appendMessage('model', "Network error encountered. Please try again.");
        });
    }

    if(chatSend) chatSend.addEventListener('click', sendMessage);
    if(chatInput) chatInput.addEventListener('keypress', function(e) { if(e.key === 'Enter') sendMessage(); });

    if(saveBtn) {
        saveBtn.addEventListener('click', function() {
            if(messages.length === 0) return alert("No conversation yet.");
            
            var dateStr = new Date().toLocaleDateString('en-GB', { day: 'numeric', month: 'short', year: 'numeric' });
            var chatName = prompt("Enter a name for this chat:", "AI Chat - " + dateStr);
            if (chatName === null) return; // User cancelled
            if (chatName.trim() === '') chatName = "AI Chat - " + dateStr;
            
            var transcriptHtml = messages.map(m => '<p><strong>' + (m.role==='user'?'You':'AI') + ':</strong><br>' + m.content + '</p>').join('');
            saveBtn.innerHTML = '<span>⏳</span> SAVING...';
            
            fetch('<?php echo home_url("/wp-json/vance-health/v1/save-chat"); ?>', {
                method: 'POST',
                headers: {'Content-Type': 'application/json', 'X-WP-Nonce': '<?php echo wp_create_nonce("wp_rest"); ?>'},
                body: JSON.stringify({ transcript: messages, title: chatName })
            })
            .then(r => r.json())
            .then(d => {
                if(d.success) {
                    saveBtn.innerHTML = "<span>✅</span> SAVED!";
                    setTimeout(() => saveBtn.innerHTML = "<span>💾</span> SAVE CHAT", 3000);
                } else saveBtn.innerHTML = "<span>💾</span> SAVE CHAT";
            })
            .catch(() => saveBtn.innerHTML = "<span>💾</span> SAVE CHAT");
        });
    }
});
</script>

<?php get_footer(); ?>
