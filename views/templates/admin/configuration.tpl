{*
* Configuration du module IaBot
*
* @author Développeur
* @copyright 2025
*}

<div class="panel">
    <div class="panel-heading">
        <i class="icon-cogs"></i> {l s='Configuration du module IaBot' mod='iabot'}
    </div>
    
    <div class="alert alert-info">
        <p>{l s='Ce module utilise une API d\'intelligence artificielle pour accéder à différents modèles d\'IA, dont Meta Llama 3.3 70B par défaut.' mod='iabot'}</p>
        <p>{l s='Vous devez configurer une clé API pour utiliser cette fonctionnalité.' mod='iabot'}</p>
    </div>
    
    {if isset($confirmations) && $confirmations|@count > 0}
        <div class="alert alert-success">
            {foreach $confirmations as $confirmation}
                <p>{$confirmation}</p>
            {/foreach}
        </div>
    {/if}
    
    {if isset($errors) && $errors|@count > 0}
        <div class="alert alert-danger">
            {foreach $errors as $error}
                <p>{$error}</p>
            {/foreach}
        </div>
    {/if}
    
    <form method="post" class="form-horizontal" action="{$post_uri|escape:'htmlall':'UTF-8'}">
        <div class="form-group">
            <label class="control-label col-lg-3">{l s='Mode Live' mod='iabot'}</label>
            <div class="col-lg-9">
                <span class="switch prestashop-switch fixed-width-lg">
                    <input type="radio" name="IABOT_LIVE_MODE" id="IABOT_LIVE_MODE_on" value="1" {if $current_values.IABOT_LIVE_MODE}checked="checked"{/if}>
                    <label for="IABOT_LIVE_MODE_on">{l s='Oui' mod='iabot'}</label>
                    <input type="radio" name="IABOT_LIVE_MODE" id="IABOT_LIVE_MODE_off" value="0" {if !$current_values.IABOT_LIVE_MODE}checked="checked"{/if}>
                    <label for="IABOT_LIVE_MODE_off">{l s='Non' mod='iabot'}</label>
                    <a class="slide-button btn"></a>
                </span>
                <p class="help-block">{l s='Activer le mode live pour utiliser le chatbot en production' mod='iabot'}</p>
            </div>
        </div>
        
        <div class="form-group">
            <label class="control-label col-lg-3">{l s='Clé API' mod='iabot'}</label>
            <div class="col-lg-9">
                <input type="text" name="IABOT_API_KEY" value="{$current_values.IABOT_API_KEY|escape:'htmlall':'UTF-8'}" class="form-control" />
                <p class="help-block">{l s='Votre clé API pour accéder aux modèles d\'IA' mod='iabot'}</p>
            </div>
        </div>
        
        <div class="form-group">
            <label class="control-label col-lg-3">{l s='Modèle d\'IA' mod='iabot'}</label>
            <div class="col-lg-9">
                <select name="IABOT_AI_MODEL" class="form-control">
                    {foreach from=$ai_models key=model_id item=model_name}
                        <option value="{$model_id|escape:'htmlall':'UTF-8'}" {if $current_values.IABOT_AI_MODEL == $model_id}selected="selected"{/if}>{$model_name|escape:'htmlall':'UTF-8'}</option>
                    {/foreach}
                </select>
                <p class="help-block">{l s='Sélectionnez le modèle d\'IA à utiliser pour générer les réponses' mod='iabot'}</p>
            </div>
        </div>
        
        <div class="form-group">
            <label class="control-label col-lg-3">{l s='Température' mod='iabot'}</label>
            <div class="col-lg-9">
                <div class="input-group">
                    <input type="range" name="IABOT_AI_TEMPERATURE" min="0" max="1" step="0.1" value="{$current_values.IABOT_AI_TEMPERATURE|escape:'htmlall':'UTF-8'}" class="form-control" oninput="document.getElementById('temp_value').textContent = this.value" />
                    <span class="input-group-addon" id="temp_value">{$current_values.IABOT_AI_TEMPERATURE|escape:'htmlall':'UTF-8'}</span>
                </div>
                <p class="help-block">{l s='Contrôle la créativité des réponses (0 = très précis, 1 = très créatif)' mod='iabot'}</p>
            </div>
        </div>
        
        <div class="form-group">
            <label class="control-label col-lg-3">{l s='Couleur du chat' mod='iabot'}</label>
            <div class="col-lg-9">
                <input type="text" name="IABOT_CHAT_COLOR" value="{$current_values.IABOT_CHAT_COLOR|escape:'htmlall':'UTF-8'}" class="form-control" />
                <p class="help-block">{l s='Format RGB : 0, 123, 255 (bleu par défaut)' mod='iabot'}</p>
            </div>
        </div>
        
        <div class="form-group">
            <label class="control-label col-lg-3">{l s='Position du chat' mod='iabot'}</label>
            <div class="col-lg-9">
                <select name="IABOT_CHAT_POSITION" class="form-control">
                    <option value="bottom-right" {if $current_values.IABOT_CHAT_POSITION == 'bottom-right'}selected="selected"{/if}>{l s='En bas à droite' mod='iabot'}</option>
                    <option value="bottom-left" {if $current_values.IABOT_CHAT_POSITION == 'bottom-left'}selected="selected"{/if}>{l s='En bas à gauche' mod='iabot'}</option>
                    <option value="top-right" {if $current_values.IABOT_CHAT_POSITION == 'top-right'}selected="selected"{/if}>{l s='En haut à droite' mod='iabot'}</option>
                    <option value="top-left" {if $current_values.IABOT_CHAT_POSITION == 'top-left'}selected="selected"{/if}>{l s='En haut à gauche' mod='iabot'}</option>
                </select>
                <p class="help-block">{l s='Position de l\'icône du chat sur la page' mod='iabot'}</p>
            </div>
        </div>
        
        <div class="form-group">
            <label class="control-label col-lg-3">{l s='Message de bienvenue' mod='iabot'}</label>
            <div class="col-lg-9">
                <textarea name="IABOT_WELCOME_MESSAGE" rows="3" class="form-control">{$current_values.IABOT_WELCOME_MESSAGE|escape:'htmlall':'UTF-8'}</textarea>
                <p class="help-block">{l s='Message affiché au début de chaque conversation' mod='iabot'}</p>
            </div>
        </div>
        
        <div class="form-group">
            <label class="control-label col-lg-3">{l s='Texte du champ de saisie' mod='iabot'}</label>
            <div class="col-lg-9">
                <input type="text" name="IABOT_PROMPT_PLACEHOLDER" value="{$current_values.IABOT_PROMPT_PLACEHOLDER|escape:'htmlall':'UTF-8'}" class="form-control" />
                <p class="help-block">{l s='Texte affiché dans le champ de saisie du chat' mod='iabot'}</p>
            </div>
        </div>
        
        <div class="panel-footer">
            <button type="submit" name="submitIaBotConfig" class="btn btn-default pull-right">
                <i class="process-icon-save"></i> {l s='Enregistrer' mod='iabot'}
            </button>
        </div>
    </form>
