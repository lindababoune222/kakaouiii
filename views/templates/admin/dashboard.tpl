{*
* Tableau de bord du module IaBot
*
* @author Développeur
* @copyright 2025
*}

<div class="panel">
    <div class="panel-heading">
        <i class="icon-dashboard"></i> {l s='Tableau de bord IaBot' mod='iabot'}
        <span class="badge badge-{if $iabot_live_mode}success{else}warning{/if} pull-right">
            {if $iabot_live_mode}
                {l s='Mode Live Activé' mod='iabot'}
            {else}
                {l s='Mode Test Activé' mod='iabot'}
            {/if}
        </span>
    </div>
    
    <div class="row dashboard-stats">
        <div class="col-md-3">
            <div class="panel widget">
                <div class="widget-heading">
                    <span>{l s='Conversations totales' mod='iabot'}</span>
                </div>
                <div class="widget-body">
                    <h3 class="text-center">{$total_conversations|intval}</h3>
                </div>
            </div>
        </div>
        
        <div class="col-md-3">
            <div class="panel widget">
                <div class="widget-heading">
                    <span>{l s='Messages totaux' mod='iabot'}</span>
                </div>
                <div class="widget-body">
                    <h3 class="text-center">{$total_messages|intval}</h3>
                </div>
            </div>
        </div>
        
        <div class="col-md-3">
            <div class="panel widget">
                <div class="widget-heading">
                    <span>{l s='Conversations actives (24h)' mod='iabot'}</span>
                </div>
                <div class="widget-body">
                    <h3 class="text-center">{$active_conversations|intval}</h3>
                </div>
            </div>
        </div>
        
        <div class="col-md-3">
            <div class="panel widget">
                <div class="widget-heading">
                    <span>{l s='Messages par conversation' mod='iabot'}</span>
                </div>
                <div class="widget-body">
                    <h3 class="text-center">{$average_messages}</h3>
                </div>
            </div>
        </div>
    </div>
    
    <div class="row">
        <div class="col-md-12">
            <div class="panel">
                <div class="panel-heading">
                    <i class="icon-bar-chart"></i> {l s='Activité des 7 derniers jours' mod='iabot'}
                </div>
                <div class="panel-body">
                    <canvas id="iabot-activity-chart" width="100%" height="40"></canvas>
                </div>
            </div>
        </div>
    </div>
    
    <div class="row">
        <div class="col-md-12">
            <div class="panel">
                <div class="panel-heading">
                    <i class="icon-comments"></i> {l s='Conversations récentes' mod='iabot'}
                </div>
                <div class="panel-body">
                    {if empty($recent_conversations)}
                        <p class="text-center text-muted">{l s='Aucune conversation récente' mod='iabot'}</p>
                    {else}
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>{l s='ID' mod='iabot'}</th>
                                        <th>{l s='Client/Visiteur' mod='iabot'}</th>
                                        <th>{l s='Messages' mod='iabot'}</th>
                                        <th>{l s='Dernier message' mod='iabot'}</th>
                                        <th>{l s='Date de mise à jour' mod='iabot'}</th>
                                        <th>{l s='Actions' mod='iabot'}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {foreach from=$recent_conversations item=conversation}
                                        <tr>
                                            <td>{$conversation.id_conversation|intval}</td>
                                            <td>
                                                {$conversation.customer_name|escape:'html':'UTF-8'}
                                                {if !empty($conversation.customer_email)}
                                                    <br><small>{$conversation.customer_email|escape:'html':'UTF-8'}</small>
                                                {/if}
                                            </td>
                                            <td>{$conversation.message_count|intval}</td>
                                            <td>
                                                {if !empty($conversation.last_message)}
                                                    <strong>{$conversation.last_message_sender|escape:'html':'UTF-8'} :</strong>
                                                    <br>
                                                    <small>{$conversation.last_message|truncate:50:'...'|escape:'html':'UTF-8'}</small>
                                                    <br>
                                                    <small class="text-muted">{$conversation.last_message_date|escape:'html':'UTF-8'}</small>
                                                {else}
                                                    <span class="text-muted">{l s='Aucun message' mod='iabot'}</span>
                                                {/if}
                                            </td>
                                            <td>{$conversation.date_upd_formatted|escape:'html':'UTF-8'}</td>
                                            <td>
                                                <a href="{$link->getAdminLink('AdminIaBotConversation')|escape:'html':'UTF-8'}&id_conversation={$conversation.id_conversation|intval}&viewiabot_conversation" class="btn btn-default btn-sm">
                                                    <i class="icon-search"></i> {l s='Voir' mod='iabot'}
                                                </a>
                                            </td>
                                        </tr>
                                    {/foreach}
                                </tbody>
                            </table>
                        </div>
                    {/if}
                </div>
            </div>
        </div>
    </div>
    
    <div class="row">
        <div class="col-md-12">
            <div class="panel">
                <div class="panel-heading">
                    <i class="icon-wrench"></i> {l s='Outils de diagnostic' mod='iabot'}
                </div>
                <div class="panel-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="alert alert-info">
                                <p>{l s='Utilisez l\'outil de diagnostic pour vérifier l\'état du module et résoudre les problèmes potentiels.' mod='iabot'}</p>
                                <a href="{$diagnostic_url|escape:'html':'UTF-8'}" class="btn btn-info" target="_blank">
                                    <i class="icon-stethoscope"></i> {l s='Accéder à l\'outil de diagnostic' mod='iabot'}
                                </a>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="alert alert-warning">
                                <p>{l s='Consultez les logs du module pour identifier les erreurs et les problèmes techniques.' mod='iabot'}</p>
                                <a href="{$link->getAdminLink('AdminLogs')|escape:'html':'UTF-8'}" class="btn btn-warning">
                                    <i class="icon-file-text"></i> {l s='Consulter les logs' mod='iabot'}
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="row">
        <div class="col-md-12">
            <div class="panel">
                <div class="panel-heading">
                    <i class="icon-search"></i> {l s='Indexation des produits' mod='iabot'}
                </div>
                <div class="panel-body">
                    <div class="row">
                        <div class="col-md-4">
                            <div class="panel">
                                <div class="panel-heading">
                                    {l s='Statistiques d\'indexation' mod='iabot'}
                                </div>
                                <div class="panel-body">
                                    <div id="indexing-stats">
                                        <div class="alert alert-info">
                                            {l s='Chargement des statistiques d\'indexation...' mod='iabot'}
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-8">
                            <div class="panel">
                                <div class="panel-heading">
                                    {l s='Gestion de l\'indexation' mod='iabot'}
                                </div>
                                <div class="panel-body">
                                    <p>{l s='L\'indexation des produits permet au chatbot de rechercher et recommander des produits aux clients.' mod='iabot'}</p>
                                    
                                    <div class="form-group">
                                        <label class="control-label">{l s='Options d\'indexation' mod='iabot'}</label>
                                        <div class="checkbox">
                                            <label>
                                                <input type="checkbox" id="force-reindex"> {l s='Forcer la réindexation complète' mod='iabot'}
                                            </label>
                                            <p class="help-block">{l s='Cochez cette option pour réindexer tous les produits, même ceux déjà indexés.' mod='iabot'}</p>
                                        </div>
                                    </div>
                                    
                                    <div class="form-group">
                                        <button type="button" id="start-indexing" class="btn btn-primary">
                                            <i class="icon-refresh"></i> {l s='Lancer l\'indexation' mod='iabot'}
                                        </button>
                                        <div id="indexing-progress" class="progress" style="display: none; margin-top: 10px;">
                                            <div class="progress-bar progress-bar-striped active" role="progressbar" style="width: 0%">
                                                <span id="progress-text">0%</span>
                                            </div>
                                        </div>
                                        <div id="indexing-result" class="alert" style="display: none; margin-top: 10px;"></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="row">
        <div class="col-md-12">
            <div class="panel">
                <div class="panel-heading">
                    <i class="icon-comments"></i> {l s='Conversations récentes' mod='iabot'}
                </div>
                <div class="panel-body">
                    {if empty($recent_conversations)}
                        <p class="text-center text-muted">{l s='Aucune conversation récente' mod='iabot'}</p>
                    {else}
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>{l s='ID' mod='iabot'}</th>
                                        <th>{l s='Client/Visiteur' mod='iabot'}</th>
                                        <th>{l s='Messages' mod='iabot'}</th>
                                        <th>{l s='Dernier message' mod='iabot'}</th>
                                        <th>{l s='Date de mise à jour' mod='iabot'}</th>
                                        <th>{l s='Actions' mod='iabot'}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {foreach from=$recent_conversations item=conversation}
                                        <tr>
                                            <td>{$conversation.id_conversation|intval}</td>
                                            <td>
                                                {$conversation.customer_name|escape:'html':'UTF-8'}
                                                {if !empty($conversation.customer_email)}
                                                    <br><small>{$conversation.customer_email|escape:'html':'UTF-8'}</small>
                                                {/if}
                                            </td>
                                            <td>{$conversation.message_count|intval}</td>
                                            <td>
                                                {if !empty($conversation.last_message)}
                                                    <strong>{$conversation.last_message_sender|escape:'html':'UTF-8'} :</strong>
                                                    <br>
                                                    <small>{$conversation.last_message|truncate:50:'...'|escape:'html':'UTF-8'}</small>
                                                    <br>
                                                    <small class="text-muted">{$conversation.last_message_date|escape:'html':'UTF-8'}</small>
                                                {else}
                                                    <span class="text-muted">{l s='Aucun message' mod='iabot'}</span>
                                                {/if}
                                            </td>
                                            <td>{$conversation.date_upd_formatted|escape:'html':'UTF-8'}</td>
                                            <td>
                                                <a href="{$link->getAdminLink('AdminIaBotConversation')|escape:'html':'UTF-8'}&id_conversation={$conversation.id_conversation|intval}&viewiabot_conversation" class="btn btn-default btn-sm">
                                                    <i class="icon-search"></i> {l s='Voir' mod='iabot'}
                                                </a>
                                            </td>
                                        </tr>
                                    {/foreach}
                                </tbody>
                            </table>
                        </div>
                    {/if}
                </div>
            </div>
        </div>
    </div>
