/**
 * JavaScript pour le chat IA côté front-end
 */

// Variables globales
let iabotConfig = {};
let iabotConversationId = null;
let iabotTypingTimeout = null;
let iabotLastMessageTime = 0;

/**
 * Initialisation du chat
 * @param {Object} config Configuration du chat
 */
function initIaBotChat(config) {
    iabotConfig = config;
    
    // Éléments DOM
    const chatContainer = document.getElementById('iabot-chat-container');
    const chatBubble = document.getElementById('iabot-open-chat-btn');
    const minimizeBtn = document.getElementById('iabot-minimize-btn');
    const messageForm = document.getElementById('iabot-message-form');
    const messageInput = document.getElementById('iabot-message-input');
    
    // Événements
    chatBubble.addEventListener('click', toggleChat);
    minimizeBtn.addEventListener('click', toggleChat);
    messageForm.addEventListener('submit', sendMessage);
    
    // Initialiser la session
    initConversation();
    
    // Fermer le chat si on clique en dehors
    document.addEventListener('click', function(event) {
        if (chatContainer.classList.contains('iabot-active') && 
            !chatContainer.contains(event.target) && 
            !chatBubble.contains(event.target)) {
            toggleChat();
        }
    });
    
    // Scroll vers le bas à l'ouverture du chat
    const messagesContainer = document.getElementById('iabot-messages-container');
    messagesContainer.scrollTop = messagesContainer.scrollHeight;
}

/**
 * Bascule l'affichage du chat
 */
function toggleChat() {
    const chatContainer = document.getElementById('iabot-chat-container');
    chatContainer.classList.toggle('iabot-active');
    
    if (chatContainer.classList.contains('iabot-active')) {
        // Scroll vers le bas quand on ouvre le chat
        const messagesContainer = document.getElementById('iabot-messages-container');
        messagesContainer.scrollTop = messagesContainer.scrollHeight;
        
        // Focus sur l'input
        setTimeout(() => {
            document.getElementById('iabot-message-input').focus();
        }, 300);
    }
}

/**
 * Initialise une nouvelle conversation
 */
function initConversation() {
    // Si le mode live est désactivé, on n'initialise pas de conversation
    if (!iabotConfig.liveMode) {
        return;
    }
    
    // Récupérer un ID de conversation existant dans le localStorage
    iabotConversationId = localStorage.getItem('iabotConversationId');
    
    // Si pas d'ID, créer une nouvelle conversation
    if (!iabotConversationId) {
        fetch(iabotConfig.ajaxUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: JSON.stringify({
                action: 'initConversation',
                customerId: iabotConfig.customerId || 0,
                isCustomerLogged: iabotConfig.isCustomerLogged || false
            })
        })
        .then(response => {
            // Vérifier si la réponse est OK avant de tenter de parser le JSON
            if (!response.ok) {
                throw new Error('Erreur réseau: ' + response.status);
            }
            
            // Essayer de parser le JSON avec gestion d'erreur
            return response.text().then(text => {
                try {
                    return JSON.parse(text);
                } catch (e) {
                    console.error('Erreur de parsing JSON:', e, 'Réponse brute:', text);
                    // Générer un ID temporaire en cas d'erreur
                    return { success: false, conversationId: 'temp_' + Date.now() };
                }
            });
        })
        .then(data => {
            if (data && data.success && data.conversationId) {
                iabotConversationId = data.conversationId;
                localStorage.setItem('iabotConversationId', iabotConversationId);
            } else if (data && data.conversationId) {
                // Utiliser l'ID temporaire en cas d'échec
                iabotConversationId = data.conversationId;
                localStorage.setItem('iabotConversationId', iabotConversationId);
                console.warn('Utilisation d\'un ID de conversation temporaire');
            }
        })
        .catch(error => {
            console.error('Erreur lors de l\'initialisation de la conversation:', error);
            // Générer un ID temporaire en cas d'erreur
            iabotConversationId = 'temp_' + Date.now();
            localStorage.setItem('iabotConversationId', iabotConversationId);
        });
    }
}

/**
 * Envoie un message au serveur
 * @param {Event} event L'événement de soumission du formulaire
 */
