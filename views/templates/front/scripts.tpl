{*
* Scripts pour le module IaBot
*
* @author Développeur
* @copyright 2025
*}

<script type="text/javascript">
    // Configuration du chatbot
    var iaBotConfig = {
        chatPosition: '{$iabot_chat_position|escape:'javascript':'UTF-8'}',
        chatColor: '{$iabot_chat_color|escape:'javascript':'UTF-8'}',
        ajaxUrl: '{$iabot_ajax_url|escape:'javascript':'UTF-8'}',
        liveMode: {if $iabot_live_mode}true{else}false{/if},
        customerLogged: {if $iabot_customer_logged}true{else}false{/if},
        customerId: {$iabot_customer_id|intval}
    };
    
    // Initialisation du chatbot au chargement complet de la page
    document.addEventListener('DOMContentLoaded', function() {
        if (typeof initIaBot === 'function') {
            initIaBot(iaBotConfig);
        }
    });
</script>