</div>

{* Section des statistiques *}
<div class="row">
    <div class="col-md-4">
        <div class="card">
            <div class="card-header">
                <i class="icon icon-comments"></i> Conversations
            </div>
            <div class="card-body">
                <div class="stat-item">
                    <span class="stat-number">{$total_conversations}</span>
                    <span class="stat-label">Total des conversations</span>
                </div>
                <div class="stat-item">
                    <span class="stat-number">{$active_conversations}</span>
                    <span class="stat-label">Conversations actives</span>
                </div>
                <div class="stat-item">
                    <span class="stat-number">{$average_messages}</span>
                    <span class="stat-label">Messages moyens par conversation</span>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-4">
        <div class="card">
            <div class="card-header">
                <i class="icon icon-envelope"></i> Messages
            </div>
            <div class="card-body">
                <div class="stat-item">
                    <span class="stat-number">{$total_messages}</span>
                    <span class="stat-label">Total des messages</span>
                </div>
                <div class="stat-item">
                    <span class="stat-number">{if isset($messages_today)}{$messages_today}{else}0{/if}</span>
                    <span class="stat-label">Messages aujourd'hui</span>
                </div>
                <div class="stat-item">
                    <span class="stat-number">{if isset($response_time)}{$response_time}{else}0{/if} sec</span>
                    <span class="stat-label">Temps de réponse moyen</span>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-4">
        <div class="card">
            <div class="card-header">
                <i class="icon icon-cogs"></i> Configuration
            </div>
            <div class="card-body">
                <div class="form-group">
                    <label for="toggle-live-mode">Mode Live</label>
                    <div class="input-group">
                        <span class="switch prestashop-switch fixed-width-lg">
                            <input type="radio" name="live_mode" id="live_mode_on" value="1" {if $iabot_live_mode}checked="checked"{/if}>
                            <label for="live_mode_on">Oui</label>
                            <input type="radio" name="live_mode" id="live_mode_off" value="0" {if !$iabot_live_mode}checked="checked"{/if}>
                            <label for="live_mode_off">Non</label>
                            <a class="slide-button btn"></a>
                        </span>
                    </div>
                    <p class="help-block">Activer/désactiver le chatbot sur le front-office</p>
                </div>
                
                <div class="form-group">
                    <label>Couleur du chat</label>
                    <div class="input-group">
                        <input type="color" id="chat-color" value="{$iabot_chat_color}" class="form-control">
                    </div>
                </div>
                
                <div class="form-group">
                    <a href="{$diagnostic_url}" target="_blank" class="btn btn-info">
                        <i class="icon icon-stethoscope"></i> Outil de diagnostic
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

