{*
* Template pour l'optimisation des produits du module IaBot
*
* @author Mike
* @copyright 2025
*}

<div class="panel">
    <div class="panel-heading">
        <i class="icon-magic"></i> {l s='Optimisation des descriptions de produits' mod='iabot'}
    </div>
    
    <div class="alert alert-info">
        <p>{l s='Cette page vous permet d\'améliorer automatiquement les descriptions de vos produits pour le référencement (SEO) grâce à l\'intelligence artificielle.' mod='iabot'}</p>
        <p>{l s='Sélectionnez les produits à améliorer puis cliquez sur le bouton "Améliorer les produits sélectionnés".' mod='iabot'}</p>
    </div>
    
    {if isset($confirmation_message)}
        <div class="alert alert-success">
            {$confirmation_message|escape:'html':'UTF-8'}
        </div>
    {/if}
    
    {if isset($error_message)}
        <div class="alert alert-danger">
            {$error_message|escape:'html':'UTF-8'}
        </div>
    {/if}
    
    <div class="row">
        <div class="col-lg-12">
            <div class="panel">
                <div class="panel-heading">
                    {l s='Sélection des produits à améliorer' mod='iabot'}
                </div>
                
                <div class="table-responsive">
                    <table class="table product-selection-table">
                        <thead>
                            <tr>
                                <th class="text-center">
                                    <input type="checkbox" id="select-all-products" title="{l s='Sélectionner tous' mod='iabot'}" />
                                </th>
                                <th>{l s='ID' mod='iabot'}</th>
                                <th>{l s='Image' mod='iabot'}</th>
                                <th>{l s='Nom' mod='iabot'}</th>
                                <th>{l s='Référence' mod='iabot'}</th>
                                <th>{l s='Catégorie' mod='iabot'}</th>
                                <th>{l s='Prix' mod='iabot'}</th>
                                <th>{l s='Qualité SEO' mod='iabot'}</th>
                            </tr>
                        </thead>
                        <tbody>
                            {foreach from=$products item=product}
                                <tr>
                                    <td class="text-center">
                                        <input type="checkbox" name="product_ids[]" value="{$product.id_product|intval}" />
                                    </td>
                                    <td>{$product.id_product|intval}</td>
                                    <td>
                                        {if isset($product.image)}
                                            <img src="{$product.image|escape:'html':'UTF-8'}" alt="{$product.name|escape:'html':'UTF-8'}" class="img-thumbnail" style="max-width: 50px;" />
                                        {else}
                                            <span class="text-muted">--</span>
                                        {/if}
                                    </td>
                                    <td>
                                        <a href="{$product.edit_url|escape:'html':'UTF-8'}" target="_blank">
                                            {$product.name|escape:'html':'UTF-8'}
                                        </a>
                                    </td>
                                    <td>{$product.reference|escape:'html':'UTF-8'}</td>
                                    <td>{$product.category_name|escape:'html':'UTF-8'}</td>
                                    <td>{$product.price|escape:'html':'UTF-8'}</td>
                                    <td>
                                        <div class="seo-quality-indicator">
                                            <div class="progress">
                                                <div class="progress-bar {if $product.seo_score < 30}progress-bar-danger{elseif $product.seo_score < 70}progress-bar-warning{else}progress-bar-success{/if}" 
                                                     role="progressbar" aria-valuenow="{$product.seo_score|intval}" 
                                                     aria-valuemin="0" aria-valuemax="100" 
                                                     style="width: {$product.seo_score|intval}%;">
                                                    {$product.seo_score|intval}%
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            {foreachelse}
                                <tr>
                                    <td colspan="8" class="text-center">
                                        <p class="alert alert-warning">{l s='Aucun produit disponible.' mod='iabot'}</p>
                                    </td>
                                </tr>
                            {/foreach}
                        </tbody>
                    </table>
                </div>
                
                <div class="panel-footer">
                    <button type="button" id="optimize-selected-products" class="btn btn-primary">
                        <i class="icon-magic"></i> {l s='Améliorer les produits sélectionnés' mod='iabot'}
                    </button>
                </div>
            </div>
        </div>
    </div>
    
    <div class="row">
        <div class="col-lg-12">
            <div class="panel">
                <div class="panel-heading">
                    {l s='Options d\'optimisation' mod='iabot'}
                </div>
                
                <form id="optimization-options-form" class="form-horizontal">
                    <div class="form-group">
                        <label class="control-label col-lg-3">{l s='Longueur de la description courte' mod='iabot'}</label>
                        <div class="col-lg-9">
                            <div class="input-group">
                                <span class="input-group-addon">{l s='Lignes' mod='iabot'}</span>
                                <input type="number" name="short_description_lines" id="short_description_lines" class="form-control" value="4" min="1" max="10" />
                            </div>
                            <p class="help-block">{l s='Nombre de lignes pour la description courte (4 lignes recommandées)' mod='iabot'}</p>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label class="control-label col-lg-3">{l s='Longueur de la description longue' mod='iabot'}</label>
                        <div class="col-lg-9">
                            <div class="input-group">
                                <span class="input-group-addon">{l s='Lignes' mod='iabot'}</span>
                                <input type="number" name="long_description_lines" id="long_description_lines" class="form-control" value="15" min="5" max="30" />
                            </div>
                            <p class="help-block">{l s='Nombre de lignes pour la description longue (15 lignes recommandées)' mod='iabot'}</p>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label class="control-label col-lg-3">{l s='Niveau d\'optimisation SEO' mod='iabot'}</label>
                        <div class="col-lg-9">
                            <div class="input-group">
                                <input type="range" name="seo_level" id="seo_level" min="1" max="10" value="7" class="form-control" oninput="document.getElementById('seo_level_value').textContent = this.value" />
                                <span class="input-group-addon" id="seo_level_value">7</span>
                            </div>
                            <p class="help-block">{l s='Niveau d\'optimisation SEO (1 = minimal, 10 = maximal). Attention : un niveau trop élevé peut conduire à une suroptimisation.' mod='iabot'}</p>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label class="control-label col-lg-3">{l s='Mots-clés supplémentaires' mod='iabot'}</label>
                        <div class="col-lg-9">
                            <input type="text" name="additional_keywords" id="additional_keywords" class="form-control" placeholder="{l s='Mots-clés séparés par des virgules' mod='iabot'}" />
                            <p class="help-block">{l s='Mots-clés supplémentaires à inclure dans l\'optimisation (facultatif)' mod='iabot'}</p>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<style type="text/css">
    .product-selection-table {
        margin-bottom: 0;
    }
    .seo-quality-indicator {
        width: 100%;
        max-width: 150px;
    }
    .btn-active {
        background-color: #5bc0de;
        color: #fff;
    }
</style>

<script type="text/javascript">
    // Variables pour les requêtes AJAX
    var ajaxFrontUrl = '{$ajax_url|escape:'javascript':'UTF-8'}';
</script>