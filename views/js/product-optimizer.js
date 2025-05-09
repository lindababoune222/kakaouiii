/**
 * JavaScript pour l'optimisation des produits du module IaBot
 */
document.addEventListener('DOMContentLoaded', function() {
    // Gestion du bouton d'amélioration des produits
    const optimizeProductsBtn = document.getElementById('optimize-selected-products');
    if (optimizeProductsBtn) {
        optimizeProductsBtn.addEventListener('click', function(e) {
            e.preventDefault();
            
            // Récupération des produits sélectionnés
            const selectedProducts = getSelectedProducts();
            
            if (selectedProducts.length === 0) {
                showErrorMessage('Veuillez sélectionner au moins un produit à améliorer.');
                return;
            }
            
            // Changement de l'état du bouton
            optimizeProductsBtn.classList.add('btn-active');
            optimizeProductsBtn.disabled = true;
            optimizeProductsBtn.innerHTML = '<i class="icon-refresh icon-spin"></i> Amélioration en cours...';
            
            // Affichage de la barre de progression
            showProgressBar('Amélioration des descriptions de produits en cours...', 0);
            
            // Appel AJAX pour améliorer les produits
            optimizeProducts(selectedProducts);
        });
    }
    
    /**
     * Récupère les produits sélectionnés
     * 
     * @return {Array} Liste des IDs des produits sélectionnés
     */
    function getSelectedProducts() {
        const selectedProducts = [];
        const checkboxes = document.querySelectorAll('input[name="product_ids[]"]:checked');
        
        checkboxes.forEach(function(checkbox) {
            selectedProducts.push(parseInt(checkbox.value));
        });
        
        return selectedProducts;
    }
    
    /**
     * Lance l'optimisation des produits sélectionnés
     * 
     * @param {Array} productIds Liste des IDs des produits à optimiser
     */
    function optimizeProducts(productIds) {
        // Préparation des données
        const formData = new FormData();
        formData.append('action', 'optimize_products');
        formData.append('product_ids', JSON.stringify(productIds));
        
        // Envoi de la requête AJAX
        fetch(ajaxFrontUrl, {
            method: 'POST',
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: formData
        })
        .then(response => {
            if (!response.ok) {
                throw new Error('Erreur réseau: ' + response.status);
            }
            return response.json();
        })
        .then(data => {
            // Traitement de la réponse
            hideProgressBar();
            
            // Réinitialisation du bouton
            const optimizeProductsBtn = document.getElementById('optimize-selected-products');
            if (optimizeProductsBtn) {
                optimizeProductsBtn.classList.remove('btn-active');
                optimizeProductsBtn.disabled = false;
                optimizeProductsBtn.innerHTML = '<i class="icon-magic"></i> Améliorer les produits sélectionnés';
            }
            
            if (data.success) {
                // Mise à jour de la barre de progression à 100%
                updateProgressBar(100);
                
                // Affichage du message de succès
                showSuccessMessage(data.message || 'Les produits ont été améliorés avec succès.');
                
                // Affichage des détails des produits améliorés
                if (data.optimized_products && data.optimized_products.length > 0) {
                    showOptimizedProductsDetails(data.optimized_products);
                }
                
                // Rechargement de la page après 3 secondes
                setTimeout(function() {
                    window.location.reload();
                }, 3000);
            } else {
                // Affichage d'une erreur
                showErrorMessage(data.message || 'Une erreur est survenue lors de l\'amélioration des produits.');
            }
        })
        .catch(error => {
            hideProgressBar();
            
            // Réinitialisation du bouton
            const optimizeProductsBtn = document.getElementById('optimize-selected-products');
            if (optimizeProductsBtn) {
                optimizeProductsBtn.classList.remove('btn-active');
                optimizeProductsBtn.disabled = false;
                optimizeProductsBtn.innerHTML = '<i class="icon-magic"></i> Améliorer les produits sélectionnés';
            }
            
            showErrorMessage('Erreur: ' + error.message);
            console.error('Erreur AJAX:', error);
        });
        
        // Simulation de progression pour donner un feedback visuel
        simulateProgress();
    }
    
    /**
     * Simule la progression de l'optimisation
     */
    function simulateProgress() {
        let progress = 0;
        const interval = setInterval(function() {
            progress += Math.random() * 5;
            if (progress > 90) {
                progress = 90; // On reste à 90% max jusqu'à la fin réelle
            }
            updateProgressBar(Math.min(progress, 90));
        }, 500);
        
        // Stockage de l'intervalle pour pouvoir l'arrêter plus tard
        window.progressInterval = interval;
    }
    
    /**
     * Affiche une barre de progression
     * 
     * @param {string} message Message à afficher
     * @param {number} initialProgress Progression initiale (0-100)
     */
    function showProgressBar(message, initialProgress = 0) {
        // Suppression des barres existantes
        hideProgressBar();
        
        // Création de la barre de progression
        const progressContainer = document.createElement('div');
        progressContainer.id = 'iabot-progress-container';
        progressContainer.className = 'alert alert-info';
        progressContainer.innerHTML = `
            <div class="progress-message"><i class="icon-refresh icon-spin"></i> ${message}</div>
            <div class="progress" style="margin-top: 10px;">
                <div id="iabot-progress-bar" class="progress-bar progress-bar-info" role="progressbar" 
                     aria-valuenow="${initialProgress}" aria-valuemin="0" aria-valuemax="100" 
                     style="width: ${initialProgress}%;">
                    <span id="iabot-progress-text">${initialProgress}%</span>
                </div>
            </div>
        `;
        
        // Insertion au début du contenu
        const contentDiv = document.querySelector('.page-head, .bootstrap');
        if (contentDiv) {
            contentDiv.insertBefore(progressContainer, contentDiv.firstChild);
        } else {
            document.body.insertBefore(progressContainer, document.body.firstChild);
        }
    }
    
    /**
     * Met à jour la progression de la barre
     * 
     * @param {number} progress Progression (0-100)
     */
    function updateProgressBar(progress) {
        const progressBar = document.getElementById('iabot-progress-bar');
        const progressText = document.getElementById('iabot-progress-text');
        
        if (progressBar && progressText) {
            const roundedProgress = Math.round(progress);
            progressBar.style.width = roundedProgress + '%';
            progressBar.setAttribute('aria-valuenow', roundedProgress);
            progressText.textContent = roundedProgress + '%';
        }
    }
    
    /**
     * Masque la barre de progression
     */
    function hideProgressBar() {
        const progressContainer = document.getElementById('iabot-progress-container');
        if (progressContainer) {
            progressContainer.parentNode.removeChild(progressContainer);
        }
        
        // Arrêt de la simulation de progression
        if (window.progressInterval) {
            clearInterval(window.progressInterval);
            window.progressInterval = null;
        }
    }
    
    /**
     * Affiche un message de succès
     * 
     * @param {string} message Message de succès
     */
    function showSuccessMessage(message) {
        const successDiv = document.createElement('div');
        successDiv.className = 'alert alert-success';
        successDiv.innerHTML = '<i class="icon-check"></i> ' + message;
        
        // Insertion au début du contenu
        const contentDiv = document.querySelector('.page-head, .bootstrap');
        if (contentDiv) {
            contentDiv.insertBefore(successDiv, contentDiv.firstChild);
        } else {
            document.body.insertBefore(successDiv, document.body.firstChild);
        }
        
        // Suppression automatique après 5 secondes
        setTimeout(function() {
            if (successDiv.parentNode) {
                successDiv.parentNode.removeChild(successDiv);
            }
        }, 5000);
    }
    
    /**
     * Affiche un message d'erreur
     * 
     * @param {string} message Message d'erreur
     */
    function showErrorMessage(message) {
        const errorDiv = document.createElement('div');
        errorDiv.className = 'alert alert-danger';
        errorDiv.innerHTML = '<i class="icon-exclamation-sign"></i> ' + message;
        
        // Insertion au début du contenu
        const contentDiv = document.querySelector('.page-head, .bootstrap');
        if (contentDiv) {
            contentDiv.insertBefore(errorDiv, contentDiv.firstChild);
        } else {
            document.body.insertBefore(errorDiv, document.body.firstChild);
        }
        
        // Suppression automatique après 5 secondes
        setTimeout(function() {
            if (errorDiv.parentNode) {
                errorDiv.parentNode.removeChild(errorDiv);
            }
        }, 5000);
    }
    
    /**
     * Affiche les détails des produits optimisés
     * 
     * @param {Array} products Liste des produits optimisés
     */
    function showOptimizedProductsDetails(products) {
        const detailsDiv = document.createElement('div');
        detailsDiv.className = 'panel';
        
        let html = '<div class="panel-heading"><i class="icon-list"></i> Détails des produits améliorés</div>';
        html += '<div class="table-responsive">';
        html += '<table class="table">';
        html += '<thead><tr><th>ID</th><th>Nom</th><th>Améliorations</th></tr></thead>';
        html += '<tbody>';
        
        products.forEach(function(product) {
            html += '<tr>';
            html += '<td>' + product.id_product + '</td>';
            html += '<td>' + product.name + '</td>';
            html += '<td>' + product.improvements.join('<br>') + '</td>';
            html += '</tr>';
        });
        
        html += '</tbody></table></div>';
        detailsDiv.innerHTML = html;
        
        // Insertion après le premier panneau
        const firstPanel = document.querySelector('.panel');
        if (firstPanel && firstPanel.parentNode) {
            firstPanel.parentNode.insertBefore(detailsDiv, firstPanel.nextSibling);
        } else {
            const contentDiv = document.querySelector('.page-head, .bootstrap');
            if (contentDiv) {
                contentDiv.appendChild(detailsDiv);
            }
        }
    }
    
    // Gestion de la sélection de tous les produits
    const selectAllCheckbox = document.getElementById('select-all-products');
    if (selectAllCheckbox) {
        selectAllCheckbox.addEventListener('change', function() {
            const checkboxes = document.querySelectorAll('input[name="product_ids[]"]');
            checkboxes.forEach(function(checkbox) {
                checkbox.checked = selectAllCheckbox.checked;
            });
        });
    }
});