</div>

<div class="panel">
    <div class="panel-heading">
        <i class="icon-wrench"></i> {l s='Outils de maintenance' mod='iabot'}
    </div>
    <div class="panel-body">
        <div class="row">
            <div class="col-md-6">
                <div class="form-group">
                    <label class="control-label">{l s='Réinitialiser la table des recommandations' mod='iabot'}</label>
                    <div class="help-block">
                        {l s='Cette action supprimera toutes les recommandations existantes et recréera la structure de la table.' mod='iabot'}
                    </div>
                    <div class="mt-2">
                        <button type="button" class="btn btn-danger" id="reset-recommendations-table">
                            <i class="icon-refresh"></i> {l s='Réinitialiser' mod='iabot'}
                        </button>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="form-group">
                    <label class="control-label">{l s='Indexer tous les produits' mod='iabot'}</label>
                    <div class="help-block">
                        {l s='Cette action va indexer tous les produits de votre catalogue pour que le chatbot puisse les recommander.' mod='iabot'}
                    </div>
                    <div class="mt-2">
                        <button type="button" class="btn btn-primary" id="index-all-products">
                            <i class="icon-database"></i> {l s='Indexer les produits' mod='iabot'}
                        </button>
                        <button type="button" class="btn btn-warning" id="force-reindex-all-products">
                            <i class="icon-refresh"></i> {l s='Forcer la réindexation' mod='iabot'}
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

{if $is_api_configured}
<div class="panel">
    <div class="panel-heading">
        <i class="icon-info-circle"></i> {l s='Test de l\'API' mod='iabot'}
    </div>
    
    <form method="post" class="form-horizontal" action="{$post_uri|escape:'htmlall':'UTF-8'}">
        <div class="form-group">
            <label class="control-label col-lg-3">{l s='Message de test' mod='iabot'}</label>
            <div class="col-lg-9">
                <input type="text" name="test_message" value="{l s='Présente-toi en quelques mots' mod='iabot'}" class="form-control" />
            </div>
        </div>
        
        <div class="panel-footer">
            <button type="submit" name="submitIaBotApiTest" class="btn btn-default pull-right">
                <i class="process-icon-refresh"></i> {l s='Tester l\'API' mod='iabot'}
            </button>
        </div>
    </form>
</div>
{/if}

{block name="after_js"}
<script type="text/javascript">
    // Variables pour les requêtes AJAX
    var baseAdminDir = '{$smarty.const._PS_ADMIN_DIR_|addslashes}';
    var configToken = '{$token|escape:'javascript':'UTF-8'}';
</script>
{/block}
