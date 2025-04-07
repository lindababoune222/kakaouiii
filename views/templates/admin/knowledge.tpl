{*
* Template pour la gestion des connaissances du module IaBot
*
* @author Mike
* @copyright 2025
*}

<div class="panel">
    <div class="panel-heading">
        <i class="icon-book"></i> {l s='Gestion des connaissances' mod='iabot'}
    </div>
    
    <div class="alert alert-info">
        <p>{l s='La base de connaissances permet au chatbot de fournir des réponses précises sur vos produits et services.' mod='iabot'}</p>
        <p>{l s='Vous pouvez ajouter des connaissances manuellement ou importer un fichier CSV.' mod='iabot'}</p>
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
    
    {if $display == 'list'}
        {$list}
    {elseif $display == 'edit'}
        {$edit_form}
    {elseif $display == 'import'}
        <div class="panel">
            <div class="panel-heading">
                <i class="icon-upload"></i> {l s='Import de connaissances' mod='iabot'}
            </div>
            
            <div class="alert alert-info">
                <p>{l s='Format du fichier CSV (séparateur: point-virgule)' mod='iabot'}</p>
                <p><code>titre;catégorie;contenu;mots-clés;actif (0/1)</code></p>
                <p>{l s='Exemple:' mod='iabot'}</p>
                <p><code>Politique de retour;Livraison;Vous disposez de 14 jours pour retourner votre produit;retour,remboursement,échange;1</code></p>
            </div>
            
            {$import_form}
        </div>
    {/if}
</div>

{if $display == 'list'}
<div class="panel">
    <div class="panel-heading">
        <i class="icon-info-circle"></i> {l s='Statistiques de la base de connaissances' mod='iabot'}
    </div>
    
    <div class="row">
        <div class="col-lg-3">
            <div class="panel">
                <div class="panel-heading">
                    {l s='Total des connaissances' mod='iabot'}
                </div>
                <h2 class="text-center">{$stats.total|intval}</h2>
            </div>
        </div>
        
        <div class="col-lg-3">
            <div class="panel">
                <div class="panel-heading">
                    {l s='Connaissances actives' mod='iabot'}
                </div>
                <h2 class="text-center">{$stats.active|intval}</h2>
            </div>
        </div>
        
        <div class="col-lg-3">
            <div class="panel">
                <div class="panel-heading">
                    {l s='Catégories' mod='iabot'}
                </div>
                <h2 class="text-center">{$stats.categories|intval}</h2>
            </div>
        </div>
        
        <div class="col-lg-3">
            <div class="panel">
                <div class="panel-heading">
                    {l s='Dernière mise à jour' mod='iabot'}
                </div>
                <h2 class="text-center">{$stats.last_update|escape:'html':'UTF-8'}</h2>
            </div>
        </div>
    </div>
    
    {if isset($stats.top_categories) && $stats.top_categories|@count > 0}
    <div class="row">
        <div class="col-lg-12">
            <h4>{l s='Principales catégories' mod='iabot'}</h4>
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>{l s='Catégorie' mod='iabot'}</th>
                            <th>{l s='Nombre d\'entrées' mod='iabot'}</th>
                        </tr>
                    </thead>
                    <tbody>
                        {foreach from=$stats.top_categories item=category}
                        <tr>
                            <td>{$category.name|escape:'html':'UTF-8'}</td>
                            <td>{$category.count|intval}</td>
                        </tr>
                        {/foreach}
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    {/if}
</div>
{/if}