function sendMessage(event) {
    event.preventDefault();
    
    // Si le mode live est désactivé, simuler une réponse
    if (!iabotConfig.liveMode) {
        handleOfflineMode();
        return;
    }
    
    const messageInput = document.getElementById('iabot-message-input');
    const message = messageInput.value.trim();
    
    if (!message) {
        return;
    }
    
    // Empêcher le spam de messages
    const now = Date.now();
    if (now - iabotLastMessageTime < 1000) {
        return;
    }
    iabotLastMessageTime = now;
    
    // Ajouter le message de l'utilisateur à l'interface
    addUserMessage(message);
    
    // Réinitialiser l'input
    messageInput.value = '';
    
    // Afficher l'indicateur de frappe
    showTypingIndicator();
    
    // Envoyer le message au serveur
    fetch(iabotConfig.ajaxUrl, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: JSON.stringify({
            action: 'sendMessage',
            conversationId: iabotConversationId || 'temp_' + Date.now(),
            message: message
        })
    })
    .then(response => {
        // Vérifier si la réponse est OK avant de tenter de parser le JSON
        if (!response.ok) {
            throw new Error('Erreur réseau: ' + response.status);
        }
        
        // Essayer de parser le JSON avec gestion d'erreur
        return response.text().then(text => {
            try {
                return JSON.parse(text);
            } catch (e) {
                console.error('Erreur de parsing JSON:', e, 'Réponse brute:', text);
                throw new Error('Erreur de format de réponse');
            }
        });
    })
    .then(data => {
        // Cacher l'indicateur de frappe
        hideTypingIndicator();
        
        if (data && data.success) {
            // Ajouter la réponse du bot
            addBotMessage(data.reply || "Je n'ai pas de réponse spécifique à cette question.");
            
            // Afficher les recommandations de produits si présentes
            if (data.recommendations && data.recommendations.length > 0) {
                showProductRecommendations(data.recommendations);
            }
        } else {
            addBotMessage("Désolé, une erreur s'est produite. Veuillez réessayer plus tard.");
        }
    })
    .catch(error => {
        hideTypingIndicator();
        console.error('Erreur lors de l\'envoi du message:', error);
        addBotMessage("Désolé, une erreur s'est produite. Veuillez réessayer plus tard.");
    });
}

/**
 * Affiche l'indicateur de frappe
 */
function showTypingIndicator() {
    const messagesContainer = document.getElementById('iabot-messages-container');
    
    // Créer l'indicateur s'il n'existe pas déjà
    if (!document.querySelector('.iabot-typing')) {
        const typingElement = document.createElement('div');
        typingElement.className = 'iabot-message iabot-bot-message';
        typingElement.innerHTML = `
            <div class="iabot-avatar">
                <img src="${iabotConfig.moduleDir}/views/img/bot_avatar.png" alt="Bot" width="32" height="32">
            </div>
            <div class="iabot-message-bubble">
                <div class="iabot-typing">
                    <div class="iabot-typing-dot"></div>
                    <div class="iabot-typing-dot"></div>
                    <div class="iabot-typing-dot"></div>
                </div>
            </div>
        `;
        messagesContainer.appendChild(typingElement);
        
        // Scroll vers le bas
        messagesContainer.scrollTop = messagesContainer.scrollHeight;
    }
    
    // Définir un timeout pour masquer l'indicateur si le serveur ne répond pas
    iabotTypingTimeout = setTimeout(() => {
        hideTypingIndicator();
        addBotMessage("Désolé, je n'ai pas pu obtenir de réponse. Veuillez réessayer plus tard.");
    }, 15000); // 15 secondes
}

/**
 * Masque l'indicateur de frappe
 */
function hideTypingIndicator() {
    const typingElement = document.querySelector('.iabot-message.iabot-bot-message:last-child .iabot-typing');
    if (typingElement) {
        typingElement.closest('.iabot-message').remove();
    }
    
    if (iabotTypingTimeout) {
        clearTimeout(iabotTypingTimeout);
        iabotTypingTimeout = null;
    }
}

/**
 * Ajoute un message de l'utilisateur à l'interface
 * @param {string} message Le message à ajouter
 */
function addUserMessage(message) {
    const messagesContainer = document.getElementById('iabot-messages-container');
    
    const messageElement = document.createElement('div');
    messageElement.className = 'iabot-message iabot-user-message';
    
    const now = new Date();
    const formattedTime = now.getHours().toString().padStart(2, '0') + ':' + 
                          now.getMinutes().toString().padStart(2, '0');
    
    messageElement.innerHTML = `
        <div class="iabot-avatar">
            <img src="${iabotConfig.moduleDir}/views/img/user_avatar.png" alt="User" width="32" height="32">
        </div>
        <div class="iabot-message-bubble">
            <div class="iabot-message-content">${escapeHtml(message)}</div>
            <div class="iabot-message-time">${formattedTime}</div>
        </div>
    `;
    
    messagesContainer.appendChild(messageElement);
    
    // Scroll vers le bas
    messagesContainer.scrollTop = messagesContainer.scrollHeight;
}

/**
 * Ajoute un message du bot à l'interface
 * @param {string} message Le message à ajouter
 */
function addBotMessage(message) {
    const messagesContainer = document.getElementById('iabot-messages-container');
    
    const messageElement = document.createElement('div');
    messageElement.className = 'iabot-message iabot-bot-message';
    
    const now = new Date();
    const formattedTime = now.getHours().toString().padStart(2, '0') + ':' + 
                          now.getMinutes().toString().padStart(2, '0');
    
    messageElement.innerHTML = `
        <div class="iabot-avatar">
            <img src="${iabotConfig.moduleDir}/views/img/bot_avatar.png" alt="Bot" width="32" height="32">
        </div>
        <div class="iabot-message-bubble">
            <div class="iabot-message-content">${message}</div>
            <div class="iabot-message-time">${formattedTime}</div>
        </div>
    `;
    
    messagesContainer.appendChild(messageElement);
    
    // Scroll vers le bas
    messagesContainer.scrollTop = messagesContainer.scrollHeight;
}