{* Section d'indexation des produits *}
<div class="row mt-4">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <i class="icon icon-search"></i> Indexation des produits
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-8">
                        <div id="indexing-stats">
                            <div class="alert alert-info">
                                Chargement des statistiques d'indexation...
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card">
                            <div class="card-header">Actions</div>
                            <div class="card-body">
                                <div class="form-group">
                                    <div class="custom-control custom-checkbox">
                                        <input type="checkbox" class="custom-control-input" id="force-reindex">
                                        <label class="custom-control-label" for="force-reindex">Forcer la réindexation de tous les produits</label>
                                    </div>
                                    <small class="form-text text-muted">Cochez cette option pour réindexer tous les produits, même ceux déjà indexés.</small>
                                </div>
                                
                                <button id="start-indexing" class="btn btn-primary btn-block">
                                    <i class="icon icon-refresh"></i> Démarrer l'indexation
                                </button>
                                
                                <div id="indexing-progress-container" class="mt-3" style="display: none;">
                                    <div class="progress">
                                        <div id="indexing-progress-bar" class="progress-bar" role="progressbar" style="width: 0%;" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100">0%</div>
                                    </div>
                                </div>
                                
                                <div id="indexing-result" class="mt-3"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script type="text/javascript">
    // URL AJAX du front-office
    var ajaxFrontUrl = '{$link->getModuleLink('iabot', 'ajax')|escape:'javascript':'UTF-8'}';
    
    // Données pour le graphique
    var chartData = {
        labels: [
            {foreach from=$stats_per_day item=stat}
                "{$stat.date|date_format:"%d/%m"|escape:'javascript':'UTF-8'}"{if !$stat@last},{/if}
            {/foreach}
        ],
        datasets: [
            {
                label: "{l s='Conversations' mod='iabot'|escape:'javascript':'UTF-8'}",
                backgroundColor: "rgba({$iabot_chat_color|escape:'javascript':'UTF-8'}, 0.2)",
                borderColor: "rgba({$iabot_chat_color|escape:'javascript':'UTF-8'}, 1)",
                borderWidth: 2,
                data: [
                    {foreach from=$stats_per_day item=stat}
                        {$stat.conversations|intval}{if !$stat@last},{/if}
                    {/foreach}
                ]
            },
            {
                label: "{l s='Messages' mod='iabot'|escape:'javascript':'UTF-8'}",
                backgroundColor: "rgba(54, 162, 235, 0.2)",
                borderColor: "rgba(54, 162, 235, 1)",
                borderWidth: 2,
                data: [
                    {foreach from=$stats_per_day item=stat}
                        {$stat.messages|intval}{if !$stat@last},{/if}
                    {/foreach}
                ]
            }
        ]
    };

    // Initialisation du graphique
    document.addEventListener('DOMContentLoaded', function() {
        var ctx = document.getElementById('iabot-activity-chart').getContext('2d');
        var myChart = new Chart(ctx, {
            type: 'line',
            data: chartData,
            options: {
                responsive: true,
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });
    });
</script>

<style type="text/css">
    .dashboard-stats .widget {
        border-radius: 5px;
        box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        margin-bottom: 20px;
    }
    
    .dashboard-stats .widget-heading {
        background-color: #f5f5f5;
        padding: 10px 15px;
        border-bottom: 1px solid #ddd;
        border-top-left-radius: 5px;
        border-top-right-radius: 5px;
        font-weight: bold;
    }
    
    .dashboard-stats .widget-body {
        padding: 15px;
    }
    
    .dashboard-stats .widget-body h3 {
        margin: 0;
        font-size: 24px;
        color: #333;
    }
</style>

{* Inclusion des scripts JavaScript *}
<script>
    var iabotToken = '{$token}';
    var iabotAjaxUrl = '{$link->getAdminLink('AdminIaBotAjax')}';
    var ajaxFrontUrl = '{$ajaxFrontUrl}';
</script>
<script src="{$module_path}views/js/admin.js"></script>
