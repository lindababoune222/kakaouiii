<?php
/**
 * Contrôleur d'administration pour la gestion des recommandations du module IaBot
 * 
 * @author  Mike
 * @copyright 2025
 */

if (!defined('_PS_VERSION_')) {
    exit;
}

// Inclusion des classes nécessaires
require_once _PS_MODULE_DIR_ . 'iabot/iabot.php';
require_once _PS_MODULE_DIR_ . 'iabot/classes/IaBotRecommendation.php';

/**
 * Contrôleur d'administration pour la gestion des recommandations du module IaBot
 */
class AdminIaBotRecommendationsController extends ModuleAdminController
{
    /**
     * Constructeur du contrôleur
     */
    public function __construct()
    {
        $this->bootstrap = true;
        $this->table = 'iabot_recommendation';
        $this->className = 'IaBotRecommendation';
        $this->lang = false;
        $this->addRowAction('edit');
        $this->addRowAction('delete');
        $this->explicitSelect = true;
        $this->allow_export = true;
        $this->deleted = false;
        $this->context = Context::getContext();
        $this->identifier = 'id_recommendation';
        $this->_defaultOrderBy = 'id_recommendation';
        $this->_defaultOrderWay = 'DESC';
        $this->meta_title = 'Gestion des recommandations IaBot';
        
        // Initialisation du module
        $this->module = Module::getInstanceByName('iabot');
        
        // Définition des champs pour la liste
        $this->fields_list = [
            'id_recommendation' => [
                'title' => 'ID',
                'align' => 'center',
                'class' => 'fixed-width-xs'
            ],
            'id_product' => [
                'title' => 'ID Produit',
                'filter_key' => 'a!id_product',
                'callback' => 'getProductName'
            ],
            'keyword' => [
                'title' => 'Mot-clé',
                'filter_key' => 'a!keyword'
            ],
            'weight' => [
                'title' => 'Poids',
                'align' => 'center',
                'class' => 'fixed-width-sm'
            ],
            'date_add' => [
                'title' => 'Date d\'ajout',
                'type' => 'datetime'
            ]
        ];
        
        // Définition des filtres de recherche
        $this->_select = 'a.id_recommendation, a.id_product, a.keyword, a.weight, a.date_add';
        
        parent::__construct();
        
        if (!$this->module->active) {
            Tools::redirectAdmin($this->context->link->getAdminLink('AdminModules'));
        }
    }
    
    /**
     * Obtient le nom du produit à partir de son ID
     * 
     * @param int $idProduct ID du produit
     * @return string Nom du produit
     */
    public function getProductName($idProduct)
    {
        $product = new Product($idProduct, false, $this->context->language->id);
        if (Validate::isLoadedObject($product)) {
            return '<a href="' . $this->context->link->getAdminLink('AdminProducts') . '&id_product=' . $idProduct . '&updateproduct">' . $product->name . '</a>';
        }
        return 'Produit #' . $idProduct;
    }
    
    /**
     * Rendu du formulaire d'édition
     */
    public function renderForm()
    {
        if (!($obj = $this->loadObject(true))) {
            return;
        }
        
        $this->fields_form = [
            'legend' => [
                'title' => 'Recommandation',
                'icon' => 'icon-star'
            ],
            'input' => [
                [
                    'type' => 'select',
                    'label' => 'Produit',
                    'name' => 'id_product',
                    'required' => true,
                    'options' => [
                        'query' => $this->getProductsList(),
                        'id' => 'id',
                        'name' => 'name'
                    ]
                ],
                [
                    'type' => 'text',
                    'label' => 'Mot-clé',
                    'name' => 'keyword',
                    'required' => true,
                    'desc' => 'Mot-clé qui déclenchera cette recommandation'
                ],
                [
                    'type' => 'text',
                    'label' => 'Poids',
                    'name' => 'weight',
                    'required' => true,
                    'class' => 'fixed-width-sm',
                    'desc' => 'Poids de la recommandation (plus le poids est élevé, plus la recommandation est prioritaire)'
                ]
            ],
            'submit' => [
                'title' => 'Enregistrer'
            ]
        ];
        
        return parent::renderForm();
    }
    
    /**
     * Récupère la liste des produits pour le formulaire
     * 
     * @return array Liste des produits
     */
    protected function getProductsList()
    {
        $products = Product::getProducts($this->context->language->id, 0, 1000, 'name', 'asc');
        $productsList = [];
        
        foreach ($products as $product) {
            $productsList[] = [
                'id' => $product['id_product'],
                'name' => $product['name']
            ];
        }
        
        return $productsList;
    }
    
    /**
     * Traitement avant sauvegarde
     */
    public function processSave()
    {
        // Validation des entrées
        $idProduct = (int)Tools::getValue('id_product');
        $keyword = Tools::getValue('keyword');
        $weight = (int)Tools::getValue('weight');
        
        if (empty($idProduct)) {
            $this->errors[] = 'Vous devez sélectionner un produit';
        } else {
            $product = new Product($idProduct);
            if (!Validate::isLoadedObject($product)) {
                $this->errors[] = 'Le produit sélectionné n\'existe pas';
            }
        }
        
        if (empty($keyword)) {
            $this->errors[] = 'Le mot-clé est requis';
        } elseif (strlen($keyword) > 64) {
            $this->errors[] = 'Le mot-clé ne doit pas dépasser 64 caractères';
        }
        
        if ($weight <= 0 || $weight > 100) {
            $this->errors[] = 'Le poids doit être un nombre positif entre 1 et 100';
        }
        
        if (count($this->errors)) {
            return false;
        }
        
        return parent::processSave();
    }
}
