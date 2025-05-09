/**
 * JavaScript pour l'administration du module IaBot
 */
document.addEventListener('DOMContentLoaded', function() {
    // Gestion du bouton de réinitialisation des recommandations
    const resetRecommendationsBtn = document.getElementById('reset-recommendations-table');
    if (resetRecommendationsBtn) {
        resetRecommendationsBtn.addEventListener('click', function() {
            if (confirm('Êtes-vous sûr de vouloir réinitialiser la table des recommandations ? Cette action ne peut pas être annulée.')) {
                // Création d'un formulaire pour soumettre l'action
                const form = document.createElement('form');
                form.method = 'post';
                form.action = window.location.href;
                
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = 'submitResetRecommendations';
                input.value = '1';
                
                form.appendChild(input);
                document.body.appendChild(form);
                form.submit();
            }
        });
    }
    
    // Gestion du bouton d'indexation des produits
    const indexProductsBtn = document.getElementById('index-all-products');
    if (indexProductsBtn) {
        indexProductsBtn.addEventListener('click', function() {
            indexProducts(false);
        });
    }
    
    // Gestion du bouton de réindexation forcée des produits
    const forceReindexProductsBtn = document.getElementById('force-reindex-all-products');
    if (forceReindexProductsBtn) {
        forceReindexProductsBtn.addEventListener('click', function() {
            if (confirm('Êtes-vous sûr de vouloir forcer la réindexation de tous les produits ? Cette opération peut prendre du temps pour les catalogues volumineux.')) {
                indexProducts(true);
            }
        });
    }
    
    /**
     * Fonction d'indexation des produits via AJAX
     * 
     * @param {boolean} forceReindex Forcer la réindexation
     */
    function indexProducts(forceReindex) {
        // Affichage d'un message de chargement avec barre de progression
        showProgressBar('Indexation des produits en cours...', 0);
        
        // Utilisation de l'URL AJAX du front-office
        const ajaxUrl = ajaxFrontUrl;
        
        // Préparation des données
        const formData = new FormData();
        formData.append('action', 'indexProducts');
        formData.append('forceReindex', forceReindex ? '1' : '0');
        
        // Envoi de la requête AJAX
        fetch(ajaxUrl, {
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
            
            if (data.success) {
                // Mise à jour de la barre de progression à 100%
                updateProgressBar(100);
                
                // Affichage direct du message de succès
                showSuccessMessage(data.count + ' produits ont été indexés avec succès.');
                
                if (data.errors && data.errors.length > 0) {
                    showWarningMessage(data.errors.length + ' erreurs ont été rencontrées pendant l\'indexation.');
                    console.error('Erreurs d\'indexation:', data.errors);
                }
                
                // Rechargement de la page après 2 secondes pour afficher les messages de confirmation
                setTimeout(function() {
                    window.location.reload();
                }, 2000);
            } else {
                // Affichage d'une erreur
                showErrorMessage('Une erreur est survenue lors de l\'indexation des produits.');
                
                if (data.errors && data.errors.length > 0) {
                    data.errors.forEach(function(error) {
                        showErrorMessage(error);
                    });
                    console.error('Erreurs d\'indexation:', data.errors);
                }
            }
        })
        .catch(error => {
            hideProgressBar();
            showErrorMessage('Erreur: ' + error.message);
            console.error('Erreur AJAX:', error);
        });
        
        // Simulation de progression pour donner un feedback visuel
        simulateProgress();
    }
    
    /**
     * Simule la progression de l'indexation
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
     * Affiche un message d'avertissement
     * 
     * @param {string} message Message d'avertissement
     */
    function showWarningMessage(message) {
        const warningDiv = document.createElement('div');
        warningDiv.className = 'alert alert-warning';
        warningDiv.innerHTML = '<i class="icon-warning-sign"></i> ' + message;
        
        // Insertion au début du contenu
        const contentDiv = document.querySelector('.page-head, .bootstrap');
        if (contentDiv) {
            contentDiv.insertBefore(warningDiv, contentDiv.firstChild);
        } else {
            document.body.insertBefore(warningDiv, document.body.firstChild);
        }
        
        // Suppression automatique après 5 secondes
        setTimeout(function() {
            if (warningDiv.parentNode) {
                warningDiv.parentNode.removeChild(warningDiv);
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
});

/**
 * Gestion de l'indexation des produits
 */
$(document).ready(function() {
    // Chargement des statistiques d'indexation au chargement de la page
    if ($('#indexing-stats').length > 0) {
        loadIndexingStats();
    }
    
    // Gestion du clic sur le bouton d'indexation
    $('#start-indexing').on('click', function() {
        startProductIndexing();
    });
});

/**
 * Charge les statistiques d'indexation des produits
 */
function loadIndexingStats() {
    $.ajax({
        url: ajaxFrontUrl,
        type: 'POST',
        dataType: 'json',
        data: {
            action: 'get_indexing_stats'
        },
        success: function(response) {
            if (response.success) {
                displayIndexingStats(response.stats);
            } else {
                $('#indexing-stats').html(
                    '<div class="alert alert-danger">' +
                    '<p><strong>Erreur :</strong> ' + response.message + '</p>' +
                    '</div>'
                );
            }
        },
        error: function() {
            $('#indexing-stats').html(
                '<div class="alert alert-danger">' +
                '<p><strong>Erreur :</strong> Impossible de charger les statistiques d\'indexation.</p>' +
                '</div>'
            );
        }
    });
}

/**
 * Affiche les statistiques d'indexation dans l'interface
 */
function displayIndexingStats(stats) {
    var html = '<ul class="list-group">';
    
    // Nombre total de produits indexés
    html += '<li class="list-group-item">' +
            '<span class="badge">' + (stats.current_indexed || 0) + '</span>' +
            'Produits indexés' +
            '</li>';
    
    // Nombre total de produits actifs
    html += '<li class="list-group-item">' +
            '<span class="badge">' + (stats.total_active || 0) + '</span>' +
            'Produits actifs' +
            '</li>';
    
    // Taux d'indexation
    var indexRate = 0;
    if (stats.total_active > 0 && stats.current_indexed > 0) {
        indexRate = Math.round((stats.current_indexed / stats.total_active) * 100);
    }
    
    var rateClass = 'success';
    if (indexRate < 50) {
        rateClass = 'danger';
    } else if (indexRate < 80) {
        rateClass = 'warning';
    }
    
    html += '<li class="list-group-item">' +
            '<span class="badge badge-' + rateClass + '">' + indexRate + '%</span>' +
            'Taux d\'indexation' +
            '</li>';
    
    // Dernière indexation
    if (stats.last_indexing_time) {
        html += '<li class="list-group-item">' +
                '<span class="badge">' + stats.last_indexing_time + '</span>' +
                'Dernière indexation' +
                '</li>';
    }
    
    html += '</ul>';
    
    // Recommandations si disponibles
    if (stats.recommendations && stats.recommendations.length > 0) {
        html += '<div class="alert alert-info">';
        html += '<strong>Recommandations :</strong>';
        html += '<ul>';
        
        for (var i = 0; i < stats.recommendations.length; i++) {
            html += '<li>' + stats.recommendations[i] + '</li>';
        }
        
        html += '</ul>';
        html += '</div>';
    }
    
    $('#indexing-stats').html(html);
}

/**
 * Lance l'indexation des produits
 */
function startProductIndexing() {
    // Récupération de l'option de réindexation forcée
    var forceReindex = $('#force-reindex').is(':checked');
    
    // Affichage de la barre de progression
    $('#indexing-progress').show();
    updateProgressBar(0);
    
    // Désactivation du bouton pendant l'indexation
    $('#start-indexing').prop('disabled', true).html('<i class="icon-refresh icon-spin"></i> Indexation en cours...');
    
    // Masquage des résultats précédents
    $('#indexing-result').hide();
    
    // Appel AJAX pour lancer l'indexation
    $.ajax({
        url: ajaxFrontUrl,
        type: 'POST',
        dataType: 'json',
        data: {
            action: 'index_products',
            forceReindex: forceReindex
        },
        success: function(response) {
            // Mise à jour de la barre de progression
            updateProgressBar(100);
            
            // Réactivation du bouton
            $('#start-indexing').prop('disabled', false).html('<i class="icon-refresh"></i> Lancer l\'indexation');
            
            // Affichage du résultat
            if (response.success) {
                $('#indexing-result')
                    .removeClass('alert-danger')
                    .addClass('alert-success')
                    .html('<strong>Succès :</strong> ' + response.count + ' produits ont été indexés avec succès.')
                    .show();
            } else {
                $('#indexing-result')
                    .removeClass('alert-success')
                    .addClass('alert-danger')
                    .html('<strong>Erreur :</strong> ' + response.message)
                    .show();
            }
            
            // Rechargement des statistiques
            loadIndexingStats();
        },
        error: function() {
            // Mise à jour de la barre de progression
            updateProgressBar(100);
            
            // Réactivation du bouton
            $('#start-indexing').prop('disabled', false).html('<i class="icon-refresh"></i> Lancer l\'indexation');
            
            // Affichage du message d'erreur
            $('#indexing-result')
                .removeClass('alert-success')
                .addClass('alert-danger')
                .html('<strong>Erreur :</strong> Une erreur est survenue lors de l\'indexation des produits.')
                .show();
        }
    });
}

/**
 * Met à jour la barre de progression
 */
function updateProgressBar(percentage) {
    $('#indexing-progress .progress-bar').css('width', percentage + '%');
    $('#progress-text').text(percentage + '%');
}

/**
 * Affiche une alerte dans la page
 * 
 * @param {string} type Type d'alerte (success, info, warning, danger)
 * @param {string} message Message à afficher
 */
function showAlert(type, message) {
    var alertHtml = '<div class="alert alert-' + type + ' alert-dismissible fade show" role="alert">' +
                    message +
                    '<button type="button" class="close" data-dismiss="alert" aria-label="Close">' +
                    '<span aria-hidden="true">&times;</span>' +
                    '</button>' +
                    '</div>';
    
    $('#alerts-container').html(alertHtml);
    
    // Disparition automatique après 5 secondes
    setTimeout(function() {
        $('.alert').alert('close');
    }, 5000);
}

/**
 * Charge les statistiques d'indexation des produits
 */
function loadIndexingStats() {
    $.ajax({
        url: ajaxFrontUrl,
        type: 'POST',
        data: {
            ajax: 1,
            action: 'get_indexing_stats'
        },
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                displayIndexingStats(response.stats);
            } else {
                console.error('Erreur lors du chargement des statistiques d\'indexation:', response.message);
                $('#indexing-stats').html('<div class="alert alert-warning">Impossible de charger les statistiques d\'indexation: ' + response.message + '</div>');
            }
        },
        error: function(xhr, status, error) {
            console.error('Erreur AJAX lors du chargement des statistiques d\'indexation:', error);
            $('#indexing-stats').html('<div class="alert alert-danger">Erreur de communication avec le serveur</div>');
        }
    });
}

/**
 * Affiche les statistiques d'indexation des produits
 * 
 * @param {Object} stats Statistiques d'indexation
 */
function displayIndexingStats(stats) {
    // Calcul du pourcentage d'indexation
    var indexedPercent = 0;
    if (stats.total_active > 0) {
        indexedPercent = Math.round((stats.current_indexed / stats.total_active) * 100);
    }
    
    // Construction du HTML pour les statistiques
    var html = '<div class="row">';
    
    // Statistiques générales
    html += '<div class="col-md-6">';
    html += '<div class="card">';
    html += '<div class="card-header">Statistiques d\'indexation</div>';
    html += '<div class="card-body">';
    html += '<p><strong>Produits indexés:</strong> ' + stats.current_indexed + ' / ' + stats.total_active + ' (' + indexedPercent + '%)</p>';
    
    // Barre de progression
    html += '<div class="progress mb-3">';
    html += '<div class="progress-bar" role="progressbar" style="width: ' + indexedPercent + '%;" aria-valuenow="' + indexedPercent + '" aria-valuemin="0" aria-valuemax="100">' + indexedPercent + '%</div>';
    html += '</div>';
    
    // Dernière indexation
    if (stats.last_indexing_time) {
        html += '<p><strong>Dernière indexation:</strong> ' + stats.last_indexing_time + '</p>';
    } else {
        html += '<p><strong>Dernière indexation:</strong> Jamais</p>';
    }
    
    html += '</div>'; // Fin card-body
    html += '</div>'; // Fin card
    html += '</div>'; // Fin col
    
    // Statistiques détaillées des opérations
    html += '<div class="col-md-6">';
    html += '<div class="card">';
    html += '<div class="card-header">Performances d\'indexation</div>';
    html += '<div class="card-body">';
    
    if (stats.total_operations > 0) {
        html += '<p><strong>Opérations totales:</strong> ' + stats.total_operations + '</p>';
        html += '<p><strong>Opérations réussies:</strong> ' + stats.successful_operations + '</p>';
        html += '<p><strong>Opérations échouées:</strong> ' + stats.failed_operations + '</p>';
        html += '<p><strong>Temps moyen par produit:</strong> ' + stats.average_time_per_product + ' ms</p>';
    } else {
        html += '<p>Aucune opération d\'indexation n\'a encore été effectuée.</p>';
    }
    
    html += '</div>'; // Fin card-body
    html += '</div>'; // Fin card
    html += '</div>'; // Fin col
    
    html += '</div>'; // Fin row
    
    // Affichage des erreurs communes si disponibles
    if (stats.common_errors && stats.common_errors.length > 0) {
        html += '<div class="row mt-3">';
        html += '<div class="col-12">';
        html += '<div class="card">';
        html += '<div class="card-header">Erreurs fréquentes</div>';
        html += '<div class="card-body">';
        html += '<ul class="list-group">';
        
        stats.common_errors.forEach(function(error) {
            html += '<li class="list-group-item">';
            html += '<strong>' + error.message + '</strong> (' + error.count + ' occurrences)';
            html += '</li>';
        });
        
        html += '</ul>';
        html += '</div>'; // Fin card-body
        html += '</div>'; // Fin card
        html += '</div>'; // Fin col
        html += '</div>'; // Fin row
    }
    
    // Mise à jour du contenu
    $('#indexing-stats').html(html);
}

/**
 * Démarre l'indexation des produits
 */
function startProductIndexing() {
    // Récupération de l'option de réindexation forcée
    var forceReindex = $('#force-reindex').is(':checked');
    
    // Affichage de la barre de progression
    $('#indexing-progress-container').show();
    $('#indexing-progress-bar').css('width', '0%').attr('aria-valuenow', 0).text('0%');
    $('#indexing-result').html('');
    
    // Désactivation du bouton pendant l'indexation
    $('#start-indexing').prop('disabled', true).html('<i class="icon-refresh icon-spin"></i> Indexation en cours...');
    
    // Appel AJAX pour démarrer l'indexation
    $.ajax({
        url: ajaxFrontUrl,
        type: 'POST',
        data: {
            ajax: 1,
            action: 'index_products',
            force_reindex: forceReindex ? 1 : 0
        },
        dataType: 'json',
        success: function(response) {
            // Réactivation du bouton
            $('#start-indexing').prop('disabled', false).html('Démarrer l\'indexation');
            
            if (response.success) {
                // Mise à jour de la barre de progression
                $('#indexing-progress-bar').css('width', '100%').attr('aria-valuenow', 100).text('100%');
                
                // Affichage du résultat
                var resultHtml = '<div class="alert alert-success mt-3">';
                resultHtml += '<p><strong>' + response.count + '</strong> produits ont été indexés avec succès.</p>';
                
                if (response.time) {
                    resultHtml += '<p>Temps total: <strong>' + (response.time / 1000).toFixed(2) + '</strong> secondes.</p>';
                }
                
                resultHtml += '</div>';
                
                $('#indexing-result').html(resultHtml);
                
                // Rechargement des statistiques
                loadIndexingStats();
            } else {
                // Affichage de l'erreur
                $('#indexing-progress-bar').css('width', '100%').addClass('bg-danger').attr('aria-valuenow', 100).text('Erreur');
                
                var errorHtml = '<div class="alert alert-danger mt-3">';
                errorHtml += '<p><strong>Erreur:</strong> ' + response.message + '</p>';
                
                if (response.errors && response.errors.length > 0) {
                    errorHtml += '<ul>';
                    response.errors.forEach(function(error) {
                        errorHtml += '<li>' + error + '</li>';
                    });
                    errorHtml += '</ul>';
                }
                
                errorHtml += '</div>';
                
                $('#indexing-result').html(errorHtml);
            }
        },
        error: function(xhr, status, error) {
            // Réactivation du bouton
            $('#start-indexing').prop('disabled', false).html('Démarrer l\'indexation');
            
            // Affichage de l'erreur
            $('#indexing-progress-bar').css('width', '100%').addClass('bg-danger').attr('aria-valuenow', 100).text('Erreur');
            
            var errorHtml = '<div class="alert alert-danger mt-3">';
            errorHtml += '<p><strong>Erreur de communication avec le serveur:</strong> ' + error + '</p>';
            errorHtml += '</div>';
            
            $('#indexing-result').html(errorHtml);
            
            console.error('Erreur AJAX lors de l\'indexation des produits:', error);
        }
    });
}
