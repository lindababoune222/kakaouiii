<?php
/**
 * Contrôleur d'administration pour la gestion des connaissances du module IaBot
 * 
 * @author  Mike
 * @copyright 2025
 */

if (!defined('_PS_VERSION_')) {
    exit;
}

// Inclusion des classes nécessaires
require_once _PS_MODULE_DIR_ . 'iabot/iabot.php';
require_once _PS_MODULE_DIR_ . 'iabot/classes/IaBotKnowledge.php';

/**
 * Contrôleur d'administration pour la gestion des connaissances du module IaBot
 */
class AdminIaBotKnowledgeController extends ModuleAdminController
{
    /**
     * Constructeur du contrôleur
     */
    public function __construct()
    {
        $this->bootstrap = true;
        $this->table = 'iabot_knowledge';
        $this->className = 'IaBotKnowledge';
        $this->lang = false;
        $this->addRowAction('edit');
        $this->addRowAction('delete');
        $this->explicitSelect = true;
        $this->allow_export = true;
        $this->deleted = false;
        $this->context = Context::getContext();
        $this->identifier = 'id_knowledge';
        $this->_defaultOrderBy = 'id_knowledge';
        $this->_defaultOrderWay = 'DESC';
        $this->meta_title = 'Gestion des connaissances IaBot';
        
        // Initialisation du module
        $this->module = Module::getInstanceByName('iabot');
        
        // Définition des champs pour la liste
        $this->fields_list = [
            'id_knowledge' => [
                'title' => 'ID',
                'align' => 'center',
                'class' => 'fixed-width-xs'
            ],
            'title' => [
                'title' => 'Titre',
                'filter_key' => 'a!title'
            ],
            'category' => [
                'title' => 'Catégorie',
                'filter_key' => 'a!category'
            ],
            'active' => [
                'title' => 'Actif',
                'align' => 'center',
                'active' => 'status',
                'type' => 'bool',
                'orderby' => false,
                'class' => 'fixed-width-sm'
            ],
            'date_add' => [
                'title' => 'Date d\'ajout',
                'type' => 'datetime'
            ],
            'date_upd' => [
                'title' => 'Date de mise à jour',
                'type' => 'datetime'
            ]
        ];
        
        // Définition des filtres de recherche
        $this->_select = 'a.id_knowledge, a.title, a.category, a.active, a.date_add, a.date_upd, a.keywords';
        
        parent::__construct();
        
        if (!$this->module->active) {
            Tools::redirectAdmin($this->context->link->getAdminLink('AdminModules'));
        }
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
                'title' => 'Connaissance',
                'icon' => 'icon-book'
            ],
            'input' => [
                [
                    'type' => 'text',
                    'label' => 'Titre',
                    'name' => 'title',
                    'required' => true,
                    'desc' => 'Titre de la connaissance (sujet principal)'
                ],
                [
                    'type' => 'text',
                    'label' => 'Catégorie',
                    'name' => 'category',
                    'required' => true,
                    'desc' => 'Catégorie de la connaissance (ex: produits, livraison, retours)'
                ],
                [
                    'type' => 'textarea',
                    'label' => 'Contenu',
                    'name' => 'content',
                    'required' => true,
                    'rows' => 10,
                    'desc' => 'Contenu détaillé de la connaissance'
                ],
                [
                    'type' => 'text',
                    'label' => 'Mots-clés',
                    'name' => 'keywords',
                    'desc' => 'Mots-clés séparés par des virgules'
                ],
                [
                    'type' => 'switch',
                    'label' => 'Actif',
                    'name' => 'active',
                    'required' => false,
                    'is_bool' => true,
                    'values' => [
                        [
                            'id' => 'active_on',
                            'value' => 1,
                            'label' => 'Oui'
                        ],
                        [
                            'id' => 'active_off',
                            'value' => 0,
                            'label' => 'Non'
                        ]
                    ],
                    'desc' => 'Activer ou désactiver cette connaissance'
                ]
            ],
            'submit' => [
                'title' => 'Enregistrer'
            ]
        ];
        
        return parent::renderForm();
    }
    
    /**
     * Traitement avant sauvegarde
     */
    public function processSave()
    {
        // Validation des entrées
        $title = Tools::getValue('title');
        $category = Tools::getValue('category');
        $content = Tools::getValue('content');
        
        if (empty($title)) {
            $this->errors[] = 'Le titre est requis';
        } elseif (strlen($title) > 255) {
            $this->errors[] = 'Le titre ne doit pas dépasser 255 caractères';
        }
        
        if (empty($category)) {
            $this->errors[] = 'La catégorie est requise';
        } elseif (strlen($category) > 128) {
            $this->errors[] = 'La catégorie ne doit pas dépasser 128 caractères';
        }
        
        if (empty($content)) {
            $this->errors[] = 'Le contenu est requis';
        }
        
        if (count($this->errors)) {
            return false;
        }
        
        // Traitement des mots-clés
        $keywords = Tools::getValue('keywords');
        if (!empty($keywords)) {
            // Normalisation des mots-clés (minuscules, sans accents, séparés par des virgules)
            $keywordsArray = array_map('trim', explode(',', $keywords));
            $keywordsArray = array_filter($keywordsArray);
            $keywords = implode(',', $keywordsArray);
        }
        
        $_POST['keywords'] = $keywords;
        
        return parent::processSave();
    }
    
    /**
     * Initialisation du contenu avant l'affichage
     */
    public function initContent()
    {
        if ($this->action == 'select_delete') {
            $this->context->smarty->assign([
                'delete_form' => true,
                'url_delete' => htmlentities($_SERVER['REQUEST_URI']),
                'boxes' => $this->boxes,
            ]);
        }
        
        parent::initContent();
    }
    
    /**
     * Initialisation de la barre d'outils
     */
    public function initToolbar()
    {
        parent::initToolbar();
        
        // Ajout du bouton d'import
        $this->page_header_toolbar_btn['import'] = [
            'href' => $this->context->link->getAdminLink('AdminIaBotKnowledge') . '&import',
            'desc' => 'Importer des connaissances',
            'icon' => 'process-icon-import'
        ];
    }
    
    /**
     * Initialisation du traitement
     */
    public function initProcess()
    {
        parent::initProcess();
        
        // Traitement de l'import
        if (Tools::isSubmit('import')) {
            $this->display = 'import';
        } elseif (Tools::isSubmit('submitImport')) {
            $this->processImport();
        }
    }
    
    /**
     * Affichage du formulaire d'import
     */
    public function renderImportForm()
    {
        $this->fields_form = [
            'legend' => [
                'title' => 'Import de connaissances',
                'icon' => 'icon-upload'
            ],
            'input' => [
                [
                    'type' => 'file',
                    'label' => 'Fichier CSV',
                    'name' => 'importFile',
                    'required' => true,
                    'desc' => 'Format: titre;catégorie;contenu;mots-clés;actif (0/1)'
                ]
            ],
            'submit' => [
                'title' => 'Importer'
            ]
        ];
        
        $this->fields_value = [];
        $this->show_toolbar = true;
        $this->toolbar_scroll = false;
        $this->toolbar_btn = [];
        
        $this->toolbar_btn['back'] = [
            'href' => $this->context->link->getAdminLink('AdminIaBotKnowledge'),
            'desc' => 'Retour à la liste'
        ];
        
        $helper = new HelperForm();
        $helper->module = $this->module;
        $helper->override_folder = 'iabot/';
        $helper->token = Tools::getAdminTokenLite('AdminIaBotKnowledge');
        $helper->currentIndex = $this->context->link->getAdminLink('AdminIaBotKnowledge');
        $helper->default_form_language = $this->context->language->id;
        $helper->allow_employee_form_lang = Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG', 0);
        $helper->title = 'Import de connaissances';
        $helper->show_toolbar = true;
        $helper->toolbar_scroll = false;
        $helper->submit_action = 'submitImport';
        $helper->toolbar_btn = $this->toolbar_btn;
        
        return $helper->generateForm([['form' => $this->fields_form]]);
    }
    
    /**
     * Traitement de l'import
     */
    private function processImport()
    {
        if (!isset($_FILES['importFile']) || empty($_FILES['importFile']['tmp_name'])) {
            $this->errors[] = 'Aucun fichier n\'a été téléchargé';
            return;
        }
        
        $extension = pathinfo($_FILES['importFile']['name'], PATHINFO_EXTENSION);
        if ($extension != 'csv') {
            $this->errors[] = 'Le fichier doit être au format CSV';
            return;
        }
        
        $handle = fopen($_FILES['importFile']['tmp_name'], 'r');
        if (!$handle) {
            $this->errors[] = 'Impossible d\'ouvrir le fichier';
            return;
        }
        
        $importCount = 0;
        $errorCount = 0;
        $line = 1;
        
        while (($data = fgetcsv($handle, 0, ';')) !== false) {
            // Vérification du nombre de colonnes
            if (count($data) < 3) {
                $this->errors[] = 'Ligne ' . $line . ' : format incorrect (minimum 3 colonnes requises)';
                $errorCount++;
                $line++;
                continue;
            }
            
            // Récupération des données
            $title = isset($data[0]) ? trim($data[0]) : '';
            $category = isset($data[1]) ? trim($data[1]) : '';
            $content = isset($data[2]) ? trim($data[2]) : '';
            $keywords = isset($data[3]) ? trim($data[3]) : '';
            $active = isset($data[4]) ? (int)$data[4] : 1;
            
            // Validation des données
            if (empty($title) || empty($category) || empty($content)) {
                $this->errors[] = 'Ligne ' . $line . ' : titre, catégorie et contenu sont requis';
                $errorCount++;
                $line++;
                continue;
            }
            
            // Création de la connaissance
            $knowledge = new IaBotKnowledge();
            $knowledge->title = $title;
            $knowledge->category = $category;
            $knowledge->content = $content;
            $knowledge->keywords = $keywords;
            $knowledge->active = $active;
            
            try {
                if ($knowledge->save()) {
                    $importCount++;
                } else {
                    $this->errors[] = 'Ligne ' . $line . ' : erreur lors de l\'enregistrement';
                    $errorCount++;
                }
            } catch (Exception $e) {
                $this->errors[] = 'Ligne ' . $line . ' : ' . $e->getMessage();
                $errorCount++;
            }
            
            $line++;
        }
        
        fclose($handle);
        
        if ($importCount > 0) {
            $this->confirmations[] = $importCount . ' connaissance(s) importée(s) avec succès';
        }
        
        if ($errorCount > 0) {
            $this->errors[] = $errorCount . ' erreur(s) durant l\'import';
        }
    }
    
    /**
     * Affichage en fonction de l'action
     */
    public function renderView()
    {
        if ($this->display == 'import') {
            return $this->renderImportForm();
        }
        
        return parent::renderView();
    }
}
