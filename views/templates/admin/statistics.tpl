{*
* Template pour les statistiques du module IaBot
*
* @author Mike
* @copyright 2025
*}

<div class="panel">
    <div class="panel-heading">
        <i class="icon-bar-chart"></i> {l s='Statistiques du chatbot IaBot' mod='iabot'}
    </div>
    
    <div class="alert alert-info">
        <p>{l s='Cette page affiche les statistiques d\'utilisation du chatbot IaBot.' mod='iabot'}</p>
    </div>
    
    <div class="row">
        <div class="col-lg-3">
            <div class="panel">
                <div class="panel-heading">
                    {l s='Conversations' mod='iabot'}
                </div>
                <h2 class="text-center">{$stats.total_conversations|intval}</h2>
            </div>
        </div>
        
        <div class="col-lg-3">
            <div class="panel">
                <div class="panel-heading">
                    {l s='Messages' mod='iabot'}
                </div>
                <h2 class="text-center">{$stats.total_messages|intval}</h2>
            </div>
        </div>
        
        <div class="col-lg-3">
            <div class="panel">
                <div class="panel-heading">
                    {l s='Messages/Conversation' mod='iabot'}
                </div>
                <h2 class="text-center">{$stats.avg_messages_per_conversation|string_format:"%.1f"}</h2>
            </div>
        </div>
        
        <div class="col-lg-3">
            <div class="panel">
                <div class="panel-heading">
                    {l s='Clients' mod='iabot'}
                </div>
                <h2 class="text-center">{$stats.total_customers|intval}</h2>
            </div>
        </div>
    </div>
    
    <div class="panel">
        <div class="panel-heading">
            {l s='Activité des 30 derniers jours' mod='iabot'}
        </div>
        
        <div id="daily-stats-chart" style="height: 300px;"></div>
    </div>
    
    <div class="row">
        <div class="col-lg-6">
            <div class="panel">
                <div class="panel-heading">
                    {l s='Top 10 des mots-clés' mod='iabot'}
                </div>
                
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>{l s='Mot-clé' mod='iabot'}</th>
                                <th class="text-center">{l s='Occurrences' mod='iabot'}</th>
                            </tr>
                        </thead>
                        <tbody>
                            {foreach from=$stats.top_keywords item=keyword}
                            <tr>
                                <td>{$keyword.keyword|escape:'html':'UTF-8'}</td>
                                <td class="text-center">{$keyword.count|intval}</td>
                            </tr>
                            {/foreach}
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        
        <div class="col-lg-6">
            <div class="panel">
                <div class="panel-heading">
                    {l s='Taux de conversion' mod='iabot'}
                </div>
                
                <div class="alert alert-info">
                    <p>{l s='Pourcentage de conversations qui ont conduit à une vente.' mod='iabot'}</p>
                </div>
                
                <div class="conversion-rate-container text-center" style="padding: 20px;">
                    <div class="conversion-rate" style="font-size: 48px; font-weight: bold; color: #72C279;">
                        {$stats.conversion_rate|string_format:"%.1f"}%
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script type="text/javascript">
    $(document).ready(function() {
        // Données pour le graphique
        var dailyData = {$stats.daily|json_encode nofilter};
        
        // Préparation des données
        var dates = [];
        var conversations = [];
        var messages = [];
        
        for (var i = 0; i < dailyData.length; i++) {
            dates.push(dailyData[i].date);
            conversations.push(dailyData[i].conversations);
            messages.push(dailyData[i].messages);
        }
        
        // Création du graphique
        var ctx = document.createElement('canvas');
        ctx.id = 'activity-chart';
        ctx.height = 300;
        document.getElementById('daily-stats-chart').appendChild(ctx);
        
        new Chart(ctx, {
            type: 'line',
            data: {
                labels: dates,
                datasets: [
                    {
                        label: '{l s='Conversations' mod='iabot'}',
                        data: conversations,
                        borderColor: '#2eacce',
                        backgroundColor: 'rgba(46, 172, 206, 0.1)',
                        borderWidth: 2,
                        fill: true
                    },
                    {
                        label: '{l s='Messages' mod='iabot'}',
                        data: messages,
                        borderColor: '#72C279',
                        backgroundColor: 'rgba(114, 194, 121, 0.1)',
                        borderWidth: 2,
                        fill: true
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });
    });
</script>
