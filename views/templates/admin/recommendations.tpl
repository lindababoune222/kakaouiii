{*
* Template pour les recommandations du module IaBot
*
* @author Mike
* @copyright 2025
*}

<div class="panel">
    <div class="panel-heading">
        <i class="icon-lightbulb-o"></i> {l s='Recommandations de produits IaBot' mod='iabot'}
    </div>
    
    <div class="alert alert-info">
        <p>{l s='Cette page vous permet de gérer les recommandations de produits qui seront proposées par le chatbot lorsque certains mots-clés sont détectés dans les conversations.' mod='iabot'}</p>
    </div>
    
    {if isset($confirmation_message)}
        <div class="alert alert-success">
            {$confirmation_message|escape:'html':'UTF-8'}
        </div>
    {/if}
    
    <div class="row">
        <div class="col-lg-6">
            <div class="panel">
                <div class="panel-heading">
                    {l s='Ajouter une nouvelle recommandation' mod='iabot'}
                </div>
                
                <form id="add_recommendation_form" class="form-horizontal" action="{$current_url|escape:'html':'UTF-8'}" method="post">
                    <div class="form-group">
                        <label class="control-label col-lg-3 required">{l s='Produit' mod='iabot'}</label>
                        <div class="col-lg-9">
                            <select name="id_product" class="fixed-width-xl" required="required">
                                <option value="">{l s='Sélectionnez un produit' mod='iabot'}</option>
                                {foreach from=$products item=product}
                                    <option value="{$product.id|intval}">{$product.name|escape:'html':'UTF-8'}</option>
                                {/foreach}
                            </select>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label class="control-label col-lg-3 required">{l s='Mot-clé' mod='iabot'}</label>
                        <div class="col-lg-9">
                            <input type="text" name="keyword" class="form-control" required="required" maxlength="64" />
                            <p class="help-block">{l s='Mot-clé qui déclenchera cette recommandation' mod='iabot'}</p>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label class="control-label col-lg-3 required">{l s='Poids' mod='iabot'}</label>
                        <div class="col-lg-9">
                            <input type="number" name="weight" class="form-control fixed-width-sm" required="required" min="1" max="100" value="10" />
                            <p class="help-block">{l s='Plus le poids est élevé, plus la recommandation est prioritaire (1-100)' mod='iabot'}</p>
                        </div>
                    </div>
                    
                    <div class="panel-footer">
                        <button type="submit" name="submitAddRecommendation" class="btn btn-default pull-right">
                            <i class="process-icon-save"></i> {l s='Ajouter' mod='iabot'}
                        </button>
                    </div>
                </form>
            </div>
        </div>
        
        <div class="col-lg-6">
            <div class="panel">
                <div class="panel-heading">
                    {l s='Comment fonctionnent les recommandations ?' mod='iabot'}
                </div>
                
                <div class="alert alert-info">
                    <p>{l s='Le système de recommandations permet au chatbot de suggérer des produits pertinents en fonction des mots-clés détectés dans les conversations avec les clients.' mod='iabot'}</p>
                </div>
                
                <div class="recommendation-steps">
                    <div class="step">
                        <div class="step-number">1</div>
                        <div class="step-content">
                            <h4>{l s='Définissez des mots-clés' mod='iabot'}</h4>
                            <p>{l s='Associez des mots-clés pertinents à vos produits.' mod='iabot'}</p>
                        </div>
                    </div>
                    
                    <div class="step">
                        <div class="step-number">2</div>
                        <div class="step-content">
                            <h4>{l s='Le chatbot analyse les conversations' mod='iabot'}</h4>
                            <p>{l s='Lorsqu\'un client discute avec le chatbot, le système analyse les mots-clés utilisés.' mod='iabot'}</p>
                        </div>
                    </div>
                    
                    <div class="step">
                        <div class="step-number">3</div>
                        <div class="step-content">
                            <h4>{l s='Recommandations intelligentes' mod='iabot'}</h4>
                            <p>{l s='Le chatbot suggère automatiquement les produits les plus pertinents en fonction du contexte de la conversation.' mod='iabot'}</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style type="text/css">
    .recommendation-steps {
        padding: 15px;
    }
    .step {
        display: flex;
        margin-bottom: 20px;
        align-items: center;
    }
    .step-number {
        width: 40px;
        height: 40px;
        border-radius: 50%;
        background-color: #25b9d7;
        color: white;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: bold;
        font-size: 18px;
        margin-right: 15px;
    }
    .step-content {
        flex: 1;
    }
    .step-content h4 {
        margin-top: 0;
        margin-bottom: 5px;
        color: #363a41;
    }
    .step-content p {
        margin: 0;
        color: #6c868e;
    }
</style>
