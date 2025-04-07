{**
 * Template pour le chat IA
 * Module: iabot
 * 
 * @author  Développeur
 * @copyright 2025
 *}

<div id="iabot-chat-container" class="iabot-position-{$iabot_chat_position}" style="--iabot-primary-color: {$iabot_chat_color}">
    <div id="iabot-chat-header">
        <div class="iabot-chat-title">
            <img src="{$module_dir|escape:'html':'UTF-8'}views/img/logo_chat.png" alt="{$iabot_shop_name}" width="32" height="32">
            <div class="iabot-title-text">
                <span class="iabot-main-title">{$iabot_chat_title}</span>
                <span class="iabot-subtitle">{$iabot_chat_subtitle}</span>
            </div>
        </div>
        <div class="iabot-chat-actions">
            <button id="iabot-minimize-btn" class="iabot-btn" aria-label="{l s='Minimiser' mod='iabot'}">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M19 13H5V11H19V13Z" fill="currentColor"/>
                </svg>
            </button>
        </div>
    </div>
    
    <div id="iabot-chat-body">
        <div id="iabot-messages-container">
            <div class="iabot-message iabot-bot-message">
                <div class="iabot-avatar">
                    <img src="{$module_dir|escape:'html':'UTF-8'}views/img/bot_avatar.png" alt="Bot" width="32" height="32">
                </div>
                <div class="iabot-message-bubble">
                    <div class="iabot-message-content">
                        {$iabot_welcome_message}
                    </div>
                    <div class="iabot-message-time">
                        {dateFormat date=date('Y-m-d H:i:s') full=0}
                    </div>
                </div>
            </div>
            <!-- Les messages seront ajoutés ici dynamiquement -->
        </div>
        
        <div id="iabot-product-recommendations" class="iabot-hidden">
            <div class="iabot-recommendations-title">{l s='Recommandations pour vous' mod='iabot'}</div>
            <div class="iabot-recommendations-carousel">
                <!-- Les produits recommandés seront ajoutés ici dynamiquement -->
            </div>
            <div class="iabot-carousel-controls">
                <button class="iabot-carousel-prev iabot-btn" aria-label="{l s='Précédent' mod='iabot'}">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M15.41 7.41L14 6L8 12L14 18L15.41 16.59L10.83 12L15.41 7.41Z" fill="currentColor"/>
                    </svg>
                </button>
                <button class="iabot-carousel-next iabot-btn" aria-label="{l s='Suivant' mod='iabot'}">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M10 6L8.59 7.41L13.17 12L8.59 16.59L10 18L16 12L10 6Z" fill="currentColor"/>
                    </svg>
                </button>
            </div>
        </div>
    </div>
    
    <div id="iabot-chat-footer">
        <form id="iabot-message-form">
            <div class="iabot-input-container">
                <input 
                    type="text" 
                    id="iabot-message-input" 
                    placeholder="{$iabot_chat_placeholder}" 
                    aria-label="{l s='Votre message' mod='iabot'}"
                    autocomplete="off"
                >
                <button type="submit" id="iabot-send-btn" class="iabot-btn" aria-label="{l s='Envoyer' mod='iabot'}">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M2.01 21L23 12L2.01 3L2 10L17 12L2 14L2.01 21Z" fill="currentColor"/>
                    </svg>
                </button>
            </div>
        </form>
    </div>
</div>

<div id="iabot-chat-bubble" class="iabot-position-{$iabot_chat_position}">
    <button id="iabot-open-chat-btn" aria-label="{l s='Ouvrir le chat' mod='iabot'}">
        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
            <path d="M20 2H4C2.9 2 2 2.9 2 4V22L6 18H20C21.1 18 22 17.1 22 16V4C22 2.9 21.1 2 20 2ZM20 16H6L4 18V4H20V16Z" fill="white"/>
            <path d="M7 9H17V11H7V9Z" fill="white"/>
            <path d="M7 12H14V14H7V12Z" fill="white"/>
            <path d="M7 6H17V8H7V6Z" fill="white"/>
        </svg>
        <span class="iabot-bubble-pulse"></span>
    </button>
</div>

{literal}
<script type="text/javascript">
    document.addEventListener('DOMContentLoaded', function() {
        // Configuration du chat
        const iabotConfig = {
            ajaxUrl: '{/literal}{$iabot_ajax_url|escape:'javascript':'UTF-8'}{literal}',
            customerId: {/literal}{$iabot_customer_id|intval}{literal},
            isCustomerLogged: {/literal}{$iabot_customer_logged|intval}{literal},
            liveMode: {/literal}{$iabot_live_mode|intval}{literal},
            moduleDir: '{/literal}{$module_dir|escape:'javascript':'UTF-8'}{literal}'
        };
        
        // Initialisation du chat
        if (typeof initIaBotChat === 'function') {
            initIaBotChat(iabotConfig);
        }
    });
</script>
{/literal}