/**
 * Affiche les recommandations de produits
 * @param {Array} products Les produits à afficher
 */
function showProductRecommendations(products) {
    const container = document.getElementById('iabot-product-recommendations');
    const carousel = container.querySelector('.iabot-recommendations-carousel');
    
    // Vider le carrousel
    carousel.innerHTML = '';
    
    // Ajouter les produits
    products.forEach(product => {
        const card = document.createElement('div');
        card.className = 'iabot-product-card';
        
        let priceHtml = '';
        if (product.hasDiscount) {
            priceHtml = `
                <span class="iabot-product-old-price">${product.regularPrice}</span>
                <span class="iabot-product-price">${product.price}</span>
            `;
        } else {
            priceHtml = `<span class="iabot-product-price">${product.price}</span>`;
        }
        
        card.innerHTML = `
            <a href="${product.url}" title="${escapeHtml(product.name)}">
                <img src="${product.imageUrl}" alt="${escapeHtml(product.name)}" class="iabot-product-image">
                <div class="iabot-product-info">
                    <div class="iabot-product-title">${escapeHtml(product.name)}</div>
                    <div class="iabot-product-price-container">
                        ${priceHtml}
                    </div>
                </div>
            </a>
        `;
        
        carousel.appendChild(card);
    });
    
    // Afficher le conteneur
    container.classList.remove('iabot-hidden');
    
    // Scroll vers le bas
    const messagesContainer = document.getElementById('iabot-messages-container');
    messagesContainer.scrollTop = messagesContainer.scrollHeight;
}

/**
 * Fonction pour échapper les caractères HTML
 * @param {string} unsafe Chaîne de caractères à échapper
 * @return {string} Chaîne de caractères échappée
 */
function escapeHtml(unsafe) {
    return unsafe
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
}

/**
 * Gère le mode hors-ligne (démo)
 */
function handleOfflineMode() {
    const messageInput = document.getElementById('iabot-message-input');
    const message = messageInput.value.trim();
    
    if (!message) {
        return;
    }
    
    // Ajouter le message de l'utilisateur
    addUserMessage(message);
    
    // Réinitialiser l'input
    messageInput.value = '';
    
    // Afficher l'indicateur de frappe
    showTypingIndicator();
    
    // Simuler un délai de réponse
    setTimeout(() => {
        hideTypingIndicator();
        
        // Réponses de démonstration
        const demoResponses = [
            "Je suis un assistant virtuel en mode démo. En mode réel, je pourrai répondre avec précision à vos questions et vous recommander des produits adaptés à vos besoins.",
            "Bonjour ! Dans la version complète, je pourrai vous aider à choisir les produits idéaux selon vos préférences et vos besoins spécifiques.",
            "En tant qu'assistant de cette boutique, je pourrai vous guider vers les meilleurs produits selon vos besoins lorsque le module sera activé.",
            "Démo : Je pourrai vous expliquer les caractéristiques des produits et vous aider à faire le meilleur choix en fonction de vos critères.",
            "Lorsque je serai pleinement opérationnel, je pourrai vous faire des recommandations personnalisées basées sur vos préférences et besoins."
        ];
        
        // Choisir une réponse aléatoire
        const randomResponse = demoResponses[Math.floor(Math.random() * demoResponses.length)];
        addBotMessage(randomResponse);
        
        // Afficher des produits de démonstration occasionnellement
        if (Math.random() > 0.5) {
            const demoProducts = [
                {
                    name: "Produit Débutant Premium",
                    price: "699,00 €",
                    regularPrice: "799,00 €",
                    hasDiscount: true,
                    imageUrl: iabotConfig.moduleDir + "/views/img/product_demo1.jpg",
                    url: "#demo-product-1"
                },
                {
                    name: "Accessoire Essentiel",
                    price: "349,00 €",
                    regularPrice: "",
                    hasDiscount: false,
                    imageUrl: iabotConfig.moduleDir + "/views/img/product_demo2.jpg",
                    url: "#demo-product-2"
                },
                {
                    name: "Pack Complet Intermédiaire",
                    price: "1099,00 €",
                    regularPrice: "1299,00 €",
                    hasDiscount: true,
                    imageUrl: iabotConfig.moduleDir + "/views/img/product_demo3.jpg",
                    url: "#demo-product-3"
                }
            ];
            
            showProductRecommendations(demoProducts);
        }
    }, 1500); // Délai de 1,5 secondes
}

// Exposer la fonction d'initialisation globalement
window.initIaBotChat = initIaBotChat;
