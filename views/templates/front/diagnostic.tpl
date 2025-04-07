{extends file='page.tpl'}

{block name='page_title'}
  Diagnostic IaBot
{/block}

{block name='page_content'}
  <div class="container">
    <div class="card">
      <div class="card-header bg-primary text-white">
        <h2>Rapport de diagnostic</h2>
      </div>
      <div class="card-body">
        <p>Un rapport de diagnostic a été généré pour vous aider à identifier les problèmes avec le module IaBot.</p>
        <p><strong>Fichier :</strong> {$diagnostic_file|escape:'html':'UTF-8'}</p>
        
        {if isset($action_result) && $action_result}
          <div class="alert alert-info">
            {$action_result|escape:'html':'UTF-8'|nl2br}
          </div>
        {/if}
        
        <div class="actions mt-3">
          <a href="{$diagnostic_url|escape:'html':'UTF-8'}" class="btn btn-primary" target="_blank">Voir le rapport</a>
          <a href="{$module_link|escape:'html':'UTF-8'}" class="btn btn-secondary">Retour à la configuration</a>
        </div>
      </div>
    </div>
    
    <div class="card mt-4">
      <div class="card-header bg-primary text-white">
        <h2>Tests de diagnostic</h2>
      </div>
      <div class="card-body">
        <form id="diagnostic-form">
          <div class="form-group row">
            <label for="test-type" class="col-sm-3 col-form-label">Type de test</label>
            <div class="col-sm-9">
              <select name="test-type" id="test-type" class="form-control">
                <option value="ajax">Test de requête AJAX</option>
                <option value="conversation">Test de création de conversation</option>
                <option value="message">Test d'envoi de message</option>
                <option value="api">Test de l'API OpenRouter</option>
                <option value="database">Test de la base de données</option>
                <option value="indexing">Test d'indexation des produits</option>
              </select>
            </div>
          </div>
          
          <div class="form-group row" id="message-group" style="display: none;">
            <label for="test-message" class="col-sm-3 col-form-label">Message de test</label>
            <div class="col-sm-9">
              <input type="text" class="form-control" id="test-message" name="test-message" placeholder="Entrez un message de test">
            </div>
          </div>
          
          <div class="form-group row" id="indexing-group" style="display: none;">
            <label for="force-reindex" class="col-sm-3 col-form-label">Options d'indexation</label>
            <div class="col-sm-9">
              <div class="form-check">
                <input class="form-check-input" type="checkbox" id="force-reindex" name="force-reindex">
                <label class="form-check-label" for="force-reindex">
                  Forcer la réindexation complète
                </label>
              </div>
              <small class="form-text text-muted">Cochez cette option pour réindexer tous les produits, même ceux déjà indexés.</small>
            </div>
          </div>
          
          <div class="form-group row">
            <div class="col-sm-9 offset-sm-3">
              <button type="button" id="run-test" class="btn btn-success">Exécuter le test</button>
            </div>
          </div>
        </form>
        
        <div id="test-result" style="display: none; margin-top: 20px;">
          <h3>Résultat du test</h3>
          <pre id="test-output" style="background: #f5f5f5; padding: 15px; border-radius: 3px; overflow: auto; max-height: 500px;"></pre>
        </div>
      </div>
    </div>
    
    <div class="card mt-4">
      <div class="card-header bg-primary text-white">
        <h2>Actions de maintenance</h2>
      </div>
      <div class="card-body">
        <div class="row">
          <div class="col-md-4 mb-3">
            <div class="card h-100">
              <div class="card-body">
                <h5 class="card-title">Gestion des logs</h5>
                <p class="card-text">Effacer tous les fichiers de log pour libérer de l'espace disque.</p>
                <a href="{$link->getModuleLink('iabot', 'diagnostic', ['action' => 'clear_logs'])|escape:'html':'UTF-8'}" class="btn btn-warning">Vider les logs</a>
              </div>
            </div>
          </div>
          
          <div class="col-md-4 mb-3">
            <div class="card h-100">
              <div class="card-body">
                <h5 class="card-title">Recommandations</h5>
                <p class="card-text">Réinitialiser la table des recommandations produits.</p>
                <a href="{$link->getModuleLink('iabot', 'diagnostic', ['action' => 'reset_recommendations'])|escape:'html':'UTF-8'}" class="btn btn-danger">Réinitialiser</a>
              </div>
            </div>
          </div>
          
          <div class="col-md-4 mb-3">
            <div class="card h-100">
              <div class="card-body">
                <h5 class="card-title">Base de données</h5>
                <p class="card-text">Vérifier l'état des tables du module.</p>
                <a href="{$link->getModuleLink('iabot', 'diagnostic', ['action' => 'check_tables'])|escape:'html':'UTF-8'}" class="btn btn-info">Vérifier les tables</a>
              </div>
            </div>
          </div>
        </div>
        
        <div class="row mt-3">
          <div class="col-md-12">
            <div class="card">
              <div class="card-body">
                <h5 class="card-title">Analyse avancée</h5>
                <p class="card-text">Générer un rapport d'analyse détaillé des logs et des performances.</p>
                <a href="{$link->getModuleLink('iabot', 'diagnostic', ['action' => 'analyze'])|escape:'html':'UTF-8'}" class="btn btn-primary">Générer un rapport d'analyse</a>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <script type="text/javascript">
    document.addEventListener('DOMContentLoaded', function() {
      // Afficher/masquer le champ de message en fonction du type de test
      document.getElementById('test-type').addEventListener('change', function() {
        var messageGroup = document.getElementById('message-group');
        var indexingGroup = document.getElementById('indexing-group');
        if (this.value === 'message' || this.value === 'api') {
          messageGroup.style.display = 'flex';
          indexingGroup.style.display = 'none';
        } else if (this.value === 'indexing') {
          messageGroup.style.display = 'none';
          indexingGroup.style.display = 'flex';
        } else {
          messageGroup.style.display = 'none';
          indexingGroup.style.display = 'none';
        }
      });
      
      // Exécuter le test
      document.getElementById('run-test').addEventListener('click', function() {
        var testType = document.getElementById('test-type').value;
        var testMessage = document.getElementById('test-message').value;
        var forceReindex = document.getElementById('force-reindex').checked;
        var resultDiv = document.getElementById('test-result');
        var outputPre = document.getElementById('test-output');
        
        resultDiv.style.display = 'block';
        outputPre.innerHTML = 'Exécution du test...';
        
        // Préparation des données pour la requête AJAX
        var formData = new FormData();
        formData.append('ajax', '1');
        formData.append('test_type', testType);
        
        if (testType === 'message' || testType === 'api') {
          formData.append('test_message', testMessage);
        } else if (testType === 'indexing') {
          formData.append('force_reindex', forceReindex);
        }
        
        // Envoi de la requête AJAX
        fetch(window.location.href, {
          method: 'POST',
          body: formData
        })
        .then(response => response.json())
        .then(data => {
          outputPre.innerHTML = JSON.stringify(data, null, 2);
        })
        .catch(error => {
          outputPre.innerHTML = 'Erreur : ' + error.message;
        });
      });
    });
  </script>
{/block}
