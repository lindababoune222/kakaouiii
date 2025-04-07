{*
* Template pour l'outil de correction des permissions IaBot
*}

<div class="panel">
    <div class="panel-heading">
        <i class="icon-cogs"></i> {l s='Correction des permissions IaBot' mod='iabot'}
    </div>
    
    <div class="alert alert-info">
        <p>{l s='Cet outil corrige les problèmes de permissions pour le module IaBot, permettant aux administrateurs d\'accéder à toutes les fonctionnalités du module.' mod='iabot'}</p>
        <p>{l s='Après application des corrections, vous devrez vous déconnecter et vous reconnecter pour que tous les changements prennent effet.' mod='iabot'}</p>
    </div>
    
    {if isset($results)}
        {if $results.success}
            <div class="alert alert-success">
                <h4><i class="icon-check"></i> {l s='Correction terminée avec succès!' mod='iabot'}</h4>
                <p>{l s='Les permissions ont été correctement configurées pour tous les onglets du module IaBot.' mod='iabot'}</p>
            </div>
        {else}
            <div class="alert alert-danger">
                <h4><i class="icon-warning"></i> {l s='Des erreurs sont survenues' mod='iabot'}</h4>
                <ul>
                    {foreach from=$results.errors item=error}
                        <li>{$error|escape:'html':'UTF-8'}</li>
                    {/foreach}
                </ul>
            </div>
        {/if}
        
        {if !empty($results.messages)}
            <div class="panel">
                <h3>{l s='Actions effectuées' mod='iabot'}</h3>
                <ul class="list-unstyled">
                    {foreach from=$results.messages item=message}
                        <li><i class="icon-angle-right"></i> {$message|escape:'html':'UTF-8'}</li>
                    {/foreach}
                </ul>
            </div>
        {/if}
        
        {if !empty($results.tabs)}
            <div class="panel">
                <h3>{l s='Onglets vérifiés' mod='iabot'}</h3>
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>{l s='Nom' mod='iabot'}</th>
                                <th>{l s='ID' mod='iabot'}</th>
                                <th>{l s='Statut' mod='iabot'}</th>
                            </tr>
                        </thead>
                        <tbody>
                            {foreach from=$results.tabs item=tab}
                                <tr>
                                    <td>{$tab.name|escape:'html':'UTF-8'}</td>
                                    <td>{if isset($tab.id)}{$tab.id|intval}{else}-{/if}</td>
                                    <td>
                                        {if $tab.found}
                                            <span class="badge badge-success">{l s='Trouvé' mod='iabot'}</span>
                                        {else}
                                            <span class="badge badge-danger">{l s='Non trouvé' mod='iabot'}</span>
                                        {/if}
                                    </td>
                                </tr>
                            {/foreach}
                        </tbody>
                    </table>
                </div>
            </div>
        {/if}
        
        {if !empty($results.profiles)}
            <div class="panel">
                <h3>{l s='Profils administrateur' mod='iabot'}</h3>
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>{l s='ID' mod='iabot'}</th>
                                <th>{l s='Nom' mod='iabot'}</th>
                            </tr>
                        </thead>
                        <tbody>
                            {foreach from=$results.profiles item=profile}
                                <tr>
                                    <td>{$profile.id|intval}</td>
                                    <td>{$profile.name|escape:'html':'UTF-8'}</td>
                                </tr>
                            {/foreach}
                        </tbody>
                    </table>
                </div>
            </div>
        {/if}
        
        {if !empty($results.employees)}
            <div class="panel">
                <h3>{l s='Employés administrateur' mod='iabot'}</h3>
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>{l s='ID' mod='iabot'}</th>
                                <th>{l s='Nom' mod='iabot'}</th>
                                <th>{l s='Email' mod='iabot'}</th>
                                <th>{l s='ID Profil' mod='iabot'}</th>
                            </tr>
                        </thead>
                        <tbody>
                            {foreach from=$results.employees item=employee}
                                <tr>
                                    <td>{$employee.id|intval}</td>
                                    <td>{$employee.name|escape:'html':'UTF-8'}</td>
                                    <td>{$employee.email|escape:'html':'UTF-8'}</td>
                                    <td>{$employee.profile_id|intval}</td>
                                </tr>
                            {/foreach}
                        </tbody>
                    </table>
                </div>
            </div>
        {/if}
    {/if}
    
    <div class="panel-footer">
        <a href="{$admin_link|escape:'html':'UTF-8'}" class="btn btn-default">
            <i class="process-icon-back"></i> {l s='Retour à la liste des modules' mod='iabot'}
        </a>
    </div>
</div>
